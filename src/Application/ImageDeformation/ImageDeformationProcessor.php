<?php

namespace App\Application\ImageDeformation;

final class ImageDeformationProcessor
{
    public const CANVAS_WIDTH = 400;
    public const CANVAS_HEIGHT = 2051;
    public const IMAGE_WIDTH = 390;
    public const IMAGE_X = 10;
    public const IMAGE_Y = 307;
    public const MAX_PIXELS = 40_000_000;

    public function process(string $sourcePath, string $resultPath): void
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('L’extension PHP GD est indisponible.');
        }

        $imageInfo = @getimagesize($sourcePath);
        if (false === $imageInfo || ($imageInfo[2] ?? null) !== IMAGETYPE_PNG) {
            throw new \RuntimeException('Le fichier source n’est pas une image PNG valide.');
        }
        if ($imageInfo[0] * $imageInfo[1] > self::MAX_PIXELS) {
            throw new \RuntimeException('Les dimensions de l’image sont trop importantes.');
        }

        $source = @imagecreatefrompng($sourcePath);
        if (!$source instanceof \GdImage) {
            throw new \RuntimeException('Impossible de lire l’image PNG.');
        }

        $trimmedSource = $this->trimTransparentSpace($source);
        if ($trimmedSource !== $source) {
            imagedestroy($source);
            $source = $trimmedSource;
        }

        $deformed = $this->deformToRequiredWidth($source);
        $croppedPath = dirname($resultPath) . '/crop.png';
        if (!imagepng($deformed, $croppedPath, 9)) {
            imagedestroy($source);
            imagedestroy($deformed);
            throw new \RuntimeException('Impossible d’enregistrer l’image croppée et déformée.');
        }

        $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
        if (!$canvas instanceof \GdImage) {
            imagedestroy($source);
            imagedestroy($deformed);
            throw new \RuntimeException('Impossible de créer le canvas.');
        }

        try {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparent);
            imagealphablending($canvas, true);

            if (
                !imagecopy(
                    $canvas,
                    $deformed,
                    self::IMAGE_X,
                    self::IMAGE_Y,
                    0,
                    0,
                    self::IMAGE_WIDTH,
                    imagesy($deformed),
                )
            ) {
                throw new \RuntimeException('Le positionnement de l’image a échoué.');
            }

            if (!imagepng($canvas, $resultPath, 9)) {
                throw new \RuntimeException('Impossible d’enregistrer l’image finale.');
            }
        } finally {
            imagedestroy($source);
            imagedestroy($deformed);
            imagedestroy($canvas);
        }
    }

    private function deformToRequiredWidth(\GdImage $source): \GdImage
    {
        $height = imagesy($source);
        $deformed = imagecreatetruecolor(self::IMAGE_WIDTH, $height);
        if (!$deformed instanceof \GdImage) {
            throw new \RuntimeException('Impossible de créer l’image déformée.');
        }

        imagealphablending($deformed, false);
        imagesavealpha($deformed, true);
        $transparent = imagecolorallocatealpha($deformed, 0, 0, 0, 127);
        imagefill($deformed, 0, 0, $transparent);

        if (
            !imagecopyresampled(
                $deformed,
                $source,
                0,
                0,
                0,
                0,
                self::IMAGE_WIDTH,
                $height,
                imagesx($source),
                $height,
            )
        ) {
            imagedestroy($deformed);
            throw new \RuntimeException('La déformation de l’image à 390 px a échoué.');
        }

        return $deformed;
    }

    private function trimTransparentSpace(\GdImage $source): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $top = 0;
        while ($top < $height && !$this->rowContainsOpaquePixel($source, $top, $width)) {
            ++$top;
        }

        if ($top === $height) {
            throw new \RuntimeException('L’image PNG ne contient aucun pixel totalement opaque.');
        }

        $bottom = $height - 1;
        while ($bottom > $top && !$this->rowContainsOpaquePixel($source, $bottom, $width)) {
            --$bottom;
        }

        $left = 0;
        while ($left < $width && !$this->columnContainsOpaquePixel($source, $left, $top, $bottom)) {
            ++$left;
        }

        $right = $width - 1;
        while ($right > $left && !$this->columnContainsOpaquePixel($source, $right, $top, $bottom)) {
            --$right;
        }

        if (0 === $top && $bottom === $height - 1 && 0 === $left && $right === $width - 1) {
            return $source;
        }

        $trimmed = imagecrop($source, [
            'x' => $left,
            'y' => $top,
            'width' => $right - $left + 1,
            'height' => $bottom - $top + 1,
        ]);
        if (!$trimmed instanceof \GdImage) {
            throw new \RuntimeException('Impossible de retirer les espaces transparents.');
        }

        imagesavealpha($trimmed, true);

        return $trimmed;
    }

    private function rowContainsOpaquePixel(\GdImage $image, int $y, int $width): bool
    {
        for ($x = 0; $x < $width; ++$x) {
            $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
            if (0 === $alpha) {
                return true;
            }
        }

        return false;
    }

    private function columnContainsOpaquePixel(\GdImage $image, int $x, int $top, int $bottom): bool
    {
        for ($y = $top; $y <= $bottom; ++$y) {
            $alpha = (imagecolorat($image, $x, $y) >> 24) & 0x7F;
            if (0 === $alpha) {
                return true;
            }
        }

        return false;
    }
}
