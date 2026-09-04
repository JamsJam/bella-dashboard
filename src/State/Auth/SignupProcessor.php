<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Auth\SignupInput;
use App\ApiResource\Auth\SignupOutput;
use App\Application\Auth\SignupConfirmationMailer;
use App\Entity\Users\Customers;
use App\Repository\Users\CustomersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<SignupInput, SignupOutput>
 */
final readonly class SignupProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomersRepository $customersRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private SignupConfirmationMailer $signupConfirmationMailer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): SignupOutput
    {
        if (!$data instanceof SignupInput) {
            throw new \InvalidArgumentException('Invalid signup payload.');
        }

        $email = mb_strtolower(trim($data->email));
        $existingCustomer = $this->customersRepository->findOneBy(['email' => $email]);
        if ($existingCustomer instanceof Customers) {
            if (!$existingCustomer->isSignupConfirmed() && $this->isSignupConfirmationExpired($existingCustomer)) {
                $this->entityManager->remove($existingCustomer);
                $this->entityManager->flush();
            } else {
                throw new ConflictHttpException('Un compte existe deja avec cet email.');
            }
        }

        $now = new \DateTimeImmutable();
        $expiresAt = $now->modify('+20 minutes');
        $customer = (new Customers())
            ->setEmail($email)
            ->setRoles([])
            ->setIsSignupConfirmed(false)
            ->setSignupVerificationCode($this->generateVerificationCode())
            ->setSignupVerificationExpiresAt($expiresAt)
            ->setCreatedAt($now)
            ->setEditedAt($now);
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $data->password));

        $this->entityManager->persist($customer);
        $this->entityManager->flush();
        $this->signupConfirmationMailer->sendConfirmationCode($customer);

        return new SignupOutput(
            id: (int) $customer->getId(),
            email: (string) $customer->getEmail(),
            isSignupConfirmed: $customer->isSignupConfirmed(),
            signupVerificationExpiresAt: $customer->getSignupVerificationExpiresAt(),
        );
    }

    private function isSignupConfirmationExpired(Customers $customer): bool
    {
        $expiresAt = $customer->getSignupVerificationExpiresAt();

        return $expiresAt instanceof \DateTimeImmutable && $expiresAt <= new \DateTimeImmutable();
    }

    private function generateVerificationCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', \STR_PAD_LEFT);
    }
}
