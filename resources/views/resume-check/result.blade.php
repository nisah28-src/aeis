<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Check — HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-50 text-neutral-900">

    <header class="border-b border-neutral-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-semibold text-lg text-violet-800">HireSense</a>
            <nav class="flex gap-6 text-sm">
                <a href="{{ route('resume-check.show') }}" class="hover:text-violet-700">← Check another resume</a>
            </nav>
        </div>
    </header>

    <section class="max-w-xl mx-auto px-6 py-14">

        @php
            $error = $result['error'] ?? null;
            $skills = $result['skills'] ?? $result['skill_matches'] ?? [];
            $traits = $result['traits'] ?? $result['trait_matches'] ?? [];
            $roles = $result['suitable_roles'] ?? $result['roles'] ?? [];
            $growthSuggestion = $result['growth_suggestion'] ?? $result['suggestion'] ?? 'No growth suggestion was returned.';
        @endphp

        @if($error)
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <p class="text-sm text-red-800">{{ $error }}</p>
            </div>
        @else
            <div class="bg-violet-300 rounded-2xl p-8">
                <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Resume Check</h1>

                <p class="text-sm font-medium text-neutral-800 mb-2">Skills detected</p>
                <div class="bg-indigo-300 rounded-lg p-4 mb-6">
                    <div class="flex flex-wrap gap-2">
                        @forelse($skills as $skill)
                            <span class="bg-white/60 text-neutral-900 text-xs font-medium px-3 py-1 rounded-full">{{ is_array($skill) ? ($skill['name'] ?? $skill['title'] ?? '') : $skill }}</span>
                        @empty
                            <span class="text-sm text-neutral-800">No skills were returned.</span>
                        @endforelse
                    </div>
                </div>

                <p class="text-sm font-medium text-neutral-800 mb-2">Traits</p>
                <div class="bg-indigo-300 rounded-lg p-4 mb-6">
                    <div class="flex flex-wrap gap-2">
                        @forelse($traits as $trait)
                            <span class="bg-white/60 text-neutral-900 text-xs font-medium px-3 py-1 rounded-full">{{ is_array($trait) ? ($trait['name'] ?? $trait['title'] ?? '') : $trait }}</span>
                        @empty
                            <span class="text-sm text-neutral-800">No traits were returned.</span>
                        @endforelse
                    </div>
                </div>

                <p class="text-sm font-medium text-neutral-800 mb-2">Fit job/roles</p>
                <div class="bg-indigo-300 rounded-lg p-4 mb-6 space-y-3">
                    @forelse($roles as $role)
                        <div>
                            <p class="text-sm font-semibold text-neutral-900">{{ is_array($role) ? ($role['title'] ?? $role['name'] ?? 'Role') : $role }}</p>
                            @if(is_array($role) && isset($role['reason']))
                                <p class="text-xs text-neutral-800">{{ $role['reason'] }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-neutral-800">No role matches were returned.</p>
                    @endforelse
                </div>

                <p class="text-sm font-medium text-neutral-800 mb-2">Suggested growth</p>
                <div class="bg-indigo-300 rounded-lg p-4">
                    <p class="text-sm text-neutral-900">{{ $growthSuggestion }}</p>
                </div>
            </div>

            <p class="text-xs text-neutral-400 mt-4 text-center">
                No score, no ranking — just what's actually in your resume.
            </p>
        @endif

        <div class="mt-6 text-center">
            <a href="{{ route('resume-check.show') }}" class="text-sm text-violet-700 font-medium hover:underline">
                Check a different resume →
            </a>
        </div>

    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

</body>
</html>