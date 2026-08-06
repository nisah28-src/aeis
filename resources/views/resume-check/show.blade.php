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
                <a href="{{ url('/') }}" class="hover:text-violet-700">← Back home</a>
            </nav>
        </div>
    </header>

    <section class="max-w-2xl mx-auto px-6 py-14">

        <div class="mb-1 text-xs font-semibold text-teal-700 uppercase tracking-wide">Free — no application required</div>
        <h1 class="text-2xl font-bold mb-1">Resume   Check</h1>
        <p class="text-sm text-neutral-500 mb-8">
            Upload your resume — see the skills and traits it actually shows,
            what roles genuinely fit, and one honest suggestion to grow.
        </p>

        <form method="POST" action="{{ route('resume-check.submit') }}" enctype="multipart/form-data"
              class="bg-white border border-neutral-200 rounded-xl p-8">
            @csrf

            <label class="block text-sm font-medium mb-1">Your resume (PDF)</label>
            <input type="file" name="resume" accept="application/pdf" required
                   class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-6">

            <button type="submit" class="bg-violet-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-violet-800 w-full">
                Check my resume
            </button>
        </form>

        @error('resume')
            <p class="mt-3 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3">{{ $message }}</p>
        @enderror

        <p class="text-xs text-neutral-400 mt-4 text-center">
            Name, IC, phone, and email are removed before anything reaches the AI.
            See our <a href="{{ route('pdpa') }}" class="underline hover:text-violet-700">Privacy Notice (PDPA)</a>.
        </p>

    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

</body>
</html>