<div class="col-md-6 offset-md-0 text-center" style="width: 35%; margin-left:auto; margin-right:auto;  margin-top:20px; height: 0px !important; z-index:1000;">
    @if(count($errors) > 0)
   
    @foreach ($errors->all() as $error)
    <div class="alert alert-danger">
    {{$error}}
    
    </div>
    @endforeach
    @endif

      

    @if (session('success'))
    <div class="alert alert-success">
    {{session('success')}}
    <button type="button" style="margin: 2px" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="True">&times;</span>
    </button>
    </div>
    @endif
    
    @if (session('error'))
    <div class="alert alert-danger">
    {{session('error')}}
    <button type="button" style="margin: 2px" class="close" data-dismiss="alert" aria-label="Close">
    <span aria-hidden="True">&times;</span>
    </button>
    </div>
    @endif
   </div>