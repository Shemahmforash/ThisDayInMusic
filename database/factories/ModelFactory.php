<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|

$factory->define(\ThisDayInMusic\User::class, function ($faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => str_random(10),
        'remember_token' => str_random(10),
    ];
});
*/

$factory->define(\ThisDayInMusic\Artist::class, function ($faker) {
    return [
        'name' => $faker->sentence(2),
        'spotify_id' => $faker->randomNumber(),
    ];
});

$factory->define(\ThisDayInMusic\Track::class, function ($faker) {
    return [
        'name' => $faker->sentence(3) ,
        'spotify_id' => $faker->randomNumber(),
    ];
});

$factory->define(\ThisDayInMusic\Event::class, function ($faker) {
    return [
        'date' => $faker->date,
        'description' => $faker->sentence,
        'type' => $faker->word,
        'source' => $faker->url,
        'tweeted' => $faker->boolean,
    ];
});

$factory->define(\ThisDayInMusic\Playlist::class, function ($faker) {
    return [
        'name' => $faker->sentence(3),
        'spotify_id' => $faker->randomNumber,
    ];
});
