<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcedureForReturnReport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $procedure = "
        CREATE PROCEDURE overall_summary_return_product(IN _locationId TEXT,IN _transType TEXT,IN _startDate TEXT,IN _endDate TEXT)
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
                        ' THEN srl.quantity END) ', contacts.contact_id))
                INTO @sql
                FROM transactions trans JOIN contacts 
                    ON trans.contact_id = contacts.id
                    JOIN sell_return_lines srl on srl.transaction_id = trans.id where trans.`location_id` =@locationid  
                    and trans.`type` = @transType and  date(trans.transaction_date) between date(@startDate) and date(@endDate);
                SET @sql = CONCAT(
                            'SELECT pro.id,pro.sku, ', @sql,  
                            ' FROM sell_return_lines srl JOIN products pro
                                ON srl.product_id = pro.id 
                                JOIN transactions trans ON trans.id = srl.transaction_id
                                where date(trans.transaction_date) between date(@startDate) and date(@endDate) and trans.`location_id` =@locationid
                                and trans.`type` = @transType
                            GROUP BY pro.id, pro.sku ORDER BY pro.sku ASC');

                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
            
            END";

        DB::unprepared("DROP PROCEDURE IF EXISTS overall_summary_return_product");
        DB::unprepared($procedure);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS overall_summary_return_product");
    }
}
