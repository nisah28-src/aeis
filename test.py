import os, json, re, sqlite3, uuid
import smtplib, secrets
from datetime import datetime
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from flask import Flask, request, jsonify, send_file
from flask_cors import CORS
import anthropic

try:
    import pdfplumber
    PDF_OK = True
except:
    PDF_OK = False

try:
    from docx import Document as DocxDoc
    DOCX_OK = True
except:
    DOCX_OK = False

app = Flask(__name__)
CORS(app)
BASE_DIR   = os.path.dirname(os.path.abspath(__file__))
UPLOAD_DIR = os.path.join(BASE_DIR, "uploads")
DB_PATH    = os.path.join(BASE_DIR, "resumes.db")
os.makedirs(UPLOAD_DIR, exist_ok=True)

def get_db():
    conn = get_db()
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    conn = get_db()
    conn.execute("""CREATE TABLE IF NOT EXISTS candidates (
        id TEXT PRIMARY KEY,
        name TEXT DEFAULT 'Unknown',
        email TEXT DEFAULT '',
        phone TEXT DEFAULT '',
        skills TEXT DEFAULT '[]',
        education TEXT DEFAULT '',
        experience TEXT DEFAULT '',
        raw_text TEXT DEFAULT '',
        filename TEXT DEFAULT '',
        filepath TEXT DEFAULT '',
        uploaded_at TEXT,
        job_title TEXT DEFAULT '',
        job_desc TEXT DEFAULT '',
        overall_score REAL DEFAULT 0,
        skills_match REAL DEFAULT 0,
        exp_match REAL DEFAULT 0,
        edu_match REAL DEFAULT 0,
        strengths TEXT DEFAULT '[]',
        gaps TEXT DEFAULT '[]',
        recommendation TEXT DEFAULT 'Pending',
        summary TEXT DEFAULT '',
        status TEXT DEFAULT 'Applied',
        rank INTEGER DEFAULT 0,
        screened INTEGER DEFAULT 0
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS job_postings (
        id TEXT PRIMARY KEY,
        job_title TEXT NOT NULL,
        department TEXT DEFAULT '',
        location TEXT DEFAULT '',
        employment_type TEXT DEFAULT 'Full-time',
        experience_level TEXT DEFAULT '',
        salary_range TEXT DEFAULT '',
        responsibilities TEXT DEFAULT '',
        requirements TEXT DEFAULT '',
        nice_to_have TEXT DEFAULT '',
        status TEXT DEFAULT 'Active',
        created_at TEXT,
        updated_at TEXT
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS questionnaires (
        id TEXT PRIMARY KEY,
        title TEXT NOT NULL,
        description TEXT DEFAULT '',
        job_title TEXT DEFAULT '',
        status TEXT DEFAULT 'Active',
        created_at TEXT,
        updated_at TEXT
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS questionnaire_questions (
        id TEXT PRIMARY KEY,
        questionnaire_id TEXT NOT NULL,
        question_text TEXT NOT NULL,
        question_type TEXT NOT NULL,
        options TEXT DEFAULT '[]',
        required INTEGER DEFAULT 1,
        order_index INTEGER DEFAULT 0,
        FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS candidate_questionnaires (
        id TEXT PRIMARY KEY,
        candidate_id TEXT NOT NULL,
        questionnaire_id TEXT NOT NULL,
        token TEXT UNIQUE NOT NULL,
        sent_at TEXT,
        completed_at TEXT,
        status TEXT DEFAULT 'Sent',
        FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
        FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS questionnaire_responses (
        id TEXT PRIMARY KEY,
        candidate_questionnaire_id TEXT NOT NULL,
        question_id TEXT NOT NULL,
        answer TEXT DEFAULT '',
        submitted_at TEXT,
        FOREIGN KEY (candidate_questionnaire_id) REFERENCES candidate_questionnaires(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES questionnaire_questions(id) ON DELETE CASCADE
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT DEFAULT ''
    )""")
    conn.commit()
    conn.close()

init_db()

# ── helpers ──────────────────────────────────────────────────
def extract_text(filepath, filename):
    ext = filename.lower().rsplit(".", 1)[-1] if "." in filename else ""
    if ext == "pdf" and PDF_OK:
        try:
            text = ""
            with pdfplumber.open(filepath) as pdf:
                for page in pdf.pages:
                    text += (page.extract_text() or "") + "\n"
            return text.strip()
        except Exception as e:
            print(f"PDF error: {e}", flush=True)
    if ext in ("docx", "doc") and DOCX_OK:
        try:
            doc = DocxDoc(filepath)
            return "\n".join(p.text for p in doc.paragraphs).strip()
        except Exception as e:
            print(f"DOCX error: {e}", flush=True)
    return ""

def ai_parse(client, raw_text, filename):
    name_guess = os.path.splitext(filename)[0].replace("_", " ").replace("-", " ").title()
    if not raw_text.strip():
        return {"name": name_guess, "email": "", "phone": "", "skills": [], "education": "", "experience": ""}
    try:
        msg = client.messages.create(
            model="claude-opus-4-5", max_tokens=600,
            messages=[{"role": "user", "content": f"""Extract info from this resume. Return ONLY valid JSON, no other text:
{{"name":"<full name or '{name_guess}'>","email":"<email or empty string>","phone":"<phone or empty string>","skills":["skill1","skill2","skill3"],"education":"<degree and institution>","experience":"<years and most recent role>"}}
RESUME TEXT:
{raw_text[:4000]}"""}])
        text = msg.content[0].text.strip()
        m = re.search(r'\{.*\}', text, re.DOTALL)
        return json.loads(m.group() if m else text)
    except Exception as e:
        print(f"Parse error: {e}", flush=True)
        return {"name": name_guess, "email": "", "phone": "", "skills": [], "education": "", "experience": ""}

