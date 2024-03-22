@extends('admin.dashboard')
@section('content')
<style>
    table thead th{
    color:white !important;
    background-color: #00BFFF !important;
    font-size: 12px;
    font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    font-weight: 600;
    white-space: nowrap;
    }
    td{
    font-size: 11px;
    height: 12px !important;
    white-space: nowrap;
    }
</style>

<div class="container-fluid" style="position: absolute">
    <button type="button" class="btn btn-xs btn-success pull-right" data-toggle="modal" data-target="#exampleModal">
    <i class="fa fa-lg fa-plus-circle"></i> Add User</button>
    </div>
    <br><br>
    <hr>
    <div class="container-fluid">
    <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead>
    <tr>
    <th scope="col">Full Name</th>
    <th scope="col">Email</th>
    <th scope="col">Telephone</th>
    <th scope="col">Role</th>
    <th scope="col">Country</th>
    <th scope="col">Enabled</th>
    <th scope="col">Action</th>
    </tr>
    </thead>
      @foreach ($users as $userdata)
     <tr>
    <td>{{ $userdata->fname }} {{ $userdata->lname }}</td>
    <td>{{ $userdata->email }}</td>
    <td>{{ $userdata->telephone }}</td>
    <td>{{ $userdata->role_name }}</td>
    <td>{{ $userdata->country_name }}</td>
    @if($userdata->status ==1)         
    <td><span><i style="color: green" class="fa fa-lg fa-check-circle"></i></span></td> 
     @else
    <td><span><i style="color: red" class="fa fa-lg fa-times-circle"></i></span></td>       
    @endif
    <td>
    <a href='' class='' data-toggle='modal' data-target="#editmodal{{$userdata->id}}"><i style="color:chocolate" class="fa fa-lg fa-pencil-square-o"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
    <input data-id="{{$userdata->id}}" class="toggle-class" data-size="xs" type="checkbox" data-onstyle="danger" data-offstyle="success" data-toggle="toggle" data-on="Deactivate" data-off="Activate" {{ $userdata->status ? 'checked' : '' }}> 
</td>
    </tr>
    @include('admin.users.modal')
    @endforeach
    </table>
    </div>



    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header ">
        <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter User Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <div class="modal-body">
           
        <form action="{{ action('UsersController@store')}}" method="POST">
        {{ csrf_field() }}
        <div class="row my-2">
        <div class="form-group col-md-4 mb-2">
        <label>First Name</label>
        <div class="input-group">
        <input type="text" class="form-control form-control" required name="fname"  placeholder="Enter First Name">
        </div></div>
        <div class="form-group col-md-4 mb-2">
        <label>Last Name</label>
        <div class="input-group">
        <input type="text" class="form-control form-control" required name="lname"  placeholder="Enter Last Name">
        </div></div>
        <div class="form-group col-md-4 mb-2">
        <label>Email Address</label>
        <div class="input-group">
        <input type="email" class="form-control form-control" required name="email"  placeholder="Enter Email">
        </div></div>
        </div>
        <div class="row my-2">
        <div class="form-group col-md-4 mb-2">
        <label>Role</label>
        <div class="form-group">
        <select id="role_id" name="role_id"  class="form-control form-control">
        <option type="text" class="form-control input-group " hidden=""   disabled="disabled" selected="selected" value=""><b>Select User Role</b></option>
        @foreach ($roles as $role)
        <option value="{{$role->id}}">{{$role->role_name}}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-4 mb-2">
        <label>Country</label>
        <div class="form-group">
        <select id="country_id" name="country_id"  class="form-control form-control">
        <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select User Country</b></option>
        @foreach ($countries as $country)
        <option value="{{$country->id}}">{{$country->country_name}}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-4 mb-2">
        <label>Telephone</label>
        <div class="input-group">
        <input type="number" class="form-control form-control" required name="telephone"  pattern="[0-9]"  placeholder="0722000000">
        </div></div>
        </div>
                
        <div class="row my-2">
        <div class="form-group col-md-6 mb-2">
        <label>Password</label>
        <div class="form-group">
        <input type="password" required minlength="4" class="form-control form-control" name="password" id="password" placeholder="min 4 characters" >
        </div>
        </div>
        <div class="form-group col-md-6 mb-2">
        <label>Confirm Password</label>
        <div class="form-group">
        <input type="password" required minlength="4" class="form-control form-control" name="conf_password" id="conf_password" placeholder="min 4 characters" >
        </div>
        </div>
        </div>  

        <div class="card-footer">
        <button type="button" class="btn btn-sm float-left btn-secondary">Cancel</button>
        <button type="submit" class="btn btn-sm float-right btn-primary">Save</button>
        </div>
        </div>
        </div>
        </form>
        </div>
        </div>
        
        
        

    <script>
    $(document).ready(function(){ 
    var table =$('#table').DataTable();
    });
    $('div.alert').delay(3000).slideUp(300);

    </script>
    <script> 
    $(function() { 
        
    $('.toggle-class').change(function() { 
  var status = $(this).prop('checked') == true ? 1 : 0;  
    var user_id = $(this).data('id');  
     $.ajax({ 
    type: "POST", 
    dataType: "json", 
    url: '/status_change', 
    data: {'status': status, 'user_id': user_id,"_token":"{{ csrf_token() }}"}, 
    success: function(data){ 
     location.reload();
    
    
    } 
        
        }); 
        
        }) 
        
        }) 
        
        </script> 


@endsection
