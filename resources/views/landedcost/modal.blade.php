
    <div class="modal fade" id="editmodal{{$ship->idShipment ?? ''}}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
        <div class="modal-header ">
        <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter Shipment Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
        </button>
        </div>
        <form action="{{ action('LandedCostController@update',$ship->idShipment)}}" method="POST">
           {{ csrf_field() }}
        <div class="modal-body">
        <div class="form-row">
        <div class="form-group col-md-3">
        <label>Shipment No:</label>
        <input type="text" class="form-control form-control" required name="shipment_no" value="{{$ship->cShipmentNo ?? ''}}"  placeholder="Enter Shipment No">
        </div>
        <div class="form-group col-md-3">
        <label>Mode of Transport:</label>
        <div class="form-group">
        <select id="trans_mode" name="trans_mode"  class="form-control form-control">
        <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Mode</b></option>
        @foreach ($transport_mode as $mode)
        <option value="{{ $mode->id }}"{{ ( $ship->trans_mode == $mode->id) ? 'selected' : '' }}>{{$mode->mode_name }}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Main Supplier Pickup No.</label>
        <input type="text" class="form-control form-control" value="{{$ship->MainSuppPickupNo ?? ''}}" name="pick_up_no"  placeholder="Enter Pickup No">
        </div>
        <div class="form-group col-md-3">
        <label>Gross Weight (Kgs): </label>
        <input type="text" class="form-control form-control" value="{{$ship->fGrossWtKg ?? ''}}"  name="gross_weight"  placeholder="Enter Gross Weight">
        </div>
        </div>

        <div class="form-row">        
        <div class="form-group col-md-3">
        <label>Volume (CBM)</label>
        <input type="text" class="form-control form-control" value="{{$ship->fVolumeCbm ?? ''}}"  name="volume_cbm"  placeholder="Enter CBM Volume">
        </div>
        <div class="form-group col-md-3">
        <label>No. of Packages:</label>
        <input type="number" class="form-control form-control" value="{{$ship->iPackages ?? ''}}" name="no_of_packages"  placeholder="No of Packages">
        </div>
        <div class="form-group col-md-3">
        <label>ETA @ Port [NBI/MSA]: </label>
        <input type="date" class="form-control form-control" value="{{$ship->dETAPort ? \Carbon\Carbon::parse($ship->dETAPort )->format('Y-m-d') : null}}" name="eta_port_date">
        </div>
        <div class="form-group col-md-3">
        <label>ETA @ Office:</label>
        <input type="date" class="form-control form-control" value="{{$ship->dETAOffice ? \Carbon\Carbon::parse($ship->dETAOffice)->format('Y-m-d'): null}}" name="eta_office_date">
        </div>
        </div>

        <div class="form-row">
        <div class="form-group col-md-3">
        <label>Actual Arrival @ Port:</label>
        <input type="date" class="form-control form-control" value="{{$ship->dactualport ? \Carbon\Carbon::parse($ship->dactualport )->format('Y-m-d'): null}}" name="arr_port_date">
        </div>
        <div class="form-group col-md-3">
        <label>Custom Entry No.</label>
        <input type="text" class="form-control form-control" value="{{$ship->cCustomEntryNo ?? ''}}" name="cust_entry_no"  placeholder="Enter Custom Entry No">
        </div>
        <div class="form-group col-md-3">
        <label>Custom Entry Date</label>
        <input type="date" class="form-control form-control"  value="{{$ship->dCustomEntryDate ? \Carbon\Carbon::parse($ship->dCustomEntryDate )->format('Y-m-d') : null}}" name="cust_entry_date">
        </div>
        <div class="form-group col-md-3">
        <label>Custom Pass Date:</label>
        <input type="date" class="form-control form-control" value="{{$ship->dCustomPassDate ? \Carbon\Carbon::parse($ship->dCustomPassDate )->format('Y-m-d') : null}}"  name="cust_pass_date">
        </div>
        </div>

        <div class="form-row">
        <div class="form-group col-md-3">
        <label>IDF No.</label>
        <input type="text" class="form-control form-control" value="{{$ship->cIDFNo ?? ''}}" name="idf_no"  placeholder="Enter IDF No">
        </div>
        <div class="form-group col-md-3">
        <label>How many 20FT Container(s):</label>
        <input type="number" class="form-control form-control" value="{{$ship->i20ft ?? ''}}"  name="twentft_cont">
        </div>
        <div class="form-group col-md-3">
        <label>How many 40FT Container(s):</label>
        <input type="number" class="form-control form-control" value="{{$ship->i40ft ?? ''}}" name="fourtyft_cont" >
        </div>
        <div class="form-group col-md-3">
        <label>LCL (Put 1 if yes):</label>
        <input type="text" class="form-control form-control" value="{{$ship->ilcl ?? ''}}" name="lcl_no"  placeholder="Enter LCL No">
        </div>
        </div>

        <div class="form-row">        
        <div class="form-group col-md-3">
        <label>Clearing Agent</label>
        <input type="number" class="form-control form-control" value="{{$ship->ccagent ?? ''}}" name="clearing_agent"  placeholder="Enter Agent Name">
        </div>
        <div class="form-group col-md-3">
        <label>Status</label>
        <div class="form-group">
        <select id="goods_status" name="goods_status"  class="form-control form-control">
        <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Mode</b></option>
        @foreach ($goodsStatus as $gstatus)
        <option value="{{ $gstatus->id }}"{{ ( $ship->trans_mode == $gstatus->id) ? 'selected' : '' }}>{{$gstatus->goods_status }}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Shipment Date:</label>
        <input type="date" class="form-control form-control" value="{{$ship->dshipmentdate ? \Carbon\Carbon::parse($ship->dshipmentdate )->format('Y-m-d'): null}}" name="awb_date">
        </div>
        <div class="form-group col-md-3">
        <label>COC No.</label>
        <input type="number" class="form-control form-control" value="{{$ship->ccocno ?? ''}}" name="coc_no">
        </div>
        </div>

        <div class="form-row">       
        <div class="form-group col-md-3">
        <label>ETD Origin (Date): </label>
        <div class="form-group">
        <input type="date" class="form-control form-control" value="{{$ship->detdorigin ? \Carbon\Carbon::parse($ship->detdorigin )->format('Y-m-d') : null}}" name="etd_origin">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Payment Status:</label>
        <div class="form-group">
        <select id="payment_status" name="payment_status"  class="form-control form-control">
        <option type="text" class="form-control input-group" hidden=""  disabled="disabled" selected="selected" value=""><b>Select Mode</b></option>
        @foreach ($payment_status as $pstatus)
        <option value="{{ $pstatus->id }}"{{ ( $ship->payment_status == $pstatus->id) ? 'selected' : '' }}>{{$pstatus->payment_status }}</option>
        @endforeach  
        </select>
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>USD Exchange Rate:</label>
        <input type="number" class="form-control form-control" value="{{$ship->fexchrateUSD ?? ''}}" name="usd_exc_rate" >
        </div>
        <div class="form-group col-md-3">
        <label>EUR Exchange Rate:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->fexchrateEUR ?? ''}}" name="eur_exc_rate" >
        </div>
        </div>
        </div>
      
        <div class="form-row">
        <div class="form-group col-md-3">
        <label>Freight Charges USD:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->ffreightchgsUSD ?? ''}}" name="freight_charges_usd">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Freight Charges EUR: </label>
        <input type="number" class="form-control form-control" value="{{$ship->ffreightchgsEUR ?? ''}}" name="freight_charges_eur">
        </div>
        <div class="form-group col-md-3">
        <label>Other Charges KES: </label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->fotherchgsHOME ?? ''}}" name="other_charges"  placeholder="Enter Shipment No">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Insurance Charges KES:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->finsurancechgsHOME ?? ''}}" name="insurance_charges" >
        </div>
        </div>
        </div>

        <div class="form-row">        
        <div class="form-group col-md-3">
        <label>Port Charges KES:</label>
        <input type="number" class="form-control form-control" value="{{$ship->fportchgsHOME ?? ''}}" name="port_charges">
        </div>
        <div class="form-group col-md-3">
        <label>Agency Fees KES:  </label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->fagencyfeesHOME ?? ''}}" name="agency_fee">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>KEBS Fees KES</label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->fKEBSfeesHOME ?? ''}}"  name="mode_tran">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>AWB/BL No.</label>
        <input type="number" class="form-control form-control" value="{{$ship->cawbblno ?? ''}}" name="awb_no">
        </div>
        </div>
        

        <div class="form-row">
        <div class="form-group col-md-2">
        <label>MI PO No. </label>
        <div class="form-group">
        <input type="text" class="form-control form-control" value="{{$ship->MIPONo ?? ''}}"  name="mi_po_no">
        </div>
        </div>
        <div class="form-group col-md-3">
        <label>Main Supplier PI No.:</label>
        <div class="form-group">
        <input type="number" class="form-control form-control" value="{{$ship->MainSuppPINo ?? ''}}"  name="main_sup_piNo">
        </div>
        </div>
        <div class="form-group col-md-2">
        <label>Main Supplier PI Date: </label>
        <input type="date" class="form-control form-control" value="{{$ship->MainSuppPIDate ? \Carbon\Carbon::parse($ship->MainSuppPIDate )->format('Y-m-d') :null}}"  name="main_sup_piDate" >
        </div>
        <div class="form-group col-md-3">
        <label>Main Supplier CI No.</label>
        <div class="form-group">
        <input type="text" class="form-control form-control" value="{{$ship->MainSuppCINo ?? ''}}"  name="main_sup_ciNo">
        </div>
        </div>
        <div class="form-group col-md-2">
        <label>Main Supplier CI Date:</label>
        <div class="form-group">
        <input type="date" class="form-control form-control" value="{{$ship->MainSuppCIDate ? \Carbon\Carbon::parse($ship->MainSuppCIDate)->format('Y-m-d') :null}}"  name="main_sup_ciDate">
        </div>
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