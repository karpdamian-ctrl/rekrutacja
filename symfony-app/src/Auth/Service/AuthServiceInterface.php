<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface AuthServiceInterface
{
    public function authenticate(string $username, string $token): ?User;

    public function logIn(SessionInterface $session, User $user): void;

    public function logOut(SessionInterface $session): void;
}
