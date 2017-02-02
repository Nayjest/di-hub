# DI-Hub (Dependency Injection Hub)
IoC container for PHP with links consistency maintaining mechanism

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
