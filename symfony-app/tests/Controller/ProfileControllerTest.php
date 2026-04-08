<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Photo;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

final class ProfileControllerTest extends WebTestCase
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

    public function testGuestIsRedirectedToHome(): void
    {
        $this->client->request('GET', '/profile');

        self::assertResponseRedirects('/');
    }

    public function testLoggedInUserSeesOwnProfileData(): void
    {
        $user = $this->createUserWithProfile();
        $this->logInUser($user);

        $this->client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringContainsString('John Doe', $content);
        self::assertStringContainsString('@profile_user', $content);
        self::assertStringContainsString('profile@example.com', $content);
        self::assertStringContainsString('31 years old', $content);
        self::assertStringContainsString('About Me', $content);
        self::assertStringContainsString('Landscape photographer', $content);
    }

    public function testProfileShowsPhotoCount(): void
    {
        $user = $this->createUserWithProfile();
        $this->createPhotoForUser($user, 'First photo');
        $this->createPhotoForUser($user, 'Second photo');
        $this->entityManager->clear();

        $this->logInUser($user);

        $crawler = $this->client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('2', trim((string) $crawler->filter('.stat-number')->text()));
        self::assertStringContainsString('Photos', (string) $this->client->getResponse()->getContent());
    }

    private function createUserWithProfile(): User
    {
        $user = new User();
        $user
            ->setUsername('profile_user')
            ->setEmail('profile@example.com')
            ->setName('John')
            ->setLastName('Doe')
            ->setAge(31)
            ->setBio('Landscape photographer');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createPhotoForUser(User $user, string $description): void
    {
        $photo = new Photo();
        $photo
            ->setImageUrl('https://example.com/' . md5($description) . '.jpg')
            ->setDescription($description)
            ->setUser($user);

        $this->entityManager->persist($photo);
        $this->entityManager->flush();
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
}
