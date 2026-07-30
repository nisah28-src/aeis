# HireSense (aeis)

Small Laravel sample app for resume/job matching and quick prototyping of an AI-backed resume assessor.

## Requirements
- PHP 8.1+ (match project's composer.json)
- Composer
- Node.js & npm (for Vite/Tailwind build)
- SQLite/MySQL/Postgres (project uses SQLite by default)
- (Optional) Redis for queue in production

## Quick start (development)
1. Install PHP deps:

   composer install

2. Install frontend deps and build (dev):

   npm install
   npm run dev

3. Copy env and set app key:

   cp .env.example .env
   php artisan key:generate

4. If using SQLite (default in `.env`):

   touch database/database.sqlite

5. Run migrations (if needed):

   php artisan migrate

6. Start the dev server:

   php artisan serve

Open http://127.0.0.1:8000 in your browser.

## Resume check / AI service
- The app integrates with a local Flask AI service at `http://127.0.0.1:5050` (endpoints used:
  - `/api/evaluate` for job-specific evaluation
  - `/api/assess-general` for the resume-check flow)
- By default the routes call the AI service synchronously and return the result view immediately. If the Flask service is not running, the app will show an error message.

## Queue (optional)
- For better production behavior you can move external API calls into background jobs.
- Dev quick option (run jobs inline): set `QUEUE_CONNECTION=sync` in `.env` to have dispatched jobs run during the HTTP request (useful for local testing).
- To use a database queue:

  php artisan queue:table
  php artisan migrate
  # set QUEUE_CONNECTION=database in .env
  php artisan queue:work --tries=3

## Where things live
- Routes: `routes/web.php`
- Views: `resources/views/` (Blade templates)
- Frontend: `resources/js/` and `resources/css/` (Vite + Tailwind)
- Storage for uploaded files and other artifacts: `storage/app/`

## Testing
- Run PHP unit tests:

  ./vendor/bin/pest
  # or
  php artisan test

## Notes and tips
- Keep external AI keys and secrets in `.env` (do not commit). The project currently references an environment variable for an API key — remove or rotate keys if they were accidentally committed.
- If you want me to enable an asynchronous job-based workflow and a simple polling or notification UI for resume results, tell me and I will scaffold the job, storage conventions, and a small results endpoint.
