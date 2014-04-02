#phalcon-php

Phalcon-PHP is a free replacement for the [Phalcon Web Framework](https://github.com/phalcon/cphalcon), delivered as a set of PHP classes based on the [Phalcon Devtools](https://github.com/phalcon/phalcon-devtools). This project allows the usage of the Phalcon API in environments without the ability to set up the phalcon extension (e.g. shared hosting).
This project tries to increase the usage of the Phalcon Web Framework by providing a compatibility layer for this environments.

##Development

The current project **is not usable for any purpose** and still requires a lot of implementation. As the base for all developments, this project is using the `phalcon-devtools/ide/1.2.6` tree as a starting point.
In case you are interested in contributing, please note that - except of the original Phalcon files - the entire code should follow the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) coding standard. The entire code should be additionally documented by PHPDoc annotations.

##Branching

This project will in future use the same braching system as the original Phalcon project. As long as the version `1.2.6` is not working, all development progresses are made in the `master` branch.

##License

To keep the compatibility with the framework, this legacy layer is licensed under the terms of the New BSD license.
