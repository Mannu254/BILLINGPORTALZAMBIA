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
<i class="fa fa-lg fa-plus-circle"></i> Create Shipment</button>
</div>
<br><br>
<div class="container-fluid">
    <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
    <th scope="col">No</th>
    <th scope="col">Shipment No</th>
    <th scope="col">Action</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $counter = 0;
    ?>
    @foreach ($shipments as $ship)
        <tr>
    <td>{{ $counter+=1 }}</td>
    <td>{{ $ship->cShipmentNo }}</td>
    <td><a href='' class='' data-toggle='modal' data-target="#editmodal{{$ship->idShipment}}"><i style="color:rgb(255, 0, 0)" class="fa fa-lg fa-pencil-square-o"></i></a>&nbsp;&nbsp;&nbsp;&nbsp;</td>
    </tr>
    @endforeach
    @include('landedcost.modal')
    </tbody>
    </table>
    </div>



    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
        <div class="modal-header ">
        <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter Shipment Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <form action="{{ action('LandedCostController@store')}}" method="POST">
           {{ csrf_field() }}
        <div class="modal-body">
        <div class="form-row">
        <div class="form-group col-md-3">
        <label>Shipment No:</label>
        <input type="text" class="form-control form-control" required name="shipment_no"  placeholder="Enter Shipment No">
        </div>
        <div class="form-group col-md-3">
        <label>Mode of Transport:</label>
        <div class="form-group">
        <select id="trans_mode" name="trans_mode"  class="form-control form-control">
        <option type="text" class="form-control input-group " hidden=""   disabled="disabled" selected="selected" value=""><b>Select Mode</b></option>
        @foreach ($transport_mode as $mode)
        <option value="{{$mode->id}}">{{$mode->mode_name}}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Main Supplier Pickup No.</label>
        <input type="text" class="form-control form-control"  name="pick_up_no"  placeholder="Enter Pickup No">
        </div>
        <div class="form-group col-md-3">
        <label>Gross Weight (Kgs): </label>
        <input type="text" class="form-control form-control"  name="gross_weight"  placeholder="Enter Gross Weight">
        </div>
        </div>

        <div class="form-row">        
        <div class="form-group col-md-3">
        <label>Volume (CBM)</label>
        <input type="text" class="form-control form-control"  name="volume_cbm"  placeholder="Enter CBM Volume">
        </div>
        <div class="form-group col-md-3">
        <label>No. of Packages:</label>
        <input type="number" class="form-control form-control"  name="no_of_packages"  placeholder="No of Packages">
        </div>
        <div class="form-group col-md-3">
        <label>ETA @ Port [NBI/MSA]: </label>
        <input type="date" class="form-control form-control"  name="eta_port_date">
        </div>
        <div class="form-group col-md-3">
        <label>ETA @ Office:</label>
        <input type="date" class="form-control form-control" name="eta_office_date">
        </div>
        </div>

        <div class="form-row">
        <div class="form-group col-md-3">
        <label>Actual Arrival @ Port:</label>
        <input type="date" class="form-control form-control" name="arr_port_date">
        </div>
        <div class="form-group col-md-3">
        <label>Custom Entry No.</label>
        <input type="text" class="form-control form-control"  name="cust_entry_no"  placeholder="Enter Custom Entry No">
        </div>
        <div class="form-group col-md-3">
        <label>Custom Entry Date</label>
        <input type="date" class="form-control form-control"  name="cust_entry_date">
        </div>
        <div class="form-group col-md-3">
        <label>Custom Pass Date:</label>
        <input type="date" class="form-control form-control"  name="cust_pass_date">
        </div>
        </div>

        <div class="form-row">
        <div class="form-group col-md-3">
        <label>IDF No.</label>
        <input type="text" class="form-control form-control" name="idf_no"  placeholder="Enter IDF No">
        </div>
        <div class="form-group col-md-3">
        <label>How many 20FT Container(s):</label>
        <input type="number" class="form-control form-control"  name="twentft_cont">
        </div>
        <div class="form-group col-md-3">
        <label>How many 40FT Container(s):</label>
        <input type="number" class="form-control form-control" name="fourtyft_cont" >
        </div>
        <div class="form-group col-md-3">
        <label>LCL (Put 1 if yes):</label>
        <input type="text" class="form-control form-control"  name="lcl_no"  placeholder="Enter LCL No">
        </div>
        </div>

        <div class="form-row">        
        <div class="form-group col-md-3">
        <label>Clearing Agent</label>
        <input type="number" class="form-control form-control"  name="clearing_agent"  placeholder="Enter Agent Name">
        </div>
        <div class="form-group col-md-3">
        <label>Status</label>
        <div class="form-group">
        <select id="goods_status" name="goods_status"  class="form-control form-control">
        <option type="text" class="form-control input-group " hidden=""   disabled="disabled" selected="selected" value=""><b>Select Mode</b></option>
        @foreach ($goodsStatus as $status)
        <option value="{{$status->id}}">{{$status->goods_status}}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Shipment Date:</label>
        <input type="date" class="form-control form-control"  name="awb_date">
        </div>
        <div class="form-group col-md-3">
        <label>COC No.</label>
        <input type="number" class="form-control form-control" name="coc_no">
        </div>
        </div>

        <div class="form-row">       
        <div class="form-group col-md-3">
        <label>ETD Origin (Date): </label>
        <div class="form-group">
        <input type="date" class="form-control form-control"  name="etd_origin">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Payment Status:</label>
        <div class="form-group">
         <select id="payment_status" name="payment_status"  class="form-control form-control">
        <option type="text" class="form-control input-group " hidden=""   disabled="disabled" selected="selected" value=""><b>Select Mode</b></option>
        @foreach ($payment_status as $pstatus)
        <option value="{{$pstatus->id}}">{{$pstatus->payment_status}}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>USD Exchange Rate:</label>
        <input type="number" class="form-control form-control"  name="usd_exc_rate" >
        </div>
        <div class="form-group col-md-3">
        <label>EUR Exchange Rate:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control"  name="eur_exc_rate" >
        </div>
        </div>
        </div>
      
        <div class="form-row">
        <div class="form-group col-md-3">
        <label>Freight Charges USD:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control"  name="freight_charges_usd">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Freight Charges EUR: </label>
        <input type="number" class="form-control form-control" name="freight_charges_eur">
        </div>
        <div class="form-group col-md-3">
        <label>Other Charges KES: </label>
        <div class="form-group">
        <input type="date" class="form-control form-control"  name="other_charges"  placeholder="Enter Shipment No">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Insurance Charges KES:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control"  name="insurance_charges" >
        </div>
        </div>
        </div>

        <div class="form-row">        
        <div class="form-group col-md-3">
        <label>Port Charges KES:</label>
        <input type="number" class="form-control form-control"  name="port_charges">
        </div>
        <div class="form-group col-md-3">
        <label>Agency Fees KES:  </label>
        <div class="form-group">
        <input type="number" class="form-control form-control"  name="agency_fee">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>KEBS Fees KES</label>
        <div class="form-group">
        <input type="number" class="form-control form-control"  name="mode_tran">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>AWB/BL No.</label>
        <input type="number" class="form-control form-control"  name="awb_no">
        </div>
        </div>
        

        <div class="form-row">
        <div class="form-group col-md-2">
        <label>MI PO No. </label>
        <div class="form-group">
        <input type="date" class="form-control form-control"  name="mi_po_no">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Main Supplier PI No.:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control"  name="main_sup_piNo">
        </div>
        </div>
        <div class="form-group col-md-2">
        <label>Main Supplier PI Date: </label>
        <input type="date" class="form-control form-control"  name="main_sup_piDate" >
        </div>
        <div class="form-group col-md-3">
        <label>Main Supplier CI No.</label>
        <div class="form-group">
        <input type="text" class="form-control form-control"  name="main_sup_ciNo">
        </div>
        </div>
        <div class="form-group col-md-2">
        <label>Main Supplier CI Date:</label>
        <div class="form-group">
        <input type="date" class="form-control form-control"  name="main_sup_ciDate">
        </div>
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

