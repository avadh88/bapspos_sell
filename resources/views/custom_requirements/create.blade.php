@extends('layouts.app')
@section('title', __('Custom Requirements'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('Custom Requirements')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action('CustomRequirementsController@store'), 'method' => 'post', 'id' => 'add_requirementcontact_id_form', 'files' => true ]) !!}
	<div class="box box-solid">
		<div class="box-body">
		@if(is_null($default_location))
            <div class="form-group row">
                <div class="col-sm-2 col-md-offset-2">
					{!! Form::label('customers', __('purchase.business_location').':*') !!}
                </div>
                <div class="col-md-4 col-md-pull-1 col-md-offset-1">
					{!! Form::select('customers', $business_locations,$business_id , ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>
		@else
			<input type="hidden" name="customers" value="{{ $default_location}}"/>
		@endif
		@if($defaultCustomerId)
			<input type="hidden" name="contact_id" value="{{ $defaultCustomerId}}"/>
		@else
			<div class="form-group row ml-3">
                <div class="col-sm-2 col-md-offset-2">
					{!! Form::label('customers', __('Customer').':*') !!}
                </div>
                <div class="col-md-4 col-md-pull-1 col-md-offset-1">
					{!! Form::select('contact_id', $customers,'' , ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>
		@endif
            

			<div class="form-group row ml-3">
                <div class="col-sm-2 col-md-offset-2">
					{!! Form::label('customers', __('Reqquirement').':*') !!}
                </div>
                <div class="col-md-4 col-md-pull-1 col-md-offset-1">
				{!! Form::textarea('requirements','', ['class' => 'form-control','placeholder' => __('Requirements'), 'required' ,'rows' => '4']); !!}
				</div>
			</div>

			<div class="col-sm-12 col-md-pull-4">
				<button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
			</div>

		</div>
	</div> <!--box end-->

{!! Form::close() !!}
</section>
@endsection
