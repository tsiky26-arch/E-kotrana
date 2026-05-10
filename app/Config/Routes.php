<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'AuthController::login');
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('logout', 'AuthController::logout');

$routes->get('inscription', 'AuthController::registerUser');
$routes->post('inscription', 'AuthController::storeRegisterUser');
$routes->get('inscription/sante', 'AuthController::registerHealth');
$routes->post('inscription/sante', 'AuthController::storeRegisterHealth');
$routes->get('ajax/imc', 'AuthController::imcPreview');

$routes->get('user', 'UserController::user');
$routes->post('user/objectif', 'UserController::saveObjective');
$routes->post('user/gold', 'UserController::buyGold');
$routes->post('user/portefeuille', 'UserController::useWalletCode');
$routes->get('user/export', 'UserController::exportProgram');

$routes->get('admin', 'AuthController::adminLogin');
$routes->post('admin/login', 'AuthController::attemptAdminLogin');
$routes->get('admin/dashboard', 'AdminController::adminDashboard');
$routes->post('admin/regimes', 'AdminController::storeRegime');
$routes->post('admin/regimes/(:num)', 'AdminController::updateRegime/$1');
$routes->post('admin/regimes/(:num)/delete', 'AdminController::deleteRegime/$1');
$routes->post('admin/activites', 'AdminController::storeActivity');
$routes->post('admin/activites/(:num)', 'AdminController::updateActivity/$1');
$routes->post('admin/activites/(:num)/delete', 'AdminController::deleteActivity/$1');
$routes->post('admin/codes', 'AdminController::storeWalletCode');
$routes->post('admin/parametres', 'AdminController::updateParametres');
