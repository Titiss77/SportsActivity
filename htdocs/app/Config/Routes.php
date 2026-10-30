<?php declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Toutes nos routes sont protégées par le filtre "session" de Shield
$routes->group('', ['filter' => 'session'], function($routes) {
    
    $routes->get('/', 'Dashboard::index');
    $routes->get('activites', 'Dashboard::activities');
    
    // Strava
    $routes->get('strava/connect', 'Strava::connect');
    $routes->get('strava/callback', 'Strava::callback');
    $routes->post('strava/sync', 'Strava::sync');
    $routes->post('strava/disconnect', 'Strava::disconnect');
    
    // Musculation
    $routes->get('strength', 'Strength::index'); 
    $routes->get('strength/form', 'Strength::form'); 
    $routes->post('strength/save', 'Strength::save'); 
    
    // Utilisation de (:segment) pour accepter les ID alphanumériques de Strava ou les ID locaux de manière fiable
    $routes->get('workout/(:segment)', 'Strength::workout/$1');
    $routes->get('exercise/(:segment)', 'Strength::exercise/$1');
    
    // Profil
    $routes->get('profile', 'Profile::index');
    $routes->post('profile/updateGoals', 'Profile::updateGoals');
    $routes->post('profile/updateAccount', 'Profile::updateAccount');
});

service('auth')->routes($routes);