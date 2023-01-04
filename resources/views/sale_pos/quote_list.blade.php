@extends('layouts.app')

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
    <div class="row" id="quote_list">
        
    </div>
</section>

@stop
<!-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
  </body>

</html> -->
@section('javascript')
<script>
            window.laravel_echo_port=6001;
    </script>
    <script src="//{{ Request::getHost() }}:6001/socket.io/socket.io.js" type="text/javascript"></script>
    <script src="{{ url('/js/laravel-echo-setup.js') }}" type="text/javascript"></script>
    
      
    <script type="text/javascript">
        var i = 0;
        window.Echo.channel('user-channel')
         .listen('.UserEvent', (data) => {
            i++;
            if(typeof(data.data)!= "undefined")
            {
              $("#quote_list").append(data.data);
            }
        });

        window.Echo.channel('user-channel-remove')
         .listen('.UserEvent', (data) => {
            i++;
            if(typeof(data.data)!= "undefined")
            {
              $("#quote_"+data.data).remove();
            }
        });
    </script>
@endsection

    
      
    
