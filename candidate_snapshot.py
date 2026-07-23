"""
Candidate Snapshot — AI Logic (Resume-to-Role, Pre-Interview Only)
--------------------------------------------------------------------
This is the actual logic behind the "keyword filter vs relevance sort" demo.

CORE RULE: this script never rejects, scores numerically, or ranks anyone
out of view. It only produces:
  1. a short relevance label (for sorting/display, not filtering)
  2. a plain-language note explaining WHY (catches keyword-filter blind spots)
  3. one tailored interview question for the human to actually ask

Nobody gets hidden. The old keyword-match function below exists only so
you can see the contrast side by side, the same way the visual demo showed it.

HOW TO USE
1. pip install anthropic --break-system-packages
2. export ANTHROPIC_API_KEY="sk-ant-..."
3. python3 candidate_snapshot.py
"""

import os
import json
import re
import anthropic
from dotenv import load_dotenv

load_dotenv()  # reads .env in this same folder — needed for standalone runs

client = anthropic.Anthropic()
MODEL = "claude-sonnet-4-6"

DEFAULT_TEST_ROLE = (
    "Customer Support Executive — handles inbound customer complaints, "
    "resolves service issues, escalates when needed, uses a CRM system, "
    "and requires clear communication and calm problem-solving under pressure."
)

# ---------------------------------------------------------------------
# Sample resumes — same 5 candidates from the visual demo.
# Replace with real (test) resumes later. NEVER use real candidate data
# until a data-privacy review has happened (see earlier discussion).
# ---------------------------------------------------------------------
CANDIDATES = [
    {
        "name": "Aisyah",
        "resume": "2 years as Customer Service Representative at a telco. "
                  "Handled inbound calls, resolved billing complaints, used "
                  "the company CRM daily, trained on complaint escalation "
                  "procedures.",
    },
    {
        "name": "Wei Ling",
        "resume": "2 years as Retail Sales Associate. Assisted customers "
                  "with returns, exchanges, and complaints on the shop "
                  "floor. Handled cash register and inventory.",
    },
    {
        "name": "Farah",
        "resume": "1 year as Store Assistant at a convenience store. Dealt "
                  "with unhappy customers over product issues and refunds "
                  "daily. Also managed shift schedules.",
    },
    {
        "name": "Danish",
        "resume": "Recent graduate, Diploma in Business Administration. "
                  "Volunteer coordinator for university charity events — "
                  "handled public inquiries and occasional complaints from "
                  "attendees, managed logistics for 200+ person events.",
    },
    {
        "name": "Kumar",
        "resume": "Excellent communicator and team player with strong "
                  "interpersonal skills. Highly motivated and "
                  "detail-oriented professional seeking a customer-facing "
                  "role. Proficient in Microsoft Office.",
    },
]

# ---------------------------------------------------------------------
# THE OLD WAY — naive keyword matching (for contrast only)
# ---------------------------------------------------------------------
KEYWORDS = ["customer", "service", "complaint", "crm", "communicat",
            "support", "resolv", "team"]


def keyword_match_percent(resume_text: str) -> int:
    text = resume_text.lower()
    hits = sum(1 for kw in KEYWORDS if re.search(kw, text))
    return round(100 * hits / len(KEYWORDS))


# ---------------------------------------------------------------------
# THE NEW WAY — Claude generates a relevance snapshot, not a score
# ---------------------------------------------------------------------
SYSTEM_PROMPT = """You help HR prepare for interviews. You are given a
candidate's resume and a job role description.

Your job is NOT to score, rank, reject, or decide anything about the
candidate. Every candidate must receive a full analysis — never skip or
refuse to process a candidate, no matter how weak the resume looks.

IMPORTANT — weigh how central the missing skill is to the role:
Some roles have a genuine hard-skill gate — a specific, non-negotiable
capability the job cannot be done without (e.g. programming ability for
an engineering role, a required license or certification). If the resume
shows NO evidence of that core skill anywhere, do not use an encouraging
label just because the candidate has unrelated positive qualities (soft
skills, organization, communication). Positive-sounding labels should be
reserved for cases where the resume shows a genuine, if unconventional,
path to the ACTUAL core skill the role needs — not just pleasant adjacent
qualities. Be direct that a core requirement is unmet, while still
describing the person fairly and without dismissiveness.

Produce exactly three things:
1. relevance_label: a short label (2-5 words) describing how relevant this
   candidate's ACTUAL experience is to the role. Do not use a numeric score.
   Examples: "Strong match", "Relevant experience", "Worth a look",
   "Keywords match, but shallow", "Missing core requirement".
2. note: ONE sentence, maximum 20 words. HR will scan this in seconds, not
   read it carefully — every word must earn its place. Still make it specific
   to this resume and this role, not generic. Prioritize whichever matters
   most for this candidate:
   - If the resume lacks exact keywords but shows genuinely relevant
     real-world experience toward the role's ACTUAL core skill, say so.
   - If the resume is buzzword-heavy with no concrete backing, flag it as
     unverified.
   - If a core, non-negotiable skill is entirely absent, say so plainly.
3. suggested_question: ONE short, direct interview question, maximum 15
   words, that would let a human interviewer verify the specific claim or
   gap you found.

Respond ONLY with valid JSON, nothing else:
{"relevance_label": "...", "note": "...", "suggested_question": "..."}"""


