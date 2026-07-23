"""
Resume Upload Demo — Frontend + Stripping Module, Wired Together
--------------------------------------------------------------------
This is the smallest possible working version of "resume goes in,
stripped text comes out" — a simple upload form on one end, and your
existing strip_pii.py logic doing the actual work on the other.

This is a local test tool, not the real backend. It does NOT call
Claude and does NOT save anything to a real database — it just proves
the upload -> extract -> strip pipeline works end to end, using a
temporary in-memory "database" (a Python dict) instead of a real one.

HOW TO USE
    pip install flask pdfplumber
    python3 app.py
    then open http://127.0.0.1:5000 in your browser
"""

import os
import uuid
from flask import Flask, request, render_template_string, jsonify
from werkzeug.utils import secure_filename
from dotenv import load_dotenv

load_dotenv()  # reads .env in this same folder and loads ANTHROPIC_API_KEY

from strip_pii import extract_text_from_pdf, strip_pii
from candidate_snapshot import generate_snapshot, DEFAULT_TEST_ROLE

# Placeholder until real job postings exist (Hariz's job-tabs feature) —
# each job will eventually carry its own role_description from the
# Jobs table, per the one-module-many-roles design.
CURRENT_ROLE_TITLE = "Customer Support Executive"  # shown on screen
CURRENT_ROLE = DEFAULT_TEST_ROLE  # actual text sent to the AI

app = Flask(__name__)
app.config["MAX_CONTENT_LENGTH"] = 5 * 1024 * 1024  # reject anything over 5MB
UPLOAD_FOLDER = "uploads"
os.makedirs(UPLOAD_FOLDER, exist_ok=True)

# Stand-in for a real database — in the real system, this is what
# Daniel's backend would store per candidate_id.
candidate_records = {}

UPLOAD_PAGE = """
<!DOCTYPE html>
<html>
<head>
  <title>Resume Upload — Bulk Demo</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 640px; margin: 60px auto; color: #1a1a1a; }
    h1 { font-size: 20px; }
    p.sub { color: #666; font-size: 14px; }
    input[type=file] { margin: 20px 0; }
    button { padding: 8px 20px; font-size: 14px; cursor: pointer; }
  </style>
</head>
<body>
  <h1>Upload resumes (PDF) — one or many at once</h1>
  <p class="sub">Select multiple files (ctrl/cmd+click, or shift+click for a range) to test bulk processing. Each resume is handled independently — one bad file won't stop the rest.</p>
  <form action="/upload" method="post" enctype="multipart/form-data">
    <input type="file" name="resumes" accept="application/pdf" multiple required>
    <br>
    <button type="submit">Upload &amp; Process All</button>
  </form>
</body>
</html>
"""

BULK_RESULTS_PAGE = """
<!DOCTYPE html>
<html>
<head>
  <title>Bulk upload results</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 760px; margin: 40px auto; color: #1a1a1a; }
    h1 { font-size: 20px; }
    .stat { color: #666; font-size: 14px; margin-bottom: 1.5rem; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 14px 18px; margin-bottom: 10px; }
    .card.errored { border-color: #e0b3b3; background: #fcebeb; }
    .id-tag { font-family: monospace; background: #f1efe8; padding: 2px 8px; border-radius: 6px; font-size: 12px; }
    .label { font-weight: 600; font-size: 13px; color: #6c4fd6; }
    .note { font-size: 13px; margin: 6px 0; }
    .q { font-size: 12px; color: #666; }
    .err { font-size: 13px; color: #791f1f; }
    a { font-size: 13px; }
  </style>
</head>
<body>
  <h1>Bulk upload results</h1>
  <p class="stat">Evaluated against: <strong>{{ role_title }}</strong></p>
  <p class="stat">{{ results|length }} file(s) submitted — {{ ok_count }} processed, {{ error_count }} failed. Nobody is hidden below, even the failures.</p>

  {% for r in results %}
  <div class="card {{ 'errored' if r.error else '' }}">
    <div style="display:flex; justify-content:space-between; align-items:center;">
      <strong>{{ r.filename }}</strong>
      {% if r.candidate_id %}<span class="id-tag">{{ r.candidate_id }}</span>{% endif %}
    </div>
    {% if r.error %}
      <p class="err">{{ r.error }}</p>
    {% else %}
      {% if r.snapshot %}
        <p class="label">{{ r.snapshot.relevance_label }}</p>
        <p class="note">{{ r.snapshot.note }}</p>
        <p class="q">Suggested question: {{ r.snapshot.suggested_question }}</p>
      {% elif r.snapshot_error %}
        <p class="err">{{ r.snapshot_error }}</p>
      {% endif %}
      <p><a href="/candidate/{{ r.candidate_id }}">View full record &rarr;</a></p>
    {% endif %}
  </div>
  {% endfor %}

  <p><a href="/">&larr; Upload more resumes</a></p>
</body>
</html>
"""

