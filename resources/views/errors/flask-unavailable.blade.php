<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="10">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard warming up — HireSense</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-50 text-neutral-900">

    <header class="border-b border-neutral-200">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="font-semibold text-lg text-violet-800">HireSense</a>
        </div>
    </header>

    <section class="max-w-md mx-auto px-6 py-20 text-center">
        <div class="mb-1 text-xs font-semibold text-teal-700 uppercase tracking-wide">One moment</div>
        <h1 class="text-2xl font-bold mb-2">Your dashboard is waking up</h1>
        <p class="text-sm text-neutral-500 mb-8">
            The employer dashboard runs on a separate service that goes to sleep when idle.
            It's starting back up now — this page will refresh automatically in a few seconds.
        </p>

        <a href="{{ url('/') }}"
           class="inline-block bg-violet-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-violet-800">
            Try again
        </a>
    </section>

</body>
</html>
