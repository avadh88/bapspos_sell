<?php
//print_r($business);
//exit; ?>
<div class="pos-tab-content active">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox("genral_store_settings[restrict_sell_with_sellorder]", 1,!empty($genral_store_settings['restrict_sell_with_sellorder']) ? true : false, 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'lang_v1.restrict_sell_with_sellorder' ) }}
                  </label>
                </div>
            </div>
        </div>
        
        <div class="clearfix"></div>
        
    </div>
</div>