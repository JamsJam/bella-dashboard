<?php

namespace App\Validator\Avatar\Filename;

final class BodyFilenameValidator
{
    public function validate($name)
    {
        $nameSplite = explode(
            separator: '__',
            string: $name
        );
        if (5 !== count($nameSplite)) {
            throw new \InvalidArgumentException('Nom invalide,  nombre de block attendu : 5, trouvé '.count($nameSplite), 1);
        }

        for ($index = 1; $index < count($nameSplite); ++$index) {
            $blockTovalidate = $nameSplite[$index];

            switch ($index) {
                case 1:
                    if (!$this->matchRegex($blockTovalidate)) {
                        throw new \InvalidArgumentException('Couleur invalide :'.$blockTovalidate, 1);
                    }
                    break;
                case 2:
                    if (!$this->matchRegex($blockTovalidate)) {
                        throw new \InvalidArgumentException('Morphologie invalide :'.$blockTovalidate, 1);
                    }
                    break;
                case 3:
                    if (!$this->matchRegex($blockTovalidate)) {
                        throw new \InvalidArgumentException('Morphotype invalide :'.$blockTovalidate, 1);
                    }
                    break;
                case 4:
                    if (!$this->matchRegex(explode('.', $blockTovalidate)[0])) {
                        throw new \InvalidArgumentException('Vetement invalide :'.$blockTovalidate, 1);
                    }
                    break;

                default:
                    break;
            }
        }
    }

    private function matchRegex(string $chunk): bool
    {
        $matchResult = preg_match('/^[a-z0-9_]+(?:-+[a-z0-9_]+)*$/', $chunk);

        switch ($matchResult) {
            case 1:
                return true;
                break;
            case 0:
                if ('-none-' === $chunk) {
                    return true;
                } else {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }
    }
}
