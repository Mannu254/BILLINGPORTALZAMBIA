<?php

namespace App\Http\Controllers;

use App\GoodsStatus;
use App\_cplshipment;
use App\TransportMode;
use App\payment_status;
use Illuminate\Http\Request;
use DB;
class LandedCostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $shipments =DB::table('invnum')
        ->select('invnumber')
          ->where('invnum.doctype','=',5)
          ->where('invnum.docstate','=',4)
       
        
         ->get();

       
    //   dd($shipments);
    //  return $shipments;
        
      return view('landedcost.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    $this->validate($request,[
        'shipment_no' => 'required|unique:_cplshipment,cShipmentNo',

    ]);

    $shipment = new _cplshipment;
    $shipment ->cShipmentNo =$request -> input('shipment_no');
    $shipment ->cMode =$request -> input('trans_mode');
    $shipment ->dETAPort=$request -> input('eta_port_date');
    $shipment ->dETAOffice=$request -> input('eta_office_date');
    $shipment ->fGrossWtKg=$request -> input('gross_weight');
    $shipment ->fVolumeCbm=$request -> input('volume_cbm');
    $shipment ->iPackages=$request -> input('no_of_packages');
    $shipment ->cCustomEntryNo=$request -> input('cust_entry_no');
    $shipment ->dCustomEntryDate=$request -> input('cust_entry_date');
    $shipment ->dCustomPassDate=$request -> input('cust_pass_date');
    $shipment ->cIDFNo=$request -> input('idf_no');
    $shipment ->i20ft=$request -> input('twentft_cont');
    $shipment ->i40ft=$request -> input('fourtyft_cont');
    $shipment ->ilcl=$request -> input('lcl_no');
    $shipment ->ccagent=$request -> input('clearing_agent');
    $shipment ->cstatus=$request -> input('goods_status');
    $shipment ->dactualport=$request -> input('arr_port_date');
    $shipment ->dshipmentdate=$request -> input('awb_date');
    $shipment ->ccocno=$request -> input('coc_no');
    $shipment ->detdorigin=$request -> input('etd_origin');
    $shipment ->cpaymentstatus=$request -> input('payment_status');
    $shipment ->fotherchgsHOME=$request -> input('other_charges');
    $shipment ->fexchrateUSD=$request -> input('usd_exc_rate');
    $shipment ->fexchrateEUR=$request -> input('eur_exc_rate');
    $shipment ->finsurancechgsHOME=$request -> input('insurance_charges');
    $shipment ->fportchgsHOME=$request -> input('port_charges');
    $shipment ->fagencyfeesHOME=$request -> input('agency_fee');
    $shipment ->fKEBSfeesHOME=$request -> input('mode_tran');
    $shipment ->ffreightchgsUSD=$request -> input('freight_charges_usd');
    $shipment ->ffreightchgsEUR=$request -> input('freight_charges_eur');
    $shipment ->cawbblno=$request -> input('awb_no');
    $shipment ->MIPONo=$request -> input('mi_po_no');
    $shipment ->MainSuppPINo=$request -> input('main_sup_piNo');
    $shipment ->MainSuppPIDate=$request -> input('main_sup_piDate');
    $shipment ->MainSuppCINo=$request -> input('main_sup_ciNo');
    $shipment ->MainSuppCIDate=$request -> input('main_sup_ciDate');
    $shipment ->MainSuppPickupNo =$request -> input('pick_up_no');    
    $shipment ->save();

    return redirect()->back()->with('success','Shipment Created Successfully');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'shipment_no' => 'required',
    
        ]);

        $shipment_update =_cplshipment::find($id);
        $shipment_update ->cShipmentNo =$request -> input('shipment_no');
        $shipment_update ->cMode =$request -> input('trans_mode');
        $shipment_update ->dETAPort=$request -> input('eta_port_date');
        $shipment_update ->dETAOffice=$request -> input('eta_office_date');
        $shipment_update ->fGrossWtKg=$request -> input('gross_weight');
        $shipment_update ->fVolumeCbm=$request -> input('volume_cbm');
        $shipment_update ->iPackages=$request -> input('no_of_packages');
        $shipment_update ->cCustomEntryNo=$request -> input('cust_entry_no');
        $shipment_update ->dCustomEntryDate=$request -> input('cust_entry_date');
        $shipment_update ->dCustomPassDate=$request -> input('cust_pass_date');
        $shipment_update ->cIDFNo=$request -> input('idf_no');
        $shipment_update ->i20ft=$request -> input('twentft_cont');
        $shipment_update ->i40ft=$request -> input('fourtyft_cont');
        $shipment_update ->ilcl=$request -> input('lcl_no');
        $shipment_update ->ccagent=$request -> input('clearing_agent');
        $shipment_update ->cstatus=$request -> input('goods_status');
        $shipment_update ->dactualport=$request -> input('arr_port_date');
        $shipment_update ->dshipmentdate=$request -> input('awb_date');
        $shipment_update ->ccocno=$request -> input('coc_no');
        $shipment_update ->detdorigin=$request -> input('etd_origin');
        $shipment_update ->cpaymentstatus=$request -> input('payment_status');
        $shipment_update ->fotherchgsHOME=$request -> input('other_charges');
        $shipment_update ->fexchrateUSD=$request -> input('usd_exc_rate');
        $shipment_update ->fexchrateEUR=$request -> input('eur_exc_rate');
        $shipment_update ->finsurancechgsHOME=$request -> input('insurance_charges');
        $shipment_update ->fportchgsHOME=$request -> input('port_charges');
        $shipment_update ->fagencyfeesHOME=$request -> input('agency_fee');
        $shipment_update ->fKEBSfeesHOME=$request -> input('mode_tran');
        $shipment_update ->ffreightchgsUSD=$request -> input('freight_charges_usd');
        $shipment_update ->ffreightchgsEUR=$request -> input('freight_charges_eur');
        $shipment_update ->cawbblno=$request -> input('awb_no');
        $shipment_update ->MIPONo=$request -> input('mi_po_no');
        $shipment_update ->MainSuppPINo=$request -> input('main_sup_piNo');
        $shipment_update ->MainSuppPIDate=$request -> input('main_sup_piDate');
        $shipment_update ->MainSuppCINo=$request -> input('main_sup_ciNo');
        $shipment_update ->MainSuppCIDate=$request -> input('main_sup_ciDate');
        $shipment_update ->MainSuppPickupNo =$request -> input('pick_up_no');    
        $shipment_update ->save();
    
        return redirect()->back()->with('success','Shipment Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function shipment(){
        $shipments =_cplshipment::
        select('_cplShipment.*','gs.id as good_status','ps.id as payment_status','tm.id as trans_mode')
        ->join('goods_statuses As gs', '_cplShipment.cstatus', '=', 'gs.id')  
        ->join('payment_status As ps', '_cplShipment.cpaymentstatus', '=', 'ps.id') 
        ->join('transport_modes As tm', '_cplShipment.cMode', '=', 'tm.id')         
        ->get();
        // dd( $shipments);

        $goodsStatus =GoodsStatus::all();
        $payment_status =payment_status::all();
        $transport_mode =TransportMode::all();
       
     return view('landedcost.shipment',compact('shipments','goodsStatus','payment_status','transport_mode'));
    }
}
