<?php

namespace ThisDayInMusic\Http\Controllers;

use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        //
        return parent::listing(
            $request,
            \ThisDayInMusic\Playlist::class,
            \ThisDayInMusic\Http\Transformers\PlaylistTransformer::class
        );
    }
}
