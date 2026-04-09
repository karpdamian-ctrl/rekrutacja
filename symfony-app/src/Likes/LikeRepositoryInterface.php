<?php

declare(strict_types=1);

namespace App\Likes;

use App\Entity\Like;
use App\Entity\Photo;
use App\Entity\User;

interface LikeRepositoryInterface
{
    public function removeLike(User $user, Photo $photo): void;

    public function hasUserLikedPhoto(User $user, Photo $photo): bool;

    /**
     * @param array<int, int|null> $photoIds
     * @return array<int, int>
     */
    public function getLikedPhotoIdsForUser(User $user, array $photoIds): array;

    public function createLike(User $user, Photo $photo): Like;
}