def ai_screen(client, candidate, job_title, job_desc):
    try:
        skills_str = ", ".join(candidate.get("skills", []))
        msg = client.messages.create(
            model="claude-opus-4-5", max_tokens=1024,
            messages=[{"role": "user", "content": f"""You are an expert HR recruiter. Evaluate this candidate for the job below.
JOB TITLE: {job_title}
JOB DESCRIPTION: {job_desc}
CANDIDATE: {candidate.get('name', 'Unknown')}
SKILLS: {skills_str}
EDUCATION: {candidate.get('education', '')}
EXPERIENCE: {candidate.get('experience', '')}
RESUME TEXT: {candidate.get('raw_text', '')[:3000]}
Return ONLY valid JSON:
{{"overall_score":<0-100>,"skills_match":<0-100>,"experience_match":<0-100>,"education_match":<0-100>,"strengths":["s1","s2","s3"],"gaps":["g1","g2"],"recommendation":"<Strong Hire|Hire|Maybe|Pass>","summary":"<2-3 sentence summary of candidate fit>"}}"""}])
        text = msg.content[0].text.strip()
        m = re.search(r'\{.*\}', text, re.DOTALL)
        return json.loads(m.group() if m else text)
    except Exception as e:
        print(f"Screen error: {e}", flush=True)
        return {"overall_score": 0, "skills_match": 0, "experience_match": 0, "education_match": 0,
                "strengths": [], "gaps": [str(e)], "recommendation": "Pass", "summary": f"Error: {e}"}

def rerank(conn):
    rows = conn.execute("SELECT id FROM candidates WHERE screened=1 ORDER BY overall_score DESC").fetchall()
    for i, row in enumerate(rows, 1):
        conn.execute("UPDATE candidates SET rank=? WHERE id=?", (i, row["id"]))
    conn.commit()

def get_setting(key, default=""):
    conn = get_db()
    row  = conn.execute("SELECT value FROM settings WHERE key=?", (key,)).fetchone()
    conn.close()
    return row["value"] if row else default

# ════════════════════════════════════════════════════════════
# CORE ROUTES
# ════════════════════════════════════════════════════════════

@app.route("/health")
def health():
    return jsonify({"status": "ok", "pdf": PDF_OK, "docx": DOCX_OK}), 200

@app.route("/upload", methods=["POST"])
def upload():
    api_key   = request.form.get("api_key", "") or os.environ.get("ANTHROPIC_API_KEY", "")
    job_id    = request.form.get("job_id", "")
    job_title = request.form.get("job_title", "")
    job_desc  = request.form.get("job_desc", "")
    # If job_id provided, fetch job details from DB
    if job_id:
        conn_tmp = get_db()
        job_row  = conn_tmp.execute("SELECT * FROM job_postings WHERE id=?", (job_id,)).fetchone()
        conn_tmp.close()
        if job_row:
            job_row   = dict(job_row)
            job_title = job_row.get("job_title", "")
            job_desc  = (job_row.get("responsibilities","") or "") + " " + (job_row.get("requirements","") or "")
    if not api_key:
        return jsonify({"error": "No API key provided"}), 400
    files = request.files.getlist("resumes")
    if not files:
        return jsonify({"error": "No files received"}), 400
    client   = anthropic.Anthropic(api_key=api_key)
    uploaded = []
    for f in files:
        fname = f.filename or ""
        ext   = fname.lower().rsplit(".", 1)[-1] if "." in fname else ""
        if ext not in ("pdf", "docx", "doc"):
            continue
        cid      = str(uuid.uuid4())
        savepath = os.path.join(UPLOAD_DIR, f"{cid}.{ext}")
        f.save(savepath)
        raw  = extract_text(savepath, fname)
        info = ai_parse(client, raw, fname)
        conn = get_db()
        conn.execute("""INSERT INTO candidates
            (id,job_id,name,email,phone,skills,education,experience,raw_text,filename,filepath,uploaded_at,job_title,job_desc)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)""",
            (cid, job_id, info.get("name","Unknown"), info.get("email",""), info.get("phone",""),
             json.dumps(info.get("skills",[])), info.get("education",""), info.get("experience",""),
             raw, fname, savepath, datetime.now().isoformat(), job_title, job_desc))
        conn.commit()
        conn.close()
        uploaded.append({"id": cid, "name": info.get("name","Unknown"), "email": info.get("email",""),
            "phone": info.get("phone",""), "skills": info.get("skills",[]),
            "education": info.get("education",""), "experience": info.get("experience",""),
            "filename": fname, "status": "Review", "screened": False})
        print(f"Uploaded: {info.get('name')} ({fname})", flush=True)
    return jsonify({"uploaded": uploaded, "count": len(uploaded)}), 200

