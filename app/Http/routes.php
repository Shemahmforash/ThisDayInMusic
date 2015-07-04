<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

$api = app('api.router');

$api->version(['version' => 'v1'], function ($api) {
    $api->group(['prefix' => 'api'], function ($api) {

        $api->get('users', function () {
            return ['users' => 'all'];
        });

        $api->get('admin', ['protected' => true, function () {
            // This route requires authentication.

            $user = app('Dingo\Api\Auth\Auth')->user();

            return $user;

            // return ['admin' => 'all'];
        }]);
    });
});

Route::post('authenticate', 'AuthenticateController@authenticate');

Route::get('/', function () {
    return view('welcome');
});
