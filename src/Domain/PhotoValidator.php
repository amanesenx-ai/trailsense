<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/** AC7.2: photo type is checked by file content (not by extension), max 3 MB. */
final class PhotoValidator
{
    public const MAX_BYTES = 3 * 1024 * 1024;
    public const TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * @param array<string,mixed>|null $file one entry of $_FILES
     * @return array{0: ?string, 1: ?string} [extension or null if no file, error or null]
     */
    public static function check(?array $file): array
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [null, 'The photo could not be uploaded. Try a smaller file.'];
        }
        if ((int) $file['size'] > self::MAX_BYTES) {
            return [null, 'The photo must be 3 MB or smaller.'];
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        if (!isset(self::TYPES[$mime])) {
            return [null, 'Only JPEG, PNG or WebP photos are accepted.'];
        }
        return [self::TYPES[$mime], null];
    }
}
