<?php
declare(strict_types=1);

namespace TrailSense\Services;

/**
 * Shrinks uploaded photos before they are stored in the database (ADR-009):
 * longest side ≤ 1600 px, re-encoded as JPEG (quality 82) — typically 150–350 KB.
 * Re-encoding also strips metadata (e.g. GPS position in EXIF), which protects privacy.
 * If GD is not available the original bytes are kept.
 */
final class PhotoResizer
{
    public const MAX_SIDE = 1600;

    /** @return array{mime:string, ext:string, bytes:string} */
    public static function shrink(string $bytes, string $mime): array
    {
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';
        if (!function_exists('imagecreatefromstring')) {
            return ['mime' => $mime, 'ext' => $ext, 'bytes' => $bytes];
        }
        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return ['mime' => $mime, 'ext' => $ext, 'bytes' => $bytes];
        }
        $src = self::upright($src, $bytes, $mime);
        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1.0, self::MAX_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));   // transparent PNG → white background
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagejpeg($dst, null, 82);
        $out = (string) ob_get_clean();

        // keep the original if it was already smaller (e.g. a tiny PNG)
        if (strlen($out) >= strlen($bytes) && $scale === 1.0) {
            return ['mime' => $mime, 'ext' => $ext, 'bytes' => $bytes];
        }
        return ['mime' => 'image/jpeg', 'ext' => 'jpg', 'bytes' => $out];
    }

    /** iPhone JPEGs are stored sideways with an EXIF "Orientation" tag; turn the pixels the right way up. */
    private static function upright(\GdImage $img, string $bytes, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
        $angle = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;
        if ($angle === 0) {
            return $img;
        }
        $turned = imagerotate($img, $angle, 0);
        return $turned === false ? $img : $turned;
    }
}
