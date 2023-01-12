<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDriverNumberToGatePassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gate_pass', function (Blueprint $table) {
            $table->string('vibhag_name')->nullable()->after('id');
            $table->string('driver_mobile_number')->nullable()->after('driver_name');
            $table->string('deliever_to')->nullable()->after('department_from');
            $table->string('sign_of_gate_pass_approval')->nullable()->after('department_from');
            $table->string('sign_of_secutiry_person')->nullable()->after('department_from');
            $table->date('date')->nullable()->after('deliever_to');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gate_pass', function (Blueprint $table) {
            //
        });
    }
}