@app.route("/screen-all", methods=["POST"])
def screen_all():
    data      = request.get_json() or {}
    api_key   = data.get("api_key", "") or os.environ.get("ANTHROPIC_API_KEY", "")
    job_title = data.get("job_title", "")
    job_desc  = data.get("job_desc", "")
    if not api_key:
        return jsonify({"error": "No API key"}), 400
    client = anthropic.Anthropic(api_key=api_key)
    conn   = get_db()
    rows   = conn.execute("SELECT * FROM candidates WHERE screened=0").fetchall()
    results = []
    for row in rows:
        c = dict(row)
        c["skills"] = json.loads(c.get("skills") or "[]")
        score = ai_screen(client, c, job_title or c["job_title"], job_desc or c["job_desc"])
        conn.execute("""UPDATE candidates SET overall_score=?,skills_match=?,exp_match=?,edu_match=?,
            strengths=?,gaps=?,recommendation=?,summary=?,screened=1 WHERE id=?""",
            (score["overall_score"], score["skills_match"], score["experience_match"], score["education_match"],
             json.dumps(score["strengths"]), json.dumps(score["gaps"]),
             score["recommendation"], score["summary"], c["id"]))
        conn.commit()
        results.append({**c, **score})
        print(f"Screened: {c['name']} -> {score['overall_score']:.0f} | {score['recommendation']}", flush=True)
    rerank(conn)
    conn.close()
    return jsonify({"results": results, "count": len(results)}), 200

@app.route("/candidates")
def get_candidates():
    search = request.args.get("search", "").lower()
    status = request.args.get("status", "")
    rec    = request.args.get("recommendation", "")
    conn   = get_db()
    rows   = conn.execute("SELECT * FROM candidates ORDER BY rank ASC, overall_score DESC").fetchall()
    conn.close()
    out = []
    for row in rows:
        c = dict(row)
        c["skills"]    = json.loads(c.get("skills")    or "[]")
        c["strengths"] = json.loads(c.get("strengths") or "[]")
        c["gaps"]      = json.loads(c.get("gaps")      or "[]")
        if search and search not in (c.get("name","") + " " + c.get("email","")).lower():
            continue
        if status and c.get("status") != status:
            continue
        if rec and c.get("recommendation") != rec:
            continue
        job_id_filter = request.args.get("job_id", "")
        if job_id_filter and c.get("job_id","") != job_id_filter:
            continue
        out.append(c)
    return jsonify({"candidates": out}), 200

@app.route("/candidate/<cid>/status", methods=["PATCH"])
def set_status(cid):
    data   = request.get_json() or {}
    status = data.get("status", "")
    if status not in ("Shortlisted", "Rejected", "Review"):
        return jsonify({"error": "Invalid status"}), 400
    conn = get_db()
    conn.execute("UPDATE candidates SET status=? WHERE id=?", (status, cid))
    conn.commit()
    conn.close()
    return jsonify({"success": True}), 200

@app.route("/candidate/<cid>/file")
def get_file(cid):
    conn = get_db()
    row  = conn.execute("SELECT filepath,filename FROM candidates WHERE id=?", (cid,)).fetchone()
    conn.close()
    if not row or not os.path.exists(row["filepath"]):
        return jsonify({"error": "File not found"}), 404
    return send_file(row["filepath"], download_name=row["filename"], as_attachment=False)

@app.route("/candidate/<cid>", methods=["DELETE"])
def delete_candidate(cid):
    conn = get_db()
    row  = conn.execute("SELECT filepath FROM candidates WHERE id=?", (cid,)).fetchone()
    if row and row["filepath"] and os.path.exists(row["filepath"]):
        try: os.remove(row["filepath"])
        except: pass
    conn.execute("DELETE FROM candidates WHERE id=?", (cid,))
    conn.commit()
    rerank(conn)
    conn.close()
    return jsonify({"success": True}), 200

# ════════════════════════════════════════════════════════════
# JOB POSTINGS
# ════════════════════════════════════════════════════════════

@app.route("/jobs", methods=["GET"])
def get_jobs():
    search = request.args.get("search", "").lower()
    status = request.args.get("status", "")
    conn   = get_db()
    rows   = conn.execute("SELECT * FROM job_postings ORDER BY created_at DESC").fetchall()
    out = []
    for row in rows:
        j = dict(row)
        if search and search not in (j.get("job_title","") + " " + j.get("department","")).lower():
            continue
        if status and j.get("status") != status:
            continue
        j["applicant_count"] = conn.execute(
            "SELECT COUNT(*) FROM candidates WHERE job_id=?", (j["id"],)
        ).fetchone()[0]
        out.append(j)
    conn.close()
    return jsonify({"jobs": out, "count": len(out)}), 200

@app.route("/jobs", methods=["POST"])
def create_job():
    data = request.get_json() or {}
    if not data.get("job_title","").strip():
        return jsonify({"error": "job_title is required"}), 400
    jid = str(uuid.uuid4())
    now = datetime.now().isoformat()
    conn = get_db()
    conn.execute("""INSERT INTO job_postings
        (id, job_title, department, location, employment_type, experience_level,
         salary_range, responsibilities, requirements, nice_to_have, status, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)""", (
        jid, data.get("job_title","").strip(), data.get("department","").strip(),
        data.get("location","").strip(), data.get("employment_type","Full-time").strip(),
        data.get("experience_level","").strip(), data.get("salary_range","").strip(),
        data.get("responsibilities","").strip(), data.get("requirements","").strip(),
        data.get("nice_to_have","").strip(), data.get("status","Active").strip(), now, now
    ))
    conn.commit()
    row = conn.execute("SELECT * FROM job_postings WHERE id=?", (jid,)).fetchone()
    conn.close()
    return jsonify(dict(row)), 201

@app.route("/jobs/<jid>", methods=["GET"])
def get_job(jid):
    conn = get_db()
    row  = conn.execute("SELECT * FROM job_postings WHERE id=?", (jid,)).fetchone()
    conn.close()
    if not row:
        return jsonify({"error": "Not found"}), 404
    return jsonify(dict(row)), 200

