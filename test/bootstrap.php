<?php
/**
 * Bootstrapping
 *
 * @author Wenzel Pünter <wenzel@phelix.me>
*/

$files = array(
	'Exception.php',
	'Loader/Exception.php',
	'Events/EventsAwareInterface.php',
	'Text.php',
	'Loader.php'
);

foreach($files as $file) {
	require(__DIR__.'/../src/Phalcon/'.$file);
}

$loader = new \Phalcon\Loader();
$loader->registerNamespaces(array(
	'Phalcon' => __DIR__.'/../src/Phalcon/'
));
$loader->register();