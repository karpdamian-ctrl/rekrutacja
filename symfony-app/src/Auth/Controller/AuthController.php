<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Service\AuthServiceInterface;
use App\Shared\Controller\AppController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AppController
{
    private const LOGIN_CSRF_TOKEN_ID = 'login';
    private const LOGOUT_CSRF_TOKEN_ID = 'logout';

    #[Route('/login', name: 'auth_login_form', methods: ['GET'])]
    public function showLoginForm(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->resolveCurrentUser($request, $entityManager, true)) {
            return $this->redirectToRoute('home');
        }

        return $this->render('auth/login.html.twig');
    }

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request, AuthServiceInterface $authService): Response
    {
        $username = trim((string) $request->request->get('username', ''));
        $token = trim((string) $request->request->get('token', ''));

        if (!$this->hasValidCsrfToken($request, self::LOGIN_CSRF_TOKEN_ID)) {
            return new Response($this->translate('security.csrf.invalid'), Response::HTTP_FORBIDDEN);
        }

        if ($username === '' || $token === '') {
            $this->addFlash('error', $this->translate('auth.login.required_fields'));

            return $this->redirectToRoute('auth_login_form');
        }

        $user = $authService->authenticate($username, $token);
        if (!$user) {
            $this->addFlash('error', $this->translate('auth.login.invalid_credentials'));

            return $this->redirectToRoute('auth_login_form');
        }

        $authService->logIn($request->getSession(), $user);

        $this->addFlash('success', $this->translate('auth.login.success', ['%username%' => $user->getUsername()]));

        return $this->redirectToRoute('home');
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request, AuthServiceInterface $authService): Response
    {
        if (!$this->hasValidCsrfToken($request, self::LOGOUT_CSRF_TOKEN_ID)) {
            return new Response($this->translate('security.csrf.invalid'), Response::HTTP_FORBIDDEN);
        }

        $authService->logOut($request->getSession());

        $this->addFlash('info', $this->translate('auth.logout.success'));

        return $this->redirectToRoute('home');
    }
}
