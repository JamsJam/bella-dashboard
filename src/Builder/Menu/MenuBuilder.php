<?php

namespace App\Builder\Menu;

use App\DTO\Menu\MenuItemDTO;
use App\Validator\DtoValidator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MenuBuilder
{
    public function __construct(
        private string $menuPath,
        private DtoValidator $dtoValidator
    ){}



    public function buildMenu(): array
    {
        $parsedMenu =(array) Yaml::parseFile($this->menuPath);

        $menuItems  = (array) array_map([$this, 'createMenuItemFromDTO'],$parsedMenu['menu']) ;


        return $menuItems;
    }

    /**
     *
     * @param Array $menuItem
     * @return MenuItemDTO
     * @throws BadRequestHttpException
     */
    private function createMenuItemFromDTO(Array $menuItem): MenuItemDTO
    {
        $dto = (new MenuItemDTO())
            ->setLabel($menuItem['label'] ?? '')
            ->setRoute($menuItem['route'] ?? '')
            ->setKey($menuItem['key'] ?? '')
            ->setClass($menuItem['class'] ?? '')
            ->setActivClass($menuItem['activClass'] ?? '')
            ->setAriaLabel($menuItem['ariaLabel'] ?? '')
            ->setRoles($menuItem['roles'] ?? [])
        ;

        
        $this->dtoValidator->validate($dto);

        return $dto;
    }
}
