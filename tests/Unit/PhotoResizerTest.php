<?php
declare(strict_types=1);

use TrailSense\Services\PhotoResizer;

$make = function (int $w, int $h, string $type): string {
    $im = imagecreatetruecolor($w, $h);
    for ($i = 0; $i < 400; $i++) {   // noisy content so the JPEG is realistic in size
        imagefilledellipse($im, random_int(0, $w), random_int(0, $h), random_int(20, 200), random_int(20, 200),
            imagecolorallocate($im, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
    }
    ob_start();
    $type === 'png' ? imagepng($im) : imagejpeg($im, null, 98);
    return (string) ob_get_clean();
};

return [
    'large photo is reduced to 1600 px on the longest side, as JPEG' => function () use ($make) {
        $out = PhotoResizer::shrink($make(4000, 3000, 'jpg'), 'image/jpeg');
        [$w, $h] = getimagesizefromstring($out['bytes']);
        assertSame([1600, 1200], [$w, $h]);
        assertSame('image/jpeg', $out['mime']);
    },
    'large PNG becomes a much smaller JPEG (fits the 100 MB database quota)' => function () use ($make) {
        // photo-like noise: PNG cannot compress it, like a real camera picture saved as PNG
        $im = imagecreatetruecolor(2000, 1000);
        for ($y = 0; $y < 1000; $y++) {
            for ($x = 0; $x < 2000; $x += 4) {
                imagefilledrectangle($im, $x, $y, $x + 3, $y, random_int(0, 0xFFFFFF));
            }
        }
        ob_start(); imagepng($im); $png = (string) ob_get_clean();
        $out = PhotoResizer::shrink($png, 'image/png');
        assertSame('jpg', $out['ext']);
        assertTrue(strlen($out['bytes']) < strlen($png) / 2, 'expected at least 2x smaller even for worst-case noise');
        assertSame(1600, getimagesizefromstring($out['bytes'])[0]);
    },
    'small image is kept as it is' => function () use ($make) {
        $png = $make(40, 30, 'png');
        $out = PhotoResizer::shrink($png, 'image/png');
        assertSame([40, 30], array_slice(getimagesizefromstring($out['bytes']), 0, 2));
    },
];
