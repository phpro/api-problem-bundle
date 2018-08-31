[![Travis](https://img.shields.io/travis/phpro/api-problem-bundle/master.svg)](http://travis-ci.org/phpro/api-problem-bundle)
[![Installs](https://img.shields.io/packagist/dt/phpro/api-problem-bundle.svg)](https://packagist.org/packages/phpro/api-problem-bundle/stats)
[![Packagist](https://img.shields.io/packagist/v/phpro/api-problem-bundle.svg)](https://packagist.org/packages/phpro/api-problem-bundle)


# Api Problem Bundle

This package provides a [RFC7807](https://tools.ietf.org/html/rfc7807) Problem details exception listener for Symfony.
Internal, this package uses the models provided by `phpro/api-problem`](https://www.github.com/phpro/api-problem).
When an `ApiProblemException` is triggered, this bundle will return the correct response.


## Installation

```sh
composer require phpro/api-problem-bundle
```

```php
// config/bundles.php

return [
    // ...
    Phpro\ApiProblemBundle\ApiProblemBundle::class => ['all' => true],
];
```

## Supported response formats

- application/problem+json


## How it works

```
Use Phpro\ApiProblem\Exception\ApiProblemException
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

SomeController {

    /**
     * @Route('/some-route', defaults={"_format" = "json"})
     */
    someAction() {
        throw new ApiProblemException(
            new HttpApiProblem('400', 'It aint all bad ...')
        );
    }
}
```

When the controller is marked as a "json" format or the request `Content-Type` is `*/json`, this bundle kicks in.
It will transform the exception to following response:

Headers:
```
Content-Type: application/problem+json
```

Body:
```json
{
    "status": 400,
    "type": "http:\/\/www.w3.org\/Protocols\/rfc2616\/rfc2616-sec10.html",
    "title": "Bad Request",
    "detail": "It ain't all bad ..."
}
```

## Adding custom exception transformations

Currently, we automatically transform exceptions from following packages to an ApiProblem instance:

- phpro/api-problem
- symfony/http-kernel
- symfony/security

Besides that, all other errors are transformed to a basic `ExceptionApiProblem` instance.

If you want to add custom transformations, you can implement the `ExceptionTransformerInterface`
 and register it in the symfony container with the `phpro.api_problem.exception_transformer` tag.
 
```php
use Phpro\ApiProblemBundle\Transformer\ExceptionTransformerInterface;

class MyTransformer implements ExceptionTransformerInterface
{
    public function transform(\Throwable $exception): ApiProblemInterface
    {
        return new MyApiProblem($exception);
    }

    public function accepts(\Throwable $exception): bool
    {
        return $exception instanceof MyException;
    }
}
```

## About

### Submitting bugs and feature requests

Bugs and feature request are tracked on [GitHub](https://github.com/phpro/api-problem-bundle/issues).
Please take a look at our rules before [contributing your code](CONTRIBUTING).

### License

api-problem-bundle is licensed under the [MIT License](LICENSE).
