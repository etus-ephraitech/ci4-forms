<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->get('appointments', 'Appointments::index');
$routes->get('appointments/new', 'Appointments::new');
$routes->post('appointments', 'Appointments::create');
$routes->get('appointments/(:num)/edit', 'Appointments::edit/$1');
$routes->patch('appointments/(:num)', 'Appointments::update/$1');
