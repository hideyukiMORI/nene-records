<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Media;

use NeNeRecords\Media\GdImageProcessor;
use NeNeRecords\Media\ImageProcessorInterface;
use PHPUnit\Framework\TestCase;

final class GdImageProcessorTest extends TestCase
{
    /**
     * @param positive-int $width
     * @param positive-int $height
     */
    private function sourcePng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        self::assertNotFalse($image);
        $color = imagecolorallocate($image, 120, 30, 30);
        self::assertNotFalse($color);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return $bytes;
    }

    public function testResizeScalesDownPreservingAspectRatio(): void
    {
        $processor = new GdImageProcessor();
        $out = $processor->resize($this->sourcePng(100, 50), 40, ImageProcessorInterface::FORMAT_PNG);

        $info = getimagesizefromstring($out);
        self::assertNotFalse($info);
        self::assertSame(40, $info[0]);
        self::assertSame(20, $info[1]);
        self::assertSame(IMAGETYPE_PNG, $info[2]);
    }

    public function testResizeNeverUpscales(): void
    {
        $processor = new GdImageProcessor();
        $out = $processor->resize($this->sourcePng(100, 50), 400, ImageProcessorInterface::FORMAT_PNG);

        $info = getimagesizefromstring($out);
        self::assertNotFalse($info);
        self::assertSame(100, $info[0]);
        self::assertSame(50, $info[1]);
    }

    public function testResizeEncodesWebp(): void
    {
        $processor = new GdImageProcessor();
        $out = $processor->resize($this->sourcePng(80, 80), 40, ImageProcessorInterface::FORMAT_WEBP);

        $info = getimagesizefromstring($out);
        self::assertNotFalse($info);
        self::assertSame(IMAGETYPE_WEBP, $info[2]);
        self::assertSame(40, $info[0]);
    }

    public function testSupportsSourceAcceptsRasterImagesOnly(): void
    {
        $processor = new GdImageProcessor();

        self::assertTrue($processor->supportsSource('image/png'));
        self::assertTrue($processor->supportsSource('image/jpeg'));
        self::assertFalse($processor->supportsSource('application/pdf'));
        self::assertFalse($processor->supportsSource('image/svg+xml'));
    }

    public function testSupportsOutputReflectsAvailableEncoders(): void
    {
        $processor = new GdImageProcessor();

        // PNG/JPEG/WebP は GD の標準構成で常に有効。AVIF はビルドフラグ依存なので
        // 環境の実状（imageavif の有無）と一致することだけを検証する。
        self::assertTrue($processor->supportsOutput(ImageProcessorInterface::FORMAT_PNG));
        self::assertTrue($processor->supportsOutput(ImageProcessorInterface::FORMAT_JPEG));
        self::assertTrue($processor->supportsOutput(ImageProcessorInterface::FORMAT_WEBP));
        self::assertSame(function_exists('imageavif'), $processor->supportsOutput(ImageProcessorInterface::FORMAT_AVIF));
        self::assertFalse($processor->supportsOutput('tiff'));
    }
}
