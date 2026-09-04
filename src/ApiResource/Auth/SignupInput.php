<?php

namespace App\ApiResource\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class SignupInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    public string $password = '';
}
