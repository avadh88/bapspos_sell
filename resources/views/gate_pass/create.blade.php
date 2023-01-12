@extends('layouts.app')
@section('title', __('gate_pass.add_gate_pass'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
	<h1>@lang('gate_pass.add_gate_pass')</h1>
</section>

<!-- Main content -->
<section class="content no-print">

	{!! Form::open(['url' => action('GatePassController@store'), 'method' => 'post', 'id' => 'add_gate_pass_form', 'files' => true ]) !!}
	<div class="box box-solid">
		<div class="box-body" style="padding: 30px;">
			<div class="row">
				<div class="col-sm-3 hide">
					<div class="form-group ">
						{!! Form::label('serial_no', __('gate_pass.serial_no').':') !!}
						{!! Form::text('serial_no', null, ['class' => 'form-control']); !!}
					</div>
				</div>
				<div class="row">

					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('vibhag_name', __('gate_pass.vibhag_name').':') !!}
							{!! Form::text('vibhag_name', null, ['class' => 'form-control','required', 'id' => 'vibhag_name']); !!}
						</div>
					</div>

					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('driver_name', __('gate_pass.driver_name').':') !!}
							{!! Form::text('driver_name', null, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('driver_mobile_number', __('gate_pass.driver_mobile_number').':') !!}

							{!! Form::number('driver_mobile_number', null, ['class' => 'form-control','required','data-rule-max-digits' =>10, 'data-msg-max-digits'=>"Please Enter Valid Number"]); !!}
						</div>
					</div>
				</div>
				<div class="row">

					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('vehicle_number', __('gate_pass.vehicle_number').':') !!}
							{!! Form::text('vehicle_number', null, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('deliever_to', __('gate_pass.deliever_to').':') !!}
							{!! Form::text('deliever_to', null, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('sign_of_gate_pass_approval', __('gate_pass.sign_of_gate_pass_approval').':') !!}
							{!! Form::text('sign_of_gate_pass_approval', null, ['class' => 'form-control','required']); !!}
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('sign_of_secutiry_person', __('gate_pass.sign_of_secutiry_person').':') !!}
							{!! Form::text('sign_of_secutiry_person', null, ['class' => 'form-control','required']); !!}
						</div>
					</div>

					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('date', __('messages.date') . ':*') !!}
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-calendar"></i>
								</span>
								{!! Form::text('date', @format_datetime('now'), ['class' => 'form-control gate_pass_date','id' => 'date']); !!}
							</div>
						</div>
					</div>

					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('document', __('gate_pass.attach_document') . ':') !!}
							{!! Form::file('document', ['id' => 'upload_document']); !!}
							<p class="help-block">@lang('gate_pass.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('item', __('gate_pass.item') . ':') !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('qty', __('gate_pass.qty') . ':') !!}
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::text('items[]', null, ['class' => 'form-control', 'required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::number('qtys[]', null, ['class' => 'form-control', 'required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							<button type="button" class="btn btn-primary" id="add_items">+</button>
						</div>
					</div>
				</div>
			</div>
			<div id="items_data"></div>

			<div class="col-sm-12">
				<button type="button" id="submit_gate_pass_form" class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
			</div>
		</div>
	</div>

	{!! Form::close() !!}
</section>
@endsection
@section('javascript')
<script src="{{ asset('js/gate_pass.js?v=' . $asset_v) }}"></script>
@endsection