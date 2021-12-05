<?php

namespace App\Http\Controllers;

use App\Traits\ModelsIteration;

class MarkerIndexingController extends Controller
{
    use ModelsIteration;

    const code = 'marker_indexing';

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
        $limit = 5;
        $i = $this->cron_stats['i'];
        $this->cron_stats['i']++;
        $models = $this->getModels(app_path('Models/Websites'));
        $modelTurn = $i % count($models);

        $modelName = $models[$modelTurn];
        $model = new $modelName;
        if (!isset($this->cron_stats[$modelName])) {
            $page = 1;
        } else {
            $page = $this->cron_stats[$modelName];
        }

        $model->indexStartingFromPage($page, $limit);

        $this->cron_stats[$modelName] = $page + $limit;

        file_put_contents(storage_path('app/'.self::code.'.json'), json_encode($this->cron_stats));
    }
}
