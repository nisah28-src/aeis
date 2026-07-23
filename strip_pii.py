"""
Resume Intake — Extract Text, THEN Strip PII (Pre-AI Step)
--------------------------------------------------------------
This is the privacy safeguard that sits BEFORE any resume reaches the AI
snapshot logic. It never touches the AI itself — it only prepares a
"safe to send" copy of a resume.

CORRECT ORDER, AND WHY IT MATTERS:
  1. EXTRACT text from the uploaded file (PDF/DOCX -> plain string).
     Regex cannot search a binary PDF file directly — it needs a plain
     text string to work on. This step is pure format conversion; it
     does not know or care about PII.
  2. STRIP PII from that extracted text (regex-based, see strip_pii()).
     This step does not know or care about file formats — it only
     works on strings.
  3. Only the STRIPPED TEXT ever gets sent to Claude.

Do NOT feed the raw PDF file directly to Claude's native PDF support for
this use case — Claude reads PDFs visually (each page as an image), which
would show it the name, IC, and any photo on the page before stripping
ever gets a chance to run. Extraction must happen locally, first, with a
plain non-AI library.

CORE RULE: two versions of every resume exist.
  1. FULL version  -> stored in the database, name/IC intact, for HR only.
  2. STRIPPED copy  -> the ONLY version that ever gets sent to Claude.

Saving the full version to a database, and reattaching the AI's response
back to the right candidate, is backend work (Daniel's side) — see the
candidate_id contract below.

HOW TO USE
    pip install pdfplumber --break-system-packages
    python3 strip_pii.py
"""

import re
import pdfplumber


def is_valid_pdf(file_path: str) -> bool:
    """
    Checks the file's actual content, not just its name/extension.
    A real PDF always starts with the bytes '%PDF-' — this is a fast,
    reliable check that catches a renamed .docx/.png/.txt masquerading
    as a .pdf, before we ever try to parse it.
    """
    try:
        with open(file_path, "rb") as f:
            header = f.read(5)
        return header == b"%PDF-"
    except Exception:
        return False


def extract_text_from_pdf(file_path: str) -> str:
    """
    STEP 1: Pure format conversion. Turns a PDF file into a plain text
    string. Knows nothing about PII — that's the next function's job.

    Uses layout=True, which better preserves reading order for
    multi-column resume templates (a common modern design) compared to
    plain extraction, which can merge separate columns into one garbled
    line. Still not perfect — heavily-designed, multi-column templates
    remain the hardest case for text extraction in general, the same
    way physical addresses remain the hardest case for PII stripping.

    Raises ValueError with a clear message if the file isn't a valid,
    readable PDF, instead of letting a raw library exception bubble up.
    """
    if not is_valid_pdf(file_path):
        raise ValueError(
            "This file doesn't look like a real PDF (failed the file "
            "header check). It may be renamed, corrupted, or a "
            "different file type entirely."
        )

    try:
        text_parts = []
        with pdfplumber.open(file_path) as pdf:
            for page in pdf.pages:
                page_text = page.extract_text(layout=True) or ""
                text_parts.append(page_text)
        return "\n".join(text_parts)
    except Exception as e:
        raise ValueError(
            f"This file passed the basic PDF check but could not be "
            f"read properly ({type(e).__name__}). It may be corrupted "
            f"or use an unsupported PDF structure."
        ) from e


