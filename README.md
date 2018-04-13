# Container7

[![Latest Version 1.2](https://img.shields.io/github/release/Moro4125/container7.svg?style=flat-square)](https://github.com/Moro4125/container7/releases)
[![Build Status](https://img.shields.io/travis/Moro4125/container7.svg?style=flat-square)](https://travis-ci.org/Moro4125/container7)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Container7 is a medium Dependency Injection Container for PHP-7.

This package is compliant with [PSR-1], [PSR-2], [PSR-4] and [PSR-11]. If you notice compliance oversights, please send a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
[PSR-11]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-11-container.md

Features:
 providers,
 singletons,
 factories,
 parameters,
 aliases,
 tags,
 configuration,
 serialization.

## Install

Via composer

    $ composer require moro/container7

### Requirements

* PHP 7.1

## Usage

Creating a container is a matter of creating a Container instance:

```php
<?php
use Moro\Container7\Container;

$container = new Container();
```

As many other dependency injection containers, Container7 manages two different kind of data: services and parameters. Services receives from container by their class or alias. Parameters stores in their service "parameters". 

```php
<?php
if ($container->has(Service::class)) {
    $service = $container->get(Service::class);
}

$value = $container->get('parameters')->get('key');
```

For service definition used Service Providers.

```php
<?php
$container->addProvider(SomeProvider::class);
```

Service Providers allow you to package code or configuration for packages that you reuse regularly.

### Service provider

Any Service Provider is simple PHP class. Services are defined by methods that return an instance of an object. You must define results of this methods as class or interface. And then you can receive service by that class or interface.

#### Singleton

```php
<?php
class SomeProvider {
    function someService(): Service {
        return new Service();
    }
}
```

#### Factory

If you want define factory - add variadic argument.

```php
<?php
class SomeProvider {
    function someService(...$arguments): Service {
        return new Service($arguments[0]);
    }
}
```

#### Dependencies

When your service require other, you can add this dependency to method arguments.

```php
<?php
class SomeProvider {
    function someService(ServiceBeta $beta): ServiceAlpha {
        return new ServiceAlpha($beta);
    }
}
```

Remember, that parameters is service too:

```php
<?php
use Moro\Container7\Parameters;

class SomeProvider {
    function someService(Parameters $parameters): Service {
        return new Service($parameters->get('key'));
    }
}
```

#### Modifying Services after Definition

In some cases you may want to modify a service definition after it has been defined. You can use method that receive service object and can return null.

```php
<?php
class SomeProvider {
    function extendService(Service $service) {
        $service->value = 'value'; // example
    }
}
```

If you need to replace the service instance use that definition:

```php
<?php
class SomeProvider {
    function extendService(Service $service): ?Service {
        return new Decorator($service);
    }
}
```

You can add dependencies here as in the definition of factories.

#### Parameters

Add default parameters.

```php
<?php
use Moro\Container7\Parameters;

class SomeProvider {
    function parameters(Parameters $parameters) {
        $parameters->set('key1', 'value1');
        $parameters->set('key2', '%key1%');
        $parameters->add('key0', ['new item']);
    }
    function someService(Parameters $parameters): Service {
        $service = new Service();
        $service->value = $parameters->get('key2');
        return $service;
    }
}
```

Set current values of parameters, that replace default values.

```php
<?php
use Moro\Container7\Container;
use Moro\Container7\Parameters;

$parameters = new Parameters(['key1' => 'value2']);
$container = new Container($parameters);
$container->addProvider(SomeProvider::class);
```

And use it.

```php
<?php
$service = $container->get(Service::class);
assert($service->value === 'value2');
```

#### Aliases

When you can not get service by class or interface, then you must define alias for class, interface or method.

```php
<?php
use Moro\Container7\Aliases;

class SomeProvider {
    function someService(): Service {
        return new Service();
    }
    function aliases(Aliases $aliases) {
        // Add alias for unique interface in provider
        $aliases->add('kernel', Service::class);
        // or you can use method name
        $aliases->add('kernel', 'someService');
    }
}
```

Now you can get service by alias.

```php
<?php
$service = $container->get('kernel');
```

#### Tags

You can group services by setting a common tag for them.

```php
<?php
use Moro\Container7\Tags;

class SomeProvider {
    function tags(Tags $tags) {
        // Add tag for unique interface in provider
        $tags->add('someTag', ServiceAlpha::class);
        $tags->add('someTag', ServiceBeta::class);
        // or you can use method name
        $tags->add('someTag', 'getServiceAlpha');
        $tags->add('someTag', 'getServiceBeta');
    }
}
```

And then get a collection of the services.

```php
<?php
$collection = $container->getCollection('someTag');
```

Collection implements Iterator interface and you can use it in foreach.

#### Manipulation with collection

```php
<?php
use Moro\Container7\Container;

$container = new Container();
$collection = $container->getCollection('A');
// Collection contains services with tag "A".
$collection = $collection->merge('B');
// Collection contains services with tags "A" or "B".
$collection = $collection->exclude('A');
// Collection contains services with tag "B" and without tag "A".
$collection = $collection->merge('B')->with('C');
// The collection contains services that are marked
// with "B" and "C" tags simultaneously.
```

### Configuration

Container7 support configuration files in JSON format. You can create provider from that files.

```php
<?php
use Moro\Container7\Container;
use Moro\Container7\Parameters;

$configuration = Parameters::fromFile('conf.json');
$container = new Container($configuration);
```

Configuration files can be nested.

```json
{
  "@extends": [
    "module1.json",
    "../module2.json"
  ]
}
```

#### Singleton

```json
{
  "container": {
    "singletons": [
      {
        "interface": "ServiceInterface",
        "class": "Service"
      }
    ]
  }
}
```

#### Factory

```json
{
  "container": {
    "factories": [
      {
        "interface": "ServiceInterface",
        "class": "Service"
      }
    ]
  }
}
```

#### Dependencies

```json
{
  "container": {
    "singletons": [
      {
        "interface": "ServiceInterface",
        "class": "ServiceAlpha",
        "args": [
          "@ServiceBeta"
        ],
        "properties": {
          "value": 1
        },
        "calls": [
          {
            "method": "add",
            "args": [
              "key",
              "value"
            ]
          }
        ]
      }
    ]
  }
}
```

#### Modifying Services after Definition

```json
{
  "container": {
    "extends": [
      {
        "target": "ServiceInterface",
        "calls": [
          {
            "method": "add",
            "args": [
              "key",
              "value"
            ]
          }
        ]
      }
    ]
  }
}
```

If you need to replace the service instance use that definition:

```json
{
  "container": {
    "extends": [
      {
        "target": "ServiceInterface",
        "class": "Decorator",
        "args": [
          "$target"
        ]
      }
    ]
  }
}
```

#### Parameters, Aliases and Tags

```json
{
  "container": {
    "parameters": {
      "key1": "value1",
      "key2": "%key1%"
    },
    "singletons": [
      {
        "aliases": ["kernel"],
        "tags": ["someTag"],
        "interface": "ServiceInterface",
        "class": "ServiceAlpha",
        "properties": {
          "value": "%key2%"
        }
      },
      {
        "tags": ["someTag"],
        "class": "ServiceBeta"
      },
      {
        "class": "Service",
        "args": [
          "$collections[someTag]"
        ]
      },
      {
        "class": "Service",
        "calls": [
          {
            "foreach": "$collections[someTag]",
            "method": "add",
            "args": ["$item"]
          }
        ]
      }
    ]
  }
}
```

### Dynamic services

```php
<?php
use Moro\Container7\Container;
use Moro\Container7\Provider;

class DynamicProvider {
    function boot(Container $container) {
        $configuration = [];
        // ... dynamic create of configuration array ...
        $container->addProvider(Provider::fromConfiguration(__METHOD__, $configuration));
    }
}
```

## License

The MIT License (MIT). Please see [License File](https://github.com/moro4125/container7/blob/master/LICENSE.md) for more information.
