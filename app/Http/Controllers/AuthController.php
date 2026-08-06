<?php

namespace App\Http\Controllers;

use App\Models\Consent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'in:employer,candidate'],
            'consent' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $user->pdpa_consent_version = Consent::CURRENT_VERSION;
        $user->pdpa_consented_at = now();
        $user->save();

        Auth::login($user);

        return redirect('/');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // "/" itself now decides what to show by role (see HomeController) —
            // employers get Flask's dashboard relayed server-side from here.
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Flask has no logout endpoint of its own — without this, an employer
        // could log out of Laravel and still have an authenticated Flask
        // session ("session" cookie) sitting in their browser. path=/ and no
        // domain match exactly what Flask itself sets, so this clears it.
        return redirect('/')->withoutCookie('session');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!$user) {
            $user = (object) [
                'name' => 'Guest Visitor',
                'role' => 'candidate',
            ];
        }

        $role = $user->role ?? 'candidate';

        $employerStats = [
            'jobsPosted' => 4,
            'applications' => 18,
            'jobs' => [
                ['title' => 'Junior Software Engineer', 'status' => 'Live'],
                ['title' => 'Customer Support Executive', 'status' => 'Reviewing'],
                ['title' => 'Product Designer', 'status' => 'Draft'],
            ],
            'applicants' => [
                ['name' => 'Aisyah Rahman', 'job' => 'Customer Support Executive', 'status' => 'New'],
                ['name' => 'Hafiz Idris', 'job' => 'Junior Software Engineer', 'status' => 'Shortlisted'],
                ['name' => 'Nadiah Lim', 'job' => 'Product Designer', 'status' => 'Interview'],
            ],
        ];

        $candidateStats = [
            'appliedJobs' => 3,
            'savedJobs' => 5,
            'applied' => [
                ['title' => 'Junior Software Engineer', 'status' => 'Applied'],
                ['title' => 'Product Designer', 'status' => 'Interview'],
            ],
            'saved' => [
                ['title' => 'Operations Coordinator', 'company' => 'Northstar Labs'],
                ['title' => 'Marketing Analyst', 'company' => 'BrightPath'],
            ],
        ];

        return view('dashboard', [
            'user' => $user,
            'role' => $role,
            'employerStats' => $employerStats,
            'candidateStats' => $candidateStats,
        ]);
    }
}