def strip_pii(resume_text: str) -> str:
    """
    STEP 2: Removes high-confidence personal identifiers from resume
    text before it is sent to any AI service.

    Handles (Malaysia-specific formats):
      - IC number      e.g. 981234-14-5678
      - Phone number    e.g. 012-3456789
      - Email address
      - Name            (heuristic: assumes the first non-empty line)

    Deliberately NOT handled here (see project notes):
      - Physical address in free text (unreliable to regex-match safely)
      - Embedded photos in the PDF (needs image-level handling, not text
        regex — another reason NOT to send the raw PDF to Claude)
    These are lower-frequency risks, left for a later pass if this scales.
    """
    text = resume_text

    # IC number: 6 digits - 2 digits - 4 digits
    text = re.sub(r"\b\d{6}-\d{2}-\d{4}\b", "[IC REMOVED]", text)

    # Malaysian mobile numbers — allows irregular spacing/dashes between
    # digit groups (e.g. "019 - 643 8200"), not just the tidy
    # "012-3456789" format. Requires a valid mobile prefix (01x) then
    # 7-9 more digits, with any mix of spaces/dashes between them.
    text = re.sub(r"\b01\d(?:[-\s]*\d){7,9}\b", "[PHONE REMOVED]", text)

    # Email address
    text = re.sub(r"[\w.+-]+@[\w-]+\.[\w.-]+", "[EMAIL REMOVED]", text)

    # Address line — best-effort only (see notes above). Redacts a whole
    # line if it contains a Malaysian postcode (5 digits) or common
    # address keywords. This will miss addresses that don't match these
    # patterns, and can occasionally over-redact a line that merely
    # mentions a place name — over-redacting is the safer failure mode
    # here, since the goal is to protect privacy, not preserve every
    # word of context.
    ADDRESS_KEYWORDS = r"(?:Jln|Jalan|Taman|Lorong|Kampung|Persiaran|Blok|No\.)"
    lines = text.split("\n")
    for i, line in enumerate(lines):
        has_postcode = re.search(r"\b\d{5}\b", line)
        has_address_word = re.search(ADDRESS_KEYWORDS, line, re.IGNORECASE)
        if has_postcode and has_address_word:
            lines[i] = "[ADDRESS LINE REMOVED]"
    text = "\n".join(lines)

    # Name — scans the first several lines for anything "name-shaped":
    # mostly-capitalized words, no digits, not a known section header.
    # Explicitly allows common lowercase naming particles (binti, bin,
    # a/l, a/p, etc.) so real Malaysian names aren't missed just because
    # one word in them is conventionally lowercase — found after testing
    # against a real resume where "Aisyah binti Rahman" slipped through
    # entirely, because the earlier version required EVERY word to start
    # with a capital letter.
    HEADER_WORDS = {"resume", "cv", "curriculum vitae", "biodata",
                     "personal particulars", "profile", "summary",
                     "education", "experience", "objective",
                     "professional summary", "career objective",
                     "work experience", "career summary",
                     "personal details", "contact information",
                     "contact details", "technical skills",
                     "relevant courses", "relevant experience"}
    LOWERCASE_NAME_PARTICLES = {"binti", "bin", "bt", "bt.", "b.",
                                 "a/l", "a/p", "al", "anak", "ibni", "binte"}

    def looks_like_name(line: str) -> bool:
        words = line.split()
        if not (2 <= len(words) <= 6):
            return False
        if any(ch.isdigit() for ch in line):
            return False
        for w in words:
            clean = w.strip(".,'-")
            if not clean:
                return False
            if clean.lower() in LOWERCASE_NAME_PARTICLES:
                continue
            if not clean[0].isupper():
                return False
            if not all(ch.isalpha() or ch in "'-" for ch in clean):
                return False
        return True

    lines = text.split("\n")
    CHECK_FIRST_N_LINES = 8  # names normally appear near the top
    for i, line in enumerate(lines[:CHECK_FIRST_N_LINES]):
        stripped_line = line.strip()
        if not stripped_line:
            continue
        if stripped_line.lower() in HEADER_WORDS:
            continue
        if "[ADDRESS LINE REMOVED]" in stripped_line:
            continue
        if looks_like_name(stripped_line):
            lines[i] = "[NAME REMOVED]"
    text = "\n".join(lines)

    return text


def process_resume(candidate_id: str, file_path: str) -> dict:
    """
    The actual function Daniel's backend would call for a real uploaded
    PDF. Runs STEP 1 then STEP 2, in the correct order, and returns the
    candidate_id unchanged alongside the stripped text — matching the
    same contract used by generate_snapshot() in candidate_snapshot.py.
    """
    full_text = extract_text_from_pdf(file_path)      # step 1: extract
    stripped_text = strip_pii(full_text)                # step 2: strip
    return {
        "candidate_id": candidate_id,
        "full_text": full_text,          # -> goes to the database, for HR
        "stripped_text": stripped_text,  # -> the ONLY thing sent to Claude
    }


def strip_for_ai(candidate_id: str, resume_text: str) -> dict:
    """
    Same contract as process_resume(), but for when you already have
    plain text (e.g. a .txt upload, or text pasted directly) and don't
    need the PDF extraction step at all.
    """
    return {
        "candidate_id": candidate_id,
        "stripped_text": strip_pii(resume_text),
    }


# ---------------------------------------------------------------------
# Quick demonstration — run this file directly to see it in action.
# ---------------------------------------------------------------------
if __name__ == "__main__":
    print("=" * 70)
    print("REAL PDF -> EXTRACT -> STRIP  (the actual pipeline order)")
    print("=" * 70)

    result = process_resume("candidate_001", "sample_resume.pdf")

    print("\nSTEP 1 output - extracted text (full, stored for HR):")
    print(result["full_text"])

    print("\nSTEP 2 output - stripped text (this is what the AI sees):")
    print(result["stripped_text"])
    print("-" * 70)
