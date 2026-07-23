<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $candidate['name'] ?? 'Candidate' }} — HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-50 text-neutral-900">

    <header class="border-b border-neutral-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-semibold text-lg text-violet-800">HireSense</a>
            <nav class="flex gap-6 text-sm">
                <a href="#" class="hover:text-violet-700">← Back to candidates</a>
            </nav>
        </div>
    </header>

    <section class="max-w-6xl mx-auto px-6 py-10">

        <div class="mb-6">
            <h1 class="text-xl font-semibold">{{ $candidate['name'] ?? 'Aisyah binti Rahman' }}</h1>
            <p class="text-sm text-neutral-500">Applied for {{ $job['title'] ?? 'Customer Support Executive' }}</p>
        </div>

        {{-- ============ SINGLE BLUE CONTAINER, TWO PANELS INSIDE ============ --}}
        <div class="bg-sky-100 rounded-2xl p-6">
            <div class="grid grid-cols-3 gap-4">

                {{-- Resume thumbnail (left, small) --}}
                <div class="col-span-1 bg-sky-200 rounded-xl border border-sky-300 h-80 flex items-center justify-center">
                    <div class="text-center text-sky-800">
                        <div class="text-3xl mb-1">📄</div>
                        <p class="text-xs font-medium">Resume image</p>
                    </div>
                </div>

                {{-- AI evaluation (right, large) --}}
                <div class="col-span-2 bg-white rounded-xl border border-sky-300 h-80 p-5 overflow-y-auto">
                    <h2 class="font-semibold text-neutral-900 mb-3">AI evaluation from resume snapshot</h2>

                    <ul class="space-y-2 text-sm text-neutral-800">
                        <li><span class="font-medium">Strength:</span> {{ $evaluation['strength'] ?? 'Direct CRM and complaint-handling experience' }}</li>
                        <li><span class="font-medium">Skills:</span> {{ $evaluation['skills'] ?? 'Customer service, CRM tools, complaint escalation' }}</li>
                        <li><span class="font-medium">Experience:</span> {{ $evaluation['experience'] ?? '2 years in a directly relevant role' }}</li>
                        <li><span class="font-medium">Trait that might be relevant:</span> {{ $evaluation['trait'] ?? 'Calm under pressure, based on how complaints are described' }}</li>
                    </ul>

                    <div class="mt-4 pt-3 border-t border-neutral-200">
                        <p class="text-xs text-neutral-500 font-medium mb-1">Suggested question</p>
                        <p class="text-sm text-neutral-800">{{ $evaluation['question'] ?? 'Walk me through resolving an escalated complaint end-to-end.' }}</p>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-6 flex gap-3">
            <button class="bg-white border border-neutral-300 px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-neutral-100">Not now</button>
            <button class="bg-violet-700 text-white px-5 py-2.5 rounded-lg text-sm font-medium hover:bg-violet-800">Shortlist</button>
        </div>

    </section>

</body>
</html>