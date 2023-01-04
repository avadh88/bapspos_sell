<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProcedureOverallSummaryUpdated extends Migration
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

                SET @locationId = _locationId;
    
                SET @transType = _transType;
                
                SET @startDate = _startDate;
                
                SET @endDate = _endDate;


                SELECT GROUP_CONCAT(DISTINCT
                        CONCAT(
                        'SUM(CASE WHEN trans.contact_id = ', trans.contact_id,
                        ' THEN tsl.quantity END) ', contacts.contact_id))
                INTO @sql
                FROM transactions trans JOIN contacts 
                    ON trans.contact_id = contacts.id
                    JOIN transaction_sell_lines tsl on tsl.transaction_id = trans.id where trans.`location_id` =@locationid  
                    and trans.`type` = @transType and  date(trans.transaction_date) between date(@startDate) and date(@endDate);
                SET @sql = CONCAT(
                            'SELECT pro.id,pro.sku, ', @sql,  
                            ' FROM transaction_sell_lines tsl JOIN products pro
                                ON tsl.product_id = pro.id 
                                JOIN transactions trans ON trans.id = tsl.transaction_id
                                where date(trans.transaction_date) between date(@startDate) and date(@endDate) and trans.`location_id` =@locationid
                                and trans.`type` = @transType
                            GROUP BY pro.id, pro.sku ORDER BY pro.sku ASC');

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
