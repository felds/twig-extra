<?php

declare(strict_types=1);

namespace Felds\TwigExtra;

use Twig\Compiler;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\SupportDefinedTestDeprecationTrait;
use Twig\Node\Expression\SupportDefinedTestInterface;
use Twig\Node\Expression\SupportDefinedTestTrait;

class NullsafeGetAttrExpression extends AbstractExpression implements SupportDefinedTestInterface
{
    use SupportDefinedTestTrait;
    use SupportDefinedTestDeprecationTrait;

    public function __construct(AbstractExpression $node, AbstractExpression $attribute, ?AbstractExpression $arguments, string $type, int $lineno)
    {
        $nodes = ['node' => $node, 'attribute' => $attribute];
        if (null !== $arguments) {
            $nodes['arguments'] = $arguments;
        }

        if ($arguments && !$arguments instanceof ArrayExpression) {
            trigger_deprecation('felds/twig-extra', '1.0', sprintf('Not passing a %s as arguments is deprecated.', ArrayExpression::class));
        }

        parent::__construct($nodes, ['type' => $type, 'ignore_strict_check' => false], $lineno);
    }

    public function enableDefinedTest(): void
    {
        $this->definedTest = true;
        $this->changeIgnoreStrictCheck($this);
    }

    public function compile(Compiler $compiler): void
    {
        $env = $compiler->getEnvironment();
        $sandboxed = $env->hasExtension(SandboxExtension::class);

        $node = $this->getNode('node');
        if ($node->hasAttribute('ignore_strict_check')) {
            $node->setAttribute('ignore_strict_check', true);
        }

        $temp = $compiler->getVarName();
        $compiler->addDebugInfo($this);
        $compiler->raw('(null === ($'.$temp.' = ');
        $compiler->subcompile($this->getNode('node'));
        $compiler->raw(') ? '.($this->definedTest ? 'false' : 'null').' : ');
        $compiler->raw('CoreExtension::getAttribute($this->env, $this->source, $'.$temp.', ');
        $compiler->subcompile($this->getNode('attribute'));

        if ($this->hasNode('arguments')) {
            $compiler->raw(', ')->subcompile($this->getNode('arguments'));
        } else {
            $compiler->raw(', []');
        }

        $compiler->raw(', ');
        $compiler->repr($this->getAttribute('type'));
        $compiler->raw(', ');
        $compiler->repr($this->definedTest);
        $compiler->raw(', ');
        $compiler->repr($this->getAttribute('ignore_strict_check'));
        $compiler->raw(', ');
        $compiler->repr($sandboxed);
        $compiler->raw(', ');
        $compiler->repr($this->getTemplateLine());
        $compiler->raw('))');
    }

    private function changeIgnoreStrictCheck(self $node): void
    {
        $node->setAttribute('ignore_strict_check', true);

        $inner = $node->getNode('node');
        if ($inner instanceof self) {
            $this->changeIgnoreStrictCheck($inner);
        }
    }
}
