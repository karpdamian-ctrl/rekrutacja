<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\DuplicateLikeException;
use App\Likes\LikeRepositoryInterface;
use App\Likes\LikeService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\TestCase;

final class LikeServiceTest extends TestCase
{
    public function testExecuteCreatesLikeAndUpdatesCounter(): void
    {
        $user = $this->createUser();
        $photo = $this->createPhoto($user);

        $repository = $this->createMock(LikeRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('createLike')
            ->with($user, $photo);
        $repository
            ->expects(self::once())
            ->method('updatePhotoCounter')
            ->with($photo, 1);

        $service = new LikeService($repository);

        $service->execute($user, $photo);
    }

    public function testExecuteThrowsDuplicateLikeExceptionForUniqueConstraintViolation(): void
    {
        $user = $this->createUser();
        $photo = $this->createPhoto($user);

        $repository = $this->createMock(LikeRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('createLike')
            ->willThrowException($this->createUniqueConstraintViolationException());
        $repository
            ->expects(self::never())
            ->method('updatePhotoCounter');

        $service = new LikeService($repository);

        $this->expectException(DuplicateLikeException::class);

        $service->execute($user, $photo);
    }

    public function testExecuteWrapsUnexpectedThrowableInRuntimeException(): void
    {
        $user = $this->createUser();
        $photo = $this->createPhoto($user);

        $repository = $this->createMock(LikeRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('createLike')
            ->willThrowException(new \LogicException('Unexpected failure'));
        $repository
            ->expects(self::never())
            ->method('updatePhotoCounter');

        $service = new LikeService($repository);

        $this->expectException(\RuntimeException::class);

        $service->execute($user, $photo);
    }

    private function createUser(): User
    {
        return (new User())
            ->setUsername('service_user')
            ->setEmail('service@example.com');
    }

    private function createPhoto(User $user): Photo
    {
        return (new Photo())
            ->setImageUrl('https://example.com/service-photo.jpg')
            ->setDescription('Service photo')
            ->setUser($user);
    }

    private function createUniqueConstraintViolationException(): UniqueConstraintViolationException
    {
        /** @var UniqueConstraintViolationException $exception */
        $exception = (new \ReflectionClass(UniqueConstraintViolationException::class))
            ->newInstanceWithoutConstructor();

        return $exception;
    }
}
