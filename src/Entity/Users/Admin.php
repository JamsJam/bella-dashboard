<?php

namespace App\Entity\Users;

use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Traits\UserFieldsTrait;
use App\Repository\Users\AdminRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AdminRepository::class)]
#[ORM\Table(name: '`admin`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Admin implements UserInterface, PasswordAuthenticatedUserInterface
{
    use DateFieldsTrait;
    use UserFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
}