@app.route("/jobs/<jid>", methods=["PUT"])
def update_job(jid):
    data = request.get_json() or {}
    now  = datetime.now().isoformat()
    conn = get_db()
    row  = conn.execute("SELECT id FROM job_postings WHERE id=?", (jid,)).fetchone()
    if not row:
        conn.close()
        return jsonify({"error": "Not found"}), 404
    conn.execute("""UPDATE job_postings SET
        job_title=?,department=?,location=?,employment_type=?,experience_level=?,
        salary_range=?,responsibilities=?,requirements=?,nice_to_have=?,status=?,updated_at=?
        WHERE id=?""", (
        data.get("job_title","").strip(), data.get("department","").strip(),
        data.get("location","").strip(), data.get("employment_type","Full-time").strip(),
        data.get("experience_level","").strip(), data.get("salary_range","").strip(),
        data.get("responsibilities","").strip(), data.get("requirements","").strip(),
        data.get("nice_to_have","").strip(), data.get("status","Active").strip(), now, jid
    ))
    conn.commit()
    updated = conn.execute("SELECT * FROM job_postings WHERE id=?", (jid,)).fetchone()
    conn.close()
    return jsonify(dict(updated)), 200

@app.route("/jobs/<jid>", methods=["DELETE"])
def delete_job(jid):
    conn = get_db()
    conn.execute("DELETE FROM job_postings WHERE id=?", (jid,))
    conn.commit()
    conn.close()
    return jsonify({"success": True}), 200

@app.route("/jobs/<jid>/status", methods=["PATCH"])
def set_job_status(jid):
    data   = request.get_json() or {}
    status = data.get("status","")
    if status not in ("Active", "Closed", "Draft"):
        return jsonify({"error": "Invalid status"}), 400
    conn = get_db()
    conn.execute("UPDATE job_postings SET status=?,updated_at=? WHERE id=?",
                 (status, datetime.now().isoformat(), jid))
    conn.commit()
    conn.close()
    return jsonify({"success": True}), 200

# ════════════════════════════════════════════════════════════
# QUESTIONNAIRES
# ════════════════════════════════════════════════════════════

@app.route("/questionnaires", methods=["GET"])
def get_questionnaires():
    conn = get_db()
    rows = conn.execute("SELECT * FROM questionnaires ORDER BY created_at DESC").fetchall()
    result = []
    for row in rows:
        q = dict(row)
        q["question_count"] = conn.execute(
            "SELECT COUNT(*) FROM questionnaire_questions WHERE questionnaire_id=?", (q["id"],)
        ).fetchone()[0]
        result.append(q)
    conn.close()
    return jsonify({"questionnaires": result}), 200

@app.route("/questionnaires", methods=["POST"])
def create_questionnaire():
    data = request.get_json() or {}
    if not data.get("title", "").strip():
        return jsonify({"error": "title is required"}), 400
    qid = str(uuid.uuid4())
    now = datetime.now().isoformat()
    conn = get_db()
    conn.execute(
        "INSERT INTO questionnaires (id,title,description,job_title,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)",
        (qid, data["title"].strip(), data.get("description","").strip(),
         data.get("job_title","").strip(), data.get("status","Active"), now, now)
    )
    for i, q in enumerate(data.get("questions", [])):
        conn.execute(
            "INSERT INTO questionnaire_questions (id,questionnaire_id,question_text,question_type,options,required,order_index) VALUES (?,?,?,?,?,?,?)",
            (str(uuid.uuid4()), qid, q.get("question_text",""), q.get("question_type","short_text"),
             json.dumps(q.get("options",[])), int(q.get("required",1)), i)
        )
    conn.commit()
    row = conn.execute("SELECT * FROM questionnaires WHERE id=?", (qid,)).fetchone()
    conn.close()
    return jsonify(dict(row)), 201

@app.route("/questionnaires/<qid>", methods=["GET"])
def get_questionnaire(qid):
    conn = get_db()
    row  = conn.execute("SELECT * FROM questionnaires WHERE id=?", (qid,)).fetchone()
    if not row:
        conn.close()
        return jsonify({"error": "Not found"}), 404
    q = dict(row)
    questions = conn.execute(
        "SELECT * FROM questionnaire_questions WHERE questionnaire_id=? ORDER BY order_index", (qid,)
    ).fetchall()
    q["questions"] = [dict(r) for r in questions]
    for question in q["questions"]:
        question["options"] = json.loads(question.get("options") or "[]")
    conn.close()
    return jsonify(q), 200

@app.route("/questionnaires/<qid>", methods=["PUT"])
def update_questionnaire(qid):
    data = request.get_json() or {}
    now  = datetime.now().isoformat()
    conn = get_db()
    if not conn.execute("SELECT id FROM questionnaires WHERE id=?", (qid,)).fetchone():
        conn.close()
        return jsonify({"error": "Not found"}), 404
    conn.execute(
        "UPDATE questionnaires SET title=?,description=?,job_title=?,status=?,updated_at=? WHERE id=?",
        (data.get("title","").strip(), data.get("description","").strip(),
         data.get("job_title","").strip(), data.get("status","Active"), now, qid)
    )
    conn.execute("DELETE FROM questionnaire_questions WHERE questionnaire_id=?", (qid,))
    for i, q in enumerate(data.get("questions", [])):
        conn.execute(
            "INSERT INTO questionnaire_questions (id,questionnaire_id,question_text,question_type,options,required,order_index) VALUES (?,?,?,?,?,?,?)",
            (str(uuid.uuid4()), qid, q.get("question_text",""), q.get("question_type","short_text"),
             json.dumps(q.get("options",[])), int(q.get("required",1)), i)
        )
    conn.commit()
    conn.close()
    return get_questionnaire(qid)

