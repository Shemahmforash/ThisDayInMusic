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
        factory(\ThisDayInMusic\Artist::class, 30)->create();

        factory(\ThisDayInMusic\Track::class, 60)->create()->each(function ($track) {
            $track->artist()->associate(factory(\ThisDayInMusic\Artist::class)->create());
        });
    }
}
