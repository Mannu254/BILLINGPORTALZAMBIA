<?php

namespace App\Http\Controllers;

use Auth;
use App\Client;
use App\Contract;
use Carbon\Carbon;
use App\ServiceAsset;
use App\BillingReview;
use App\_smtblrateslab;
use App\Monthlyreading;
use App\cplmeterreading;
use App\_smtblPeriodService;
use App\Exports\ReadingTemp;
use Illuminate\Http\Request;
use App\_smtblCounterElapsed;
use App\Exports\SummaryExport;
use App\Imports\ReadingImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\SessionDate;
use Illuminate\Support\Facades\Session;

class BillingExecutiveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

       $clients =Client::
       whereNotIn('Client.DCLink', [1])  
       ->select('DCLink','Account','Name')  
       ->orderBy('DCLink','desc')     
       ->get();

       $session =SessionDate::where('user_id',Auth::user()->id)
       ->select('session_date')
       ->first();
       
    
    return view('billing.index',compact('clients','session'));
    }

    public function search_serial(Request $request){
        $asset =DB::table('_smtblServiceAsset as sa')
        ->where('sa.ucSASerialNo',$request->serial_no)
        ->join('Client', 'sa.iCustomerId', '=', 'Client.DCLink') 
        ->select('sa.cDescription','Client.Name')
        ->first();

        return response()->json($asset);

        
    }

    public function search_hint(Request $request){
        $asset_hint =DB::table('_smtblServiceAsset as sa')
        ->where('sa.ucSASerialNo','LIKE','%'.$request->serial.'%')
         ->select('sa.ucSASerialNo')
        ->get();

        $output ='';
        if(count($asset_hint) > 0){
        $output ='<ul class ="list-group" style= "display-block;position:absolute;z-index:1 !important;cursor: pointer; hover: background-color:#ccc;">';
        foreach($asset_hint as $row){
            $output .='<li class ="list-group-item">'.$row->ucSASerialNo.'</li>';
}
        }
        else{

            $output .='<li class ="list-group-item" style= "display-block;position:absolute;z-index:20000 !important;">No data Found</li>';
        }

        return response()->json($output);



    }

    public function export_orders(Request $request){
        $summary =DB::table('InvNum')
        ->whereIn('orderNum',$request->orders ?? '')
        ->whereIn('Client.Account',$request->account ?? '')
        ->select('AccountID','OrderNum','InvTotExcl','InvTotTax','InvTotIncl','Client.Name','Client.Account','OrderDate')
        ->join('Client', 'InvNum.AccountID', '=', 'Client.DCLink') 
        ->get();


        return response()->json($summary);


    }
  
    public function downloadTemp()
    {
    $file ='MeterReadingTemplate.xlsx';
    return response()->download(public_path('storage/uploads/'.$file));
   
    
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
    public function meter_reading_review(Request $request){

    if(empty($request->cust_id))
    {
        return redirect()->back()->with('error','No Customer Selected To Get Meter reading');

    }
        
    $clients_machines =Client::
    whereIn('Client.DCLink',$request->cust_id ?? null)
    ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')  
       ->Where(function($query)
    {
        $query->where('_smtblServiceAsset.cDescription', 'NOT LIKE', '%KISA%')
        ->orwhere('_smtblServiceAsset.cDescription', 'NOT LIKE', '%TAI%')
        ->orWhereNull('_smtblServiceAsset.cDescription');
    }) 
              
    ->join('_smtblServiceAsset', '_smtblServiceAsset.iCustomerId', '=', 'Client.DCLink')        
    ->select('Client.DCLink','Client.Account','Client.Name','_smtblServiceAsset.ucSASerialNo','_smtblServiceAsset.cDescription','_smtblServiceAsset.cCode','_smtblServiceAsset.AutoIdx','_smtblServiceAsset.cLocation','_smtblServiceAsset.ucSABillingAsset','_smtblServiceAsset.AutoIdx')
    ->get();

      

    
       foreach($clients_machines as $machine){
        $today = Carbon::parse($request->billing_date)->subMonthsNoOverflow()->endOfMonth(); 

               
        //get contract details
        $contract_details =Contract::
        where('iCustomerId',$machine->DCLink)
        ->select(DB::raw('MIN(_smtblContractMatrix.dStartDate) as startdate'),DB::raw('MIN(_smtblContractMatrix.dEndDate) as Enddate'),'AutoIdx')
        ->groupBy('AutoIdx')
        ->first();  
        
       

       
        //get the billing cycle
        $billing_cyle =DB::table('_smtblContractMatrixPeriodService')
        ->select('iJan','iFeb','iMar','iApr','iMay','iApr','iMay','iJun','iJul','iAug','iSep','iOct','iNov','iDec')

        ->where('iContractMatrixID',$contract_details->AutoIdx ?? '')
        ->where('cCode','BILLCYCLE')
        ->first();

        

        if(empty($billing_cyle)){
            $cBillingSchedule = '';
           
        }
        else if($billing_cyle->iJan ==1 && $billing_cyle->iFeb ==1 && $billing_cyle->iMar ==1 && $billing_cyle->iApr ==1 && $billing_cyle->iMay ==1 && $billing_cyle->iJun ==1 
            && $billing_cyle->iJul =1 && $billing_cyle->iAug =1 && $billing_cyle->iSep =1 && $billing_cyle->iOct =1 && $billing_cyle->iNov =1 && $billing_cyle->iDec ==1
        ){
            $cBillingSchedule ='MON';
            $month =1;

        }
        else{
            $cBillingSchedule ='';

        }

        //if monthly strictly get the reading of last month only even when null
        if( $cBillingSchedule =='MON'){
            $today_current = Carbon::parse($request->billing_date);

            //check previous reading from sage database if update has been done reading will be this month
            $Previous_readings_db  = DB::table('_cplmeterreading')
            ->where('_cplmeterreading.AssetID',$machine->AutoIdx)
            ->whereYear('ReadingDate', '=', $today_current->year)
            ->whereMonth('ReadingDate', '=', $today_current->month) 
            ->select(DB::raw('Max(_cplmeterreading.ReadingDate) as reading_date'),DB::raw('Max(_cplmeterreading.Autoidx) as id'),
            DB::raw('Max(_cplmeterreading.MonPMR) as mono_pmr'),
            DB::raw('Max(_cplmeterreading.ColPMR) as color_pmr')
            
            
            )
            ->first();           
            
            $machine->ReadingDate = $Previous_readings_db->reading_date ?? Null;
            $machine->mono_pmr =$Previous_readings_db->mono_pmr  ?? NULL;
            $machine->color_pmr =$Previous_readings_db->color_pmr  ?? NULL;
            
        
            


            
        //no update done hence check cmr of the previous month to be the pmr
        if(is_null($Previous_readings_db->mono_pmr) || is_null($Previous_readings_db->color_pmr) ){ 
        $today = Carbon::parse($request->billing_date)->subMonthsNoOverflow()->endOfMonth();                
                
        $Previous_readings  = DB::table('_cplmeterreading')
        ->where('_cplmeterreading.AssetID',$machine->AutoIdx)
        ->whereYear('ReadingDate', '=', $today->year)
        ->whereMonth('ReadingDate', '=', $today->month) 
        ->select(DB::raw('Max(_cplmeterreading.ReadingDate) as reading_date'),DB::raw('Max(_cplmeterreading.Autoidx) as id'),
        DB::raw('Max(_cplmeterreading.MonCMR) as mono_pmr'),
        DB::raw('Max(_cplmeterreading.ColCMR) as color_pmr')
        
        )
        ->first();                
                
        $machine->ReadingDate = $Previous_readings->reading_date;
        $machine->mono_pmr =$Previous_readings->mono_pmr  ?? NULL;
        $machine->color_pmr =$Previous_readings->color_pmr  ?? NULL;
      


                }
            }
        else{
        // get the readings with  the highest previous date ie for quatery billing and where no cycle available
        $today_current_p = Carbon::parse($request->billing_date)->subMonthsNoOverflow()->endOfMonth();              

        $max_prevdate =DB::table('_cplmeterreading')
        ->where('ReadingDate', '<=', $today_current_p)
        ->where('_cplmeterreading.AssetID',$machine->AutoIdx)        
        ->select(DB::raw('Max(_cplmeterreading.ReadingDate) as reading_date'),DB::raw('Max(_cplmeterreading.Autoidx) as id'))      
        ->first();
                           
        $Previous_readings  = DB::table('_cplmeterreading')
        ->where('_cplmeterreading.AssetID',$machine->AutoIdx)
        ->where('_cplmeterreading.ReadingDate',$max_prevdate->reading_date)
        
        ->select(DB::raw('Max(_cplmeterreading.ReadingDate) as reading_date'),DB::raw('Max(_cplmeterreading.Autoidx) as id'),
        DB::raw('Max(_cplmeterreading.MonCMR) as mono_pmr'),
        DB::raw('Max(_cplmeterreading.ColCMR) as color_pmr')
        )      
        ->first();
        
     
         $machine->ReadingDate = $Previous_readings->reading_date;
         $machine->mono_pmr =$Previous_readings->mono_pmr  ?? NULL;
         $machine->color_pmr =$Previous_readings->color_pmr  ?? NULL;
        

        }

      
        $today_current = Carbon::parse($request->billing_date);
        
        $current_readings  = DB::table('_cplmeterreading')
        ->where('_cplmeterreading.AssetID',$machine->AutoIdx)
        ->whereYear('ReadingDate', '=', $today_current->year)
        ->whereMonth('ReadingDate', '=', $today_current->month)      
        ->select('_cplmeterreading.ReadingDate','_cplmeterreading.Autoidx','_cplmeterreading.MonCMR',
        '_cplmeterreading.ColCMR')
        ->first();

       

        
        
        $machine->MonCMR =$current_readings->MonCMR  ?? NULL;
        $machine->ColCMR =$current_readings->ColCMR  ?? NULL;
        

        

        
        if(($current_readings->MonCMR ?? 0) == 0 ){
          

            $current_readings_db  = DB::table('monthlyreadings')
            ->where('monthlyreadings.serial_no',trim($machine->ucSASerialNo))
            ->whereYear('reading_date', '=', $today_current->year)
            ->whereMonth('reading_date', '=', $today_current->month)      
            ->select('monthlyreadings.reading_date','monthlyreadings.id','monthlyreadings.mono_cmr','monthlyreadings.copies_mono','monthlyreadings.copies_col',
            'monthlyreadings.color_cmr')
            ->first();            

            
            
            $machine->MonCMR =$current_readings_db->mono_cmr  ?? NULL;
            $machine->ColCMR =$current_readings_db->color_cmr  ?? NULL;
            
            
            $machine->copies_mono =$current_readings_db->copies_mono  ?? NULL;  
            $machine->copies_col =$current_readings_db->copies_col  ?? NULL;  

            
        }
        else{
            $current_readings_db  = DB::table('monthlyreadings')
            ->where('monthlyreadings.serial_no',$machine->ucSASerialNo)
            ->whereYear('reading_date', '=', $today_current->year)
            ->whereMonth('reading_date', '=', $today_current->month)      
            ->select('monthlyreadings.copies_mono','monthlyreadings.copies_col')
            ->first();

            $machine->copies_mono =$current_readings_db->copies_mono  ?? NULL;  
            $machine->copies_col =$current_readings_db->copies_col  ?? NULL;  
        }
        
       }
      
    
     
        $billing_date = $request->billing_date;
        $customers_id = json_encode($request->cust_id); 

        
       
        
      

     return view('billing.meter_reading_review',compact('clients_machines','billing_date','customers_id'));
    }

    public function post_reading(Request $request){

        $today_current = Carbon::parse($request->billing_date);
        $data_array = $request->values;
        

        foreach($data_array as $key => $value) {

            $check_if_exist=cplmeterreading::whereYear('ReadingDate', $today_current->year)
            ->whereMonth('ReadingDate', $today_current->month)
            ->where('AssetID',$value['asset_id'])
            ->select('Autoidx')
            ->first();

            if($check_if_exist){
                $update =DB::table('_cplmeterreading')
                ->where('Autoidx',$check_if_exist->Autoidx)
                ->update([
            'ReadingDate' =>$request->billing_date,  
            'MonPMR'=>str_replace(',','',$value['mono_pmr']),
            'MonCMR'=>str_replace(',','',$value['mono_cmr']),
            'ColPMR'=>str_replace(',','',$value['color_pmr']),
            'ColCMR'=>str_replace(',','',$value['color_cmr']),
           

            
                 
               ]);               
            }
            else{
                $reading  = new cplmeterreading;                     
                $reading->AssetID =$value['asset_id'];
                $reading->ReadingDate=$request->billing_date;
                $reading->MonPMR=str_replace(',','',$value['mono_pmr']);
                $reading->MonCMR=str_replace(',','',$value['mono_cmr']);
                $reading->ColPMR =str_replace(',','',$value['color_pmr']);
                $reading->ColCMR=str_replace(',','',$value['color_cmr']);
    
                $reading->A3MPMR=0;
                $reading->A3MCMR=0;
    
                $reading->A3CPMR=0;
                $reading->A3CCMR=0;
    
    
                $reading->ScnPMR=0;
                $reading->ScnCMR =0;
    
                 $reading->save();             


            }        

                      
           
         
        }
       
         return response()->json(['success'=>'Reading Updated successfully.'],200);



    }

    public function previous_reading(){
        $clients =Client::
        whereNotIn('Client.DCLink', [1])  
        ->select('DCLink','Account','Name')  
        ->orderBy('DCLink','desc')     
        ->get();

        return view('billing.previous_reading',compact('clients'));
    }
    

    public function reading_db(){
        $clients =Client::
        whereNotIn('Client.DCLink', [1])  
        ->select('DCLink','Account','Name')  
        ->orderBy('DCLink','desc')     
        ->get();

        
       $session =SessionDate::where('user_id',Auth::user()->id)
       ->select('session_date')
       ->first();
        

        return view('billing.reading_database',compact('clients','session'));
    }

    public function post_excel_reading(Request $request){

       
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

                           
                $reading_date = $request->reading_date;
                
               $import = Excel::import(new ReadingImport($reading_date),request()->file('file')); 
              
                                      
                           
               
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
            return redirect()->back()->with('success', 'Meter Reading Uploaded Successfully');


    }

    public function post_single_reading(Request $request){

        $this->validate($request,[
            'cust_name'=> 'required',
            'serial_no'=> 'required',
            'description'=>'required',
            'mono_cmr'=>'required',
            'color_cmr' => 'numeric|nullable',
            
            'reading_date'=>'date'  
        ]);
        $today_current = Carbon::parse($request->reading_date);
        Monthlyreading::updateOrCreate([
            'serial_no'          => $request->serial_no,            
            'created_at' => Monthlyreading::whereYear('created_at', $today_current->year)->first()->created_at ?? null,
            'created_at' => Monthlyreading::whereMonth('created_at', $today_current->month)->first()->created_at ?? null
           
            ],[
            'customer_name'      => $request->cust_name,
            'serial_no'          => $request->serial_no, 
            'description'        => $request->description, 
            'mono_cmr'           => $request->mono_cmr,
            'color_cmr'          => $request->color_cmr,
            'a3mono_cmr'         =>0, 
            'a3color_cmr'        => 0,  
            'scan_cmr'           =>0,            
            'reading_date'       => $request->reading_date,
            ]
            );

        return redirect()->back()->with('success',' Meter Reading Updated Successfully');


        
    }

    public function billing_review(Request $request){

        $clients = array_unique($request->input('DCLink',[])); 
        

        $today=Carbon::parse($request->input('billing_date'));     

        
        $billing_review_data = DB::table('_smtblServiceAsset As sa')
        ->whereIn('DCLink',$clients)
        
        ->Where(function($query)
        {
            $query->where('sa.cDescription', 'NOT LIKE', '%KISA%')
            ->orwhere('sa.cDescription', 'NOT LIKE', '%TAI%')
            
            ->orWhereNull('sa.cDescription');
        })          
        ->select('cl.DCLink','cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',DB::raw('sum(cmr.moncmr - cmr.monpmr) as monunit'),
       
        DB::raw('sum(cmr.colcmr - cmr.colpmr) as colunit'),       
        DB::raw('MAX(CASE WHEN sa.ucSABillingAsset IS NULL THEN sa.ccode ELSE sa.ucSABillingAsset END) AS billingasset'),
        DB::raw("MAX(CASE WHEN sa.ucSABillingAsset IS NULL THEN sa.cdescription ELSE 'Billed Asset' END) AS assetdesc")      
         )
         ->leftjoin('_bvARAccountsFull As cl', 'cl.DCLink', '=', 'sa.icustomerid')  
         ->leftjoin('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')  
          ->whereYear('ReadingDate', '=', $today->year)
         ->whereMonth('ReadingDate', '=', $today->month)          
        


        ->groupBy('cl.DCLink','cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',DB::raw('CASE WHEN sa.ucSABillingAsset IS NULL THEN sa.ccode ELSE sa.ucSABillingAsset END'))
        ->get();



        //   dd($billing_review_data);

    
           

          
         foreach($billing_review_data as $data){

            //check if an invoice exist for thr billed asset

              $get_order_number =DB::table('_btblInvoiceLines As linv')
             ->where('linv.ucIDSOrdTxCMServiceAsset',$data->billingasset)
             ->where('uhl.UserValue', '=','MR')           
            ->where('InvNum.doctype', '=',4)          
            ->whereYear('InvNum.orderdate', '=', $today->year)
            ->whereMonth('InvNum.orderdate', '=', $today->month)                    
            ->join('InvNum', 'InvNum.AutoIndex', '=', 'linv.iInvoiceID')  
            ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
              join _rtbluserdict on userdictid=iduserdict
            where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','InvNum.AutoIndex')
            ->select('OrderNum')
            ->first();
            // dd($get_order_number);

            //check if there is a wrongly MR invoice created
            $data->OrderNum =$get_order_number->OrderNum ?? null;   

            $check_if_done =DB::table('client as cl')
            ->select(DB::raw('COUNT(inv.accountid) as idcount'),'uhl.UserValue','inv.orderNum') 
            ->where('uhl.UserValue', '=','MR')           
            ->where('inv.doctype', '=',4)          
            ->whereYear('inv.orderdate', '=', $today->year)
            ->whereMonth('inv.orderdate', '=', $today->month)       
            ->where('cl.Account',$data->account)            
            ->join('InvNum as inv','cl.DCLink','=','inv.AccountID')
            ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
              join _rtbluserdict on userdictid=iduserdict
            where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
            ->groupBy('uhl.UserValue','inv.orderNum')
            ->first();

//  dd($check_if_done);

          

             $data->check_if_done =$check_if_done->idcount ?? 0;              

            // get the asset id of the billed asset
            $asset_id = DB::table('_smtblServiceAsset As sa')
            ->where('cCode',$data->billingasset)
            ->select('AutoIdx')
            ->first();  

            
            
                         
      
      
          //get any rental amount for the billed asset
           $rentamt =DB::table('_smtblperiodservice As ps')
          ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
          ->Where(function($query)
          {
              $query->where('cCode','RENTAL')
              ->orwhere('cCode','RENTAL CHARGES');
          })            
          ->select('fAmount')
          ->first();






          $data->famount =$rentamt->fAmount ?? 0;

          //software Charges
        


         

          $software =DB::table('_smtblperiodservice As ps')
          ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
          ->Where(function($query)
          {
              $query->where('cDescription','Software Rental Charges')
              ->orwhere('cDescription','Scanner Charges');
             
          })            
          ->select(DB::raw('sum(fAmount) as fAmount'))
          ->first();

         

          $data->software =$software->fAmount ?? 0;

         


            // "MONO" get the billing id
            $counter_elapse =DB::table('_smtblcounterelapsed')
            ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
            ->Where(function($query)
            {
                $query->where('cCode','BILLMON')
                ->orwhere('cCode','BILMON');
            })
            ->select('iMeterId')
            ->first();   

           
            

            
           
        // get min vol of the billing id
        $min_vol = _smtblrateslab::where('iBillingID',$counter_elapse->iMeterId ?? '')
        ->select(DB::raw('MIN(iToqty) as min_vol'),'fRate')
        ->groupBy('fRate',DB::raw('(iToqty)'))
        ->first();   
        

       
       

            
          if(!empty($min_vol))
          {
            
            // if min vol exids 100m then there ins no min von its a flat rate 
            if($min_vol->min_vol  >=1000000){
                $data->min_mono_vol = null;
               }
               else{
                $data->min_mono_vol =$min_vol->min_vol ?? null;
               }
    
            
            // if min vol is more than 100m just calculate using the flat rate
            if( $min_vol->min_vol  >=1000000){ 

                $total_frate =($min_vol->fRate * $data->monunit);
                $rate_frate =number_format($min_vol->fRate,3);
              

                $data->comb_rates = $rate_frate;
                $data->total = $total_frate;           


            }
            else{
            $diff_mon = ($data->monunit -$min_vol->min_vol ?? 0);         

            
            
            
            // the value is bigger than min volume
            //slab two starts here
            if($diff_mon > 0){
            $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
            ->where('iToqty',$min_vol->min_vol)
            ->select('frate')
            ->first();

            
            $total_min =($rate_min->frate * $min_vol->min_vol);
           
            $rate =number_format($rate_min->frate,3);

            // we increase minimum to move to the next slab 


            //slab two starts
            $Increment_1 =$min_vol->min_vol +1;         
            
            // we get the rate for the next slab and their readings
            $next_slab =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
            ->where('iFromQty','<=',$Increment_1)
           ->where('iToqty','>=',$Increment_1)
           ->select('iFromQty','iToqty','fRate')
           ->first();

           if(empty($next_slab)){
            return redirect()->back()->with('error','Error in Slabs available contact Administrator');
           }

      
           
            //  we get the difference of this slab
           $diff_slab =($next_slab->iToqty - $next_slab->iFromQty)+1;

        //  we check if the remainder fulls fall in this slab or exceed
           $diff_bil_slab =$diff_mon -$diff_slab;           

           //counts fits fully in this slab the difference is negative
           if($diff_bil_slab < 0){
            $total_slab_1 =($diff_mon * $next_slab->fRate );

            $rate_2 =number_format($next_slab->fRate,3);            

            // two slabs used             
            $comb_rates =$rate.' | '.$rate_2;
            $total =($total_slab_1 +$total_min);

            $data->comb_rates = $comb_rates;
            $data->total = $total;          
            
           }

           //  slab 3
           //the counter is still much than the previous slab difference
           if($diff_bil_slab > 0){
            $total_slab_1 =($diff_slab * $next_slab->fRate );      
            
            $rate_2 =number_format($next_slab->fRate,3);
            

            //we increase the slab to quantity to move to the next slab
            $increment2 =$next_slab->iToqty +1;
           

            // we get the slab values rates and quantity
            $rate_slab2 =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
            ->where('iFromQty','<=',$increment2)
           ->where('iToqty','>=',$increment2)
           ->select('iFromQty','iToqty','fRate')
           ->first();
           

           $slab2_diff =($rate_slab2->iToqty -$rate_slab2->iFromQty)+1; 
      
            //  we check if the remainder fully fall in this slab or exceed
            $remaining_bil_slab =$diff_bil_slab -$slab2_diff; 
            
            if($remaining_bil_slab < 0){

            $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

            $rate_3 =number_format($rate_slab2->fRate,3);

            // three slabs used

            $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3;
            $total =($total_slab_1 +$total_min +$total_slab_2);

            $data->comb_rates = $comb_rates;
            $data->total = $total;


            }

            // slab four
            if($remaining_bil_slab > 0){
            $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

            $rate_3 =$rate_slab2->fRate;

            // increase slab to next

            $increment3 =$rate_slab2->iToqty +1;
            $rate_slab3 =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
            ->where('iFromQty','<=',$increment3)
            ->where('iToqty','>=',$increment3)
            ->select('iFromQty','iToqty','fRate')
            ->first();

            
            $slab3_diff =($rate_slab3->iToqty -$rate_slab3->iFromQty)+1; 

            $remaing_slab3_diff =($remaining_bil_slab -$slab3_diff);

            if(!$remaing_slab3_diff){

            $total_slab_3 =$remaining_bil_slab * $rate_slab3->fRate;
            $rate_4 =number_format($rate_slab3->fRate,3);

            // four slabs used

            $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3. '|'.$rate_4;
            $total =($total_slab_1 +$total_min +$total_slab_2 +$total_slab_3);

            $data->comb_rates = $comb_rates;
            $data->total = $total;

            }
            }
           }             
            }
            else{
             $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
            ->where('iToqty',$min_vol->min_vol)
            ->select('frate')
            ->first();
        
            $total_min =($rate_min->frate * $min_vol->min_vol);
            $rate =number_format($rate_min->frate,3);

            $data->comb_rates = $rate;
            $data->total = $total_min;  
                }    
           

          
          
        }
    }
    
           //COLOR          
           $color_counter_elapse =DB::table('_smtblcounterelapsed')
           ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
           ->Where(function($query)
            {
                $query->where('cCode','BILLCOL')
                ->orwhere('cCode','BILCOL');
            })
           
           ->select('iMeterId')
           ->first();           
       

           
           $min_vol_color = _smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId ?? '')
            ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
            ->groupBy('fRate',DB::raw('(iToqty)'))
            ->first();

                       
            

          if(!empty($min_vol_color))
          {
        

            if($min_vol_color->min_vol >= 1000000){

            $total_color =$min_vol_color->fRate * $data->colunit;
            $rate_frate =number_format($min_vol_color->fRate,3);

            $data->total_color = $total_color;
           $data->comb_rates_color =$rate_frate;   


            }
            else{                   

            $diff_color = ($data->colunit -$min_vol_color->min_vol);
            

            if($diff_color > 0){
            $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
            ->where('iToqty',$min_vol_color->min_vol)
            ->select('frate')
            ->first();
             $total_min_color =($rate_min_color->frate * $min_vol_color->min_vol);

            $rate =number_format($rate_min_color->frate,3);

            $diff_remaining_color = ($data->colunit -$min_vol_color->min_vol);

            // we increase minimum to move to the next slab 

            //slab two starts
            $Increment_1 =$min_vol_color->min_vol +1;  

             //we get the rate for the next slab and their readings
             $next_slab_col =_smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
             ->where('iFromQty','<=',$Increment_1)
            ->where('iToqty','>=',$Increment_1)
            ->select('iFromQty','iToqty','fRate')
            ->first();

             //  we get the difference of this slab
           $diff_slab_color =($next_slab_col->iToqty - $next_slab_col->iFromQty)+1;

           //  we check if the remainder fulls fall in this slab or exceed
              $diff_bal_slab =$diff_remaining_color -$diff_slab_color;

            //   value fully fits here


            if($diff_bal_slab < 0){
            $total_slab_2_col =($diff_remaining_color * $next_slab_col->fRate );

            $rate_2 =number_format($next_slab_col->fRate,3);         

            
            // two slabs used 
            
            $comb_rates_color =$rate.' | '.$rate_2;
            $total_color =($total_min_color + $total_slab_2_col);

            $data->comb_rates_color = $comb_rates_color;
            $data->total_color = $total_color;



            }

            // slab three
            if($diff_bal_slab > 0){
            $total_slab_2_col =($diff_slab_color * $next_slab_col->fRate );  
            
            $rate_2 =number_format($next_slab_col->fRate,3); 

            // $diff_remaining2_color = ($diff_bal_slab - $diff_slab_color);

            // we increase minimum to move to the next slab 


            //slab two starts
            $increment2 =$next_slab_col->iToqty +1; 
           

             // we get the slab values rates and quantity
             $rate_slab3_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
             ->where('iFromQty','<=',$increment2)
            ->where('iToqty','>=',$increment2)
            ->select('iFromQty','iToqty','fRate')
            ->first();

            // we get slab difference
            $slab_3_diff =($rate_slab3_color->iToqty - $rate_slab3_color->iFromQty) +1;

            // we check if balance is still bigger or less

            $diff_remaining3_color =$diff_bal_slab -  $slab_3_diff;

            if($diff_remaining3_color < 0){

            $total_slab_3_col =($diff_bal_slab * $rate_slab3_color->fRate );

            $rate_3 =number_format($rate_slab3_color->fRate,3); 

             // three slabs used 
            
             $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3;
             $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col);
 
             $data->comb_rates_color = $comb_rates_color;
             $data->total_color = $total_color;


            }
           if($diff_remaining3_color > 0){

            $total_slab_3_col =($slab_3_diff * $rate_slab3_color->fRate );

            $rate_3 =number_format($rate_slab3_color->fRate,3); 

            // increase to move to the next slab

            $increment3 =$rate_slab3_color->iToqty +1;  

            $rate_slab4_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
            ->where('iFromQty','<=',$increment3)
            ->where('iToqty','>=',$increment3)
            ->select('iFromQty','iToqty','fRate')
            ->first();

            // we get the difference of the slab

            $diff_slab4 =($rate_slab4_color->iToqty - $rate_slab4_color->iFromQty)+1;


            // we check if balance is still bigger or less
            $diff_remaining4_color =$diff_remaining3_color - $slab_3_diff ;

            // we check if balance fits in this slab

            if($diff_remaining4_color < 0){

            $total_slab_4_col =($diff_remaining3_color * $rate_slab4_color->fRate );

            $rate_4 =number_format($rate_slab4_color->fRate,3); 
            
             // four slabs used 
            
             $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3. '|'. $rate_4;
             $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col+$total_slab_4_col);
 
             $data->comb_rates_color = $comb_rates_color;
             $data->total_color = $total_color;


            }

           }

        }

}

            else{

            $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
            ->where('iToqty',$min_vol_color->min_vol)
            ->select('frate')
            ->first();

            $total_color =($rate_min_color->frate * $min_vol_color->min_vol);
            $rate_color =number_format($rate_min_color->frate,3);           

            $data->comb_rates_color = $rate_color;
            $data->total_color = $total_color;   

            }

        }
    }


    
        
   
        //   SCAN CALCULATION 

        $scan_counter_elapse =DB::table('_smtblcounterelapsed')
        ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
        ->where('cCode','BILLSCN')
        ->select('iMeterId')
        ->first();

   
       
      
        
        $total_col = $data->total_color ?? '0';
        $rental_amt =$data->famount ?? '0';
        $total_mono =$data->total ?? '0';   
        $software_amt =$software->fAmount ?? 0;    
   

        $total_inv_amt =  $total_mono + $total_col + $rental_amt +$software_amt;         
        $data->total_inv_amt =$total_inv_amt;           

       
          $min_color_vol = _smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId ?? '')
          ->select(DB::raw('min(iToqty) as min_color_vol'))
          ->first();

          if($min_color_vol->min_color_vol >=1000000){
            $data->min_color_vol = null;
           }
           else{
            $data->min_color_vol =$min_color_vol->min_color_vol ?? null;
           }        
            
        
          }          
    
        
    
    return view('billing.billing_review',compact('billing_review_data','today'));
 }


 public function mds_service_assets_b(){

    return view('billing.service_assets');



 }

 public function mds_cust_assets_b(Request $request){
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
            $query->where('cSerialNo',$hint)
            ->orwhere('ucSASerialNo',$hint)
            ->orwhere('ucSABillingAsset',$hint)
            ->orwhere('_smtblContractMatrix.cCode',$hint)
            ->orWhere('Name', 'like', '%'.$hint.'%')
            ->orWhere('_smtblServiceAsset.Ccode', 'like', '%'.$hint.'%');
        })
       
        
       

        ->select('ucSABillingAsset','ucSASerialNo','Name','_smtblServiceAsset.cCode as ConCode','_smtblContractMatrix.dStartDate as Sdate','_smtblContractMatrix.dEnddate as Edate','_smtblServiceAsset.CDescription as Desc','_smtblServiceAsset.cLocation')    
        ->get();

        

        

    }

    return response()->json($assets);



 }

 public function mds_cost_click_b(){
    return view ('billing.cost');


 }

 public function cont_code_b(Request $request){
    $con_code =$request->hint;

    $asset_id = DB::table('_smtblServiceAsset As sa')
    ->where('cCode',$con_code)
    ->select('AutoIdx')
    ->first(); 



  

    $counter_elapse =DB::table('_smtblcounterelapsed')
    ->join('_smtblServiceAsset','_smtblcounterelapsed.iServiceAssetId', '=', '_smtblServiceAsset.AutoIdx')
    ->join('Client', 'Client.DClink', '=', '_smtblServiceAsset.iCustomerId') 
    ->where('iServiceAssetId',$asset_id->AutoIdx)
    ->select('_smtblcounterelapsed.AutoIdx as AIdx','Name','iServiceAssetId','_smtblcounterelapsed.cCode as ctype','_smtblcounterelapsed.cDescription as desc','iMeterId')
    ->get(); 

   
          
              
     return response()->json($counter_elapse);
    
   }




 public function slabs_rate_b(Request $request){
    if($request->ajax()){

        $billingID =$request->dataId;

        
        $billing_rates = _smtblrateslab::where('iBillingID',$billingID)
        ->select('AutoIdx','iFromQty','iToqty','frate')
        ->get(); 

       return response()->json($billing_rates);



     }


 }

 public function update_rates_b(Request $request){
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
 public function session_date(Request $request){
    if($request->ajax()){
        $this-> validate($request,[
        'year' => 'required'
            
        ]);


        $today=Carbon::parse($request->year);
        
       

        $update_year = SessionDate::updateOrCreate(
            ['user_id' =>Auth::user()->id],
            ['session_date' =>$request->year]
        );
        
        Session::flash('success',' You are Doing Billing For '.$today->format('F')); 
        return response()->json(['success'=>'Session Updated successfully.']);
    }


 }



}
 


