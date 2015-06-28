<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlaylistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('playlists', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->date('date');
            $table->softDeletes();
        });

        Schema::create('playlist_track', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('track_id')->unsigned();
            $table->integer('playlist_id')->unsigned();

            $table->foreign('playlist_id')->references('id')->on('playlists')
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

        Schema::table('playlist_track', function (Blueprint $table) {
            $table->dropForeign('playlist_track_playlist_id_foreign');
            $table->dropForeign('playlist_track_track_id_foreign');
        });

        Schema::drop('playlist_track');
        Schema::drop('playlists');
    }
}
