<?php

namespace ThisDayInMusic\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Dingo\Api\Routing\Helpers;

abstract class Controller extends BaseController
{
    use DispatchesJobs, ValidatesRequests, Helpers;

    public function listing(
        \Dingo\Api\Http\Request $request,
        $model,
        $transformer
        )
    {
        $data = $model::paginate($request->input("per_page", config("pagination.per_page")));

        return $this->response->withPaginator(
            $data,
            new $transformer
        );
    }
}
