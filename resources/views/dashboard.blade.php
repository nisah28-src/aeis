<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard • HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-6xl px-6 py-12">
        <div class="flex items-center justify-between rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">Welcome back</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ ucfirst($role) }} Dashboard</h1>
                <p class="mt-2 text-sm text-slate-600">Hello, {{ $user->name }}. Your role-based workspace is ready.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Log out</button>
            </form>
        </div>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold">Quick actions</h2>
                <p class="mt-2 text-sm text-slate-600">This is the foundation for role-based workflows.</p>
                <div class="mt-6 space-y-3">
                    @if($role === 'employer')
                        <div class="rounded-2xl bg-violet-50 p-4 text-sm text-violet-900">Post jobs, review applicants, and manage hiring activity.</div>
                    @else
                        <div class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900">Browse roles, manage applications, and track your profile.</div>
                    @endif
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold">Next steps</h2>
                <p class="mt-2 text-sm text-slate-600">We’ll build the deeper permissioning and workflows next.</p>
                <ul class="mt-6 list-disc space-y-2 pl-5 text-sm text-slate-600">
                    <li>Role-based access checks</li>
                    <li>Employer-specific job management</li>
                    <li>Candidate-specific application tracking</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
