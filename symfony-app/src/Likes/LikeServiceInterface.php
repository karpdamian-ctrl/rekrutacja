<?php

declare(strict_types=1);

namespace App\Likes;

use App\Entity\Photo;
use App\Entity\User;

interface LikeServiceInterface
{
    public function execute(User $user, Photo $photo): void;
}
