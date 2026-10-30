<?php declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

// @var RouteCollection $routes
$routes->get('/', 'Dashboard::index');
$routes->get('activites', 'Dashboard::activities');

// Routes Strava (remplacent COROS)
$routes->get('strava/connect', 'Strava::connect');
$routes->get('strava/callback', 'Strava::callback');

// Routes Musculation
$routes->get('workout/(:num)', 'Strength::workout/$1');
$routes->get('exercise/(:num)', 'Strength::exercise/$1');