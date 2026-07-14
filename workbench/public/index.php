<?php

declare(strict_types=1);

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Orchestra\Testbench\Foundation\Application;
use Orchestra\Testbench\Foundation\Config;
use Orchestra\Testbench\Workbench\Workbench;

define('LARAVEL_START', microtime(true));

$workingPath = realpath(__DIR__.'/../..');

chdir($workingPath);

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require $workingPath.'/vendor/autoload.php';

if (! defined('TESTBENCH_WORKING_PATH')) {
    define('TESTBENCH_WORKING_PATH', $workingPath);
}

$config = Config::loadFromYaml(workingPath: $workingPath, filename: 'testbench.yaml');

$app = Application::create(
    basePath: $workingPath.'/'.$config['laravel'],
    options: ['load_environment_variables' => is_file($workingPath.'/'.$config['laravel'].'/.env')],
    resolvingCallback: static function ($app) use ($config): void {
        Workbench::startWithProviders($app, $config);
        Workbench::discoverRoutes($app, $config);
    },
);

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
