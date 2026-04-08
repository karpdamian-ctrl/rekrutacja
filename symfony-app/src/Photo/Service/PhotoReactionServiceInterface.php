<?php

declare(strict_types=1);

namespace App\Photo\Service;

use App\Entity\User;

interface PhotoReactionServiceInterface
{
    public function like(User $user, int $photoId): PhotoReactionResult;

    public function unlike(User $user, int $photoId): PhotoReactionResult;
}
