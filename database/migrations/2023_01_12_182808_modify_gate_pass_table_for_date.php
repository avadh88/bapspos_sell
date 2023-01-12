<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyGatePassTableForDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('table_name', function (Blueprint $table) {
        //     $table->dropColumn('column_name');          // Removes the old column

        //     // You need to set a ->default() or make this column ->nullable() or you'll
        //     // get errors since the data is empty now.
        //     $table->dateTime('column_name');            // Creates the new datetime
        // });
        DB::statement("ALTER TABLE gate_pass MODIFY COLUMN `date` datetime DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
