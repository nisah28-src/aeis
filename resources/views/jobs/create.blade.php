<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a job — HireSense</title>
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
        <h1 class="text-2xl font-bold mb-1">Post a job</h1>
        <p class="text-sm text-neutral-500 mb-8">
            The role description below is exactly what the AI uses to judge relevance later —
            be specific about what the role actually needs.
        </p>

        {{-- Not wired to a real submission yet. Swap the form action to a real route
             (e.g. route('jobs.store')) once the Jobs table exists — see Module 1
             on the project Gantt chart. --}}
        <form onsubmit="event.preventDefault(); document.getElementById('posted-msg').classList.remove('hidden'); this.classList.add('hidden');"
              class="bg-white border border-neutral-200 rounded-xl p-8">

            <label class="block text-sm font-medium mb-1">Job title</label>
            <input type="text" required class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-4" placeholder="e.g. Junior Software Engineer">

            <label class="block text-sm font-medium mb-1">Department</label>
            <select class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-4">
                <option>Engineering</option>
                <option>Customer Support</option>
                <option>Sales</option>
                <option>Marketing</option>
                <option>Operations</option>
            </select>

            <label class="block text-sm font-medium mb-1">Role description</label>
            <p class="text-xs text-neutral-500 mb-2">
                This is sent directly to the AI snapshot engine alongside every applicant's
                resume — the more specific this is, the better the AI's reasoning will be.
            </p>
            <textarea required rows="5" class="w-full border border-neutral-300 rounded-lg px-3 py-2 mb-6"
                placeholder="e.g. Builds and maintains web applications, works with databases, collaborates with a team using Git, and applies foundational software engineering and system design knowledge."></textarea>

            <button type="submit" class="bg-violet-700 text-white px-6 py-3 rounded-lg font-medium hover:bg-violet-800">
                Post job
            </button>
        </form>

        <div id="posted-msg" class="hidden mt-4 text-sm text-teal-800 bg-teal-50 border border-teal-200 rounded-lg px-4 py-3">
            Job posted — candidates can now apply. (This is a static demo confirmation; nothing was actually saved.)
        </div>
    </section>

    <footer class="border-t border-neutral-200 py-8 text-center text-sm text-neutral-500">
        &copy; {{ date('Y') }} HireSense — built internally, still in progress.
    </footer>

</body>
</html>
