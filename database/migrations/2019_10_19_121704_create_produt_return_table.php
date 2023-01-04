<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProdutReturnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sell_return_lines', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('transaction_id')->unsigned();
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
            $table->integer('product_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('variation_id')->unsigned();
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->decimal('quantity',20,2);
            $table->decimal('unit_price', 20, 2)->comment("Sell price excluding tax")->nullable();
            $table->decimal('unit_price_inc_tax', 20, 2)->comment("Sell price including tax")->nullable();
            $table->decimal('item_tax', 20, 2)->comment("Tax for one quantity");
            $table->integer('tax_id')->unsigned()->nullable();
            $table->foreign('tax_id')->references('id')->on('tax_rates')->onDelete('cascade');
            $table->text('sell_return_note', 300)->nullable();
            $table->integer('parent_sell_line_id')->nullable();
            $table->integer('lot_no_line_id')->nullable();
            $table->enum('line_discount_type', ['fixed', 'percentage'])->nullable();
            $table->decimal('line_discount_amount', 20, 2)->default(0);
            $table->decimal('unit_price_before_discount', 20, 2)->default(0);
            $table->decimal('quantity_returned', 20, 4)->default(0);
            $table->integer('sub_unit_id')->nullable();
            $table->integer('discount_id')->nullable();
            $table->integer('res_service_staff_id');
            $table->string('res_line_order_status');
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
        Schema::dropIfExists('sell_return_lines');
    }
}
