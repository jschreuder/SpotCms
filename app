#!/usr/bin/php
<?php declare(strict_types = 1);

require __DIR__ . '/app_init.php';
/** @var  \Pimple\Container $container */

$application = new \Symfony\Component\Console\Application('SpotCms-CLI');

$application->add(new \Spot\Auth\Command\CreateUserCommand($container['service.authentication']));
$application->add(new \Spot\Auth\Command\DeleteUserCommand($container['repository.users']));

$application->run();
