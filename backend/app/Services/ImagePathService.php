<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ImagePathService
{
    private ?string $cloudinaryBaseUrl = null;

    /**
     * Resolve the first valid image URL from image payload.
     */
    public function resolveFirstUrl(mixed $images): ?string
    {
        if (!is_array($images)) {
            return null;
        }

        foreach ($images as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            return $this->resolveSingleUrl($path);
        }

        return null;
    }

    /**
     * Resolve all image URLs.
     *
     * @return array<int, string>
     */
    public function resolveAllUrls(mixed $images): array
    {
        if (!is_array($images)) {
            return [];
        }

        $urls = [];
        foreach ($images as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $urls[] = $this->resolveSingleUrl($path);
        }

        return $urls;
    }

    /**
     * Resolve a single image path to browser URL.
     */
    public function resolveSingleUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        if (str_starts_with($path, 'questroom/')) {
            return $this->resolveCloudinaryUrl($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function resolveCloudinaryUrl(string $path): string
    {
        $baseUrl = $this->getCloudinaryBaseUrl();

        if ($baseUrl === null) {
            return Storage::disk('cloudinary')->url($path);
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function getCloudinaryBaseUrl(): ?string
    {
        if ($this->cloudinaryBaseUrl !== null) {
            return $this->cloudinaryBaseUrl;
        }

        $cloudUrl = config('cloudinary.cloud_url') ?? env('CLOUDINARY_URL');

        if (!is_string($cloudUrl) || $cloudUrl === '') {
            $this->cloudinaryBaseUrl = null;
            return null;
        }

        $parts = parse_url($cloudUrl);
        $cloudName = $parts['host'] ?? null;

        if (!is_string($cloudName) || $cloudName === '') {
            $this->cloudinaryBaseUrl = null;
            return null;
        }

        $this->cloudinaryBaseUrl = 'https://res.cloudinary.com/' . $cloudName . '/image/upload';

        return $this->cloudinaryBaseUrl;
    }
}
