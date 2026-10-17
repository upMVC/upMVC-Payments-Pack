<?php

use App\Modules\Api\Modules\Payments\Controller;

/** @var \App\Etc\Router $router */
$router->addRoute('/api/payments', Controller::class, 'create', ['cors', 'jwt'], ['POST']);
$router->addParamRoute('/api/payments/{id:int}', Controller::class, 'show', ['cors', 'jwt'], ['GET']);
