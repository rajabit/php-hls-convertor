<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
      $router->get('report', 'StatusController@index');
      $router->group(['prefix' => 'converter'], function () use ($router) {
            $router->post('/', 'ConverterController@store');
      });
});

$router->get('/{route:.*}/', 'IndexController@index');
