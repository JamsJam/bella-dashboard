<?php

namespace App\Validator\Avatar\Filename;

final class HairFilenameValidator
{
    public function validate($name)
    {
        $nameSplite = explode(
            separator: '__',
            string: $name
        );
        if (4 !== count($nameSplite)) {
            throw new \InvalidArgumentException('Nom invalide,  nombre de block attendu : 4, trouvé '.count($nameSplite), 1);
        }

        for ($index = 1; $index < count($nameSplite); ++$index) {
            $blockTovalidate = $nameSplite[$index];

            switch ($index) {
                case 1:
                    if (!$this->matchRegex($blockTovalidate)) {
                        throw new \InvalidArgumentException('Couleur invalide : '.$blockTovalidate, 1);
                    }
                    break;
                case 2:
                    if (!$this->matchRegex($blockTovalidate)) {
                        throw new \InvalidArgumentException('Forme invalide : '.$blockTovalidate, 1);
                    }
                    break;
                case 3:
                    if (!$this->matchSide(explode('.', $blockTovalidate)[0])) {
                        throw new \InvalidArgumentException('Side invalide,  attendu front or Back, trouvé '.$blockTovalidate, 1);
                    }
                    break;

                default:
                    break;
            }
        }
    }

    private function matchSide(string $chunk): bool
    {
        return 'front' === $chunk || 'back' === $chunk;
    }

    private function matchRegex(string $chunk): bool
    {
        $matchResult = preg_match('/^[a-z0-9_]+(?:-+[a-z0-9_]+)*$/', $chunk);

        return 1 === $matchResult ? true : false;
    }
}
