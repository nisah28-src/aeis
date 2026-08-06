<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $job['title'] ?? 'Job' }} — HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-50 text-neutral-900">

    <header class="border-b border-neutral-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-semibold text-lg text-violet-800">HireSense</a>
            <nav class="flex gap-6 text-sm">
                <a href="{{ url('/') }}" class="hover:text-violet-700">← Back to open roles</a>
            </nav>
        </div>
    </header>

    <section class="max-w-3xl mx-auto px-6 py-14">

        {{-- ============ JOB DETAILS ============ --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-8 mb-6">
            <h1 class="text-2xl font-bold text-neutral-900">{{ $job['title'] ?? 'Junior Software Engineer' }}</h1>
            <p class="text-sm text-neutral-500 mt-1">{{ $job['company'] ?? 'HireSense (internal)' }} &middot; Posted recently</p>

            <p class="mt-5 text-neutral-700 leading-relaxed">
                {{ $job['description'] ?? 'Builds and maintains web applications, works with databases, collaborates with a team using Git, and applies foundational software engineering and system design knowledge.' }}
            </p>
        </div>

        {{-- ============ CANDIDATE ACTIONS ============ --}}
        <div class="bg-white border border-neutral-200 rounded-xl p-8 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <a href="#apply-form" class="inline-flex items-center rounded-full bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-800">
                    Apply now
                </a>
                <button type="button" class="inline-flex items-center rounded-full border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-700 hover:border-violet-600 hover:text-violet-700">
                    Save for later
                </button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-full border border-violet-200 bg-violet-50 px-4 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-100">
                    Track application
                </a>
            </div>
            <div class="mt-4 rounded-2xl border border-violet-100 bg-violet-50 p-4 text-sm text-violet-800">
                Keep your next step organised: apply, save standout roles, and follow progress from one candidate workspace.
            </div>
        </div>

        {{-- ============ APPLY FORM ============ --}}
        <div id="apply-form" class="bg-white border border-neutral-200 rounded-xl p-8">
            <h2 class="text-lg font-semibold mb-1">Apply for this role</h2>
            <p class="text-sm text-neutral-500 mb-6">
                Your name, IC, phone, and email are stripped before anything reaches the AI —
                see how that works on our project docs.
            </p>

            {{-- Now wired for real: submits to jobs.apply, which forwards the
                 file to the Python AI backend and shows the actual result. --}}
            <form method="POST" action="{{ route('jobs.apply', $jobId ?? 1) }}" enctype="multipart/form-data">
                @csrf
                <label class="block text-sm font-medium mb-1">Your name</label>
                <input type="text" name="name" required class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-4" placeholder="Full name">

                <label class="block text-sm font-medium mb-1">Email</label>
                <input type="email" name="email" required class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-4" placeholder="you@example.com">

                <label class="block text-sm font-medium mb-1">Resume (PDF)</label>
                <input type="file" name="resume" accept="application/pdf" required class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-6">

                <div class="flex items-start gap-3 mb-6">
                    <input id="consent" name="consent" type="checkbox" required value="1" @checked($alreadyConsented ?? false)
                           class="mt-1 h-4 w-4 rounded border-neutral-300 text-violet-600 focus:ring-violet-500">
                    @if($alreadyConsented ?? false)
                        <label for="consent" class="text-sm text-neutral-600">
                            You've already agreed to our <a href="{{ route('pdpa') }}" target="_blank" class="underline hover:text-violet-700">Privacy Notice (PDPA)</a> on this device — thanks. Uncheck to review it again.
                        </label>
                    @else
                        <label for="consent" class="text-sm text-neutral-600">
                            I have read and agree to the <a href="{{ route('pdpa') }}" target="_blank" class="underline hover:text-violet-700">Privacy Notice (PDPA)</a>, and consent to the collection and processing of my personal data for this application.
                        </label>
                    @endif
                </div>
                @error('consent')
                    <p class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ $message }}</p>
                @enderror

                <button type="submit" class="bg-violet-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-violet-800">
                    Submit application
                </button>
            </form>
            @error('resume')
                <p class="mt-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ $message }}</p>
            @enderror
        </div>

    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

</body>
</html>