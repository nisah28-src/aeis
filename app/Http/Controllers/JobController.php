<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Browser-remembered consent, checked against the version below so a
// re-visit after the notice changes doesn't silently skip re-consent.
const PDPA_CONSENT_COOKIE = 'pdpa_consent_v';

class JobController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $mode = trim((string) $request->query('mode', ''));

        $query = Job::query()->where('status', 'Active');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('job_title', 'ilike', $like)
                    ->orWhere('department', 'ilike', $like)
                    ->orWhere('responsibilities', 'ilike', $like);
            });
        }

        if ($type !== '') {
            $query->where('employment_type', $type);
        }

        if ($mode !== '') {
            $query->where('location', 'ilike', '%' . $mode . '%');
        }

        $jobs = $query->orderByDesc('created_at')->get()
            ->mapWithKeys(fn (Job $job) => [$job->id => $this->toJobArray($job)]);

        return view('jobs.index', [
            'jobs' => $jobs,
            'search' => $search,
            'type' => $type,
            'mode' => $mode,
        ]);
    }

    public function show(string $id, Request $request)
    {
        $job = Job::find($id);

        abort_if(!$job, 404);

        return view('jobs.show', [
            'job' => $this->toJobArray($job),
            'jobId' => $job->id,
            'alreadyConsented' => $request->cookie(PDPA_CONSENT_COOKIE) === Consent::CURRENT_VERSION,
        ]);
    }

    public function apply(Request $request, string $id)
    {
        $job = Job::find($id);

        abort_if(!$job, 404);

        set_time_limit(45);

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'resume' => 'required|file|mimes:pdf|max:5120',
            'consent' => 'required|accepted',
        ]);

        Consent::create([
            'email' => $request->input('email'),
            'job_id' => $job->id,
            'notice_version' => Consent::CURRENT_VERSION,
        ]);

        Cookie::queue(PDPA_CONSENT_COOKIE, Consent::CURRENT_VERSION, 60 * 24 * 365);

        $file = $request->file('resume');
        $fileContents = file_get_contents($file->getRealPath());

        // The real hand-off: write straight into the shared `applications`
        // table HR's dashboard reads from, so the application is visible
        // to HR the moment it's submitted — no manual upload on their end.
        $applicationId = (string) Str::uuid();

        DB::table('applications')->insert([
            'id' => $applicationId,
            'job_id' => $job->id,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'filename' => $file->getClientOriginalName(),
            'filedata' => DB::raw("'\\x" . bin2hex($fileContents) . "'"),
            'uploaded_at' => now()->toISOString(),
            'job_title' => $job->job_title,
            'job_desc' => trim($job->responsibilities . "\n\n" . $job->requirements),
            'status' => 'Applied',
            'candidate_user_id' => optional($request->user())->id,
        ]);

        // Best-effort candidate-facing AI preview — the local Flask demo
        // service isn't always running, and that must never block the
        // application above, which already succeeded.
        $result = ['candidate_id' => $applicationId];

        try {
            $response = Http::connectTimeout(5)->timeout(40)
                ->attach('resume', $fileContents, $file->getClientOriginalName())
                ->post('http://127.0.0.1:5050/api/evaluate', [
                    'role_description' => $job->responsibilities,
                ]);

            $json = $response->successful() ? $response->json() : null;
            if ($json && !isset($json['error'])) {
                // The real application ID (used to find this row in the
                // `applications` table) must win over Flask's own
                // throwaway in-memory candidate_id.
                $result = array_merge($result, $json, ['candidate_id' => $applicationId]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            // No live preview available — the application was still recorded above.
        }

        $result += [
            'relevance_label' => 'Submitted',
            'note' => 'Your application has been received and sent to the hiring team for review.',
            'suggested_question' => null,
        ];

        return view('jobs.applied', [
            'job' => $this->toJobArray($job),
            'result' => $result,
        ]);
    }

    protected function toJobArray(Job $job): array
    {
        return [
            'title' => $job->job_title,
            'company' => 'HireSense (internal)',
            'department' => $job->department ?: 'General',
            'type' => $job->employment_type ?: 'Full-time',
            'mode' => $job->location ?: 'On-site',
            'posted_at' => $job->created_at ? substr($job->created_at, 0, 10) : now()->toDateString(),
            'description' => $job->responsibilities,
        ];
    }
}
