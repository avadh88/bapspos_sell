@extends('layouts.app')
@section('title', __('Custom requirements'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1> @lang('Custom requirements')
        <small>@lang( 'contact.manage_your_contact', ['contacts' =>  __('Custom requirements') ])</small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content">
    <input type="hidden" value="" id="contact_type">
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'contact.all_your_contact', [])])
        
        
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="custom_requirements">
                    <thead>
                        <tr>
                            <th>@lang('Id')</th>
                            <th>@lang('Business Id')</th>
                            <th>@lang('Customer')</th>
                            <th>@lang('Requirements')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                       
                    </tfoot>
                </table>
            </div>
        
    @endcomponent

    <div class="modal fade contact_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade pay_contact_due_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->

@endsection
