@extends('layouts.app')
@section('title', __('gate_pass.edit_gate_pass'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
	<h1>@lang('gate_pass.edit_gate_pass')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action('GatePassController@update' , [$gatePassData->id] ), 'method' => 'PUT', 'id' => 'add_gate_pass_form', 'files' => true ]) !!}

	<div class="box box-solid">
		<div class="box-body" style="padding: 30px;">
			<div class="row">
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group ">
							{!! Form::label('getpass_type', __('gate_pass.type').':*') !!}
							{!! Form::select('getpass_type', ['' => __('Please select'),'1' => __('Mandir'), '0' => __('Haribhakt')],$gatePassData->getpass_type, ['class' => 'form-control','required']) !!}
						</div>
					</div>
					<div class="col-sm-4 <?= $class= $gatePassData->getpass_type == 0 ? 'hide':''; ?>" id="refrence_no_div">
						<div class="form-group ">
							{!! Form::label('reference_no', __('gate_pass.reference_no').':*') !!}
							{!! Form::text('reference_no', $gatePassData->reference_no, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('vibhag_name', __('gate_pass.vibhag_name').':*') !!}
							{!! Form::text('vibhag_name', $gatePassData->vibhag_name, ['class' => 'form-control','required']); !!}
						</div>
					</div>

					
				</div>

				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('driver_name', __('gate_pass.driver_name').':*') !!}
							{!! Form::text('driver_name', $gatePassData->driver_name, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('driver_mobile_number', __('gate_pass.driver_mobile_number').':*') !!}
							{!! Form::number('driver_mobile_number', $gatePassData->driver_mobile_number, ['class' => 'form-control','required','data-rule-max-digits' =>10, 'data-msg-max-digits'=>"Please Enter Valid Number"]); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('vehicle_number', __('gate_pass.vehicle_number').':*') !!}
							{!! Form::text('vehicle_number', $gatePassData->vehicle_number, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					
				</div>
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('deliever_to', __('gate_pass.deliever_to').':*') !!}
							{!! Form::text('deliever_to', $gatePassData->deliever_to, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('sign_of_gate_pass_approval', __('gate_pass.sign_of_gate_pass_approval').':*') !!}
							{!! Form::text('sign_of_gate_pass_approval', $gatePassData->sign_of_gate_pass_approval, ['class' => 'form-control','required']); !!}
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('sign_of_secutiry_person', __('gate_pass.sign_of_secutiry_person').':') !!}
							{!! Form::text('sign_of_secutiry_person', $gatePassData->sign_of_secutiry_person, ['class' => 'form-control']); !!}
						</div>
					</div>
					
				</div>
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('date', __('messages.date') . ':*') !!}
							<div class="input-group">
								<span class="input-group-addon">
									<i class="fa fa-calendar"></i>
								</span>
								{!! Form::text('date', @format_datetime($gatePassData->date), ['class' => 'form-control gate_pass_date', 'id' => 'date']); !!}
							</div>
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							@if($gatePassData->document)
							<div class="input-group">
								@endif
								{!! Form::label('document', __('gate_pass.attach_document') . ':') !!}
								{!! Form::file('document', ['id' => 'upload_document']); !!}
								@if($gatePassData->document)
								<span class="input-group-btn input-space" style="padding-left:15px;padding-top:22px;">
									<button type="button" data-toggle="modal" data-target="#gate_pass_image" id="cash_receipt_modal" class="btn btn-primary pull-right btn-flat">@lang('View')</button>
								</span>
								@endif
							</div>
							@if($gatePassData->document)
						</div>
						@endif
					</div>
				</div>
				<div class="row">
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::label('item', __('gate_pass.item') . ':') !!}
							@foreach( $gatePassData->values as $attr)
							@if( $loop->first )

							{!! Form::text('edit_items[' . $attr->id . ']', $attr->name, ['class' => 'form-control']); !!}

							@endif
							@endforeach
						</div>
					</div>
					<div class="col-sm-4">
						<div class="form-group">
							<button type="button" class="btn btn-primary" id="add_items" style="margin-top: 25px;">+</button>
						</div>
					</div>
				</div>
				<div id="items_data" class="box-body" style="padding-left: 14px;">
					@foreach( $gatePassData->values as $attr)
					@if( !$loop->first )
					<div class="row">
						<div class="col-sm-4" style="padding-left: 0">
							<div class="form-group">
								{!! Form::text('edit_items[' . $attr->id . ']', $attr->name, ['class' => 'form-control']); !!}
							</div>
						</div>
						<div class="col-sm-4" style="padding-left:22px">
							<div class="form-group"><button type="button" class="btn btn-danger delete_items">-</button></div>
						</div>
					</div>
					@endif
					@endforeach
				</div>
				<!-- <div class="row">
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
				</div> -->
				<!-- @foreach( $gatePassData->values as $attr)
				@if( $loop->first )
				<div class="row">

					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::text('edit_items[' . $attr->id . ']', $attr->name, ['class' => 'form-control', 'required']); !!}
						</div>

					</div>
					<div class="col-sm-4">
						<div class="form-group">
							{!! Form::number('edit_qtys[' . $attr->id . ']', $attr->qty, ['class' => 'form-control', 'required']); !!}
						</div>
					</div>

					<div class="col-sm-4">
						<div class="form-group">
							<button type="button" class="btn btn-primary" id="add_items">+</button>
						</div>
					</div>
				</div>
				@endif
				@endforeach -->

				<!-- <div id="items_data" class="box-body" style="padding-left: 14px;">
					@foreach( $gatePassData->values as $attr)
					@if( !$loop->first )
					<div class="row">
						<div class="col-sm-4" style="padding-left: 0">
							<div class="form-group">
								{!! Form::text('edit_items[' . $attr->id . ']', $attr->name, ['class' => 'form-control', 'required']); !!}
							</div>
						</div>
						<div class="col-sm-4" style="padding-left: 10px;padding-right: 10px ">
							<div class="form-group">
								{!! Form::number('edit_qtys[' . $attr->id . ']', $attr->qty, ['class' => 'form-control', 'required']); !!}
							</div>
						</div>
						<div class="col-sm-4" style="padding-left:22px">
							<div class="form-group"><button type="button" class="btn btn-danger delete_items">-</button></div>
						</div>
					</div>
					@endif
					@endforeach
				</div> -->
				<div class="col-sm-12">
					<button type="submit" id="submit_edit_gate_pass_form" class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
				</div>
			</div>
		</div>

		@if($gatePassData->document)
		<!-- Image Modal -->
		<div class="modal fade" tabindex="-1" role="dialog" id="gate_pass_image" class="cash_receipt">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title"></h4>
					</div>
					<div class="modal-body">
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<img src="{{ asset('/uploads/documents/' . $gatePassData->document) }}" style="width:100%" />
								</div>
							</div>

						</div>
					</div>
					<div class="modal-footer">
						<a download="{{$gatePassData->document}}" href="{{ asset('/uploads/documents/' . $gatePassData->document) }}">
							<button type="button" class="btn btn-primary">@lang('messages.download')</button>
						</a>
						<button type="button" class="btn btn-primary" data-dismiss="modal">@lang('messages.close')</button>
					</div>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->
		@endif
	</div>

	{!! Form::close() !!}
</section>
@endsection
@section('javascript')
<script src="{{ asset('js/gate_pass.js?v=' . $asset_v) }}"></script>
<script>
$('#getpass_type').on('change', function() {
	if(this.value==1)
	{
		$("#refrence_no_div").removeClass('hide');
	}
	else
	{
		$("#reference_no").val("");
		$("#refrence_no_div").addClass('hide');
	}
});
</script>
@endsection