#!/usr/bin/env php
<?php

use Wedos\Commands;
use Symfony\Component\Console\Application;

$loader = require __DIR__ . '/vendor/autoload.php';
$loader->add('Wedos', __DIR__);

$application = new Application();
$application->add(new Commands\AuthCommand);
$application->add(new Commands\ListCommand);
$application->add(new Commands\SyncCommand);
$application->run();
