<?php

namespace App\Http\Controllers;

use App\Traits\ModelsIteration;

class MarkerImportingController extends Controller
{
    use ModelsIteration;

    const code = 'marker_importing';

    private $cron_stats = [];

    public function __construct()
    {
        if (file_exists(storage_path('app/'.self::code.'.json'))) {
            $this->cron_stats = (array) json_decode(file_get_contents(storage_path('app/'.self::code.'.json')));
        } else {
            $this->cron_stats = ['i' => 0];
        }
    }

    public function index()
    {
        $limit = 10;
        $i = $this->cron_stats['i'];
        $models = $this->getModels(app_path('Models/Websites'));
        $modelTurn = $i % count($models);

        $modelName = $models[$modelTurn];
        $model = new $modelName;

        $model->importMarkers($limit);
    }
}
