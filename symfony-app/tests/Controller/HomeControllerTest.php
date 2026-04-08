<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\Like;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;

final class HomeControllerTest extends WebTestCase
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

    public function testGuestSeesPhotosAndDisabledLikeButtons(): void
    {
        [, $photo] = $this->createUserAndPhoto();

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Test photo', (string) $this->client->getResponse()->getContent());
        self::assertCount(1, $crawler->filter(sprintf('img[src="%s"]', $photo->getImageUrl())));
        self::assertCount(1, $crawler->filter('.like-button.disabled'));
        self::assertCount(0, $crawler->filter('.js-like-button'));
    }

    public function testLoggedInUserSeesInteractiveLikeButton(): void
    {
        [$user, $photo] = $this->createUserAndPhoto();
        $this->logInUser($user);

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter(sprintf('.js-like-button[data-photo-id="%d"]', $photo->getId())));
        self::assertSame('false', $crawler->filter('.js-like-button')->attr('data-liked'));
        self::assertSame('🤍', trim((string) $crawler->filter('.js-like-icon')->text()));
    }

    public function testLoggedInUserSeesLikedPhotoState(): void
    {
        [$user, $photo] = $this->createUserAndPhoto();

        $like = new Like();
        $like->setUser($user);
        $like->setPhoto($photo);
        $photo->setLikeCounter(1);

        $this->entityManager->persist($like);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();

        $this->logInUser($user);

        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSame('true', $crawler->filter('.js-like-button')->attr('data-liked'));
        self::assertStringContainsString('liked', (string) $crawler->filter('.js-like-button')->attr('class'));
        self::assertSame('❤️', trim((string) $crawler->filter('.js-like-icon')->text()));
        self::assertSame('1', trim((string) $crawler->filter('.js-like-counter')->text()));
    }

    public function testEmptyStateIsVisibleWhenThereAreNoPhotos(): void
    {
        $crawler = $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('No photos yet', (string) $this->client->getResponse()->getContent());
        self::assertStringContainsString('Be the first to share your photography!', (string) $this->client->getResponse()->getContent());
        self::assertCount(1, $crawler->filter('.empty-state'));
    }

    /**
     * @return array{0: User, 1: Photo}
     */
    private function createUserAndPhoto(): array
    {
        $user = new User();
        $user
            ->setUsername('home_user')
            ->setEmail('home@example.com');

        $photo = new Photo();
        $photo
            ->setImageUrl('https://example.com/home-photo.jpg')
            ->setDescription('Test photo')
            ->setUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();

        return [$user, $photo];
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
