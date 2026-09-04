<?php

namespace App\Application\Config\Service;

use App\Application\Config\Dto\OrdersConfigDto;
use App\Application\Config\Dto\ShippingFeeDto;
use App\Application\Config\Provider\OrdersConfigProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class OrdersConfigService
{
    public function __construct(
        private OrdersConfigProvider $provider,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function get(): OrdersConfigDto
    {
        return $this->provider->get();
    }

    /**
     * @param array<int, UploadedFile> $flagFiles
     */
    public function save(OrdersConfigDto $config, array $flagFiles = []): void
    {
        foreach ($config->shippingFees as $index => $shippingFee) {
            if (!$shippingFee instanceof ShippingFeeDto || !isset($flagFiles[$index])) {
                continue;
            }

            $shippingFee->flag = $this->storeFlag($flagFiles[$index], $shippingFee);
        }

        $this->provider->save($config);
    }

    private function storeFlag(UploadedFile $file, ShippingFeeDto $shippingFee): string
    {
        $publicPath = '/images/upload/config/flags';
        $directory = $this->projectDir . '/public' . $publicPath;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create upload directory "%s".', $directory));
        }

        $destination = '' !== $shippingFee->destination ? $shippingFee->destination : 'drapeau';
        $slug = strtolower((string) (new AsciiSlugger())->slug($destination));
        $filename = sprintf('flag-%s-%s.png', $slug, bin2hex(random_bytes(4)));

        $file->move($directory, $filename);

        return $publicPath . '/' . $filename;
    }
}
