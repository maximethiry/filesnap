<?php

declare(strict_types=1);

namespace App\Tests\Domain\UseCase\Snap;

use App\Application\Domain\Snap\Exception\FileNotFoundException;
use App\Application\Domain\Snap\FileStorage\File;
use App\Application\Domain\Snap\FileStorage\FileStorageInterface;
use App\Application\Domain\Snap\MimeType;
use App\Application\Domain\Snap\Snap;
use App\Application\Domain\Snap\SnapFactory;
use App\Application\Domain\Snap\SnapRepositoryInterface;
use App\Application\UseCase\Snap\FindByUser\FindSnapsByUserRequest;
use App\Application\UseCase\Snap\FindByUser\FindSnapsByUserUseCase;
use App\Tests\FilesnapTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use Random\RandomException;
use Symfony\Component\Uid\Uuid;

final class FindByUserTest extends FilesnapTestCase
{
    /**
     * @return list<array{0:list<Snap>}>
     *
     * @throws Exception
     * @throws FileNotFoundException
     * @throws RandomException
     */
    public static function provider(): array
    {
        $fileRepositoryStub = self::createConfiguredStub(FileStorageInterface::class, [
            'get' => new File('/this/is/an/absolute/path/to/file.jpg'),
        ]);

        $snapFactory = new SnapFactory($fileRepositoryStub);

        $snaps = [];
        for ($i = 0; $i < 5; ++$i) {
            $snaps[] = $snapFactory->create(
                id: Uuid::v4(),
                userId: Uuid::v7(),
                originalFilename: 'original-filename.jpg',
                mimeType: MimeType::ImageJpeg,
                creationDate: self::getRandomDateTime(),
                lastSeenDate: self::getRandomDateTime()
            );
        }

        return [
            [$snaps],
            [[]],
        ];
    }

    /**
     * @param list<Snap> $expectedSnaps
     *
     * @throws Exception
     * @throws RandomException
     */
    #[DataProvider('provider')]
    public function test(array $expectedSnaps): void
    {
        $count = self::getRandomInt();
        $request = new FindSnapsByUserRequest(Uuid::v7(), self::getRandomInt(), self::getRandomInt());

        $snapRepositoryMock = $this->createMock(SnapRepositoryInterface::class);

        $snapRepositoryMock
            ->expects($this->once())
            ->method('findByUser')
            ->with($request->getUserId(), $request->getOffset(), $request->getLimit())
            ->willReturn($expectedSnaps);

        $snapRepositoryMock
            ->expects($this->once())
            ->method('countByUser')
            ->with($request->getUserId())
            ->willReturn($count);

        $useCase = new FindSnapsByUserUseCase($snapRepositoryMock);

        $response = $useCase($request);
        $actualSnaps = $response->getSnaps();

        self::assertSameSize($expectedSnaps, $actualSnaps);
        self::assertSame($count, $response->getTotalCount());

        if ($expectedSnaps !== []) {
            foreach ($expectedSnaps as $i => $expectedSnap) {
                $actualSnap = $actualSnaps[$i];

                self::assertSame($expectedSnap->getId(), $actualSnap->getId());
                self::assertSame($expectedSnap->getUserId(), $actualSnap->getUserId());
                self::assertSame($expectedSnap->getOriginalFilename(), $actualSnap->getOriginalFilename());
                self::assertSame($expectedSnap->getMimeType(), $actualSnap->getMimeType());
                self::assertSame($expectedSnap->getCreationDate(), $actualSnap->getCreationDate());
                self::assertSame($expectedSnap->getLastSeenDate(), $actualSnap->getLastSeenDate());
                self::assertSame($expectedSnap->getFile()->getAbsolutePath(), $actualSnap->getFile()->getAbsolutePath());
            }
        }
    }
}
