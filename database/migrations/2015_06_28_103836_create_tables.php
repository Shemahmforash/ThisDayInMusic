<?php

use Illuminate\Database\Migrations\Migration;

class CreateTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('artists', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('spotify_id');
            $table->softDeletes();
        });

        Schema::create('tracks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('spotify_id');
            $table->integer('artist_id')->unsigned()->nullable();
            $table->softDeletes();

            $table->foreign('artist_id')->references('id')->on('artists')
                        ->onDelete('restrict')
                        ->onUpdate('restrict');
        });

        Schema::create('events', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->date('date');
            $table->text('description');
            $table->string('type');
            $table->text('source');
            $table->boolean('tweeted');
            $table->integer('track_id')->unsigned()->nullable();
            $table->integer('artist_id')->unsigned()->nullable();

            $table->foreign('artist_id')->references('id')->on('artists')
                        ->onDelete('restrict')
                        ->onUpdate('restrict');
            $table->foreign('track_id')->references('id')->on('tracks')
                        ->onDelete('restrict')
                        ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //

        Schema::table('tracks', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropForeign('tracks_artist_id_foreign');
        });

        Schema::table('events', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->dropForeign('events_artist_id_foreign');
            $table->dropForeign('events_track_id_foreign');
        });

        Schema::drop('tracks');

        Schema::drop('artists');

        Schema::drop('events');
    }
}
