@extends('layouts.app')
@section('title', __('home.home'))

@section('css')
{{--{!! Charts::styles(['highcharts']) !!} --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.8.2/css/all.min.css" crossorigin="anonymous" />

@endsection

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}
    </h1>
</section>
@if(auth()->user()->can('dashboard.data'))
<!-- Main content -->
<section class="content no-print">
    <div class="row">
        <div class="col-md-3 col-sm-4 col-xs-6">
            <div class="info-box1">
                <a href="{{action('SellPosController@create')}}">
                    <span class="info-box-icon bg-teal" style="width:100%">
                        <i class="ion ion-ios-cart-outline"></i>
                    </span>
                    <h3>POS</h3>
                </a>
                <!-- /.info-box-content -->
            </div>
        <!-- /.info-box -->
        </div>
        
        <div class="col-md-3 col-sm-4 col-xs-6">
            <div class="info-box1">
                <a href="{{action('SellPosController@index')}}">
                    <span class="info-box-icon bg-aqua" style="width:100%">
                        <i class="ion ion-ios-cart-outline"></i>
                    </span>
                    <h3>Total Sale</h3>
                </a>
                <!-- /.info-box-content -->
            </div>
        <!-- /.info-box -->
        </div>
        <div class="col-md-3 col-sm-4 col-xs-6">
            <div class="info-box1">
                <a href="{{action('SellReturnGenralstoreController@index')}}">
                    <span class="info-box-icon bg-green" style="width:100%">
                    <i class="fa-solid fa-cart-arrow-down"></i>
                    
                    <!-- <i class="fa-brands fa-opencart"></i> -->
                    </span>
                    <!-- /.info-box-content -->
                    <h3>Total Return</h3>
                </a>
            </div>
        <!-- /.info-box -->
        </div>

        <div class="col-md-3 col-sm-4 col-xs-6">
            <div class="info-box1">
                <a href="{{action('GenralstoreReportController@getDepartmentWisePendingReport')}}">
                    <span class="info-box-icon bg-red" style="width:100%">
                    <i class="ion ion-ios-cart-outline"></i>
                    </span>
                    <h3>Pending Report</h3>
                </a>
                <!-- /.info-box-content -->
            </div>
        <!-- /.info-box -->
        </div>
        <div class="col-md-3 col-sm-4 col-xs-6">
            <div class="info-box1">
                <a href="{{action('CustomRequirementsController@create')}}">
                    <span class="info-box-icon bg-aqua" style="width:100%"><i class="ion ion-ios-cart-outline"></i></span>
                    <!-- /.info-box-content -->
                    
                    <h3>Custom Requirements</h3>
                </a>
            </div>
        <!-- /.info-box -->
        </div>
    </div>
</section>
<!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
   {{-- {!! Charts::assets(['highcharts']) !!} --}}
    <script src="https://kit.fontawesome.com/610b6b2bf1.js" crossorigin="anonymous"></script>
@endif
@endsection

