<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'HireSense') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-slate-900">
    <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/80 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
            <a href="#" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-600 font-semibold text-white">HS</span>
                <span class="text-lg font-semibold text-slate-900">HireSense</span>
            </a>
            <nav class="hidden items-center gap-6 text-sm text-slate-600 md:flex">
                <a href="{{ route('jobs.index') }}" class="transition hover:text-violet-700">Browse jobs</a>
                <a href="#how-it-works" class="transition hover:text-violet-700">How it works</a>
                <a href="{{ route('register') }}" class="rounded-full border border-violet-200 px-4 py-2 font-medium text-violet-700 transition hover:bg-violet-50">Join HireSense</a>
                <a href="{{ route('jobs.create') }}" class="rounded-full bg-slate-900 px-4 py-2 font-medium text-white transition hover:bg-slate-700">Employer account</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="overflow-hidden bg-gradient-to-br from-violet-600 via-violet-700 to-slate-950 text-white">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-6 py-20 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-24">
                <div>
                    <p class="mb-4 inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-sm font-medium text-violet-100">
                        AI-assisted hiring, designed for real teams
                    </p>
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight sm:text-5xl">
                        Find sharper candidates without losing the human touch.
                    </h1>
                    <p class="mt-6 max-w-2xl text-lg text-violet-100/90">
                        HireSense helps hiring teams review resumes with context, structure, and clearer interview prompts — while keeping every decision human-led.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('resume-check.show') }}" class="rounded-full border border-white/30 px-6 py-3 text-center font-semibold text-white transition hover:bg-white/10">
                            Try resume check
                        </a>
                    </div>
                    <div class="mt-8 flex flex-wrap gap-4 text-sm text-violet-100/90">
                        <span class="rounded-full bg-white/10 px-3 py-1">⚡ Faster shortlist prep</span>
                        <span class="rounded-full bg-white/10 px-3 py-1">🛡️ Privacy-safe summaries</span>
                        <span class="rounded-full bg-white/10 px-3 py-1">🤝 Human decisions, always</span>
                    </div>
                </div>

                <div class="rounded-3xl border border-white/20 bg-white/10 p-5 shadow-2xl shadow-black/20 backdrop-blur">
                    <div class="rounded-2xl bg-white p-6 text-slate-900">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">This week</p>
                                <h2 class="mt-1 text-xl font-semibold">Fresh opportunities</h2>
                            </div>
                            <span class="rounded-full bg-violet-50 px-3 py-1 text-sm font-medium text-violet-700">12 live roles</span>
                        </div>
                        <div class="mt-6 space-y-3">
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="font-semibold">Customer Support Executive</p>
                                <p class="mt-1 text-sm text-slate-600">Customer Support • Remote friendly</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="font-semibold">Junior Software Engineer</p>
                                <p class="mt-1 text-sm text-slate-600">Engineering • Full-time</p>
                            </div>
                        </div>
                        <div class="mt-6 rounded-2xl bg-slate-900 p-4 text-sm text-slate-300">
                            <p class="font-semibold text-white">Candidate snapshot preview</p>
                            <p class="mt-2">“Strong CRM background with a calm approach to escalations and customer care.”</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-200 bg-white">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-6 px-6 py-6 lg:px-8">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Trusted by modern hiring teams</p>
                <div class="flex flex-wrap gap-4 text-sm font-medium text-slate-600">
                    <span class="rounded-full bg-slate-100 px-3 py-2">Northstar Labs</span>
                    <span class="rounded-full bg-slate-100 px-3 py-2">BrightPath</span>
                    <span class="rounded-full bg-slate-100 px-3 py-2">Lumen Works</span>
                    <span class="rounded-full bg-slate-100 px-3 py-2">Apex Studio</span>
                </div>
            </div>
        </section>

        <section id="open-roles" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">Featured roles</p>
                    <h2 class="mt-2 text-3xl font-semibold text-slate-900">Open positions worth a closer look</h2>
                </div>
                <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-violet-700 hover:underline">See all roles</a>
            </div>

            @if(!empty($jobs))
                <div class="mt-10 grid gap-6 lg:grid-cols-2">
                    @foreach($jobs as $id => $job)
                        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-violet-600">{{ $job['department'] }}</p>
                                    <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ $job['title'] }}</h3>
                                </div>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">New</span>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $job['description'] }}</p>
                            <div class="mt-5 flex flex-wrap gap-2 text-sm">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Full-time</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Remote friendly</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">Hybrid support</span>
                            </div>
                            <div class="mt-6 flex items-center justify-between">
                                <p class="text-sm text-slate-500">Posted recently</p>
                                <a href="{{ route('jobs.show', $id) }}" class="font-semibold text-violet-700 hover:underline">View role →</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-slate-600">
                    No roles are available right now. Check back soon.
                </div>
            @endif
        </section>

        <section id="how-it-works" class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">How it works</p>
                    <h2 class="mt-2 text-3xl font-semibold text-slate-900">A simple workflow that keeps hiring clear and fair</h2>
                </div>
                <div class="mt-10 grid gap-6 md:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-lg font-semibold text-violet-700">1</div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">Resume intake, privacy-safe</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Sensitive details are stripped before the review flow begins so the focus stays on skills and evidence.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-lg font-semibold text-emerald-700">2</div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">Structured AI support</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">The assistant prepares a snapshot, interview prompt, and candidate highlights without replacing human judgment.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-200 text-lg font-semibold text-slate-700">3</div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">Human-led decisions</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Every shortlist and hiring decision remains with your team, with clearer context to help you move faster.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white py-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-2 px-6 text-sm text-slate-500 lg:flex-row lg:items-center lg:justify-between lg:px-8">
            <p>&copy; {{ date('Y') }} HireSense. Built for focused, modern hiring.</p>
            <a href="{{ route('jobs.create') }}" class="font-semibold text-violet-700 hover:underline">Start hiring today</a>
        </div>
    </footer>
</body>
</html>