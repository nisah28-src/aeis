# Bug: Employer dashboard doesn't redirect after logout

## Summary

Logging out from the employer dashboard destroys the session correctly on
the backend, but the browser is never navigated anywhere afterward. The UI
visibly updates to a "logged out" state, then just sits there on the same
dead page until the user manually reloads or navigates away.

This is a front-end bug in the employer dashboard (Flask/React), not in the
Laravel app. Laravel's side of the logout flow has been verified to behave
correctly.

## Affected flow

- **Role:** Employer only. (Candidate logout, which uses a plain server-rendered
  form submit, works correctly and is unaffected.)
- **Where:** The employer dashboard SPA, served by the separate Flask
  backend and relayed to the browser by Laravel's `HomeController::index`.

## Steps to reproduce

1. Log in as an employer.
2. Land on the employer dashboard (served from `/`).
3. Click "Log out" in the dashboard UI.

## Expected behavior

After logout, the browser should end up back on the app's root (`/`),
which — now that the session is gone — renders the guest welcome page.

## Actual behavior

The dashboard UI flips to a logged-out-looking state, but the browser stays
on the exact same URL, showing a dead/stale page. Nothing happens until the
user manually refreshes or navigates elsewhere.

## Root cause

The dashboard's logout control calls `POST /auth/logout` from client-side
JS, almost certainly via `fetch()`. On the Laravel side, that request:

1. Destroys the Laravel session (`Auth::logout()`, `session()->invalidate()`).
2. Clears the Flask session cookie (`withoutCookie('session')`).
3. Returns an HTTP `302` redirect to `/`.

Step 3 is correct behavior for a normal `<form>` POST (the browser follows
the redirect and reloads). But `fetch()` follows redirects **internally**
and returns the final response body to the calling JS — it does **not**
navigate the actual page. So the request succeeds, the cookie is cleared,
and the SPA's own JS state updates to reflect "logged out" — but nothing
ever tells the browser's address bar / rendered page to actually move.

**Confirmed:** this is not a Laravel bug. The session is destroyed and the
cookie is cleared exactly as intended; the gap is purely that the SPA never
acts on the successful response by navigating.

## Suggested fix (in the Flask/React dashboard's logout handler)

After the logout request resolves successfully, explicitly navigate:

```js
fetch('/auth/logout', { method: 'POST', credentials: 'same-origin' })
  .then(() => {
    window.location.href = '/';
  });
```

Currently the handler appears to stop at updating local component state and
never issues this navigation.

## Notes

- No workaround for this has been added on the Laravel side. A stopgap
  (polling `/auth/me` and forcing a redirect) was tried and then
  deliberately reverted — this needs to be fixed at the source, in the
  service that owns the logout button's behavior.
- Laravel's `/auth/logout` route exists specifically so the SPA (which has
  no Laravel CSRF token available to it) can call logout directly; see
  `routes/web.php` and `AuthController::logout`.
