<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FlaskProxyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\JobController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

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

Route::get('/', [HomeController::class, 'index']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Same handler, reachable from inside Flask's own dashboard HTML — that page
// has no Laravel CSRF token available to it (it's Flask's markup, not a Blade
// view), so this alias is exempted from CSRF specifically for that reason.
Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->withoutMiddleware(PreventRequestForgery::class);
Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

Route::get('/jobs', [JobController::class, 'index'])->name('jobs.index');

Route::get('/jobs/create', function () {
    return view('jobs.create');
})->name('jobs.create');

Route::get('/jobs/{id}', [JobController::class, 'show'])->name('jobs.show');

/*
|--------------------------------------------------------------------------
| THE REAL BRIDGE
|--------------------------------------------------------------------------
| Receives the application form submission, forwards the resume file to
| the Python Flask AI backend over HTTP (server-to-server, no CORS
| involved since the browser never talks to Flask directly), and shows
| whatever the AI actually returns.
*/
Route::post('/jobs/{id}/apply', [JobController::class, 'apply'])->name('jobs.apply');

Route::get('/candidates/{id}', function ($id) use ($fakeCandidates) {
    abort_if(!isset($fakeCandidates[$id]), 404);
    $data = $fakeCandidates[$id];
    return view('candidates.show', [
        'candidate' => $data['candidate'],
        'job' => $data['job'],
        'evaluation' => $data['evaluation'],
    ]);
})->name('candidates.show');

/*
|--------------------------------------------------------------------------
| RESUME HEALTH CHECK — candidate-facing, no job application required
|--------------------------------------------------------------------------
| Calls /api/assess-general on the Flask side — a genuinely different
| endpoint from job applications, since no target role is involved.
| Returns skills, traits, 3 suitable roles, and one growth suggestion.
*/
Route::get('/resume-check', function () {
    return view('resume-check.show');
})->name('resume-check.show');

Route::get('/pdpa', function () {
    return view('pdpa');
})->name('pdpa');

Route::post('/resume-check', function (Request $request) {
    set_time_limit(45);

    $request->validate([
        'resume' => 'required|file|mimes:pdf|max:5120',
    ]);

    $file = $request->file('resume');

    try {
        $response = Http::connectTimeout(10)->timeout(40)
            ->attach('resume', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post('http://127.0.0.1:5050/api/assess-general');
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        return back()->withErrors([
            'resume' => 'Could not reach the AI service. Is the Flask app running on port 5050?',
        ]);
    }

    if ($response->failed()) {
        return back()->withErrors(['resume' => 'The AI service returned an error. Please try again.']);
    }

    return view('resume-check.result', [
        'result' => $response->json(),
    ]);
})->name('resume-check.submit');


/*
|--------------------------------------------------------------------------
| FLASK HR SIDE — single-URL merge
|--------------------------------------------------------------------------
| Must be the LAST route registered — matches anything nothing else claimed
| (/jobs-app assets, /candidates API, /portal/<token>, /auth/*, etc.) and
| forwards it to Flask byte-for-byte. CSRF is exempted here specifically:
| the React SPA served through this proxy has no way to obtain Laravel's
| token, so its own POST/PATCH/DELETE calls would otherwise 419.
|
| NOT Route::fallback() — that only ever registers for GET, so every write
| Flask needs (POST /upload, /screen-all, PATCH /candidate/{id}/status,
| DELETE /candidate/{id}) would 405 at Laravel's own router before ever
| reaching this controller. Route::any() matches every verb.
*/
Route::any('{any}', [FlaskProxyController::class, 'proxy'])
    ->where('any', '.*')
    ->withoutMiddleware(PreventRequestForgery::class);