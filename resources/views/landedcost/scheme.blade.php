@extends('landedcost.dashboard')
@section('content')
<style>
table thead th{
    color:white !important;
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
    label{
        font-size: 12px;
        font-weight:600;
        color: black;
        font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    }    
   
 
</style>
<br>
<div class="container-fluid" style="position: absolute">
<button type="button" class="btn btn-xs btn-success pull-right" data-toggle="modal" data-target="#exampleModal">
<i class="fa fa-lg fa-plus-circle"></i> Create Scheme</button>
</div>
<br><br>
<div class="container-fluid">
    <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
    <th scope="col">No</th>
    <th scope="col">Scheme Name</th>
    <th scope="col">Cost Code</th>
    <th scope="col">Calculation Base</th>
    <th scope="col">Rate</th>
    <th scope="col">VAT</th>
    <th scope="col">Created</th>
    <th scope="col">Action</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $counter = 0;
    ?>
    @foreach ($schemes as $sch)
        <tr>
    <td>{{ $counter+=1 }}</td>
    <td>{{ $sch->scheme_name }}</td>
    <td>{{ $sch->cost_name }}</td>
    <td>{{ $sch->calc_base_name }}</td>
    <td>{{ $sch->rate }}</td>
    <td>{{ $sch->vat }}</td>
    <td>{{\Carbon\Carbon::parse( $sch->created_at)->diffForHumans()}}</td>    
    <td><a href='' class='' data-toggle='modal' data-target="#editmodal{{$sch->id}}"><i style="color:#fd1212" class="fa fa-lg fa-pencil-square-o"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;
    <a href="#" data-id="{{$sch->id}}" class="" data-toggle="modal" data-target="#deleteModal{{$sch->id}}"><i style="color:darkorange" class="fa fa-lg fa-trash"></i></a></td> 
            
    
    </td>
    </tr>
    @include('landedcost.modal_scheme')
    @endforeach
   
    </tbody>
    </table>
    </div>



    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg " role="document">
        <div class="modal-content">
        <div class="modal-header ">
        <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter Scheme Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <form action="{{ action('SchemesController@store')}}" method="POST">
           {{ csrf_field() }}
        <div class="modal-body">
       

                <form>
                <div class="form-group row">
                <label for="" class="col-sm-2 col-form-label">Scheme Name:</label>
                <div class="col-sm-10">
                <input type="text" class="form-control"  name="scheme_name" id="scheme_name" placeholder="Enter Scheme Name">
                </div>
                </div>
                <div class="form-group row">
                <label for="" class="col-sm-2 col-form-label">Type of Cost:</label>
                <div class="col-sm-10" >
                <select id="cost_type" name="cost_type" required  class="form-control form-control">
                <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Cost Type</b></option>
                @foreach ($cost_types as $cost)
                <option value="{{ $cost->id }}">{{$cost->cost_name }}</option>
                @endforeach  
                </select>
                </div>
                </div>
                <div class="form-group row">
                <label for="" class="col-sm-2 col-form-label">Calculation Base:</label>
                <div class="col-sm-10">
                <select id="calc_base" name="calc_base" required  class="form-control form-control">
                <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Calculation Base</b></option>
                @foreach ($cal_bases as $calc_base)
                <option value="{{ $calc_base->id }}">{{$calc_base->calc_base_name }}</option>
                @endforeach  
                </select>
                </div>
                </div>
                <div class="form-group row">
                <label for="" class="col-sm-2 col-form-label">RATE:</label>
                <div class="col-sm-10">
                <input type="number" class="form-control" step=".01" name="rate" id="rate" placeholder="Enter RATE">
                </div>
                </div>
                <div class="form-group row">
                <label for="inputPassword" class="col-sm-2 col-form-label">VAT %:</label>
                <div class="col-sm-10">
                <input type="number" class="form-control" name="vat" id="vat" placeholder="Enter VAT">
                </div>
                </div>
                </form>      

       
        <div class="modal-footer">
        <button type="button" class="btn btn-sm mr-auto btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-sm btn-primary">Save</button>
        </div>
        </div>
        </form>
        </div>
        </div>
        </div>
        <script>
        $(document).ready(function(){ 
        var table =$('#table').DataTable();
        });
        $('div.alert').delay(3000).slideUp(300);
        </script>
    
@endsection

