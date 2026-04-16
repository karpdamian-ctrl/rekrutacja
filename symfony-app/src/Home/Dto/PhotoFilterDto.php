<?php

declare(strict_types=1);

namespace App\Home\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final class PhotoFilterDto
{
    private const TAKEN_AT_PATTERN = '/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2})?$/';

    #[Assert\Length(max: 255)]
    public ?string $location = null;

    #[Assert\Length(max: 255)]
    public ?string $camera = null;

    #[Assert\Length(max: 255)]
    public ?string $description = null;

    #[Assert\Length(max: 180)]
    public ?string $username = null;

    #[Assert\Regex(pattern: self::TAKEN_AT_PATTERN)]
    public ?string $takenAtFrom = null;

    #[Assert\Regex(pattern: self::TAKEN_AT_PATTERN)]
    public ?string $takenAtTo = null;

    public static function fromRequest(Request $request): self
    {
        $dto = new self();
        $dto->location = self::normalizeFilterValue($request->query->get('location'));
        $dto->camera = self::normalizeFilterValue($request->query->get('camera'));
        $dto->description = self::normalizeFilterValue($request->query->get('description'));
        $dto->takenAtFrom = self::normalizeFilterValue($request->query->get('taken_at_from'));
        $dto->takenAtTo = self::normalizeFilterValue($request->query->get('taken_at_to'));
        $dto->username = self::normalizeFilterValue($request->query->get('username'));

        return $dto;
    }

    /**
     * @return array<string, string|null>
     */
    public function toRepositoryFilters(): array
    {
        return [
            'location' => $this->location,
            'camera' => $this->camera,
            'description' => $this->description,
            'taken_at_from' => $this->takenAtFrom,
            'taken_at_to' => $this->takenAtTo,
            'username' => $this->username,
        ];
    }

    private static function normalizeFilterValue(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $trimmedValue = trim($value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }
}
