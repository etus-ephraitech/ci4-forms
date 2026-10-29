<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->get('contact', 'Contact::index');
$routes->post('contact', 'Contact::send');
