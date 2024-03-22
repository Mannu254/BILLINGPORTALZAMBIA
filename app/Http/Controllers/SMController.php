<?php

namespace App\Http\Controllers;

use DB;
use App\Area;
use DateTime;
use App\ClientDS;
use App\SalesRep;
use App\_smtblWorker;
use Carbon\Carbon;
use App\stkSegment;
use App\Imports\AssetUpdate;
use Illuminate\Http\Request;
use App\_smtblContractMatrix;
use App\Exports\AssetsExport;
use App\Exports\AssetExportAll;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\_smtblCodeMaster;


class SMController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    return view("SM.index");
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

    public function contract_update(){

        $templates =DB::connection('service_manager')->table('_smtblContractMatrix')
        ->whereIn('AutoIdx',[7020,6706,2301,1,2549,2300,3587,2588,7021,2302,2587])
        ->select('AutoIdx','cCode','cDescription')
        ->get();
        return view('SM.contract_update',compact('templates'));


    }

    public function contract_search(Request $request){

        $contract_hint =$request->contract_hint;

        
        $client = (new ClientDS())->getTable();

       
       
        
        $contract =DB::connection('service_manager')->table('_smtblContractMatrix')
         
         ->join("$client", function ($join) {
            $join->on('Client.DClink','=','_smtblContractMatrix.iCustomerId');
               
        })
        ->where('_smtblContractMatrix.cCode',$contract_hint)
        ->first();

       
        $template = DB::connection('service_manager')->table('_smtblContractMatrix')
        ->where('AutoIdx',$contract->iContractTemplateId)
        ->first();


        $contract->template =$template;

        //  dd($contract);

            return response()->json($contract);
    }

    public function contract_date_change(Request $request){

        $sdate_req = $request->sdate;
        $temp_id = $request->template_id;

        $sdate =Carbon::createFromFormat('d/m/Y', $sdate_req)->format('d-m-Y');
          

        
       

         $contract_temp =DB::connection('service_manager')->table('_smtblContractMatrix')
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

   public function contract_update_date(Request $request){ 

    $sdate =Carbon::createFromFormat('d/m/Y',$request->sdate)->format('m-d-Y');
    $edate=Carbon::createFromFormat('d/m/Y',$request->edate)->format('m-d-Y');
    $rdate=Carbon::createFromFormat('d/m/Y',$request->rdate)->format('m-d-Y');
   
    
    
    $code =$request->code;
    $template_id =$request->template_id;

    $update =DB::connection('service_manager')->table('_smtblContractMatrix')
          ->where('cCode',$code)
          ->update([
          'dReviewDate' => $rdate,
          'dStartDate' =>  $sdate,
          'dEndDate' => $edate,
          'iContractTemplateId' => $template_id,
    ]);



    $update_assets =DB::connection('service_manager')->table('_smtblServiceAsset')
    ->where('ucSABillingAsset',$code)
    ->update([
    
    'udSAContractStart' =>  $sdate,
    'udSAContractEnd' => $edate,
    '_smtblServiceAsset_dModifiedDate'=>Carbon::Now(),
    
]);


    if($update && $update_assets){

        return response()->json(['success'=>'Contract Updated successfully.'],200);
    }
    else{

        return response()->json(['error'=>'Contract Update failed!!.Contact Administrator'], 400);
    }





   }

   public function asset_export(Request $request){
    $client = (new ClientDS())->getTable();

    $assets =DB::connection('service_manager')->table('_smtblServiceAsset')
    ->join("$client", function ($join) {
        $join->on('Client.DClink','=','_smtblServiceAsset.iCustomerId');
           
    })
    ->where('ucSABillingAsset',$request->con_asset)
    ->select('ucSABillingAsset','ucSASerialNo','Name')    
    ->get();

    return Excel::download(new AssetsExport($assets),'CustomerAssets.xlsx');
    

    
   }

   public function update_asset(Request $request){
    
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

                
            
           $import = Excel::import(new AssetUpdate(),request()->file('file')); 
              
                                  
                       
           
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

   public function service_assets(){

    return view('SM.service_assets');
   }

   public function cust_assets(Request $request){
    $client = (new ClientDS())->getTable();
    if($request->ajax()){        
        $assets =DB::connection('service_manager')->table('_smtblServiceAsset')
        ->join("$client", function ($join) {
            $join->on('Client.DClink','=','_smtblServiceAsset.iCustomerId');
               
        })
        ->where('ucSABillingAsset',$request->hint)
        ->orwhere('cSerialNo',$request->hint)
        ->orwhere('ucSASerialNo',$request->hint)
        ->orwhere('cCode',$request->hint)
        ->orWhere('Name', 'like', '%'.$request->hint.'%')
         ->orWhere('_smtblServiceAsset.Ccode', 'like', '%'.$request->hint.'%')

        
        ->select('ucSABillingAsset','ucSASerialNo','Name','_smtblServiceAsset.cCode','udSAContractStart','udSAContractEnd','CDescription')    
        ->get();

    }

    return response()->json($assets);


   }


   public function contract_assets(){

    return view('SM.contractAssets');
   }

   public function asset_export_all(){
    $client = (new ClientDS())->getTable();
    $salesRep =(new SalesRep())->getTable();
    $code_master =(new _smtblCodeMaster())->getTable();
    $areas =(new Area())->getTable();
    $stksegment =(new stkSegment())->getTable();
    $iworker=(new _smtblWorker())->getTable();
    $assets_all =DB::connection('service_manager')->table('_smtblServiceAsset')
        ->leftjoin("$client", function ($join) {                       
            $join->on('_smtblServiceAsset.iCustomerId','=','Client.DClink');  
        })
        ->leftjoin("$salesRep", function ($join) {                     
            $join->on('Client.RepID','=','SalesRep.idSalesRep'); 
        })

        ->leftjoin("$code_master", function ($join) {                     
            $join->on('_smtblCodeMaster.AutoIdx','=','_smtblServiceAsset.iAreaId'); 
        })

        ->leftjoin("$areas", function ($join) {
            $join->on('Areas.idAreas','=','Client.iAreasID');               
        })
       
        ->leftjoin("$stksegment", function ($join) {
        $join->on('_smtblServiceAsset.ucSAStockCode','=','_bvstockfullsegments.csimplecode');  
        })

        ->leftjoin("$iworker", function ($join) {
            $join->on('_smtblServiceAsset.iWorkerId','=','_smtblWorker.AutoIdx');   
        })

        ->where('_smtblServiceAsset.CDescription','!=','')
        
        
        ->orderBy('Client.Name','asc')     

        ->select('ucSABillingAsset','cSerialNo','ucSASerialNo','Client.Name as cname','_smtblServiceAsset.cCode as Acode','udSAContractStart','udSAContractEnd','_smtblServiceAsset.CDescription as asset_desc','SalesRep.Name as salesRep','Account','ulARRegionArea','Areas.Description as cust_area','_smtblCodeMaster.cDescription as service_area','cLocation','udSAContractStart','udSAContractEnd','dInstallationDate','_bvstockfullsegments.cInvSegValue4Desc','_smtblWorker.cFirstName')    
        ->get();

        

   
    return Excel::download(new AssetExportAll($assets_all),'AssetsDsBase.xlsx');
   }

   public function asset_inContract(){
    $today=Carbon::Now(); 
    
    $client = (new ClientDS())->getTable();
    $salesRep =(new SalesRep())->getTable();
    $areas =(new Area())->getTable();
    $code_master =(new _smtblCodeMaster())->getTable();
    $stksegment =(new stkSegment())->getTable();
    $iworker=(new _smtblWorker())->getTable();
    $assets_all =DB::connection('service_manager')->table('_smtblServiceAsset')
        ->leftjoin("$client", function ($join) {                       
            $join->on('_smtblServiceAsset.iCustomerId','=','Client.DClink');  
        })
        ->leftjoin("$salesRep", function ($join) {                     
            $join->on('Client.RepID','=','SalesRep.idSalesRep');  
        })
        ->leftjoin("$code_master", function ($join) {                     
            $join->on('_smtblCodeMaster.AutoIdx','=','_smtblServiceAsset.iAreaId'); 
        })

        ->leftjoin("$areas", function ($join) {
            $join->on('Areas.idAreas','=','Client.iAreasID');               
        })
       
        ->leftjoin("$stksegment", function ($join) {
        $join->on('_smtblServiceAsset.ucSAStockCode','=','_bvstockfullsegments.csimplecode');  
        })

        ->leftjoin("$iworker", function ($join) {
            $join->on('_smtblServiceAsset.iWorkerId','=','_smtblWorker.AutoIdx');   
        })

        ->where('_smtblServiceAsset.CDescription','!=','')
        ->where('udSAContractEnd','>=',$today)
        
        
        ->orderBy('Client.Name','asc')     

        ->select('ucSABillingAsset','cSerialNo','ucSASerialNo','Client.Name as cname','_smtblServiceAsset.cCode as Acode','udSAContractStart','udSAContractEnd','_smtblServiceAsset.CDescription as asset_desc','SalesRep.Name as salesRep','Account','ulARRegionArea','Areas.Description as cust_area','_smtblCodeMaster.cDescription as service_area','cLocation','udSAContractStart','udSAContractEnd','dInstallationDate','_bvstockfullsegments.cInvSegValue4Desc','_smtblWorker.cFirstName')    
        ->get();


        

   
    return Excel::download(new AssetExportAll($assets_all),'AssetsInContract.xlsx');
   

   }

   public function contract_renew_monthly(Request $request){

   
    $date_month = substr($request->date, 0, 2);
    $date_year = substr($request->date, 3, 7);
    $client = (new ClientDS())->getTable();
    $salesRep =(new SalesRep())->getTable();
    $code_master =(new _smtblCodeMaster())->getTable();
    $areas =(new Area())->getTable();
    $stksegment =(new stkSegment())->getTable();
    $iworker=(new _smtblWorker())->getTable();
    $assets_all =DB::connection('service_manager')->table('_smtblServiceAsset')
        ->leftjoin("$client", function ($join) {                       
            $join->on('_smtblServiceAsset.iCustomerId','=','Client.DClink');  
        })
        ->leftjoin("$salesRep", function ($join) {                     
            $join->on('Client.RepID','=','SalesRep.idSalesRep'); 
        })
        ->leftjoin("$code_master", function ($join) {                     
            $join->on('_smtblCodeMaster.AutoIdx','=','_smtblServiceAsset.iAreaId'); 
        })

        ->leftjoin("$areas", function ($join) {
            $join->on('Areas.idAreas','=','Client.iAreasID');               
        })
       
        ->leftjoin("$stksegment", function ($join) {
        $join->on('_smtblServiceAsset.ucSAStockCode','=','_bvstockfullsegments.csimplecode');  
        })

        ->leftjoin("$iworker", function ($join) {
            $join->on('_smtblServiceAsset.iWorkerId','=','_smtblWorker.AutoIdx');   
        })

        ->where('_smtblServiceAsset.CDescription','!=','')
        ->whereYear('udSAContractStart', '=', $date_year)
        ->whereMonth('udSAContractStart', '=', $date_month)   
        
        
        ->orderBy('Client.Name','asc')     

        ->select('ucSABillingAsset','cSerialNo','ucSASerialNo','Client.Name as cname','_smtblServiceAsset.cCode as Acode','udSAContractStart','udSAContractEnd','_smtblServiceAsset.CDescription as asset_desc','SalesRep.Name as salesRep','Account','ulARRegionArea','Areas.Description as cust_area','_smtblCodeMaster.cDescription as service_area','cLocation','udSAContractStart','udSAContractEnd','dInstallationDate','_bvstockfullsegments.cInvSegValue4Desc','_smtblWorker.cFirstName')    
        ->get();
        

   
    return Excel::download(new AssetExportAll($assets_all),'ContractsRenewed.xlsx');
    
    


    
   }


   public function NotInContract(){

    $today=Carbon::Now(); 
    
    $client = (new ClientDS())->getTable();
    $salesRep =(new SalesRep())->getTable();
    $areas =(new Area())->getTable();
    $code_master =(new _smtblCodeMaster())->getTable();
    $stksegment =(new stkSegment())->getTable();
    $iworker=(new _smtblWorker())->getTable();
    $assets_all =DB::connection('service_manager')->table('_smtblServiceAsset')
        ->leftjoin("$client", function ($join) {                       
            $join->on('_smtblServiceAsset.iCustomerId','=','Client.DClink');  
        })
        ->leftjoin("$salesRep", function ($join) {                     
            $join->on('Client.RepID','=','SalesRep.idSalesRep'); 
        })
        ->leftjoin("$code_master", function ($join) {                     
            $join->on('_smtblCodeMaster.AutoIdx','=','_smtblServiceAsset.iAreaId'); 
        })

        ->leftjoin("$areas", function ($join) {
            $join->on('Areas.idAreas','=','Client.iAreasID');               
        })
       
        ->leftjoin("$stksegment", function ($join) {
        $join->on('_smtblServiceAsset.ucSAStockCode','=','_bvstockfullsegments.csimplecode');  
        })

        ->leftjoin("$iworker", function ($join) {
            $join->on('_smtblServiceAsset.iWorkerId','=','_smtblWorker.AutoIdx');   
        })              

         ->where(function ($query) use ($today) {
            $query->where('udSAContractEnd', '<=', $today)                
                  ->orwhere('ucSABillingAsset','=','');
                  
        })         
        ->orderBy('Client.Name','asc')     

        ->select('ucSABillingAsset','cSerialNo','ucSASerialNo','Client.Name as cname','_smtblServiceAsset.cCode as Acode','udSAContractStart','udSAContractEnd','_smtblServiceAsset.CDescription as asset_desc','SalesRep.Name as salesRep','Account','ulARRegionArea','Areas.Description as cust_area','_smtblCodeMaster.cDescription as service_area','cLocation','udSAContractStart','udSAContractEnd','dInstallationDate','_bvstockfullsegments.cInvSegValue4Desc','_smtblWorker.cFirstName')    
        ->get();

        

   
    return Excel::download(new AssetExportAll($assets_all),'AssetsNotInContract.xlsx');


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
