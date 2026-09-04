<?php

namespace App\ApiResource\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmSignupInput
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    public int $id = 0;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{6}$/')]
    public string $code = '';
}
