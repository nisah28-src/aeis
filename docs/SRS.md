# Software Requirements Specification (SRS)

## HireSense (aeis) — AI-Assisted Resume Screening & Job Board

**Version:** 0.1 (draft, reverse-engineered from current codebase)
**Date:** 2026-08-04
**Status:** Working draft for PRD input — not yet reviewed/approved

---

## 1. Introduction

### 1.1 Purpose
This document specifies the functional and non-functional requirements of HireSense, a recruitment platform that lets employers post jobs and screen candidates with AI-generated relevance snapshots, while giving candidates a place to browse jobs, apply, and get an AI-based resume health check. It is derived from the current implementation to serve as a baseline for the PRD and future development.

### 1.2 Scope
The system consists of two cooperating backend services presented to the user as a single application:
- A **Laravel (PHP)** application that owns authentication, job listings, applications, and all persistent data.
- A **Flask (Python)** service that owns AI resume processing (PII stripping, candidate snapshot generation) and the employer dashboard SPA (React), reached only via server-to-server calls or a transparent Laravel proxy.

### 1.3 Definitions, Acronyms, Abbreviations
| Term | Meaning |
|---|---|
| PII | Personally Identifiable Information (name, IC number, phone, email, address) |
| IC | Malaysian Identity Card number |
| SPA | Single-Page Application |
| Snapshot | The AI's non-numeric relevance assessment of a candidate against a role |
| Handoff token | Single-use token Laravel issues to establish a Flask session for an employer |

### 1.4 References
- [routes/web.php](../routes/web.php) — Laravel route table
- [app.py](../app.py), [strip_pii.py](../strip_pii.py), [candidate_snapshot.py](../candidate_snapshot.py) — Flask AI service
- [docs/logout-redirect-bug.md](logout-redirect-bug.md) — open defect log

---

## 2. Overall Description

### 2.1 Product Perspective
HireSense is not a single monolith: Laravel is the system of record and public entry point; Flask is a satellite AI/UI service proxied transparently through Laravel so the browser only ever sees one origin. Flask is deployed separately (Render) and can cold-start or be unreachable, which the system must degrade gracefully around.

### 2.2 User Classes
| User class | Description |
|---|---|
| **Guest** | Unauthenticated visitor; sees marketing/welcome page and public job listings |
| **Candidate** | Registered job seeker; browses/applies to jobs, saves jobs, views own applications, uses the standalone resume health check |
| **Employer** | Registered hiring user; views dashboard (jobs posted, applicants, statuses), relies on AI snapshots to prioritize review |

### 2.3 Operating Environment
- Laravel app: PHP 8.1+, SQLite (default)/MySQL/Postgres, served via `php artisan serve` or a web server
- Flask service: Python 3, runs on port 5050 locally / a separate Render web service in production
- Frontend build: Vite + Tailwind (Laravel side); React (Flask-served employer dashboard)

### 2.4 Assumptions & Dependencies
- The Flask AI service depends on an `ANTHROPIC_API_KEY`; if absent, AI steps are skipped but the surrounding feature (application submission, resume upload) must still succeed.
- The Flask service may be cold or offline at any time (Render idle spin-down) — every call into it from Laravel must have a timeout and a defined fallback.
- Only text-layer PDFs are supported for resume parsing; scanned/image-only PDFs are explicitly rejected with a manual-review message.

---

## 3. Functional Requirements

### FR-1 — Authentication & Account Management
| ID | Requirement |
|---|---|
| FR-1.1 | The system shall allow a new user to register with name, email, password (confirmed, min. 8 chars), and a role of either `employer` or `candidate`. |
| FR-1.2 | The system shall allow a registered user to log in with email/password and shall regenerate the session on successful login. |
| FR-1.3 | The system shall allow a logged-in user to log out, which shall: (a) invalidate the Laravel session, (b) regenerate the CSRF token, and (c) clear the Flask session cookie so no authenticated Flask session survives a Laravel logout. |
| FR-1.4 | The system shall expose a CSRF-exempt logout endpoint (`POST /auth/logout`) reachable from the Flask-rendered SPA, which has no access to a Laravel CSRF token. |
| FR-1.5 | After logout, the system shall return the user to the guest-facing root page. |
| FR-1.6 | The system shall route an authenticated user's `/` request to the correct view based on role (employer → relayed Flask dashboard; candidate → Laravel dashboard). |

