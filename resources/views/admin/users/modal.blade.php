<div class="modal fade" id="editmodal{{$userdata->id}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
    <div class="modal-header ">
    <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Update User Details</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <div class="modal-body">
       
    <form action="{{ action('UsersController@update',$userdata->id)}}" method="POST">
    {{ csrf_field() }}
    <div class="row my-2">
    <div class="form-group col-md-4 mb-2">
    <label>First Name</label>
    <div class="input-group">
    <input type="text" class="form-control form-control" required name="fname"  value="{{$userdata->fname}}">
    </div></div>
    <div class="form-group col-md-4 mb-2">
    <label>Last Name</label>
    <div class="input-group">
    <input type="text" class="form-control form-control" required name="lname"  value="{{$userdata->lname}}">
    </div></div>
    <div class="form-group col-md-4 mb-2">
    <label>Email Address</label>
    <div class="input-group">
    <input type="email" class="form-control form-control" required name="email"  value="{{$userdata->email}}">
    </div></div>
    </div>
    <div class="row my-2">
    <div class="form-group col-md-4 mb-2">
    <label>Role</label>
    <div class="form-group">
    <select id="role_id" name="role_id"  class="form-control form-control">
    <option type="text" class="form-control input-group " hidden=""   disabled="disabled" selected="selected" value=""><b>Select User Role</b></option>
    @foreach ($roles as $role)
    <option value="{{ $role->id }}"{{ ( $userdata->role_id == $role->id) ? 'selected' : '' }}>{{$role->role_name }}</option>
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
    <option value="{{ $country->id }}"{{ ( $userdata->country_id == $country->id) ? 'selected' : '' }}>{{$country->country_name }}</option>
    @endforeach  
    </select>
    </div>
    </div>
    <div class="form-group col-md-4 mb-2">
    <label>Telephone</label>
    <div class="input-group">
    <input type="number" class="form-control form-control" required name="telephone"  pattern="[0-9]"  value="{{$userdata->telephone}}">
    </div></div>
    </div>
    {{ method_field('PUT') }}
            
    
    <div class="card-footer">
    <button type="button" class="btn btn-sm float-left btn-secondary">Cancel</button>
    <button type="submit" class="btn btn-sm float-right btn-primary">Update</button>
    </div>
    </div>
    </div>
    </form>
    </div>
    </div>
    