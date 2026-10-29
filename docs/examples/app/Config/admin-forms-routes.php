<?php

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->get('forms/(:num)', 'Forms::show/$1');
$routes->post('forms/(:num)', 'Forms::submit/$1');

$routes->group('admin', static function ($routes): void {
    $routes->get('forms/(:num)/edit', 'Admin\FormEditor::edit/$1');
    $routes->post('forms/(:num)', 'Admin\FormEditor::save/$1');
    $routes->post('forms/check', 'Admin\FormEditor::check');
    $routes->get('forms/(:num)/answers', 'Admin\Submissions::index/$1');
});
