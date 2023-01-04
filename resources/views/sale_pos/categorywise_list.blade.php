@extends('layouts.app_without_header_footer')

@section('content')
<style type="text/css">
/* Responsive layout - makes a two column-layout instead of four columns */
@media (max-width: 800px) {
  .column {
    flex: 46%;
    max-width: 46%;
  }
}

.card {
  opacity: 0.8;
  filter: alpha(opacity=60);
}
.card-title {
  font-weight:bold;
  text-align: center;
}
.card-text {
  text-align: justify;
}

.modal-header {
  background-image: linear-gradient(#7FDBFF,white);
}
.modal-footer {
  background-image: linear-gradient(white,#7FDBFF);
}
#left-panel-link {
  position: relative;
  left: 5%;
  background-color: #555;
  color: white;
  font-size: 16px;
  padding: 12px 20px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
}
#right-panel-link {
  position: absolute;
  right: 10%;
  background-color: #555;
  color: white;
  font-size: 16px;
  padding: 12px 20px;
  border: none;
  cursor: pointer;
  border-radius: 5px;
}
.sep {
  height: 25px;
}
</style>
<section class="content no-print">
    <h1 style="text-align:center; font-size: 100px;"></h1>
    <div class="row" id="category_list">
        @foreach($category as $categoryData)
        <div class="card col-sm-3 col-md-3 col-lg-3" id="">
          <div class="card-body">
          <a href="/sells/pos/category/{{$categoryData->name}}" >
            <h3 style="font-size:35px;  background-color:#dff0d8; padding:10%;" class="card-title">
            {{$categoryData->name}}
            </h3>
          </a>
          </div>
        </div>
        @endforeach
    </div>
</section>

@stop

@section('javascript')

@endsection

    
      
    
