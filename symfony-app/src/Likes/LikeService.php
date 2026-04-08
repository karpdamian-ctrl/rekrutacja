<?php

declare(strict_types=1);

namespace App\Likes;

use App\Entity\Photo;
use App\Entity\User;

class LikeService
{
    public function __construct(
        private LikeRepositoryInterface $likeRepository
    ) {}

    public function execute(Photo $photo): void
    {
        try {
            $this->likeRepository->createLike($photo);
            $this->likeRepository->updatePhotoCounter($photo, 1);
        } catch (\Throwable $e) {
            throw new \Exception('Something went wrong while liking the photo');
        }
    }
}
