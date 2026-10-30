<?php declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Dashboard::index');
$routes->get('activites', 'Dashboard::activities');

// Routes Strava
$routes->get('strava/connect', 'Strava::connect');
$routes->get('strava/callback', 'Strava::callback');
$routes->post('strava/sync', 'Strava::sync');

// Routes Musculation
$routes->get('strength/form', 'Strength::form'); 
$routes->post('strength/save', 'Strength::save'); 

// On utilise (:any) pour être sûr de capturer l'ID alphanumérique de Strava si besoin
$routes->get('workout/(:any)', 'Strength::workout/$1');
$routes->get('exercise/(:num)', 'Strength::exercise/$1');