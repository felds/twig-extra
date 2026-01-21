<?php

declare(strict_types=1);

namespace Felds\TwigExtra\Tests;

use Felds\TwigExtra\NullsafeExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class NullsafeOperatorTest extends TestCase
{
	private Environment $twig;
	private ArrayLoader $loader;

	protected function setUp(): void
	{
		$this->loader = new ArrayLoader();
		$this->twig = new Environment($this->loader, ['strict_variables' => true]);
		$this->twig->addExtension(new NullsafeExtension());
	}

	public function testDateFormatting(): void
	{
		$tpl = '{{ date?.format("d/m/Y") ?? "no date" }}';
		$this->assertSame('01/02/2013', $this->render($tpl, ['date' => new \DateTimeImmutable("2013-02-01")]));
		$this->assertSame('no date', $this->render($tpl, ['date' => null]));
	}

	public function testReturnsValueWhenPresent(): void
	{
		$this->assertSame('Ada', $this->render('{{ user?.name }}', ['user' => ['name' => 'Ada']]));

		$user = new class {
			public string $name = 'Ada';
		};

		$this->assertSame('Ada', $this->render('{{ user?.name }}', ['user' => $user]));
	}

	public function testGracefullyHandlesNullOrMissing(): void
	{
		$this->assertSame('', $this->render('{{ user?.name }}', ['user' => null]));
		$this->assertSame('', $this->render('{{ profile?.name }}', []));
	}

	public function testShortCircuitsAcrossChain(): void
	{
		$template = '{{ post?.author?.getName() }}';
		$this->assertSame('', $this->render($template, ['post' => ['author' => null]]));

		$author = new class {
			public function getName(): string
			{
				return 'Grace';
			}
		};

		$this->assertSame('Grace', $this->render($template, ['post' => ['author' => $author]]));
	}

	public function testEvaluatesLeftSideOnce(): void
	{
		$calls = 0;
		$this->twig->addFunction(new TwigFunction('makeUser', function () use (&$calls) {
			$calls++;
			return null;
		}));

		$this->render('{{ makeUser()?.name }}', []);

		$this->assertSame(1, $calls);
	}

	private function render(string $template, array $context): string
	{
		$this->loader->setTemplate('tpl', $template);
		return $this->twig->render('tpl', $context);
	}
}