ERROR_PAGE = """
<!DOCTYPE html>
<html>
<head>
  <title>Upload problem</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 560px; margin: 60px auto; color: #1a1a1a; }
    .box { background: #fcebeb; border-radius: 8px; padding: 16px 20px; color: #791f1f; }
    a { font-size: 13px; }
  </style>
</head>
<body>
  <h1>Couldn't process that file</h1>
  <div class="box">{{ message }}</div>
  <p><a href="/">&larr; Try a different file</a></p>
</body>
</html>
"""

RESULT_PAGE = """
<!DOCTYPE html>
<html>
<head>
  <title>Result — {{ candidate_id }}</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 1100px; margin: 40px auto; color: #1a1a1a; padding: 0 20px; }
    h1 { font-size: 20px; }
    .id-tag { font-family: monospace; background: #f1efe8; padding: 3px 8px; border-radius: 6px; font-size: 13px; }
    .side-by-side { display: flex; gap: 16px; align-items: stretch; margin-top: 16px; }
    .side-by-side .panel { flex: 1; min-width: 0; margin-top: 0; }
    .panel { border: 1px solid #ddd; border-radius: 8px; padding: 16px 20px; margin-top: 16px; }
    .panel h2 { font-size: 14px; margin: 0 0 10px; }
    .full h2 { color: #444; }
    .stripped h2 { color: #0070c0; }
    pre { white-space: pre-wrap; word-wrap: break-word; font-size: 13px; line-height: 1.5; }
    .note { font-size: 12px; color: #777; margin-top: 6px; }
    a { font-size: 13px; }
    @media (max-width: 800px) {
      .side-by-side { flex-direction: column; }
    }
  </style>
</head>
<body>
  <h1>Processed: <span class="id-tag">{{ candidate_id }}</span></h1>
  <p style="font-size:13px; color:#666;">Evaluated against: <strong>{{ role_title }}</strong></p>

  <div class="side-by-side">
    <div class="panel full">
      <h2>FULL TEXT — would be stored in the database for HR</h2>
      <pre>{{ full_text }}</pre>
    </div>

    <div class="panel stripped">
      <h2>STRIPPED TEXT — the ONLY version that would be sent to Claude</h2>
      <pre>{{ stripped_text }}</pre>
      <p class="note">Name, IC, phone, and email are removed before this ever reaches the AI.</p>
    </div>
  </div>

  {% if snapshot_error %}
  <div class="panel" style="border-color:#e0b3b3;">
    <h2 style="color:#a33;">AI SNAPSHOT — could not run</h2>
    <p style="font-size:13px;">{{ snapshot_error }}</p>
  </div>
  {% elif snapshot %}
  <div class="panel" style="border-color:#a98cf5;">
    <h2 style="color:#6c4fd6;">AI SNAPSHOT — this is what HR would see</h2>
    <p style="font-size:14px; font-weight:600; margin:0 0 8px;">{{ snapshot.relevance_label }}</p>
    <p style="font-size:13px; margin:0 0 10px;">{{ snapshot.note }}</p>
    <p style="font-size:13px; color:#555; border-top:1px solid #eee; padding-top:8px; margin:0;">
      Suggested question: {{ snapshot.suggested_question }}
    </p>
  </div>
  {% endif %}

  <p><a href="/">&larr; Upload another resume</a></p>
</body>
</html>
"""


@app.errorhandler(413)
def file_too_large(e):
    return render_template_string(
        ERROR_PAGE,
        message="That file is over the 5MB limit. Please upload a smaller PDF.",
    ), 413


@app.route("/candidate/<candidate_id>")
def view_candidate(candidate_id):
    """
    Proves step 5-6 from the flow diagram actually work: look up a
    candidate AFTER the initial upload and confirm the snapshot is
    still attached to their record, not just shown once and lost.
    """
    record = candidate_records.get(candidate_id)
    if not record:
        return render_template_string(ERROR_PAGE, message=f"No record found for {candidate_id}.")

    snapshot = record.get("snapshot")
    return render_template_string(
        RESULT_PAGE,
        candidate_id=candidate_id,
        full_text=record["full_text"],
        stripped_text=record["stripped_text"],
        snapshot=snapshot,
        snapshot_error=None if snapshot else "No snapshot attached to this record yet.",
        role_title=CURRENT_ROLE_TITLE,
    )


@app.route("/")
def index():
    return render_template_string(UPLOAD_PAGE)


