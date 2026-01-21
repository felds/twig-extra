# felds/twig-extra

Custom Twig extras for Twig 3.22+ and PHP 8.4+:

- `switch` tag
- `?.` nullsafe operator

## Installation

```bash
composer require felds/twig-extra
```

## Usage

Register the desired extensions with Twig:

```php
$twig->addExtension(new \Felds\TwigExtra\SwitchExtension());
$twig->addExtension(new \Felds\TwigExtra\NullsafeExtension());
```

In Symfony, you can let autoconfigure pick it up by registering the services:

```yaml
# services.yaml
Felds\TwigExtra\SwitchExtension: ~
Felds\TwigExtra\NullsafeExtension: ~
```

### Switch tag (`{% switch %}`)

Match a value against multiple comparisons, picking the first `case` that evaluates truthy:

```twig
{% switch value %}
{% case 1 %} exactly 1
{% case == 2 %} exactly 2
{% case > 1 %} More than 1
{% default %} Other
{% endswitch %}
```

- Supports all Twig comparison operators (==, !=, >, <, >=, <=, `is same as`, `in`, `not in`, `matches`, `starts with`, `ends with`, etc.).
- Cases are evaluated top-down; the first hit wins, otherwise `default` runs when present.
- The switch value is stored internally and the original context is restored after the block.

### Nullsafe operator (`?.`)

Access attributes and call methods without throwing when the left side is null or missing:

```twig
{{ user?.name }}
{{ post?.author?.getName() }}
{{ date?.format('Y-m-d') ?? 'no date' }}
```

- If the base value is null or undefined, the chain short-circuits to null.
- Works with arrays, objects, property fetches, and method calls.
- Respects strict variables (nullsafe short-circuits instead of raising).

## Tests

```bash
composer install
./vendor/bin/phpunit tests
```

## License

MIT

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
