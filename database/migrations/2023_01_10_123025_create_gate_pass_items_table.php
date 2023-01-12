<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGatePassItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gate_pass_items_quantity', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('gate_pass_id')->unsigned()->index()->nullable();
            $table->foreign('gate_pass_id')->references('id')->on('gate_pass')->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('qty')->nullable();
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
        Schema::dropIfExists('gate_pass_items');
    }
}
