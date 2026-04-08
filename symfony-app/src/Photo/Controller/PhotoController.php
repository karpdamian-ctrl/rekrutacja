<?php

declare(strict_types=1);

namespace App\Photo\Controller;

use App\Entity\Photo;
use App\Entity\User;
use App\Photo\Service\PhotoReactionResult;
use App\Photo\Service\PhotoReactionServiceInterface;
use App\Shared\Controller\AppController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PhotoController extends AppController
{
    private const LIKE_CSRF_TOKEN_ID = 'photo_like';

    #[Route('/photo/{id}/like', name: 'photo_like', methods: ['POST'])]
    public function like(int $id, Request $request, EntityManagerInterface $em, PhotoReactionServiceInterface $photoReactionService): JsonResponse
    {
        $user = $this->resolveCurrentUser($request, $em);
        if (!$user instanceof User) {
            return $this->errorResponse('photo.like.login_required', JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$this->hasValidCsrfToken($request, self::LIKE_CSRF_TOKEN_ID, 'X-CSRF-TOKEN', true)) {
            return $this->errorResponse('security.csrf.invalid_json', JsonResponse::HTTP_FORBIDDEN);
        }

        $result = $photoReactionService->like($user, $id);

        return $this->reactionResponse($result);
    }

    #[Route('/photo/{id}/unlike', name: 'photo_unlike', methods: ['POST'])]
    public function unlike(int $id, Request $request, EntityManagerInterface $em, PhotoReactionServiceInterface $photoReactionService): JsonResponse
    {
        $user = $this->resolveCurrentUser($request, $em);
        if (!$user instanceof User) {
            return $this->errorResponse('photo.unlike.login_required', JsonResponse::HTTP_UNAUTHORIZED);
        }

        if (!$this->hasValidCsrfToken($request, self::LIKE_CSRF_TOKEN_ID, 'X-CSRF-TOKEN', true)) {
            return $this->errorResponse('security.csrf.invalid_json', JsonResponse::HTTP_FORBIDDEN);
        }

        $result = $photoReactionService->unlike($user, $id);

        return $this->reactionResponse($result);
    }

    /**
     * @param array<string, bool|float|int|string|null> $parameters
     */
    private function errorResponse(string $messageKey, int $statusCode, array $parameters = []): JsonResponse
    {
        return $this->json([
            'status' => 'error',
            'message' => $this->translate($messageKey, $parameters),
        ], $statusCode);
    }

    private function reactionResponse(PhotoReactionResult $result): JsonResponse
    {
        if ($result->isNotFound()) {
            return $this->errorResponse($result->getMessageKey(), JsonResponse::HTTP_NOT_FOUND, $result->getMessageParameters());
        }

        $photo = $result->getPhoto();
        \assert($photo instanceof Photo);

        return $this->json([
            'status' => $result->getStatus(),
            'message' => $this->translate($result->getMessageKey(), $result->getMessageParameters()),
            'photoId' => $photo->getId(),
            'liked' => $result->isLiked(),
            'likeCounter' => $photo->getLikeCounter(),
        ]);
    }
}
