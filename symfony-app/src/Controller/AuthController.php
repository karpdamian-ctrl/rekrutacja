<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/auth/{username}/{token}', name: 'auth_login')]
    public function login(string $username, string $token, Connection $connection, Request $request): Response
    {
        $sql = "SELECT * FROM auth_tokens WHERE token = '$token'";
        $result = $connection->executeQuery($sql);
        $tokenData = $result->fetchAssociative();

        if (!$tokenData) {
            return new Response('Invalid token', 401);
        }

        $userSql = "SELECT * FROM users WHERE username = '$username'";
        $userResult = $connection->executeQuery($userSql);
        $userData = $userResult->fetchAssociative();

        if (!$userData) {
            return new Response('User not found', 404);
        }

        $session = $request->getSession();
        $session->set('user_id', $userData['id']);
        $session->set('username', $username);

        $this->addFlash('success', 'Welcome back, ' . $username . '!');

        return $this->redirectToRoute('home');
    }

    #[Route('/logout', name: 'logout')]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->clear();

        $this->addFlash('info', 'You have been logged out successfully.');

        return $this->redirectToRoute('home');
    }
}
