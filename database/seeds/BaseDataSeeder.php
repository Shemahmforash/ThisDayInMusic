<?php

use Illuminate\Database\Seeder;

class BaseDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //factory(\ThisDayInMusic\Artist::class, 30)->create();

        // factory(\ThisDayInMusic\Track::class, 60)->create()->each(function ($track) {
        //       $track->artist()->save(factory(\ThisDayInMusic\Artist::class)->create());
        // });

        // factory(\ThisDayInMusic\Event::class, 60)->create()->each(function ($event) {
        //       $event->artist()->associate(factory(\ThisDayInMusic\Artist::class)->create());
        // });

        factory(\ThisDayInMusic\Artist::class, 60)->create()->each(function ($artist) {
              $artist->tracks()->save(factory(\ThisDayInMusic\Track::class)->create());
              $artist->events()->save(factory(\ThisDayInMusic\Event::class)->create());
        });

        // factory(\ThisDayInMusic\User::class, 1)->create();
    }
}
