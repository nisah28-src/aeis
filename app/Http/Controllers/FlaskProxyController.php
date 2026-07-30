<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FlaskProxyController extends Controller
{
    /**
     * Transparent reverse proxy to the Flask backend. Laravel owns its own
     * explicit routes (/, /login, /register, etc.); Route::fallback() sends
     * everything else here — /jobs, /candidates, /assets/*, /portal/<token>,
     * /auth/me, /auth/logout — forwarded byte-for-byte. Flask keeps assuming
     * it's mounted at root and needs zero path-rewriting logic.
     *
     * Multipart requests (resume/video uploads) need special handling: PHP
     * consumes the raw input stream to populate $_FILES/$_POST for
     * multipart/form-data bodies, so $request->getContent() comes back empty
     * for those — we have to rebuild the multipart body from the parsed
     * fields/files instead of forwarding raw bytes.
     */
    public function proxy(Request $request)
    {
        $flaskBaseUrl = rtrim(config('services.flask.base_url'), '/');
        $path         = $request->getRequestUri(); // leading slash + query string, unchanged

        $headers = collect($request->headers->all())
            ->except(['host', 'content-length', 'content-type'])
            ->map(fn ($v) => $v[0])
            ->toArray();

        $client = Http::withHeaders($headers)->withOptions(['allow_redirects' => false]);

        if ($request->files->count() > 0) {
            foreach ($request->files->all() as $key => $fileOrFiles) {
                foreach (is_array($fileOrFiles) ? $fileOrFiles : [$fileOrFiles] as $file) {
                    $client = $client->attach($key, file_get_contents($file->getRealPath()), $file->getClientOriginalName());
                }
            }
            $fileKeys = array_keys($request->files->all());
            $response = $client->post($flaskBaseUrl . $path, $request->except($fileKeys));
        } else {
            $response = $client
                ->withBody($request->getContent(), $request->header('Content-Type', 'application/octet-stream'))
                ->send($request->method(), $flaskBaseUrl . $path);
        }

        // Guzzle/PSR-7 preserves the origin server's original header casing as
        // array keys (unlike Symfony's request HeaderBag, which lowercases),
        // so keys must be normalized before except() can reliably strip them —
        // otherwise e.g. "Transfer-Encoding" survives alongside the real
        // Content-Length Laravel calculates for this literal body string,
        // producing conflicting framing headers on the outgoing response.
        $responseHeaders = collect($response->headers())
            ->mapWithKeys(fn ($v, $k) => [strtolower($k) => $v])
            ->except(['transfer-encoding', 'content-encoding', 'connection'])
            ->map(fn ($v) => $v[0])
            ->toArray();

        return response($response->body(), $response->status())->withHeaders($responseHeaders);
    }
}
