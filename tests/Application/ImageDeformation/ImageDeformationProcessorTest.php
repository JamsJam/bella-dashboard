<?php

namespace App\Tests\Application\ImageDeformation;

use App\Application\ImageDeformation\ImageDeformationProcessor;
use PHPUnit\Framework\TestCase;

final class ImageDeformationProcessorTest extends TestCase
{
    public function testItCreatesTheExpectedTransparentPng(): void
    {
        $directory = sys_get_temp_dir() . '/image-deformation-test-' . bin2hex(random_bytes(6));
        mkdir($directory, 0775, true);
        $sourcePath = $directory . '/source.png';
        $croppedPath = $directory . '/crop.png';
        $resultPath = $directory . '/result.png';

        $source = imagecreatetruecolor(100, 50);
        self::assertInstanceOf(\GdImage::class, $source);
        $red = imagecolorallocate($source, 255, 0, 0);
        imagefill($source, 0, 0, $red);
        imagepng($source, $sourcePath);
        imagedestroy($source);

        try {
            (new ImageDeformationProcessor())->process($sourcePath, $resultPath);

            $cropped = imagecreatefrompng($croppedPath);
            self::assertInstanceOf(\GdImage::class, $cropped);
            self::assertSame(390, imagesx($cropped));
            self::assertSame(50, imagesy($cropped));
            imagedestroy($cropped);

            $result = imagecreatefrompng($resultPath);
            self::assertInstanceOf(\GdImage::class, $result);
            self::assertSame(400, imagesx($result));
            self::assertSame(2051, imagesy($result));
            self::assertSame(127, (imagecolorat($result, 0, 0) >> 24) & 0x7F);

            $placedPixel = imagecolorsforindex($result, imagecolorat($result, 10, 307));
            self::assertSame(255, $placedPixel['red']);
            self::assertSame(0, $placedPixel['green']);
            self::assertSame(0, $placedPixel['blue']);
            $rightEdgePixel = imagecolorsforindex($result, imagecolorat($result, 399, 307));
            self::assertSame(255, $rightEdgePixel['red'], 'The deformed image must occupy exactly 390 px from x=10 to x=399.');
            imagedestroy($result);
        } finally {
            @unlink($resultPath);
            @unlink($croppedPath);
            @unlink($sourcePath);
            @rmdir($directory);
        }
    }

    public function testItTrimsTransparentSpaceAtTopAndBottomBeforePlacement(): void
    {
        $directory = sys_get_temp_dir() . '/image-deformation-trim-test-' . bin2hex(random_bytes(6));
        mkdir($directory, 0775, true);
        $sourcePath = $directory . '/source.png';
        $croppedPath = $directory . '/crop.png';
        $resultPath = $directory . '/result.png';

        $source = imagecreatetruecolor(100, 50);
        self::assertInstanceOf(\GdImage::class, $source);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        $transparent = imagecolorallocatealpha($source, 0, 0, 0, 127);
        imagefill($source, 0, 0, $transparent);
        $semiTransparentRed = imagecolorallocatealpha($source, 255, 0, 0, 64);
        imagefilledrectangle($source, 5, 5, 94, 9, $semiTransparentRed);
        $red = imagecolorallocatealpha($source, 255, 0, 0, 0);
        imagefilledrectangle($source, 10, 10, 89, 29, $red);
        imagefilledrectangle($source, 5, 30, 94, 34, $semiTransparentRed);
        imagepng($source, $sourcePath);
        imagedestroy($source);

        try {
            (new ImageDeformationProcessor())->process($sourcePath, $resultPath);
            $cropped = imagecreatefrompng($croppedPath);
            self::assertInstanceOf(\GdImage::class, $cropped);
            self::assertSame(390, imagesx($cropped));
            self::assertSame(20, imagesy($cropped));
            imagedestroy($cropped);

            $result = imagecreatefrompng($resultPath);
            self::assertInstanceOf(\GdImage::class, $result);

            $firstPixel = imagecolorsforindex($result, imagecolorat($result, 10, 307));
            self::assertSame(255, $firstPixel['red']);
            self::assertSame(0, $firstPixel['alpha']);
            $rightPixel = imagecolorsforindex($result, imagecolorat($result, 399, 307));
            self::assertSame(255, $rightPixel['red']);
            self::assertSame(0, $rightPixel['alpha']);
            self::assertSame(127, (imagecolorat($result, 10, 327) >> 24) & 0x7F);
            imagedestroy($result);
        } finally {
            @unlink($resultPath);
            @unlink($croppedPath);
            @unlink($sourcePath);
            @rmdir($directory);
        }
    }
}