@app.route("/questionnaires/<qid>", methods=["DELETE"])
def delete_questionnaire(qid):
    conn = get_db()
    conn.execute("DELETE FROM questionnaires WHERE id=?", (qid,))
    conn.commit()
    conn.close()
    return jsonify({"success": True}), 200

@app.route("/questionnaires/<qid>/duplicate", methods=["POST"])
def duplicate_questionnaire(qid):
    conn = get_db()
    row  = conn.execute("SELECT * FROM questionnaires WHERE id=?", (qid,)).fetchone()
    if not row:
        conn.close()
        return jsonify({"error": "Not found"}), 404
    orig   = dict(row)
    new_id = str(uuid.uuid4())
    now    = datetime.now().isoformat()
    conn.execute(
        "INSERT INTO questionnaires (id,title,description,job_title,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?)",
        (new_id, f"Copy of {orig['title']}", orig["description"], orig["job_title"], "Active", now, now)
    )
    questions = conn.execute(
        "SELECT * FROM questionnaire_questions WHERE questionnaire_id=? ORDER BY order_index", (qid,)
    ).fetchall()
    for q in questions:
        conn.execute(
            "INSERT INTO questionnaire_questions (id,questionnaire_id,question_text,question_type,options,required,order_index) VALUES (?,?,?,?,?,?,?)",
            (str(uuid.uuid4()), new_id, q["question_text"], q["question_type"], q["options"], q["required"], q["order_index"])
        )
    conn.commit()
    conn.close()
    return get_questionnaire(new_id)

# ════════════════════════════════════════════════════════════
# EMAIL SETTINGS
# ════════════════════════════════════════════════════════════

@app.route("/settings", methods=["GET"])
def get_settings():
    conn = get_db()
    rows = conn.execute("SELECT key,value FROM settings").fetchall()
    conn.close()
    data = {r["key"]: r["value"] for r in rows}
    if "smtp_password" in data:
        data["smtp_password"] = "••••••••" if data["smtp_password"] else ""
    return jsonify(data), 200

@app.route("/settings", methods=["POST"])
def save_settings():
    data = request.get_json() or {}
    conn = get_db()
    for key, value in data.items():
        if key == "smtp_password" and value == "••••••••":
            continue
        conn.execute("INSERT OR REPLACE INTO settings (key,value) VALUES (?,?)", (key, str(value)))
    conn.commit()
    conn.close()
    return jsonify({"success": True}), 200

# ════════════════════════════════════════════════════════════
# SEND QUESTIONNAIRE EMAIL
# ════════════════════════════════════════════════════════════

def send_questionnaire_email(candidate, questionnaire, token, portal_base_url):
    smtp_host = get_setting("smtp_host", "smtp.gmail.com")
    smtp_port = int(get_setting("smtp_port", "587"))
    smtp_user = get_setting("smtp_user", "")
    smtp_pass = get_setting("smtp_password", "")
    from_name = get_setting("from_name", "HR Team")
    if not smtp_user or not smtp_pass:
        raise ValueError("SMTP credentials not configured. Go to Email Settings first.")
    link    = f"{portal_base_url}/portal/{token}"
    subject = f"Questionnaire for {questionnaire['job_title'] or questionnaire['title']}"
    html_body = f"""
<!DOCTYPE html><html><body style="font-family:'Segoe UI',sans-serif;background:#f4f6f9;margin:0;padding:30px;">
<div style="max-width:580px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);">
  <div style="background:linear-gradient(135deg,#1a1f36,#2d3561);padding:32px 36px;">
    <h1 style="color:#ffffff;margin:0;font-size:22px;">Application Questionnaire</h1>
    <p style="color:#a0aec0;margin:6px 0 0;font-size:14px;">{questionnaire['job_title'] or questionnaire['title']}</p>
  </div>
  <div style="padding:32px 36px;">
    <p style="color:#2d3748;font-size:16px;margin:0 0 16px;">Dear <strong>{candidate['name']}</strong>,</p>
    <p style="color:#4a5568;font-size:15px;line-height:1.6;margin:0 0 24px;">
      Thank you for your interest in the <strong>{questionnaire['job_title'] or 'open position'}</strong> role.
      Please complete the short questionnaire below.
    </p>
    <a href="{link}" style="display:inline-block;background:linear-gradient(135deg,#4299e1,#3182ce);color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:15px;">
      Start Questionnaire →
    </a>
    <p style="color:#718096;font-size:13px;margin:24px 0 0;">Or copy: <span style="color:#4299e1;">{link}</span></p>
  </div>
  <div style="background:#f7fafc;padding:20px 36px;border-top:1px solid #e2e8f0;">
    <p style="color:#a0aec0;font-size:12px;margin:0;">Best regards,<br><strong style="color:#718096;">{from_name}</strong></p>
  </div>
</div>
</body></html>"""
    msg = MIMEMultipart("alternative")
    msg["Subject"] = subject
    msg["From"]    = f"{from_name} <{smtp_user}>"
    msg["To"]      = candidate["email"]
    msg.attach(MIMEText(html_body, "html"))
    with smtplib.SMTP(smtp_host, smtp_port) as server:
        server.ehlo()
        server.starttls()
        server.login(smtp_user, smtp_pass)
        server.sendmail(smtp_user, candidate["email"], msg.as_string())

