<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->post('api/appointments', 'Api\Appointments::create');
$routes->patch('api/appointments/(:num)', 'Api\Appointments::update/$1');
