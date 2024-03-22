<?php

namespace App\Http\Controllers;

use App\Client;
use Carbon\Carbon;
use App\_smtblrateslab;
use App\_smtblperiodservice;
use Illuminate\Http\Request;
use App\Exports\AssetsExport;
use App\Exports\ContractExport;
use App\Imports\MDSAssetUpdate;
use App\Exports\EscalationExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;

class BillingManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
     return view('MDS.index');
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

    public function mds_contract_update(){
        $templates =DB::table('_smtblContractMatrix')
        ->whereIn('AutoIdx',[1878,2138,550,551,552,1877,2204,2355,2399,2422,2450,2463,2541,2571,2606,2629,3750,3753,3793,3807,3850,3858,3876,3905,4001,3991])
        ->select('AutoIdx','cCode','cDescription')
        ->get();
        return view('MDS.contract_update',compact('templates'));
        
    }

    public function mds_asset_export(Request $request){
       
        $assets =DB::table('_smtblServiceAsset')
        ->join('Client', function ($join) {
            $join->on('Client.DClink','=','_smtblServiceAsset.iCustomerId');
               
        })
        ->where('ucSABillingAsset',$request->con_asset)
        ->select('ucSABillingAsset','ucSASerialNo','Name','Account')    
        ->get();
    
        return Excel::download(new AssetsExport($assets),'CustomerAssets.xlsx');

    }

    public function mds_update_asset(Request $request){

        $validator = Validator::make(
            [
                'file'      => $request->file,
                'extension' => strtolower($request->file->getClientOriginalExtension()),
            ],
            [
                'file'          => 'required',
                'extension'      => 'required|in:xlsx, xls,csv',
            ]
            );
            if ($validator->fails()) {
            return redirect()->back()->with('error','Invalid File Extension use xlsx,xls Only');
                    
            }
            try{
    
                 
                
               $import = Excel::import(new MDSAssetUpdate(),request()->file('file'));              
                                      
                           
               
             }
             catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();
                             
            
                                                
                foreach ($failures as $errors) {
                    $errors->row(); // row that went wrong
                    $errors->attribute(); // either heading key (if using heading row concern) or column index
                    $errors->errors(); // Actual error messages from Laravel validator
                    $errors->values(); // The values of the row that has failed.
            
                foreach($errors->errors() as $error){
                return redirect()->back()->with('error',$error); 
                            
            
                }
                   
                    
                }

                                
      
            }
            return redirect()->back()->with('success', 'Assets Updated  Successfully');
        }


        public function mds_contract_search(Request $request){

            $contract_hint =$request->contract_hint;
    
            
                 
           
            
            $contract =DB::table('_smtblContractMatrix')
            ->select('_smtblContractMatrix.*','Account','Name')
             
             ->join("Client", function ($join) {
                $join->on('Client.DClink','=','_smtblContractMatrix.iCustomerId');
                   
            })
            ->where('_smtblContractMatrix.cCode',$contract_hint)
            ->first();

          
    
           
            $template = DB::table('_smtblContractMatrix')
            ->where('AutoIdx',$contract->iContractTemplateId)
            ->first();
    
    
            $contract->template =$template;
    
                          
    
                return response()->json($contract);
        }

        public function mds_contract_date_change(Request $request){

            $sdate_req = $request->sdate;
            $temp_id = $request->template_id;
    
            $sdate =Carbon::createFromFormat('d/m/Y', $sdate_req)->format('d-m-Y');
              
    
            
           
    
             $contract_temp =DB::table('_smtblContractMatrix')
             ->select('iContractLenType','iContractLen','iReviewPeriodType','iReviewPeriod')
            ->where('AutoIdx',$temp_id)        
            ->first();

            
            
            $sdate_c = Carbon::parse($sdate); 
              
            $sdate_r = Carbon::parse($sdate); 
    
    
            //days
            if($contract_temp->iContractLenType == 0){           
    
                $new_end_date =$sdate_c->addDays($contract_temp->iContractLen);
                
            }
            //weeks
            if($contract_temp->iContractLenType == 1){         
                $new_end_date =$sdate_c->addWeeks($contract_temp->iContractLen)->modify('-1 day');
    
                
            }
            //months
            if($contract_temp->iContractLenType == 2){
               
    
                $new_end_date =$sdate_c->addMonths($contract_temp->iContractLen)->modify('-1 day');
                
                
    
                
            }
            //years
            if($contract_temp->iContractLenType == 3){
              
    
                $new_end_date =$sdate_c->addYear($contract_temp->iContractLen)->modify('-1 day');          
    
                
            }
    
    
            
            if($contract_temp->iReviewPeriodType == 0){
               
    
                $new_rev_date =$sdate_r->addDays($contract_temp->iReviewPeriod);
               
    
            }
           
    
            if($contract_temp->iReviewPeriodType == 1){
                
    
                $new_rev_date =$sdate_r->addWeeks($contract_temp->iReviewPeriod);
                
            }
            if($contract_temp->iReviewPeriodType == 2){
               
    
                $new_rev_date =$sdate_r->addMonths($contract_temp->iReviewPeriod);
                
            }
            if($contract_temp->iReviewPeriodType == 3){                    
    
                $new_rev_date =$sdate_r->addYear($contract_temp->iReviewPeriod);            
                
                
            }
    
            
            
            $contract_temp->new_rev_date=$new_rev_date;
            $contract_temp->new_end_date=$new_end_date;
    
        
            return response()->json($contract_temp);
    
            
            
       }

       public function mds_contract_update_date(Request $request){ 

        $sdate =Carbon::createFromFormat('d/m/Y',$request->sdate)->format('m-d-Y');
        $edate=Carbon::createFromFormat('d/m/Y',$request->edate)->format('m-d-Y');
        $rdate=Carbon::createFromFormat('d/m/Y',$request->rdate)->format('m-d-Y');
       
        
        
        $code =$request->code;
        $template_id =$request->template_id;
    
        $update =DB::table('_smtblContractMatrix')
              ->where('cCode',$code)
              ->update([
              'dReviewDate' => $rdate,
              'dStartDate' =>  $sdate,
              'dEndDate' => $edate,
              'iContractTemplateId' => $template_id,
        ]);
    
    //     $update_assets =DB::table('_smtblServiceAsset')
    //     ->where('ucSABillingAsset',$code)
    //     ->update([
        
    //     'udSAContractStart' =>  $sdate,
    //     'udSAContractEnd' => $edate,
    //     '_smtblServiceAsset_dModifiedDate'=>Carbon::Now(),
        
    // ]);
    
    
    
    
        if($update){
    
            return response()->json(['success'=>'Contract Updated successfully.'],200);
        }
        else{
    
            return response()->json(['error'=>'Contract Update failed!!.Contact Administrator'], 400);
        }
    
    
    
    
    
       }

       public function mds_service_assets(){


        return view('MDS.service_assets');

       }

       public function mds_cust_assets(Request $request){
       
        if($request->ajax()){  
            $hint =$request->hint;            
            
            $assets =DB::table('_smtblServiceAsset')
            ->join('Client', function ($join) {
                $join->on('Client.DClink','=','_smtblServiceAsset.iCustomerId');
                   
            })
            ->leftjoin('_smtblContractMatrix', function ($join) {
                $join->on('_smtblServiceAsset.ucSABillingAsset','=','_smtblContractMatrix.cCode');
                   
            })
            ->where('_smtblServiceAsset.CDescription','not like', "%Service%")
            ->Where(function($query) use ($hint)
            {
                $query->where('cSerialNo','like', '%'.$hint.'%')
                ->orwhere('ucSASerialNo','like', '%'.$hint.'%')
                ->orwhere('ucSABillingAsset',$hint)
                ->orwhere('_smtblContractMatrix.cCode',$hint)
                ->orWhere('Name', 'like', '%'.$hint.'%')
                ->orWhere('_smtblServiceAsset.Ccode', 'like', '%'.$hint.'%');
            })

    
            ->select('ucSABillingAsset','ucSASerialNo','Name','_smtblServiceAsset.cCode as ConCode','_smtblContractMatrix.dStartDate as Sdate','_smtblContractMatrix.dEnddate as Edate','_smtblServiceAsset.CDescription as Desc','cLocation')    
            ->get();

            
    
        }
       
    
        return response()->json($assets);
    
    
       }

       public function mds_cost_click(){

        return view ('MDS.cost');
       }


       public function cont_code(Request $request){

        $con_code =$request->hint;

        $asset_id = DB::table('_smtblServiceAsset As sa')
        ->where('cCode',$con_code)
        ->select('AutoIdx')
        ->first(); 

      

        $counter_elapse =DB::table('_smtblcounterelapsed')
        ->join('_smtblServiceAsset','_smtblcounterelapsed.iServiceAssetId', '=', '_smtblServiceAsset.AutoIdx')
        ->join('Client', 'Client.DClink', '=', '_smtblServiceAsset.iCustomerId') 
        ->where('iServiceAssetId',$asset_id->AutoIdx)
        ->select('_smtblcounterelapsed.AutoIdx as AIdx','Name','iServiceAssetId','_smtblcounterelapsed.cCode as ctype','_smtblcounterelapsed.cDescription as desc')
        ->get(); 

              
                  
         return response()->json($counter_elapse);
        
       }


       public function slabs_rate(Request $request){
        if($request->ajax()){

            $billingID =$request->dataId;

            $billing_rates = _smtblrateslab::where('iBillingID',$billingID)
            ->select('AutoIdx','iFromQty','iToqty','frate')
            ->get(); 

           return response()->json($billing_rates);



         }


        
       }

       public function update_rates(Request $request){
        $value_array =$request->values;

        foreach($value_array as $key => $value){
            $update =_smtblrateslab::
            where('AutoIdx',$value['id'])
            ->update([
            'frate' => $value['rate'],
            'iFromQty' => $value['fromqty'],
            'iToqty' => $value['toqty'],
           ]);
           


        }
        return response()->json(['success'=>'Rates Updated successfully.'],200);
        

       }

       public function rental_charges(){

        return view('MDS.rental');
       }

       public function rental_cont_code(Request $request){
        $con_code =$request->hint;

        $asset_id = DB::table('_smtblServiceAsset As sa')
        ->where('cCode',$con_code)
        ->select('AutoIdx')
        ->first(); 

        $rentamt =DB::table('_smtblperiodservice As ps')
            ->where('iServiceAssetId',$asset_id->AutoIdx )
            ->Where(function($query)
            {
                $query->where('ps.cCode','RENTAL')
                ->orwhere('ps.cCode','RENTAL CHARGES');
            }) 
            ->join('_smtblServiceAsset','ps.iServiceAssetId', '=', '_smtblServiceAsset.AutoIdx')
        ->join('Client', 'Client.DClink', '=', '_smtblServiceAsset.iCustomerId')          
            ->select('ps.AutoIdx as Idx','fAmount','_smtblServiceAsset.cCode as code','ps.cDescription as desc','Name')
            ->get();

          
             return response()->json($rentamt);

          
        

       }

       public function update_rental(Request $request){

        $data_id =$request->data_id;
        $amount =$request->amount;


        $update =_smtblperiodservice::
        where('AutoIdx',$data_id)
        ->update([
        'fAmount' => $amount        
       ]);

       if($update){

        return response()->json(['success'=>'Rental Charges Updated successfully.'],200);
       }




       }

       public function contract_rates_report(){

        return view('MDS.contracts');
       }

       public function export_rates(Request $request){
        $contracts = DB::table('_bvARAccountsFull As cl')
              
        ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
       
        DB::raw('MAX(CASE WHEN sa.ucSABillingAsset IS NULL THEN sa.ccode ELSE sa.ucSABillingAsset END) AS billingasset'),
        DB::raw("MAX(CASE WHEN sa.ucSABillingAsset IS NULL THEN sa.cdescription ELSE 'Billed Asset' END) AS assetdesc")      
         )
        ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
                 
        ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',DB::raw('CASE WHEN sa.ucSABillingAsset IS NULL THEN sa.ccode ELSE sa.ucSABillingAsset END'))
        ->where('sa.cCode', 'NOT LIKE', 'BP%') 
        ->where('sa.cDescription', 'NOT LIKE', 'KIS%')
        ->where('sa.cDescription', 'NOT LIKE', 'Bil%')
        ->get();
       foreach($contracts as $con){

        $asset_id = DB::table('_smtblServiceAsset As sa')
        ->where('cCode',$con->billingasset)
        ->where('sa.cCode', 'NOT LIKE', 'BP%') 
        ->where('sa.cDescription', 'NOT LIKE', 'KIS%')
        ->where('sa.cDescription', 'NOT LIKE', 'Bil%')
         ->select('AutoIdx')
        ->first(); 

       

     
        // get the billing id
        $counter_elapse =DB::table('_smtblcounterelapsed')
        ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
        ->Where(function($query)
        {
            $query->where('cCode','BILLMON')
            ->orwhere('cCode','BILMON')
            ->orwhere('cCode','BILLCOL')
            ->orwhere('cCode','BILCOL');
            
        })
        ->pluck('AutoIdx');  
    
        
       
        $slabs = _smtblrateslab::whereIn('iBillingID',$counter_elapse)
        ->select('iFromQty','iToqty','frate','fRate','cDescription')
        ->orderBy('cDescription','desc')
        ->get();

        $con->slabs =$slabs;

           


        
       

    }

            
        return Excel::download(new ContractExport($contracts),'ContractsRates.xlsx');

    
        



       
       }
       public function export_rates_ecalation(){

        $today=Carbon::parse('2022-06-30');
        $billing=DB::table('_btblInvoiceLines')
        ->join('InvNum as inv','_btblInvoiceLines.iInvoiceID','=','inv.AutoIndex')
        ->join('Client as cl','cl.DCLink','=','inv.AccountID')
        ->where('inv.doctype', '=',4) 
        ->where('inv.DocState', '!=',7)
        
        ->Where(function ($query) {
            $query->where('ucIDSOrdTxCMRates','!=','')
                   ->orwhere('ucIDSOrdTxCMMeterType','=','RENTAL')
                   ->orwhere('ucIDSOrdTxCMMeterType','RENTAL CHARGES');
        })
      
         ->whereYear('dDeliveryDate', '=', $today->year)
        ->whereMonth('dDeliveryDate', '=', $today->month) 
      

        ->select('iInvoiceID','ucIDSOrdTxCMServiceAsset','ucIDSOrdTxCMRates','ucIDSOrdTxCMMeterType','udIDSOrdTxCMReadingDate','Name','Account','uiIDSOrdTxCMPrevReading','uiIDSOrdTxCMCurrReading','ucIDSOrdTxCMMinVol','fQuantityLineTotExcl')
        ->get(); 

       

      
        

       
       

       foreach( $billing as $bil){
        if($bil->ucIDSOrdTxCMMeterType =='BILLMON' || $bil->ucIDSOrdTxCMMeterType =='BILMON'){
        $today_p = Carbon::parse('2022-06-30')->subMonthsNoOverflow()->endOfMonth(); 

       
        $billing_p=DB::table('_btblInvoiceLines')
        ->where('ucIDSOrdTxCMMeterType','=','BILLMON')
        ->join('InvNum as inv','_btblInvoiceLines.iInvoiceID','=','inv.AutoIndex')
         ->where('inv.doctype', '=',4)
         ->where('inv.DocState', '!=',7)
        ->where('ucIDSOrdTxCMServiceAsset','=',$bil->ucIDSOrdTxCMServiceAsset)
        ->whereYear('dDeliveryDate', '=', $today_p->year)
        ->whereMonth('dDeliveryDate', '=', $today_p->month)  

        ->select('ucIDSOrdTxCMRates as prate','ucIDSOrdTxCMMinVol as pMinVol','fQuantityLineTotExcl as PTotAmount','dDeliveryDate','inv.AutoIndex')
        ->selectraw('uiIDSOrdTxCMCurrReading - uiIDSOrdTxCMPrevReading AS preading')
        ->first();

         
        

            $bil->prate =$billing_p->prate ?? '';
            $bil->preading =$billing_p->preading ?? '';            
            $bil->pAmount =$billing_p->PTotAmount ?? '';
            $bil->pMinVol =$billing_p->pMinVol ?? '';
            $bil->PTotAmount =$billing_p->PTotAmount ?? '';

        }
        // scan reading
        else if($bil->ucIDSOrdTxCMMeterType =='BILLSCN'){
            $today_p = Carbon::parse('2022-06-30')->subMonthsNoOverflow()->endOfMonth(); 
    
           
            $billing_p=DB::table('_btblInvoiceLines')
            ->where('ucIDSOrdTxCMMeterType','=','BILLSCN')
            ->join('InvNum as inv','_btblInvoiceLines.iInvoiceID','=','inv.AutoIndex')
             ->where('inv.doctype', '=',4)
             ->where('inv.DocState', '!=',7)
            ->where('ucIDSOrdTxCMServiceAsset','=',$bil->ucIDSOrdTxCMServiceAsset)
            ->whereYear('dDeliveryDate', '=', $today_p->year)
            ->whereMonth('dDeliveryDate', '=', $today_p->month)  
    
            ->select('ucIDSOrdTxCMRates as prate','ucIDSOrdTxCMMinVol as pMinVol','fQuantityLineTotExcl as PTotAmount','dDeliveryDate')
            ->selectraw('uiIDSOrdTxCMCurrReading - uiIDSOrdTxCMPrevReading AS preading')
            ->first();
            
    
                $bil->prate =$billing_p->prate ?? '';
                $bil->preading =$billing_p->preading ?? '';            
                $bil->pAmount =$billing_p->PTotAmount ?? '';
                $bil->pMinVol =$billing_p->pMinVol ?? '';
                $bil->PTotAmount =$billing_p->PTotAmount ?? '';

             
    
            }

           






        else if($bil->ucIDSOrdTxCMMeterType =='RENTAL' || $bil->ucIDSOrdTxCMMeterType =='RENTAL CHARGES'){
            $today_p = Carbon::parse('2022-06-30')->subMonthsNoOverflow()->endOfMonth(); 
            $billing_p=DB::table('_btblInvoiceLines')
            ->join('InvNum as inv','_btblInvoiceLines.iInvoiceID','=','inv.AutoIndex')
            ->where('inv.doctype', '=',4)
             ->where('inv.DocState', '!=',7)
            ->where('ucIDSOrdTxCMMeterType','=','RENTAL')
            ->where('ucIDSOrdTxCMServiceAsset','=',$bil->ucIDSOrdTxCMServiceAsset)
            ->whereYear('dDeliveryDate', '=', $today_p->year)
            ->whereMonth('dDeliveryDate', '=', $today_p->month)  
    
            ->select('fQuantityLineTotExcl as PRental_tAmount')
            
            ->first();
    
                $bil->PRental_tAmount =$billing_p->PRental_tAmount ?? '';
                 $bil->PTotAmount =$billing_p->PRental_tAmount ?? '';

            // current rental
            $today=Carbon::parse('2022-06-30');
            $billing_c=DB::table('_btblInvoiceLines')
            ->join('InvNum as inv','_btblInvoiceLines.iInvoiceID','=','inv.AutoIndex')
             ->where('inv.doctype', '=',4)
             ->where('inv.DocState', '!=',7)
            ->where('ucIDSOrdTxCMMeterType','=','RENTAL')            
            ->where('ucIDSOrdTxCMServiceAsset','=',$bil->ucIDSOrdTxCMServiceAsset)
            ->whereYear('dDeliveryDate', '=', $today->year)
            ->whereMonth('dDeliveryDate', '=', $today->month)  
    
            ->select('fQuantityLineTotIncl as CRental_tAmount')
            
            ->first();

            $bil->CRental_tAmount =$billing_c->CRental_tAmount ?? '';
            // $bil->fQuantityLineTotIncl =$billing_c->CRental_tAmount ?? '';

               
    

        }
        else{

        $today_p = Carbon::parse('2022-06-30')->subMonthsNoOverflow()->endOfMonth(); 
       
        $billing_p=DB::table('_btblInvoiceLines')
        ->join('InvNum as inv','_btblInvoiceLines.iInvoiceID','=','inv.AutoIndex')
         ->where('inv.doctype', '=',4)
         ->where('inv.DocState', '!=',7)
        ->where('ucIDSOrdTxCMRates','!=','')
        ->where('ucIDSOrdTxCMServiceAsset','=',$bil->ucIDSOrdTxCMServiceAsset)
        ->where('ucIDSOrdTxCMMeterType','=','BILLCOL')
        ->orwhere('ucIDSOrdTxCMMeterType','=','BILCOL')
        ->whereYear('dDeliveryDate', '=', $today_p->year)
       ->whereMonth('dDeliveryDate', '=', $today_p->month)  
       ->select('ucIDSOrdTxCMRates as prate','ucIDSOrdTxCMMinVol as pMinVol','fQuantityLineTotExcl as PTotAmount','inv.OrderNum')
       ->selectraw('uiIDSOrdTxCMCurrReading - uiIDSOrdTxCMPrevReading AS preading')
       ->first();

           $bil->prate =$billing_p->prate ?? '';


           $bil->preading =$billing_p->preading ?? '';            
           $bil->pAmount =$billing_p->PTotAmount ?? '';
           $bil->pMinVol =$billing_p->pMinVol ?? '';
           $bil->PTotAmount =$billing_p->PTotAmount ?? '';

           

        }

       }  
       
       

          


      
    return Excel::download(new EscalationExport($billing),'EscalationRates.xlsx');

        }

       



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
}
