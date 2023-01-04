<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTriggerForProductStock extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $trigger = "
        CREATE TRIGGER product_stock_list
    
        BEFORE UPDATE
    
        ON variation_location_details FOR EACH ROW
    
        IF NEW.qty_available<>OLD.qty_available
    
        THEN
    
        INSERT INTO product_stock_activity_log(product_id, old_stock, new_stock,location_id) values (OLD.product_variation_id, OLD.qty_available, NEW.qty_available,OLD.location_id); 
    
        END IF" ;
        DB::unprepared($trigger);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trigger_for_product_stock');
    }
}
