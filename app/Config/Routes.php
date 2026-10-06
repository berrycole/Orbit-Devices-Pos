<?php
use CodeIgniter\Router\RouteCollection;
$routes->setAutoRoute(false);
$routes->get('login','Auth::login');
$routes->post('login','Auth::authenticate');
$routes->post('logout','Auth::logout');
$routes->get('health','Health::index');
$routes->group('', ['filter'=>'auth'], static function(RouteCollection $routes) {
    $routes->get('/','Dashboard::index');
    foreach (['products','customers','staff'] as $entity) {
        $routes->get($entity,'Catalog::index/'.$entity);
        $routes->get($entity.'/new','Catalog::form/'.$entity);
        $routes->get($entity.'/(:num)/edit','Catalog::form/'.$entity.'/$1');
        $routes->post($entity,'Catalog::save/'.$entity);
        $routes->post($entity.'/(:num)','Catalog::save/'.$entity.'/$1');
        $routes->post($entity.'/(:num)/delete','Catalog::delete/'.$entity.'/$1');
    }
    $routes->get('sales/new','Sales::create');
    $routes->post('sales','Sales::store');
    $routes->get('sales','Sales::index');
    $routes->get('media/([a-f0-9]{32}\.(?:jpg|png|webp))','Media::show/$1');
});
