<?php

use App\Services\ImageNormalizer;

function makePng(string $path, int $width, int $height): void
{
    $image = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($image, 120, 180, 90);
    imagefilledrectangle($image, 0, 0, $width, $height, $color);
    imagepng($image, $path);
    imagedestroy($image);
}

test('it converts an oversized image to a resized jpeg', function () {
    $source = tempnam(sys_get_temp_dir(), 'opencal_src_').'.png';
    makePng($source, 2000, 1000);

    $prepared = ImageNormalizer::prepare($source, 500);

    expect($prepared)->not->toBeNull()
        ->and(mime_content_type($prepared))->toBe('image/jpeg');

    [$width, $height] = getimagesize($prepared);

    expect($width)->toBeLessThanOrEqual(500)
        ->and($height)->toBeLessThanOrEqual(500);

    @unlink($source);
    @unlink($prepared);
});

test('it leaves a small supported image untouched', function () {
    $source = tempnam(sys_get_temp_dir(), 'opencal_src_').'.png';
    makePng($source, 40, 40);

    expect(ImageNormalizer::prepare($source, 1280))->toBeNull();

    @unlink($source);
});

test('it returns null for a non-image file', function () {
    $source = tempnam(sys_get_temp_dir(), 'opencal_src_').'.txt';
    file_put_contents($source, 'not an image');

    expect(ImageNormalizer::prepare($source))->toBeNull();

    @unlink($source);
});
