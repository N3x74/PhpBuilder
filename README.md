# PhpBuilder

PhpBuilder is a simple and extensible PHP class to generate cURL code snippets dynamically based on the HTTP method, headers, and payload type.

## Features

- Supports all major HTTP methods (`GET`, `POST`, `PUT`, `DELETE`, `HEAD`, `OPTIONS`, `PATCH`, `CUSTOM`)
- Supports multiple payload types (`JSON`, `URL-ENCODE`, `TEXT`, `BINARY`, `XML`, `HTML`, `GRAPHQL`, etc.)
- Validates URLs and methods
- Generates clean, ready-to-use PHP cURL code
- Supports custom headers and timeouts

## Installation

Install via Composer:

```bash
composer require n3x74/php-builder
````

Or manually include the file if not using Composer.

## Usage

```php
use N3x74\PhpBuilder;

$builder = new PhpBuilder('https://example.com/api', 'POST');

$builder->setPayloads('JSON', [
    'username' => 'user',
    'password' => 'secret'
]);

$builder->setHeaders([
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer your_token'
]);

$builder->setTimeout(30, 10);

$code = $builder->fetchCode();
echo $code;
```

## Payload Types

| Method  | Supported Payload Types                                                           |
| ------- | --------------------------------------------------------------------------------- |
| GET     | NONE, URL-ENCODE                                                                  |
| POST    | NONE, URL-ENCODE, JSON, XML, MULTIPART, TEXT, BINARY, CUSTOM, GRAPHQL, YAML, HTML |
| PUT     | NONE, URL-ENCODE, JSON, XML, TEXT, BINARY, CUSTOM, GRAPHQL, YAML, HTML            |
| DELETE  | NONE, URL-ENCODE, JSON, XML, TEXT, BINARY, CUSTOM, GRAPHQL, YAML, HTML            |
| HEAD    | NONE                                                                              |
| OPTIONS | NONE, XML, JSON                                                                   |
| PATCH   | NONE, URL-ENCODE, JSON, YAML                                                      |
| CUSTOM  | OPTIONAL                                                                          |

## License

This project is open-sourced under the [MIT license](LICENSE).

---

## Links

- 🧠 Wiki API: [https://nestcode.org/](https://nestcode.org/)
- 📦 Composer: [https://packagist.org/packages/n3x74/php-builder](https://packagist.org/packages/n3x74/php-builder)
- 🧑‍💻 Author: [@N3x74](https://github.com/N3x74)
- ☁️ telegram: [@N3x74](https://t.me/N3x74)