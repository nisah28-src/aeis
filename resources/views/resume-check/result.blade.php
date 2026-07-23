<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your resume check — HireSense</title>
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

    <section class="max-w-2xl mx-auto px-6 py-14">

        <h1 class="text-xl font-semibold mb-1">Your resume, honestly</h1>
        <p class="text-sm text-neutral-500 mb-8">Checked against: {{ $target_role_title }}</p>

        @if(isset($result['error']))
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <p class="text-sm text-red-800">{{ $result['error'] }}</p>
            </div>
        @else
            <div class="bg-white border border-neutral-200 rounded-xl p-6 mb-5">
                <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full bg-violet-100 text-violet-800 mb-4">
                    {{ $result['relevance_label'] ?? '' }}
                </span>
                <p class="text-sm text-neutral-800 leading-relaxed">{{ $result['note'] ?? '' }}</p>
            </div>

            @if(!empty($result['suggested_question']))
            <div class="bg-teal-50 border border-teal-200 rounded-xl p-6 mb-5">
                <p class="text-xs font-semibold text-teal-800 uppercase tracking-wide mb-2">A question you might be asked</p>
                <p class="text-sm text-neutral-800">{{ $result['suggested_question'] }}</p>
                <p class="text-xs text-neutral-500 mt-3">Worth preparing an answer for this before you apply.</p>
            </div>
            @endif

            <div class="bg-violet-50 border border-violet-200 rounded-xl p-5">
                <p class="text-xs text-violet-800">
                    This is the same honest, no-score evaluation an employer using HireSense would see —
                    nothing here is hidden or different for you.
                </p>
            </div>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('resume-check.show') }}" class="text-sm text-violet-700 font-medium hover:underline">
                Check a different role or resume →
            </a>
        </div>

    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

</body>
</html>