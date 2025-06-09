<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', [
    'as'   => 'home',
    'uses' => 'LlmsController@index',
]);

$router->get('/ping', function () {
    return response()->json(['pong' => true]);
});

$router->post('/generate', [
    'as'   => 'llms.generate',
    'uses' => 'LlmsController@generate',
]);


$router->get('{any:.*}', function () {
    return redirect('/');
});
