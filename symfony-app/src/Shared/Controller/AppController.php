<?php

declare(strict_types=1);

namespace App\Shared\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AppController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    protected function resolveCurrentUser(
        Request $request,
        EntityManagerInterface $entityManager,
        bool $clearInvalidSession = false
    ): ?User {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return null;
        }

        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user instanceof User && $clearInvalidSession) {
            $request->getSession()->clear();
        }

        return $user instanceof User ? $user : null;
    }

    protected function hasValidCsrfToken(
        Request $request,
        string $tokenId,
        string $tokenField = '_token',
        bool $fromHeader = false
    ): bool {
        $csrfToken = $fromHeader
            ? (string) $request->headers->get($tokenField, '')
            : (string) $request->request->get($tokenField, '');

        return $this->isCsrfTokenValid($tokenId, $csrfToken);
    }

    /**
     * @param array<string, bool|float|int|string|null> $parameters
     */
    protected function translate(string $messageKey, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->trans($messageKey, $parameters, $domain, $locale);
    }
}