@app.route("/candidates/<cid>/send-questionnaire", methods=["POST"])
def send_questionnaire(cid):
    data             = request.get_json() or {}
    questionnaire_id = data.get("questionnaire_id", "")
    portal_base_url  = data.get("portal_base_url", "http://127.0.0.1:5001")
    conn      = get_db()
    candidate = conn.execute("SELECT * FROM candidates WHERE id=?", (cid,)).fetchone()
    qrow      = conn.execute("SELECT * FROM questionnaires WHERE id=?", (questionnaire_id,)).fetchone()
    if not candidate:
        conn.close()
        return jsonify({"error": "Candidate not found"}), 404
    if not qrow:
        conn.close()
        return jsonify({"error": "Questionnaire not found"}), 404
    if not dict(candidate).get("email",""):
        conn.close()
        return jsonify({"error": "Candidate has no email address"}), 400
    token  = secrets.token_urlsafe(32)
    cq_id  = str(uuid.uuid4())
    now    = datetime.now().isoformat()
    try:
        send_questionnaire_email(dict(candidate), dict(qrow), token, portal_base_url)
    except Exception as e:
        conn.close()
        return jsonify({"error": str(e)}), 500
    conn.execute(
        "INSERT INTO candidate_questionnaires (id,candidate_id,questionnaire_id,token,sent_at,status) VALUES (?,?,?,?,?,?)",
        (cq_id, cid, questionnaire_id, token, now, "Sent")
    )
    conn.execute("UPDATE candidates SET status='Questionnaire Sent' WHERE id=?", (cid,))
    conn.commit()
    conn.close()
    return jsonify({"success": True, "token": token}), 200

# ════════════════════════════════════════════════════════════
# CANDIDATE RESPONSE PORTAL
# ════════════════════════════════════════════════════════════

