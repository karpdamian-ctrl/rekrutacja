<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use App\Shared\Controller\AppController;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AppControllerTest extends TestCase
{
    public function testResolveCurrentUserReturnsNullWhenSessionHasNoUserId(): void
    {
        $controller = $this->createController();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('getRepository');

        self::assertNull($controller->publicResolveCurrentUser($this->createRequest(), $entityManager));
    }

    public function testResolveCurrentUserReturnsUserWhenFound(): void
    {
        $user = (new User())
            ->setUsername('john')
            ->setEmail('john@example.com');

        $reflectionProperty = new \ReflectionProperty(User::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($user, 15);

        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(15)
            ->willReturn($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $request = $this->createRequest();
        $request->getSession()->set('user_id', 15);

        self::assertSame($user, $this->createController()->publicResolveCurrentUser($request, $entityManager));
    }

    public function testResolveCurrentUserClearsInvalidSessionWhenRequested(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository
            ->expects(self::once())
            ->method('find')
            ->with(99)
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $request = $this->createRequest();
        $request->getSession()->set('user_id', 99);
        $request->getSession()->set('username', 'ghost');

        $controller = $this->createController();

        self::assertNull($controller->publicResolveCurrentUser($request, $entityManager, true));
        self::assertNull($request->getSession()->get('user_id'));
        self::assertNull($request->getSession()->get('username'));
    }

    public function testHasValidCsrfTokenReadsRequestBodyByDefault(): void
    {
        $controller = $this->createController(['login' => 'body-token']);
        $request = $this->createRequest();
        $request->request->set('_token', 'body-token');

        self::assertTrue($controller->publicHasValidCsrfToken($request, 'login'));
    }

    public function testHasValidCsrfTokenReadsHeaderWhenRequested(): void
    {
        $controller = $this->createController(['photo_like' => 'header-token']);
        $request = $this->createRequest();
        $request->headers->set('X-CSRF-TOKEN', 'header-token');

        self::assertTrue($controller->publicHasValidCsrfToken($request, 'photo_like', 'X-CSRF-TOKEN', true));
    }

    public function testHasValidCsrfTokenReturnsFalseForInvalidToken(): void
    {
        $controller = $this->createController(['logout' => 'valid-token']);
        $request = $this->createRequest();
        $request->request->set('_token', 'invalid-token');

        self::assertFalse($controller->publicHasValidCsrfToken($request, 'logout'));
    }

    public function testTranslateDelegatesToTranslator(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('auth.login.success', ['%username%' => 'john'], 'messages', 'en')
            ->willReturn('Welcome back, john!');

        $controller = new TestAppController($translator);

        self::assertSame(
            'Welcome back, john!',
            $controller->publicTranslate('auth.login.success', ['%username%' => 'john'], 'messages', 'en')
        );
    }

    /**
     * @param array<string, string> $validTokensById
     */
    private function createController(array $validTokensById = []): TestAppController
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $messageKey): string => $messageKey);

        return new TestAppController($translator, $validTokensById);
    }

    private function createRequest(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}

final class TestAppController extends AppController
{
    /**
     * @param array<string, string> $validTokensById
     */
    public function __construct(
        TranslatorInterface $translator,
        private readonly array $validTokensById = []
    ) {
        parent::__construct($translator);
    }

    public function publicResolveCurrentUser(
        Request $request,
        EntityManagerInterface $entityManager,
        bool $clearInvalidSession = false
    ): ?User {
        return $this->resolveCurrentUser($request, $entityManager, $clearInvalidSession);
    }

    public function publicHasValidCsrfToken(
        Request $request,
        string $tokenId,
        string $tokenField = '_token',
        bool $fromHeader = false
    ): bool {
        return $this->hasValidCsrfToken($request, $tokenId, $tokenField, $fromHeader);
    }

    /**
     * @param array<string, bool|float|int|string|null> $parameters
     */
    public function publicTranslate(
        string $messageKey,
        array $parameters = [],
        ?string $domain = null,
        ?string $locale = null
    ): string {
        return $this->translate($messageKey, $parameters, $domain, $locale);
    }

    protected function isCsrfTokenValid(string $id, ?string $token): bool
    {
        return isset($this->validTokensById[$id]) && $this->validTokensById[$id] === $token;
    }
}
