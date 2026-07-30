<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Serves "/" for everyone. This is the one route that can't just be a
     * plain redirect to Flask's dashboard, because both apps want to own "/"
     * and Laravel's own explicit routes always win over the fallback proxy.
     * Instead: decide by role, and for employers, fetch Flask's dashboard
     * HTML server-side and return it directly in this same response — no
     * redirect, no second URL the browser ever sees.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return view('welcome');
        }

        if ($user->role !== 'employer') {
            return redirect()->route('dashboard');
        }

        // Employer: exchange a fresh one-time token for a Flask session server-side,
        // fetch the dashboard HTML using that session, and relay both back in one shot.
        $flaskBaseUrl = rtrim(config('services.flask.base_url'), '/');
        $token = Str::random(48);

        DB::table('handoff_tokens')->insert([
            'token'       => $token,
            'employer_id' => $user->id,
            'created_at'  => now()->timestamp,
            'expires_at'  => now()->addMinutes(2)->timestamp,
            'used'        => 0,
        ]);

        $handoffResponse = Http::withOptions(['allow_redirects' => false])
            ->get($flaskBaseUrl . '/auth/handoff', ['token' => $token]);

        $flaskCookieHeader = $handoffResponse->header('Set-Cookie');
        if (!$flaskCookieHeader) {
            abort(502, 'Could not establish an employer dashboard session with Flask.');
        }
        $flaskCookiePair = explode(';', $flaskCookieHeader)[0]; // "session=eyJ..."

        $dashboardResponse = Http::withHeaders(['Cookie' => $flaskCookiePair])->get($flaskBaseUrl . '/');

        // Set-Cookie is relayed VERBATIM (not via Laravel's cookie()/Cookie::queue()
        // helpers) so Laravel's own cookie encryption never touches Flask's session
        // value — Flask needs to read back exactly what it wrote.
        return response($dashboardResponse->body(), $dashboardResponse->status())
            ->header('Content-Type', $dashboardResponse->header('Content-Type'))
            ->header('Set-Cookie', $flaskCookieHeader);
    }
}