@app.route("/portal/<token>", methods=["GET"])
def portal_page(token):
    conn = get_db()
    cq   = conn.execute("SELECT * FROM candidate_questionnaires WHERE token=?", (token,)).fetchone()
    if not cq:
        conn.close()
        return "<h2 style='font-family:sans-serif;text-align:center;margin-top:80px'>Link not found or expired.</h2>", 404
    cq = dict(cq)
    if cq["status"] == "Completed":
        conn.close()
        return """<html><body style="font-family:'Segoe UI',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f4f6f9;">
<div style="text-align:center;padding:40px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:480px;">
<div style="font-size:60px;margin-bottom:16px">✅</div>
<h2 style="color:#2d3748;margin:0 0 8px">Already Submitted</h2>
<p style="color:#718096">You have already completed this questionnaire. Thank you!</p>
</div></body></html>"""
    candidate     = dict(conn.execute("SELECT * FROM candidates WHERE id=?", (cq["candidate_id"],)).fetchone())
    questionnaire = dict(conn.execute("SELECT * FROM questionnaires WHERE id=?", (cq["questionnaire_id"],)).fetchone())
    questions     = conn.execute(
        "SELECT * FROM questionnaire_questions WHERE questionnaire_id=? ORDER BY order_index",
        (cq["questionnaire_id"],)
    ).fetchall()
    conn.close()
    questions_data = []
    for q in questions:
        qd = dict(q)
        qd["options"] = json.loads(qd.get("options") or "[]")
        questions_data.append(qd)
    q_json = json.dumps(questions_data)
    return f"""<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{questionnaire['title']}</title>
<style>
*{{box-sizing:border-box;margin:0;padding:0}}
body{{font-family:'Segoe UI',sans-serif;background:#f4f6f9;color:#2d3748;min-height:100vh}}
.header{{background:linear-gradient(135deg,#1a1f36,#2d3561);color:#fff;padding:28px 0}}
.header-inner{{max-width:680px;margin:0 auto;padding:0 24px}}
.header h1{{font-size:22px;font-weight:700;margin-bottom:6px}}
.header p{{color:#a0aec0;font-size:14px}}
.container{{max-width:680px;margin:32px auto;padding:0 24px 60px}}
.card{{background:#fff;border-radius:12px;padding:28px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06)}}
.question-num{{font-size:12px;font-weight:700;color:#4299e1;text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px}}
.question-text{{font-size:16px;font-weight:600;color:#2d3748;margin-bottom:16px;line-height:1.5}}
.required{{color:#e53e3e;margin-left:3px}}
input[type=text],input[type=number],textarea{{width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px 14px;font-size:15px;font-family:inherit;color:#2d3748;outline:none;transition:border-color .2s}}
input:focus,textarea:focus{{border-color:#4299e1}}
textarea{{min-height:100px;resize:vertical}}
.options{{display:flex;flex-direction:column;gap:10px}}
.option-label{{display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:8px;transition:.2s}}
.option-label:hover{{border-color:#4299e1;background:#ebf8ff}}
.option-label input{{width:auto;margin:0}}
.rating-row{{display:flex;gap:8px;flex-wrap:wrap}}
.rating-btn{{width:44px;height:44px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:15px;font-weight:600;color:#4a5568;transition:.15s}}
.rating-btn.selected{{background:#4299e1;border-color:#4299e1;color:#fff}}
.yn-row{{display:flex;gap:12px}}
.yn-btn{{flex:1;padding:12px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:15px;font-weight:600;color:#4a5568;transition:.15s}}
.submit-btn{{width:100%;padding:16px;background:linear-gradient(135deg,#4299e1,#3182ce);color:#fff;border:none;border-radius:10px;font-size:17px;font-weight:700;cursor:pointer;transition:.2s}}
.submit-btn:disabled{{opacity:.5;cursor:not-allowed}}
.progress-bar{{height:4px;background:#e2e8f0;border-radius:2px;margin-top:16px}}
.progress-fill{{height:100%;background:#4299e1;border-radius:2px;transition:width .3s}}
</style>
</head>
<body>
<div class="header">
  <div class="header-inner">
    <h1>{questionnaire['title']}</h1>
    <p>Dear <strong>{candidate['name']}</strong> — please complete all required questions below.</p>
    <div class="progress-bar"><div class="progress-fill" id="progress" style="width:0%"></div></div>
  </div>
</div>
<div class="container">
  <div id="questions"></div>
  <div class="card" style="background:#f7fafc">
    <button class="submit-btn" id="submitBtn" onclick="submitForm()">Submit Questionnaire</button>
    <p id="submitMsg" style="text-align:center;margin-top:12px;font-size:14px;color:#718096"></p>
  </div>
</div>
<script>
const QUESTIONS={q_json};
const TOKEN="{token}";
const answers={{}};
function renderQuestions(){{
  const container=document.getElementById("questions");
  QUESTIONS.forEach((q,idx)=>{{
    const card=document.createElement("div");
    card.className="card";
    const req=q.required?'<span class="required">*</span>':'';
    let input='';
    if(q.question_type==='short_text') input=`<input type="text" placeholder="Your answer…" oninput="setAnswer('${{q.id}}',this.value)"/>`;
    else if(q.question_type==='long_text') input=`<textarea placeholder="Your answer…" oninput="setAnswer('${{q.id}}',this.value)"></textarea>`;
    else if(q.question_type==='number') input=`<input type="number" placeholder="0" oninput="setAnswer('${{q.id}}',this.value)" style="max-width:160px"/>`;
    else if(q.question_type==='multiple_choice') input=`<div class="options">${{q.options.map(o=>`<label class="option-label"><input type="radio" name="q_${{q.id}}" value="${{o}}" onchange="setAnswer('${{q.id}}',this.value)"/>${{o}}</label>`).join('')}}</div>`;
    else if(q.question_type==='checkbox') input=`<div class="options">${{q.options.map(o=>`<label class="option-label"><input type="checkbox" value="${{o}}" onchange="toggleCheck('${{q.id}}',this)"/>${{o}}</label>`).join('')}}</div>`;
    else if(q.question_type==='rating') input=`<div class="rating-row">${{[1,2,3,4,5].map(n=>`<button class="rating-btn" onclick="setRating('${{q.id}}',${{n}},this)">${{n}}</button>`).join('')}}</div>`;
    else if(q.question_type==='yes_no') input=`<div class="yn-row"><button class="yn-btn" onclick="setYN('${{q.id}}','Yes',this)">👍 Yes</button><button class="yn-btn" onclick="setYN('${{q.id}}','No',this)">👎 No</button></div>`;
    card.innerHTML=`<div class="question-num">Question ${{idx+1}} of ${{QUESTIONS.length}}</div><div class="question-text">${{q.question_text}}${{req}}</div>`+input;
    container.appendChild(card);
  }});
}}
function setAnswer(qid,val){{answers[qid]=val;updateProgress();}}
function toggleCheck(qid,el){{if(!answers[qid])answers[qid]=[];if(el.checked)answers[qid].push(el.value);else answers[qid]=answers[qid].filter(v=>v!==el.value);updateProgress();}}
function setRating(qid,val,btn){{answers[qid]=String(val);btn.closest('.rating-row').querySelectorAll('.rating-btn').forEach((b,i)=>b.classList.toggle('selected',i<val));updateProgress();}}
function setYN(qid,val,btn){{answers[qid]=val;btn.parentElement.querySelectorAll('.yn-btn').forEach(b=>{{b.style.background='';b.style.color='';b.style.borderColor=''}});btn.style.background=val==='Yes'?'#48bb78':'#fc8181';btn.style.borderColor=val==='Yes'?'#48bb78':'#fc8181';btn.style.color='#fff';updateProgress();}}
function updateProgress(){{const answered=QUESTIONS.filter(q=>answers[q.id]!==undefined&&answers[q.id]!==''&&!(Array.isArray(answers[q.id])&&!answers[q.id].length)).length;document.getElementById("progress").style.width=(answered/QUESTIONS.length*100)+'%';}}
async function submitForm(){{
  const missing=QUESTIONS.filter(q=>q.required&&(answers[q.id]===undefined||answers[q.id]===''||(Array.isArray(answers[q.id])&&!answers[q.id].length)));
  if(missing.length){{alert('Please answer all required questions ('+missing.length+' remaining).');return;}}
  const btn=document.getElementById("submitBtn");
  btn.disabled=true;btn.textContent="Submitting…";
  const payload=QUESTIONS.map(q=>{{return{{question_id:q.id,answer:Array.isArray(answers[q.id])?answers[q.id].join(', '):(answers[q.id]||'')}}}});
  try{{
    const res=await fetch('/portal/'+TOKEN+'/submit',{{method:'POST',headers:{{'Content-Type':'application/json'}},body:JSON.stringify({{responses:payload}})}});
    if(res.ok){{document.body.innerHTML=`<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:'Segoe UI',sans-serif;background:#f4f6f9"><div style="text-align:center;background:#fff;padding:48px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:440px"><div style="font-size:64px;margin-bottom:20px">🎉</div><h2 style="color:#2d3748;margin-bottom:12px">Thank You!</h2><p style="color:#718096;font-size:16px;line-height:1.6">Your responses have been submitted. Our HR team will be in touch soon.</p></div></div>`;}}
    else throw new Error('fail');
  }}catch(e){{btn.disabled=false;btn.textContent="Submit Questionnaire";alert("Submission failed. Please try again.");}}
}}
renderQuestions();
</script>
</body></html>"""