def process_one_file(file) -> dict:
    """
    Handles exactly one uploaded resume, start to finish. Returns a dict
    describing what happened — success or failure — but never raises.
    This is what makes bulk upload safe: one bad file in a batch of 10
    can't crash the other 9, because every failure is caught here and
    turned into a result entry instead of an exception.
    """
    filename = secure_filename(file.filename)
    result = {"filename": filename, "candidate_id": None, "error": None,
              "snapshot": None, "snapshot_error": None}

    save_path = os.path.join(UPLOAD_FOLDER, filename)
    file.save(save_path)

    candidate_id = f"candidate_{uuid.uuid4().hex[:8]}"
    result["candidate_id"] = candidate_id

    try:
        full_text = extract_text_from_pdf(save_path)
    except ValueError as e:
        result["error"] = str(e)
        return result

    if not full_text.strip():
        result["error"] = (
            "This PDF has no readable text (may be a scanned image "
            "with no text layer). Needs manual review."
        )
        return result

    stripped_text = strip_pii(full_text)
    candidate_records[candidate_id] = {
        "full_text": full_text,
        "stripped_text": stripped_text,
    }

    if not os.environ.get("ANTHROPIC_API_KEY"):
        result["snapshot_error"] = "No ANTHROPIC_API_KEY set — AI step skipped."
    else:
        try:
            snapshot = generate_snapshot(stripped_text, CURRENT_ROLE)
            if "error" in snapshot:
                result["snapshot_error"] = f"AI response could not be parsed: {snapshot.get('raw', '')}"
            else:
                result["snapshot"] = snapshot
                candidate_records[candidate_id]["snapshot"] = snapshot
        except Exception as e:
            result["snapshot_error"] = f"AI call failed: {type(e).__name__}: {e}"

    return result


@app.route("/upload", methods=["POST"])
def upload():
    files = request.files.getlist("resumes")
    if not files:
        return render_template_string(ERROR_PAGE, message="No files were uploaded.")

    results = [process_one_file(f) for f in files]

    ok_count = sum(1 for r in results if not r["error"])
    error_count = sum(1 for r in results if r["error"])

    return render_template_string(
        BULK_RESULTS_PAGE,
        results=results,
        ok_count=ok_count,
        error_count=error_count,
        role_title=CURRENT_ROLE_TITLE,
    )


@app.route("/api/evaluate", methods=["POST"])
def api_evaluate():
    """
    The real bridge between the Laravel frontend and this Python AI logic.
    Laravel's backend (not the browser) calls this directly, server-to-server
    — no CORS needed, since CORS only applies to browser-initiated requests.

    Expects: a multipart POST with a file field named "resume" and a form
    field "role_description". Returns JSON, not HTML, since this is meant
    to be consumed by code (Laravel), not viewed directly by a person.
    """
    file = request.files.get("resume")
    if not file:
        return jsonify({"error": "No resume file uploaded"}), 400

    role_description = request.form.get("role_description", DEFAULT_TEST_ROLE)

    filename = secure_filename(file.filename)
    save_path = os.path.join(UPLOAD_FOLDER, filename)
    file.save(save_path)

    candidate_id = f"candidate_{uuid.uuid4().hex[:8]}"

    try:
        full_text = extract_text_from_pdf(save_path)
    except ValueError as e:
        return jsonify({"error": str(e)}), 400

    if not full_text.strip():
        return jsonify({"error": "This PDF has no readable text (may be a scanned image)."}), 400

    stripped_text = strip_pii(full_text)
    candidate_records[candidate_id] = {"full_text": full_text, "stripped_text": stripped_text}

    if not os.environ.get("ANTHROPIC_API_KEY"):
        return jsonify({
            "candidate_id": candidate_id,
            "error": "No ANTHROPIC_API_KEY set on the server — AI step skipped.",
        }), 200

    try:
        snapshot = generate_snapshot(stripped_text, role_description)
        if "error" in snapshot:
            return jsonify({"candidate_id": candidate_id, "error": "AI response could not be parsed."}), 200
        candidate_records[candidate_id]["snapshot"] = snapshot
        return jsonify({
            "candidate_id": candidate_id,
            "relevance_label": snapshot["relevance_label"],
            "note": snapshot["note"],
            "suggested_question": snapshot["suggested_question"],
        }), 200
    except Exception as e:
        return jsonify({"candidate_id": candidate_id, "error": f"AI call failed: {e}"}), 200


if __name__ == "__main__":
    # use_reloader=False avoids a known Windows issue where the debug
    # reloader's internal socket gets blocked by Windows/antivirus
    # permissions ("access forbidden" error). Debug info still shows,
    # just without the auto-restart-on-file-change behavior.
    #
    # port=5050 instead of the default 5000 — Windows (via Hyper-V/WSL)
    # sometimes reserves port 5000 for its own internal use, which
    # causes exactly this kind of "access forbidden" error for any
    # regular app trying to bind to it.
    app.run(debug=True, use_reloader=False, port=5050)