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

        $api->get('events', '\ThisDayInMusic\Http\Controllers\EventController@index');
        $api->get('artists', '\ThisDayInMusic\Http\Controllers\ArtistController@index');
        $api->get('tracks', '\ThisDayInMusic\Http\Controllers\TrackController@index');
        $api->get('playlists', '\ThisDayInMusic\Http\Controllers\PlaylistController@index');

        $api->get('artists/{name}', '\ThisDayInMusic\Http\Controllers\ArtistController@findByName');

        // $api->get('admin', ['protected' => true, function () {
        //     // This route requires authentication.

        //     $user = app('Dingo\Api\Auth\Auth')->user();

        //     return $user;
        // }]);
    });
});

Route::post('authenticate', 'AuthenticateController@authenticate');

Route::get('/', function () {
    return view('welcome');
});