@app.route("/portal/<token>/submit", methods=["POST"])
def portal_submit(token):
    data = request.get_json() or {}
    conn = get_db()
    cq   = conn.execute("SELECT * FROM candidate_questionnaires WHERE token=?", (token,)).fetchone()
    if not cq:
        conn.close()
        return jsonify({"error": "Invalid token"}), 404
    cq = dict(cq)
    if cq["status"] == "Completed":
        conn.close()
        return jsonify({"error": "Already submitted"}), 400
    now = datetime.now().isoformat()
    for resp in data.get("responses", []):
        conn.execute(
            "INSERT INTO questionnaire_responses (id,candidate_questionnaire_id,question_id,answer,submitted_at) VALUES (?,?,?,?,?)",
            (str(uuid.uuid4()), cq["id"], resp["question_id"], resp.get("answer",""), now)
        )
    conn.execute("UPDATE candidate_questionnaires SET status='Completed',completed_at=? WHERE id=?", (now, cq["id"]))
    conn.execute("UPDATE candidates SET status='Questionnaire Completed' WHERE id=?", (cq["candidate_id"],))
    conn.commit()
    conn.close()
    return jsonify({"success": True}), 200

@app.route("/candidates/<cid>/responses", methods=["GET"])
def get_candidate_responses(cid):
    conn = get_db()
    cqs  = conn.execute(
        "SELECT cq.*,q.title as q_title,q.job_title as q_job FROM candidate_questionnaires cq JOIN questionnaires q ON cq.questionnaire_id=q.id WHERE cq.candidate_id=? ORDER BY cq.sent_at DESC",
        (cid,)
    ).fetchall()
    result = []
    for cq in cqs:
        cq = dict(cq)
        responses = conn.execute(
            "SELECT r.answer,qq.question_text,qq.question_type FROM questionnaire_responses r JOIN questionnaire_questions qq ON r.question_id=qq.id WHERE r.candidate_questionnaire_id=? ORDER BY qq.order_index",
            (cq["id"],)
        ).fetchall()
        cq["responses"] = [dict(r) for r in responses]
        result.append(cq)
    conn.close()
    return jsonify({"questionnaires": result}), 200

# ════════════════════════════════════════════════════════════
# STATS
# ════════════════════════════════════════════════════════════

@app.route("/jobs/<jid>/candidates")
def get_job_candidates(jid):
    search = request.args.get("search", "").lower()
    status = request.args.get("status", "")
    rec    = request.args.get("recommendation", "")
    conn   = get_db()
    rows   = conn.execute(
        "SELECT * FROM candidates WHERE job_id=? ORDER BY rank ASC, overall_score DESC", (jid,)
    ).fetchall()
    conn.close()
    out = []
    for row in rows:
        c = dict(row)
        c["skills"]    = json.loads(c.get("skills")    or "[]")
        c["strengths"] = json.loads(c.get("strengths") or "[]")
        c["gaps"]      = json.loads(c.get("gaps")      or "[]")
        if search and search not in (c.get("name","") + " " + c.get("email","")).lower():
            continue
        if status and c.get("status") != status:
            continue
        if rec and c.get("recommendation") != rec:
            continue
        out.append(c)
    return jsonify({"candidates": out}), 200


@app.route("/jobs/<jid>/stats")
def get_job_stats(jid):
    conn        = get_db()
    total       = conn.execute("SELECT COUNT(*) FROM candidates WHERE job_id=?", (jid,)).fetchone()[0]
    screened    = conn.execute("SELECT COUNT(*) FROM candidates WHERE job_id=? AND screened=1", (jid,)).fetchone()[0]
    shortlisted = conn.execute("SELECT COUNT(*) FROM candidates WHERE job_id=? AND status='Shortlisted'", (jid,)).fetchone()[0]
    avg         = conn.execute("SELECT AVG(overall_score) FROM candidates WHERE job_id=? AND screened=1", (jid,)).fetchone()[0]
    conn.close()
    return jsonify({
        "total": total,
        "screened": screened,
        "shortlisted": shortlisted,
        "avg_score": round(avg or 0, 1)
    }), 200


@app.route("/stats")
def stats():
    conn        = get_db()
    total       = conn.execute("SELECT COUNT(*) FROM candidates").fetchone()[0]
    screened    = conn.execute("SELECT COUNT(*) FROM candidates WHERE screened=1").fetchone()[0]
    shortlisted = conn.execute("SELECT COUNT(*) FROM candidates WHERE status='Shortlisted'").fetchone()[0]
    rejected    = conn.execute("SELECT COUNT(*) FROM candidates WHERE status='Rejected'").fetchone()[0]
    strong      = conn.execute("SELECT COUNT(*) FROM candidates WHERE recommendation='Strong Hire'").fetchone()[0]
    avg         = conn.execute("SELECT AVG(overall_score) FROM candidates WHERE screened=1").fetchone()[0]
    q_sent      = conn.execute("SELECT COUNT(*) FROM candidate_questionnaires").fetchone()[0]
    q_completed = conn.execute("SELECT COUNT(*) FROM candidate_questionnaires WHERE status='Completed'").fetchone()[0]
    conn.close()
    return jsonify({
        "total": total, "screened": screened, "shortlisted": shortlisted,
        "rejected": rejected, "strong_hire": strong, "avg_score": round(avg or 0, 1),
        "q_sent": q_sent, "q_completed": q_completed, "q_pending": q_sent - q_completed,
        "completion_rate": round((q_completed / q_sent * 100) if q_sent else 0, 1)
    }), 200

if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5001))
    print(f"Backend running on port {port} | PDF:{PDF_OK} DOCX:{DOCX_OK}", flush=True)
    app.run(host="0.0.0.0", port=port, debug=False)
 