<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register • HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-6 py-12">
        <div class="w-full max-w-2xl rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-8 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600">Create your account</p>
                <h1 class="mt-2 text-3xl font-semibold">Join HireSense</h1>
                <p class="mt-3 text-sm text-slate-600">Start as an employer to post jobs or as a candidate to explore opportunities.</p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="name">Full name</label>
                    <input id="name" name="name" type="text" required class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="email">Email address</label>
                    <input id="email" name="email" type="email" required class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700" for="password">Password</label>
                        <input id="password" name="password" type="password" required class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border border-slate-300 px-4 py-3" />
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700" for="role">Choose your role</label>
                    <select id="role" name="role" required class="w-full rounded-xl border border-slate-300 px-4 py-3">
                        <option value="employer">Employer</option>
                        <option value="candidate">Candidate</option>
                    </select>
                </div>
                <button type="submit" class="w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white transition hover:bg-violet-700">Create account</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-violet-700 hover:underline">Log in</a>
            </p>
        </div>
    </div>
</body>
</html>
