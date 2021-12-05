<?php

namespace App\Http\Controllers;

use App\Models\Marker;
use App\Traits\ModelsIteration;
use Illuminate\Http\Request;

class MarkerController extends Controller
{
    use ModelsIteration;

    public function index()
    {
        return response()->json(Marker::all());
    }

    public function create()
    {
        $path = app_path() . "/Models/Websites";

        foreach ($this->getModels($path) as $model) {
            $model = new $model;
            $model->parse();
        }
    }
}
