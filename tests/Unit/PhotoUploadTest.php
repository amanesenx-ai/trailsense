<?php
declare(strict_types=1);

use TrailSense\Domain\PhotoValidator;
use TrailSense\Services\PhotoResizer;

/* AC7.2 rev / AC7.5 — iPhone photos (> 3 MB) are accepted, shrunk and turned the right way up. */

$jpeg = function (int $w, int $h): string {
    $im = imagecreatetruecolor($w, $h);
    imagefilledrectangle($im, 0, 0, $w - 1, (int) ($h / 2), imagecolorallocate($im, 200, 30, 30));
    ob_start(); imagejpeg($im, null, 90); return (string) ob_get_clean();
};
// insert an EXIF APP1 block with Orientation = $o right after the JPEG SOI marker (what an iPhone does)
$withOrientation = function (string $jpg, int $o): string {
    $tiff = "MM\x00\x2A\x00\x00\x00\x08" . "\x00\x01" . "\x01\x12\x00\x03\x00\x00\x00\x01" . pack('n', $o) . "\x00\x00" . "\x00\x00\x00\x00";
    $app1 = "Exif\x00\x00" . $tiff;
    return substr($jpg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpg, 2);
};
$tmp = function (string $bytes): string { $f = tempnam(sys_get_temp_dir(), 'ph'); file_put_contents($f, $bytes); return $f; };

return [
    'a 6 MB phone photo is within the limit' => function () use ($jpeg, $tmp) {
        $f = $tmp($jpeg(40, 30));
        [$mime, $err] = PhotoValidator::check(['error' => UPLOAD_ERR_OK, 'size' => 6 * 1024 * 1024, 'tmp_name' => $f]);
        assertSame('image/jpeg', $mime);
        assertSame(null, $err);
    },
    'over 15 MB is refused with a clear message' => function () use ($jpeg, $tmp) {
        $f = $tmp($jpeg(40, 30));
        [$mime, $err] = PhotoValidator::check(['error' => UPLOAD_ERR_OK, 'size' => 16 * 1024 * 1024, 'tmp_name' => $f]);
        assertSame(null, $mime);
        assertSame('The photo must be 15 MB or smaller.', $err);
    },
    'sideways iPhone photo (EXIF orientation 6) is stored upright' => function () use ($jpeg, $withOrientation) {
        $raw = $withOrientation($jpeg(2400, 1800), 6);
        assertSame(6, (int) (exif_read_data('data://image/jpeg;base64,' . base64_encode($raw))['Orientation'] ?? 0), 'fixture has EXIF');
        $out = PhotoResizer::shrink($raw, 'image/jpeg');
        [$w, $h] = getimagesizefromstring($out['bytes']);
        assertSame([1200, 1600], [$w, $h], 'portrait after rotation');
    },
    'normal orientation is not rotated' => function () use ($jpeg, $withOrientation) {
        $out = PhotoResizer::shrink($withOrientation($jpeg(2400, 1800), 1), 'image/jpeg');
        assertSame([1600, 1200], array_slice(getimagesizefromstring($out['bytes']), 0, 2));
    },
    'publish form loads the browser resizer and no longer says 3 MB' => function () {
        $view = (string) file_get_contents(__DIR__ . '/../../views/feed/form.php');
        assertTrue(str_contains($view, 'data-shrink'), 'input marked for resizing');
        assertTrue(!str_contains($view, '3 MB'), 'old limit text removed');
        $js = (string) file_get_contents(__DIR__ . '/../../public/assets/photo-shrink.js');
        assertTrue(str_contains($js, 'MAX_SIDE = 1600') && str_contains($js, 'DataTransfer'), 'resizes and replaces the file');
    },
];
