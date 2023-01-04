<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
class AddPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $data = [
            ['name' => 'sellreturn.view'],
            ['name' => 'sellreturn.create'],
            ['name' => 'sellreturn.update'],
            ['name' => 'sellreturn.delete'],
            ['name' => 'genralstore_report.departmentwisedemandreport'],
            ['name' => 'genralstore_report.totaldemandreport'],
            ['name' => 'genralstore_report.departmentwisependingreport'],
            ['name' => 'genralstore_report.totalpendingreport'],
            ['name' => 'genralstore_report.departmentwisesummaryreport'],
            ['name' => 'genralstore_report.overallsummaryreport'],
            ['name' => 'genralstore_report.overallproductsummaryreport'],
        ];

        $insert_data = [];
        $time_stamp = \Carbon::now()->toDateTimeString();
        foreach ($data as $d) {
            $d['guard_name'] = 'web';
            $d['created_at'] = $time_stamp;
            $insert_data[] = $d;
        }
        Permission::insert($insert_data);
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
