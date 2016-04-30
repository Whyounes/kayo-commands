#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use KAYO\Commands\NamespaceVendorCommand;
use KAYO\Commands\AppNameCommand;
use KAYO\Commands\TranslateCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new NamespaceVendorCommand());
$application->add(new AppNameCommand());
$application->add(new TranslateCommand());

$application->run();
