<?php

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    echo 'Warning: Infection may only be invoked from a command line', PHP_EOL;
}

require __DIR__ . '/vendor/infection/infection/app/bootstrap.php';

use Infection\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

function prepareArgv()
{
    $argv = $_SERVER['argv'];

    $found = false;

    while (next($argv)) {
        $value = current($argv);
        if (!$value || '-' !== $value[0]) {
            $found = true;
        }
    }

    if (!$found) {
        array_splice($argv, 1, 0,'run');
    }

    return $argv;
}

$input = new ArgvInput(prepareArgv());

$application = new Application($container);
$application->run($input);