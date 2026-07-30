<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate login • HireSense</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(129,140,248,0.22),_transparent_35%),linear-gradient(135deg,_#0f172a_0%,_#111827_100%)]">
        <div class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-12 lg:px-8">
            <div class="grid w-full overflow-hidden rounded-[2rem] border border-white/10 bg-white/95 shadow-2xl shadow-black/25 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="bg-slate-900 p-8 text-white sm:p-10 lg:p-12">
                    <p class="text-sm font-semibold uppercase tracking-[0.3em] text-violet-300">For candidates</p>
                    <h1 class="mt-4 text-3xl font-semibold sm:text-4xl">Continue your hiring journey with confidence.</h1>
                    <p class="mt-4 max-w-xl text-base leading-7 text-slate-300">
                        Sign in to review your applications, save roles you love, and keep your next opportunity moving forward.
                    </p>

                    <div class="mt-8 space-y-3 rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <p class="text-sm text-slate-200">Track every application from one simple dashboard.</p>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-violet-400"></span>
                            <p class="text-sm text-slate-200">Save standout roles and revisit them whenever you are ready.</p>
                        </div>
                    </div>
                </div>

                <div class="p-8 sm:p-10 lg:p-12">
                    <div class="mb-8 text-center lg:text-left">
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-violet-600">Welcome back</p>
                        <h2 class="mt-2 text-3xl font-semibold text-slate-900">Log in to HireSense</h2>
                        <p class="mt-2 text-sm text-slate-600">Use your email and password to access your candidate workspace.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                            <p class="font-medium">We could not sign you in.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-200" />
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" for="password">Password</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-slate-900 shadow-sm outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-200" />
                        </div>
                        <button type="submit" class="w-full rounded-2xl bg-violet-600 px-4 py-3 font-semibold text-white transition hover:bg-violet-700">Log in</button>
                    </form>

                    <p class="mt-6 text-center text-sm text-slate-600 lg:text-left">
                        New here?
                        <a href="{{ route('register') }}" class="font-semibold text-violet-700 hover:underline">Create your account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
