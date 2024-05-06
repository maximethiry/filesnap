<?php

declare(strict_types=1);

namespace App\Tests\Domain\UseCase\Snap;

use App\Application\Domain\Entity\Snap\Exception\FileNotFoundException;
use App\Application\Domain\Entity\Snap\Exception\FileSizeTooBigException;
use App\Application\Domain\Entity\Snap\Exception\UnsupportedFileTypeException;
use App\Application\Domain\Entity\Snap\Factory\SnapFactory;
use App\Application\Domain\Entity\Snap\FileStorage\File;
use App\Application\Domain\Entity\Snap\FileStorage\FileStorageInterface;
use App\Application\Domain\Entity\Snap\MimeType;
use App\Application\Domain\Entity\Snap\Repository\SnapRepositoryInterface;
use App\Application\UseCase\Snap\Create\CreateSnapRequest;
use App\Application\UseCase\Snap\Create\CreateSnapUseCase;
use App\Tests\FilesnapTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Uid\Uuid;

final class CreateTest extends FilesnapTestCase
{
    public static function itCreatesSnapProvider(): array
    {
        return array_map(
            static function (MimeType $mimeType) {
                $originalFilename = 'the-original-filename';
                $fileAbsolutePath = '/this/is/an/absolute/path/to/a/file';

                return match ($mimeType) {
                    MimeType::ImageJpeg => [
                        $originalFilename . '.jpg',
                        MimeType::ImageJpeg->value,
                        $fileAbsolutePath . '.jpg',
                    ],
                    MimeType::ImagePng => [
                        $originalFilename . '.png',
                        MimeType::ImagePng->value,
                        $fileAbsolutePath . '.png',
                    ],
                    MimeType::ImageGif => [
                        $originalFilename . '.gif',
                        MimeType::ImageGif->value,
                        $fileAbsolutePath . '.gif',
                    ],
                    MimeType::VideoWebm => [
                        $originalFilename . '.webm',
                        MimeType::VideoWebm->value,
                        $fileAbsolutePath . '.webm',
                    ],
                    MimeType::VideoMp4 => [
                        $originalFilename . '.mp4',
                        MimeType::VideoMp4->value,
                        $fileAbsolutePath . '.mp4',
                    ]
                };
            },
            MimeType::cases()
        );
    }

    /**
     * @throws Exception
     * @throws FileNotFoundException
     * @throws FileSizeTooBigException
     * @throws UnsupportedFileTypeException
     */
    #[DataProvider('itCreatesSnapProvider')]
    public function testItCreatesSnap(
        string $originalFilename,
        string $fileMimeType,
        string $fileAbsolutePath
    ): void {
        $userId = Uuid::v7();
        $file = new File($fileAbsolutePath);

        $fileStorageStub = $this->createConfiguredStub(FileStorageInterface::class, [
            'getFileMaximumAuthorizedBytesSize' => 100,
            'get' => $file,
        ]);

        $snapRepositoryStub = $this->createStub(SnapRepositoryInterface::class);
        $snapFactory = new SnapFactory($fileStorageStub);
        $useCase = new CreateSnapUseCase($snapRepositoryStub, $fileStorageStub, $snapFactory);

        $request = new CreateSnapRequest(
            userId: $userId,
            fileOriginalName: $originalFilename,
            fileMimeType: $fileMimeType,
            filePath: '/this/is/a/path/to/a/file',
            fileBytesSize: 10
        );

        $response = $useCase($request);
        $snap = $response->getSnap();

        $this->assertEquals($userId, $snap->getUserId());
        $this->assertEquals($originalFilename, $snap->getOriginalFilename());
        $this->assertEquals(MimeType::tryFrom($fileMimeType), $snap->getMimeType());
        $this->assertEquals(time(), $snap->getCreationDate()->getTimestamp());
        $this->assertNull($snap->getLastSeenDate());
        $this->assertEquals($file->getAbsolutePath(), $snap->getFile()->getAbsolutePath());
    }

    /**
     * @throws Exception
     * @throws FileNotFoundException
     * @throws FileSizeTooBigException
     * @throws UnsupportedFileTypeException
     */
    public function testItFailsFileTooBig(): void
    {
        $fileStorageStub = $this->createConfiguredStub(FileStorageInterface::class, [
            'getFileMaximumAuthorizedBytesSize' => 10,
        ]);

        $snapRepositoryStub = $this->createStub(SnapRepositoryInterface::class);
        $snapFactory = new SnapFactory($fileStorageStub);
        $useCase = new CreateSnapUseCase($snapRepositoryStub, $fileStorageStub, $snapFactory);

        $request = new CreateSnapRequest(
            userId: Uuid::v7(),
            fileOriginalName: 'original-file-name.jpg',
            fileMimeType: MimeType::ImageJpeg->value,
            filePath: '/this/is/a/path/to/a/file.jpg',
            fileBytesSize: 100
        );

        $this->expectException(FileSizeTooBigException::class);

        $useCase($request);
    }

    /**
     * @throws Exception
     * @throws FileNotFoundException
     * @throws FileSizeTooBigException
     * @throws UnsupportedFileTypeException
     */
    public function testItFailsUnsupportedFileType(): void
    {
        $fileStorageStub = $this->createConfiguredStub(FileStorageInterface::class, [
            'getFileMaximumAuthorizedBytesSize' => 100,
        ]);

        $snapRepositoryStub = $this->createStub(SnapRepositoryInterface::class);
        $snapFactory = new SnapFactory($fileStorageStub);
        $useCase = new CreateSnapUseCase($snapRepositoryStub, $fileStorageStub, $snapFactory);

        $request = new CreateSnapRequest(
            userId: Uuid::v7(),
            fileOriginalName: 'original-file-name.bmp',
            fileMimeType: 'image/bmp',
            filePath: '/this/is/a/path/to/a/file.bmp',
            fileBytesSize: 10
        );

        $this->expectException(UnsupportedFileTypeException::class);

        $useCase($request);
    }

    /**
     * @throws Exception
     * @throws FileNotFoundException
     * @throws FileSizeTooBigException
     * @throws UnsupportedFileTypeException
     */
    public function testItFailsFileNotFound(): void
    {
        $fileStorageStub = $this->createConfiguredStub(FileStorageInterface::class, [
            'getFileMaximumAuthorizedBytesSize' => 100,
            'get' => null,
        ]);

        $snapRepositoryStub = $this->createStub(SnapRepositoryInterface::class);
        $snapFactory = new SnapFactory($fileStorageStub);
        $useCase = new CreateSnapUseCase($snapRepositoryStub, $fileStorageStub, $snapFactory);

        $request = new CreateSnapRequest(
            userId: Uuid::v7(),
            fileOriginalName: 'original-file-name.jpg',
            fileMimeType: MimeType::ImageJpeg->value,
            filePath: '/this/is/a/path/to/a/file.jpg',
            fileBytesSize: 10
        );

        $this->expectException(FileNotFoundException::class);

        $useCase($request);
    }
}
