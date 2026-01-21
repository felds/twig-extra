<?php

declare(strict_types=1);

namespace Felds\TwigExtra;

use Twig\Extension\AbstractExtension;

class SwitchExtension extends AbstractExtension
{
    public function getTokenParsers(): array
    {
        return [new SwitchTokenParser()];
    }
}
