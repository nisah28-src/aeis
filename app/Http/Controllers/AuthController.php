<?php

namespace App\Http\Controllers;

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
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        Auth::login($user);

        return redirect()->route('dashboard');
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

            return redirect()->intended(route('dashboard'));
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

        return redirect('/');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
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
