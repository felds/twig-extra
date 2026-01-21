<?php

declare(strict_types=1);

namespace Felds\TwigExtra;

use Twig\Compiler;
use Twig\Node\Node;

class SwitchNode extends Node
{
    public function __construct(Node $expression, array $cases, ?Node $default, int $lineno)
    {
        $nodes = ['expression' => $expression];
        if ($default !== null) {
            $nodes['default'] = $default;
        }

        parent::__construct($nodes, ['cases' => $cases], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        $uid = spl_object_id($this);
        $valVar = "__switch_value_$uid";
        $existsVar = "__switch_value_exists_$uid";
        $origVar = "__switch_value_original_$uid";

        $compiler->addDebugInfo($this);
        $compiler->write('$' . $valVar . ' = ');
        $compiler->subcompile($this->getNode('expression'));
        $compiler->raw(";\n");
        $compiler->write('$' . $existsVar . ' = array_key_exists("__switch_value", $context);' . "\n");
        $compiler->write('$' . $origVar . ' = $context["__switch_value"] ?? null;' . "\n");
        $compiler->write('$context["__switch_value"] = $' . $valVar . ';' . "\n");

        foreach ($this->getAttribute('cases') as $index => $case) {
            $compiler->write($index === 0 ? 'if (' : 'elseif (');
            $compiler->subcompile($case['comparison']);
            $compiler->raw(") {\n");
            $compiler->indent();
            $compiler->subcompile($case['body']);
            $compiler->outdent();
            $compiler->write("}\n");
        }

        if ($this->hasNode('default')) {
            $compiler->write("else {\n");
            $compiler->indent();
            $compiler->subcompile($this->getNode('default'));
            $compiler->outdent();
            $compiler->write("}\n");
        }

        $compiler->write('if ($' . $existsVar . ') {' . "\n");
        $compiler->indent();
        $compiler->write('$context["__switch_value"] = $' . $origVar . ';' . "\n");
        $compiler->outdent();
        $compiler->write("} else {\n");
        $compiler->indent();
        $compiler->write('unset($context["__switch_value"]);' . "\n");
        $compiler->outdent();
        $compiler->write("}\n");
    }
}
