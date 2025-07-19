<?php

declare(strict_types=1);

namespace App\Infrastructure\FormatConverter\Format;

use App\Application\Domain\Snap\Snap;
use App\Infrastructure\FormatConverter\AbstractFormat;
use App\Infrastructure\FormatConverter\StorageInterface;
use FFMpeg\FFMpeg;
use FFMpeg\Media\Video;
use Symfony\Component\HttpFoundation\File\File;

final readonly class Webm extends AbstractFormat
{
    public function __construct(
        StorageInterface $storage,
        private int $videoKiloBitrate = 1000,
        private int $audioKiloBitrate = 196,
    ) {
        parent::__construct($storage);
    }

    public static function getExtension(): string
    {
        return 'webm';
    }

    protected function convertFile(Snap $snap): File
    {
        $bitrate = $this->videoKiloBitrate;
        $video = FFMpeg::create()->open($snap->getFile()->getAbsolutePath());

        if ($video instanceof Video === false) {
            throw new \RuntimeException('Opened file is not a video');
        }

        $videoStream = $video->getStreams()->videos()->first();

        if ($videoStream !== null) {
            $streamBitrate = $videoStream->get('bit_rate');

            if (is_numeric($streamBitrate) === true) {
                $bitrate = (int) ceil(((int) $streamBitrate) / 1000);
            }
        }

        $format = new \FFMpeg\Format\Video\WebM()
            ->setKiloBitrate($bitrate)
            ->setAudioKiloBitrate($this->audioKiloBitrate);

        $tempPath = sprintf('%s/%s.%s', sys_get_temp_dir(), $snap->getId()->toBase58(), self::getExtension());
        $video->save($format, $tempPath);

        return new File($tempPath);
    }
}
