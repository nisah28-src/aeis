<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application received — HireSense</title>
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

        <h1 class="text-xl font-semibold mb-1">Application received</h1>
        <p class="text-sm text-neutral-500 mb-8">For {{ $job['title'] ?? 'this role' }}</p>

        @if(isset($result['error']))
            <div class="bg-red-50 border border-red-200 rounded-xl p-6">
                <p class="text-sm text-red-800">{{ $result['error'] }}</p>
            </div>
        @else
            <div class="bg-teal-50 border border-teal-200 rounded-xl p-6 mb-6">
                <p class="text-sm text-teal-900">
                    Your resume was processed — name, IC, phone, and email were removed before
                    anything reached the AI. This is the real output, generated live.
                </p>
            </div>

            <div class="bg-white border border-neutral-200 rounded-xl p-6">
                <span class="inline-block text-xs font-semibold px-3 py-1 rounded-full bg-violet-100 text-violet-800 mb-3">
                    {{ $result['relevance_label'] ?? 'Processed' }}
                </span>
                <p class="text-sm text-neutral-800 mb-4">{{ $result['note'] ?? '' }}</p>
                <div class="pt-4 border-t border-neutral-200">
                    <p class="text-xs text-neutral-500 font-medium mb-1">A question you might be asked</p>
                    <p class="text-sm text-neutral-800">{{ $result['suggested_question'] ?? '' }}</p>
                </div>
            </div>

            <p class="text-xs text-neutral-400 mt-4">Reference ID: {{ $result['candidate_id'] ?? 'n/a' }}</p>
        @endif

    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

</body>
</html>
