<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

        try {
            $handoffResponse = Http::withOptions(['allow_redirects' => false])
                ->timeout(15)
                ->connectTimeout(10)
                ->get($flaskBaseUrl . '/auth/handoff', ['token' => $token]);
        } catch (Throwable $e) {
            // Flask lives on its own Render service and spins down after
            // idling — the first request after that can hang well past a
            // normal timeout. Without this, that exception used to bubble up
            // uncaught and render as a blank page (APP_DEBUG=false in prod).
            Log::warning('Flask handoff request failed', ['error' => $e->getMessage()]);

            return $this->dashboardUnavailable();
        }

        $flaskCookieHeader = $handoffResponse->header('Set-Cookie');
        if (!$flaskCookieHeader) {
            Log::warning('Flask handoff returned no session cookie', ['status' => $handoffResponse->status()]);

            return $this->dashboardUnavailable();
        }
        $flaskCookiePair = explode(';', $flaskCookieHeader)[0]; // "session=eyJ..."

        try {
            $dashboardResponse = Http::withHeaders(['Cookie' => $flaskCookiePair])
                ->timeout(15)
                ->connectTimeout(10)
                ->get($flaskBaseUrl . '/');
        } catch (Throwable $e) {
            Log::warning('Flask dashboard fetch failed', ['error' => $e->getMessage()]);

            return $this->dashboardUnavailable();
        }

        // Set-Cookie is relayed VERBATIM (not via Laravel's cookie()/Cookie::queue()
        // helpers) so Laravel's own cookie encryption never touches Flask's session
        // value — Flask needs to read back exactly what it wrote.
        return response($dashboardResponse->body(), $dashboardResponse->status())
            ->header('Content-Type', $dashboardResponse->header('Content-Type'))
            ->header('Set-Cookie', $flaskCookieHeader);
    }

    private function dashboardUnavailable()
    {
        return response()->view('errors.flask-unavailable', [], 503);
    }
}
