# felds/twig-extra

Custom Twig extras, currently providing a `switch` tag for Twig 3.22+ and PHP 8.4+.

## Installation

```bash
composer require felds/twig-extra
```

## Usage

Register the extension with Twig:

```php
$twig->addExtension(new \Felds\TwigExtra\SwitchExtension());
```

In Symfony, you can let autoconfigure pick it up by registering the service:

```yaml
# services.yaml
Felds\TwigExtra\SwitchExtension: ~
```

Then use the tag:

```twig
{% switch value %}
{% case 1 %} exactly 1
{% case == 2 %} exactly 2
{% case > 1 %} More than 1
{% default %} Other
{% endswitch %}
```

Operators: all Twig comparison operators are supported (==, !=, >, <, >=, <=, `is same as`, `in`, `not in`, `matches`, `starts with`, `ends with`, etc.). The expression on each `case` is parsed by Twig itself.

## Tests

```bash
composer install
./vendor/bin/phpunit tests
```

## License

MIT
