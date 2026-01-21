<?php

declare(strict_types=1);

namespace Felds\TwigExtra\Tests;

use Felds\TwigExtra\SwitchExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

class SwitchTagTest extends TestCase
{
    private Environment $twig;
    private ArrayLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ArrayLoader();
        $this->twig = new Environment($this->loader, ['strict_variables' => true]);
        $this->twig->addExtension(new SwitchExtension());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('comparisonsProvider')]
    public function testComparisons(string $caseExpression, mixed $value, string $expected): void
    {
        $template = "{% switch value %}{% case $caseExpression %}hit{% default %}miss{% endswitch %}";
        $result = $this->render($template, ['value' => $value]);
        $this->assertSame($expected, $result);
    }

    public static function comparisonsProvider(): array
    {
        return [
            ['2', 2, 'hit'],
            ['== 3', 3, 'hit'],
            ['!= 3', 2, 'hit'],
            ['is not same as(3)', '3', 'hit'],
            ['is same as(3)', 3, 'hit'],
            ['is same as("3")', 3, 'miss'],
            ['> 2', 3, 'hit'],
            ['< 2', 1, 'hit'],
            ['>= 2', 2, 'hit'],
            ['<= 2', 2, 'hit'],
            ['in [1, 2, 3]', 2, 'hit'],
            ['not in [1, 2, 3]', 4, 'hit'],
            ['matches "/foo$/"', 'barfoo', 'hit'],
            ['starts with "foo"', 'foobar', 'hit'],
            ['ends with "bar"', 'foobar', 'hit'],
            ['is same as(2)', '2', 'miss'],
        ];
    }

    public function testMultipleCasesSelectsFirstMatch(): void
    {
        $template = <<<'TWIG'
            {% switch value %}
            {% case > 10 %}gt10
            {% case > 1 %}gt1
            {% default %}other
            {% endswitch %}
        TWIG;

        $this->assertSame('gt1', trim($this->render($template, ['value' => 5])));
        $this->assertSame('other', trim($this->render($template, ['value' => 1])));
        $this->assertSame('gt10', trim($this->render($template, ['value' => 42])));
    }

    public function testUnexpectedTagRaisesSyntaxError(): void
    {
        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessageMatches('/Expected "case" or "default" in switch/');

        $template = "{% switch value %}{% foo %}{% endswitch %}";
        $this->render($template, ['value' => 1]);
    }

    public function testEmptyCaseExpressionRaisesSyntaxError(): void
    {
        $this->expectException(SyntaxError::class);

        $template = "{% switch value %}{% case %}{% endswitch %}";
        $this->render($template, ['value' => 1]);
    }

    public function testNestedSwitchRestoresContext(): void
    {
        $template = <<<'TWIG'
            {% switch outer %}
            {% case 1 %}
                {% switch inner %}
                {% case 2 %}inner2{% default %}innerOther{% endswitch %}
                after_inner={{ __switch_value|default('undef') }}
            {% default %}outerOther{% endswitch %}
            after_outer={{ __switch_value|default('undef') }}
        TWIG;

        $rendered = trim(preg_replace('/\s+/', ' ', $this->render($template, ['outer' => 1, 'inner' => 2])));
        $this->assertSame('inner2 after_inner=1 after_outer=undef', $rendered);
    }

    private function render(string $template, array $context): string
    {
        $this->loader->setTemplate('tpl', $template);
        return $this->twig->render('tpl', $context);
    }
}
