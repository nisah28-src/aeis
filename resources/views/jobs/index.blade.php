<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse jobs • HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 font-semibold text-white">HS</span>
                <span class="text-lg font-semibold text-slate-900">HireSense</span>
            </a>
            <a href="{{ route('jobs.create') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Post a job</a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">Career opportunities</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-900">Find your next role</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">Search by keyword, then refine by work type and location style to quickly discover the best-fit openings.</p>
                </div>
                <a href="{{ url('/') }}" class="text-sm font-semibold text-violet-700 hover:underline">Back to home</a>
            </div>

            <form method="get" class="mt-8 grid gap-4 rounded-2xl bg-slate-50 p-4 md:grid-cols-[1.4fr_0.8fr_0.8fr_auto] md:items-end">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="search">Search</label>
                    <input id="search" name="search" value="{{ $search }}" placeholder="Engineer, support, design..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none ring-0 focus:border-violet-500" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="type">Work type</label>
                    <select id="type" name="type" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-violet-500">
                        <option value="">All types</option>
                        <option value="Full-time" @selected($type === 'Full-time')>Full-time</option>
                        <option value="Part-time" @selected($type === 'Part-time')>Part-time</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="mode">Work mode</label>
                    <select id="mode" name="mode" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-violet-500">
                        <option value="">All modes</option>
                        <option value="Hybrid" @selected($mode === 'Hybrid')>Hybrid</option>
                        <option value="Remote" @selected($mode === 'Remote')>Remote</option>
                    </select>
                </div>
                <button type="submit" class="rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-violet-700">Search roles</button>
            </form>
        </section>

        <section class="mt-8">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm text-slate-600">Showing {{ count($jobs) }} matching role{{ count($jobs) === 1 ? '' : 's' }}</p>
                <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-violet-700 hover:underline">Clear filters</a>
            </div>

            @if(count($jobs) > 0)
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach($jobs as $id => $job)
                        @php
                            $postedAt = $job['posted_at'] ?? now()->subDays(90)->toDateString();
                            $jobType = $job['type'] ?? 'Full-time';
                            $jobMode = $job['mode'] ?? 'Hybrid';
                            $daysAgo = max(0, (int) now()->parse($postedAt)->diffInDays(now()));
                            $isNew = $daysAgo <= 30;
                            $ageLabel = $daysAgo === 0
                                ? 'Posted today'
                                : ($daysAgo < 30
                                    ? 'Posted ' . $daysAgo . ' day' . ($daysAgo === 1 ? '' : 's') . ' ago'
                                    : ($daysAgo < 60
                                        ? 'Posted about a month ago'
                                        : ($daysAgo < 90
                                            ? 'Posted a couple of months ago'
                                            : 'Posted several months ago')));
                        @endphp
                        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-violet-600">{{ $job['department'] }}</p>
                                    <h2 class="mt-1 text-xl font-semibold text-slate-900">{{ $job['title'] }}</h2>
                                </div>
                                @if($isNew)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">New</span>
                                @endif
                            </div>
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $job['description'] }}</p>
                            <div class="mt-5 flex flex-wrap gap-2 text-sm">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">{{ $jobType }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">{{ $jobMode }}</span>
                            </div>
                            <div class="mt-6 flex items-center justify-between">
                                <p class="text-sm text-slate-500">{{ $ageLabel }}</p>
                                <a href="{{ route('jobs.show', $id) }}" class="font-semibold text-violet-700 hover:underline">View details →</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600">
                    No roles matched your filters. Try a broader search.
                </div>
            @endif
        </section>
    </main>
</body>
</html>
