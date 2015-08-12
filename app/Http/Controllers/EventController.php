<?php

namespace ThisDayInMusic\Http\Controllers;

use Illuminate\Http\Request;

class EventController extends Controller
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
            \ThisDayInMusic\Event::class,
            \ThisDayInMusic\Http\Transformers\EventTransformer::class
        );
    }
}
