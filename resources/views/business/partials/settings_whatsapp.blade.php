<div class="pos-tab-content">
    <div class="row">
        <div class="col-xs-3">
            <div class="form-group">
            	{!! Form::label('whatsapp_settings[url]', 'URL:') !!}
            	{!! Form::text('whatsapp_settings[url]', $whatsapp_settings['url'], ['class' => 'form-control','placeholder' => 'URL']); !!}
            </div>
        </div>
        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('whatsapp_settings[version]', __('lang_v1.version') . ':') !!}
                {!! Form::text('whatsapp_settings[version]',$whatsapp_settings["version"], ['class' => 'form-control','placeholder' => __('lang_v1.version')]); !!}
            </div>
        </div>

        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('whatsapp_settings[business_id]', __('lang_v1.business_id') . ':') !!}
                {!! Form::text('whatsapp_settings[business_id]',$whatsapp_settings["business_id"], ['class' => 'form-control','placeholder' => __('lang_v1.business_id')]); !!}
            </div>
        </div>

        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('whatsapp_settings[phone_number_id]', __('lang_v1.phone_number_id') . ':') !!}
                {!! Form::text('whatsapp_settings[phone_number_id]',$whatsapp_settings["phone_number_id"], ['class' => 'form-control','placeholder' => __('lang_v1.phone_number_id')]); !!}
            </div>
        </div>

        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('whatsapp_settings[user_access_token]', __('lang_v1.user_access_token') . ':') !!}
                {!! Form::text('whatsapp_settings[user_access_token]',$whatsapp_settings["user_access_token"], ['class' => 'form-control','placeholder' => __('lang_v1.user_access_token')]); !!}
            </div>
        </div>

        <div class="col-xs-3">
            <div class="form-group">
                {!! Form::label('whatsapp_settings[waba-id]', __('lang_v1.waba-id') . ':') !!}
                {!! Form::text('whatsapp_settings[waba-id]',$whatsapp_settings["waba-id"], ['class' => 'form-control','placeholder' => __('lang_v1.waba-id')]); !!}
            </div>
        </div>

        
       
        <div class="clearfix"></div>
        <hr>
        
       
    </div>
</div>