<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OverallSummaryProduct extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "
        CREATE PROCEDURE overall_summary_product(IN _locationId TEXT,IN _transType TEXT,IN _startDate TEXT,IN _endDate TEXT)
            BEGIN
                SET SESSION group_concat_max_len = (7 * 1024);

                SET @sql = NULL;

                SELECT GROUP_CONCAT(DISTINCT
                        CONCAT(
                        'SUM(CASE WHEN trans.contact_id = ', trans.contact_id,
                        ' THEN tsl.quantity END) ', contacts.contact_id))
                INTO @sql
                FROM transactions trans JOIN contacts 
                    ON trans.contact_id = contacts.id
                    JOIN transaction_sell_lines tsl on tsl.transaction_id = trans.id where trans.`location_id` in (_locationId)  
                    and trans.`type` = _transType and  date(trans.transaction_date) between date(_startDate) and date(_endDate);

                SET @sql = CONCAT(
                            'SELECT pro.id,pro.name, ', @sql,  
                            ' FROM transaction_sell_lines tsl JOIN products pro
                                ON tsl.product_id = pro.id 
                                JOIN transactions trans ON trans.id = tsl.transaction_id
                            GROUP BY pro.name, pro.id');

                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            
            END";

        DB::unprepared("DROP PROCEDURE IF EXISTS overall_summary_product");
        DB::unprepared($procedure);
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
