<?php

namespace App\Service;

use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

final class YamlLoaderService
{
    /**
     * Charge un fichier YAML et retourne son contenu sous forme de tableau.
     *
     * @param string $filePath Chemin complet vers le fichier YAML
     *
     * @return array Contenu du YAML converti en tableau
     *
     * @throws FileNotFoundException si le fichier n'existe pas
     * @throws \RuntimeException     si le YAML n'est pas valide
     */
    public function load(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException(sprintf('Le fichier YAML "%s" est introuvable.', $filePath));
        }

        try {
            $data = Yaml::parseFile($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Erreur lors du parsing du fichier YAML "%s" : %s', $filePath, $e->getMessage()));
        }

        return $data ?? [];
    }
}
