<?php

namespace App\Processor\Menu;

use App\DTO\Menu\MenuItemDTO;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\Length;


class MenuProcessor
{

    public function __construct(
        private Security $security,
    ){
    }

    /**
     * Retourne uniquement les item que l'utilisateur a le droit de consulter
     * @param MenuItemDTO[] $menu
     * @return MenuItemDTO[]
     */
    public function process(array $menu): array
    {
        return array_values(array_filter($menu, [$this, 'isAccessible']));
    }
    
    /**
     * Retourne uniquement les item que l'utilisateur a le droit de consulter
     *
     * @param MenuItemDTO $item
     * @return boolean
     */
    private function isAccessible(MenuItemDTO $item): bool
    {
        $user = $this->security->getUser();
        $requiredRoles = (array) $item->getRoles();

        // Pas connecté → masquer les items qui ont des rôles
        // if (!$user) {
        //     return false;
        // }
        // Aucun rôle requis → visible
        if(count($requiredRoles) === 0){
            return true;
        }

        $userRoles = $user->getRoles();
        foreach ($item->getRoles() as $role) {
            if(in_array($role,$userRoles)){

                return true;
            }
        }
        return false;
    }
}
