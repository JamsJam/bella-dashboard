<?php

namespace App\Application\Avatar\Resolver;


abstract class AbstractResolver
{

    /** Returns the mapping of keys to class names.
     *
     * @return array The mapping of keys to class names.
     */
    abstract protected function map(): array;


    /** Retourne le nom de la classe résolue pour une clé donnée.
     *
     * @param string $key The key to resolve.
     * @return string The resolved class name.
     * @throws \InvalidArgumentException If the key is not found in the mapping.
     */
    public function resolve(string $key): string
    {

        if (!$this->supports($key)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown key "%s". Available keys are: %s.',
                $key,
                implode(', ', $this->getAvailableParts()),
            ));
        }

        return $this->map()[$key];
    }

    /** Retourne les clés disponibles dans la map.
     *
     * @return array The available keys in the mapping.
     */
    public function getAvailableParts(): array
    {
        return array_keys($this->map());
    }

    /** Retourne true si la clé est supportée par le resolver, false sinon.
     *
     * @param string $key The key to check.
     * @return bool True if the key is supported, false otherwise.
     */
    public function supports(string $key): bool
    {
        return isset($this->map()[$key]);
    }
}