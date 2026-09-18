<?php

namespace App\Services;

/**
 * Normalizes an uploaded image into a JPEG that AI providers accept.
 *
 * Providers only support a limited set of formats (webp, png, jpeg, gif), while
 * phones and browsers may upload AVIF, HEIC, BMP, etc. This converts anything GD
 * can decode to JPEG and caps the longest edge to keep token usage down.
 */
class ImageNormalizer
{
    private const SUPPORTED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    /**
     * Return a path to a prepared JPEG, or null when the original can be used
     * as-is (already supported and small enough) or cannot be converted.
     */
    public static function prepare(string $path, int $maxDimension = 1280): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $data = @file_get_contents($path);

        if ($data === false || $data === '') {
            return null;
        }

        $mime = mime_content_type($path) ?: '';
        [$width, $height] = @getimagesizefromstring($data) ?: [0, 0];

        $alreadySupported = in_array($mime, self::SUPPORTED_MIMES, true);
        $needsResize = $width > $maxDimension || $height > $maxDimension;

        if ($alreadySupported && ! $needsResize) {
            return null;
        }

        $image = @imagecreatefromstring($data);

        if ($image === false) {
            return null;
        }

        if ($needsResize && $width > 0 && $height > 0) {
            $scale = min(1, $maxDimension / max($width, $height));
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            $white = imagecolorallocate($resized, 255, 255, 255);
            imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $white);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            imagedestroy($image);
            $image = $resized;
        }

        $temporary = tempnam(sys_get_temp_dir(), 'opencal_norm_');

        if ($temporary === false) {
            imagedestroy($image);

            return null;
        }

        $output = $temporary.'.jpg';

        $encoded = imagejpeg($image, $output, 85);

        imagedestroy($image);
        @unlink($temporary);

        return $encoded ? $output : null;
    }
}
