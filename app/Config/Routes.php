<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Users::login');
$routes->get('login', 'Users::login');
$routes->post('login', 'Users::attemptLogin');
$routes->get('logout', 'Users::logout');

$routes->get('inscription', 'Users::registerUser');
$routes->post('inscription', 'Users::storeRegisterUser');
$routes->get('inscription/sante', 'Users::registerHealth');
$routes->post('inscription/sante', 'Users::storeRegisterHealth');
$routes->get('ajax/imc', 'Users::imcPreview');

$routes->get('user', 'Users::user');
$routes->post('user/objectif', 'Users::saveObjective');
$routes->post('user/gold', 'Users::buyGold');
$routes->post('user/portefeuille', 'Users::useWalletCode');
$routes->get('user/export', 'Users::exportProgram');

$routes->get('admin', 'Users::adminLogin');
$routes->post('admin/login', 'Users::attemptAdminLogin');
$routes->get('admin/dashboard', 'Users::adminDashboard');
$routes->post('admin/regimes', 'Users::storeRegime');
$routes->post('admin/regimes/(:num)', 'Users::updateRegime/$1');
$routes->post('admin/regimes/(:num)/delete', 'Users::deleteRegime/$1');
$routes->post('admin/activites', 'Users::storeActivity');
$routes->post('admin/activites/(:num)', 'Users::updateActivity/$1');
$routes->post('admin/activites/(:num)/delete', 'Users::deleteActivity/$1');
$routes->post('admin/codes', 'Users::storeWalletCode');
$routes->post('admin/parametres', 'Users::updateParametres');
