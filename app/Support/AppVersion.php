<?php

namespace App\Support;

class AppVersion
{
    private static ?string $version = null;

    public static function current(): ?string
    {
        return self::$version ??= self::resolve();
    }

    private static function resolve(): ?string
    {
        $packageVersion = self::packageVersion();
        $revision = self::gitRevision();

        if ($packageVersion && $revision) {
            return self::withEnvironmentSuffix("{$packageVersion}-{$revision}");
        }

        $version = $packageVersion ?: $revision;

        return $version ? self::withEnvironmentSuffix($version) : null;
    }

    private static function withEnvironmentSuffix(string $version): string
    {
        if (app()->environment('local')) {
            return "{$version}-LOCAL";
        }

        return $version;
    }

    private static function packageVersion(): ?string
    {
        $path = base_path('package.json');

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $package = json_decode($contents, true);
        $version = is_array($package) ? ($package['version'] ?? null) : null;

        return is_string($version) && $version !== '' ? $version : null;
    }

    private static function gitRevision(): ?string
    {
        $gitDir = self::gitDir();

        if (! $gitDir) {
            return null;
        }

        $headPath = "{$gitDir}/HEAD";

        if (! is_file($headPath)) {
            return null;
        }

        $head = trim((string) file_get_contents($headPath));

        if (str_starts_with($head, 'ref: ')) {
            $ref = trim(substr($head, 5));
            $hash = self::hashFromRef($gitDir, $ref);
        } else {
            $hash = $head;
        }

        if (! $hash || ! preg_match('/^[a-f0-9]{7,40}$/i', $hash)) {
            return null;
        }

        return substr($hash, 0, 7);
    }

    private static function gitDir(): ?string
    {
        $path = base_path('.git');

        if (is_dir($path)) {
            return $path;
        }

        if (! is_file($path)) {
            return null;
        }

        $contents = trim((string) file_get_contents($path));

        if (! str_starts_with($contents, 'gitdir:')) {
            return null;
        }

        $gitDir = trim(substr($contents, 7));

        if (! str_starts_with($gitDir, DIRECTORY_SEPARATOR)) {
            $gitDir = base_path($gitDir);
        }

        return is_dir($gitDir) ? $gitDir : null;
    }

    private static function hashFromRef(string $gitDir, string $ref): ?string
    {
        $refPath = "{$gitDir}/{$ref}";

        if (is_file($refPath)) {
            return trim((string) file_get_contents($refPath));
        }

        return self::hashFromPackedRefs($gitDir, $ref);
    }

    private static function hashFromPackedRefs(string $gitDir, string $ref): ?string
    {
        $packedRefsPath = "{$gitDir}/packed-refs";

        if (! is_file($packedRefsPath)) {
            return null;
        }

        $lines = file($packedRefsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return null;
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || str_starts_with($line, '^')) {
                continue;
            }

            [$hash, $name] = array_pad(explode(' ', $line, 2), 2, null);

            if ($name === $ref) {
                return $hash;
            }
        }

        return null;
    }
}
