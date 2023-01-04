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
    font-weight: bold;
    text-align: center;
  }

  .card-text {
    text-align: justify;
  }

  .modal-header {
    background-image: linear-gradient(#7FDBFF, white);
  }

  .modal-footer {
    background-image: linear-gradient(white, #7FDBFF);
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
<section class="no-print">
  <!-- <h1 style="text-align:center; font-size: 70px;">
      @if($name == 'Provision' || $name == 'Swachhata')
      Provision/Swachhata
      @elseif($name == 'Electric' || $name == 'Safety')
      Electric/Safety
      @elseif($name == 'Hardware' || $name == 'Paints')
      Hardware/Paints
      @else
      {{$name}}
      @endif
      
    </h1> -->
  <div class="row">
    <div class="col-md-4">
      <h1 class="text-center">Stationery</h1>
      <div id="category_list_stationery">
      </div>

    </div>
    <div class="col-md-4" id="">
      <h1 class="text-center">Provision</h1>
      <div id="category_list_provision">
      </div>

    </div>
    <div class="col-md-4" id="category_list_plastic">
      <h1 class="text-center">Plastic</h1>
      <div id="category_list_plastic">
      </div>


    </div>
  </div>
</section>

@stop

@section('javascript')
<script>
  window.laravel_echo_port = 6001;
</script>
<script src="//{{ Request::getHost() }}:6001/socket.io/socket.io.js" type="text/javascript"></script>
<script src="{{ url('/js/laravel-echo-setup.js') }}" type="text/javascript"></script>


<script type="text/javascript">
  var i = 0;
  window.Echo.channel('user-channel-16')
    .listen('.UserEvent', (data) => {
      i++;
      console.log(data);
      if (typeof(data.data) != "undefined") {
        toastr.success("New order coming");
        // $("#"+data.bill_no).remove();
        //$("#category_list_stationary").remove();
        $("#category_list_stationery").append(data.data);
      }
    });

  window.Echo.channel('user-channel-17')
    .listen('.UserEvent', (data) => {
      i++;
      console.log(data);
      if (typeof(data.data) != "undefined") {
        toastr.success("New order coming");
        // $("#"+data.bill_no).remove();
        //$("#category_list_provision").remove();
        $("#category_list_provision").append(data.data);
      }
    });

  window.Echo.channel('user-channel-15')
    .listen('.UserEvent', (data) => {
      i++;
      console.log(data);
      if (typeof(data.data) != "undefined") {
        toastr.success("New order coming");
        // $("#"+data.bill_no).remove();
        //$("#category_list_plastic").remove();
        $("#category_list_plastic").append(data.data);
      }
    });

  window.Echo.channel('user-channel-remove-category-product')
    .listen('.UserEvent', (data) => {
      i++;
      if (typeof(data.data) != "undefined") {
        console.log(data.data)
        var contentToRemove = document.querySelectorAll(".product-" + data.data);
        $(contentToRemove).remove();
        //$("#"+data.data).remove();
      }
    });
</script>
@endsection