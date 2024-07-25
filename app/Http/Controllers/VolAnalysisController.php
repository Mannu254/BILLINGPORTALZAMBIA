<?php

namespace App\Http\Controllers;

use App\Client;
use Carbon\Carbon;


use PDF;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use App\Exports\SummaryExport;
use App\Exports\VolAnaysisExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class VolAnalysisController extends Controller
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
     return view('VolumeAnalysis.index',compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function vol_analsisExpo(Request $request){

        if($request->ajax()){ 

       $start_date =$request->start_date;
        $end_date =$request->end_date;

        

        $clients =Client::
        join('_smtblServiceAsset', '_smtblServiceAsset.iCustomerId', '=', 'Client.DCLink') 
        ->join('_smtblCodeMaster as sc','sc.AutoIdx','=','_smtblServiceAsset.iAreaId')
        ->whereNotIn('Client.DCLink', [1])  
        ->whereIn('Client.DCLink',$request->customer_id)
        ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')
             
       

       


       
       
       ->select('Client.DCLink','Account','Name','_smtblServiceAsset.cCode','ucSASerialNo','_smtblServiceAsset.cDescription','ucSASerialNo','sc.cDescription as cLocation','_smtblServiceAsset.AutoIdx')            
       ->get();  
       
       
       
   


       foreach($clients as $client){

           
        $period = CarbonPeriod::create(Carbon::parse($request->start_date), '1 month',Carbon::parse($request->end_date));         
       

       $data=[];
       $total_mon_vol =0;
       $total_col_vol =0;

       $months = 1;


         foreach ($period as $dt) { 
            
            $counter =$months++;

          $current_reading = DB::table('_cplmeterreading')
          ->where('_cplmeterreading.AssetID',$client->AutoIdx)
          ->whereYear('ReadingDate', '=', $dt->year)
          ->whereMonth('ReadingDate', '=', $dt->month) 
          ->first();   

          $tot_mon =($current_reading->MonCMR ?? 0) -($current_reading->MonPMR ?? 0);
          $total_mon_vol+= $tot_mon;
          $tot_col =($current_reading->ColCMR ?? 0) -($current_reading->ColPMR ?? 0);
          $total_col_vol+=$tot_col;

          $data[] = array(

            
          'mono_vol' =>($current_reading->MonCMR ?? 0) -($current_reading->MonPMR ?? 0),
           'col_vol' =>($current_reading->ColCMR ?? 0) -($current_reading->ColPMR ?? 0),
           'reading_date'=>$current_reading->ReadingDate ??'',



          );             

       }

       $average_mono =($total_mon_vol / $counter);
       $average_color =($total_col_vol / $counter);

       $client->months =$counter;    
       $client->total_mon_vol =$total_mon_vol;
       $client->total_col_vol =$total_col_vol;

       $client->average_mono =$average_mono;
       $client->average_color =$average_color;

        $client->data =$data;
        

      
      
       
}


 
  


}
     

        return Excel::download(new VolAnaysisExport($clients,$period),'vol_analysis.xlsx');

        


     }
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

    public function summary_sheet(){

        $clients =Client::
        whereNotIn('Client.DCLink', [1])  
        ->select('DCLink','Account','Name')  
        ->orderBy('DCLink','desc')     
        ->get();

        return view('VolumeAnalysis.summary_sheet',compact('clients'));
    }

    public function summary(Request $request){

        if($request->ajax()){ 
           
            $today=Carbon::parse($request->date_month);
            $customer_id =$request->customer_id;

            

            foreach ($customer_id as $cust) {

                $details =DB::table('client as cl')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.accountid','inv.AutoIndex','inv.OrderNum','InvTotExcl') 
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4)          
                ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)       
                ->where('cl.DCLink',$cust)            
                ->join('InvNum as inv','cl.DCLink','=','inv.AccountID')
                ->join('_btblInvoiceLines as II','II.iInvoiceID','=','inv.AutoIndex')
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                  join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')            
                ->first();
    
                   if(empty($details)){
    
                    return response()->json(['error'=>'Error in Exporting Summary!!.Contact Administrator'],400);
                   }
    
               
    
                // get values for CON assets only
    
                $summary_cons =DB::table('_btblInvoiceLines as II')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.AccountID','inv.AutoIndex','inv.OrderNum','InvTotExcl','ucIDSOrdTxCMServiceAsset','fQuantityLineTotExcl','uiIDSOrdTxCMCurrReading','uiIDSOrdTxCMPrevReading','ucIDSOrdTxCMMinVol','ucIDSOrdTxCMRates')
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BILLMON%')               
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)  
                
                ->get();
    
                 
    
                foreach($summary_cons as $cons){
    
                // get color con
                $cons_color =DB::table('_btblInvoiceLines as II')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.AccountID','inv.AutoIndex','inv.OrderNum','InvTotExcl','ucIDSOrdTxCMServiceAsset','fQuantityLineTotExcl','uiIDSOrdTxCMCurrReading','uiIDSOrdTxCMPrevReading','ucIDSOrdTxCMMinVol','ucIDSOrdTxCMRates')
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')    
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4)         
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', '=',$cons->ucIDSOrdTxCMServiceAsset)
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BILLCOL%')  
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')              
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
                $cons->cons_color =$cons_color;
    
                //get the scan con
    
                $cons_scan =DB::table('_btblInvoiceLines as II')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.AccountID','inv.AutoIndex','inv.OrderNum','InvTotExcl','ucIDSOrdTxCMServiceAsset','fQuantityLineTotExcl','uiIDSOrdTxCMCurrReading','uiIDSOrdTxCMPrevReading','ucIDSOrdTxCMMinVol','ucIDSOrdTxCMRates')
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', '=',$cons->ucIDSOrdTxCMServiceAsset)
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BILLSCN%')  
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')              
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
                $cons->cons_scan =$cons_scan;
    
                // get a3 mon
                $cons_a3m =DB::table('_btblInvoiceLines as II')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.AccountID','inv.AutoIndex','inv.OrderNum','InvTotExcl','ucIDSOrdTxCMServiceAsset','fQuantityLineTotExcl','uiIDSOrdTxCMCurrReading','uiIDSOrdTxCMPrevReading','ucIDSOrdTxCMMinVol','ucIDSOrdTxCMRates')
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', '=',$cons->ucIDSOrdTxCMServiceAsset)
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'A3BILLMON%')  
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')              
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
                $cons->cons_a3m =$cons_a3m;
    
                // get a3 color
    
                $cons_a3C =DB::table('_btblInvoiceLines as II')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.AccountID','inv.AutoIndex','inv.OrderNum','InvTotExcl','ucIDSOrdTxCMServiceAsset','fQuantityLineTotExcl','uiIDSOrdTxCMCurrReading','uiIDSOrdTxCMPrevReading','ucIDSOrdTxCMMinVol','ucIDSOrdTxCMRates')
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', '=',$cons->ucIDSOrdTxCMServiceAsset)
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'A3BILLCOL%')  
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')              
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
                $cons->cons_a3C =$cons_a3C;
    
                }
    
    
    
                $summary_assets =DB::table('_btblInvoiceLines as II')
                ->select('cl.DCLink','cl.name','cl.account','inv.autoindex','inv.orderdate','inv.AccountID','inv.AutoIndex','inv.OrderNum','InvTotExcl','ucIDSOrdTxCMServiceAsset','fQuantityLineTotExcl','uiIDSOrdTxCMCurrReading','uiIDSOrdTxCMPrevReading','ucIDSOrdTxCMMinVol','ucIDSOrdTxCMRates')
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BIL%') 
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'NOT LIKE', 'CON%') 
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)             
                ->get();
    
                // dd($summary_assets);           
    
               
                    $mon_total =0;
                    $col_total =0;
                    $scn_total =0;
                    $a3m_total =0;
                    $a3c_total =0;
                    $rental= 0;
    
                
    
                // total for mono
                $total_mon =DB::table('_btblInvoiceLines as II')
                ->select(DB::raw('sum(fQuantityLineTotExcl) as fQuantityLineTotExcl'))
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BILLMON%')               
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
              
    
                // total for color
                $total_col =DB::table('_btblInvoiceLines as II')
                ->select(DB::raw('sum(fQuantityLineTotExcl) as fQuantityLineTotExcl'))
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BILLCOL%')               
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
              
    
                // total scan 
                $total_scn =DB::table('_btblInvoiceLines as II')
                ->select(DB::raw('sum(fQuantityLineTotExcl) as fQuantityLineTotExcl'))
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'BILLSCN%')               
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
    
                // total a3m
                $total_a3m =DB::table('_btblInvoiceLines as II')
                ->select(DB::raw('sum(fQuantityLineTotExcl) as fQuantityLineTotExcl'))
                ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                ->join('client as cl','cl.DCLink','=','inv.AccountID')
                ->where('uhl.UserValue', '=','MR')           
                ->where('inv.doctype', '=',4) 
                
                ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                join _rtbluserdict on userdictid=iduserdict
                where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                 ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')
                 ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'A3BILLMON%')               
                 ->where('cl.DCLink',$cust) 
                 ->whereYear('inv.orderdate', '=', $today->year)
                ->whereMonth('inv.orderdate', '=', $today->month)              
                ->first();
    
                  // total a3C
                  $total_a3c =DB::table('_btblInvoiceLines as II')
                  ->select(DB::raw('sum(fQuantityLineTotExcl) as fQuantityLineTotExcl'))
                  ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                  ->join('client as cl','cl.DCLink','=','inv.AccountID')
                  ->where('uhl.UserValue', '=','MR')           
                  ->where('inv.doctype', '=',4) 
                  
                  ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                  join _rtbluserdict on userdictid=iduserdict
                  where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                   ->where('II.ucIDSOrdTxCMServiceAsset', 'LIKE', 'CON%')
                   ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'A3BILLCON%')               
                   ->where('cl.DCLink',$customer_id) 
                   ->whereYear('inv.orderdate', '=', $today->year)
                  ->whereMonth('inv.orderdate', '=', $today->month)              
                  ->first();
    
    
                //   RENTAL
    
                  $rental =DB::table('_btblInvoiceLines as II')
                  ->select(DB::raw('sum(fQuantityLineTotExcl) as fQuantityLineTotExcl'))
                  ->join('InvNum as inv','II.iInvoiceID','=','inv.AutoIndex')
                  ->join('client as cl','cl.DCLink','=','inv.AccountID')              
                  ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
                  join _rtbluserdict on userdictid=iduserdict
                  where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
                   ->where('II.ucIDSOrdTxCMMeterType', 'LIKE', 'RENTAL%')
                   ->where('uhl.UserValue', '=','MR')           
                   ->where('inv.doctype', '=',4) 
                                
                   ->where('cl.DCLink',$cust) 
                   ->whereYear('inv.orderdate', '=', $today->year)
                  ->whereMonth('inv.orderdate', '=', $today->month)              
                  ->first();
    
    
    
    
    
               
    
                $individual_asset=DB::table('_smtblServiceAsset as sa')
                ->select('sa.cDescription','sa.cCode','sa.ucSASerialNo','MonPMR','MonCMR','ColPMR','ColCMR','ScnCMR','ScnPMR','A3MPMR','A3MCMR','A3CPMR','A3CCMR','sc.cDescription as cLocation')
                ->join('_cplmeterreading','_cplmeterreading.AssetID','=','sa.AutoIdx')
                ->join('_smtblCodeMaster as sc','sc.AutoIdx','=','sa.iAreaId')              
                ->where('sc.cType','ARE')
                 ->whereYear('ReadingDate', '=', $today->year)
                ->whereMonth('ReadingDate', '=', $today->month) 
                ->where('sa.iCustomerId',$customer_id) 
                ->where('sa.cCode', 'NOT LIKE', 'CON%')  
                 ->where('sa.cDescription', 'NOT LIKE', 'KISA%') 
                ->where('sa.cDescription', 'NOT LIKE', 'TAI%')
                ->get();         

                
    
    
                $individual_asset_totals=DB::table('_smtblServiceAsset')
                ->select(DB::raw('sum(MonCMR) as mon_cmr_total'),DB::raw('sum(MonPMR) as mon_pmr_total'),DB::raw('sum(MonPMR) as mon_pmr_total'),
                DB::raw('sum(ColPMR) as col_pmr_total'),DB::raw('sum(ColCMR) as col_cmr_total'),
                DB::raw('sum(ScnCMR) as scn_cmr_total'),DB::raw('sum(ScnPMR) as scn_pmr_total'),
                DB::raw('sum(A3MPMR) as a3m_pmr_total'),DB::raw('sum(A3MCMR) as a3m_cmr_total'),
                DB::raw('sum(A3CPMR) as a3c_pmr_total'),DB::raw('sum(A3CCMR) as a3c_cmr_total'))
                ->join('_cplmeterreading','_cplmeterreading.AssetID','=','_smtblServiceAsset.AutoIdx')
                 ->whereYear('ReadingDate', '=', $today->year)
                ->whereMonth('ReadingDate', '=', $today->month) 
                ->where('_smtblServiceAsset.iCustomerId',$cust) 
                ->where('_smtblServiceAsset.cCode', 'NOT LIKE', 'CON%')  
                 ->where('_smtblServiceAsset.cDescription', 'NOT LIKE', 'KISA%') 
                ->where('_smtblServiceAsset.cDescription', 'NOT LIKE', 'TAI%')
                ->first();

                
                $data =[
                    'details'=> $details,
                    'today' => $today,
                    'summary_cons' => $summary_cons,
                    'individual_asset' => $individual_asset,
                    'rental' => $rental,
                    'total_mon' => $total_mon,
                    'total_col' => $total_col,
                    'individual_asset_totals' => $individual_asset_totals,
                    'details' => $details,

                ];

                $pdf = PDF::loadView('VolumeAnalysis.CustomerSummary',$data)->setPaper('a4', 'landscape');
                return $pdf->download('CustomerSummarySheet.pdf');
    
                        
                
               
              
    
                 
            
            
            
            
    
        } 
    
    
    
               
    
        
        }
    

             
            }
                
        



           

           
        
        
        
    }

