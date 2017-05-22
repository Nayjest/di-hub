# DI-Hub (Dependency Injection Hub)
IoC container for PHP with hierarchy and links consistency maintaining mechanism

[![Build Status](https://travis-ci.org/Nayjest/di-hub.svg?branch=master)](https://travis-ci.org/Nayjest/di-hub)

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/2d3bd038-0411-4ba7-be25-82a823e90f76/big.png)](https://insight.sensiolabs.com/projects/2d3bd038-0411-4ba7-be25-82a823e90f76)

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Testing](#testing)
- [Contributing](#contributing)
- [Security](#security)
- [License](#license)

## Requirements

* PHP 5.5+ (HHVM & PHP7 are supported)

## Installation

The recommended way of installing this package is through [Composer](https://getcomposer.org).

Run following command from your project folder:

```bash
composer require nayjest/di-hub
```

## Usage

### Creating hub
Class Nayjest\DI\Hub represents IoC container. This class implements ContainerInterface from container-interop/container-interop package.

Hub can be instantiated without arguments or with array containing definitions.
```php
use Nayjest\DI\Hub;
# Empty hub
$hub = new Hub;

# Hub with definitions

$hub = new Hub([
  new Value('item1', $item1),
  new Value('item2', $item2),
  new Relation('item1', 'item2', $handler),
]);

```

### Definitions

There are several types of definitions that can be added to hub:
- Values
- Relations
- Items

Definitions should implement `Nayjest\DI\Definition\DefinitionInterface`.
This intarface don't contains any methods, it's used only to signalize that instances of target class defines data or relations inside container.

Definitions can be added to container(hub) in following ways:
1) Inject array of definition instances into hub constructor
2) Add definition instance to existing hub via `$hub->addDefinition(DefinitionInterface $definition)` 
3) Add array of definition instances to existing hub via `$hub->addDefinitions(DefinitionInterface[] $definitions)`
4) Create definitions via DefinitionBuilder: `$hub->builder()->define($id $source)`

#### Value Definitions
Instance of Nayjest\DI\Definition\Value represent single value in container that can be accessed by it's id.
Nayjest\DI\Definition\Value accepts two arguments: id and source.
source can contain value to store inside hub or callable that returns target value.

```php
# Add data directly to definition
$hub->addDefinition(new Value('item1', $data));

# Add data via callable
$hub->addDefinition(new Value('item2', function(){
   return $data;
}));
```
#### Relation Definitions

@todo

#### Item Definitions

Item is a combination of value & it's initial dependencies.
May be useful to store class instances that require DI in constructor.

@todo

### Hierarchy of hubs

@todo

## Testing

This package bundled with unit tests (PHPUnit).

1) Install nayjest/di-hub as stand-alone project

```bash
composer create-project nayjest/di-hub -s dev
```

2) Run tests from package folder

```bash
cd di-hub
composer test
```

Also it's possible to check code style (PSR-2):

```bash
composer code-style
```

## Contributing

Please see [Contributing Guidelines](contributing.md) and [Code of Conduct](code_of_conduct.md) for details.

## Security

If you discover any security related issues, please email mail@vitaliy.in instead of using the issue tracker.

## License

© 2016&mdash;2017 Vitalii Stepanenko

Licensed under the MIT License. 

Please see [License File](LICENSE) for more information.
