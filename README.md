#phalcon-php

Phalcon-PHP is a free replacement for the [Phalcon Web Framework](https://github.com/phalcon/cphalcon), delivered as a set of PHP classes based on the [Phalcon Devtools](https://github.com/phalcon/phalcon-devtools). This project allows the usage of the Phalcon API in environments without the possibility to set up the phalcon extension (e.g. shared hosting) by providing a compatibility layer.

##Development

The current project **is not usable for any purpose** and still requires a lot of implementation. As the base for all developments, this project is using the `phalcon-devtools/ide/1.2.6` tree as a starting point.
In case you are interested in contributing, please note that - except of the original Phalcon files - the entire code should follow the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) coding standard. The entire code should be additionally documented by PHPDoc annotations.

##Documentation

It is possible to generate a low-level PHPDoc-based documentation of the entire code by using [PHP_UML](https://pear.php.net/manual/en/package.php.php-uml.command-line.php) or [phpDocumentator](http://www.phpdoc.org/). The repository contains a shell script, which generates the corresponding documentation if one or both of the tools are available.

##Branching

This project will in future use the original Phalcon project's braching system. As long as the version `1.2.6` is not working, all development progress is part of the `master` branch.

##License

To keep the compatibility with the framework, this legacy layer is licensed under the terms of the New BSD license.

##Usage

You can use this project as a fallback for the original framework.

###Native

Add the following code at the top of your bootstrap file:
```php
if(extension_loaded('Phalcon') === false) {
	$files = array('Exception.php', 'Loader/Exception.php', 'Events/EventsAwareInterface.php', 'Text.php', 'Loader.php');
	foreach($files as $file) {
		require(__DIR__.'/src/Phalcon/'.$file);
	}

	$loader = new \Phalcon\Loader();
	$loader->registerNamespaces(array(
		'Phalcon' => __DIR__.'/src/Phalcon/'
	));
	$loader->register();
}
```

###Composer

You can include the default autoloader of Composer, which is generated after the execution of `composer install`.