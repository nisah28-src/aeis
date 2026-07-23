<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in • HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-6 py-12">
        <div class="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">Welcome back</p>
                <h1 class="mt-2 text-3xl font-semibold">Log in to HireSense</h1>
            </div>

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email address</label>
                    <input id="email" name="email" type="email" required class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="password">Password</label>
                    <input id="password" name="password" type="password" required class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                </div>
                <button type="submit" class="w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white transition hover:bg-violet-700">Log in</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                New here?
                <a href="{{ route('register') }}" class="font-semibold text-violet-700 hover:underline">Create an account</a>
            </p>
        </div>
    </div>
</body>
</html>
