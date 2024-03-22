@extends('admin.dashboard')
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
</style>
<br>
<div class="container-fluid" style="position: absolute">
<button type="button" class="btn btn-xs btn-success pull-right" data-toggle="modal" data-target="#exampleModal">
<i class="fa fa-lg fa-plus-circle"></i> Country</button>
</div>
<br><br>
<div class="container-fluid">
    <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
    <th scope="col">No</th>
    <th scope="col">Country Name</th>
    <th scope="col">Country Code</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $counter = 0;
    ?>
    @foreach ($countries as $country)
        <tr>
    <td>{{ $counter+=1 }}</td>
    <td>{{ $country->country_name }}</td>
    <td>{{ $country->country_code }}</td>
    </tr>
    @endforeach
    </tbody>
    </table>
    </div>



    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <div class="modal-header ">
        <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter Country Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <form action="{{ action('CountrysController@store')}}" method="POST">
           {{ csrf_field() }}
        <div class="modal-body">
        <div class="form-row">
        <div class="form-group col-md-6">
        <label>Country Name</label>
        <input type="text" class="form-control form-control-sm" required name="country_name"  placeholder="Enter Country Name">
        </div>
        <div class="form-group col-md-6">
        <label>Country Code</label>
        <input type="text" class="form-control form-control-sm" required name="country_code"  placeholder="Enter Country Code">
        </div>
        </div>
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

