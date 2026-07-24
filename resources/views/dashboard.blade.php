<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard • HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-7xl px-6 py-12">
        <div class="flex flex-col gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">Welcome back</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ ucfirst($role) }} Dashboard</h1>
                <p class="mt-2 text-sm text-slate-600">Hello, {{ $user->name }}. This is your starter workspace.</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Log out</button>
            </form>
        </div>

        @if($role === 'employer')
            <div class="mt-8 grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Total jobs posted</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $employerStats['jobsPosted'] }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Applications received</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $employerStats['applications'] }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Active hiring funnel</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">3 stages</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold">Your posted jobs</h2>
                        <a href="{{ route('jobs.create') }}" class="text-sm font-semibold text-violet-700 hover:underline">Post new</a>
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach($employerStats['jobs'] as $job)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-slate-900">{{ $job['title'] }}</p>
                                    <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $job['status'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Applicant overview</h2>
                    <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Applicant</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Role</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                @foreach($employerStats['applicants'] as $applicant)
                                    <tr>
                                        <td class="px-4 py-3 text-slate-800">{{ $applicant['name'] }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ $applicant['job'] }}</td>
                                        <td class="px-4 py-3">
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $applicant['status'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="mt-8 grid gap-6 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Jobs applied</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $candidateStats['appliedJobs'] }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Saved jobs</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $candidateStats['savedJobs'] }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold text-slate-500">Profile strength</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">Strong</p>
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Recent applications</h2>
                    <div class="mt-5 space-y-3">
                        @foreach($candidateStats['applied'] as $application)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-slate-900">{{ $application['title'] }}</p>
                                    <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $application['status'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Saved jobs</h2>
                    <div class="mt-5 space-y-3">
                        @foreach($candidateStats['saved'] as $saved)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">{{ $saved['title'] }}</p>
                                <p class="mt-1 text-sm text-slate-600">{{ $saved['company'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
