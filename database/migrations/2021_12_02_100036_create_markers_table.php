<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMarkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('markers', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('url');
            $table->tinyInteger('checked')->default(1);
            $table->integer('price')->index();
            $table->decimal('lat', 9, 7);
            $table->decimal('lon', 9, 7);
            $table->string('text_price');
            $table->tinyInteger('type')->default(1);
            $table->tinyInteger('currency')->default(1);
            $table->integer('usable_area')->nullable()->index();
            $table->tinyInteger('rooms')->nullable()->index();
            $table->tinyInteger('seller_type')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('markers');
    }
}
