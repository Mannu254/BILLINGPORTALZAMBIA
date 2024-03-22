<style>
    #calc_base
{
    pointer-events: none;
}
</style>

<div class="modal fade" id="editmodal{{$sch->id ?? ''}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg " role="document">
    <div class="modal-content">
    <div class="modal-header ">
    <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter Scheme Details</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <form action="{{ action('SchemesController@update',$sch->id)}}" method="POST">
       {{ csrf_field() }}
    <div class="modal-body">
   
            <div class="form-group row">
            <label for="" class="col-sm-2 col-form-label">Scheme Name:</label>
            <div class="col-sm-10">
            <input type="text" class="form-control" readonly name="scheme_name" id="scheme_name" value="{{$sch->scheme_name}}">
            </div>
            </div>
            <div class="form-group row">
            <label for="" class="col-sm-2 col-form-label">Type of Cost:</label>
            <div class="col-sm-10" >
            <select id="cost_type" name="cost_type" required readonly style="pointer-events: none" class="form-control form-control">
            <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Cost Type</b></option>
            @foreach ($cost_types as $cost)
            <option value="{{ $cost->id }}"{{ ( $sch->cost_code == $cost->id) ? 'selected' : '' }}>{{$cost->cost_name }}</option>
            @endforeach  
            </select>
            </div>
            </div>
            <div class="form-group row">
            <label for="" class="col-sm-2 col-form-label">Calculation Base:</label>
            <div class="col-sm-10">
            <select id="calc_base" name="calc_base" required readonly style="pointer-events: none" class="form-control form-control">
            <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Calculation Base</b></option>
            @foreach ($cal_bases as $calc_base)            
            <option value="{{ $calc_base->id }}"{{ ( $sch->calcbase == $calc_base->id) ? 'selected' : '' }}>{{$calc_base->calc_base_name }}</option>
            @endforeach  
            </select>
            </div>
            </div>
            <div class="form-group row">
            <label for="" class="col-sm-2 col-form-label">RATE:</label>
            <div class="col-sm-10">
            <input type="number" class="form-control" step=".01" name="rate" id="rate" value="{{$sch->rate}}">
            </div>
            </div>
            <div class="form-group row">
            <label for="inputPassword" class="col-sm-2 col-form-label">VAT %:</label>
            <div class="col-sm-10">
            <input type="number" class="form-control" readonly name="vat" id="vat" value="{{$sch->vat}}">
            </div>
            </div>
              
            {{ method_field('PUT') }}
   
    <div class="modal-footer">
    <button type="button" class="btn btn-sm mr-auto btn-secondary" data-dismiss="modal">Close</button>
    <button type="submit" class="btn btn-sm btn-primary">Update</button>
    </div>
    </div>
    </form>
    </div>
    </div>
    </div>


    <!-- delete Modal -->

<div class="modal fade" id="deleteModal{{$sch->id ?? ''}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
    <div class="modal-header ">
    <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Delete Scheme</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <form action="{{ action('SchemesController@destroy', $sch->id)}}" method="POST">
    @csrf
    @method('DELETE')
    <h5 class="text-center">Are you sure you want to delete this Scheme?</h5>
    <div class="modal-body">
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-xs mr-auto btn-secondary" data-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i> Delete</button>
    </div>
    </form>
    </div>
    </div>
    </div>