### FR-2 — Employer Dashboard
| ID | Requirement |
|---|---|
| FR-2.1 | The system shall issue a single-use handoff token (2-minute expiry) per employer visit to `/`, exchange it server-side for a Flask session, and relay the resulting dashboard HTML in the same response (no client-visible redirect). |
| FR-2.2 | If the Flask service does not respond, does not return a session cookie, or errors, the system shall render a "dashboard unavailable" page (HTTP 503) rather than a blank page or uncaught exception. |
| FR-2.3 | The employer dashboard shall display: number of jobs posted, number of applications received, a list of jobs with status (Live/Reviewing/Draft), and a list of applicants with status (New/Shortlisted/Interview). |
| FR-2.4 | The employer dashboard's logout control shall, after the logout request succeeds, explicitly navigate the browser to `/`. *(Currently unmet — see [Known Issues](#6-known-issues--defects), FR-2.4 is not satisfied by the present implementation.)* |

### FR-3 — Candidate Dashboard
| ID | Requirement |
|---|---|
| FR-3.1 | The candidate dashboard shall display the candidate's applied jobs with status and their saved jobs. |

### FR-4 — Job Listings
| ID | Requirement |
|---|---|
| FR-4.1 | The system shall list only jobs with status `Active` on the public jobs index. |
| FR-4.2 | The system shall allow filtering the jobs index by free-text search (matched against title, department, responsibilities), employment type, and work mode/location. |
| FR-4.3 | The system shall provide a job detail view for a single job listing. |
| FR-4.4 | The system shall associate each job listing with an owning employer (`employer_id`). |
| FR-4.5 | The system shall provide a job-creation view. *(Not yet wired to persistence — creation does not currently save a job.)* |

### FR-5 — Job Application
| ID | Requirement |
|---|---|
| FR-5.1 | The system shall allow a candidate to apply to a job with name, email, and a resume file (PDF only, ≤5MB). |
| FR-5.2 | The system shall persist every application to the shared `applications` table immediately upon submission, independent of AI service availability. |
| FR-5.3 | The system shall attempt a best-effort AI evaluation preview for the applicant (via the Flask `/api/evaluate` endpoint) with a bounded timeout, and shall not fail or delay the application if the AI service is unavailable. |
| FR-5.4 | The system shall show the candidate a confirmation view including the AI preview when available, or a generic "received" message when not. |
| FR-5.5 | The system shall allow a candidate to save/bookmark a job listing. *(Data model exists; no controller currently exposes this to users.)* |

### FR-6 — Resume Intake & PII Protection
| ID | Requirement |
|---|---|
| FR-6.1 | The system shall verify that an uploaded file is a genuine PDF by inspecting its file header, not merely its extension. |
| FR-6.2 | The system shall extract plain text from a PDF using layout-preserving extraction before any further processing. |
| FR-6.3 | The system shall reject PDFs with no extractable text layer (e.g., scanned images) with a message indicating manual review is needed. |
| FR-6.4 | The system shall strip high-confidence PII (IC number, phone number, email address, best-effort address line, candidate name) from extracted resume text before that text is sent to any AI model. |
| FR-6.5 | The system shall retain the full (unstripped) resume text in storage for human (HR) review; only the stripped copy shall ever be transmitted to the AI. |
| FR-6.6 | PII stripping shall recognize Malaysian naming conventions (e.g., `binti`, `bin`, `a/l`, `a/p`) so that lowercase naming particles do not prevent a full name from being detected and redacted. |

### FR-7 — AI-Assisted Candidate Evaluation
| ID | Requirement |
|---|---|
| FR-7.1 | For a resume evaluated against a specific role, the system shall produce exactly: a short non-numeric relevance label, a one-sentence rationale (≤20 words), and one suggested interview question (≤15 words). |
| FR-7.2 | The system shall never numerically score, rank, or reject a candidate; every candidate shall receive a full analysis. |
| FR-7.3 | The evaluation shall distinguish between a resume that merely lacks matching keywords but shows relevant real-world experience, versus one that is missing a genuine, non-negotiable core skill required by the role — and shall label these differently. |
| FR-7.4 | The system shall provide a role-independent "general assessment" given only a resume: 3–6 grounded skills, 2–4 behavioral traits, exactly 3 suitable job roles with justification, and one growth suggestion — with no invented content beyond what the resume evidences. |
| FR-7.5 | The system shall expose a candidate-facing "Resume Health Check" that performs the general assessment without requiring a job application. |
| FR-7.6 | If no AI API key is configured, the system shall skip the AI step and clearly communicate that skip rather than failing the surrounding request. |

