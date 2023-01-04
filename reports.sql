select (SELECT SUM(pl.quantity) FROM transactions JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.purchase_seva=0   
                    AND pl.variation_id=vld.product_id AND transactions.location_id='1' AND (transactions.transaction_date >= '2022-02-05' and transactions.transaction_date <= '2022-02-05')) as total_purchase, (SELECT SUM(pl.quantity) FROM transactions JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.purchase_seva=1 AND pl.variation_id=vld.product_id AND transactions.location_id='1' 
                    AND (transactions.transaction_date >= '2022-02-05' and transactions.transaction_date <= '2022-02-05')) as total_seva, 
                    
                    (SELECT SUM(pl.quantity) FROM transactions JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase_transfer'  
                    AND pl.variation_id=vld.product_id AND transactions.location_id='1'
                    AND (transactions.transaction_date >= '2022-02-05' and transactions.transaction_date <= '2022-02-05')) as total_transfer_from, 
                    
                    SUM(`vld`.`qty_available`) as current_stock, 
                    
                    (SELECT SUM(tsl.quantity) FROM transactions JOIN transaction_sell_lines AS tsl ON transactions.id=tsl.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell_transfer'  
                    AND tsl.variation_id=vld.product_id AND transactions.location_id='1'
                    AND (transactions.transaction_date >= '2022-02-05' and transactions.transaction_date <= '2022-02-05')) as total_transfer_to, 
                    
                    (SELECT SUM(tsl.quantity) FROM transactions JOIN transaction_sell_lines AS tsl ON transactions.id=tsl.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell'  AND transactions.permanent_sell=1
                    AND tsl.variation_id=vld.product_id AND transactions.location_id='1'
                    AND (transactions.transaction_date >= '2022-02-05' and transactions.transaction_date <= '2022-02-05')) as total_permanent_sell, variations.sub_sku as sku, p.name as product, p.type, p.id as product_id, p.disposable_item as disposable, 
                    units.short_name as unit, p.enable_stock as enable_stock, variations.sell_price_inc_tax as unit_price, pv.name as product_variation, 
                    variations.name as variation_name  from `variations` inner join `products` as `p` on `p`.`id` = `variations`.`product_id` inner join `units` on `p`.`unit_id` = `units`.`id` inner join `product_variations` as `pv` on `variations`.`product_variation_id` = `pv`.`id` inner join `variation_location_details` as `vld` on `p`.`id` = `vld`.`product_id` where `vld`.`location_id` = 1 group by `variations`.`id`