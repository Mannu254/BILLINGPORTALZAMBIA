<?php

namespace App\Http\Controllers;

use DB;
use App\Exports\MDSSalesRpt;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
     return view('Reports.index');
    }

    public function sales_rpt(Request $request){

        $start_date =$request->start_date;
        $end_date =$request->end_date;


$sales = DB::table("PostAR as pa")
->join("client as c", function($join){
	$join->on("pa.accountlink", "=", "c.dclink");
})
->leftJoin("invnum as inv", function($join){
	$join->on("inv.invnumber", "=", "pa.reference")
	->whereIn("inv.doctype", [1, 4])
	->where("inv.docstate", "=", 4);
})
->leftJoin("_btblinvoicelines as inl", function($join){
	$join->on("inv.autoindex", "=", "inl.iinvoiceid");
})
->leftJoin("project as pjt2", function($join){
	$join->on("pjt2.projectlink", "=", "inl.ilineprojectid");
})
->leftJoin("areas as a", function($join){
	$join->on("c.iareasid", "=", "a.idareas");
})
->leftJoin("_smtblserviceasset as sa", function($join){
	$join->on("inl.ucidsordtxcmserviceasset", "=", "sa.ccode");
})
->leftJoin("_smtblcodemaster as cm", function($join){
	$join->on("sa.iareaid", "=", "cm.autoidx");
})
->leftJoin("_bvstockfullsegments as stk", function($join){
	$join->on("stk.csimplecode", "=", "sa.ucsastockcode");
})
->leftJoin("cliclass as clc", function($join){
	$join->on("clc.idcliclass", "=", "c.iclassid");
})
->whereBetween('PA.txdate', [$start_date,$end_date])
->whereIn("PA.TrCodeId", [6,10,34,37,38])
->select("txdate", "reference as inv_num", "Order_No as Order_Num",DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='RENTAL' then 'Period Billing' else  'Meter Reading' end AS InvSource"),"c.Name as Name","c.Account as Customer_Code","c.ulARRegionGroup as RegionGroup","c.ulARRegionArea as RegionArea","sa.cCode as Asset","sa.ucSAserialNo as serialNo",DB::raw("'NA' as service_task"),
DB::raw("CASE WHEN inv.doctype=1 then -1 else 1 end *inv.InvTotExcl as ExclAmount"),DB::raw("CASE WHEN inv.doctype=1 then -1 else 1 end *inv.InvTotExcl as ActualInv"),
"a.Code as SalesArea","cm.cDescription as Actual_Location",
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLMON' THEN (INL.uiIDSOrdTxCMCurrReading-INL.uiIDSOrdTxCMPrevReading) ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as monoPages"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLCOL' THEN (INL.uiIDSOrdTxCMCurrReading-INL.uiIDSOrdTxCMPrevReading) ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as ColorPages"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLSCN' THEN (INL.uiIDSOrdTxCMCurrReading-INL.uiIDSOrdTxCMPrevReading) ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as ScanPages"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLA3M' THEN (INL.uiIDSOrdTxCMCurrReading-INL.uiIDSOrdTxCMPrevReading) ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as A3MonoPages"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLA3C' THEN (INL.uiIDSOrdTxCMCurrReading-INL.uiIDSOrdTxCMPrevReading) ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as A3ColorPages"),
DB::raw("0 as MeterID"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='RENTAL' THEN fquantitylinetotexcl ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as PeriodicAmt"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLMON' THEN fquantitylinetotexcl ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as BillMonoAmt"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLCOL' THEN fquantitylinetotexcl ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as BillColAmt"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLSCN' THEN fquantitylinetotexcl ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as BillScnAmt"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLA3M' THEN fquantitylinetotexcl ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as BillA3MAmt"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType='BILLA3C' THEN fquantitylinetotexcl ELSE 0 END * CASE WHEN inv.doctype=1 then -1 else 1 end as BillA3CAmt"),
DB::raw("CASE WHEN ucIDSOrdTxCMMeterType IN('RENTAL','BILLMON','BILLCOL','BILLSCN','BILLA3M','BILLA3C') THEN 0 ELSE fquantitylinetotexcl END * CASE WHEN inv.doctype=1 then -1 else 1 end as OtherAmt"),

DB::raw("CASE WHEN ucIDSOrdTxCMMeterType = 'RENTAL' THEN 'PERIODIC' WHEN ucIDSOrdTxCMMeterType='BILLMON' then 'MONO PAGES' WHEN ucIDSOrdTxCMMeterType='BILLCOL' then 'COLOR PAGES' when ucIDSOrdTxCMMeterType='BILLSCN' then 'SCANS' when ucIDSOrdTxCMMeterType='BILLA3M' then 'A3 MONO' when ucIDSOrdTxCMMeterType='BILLA3C' then 'A3 COLOR' else  ucIDSOrdTxCMMeterType END as MeterCode"),
'stk.cInvSegValue1Value','stk.cInvSegValue2Value','stk.cInvSegValue3Value','stk.cInvSegValue4Value','stk.cInvSegValue5Value','clc.code as CustGp',
DB::raw("case when cm.iParentId = '1016' then 'Eldoret' when cm.iParentId = '1018' then 'Industrial Area'
when cm.iParentId ='1019' then'Kisii' when cm.iParentId ='1020' then'Karen' when cm.iParentId ='1021' then'Kisumu'
when cm.iParentId ='1022' then'Machakos' when cm.iParentId ='1023' then'Mombasa' when cm.iParentId ='1024' then 'Nakuru'
when cm.iParentId ='1025' then'Naivasha' when cm.iParentId ='1026' then 'Nyeri' when cm.iParentId ='1027' then'Thika'
when cm.iParentId ='1028' then'Town' when cm.iParentId ='1029' then'UpperHill' when cm.iParentId ='1030' then 'Westlands'
when cm.iParentId ='1031' then'Woodlands' when cm.iParentId ='9680' then 'General' End as branchcode"),
'inl.cdescription as ItemDesc','PJT2.projectcode as projectcode','PJT2.projectname as projectname',
DB::raw("CASE when ucIDSOrdTxCMMeterType='BILLMON' then ucIDSOrdTxCMMinVol else '0' end as BillMonoMinVol"),
DB::raw("CASE when ucIDSOrdTxCMMeterType='BILLCOL' then ucIDSOrdTxCMMinVol else '0' end as BillColMinVol"),
DB::raw("CASE when ucIDSOrdTxCMMeterType='BILLSCN' then ucIDSOrdTxCMMinVol else '0' end as BillScnMinVol"),
DB::raw("CASE when ucIDSOrdTxCMMeterType='BILLA3M' then ucIDSOrdTxCMMinVol else '0' end as BillA3MMinVol"),
DB::raw("CASE when ucIDSOrdTxCMMeterType='BILLA3C' then ucIDSOrdTxCMMinVol else '0' end as BillA3CMinVol"),

DB::raw("(CASE WHEN ucIDSOrdTxCMMeterType ='BILLMON' and c.iCurrencyID > 0 then convert(varchar,round(convert(float,'0'+left(ucIDSOrdTxCMRates,charindex('|',ucIDSOrdTxCMRates+'|')-1))*inv.fExchangeRate,2)) when  ucIDSOrdTxCMMeterType='BILLMON' then convert(varchar,ucIDSOrdTxCMRates) ELSE '0' END) as BillMonoRates"),
DB::raw("(CASE WHEN ucIDSOrdTxCMMeterType ='BILLCOL' and c.iCurrencyID > 0 then convert(varchar,round(convert(float,'0'+left(ucIDSOrdTxCMRates,charindex('|',ucIDSOrdTxCMRates+'|')-1))*inv.fExchangeRate,2)) when  ucIDSOrdTxCMMeterType='BILLCOL' then convert(varchar,ucIDSOrdTxCMRates) ELSE '0' END) as BillColRates"),
DB::raw("(CASE WHEN ucIDSOrdTxCMMeterType ='BILLSCN' and c.iCurrencyID > 0 then convert(varchar,round(convert(float,'0'+left(ucIDSOrdTxCMRates,charindex('|',ucIDSOrdTxCMRates+'|')-1))*inv.fExchangeRate,2)) when  ucIDSOrdTxCMMeterType='BILLSCN' then convert(varchar,ucIDSOrdTxCMRates) ELSE '0' END) as BillScnRates"),
DB::raw("(CASE WHEN ucIDSOrdTxCMMeterType ='BILLA3M'  then ucIDSOrdTxCMRates ELSE '0' END) as BillA3MRates"),
DB::raw("(CASE WHEN ucIDSOrdTxCMMeterType ='BILLA3C'  then ucIDSOrdTxCMRates ELSE '0' END) as BillA3CRates")


);

$sales_rpt=DB::table($sales, 't')
->select('txdate','inv_num','Order_Num','InvSource','Name','Customer_Code','RegionGroup','RegionArea','Asset','serialNo','service_task','SalesArea','Actual_Location','Custgp','branchcode','cInvSegValue1Value','cInvSegValue2Value','cInvSegValue3Value','cInvSegValue4Value','cInvSegValue5Value','ActualInv',DB::raw("SUM(monoPages) as monoPages"),DB::raw("SUM(ColorPages) as ColorPages"),DB::raw("SUM(ScanPages) as ScanPages"),DB::raw("SUM(A3MonoPages) as A3MonoPages"),DB::raw("SUM(A3ColorPages) as A3ColorPages"),
DB::raw("SUM(PeriodicAmt) as PeriodicAmt"),DB::raw("SUM(BillMonoAmt) as BillMonoAmt"),DB::raw("SUM(BillColAmt) as BillColAmt"),
DB::raw("SUM(BillScnAmt) as BillScnAmt"),DB::raw("SUM(BillA3MAmt) as BillA3MAmt"),DB::raw("SUM(BillA3CAmt) as BillA3CAmt"),DB::raw("SUM(OtherAmt) as OtherAmt"),
'ItemDesc','projectcode','projectname','BillMonoMinVol','BillColMinVol','BillScnMinVol','BillA3MMinVol','BillA3CMinVol',
'BillMonoRates','BillColRates','BillScnRates','BillA3MRates','BillA3CRates'
)
 ->groupBy('txdate','inv_num','Order_Num','InvSource','Name','Customer_Code','RegionGroup','RegionArea','Asset','serialNo','ActualInv','service_task','Custgp','branchcode','SalesArea','Actual_Location','ItemDesc','projectcode','projectname','BillMonoMinVol','BillColMinVol','BillScnMinVol','BillA3MMinVol','BillA3CMinVol',
 'BillMonoRates','BillColRates','BillScnRates','BillA3MRates','BillA3CRates','cInvSegValue1Value','cInvSegValue2Value','cInvSegValue3Value','cInvSegValue4Value','cInvSegValue5Value')
 ->orderBy('Name')
 ->orderBy('Inv_num')
 ->orderBy('Asset')
->get();



$sales_join=DB::table('invnum as inv')
->select('inv.invdate as txdate','invnumber as inv_num','inv.ordernum as Order_Num',DB::raw("'Discount' as InvSource"),'Name','cl.account as Customer_Code','cl.ulARRegionGroup as RegionGroup','cl.ulARRegionArea as RegionArea',
DB::raw("'' as Asset"),DB::raw("'' as serialNo"),DB::raw("'' as service_task"),'A.Code as SalesArea',DB::raw("'' as actual_location"),
'clc.code as Custgp',DB::raw("'' as branchcode"),DB::raw("'' as cInvSegValue1Value"),DB::raw("'' as cInvSegValue2Value"),
DB::raw("'' as cInvSegValue3Value"),DB::raw("'' as cInvSegValue4Value"),DB::raw("'' as cInvSegValue5Value"),DB::raw("CASE WHEN inv.doctype=1 then -1 else 1 end *inv.InvTotExcl as ActualInv"),DB::raw("'0' as monoPages"),DB::raw("'0' as ColorPages"),DB::raw("'0' as ScanPages"),DB::raw("'0' as A3MonoPages"),DB::raw("'0' as A3ColorPages"),
DB::raw("0 as PeriodicAmt"),DB::raw("0 as BillMonoAmt"),DB::raw("0 as BillColAmt"),DB::raw("0 as BillA3MAmt"),DB::raw("0 as BillA3CAmt"),
DB::raw("-1*inv.InvDiscAmntEx as OtherAmt"),DB::raw("'0' as BillScnAmt"),DB::raw("'' as ItemDesc"),DB::raw("'KEMDS' as projectcode"),DB::raw("'MANAGED DOCUMENT SOLUTIONS KENYA' as projectname"),DB::raw("'' as BillMonoMinVol"),
DB::raw("'' as BillColMinVol"),DB::raw("'' as BillScnMinVol"),DB::raw("'' as BillA3MMinVol"),DB::raw("'' as BillA3CMinVol"),DB::raw("'' as BillMonoRates"),
DB::raw("'' as BillColRates"),DB::raw("'' as BillScnRates"),DB::raw("'' as BillA3MRates"),DB::raw("'' as BillA3CRates")


)
->join("client as cl", function($join){
	$join->on("inv.AccountID", "=", "cl.DCLink");
})
->leftJoin("cliclass as clc", function($join){
	$join->on("clc.idcliclass", "=", "cl.iclassid");
})
->leftJoin("areas as A", function($join){
	$join->on("cl.iareasid", "=", "A.idareas");
})
->whereBetween('inv.invdate', [$start_date,$end_date])
->where("inv.doctype",4)
->where("inv.docstate", 4)
->where("inv.InvDiscAmntEx","<>",0)
 ->get();
 



$con_sales = $sales_rpt->union($sales_join);






return Excel::download(new MDSSalesRpt($con_sales),'MDS_SalesReport.xlsx');



       





        

















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
}
