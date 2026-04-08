<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Auth\Service\AuthService;
use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class AuthServiceTest extends TestCase
{
    public function testAuthenticateReturnsUserWhenUsernameMatchesTokenOwner(): void
    {
        $user = (new User())
            ->setUsername('nature_lover')
            ->setEmail('nature@example.com');

        $authToken = (new AuthToken())
            ->setToken('valid-token')
            ->setUser($user);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['token' => 'valid-token'])
            ->willReturn($authToken);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(AuthToken::class)
            ->willReturn($repository);

        $service = new AuthService($entityManager);

        self::assertSame($user, $service->authenticate('nature_lover', 'valid-token'));
    }

    public function testAuthenticateReturnsNullWhenUsernameDoesNotMatchTokenOwner(): void
    {
        $user = (new User())
            ->setUsername('nature_lover')
            ->setEmail('nature@example.com');

        $authToken = (new AuthToken())
            ->setToken('valid-token')
            ->setUser($user);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['token' => 'valid-token'])
            ->willReturn($authToken);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(AuthToken::class)
            ->willReturn($repository);

        $service = new AuthService($entityManager);

        self::assertNull($service->authenticate('wrong_user', 'valid-token'));
    }

    public function testAuthenticateReturnsNullWhenTokenDoesNotExist(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['token' => 'missing-token'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(AuthToken::class)
            ->willReturn($repository);

        $service = new AuthService($entityManager);

        self::assertNull($service->authenticate('nature_lover', 'missing-token'));
    }

    public function testLogInStoresUserDataInSession(): void
    {
        $user = (new User())
            ->setUsername('session_user')
            ->setEmail('session@example.com');

        $reflectionProperty = new \ReflectionProperty(User::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($user, 42);

        $call = 0;
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects(self::exactly(2))
            ->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$call): void {
                if ($call === 0) {
                    self::assertSame('user_id', $key);
                    self::assertSame(42, $value);
                } else {
                    self::assertSame('username', $key);
                    self::assertSame('session_user', $value);
                }

                $call++;
            });

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = new AuthService($entityManager);

        $service->logIn($session, $user);
    }

    public function testLogOutClearsSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects(self::once())
            ->method('clear');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $service = new AuthService($entityManager);

        $service->logOut($session);
    }
}
