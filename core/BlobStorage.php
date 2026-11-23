<?php
declare(strict_types=1);

namespace App\Core;

// Uploads files to Vercel Blob (public store) via its REST API. Only
// active when BLOB_READ_WRITE_TOKEN is set - Vercel injects it
// automatically when a Blob store is linked to the project. Without it,
// callers fall back to saving under uploads/ (XAMPP / InfinityFree).
final class BlobStorage
{
    private const API = 'https://blob.vercel-storage.com';

    public static function enabled(): bool
    {
        $token = getenv('BLOB_READ_WRITE_TOKEN');
        return is_string($token) && $token !== '';
    }

    // Uploads $tmpPath as $pathname (e.g. rooms/room_101.jpg) and returns
    // the public URL, or null on any failure.
    public static function upload(string $tmpPath, string $pathname, string $mime): ?string
    {
        $token = getenv('BLOB_READ_WRITE_TOKEN');
        if (!is_string($token) || $token === '') return null;
        $data = file_get_contents($tmpPath);
        if ($data === false) return null;
        $parts = array_map('rawurlencode', explode('/', ltrim($pathname, '/')));
        $ch = curl_init(self::API . '/' . implode('/', $parts));
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'x-api-version: 7',
                'x-content-type: ' . $mime,
                'access: public',
                'x-add-random-suffix: 0',
            ],
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($body === false || $code !== 200) return null;
        $json = json_decode((string) $body, true);
        $url = is_array($json) ? ($json['url'] ?? null) : null;
        return is_string($url) && $url !== '' ? $url : null;
    }
}
