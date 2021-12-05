<?php

use Illuminate\Support\Facades\Route;

function vdd(...$args)
{
    foreach ($args as $arg)
    {
        echo '<pre>';
        print_r($arg);
        echo '</pre>';
    }
    die();
}
function vd(...$args)
{
    foreach ($args as $arg)
    {
        echo '<pre>';
        print_r($arg);
        echo '</pre>';
    }
}

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomepageController@index');
Route::resource('markers', 'MarkerController');
Route::get('marker_indexing', 'MarkerIndexingController@index');
Route::get('marker_importing', 'MarkerImportingController@index');

Route::get('test/indexing/{m}', function ($m) {
    $model = 'App\\Models\\Websites\\'.$m;
    $model = new $model;
    $model->indexStartingFromPage(1, 5);
});

Route::get('test/importing/{m}', function ($m) {
    $model = 'App\\Models\\Websites\\'.$m;
    $model = new $model;
    $model->importMarkers(10);
});
