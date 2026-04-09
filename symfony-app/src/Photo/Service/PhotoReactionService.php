<?php

declare(strict_types=1);

namespace App\Photo\Service;

use App\Entity\Photo;
use App\Entity\User;
use App\Likes\LikeRepositoryInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class PhotoReactionService implements PhotoReactionServiceInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LikeRepositoryInterface $likeRepository
    ) {
    }

    public function like(User $user, int $photoId): PhotoReactionResult
    {
        $photo = $this->findPhoto($photoId);
        if (!$photo instanceof Photo) {
            return PhotoReactionResult::notFound();
        }

        if ($this->likeRepository->hasUserLikedPhoto($user, $photo)) {
            return PhotoReactionResult::alreadyLiked($photo);
        }

        try {
            $this->likeRepository->createLike($user, $photo);
            $this->changePhotoLikeCounter($photo, 1);
        } catch (UniqueConstraintViolationException) {
            return PhotoReactionResult::alreadyLiked($photo);
        }

        return PhotoReactionResult::liked($photo);
    }

    public function unlike(User $user, int $photoId): PhotoReactionResult
    {
        $photo = $this->findPhoto($photoId);
        if (!$photo instanceof Photo) {
            return PhotoReactionResult::notFound();
        }

        if (!$this->likeRepository->hasUserLikedPhoto($user, $photo)) {
            return PhotoReactionResult::notLikedYet($photo);
        }

        $this->likeRepository->removeLike($user, $photo);
        $this->changePhotoLikeCounter($photo, -1);

        return PhotoReactionResult::unliked($photo);
    }

    private function changePhotoLikeCounter(Photo $photo, int $increment): void
    {
        $photo->setLikeCounter($photo->getLikeCounter() + $increment);
        $this->entityManager->persist($photo);
        $this->entityManager->flush();
    }

    private function findPhoto(int $photoId): ?Photo
    {
        $photo = $this->entityManager->getRepository(Photo::class)->find($photoId);

        return $photo instanceof Photo ? $photo : null;
    }
}
