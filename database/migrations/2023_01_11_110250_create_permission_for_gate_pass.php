<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class CreatePermissionForGatePass extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Permission::create(['name' => 'gate_pass.view']);
        Permission::create(['name' => 'gate_pass.create']);
        Permission::create(['name' => 'gate_pass.update']);
        Permission::create(['name' => 'gate_pass.delete']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('permission_for_gate_pass');
    }
}
