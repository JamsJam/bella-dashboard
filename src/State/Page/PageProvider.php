<?php

namespace App\State\Page;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Pages\Page as PageDTO;
use App\Application\Clothes\Services\ClotheService;
use App\Service\YamlLoaderService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PageProvider implements ProviderInterface
{

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private YamlLoaderService $yamlLoader,
        private ClotheService $clotheService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {

        $slug = $uriVariables['slug'];

        $ymlPagePath = $this->parameterBag->get('kernel.project_dir') . '/pages/api/'.$slug.'.yaml';
        
        $data = $this->yamlLoader->load($ymlPagePath);
        foreach ($data["sections"] as &$section) {
            if ($section["type"] === "product_list") {
                
                $section['content']["products"] = $this->clotheService->getBestselledClothe(4);
                
                
            }
        }

        $page = new PageDTO;
        $page->slug = $data["slug"];
        $page->seo = $data["seo"];
        $page->section = $data["sections"];







        // Retrieve the state from somewhere
        return $page;
    }
}
