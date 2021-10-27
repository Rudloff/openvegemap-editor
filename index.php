<?php

use OpenVegeMap\Editor\Controller\MainController;
use Slim\App;
use Slim\Views\Smarty as SmartyView;
use Slim\Views\SmartyPlugins;

require_once __DIR__ . '/vendor/autoload.php';

$configPath = __DIR__ . '/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
} else {
    die('config.php file does not exist.');
}

$app = new App();
$container = $app->getContainer();
$container['view'] = function ($c) {
    $view = new SmartyView(__DIR__ . '/templates/');
    $smartyPlugins = new SmartyPlugins($c['router'], $c['request']->getUri());
    $view->registerPlugin('function', 'path_for', [$smartyPlugins, 'pathFor']);
    $view->registerPlugin('function', 'base_url', [$smartyPlugins, 'baseUrl']);

    return $view;
};
$controller = new MainController($container);
$app->get('/{type}/{id}', [$controller, 'edit']);
$app->get('/', [$controller, 'search']);
$app->post('/{type}/{id}', [$controller, 'submit']);

try {
    $app->run();
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
