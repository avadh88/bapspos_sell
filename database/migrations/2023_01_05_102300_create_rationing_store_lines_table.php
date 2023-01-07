<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRationingStoreLinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rationing_store_lines', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('transaction_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('variation_id')->unsigned();
            $table->decimal('quantity',20,4);
            $table->decimal('pp_without_discount', 20, 2)->default(0)->comment('Purchase price before inline discounts');
            $table->decimal('discount_percent', 5, 2)->default(0)->comment('Inline discount percentage');
            $table->decimal('purchase_price', 20, 2);
            $table->decimal('purchase_price_inc_tax', 20, 2)->default(0);
            $table->decimal('item_tax', 20, 2)->comment("Tax for one quantity");
            $table->integer('tax_id')->unsigned()->nullable();
            $table->decimal('quantity_sold', 20, 4)->default(0.00)->comment("Quanity sold from this purchase line");
            $table->decimal('quantity_adjusted', 20, 4)->default(0.00)->comment("Quanity adjusted in stock adjustment from this purchase line");
            $table->decimal('quantity_returned', 20, 4)->default(0);
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('lot_number', 256)->nullable();
            $table->integer('sub_unit_id')->nullable();
            $table->timestamps();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->index('sub_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rationing_store_lines');
    }
}
