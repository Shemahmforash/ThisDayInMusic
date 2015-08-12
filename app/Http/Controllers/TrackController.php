<?php

namespace ThisDayInMusic\Http\Controllers;

use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        return parent::listing(
            $request,
            \ThisDayInMusic\Track::class,
            \ThisDayInMusic\Http\Transformers\TrackTransformer::class
        );
    }
}
