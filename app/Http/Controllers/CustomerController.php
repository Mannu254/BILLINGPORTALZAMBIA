<?php

namespace App\Http\Controllers;

use DB;
use App\Client;
use App\Contract;
use Carbon\Carbon;
use App\ServiceAsset;
use App\Monthlyreading;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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

    public function previous_reading(Request $request){

        if($request->ajax()){ 
            $assets =Client::
            whereNotIn('Client.DCLink', [1])  
            ->whereIn('Client.DCLink',$request->customer_id)
            ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')          
           ->join('_smtblServiceAsset', '_smtblServiceAsset.iCustomerId', '=', 'Client.DCLink')       
           
           ->select('Client.DCLink','Account','Name','cCode','ucSASerialNo','cDescription','ucSASerialNo','cLocation')            
           ->get();
           foreach($assets as $ast) {
            $today_current=Carbon::parse($request->reading_date);
            $readings =Monthlyreading::
            select('mono_cmr','color_cmr','a3mono_cmr','a3color_cmr','scan_cmr','billing_cycle','branch','remarks','mode_collection','reading_date')
            ->where('serial_no',$ast->ucSASerialNo)
            ->whereYear('reading_date', $today_current->year)
           ->whereMonth('reading_date', $today_current->month)
           ->first();

           $ast->mono_cmr =$readings->mono_cmr ?? '0';
           $ast->color_cmr=$readings->color_cmr?? '';
           $ast->a3mono_cmr=$readings->a3mono_cmr?? '';
           $ast->a3color_cmr=$readings->a3color_cmr?? '';
           $ast->scan_cmr=$readings->scan_cmr?? '';   
           $ast->billing_cycle=$readings->billing_cycle?? '';      
           $ast->branch=$readings->branch?? ''; 
           $ast->remarks=$readings->remarks?? ''; 
           $ast->mode_collection=$readings->mode_collection?? ''; 
           $ast->reading_date=$readings->reading_date?? ''; 

           }
        
                    

      return response()->json($assets);



         }
        
    }

    public function cust_assets(Request $request){
        if($request->ajax()){ 
            $assets =Client::
            whereNotIn('Client.DCLink', [1])  
            ->whereIn('Client.DCLink',$request->customer_id)
            ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')           
           ->join('_smtblServiceAsset', '_smtblServiceAsset.iCustomerId', '=', 'Client.DCLink')       
           
           ->select('Client.DCLink','Account','Name','cCode','ucSASerialNo','cDescription','ucSASerialNo','cLocation')            
           ->get();
           foreach($assets as $ast) {
            $today_current=Carbon::now();
            $readings =Monthlyreading::
            select('mono_cmr','color_cmr','a3mono_cmr','a3color_cmr','scan_cmr')
            ->where('serial_no',$ast->ucSASerialNo)
            ->whereYear('reading_date', $today_current->year)
           ->whereMonth('reading_date', $today_current->month)
           ->first();

           $ast->mono_cmr =$readings->mono_cmr ?? '0';
           $ast->color_cmr=$readings->color_cmr?? '';
           $ast->a3mono_cmr=$readings->a3mono_cmr?? '';
           $ast->a3color_cmr=$readings->a3color_cmr?? '';
           $ast->scan_cmr=$readings->scan_cmr?? '';        

           }

                    

       return response()->json($assets);



         }
        

    }

    public function reading_save(Request $request){

        $count =count( $request->input('mono_cmr',[]));
       

        
        $date = $request->reading_date;
        for ($i=0; $i<$count; $i++){
            $data[] = array(
                'customer_name'      => $request->input("cust_name")[$i],
                'asset_code'        =>  $request->input("asset_code")[$i],
                'serial_no'          => $request->input('ucSASerialNo')[$i], 
                'description'        => $request->input('cDescription')[$i], 
                'mono_cmr'           => $request->input('mono_cmr')[$i],
                'color_cmr'          => $request->input('color_cmr')[$i],
                'a3mono_cmr'         => $request->input('a3mono_cmr')[$i], 
                'a3color_cmr'        => $request->input('a3color_cmr')[$i],  
                'scan_cmr'           => $request->input('scan_pmr')[$i], 
                'remarks'           => $request->input('remarks')[$i],  
                'billing_cycle'       =>$request->input('billing_cycle')[$i],
                'branch'            =>$request->input('branch')[$i],
                'mode_collection'   =>$request->input('mode')[$i],
                'reading_date'        => Carbon::parse($request->reading_date),
                
            );
        }

     
        foreach($data as $dt){

              $today_current = Carbon::parse($request->reading_date);

                          
            Monthlyreading::updateOrCreate([
                'serial_no'          => $dt["serial_no"],            
                'created_at' => Monthlyreading::whereYear('created_at', $today_current->year)->first()->created_at ?? $today_current,
               'created_at' => Monthlyreading::whereMonth('created_at', $today_current->month)->first()->created_at ?? $today_current
               
                ],$dt
                );
        }
        return redirect()->back()->with('success',' Meter Reading Updated Successfully');
       

    }

    public function cust_search(Request $request){
        if($request->ajax()){        
        $clients =Client::
         whereNotIn('Client.DCLink', [1])  
         ->whereIn('Client.DCLink',$request->customer_id)
         ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')        
        ->join('_smtblServiceAsset', '_smtblServiceAsset.iCustomerId', '=', 'Client.DCLink') 
         
        
        ->select('Client.DCLink','Account','Name')
        ->groupBy('Client.DCLink','Account','Name')
        ->selectRaw('count(_smtblServiceAsset.iCustomerId) as total_machine')                     
      
         
        ->get();

   
        
        foreach($clients as $client){

        $contract_details =Contract::
        where('iCustomerId',$client->DCLink)
        ->select(DB::raw('MIN(_smtblContractMatrix.dStartDate) as startdate'),DB::raw('MIN(_smtblContractMatrix.dEndDate) as Enddate'),'AutoIdx')
        ->groupBy('AutoIdx')
        ->first();

        //  dd($contract_details);
        
        
        $client->startdate = $contract_details->startdate ?? '';
        $client->Enddate = $contract_details->Enddate ?? '';
      
        $billing_cyle =DB::table('_smtblContractMatrixPeriodService')
        ->select('iJan','iFeb','iMar','iApr','iMay','iApr','iMay','iJun','iJul','iAug','iSep','iOct','iNov','iDec')

        ->where('iContractMatrixID',$contract_details->AutoIdx ?? '')
        ->where('cCode','BILLCYCLE')        
        ->first();

      

        if(empty($billing_cyle)){
            $client->billcylce ='';

        }
        else if($billing_cyle->iJan ==1 && $billing_cyle->iFeb ==1 && $billing_cyle->iMar ==1 && $billing_cyle->iApr ==1 && $billing_cyle->iMay ==1 && $billing_cyle->iJun ==1 
            && $billing_cyle->iJul =1 && $billing_cyle->iAug =1 && $billing_cyle->iSep =1 && $billing_cyle->iOct =1 && $billing_cyle->iNov =1 && $billing_cyle->iDec ==1
        ){
            $client->billcylce ='MON';

        }
        else{
            $client->billcylce ='';

        }        

        $client->jan = $billing_cyle->iJan ?? '';
        $client->feb = $billing_cyle->iFeb ?? '';
        $client->mar = $billing_cyle->iMar ?? '';
        $client->apr = $billing_cyle->iApr ?? '';
        $client->may = $billing_cyle->iMay ?? '';
        $client->jun = $billing_cyle->iJun ?? '';
        $client->jul = $billing_cyle->iJul ?? '';
        $client->aug = $billing_cyle->iAug ?? '';
        $client->sep = $billing_cyle->iSep ?? '';
        $client->oct = $billing_cyle->iOct ?? '';
        $client->nov = $billing_cyle->iNov ?? '';
        $client->dec = $billing_cyle->iDec ?? '';


               

        $total_asset_billed = ServiceAsset::
        where('iCustomerId',$client->DCLink)
        ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')
        ->select('AutoIdx')        
        ->get()->toArray();
         $today = Carbon::now();
       
        $total_asset_billed_count = DB::table('_cplmeterreading')
        ->whereYear('ReadingDate', '=', $today->year)
        ->whereMonth('ReadingDate', '=', $today->month)
        ->whereIn('AssetID',$total_asset_billed)
        ->count();

         $pending_billing =$client->total_machine - $total_asset_billed_count; 

         $client->total_asset_billed_count = $total_asset_billed_count;
         $client->pending_billing = $pending_billing;
       

        }
        
       
        
     return response()->json($clients);


        }

        
    }
}
