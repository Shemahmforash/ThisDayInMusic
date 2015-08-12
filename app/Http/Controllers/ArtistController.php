<?php

namespace ThisDayInMusic\Http\Controllers;

use Illuminate\Http\Request;

class ArtistController extends Controller
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
            \ThisDayInMusic\Artist::class,
            \ThisDayInMusic\Http\Transformers\ArtistTransformer::class
        );
    }

    public function findByName($name)
    {
        if (!$name) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
        }

        $artist = \ThisDayInMusic\Artist::where('name', '=', $name)->first();

        if (!$artist) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Artist $name not found");
        }

        return $this->response->item($artist, new \ThisDayInMusic\Http\Transformers\ArtistTransformer);

    }
}
