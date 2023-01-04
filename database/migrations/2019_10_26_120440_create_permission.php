<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('sellreturn.view', 'web', now(), now())");
        DB::statement("INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('sellreturn.create', 'web', now(), now())");
        DB::statement("INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('sellreturn.update', 'web', now(), now())");
        DB::statement("INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('sellreturn.delete', 'web', now(), now())");
        DB::statement("INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('custom_req.view', 'web', now(), now())");
        DB::statement("INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES ('custom_req.create', 'web', now(), now())");
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
