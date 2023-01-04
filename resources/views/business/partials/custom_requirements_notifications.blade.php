<div class="pos-tab-content">
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                
                {!! Form::label("custom_req[notify_mob_no]", __('lang_v1.custom_requirements_notifications_mobile_no') . ':') !!}
                {!! Form::text("custom_req[notify_mob_no]", $custom_req['notify_mob_no'], ['class' => 'form-control']); !!}
            </div>
        </div>
    </div>
</div>