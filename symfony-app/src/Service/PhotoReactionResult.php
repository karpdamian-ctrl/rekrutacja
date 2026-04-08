<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Photo;

final class PhotoReactionResult
{
    /**
     * @param array<string, bool|float|int|string|null> $messageParameters
     */
    public function __construct(
        private readonly string $status,
        private readonly string $messageKey,
        private readonly ?Photo $photo,
        private readonly bool $liked,
        private readonly array $messageParameters = []
    ) {
    }

    public static function notFound(): self
    {
        return new self('error', 'photo.reaction.not_found', null, false);
    }

    public static function liked(Photo $photo): self
    {
        return new self('liked', 'photo.reaction.liked', $photo, true);
    }

    public static function alreadyLiked(Photo $photo): self
    {
        return new self('noop', 'photo.reaction.already_liked', $photo, true);
    }

    public static function unliked(Photo $photo): self
    {
        return new self('unliked', 'photo.reaction.unliked', $photo, false);
    }

    public static function notLikedYet(Photo $photo): self
    {
        return new self('noop', 'photo.reaction.not_liked_yet', $photo, false);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    public function getMessageParameters(): array
    {
        return $this->messageParameters;
    }

    public function getPhoto(): ?Photo
    {
        return $this->photo;
    }

    public function isLiked(): bool
    {
        return $this->liked;
    }

    public function isNotFound(): bool
    {
        return $this->photo === null;
    }
}