### FR-8 — Service Bridge / Reverse Proxy
| ID | Requirement |
|---|---|
| FR-8.1 | The system shall transparently forward any request not matched by an explicit Laravel route to the Flask service, byte-for-byte, preserving path, query string, method, and body. |
| FR-8.2 | The proxy shall support all HTTP verbs Flask requires (GET, POST, PATCH, DELETE), not only GET. |
| FR-8.3 | The proxy shall correctly rebuild multipart/form-data request bodies (file uploads) since the raw body stream is already consumed by the time Laravel sees the request. |
| FR-8.4 | The proxy shall normalize and strip hop-by-hop/framing headers (`Transfer-Encoding`, `Content-Encoding`, `Connection`) to avoid conflicting with headers Laravel computes for the relayed response. |

---

## 4. Data Requirements

| Entity | Key fields | Notes |
|---|---|---|
| `users` | name, email, password (hashed), role (`employer`\|`candidate`) | Auth source of truth |
| `job_listings` | id (string), employer_id (FK→users), job_title, department, employment_type, location, responsibilities, requirements, status | Renamed from `job_postings` |
| `applications` | id (uuid), job_id (FK), candidate_user_id (FK, nullable), name, email, filename, filedata (bytea), status | Renamed from `candidates`; resume stored as binary in-row |
| `saved_jobs` | candidate_user_id (FK), job_id (FK), unique(candidate_user_id, job_id) | Bookmarking |
| `handoff_tokens` | token, employer_id, created_at, expires_at (2 min), used | Single-use SSO bridge to Flask |

---

## 5. Non-Functional Requirements

| ID | Category | Requirement |
|---|---|---|
| NFR-1 | Availability | Loss of the Flask AI/dashboard service shall degrade functionality gracefully (visible error state, no data loss, no blocked writes) rather than causing a full outage of the Laravel application. |
| NFR-2 | Performance | Outbound calls from Laravel to Flask shall use bounded connect/read timeouts (observed: 5–15s connect, 40s read) so a slow or cold Flask instance cannot hang a request indefinitely. |
| NFR-3 | Privacy | Resume text shall never be sent to the AI model in unstripped form; PII stripping shall run before any external AI call, with no code path that bypasses this ordering. |
| NFR-4 | Security | File uploads shall be validated by content (magic bytes), not filename/extension; size shall be capped (5MB) at both parse layers. |
| NFR-5 | Security | CSRF protection shall remain enabled for all Laravel-owned form routes; exemptions shall be limited to the two documented cross-service bridge routes (`/auth/logout` alias, the Flask proxy catch-all) and shall not be extended without a documented reason. |
| NFR-6 | Reliability | Bulk/batch resume processing shall isolate per-item failures so one malformed file cannot abort processing of the remaining batch. |
| NFR-7 | Data integrity | A job application record shall be persisted before any best-effort AI enrichment is attempted, so AI/service failure can never cause application data loss. |

---

## 6. Known Issues / Defects

| ID | Description | Status |
|---|---|---|
| BUG-1 | Employer dashboard logout: session/cookie are correctly cleared server-side, but the SPA never navigates the browser afterward (`fetch()` follows the 302 internally). Confirmed as a Flask/React-side defect, not a Laravel defect. A Laravel-side stopgap was tried and deliberately reverted. | Open — see [logout-redirect-bug.md](logout-redirect-bug.md) |
| GAP-1 | `saved_jobs` table and relations exist but no controller/route exposes save/unsave to users. | Not implemented |
| GAP-2 | `/jobs/create` renders a view but does not persist a new job listing. | Not implemented |

---

## 7. Out of Scope (current version)
- Numeric candidate scoring/ranking/rejection — explicitly rejected by design (AI evaluation always returns qualitative output).
- Address-level and photo-level PII redaction (regex only handles line-level heuristics; embedded images are not scanned).
- Asynchronous/queued AI processing — all AI calls are currently synchronous within the request lifecycle (queue infrastructure exists but is unused in practice).
