<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter\Format;

use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\AbstractFormat;
use App\Infrastructure\FormatConverter\StorageInterface;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Video;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Symfony\Component\HttpFoundation\File\File;

final readonly class Thumbnail extends AbstractFormat
{
    public function __construct(
        StorageInterface $storage,
        private int $resizePxWidth = 240,
    ) {
        parent::__construct($storage);
    }

    public static function getExtension(): string
    {
        return 'thumbnail';
    }

    protected function convertFile(Snap $snap): File
    {
        $imagine = new Imagine();
        $tempFilePath = sprintf('%s/thumbnail_tmp_%s.jpeg', sys_get_temp_dir(), $snap->getId()->toBase58());

        if ($snap->isImage() === true) {
            $imagineImage = $this->resize($imagine->open($snap->getFile()->getAbsolutePath()));
            $imagineImage->save($tempFilePath);
        }

        if ($snap->isVideo() === true) {
            $tempFrameFilePath = sprintf('%s/ffmpeg_tmp_frame_%s.jpeg', sys_get_temp_dir(), $snap->getId()->toBase58());
            $ffmpegVideo = FFMpeg::create()->open($snap->getFile()->getAbsolutePath());

            if ($ffmpegVideo instanceof Video === false) {
                throw new \RuntimeException('Opened file is not a video');
            }

            $ffmpegVideo
                ->frame(TimeCode::fromSeconds(1))
                ->save($tempFrameFilePath);

            $imagineImage = $this->resize($imagine->open($tempFrameFilePath));
            $imagineImage->save($tempFilePath);
        }

        return new File($tempFilePath);
    }

    private function resize(ImageInterface $image): ImageInterface
    {
        $height = ($image->getSize()->getHeight() / $image->getSize()->getWidth()) * $this->resizePxWidth;
        $image->resize(new Box($this->resizePxWidth, $height));

        return $image;
    }
}
