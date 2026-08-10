<?php

namespace App\Application\Avatar\Validator\Constraint;

use App\Application\Avatar\Dto\Input\AvatarRenameValidationInputDto;
use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ValidAvatarRenameFiltersValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AvatarRenameFilterMapper $filterMapper,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidAvatarRenameFilters) {
            throw new UnexpectedTypeException($constraint, ValidAvatarRenameFilters::class);
        }

        if (!$value instanceof AvatarRenameValidationInputDto || !is_array($value->filters)) {
            return;
        }

        foreach ($this->filterMapper->getRequiredFilters($value->category) as $filterId) {
            $filterValue = $value->filters[$filterId] ?? null;

            if (is_array($filterValue)) {
                $filterValue = $filterValue['name'] ?? null;
            }

            if (!is_scalar($filterValue) || '' === trim((string) $filterValue)) {
                $this->context
                    ->buildViolation('Le paramètre « {{ filter }} » est obligatoire.')
                    ->setParameter('{{ filter }}', $filterId)
                    ->atPath(sprintf('filters[%s]', $filterId))
                    ->addViolation();
            }
        }
    }
}
