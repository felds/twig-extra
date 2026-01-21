<?php

declare(strict_types=1);

namespace Felds\TwigExtra;

use Twig\Extension\AbstractExtension;

class NullsafeExtension extends AbstractExtension
{
    public function getExpressionParsers(): array
    {
        return [new NullsafeDotExpressionParser()];
    }
}
