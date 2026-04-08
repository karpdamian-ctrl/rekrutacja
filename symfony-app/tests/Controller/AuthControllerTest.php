<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeStatement('SET session_replication_role = replica');
        $connection->executeStatement($platform->getTruncateTableSQL('likes', true));
        $connection->executeStatement($platform->getTruncateTableSQL('photos', true));
        $connection->executeStatement($platform->getTruncateTableSQL('auth_tokens', true));
        $connection->executeStatement($platform->getTruncateTableSQL('users', true));
        $connection->executeStatement('SET session_replication_role = DEFAULT');
    }

    public function testLoginForm(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('form[action="/login"][method="post"]'));
        self::assertCount(1, $crawler->filter('input[name="username"]'));
        self::assertCount(1, $crawler->filter('input[name="token"]'));
        self::assertCount(1, $crawler->filter('input[name="_token"]'));
    }

    public function testLoginFormRedirectsLoggedInUser(): void
    {
        [$user] = $this->createUserWithToken('logged_user', 'logged@example.com', 'logged-token');
        $this->logInUser($user);

        $this->client->request('GET', '/login');

        self::assertResponseRedirects('/');
    }

    public function testLoginValidPost(): void
    {
        [$user, $authToken] = $this->createUserWithToken('matched_user', 'matched@example.com', 'matched-token');
        $csrfToken = $this->fetchLoginCsrfToken();

        $this->client->request('POST', '/login', [
            'username' => $user->getUsername(),
            'token' => $authToken->getToken(),
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/');

        $session = $this->readSession();
        self::assertSame($user->getId(), $session->get('user_id'));
        self::assertSame($user->getUsername(), $session->get('username'));
    }

    public function testLoginRejectsWrongPair(): void
    {
        [$firstUser] = $this->createUserWithToken('first_user', 'first@example.com', 'first-token');
        [, $secondToken] = $this->createUserWithToken('second_user', 'second@example.com', 'second-token');
        $csrfToken = $this->fetchLoginCsrfToken();

        $this->client->request('POST', '/login', [
            'username' => $firstUser->getUsername(),
            'token' => $secondToken->getToken(),
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/login');

        $this->client->followRedirect();
        self::assertStringContainsString('Invalid username or token.', (string) $this->client->getResponse()->getContent());
        self::assertNull($this->readSession()->get('user_id'));
    }

    public function testLoginRejectsInvalidCsrf(): void
    {
        [$user, $authToken] = $this->createUserWithToken('csrf_user', 'csrf@example.com', 'csrf-token');

        $this->client->request('POST', '/login', [
            'username' => $user->getUsername(),
            'token' => $authToken->getToken(),
            '_token' => 'invalid-token',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame('Invalid CSRF token', $this->client->getResponse()->getContent());
    }

    public function testLoginRejectsEmptyCredentials(): void
    {
        $csrfToken = $this->fetchLoginCsrfToken();

        $this->client->request('POST', '/login', [
            'username' => '',
            'token' => '',
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/login');

        $this->client->followRedirect();
        self::assertStringContainsString('Username and token are required.', (string) $this->client->getResponse()->getContent());
        self::assertNull($this->readSession()->get('user_id'));
    }

    public function testLoginShowsSuccessFlash(): void
    {
        [$user, $authToken] = $this->createUserWithToken('flash_user', 'flash@example.com', 'flash-token');
        $csrfToken = $this->fetchLoginCsrfToken();

        $this->client->request('POST', '/login', [
            'username' => $user->getUsername(),
            'token' => $authToken->getToken(),
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();
        self::assertStringContainsString('Welcome back, ' . $user->getUsername() . '!', (string) $this->client->getResponse()->getContent());
    }

    public function testLogoutValidPost(): void
    {
        [$user] = $this->createUserWithToken('logout_user', 'logout@example.com', 'logout-token');
        $this->logInUser($user);
        $csrfToken = $this->fetchLogoutCsrfToken();

        $this->client->request('POST', '/logout', [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/');

        $session = $this->readSession();
        self::assertNull($session->get('user_id'));
        self::assertNull($session->get('username'));
    }

    public function testLogoutRejectsInvalidCsrf(): void
    {
        [$user] = $this->createUserWithToken('logout_fail', 'logout_fail@example.com', 'logout-fail-token');
        $this->logInUser($user);

        $this->client->request('POST', '/logout', [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame('Invalid CSRF token', $this->client->getResponse()->getContent());
        self::assertSame($user->getId(), $this->readSession()->get('user_id'));
    }

    public function testLogoutShowsInfoFlash(): void
    {
        [$user] = $this->createUserWithToken('logout_flash', 'logout_flash@example.com', 'logout-flash-token');
        $this->logInUser($user);
        $csrfToken = $this->fetchLogoutCsrfToken();

        $this->client->request('POST', '/logout', [
            '_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/');

        $this->client->followRedirect();
        self::assertStringContainsString('You have been logged out successfully.', (string) $this->client->getResponse()->getContent());
    }

    public function testLogoutOnlyPost(): void
    {
        $this->client->request('GET', '/logout');

        self::assertResponseStatusCodeSame(405);
    }

    public function testGuestSeesLoginLink(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('a[href="/login"]'));
        self::assertCount(0, $crawler->filter('form[action="/logout"]'));
    }

    public function testLoggedInUserSeesLogoutForm(): void
    {
        [$user] = $this->createUserWithToken('nav_user', 'nav@example.com', 'nav-token');
        $this->logInUser($user);

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('a[href="/login"]'));
        self::assertCount(1, $crawler->filter('form[action="/logout"]'));
    }

    /**
     * @return array{0: User, 1: AuthToken}
     */
    private function createUserWithToken(string $username, string $email, string $tokenValue): array
    {
        $user = new User();
        $user
            ->setUsername($username)
            ->setEmail($email);

        $token = new AuthToken();
        $token
            ->setToken($tokenValue)
            ->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return [$user, $token];
    }

    private function fetchLoginCsrfToken(): string
    {
        $crawler = $this->client->request('GET', '/login');

        return (string) $crawler->filter('input[name="_token"]')->attr('value');
    }

    private function fetchLogoutCsrfToken(): string
    {
        $crawler = $this->client->request('GET', '/');

        return (string) $crawler->filter('form[action="/logout"] input[name="_token"]')->attr('value');
    }

    private function logInUser(User $user): void
    {
        $session = $this->client->getContainer()->get('session.factory')->createSession();
        $session->start();
        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->save();

        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }

    private function readSession(): SessionInterface
    {
        $session = $this->client->getContainer()->get('session.factory')->createSession();
        $cookie = $this->client->getCookieJar()->get($session->getName());

        self::assertNotNull($cookie);

        $session->setId($cookie->getValue());
        $session->start();

        return $session;
    }
}
