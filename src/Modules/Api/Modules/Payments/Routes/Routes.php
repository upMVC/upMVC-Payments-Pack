<?php

use App\Modules\Api\Modules\Payments\Controller;

/** @var \App\Etc\Router $router */
$router->addRoute('/api/payments/drivers', Controller::class, 'drivers', ['cors', 'jwt'], ['GET']);
$router->addRoute('/api/payments', Controller::class, 'create', ['cors', 'jwt'], ['POST']);
$router->addParamRoute('/api/payments/{id:int}', Controller::class, 'show', ['cors', 'jwt'], ['GET']);
$router->addParamRoute('/api/payments/{id:int}/status', Controller::class, 'updateStatus', ['cors', 'jwt'], ['PATCH']);
