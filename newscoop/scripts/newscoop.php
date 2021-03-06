<?php

define('APPLICATION_ENV', 'cli');

require_once __DIR__ . '/../application.php';
$application->bootstrap();

// Retrieve Doctrine Entity Manager
$em = Zend_Registry::get('container')->getService('em');

// Console
$cli = new \Symfony\Component\Console\Application(
    'Newscoop Command Line Interface',
    \Newscoop\Version::VERSION
);

try {
    // Bootstrapping Console HelperSet
    $helperSet = array();

    if ($em !== null) {
        $helperSet['container'] = new \Newscoop\Tools\Console\Helper\ServiceContainerHelper($application->getBootstrap()->getResource('container'));
    }
} catch (\Exception $e) {
    $cli->renderException($e, new \Symfony\Component\Console\Output\ConsoleOutput());
}

$cli->setCatchExceptions(true);
$cli->setHelperSet(new \Symfony\Component\Console\Helper\HelperSet($helperSet));

$cli->addCommands(array(
    new \Newscoop\Tools\Console\Command\UpdateIngestCommand(),
    new \Newscoop\Tools\Console\Command\LogMaintenanceCommand(),
    new \Newscoop\Tools\Console\Command\SendStatsCommand(),
    new \Newscoop\Tools\Console\Command\UpdateImageStorageCommand(),
));

$cli->run();
