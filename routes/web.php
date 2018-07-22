<?php

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

use function Swagger\scan;

$router->group(['middleware' => ['auth']], function () use ($router) {
    $router->get('/api/labs', ['uses' => 'LabController@index', 'as' => 'labs.index']);
    $router->post('/api/labs', ['uses' => 'LabController@store', 'as' => 'labs.store']);
    $router->patch('/api/labs/{labId}', ['uses' => 'LabController@update', 'as' => 'labs.update']);
    $router->delete('/api/labs/{labId}', ['uses' => 'LabController@destroy', 'as' => 'labs.destroy']);
});
$router->get('/api/labs/{labId}', ['uses' => 'LabController@show', 'as' => 'labs.show']);

$router->get('/courses/enroll', 'LabController@enroll');
$router->get('/courses/{courseId}/sessions/{sessionId}/attend', 'SessionController@attend');


$router->get('/api/users/{userId}', ['uses' => 'SessionController@index', 'as' => 'users.show']);

$router->get('/api/auth/signup', 'AuthController@signup');
$router->post('/api/auth/signin', 'AuthController@signIn');

$router->get('/docs', function () {
//    $openapi = scan(__DIR__ . '/../vendor/shippinno/learn/src/Infrastructure/Ui/Api/Api');
    $openapi = scan(__DIR__ . '/../app/Http');
    header('Content-Type: application/json');
    echo $openapi;
});