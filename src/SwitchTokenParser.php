<?php

declare(strict_types=1);

namespace Felds\TwigExtra;

use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenStream;
use Twig\TokenParser\AbstractTokenParser;

class SwitchTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $parser = $this->parser;
        $stream = $parser->getStream();

        $expression = $parser->parseExpression();
        $stream->expect(Token::BLOCK_END_TYPE);

        $cases = [];
        $default = null;

        while (true) {
            $current = $stream->getCurrent();

            if ($current->test(Token::NAME_TYPE, 'endswitch')) {
                break;
            }

            if ($current->test(Token::TEXT_TYPE) || $current->test(Token::BLOCK_START_TYPE)) {
                $stream->next();
                continue;
            }

            if (!$current->test(Token::NAME_TYPE, ['case', 'default'])) {
                throw new SyntaxError('Expected "case" or "default" in switch.', $current->getLine(), $stream->getSourceContext());
            }

            if ($current->getValue() === 'default') {
                $stream->next();
                $stream->expect(Token::BLOCK_END_TYPE);
                $default = $parser->subparse(fn(Token $token) => $token->test(['case', 'default', 'endswitch']));
                continue;
            }

            $cases[] = $this->parseCase();
        }

        $stream->next();
        $stream->expect(Token::BLOCK_END_TYPE);

        return new SwitchNode($expression, $cases, $default, $lineno);
    }

    private function parseCase(): array
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $stream->next();
        $line = $stream->getCurrent()->getLine();

        $comparisonExpression = $this->buildComparisonExpression($stream, $parser, $line);

        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $parser->subparse(fn(Token $token) => $token->test(['case', 'default', 'endswitch']));

        return [
            'comparison' => $comparisonExpression,
            'body' => $body,
        ];
    }

    private function buildComparisonExpression(TokenStream $stream, Parser $parser, int $line): Node
    {
        $comparisonTokens = [
            new Token(Token::VAR_START_TYPE, '', $line),
            new Token(Token::NAME_TYPE, '__switch_value', $line),
            $stream->test(Token::OPERATOR_TYPE) ? $stream->next() : new Token(Token::OPERATOR_TYPE, '==', $line),
        ];

        while (!$stream->test(Token::BLOCK_END_TYPE)) {
            $comparisonTokens[] = $stream->next();
        }

        $comparisonTokens[] = new Token(Token::VAR_END_TYPE, '', $line);
        $comparisonTokens[] = new Token(Token::EOF_TYPE, '', $line);

        $module = (new Parser($parser->getEnvironment()))->parse(new TokenStream($comparisonTokens, $stream->getSourceContext()));
        foreach ($module->getNode('body') as $node) {
            if ($node->hasNode('expr')) {
                return $node->getNode('expr');
            }
        }

        throw new SyntaxError('Unable to parse switch case expression.', $line, $stream->getSourceContext());
    }

    public function getTag(): string
    {
        return 'switch';
    }
}
