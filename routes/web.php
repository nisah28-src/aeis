<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Temporary static job data
|--------------------------------------------------------------------------
| Stand-in for a real Jobs database table. Replace with Job::find($id)
| once that table exists.
*/
$fakeJobs = [
    1 => [
        'title' => 'Junior Software Engineer',
        'company' => 'HireSense (internal)',
        'department' => 'Engineering',
        'type' => 'Full-time',
        'mode' => 'Hybrid',
        'posted_at' => now()->subDays(8)->toDateString(),
        'description' => 'Builds and maintains web applications, works with databases, '
            . 'collaborates with a team using Git, and applies foundational software '
            . 'engineering and system design knowledge.',
    ],
    2 => [
        'title' => 'Customer Support Executive',
        'company' => 'HireSense (internal)',
        'department' => 'Customer Support',
        'type' => 'Part-time',
        'mode' => 'Remote',
        'posted_at' => now()->subDays(42)->toDateString(),
        'description' => 'Handles inbound customer complaints, resolves service issues, '
            . 'escalates when needed, uses a CRM system, and requires clear '
            . 'communication and calm problem-solving under pressure.',
    ],
    3 => [
        'title' => 'Product Designer',
        'company' => 'HireSense (internal)',
        'department' => 'Design',
        'type' => 'Full-time',
        'mode' => 'Remote',
        'posted_at' => now()->subDays(2)->toDateString(),
        'description' => 'Shapes polished product experiences, contributes to design systems, and works closely with product and engineering teams to deliver thoughtful user journeys.',
    ],
    4 => [
        'title' => 'Operations Coordinator',
        'company' => 'HireSense (internal)',
        'department' => 'Operations',
        'type' => 'Part-time',
        'mode' => 'Hybrid',
        'posted_at' => now()->subDays(95)->toDateString(),
        'description' => 'Keeps delivery moving across people and process, coordinates schedules, tracks progress, and supports the team with clear follow-through and planning.',
    ],
];

$fakeCandidates = [
    1 => [
        'candidate' => ['name' => 'Aisyah binti Rahman'],
        'job' => ['title' => 'Customer Support Executive'],
        'evaluation' => [
            'strength' => 'Direct CRM and complaint-handling experience',
            'skills' => 'Customer service, CRM tools, complaint escalation',
            'experience' => '2 years in a directly relevant role',
            'trait' => 'Calm under pressure, based on how complaints are described',
            'question' => 'Walk me through resolving an escalated complaint end-to-end.',
        ],
    ],
];

Route::get('/', function () use ($fakeJobs) {
    return view('welcome', ['jobs' => $fakeJobs]);
});

Route::get('/jobs', function (Request $request) use ($fakeJobs) {
    $search = trim((string) $request->query('search', ''));
    $type = trim((string) $request->query('type', ''));
    $mode = trim((string) $request->query('mode', ''));

    $filteredJobs = [];

    foreach ($fakeJobs as $id => $job) {
        $matchesSearch = $search === ''
            || str_contains(strtolower($job['title'] . ' ' . $job['department'] . ' ' . $job['description']), strtolower($search));
        $matchesType = $type === '' || strtolower($job['type']) === strtolower($type);
        $matchesMode = $mode === '' || strtolower($job['mode']) === strtolower($mode);

        if ($matchesSearch && $matchesType && $matchesMode) {
            $filteredJobs[$id] = $job;
        }
    }

    return view('jobs.index', [
        'jobs' => $filteredJobs,
        'search' => $search,
        'type' => $type,
        'mode' => $mode,
    ]);
})->name('jobs.index');

Route::get('/jobs/create', function () {
    return view('jobs.create');
})->name('jobs.create');

Route::get('/jobs/{id}', function ($id) use ($fakeJobs) {
    abort_if(!isset($fakeJobs[$id]), 404);
    return view('jobs.show', ['job' => $fakeJobs[$id], 'jobId' => $id]);
})->name('jobs.show');

/*
|--------------------------------------------------------------------------
| THE REAL BRIDGE — this is the new part
|--------------------------------------------------------------------------
| Receives the application form submission, forwards the resume file to
| the Python Flask AI backend over HTTP (server-to-server, no CORS
| involved since the browser never talks to Flask directly), and shows
| whatever the AI actually returns.
*/
Route::post('/jobs/{id}/apply', function (Request $request, $id) use ($fakeJobs) {
    abort_if(!isset($fakeJobs[$id]), 404);
    $job = $fakeJobs[$id];

    // PHP's own execution timer defaults to 30s and would otherwise kill
    // this request with a raw fatal error BEFORE our own Http::timeout()
    // below ever gets a chance to fail gracefully. Extend it here so our
    // own timeout logic is what actually handles a slow response.
    set_time_limit(45);

    $request->validate([
        'name' => 'required|string',
        'email' => 'required|email',
        'resume' => 'required|file|mimes:pdf|max:5120',
    ]);

    $file = $request->file('resume');

    try {
        $response = Http::connectTimeout(10)->timeout(40)
            ->attach('resume', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post('http://127.0.0.1:5050/api/evaluate', [
                'role_description' => $job['description'],
            ]);
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        return back()->withErrors([
            'resume' => 'Could not reach the AI service. Is the Flask app running on port 5000?',
        ]);
    }

    if ($response->failed()) {
        return back()->withErrors(['resume' => 'The AI service returned an error. Please try again.']);
    }

    return view('jobs.applied', [
        'job' => $job,
        'result' => $response->json(),
    ]);
})->name('jobs.apply');

Route::get('/candidates/{id}', function ($id) use ($fakeCandidates) {
    abort_if(!isset($fakeCandidates[$id]), 404);
    $data = $fakeCandidates[$id];
    return view('candidates.show', [
        'candidate' => $data['candidate'],
        'job' => $data['job'],
        'evaluation' => $data['evaluation'],
    ]);
})->name('candidates.show');