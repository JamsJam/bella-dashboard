<?php

namespace App\State\Auth;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Auth\ConfirmSignupOutput;
use App\Entity\Users\Customers;
use App\Repository\Users\CustomersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProcessorInterface<mixed, ConfirmSignupOutput>
 */
final readonly class ConfirmSignupProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomersRepository $customersRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ConfirmSignupOutput
    {
        $payload = $this->getPayload();

        $id = (int) ($payload['id'] ?? 0);
        $code = trim((string) ($payload['code'] ?? ''));

        if ($id <= 0 || !preg_match('/^\d{6}$/', $code)) {
            throw new BadRequestHttpException('Code de confirmation invalide.');
        }

        $customer = $this->customersRepository->find($id);
        if (!$customer instanceof Customers) {
            throw new BadRequestHttpException('Code de confirmation invalide.');
        }

        if ($customer->isSignupConfirmed()) {
            return new ConfirmSignupOutput(true);
        }

        if (!hash_equals((string) $customer->getSignupVerificationCode(), $code)) {
            throw new BadRequestHttpException('Code de confirmation invalide.');
        }

        if ($this->isSignupConfirmationExpired($customer)) {
            $this->entityManager->remove($customer);
            $this->entityManager->flush();

            throw new BadRequestHttpException('Le code de confirmation a expire.');
        }

        $customer
            ->setIsSignupConfirmed(true)
            ->setSignupVerificationCode(null)
            ->setSignupVerificationExpiresAt(null)
            ->setEditedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return new ConfirmSignupOutput(true);
    }

    private function isSignupConfirmationExpired(Customers $customer): bool
    {
        $expiresAt = $customer->getSignupVerificationExpiresAt();

        return $expiresAt instanceof \DateTimeImmutable && $expiresAt <= new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Payload de confirmation invalide.');
        }

        try {
            $payload = json_decode($request->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BadRequestHttpException('Payload de confirmation invalide.');
        }

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Payload de confirmation invalide.');
        }

        return $payload;
    }
}
