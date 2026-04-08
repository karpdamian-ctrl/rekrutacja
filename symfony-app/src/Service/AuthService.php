<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AuthToken;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function authenticate(string $username, string $token): ?User
    {
        $authToken = $this->entityManager
            ->getRepository(AuthToken::class)
            ->findOneBy(['token' => $token]);

        if (!$authToken instanceof AuthToken) {
            return null;
        }

        $user = $authToken->getUser();

        return $user->getUsername() === $username ? $user : null;
    }

    public function logIn(SessionInterface $session, User $user): void
    {
        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
    }

    public function logOut(SessionInterface $session): void
    {
        $session->clear();
    }
}