def generate_snapshot(resume_text: str, role_description: str) -> dict:
    """
    One shared function for every role — nothing here is role-specific.
    role_description is data, not code: it should come from whichever
    job posting the candidate actually applied to (the Jobs table /
    job-tabs concept), not a hardcoded constant. The same function
    reasons correctly about a Software Engineer role, a Sales role, or
    anything else, as long as the right role_description is passed in.
    """
    user_message = f"Job role:\n{role_description}\n\nCandidate resume:\n{resume_text}"
    response = client.messages.create(
        model=MODEL,
        max_tokens=150,
        system=SYSTEM_PROMPT,
        messages=[{"role": "user", "content": user_message}],
    )
    raw_text = response.content[0].text.strip()
    # Claude sometimes wraps its answer in markdown fences (```json ... ```)
    # even when told to respond with ONLY JSON. Strip those before parsing —
    # the JS demo already did this; this was missing here, causing every
    # real response to fail parsing once a real API key was used.
    cleaned_text = raw_text.replace("```json", "").replace("```", "").strip()
    try:
        return json.loads(cleaned_text)
    except json.JSONDecodeError:
        return {"error": "Could not parse response", "raw": raw_text}


GENERAL_ASSESSMENT_PROMPT = """You help someone understand their own resume from a career perspective — no specific job is being targeted. Given only a resume, identify four things.

Be strict about grounding every claim in something actually written in the resume. Never invent skills, traits, or experience that isn't there. If the resume is thin, say so plainly rather than padding it with generic encouragement.

Produce exactly four things:
1. skills: a list of 3-6 concrete, functional skills genuinely evidenced by the resume text (e.g. "CRM tools", "Python", "complaint escalation") — not aspirational, only what's actually shown.
2. traits: a list of 2-4 soft-skill or behavioral traits reasonably inferable from HOW the resume describes the work (e.g. "calm under pressure", "detail-oriented") — distinct from skills, these are about working style, not tools or technical ability.
3. suitable_roles: EXACTLY 3 job roles this resume would be a genuinely reasonable fit for. For each, give the role title and ONE sentence explaining why, tied to specific resume content. Never invent a role wildly disconnected from what's actually shown.
4. growth_suggestion: ONE honest, specific suggestion — a skill or experience that would meaningfully broaden this person's options — maximum 25 words.

Never give a numeric score or percentage. Respond ONLY with valid JSON, nothing else:
{"skills": ["...", "..."], "traits": ["...", "..."], "suitable_roles": [{"title": "...", "reason": "..."}, ...], "growth_suggestion": "..."}"""


def generate_general_assessment(resume_text: str) -> dict:
    """
    A genuinely different task from generate_snapshot() above — this one
    takes NO role at all. Instead of "how relevant is this resume to a
    specific job", it answers "given only this resume, what does it
    actually show, and what roles would it reasonably fit." Tested live
    against several resume shapes (thin, technical, non-traditional)
    before being wired in here.
    """
    response = client.messages.create(
        model=MODEL,
        max_tokens=500,
        system=GENERAL_ASSESSMENT_PROMPT,
        messages=[{"role": "user", "content": f"RESUME:\n{resume_text}"}],
    )
    raw_text = response.content[0].text.strip()
    cleaned_text = raw_text.replace("```json", "").replace("```", "").strip()
    try:
        return json.loads(cleaned_text)
    except json.JSONDecodeError:
        return {"error": "Could not parse response", "raw": raw_text}


def run(role_description: str = DEFAULT_TEST_ROLE):
    print("=" * 78)
    print("OLD WAY vs NEW WAY — same 5 resumes, same role")
    print(f"Role being tested: {role_description[:60]}...")
    print("=" * 78)

    for c in CANDIDATES:
        name, resume = c["name"], c["resume"]
        old_pct = keyword_match_percent(resume)
        snapshot = generate_snapshot(resume, role_description)

        print(f"\n--- {name} ---")
        print(f"  OLD (keyword filter): {old_pct}% match "
              f"{'-> HIDDEN from HR' if old_pct < 50 else '-> visible'}")

        if "error" in snapshot:
            print(f"  NEW (relevance snapshot): ERROR - {snapshot['error']}")
            continue

        print(f"  NEW (relevance snapshot): {snapshot['relevance_label']}")
        print(f"    Note: {snapshot['note']}")
        print(f"    Suggested question: {snapshot['suggested_question']}")

    print("\n" + "=" * 78)
    print("WHAT TO LOOK FOR:")
    print("- Did the OLD method hide anyone the NEW method still finds relevant?")
    print("- Did the NEW method correctly flag Kumar's buzzwords as unverified,")
    print("  even though his keyword % is high?")
    print("- Everyone should get a note + question in the NEW method - nobody")
    print("  should ever be silently skipped.")
    print("=" * 78)


if __name__ == "__main__":
    if not os.environ.get("ANTHROPIC_API_KEY"):
        print('ERROR: run  export ANTHROPIC_API_KEY="sk-ant-..."  first')
    else:
        run()