<?php

namespace App\Http\Controllers;

use DB;
use App\Client;
use App\InvNum;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\_smtblrateslab;
use App\_btblInvoiceLines;

class SalesOrderController extends Controller
{
    public function salesOrder(Request $request){
      
      $data_array = $request->values;

        $count = count($request->input('account',[]));

        foreach($data_array as $key => $value) {
          
          $today=Carbon::parse($request->billing_date);         
    
          $billing_type=$value['billing_type'];   
           $con_asset =str_starts_with($value['ucSABillingAsset'],'CON');
          $checkifService =str_starts_with($value['ucSABillingAsset'],'SER');
         
         
          #checks which type of billing
          if($billing_type =='J' && $con_asset ){              
                    
            $check_if_done =DB::table('client as cl')
            ->select('cl.DCLink','cl.name','cl.account','cl.udARContractStartDate','inv.autoindex','inv.orderdate','inv.accountid',DB::raw('COUNT(inv.accountid) as idcount'),'inv.AutoIndex') 
            ->where('uhl.UserValue', '=','MR')           
            ->where('inv.doctype', '=',4)          
            ->whereYear('inv.orderdate', '=', $today->year)
            ->whereMonth('inv.orderdate', '=', $today->month)       
            ->where('cl.Account',$value['account'])            
            ->join('InvNum as inv','cl.DCLink','=','inv.AccountID')
            ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
              join _rtbluserdict on userdictid=iduserdict
            where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
            ->groupBy('cl.DCLink','cl.name','cl.account','cl.udARContractStartDate','inv.orderdate','inv.accountid','inv.AutoIndex')
            ->first();
           
    
            
            #if done get the invoice id and use
           if($check_if_done->idcount ?? 0 > 0){
    
            $invoice_id= $check_if_done->AutoIndex;  
            $customer  = $value['account'];
            // return $customer;
            $customer_details =Client::where('Account',$customer)
            ->select('dclink','name','post1','post2','post3','post4','email',DB::raw("(CASE WHEN repid = null THEN 0 ELSE repid END) AS repid"),
            DB::raw("(CASE WHEN icurrencyid = null THEN 0 ELSE icurrencyid END) AS icurrencyid"),'ideftaxtypeid'
            )
             ->first(); 

             $today=Carbon::parse($request->billing_date);                     
    
           
            }  


            #if not done create a new invoice No
            else{

            // get customer details
          $customer  = $value['account'];
            $customer_details =Client::where('Account',$customer)
          ->select('dclink','name','post1','post2','post3','post4','email',DB::raw("(CASE WHEN repid = null THEN 0 ELSE repid END) AS repid"),
          DB::raw("(CASE WHEN icurrencyid = null THEN 0 ELSE icurrencyid END) AS icurrencyid"),'ideftaxtypeid')
         ->first(); 
                   
                 
             //get po number
        $po_number =DB::table('OrdersDf')
        ->where('DefaultCounter',101000000)
        ->select('OrderPrefix','DNoPadLgth','NextCustNo')
        ->first();

       
        

        $update =DB::table('OrdersDf')
        ->where('DefaultCounter',101000000)
        ->update([
        'NextCustNo' => $po_number->NextCustNo +1
       ]);

         // Get exchangerate
      
        $today=Carbon::parse($request->billing_date);
      
        $echange_rate =DB::table('currencyhist')
        ->select('fbuyrate')
        ->where('icurrencyid',$customer_details->icurrencyid)
        ->whereRaw('idCurrencyHist in (select max(idCurrencyHist) from currencyhist group by (iCurrencyID))')  
        ->first();
  
        $exc_rate =$echange_rate->fbuyrate  ?? '0';
    
  
      // Get tax rate     
      $tax_rate =DB::table('taxrate')
      ->where('idtaxrate',$customer_details->ideftaxtypeid)
      ->select('TaxRate')
      ->first();
  
      
  
      
       $padded_num = str_pad( $po_number->NextCustNo, $po_number->DNoPadLgth , '0', STR_PAD_LEFT); 
      
       $tsonum = $po_number->OrderPrefix.$padded_num;  
    
       $month =$today->format('M');       
     
        $tinvamtexcl = 0;
        $tinvamtincl = 0;
        $tinvamttax = 0;
        $tordamtexcl = 0;
        $tordamtincl = 0;
        $tordamttax = 0;
        $tfcinvamtexcl = 0;
        $tfcinvamtincl = 0;
        $tfcinvamttax = 0;
        $tfcordamtexcl = 0;
        $tfcordamtincl = 0;
        $tfcordamttax = 0;
        $textorderno = $tsonum;
        $torddesc = "Monthly Billing for $month $today->year";
        $tsdate = $today;
                      
  
      
      // generate so order
  
      $new_so_order =new InvNum;
      $new_so_order->DocType =4;
      $new_so_order->DocVersion=1;
      $new_so_order->DocState =1;
      $new_so_order->DocFlag=0;
      $new_so_order->OrigDocID =0;
      $new_so_order->InvNumber='';
      $new_so_order->GrvNumber ='';
      $new_so_order->GrvID =0;
      $new_so_order->AccountID=$customer_details->dclink;    
      $new_so_order->Description =$torddesc;
      $new_so_order->InvDate =$tsdate;
      $new_so_order->OrderDate =$tsdate;
      $new_so_order->DueDate =$tsdate;
      $new_so_order->DeliveryDate =$tsdate;
      $new_so_order->TaxInclusive =0;
      $new_so_order->Email_Sent =1;
      $new_so_order->Address1 =$customer_details->post1;
      $new_so_order->Address2 =$customer_details->post2;
      $new_so_order->Address3 =$customer_details->post3;
      $new_so_order->Address4 =$customer_details->post4;
      $new_so_order->Address5 ='';
      $new_so_order->Address6 ='';
      $new_so_order->PAddress1 ='';
      $new_so_order->PAddress2 ='';
      $new_so_order->PAddress3 ='';
      $new_so_order->PAddress4 ='';
      $new_so_order->PAddress5 ='';
      $new_so_order->PAddress6 ='';
      $new_so_order->DelMethodID =0;
      $new_so_order->DocRepID =$customer_details->repid;
      $new_so_order->OrderNum =$tsonum;
      $new_so_order->DeliveryNote ='';
      $new_so_order->InvDisc =0;
      $new_so_order->InvDiscReasonID =0;
      $new_so_order->Message1 ='';
      $new_so_order->Message2 ='';
      $new_so_order->Message3 ='';
      $new_so_order->ProjectID =2;
      $new_so_order->TillID =0;
      $new_so_order->POSAmntTendered =0;
      $new_so_order->POSChange =0;
      $new_so_order->GrvSplitFixedCost =0;
      $new_so_order->GrvSplitFixedAmnt =0;
      $new_so_order->OrderStatusID =0;
      $new_so_order->OrderPriorityID=4;
      $new_so_order->ExtOrderNum =$textorderno;
      $new_so_order->ForeignCurrencyID =$customer_details->icurrencyid;
      $new_so_order->InvDiscAmnt =0; 
      $new_so_order->InvDiscAmntEx =0;
      $new_so_order->InvTotExclDEx =$tinvamtexcl;
      $new_so_order->InvTotTaxDEx =$tinvamttax;
      $new_so_order->InvTotInclDEx =$tinvamtincl;
      $new_so_order->InvTotExcl =$tinvamtexcl;
      $new_so_order->InvTotTax =$tinvamttax;
      $new_so_order->InvTotIncl =$tinvamtincl;
      $new_so_order->OrdDiscAmnt = 0;
      $new_so_order->OrdDiscAmntEx =0;
      $new_so_order->OrdTotExclDEx =$tordamtexcl;
      $new_so_order->OrdTotTaxDEx =$tordamttax;
      $new_so_order->OrdTotInclDEx =$tordamtincl;
      $new_so_order->OrdTotExcl =$tordamtexcl;
      $new_so_order->OrdTotTax =$tordamttax;
      $new_so_order->OrdTotIncl =$tordamtincl;
      $new_so_order->bUseFixedPrices =0;
      $new_so_order->iDocPrinted =0;
      $new_so_order->iINVNUMAgentID =1;
      $new_so_order->fExchangeRate =$exc_rate;
      $new_so_order->fGrvSplitFixedAmntForeign =0;
      $new_so_order->fInvDiscAmntForeign =0;
      $new_so_order->fInvDiscAmntExForeign =0;
      $new_so_order->fInvTotExclDExForeign =$tfcinvamtexcl;
      $new_so_order->fInvTotTaxDExForeign =$tfcinvamttax;
      $new_so_order->fInvTotInclDExForeign =$tfcinvamtincl;
      $new_so_order->fInvTotExclForeign =$tfcinvamtexcl;
      $new_so_order->fInvTotTaxForeign =$tfcinvamttax;
      $new_so_order->fInvTotInclForeign =$tfcinvamtincl;
      $new_so_order->fOrdDiscAmntForeign =0;
      $new_so_order->fOrdDiscAmntExForeign =0;
      $new_so_order->fOrdTotExclDExForeign =$tfcordamtexcl;
      $new_so_order->fOrdTotTaxDExForeign =$tfcordamttax;
      $new_so_order->fOrdTotInclDExForeign =$tfcordamtincl;
      $new_so_order->fOrdTotExclForeign =$tfcordamtexcl;
      $new_so_order->fOrdTotTaxForeign =$tfcordamttax;
      $new_so_order->fOrdTotInclForeign =$tfcinvamtincl;
      $new_so_order->cTaxNumber ='';
      $new_so_order->cAccountName=$customer_details->name;
      $new_so_order->iProspectID =0;
      $new_so_order->iOpportunityID =0;
      $new_so_order->InvTotRounding =0;
      $new_so_order->OrdTotRounding = 0;
      $new_so_order->fInvTotForeignRounding =0;
      $new_so_order->fOrdTotForeignRounding =0;
      $new_so_order->bInvRounding =1;
      $new_so_order->iInvSettlementTermsID =0;
      $new_so_order->cSettlementTermInvMsg ='';
      $new_so_order->iOrderCancelReasonID =0;
      $new_so_order->iLinkedDocID =0;
      $new_so_order->bLinkedTemplate =0;
      $new_so_order->InvTotInclExRounding =$tinvamtincl;
      $new_so_order->OrdTotInclExRounding =$tordamtincl;
      $new_so_order->fInvTotInclForeignExRounding =$tfcinvamtincl;
      $new_so_order->fOrdTotInclForeignExRounding =$tfcordamtincl;
      $new_so_order->iEUNoTCID =0;
      $new_so_order->iPOAuthStatus =0;
      $new_so_order->iPOIncidentID =0;
      $new_so_order->iSupervisorID =0;
      $new_so_order->iMergedDocID =0;
      $new_so_order->iDocEmailed =0;
      $new_so_order->fDepositAmountForeign =0;
      $new_so_order->fRefundAmount =0;
      $new_so_order->bTaxPerLine =1;
      $new_so_order->fDepositAmountTotal =0;
      $new_so_order->fDepositAmountUnallocated =0;
      $new_so_order->fDepositAmountNew =0;
      $new_so_order->cContact ='';
      $new_so_order->cTelephone ='';
      $new_so_order->cFax ='';
      $new_so_order->cEmail ='1';
      $new_so_order->cCellular ='';
      $new_so_order->iInsuranceState =0;
      $new_so_order->cAuthorisedBy ='';
      $new_so_order->cClaimNumber ='';
      $new_so_order->cPolicyNumber ='';
      $new_so_order->cExcessAccName ='';
      $new_so_order->cExcessAccCont1 ='';
      $new_so_order->cExcessAccCont2 ='';
      $new_so_order->fExcessAmt =0;
      $new_so_order->fExcessPct =0;
      $new_so_order->fExcessExclusive =0;
      $new_so_order->fExcessInclusive =0;
      $new_so_order->fExcessTax =0;
      $new_so_order->fAddChargeExclusive =0;
      $new_so_order->fAddChargeTax =0;
      $new_so_order->fAddChargeInclusive =0;
      $new_so_order->fAddChargeExclusiveForeign =0;
      $new_so_order->fAddChargeTaxForeign =0;
      $new_so_order->fAddChargeInclusiveForeign =0;
      $new_so_order->fOrdAddChargeExclusive =0;
      $new_so_order->fOrdAddChargeTax =0;
      $new_so_order->fOrdAddChargeInclusive =0;
      $new_so_order->fOrdAddChargeExclusiveForeign =0;
      $new_so_order->fOrdAddChargeTaxForeign =0;
      $new_so_order->fOrdAddChargeInclusiveForeign =0;
      $new_so_order->iInvoiceSplitDocID =0;
      $new_so_order->cGIVNumber =0;
      $new_so_order->bIsDCOrder =0;
      $new_so_order->iDCBranchID =0;
      $new_so_order->iSalesBranchID =0;
      $new_so_order->InvNum_iBranchID =1;
      $new_so_order->save();

    
  
     
  //  get invoice id to use in entering line items
      $invoice_id =DB::table('invnum')
      ->where('ordernum', $tsonum)
      ->select('autoindex')
      ->first();

     

      $invoice_id = $invoice_id->autoindex;

        //update udf table

        $update_udf =DB::table('_etblUserHistLink')
        ->insert([
       'userdictid' => 101000037,
       'Tableid' => $invoice_id,
       'UserValue' => 'MR'       
      ]);

      }
      $month =$today->format('M');
    
      //  Get exchangerate
      
      $today=Carbon::parse($request->billing_date);

      $lastday =Carbon::parse($request->billing_date)->daysInMonth;

      
      $echange_rate =DB::table('currencyhist')
      ->select('fbuyrate')
      ->where('icurrencyid',$customer_details->icurrencyid)
      ->whereRaw('idCurrencyHist in (select max(idCurrencyHist) from currencyhist group by (iCurrencyID))')  
      ->first();
      // dd($echange_rate);
  
      $exc_rate =$echange_rate->fbuyrate  ?? '0';
     
    
  
      // Get tax rate     
      $tax_rate =DB::table('taxrate')
      ->where('idtaxrate',$customer_details->ideftaxtypeid)
      ->select('TaxRate')
      ->first();   
      
     
  
  
      // getting mono consolidated billed Asset
      $billing_mono_consolidated = DB::table('_bvARAccountsFull As cl')
          ->where('DCLink',$customer_details->dclink)
          ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
          ->whereYear('ReadingDate', '=', $today->year)
          ->whereMonth('ReadingDate', '=', $today->month)
  
          ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
          DB::raw('sum(cmr.moncmr) as mon_cmr_total'),
          DB::raw('sum(cmr.monpmr) as mon_pmr_total'),
          DB::raw('sum(cmr.moncmr - cmr.monpmr) as monunit'),        
          DB::raw('sum(cmr.a3mcmr - cmr.a3mpmr) as a3munit'),
          DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.ucSABillingAsset'
           
           )
          ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
          ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
           ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
           ->get();
          
  
           foreach($billing_mono_consolidated as $data){
              $asset_id = DB::table('_smtblServiceAsset As sa')
              ->where('cCode',$data->ucSABillingAsset)
              ->select('AutoIdx')
              ->first();
              
          
               $rentamt =DB::table('_smtblperiodservice As ps')
              ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
              ->Where(function($query)
               {
                $query->where('cCode','RENTAL')
                ->orwhere('cCode','RENTAL CHARGES');
               }) 
              ->select('fAmount')
              ->first();
              $data->famount =$rentamt->famount ?? null;
  
              //mono consolidated
              // "MONO"
              $counter_elapse =DB::table('_smtblcounterelapsed')
              ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
              ->Where(function($query)
               {
                $query->where('cCode','BILLMON')
                ->orwhere('cCode','BILMON');
              }) 
              ->select('iMeterId')
              ->first();
              
              
             
  
              $min_vol = _smtblrateslab::where('iBillingID',$counter_elapse->iMeterId ?? 0)
              ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
              ->groupBy('fRate',DB::raw('(iToqty)'))
              ->first();

              $copies =$data->monunit - $min_vol->min_vol;

              if($copies > 0){

                $excess_copies =$copies;
              }
              else{
                $excess_copies ='NIL';

              }



              if(($min_vol->min_vol ?? 0 ) >=1000000){
                  $data->min_mono_vol = null;
                 
                 }
                 else{
                  $data->min_mono_vol =$min_vol->min_vol ?? null;
                 }


      
              
                 //calculate on flat rate  
              if( ($min_vol->min_vol ?? 0)  >=1000000 ){
                
  
                  $total_frate =(($min_vol->fRate ?? 0) *($data->monunit ?? 0));
                  $rate_frate =number_format($min_vol->fRate ?? 0,4);

                  $data->total ='';
  
                  
                 
                  $t_mono_cmr = $data->mon_cmr_total;
                  $t_mon_pmr = $data->mon_pmr_total;
                  $tunits =$data->monunit;
                  $mono_rate =$rate_frate ?? 0;
                  $min_mono_vol ='';
                  $tbillmeter = "BILLMON";
                  $treadingdate = "1900-01-01";
                  $total_mon =$total_frate ?? 0;

                  $total_mono_cons =$total_mon;
                if($tax_rate->TaxRate > 0){
                  if($customer_details->icurrencyid ==0){
                    $tlineamtexcl = $total_mono_cons;
                    $tlineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
                    $tlineamtincl =($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
                    $tfclineamtexcl = 0;
                    $tfclineamttax = 0;
                    $tfclineamtincl = 0;
                  }
                  else{
                    $tfclineamtexcl = $total_mono_cons;
                    $tfclineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
                    $tfclineamtincl = ($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
                    $tlineamtexcl = $tfclineamtexcl *$exc_rate;
                    $tlineamttax = $tfclineamttax * $exc_rate;

                    $tlineamtincl = $tfclineamtincl * $exc_rate;
                  }
                }
            else{
              if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_mono_cons;
              $tlineamttax = 0;
              $tlineamtincl =$total_mono_cons;
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
              }
              else{

                $tfclineamtexcl = $total_mono_cons;
                $tfclineamttax = 0;
                $tfclineamtincl = $total_mono_cons;
                $tlineamtexcl = $tfclineamtexcl *$exc_rate;
                $tlineamttax = 0;
                $tlineamtincl = $tfclineamtincl * $exc_rate;
              }


            }  
  
          
         
  
          $tstockid = 17987;          
          $tlinedesc = "MONTHLY MONO BILLING CHARGES-B/W";  
           $tlinenote ="COPIES DONE: $tunits\r\n\r\n BILLING From 1st to $lastday $month $today->year";
          


          $_btblInvoiceLines = new _btblInvoiceLines;
           $_btblInvoiceLines->iInvoiceID =$invoice_id;
           $_btblInvoiceLines->iOrigLineID =0;
           $_btblInvoiceLines->iGrvLineID =0;
           $_btblInvoiceLines->iLineDocketMode =0; 
           $_btblInvoiceLines->cDescription =$tlinedesc; 
           $_btblInvoiceLines->iUnitsOfMeasureStockingID=0; 
           $_btblInvoiceLines->iUnitsOfMeasureCategoryID=0;
           $_btblInvoiceLines->iUnitsOfMeasureID=0;
           $_btblInvoiceLines->fQuantity=1;
           $_btblInvoiceLines->fQtyChange=1;
           $_btblInvoiceLines->fQtyToProcess=1; 
           $_btblInvoiceLines->fQtyLastProcess=0; 
           $_btblInvoiceLines->fQtyProcessed =0; 
           $_btblInvoiceLines->fQtyReserved=0; 
           $_btblInvoiceLines->fQtyReservedChange =0;
           $_btblInvoiceLines->cLineNotes=$tlinenote; 
           $_btblInvoiceLines->fUnitPriceExcl=$tlineamtexcl; 
           $_btblInvoiceLines->fUnitPriceIncl=$tlineamtincl;
           $_btblInvoiceLines->iUnitPriceOverrideReasonID=0; 
           $_btblInvoiceLines->fUnitCost=0;
           $_btblInvoiceLines->fLineDiscount=0; 
           $_btblInvoiceLines->iLineDiscountReasonID=0;
           $_btblInvoiceLines->iReturnReasonID=0; 
           $_btblInvoiceLines->fTaxRate=$tax_rate->TaxRate; 
           $_btblInvoiceLines->bIsSerialItem=0; 
           $_btblInvoiceLines->bIsWhseItem=1;
           $_btblInvoiceLines->fAddCost=0; 
           $_btblInvoiceLines->cTradeinItem='';
           $_btblInvoiceLines->iStockCodeID=$tstockid; 
           $_btblInvoiceLines->iJobID=0;
           $_btblInvoiceLines->iWarehouseID=6;
           $_btblInvoiceLines->iTaxTypeID=$customer_details->ideftaxtypeid;
           $_btblInvoiceLines->iPriceListNameID=1;
           $_btblInvoiceLines->fQuantityLineTotIncl=$tlineamtincl;
           $_btblInvoiceLines->fQuantityLineTotExcl=$tlineamtexcl;
           $_btblInvoiceLines->fQuantityLineTotInclNoDisc=$tlineamtincl;
           $_btblInvoiceLines->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
           $_btblInvoiceLines->fQuantityLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
           $_btblInvoiceLines->fQtyChangeLineTotIncl =$tlineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExcl =$tlineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTotIncl =$tlineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExcl =$tlineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
           $_btblInvoiceLines->fQtyLastProcessLineTotIncl=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotExcl =0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDisc=0;
           $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDisc=0;
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmount=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDisc=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotIncl=0;
           $_btblInvoiceLines->fQtyProcessedLineTotExcl=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotInclNoDisc=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotExclNoDisc=0; 
           $_btblInvoiceLines->fQtyProcessedLineTaxAmount=0;
           $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDisc=0; 
           $_btblInvoiceLines->fUnitPriceExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fUnitPriceInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fUnitCostForeign=0;
           $_btblInvoiceLines->fAddCostForeign=0;
           $_btblInvoiceLines->fQuantityLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQuantityLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
           $_btblInvoiceLines->fQuantityLineTaxAmountForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
           $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyLastProcessLineTotInclForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotExclForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmountForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotInclForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotExclForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotInclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotExclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTaxAmountForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
           $_btblInvoiceLines->iLineRepID=$customer_details->repid; 
           $_btblInvoiceLines->iLineProjectID=2; 
           $_btblInvoiceLines->iLedgerAccountID=0; 
           $_btblInvoiceLines->IModule=0;
           $_btblInvoiceLines->bChargeCom=1;
           $_btblInvoiceLines->bIsLotItem=0;
          //  $_btblInvoiceLines->iLotID=0;
          //  $_btblInvoiceLines->cLotNumber='';
          //  $_btblInvoiceLines->dLotExpiryDate=null;
           $_btblInvoiceLines->iMFPID=0;
           $_btblInvoiceLines->iLineID=1;
           $_btblInvoiceLines->iLinkedLineID=0;
           $_btblInvoiceLines->fQtyLinkedUsed=null;
           $_btblInvoiceLines->fUnitPriceInclOrig=null;
           $_btblInvoiceLines->fUnitPriceExclOrig=Null;
           $_btblInvoiceLines->fUnitPriceInclForeignOrig=Null;
           $_btblInvoiceLines->fUnitPriceExclForeignOrig=0;
           $_btblInvoiceLines->iDeliveryMethodID=0;
           $_btblInvoiceLines->fQtyDeliver=0;
           $_btblInvoiceLines->dDeliveryDate=$today;
           $_btblInvoiceLines->iDeliveryStatus=0;
           $_btblInvoiceLines->fQtyForDelivery=0;
           $_btblInvoiceLines->bPromotionApplied=0;
           $_btblInvoiceLines->fPromotionPriceExcl=0;
           $_btblInvoiceLines->fPromotionPriceIncl=0;
           $_btblInvoiceLines->cPromotionCode=0;
           $_btblInvoiceLines->iSOLinkedPOLineID=0;
           $_btblInvoiceLines->fLength=0;
           $_btblInvoiceLines->fWidth=0;
           $_btblInvoiceLines->fHeight=0;
           $_btblInvoiceLines->iPieces=0;
           $_btblInvoiceLines->iPiecesToProcess=0;
           $_btblInvoiceLines->iPiecesLastProcess=0;
           $_btblInvoiceLines->iPiecesProcessed=0;
           $_btblInvoiceLines->iPiecesReserved=0;
           $_btblInvoiceLines->iPiecesDeliver=0;
           $_btblInvoiceLines->iPiecesForDelivery=0;
           $_btblInvoiceLines->fQuantityUR=1;
           $_btblInvoiceLines->fQtyChangeUR=1;
           $_btblInvoiceLines->fQtyToProcessUR=1;
           $_btblInvoiceLines->fQtyLastProcessUR=0;
           $_btblInvoiceLines->fQtyProcessedUR=0;
           $_btblInvoiceLines->fQtyReservedUR=0;
           $_btblInvoiceLines->fQtyReservedChangeUR=0;
           $_btblInvoiceLines->fQtyDeliverUR=0;
           $_btblInvoiceLines->fQtyForDeliveryUR=0;
           $_btblInvoiceLines->fQtyLinkedUsedUR=0;
           $_btblInvoiceLines->iPiecesLinkedUsed=0;
           $_btblInvoiceLines->iSalesWhseID=0;
           $_btblInvoiceLines->_btblInvoiceLines_iBranchID=1;
           $_btblInvoiceLines->udIDSOrdTxCMReadingDate=$today;
           $_btblInvoiceLines->uiIDSOrdTxCMPrevReading=$t_mon_pmr;
           $_btblInvoiceLines->uiIDSOrdTxCMCurrReading=$t_mono_cmr;
           $_btblInvoiceLines->ucIDSOrdTxCMMinVol=$min_mono_vol;
           $_btblInvoiceLines->ucIDSOrdTxCMRates=$mono_rate;
           $_btblInvoiceLines->ucIDSOrdTxCMServiceAsset=$data->ucSABillingAsset;
           $_btblInvoiceLines->ucIDSOrdTxCMMeterType="BILLMON";
  
           $_btblInvoiceLines->save();                
                  



  
  
              }

              // CALCULATE THE COMMITMENT FEE if minimum vol available

              else{
              

              
              $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
              ->where('iToqty',$min_vol->min_vol ?? 0)
              ->select('frate',)
              ->first();
              $total_min =(($rate_min->frate ?? 0) * ($min_vol->min_vol ?? 0));


              
            $t_mono_cmr =0;
            $t_mon_pmr =0;
            $tunits = $min_vol->min_vol;
            $mono_rate =$rate_min->frate ;
            $min_mono_vol =$min_vol->min_vol;
            $tbillmeter = "BILLMON";
            $treadingdate = "1900-01-01";
            $total_mon =$total_min;
            

          $total_mono_cons =$total_mon;

          $total_mono_cons =$total_mon;
          if($tax_rate->TaxRate > 0){
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_mono_cons;
              $tlineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
              $tlineamtincl =($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_mono_cons;
              $tfclineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
              $tfclineamtincl = ($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = $tfclineamttax * $exc_rate;

              $tlineamtincl = $tfclineamtincl * $exc_rate;
            }
          }
          else{
            if($customer_details->icurrencyid ==0){
            $tlineamtexcl = $total_mono_cons;
            $tlineamttax = 0;
            $tlineamtincl =$total_mono_cons;
            $tfclineamtexcl = 0;
            $tfclineamttax = 0;
            $tfclineamtincl = 0;
            }
            else{

              $tfclineamtexcl = $total_mono_cons;
              $tfclineamttax = 0;
              $tfclineamtincl = $total_mono_cons;
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = 0;
              $tlineamtincl = $tfclineamtincl * $exc_rate;
            }


          }  
          $tstockid = 17987;
          $tlinenote ="TOTAL COPIES DONE: $data->monunit\r\nEXCESS COPIES DONE: $data->monunit - $min_vol->min_vol =$excess_copies\r\n\r\n BILLING From 1st to $lastday $month $today->year";
          $tlinedesc = "MONTHLY COMMITTED COPIES CHARGES-B/W";

          $_btblInvoiceLines = new _btblInvoiceLines;
          $_btblInvoiceLines->iInvoiceID =$invoice_id;
          $_btblInvoiceLines->iOrigLineID =0;
          $_btblInvoiceLines->iGrvLineID =0;
          $_btblInvoiceLines->iLineDocketMode =0; 
          $_btblInvoiceLines->cDescription =$tlinedesc; 
          $_btblInvoiceLines->iUnitsOfMeasureStockingID=0; 
          $_btblInvoiceLines->iUnitsOfMeasureCategoryID=0;
          $_btblInvoiceLines->iUnitsOfMeasureID=0;
          $_btblInvoiceLines->fQuantity=1;
          $_btblInvoiceLines->fQtyChange=1;
          $_btblInvoiceLines->fQtyToProcess=1; 
          $_btblInvoiceLines->fQtyLastProcess=0; 
          $_btblInvoiceLines->fQtyProcessed =0; 
          $_btblInvoiceLines->fQtyReserved=0; 
          $_btblInvoiceLines->fQtyReservedChange =0;
          $_btblInvoiceLines->cLineNotes=$tlinenote; 
          $_btblInvoiceLines->fUnitPriceExcl=$tlineamtexcl; 
          $_btblInvoiceLines->fUnitPriceIncl=$tlineamtincl;
          $_btblInvoiceLines->iUnitPriceOverrideReasonID=0; 
          $_btblInvoiceLines->fUnitCost=0;
          $_btblInvoiceLines->fLineDiscount=0; 
          $_btblInvoiceLines->iLineDiscountReasonID=0;
          $_btblInvoiceLines->iReturnReasonID=0; 
          $_btblInvoiceLines->fTaxRate=$tax_rate->TaxRate; 
          $_btblInvoiceLines->bIsSerialItem=0; 
          $_btblInvoiceLines->bIsWhseItem=1;
          $_btblInvoiceLines->fAddCost=0; 
          $_btblInvoiceLines->cTradeinItem='';
          $_btblInvoiceLines->iStockCodeID=$tstockid; 
          $_btblInvoiceLines->iJobID=0;
          $_btblInvoiceLines->iWarehouseID=6;
          $_btblInvoiceLines->iTaxTypeID=$customer_details->ideftaxtypeid;
          $_btblInvoiceLines->iPriceListNameID=1;
          $_btblInvoiceLines->fQuantityLineTotIncl=$tlineamtincl;
          $_btblInvoiceLines->fQuantityLineTotExcl=$tlineamtexcl;
          $_btblInvoiceLines->fQuantityLineTotInclNoDisc=$tlineamtincl;
          $_btblInvoiceLines->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
          $_btblInvoiceLines->fQuantityLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
          $_btblInvoiceLines->fQtyChangeLineTotIncl =$tlineamtincl; 
          $_btblInvoiceLines->fQtyChangeLineTotExcl =$tlineamtexcl; 
          $_btblInvoiceLines->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
          $_btblInvoiceLines->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
          $_btblInvoiceLines->fQtyChangeLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
          $_btblInvoiceLines->fQtyToProcessLineTotIncl =$tlineamtincl; 
          $_btblInvoiceLines->fQtyToProcessLineTotExcl =$tlineamtexcl; 
          $_btblInvoiceLines->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
          $_btblInvoiceLines->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
          $_btblInvoiceLines->fQtyToProcessLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
          $_btblInvoiceLines->fQtyLastProcessLineTotIncl=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTotExcl =0; 
          $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDisc=0;
          $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDisc=0;
          $_btblInvoiceLines->fQtyLastProcessLineTaxAmount=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDisc=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotIncl=0;
          $_btblInvoiceLines->fQtyProcessedLineTotExcl=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotInclNoDisc=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotExclNoDisc=0; 
          $_btblInvoiceLines->fQtyProcessedLineTaxAmount=0;
          $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDisc=0; 
          $_btblInvoiceLines->fUnitPriceExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines->fUnitPriceInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fUnitCostForeign=0;
          $_btblInvoiceLines->fAddCostForeign=0;
          $_btblInvoiceLines->fQuantityLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fQuantityLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
          $_btblInvoiceLines->fQuantityLineTaxAmountForeign=$tfclineamttax; 
          $_btblInvoiceLines->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
          $_btblInvoiceLines->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
          $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
          $_btblInvoiceLines->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
          $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines->fQtyLastProcessLineTotInclForeign=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTotExclForeign=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDiscForeign=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDiscForeign=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTaxAmountForeign=0; 
          $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotInclForeign=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotExclForeign=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotInclNoDiscForeign=0; 
          $_btblInvoiceLines->fQtyProcessedLineTotExclNoDiscForeign=0; 
          $_btblInvoiceLines->fQtyProcessedLineTaxAmountForeign=0; 
          $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
          $_btblInvoiceLines->iLineRepID=$customer_details->repid; 
          $_btblInvoiceLines->iLineProjectID=2; 
          $_btblInvoiceLines->iLedgerAccountID=0; 
          $_btblInvoiceLines->IModule=0;
          $_btblInvoiceLines->bChargeCom=1;
          $_btblInvoiceLines->bIsLotItem=0;
         //  $_btblInvoiceLines->iLotID=0;
         //  $_btblInvoiceLines->cLotNumber='';
         //  $_btblInvoiceLines->dLotExpiryDate=null;
          $_btblInvoiceLines->iMFPID=0;
          $_btblInvoiceLines->iLineID=1;
          $_btblInvoiceLines->iLinkedLineID=0;
          $_btblInvoiceLines->fQtyLinkedUsed=null;
          $_btblInvoiceLines->fUnitPriceInclOrig=null;
          $_btblInvoiceLines->fUnitPriceExclOrig=Null;
          $_btblInvoiceLines->fUnitPriceInclForeignOrig=Null;
          $_btblInvoiceLines->fUnitPriceExclForeignOrig=0;
          $_btblInvoiceLines->iDeliveryMethodID=0;
          $_btblInvoiceLines->fQtyDeliver=0;
          $_btblInvoiceLines->dDeliveryDate=$today;
          $_btblInvoiceLines->iDeliveryStatus=0;
          $_btblInvoiceLines->fQtyForDelivery=0;
          $_btblInvoiceLines->bPromotionApplied=0;
          $_btblInvoiceLines->fPromotionPriceExcl=0;
          $_btblInvoiceLines->fPromotionPriceIncl=0;
          $_btblInvoiceLines->cPromotionCode=0;
          $_btblInvoiceLines->iSOLinkedPOLineID=0;
          $_btblInvoiceLines->fLength=0;
          $_btblInvoiceLines->fWidth=0;
          $_btblInvoiceLines->fHeight=0;
          $_btblInvoiceLines->iPieces=0;
          $_btblInvoiceLines->iPiecesToProcess=0;
          $_btblInvoiceLines->iPiecesLastProcess=0;
          $_btblInvoiceLines->iPiecesProcessed=0;
          $_btblInvoiceLines->iPiecesReserved=0;
          $_btblInvoiceLines->iPiecesDeliver=0;
          $_btblInvoiceLines->iPiecesForDelivery=0;
          $_btblInvoiceLines->fQuantityUR=1;
          $_btblInvoiceLines->fQtyChangeUR=1;
          $_btblInvoiceLines->fQtyToProcessUR=1;
          $_btblInvoiceLines->fQtyLastProcessUR=0;
          $_btblInvoiceLines->fQtyProcessedUR=0;
          $_btblInvoiceLines->fQtyReservedUR=0;
          $_btblInvoiceLines->fQtyReservedChangeUR=0;
          $_btblInvoiceLines->fQtyDeliverUR=0;
          $_btblInvoiceLines->fQtyForDeliveryUR=0;
          $_btblInvoiceLines->fQtyLinkedUsedUR=0;
          $_btblInvoiceLines->iPiecesLinkedUsed=0;
          $_btblInvoiceLines->iSalesWhseID=0;
          $_btblInvoiceLines->_btblInvoiceLines_iBranchID=1;
          $_btblInvoiceLines->udIDSOrdTxCMReadingDate=$today;
          $_btblInvoiceLines->uiIDSOrdTxCMPrevReading=$t_mon_pmr;
          $_btblInvoiceLines->uiIDSOrdTxCMCurrReading=$t_mono_cmr;
          $_btblInvoiceLines->ucIDSOrdTxCMMinVol=$min_mono_vol;
          $_btblInvoiceLines->ucIDSOrdTxCMRates=$mono_rate;
          $_btblInvoiceLines->ucIDSOrdTxCMServiceAsset=$data->ucSABillingAsset;
          $_btblInvoiceLines->ucIDSOrdTxCMMeterType="BILLMON";
 
          $_btblInvoiceLines->save();

        }

        //get the over and above copies        
        $diff_mon = (($data->monunit ?? 0) - ($min_vol->min_vol ?? 0));        
              
              
              // the value is bigger than min volume
              //slab two starts here
              if($diff_mon > 0){                
                
              $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
              ->where('iToqty',$min_vol->min_vol ?? 0)
              ->select('frate')
              ->first();             
  
  
              // we increase minimum to move to the next slab from minimum 
  
              //slab two starts
              $Increment_1 =($min_vol->min_vol ?? 0) +1;          
              
              // we get the rate for the next slab and their readings
              $next_slab =_smtblrateslab::where('iBillingID',$counter_elapse->iMeterId)
              ->where('iFromQty','<=',$Increment_1)
             ->where('iToqty','>=',$Increment_1)
             ->select('iFromQty','iToqty','fRate')
             ->first();
             
              //  we get the difference of this slab
             $diff_slab =($next_slab->iToqty - $next_slab->iFromQty)+1;
  
          //  we check if the remainder fulls fall in this slab or exceed
             $diff_bil_slab =$diff_mon -$diff_slab;           
  
             //counts fits fully in this slab the difference is negative
             if($diff_bil_slab < 0){
              $total_slab_1 =($diff_mon * $next_slab->fRate );
  
              $rate_2 =number_format($next_slab->fRate,4);            
  
              // two slabs used             
              $comb_rates =$rate_2;
              $total =($total_slab_1);
  
              $data->comb_rates = $comb_rates;
              $data->total = $total;
  
            
              
             }
  
             //  slab 3
             //the counter is still much than the previous slab difference
             if($diff_bil_slab > 0){
              $total_slab_1 =($diff_slab * $next_slab->fRate );      
              
              $rate_2 =number_format($next_slab->fRate, 4);
              
  
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
  
              $rate_3 =number_format($rate_slab2->fRate, 4);
  
              // three slabs used
  
              $comb_rates =$rate_2. '|'.$rate_3;
              $total =($total_slab_1+$total_slab_2);
  
              $data->comb_rates = $comb_rates;
              $data->total = $total;  
  
              }
  
              // slab four
              if($remaining_bil_slab > 0){
              $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;
  
              $rate_3 =number_format($rate_slab2->fRate, 4);
  
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
              $rate_4 =number_format($rate_slab3->fRate, 4);
  
              // four slabs used
  
              $comb_rates =$rate_2. '|'.$rate_3. '|'.$rate_4;
              $total =($total_slab_1+$total_slab_2 +$total_slab_3);
  
              $data->comb_rates = $comb_rates;
              $data->total = $total;
  
              }
              }
                   
              }
             
            
            
          }          
  
              
  
           }   
           
           
           
           $bill_mono_con =$billing_mono_consolidated[0] ?? '';
           $mono_consolidated =(object) $bill_mono_con; 


                   

                    
           
          if (!empty($mono_consolidated->total)){ 

           
          $t_mono_cmr = $mono_consolidated->mon_cmr_total;
          $t_mon_pmr = $mono_consolidated->mon_pmr_total;
          $tunits =$diff_mon;
          $mono_rate =$mono_consolidated->comb_rates ?? 0;
          $min_mono_vol =$mono_consolidated->min_mono_vol;
          $tbillmeter = "BILLMON";
          $treadingdate = "1900-01-01";
          $total_mon =$mono_consolidated->total ?? 0;
          

                  
            $total_mono_cons =$total_mon;
            if($tax_rate->TaxRate > 0){
              if($customer_details->icurrencyid ==0){
                $tlineamtexcl = $total_mono_cons;
                $tlineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
                $tlineamtincl =($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
                $tfclineamtexcl = 0;
                $tfclineamttax = 0;
                $tfclineamtincl = 0;
              }
              else{
                $tfclineamtexcl = $total_mono_cons;
                $tfclineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
                $tfclineamtincl = ($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
                $tlineamtexcl = $tfclineamtexcl *$exc_rate;
                $tlineamttax = $tfclineamttax * $exc_rate;

                $tlineamtincl = $tfclineamtincl * $exc_rate;
              }
            }
            else{
              if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_mono_cons;
              $tlineamttax = 0;
              $tlineamtincl =$total_mono_cons;
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
              }
              else{

                $tfclineamtexcl = $total_mono_cons;
                $tfclineamttax = 0;
                $tfclineamtincl = $total_mono_cons;
                $tlineamtexcl = $tfclineamtexcl *$exc_rate;
                $tlineamttax = 0;
                $tlineamtincl = $tfclineamtincl * $exc_rate;
              }


            }  
  
          
         
  
          $tstockid = 17987;          
          $tlinedesc = "EXCESS COPIES DONE CHARGES B/W";  
         
  
           $_btblInvoiceLines = new _btblInvoiceLines;
           $_btblInvoiceLines->iInvoiceID =$invoice_id;
           $_btblInvoiceLines->iOrigLineID =0;
           $_btblInvoiceLines->iGrvLineID =0;
           $_btblInvoiceLines->iLineDocketMode =0; 
           $_btblInvoiceLines->cDescription =$tlinedesc; 
           $_btblInvoiceLines->iUnitsOfMeasureStockingID=0; 
           $_btblInvoiceLines->iUnitsOfMeasureCategoryID=0;
           $_btblInvoiceLines->iUnitsOfMeasureID=0;
           $_btblInvoiceLines->fQuantity=1;
           $_btblInvoiceLines->fQtyChange=1;
           $_btblInvoiceLines->fQtyToProcess=1; 
           $_btblInvoiceLines->fQtyLastProcess=0; 
           $_btblInvoiceLines->fQtyProcessed =0; 
           $_btblInvoiceLines->fQtyReserved=0; 
           $_btblInvoiceLines->fQtyReservedChange =0;
           $_btblInvoiceLines->cLineNotes=''; 
           $_btblInvoiceLines->fUnitPriceExcl=$tlineamtexcl; 
           $_btblInvoiceLines->fUnitPriceIncl=$tlineamtincl;
           $_btblInvoiceLines->iUnitPriceOverrideReasonID=0; 
           $_btblInvoiceLines->fUnitCost=0;
           $_btblInvoiceLines->fLineDiscount=0; 
           $_btblInvoiceLines->iLineDiscountReasonID=0;
           $_btblInvoiceLines->iReturnReasonID=0; 
           $_btblInvoiceLines->fTaxRate=$tax_rate->TaxRate; 
           $_btblInvoiceLines->bIsSerialItem=0; 
           $_btblInvoiceLines->bIsWhseItem=1;
           $_btblInvoiceLines->fAddCost=0; 
           $_btblInvoiceLines->cTradeinItem='';
           $_btblInvoiceLines->iStockCodeID=$tstockid; 
           $_btblInvoiceLines->iJobID=0;
           $_btblInvoiceLines->iWarehouseID=6;
           $_btblInvoiceLines->iTaxTypeID=$customer_details->ideftaxtypeid;
           $_btblInvoiceLines->iPriceListNameID=1;
           $_btblInvoiceLines->fQuantityLineTotIncl=$tlineamtincl;
           $_btblInvoiceLines->fQuantityLineTotExcl=$tlineamtexcl;
           $_btblInvoiceLines->fQuantityLineTotInclNoDisc=$tlineamtincl;
           $_btblInvoiceLines->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
           $_btblInvoiceLines->fQuantityLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
           $_btblInvoiceLines->fQtyChangeLineTotIncl =$tlineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExcl =$tlineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTotIncl =$tlineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExcl =$tlineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
           $_btblInvoiceLines->fQtyLastProcessLineTotIncl=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotExcl =0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDisc=0;
           $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDisc=0;
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmount=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDisc=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotIncl=0;
           $_btblInvoiceLines->fQtyProcessedLineTotExcl=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotInclNoDisc=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotExclNoDisc=0; 
           $_btblInvoiceLines->fQtyProcessedLineTaxAmount=0;
           $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDisc=0; 
           $_btblInvoiceLines->fUnitPriceExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fUnitPriceInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fUnitCostForeign=0;
           $_btblInvoiceLines->fAddCostForeign=0;
           $_btblInvoiceLines->fQuantityLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQuantityLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
           $_btblInvoiceLines->fQuantityLineTaxAmountForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
           $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines->fQtyLastProcessLineTotInclForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotExclForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmountForeign=0; 
           $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotInclForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotExclForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotInclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTotExclNoDiscForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTaxAmountForeign=0; 
           $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
           $_btblInvoiceLines->iLineRepID=$customer_details->repid; 
           $_btblInvoiceLines->iLineProjectID=2; 
           $_btblInvoiceLines->iLedgerAccountID=0; 
           $_btblInvoiceLines->IModule=0;
           $_btblInvoiceLines->bChargeCom=1;
           $_btblInvoiceLines->bIsLotItem=0;
          //  $_btblInvoiceLines->iLotID=0;
          //  $_btblInvoiceLines->cLotNumber='';
          //  $_btblInvoiceLines->dLotExpiryDate=null;
           $_btblInvoiceLines->iMFPID=0;
           $_btblInvoiceLines->iLineID=1;
           $_btblInvoiceLines->iLinkedLineID=0;
           $_btblInvoiceLines->fQtyLinkedUsed=null;
           $_btblInvoiceLines->fUnitPriceInclOrig=null;
           $_btblInvoiceLines->fUnitPriceExclOrig=Null;
           $_btblInvoiceLines->fUnitPriceInclForeignOrig=Null;
           $_btblInvoiceLines->fUnitPriceExclForeignOrig=0;
           $_btblInvoiceLines->iDeliveryMethodID=0;
           $_btblInvoiceLines->fQtyDeliver=0;
           $_btblInvoiceLines->dDeliveryDate=$today;
           $_btblInvoiceLines->iDeliveryStatus=0;
           $_btblInvoiceLines->fQtyForDelivery=0;
           $_btblInvoiceLines->bPromotionApplied=0;
           $_btblInvoiceLines->fPromotionPriceExcl=0;
           $_btblInvoiceLines->fPromotionPriceIncl=0;
           $_btblInvoiceLines->cPromotionCode=0;
           $_btblInvoiceLines->iSOLinkedPOLineID=0;
           $_btblInvoiceLines->fLength=0;
           $_btblInvoiceLines->fWidth=0;
           $_btblInvoiceLines->fHeight=0;
           $_btblInvoiceLines->iPieces=0;
           $_btblInvoiceLines->iPiecesToProcess=0;
           $_btblInvoiceLines->iPiecesLastProcess=0;
           $_btblInvoiceLines->iPiecesProcessed=0;
           $_btblInvoiceLines->iPiecesReserved=0;
           $_btblInvoiceLines->iPiecesDeliver=0;
           $_btblInvoiceLines->iPiecesForDelivery=0;
           $_btblInvoiceLines->fQuantityUR=1;
           $_btblInvoiceLines->fQtyChangeUR=1;
           $_btblInvoiceLines->fQtyToProcessUR=1;
           $_btblInvoiceLines->fQtyLastProcessUR=0;
           $_btblInvoiceLines->fQtyProcessedUR=0;
           $_btblInvoiceLines->fQtyReservedUR=0;
           $_btblInvoiceLines->fQtyReservedChangeUR=0;
           $_btblInvoiceLines->fQtyDeliverUR=0;
           $_btblInvoiceLines->fQtyForDeliveryUR=0;
           $_btblInvoiceLines->fQtyLinkedUsedUR=0;
           $_btblInvoiceLines->iPiecesLinkedUsed=0;
           $_btblInvoiceLines->iSalesWhseID=0;
           $_btblInvoiceLines->_btblInvoiceLines_iBranchID=1;
           $_btblInvoiceLines->udIDSOrdTxCMReadingDate=$today;
           $_btblInvoiceLines->uiIDSOrdTxCMPrevReading=$t_mon_pmr;
           $_btblInvoiceLines->uiIDSOrdTxCMCurrReading=$t_mono_cmr;
           $_btblInvoiceLines->ucIDSOrdTxCMMinVol=$min_mono_vol;
           $_btblInvoiceLines->ucIDSOrdTxCMRates=$mono_rate;
           $_btblInvoiceLines->ucIDSOrdTxCMServiceAsset=$mono_consolidated->ucSABillingAsset;
           $_btblInvoiceLines->ucIDSOrdTxCMMeterType="BILLMON";
  
           $_btblInvoiceLines->save();

          }
  
  

          ////////////////////////////////////////////////////////////////////////////
          ///////////////////////////////////////////////////////////////////////////
          // calculation color consolidated
  
             $asset_id_color = DB::table('_smtblServiceAsset As sa')
            ->where('cCode',$value['ucSABillingAsset'])
            ->select('AutoIdx')
            ->first();

            
         

            $color_counter_elapse =DB::table('_smtblcounterelapsed')
            ->where('iServiceAssetId',$asset_id_color->AutoIdx ?? 0)
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


                          
  
          if(!empty($color_counter_elapse)){
            
          $billing_color_consolidated = DB::table('_bvARAccountsFull As cl')
          ->where('DCLink',$customer_details->dclink)
          ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
          ->whereYear('ReadingDate', '=', $today->year)
          ->whereMonth('ReadingDate', '=', $today->month)
  
          ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
          DB::raw('sum(cmr.ColCMR) as color_cmr_total'),
          DB::raw('sum(cmr.ColPMR) as color_pmr_total'),
          DB::raw('sum(cmr.ColCMR - cmr.ColPMR) as colunit'),   
          DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.ucSABillingAsset'
          
           )
          ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
          ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
          ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
          ->get();
  
  
           
          foreach($billing_color_consolidated as $data){
            $asset_id = DB::table('_smtblServiceAsset As sa')
            ->where('cCode',$data->ucSABillingAsset)
            ->select('AutoIdx')
            ->first();            
          

            // color consolidated calculation

             //color
           $color_counter_elapse =DB::table('_smtblcounterelapsed')
           ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
           ->Where(function($query)
           {
               $query->where('cCode','BILLCOL')
               ->orwhere('cCode','BILCOL');
           })
           ->select('iMeterId')
           ->first();

           $min_vol_color = _smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId ?? 0)
            ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
            ->groupBy('fRate',DB::raw('(iToqty)'))
            ->first();

          

            

            $copiesCol =$data->colunit - $min_vol_color->min_vol;

            if($copiesCol > 0){

              $excess_copiesC =$copiesCol;
            }
            else{
              $excess_copiesC ='NIL';

            }


                       

          
            if(!empty($min_vol_color))
            {
           
        

            if($min_vol_color->min_vol >= 1000000){
              

            $total_color =$min_vol_color->fRate * $data->colunit;
            $rate_frate =number_format($min_vol_color->fRate,4);

            $data->total_color ='';                


            
            $t_color_cmr = $data->color_cmr_total;
            $t_color_pmr = $data->color_pmr_total;
            $tunits =  $data->colunit;
            $color_rate =$rate_frate ?? 0;
            $min_color_vol ='';
            $tbillmeter = "BILLCOL";
            $treadingdate = "1900-01-01";
            $total_col_con =$total_color ?? 0;


            $total_color_cons =$total_col_con;
          if($tax_rate->TaxRate > 0){
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_color_cons;
              $tlineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
              $tlineamtincl =($total_color_cons * (1 + $tax_rate->TaxRate / 100));
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_color_cons;
              $tfclineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
              $tfclineamtincl = ($total_color_cons * (1 + $tax_rate->TaxRate / 100));
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = $tfclineamttax * $exc_rate;
              $tlineamtincl = $tfclineamtincl * $exc_rate;
            }
          }
          else{
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_color_cons;
              $tlineamttax = 0;
              $tlineamtincl =$total_color_cons;
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_color_cons;
              $tfclineamttax = 0;
              $tfclineamtincl = $total_color_cons;
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = 0;
              $tlineamttax = $tfclineamtincl * $exc_rate;
            }

          }
      
          
  
          $tstockid = 17987;
          $tlinenote ="COPIES DONE: $tunits";
          $tlinedesc = "MONTHLY BILLING COPIES CHARGES-COLOR";


          $_btblInvoiceLines_color = new _btblInvoiceLines;
          $_btblInvoiceLines_color->iInvoiceID =$invoice_id;
          $_btblInvoiceLines_color->iOrigLineID =0;
          $_btblInvoiceLines_color->iGrvLineID =0;
          $_btblInvoiceLines_color->iLineDocketMode =0; 
          $_btblInvoiceLines_color->cDescription =$tlinedesc; 
          $_btblInvoiceLines_color->iUnitsOfMeasureStockingID=0; 
          $_btblInvoiceLines_color->iUnitsOfMeasureCategoryID=0;
          $_btblInvoiceLines_color->iUnitsOfMeasureID=0;
          $_btblInvoiceLines_color->fQuantity=1;
          $_btblInvoiceLines_color->fQtyChange=1;
          $_btblInvoiceLines_color->fQtyToProcess=1; 
          $_btblInvoiceLines_color->fQtyLastProcess=0; 
          $_btblInvoiceLines_color->fQtyProcessed =0; 
          $_btblInvoiceLines_color->fQtyReserved=0; 
          $_btblInvoiceLines_color->fQtyReservedChange =0;
          $_btblInvoiceLines_color->cLineNotes=$tlinenote; 
          $_btblInvoiceLines_color->fUnitPriceExcl=$tlineamtexcl; 
          $_btblInvoiceLines_color->fUnitPriceIncl=$tlineamtincl;
          $_btblInvoiceLines_color->iUnitPriceOverrideReasonID=0; 
          $_btblInvoiceLines_color->fUnitCost=0;
          $_btblInvoiceLines_color->fLineDiscount=0; 
          $_btblInvoiceLines_color->iLineDiscountReasonID=0;
          $_btblInvoiceLines_color->iReturnReasonID=0; 
          $_btblInvoiceLines_color->fTaxRate=$tax_rate->TaxRate; 
          $_btblInvoiceLines_color->bIsSerialItem=0; 
          $_btblInvoiceLines_color->bIsWhseItem=1;
          $_btblInvoiceLines_color->fAddCost=0; 
          $_btblInvoiceLines_color->cTradeinItem='';
          $_btblInvoiceLines_color->iStockCodeID=$tstockid; 
          $_btblInvoiceLines_color->iJobID=0;
          $_btblInvoiceLines_color->iWarehouseID=6;
          $_btblInvoiceLines_color->iTaxTypeID=$customer_details->ideftaxtypeid;
          $_btblInvoiceLines_color->iPriceListNameID=1;
          $_btblInvoiceLines_color->fQuantityLineTotIncl=$tlineamtincl;
          $_btblInvoiceLines_color->fQuantityLineTotExcl=$tlineamtexcl;
          $_btblInvoiceLines_color->fQuantityLineTotInclNoDisc=$tlineamtincl;
          $_btblInvoiceLines_color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQuantityLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
          $_btblInvoiceLines_color->fQtyChangeLineTotIncl =$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExcl =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotIncl =$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotIncl=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExcl =0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDisc=0;
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDisc=0;
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmount=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDisc=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotIncl=0;
          $_btblInvoiceLines_color->fQtyProcessedLineTotExcl=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDisc=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDisc=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmount=0;
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDisc=0; 
          $_btblInvoiceLines_color->fUnitPriceExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fUnitPriceInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fUnitCostForeign=0;
          $_btblInvoiceLines_color->fAddCostForeign=0;
          $_btblInvoiceLines_color->fQuantityLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
          $_btblInvoiceLines_color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotInclForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExclForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotInclForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotExclForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
          $_btblInvoiceLines_color->iLineRepID=$customer_details->repid; 
          $_btblInvoiceLines_color->iLineProjectID=2; 
          $_btblInvoiceLines_color->iLedgerAccountID=0; 
          $_btblInvoiceLines_color->IModule=0;
          $_btblInvoiceLines_color->bChargeCom=1;
          $_btblInvoiceLines_color->bIsLotItem=0;
         //  $_btblInvoiceLines_color->iLotID=0;
         //  $_btblInvoiceLines_color->cLotNumber='';
         //  $_btblInvoiceLines_color->dLotExpiryDate=null;
          $_btblInvoiceLines_color->iMFPID=0;
          $_btblInvoiceLines_color->iLineID=1;
          $_btblInvoiceLines_color->iLinkedLineID=0;
          $_btblInvoiceLines_color->fQtyLinkedUsed=null;
          $_btblInvoiceLines_color->fUnitPriceInclOrig=null;
          $_btblInvoiceLines_color->fUnitPriceExclOrig=Null;
          $_btblInvoiceLines_color->fUnitPriceInclForeignOrig=Null;
          $_btblInvoiceLines_color->fUnitPriceExclForeignOrig=0;
          $_btblInvoiceLines_color->iDeliveryMethodID=0;
          $_btblInvoiceLines_color->fQtyDeliver=0;
          $_btblInvoiceLines_color->dDeliveryDate=$today;
          $_btblInvoiceLines_color->iDeliveryStatus=0;
          $_btblInvoiceLines_color->fQtyForDelivery=0;
          $_btblInvoiceLines_color->bPromotionApplied=0;
          $_btblInvoiceLines_color->fPromotionPriceExcl=0;
          $_btblInvoiceLines_color->fPromotionPriceIncl=0;
          $_btblInvoiceLines_color->cPromotionCode=0;
          $_btblInvoiceLines_color->iSOLinkedPOLineID=0;
          $_btblInvoiceLines_color->fLength=0;
          $_btblInvoiceLines_color->fWidth=0;
          $_btblInvoiceLines_color->fHeight=0;
          $_btblInvoiceLines_color->iPieces=0;
          $_btblInvoiceLines_color->iPiecesToProcess=0;
          $_btblInvoiceLines_color->iPiecesLastProcess=0;
          $_btblInvoiceLines_color->iPiecesProcessed=0;
          $_btblInvoiceLines_color->iPiecesReserved=0;
          $_btblInvoiceLines_color->iPiecesDeliver=0;
          $_btblInvoiceLines_color->iPiecesForDelivery=0;
          $_btblInvoiceLines_color->fQuantityUR=1;
          $_btblInvoiceLines_color->fQtyChangeUR=1;
          $_btblInvoiceLines_color->fQtyToProcessUR=1;
          $_btblInvoiceLines_color->fQtyLastProcessUR=0;
          $_btblInvoiceLines_color->fQtyProcessedUR=0;
          $_btblInvoiceLines_color->fQtyReservedUR=0;
          $_btblInvoiceLines_color->fQtyReservedChangeUR=0;
          $_btblInvoiceLines_color->fQtyDeliverUR=0;
          $_btblInvoiceLines_color->fQtyForDeliveryUR=0;
          $_btblInvoiceLines_color->fQtyLinkedUsedUR=0;
          $_btblInvoiceLines_color->iPiecesLinkedUsed=0;
          $_btblInvoiceLines_color->iSalesWhseID=0;
          $_btblInvoiceLines_color->_btblInvoiceLines_iBranchID=1;
          $_btblInvoiceLines_color->udIDSOrdTxCMReadingDate=$today;
          $_btblInvoiceLines_color->uiIDSOrdTxCMPrevReading=$t_color_pmr;
          $_btblInvoiceLines_color->uiIDSOrdTxCMCurrReading=$t_color_cmr;
          $_btblInvoiceLines_color->ucIDSOrdTxCMMinVol=$min_color_vol;
          $_btblInvoiceLines_color->ucIDSOrdTxCMRates=$color_rate;
          $_btblInvoiceLines_color->ucIDSOrdTxCMServiceAsset=$data->ucSABillingAsset;
          $_btblInvoiceLines_color->ucIDSOrdTxCMMeterType="BILLCOL";  
          $_btblInvoiceLines_color->save();
            








            }




           
            else{
              //calculated commited amount                   

             $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
            ->where('iToqty',$min_vol_color->min_vol)
            ->select('frate')
            ->first();
             $total_min_color =($rate_min_color->frate * $min_vol_color->min_vol);

            $rate =number_format($rate_min_color->frate,4);

           


            $t_color_cmr =0;
            $t_color_pmr =0;
            $tunits = $data-> colunit;
            $color_rate =$rate;
            $min_color_vol =$min_vol_color->min_vol;
            $tbillmeter = "BILLCOL";
            $treadingdate = "1900-01-01";
            $total_col_con =$total_min_color ?? 0;
            
      
      
          $total_color_cons =$total_col_con;

          if($tax_rate->TaxRate > 0){
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_color_cons;
              $tlineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
              $tlineamtincl =($total_color_cons * (1 + $tax_rate->TaxRate / 100));
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_color_cons;
              $tfclineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
              $tfclineamtincl = ($total_color_cons * (1 + $tax_rate->TaxRate / 100));
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = $tfclineamttax * $exc_rate;
              $tlineamtincl = $tfclineamtincl * $exc_rate;
            }
          }
          else{
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_color_cons;
              $tlineamttax = 0;
              $tlineamtincl =$total_color_cons;
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_color_cons;
              $tfclineamttax = 0;
              $tfclineamtincl = $total_color_cons;
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = 0;
              $tlineamttax = $tfclineamtincl * $exc_rate;
            }

          }

          $tstockid = 17987;
          $tlinenote ="TOTAL COPIES DONE: $tunits\r\nEXCESS COPIES DONE: $data->colunit - $min_vol_color->min_vol =$copiesCol";
          $tlinedesc = "MONTHLY COMMITTED COPIES CHARGES-COLOR";


          $_btblInvoiceLines_color = new _btblInvoiceLines;
          $_btblInvoiceLines_color->iInvoiceID =$invoice_id;
          $_btblInvoiceLines_color->iOrigLineID =0;
          $_btblInvoiceLines_color->iGrvLineID =0;
          $_btblInvoiceLines_color->iLineDocketMode =0; 
          $_btblInvoiceLines_color->cDescription =$tlinedesc; 
          $_btblInvoiceLines_color->iUnitsOfMeasureStockingID=0; 
          $_btblInvoiceLines_color->iUnitsOfMeasureCategoryID=0;
          $_btblInvoiceLines_color->iUnitsOfMeasureID=0;
          $_btblInvoiceLines_color->fQuantity=1;
          $_btblInvoiceLines_color->fQtyChange=1;
          $_btblInvoiceLines_color->fQtyToProcess=1; 
          $_btblInvoiceLines_color->fQtyLastProcess=0; 
          $_btblInvoiceLines_color->fQtyProcessed =0; 
          $_btblInvoiceLines_color->fQtyReserved=0; 
          $_btblInvoiceLines_color->fQtyReservedChange =0;
          $_btblInvoiceLines_color->cLineNotes=$tlinenote; 
          $_btblInvoiceLines_color->fUnitPriceExcl=$tlineamtexcl; 
          $_btblInvoiceLines_color->fUnitPriceIncl=$tlineamtincl;
          $_btblInvoiceLines_color->iUnitPriceOverrideReasonID=0; 
          $_btblInvoiceLines_color->fUnitCost=0;
          $_btblInvoiceLines_color->fLineDiscount=0; 
          $_btblInvoiceLines_color->iLineDiscountReasonID=0;
          $_btblInvoiceLines_color->iReturnReasonID=0; 
          $_btblInvoiceLines_color->fTaxRate=$tax_rate->TaxRate; 
          $_btblInvoiceLines_color->bIsSerialItem=0; 
          $_btblInvoiceLines_color->bIsWhseItem=1;
          $_btblInvoiceLines_color->fAddCost=0; 
          $_btblInvoiceLines_color->cTradeinItem='';
          $_btblInvoiceLines_color->iStockCodeID=$tstockid; 
          $_btblInvoiceLines_color->iJobID=0;
          $_btblInvoiceLines_color->iWarehouseID=6;
          $_btblInvoiceLines_color->iTaxTypeID=$customer_details->ideftaxtypeid;
          $_btblInvoiceLines_color->iPriceListNameID=1;
          $_btblInvoiceLines_color->fQuantityLineTotIncl=$tlineamtincl;
          $_btblInvoiceLines_color->fQuantityLineTotExcl=$tlineamtexcl;
          $_btblInvoiceLines_color->fQuantityLineTotInclNoDisc=$tlineamtincl;
          $_btblInvoiceLines_color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQuantityLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
          $_btblInvoiceLines_color->fQtyChangeLineTotIncl =$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExcl =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotIncl =$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmount =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotIncl=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExcl =0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDisc=0;
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDisc=0;
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmount=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDisc=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotIncl=0;
          $_btblInvoiceLines_color->fQtyProcessedLineTotExcl=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDisc=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDisc=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmount=0;
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDisc=0; 
          $_btblInvoiceLines_color->fUnitPriceExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fUnitPriceInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fUnitCostForeign=0;
          $_btblInvoiceLines_color->fAddCostForeign=0;
          $_btblInvoiceLines_color->fQuantityLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
          $_btblInvoiceLines_color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
          $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotInclForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExclForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountForeign=0; 
          $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotInclForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotExclForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDiscForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountForeign=0; 
          $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
          $_btblInvoiceLines_color->iLineRepID=$customer_details->repid; 
          $_btblInvoiceLines_color->iLineProjectID=2; 
          $_btblInvoiceLines_color->iLedgerAccountID=0; 
          $_btblInvoiceLines_color->IModule=0;
          $_btblInvoiceLines_color->bChargeCom=1;
          $_btblInvoiceLines_color->bIsLotItem=0;
         //  $_btblInvoiceLines_color->iLotID=0;
         //  $_btblInvoiceLines_color->cLotNumber='';
         //  $_btblInvoiceLines_color->dLotExpiryDate=null;
          $_btblInvoiceLines_color->iMFPID=0;
          $_btblInvoiceLines_color->iLineID=1;
          $_btblInvoiceLines_color->iLinkedLineID=0;
          $_btblInvoiceLines_color->fQtyLinkedUsed=null;
          $_btblInvoiceLines_color->fUnitPriceInclOrig=null;
          $_btblInvoiceLines_color->fUnitPriceExclOrig=Null;
          $_btblInvoiceLines_color->fUnitPriceInclForeignOrig=Null;
          $_btblInvoiceLines_color->fUnitPriceExclForeignOrig=0;
          $_btblInvoiceLines_color->iDeliveryMethodID=0;
          $_btblInvoiceLines_color->fQtyDeliver=0;
          $_btblInvoiceLines_color->dDeliveryDate=$today;
          $_btblInvoiceLines_color->iDeliveryStatus=0;
          $_btblInvoiceLines_color->fQtyForDelivery=0;
          $_btblInvoiceLines_color->bPromotionApplied=0;
          $_btblInvoiceLines_color->fPromotionPriceExcl=0;
          $_btblInvoiceLines_color->fPromotionPriceIncl=0;
          $_btblInvoiceLines_color->cPromotionCode=0;
          $_btblInvoiceLines_color->iSOLinkedPOLineID=0;
          $_btblInvoiceLines_color->fLength=0;
          $_btblInvoiceLines_color->fWidth=0;
          $_btblInvoiceLines_color->fHeight=0;
          $_btblInvoiceLines_color->iPieces=0;
          $_btblInvoiceLines_color->iPiecesToProcess=0;
          $_btblInvoiceLines_color->iPiecesLastProcess=0;
          $_btblInvoiceLines_color->iPiecesProcessed=0;
          $_btblInvoiceLines_color->iPiecesReserved=0;
          $_btblInvoiceLines_color->iPiecesDeliver=0;
          $_btblInvoiceLines_color->iPiecesForDelivery=0;
          $_btblInvoiceLines_color->fQuantityUR=1;
          $_btblInvoiceLines_color->fQtyChangeUR=1;
          $_btblInvoiceLines_color->fQtyToProcessUR=1;
          $_btblInvoiceLines_color->fQtyLastProcessUR=0;
          $_btblInvoiceLines_color->fQtyProcessedUR=0;
          $_btblInvoiceLines_color->fQtyReservedUR=0;
          $_btblInvoiceLines_color->fQtyReservedChangeUR=0;
          $_btblInvoiceLines_color->fQtyDeliverUR=0;
          $_btblInvoiceLines_color->fQtyForDeliveryUR=0;
          $_btblInvoiceLines_color->fQtyLinkedUsedUR=0;
          $_btblInvoiceLines_color->iPiecesLinkedUsed=0;
          $_btblInvoiceLines_color->iSalesWhseID=0;
          $_btblInvoiceLines_color->_btblInvoiceLines_iBranchID=1;
          $_btblInvoiceLines_color->udIDSOrdTxCMReadingDate=$today;
          $_btblInvoiceLines_color->uiIDSOrdTxCMPrevReading=$t_color_pmr;
          $_btblInvoiceLines_color->uiIDSOrdTxCMCurrReading=$t_color_cmr;
          $_btblInvoiceLines_color->ucIDSOrdTxCMMinVol=$min_color_vol;
          $_btblInvoiceLines_color->ucIDSOrdTxCMRates=$color_rate;
          $_btblInvoiceLines_color->ucIDSOrdTxCMServiceAsset=$data->ucSABillingAsset;
          $_btblInvoiceLines_color->ucIDSOrdTxCMMeterType="BILLCOL";
 
          $_btblInvoiceLines_color->save();

        }





          $diff_color = ($data->colunit -($min_vol_color->min_vol ?? 0));

         
        
            if($diff_color > 0){
                        

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

            $rate_2 =number_format($next_slab_col->fRate,4);         

            
            // two slabs used 
            
            $comb_rates_color =$rate_2;
            $total_color =($total_slab_2_col);

            $data->comb_rates_color = $comb_rates_color;
            $data->total_color = $total_color;



            }

            // slab three
            if($diff_bal_slab > 0){
            $total_slab_2_col =($diff_slab_color * $next_slab_col->fRate );  
            
            $rate_2 =number_format($next_slab_col->fRate,4); 

            $diff_remaining2_color = ($diff_bal_slab - $diff_slab_color);

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

            $diff_remaining3_color =$diff_remaining2_color -  $slab_3_diff;

            if($diff_remaining3_color < 0){

            $total_slab_3_col =($diff_remaining2_color * $rate_slab3_color->fRate );

            $rate_3 =number_format($rate_slab3_color->fRate,4); 

             // three slabs used 
            
             $comb_rates_color =$rate_2. '|'.$rate_3;
             $total_color =($total_slab_2_col +$total_slab_3_col);
 
             $data->comb_rates_color = $comb_rates_color;
             $data->total_color = $total_color;


            }
           if($diff_remaining3_color > 0){

            $total_slab_3_col =($slab_3_diff * $rate_slab3_color->fRate );

            $rate_3 =number_format($rate_slab3_color->fRate,4); 

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

            $rate_4 =number_format($rate_slab4_color->fRate,4); 
            
             // four slabs used 
            
             $comb_rates_color =$rate_2. '|'.$rate_3. '|'. $rate_4;
             $total_color =($total_slab_2_col +$total_slab_3_col+$total_slab_4_col);
 
             $data->comb_rates_color = $comb_rates_color;
             $data->total_color = $total_color;


            }

           }

        }

}



        
  
    $min_color_vol = _smtblrateslab::where('iBillingID',$color_counter_elapse->iMeterId)
    ->select(DB::raw('min(iToqty) as min_color_vol'))
    ->first();

    if($min_color_vol->min_color_vol >=1000000){
      $data->min_color_vol = null;
     }
     else{
      $data->min_color_vol =$min_color_vol->min_color_vol ?? null;
     }        
      

    } 
    
  }
  
           
       
    $bill_color_con =$billing_color_consolidated[0] ?? '';
    $color_consolidated =(object) $bill_color_con;

    

        

        // if no minimun volume dont save anything
         
            if(!empty($color_consolidated->total_color))
            {
           
        
  
  
        $t_color_cmr = $color_consolidated->color_cmr_total;
        $t_color_pmr = $color_consolidated->color_pmr_total;
        $tunits =  $diff_color;
        $color_rate =$color_consolidated->comb_rates_color ?? 0;
        $min_color_vol =$color_consolidated->min_color_vol ?? 0;
        $tbillmeter = "BILLCOL";
        $treadingdate = "1900-01-01";
        $total_col_con =$color_consolidated->total_color ?? 0;
        
  
  
       
          $total_color_cons =$total_col_con;
          if($tax_rate->TaxRate > 0){
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_color_cons;
              $tlineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
              $tlineamtincl =($total_color_cons * (1 + $tax_rate->TaxRate / 100));
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_color_cons;
              $tfclineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
              $tfclineamtincl = ($total_color_cons * (1 + $tax_rate->TaxRate / 100));
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = $tfclineamttax * $exc_rate;
              $tlineamtincl = $tfclineamtincl * $exc_rate;
            }
          }
          else{
            if($customer_details->icurrencyid ==0){
              $tlineamtexcl = $total_color_cons;
              $tlineamttax = 0;
              $tlineamtincl =$total_color_cons;
              $tfclineamtexcl = 0;
              $tfclineamttax = 0;
              $tfclineamtincl = 0;
            }
            else{
              $tfclineamtexcl = $total_color_cons;
              $tfclineamttax = 0;
              $tfclineamtincl = $total_color_cons;
              $tlineamtexcl = $tfclineamtexcl *$exc_rate;
              $tlineamttax = 0;
              $tlineamttax = $tfclineamtincl * $exc_rate;
            }

          }

          $copiesCol =$data->colunit - $min_vol_color->min_vol;

          if($copiesCol > 0){

            $excess_copiesC =$copiesCol;
          }
          else{
            $excess_copiesC ='NIL';

          }
        
          
  
          $tstockid = 17987;          
          $tlinedesc = "EXCESS COPIES DONE CHARGES-COLOR";
  
          $_btblInvoiceLines_color = new _btblInvoiceLines;
           $_btblInvoiceLines_color->iInvoiceID =$invoice_id;
           $_btblInvoiceLines_color->iOrigLineID =0;
           $_btblInvoiceLines_color->iGrvLineID =0;
           $_btblInvoiceLines_color->iLineDocketMode =0; 
           $_btblInvoiceLines_color->cDescription =$tlinedesc; 
           $_btblInvoiceLines_color->iUnitsOfMeasureStockingID=0; 
           $_btblInvoiceLines_color->iUnitsOfMeasureCategoryID=0;
           $_btblInvoiceLines_color->iUnitsOfMeasureID=0;
           $_btblInvoiceLines_color->fQuantity=1;
           $_btblInvoiceLines_color->fQtyChange=1;
           $_btblInvoiceLines_color->fQtyToProcess=1; 
           $_btblInvoiceLines_color->fQtyLastProcess=0; 
           $_btblInvoiceLines_color->fQtyProcessed =0; 
           $_btblInvoiceLines_color->fQtyReserved=0; 
           $_btblInvoiceLines_color->fQtyReservedChange =0;
           $_btblInvoiceLines_color->cLineNotes=''; 
           $_btblInvoiceLines_color->fUnitPriceExcl=$tlineamtexcl; 
           $_btblInvoiceLines_color->fUnitPriceIncl=$tlineamtincl;
           $_btblInvoiceLines_color->iUnitPriceOverrideReasonID=0; 
           $_btblInvoiceLines_color->fUnitCost=0;
           $_btblInvoiceLines_color->fLineDiscount=0; 
           $_btblInvoiceLines_color->iLineDiscountReasonID=0;
           $_btblInvoiceLines_color->iReturnReasonID=0; 
           $_btblInvoiceLines_color->fTaxRate=$tax_rate->TaxRate; 
           $_btblInvoiceLines_color->bIsSerialItem=0; 
           $_btblInvoiceLines_color->bIsWhseItem=1;
           $_btblInvoiceLines_color->fAddCost=0; 
           $_btblInvoiceLines_color->cTradeinItem='';
           $_btblInvoiceLines_color->iStockCodeID=$tstockid; 
           $_btblInvoiceLines_color->iJobID=0;
           $_btblInvoiceLines_color->iWarehouseID=6;
           $_btblInvoiceLines_color->iTaxTypeID=$customer_details->ideftaxtypeid;
           $_btblInvoiceLines_color->iPriceListNameID=1;
           $_btblInvoiceLines_color->fQuantityLineTotIncl=$tlineamtincl;
           $_btblInvoiceLines_color->fQuantityLineTotExcl=$tlineamtexcl;
           $_btblInvoiceLines_color->fQuantityLineTotInclNoDisc=$tlineamtincl;
           $_btblInvoiceLines_color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
           $_btblInvoiceLines_color->fQuantityLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
           $_btblInvoiceLines_color->fQtyChangeLineTotIncl =$tlineamtincl; 
           $_btblInvoiceLines_color->fQtyChangeLineTotExcl =$tlineamtexcl; 
           $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
           $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
           $_btblInvoiceLines_color->fQtyChangeLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotIncl =$tlineamtincl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTaxAmount =$tlineamttax; 
           $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotIncl=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotExcl =0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDisc=0;
           $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDisc=0;
           $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmount=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDisc=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotIncl=0;
           $_btblInvoiceLines_color->fQtyProcessedLineTotExcl=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDisc=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDisc=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTaxAmount=0;
           $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDisc=0; 
           $_btblInvoiceLines_color->fUnitPriceExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines_color->fUnitPriceInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fUnitCostForeign=0;
           $_btblInvoiceLines_color->fAddCostForeign=0;
           $_btblInvoiceLines_color->fQuantityLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines_color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
           $_btblInvoiceLines_color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
           $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines_color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
           $_btblInvoiceLines_color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
           $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
           $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
           $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotInclForeign=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotExclForeign=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountForeign=0; 
           $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotInclForeign=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotExclForeign=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDiscForeign=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDiscForeign=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountForeign=0; 
           $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
           $_btblInvoiceLines_color->iLineRepID=$customer_details->repid; 
           $_btblInvoiceLines_color->iLineProjectID=2; 
           $_btblInvoiceLines_color->iLedgerAccountID=0; 
           $_btblInvoiceLines_color->IModule=0;
           $_btblInvoiceLines_color->bChargeCom=1;
           $_btblInvoiceLines_color->bIsLotItem=0;
          //  $_btblInvoiceLines_color->iLotID=0;
          //  $_btblInvoiceLines_color->cLotNumber='';
          //  $_btblInvoiceLines_color->dLotExpiryDate=null;
           $_btblInvoiceLines_color->iMFPID=0;
           $_btblInvoiceLines_color->iLineID=1;
           $_btblInvoiceLines_color->iLinkedLineID=0;
           $_btblInvoiceLines_color->fQtyLinkedUsed=null;
           $_btblInvoiceLines_color->fUnitPriceInclOrig=null;
           $_btblInvoiceLines_color->fUnitPriceExclOrig=Null;
           $_btblInvoiceLines_color->fUnitPriceInclForeignOrig=Null;
           $_btblInvoiceLines_color->fUnitPriceExclForeignOrig=0;
           $_btblInvoiceLines_color->iDeliveryMethodID=0;
           $_btblInvoiceLines_color->fQtyDeliver=0;
           $_btblInvoiceLines_color->dDeliveryDate=$today;
           $_btblInvoiceLines_color->iDeliveryStatus=0;
           $_btblInvoiceLines_color->fQtyForDelivery=0;
           $_btblInvoiceLines_color->bPromotionApplied=0;
           $_btblInvoiceLines_color->fPromotionPriceExcl=0;
           $_btblInvoiceLines_color->fPromotionPriceIncl=0;
           $_btblInvoiceLines_color->cPromotionCode=0;
           $_btblInvoiceLines_color->iSOLinkedPOLineID=0;
           $_btblInvoiceLines_color->fLength=0;
           $_btblInvoiceLines_color->fWidth=0;
           $_btblInvoiceLines_color->fHeight=0;
           $_btblInvoiceLines_color->iPieces=0;
           $_btblInvoiceLines_color->iPiecesToProcess=0;
           $_btblInvoiceLines_color->iPiecesLastProcess=0;
           $_btblInvoiceLines_color->iPiecesProcessed=0;
           $_btblInvoiceLines_color->iPiecesReserved=0;
           $_btblInvoiceLines_color->iPiecesDeliver=0;
           $_btblInvoiceLines_color->iPiecesForDelivery=0;
           $_btblInvoiceLines_color->fQuantityUR=1;
           $_btblInvoiceLines_color->fQtyChangeUR=1;
           $_btblInvoiceLines_color->fQtyToProcessUR=1;
           $_btblInvoiceLines_color->fQtyLastProcessUR=0;
           $_btblInvoiceLines_color->fQtyProcessedUR=0;
           $_btblInvoiceLines_color->fQtyReservedUR=0;
           $_btblInvoiceLines_color->fQtyReservedChangeUR=0;
           $_btblInvoiceLines_color->fQtyDeliverUR=0;
           $_btblInvoiceLines_color->fQtyForDeliveryUR=0;
           $_btblInvoiceLines_color->fQtyLinkedUsedUR=0;
           $_btblInvoiceLines_color->iPiecesLinkedUsed=0;
           $_btblInvoiceLines_color->iSalesWhseID=0;
           $_btblInvoiceLines_color->_btblInvoiceLines_iBranchID=1;
           $_btblInvoiceLines_color->udIDSOrdTxCMReadingDate=$today;
           $_btblInvoiceLines_color->uiIDSOrdTxCMPrevReading=$t_color_pmr;
           $_btblInvoiceLines_color->uiIDSOrdTxCMCurrReading=$t_color_cmr;
           $_btblInvoiceLines_color->ucIDSOrdTxCMMinVol=$min_color_vol;
           $_btblInvoiceLines_color->ucIDSOrdTxCMRates=$color_rate;
           $_btblInvoiceLines_color->ucIDSOrdTxCMServiceAsset=$color_consolidated->ucSABillingAsset;
           $_btblInvoiceLines_color->ucIDSOrdTxCMMeterType="BILLCOL";  
           $_btblInvoiceLines_color->save();
  
  
          } 
        }
  
  
  
        //  calculating rental Charges
  
        $rental_amount = $value['rental_charges'];


           
  
          $rental_charge = $rental_amount;
  
          if($rental_charge > 0){   
            
            
            $tbillmeter = "RENTAL";
            $rental_amt = $rental_charge;
            if($tax_rate->TaxRate > 0){
              if($customer_details->icurrencyid ==0){
                $tlineamtexcl = $rental_amt;
                $tlineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
                $tlineamtincl =($rental_amt * (1 + $tax_rate->TaxRate / 100));
                $tfclineamtexcl = 0;
                $tfclineamttax = 0;
                $tfclineamtincl = 0;
              }
              else{
                $tfclineamtexcl = $rental_amt;
                $tfclineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
                $tfclineamtincl = ($rental_amt * (1 + $tax_rate->TaxRate / 100));
                $tlineamtexcl = $tfclineamtexcl *$exc_rate;
                $tlineamttax = $tfclineamttax * $exc_rate;
                $tlineamtincl = $tfclineamtincl * $exc_rate;
              }
            }
            else{
              if($customer_details->icurrencyid ==0){
                $tlineamtexcl = $rental_amt;
                $tlineamttax = 0;
                $tlineamtincl =$rental_amt;
                $tfclineamtexcl = 0;
                $tfclineamttax = 0;
                $tfclineamtincl = 0;
              }
              else{
                $tfclineamtexcl = $rental_amt;
                $tfclineamttax = 0;
                $tfclineamtincl = $rental_amt ;
                $tlineamtexcl = $tfclineamtexcl *$exc_rate;
                $tlineamttax = 0;
                $tlineamtincl = $tfclineamtincl * $exc_rate;
              }



            }
  
            $tlinenote = "";                    
            $tlinedesc = "Rental From 1st to $lastday $month $today->year";          
            $tstockid = 17987;
  
            $_btblInvoiceLines_rental = new _btblInvoiceLines;
            $_btblInvoiceLines_rental->iInvoiceID =$invoice_id;
            $_btblInvoiceLines_rental->iOrigLineID =0;
            $_btblInvoiceLines_rental->iGrvLineID =0;
            $_btblInvoiceLines_rental->iLineDocketMode =0; 
            $_btblInvoiceLines_rental->cDescription =$tlinedesc; 
            $_btblInvoiceLines_rental->iUnitsOfMeasureStockingID=0; 
            $_btblInvoiceLines_rental->iUnitsOfMeasureCategoryID=0;
            $_btblInvoiceLines_rental->iUnitsOfMeasureID=0;
            $_btblInvoiceLines_rental->fQuantity=1;
            $_btblInvoiceLines_rental->fQtyChange=1;
            $_btblInvoiceLines_rental->fQtyToProcess=1; 
            $_btblInvoiceLines_rental->fQtyLastProcess=0; 
            $_btblInvoiceLines_rental->fQtyProcessed =0; 
            $_btblInvoiceLines_rental->fQtyReserved=0; 
            $_btblInvoiceLines_rental->fQtyReservedChange =0;
            $_btblInvoiceLines_rental->cLineNotes=$tlinenote; 
            $_btblInvoiceLines_rental->fUnitPriceExcl=$tlineamtexcl; 
            $_btblInvoiceLines_rental->fUnitPriceIncl=$tlineamtincl;
            $_btblInvoiceLines_rental->iUnitPriceOverrideReasonID=0; 
            $_btblInvoiceLines_rental->fUnitCost=0;
            $_btblInvoiceLines_rental->fLineDiscount=0; 
            $_btblInvoiceLines_rental->iLineDiscountReasonID=0;
            $_btblInvoiceLines_rental->iReturnReasonID=0; 
            $_btblInvoiceLines_rental->fTaxRate=$tax_rate->TaxRate; 
            $_btblInvoiceLines_rental->bIsSerialItem=0; 
            $_btblInvoiceLines_rental->bIsWhseItem=1;
            $_btblInvoiceLines_rental->fAddCost=0; 
            $_btblInvoiceLines_rental->cTradeinItem='';
            $_btblInvoiceLines_rental->iStockCodeID=$tstockid; 
            $_btblInvoiceLines_rental->iJobID=0;
            $_btblInvoiceLines_rental->iWarehouseID=6;
            $_btblInvoiceLines_rental->iTaxTypeID=$customer_details->ideftaxtypeid;
            $_btblInvoiceLines_rental->iPriceListNameID=1;
            $_btblInvoiceLines_rental->fQuantityLineTotIncl=$tlineamtincl;
            $_btblInvoiceLines_rental->fQuantityLineTotExcl=$tlineamtexcl;
            $_btblInvoiceLines_rental->fQuantityLineTotInclNoDisc=$tlineamtincl;
            $_btblInvoiceLines_rental->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
            $_btblInvoiceLines_rental->fQuantityLineTaxAmount =$tlineamttax; 
            $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotIncl =$tlineamtincl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotExcl =$tlineamtexcl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTaxAmount =$tlineamttax; 
            $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotIncl =$tlineamtincl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotExcl =$tlineamtexcl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmount =$tlineamttax; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotIncl=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotExcl =0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDisc=0;
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDisc=0;
            $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmount=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDisc=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotIncl=0;
            $_btblInvoiceLines_rental->fQtyProcessedLineTotExcl=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDisc=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDisc=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmount=0;
            $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDisc=0; 
            $_btblInvoiceLines_rental->fUnitPriceExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_rental->fUnitPriceInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fUnitCostForeign=0;
            $_btblInvoiceLines_rental->fAddCostForeign=0;
            $_btblInvoiceLines_rental->fQuantityLineTotInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fQuantityLineTotExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_rental->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
            $_btblInvoiceLines_rental->fQuantityLineTaxAmountForeign=$tfclineamttax; 
            $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
            $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
            $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclForeign=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclForeign=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDiscForeign=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDiscForeign=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountForeign=0; 
            $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotInclForeign=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotExclForeign=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDiscForeign=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDiscForeign=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountForeign=0; 
            $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
            $_btblInvoiceLines_rental->iLineRepID=$customer_details->repid; 
            $_btblInvoiceLines_rental->iLineProjectID=2; 
            $_btblInvoiceLines_rental->iLedgerAccountID=0; 
            $_btblInvoiceLines_rental->IModule=0;
            $_btblInvoiceLines_rental->bChargeCom=1;
            $_btblInvoiceLines_rental->bIsLotItem=0;
            // $_btblInvoiceLines_rental->iLotID=0;
            // $_btblInvoiceLines_rental->cLotNumber='';
            // $_btblInvoiceLines_rental->dLotExpiryDate=null;
            $_btblInvoiceLines_rental->iMFPID=0;
            $_btblInvoiceLines_rental->iLineID=1;
            $_btblInvoiceLines_rental->iLinkedLineID=0;
            $_btblInvoiceLines_rental->fQtyLinkedUsed=null;
            $_btblInvoiceLines_rental->fUnitPriceInclOrig=null;
            $_btblInvoiceLines_rental->fUnitPriceExclOrig=Null;
            $_btblInvoiceLines_rental->fUnitPriceInclForeignOrig=Null;
            $_btblInvoiceLines_rental->fUnitPriceExclForeignOrig=0;
            $_btblInvoiceLines_rental->iDeliveryMethodID=0;
            $_btblInvoiceLines_rental->fQtyDeliver=0;
            $_btblInvoiceLines_rental->dDeliveryDate=$today;
            $_btblInvoiceLines_rental->iDeliveryStatus=0;
            $_btblInvoiceLines_rental->fQtyForDelivery=0;
            $_btblInvoiceLines_rental->bPromotionApplied=0;
            $_btblInvoiceLines_rental->fPromotionPriceExcl=0;
            $_btblInvoiceLines_rental->fPromotionPriceIncl=0;
            $_btblInvoiceLines_rental->cPromotionCode=0;
            $_btblInvoiceLines_rental->iSOLinkedPOLineID=0;
            $_btblInvoiceLines_rental->fLength=0;
            $_btblInvoiceLines_rental->fWidth=0;
            $_btblInvoiceLines_rental->fHeight=0;
            $_btblInvoiceLines_rental->iPieces=0;
            $_btblInvoiceLines_rental->iPiecesToProcess=0;
            $_btblInvoiceLines_rental->iPiecesLastProcess=0;
            $_btblInvoiceLines_rental->iPiecesProcessed=0;
            $_btblInvoiceLines_rental->iPiecesReserved=0;
            $_btblInvoiceLines_rental->iPiecesDeliver=0;
            $_btblInvoiceLines_rental->iPiecesForDelivery=0;
            $_btblInvoiceLines_rental->fQuantityUR=1;
            $_btblInvoiceLines_rental->fQtyChangeUR=1;
            $_btblInvoiceLines_rental->fQtyToProcessUR=1;
            $_btblInvoiceLines_rental->fQtyLastProcessUR=0;
            $_btblInvoiceLines_rental->fQtyProcessedUR=0;
            $_btblInvoiceLines_rental->fQtyReservedUR=0;
            $_btblInvoiceLines_rental->fQtyReservedChangeUR=0;
            $_btblInvoiceLines_rental->fQtyDeliverUR=0;
            $_btblInvoiceLines_rental->fQtyForDeliveryUR=0;
            $_btblInvoiceLines_rental->fQtyLinkedUsedUR=0;
            $_btblInvoiceLines_rental->iPiecesLinkedUsed=0;
            $_btblInvoiceLines_rental->iSalesWhseID=0;
            $_btblInvoiceLines_rental->_btblInvoiceLines_iBranchID=1;
            $_btblInvoiceLines_rental->udIDSOrdTxCMReadingDate='';
            $_btblInvoiceLines_rental->uiIDSOrdTxCMPrevReading='';
            $_btblInvoiceLines_rental->uiIDSOrdTxCMCurrReading='';
            $_btblInvoiceLines_rental->ucIDSOrdTxCMMinVol='';
            $_btblInvoiceLines_rental->ucIDSOrdTxCMRates='';
            $_btblInvoiceLines_rental->ucIDSOrdTxCMServiceAsset=$mono_consolidated->ucSABillingAsset;
            $_btblInvoiceLines_rental->ucIDSOrdTxCMMeterType="RENTAL";
  
            $_btblInvoiceLines_rental->save();
  
          }    
          
          
          $total_inv =DB::table('_btblinvoicelines as lni')
          ->where('iinvoiceid',$invoice_id)
          ->select(DB::raw('sum(lni.fquantitylinetotincl) as inclamt'),
                DB::raw('sum(lni.fquantitylinetotexcl) as exclamt'),
                DB::raw('sum(lni.fquantitylinetaxamount) as taxamt'),
                DB::raw('sum(lni.fquantitylinetotinclforeign) as fcinclamt'),
                DB::raw('sum(lni.fquantitylinetotexclforeign) as fcexclamt'),
                DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt'),
                DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt')
          
          
          
          )
          ->first();
          $tinvamtexcl =$total_inv->exclamt;
          $tinvamtincl =$total_inv->inclamt;
          $tinvamttax = $total_inv->taxamt;
          $tordamtexcl = $tinvamtexcl;
          $tordamtincl =  $tinvamtincl;
          $tordamttax = $tinvamttax;
          $tfcinvamtexcl =$total_inv->fcexclamt;
          $tfcinvamtincl =$total_inv->fcinclamt;
          $tfcinvamttax =$total_inv->fctaxamt;
          $tfcordamtexcl = $tfcinvamtexcl;
          $tfcordamtincl = $tfcinvamtincl;
          $tfcordamttax = $tfcinvamttax;
  
          $inv_update =InvNum::find($invoice_id);
          
          $inv_update->InvTotExclDEx =$tinvamtexcl;
          $inv_update->InvTotTaxDEx =$tinvamttax;
          $inv_update->InvTotInclDEx =$tinvamtincl;
          $inv_update->InvTotExcl =$tinvamtexcl;
          $inv_update->InvTotTax =$tinvamttax;
          $inv_update->InvTotIncl =$tinvamtincl;
          $inv_update->OrdTotExclDEx=$tordamtexcl;
          $inv_update->OrdTotTaxDEx=$tordamttax;
          $inv_update->OrdTotInclDEx=$tordamtincl;
          $inv_update->OrdTotExcl=$tordamtexcl;
          $inv_update->OrdTotTax=$tordamttax;
          $inv_update->OrdTotIncl=$tordamtincl;
          $inv_update->fInvTotExclDExForeign=$tfcinvamtexcl;
          $inv_update->fInvTotTaxDExForeign=$tfcinvamttax;
          $inv_update->fInvTotInclDExForeign=$tfcinvamtincl;
          $inv_update->fInvTotExclForeign=$tfcinvamtexcl;
          $inv_update->fInvTotTaxForeign=$tfcinvamttax;
          $inv_update->fInvTotInclForeign=$tfcinvamtincl;
          $inv_update->fOrdTotExclDExForeign=$tfcordamtexcl;
          $inv_update->fOrdTotTaxDExForeign=$tfcordamttax;
          $inv_update->fOrdTotInclDExForeign=$tfcordamtincl;
          $inv_update->fOrdTotExclForeign=$tfcordamtexcl;
          $inv_update->fOrdTotTaxForeign=$tfcordamttax;
          $inv_update->fOrdTotInclForeign=$tfcordamtincl;
          $inv_update->InvTotInclExRounding=$tinvamtincl;
          $inv_update->OrdTotInclExRounding=$tordamtincl;
          $inv_update->fInvTotInclForeignExRounding=$tfcinvamtincl;
          $inv_update->fOrdTotInclForeignExRounding=$tfcordamtincl;
          $inv_update->save(); 
          
          
         
  
  
  
             
          


          

  
        }
///////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////



















          // joint but not consolidated

          // INVOICE TYPE 2

//         elseif($billing_type =='J' && !$con_asset && !$checkifService ){
//           $check_if_done =DB::table('client as cl')
//           ->select('cl.DCLink','cl.name','cl.account','cl.udARContractStartDate','inv.autoindex','inv.orderdate','inv.accountid',DB::raw('COUNT(inv.accountid) as idcount'),'inv.AutoIndex') 
//           ->where('uhl.UserValue', '=','MR')           
//           ->where('inv.doctype', '=',4)          
//           ->whereYear('inv.orderdate', '=', $today->year)
//           ->whereMonth('inv.orderdate', '=', $today->month)       
//           ->where('cl.Account',$value['account'])            
//           ->join('InvNum as inv','cl.DCLink','=','inv.AccountID')
//           ->join(DB::raw("(select  _etblUserHistLink.* from _etblUserHistLink
//             join _rtbluserdict on userdictid=iduserdict
//           where cFieldName='ulIDSOrdOrderType' and cTableName='InvNum') AS uhl"),'uhl.TableId','=','inv.autoindex')
//           ->groupBy('cl.DCLink','cl.name','cl.account','cl.udARContractStartDate','inv.orderdate','inv.accountid','inv.AutoIndex')
//           ->first();
      
//           //check if its done
//          if($check_if_done->idcount ?? 0 > 0){
  
//           $invoice_id= $check_if_done->AutoIndex;  
//           $customer  = $value['account'];
         
//           $customer_details =Client::where('Account',$customer)
//           ->select('dclink','name','post1','post2','post3','post4','email',DB::raw("(CASE WHEN repid = null THEN 0 ELSE repid END) AS repid"),
//           DB::raw("(CASE WHEN icurrencyid = null THEN 0 ELSE icurrencyid END) AS icurrencyid"),'ideftaxtypeid'
//           )
//            ->first(); 
         
//           } 

//          else{
//            // get customer details
//           $customer  = $value['account'];
          
//           $customer_details =Client::where('Account',$customer)
//           ->select('dclink','name','post1','post2','post3','post4','email',DB::raw("(CASE WHEN repid = null THEN 0 ELSE repid END) AS repid"),
//           DB::raw("(CASE WHEN icurrencyid = null THEN 0 ELSE icurrencyid END) AS icurrencyid"),'ideftaxtypeid'
//           )
//            ->first();  
      
          
               
//           $today=Carbon::parse($request->billing_date);

//           // Get exchangerate          
//           $echange_rate =DB::table('currencyhist')
//           ->select('fbuyrate')
//           ->where('icurrencyid',$customer_details->icurrencyid)
//           ->whereRaw('idCurrencyHist in (select max(idCurrencyHist) from currencyhist group by (iCurrencyID))')  
//           ->first();
      
//           $exc_rate =$echange_rate->fbuyrate  ?? '0';
  
     
  
//          // Get tax rate     
//           $tax_rate =DB::table('taxrate')
//           ->where('idtaxrate',$customer_details->ideftaxtypeid)
//           ->select('TaxRate')
//           ->first();
  
     
//         //get po number
//         $po_number =DB::table('_etblDocProfiles')
//         ->where('idDocProfiles',60)
//         ->select('cautonumprefix','iautonumpadlength','inextautonum')
//         ->first();

//           //secure next po number
//         $update =DB::table('_etblDocProfiles')
//         ->where('idDocProfiles',60)
//         ->update([
//           'inextautonum' => $po_number->inextautonum +1
//        ]);

  
//       //pad so number to reach min of 7 values
//        $padded_num = str_pad( $po_number->inextautonum, $po_number->iautonumpadlength , '0', STR_PAD_LEFT); 
  
      
//        //cocatenate to get so number
//        $tsonum = $po_number->cautonumprefix.$padded_num;

      
//        $month =$today->format('F');
  
       
      
//       $tinvamtexcl = 0;
//       $tinvamtincl = 0;
//       $tinvamttax = 0;
//       $tordamtexcl = 0;
//       $tordamtincl = 0;
//       $tordamttax = 0;
//       $tfcinvamtexcl = 0;
//       $tfcinvamtincl = 0;
//       $tfcinvamttax = 0;
//       $tfcordamtexcl = 0;
//       $tfcordamtincl = 0;
//       $tfcordamttax = 0;
//       $textorderno = $tsonum;
//       $torddesc = "Meter Reading Billing for $month $today->year";
//       $tsdate = $today;
                      
  
      
//       // generate so order  
//       $new_so_order =new InvNum;
//       $new_so_order->DocType =4;
//       $new_so_order->DocVersion=1;
//       $new_so_order->DocState =1;
//       $new_so_order->DocFlag=0;
//       $new_so_order->OrigDocID =0;
//       $new_so_order->InvNumber='';
//       $new_so_order->GrvNumber ='';
//       $new_so_order->GrvID =0;
//       $new_so_order->AccountID=$customer_details->dclink;    
//       $new_so_order->Description =$torddesc;
//       $new_so_order->InvDate =$tsdate;
//       $new_so_order->OrderDate =$tsdate;
//       $new_so_order->DueDate =$tsdate;
//       $new_so_order->DeliveryDate =$tsdate;
//       $new_so_order->TaxInclusive =0;
//       $new_so_order->Email_Sent =1;
//       $new_so_order->Address1 =$customer_details->post1;
//       $new_so_order->Address2 =$customer_details->post2;
//       $new_so_order->Address3 =$customer_details->post3;
//       $new_so_order->Address4 =$customer_details->post4;
//       $new_so_order->Address5 ='';
//       $new_so_order->Address6 ='';
//       $new_so_order->PAddress1 ='';
//       $new_so_order->PAddress2 ='';
//       $new_so_order->PAddress3 ='';
//       $new_so_order->PAddress4 ='';
//       $new_so_order->PAddress5 ='';
//       $new_so_order->PAddress6 ='';
//       $new_so_order->DelMethodID =0;
//       $new_so_order->DocRepID =$customer_details->repid;
//       $new_so_order->OrderNum =$tsonum;
//       $new_so_order->DeliveryNote ='';
//       $new_so_order->InvDisc =0;
//       $new_so_order->InvDiscReasonID =0;
//       $new_so_order->Message1 ='';
//       $new_so_order->Message2 ='';
//       $new_so_order->Message3 ='';
//       $new_so_order->ProjectID =2;
//       $new_so_order->TillID =0;
//       $new_so_order->POSAmntTendered =0;
//       $new_so_order->POSChange =0;
//       $new_so_order->GrvSplitFixedCost =0;
//       $new_so_order->GrvSplitFixedAmnt =0;
//       $new_so_order->OrderStatusID =0;
//       $new_so_order->OrderPriorityID=4;
//       $new_so_order->ExtOrderNum =$textorderno;
//       $new_so_order->ForeignCurrencyID =$customer_details->icurrencyid;
//       $new_so_order->InvDiscAmnt =0; 
//       $new_so_order->InvDiscAmntEx =0;
//       $new_so_order->InvTotExclDEx =$tinvamtexcl;
//       $new_so_order->InvTotTaxDEx =$tinvamttax;
//       $new_so_order->InvTotInclDEx =$tinvamtincl;
//       $new_so_order->InvTotExcl =$tinvamtexcl;
//       $new_so_order->InvTotTax =$tinvamttax;
//       $new_so_order->InvTotIncl =$tinvamtincl;
//       $new_so_order->OrdDiscAmnt = 0;
//       $new_so_order->OrdDiscAmntEx =0;
//       $new_so_order->OrdTotExclDEx =$tordamtexcl;
//       $new_so_order->OrdTotTaxDEx =$tordamttax;
//       $new_so_order->OrdTotInclDEx =$tordamtincl;
//       $new_so_order->OrdTotExcl =$tordamtexcl;
//       $new_so_order->OrdTotTax =$tordamttax;
//       $new_so_order->OrdTotIncl =$tordamtincl;
//       $new_so_order->bUseFixedPrices =0;
//       $new_so_order->iDocPrinted =0;
//       $new_so_order->iINVNUMAgentID =1;
//       $new_so_order->fExchangeRate =$exc_rate;
//       $new_so_order->fGrvSplitFixedAmntForeign =0;
//       $new_so_order->fInvDiscAmntForeign =0;
//       $new_so_order->fInvDiscAmntExForeign =0;
//       $new_so_order->fInvTotExclDExForeign =$tfcinvamtexcl;
//       $new_so_order->fInvTotTaxDExForeign =$tfcinvamttax;
//       $new_so_order->fInvTotInclDExForeign =$tfcinvamtincl;
//       $new_so_order->fInvTotExclForeign =$tfcinvamtexcl;
//       $new_so_order->fInvTotTaxForeign =$tfcinvamttax;
//       $new_so_order->fInvTotInclForeign =$tfcinvamtincl;
//       $new_so_order->fOrdDiscAmntForeign =0;
//       $new_so_order->fOrdDiscAmntExForeign =0;
//       $new_so_order->fOrdTotExclDExForeign =$tfcordamtexcl;
//       $new_so_order->fOrdTotTaxDExForeign =$tfcordamttax;
//       $new_so_order->fOrdTotInclDExForeign =$tfcordamtincl;
//       $new_so_order->fOrdTotExclForeign =$tfcordamtexcl;
//       $new_so_order->fOrdTotTaxForeign =$tfcordamttax;
//       $new_so_order->fOrdTotInclForeign =$tfcinvamtincl;
//       $new_so_order->cTaxNumber ='';
//       $new_so_order->cAccountName=$customer_details->name;
//       $new_so_order->iProspectID =0;
//       $new_so_order->iOpportunityID =0;
//       $new_so_order->InvTotRounding =0;
//       $new_so_order->OrdTotRounding = 0;
//       $new_so_order->fInvTotForeignRounding =0;
//       $new_so_order->fOrdTotForeignRounding =0;
//       $new_so_order->bInvRounding =1;
//       $new_so_order->iInvSettlementTermsID =0;
//       $new_so_order->cSettlementTermInvMsg ='';
//       $new_so_order->iOrderCancelReasonID =0;
//       $new_so_order->iLinkedDocID =0;
//       $new_so_order->bLinkedTemplate =0;
//       $new_so_order->InvTotInclExRounding =$tinvamtincl;
//       $new_so_order->OrdTotInclExRounding =$tordamtincl;
//       $new_so_order->fInvTotInclForeignExRounding =$tfcinvamtincl;
//       $new_so_order->fOrdTotInclForeignExRounding =$tfcordamtincl;
//       $new_so_order->iEUNoTCID =0;
//       $new_so_order->iPOAuthStatus =0;
//       $new_so_order->iPOIncidentID =0;
//       $new_so_order->iSupervisorID =0;
//       $new_so_order->iMergedDocID =0;
//       $new_so_order->iDocEmailed =0;
//       $new_so_order->fDepositAmountForeign =0;
//       $new_so_order->fRefundAmount =0;
//       $new_so_order->bTaxPerLine =1;
//       $new_so_order->fDepositAmountTotal =0;
//       $new_so_order->fDepositAmountUnallocated =0;
//       $new_so_order->fDepositAmountNew =0;
//       $new_so_order->cContact ='';
//       $new_so_order->cTelephone ='';
//       $new_so_order->cFax ='';
//       $new_so_order->cEmail ='1';
//       $new_so_order->cCellular ='';
//       $new_so_order->iInsuranceState =0;
//       $new_so_order->cAuthorisedBy ='';
//       $new_so_order->cClaimNumber ='';
//       $new_so_order->cPolicyNumber ='';
//       $new_so_order->cExcessAccName ='';
//       $new_so_order->cExcessAccCont1 ='';
//       $new_so_order->cExcessAccCont2 ='';
//       $new_so_order->fExcessAmt =0;
//       $new_so_order->fExcessPct =0;
//       $new_so_order->fExcessExclusive =0;
//       $new_so_order->fExcessInclusive =0;
//       $new_so_order->fExcessTax =0;
//       $new_so_order->fAddChargeExclusive =0;
//       $new_so_order->fAddChargeTax =0;
//       $new_so_order->fAddChargeInclusive =0;
//       $new_so_order->fAddChargeExclusiveForeign =0;
//       $new_so_order->fAddChargeTaxForeign =0;
//       $new_so_order->fAddChargeInclusiveForeign =0;
//       $new_so_order->fOrdAddChargeExclusive =0;
//       $new_so_order->fOrdAddChargeTax =0;
//       $new_so_order->fOrdAddChargeInclusive =0;
//       $new_so_order->fOrdAddChargeExclusiveForeign =0;
//       $new_so_order->fOrdAddChargeTaxForeign =0;
//       $new_so_order->fOrdAddChargeInclusiveForeign =0;
//       $new_so_order->iInvoiceSplitDocID =0;
//       $new_so_order->cGIVNumber =0;
//       $new_so_order->bIsDCOrder =0;
//       $new_so_order->iDCBranchID =0;
//       $new_so_order->iSalesBranchID =0;
//       $new_so_order->InvNum_iBranchID =1;
//       // $new_so_order->ulIDSOrdOrderType ='MR';
//       $new_so_order->save();
  
     
//   //  get invoice id to use in looping  line items
//       $invoice_id =DB::table('invnum')
//       ->where('ordernum', $tsonum)
//       ->select('autoindex')
//       ->first();
  
//       $invoice_id =$invoice_id->autoindex;

//        //update udf table version 11

//        $update_udf =DB::table('_etblUserHistLink')
//        ->insert([
//       'userdictid' => 60,
//       'Tableid' => $invoice_id,
//       'UserValue' => 'MR'       
//      ]);
      
//         }

      
     
//       $today=Carbon::parse($request->billing_date);

//       // Get exchangerate      
//       $echange_rate =DB::table('currencyhist')
//       ->select('fbuyrate')
//       ->where('icurrencyid',$customer_details->icurrencyid)
//       ->whereRaw('idCurrencyHist in (select max(idCurrencyHist) from currencyhist group by (iCurrencyID))')  
//       ->first();
  
//         $exc_rate =$echange_rate->fbuyrate  ?? '0';
  
       
//       // Get tax rate     
//       $tax_rate =DB::table('taxrate')
//       ->where('idtaxrate',$customer_details->ideftaxtypeid)
//       ->select('TaxRate')
//       ->first();
  
//     // mono calculation starts

//      // getting mono join non con billed Asset
//       $billing_mono_separate = DB::table('_bvARAccountsFull As cl')
//       ->where('DCLink',$customer_details->dclink)
//       ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//       ->whereYear('ReadingDate', '=', $today->year)
//       ->whereMonth('ReadingDate', '=', $today->month)

//       ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode',
//       DB::raw('sum(cmr.moncmr) as mon_cmr_total'),
//       DB::raw('sum(cmr.monpmr) as mon_pmr_total'),
//       DB::raw('sum(cmr.moncmr - cmr.monpmr) as monunit'),        
//       DB::raw('sum(cmr.a3mcmr - cmr.a3mpmr) as a3munit'),
//       DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.cCode','sa.cDescription','sa.ucSASerialNo'
    
//     )
//     ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//     ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//     ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode','sa.cDescription','sa.ucSASerialNo')
//     ->get();

       
   

//     foreach($billing_mono_separate as $data){
//        $asset_id = DB::table('_smtblServiceAsset As sa')
//        ->where('cCode',$data->cCode)
//        ->select('AutoIdx')
//        ->first();
      
   
//         $rentamt =DB::table('_smtblperiodservice As ps')
//        ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
//        ->Where(function($query)
//         {
//         $query->where('cCode','RENTAL')
//         ->orwhere('cCode','RENTAL CHARGES');
//         }) 
//        ->select('fAmount')
//        ->first();
//        $data->famount =$rentamt->famount ?? null;

//        //mono join non consolidated
//        // "MONO"
//        $counter_elapse =DB::table('_smtblcounterelapsed')
//        ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
//        ->Where(function($query)
//                {
//                 $query->where('cCode','BILLMON')
//                 ->orwhere('cCode','BILMON');
//               }) 
//        ->select('AutoIdx')
//        ->first(); 

        
       
      

//        $min_vol = _smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx ?? 0)
//        ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//        ->groupBy('fRate',DB::raw('(iToqty)'))
//        ->first();
      

//        if(!empty($min_vol) && ($min_vol->fRate ?? 0) > 0  )
//        {
//        if($min_vol->min_vol >=1000000){
//            $data->min_mono_vol = null;
//           }
//           else{
//            $data->min_mono_vol =$min_vol->min_vol ?? null;
//           }

       

//        if( $min_vol->min_vol >=1000000 ){

//            $total_frate =($min_vol->fRate * $data->monunit);
//            $rate_frate =number_format($min_vol->fRate,4);            

//            $data->comb_rates = $rate_frate;
//            $data->total = $total_frate;       


//        }
//        else{
//        $diff_mon = ($data->monunit -$min_vol->min_vol);
       
       
//        // the value is bigger than min volume
//        //slab two starts here
//        if($diff_mon > 0){
//        $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iToqty',$min_vol->min_vol)
//        ->select('frate')
//        ->first();
//        $total_min =($rate_min->frate * $min_vol->min_vol);

//        $rate =number_format($rate_min->frate,4);


//        // we increase minimum to move to the next slab 


//        //slab two starts
//        $Increment_1 =$min_vol->min_vol +1;          
       
//        // we get the rate for the next slab and their readings
//        $next_slab =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$Increment_1)
//       ->where('iToqty','>=',$Increment_1)
//       ->select('iFromQty','iToqty','fRate')
//       ->first();
      
//        //  we get the difference of this slab
//       $diff_slab =($next_slab->iToqty - $next_slab->iFromQty)+1;

//    //  we check if the remainder fulls fall in this slab or exceed
//       $diff_bil_slab =$diff_mon -$diff_slab;           

//       //counts fits fully in this slab the difference is negative
//       if($diff_bil_slab < 0){
//        $total_slab_1 =($diff_mon * $next_slab->fRate );

//        $rate_2 =number_format($next_slab->fRate,4);            

//        // two slabs used             
//        $comb_rates =$rate.' | '.$rate_2;
//        $total =($total_slab_1 +$total_min);

//        $data->comb_rates = $comb_rates;
//        $data->total = $total;

     
       
//       }

//       //  slab 3
//       //the counter is still much than the previous slab difference
//       if($diff_bil_slab > 0){
//        $total_slab_1 =($diff_slab * $next_slab->fRate );      
       
//        $rate_2 =number_format($next_slab->fRate,4);
       

//        //we increase the slab to quantity to move to the next slab
//        $increment2 =$next_slab->iToqty +1;
      

//        // we get the slab values rates and quantity
//        $rate_slab2 =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$increment2)
//       ->where('iToqty','>=',$increment2)
//       ->select('iFromQty','iToqty','fRate')
//       ->first();
      

//       $slab2_diff =($rate_slab2->iToqty -$rate_slab2->iFromQty)+1; 

 
//        //  we check if the remainder fully fall in this slab or exceed
//        $remaining_bil_slab =$diff_bil_slab -$slab2_diff; 
       
//        if($remaining_bil_slab < 0){

//        $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

//        $rate_3 =number_format($rate_slab2->fRate, 4);

//        // three slabs used

//        $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3;
//        $total =($total_slab_1 +$total_min +$total_slab_2);

//        $data->comb_rates = $comb_rates;
//        $data->total = $total;


//        }

//        // slab four
//        if($remaining_bil_slab > 0){
//        $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

//        $rate_3 =number_format($rate_slab2->fRate,3);

//        // increase slab to next

//        $increment3 =$rate_slab2->iToqty +1;
//        $rate_slab3 =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$increment3)
//        ->where('iToqty','>=',$increment3)
//        ->select('iFromQty','iToqty','fRate')
//        ->first();

       
//        $slab3_diff =($rate_slab3->iToqty -$rate_slab3->iFromQty)+1; 

//        $remaing_slab3_diff =($remaining_bil_slab -$slab3_diff);

//        if(!$remaing_slab3_diff){

//        $total_slab_3 =$remaining_bil_slab * $rate_slab3->fRate;
//        $rate_4 =number_format($rate_slab3->fRate,4);

//        // four slabs used

//        $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3. '|'.$rate_4;
//        $total =($total_slab_1 +$total_min +$total_slab_2 +$total_slab_3);

//        $data->comb_rates = $comb_rates;
//        $data->total = $total;

//        }
//        }
//       }             
//        }
//        else{
//         $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iToqty',$min_vol->min_vol)
//        ->select('frate')
//        ->first();
//        $total_min =($rate_min->frate * $min_vol->min_vol);
//        $rate =number_format($rate_min->frate,4);

//        $data->comb_rates = $rate;
//        $data->total = $total_min;     
//        } 
     
//       }
//    }          

       

//     }        
    
//     $bill_mono_sep =$billing_mono_separate[0] ?? '';
//     $mono_consolidated_sep =(object) $bill_mono_sep;

    
//     if(!empty($min_vol))
//     {

//    $t_mono_cmr = $mono_consolidated_sep->mon_cmr_total ?? 0;
//    $t_mon_pmr = $mono_consolidated_sep->mon_pmr_total ?? 0;
//    $tunits = $mono_consolidated_sep-> monunit ?? 0;
//    $mono_rate =$mono_consolidated_sep->comb_rates ?? 0;
//    $min_mono_vol =$mono_consolidated_sep->min_mono_vol ?? 0;
//    $tbillmeter = "BILLMON";
//    $treadingdate = "1900-01-01";
//    $total_mon =$mono_consolidated_sep->total ?? 0;

  
//      $total_mono_sep =$total_mon;
//      if($tax_rate->TaxRate > 0){
//        if($customer_details->icurrencyid ==0){
//          $tlineamtexcl = $total_mono_sep;
//          $tlineamttax = ($total_mono_sep * ($tax_rate->TaxRate / 100));
//          $tlineamtincl =($total_mono_sep * (1 + $tax_rate->TaxRate / 100));
//          $tfclineamtexcl = 0;
//          $tfclineamttax = 0;
//          $tfclineamtincl = 0;
//        }
//        else{
//          $tfclineamtexcl = $total_mono_sep;
//          $tfclineamttax = ($total_mono_sep * ($tax_rate->TaxRate / 100));
//          $tfclineamtincl = ($total_mono_sep * (1 + $tax_rate->TaxRate / 100));
//          $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//          $tlineamttax = $tfclineamttax * $exc_rate;
//          $tlineamtincl = $tfclineamtincl * $exc_rate;
//        }
//      }
//      else{
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_mono_sep;
//         $tlineamttax = 0;
//         $tlineamtincl =$total_mono_sep;
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_mono_sep;
//         $tfclineamttax = 0;
//         $tfclineamtincl = $total_mono_sep;
//         $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tlineamttax = 0;
//         $tlineamtincl = $tfclineamtincl * $exc_rate;
//       }


//      }



   
  

//    $tstockid = 17987;
//    $tlinenote ="CMR: $t_mono_cmr\r\nPMR: $t_mon_pmr\r\nCOPIES DONE: $tunits\r\nMIN. VOL: $min_mono_vol\r\nRATE: $mono_rate";
//    $tlinedesc = "Mono Billing for $mono_consolidated_sep->cDescription ($mono_consolidated_sep->ucSASerialNo)";

    
//     $_btblInvoiceLines = new _btblInvoiceLines;
//     $_btblInvoiceLines->iInvoiceID =$invoice_id;
//     $_btblInvoiceLines->iOrigLineID =0;
//     $_btblInvoiceLines->iGrvLineID =0;
//     $_btblInvoiceLines->iLineDocketMode =0; 
//     $_btblInvoiceLines->cDescription =$tlinedesc; 
//     $_btblInvoiceLines->iUnitsOfMeasureStockingID=0; 
//     $_btblInvoiceLines->iUnitsOfMeasureCategoryID=0;
//     $_btblInvoiceLines->iUnitsOfMeasureID=0;
//     $_btblInvoiceLines->fQuantity=1;
//     $_btblInvoiceLines->fQtyChange=1;
//     $_btblInvoiceLines->fQtyToProcess=1; 
//     $_btblInvoiceLines->fQtyLastProcess=0; 
//     $_btblInvoiceLines->fQtyProcessed =0; 
//     $_btblInvoiceLines->fQtyReserved=0; 
//     $_btblInvoiceLines->fQtyReservedChange =0;
//     $_btblInvoiceLines->cLineNotes=$tlinenote; 
//     $_btblInvoiceLines->fUnitPriceExcl=$tlineamtexcl; 
//     $_btblInvoiceLines->fUnitPriceIncl=$tlineamtincl;
//     $_btblInvoiceLines->iUnitPriceOverrideReasonID=0; 
//     $_btblInvoiceLines->fUnitCost=0;
//     $_btblInvoiceLines->fLineDiscount=0; 
//     $_btblInvoiceLines->iLineDiscountReasonID=0;
//     $_btblInvoiceLines->iReturnReasonID=0; 
//     $_btblInvoiceLines->fTaxRate=$tax_rate->TaxRate; 
//     $_btblInvoiceLines->bIsSerialItem=0; 
//     $_btblInvoiceLines->bIsWhseItem=1;
//     $_btblInvoiceLines->fAddCost=0; 
//     $_btblInvoiceLines->cTradeinItem='';
//     $_btblInvoiceLines->iStockCodeID=$tstockid; 
//     $_btblInvoiceLines->iJobID=0;
//     $_btblInvoiceLines->iWarehouseID=6;
//     $_btblInvoiceLines->iTaxTypeID=$customer_details->ideftaxtypeid;
//     $_btblInvoiceLines->iPriceListNameID=1;
//     $_btblInvoiceLines->fQuantityLineTotIncl=$tlineamtincl;
//     $_btblInvoiceLines->fQuantityLineTotExcl=$tlineamtexcl;
//     $_btblInvoiceLines->fQuantityLineTotInclNoDisc=$tlineamtincl;
//     $_btblInvoiceLines->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines->fQuantityLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//     $_btblInvoiceLines->fQtyChangeLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotIncl=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotExcl =0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDisc=0;
//     $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDisc=0;
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmount=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotIncl=0;
//     $_btblInvoiceLines->fQtyProcessedLineTotExcl=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotInclNoDisc=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotExclNoDisc=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmount=0;
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines->fUnitPriceExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fUnitPriceInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fUnitCostForeign=0;
//     $_btblInvoiceLines->fAddCostForeign=0;
//     $_btblInvoiceLines->fQuantityLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//     $_btblInvoiceLines->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//     $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotInclForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotExclForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmountForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotInclForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotExclForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmountForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines->iLineRepID=$customer_details->repid; 
//     $_btblInvoiceLines->iLineProjectID=2; 
//     $_btblInvoiceLines->iLedgerAccountID=0; 
//     $_btblInvoiceLines->IModule=0;
//     $_btblInvoiceLines->bChargeCom=1;
//     $_btblInvoiceLines->bIsLotItem=0;
//     // $_btblInvoiceLines->iLotID=0;
//     // $_btblInvoiceLines->cLotNumber='';
//     // $_btblInvoiceLines->dLotExpiryDate=null;
//     $_btblInvoiceLines->iMFPID=0;
//     $_btblInvoiceLines->iLineID=1;
//     $_btblInvoiceLines->iLinkedLineID=0;
//     $_btblInvoiceLines->fQtyLinkedUsed=null;
//     $_btblInvoiceLines->fUnitPriceInclOrig=null;
//     $_btblInvoiceLines->fUnitPriceExclOrig=Null;
//     $_btblInvoiceLines->fUnitPriceInclForeignOrig=Null;
//     $_btblInvoiceLines->fUnitPriceExclForeignOrig=0;
//     $_btblInvoiceLines->iDeliveryMethodID=0;
//     $_btblInvoiceLines->fQtyDeliver=0;
//     $_btblInvoiceLines->dDeliveryDate=$today;
//     $_btblInvoiceLines->iDeliveryStatus=0;
//     $_btblInvoiceLines->fQtyForDelivery=0;
//     $_btblInvoiceLines->bPromotionApplied=0;
//     $_btblInvoiceLines->fPromotionPriceExcl=0;
//     $_btblInvoiceLines->fPromotionPriceIncl=0;
//     $_btblInvoiceLines->cPromotionCode=0;
//     $_btblInvoiceLines->iSOLinkedPOLineID=0;
//     $_btblInvoiceLines->fLength=0;
//     $_btblInvoiceLines->fWidth=0;
//     $_btblInvoiceLines->fHeight=0;
//     $_btblInvoiceLines->iPieces=0;
//     $_btblInvoiceLines->iPiecesToProcess=0;
//     $_btblInvoiceLines->iPiecesLastProcess=0;
//     $_btblInvoiceLines->iPiecesProcessed=0;
//     $_btblInvoiceLines->iPiecesReserved=0;
//     $_btblInvoiceLines->iPiecesDeliver=0;
//     $_btblInvoiceLines->iPiecesForDelivery=0;
//     $_btblInvoiceLines->fQuantityUR=1;
//     $_btblInvoiceLines->fQtyChangeUR=1;
//     $_btblInvoiceLines->fQtyToProcessUR=1;
//     $_btblInvoiceLines->fQtyLastProcessUR=0;
//     $_btblInvoiceLines->fQtyProcessedUR=0;
//     $_btblInvoiceLines->fQtyReservedUR=0;
//     $_btblInvoiceLines->fQtyReservedChangeUR=0;
//     $_btblInvoiceLines->fQtyDeliverUR=0;
//     $_btblInvoiceLines->fQtyForDeliveryUR=0;
//     $_btblInvoiceLines->fQtyLinkedUsedUR=0;
//     $_btblInvoiceLines->iPiecesLinkedUsed=0;
//     $_btblInvoiceLines->iSalesWhseID=0;
//     $_btblInvoiceLines->_btblInvoiceLines_iBranchID=1;
//     $_btblInvoiceLines->udIDSOrdTxCMReadingDate=$today;
//     $_btblInvoiceLines->uiIDSOrdTxCMPrevReading=$t_mon_pmr;
//     $_btblInvoiceLines->uiIDSOrdTxCMCurrReading=$t_mono_cmr;
//     $_btblInvoiceLines->ucIDSOrdTxCMMinVol=$min_mono_vol;
//     $_btblInvoiceLines->ucIDSOrdTxCMRates=$mono_rate;
//     $_btblInvoiceLines->ucIDSOrdTxCMServiceAsset=$mono_consolidated_sep->cCode;
//     $_btblInvoiceLines->ucIDSOrdTxCMMeterType="BILLMON";

//    $_btblInvoiceLines->save();

//     }







//    // calculation color J non con

//            $asset_id_color = DB::table('_smtblServiceAsset As sa')
//             ->where('cCode',$value['ucSABillingAsset'])
//             ->select('AutoIdx')
//             ->first();
         

//             $color_counter_elapse =DB::table('_smtblcounterelapsed')
//             ->where('iServiceAssetId',$asset_id_color->AutoIdx)
//             ->where('cCode','BILLCOL')
//             ->select('AutoIdx')
//             ->first();  
//             if(!empty($color_counter_elapse)){

 
//           $billing_color_sep = DB::table('_bvARAccountsFull As cl')
//           ->where('DCLink',$customer_details->dclink)
//           ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//           ->whereYear('ReadingDate', '=', $today->year)
//           ->whereMonth('ReadingDate', '=', $today->month)

//           ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//           DB::raw('sum(cmr.ColCMR) as color_cmr_total'),
//           DB::raw('sum(cmr.ColPMR) as color_pmr_total'),
//           DB::raw('sum(cmr.ColCMR - cmr.ColPMR) as colunit'),   
//           DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.cCode','sa.cDescription','sa.ucSASerialNo'
                
//             )
//           ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//           ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//           ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode','sa.cDescription','sa.ucSASerialNo')
//           ->get();


//           foreach($billing_color_sep as $data){
//             $asset_id = DB::table('_smtblServiceAsset As sa')
//             ->where('cCode',$data->cCode)
//             ->select('AutoIdx')
//             ->first();            
   

     
//             $color_counter_elapse =DB::table('_smtblcounterelapsed')
//             ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
//             ->where('cCode','BILLCOL')
//             ->select('AutoIdx')
//             ->first();

//             $min_vol_color = _smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx ?? '')
//             ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//             ->groupBy('fRate',DB::raw('(iToqty)'))
//             ->first();

//             if(!empty($min_vol_color) && $min_vol_color->fRate > 0)
//             {
 

//             if($min_vol_color->min_vol >= 1000000){

//             $total_color =$min_vol_color->fRate * $data->colunit;
//             $rate_frate =number_format($min_vol_color->fRate,4);

//             $data->total_color = $total_color;
            
//             $data->comb_rates_color =$rate_frate;   


//          }
//          else{                   

//      $diff_color = ($data->colunit -$min_vol_color->min_vol);
     

//      if($diff_color > 0){
//      $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//      ->where('iToqty',$min_vol_color->min_vol)
//      ->select('frate')
//      ->first();
//       $total_min_color =($rate_min_color->frate * $min_vol_color->min_vol);

//      $rate =number_format($rate_min_color->frate,2);

//      $diff_remaining_color = ($data->colunit -$min_vol_color->min_vol);

//      // we increase minimum to move to the next slab 

//      //slab two starts
//      $Increment_1 =$min_vol_color->min_vol +1;  

//       //we get the rate for the next slab and their readings
//       $next_slab_col =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//       ->where('iFromQty','<=',$Increment_1)
//      ->where('iToqty','>=',$Increment_1)
//      ->select('iFromQty','iToqty','fRate')
//      ->first();

//       //  we get the difference of this slab
//     $diff_slab_color =($next_slab_col->iToqty - $next_slab_col->iFromQty)+1;

//     //  we check if the remainder fulls fall in this slab or exceed
//        $diff_bal_slab =$diff_remaining_color -$diff_slab_color;

//      //   value fully fits here


//      if($diff_bal_slab < 0){
//      $total_slab_2_col =($diff_remaining_color * $next_slab_col->fRate );

//      $rate_2 =number_format($next_slab_col->fRate,4);         

     
//      // two slabs used 
     
//      $comb_rates_color =$rate.' | '.$rate_2;
//      $total_color =($total_min_color + $total_slab_2_col);

//      $data->comb_rates_color = $comb_rates_color;
//      $data->total_color = $total_color;



//      }

//      // slab three
//      if($diff_bal_slab > 0){
//      $total_slab_2_col =($diff_slab_color * $next_slab_col->fRate );  
     
//      $rate_2 =number_format($next_slab_col->fRate,4); 

//      $diff_remaining2_color = ($diff_bal_slab - $diff_slab_color);

//      // we increase minimum to move to the next slab 


//      //slab two starts
//      $Increment_2 =$next_slab_col->iToqty +1;  

//       // we get the slab values rates and quantity
//       $rate_slab3_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//       ->where('iFromQty','<=',$increment2)
//      ->where('iToqty','>=',$increment2)
//      ->select('iFromQty','iToqty','fRate')
//      ->first();

//      // we get slab difference
//      $slab_3_diff =($rate_slab3_color->iToqty - $rate_slab3_color->iFromQty) +1;

//      // we check if balance is still bigger or less

//      $diff_remaining3_color =$diff_remaining2_color -  $slab_3_diff;

//      if($diff_remaining3_color < 0){

//      $total_slab_3_col =($diff_remaining2_color * $rate_slab3_color->fRate );

//      $rate_3 =number_format($rate_slab3_color->fRate,4); 

//       // three slabs used 
     
//       $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3;
//       $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col);

//       $data->comb_rates_color = $comb_rates_color;
//       $data->total_color = $total_color;


//      }
//     if($diff_remaining3_color > 0){

//      $total_slab_3_col =($slab_3_diff * $rate_slab3_color->fRate );

//      $rate_3 =$rate_slab3_color->fRate; 

//      // increase to move to the next slab

//      $Increment3 =$rate_slab3_color->iToqty +1;  

//      $rate_slab4_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//      ->where('iFromQty','<=',$increment3)
//      ->where('iToqty','>=',$increment3)
//      ->select('iFromQty','iToqty','fRate')
//      ->first();

//      // we get the difference of the slab

//      $diff_slab4 =($rate_slab4_color->iToqty - $rate_slab4_color->iFromQty)+1;


//      // we check if balance is still bigger or less
//      $diff_remaining4_color =$diff_remaining3_color - $slab_3_diff ;

//      // we check if balance fits in this slab

//      if($diff_remaining4_color < 0){

//      $total_slab_4_col =($diff_remaining3_color * $rate_slab4_color->fRate );

//      $rate_4 =number_format($rate_slab4_color->fRate,4); 
     
//       // four slabs used 
     
//       $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3. '|'. $rate_4;
//       $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col+$total_slab_4_col);

//       $data->comb_rates_color = $comb_rates_color;
//       $data->total_color = $total_color;


//      }

//     }

//  }

// }


//      else{

//      $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//      ->where('iToqty',$min_vol_color->min_vol)
//      ->select('frate')
//      ->first();
//      $total_color =($rate_min_color->frate * $min_vol_color->min_vol);
//      $rate_color =number_format($rate_min_color->frate,3);

//      $data->comb_rates_color = $rate_color;
//      $data->total_color = $total_color;       

     



//      }

//  }
// $min_color_vol = _smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx ?? '')
// ->select(DB::raw('min(iToqty) as min_color_vol'))
// ->first();

// if($min_color_vol->min_color_vol >=100000000){
// $data->min_color_vol = null;
// }
// else{
// $data->min_color_vol =$min_color_vol->min_color_vol ?? null;
// }        

// }    
// }    
 
//   $bill_color_sep =$billing_color_sep[0] ?? '';
//   $color_bill_sep =(object) $bill_color_sep;


  

//  $t_color_cmr = $color_bill_sep->color_cmr_total;
//  $t_color_pmr = $color_bill_sep->color_pmr_total;
//  $tunits = $color_bill_sep-> colunit;
//  $color_rate =$color_bill_sep->comb_rates_color ?? 0;
//  $min_color_vol =$color_bill_sep->min_color_vol ?? 0;
//  $tbillmeter = "BILLCOL";
//  $treadingdate = "1900-01-01";
//  $total_col_con =$color_bill_sep->total_color ?? 0;



//    $total_color_sep =$total_col_con;
//    if($tax_rate->TaxRate > 0){
//      if($customer_details->icurrencyid ==0){
//        $tlineamtexcl = $total_color_sep;
//        $tlineamttax = ($total_color_sep * ($tax_rate->TaxRate / 100));
//        $tlineamtincl =($total_color_sep * (1 + $tax_rate->TaxRate / 100));
//        $tfclineamtexcl = 0;
//        $tfclineamttax = 0;
//        $tfclineamtincl = 0;
//      }
//      else{
//        $tfclineamtexcl = $total_color_sep;
//        $tfclineamttax = ($total_color_sep * ($tax_rate->TaxRate / 100));
//        $tfclineamtincl = ($total_color_sep * (1 + $tax_rate->TaxRate / 100));
//        $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//        $tlineamttax = $tfclineamttax * $exc_rate;
//        $tlineamtincl = $tfclineamtincl * $exc_rate;
//      }
//    }
//    else{
//     if($customer_details->icurrencyid ==0){
//       $tlineamtexcl = $total_color_sep;
//       $tlineamttax = 0;
//       $tlineamtincl =$total_color_sep;
//       $tfclineamtexcl = 0;
//       $tfclineamttax = 0;
//       $tfclineamtincl = 0;
//     }
//     else{
//       $tfclineamtexcl = $total_color_sep;
//       $tfclineamttax = 0;
//       $tfclineamtincl = $total_color_sep;
//       $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//       $tlineamttax = 0;
//       $tlineamtincl = $tfclineamtincl * $exc_rate;
//     }



//    }
 
   

//    $tstockid = 17987;
//    $tlinenote ="CMR: $t_color_cmr\r\nPMR: $t_color_pmr\r\nCOPIES DONE: $tunits\r\nMIN. VOL: $min_color_vol\r\nRATE: $color_rate";
//    $tlinedesc = "Color Billing for $color_bill_sep->cDescription ($color_bill_sep->ucSASerialNo)";

//    $_btblInvoiceLines_color = new _btblInvoiceLines;
//     $_btblInvoiceLines_color->iInvoiceID =$invoice_id;
//     $_btblInvoiceLines_color->iOrigLineID =0;
//     $_btblInvoiceLines_color->iGrvLineID =0;
//     $_btblInvoiceLines_color->iLineDocketMode =0; 
//     $_btblInvoiceLines_color->cDescription =$tlinedesc; 
//     $_btblInvoiceLines_color->iUnitsOfMeasureStockingID=0; 
//     $_btblInvoiceLines_color->iUnitsOfMeasureCategoryID=0;
//     $_btblInvoiceLines_color->iUnitsOfMeasureID=0;
//     $_btblInvoiceLines_color->fQuantity=1;
//     $_btblInvoiceLines_color->fQtyChange=1;
//     $_btblInvoiceLines_color->fQtyToProcess=1; 
//     $_btblInvoiceLines_color->fQtyLastProcess=0; 
//     $_btblInvoiceLines_color->fQtyProcessed =0; 
//     $_btblInvoiceLines_color->fQtyReserved=0; 
//     $_btblInvoiceLines_color->fQtyReservedChange =0;
//     $_btblInvoiceLines_color->cLineNotes=$tlinenote; 
//     $_btblInvoiceLines_color->fUnitPriceExcl=$tlineamtexcl; 
//     $_btblInvoiceLines_color->fUnitPriceIncl=$tlineamtincl;
//     $_btblInvoiceLines_color->iUnitPriceOverrideReasonID=0; 
//     $_btblInvoiceLines_color->fUnitCost=0;
//     $_btblInvoiceLines_color->fLineDiscount=0; 
//     $_btblInvoiceLines_color->iLineDiscountReasonID=0;
//     $_btblInvoiceLines_color->iReturnReasonID=0; 
//     $_btblInvoiceLines_color->fTaxRate=$tax_rate->TaxRate; 
//     $_btblInvoiceLines_color->bIsSerialItem=0; 
//     $_btblInvoiceLines_color->bIsWhseItem=1;
//     $_btblInvoiceLines_color->fAddCost=0; 
//     $_btblInvoiceLines_color->cTradeinItem='';
//     $_btblInvoiceLines_color->iStockCodeID=$tstockid; 
//     $_btblInvoiceLines_color->iJobID=0;
//     $_btblInvoiceLines_color->iWarehouseID=6;
//     $_btblInvoiceLines_color->iTaxTypeID=$customer_details->ideftaxtypeid;
//     $_btblInvoiceLines_color->iPriceListNameID=1;
//     $_btblInvoiceLines_color->fQuantityLineTotIncl=$tlineamtincl;
//     $_btblInvoiceLines_color->fQuantityLineTotExcl=$tlineamtexcl;
//     $_btblInvoiceLines_color->fQuantityLineTotInclNoDisc=$tlineamtincl;
//     $_btblInvoiceLines_color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQuantityLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotIncl=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExcl =0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDisc=0;
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDisc=0;
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmount=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotIncl=0;
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExcl=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDisc=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDisc=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmount=0;
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines_color->fUnitPriceExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fUnitPriceInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fUnitCostForeign=0;
//     $_btblInvoiceLines_color->fAddCostForeign=0;
//     $_btblInvoiceLines_color->fQuantityLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//     $_btblInvoiceLines_color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotInclForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExclForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotInclForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExclForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines_color->iLineRepID=$customer_details->repid; 
//     $_btblInvoiceLines_color->iLineProjectID=2; 
//     $_btblInvoiceLines_color->iLedgerAccountID=0; 
//     $_btblInvoiceLines_color->IModule=0;
//     $_btblInvoiceLines_color->bChargeCom=1;
//     $_btblInvoiceLines_color->bIsLotItem=0;
//     // $_btblInvoiceLines_color->iLotID=0;
//     // $_btblInvoiceLines_color->cLotNumber='';
//     // $_btblInvoiceLines_color->dLotExpiryDate=null;
//     $_btblInvoiceLines_color->iMFPID=0;
//     $_btblInvoiceLines_color->iLineID=1;
//     $_btblInvoiceLines_color->iLinkedLineID=0;
//     $_btblInvoiceLines_color->fQtyLinkedUsed=null;
//     $_btblInvoiceLines_color->fUnitPriceInclOrig=null;
//     $_btblInvoiceLines_color->fUnitPriceExclOrig=Null;
//     $_btblInvoiceLines_color->fUnitPriceInclForeignOrig=Null;
//     $_btblInvoiceLines_color->fUnitPriceExclForeignOrig=0;
//     $_btblInvoiceLines_color->iDeliveryMethodID=0;
//     $_btblInvoiceLines_color->fQtyDeliver=0;
//     $_btblInvoiceLines_color->dDeliveryDate=$today;
//     $_btblInvoiceLines_color->iDeliveryStatus=0;
//     $_btblInvoiceLines_color->fQtyForDelivery=0;
//     $_btblInvoiceLines_color->bPromotionApplied=0;
//     $_btblInvoiceLines_color->fPromotionPriceExcl=0;
//     $_btblInvoiceLines_color->fPromotionPriceIncl=0;
//     $_btblInvoiceLines_color->cPromotionCode=0;
//     $_btblInvoiceLines_color->iSOLinkedPOLineID=0;
//     $_btblInvoiceLines_color->fLength=0;
//     $_btblInvoiceLines_color->fWidth=0;
//     $_btblInvoiceLines_color->fHeight=0;
//     $_btblInvoiceLines_color->iPieces=0;
//     $_btblInvoiceLines_color->iPiecesToProcess=0;
//     $_btblInvoiceLines_color->iPiecesLastProcess=0;
//     $_btblInvoiceLines_color->iPiecesProcessed=0;
//     $_btblInvoiceLines_color->iPiecesReserved=0;
//     $_btblInvoiceLines_color->iPiecesDeliver=0;
//     $_btblInvoiceLines_color->iPiecesForDelivery=0;
//     $_btblInvoiceLines_color->fQuantityUR=1;
//     $_btblInvoiceLines_color->fQtyChangeUR=1;
//     $_btblInvoiceLines_color->fQtyToProcessUR=1;
//     $_btblInvoiceLines_color->fQtyLastProcessUR=0;
//     $_btblInvoiceLines_color->fQtyProcessedUR=0;
//     $_btblInvoiceLines_color->fQtyReservedUR=0;
//     $_btblInvoiceLines_color->fQtyReservedChangeUR=0;
//     $_btblInvoiceLines_color->fQtyDeliverUR=0;
//     $_btblInvoiceLines_color->fQtyForDeliveryUR=0;
//     $_btblInvoiceLines_color->fQtyLinkedUsedUR=0;
//     $_btblInvoiceLines_color->iPiecesLinkedUsed=0;
//     $_btblInvoiceLines_color->iSalesWhseID=0;
//     $_btblInvoiceLines_color->_btblInvoiceLines_iBranchID=1;
//     $_btblInvoiceLines_color->udIDSOrdTxCMReadingDate=$today;
//     $_btblInvoiceLines_color->uiIDSOrdTxCMPrevReading=$t_color_pmr;
//     $_btblInvoiceLines_color->uiIDSOrdTxCMCurrReading=$t_color_cmr;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMMinVol=$min_color_vol;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMRates=$color_rate;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMServiceAsset=$color_bill_sep->cCode;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMMeterType="BILLCOL";

//     $_btblInvoiceLines_color->save();

  
// }

//       // calculate Scan non CON

//       $asset_id_scan = DB::table('_smtblServiceAsset As sa')
//       ->where('cCode',$value['ucSABillingAsset'])
//       ->select('AutoIdx')
//       ->first();
   

//       $scan_counter_elapse =DB::table('_smtblcounterelapsed')
//       ->where('iServiceAssetId',$asset_id_color->AutoIdx)
//       ->where('cCode','BILLSCN')
//       ->select('AutoIdx')
//       ->first();

//       if(!empty($scan_counter_elapse)){
//         $billing_scan_non_con = DB::table('_bvARAccountsFull As cl')
//         ->where('DCLink',$customer_details->dclink)
//         ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//         ->whereYear('ReadingDate', '=', $today->year)
//         ->whereMonth('ReadingDate', '=', $today->month)

//         ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//         DB::raw('sum(cmr.scncmr - cmr.scnpmr) as scnunit'),
//         DB::raw('sum(cmr.scncmr) as tscancmr'),
//         DB::raw('sum(cmr.scnpmr) as tscanpmr'),'sa.cCode','sa.cDescription','sa.ucSASerialNo'        
//          )
//         ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//         ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//         ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode','sa.cDescription','sa.ucSASerialNo')
//         ->get();

          
//           foreach($billing_scan_non_con as $data){
//             $asset_id = DB::table('_smtblServiceAsset As sa')
//             ->where('cCode',$data->cCode)
//             ->select('AutoIdx')
//             ->first();            
          
  
         
//            $scan_counter_elapse =DB::table('_smtblcounterelapsed')
//            ->where('iServiceAssetId',$asset_id->AutoIdx)
//            ->where('cCode','BILLSCN')
//            ->select('AutoIdx')
//            ->first();
  
  
//            $scan_min_vol = _smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx ?? '')
//            ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//            ->groupBy('fRate')
//            ->first();

//            if(!empty($scan_min_vol) &&  $scan_min_vol->fRate > 0)
//            {
   
//            if($scan_min_vol->min_vol >=1000000){
//                $data->scan_min_vol = null;
//               }
//            else{
//                $data->scan_min_vol =$scan_min_vol->min_vol ?? null;
//            }
//            if( $scan_min_vol->min_vol  >=1000000 ){
   
//                $total_frate_scan =($scan_min_vol->fRate * $data->scnunit);
//                $rate_frate_scan =number_format($scan_min_vol->fRate,4);
   
             
   
//                $data->comb_rates_scan = $rate_frate_scan;
//                $data->total_scan = $total_frate_scan;            
   
   
//            }
   
//            else{
//                $diff_scan = ($data->scnunit - $scan_min_vol->min_vol);
//                if($diff_scan > 0){
//                    $rate_min_scan =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                    ->where('iToqty',$scan_min_vol->min_vol)
//                    ->select('frate')
//                    ->first();
//                    $total_min_scan =($rate_min_scan->frate * $scan_min_vol->min_vol);
   
//                    $rate_scan =number_format($rate_min_scan->frate,4);
                   
//                    //slab two starts
//                    $Increment_1_sc =$scan_min_vol->min_vol +1; 
   
           
//                // we get the rate for the next slab and their readings
//                $next_slab_sn =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                ->where('iFromQty','<=',$Increment_1_sc)
//                ->where('iToqty','>=',$Increment_1_sc)
//                ->select('iFromQty','iToqty','fRate')
//                ->first();
          
//                    //  we get the difference of this slab
//                $diff_slab1_sn =($next_slab_sn->iToqty - $next_slab_sn->iFromQty)+1;
   
   
//                //  we check if the remainder fulls fall in this slab or exceed
//                $diff_bil_slab_sn =$diff_scan -$diff_slab1_sn;           
   
//               //counts fits fully in this slab the difference is negative
//                if($diff_bil_slab_sn < 0){
//                    $total_slab_1_sn =($diff_scan * $next_slab_sn->fRate );
   
//                    $rate_2_scan =number_format($next_slab_sn->fRate,4);            
   
//                // two slabs used             
//                $comb_rates_scan =$rate_scan.' | '.$rate_2_scan;
//                $total_scan =($total_slab_1_sn +$total_min_scan);
   
//                $data->comb_rates_scan = $comb_rates_scan;
//                $data->total_scan = $total_scan;          
           
//            }
//            }
   
//            else{
//                $rate_min_scan =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                    ->where('iToqty',$scan_min_vol->min_vol)
//                    ->select('frate')
//                    ->first();
//                $total_min_scan =($rate_min_scan->frate * $scan_min_vol->min_vol);
//                $rate_scan =number_format($rate_min_scan->frate,4);
   
//                $data->comb_rates_scan = $rate_scan;
//                $data->total_scan = $total_min_scan;  
   
//            }
//            }
//           }
//         }
  
           
           
//         $bill_scan_noncon =$billing_scan_non_con[0] ?? '';
//         $scan_noncon =(object) $bill_scan_noncon;

//         $t_scan_cmr = $scan_noncon->tscancmr;
//         $t_scan_pmr = $scan_noncon->tscanpmr;
//         $tscunits = $scan_noncon-> scnunit;
//         $scan_rate =$scan_noncon->comb_rates_scan ?? 0;
//         $min_scan_vol =$scan_noncon->scan_min_vol ?? 0;
//         $tbillmeter = "BILLSCN";
//         $treadingdate = "1900-01-01";
//         $total_scan_con =$scan_noncon->total_scan ?? 0;

//         if($tax_rate->TaxRate > 0){
//           if($customer_details->icurrencyid ==0){
//             $tlineamtexcl = $total_scan_con;
//             $tlineamttax = ($total_scan_con * ($tax_rate->TaxRate / 100));
//             $tlineamtincl =($total_scan_con * (1 + $tax_rate->TaxRate / 100));
//             $tfclineamtexcl = 0;
//             $tfclineamttax = 0;
//             $tfclineamtincl = 0;
//           }
//           else{
//             $tfclineamtexcl = $total_scan_con;
//             $tfclineamttax = ($total_scan_con * ($tax_rate->TaxRate / 100));
//             $tfclineamtincl = ($total_scan_con * (1 + $tax_rate->TaxRate / 100));
//             $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//             $tlineamttax = $tfclineamttax * $exc_rate;
//             $tlineamtincl = $tfclineamtincl * $exc_rate;
//           }
//         }
//         else{
//           if($customer_details->icurrencyid ==0){
//             $tlineamtexcl = $total_scan_con;
//             $tlineamttax = 0;
//             $tlineamtincl =$total_scan_con ;
//             $tfclineamtexcl = 0;
//             $tfclineamttax = 0;
//             $tfclineamtincl = 0;
//           }
//           else{
//             $tfclineamtexcl = $total_scan_con;
//             $tfclineamttax = 0;
//             $tfclineamtincl = $total_scan_con;
//             $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//             $tlineamttax = 0;
//             $tlineamtincl = $tfclineamtincl * $exc_rate;
//           }


//         }

//         $tstockid = 17987;
//         $tlinenote ="CMR: $t_scan_cmr\r\nPMR: $t_scan_pmr\r\nCOPIES DONE: $tscunits\r\nMIN. VOL: $min_scan_vol\r\nRATE: $scan_rate";
//         $tlinedesc = "Scan Billing for $scan_noncon->cDescription ($scan_noncon->ucSASerialNo)";

//         $_btblInvoiceLines_scan = new _btblInvoiceLines;
//            $_btblInvoiceLines_scan->iInvoiceID =$invoice_id;
//            $_btblInvoiceLines_scan->iOrigLineID =0;
//            $_btblInvoiceLines_scan->iGrvLineID =0;
//            $_btblInvoiceLines_scan->iLineDocketMode =0; 
//            $_btblInvoiceLines_scan->cDescription =$tlinedesc; 
//            $_btblInvoiceLines_scan->iUnitsOfMeasureStockingID=0; 
//            $_btblInvoiceLines_scan->iUnitsOfMeasureCategoryID=0;
//            $_btblInvoiceLines_scan->iUnitsOfMeasureID=0;
//            $_btblInvoiceLines_scan->fQuantity=1;
//            $_btblInvoiceLines_scan->fQtyChange=1;
//            $_btblInvoiceLines_scan->fQtyToProcess=1; 
//            $_btblInvoiceLines_scan->fQtyLastProcess=0; 
//            $_btblInvoiceLines_scan->fQtyProcessed =0; 
//            $_btblInvoiceLines_scan->fQtyReserved=0; 
//            $_btblInvoiceLines_scan->fQtyReservedChange =0;
//            $_btblInvoiceLines_scan->cLineNotes=$tlinenote; 
//            $_btblInvoiceLines_scan->fUnitPriceExcl=$tlineamtexcl; 
//            $_btblInvoiceLines_scan->fUnitPriceIncl=$tlineamtincl;
//            $_btblInvoiceLines_scan->iUnitPriceOverrideReasonID=0; 
//            $_btblInvoiceLines_scan->fUnitCost=0;
//            $_btblInvoiceLines_scan->fLineDiscount=0; 
//            $_btblInvoiceLines_scan->iLineDiscountReasonID=0;
//            $_btblInvoiceLines_scan->iReturnReasonID=0; 
//            $_btblInvoiceLines_scan->fTaxRate=$tax_rate->TaxRate; 
//            $_btblInvoiceLines_scan->bIsSerialItem=0; 
//            $_btblInvoiceLines_scan->bIsWhseItem=1;
//            $_btblInvoiceLines_scan->fAddCost=0; 
//            $_btblInvoiceLines_scan->cTradeinItem='';
//            $_btblInvoiceLines_scan->iStockCodeID=$tstockid; 
//            $_btblInvoiceLines_scan->iJobID=0;
//            $_btblInvoiceLines_scan->iWarehouseID=6;
//            $_btblInvoiceLines_scan->iTaxTypeID=$customer_details->ideftaxtypeid;
//            $_btblInvoiceLines_scan->iPriceListNameID=1;
//            $_btblInvoiceLines_scan->fQuantityLineTotIncl=$tlineamtincl;
//            $_btblInvoiceLines_scan->fQuantityLineTotExcl=$tlineamtexcl;
//            $_btblInvoiceLines_scan->fQuantityLineTotInclNoDisc=$tlineamtincl;
//            $_btblInvoiceLines_scan->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//            $_btblInvoiceLines_scan->fQuantityLineTaxAmount =$tlineamttax; 
//            $_btblInvoiceLines_scan->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotIncl =$tlineamtincl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotExcl =$tlineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTaxAmount =$tlineamttax; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotIncl =$tlineamtincl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmount =$tlineamttax; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotIncl=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotExcl =0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclNoDisc=0;
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclNoDisc=0;
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmount=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountNoDisc=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotIncl=0;
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotExcl=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotInclNoDisc=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotExclNoDisc=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmount=0;
//            $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountNoDisc=0; 
//            $_btblInvoiceLines_scan->fUnitPriceExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_scan->fUnitPriceInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fUnitCostForeign=0;
//            $_btblInvoiceLines_scan->fAddCostForeign=0;
//            $_btblInvoiceLines_scan->fQuantityLineTotInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_scan->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//            $_btblInvoiceLines_scan->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//            $_btblInvoiceLines_scan->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//            $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//            $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclForeign=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclForeign=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountForeign=0; 
//            $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotInclForeign=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotExclForeign=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotInclNoDiscForeign=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTotExclNoDiscForeign=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountForeign=0; 
//            $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//            $_btblInvoiceLines_scan->iLineRepID=$customer_details->repid; 
//            $_btblInvoiceLines_scan->iLineProjectID=2; 
//            $_btblInvoiceLines_scan->iLedgerAccountID=0; 
//            $_btblInvoiceLines_scan->IModule=0;
//            $_btblInvoiceLines_scan->bChargeCom=1;
//            $_btblInvoiceLines_scan->bIsLotItem=0;
//           //  $_btblInvoiceLines_scan->iLotID=0;
//           //  $_btblInvoiceLines_scan->cLotNumber='';
//           //  $_btblInvoiceLines_scan->dLotExpiryDate=null;
//            $_btblInvoiceLines_scan->iMFPID=0;
//            $_btblInvoiceLines_scan->iLineID=1;
//            $_btblInvoiceLines_scan->iLinkedLineID=0;
//            $_btblInvoiceLines_scan->fQtyLinkedUsed=null;
//            $_btblInvoiceLines_scan->fUnitPriceInclOrig=null;
//            $_btblInvoiceLines_scan->fUnitPriceExclOrig=Null;
//            $_btblInvoiceLines_scan->fUnitPriceInclForeignOrig=Null;
//            $_btblInvoiceLines_scan->fUnitPriceExclForeignOrig=0;
//            $_btblInvoiceLines_scan->iDeliveryMethodID=0;
//            $_btblInvoiceLines_scan->fQtyDeliver=0;
//            $_btblInvoiceLines_scan->dDeliveryDate=$today;
//            $_btblInvoiceLines_scan->iDeliveryStatus=0;
//            $_btblInvoiceLines_scan->fQtyForDelivery=0;
//            $_btblInvoiceLines_scan->bPromotionApplied=0;
//            $_btblInvoiceLines_scan->fPromotionPriceExcl=0;
//            $_btblInvoiceLines_scan->fPromotionPriceIncl=0;
//            $_btblInvoiceLines_scan->cPromotionCode=0;
//            $_btblInvoiceLines_scan->iSOLinkedPOLineID=0;
//            $_btblInvoiceLines_scan->fLength=0;
//            $_btblInvoiceLines_scan->fWidth=0;
//            $_btblInvoiceLines_scan->fHeight=0;
//            $_btblInvoiceLines_scan->iPieces=0;
//            $_btblInvoiceLines_scan->iPiecesToProcess=0;
//            $_btblInvoiceLines_scan->iPiecesLastProcess=0;
//            $_btblInvoiceLines_scan->iPiecesProcessed=0;
//            $_btblInvoiceLines_scan->iPiecesReserved=0;
//            $_btblInvoiceLines_scan->iPiecesDeliver=0;
//            $_btblInvoiceLines_scan->iPiecesForDelivery=0;
//            $_btblInvoiceLines_scan->fQuantityUR=1;
//            $_btblInvoiceLines_scan->fQtyChangeUR=1;
//            $_btblInvoiceLines_scan->fQtyToProcessUR=1;
//            $_btblInvoiceLines_scan->fQtyLastProcessUR=0;
//            $_btblInvoiceLines_scan->fQtyProcessedUR=0;
//            $_btblInvoiceLines_scan->fQtyReservedUR=0;
//            $_btblInvoiceLines_scan->fQtyReservedChangeUR=0;
//            $_btblInvoiceLines_scan->fQtyDeliverUR=0;
//            $_btblInvoiceLines_scan->fQtyForDeliveryUR=0;
//            $_btblInvoiceLines_scan->fQtyLinkedUsedUR=0;
//            $_btblInvoiceLines_scan->iPiecesLinkedUsed=0;
//            $_btblInvoiceLines_scan->iSalesWhseID=0;
//            $_btblInvoiceLines_scan->_btblInvoiceLines_iBranchID=1;
//            $_btblInvoiceLines_scan->udIDSOrdTxCMReadingDate=$today;
//            $_btblInvoiceLines_scan->uiIDSOrdTxCMPrevReading=$t_scan_pmr;
//            $_btblInvoiceLines_scan->uiIDSOrdTxCMCurrReading=$t_scan_cmr;
//            $_btblInvoiceLines_scan->ucIDSOrdTxCMMinVol=$min_scan_vol;
//            $_btblInvoiceLines_scan->ucIDSOrdTxCMRates=$scan_rate;
//            $_btblInvoiceLines_scan->ucIDSOrdTxCMServiceAsset=$total_scan_con->cCode;
//            $_btblInvoiceLines_scan->ucIDSOrdTxCMMeterType="BILLSCN";
  
//            $_btblInvoiceLines_scan->save();
//       }
    


      

      
//           // calculate A3MONO non CON
  
//           $asset_id = DB::table('_smtblServiceAsset As sa')
//           ->where('cCode',$value['ucSABillingAsset'])
//           ->select('AutoIdx')
//           ->first();

//           //A3 MONO
//           $a3Mono_counter_elapse =DB::table('_smtblcounterelapsed')
//           ->where('iServiceAssetId',$asset_id->AutoIdx)
//           ->where('cCode','A3BILLMON')
//           ->select('AutoIdx')
//           ->first();

     
     
//         if(!empty($a3Mono_counter_elapse))
//         {
//         $billing_a3mono_noncon = DB::table('_bvARAccountsFull As cl')
//           ->where('DCLink',$customer_details->dclink)
//           ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//           ->whereYear('ReadingDate', '=', $today->year)
//           ->whereMonth('ReadingDate', '=', $today->month)

//           ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//           DB::raw('sum(cmr.A3MPMR) as a3mpmr'),
//           DB::raw('sum(cmr.A3MCMR) as a3mcmr'),
//           DB::raw('sum(cmr.A3MCMR - cmr.A3MPMR) as a3monunit'),'sa.cDescription','sa.ucSASerialNo','sa.cCode'
        
//           )
//           ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//           ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//           ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cDescription','sa.ucSASerialNo','sa.cCode')
//           ->get();


//           foreach($billing_a3mono_noncon as $data){
//             $asset_id = DB::table('_smtblServiceAsset As sa')
//             ->where('cCode',$data->cCode)
//             ->select('AutoIdx')
//             ->first();            
          

//             // A3  Mono consolidated calculation

//              //A3 MONO
//              $a3Mono_counter_elapse =DB::table('_smtblcounterelapsed')
//              ->where('iServiceAssetId',$asset_id->AutoIdx)
//              ->where('cCode','A3BILLMON')
//              ->select('AutoIdx')
//              ->first();

             
//              $a3mono_min_vol = _smtblrateslab::where('iBillingID',$a3Mono_counter_elapse->AutoIdx ?? '')
//               ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//               ->groupBy('fRate')
//               ->first();
//               if(!empty($a3mono_min_vol) && $a3mono_min_vol->fRate > 0)
//               {
 
//          if($a3mono_min_vol->min_vol >=1000000){
//              $data->a3mono_min_vol = null;
//             }
//          else{
//              $data->a3mono_min_vol =$a3mono_min_vol->min_vol ?? null;
//          }
//          if( $a3mono_min_vol->min_vol  >=1000000 ){
 
//              $total_frate_a3mono =($a3mono_min_vol->fRate * $data->a3monunit);
//              $rate_frate_a3mono =number_format($a3mono_min_vol->fRate,4);
           
 
//              $data->comb_rates_a3m = $rate_frate_a3mono;
//              $data->total_a3m = $total_frate_a3mono;            
 
 
//          }
//          else{
//           $diff_a3mon = ($data->a3munit - $A3min_vol->min_vol);
//           if($diff_a3mon > 0){
//               $rate_min_a3m =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//               ->where('iToqty',$A3min_vol->min_vol)
//               ->select('frate')
//               ->first();
//               $total_min_a3m =($rate_min_a3m->frate * $A3min_vol->min_vol);

//               $rate_a3m =number_format($rate_min_a3m->frate,4);
              
//               //slab two starts
//               $Increment_1a3m =$A3min_vol->min_vol +1; 

      
//           // we get the rate for the next slab and their readings
//           $next_slab_a3m =_smtblrateslab::where('iBillingID',$A3counter_elapse->AutoIdx)
//           ->where('iFromQty','<=',$Increment_1a3m)
//           ->where('iToqty','>=',$Increment_1a3m)
//           ->select('iFromQty','iToqty','fRate')
//           ->first();
     
//               //  we get the difference of this slab
//           $diff_slab1_a3m =($next_slab_a3m->iToqty - $next_slab_a3m->iFromQty)+1;


//           //  we check if the remainder fulls fall in this slab or exceed
//           $diff_bil_slab_a3m =$diff_a3mon -$diff_slab1_a3m;           

//          //counts fits fully in this slab the difference is negative
//           if($diff_bil_slab_a3m < 0){
//               $total_slab_1_a3m =($diff_a3mon * $next_slab_a3m->fRate );

//           $rate_2_a3m =number_format($next_slab_a3m->fRate,4);            

//           // two slabs used             
//           $comb_rates_a3m =$rate_a3m.' | '.$rate_2_a3m;
//           $total_a3m =($total_slab_1_a3m +$total_min_a3m);

//           $data->comb_rates_a3m = $comb_rates_a3m;
//           $data->total_a3m = $total_a3m;          
      
//       }
//       }

//       else{
//           $rate_min_a3m =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//               ->where('iToqty',$A3min_vol->min_vol)
//               ->select('frate')
//               ->first();
//           $total_min_a3m =($rate_min_a3m->frate * $A3min_vol->min_vol);
//           $rate_a3m =number_format($rate_min_a3m->frate,4);

//           $data->comb_rates_a3m = $rate_a3m;
//           $data->total_a3m = $total_min_a3m;  

//       }
//       }
//     }
//   }
      
     
//       $bill_a3mono_noncon =$billing_a3mono_noncon[0] ?? '';
//       $a3mono_noncon =(object) $bill_a3mono_noncon;

//       // dd($a3mono_noncon);
//     $t_a3m_cmr = $a3mono_noncon->a3mcmr;
//      $t_a3m_pmr = $a3mono_noncon->a3mpmr;
//      $tA3munits = $a3mono_noncon-> a3monunit;
//      $A3m_rate =$a3mono_noncon->comb_rates_a3m;
//      $min_A3m_vol =$a3mono_noncon->a3mono_min_vol ?? 0;
//      $tbillmeter = "BILLA3MON";
//      $treadingdate = "1900-01-01";
//      $total_A3m_con =$a3mono_noncon->total_a3m ?? 0;


//      if($tax_rate->TaxRate > 0){
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_A3m_con;
//         $tlineamttax = ($total_A3m_con * ($tax_rate->TaxRate / 100));
//         $tlineamtincl =($total_A3m_con * (1 + $tax_rate->TaxRate / 100));
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_A3m_con;
//         $tfclineamttax = ($total_A3m_con * ($tax_rate->TaxRate / 100));
//         $tfclineamtincl = ($total_A3m_con * (1 + $tax_rate->TaxRate / 100));
//         $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tlineamttax = $tfclineamttax * $exc_rate;
//         $tlineamtincl = $tfclineamtincl * $exc_rate;
//       }
//     }
//     else{
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_A3m_con;
//         $tlineamttax = 0;
//         $tlineamtincl =$total_A3m_con;
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_A3m_con;
//         $tfclineamttax = 0;
//         $tfclineamtincl = $total_A3m_con;
//         $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tlineamttax = 0;
//         $tlineamtincl = $tfclineamtincl * $exc_rate;
//       }




//     }
//         $tstockid = 17987;
//         $tlinenote ="CMR: $t_a3m_cmr\r\nPMR: $t_a3m_pmr\r\nCOPIES DONE: $tA3munits\r\nMIN. VOL: $min_A3m_vol\r\nRATE: $A3m_rate";
//         $tlinedesc = "A3 Mono Billing for $a3mono_noncon->cDescription ($a3mono_noncon->ucSASerialNo)";


//         $_btblInvoiceLines_A3mono = new _btblInvoiceLines;
//            $_btblInvoiceLines_A3mono->iInvoiceID =$invoice_id;
//            $_btblInvoiceLines_A3mono->iOrigLineID =0;
//            $_btblInvoiceLines_A3mono->iGrvLineID =0;
//            $_btblInvoiceLines_A3mono->iLineDocketMode =0; 
//            $_btblInvoiceLines_A3mono->cDescription =$tlinedesc; 
//            $_btblInvoiceLines_A3mono->iUnitsOfMeasureStockingID=0; 
//            $_btblInvoiceLines_A3mono->iUnitsOfMeasureCategoryID=0;
//            $_btblInvoiceLines_A3mono->iUnitsOfMeasureID=0;
//            $_btblInvoiceLines_A3mono->fQuantity=1;
//            $_btblInvoiceLines_A3mono->fQtyChange=1;
//            $_btblInvoiceLines_A3mono->fQtyToProcess=1; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcess=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessed =0; 
//            $_btblInvoiceLines_A3mono->fQtyReserved=0; 
//            $_btblInvoiceLines_A3mono->fQtyReservedChange =0;
//            $_btblInvoiceLines_A3mono->cLineNotes=$tlinenote; 
//            $_btblInvoiceLines_A3mono->fUnitPriceExcl=$tlineamtexcl; 
//            $_btblInvoiceLines_A3mono->fUnitPriceIncl=$tlineamtincl;
//            $_btblInvoiceLines_A3mono->iUnitPriceOverrideReasonID=0; 
//            $_btblInvoiceLines_A3mono->fUnitCost=0;
//            $_btblInvoiceLines_A3mono->fLineDiscount=0; 
//            $_btblInvoiceLines_A3mono->iLineDiscountReasonID=0;
//            $_btblInvoiceLines_A3mono->iReturnReasonID=0; 
//            $_btblInvoiceLines_A3mono->fTaxRate=$tax_rate->TaxRate; 
//            $_btblInvoiceLines_A3mono->bIsSerialItem=0; 
//            $_btblInvoiceLines_A3mono->bIsWhseItem=1;
//            $_btblInvoiceLines_A3mono->fAddCost=0; 
//            $_btblInvoiceLines_A3mono->cTradeinItem='';
//            $_btblInvoiceLines_A3mono->iStockCodeID=$tstockid; 
//            $_btblInvoiceLines_A3mono->iJobID=0;
//            $_btblInvoiceLines_A3mono->iWarehouseID=6;
//            $_btblInvoiceLines_A3mono->iTaxTypeID=$customer_details->ideftaxtypeid;
//            $_btblInvoiceLines_A3mono->iPriceListNameID=1;
//            $_btblInvoiceLines_A3mono->fQuantityLineTotIncl=$tlineamtincl;
//            $_btblInvoiceLines_A3mono->fQuantityLineTotExcl=$tlineamtexcl;
//            $_btblInvoiceLines_A3mono->fQuantityLineTotInclNoDisc=$tlineamtincl;
//            $_btblInvoiceLines_A3mono->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQuantityLineTaxAmount =$tlineamttax; 
//            $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotIncl =$tlineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotExcl =$tlineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmount =$tlineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotIncl =$tlineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmount =$tlineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotIncl=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExcl =0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclNoDisc=0;
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclNoDisc=0;
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmount=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountNoDisc=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotIncl=0;
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExcl=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclNoDisc=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclNoDisc=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmount=0;
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountNoDisc=0; 
//            $_btblInvoiceLines_A3mono->fUnitPriceExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_A3mono->fUnitPriceInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fUnitCostForeign=0;
//            $_btblInvoiceLines_A3mono->fAddCostForeign=0;
//            $_btblInvoiceLines_A3mono->fQuantityLineTotInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//            $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//            $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//            $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclNoDiscForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclNoDiscForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountForeign=0; 
//            $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//            $_btblInvoiceLines_A3mono->iLineRepID=$customer_details->repid; 
//            $_btblInvoiceLines_A3mono->iLineProjectID=2; 
//            $_btblInvoiceLines_A3mono->iLedgerAccountID=0; 
//            $_btblInvoiceLines_A3mono->IModule=0;
//            $_btblInvoiceLines_A3mono->bChargeCom=1;
//            $_btblInvoiceLines_A3mono->bIsLotItem=0;
//           //  $_btblInvoiceLines_A3mono->iLotID=0;
//           //  $_btblInvoiceLines_A3mono->cLotNumber='';
//           //  $_btblInvoiceLines_A3mono->dLotExpiryDate=null;
//            $_btblInvoiceLines_A3mono->iMFPID=0;
//            $_btblInvoiceLines_A3mono->iLineID=1;
//            $_btblInvoiceLines_A3mono->iLinkedLineID=0;
//            $_btblInvoiceLines_A3mono->fQtyLinkedUsed=null;
//            $_btblInvoiceLines_A3mono->fUnitPriceInclOrig=null;
//            $_btblInvoiceLines_A3mono->fUnitPriceExclOrig=Null;
//            $_btblInvoiceLines_A3mono->fUnitPriceInclForeignOrig=Null;
//            $_btblInvoiceLines_A3mono->fUnitPriceExclForeignOrig=0;
//            $_btblInvoiceLines_A3mono->iDeliveryMethodID=0;
//            $_btblInvoiceLines_A3mono->fQtyDeliver=0;
//            $_btblInvoiceLines_A3mono->dDeliveryDate=$today;
//            $_btblInvoiceLines_A3mono->iDeliveryStatus=0;
//            $_btblInvoiceLines_A3mono->fQtyForDelivery=0;
//            $_btblInvoiceLines_A3mono->bPromotionApplied=0;
//            $_btblInvoiceLines_A3mono->fPromotionPriceExcl=0;
//            $_btblInvoiceLines_A3mono->fPromotionPriceIncl=0;
//            $_btblInvoiceLines_A3mono->cPromotionCode=0;
//            $_btblInvoiceLines_A3mono->iSOLinkedPOLineID=0;
//            $_btblInvoiceLines_A3mono->fLength=0;
//            $_btblInvoiceLines_A3mono->fWidth=0;
//            $_btblInvoiceLines_A3mono->fHeight=0;
//            $_btblInvoiceLines_A3mono->iPieces=0;
//            $_btblInvoiceLines_A3mono->iPiecesToProcess=0;
//            $_btblInvoiceLines_A3mono->iPiecesLastProcess=0;
//            $_btblInvoiceLines_A3mono->iPiecesProcessed=0;
//            $_btblInvoiceLines_A3mono->iPiecesReserved=0;
//            $_btblInvoiceLines_A3mono->iPiecesDeliver=0;
//            $_btblInvoiceLines_A3mono->iPiecesForDelivery=0;
//            $_btblInvoiceLines_A3mono->fQuantityUR=1;
//            $_btblInvoiceLines_A3mono->fQtyChangeUR=1;
//            $_btblInvoiceLines_A3mono->fQtyToProcessUR=1;
//            $_btblInvoiceLines_A3mono->fQtyLastProcessUR=0;
//            $_btblInvoiceLines_A3mono->fQtyProcessedUR=0;
//            $_btblInvoiceLines_A3mono->fQtyReservedUR=0;
//            $_btblInvoiceLines_A3mono->fQtyReservedChangeUR=0;
//            $_btblInvoiceLines_A3mono->fQtyDeliverUR=0;
//            $_btblInvoiceLines_A3mono->fQtyForDeliveryUR=0;
//            $_btblInvoiceLines_A3mono->fQtyLinkedUsedUR=0;
//            $_btblInvoiceLines_A3mono->iPiecesLinkedUsed=0;
//            $_btblInvoiceLines_A3mono->iSalesWhseID=0;
//            $_btblInvoiceLines_A3mono->_btblInvoiceLines_iBranchID=1;
//            $_btblInvoiceLines_A3mono->udIDSOrdTxCMReadingDate=$today;
//            $_btblInvoiceLines_A3mono->uiIDSOrdTxCMPrevReading=$t_a3m_pmr;
//            $_btblInvoiceLines_A3mono->uiIDSOrdTxCMCurrReading=$t_a3m_cmr;
//            $_btblInvoiceLines_A3mono->ucIDSOrdTxCMMinVol=$min_A3m_vol;
//            $_btblInvoiceLines_A3mono->ucIDSOrdTxCMRates=$A3m_rate;
//            $_btblInvoiceLines_A3mono->ucIDSOrdTxCMServiceAsset=$a3mono_noncon->cCode;
//            $_btblInvoiceLines_A3mono->ucIDSOrdTxCMMeterType="BILLA3MON";
  
//            $_btblInvoiceLines_A3mono->save();


//         }
      

//         // calculate A3 COLOR NON CONSOLIDATED
//         $asset_id = DB::table('_smtblServiceAsset As sa')
//         ->where('cCode',$value['ucSABillingAsset'])
//         ->select('AutoIdx')
//         ->first();

//         //A3 COLOR
//         $a3color_counter_elapse =DB::table('_smtblcounterelapsed')
//         ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
//         ->where('cCode','A3BILLCOL')
//         ->select('AutoIdx')
//         ->first();

//         if(!empty($a3color_counter_elapse))
//               {
//               $billing_a3color_noncon = DB::table('_bvARAccountsFull As cl')
//               ->where('DCLink',$customer_details->dclink)
//               ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//               ->whereYear('ReadingDate', '=', $today->year)
//               ->whereMonth('ReadingDate', '=', $today->month)
    
//               ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//               DB::raw('sum(cmr.A3CPMR) as a3Cpmr'),
//               DB::raw('sum(cmr.A3CCMR) as a3Ccmr'),
//               DB::raw('sum(cmr.A3CCMR - cmr.A3CPMR) as a3Colunit'),'sa.cDescription','sa.ucSASerialNo','sa.cCode'   
              
            
//               )
//               ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//               ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//               ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cDescription','sa.ucSASerialNo','sa.cCode')
//               ->get();
    
           

//               foreach($billing_a3color_noncon as $data){

//                 $asset_id = DB::table('_smtblServiceAsset As sa')
//                 ->where('cCode',$data->cCode)
//                 ->select('AutoIdx')
//                 ->first();            
              

//             // A3  COLOR consolidated calculation

//                 //A3 COLOR
//                 $a3color_counter_elapse =DB::table('_smtblcounterelapsed')
//                 ->where('iServiceAssetId',$asset_id->AutoIdx)
//                 ->where('cCode','A3BILLCOL')
//                 ->select('AutoIdx')
//                 ->first();

             
//                 $a3color_min_vol = _smtblrateslab::where('iBillingID',$a3color_counter_elapse->AutoIdx ?? '')
//                   ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//                   ->groupBy('fRate',DB::raw('(iToqty)'))
//                   ->first();

//                   if(!empty($a3color_min_vol) && $a3color_min_vol->fRate > 0)
//                   {
                  
//                   if($a3color_min_vol->min_vol >=1000000){
//                     $data->a3color_min_vol = null;
//                    }
//                 else{
//                     $data->a3color_min_vol =$a3color_min_vol->min_vol ?? null;
//                 }
//                 if( $a3color_min_vol->min_vol  >=1000000){
        
//                     $total_frate_a3color =($a3color_min_vol->fRate * $data->a3Colunit);
//                     $rate_frate_a3color =number_format($a3color_min_vol->fRate,4);
                  
        
//                     $data->comb_rates_a3C = $rate_frate_a3color;
//                     $data->total_a3C = $total_frate_a3color;            
        
        
//                 }
                
//               else{
//                 $diff_a3Col = ($data->a3cunit - $A3Cmin_vol->min_vol);
//                 if($diff_a3Col > 0){
//                     $rate_min_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//                     ->where('iToqty',$A3Cmin_vol->min_vol)
//                     ->select('frate')
//                     ->first();
//                     $total_min_a3C =($rate_min_a3C->frate * $A3Cmin_vol->min_vol);

//                     $rate_a3C =number_format($rate_min_a3C->frate,3);
                    
//                     //slab two starts
//                     $Increment_1a3C =$A3Cmin_vol->min_vol +1; 

            
//                 // we get the rate for the next slab and their readings
//                 $next_slab_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//                 ->where('iFromQty','<=',$Increment_1a3C)
//                 ->where('iToqty','>=',$Increment_1a3C)
//                 ->select('iFromQty','iToqty','fRate')
//                 ->first();
           
//                     //  we get the difference of this slab
//                 $diff_slab1_a3C =($next_slab_a3C->iToqty - $next_slab_a3C->iFromQty)+1;


//                 //  we check if the remainder fulls fall in this slab or exceed
//                 $diff_bil_slab_a3C =$diff_a3Col -$diff_slab1_a3C;           

//                //counts fits fully in this slab the difference is negative
//                 if($diff_bil_slab_a3C < 0){
//                     $total_slab_1_a3C =($diff_a3Col * $next_slab_a3C->fRate );

//                     $rate_2_a3C =number_format($next_slab_a3C->fRate,4);            

//                 // two slabs used             
//                 $comb_rates_a3C =$rate_a3C.' | '.$rate_2_a3C;
//                 $total_a3C =($total_slab_1_a3C +$total_min_a3C);

//                 $data->comb_rates_a3C = $comb_rates_a3C;
//                 $data->total_a3C = $total_a3C;          
            
//             }
//             }

//             else{
//                 $rate_min_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//                     ->where('iToqty',$A3Cmin_vol->min_vol)
//                     ->select('frate')
//                     ->first();
//                 $total_min_a3C =($rate_min_a3C->frate * $A3Cmin_vol->min_vol);
//                 $rate_a3C =number_format($rate_min_a3C->frate,4);
    
//                 $data->comb_rates_a3C = $rate_a3C;
//                 $data->total_a3C = $total_min_a3C;  

//             }
//             }   
//           } 
//         }            

            

             
//           $bill_a3color_noncon =$billing_a3color_noncon[0] ?? '';
//           $a3color_noncon =(object) $bill_a3color_noncon;

        
//         $t_a3C_cmr = $a3color_noncon->a3Ccmr;
//         $t_a3C_pmr = $a3color_noncon->a3Cpmr;
//         $tA3Cunits = $a3color_noncon-> a3Colunit;
//         $A3C_rate =$a3color_noncon->comb_rates_a3C;
//         $min_A3C_vol =$a3color_noncon->a3color_min_vol ?? 0;
//         $tbillmeter = "BILLA3COL";
//         $treadingdate = "1900-01-01";
//         $total_A3C_con =$a3color_noncon->total_a3C ?? 0;
   
//         if($tax_rate->TaxRate > 0){
//          if($customer_details->icurrencyid ==0){
//            $tlineamtexcl = $total_A3C_con;
//            $tlineamttax = ($total_A3C_con * ($tax_rate->TaxRate / 100));
//            $tlineamtincl =($total_A3C_con * (1 + $tax_rate->TaxRate / 100));
//            $tfclineamtexcl = 0;
//            $tfclineamttax = 0;
//            $tfclineamtincl = 0;
//          }
//          else{
//            $tfclineamtexcl = $total_A3C_con;
//            $tfclineamttax = ($total_A3C_con * ($tax_rate->TaxRate / 100));
//            $tfclineamtincl = ($total_A3C_con * (1 + $tax_rate->TaxRate / 100));
//            $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//            $tlineamttax = $tfclineamttax * $exc_rate;
//            $tlineamtincl = $tfclineamtincl * $exc_rate;
//          }
//        }
//        else{
//         if($customer_details->icurrencyid ==0){
//           $tlineamtexcl = $total_A3C_con;
//           $tlineamttax = 0;
//           $tlineamtincl =$total_A3C_con;
//           $tfclineamtexcl = 0;
//           $tfclineamttax = 0;
//           $tfclineamtincl = 0;
//         }
//         else{
//           $tfclineamtexcl = $total_A3C_con;
//           $tfclineamttax = 0;
//           $tfclineamtincl = $total_A3C_con;
//           $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//           $tlineamttax = 0;
//           $tlineamtincl = $tfclineamtincl * $exc_rate;
//         }



//        }
//        $tstockid = 17987;
//        $tlinenote ="CMR: $t_a3C_cmr\r\nPMR: $t_a3C_pmr\r\nCOPIES DONE: $tA3Cunits\r\nMIN. VOL: $min_A3C_vol\r\nRATE: $A3C_rate";
//         $tlinedesc = "A3 Color Billing for $a3color_noncon->cDescription ($a3color_noncon->ucSASerialNo)";

//     $_btblInvoiceLines_A3Color = new _btblInvoiceLines;
//     $_btblInvoiceLines_A3Color->iInvoiceID =$invoice_id;
//     $_btblInvoiceLines_A3Color->iOrigLineID =0;
//     $_btblInvoiceLines_A3Color->iGrvLineID =0;
//     $_btblInvoiceLines_A3Color->iLineDocketMode =0; 
//     $_btblInvoiceLines_A3Color->cDescription =$tlinedesc; 
//     $_btblInvoiceLines_A3Color->iUnitsOfMeasureStockingID=0; 
//     $_btblInvoiceLines_A3Color->iUnitsOfMeasureCategoryID=0;
//     $_btblInvoiceLines_A3Color->iUnitsOfMeasureID=0;
//     $_btblInvoiceLines_A3Color->fQuantity=1;
//     $_btblInvoiceLines_A3Color->fQtyChange=1;
//     $_btblInvoiceLines_A3Color->fQtyToProcess=1; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcess=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessed =0; 
//     $_btblInvoiceLines_A3Color->fQtyReserved=0; 
//     $_btblInvoiceLines_A3Color->fQtyReservedChange =0;
//     $_btblInvoiceLines_A3Color->cLineNotes=$tlinenote; 
//     $_btblInvoiceLines_A3Color->fUnitPriceExcl=$tlineamtexcl; 
//     $_btblInvoiceLines_A3Color->fUnitPriceIncl=$tlineamtincl;
//     $_btblInvoiceLines_A3Color->iUnitPriceOverrideReasonID=0; 
//     $_btblInvoiceLines_A3Color->fUnitCost=0;
//     $_btblInvoiceLines_A3Color->fLineDiscount=0; 
//     $_btblInvoiceLines_A3Color->iLineDiscountReasonID=0;
//     $_btblInvoiceLines_A3Color->iReturnReasonID=0; 
//     $_btblInvoiceLines_A3Color->fTaxRate=$tax_rate->TaxRate; 
//     $_btblInvoiceLines_A3Color->bIsSerialItem=0; 
//     $_btblInvoiceLines_A3Color->bIsWhseItem=1;
//     $_btblInvoiceLines_A3Color->fAddCost=0; 
//     $_btblInvoiceLines_A3Color->cTradeinItem='';
//     $_btblInvoiceLines_A3Color->iStockCodeID=$tstockid; 
//     $_btblInvoiceLines_A3Color->iJobID=0;
//     $_btblInvoiceLines_A3Color->iWarehouseID=6;
//     $_btblInvoiceLines_A3Color->iTaxTypeID=$customer_details->ideftaxtypeid;
//     $_btblInvoiceLines_A3Color->iPriceListNameID=1;
//     $_btblInvoiceLines_A3Color->fQuantityLineTotIncl=$tlineamtincl;
//     $_btblInvoiceLines_A3Color->fQuantityLineTotExcl=$tlineamtexcl;
//     $_btblInvoiceLines_A3Color->fQuantityLineTotInclNoDisc=$tlineamtincl;
//     $_btblInvoiceLines_A3Color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQuantityLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotIncl=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExcl =0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclNoDisc=0;
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclNoDisc=0;
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmount=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotIncl=0;
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExcl=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclNoDisc=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclNoDisc=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmount=0;
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines_A3Color->fUnitPriceExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_A3Color->fUnitPriceInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fUnitCostForeign=0;
//     $_btblInvoiceLines_A3Color->fAddCostForeign=0;
//     $_btblInvoiceLines_A3Color->fQuantityLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//     $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//     $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountForeign=0; 
//     $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines_A3Color->iLineRepID=$customer_details->repid; 
//     $_btblInvoiceLines_A3Color->iLineProjectID=2; 
//     $_btblInvoiceLines_A3Color->iLedgerAccountID=0; 
//     $_btblInvoiceLines_A3Color->IModule=0;
//     $_btblInvoiceLines_A3Color->bChargeCom=1;
//     $_btblInvoiceLines_A3Color->bIsLotItem=0;
//     // $_btblInvoiceLines_A3Color->iLotID=0;
//     // $_btblInvoiceLines_A3Color->cLotNumber='';
//     // $_btblInvoiceLines_A3Color->dLotExpiryDate=null;
//     $_btblInvoiceLines_A3Color->iMFPID=0;
//     $_btblInvoiceLines_A3Color->iLineID=1;
//     $_btblInvoiceLines_A3Color->iLinkedLineID=0;
//     $_btblInvoiceLines_A3Color->fQtyLinkedUsed=null;
//     $_btblInvoiceLines_A3Color->fUnitPriceInclOrig=null;
//     $_btblInvoiceLines_A3Color->fUnitPriceExclOrig=Null;
//     $_btblInvoiceLines_A3Color->fUnitPriceInclForeignOrig=Null;
//     $_btblInvoiceLines_A3Color->fUnitPriceExclForeignOrig=0;
//     $_btblInvoiceLines_A3Color->iDeliveryMethodID=0;
//     $_btblInvoiceLines_A3Color->fQtyDeliver=0;
//     $_btblInvoiceLines_A3Color->dDeliveryDate=$today;
//     $_btblInvoiceLines_A3Color->iDeliveryStatus=0;
//     $_btblInvoiceLines_A3Color->fQtyForDelivery=0;
//     $_btblInvoiceLines_A3Color->bPromotionApplied=0;
//     $_btblInvoiceLines_A3Color->fPromotionPriceExcl=0;
//     $_btblInvoiceLines_A3Color->fPromotionPriceIncl=0;
//     $_btblInvoiceLines_A3Color->cPromotionCode=0;
//     $_btblInvoiceLines_A3Color->iSOLinkedPOLineID=0;
//     $_btblInvoiceLines_A3Color->fLength=0;
//     $_btblInvoiceLines_A3Color->fWidth=0;
//     $_btblInvoiceLines_A3Color->fHeight=0;
//     $_btblInvoiceLines_A3Color->iPieces=0;
//     $_btblInvoiceLines_A3Color->iPiecesToProcess=0;
//     $_btblInvoiceLines_A3Color->iPiecesLastProcess=0;
//     $_btblInvoiceLines_A3Color->iPiecesProcessed=0;
//     $_btblInvoiceLines_A3Color->iPiecesReserved=0;
//     $_btblInvoiceLines_A3Color->iPiecesDeliver=0;
//     $_btblInvoiceLines_A3Color->iPiecesForDelivery=0;
//     $_btblInvoiceLines_A3Color->fQuantityUR=1;
//     $_btblInvoiceLines_A3Color->fQtyChangeUR=1;
//     $_btblInvoiceLines_A3Color->fQtyToProcessUR=1;
//     $_btblInvoiceLines_A3Color->fQtyLastProcessUR=0;
//     $_btblInvoiceLines_A3Color->fQtyProcessedUR=0;
//     $_btblInvoiceLines_A3Color->fQtyReservedUR=0;
//     $_btblInvoiceLines_A3Color->fQtyReservedChangeUR=0;
//     $_btblInvoiceLines_A3Color->fQtyDeliverUR=0;
//     $_btblInvoiceLines_A3Color->fQtyForDeliveryUR=0;
//     $_btblInvoiceLines_A3Color->fQtyLinkedUsedUR=0;
//     $_btblInvoiceLines_A3Color->iPiecesLinkedUsed=0;
//     $_btblInvoiceLines_A3Color->iSalesWhseID=0;
//     $_btblInvoiceLines_A3Color->_btblInvoiceLines_iBranchID=1;
//     $_btblInvoiceLines_A3Color->udIDSOrdTxCMReadingDate=$today;
//     $_btblInvoiceLines_A3Color->uiIDSOrdTxCMPrevReading=$t_a3C_pmr;
//     $_btblInvoiceLines_A3Color->uiIDSOrdTxCMCurrReading=$t_a3C_cmr;
//     $_btblInvoiceLines_A3Color->ucIDSOrdTxCMMinVol=$min_A3C_vol;
//     $_btblInvoiceLines_A3Color->ucIDSOrdTxCMRates=$A3C_rate;
//     $_btblInvoiceLines_A3Color->ucIDSOrdTxCMServiceAsset=$a3color_noncon->cCode;
//     $_btblInvoiceLines_A3Color->ucIDSOrdTxCMMeterType="BILLA3COL";

//     $_btblInvoiceLines_A3Color->save();              

//       }

   

   
//       //  calculating rental Charges

//       $rental_amount = $value['rental_charges'];
         

//         $rental_charge = $rental_amount;

//         if($rental_charge > 0){
          
//           $tbillmeter = "RENTAL";
//           $rental_amt = $rental_charge;
//           if($tax_rate->TaxRate > 0){
//             if($customer_details->icurrencyid ==0){
//               $tlineamtexcl = $rental_amt;
//               $tlineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
//               $tlineamtincl =($rental_amt * (1 + $tax_rate->TaxRate / 100));
//               $tfclineamtexcl = 0;
//               $tfclineamttax = 0;
//               $tfclineamtincl = 0;
//             }
//             else{
//               $tfclineamtexcl = $rental_amt;
//               $tfclineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
//               $tfclineamtincl = ($rental_amt * (1 + $tax_rate->TaxRate / 100));
//               $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//               $tlineamttax = $tfclineamttax * $exc_rate;
//               $tlineamtincl = $tfclineamtincl * $exc_rate;
//             }
//           }
//           else{
//             if($customer_details->icurrencyid ==0){
//               $tlineamtexcl = $rental_amt;
//               $tlineamttax = 0;
//               $tlineamtincl =$rental_amt;
//               $tfclineamtexcl = 0;
//               $tfclineamttax = 0;
//               $tfclineamtincl = 0;
//             }
//             else{
//               $tfclineamtexcl = $rental_amt;
//               $tfclineamttax = 0;
//               $tfclineamtincl = $rental_amt;
//               $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//               $tlineamttax = 0;
//               $tlineamtincl = $tfclineamtincl * $exc_rate;
//             }
//           }




          

//           $tlinenote = "";                    
//           $tlinedesc = "Rental for $month $today->year";          
//           $tstockid = 17987;

//           $_btblInvoiceLines_rental = new _btblInvoiceLines;
//           $_btblInvoiceLines_rental->iInvoiceID =$invoice_id;
//           $_btblInvoiceLines_rental->iOrigLineID =0;
//           $_btblInvoiceLines_rental->iGrvLineID =0;
//           $_btblInvoiceLines_rental->iLineDocketMode =0; 
//           $_btblInvoiceLines_rental->cDescription =$tlinedesc; 
//           $_btblInvoiceLines_rental->iUnitsOfMeasureStockingID=0; 
//           $_btblInvoiceLines_rental->iUnitsOfMeasureCategoryID=0;
//           $_btblInvoiceLines_rental->iUnitsOfMeasureID=0;
//           $_btblInvoiceLines_rental->fQuantity=1;
//           $_btblInvoiceLines_rental->fQtyChange=1;
//           $_btblInvoiceLines_rental->fQtyToProcess=1; 
//           $_btblInvoiceLines_rental->fQtyLastProcess=0; 
//           $_btblInvoiceLines_rental->fQtyProcessed =0; 
//           $_btblInvoiceLines_rental->fQtyReserved=0; 
//           $_btblInvoiceLines_rental->fQtyReservedChange =0;
//           $_btblInvoiceLines_rental->cLineNotes=$tlinenote; 
//           $_btblInvoiceLines_rental->fUnitPriceExcl=$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fUnitPriceIncl=$tlineamtincl;
//           $_btblInvoiceLines_rental->iUnitPriceOverrideReasonID=0; 
//           $_btblInvoiceLines_rental->fUnitCost=0;
//           $_btblInvoiceLines_rental->fLineDiscount=0; 
//           $_btblInvoiceLines_rental->iLineDiscountReasonID=0;
//           $_btblInvoiceLines_rental->iReturnReasonID=0; 
//           $_btblInvoiceLines_rental->fTaxRate=$tax_rate->TaxRate; 
//           $_btblInvoiceLines_rental->bIsSerialItem=0; 
//           $_btblInvoiceLines_rental->bIsWhseItem=1;
//           $_btblInvoiceLines_rental->fAddCost=0; 
//           $_btblInvoiceLines_rental->cTradeinItem='';
//           $_btblInvoiceLines_rental->iStockCodeID=$tstockid; 
//           $_btblInvoiceLines_rental->iJobID=0;
//           $_btblInvoiceLines_rental->iWarehouseID=6;
//           $_btblInvoiceLines_rental->iTaxTypeID=$customer_details->ideftaxtypeid;
//           $_btblInvoiceLines_rental->iPriceListNameID=1;
//           $_btblInvoiceLines_rental->fQuantityLineTotIncl=$tlineamtincl;
//           $_btblInvoiceLines_rental->fQuantityLineTotExcl=$tlineamtexcl;
//           $_btblInvoiceLines_rental->fQuantityLineTotInclNoDisc=$tlineamtincl;
//           $_btblInvoiceLines_rental->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmount =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotIncl =$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExcl =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmount =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotIncl =$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmount =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotIncl=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExcl =0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDisc=0;
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDisc=0;
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmount=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotIncl=0;
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExcl=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDisc=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDisc=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmount=0;
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_rental->fUnitPriceExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fUnitPriceInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fUnitCostForeign=0;
//           $_btblInvoiceLines_rental->fAddCostForeign=0;
//           $_btblInvoiceLines_rental->fQuantityLineTotInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotInclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->iLineRepID=$customer_details->repid; 
//           $_btblInvoiceLines_rental->iLineProjectID=2; 
//           $_btblInvoiceLines_rental->iLedgerAccountID=0; 
//           $_btblInvoiceLines_rental->IModule=0;
//           $_btblInvoiceLines_rental->bChargeCom=1;
//           $_btblInvoiceLines_rental->bIsLotItem=0;
//           // $_btblInvoiceLines_rental->iLotID=0;
//           // $_btblInvoiceLines_rental->cLotNumber='';
//           // $_btblInvoiceLines_rental->dLotExpiryDate=null;
//           $_btblInvoiceLines_rental->iMFPID=0;
//           $_btblInvoiceLines_rental->iLineID=1;
//           $_btblInvoiceLines_rental->iLinkedLineID=0;
//           $_btblInvoiceLines_rental->fQtyLinkedUsed=null;
//           $_btblInvoiceLines_rental->fUnitPriceInclOrig=null;
//           $_btblInvoiceLines_rental->fUnitPriceExclOrig=Null;
//           $_btblInvoiceLines_rental->fUnitPriceInclForeignOrig=Null;
//           $_btblInvoiceLines_rental->fUnitPriceExclForeignOrig=0;
//           $_btblInvoiceLines_rental->iDeliveryMethodID=0;
//           $_btblInvoiceLines_rental->fQtyDeliver=0;
//           $_btblInvoiceLines_rental->dDeliveryDate=$today;
//           $_btblInvoiceLines_rental->iDeliveryStatus=0;
//           $_btblInvoiceLines_rental->fQtyForDelivery=0;
//           $_btblInvoiceLines_rental->bPromotionApplied=0;
//           $_btblInvoiceLines_rental->fPromotionPriceExcl=0;
//           $_btblInvoiceLines_rental->fPromotionPriceIncl=0;
//           $_btblInvoiceLines_rental->cPromotionCode=0;
//           $_btblInvoiceLines_rental->iSOLinkedPOLineID=0;
//           $_btblInvoiceLines_rental->fLength=0;
//           $_btblInvoiceLines_rental->fWidth=0;
//           $_btblInvoiceLines_rental->fHeight=0;
//           $_btblInvoiceLines_rental->iPieces=0;
//           $_btblInvoiceLines_rental->iPiecesToProcess=0;
//           $_btblInvoiceLines_rental->iPiecesLastProcess=0;
//           $_btblInvoiceLines_rental->iPiecesProcessed=0;
//           $_btblInvoiceLines_rental->iPiecesReserved=0;
//           $_btblInvoiceLines_rental->iPiecesDeliver=0;
//           $_btblInvoiceLines_rental->iPiecesForDelivery=0;
//           $_btblInvoiceLines_rental->fQuantityUR=1;
//           $_btblInvoiceLines_rental->fQtyChangeUR=1;
//           $_btblInvoiceLines_rental->fQtyToProcessUR=1;
//           $_btblInvoiceLines_rental->fQtyLastProcessUR=0;
//           $_btblInvoiceLines_rental->fQtyProcessedUR=0;
//           $_btblInvoiceLines_rental->fQtyReservedUR=0;
//           $_btblInvoiceLines_rental->fQtyReservedChangeUR=0;
//           $_btblInvoiceLines_rental->fQtyDeliverUR=0;
//           $_btblInvoiceLines_rental->fQtyForDeliveryUR=0;
//           $_btblInvoiceLines_rental->fQtyLinkedUsedUR=0;
//           $_btblInvoiceLines_rental->iPiecesLinkedUsed=0;
//           $_btblInvoiceLines_rental->iSalesWhseID=0;
//           $_btblInvoiceLines_rental->_btblInvoiceLines_iBranchID=1;
//           $_btblInvoiceLines_rental->udIDSOrdTxCMReadingDate='';
//           $_btblInvoiceLines_rental->uiIDSOrdTxCMPrevReading='';
//           $_btblInvoiceLines_rental->uiIDSOrdTxCMCurrReading='';
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMMinVol='';
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMRates='';
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMServiceAsset=$color_bill_sep->cCode;
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMMeterType="RENTAL";

//           $_btblInvoiceLines_rental->save();

//         }
           
     

//     $total_inv =DB::table('_btblinvoicelines as lni')
//     ->where('iinvoiceid',$invoice_id)
//     ->select(DB::raw('sum(lni.fquantitylinetotincl) as inclamt'),
//           DB::raw('sum(lni.fquantitylinetotexcl) as exclamt'),
//           DB::raw('sum(lni.fquantitylinetaxamount) as taxamt'),
//           DB::raw('sum(lni.fquantitylinetotinclforeign) as fcinclamt'),
//           DB::raw('sum(lni.fquantitylinetotexclforeign) as fcexclamt'),
//           DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt'),
//           DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt')
    
    
    
//     )
//     ->first();

    
//     $tinvamtexcl =$total_inv->exclamt;
//     $tinvamtincl =$total_inv->inclamt;
//     $tinvamttax = $total_inv->taxamt;
//     $tordamtexcl = $tinvamtexcl;
//     $tordamtincl =  $tinvamtincl;
//     $tordamttax = $tinvamttax;
//     $tfcinvamtexcl =$total_inv->fcexclamt;
//     $tfcinvamtincl =$total_inv->fcinclamt;
//     $tfcinvamttax =$total_inv->fctaxamt;
//     $tfcordamtexcl = $tfcinvamtexcl;
//     $tfcordamtincl = $tfcinvamtincl;
//     $tfcordamttax = $tfcinvamttax;

//     $inv_update =InvNum::find($invoice_id);
    
//     $inv_update->InvTotExclDEx =$tinvamtexcl;
//     $inv_update->InvTotTaxDEx =$tinvamttax;
//     $inv_update->InvTotInclDEx =$tinvamtincl;
//     $inv_update->InvTotExcl =$tinvamtexcl;
//     $inv_update->InvTotTax =$tinvamttax;
//     $inv_update->InvTotIncl =$tinvamtincl;
//     $inv_update->OrdTotExclDEx=$tordamtexcl;
//     $inv_update->OrdTotTaxDEx=$tordamttax;
//     $inv_update->OrdTotInclDEx=$tordamtincl;
//     $inv_update->OrdTotExcl=$tordamtexcl;
//     $inv_update->OrdTotTax=$tordamttax;
//     $inv_update->OrdTotIncl=$tordamtincl;
//     $inv_update->fInvTotExclDExForeign=$tfcinvamtexcl;
//     $inv_update->fInvTotTaxDExForeign=$tfcinvamttax;
//     $inv_update->fInvTotInclDExForeign=$tfcinvamtincl;
//     $inv_update->fInvTotExclForeign=$tfcinvamtexcl;
//     $inv_update->fInvTotTaxForeign=$tfcinvamttax;
//     $inv_update->fInvTotInclForeign=$tfcinvamtincl;
//     $inv_update->fOrdTotExclDExForeign=$tfcordamtexcl;
//     $inv_update->fOrdTotTaxDExForeign=$tfcordamttax;
//     $inv_update->fOrdTotInclDExForeign=$tfcordamtincl;
//     $inv_update->fOrdTotExclForeign=$tfcordamtexcl;
//     $inv_update->fOrdTotTaxForeign=$tfcordamttax;
//     $inv_update->fOrdTotInclForeign=$tfcordamtincl;
//     $inv_update->InvTotInclExRounding=$tinvamtincl;
//     $inv_update->OrdTotInclExRounding=$tordamtincl;
//     $inv_update->fInvTotInclForeignExRounding=$tfcinvamtincl;
//     $inv_update->fOrdTotInclForeignExRounding=$tfcordamtincl;
//     $inv_update->save();   
  
         
//   }




// //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//     // 3 TYPE OF INVOICE

//     elseif($billing_type =='S' && $con_asset){     

                     
       
//            // get customer details
//         $customer  = $value['account'];
        
      
//       $customer_details =Client::where('Account',$customer)
//       ->select('dclink','name','post1','post2','post3','post4','email',DB::raw("(CASE WHEN repid = null THEN 0 ELSE repid END) AS repid"),
//       DB::raw("(CASE WHEN icurrencyid = null THEN 0 ELSE icurrencyid END) AS icurrencyid"),'ideftaxtypeid'
//       )
//       ->first();
  
  
     
//       $today=Carbon::parse($request->billing_date);
      

//       // get exchange rate
//       $echange_rate =DB::table('currencyhist')
//       ->select('fbuyrate')
//       ->where('icurrencyid',$customer_details->icurrencyid)
//       ->whereRaw('idCurrencyHist in (select max(idCurrencyHist) from currencyhist group by (iCurrencyID))')  
//       ->first();
  
//         $exc_rate =$echange_rate->fbuyrate  ?? '0';
  
   
  
  
//       // Get tax rate
     
//       $tax_rate =DB::table('taxrate')
//       ->where('idtaxrate',$customer_details->ideftaxtypeid)
//       ->select('TaxRate')
//       ->first(); 

  
//       //get po number
//     $po_number =DB::table('_etblDocProfiles')
//     ->where('idDocProfiles',60)
//     ->select('cautonumprefix','iautonumpadlength','inextautonum')
//     ->first();
//     $update =DB::table('_etblDocProfiles')
//     ->where('idDocProfiles',60)
//     ->update([
//     'inextautonum' => $po_number->inextautonum +1
//     ]);


//     //  pad po number if digits are less than 7
//     $padded_num = str_pad( $po_number->inextautonum, $po_number->iautonumpadlength , '0', STR_PAD_LEFT); 

//     //order Number
//     $tsonum = $po_number->cautonumprefix.$padded_num;



//     $month =$today->format('F');

//     $tinvamtexcl = 0;
//     $tinvamtincl = 0;
//     $tinvamttax = 0;
//     $tordamtexcl = 0;
//     $tordamtincl = 0;
//     $tordamttax = 0;
//     $tfcinvamtexcl = 0;
//     $tfcinvamtincl = 0;
//     $tfcinvamttax = 0;
//     $tfcordamtexcl = 0;
//     $tfcordamtincl = 0;
//     $tfcordamttax = 0;
//     $textorderno = $tsonum;
//     $torddesc = "Meter Reading Billing for $month $today->year";
//     $tsdate = $today;
                    

    
//     // generate so order

//     $new_so_order =new InvNum;
//     $new_so_order->DocType =4;
//     $new_so_order->DocVersion=1;
//     $new_so_order->DocState =1;
//     $new_so_order->DocFlag=0;
//     $new_so_order->OrigDocID =0;
//     $new_so_order->InvNumber='';
//     $new_so_order->GrvNumber ='';
//     $new_so_order->GrvID =0;
//     $new_so_order->AccountID=$customer_details->dclink;    
//     $new_so_order->Description =$torddesc;
//     $new_so_order->InvDate =$tsdate;
//     $new_so_order->OrderDate =$tsdate;
//     $new_so_order->DueDate =$tsdate;
//     $new_so_order->DeliveryDate =$tsdate;
//     $new_so_order->TaxInclusive =0;
//     $new_so_order->Email_Sent =1;
//     $new_so_order->Address1 =$customer_details->post1;
//     $new_so_order->Address2 =$customer_details->post2;
//     $new_so_order->Address3 =$customer_details->post3;
//     $new_so_order->Address4 =$customer_details->post4;
//     $new_so_order->Address5 ='';
//     $new_so_order->Address6 ='';
//     $new_so_order->PAddress1 ='';
//     $new_so_order->PAddress2 ='';
//     $new_so_order->PAddress3 ='';
//     $new_so_order->PAddress4 ='';
//     $new_so_order->PAddress5 ='';
//     $new_so_order->PAddress6 ='';
//     $new_so_order->DelMethodID =0;
//     $new_so_order->DocRepID =$customer_details->repid;
//     $new_so_order->OrderNum =$tsonum;
//     $new_so_order->DeliveryNote ='';
//     $new_so_order->InvDisc =0;
//     $new_so_order->InvDiscReasonID =0;
//     $new_so_order->Message1 ='';
//     $new_so_order->Message2 ='';
//     $new_so_order->Message3 ='';
//     $new_so_order->ProjectID =2;
//     $new_so_order->TillID =0;
//     $new_so_order->POSAmntTendered =0;
//     $new_so_order->POSChange =0;
//     $new_so_order->GrvSplitFixedCost =0;
//     $new_so_order->GrvSplitFixedAmnt =0;
//     $new_so_order->OrderStatusID =0;
//     $new_so_order->OrderPriorityID=4;
//     $new_so_order->ExtOrderNum =$textorderno;
//     $new_so_order->ForeignCurrencyID =$customer_details->icurrencyid;
//     $new_so_order->InvDiscAmnt =0; 
//     $new_so_order->InvDiscAmntEx =0;
//     $new_so_order->InvTotExclDEx =$tinvamtexcl;
//     $new_so_order->InvTotTaxDEx =$tinvamttax;
//     $new_so_order->InvTotInclDEx =$tinvamtincl;
//     $new_so_order->InvTotExcl =$tinvamtexcl;
//     $new_so_order->InvTotTax =$tinvamttax;
//     $new_so_order->InvTotIncl =$tinvamtincl;
//     $new_so_order->OrdDiscAmnt = 0;
//     $new_so_order->OrdDiscAmntEx =0;
//     $new_so_order->OrdTotExclDEx =$tordamtexcl;
//     $new_so_order->OrdTotTaxDEx =$tordamttax;
//     $new_so_order->OrdTotInclDEx =$tordamtincl;
//     $new_so_order->OrdTotExcl =$tordamtexcl;
//     $new_so_order->OrdTotTax =$tordamttax;
//     $new_so_order->OrdTotIncl =$tordamtincl;
//     $new_so_order->bUseFixedPrices =0;
//     $new_so_order->iDocPrinted =0;
//     $new_so_order->iINVNUMAgentID =1;
//     $new_so_order->fExchangeRate =$exc_rate;
//     $new_so_order->fGrvSplitFixedAmntForeign =0;
//     $new_so_order->fInvDiscAmntForeign =0;
//     $new_so_order->fInvDiscAmntExForeign =0;
//     $new_so_order->fInvTotExclDExForeign =$tfcinvamtexcl;
//     $new_so_order->fInvTotTaxDExForeign =$tfcinvamttax;
//     $new_so_order->fInvTotInclDExForeign =$tfcinvamtincl;
//     $new_so_order->fInvTotExclForeign =$tfcinvamtexcl;
//     $new_so_order->fInvTotTaxForeign =$tfcinvamttax;
//     $new_so_order->fInvTotInclForeign =$tfcinvamtincl;
//     $new_so_order->fOrdDiscAmntForeign =0;
//     $new_so_order->fOrdDiscAmntExForeign =0;
//     $new_so_order->fOrdTotExclDExForeign =$tfcordamtexcl;
//     $new_so_order->fOrdTotTaxDExForeign =$tfcordamttax;
//     $new_so_order->fOrdTotInclDExForeign =$tfcordamtincl;
//     $new_so_order->fOrdTotExclForeign =$tfcordamtexcl;
//     $new_so_order->fOrdTotTaxForeign =$tfcordamttax;
//     $new_so_order->fOrdTotInclForeign =$tfcinvamtincl;
//     $new_so_order->cTaxNumber ='';
//     $new_so_order->cAccountName=$customer_details->name;
//     $new_so_order->iProspectID =0;
//     $new_so_order->iOpportunityID =0;
//     $new_so_order->InvTotRounding =0;
//     $new_so_order->OrdTotRounding = 0;
//     $new_so_order->fInvTotForeignRounding =0;
//     $new_so_order->fOrdTotForeignRounding =0;
//     $new_so_order->bInvRounding =1;
//     $new_so_order->iInvSettlementTermsID =0;
//     $new_so_order->cSettlementTermInvMsg ='';
//     $new_so_order->iOrderCancelReasonID =0;
//     $new_so_order->iLinkedDocID =0;
//     $new_so_order->bLinkedTemplate =0;
//     $new_so_order->InvTotInclExRounding =$tinvamtincl;
//     $new_so_order->OrdTotInclExRounding =$tordamtincl;
//     $new_so_order->fInvTotInclForeignExRounding =$tfcinvamtincl;
//     $new_so_order->fOrdTotInclForeignExRounding =$tfcordamtincl;
//     $new_so_order->iEUNoTCID =0;
//     $new_so_order->iPOAuthStatus =0;
//     $new_so_order->iPOIncidentID =0;
//     $new_so_order->iSupervisorID =0;
//     $new_so_order->iMergedDocID =0;
//     $new_so_order->iDocEmailed =0;
//     $new_so_order->fDepositAmountForeign =0;
//     $new_so_order->fRefundAmount =0;
//     $new_so_order->bTaxPerLine =1;
//     $new_so_order->fDepositAmountTotal =0;
//     $new_so_order->fDepositAmountUnallocated =0;
//     $new_so_order->fDepositAmountNew =0;
//     $new_so_order->cContact ='';
//     $new_so_order->cTelephone ='';
//     $new_so_order->cFax ='';
//     $new_so_order->cEmail ='1';
//     $new_so_order->cCellular ='';
//     $new_so_order->iInsuranceState =0;
//     $new_so_order->cAuthorisedBy ='';
//     $new_so_order->cClaimNumber ='';
//     $new_so_order->cPolicyNumber ='';
//     $new_so_order->cExcessAccName ='';
//     $new_so_order->cExcessAccCont1 ='';
//     $new_so_order->cExcessAccCont2 ='';
//     $new_so_order->fExcessAmt =0;
//     $new_so_order->fExcessPct =0;
//     $new_so_order->fExcessExclusive =0;
//     $new_so_order->fExcessInclusive =0;
//     $new_so_order->fExcessTax =0;
//     $new_so_order->fAddChargeExclusive =0;
//     $new_so_order->fAddChargeTax =0;
//     $new_so_order->fAddChargeInclusive =0;
//     $new_so_order->fAddChargeExclusiveForeign =0;
//     $new_so_order->fAddChargeTaxForeign =0;
//     $new_so_order->fAddChargeInclusiveForeign =0;
//     $new_so_order->fOrdAddChargeExclusive =0;
//     $new_so_order->fOrdAddChargeTax =0;
//     $new_so_order->fOrdAddChargeInclusive =0;
//     $new_so_order->fOrdAddChargeExclusiveForeign =0;
//     $new_so_order->fOrdAddChargeTaxForeign =0;
//     $new_so_order->fOrdAddChargeInclusiveForeign =0;
//     $new_so_order->iInvoiceSplitDocID =0;
//     $new_so_order->cGIVNumber =0;
//     $new_so_order->bIsDCOrder =0;
//     $new_so_order->iDCBranchID =0;
//     $new_so_order->iSalesBranchID =0;
//     $new_so_order->InvNum_iBranchID =1;
//     // $new_so_order->ulIDSOrdOrderType ='MR';
//     $new_so_order->save();

  
// //  get invoice id to use in entering line items
//     $invoice_id =DB::table('invnum')
//     ->where('ordernum', $tsonum)
//     ->select('autoindex')
//     ->first();

//     //update udf table

//     $update_udf =DB::table('_etblUserHistLink')
//     ->insert([
//    'userdictid' => 60,
//    'Tableid' => $invoice_id->autoindex,
//    'UserValue' => 'MR'       
//     ]);


   


//      // getting mono consolidated billed Asset
//      $billing_mono_consolidated = DB::table('_bvARAccountsFull As cl')
//      ->where('DCLink',$customer_details->dclink)
//      ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//      ->whereYear('ReadingDate', '=', $today->year)
//      ->whereMonth('ReadingDate', '=', $today->month)

//      ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
//      DB::raw('sum(cmr.moncmr) as mon_cmr_total'),
//      DB::raw('sum(cmr.monpmr) as mon_pmr_total'),
//      DB::raw('sum(cmr.moncmr - cmr.monpmr) as monunit'),        
//      DB::raw('sum(cmr.a3mcmr - cmr.a3mpmr) as a3munit'),
//      DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.ucSABillingAsset'
        
//       )
//      ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//      ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//       ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
//       ->get();
     

//       foreach($billing_mono_consolidated as $data){
//          $asset_id = DB::table('_smtblServiceAsset As sa')
//          ->where('cCode',$data->ucSABillingAsset)
//          ->select('AutoIdx')
//          ->first();
     
//           $rentamt =DB::table('_smtblperiodservice As ps')
//          ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
//          ->Where(function($query)
//             {
//             $query->where('cCode','RENTAL')
//             ->orwhere('cCode','RENTAL CHARGES');
//             }) 
//          ->select('fAmount')
//          ->first();
//          $data->famount =$rentamt->famount ?? null;

//          //mono consolidated
//          // "MONO"
//          $counter_elapse =DB::table('_smtblcounterelapsed')
//          ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
//          ->Where(function($query)
//          {
//           $query->where('cCode','BILLMON')
//           ->orwhere('cCode','BILMON');
//         }) 
//          ->select('AutoIdx')
//          ->first(); 
         
         
        

//          $min_vol = _smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx ?? 0)
//          ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//          ->groupBy('fRate',DB::raw('(iToqty)'))
//          ->first();
//          if(($min_vol->min_vol ?? 0) >=1000000){
//              $data->min_mono_vol = null;
//             }
//             else{
//              $data->min_mono_vol =$min_vol->min_vol ?? null;
//             }
 
//             if(!empty($min_vol) && $min_vol->fRate > 0 )
//             {

//          if(( $min_vol->min_vol ?? 0) >=1000000 ){

//              $total_frate =(($min_vol->fRate ?? 0) * ($data->monunit ?? 0));
//              $rate_frate =number_format($min_vol->fRate ?? 0,2);

            
//              $data->comb_rates = $rate_frate;
//              $data->total = $total_frate;
         


//          }
//          else{
//          $diff_mon = (($data->monunit ?? 0) -($min_vol->min_vol ?? 0));
         
         
//          // the value is bigger than min volume
//          //slab two starts here
//          if($diff_mon > 0){
//          $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//          ->where('iToqty',$min_vol->min_vol)
//          ->select('frate')
//          ->first();
//          $total_min =($rate_min->frate * $min_vol->min_vol);

//          $rate =number_format($rate_min->frate,3);


//          // we increase minimum to move to the next slab 


//          //slab two starts
//          $Increment_1 =$min_vol->min_vol +1;          
         
//          // we get the rate for the next slab and their readings
//          $next_slab =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//          ->where('iFromQty','<=',$Increment_1)
//         ->where('iToqty','>=',$Increment_1)
//         ->select('iFromQty','iToqty','fRate')
//         ->first();
        
//          //  we get the difference of this slab
//         $diff_slab =($next_slab->iToqty - $next_slab->iFromQty)+1;

//      //  we check if the remainder fulls fall in this slab or exceed
//         $diff_bil_slab =$diff_mon -$diff_slab;           

//         //counts fits fully in this slab the difference is negative
//         if($diff_bil_slab < 0){
//          $total_slab_1 =($diff_mon * $next_slab->fRate );

//          $rate_2 =number_format($next_slab->fRate,3);            

//          // two slabs used             
//          $comb_rates =$rate.' | '.$rate_2;
//          $total =($total_slab_1 +$total_min);

//          $data->comb_rates = $comb_rates;
//          $data->total = $total;

       
         
//         }

//         //  slab 3
//         //the counter is still much than the previous slab difference
//         if($diff_bil_slab > 0){
//          $total_slab_1 =($diff_slab * $next_slab->fRate );      
         
//          $rate_2 =number_format($next_slab->fRate,3);
         

//          //we increase the slab to quantity to move to the next slab
//          $increment2 =$next_slab->iToqty +1;
        

//          // we get the slab values rates and quantity
//          $rate_slab2 =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//          ->where('iFromQty','<=',$increment2)
//         ->where('iToqty','>=',$increment2)
//         ->select('iFromQty','iToqty','fRate')
//         ->first();
        

//         $slab2_diff =($rate_slab2->iToqty -$rate_slab2->iFromQty)+1; 

   
//          //  we check if the remainder fully fall in this slab or exceed
//          $remaining_bil_slab =$diff_bil_slab -$slab2_diff; 
         
//          if($remaining_bil_slab < 0){

//          $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

//          $rate_3 =number_format($rate_slab2->fRate,4);

//          // three slabs used

//          $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3;
//          $total =($total_slab_1 +$total_min +$total_slab_2);

//          $data->comb_rates = $comb_rates;
//          $data->total = $total;


//          }

//          // slab four
//          if($remaining_bil_slab > 0){
//          $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

//          $rate_3 =number_format($rate_slab2->fRate,4);

//          // increase slab to next

//          $increment3 =$rate_slab2->iToqty +1;
//          $rate_slab3 =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//          ->where('iFromQty','<=',$increment3)
//          ->where('iToqty','>=',$increment3)
//          ->select('iFromQty','iToqty','fRate')
//          ->first();

         
//          $slab3_diff =($rate_slab3->iToqty -$rate_slab3->iFromQty)+1; 

//          $remaing_slab3_diff =($remaining_bil_slab -$slab3_diff);

//          if(!$remaing_slab3_diff){

//          $total_slab_3 =$remaining_bil_slab * $rate_slab3->fRate;
//          $rate_4 =number_format($rate_slab3->fRate,3);

//          // four slabs used

//          $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3. '|'.$rate_4;
//          $total =($total_slab_1 +$total_min +$total_slab_2 +$total_slab_3);

//          $data->comb_rates = $comb_rates;
//          $data->total = $total;

//          }
//          }
//         }             
//          }
//          else{
//           $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx ?? 0)
//          ->where('iToqty',$min_vol->min_vol ?? 0)
//          ->select('frate')
//          ->first();
//          $total_min =(($rate_min->frate ?? 0) * ($min_vol->min_vol ?? 0));
//          $rate =number_format($rate_min->frate ?? 0,3);

//          $data->comb_rates = $rate;
//          $data->total = $total_min;     
//          } 
       
       
//      }          

         

             
      
//       $bill_mono_con =$billing_mono_consolidated[0] ?? '';
//       $mono_consolidated =(object) $bill_mono_con;     



//      $t_mono_cmr = $mono_consolidated->mon_cmr_total;
//      $t_mon_pmr = $mono_consolidated->mon_pmr_total;
//      $tunits = $mono_consolidated-> monunit;
//      $mono_rate =$mono_consolidated->comb_rates ?? 0;
//      $min_mono_vol =$mono_consolidated->min_mono_vol ?? 0;
//      $tbillmeter = "BILLMON";
//      $treadingdate = "1900-01-01";
//      $total_mon =$mono_consolidated->total ?? 0;

    
//        $total_mono_cons =$total_mon;
//        if($tax_rate->TaxRate > 0){
//          if($customer_details->icurrencyid ==0){
//            $tlineamtexcl = $total_mono_cons;
//            $tlineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
//            $tlineamtincl =($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
//            $tfclineamtexcl = 0;
//            $tfclineamttax = 0;
//            $tfclineamtincl = 0;
//          }
//          else{
//            $tfclineamtexcl = $total_mono_cons;
//            $tfclineamttax = ($total_mono_cons * ($tax_rate->TaxRate / 100));
//            $tfclineamtincl = ($total_mono_cons * (1 + $tax_rate->TaxRate / 100));
//            $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//            $tlineamttax = $tfclineamttax * $exc_rate;
//            $tlineamtincl = $tfclineamtincl * $exc_rate;
//          }
//        }
//        else{
//         if($customer_details->icurrencyid ==0){
//           $tlineamtexcl = $total_mono_cons;
//           $tlineamttax = 0;
//           $tlineamtincl =$total_mono_cons;
//           $tfclineamtexcl = 0;
//           $tfclineamttax = 0;
//           $tfclineamtincl = 0;
//         }
//         else{
//           $tfclineamtexcl = $total_mono_cons;
//           $tfclineamttax = 0;
//           $tfclineamtincl = $total_mono_cons;
//           $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//           $tlineamttax = 0;
//           $tlineamtincl = $tfclineamtincl * $exc_rate;
//         }



//        }     
    

//      $tstockid = 17987;
//      $tlinenote ="CMR: $t_mono_cmr\r\nPMR: $t_mon_pmr\r\nCOPIES DONE: $tunits\r\nMIN. VOL: $min_mono_vol\r\nRATE: $mono_rate";
//      $tlinedesc = "Mono Billing for $month $today->year";

      

//       $_btblInvoiceLines = new _btblInvoiceLines;
//       $_btblInvoiceLines->iInvoiceID =$invoice_id->autoindex;
//       $_btblInvoiceLines->iOrigLineID =0;
//       $_btblInvoiceLines->iGrvLineID =0;
//       $_btblInvoiceLines->iLineDocketMode =0; 
//       $_btblInvoiceLines->cDescription =$tlinedesc; 
//       $_btblInvoiceLines->iUnitsOfMeasureStockingID=0; 
//       $_btblInvoiceLines->iUnitsOfMeasureCategoryID=0;
//       $_btblInvoiceLines->iUnitsOfMeasureID=0;
//       $_btblInvoiceLines->fQuantity=1;
//       $_btblInvoiceLines->fQtyChange=1;
//       $_btblInvoiceLines->fQtyToProcess=1; 
//       $_btblInvoiceLines->fQtyLastProcess=0; 
//       $_btblInvoiceLines->fQtyProcessed =0; 
//       $_btblInvoiceLines->fQtyReserved=0; 
//       $_btblInvoiceLines->fQtyReservedChange =0;
//       $_btblInvoiceLines->cLineNotes=$tlinenote; 
//       $_btblInvoiceLines->fUnitPriceExcl=$tlineamtexcl; 
//       $_btblInvoiceLines->fUnitPriceIncl=$tlineamtincl;
//       $_btblInvoiceLines->iUnitPriceOverrideReasonID=0; 
//       $_btblInvoiceLines->fUnitCost=0;
//       $_btblInvoiceLines->fLineDiscount=0; 
//       $_btblInvoiceLines->iLineDiscountReasonID=0;
//       $_btblInvoiceLines->iReturnReasonID=0; 
//       $_btblInvoiceLines->fTaxRate=$tax_rate->TaxRate; 
//       $_btblInvoiceLines->bIsSerialItem=0; 
//       $_btblInvoiceLines->bIsWhseItem=1;
//       $_btblInvoiceLines->fAddCost=0; 
//       $_btblInvoiceLines->cTradeinItem='';
//       $_btblInvoiceLines->iStockCodeID=$tstockid; 
//       $_btblInvoiceLines->iJobID=0;
//       $_btblInvoiceLines->iWarehouseID=6;
//       $_btblInvoiceLines->iTaxTypeID=$customer_details->ideftaxtypeid;
//       $_btblInvoiceLines->iPriceListNameID=1;
//       $_btblInvoiceLines->fQuantityLineTotIncl=$tlineamtincl;
//       $_btblInvoiceLines->fQuantityLineTotExcl=$tlineamtexcl;
//       $_btblInvoiceLines->fQuantityLineTotInclNoDisc=$tlineamtincl;
//       $_btblInvoiceLines->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//       $_btblInvoiceLines->fQuantityLineTaxAmount =$tlineamttax; 
//       $_btblInvoiceLines->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//       $_btblInvoiceLines->fQtyChangeLineTotIncl =$tlineamtincl; 
//       $_btblInvoiceLines->fQtyChangeLineTotExcl =$tlineamtexcl; 
//       $_btblInvoiceLines->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//       $_btblInvoiceLines->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//       $_btblInvoiceLines->fQtyChangeLineTaxAmount =$tlineamttax; 
//       $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//       $_btblInvoiceLines->fQtyToProcessLineTotIncl =$tlineamtincl; 
//       $_btblInvoiceLines->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//       $_btblInvoiceLines->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//       $_btblInvoiceLines->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//       $_btblInvoiceLines->fQtyToProcessLineTaxAmount =$tlineamttax; 
//       $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotIncl=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotExcl =0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDisc=0;
//       $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDisc=0;
//       $_btblInvoiceLines->fQtyLastProcessLineTaxAmount=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDisc=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotIncl=0;
//       $_btblInvoiceLines->fQtyProcessedLineTotExcl=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotInclNoDisc=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotExclNoDisc=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTaxAmount=0;
//       $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDisc=0; 
//       $_btblInvoiceLines->fUnitPriceExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines->fUnitPriceInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fUnitCostForeign=0;
//       $_btblInvoiceLines->fAddCostForeign=0;
//       $_btblInvoiceLines->fQuantityLineTotInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//       $_btblInvoiceLines->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//       $_btblInvoiceLines->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//       $_btblInvoiceLines->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//       $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//       $_btblInvoiceLines->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//       $_btblInvoiceLines->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//       $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotInclForeign=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotExclForeign=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTaxAmountForeign=0; 
//       $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotInclForeign=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotExclForeign=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotInclNoDiscForeign=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTotExclNoDiscForeign=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTaxAmountForeign=0; 
//       $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//       $_btblInvoiceLines->iLineRepID=$customer_details->repid; 
//       $_btblInvoiceLines->iLineProjectID=2; 
//       $_btblInvoiceLines->iLedgerAccountID=0; 
//       $_btblInvoiceLines->IModule=0;
//       $_btblInvoiceLines->bChargeCom=1;
//       $_btblInvoiceLines->bIsLotItem=0;
//       // $_btblInvoiceLines->iLotID=0;
//       // $_btblInvoiceLines->cLotNumber='';
//       // $_btblInvoiceLines->dLotExpiryDate=null;
//       $_btblInvoiceLines->iMFPID=0;
//       $_btblInvoiceLines->iLineID=1;
//       $_btblInvoiceLines->iLinkedLineID=0;
//       $_btblInvoiceLines->fQtyLinkedUsed=null;
//       $_btblInvoiceLines->fUnitPriceInclOrig=null;
//       $_btblInvoiceLines->fUnitPriceExclOrig=Null;
//       $_btblInvoiceLines->fUnitPriceInclForeignOrig=Null;
//       $_btblInvoiceLines->fUnitPriceExclForeignOrig=0;
//       $_btblInvoiceLines->iDeliveryMethodID=0;
//       $_btblInvoiceLines->fQtyDeliver=0;
//       $_btblInvoiceLines->dDeliveryDate=$today;
//       $_btblInvoiceLines->iDeliveryStatus=0;
//       $_btblInvoiceLines->fQtyForDelivery=0;
//       $_btblInvoiceLines->bPromotionApplied=0;
//       $_btblInvoiceLines->fPromotionPriceExcl=0;
//       $_btblInvoiceLines->fPromotionPriceIncl=0;
//       $_btblInvoiceLines->cPromotionCode=0;
//       $_btblInvoiceLines->iSOLinkedPOLineID=0;
//       $_btblInvoiceLines->fLength=0;
//       $_btblInvoiceLines->fWidth=0;
//       $_btblInvoiceLines->fHeight=0;
//       $_btblInvoiceLines->iPieces=0;
//       $_btblInvoiceLines->iPiecesToProcess=0;
//       $_btblInvoiceLines->iPiecesLastProcess=0;
//       $_btblInvoiceLines->iPiecesProcessed=0;
//       $_btblInvoiceLines->iPiecesReserved=0;
//       $_btblInvoiceLines->iPiecesDeliver=0;
//       $_btblInvoiceLines->iPiecesForDelivery=0;
//       $_btblInvoiceLines->fQuantityUR=1;
//       $_btblInvoiceLines->fQtyChangeUR=1;
//       $_btblInvoiceLines->fQtyToProcessUR=1;
//       $_btblInvoiceLines->fQtyLastProcessUR=0;
//       $_btblInvoiceLines->fQtyProcessedUR=0;
//       $_btblInvoiceLines->fQtyReservedUR=0;
//       $_btblInvoiceLines->fQtyReservedChangeUR=0;
//       $_btblInvoiceLines->fQtyDeliverUR=0;
//       $_btblInvoiceLines->fQtyForDeliveryUR=0;
//       $_btblInvoiceLines->fQtyLinkedUsedUR=0;
//       $_btblInvoiceLines->iPiecesLinkedUsed=0;
//       $_btblInvoiceLines->iSalesWhseID=0;
//       $_btblInvoiceLines->_btblInvoiceLines_iBranchID=1;
//       $_btblInvoiceLines->udIDSOrdTxCMReadingDate=$today;
//       $_btblInvoiceLines->uiIDSOrdTxCMPrevReading=$t_mon_pmr;
//       $_btblInvoiceLines->uiIDSOrdTxCMCurrReading=$t_mono_cmr;
//       $_btblInvoiceLines->ucIDSOrdTxCMMinVol=$min_mono_vol;
//       $_btblInvoiceLines->ucIDSOrdTxCMRates=$mono_rate;
//       $_btblInvoiceLines->ucIDSOrdTxCMServiceAsset=$mono_consolidated->ucSABillingAsset;
//       $_btblInvoiceLines->ucIDSOrdTxCMMeterType="BILLMON";

//      $_btblInvoiceLines->save();
//       }
//     }


//      // calculation color consolidated

     
//      $asset_id_color = DB::table('_smtblServiceAsset As sa')
//      ->where('cCode',$value['ucSABillingAsset'])
//      ->select('AutoIdx')
//      ->first();
     

//      $color_counter_elapse =DB::table('_smtblcounterelapsed')
//      ->where('iServiceAssetId',$asset_id_color->AutoIdx ?? '')
//      ->where('cCode','BILLCOL')
//      ->select('AutoIdx')
//      ->first();  
     

//      if(!empty($color_counter_elapse)){
//        $billing_color_consolidated = DB::table('_bvARAccountsFull As cl')
//      ->where('DCLink',$customer_details->dclink)
//      ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//      ->whereYear('ReadingDate', '=', $today->year)
//      ->whereMonth('ReadingDate', '=', $today->month)

//      ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
//      DB::raw('sum(cmr.ColCMR) as color_cmr_total'),
//      DB::raw('sum(cmr.ColPMR) as color_pmr_total'),
//      DB::raw('sum(cmr.ColCMR - cmr.ColPMR) as colunit'),   
//      DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.ucSABillingAsset'
        
//       )
//      ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//      ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//      ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
//      ->get();

     
     
//      foreach($billing_color_consolidated as $data){
//        $asset_id = DB::table('_smtblServiceAsset As sa')
//        ->where('cCode',$data->ucSABillingAsset)
//        ->select('AutoIdx')
//        ->first();            
     

//        // color consolidated calculation

//         //color
//       $color_counter_elapse =DB::table('_smtblcounterelapsed')
//       ->where('iServiceAssetId',$asset_id->AutoIdx)
//       ->where('cCode','BILLCOL')
//       ->select('AutoIdx')
//       ->first();

//       $min_vol_color = _smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//        ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//        ->groupBy('fRate',DB::raw('(iToqty)'))
//        ->first();

//      if(!empty($min_vol_color) && ($min_vol_color->fRate ?? 0) > 0)
//      {
   

//        if($min_vol_color->min_vol >= 1000000){

//        $total_color =$min_vol_color->fRate * $data->colunit;
//        $rate_frate =number_format($min_vol_color->fRate,3);

//        $data->total_color = $total_color;
      
//        $data->comb_rates_color =$rate_frate;   


//        }
//        else{                   

//        $diff_color = ($data->colunit -$min_vol_color->min_vol);
       

//        if($diff_color > 0){
//        $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//        ->where('iToqty',$min_vol_color->min_vol)
//        ->select('frate')
//        ->first();
//         $total_min_color =($rate_min_color->frate * $min_vol_color->min_vol);

//        $rate =number_format($rate_min_color->frate,3);

//        $diff_remaining_color = ($data->colunit -$min_vol_color->min_vol);

//        // we increase minimum to move to the next slab 

//        //slab two starts
//        $Increment_1 =$min_vol_color->min_vol +1;  

//         //we get the rate for the next slab and their readings
//         $next_slab_col =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//         ->where('iFromQty','<=',$Increment_1)
//        ->where('iToqty','>=',$Increment_1)
//        ->select('iFromQty','iToqty','fRate')
//        ->first();

//         //  we get the difference of this slab
//       $diff_slab_color =($next_slab_col->iToqty - $next_slab_col->iFromQty)+1;

//       //  we check if the remainder fulls fall in this slab or exceed
//          $diff_bal_slab =$diff_remaining_color -$diff_slab_color;

//        //   value fully fits here


//        if($diff_bal_slab < 0){
//        $total_slab_2_col =($diff_remaining_color * $next_slab_col->fRate );

//        $rate_2 =number_format($next_slab_col->fRate,3);         

       
//        // two slabs used 
       
//        $comb_rates_color =$rate.' | '.$rate_2;
//        $total_color =($total_min_color + $total_slab_2_col);

//        $data->comb_rates_color = $comb_rates_color;
//        $data->total_color = $total_color;



//        }

//        // slab three
//        if($diff_bal_slab > 0){
//        $total_slab_2_col =($diff_slab_color * $next_slab_col->fRate );  
       
//        $rate_2 =number_format($next_slab_col->fRate,3); 

//        $diff_remaining2_color = ($diff_bal_slab - $diff_slab_color);

//        // we increase minimum to move to the next slab 


//        //slab two starts
//        $Increment_2 =$next_slab_col->iToqty +1;  

//         // we get the slab values rates and quantity
//         $rate_slab3_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//         ->where('iFromQty','<=',$increment2)
//        ->where('iToqty','>=',$increment2)
//        ->select('iFromQty','iToqty','fRate')
//        ->first();

//        // we get slab difference
//        $slab_3_diff =($rate_slab3_color->iToqty - $rate_slab3_color->iFromQty) +1;

//        // we check if balance is still bigger or less

//        $diff_remaining3_color =$diff_remaining2_color -  $slab_3_diff;

//        if($diff_remaining3_color < 0){

//        $total_slab_3_col =($diff_remaining2_color * $rate_slab3_color->fRate );

//        $rate_3 =number_format($rate_slab3_color->fRate,3); 

//         // three slabs used 
       
//         $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3;
//         $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col);

//         $data->comb_rates_color = $comb_rates_color;
//         $data->total_color = $total_color;


//        }
//       if($diff_remaining3_color > 0){

//        $total_slab_3_col =($slab_3_diff * $rate_slab3_color->fRate );

//        $rate_3 =number_format($rate_slab3_color->fRate,3); 

//        // increase to move to the next slab

//        $Increment3 =$rate_slab3_color->iToqty +1;  

//        $rate_slab4_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$increment3)
//        ->where('iToqty','>=',$increment3)
//        ->select('iFromQty','iToqty','fRate')
//        ->first();

//        // we get the difference of the slab

//        $diff_slab4 =($rate_slab4_color->iToqty - $rate_slab4_color->iFromQty)+1;


//        // we check if balance is still bigger or less
//        $diff_remaining4_color =$diff_remaining3_color - $slab_3_diff ;

//        // we check if balance fits in this slab

//        if($diff_remaining4_color < 0){

//        $total_slab_4_col =($diff_remaining3_color * $rate_slab4_color->fRate );

//        $rate_4 =number_format($rate_slab4_color->fRate,3); 
       
//         // four slabs used 
       
//         $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3. '|'. $rate_4;
//         $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col+$total_slab_4_col);

//         $data->comb_rates_color = $comb_rates_color;
//         $data->total_color = $total_color;


//        }

//       }

//    }

// }


//        else{

//        $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//        ->where('iToqty',$min_vol_color->min_vol)
//        ->select('frate')
//        ->first();
//        $total_color =($rate_min_color->frate * $min_vol_color->min_vol);
//        $rate_color =number_format($rate_min_color->frate,3);

//        $data->comb_rates_color = $rate_color;
//        $data->total_color = $total_color;       

       



//        }

//    }
// }


// $min_color_vol = _smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
// ->select(DB::raw('min(iToqty) as min_color_vol'))
// ->first();

// if($min_color_vol->min_color_vol >=1000000){
//  $data->min_color_vol = null;
 
// }
// else{
//  $data->min_color_vol =$min_color_vol->min_color_vol ?? null;
// }      

        

//   }        
   
//     $bill_color_con =$billing_color_consolidated[0] ?? '';
//     $color_consolidated =(object) $bill_color_con;
    

//     // if(!empty($color_counter_elapse)){
//       if(!empty($min_vol_color)  && ($min_vol_color->fRate ?? 0 )  > 0)
//       {
    
   


//    $t_color_cmr = $color_consolidated->color_cmr_total;
//    $t_color_pmr = $color_consolidated->color_pmr_total;
//    $tunits = $color_consolidated-> colunit;
//    $color_rate =$color_consolidated->comb_rates_color ?? 0;
//    $min_color_vol =$color_consolidated->min_color_vol;
//    $tbillmeter = "BILLCOL";
//    $treadingdate = "1900-01-01";
//    $total_col_con =$color_consolidated->total_color ?? 0;


  
//      $total_color_cons =$total_col_con;
//      if($tax_rate->TaxRate > 0){
//        if($customer_details->icurrencyid ==0){
//          $tlineamtexcl = $total_color_cons;
//          $tlineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
//          $tlineamtincl =($total_color_cons * (1 + $tax_rate->TaxRate / 100));
//          $tfclineamtexcl = 0;
//          $tfclineamttax = 0;
//          $tfclineamtincl = 0;
//        }
//        else{
//          $tfclineamtexcl = $total_color_cons;
//          $tfclineamttax = ($total_color_cons * ($tax_rate->TaxRate / 100));
//          $tfclineamtincl = ($total_color_cons * (1 + $tax_rate->TaxRate / 100));
//          $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//          $tlineamttax = $tfclineamttax * $exc_rate;
//          $tlineamtincl = $tfclineamtincl * $exc_rate;
//        }
//      }
//      else{
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_color_cons;
//         $tlineamttax =  0;
//         $tlineamtincl = $total_color_cons;
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_color_cons;
//         $tfclineamttax = 0;
//         $tfclineamtincl = $total_color_cons;
//         $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tlineamttax = 0;
//         $tlineamtincl = $tfclineamtincl * $exc_rate;
//       }

//      }
   
     

//      $tstockid = 17987;
//      $tlinenote ="CMR: $t_color_cmr\r\nPMR: $t_color_pmr\r\nCOPIES DONE: $tunits\r\nMIN. VOL: $min_color_vol\r\nRATE: $color_rate";
//      $tlinedesc = "Color Billing for $month $today->year";

//       $_btblInvoiceLines_color = new _btblInvoiceLines;
//       $_btblInvoiceLines_color->iInvoiceID =$invoice_id->autoindex;
//       $_btblInvoiceLines_color->iOrigLineID =0;
//       $_btblInvoiceLines_color->iGrvLineID =0;
//       $_btblInvoiceLines_color->iLineDocketMode =0; 
//       $_btblInvoiceLines_color->cDescription =$tlinedesc; 
//       $_btblInvoiceLines_color->iUnitsOfMeasureStockingID=0; 
//       $_btblInvoiceLines_color->iUnitsOfMeasureCategoryID=0;
//       $_btblInvoiceLines_color->iUnitsOfMeasureID=0;
//       $_btblInvoiceLines_color->fQuantity=1;
//       $_btblInvoiceLines_color->fQtyChange=1;
//       $_btblInvoiceLines_color->fQtyToProcess=1; 
//       $_btblInvoiceLines_color->fQtyLastProcess=0; 
//       $_btblInvoiceLines_color->fQtyProcessed =0; 
//       $_btblInvoiceLines_color->fQtyReserved=0; 
//       $_btblInvoiceLines_color->fQtyReservedChange =0;
//       $_btblInvoiceLines_color->cLineNotes=$tlinenote; 
//       $_btblInvoiceLines_color->fUnitPriceExcl=$tlineamtexcl; 
//       $_btblInvoiceLines_color->fUnitPriceIncl=$tlineamtincl;
//       $_btblInvoiceLines_color->iUnitPriceOverrideReasonID=0; 
//       $_btblInvoiceLines_color->fUnitCost=0;
//       $_btblInvoiceLines_color->fLineDiscount=0; 
//       $_btblInvoiceLines_color->iLineDiscountReasonID=0;
//       $_btblInvoiceLines_color->iReturnReasonID=0; 
//       $_btblInvoiceLines_color->fTaxRate=$tax_rate->TaxRate; 
//       $_btblInvoiceLines_color->bIsSerialItem=0; 
//       $_btblInvoiceLines_color->bIsWhseItem=1;
//       $_btblInvoiceLines_color->fAddCost=0; 
//       $_btblInvoiceLines_color->cTradeinItem='';
//       $_btblInvoiceLines_color->iStockCodeID=$tstockid; 
//       $_btblInvoiceLines_color->iJobID=0;
//       $_btblInvoiceLines_color->iWarehouseID=6;
//       $_btblInvoiceLines_color->iTaxTypeID=$customer_details->ideftaxtypeid;
//       $_btblInvoiceLines_color->iPriceListNameID=1;
//       $_btblInvoiceLines_color->fQuantityLineTotIncl=$tlineamtincl;
//       $_btblInvoiceLines_color->fQuantityLineTotExcl=$tlineamtexcl;
//       $_btblInvoiceLines_color->fQuantityLineTotInclNoDisc=$tlineamtincl;
//       $_btblInvoiceLines_color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//       $_btblInvoiceLines_color->fQuantityLineTaxAmount =$tlineamttax; 
//       $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotIncl =$tlineamtincl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotExcl =$tlineamtexcl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTaxAmount =$tlineamttax; 
//       $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotIncl =$tlineamtincl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTaxAmount =$tlineamttax; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotIncl=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotExcl =0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDisc=0;
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDisc=0;
//       $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmount=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDisc=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotIncl=0;
//       $_btblInvoiceLines_color->fQtyProcessedLineTotExcl=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDisc=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDisc=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTaxAmount=0;
//       $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDisc=0; 
//       $_btblInvoiceLines_color->fUnitPriceExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines_color->fUnitPriceInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fUnitCostForeign=0;
//       $_btblInvoiceLines_color->fAddCostForeign=0;
//       $_btblInvoiceLines_color->fQuantityLineTotInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines_color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//       $_btblInvoiceLines_color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//       $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines_color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//       $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//       $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotInclForeign=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotExclForeign=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountForeign=0; 
//       $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotInclForeign=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotExclForeign=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDiscForeign=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDiscForeign=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountForeign=0; 
//       $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//       $_btblInvoiceLines_color->iLineRepID=$customer_details->repid; 
//       $_btblInvoiceLines_color->iLineProjectID=2; 
//       $_btblInvoiceLines_color->iLedgerAccountID=0; 
//       $_btblInvoiceLines_color->IModule=0;
//       $_btblInvoiceLines_color->bChargeCom=1;
//       $_btblInvoiceLines_color->bIsLotItem=0;
//       // $_btblInvoiceLines_color->iLotID=0;
//       // $_btblInvoiceLines_color->cLotNumber='';
//       // $_btblInvoiceLines_color->dLotExpiryDate=null;
//       $_btblInvoiceLines_color->iMFPID=0;
//       $_btblInvoiceLines_color->iLineID=1;
//       $_btblInvoiceLines_color->iLinkedLineID=0;
//       $_btblInvoiceLines_color->fQtyLinkedUsed=null;
//       $_btblInvoiceLines_color->fUnitPriceInclOrig=null;
//       $_btblInvoiceLines_color->fUnitPriceExclOrig=Null;
//       $_btblInvoiceLines_color->fUnitPriceInclForeignOrig=Null;
//       $_btblInvoiceLines_color->fUnitPriceExclForeignOrig=0;
//       $_btblInvoiceLines_color->iDeliveryMethodID=0;
//       $_btblInvoiceLines_color->fQtyDeliver=0;
//       $_btblInvoiceLines_color->dDeliveryDate=$today;
//       $_btblInvoiceLines_color->iDeliveryStatus=0;
//       $_btblInvoiceLines_color->fQtyForDelivery=0;
//       $_btblInvoiceLines_color->bPromotionApplied=0;
//       $_btblInvoiceLines_color->fPromotionPriceExcl=0;
//       $_btblInvoiceLines_color->fPromotionPriceIncl=0;
//       $_btblInvoiceLines_color->cPromotionCode=0;
//       $_btblInvoiceLines_color->iSOLinkedPOLineID=0;
//       $_btblInvoiceLines_color->fLength=0;
//       $_btblInvoiceLines_color->fWidth=0;
//       $_btblInvoiceLines_color->fHeight=0;
//       $_btblInvoiceLines_color->iPieces=0;
//       $_btblInvoiceLines_color->iPiecesToProcess=0;
//       $_btblInvoiceLines_color->iPiecesLastProcess=0;
//       $_btblInvoiceLines_color->iPiecesProcessed=0;
//       $_btblInvoiceLines_color->iPiecesReserved=0;
//       $_btblInvoiceLines_color->iPiecesDeliver=0;
//       $_btblInvoiceLines_color->iPiecesForDelivery=0;
//       $_btblInvoiceLines_color->fQuantityUR=1;
//       $_btblInvoiceLines_color->fQtyChangeUR=1;
//       $_btblInvoiceLines_color->fQtyToProcessUR=1;
//       $_btblInvoiceLines_color->fQtyLastProcessUR=0;
//       $_btblInvoiceLines_color->fQtyProcessedUR=0;
//       $_btblInvoiceLines_color->fQtyReservedUR=0;
//       $_btblInvoiceLines_color->fQtyReservedChangeUR=0;
//       $_btblInvoiceLines_color->fQtyDeliverUR=0;
//       $_btblInvoiceLines_color->fQtyForDeliveryUR=0;
//       $_btblInvoiceLines_color->fQtyLinkedUsedUR=0;
//       $_btblInvoiceLines_color->iPiecesLinkedUsed=0;
//       $_btblInvoiceLines_color->iSalesWhseID=0;
//       $_btblInvoiceLines_color->_btblInvoiceLines_iBranchID=1;
//       $_btblInvoiceLines_color->udIDSOrdTxCMReadingDate=$today;
//       $_btblInvoiceLines_color->uiIDSOrdTxCMPrevReading=$t_color_pmr;
//       $_btblInvoiceLines_color->uiIDSOrdTxCMCurrReading=$t_color_cmr;
//       $_btblInvoiceLines_color->ucIDSOrdTxCMMinVol=$min_color_vol;
//       $_btblInvoiceLines_color->ucIDSOrdTxCMRates=$color_rate;
//       $_btblInvoiceLines_color->ucIDSOrdTxCMServiceAsset=$color_consolidated->ucSABillingAsset;
//       $_btblInvoiceLines_color->ucIDSOrdTxCMMeterType="BILLCOL";
//       $_btblInvoiceLines_color->save();


//      }
//     }


//     //  SCAN BILLING SEPARATE  CONSOLIDATED

//       $asset_id_scan = DB::table('_smtblServiceAsset As sa')
//      ->where('cCode',$value['ucSABillingAsset'])
//      ->select('AutoIdx')
//      ->first();
  

//      $scan_counter_elapse =DB::table('_smtblcounterelapsed')
//      ->where('iServiceAssetId',$asset_id_color->AutoIdx ?? '')
//      ->where('cCode','BILLSCN')
//      ->select('AutoIdx')
//      ->first();

//      if(!empty($scan_counter_elapse)){
//       $billing_scan_consolidated = DB::table('_bvARAccountsFull As cl')
//       ->where('DCLink',$customer_details->dclink)
//       ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//       ->whereYear('ReadingDate', '=', $today->year)
//       ->whereMonth('ReadingDate', '=', $today->month)

//       ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
//       DB::raw('sum(cmr.scncmr - cmr.scnpmr) as scnunit'),
//       DB::raw('sum(cmr.scncmr) as tscancmr'),
//       DB::raw('sum(cmr.scnpmr) as tscanpmr')        
//        )
//       ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//       ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//       ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
//       ->get();

        
//         foreach($billing_scan_consolidated as $data){
//           $asset_id = DB::table('_smtblServiceAsset As sa')
//           ->where('cCode',$data->ucSABillingAsset)
//           ->select('AutoIdx')
//           ->first();            
        

       
//          $scan_counter_elapse =DB::table('_smtblcounterelapsed')
//          ->where('iServiceAssetId',$asset_id->AutoIdx)
//          ->where('cCode','BILLSCN')
//          ->select('AutoIdx')
//          ->first();


//          $scan_min_vol = _smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx ?? '')
//          ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//          ->groupBy('fRate',DB::raw('(iToqty)'))
//          ->first();

//          if(!empty($scan_min_vol) && $scan_min_vol->fRate > 0)
//          {
 
//          if($scan_min_vol->min_vol >=1000000){
//              $data->scan_min_vol = null;
//             }
//          else{
//              $data->scan_min_vol =$scan_min_vol->min_vol ?? null;
//          }
//          if( $scan_min_vol->min_vol  >=1000000 ){
 
//              $total_frate_scan =($scan_min_vol->fRate * $data->scnunit);
//              $rate_frate_scan =number_format($scan_min_vol->fRate,3);
 
           
 
//              $data->comb_rates_scan = $rate_frate_scan;
//              $data->total_scan = $total_frate_scan;            
 
 
//          }
 
//          else{
//              $diff_scan = ($data->scnunit - $scan_min_vol->min_vol);
//              if($diff_scan > 0){
//                  $rate_min_scan =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                  ->where('iToqty',$scan_min_vol->min_vol)
//                  ->select('frate')
//                  ->first();
//                  $total_min_scan =($rate_min_scan->frate * $scan_min_vol->min_vol);
 
//                  $rate_scan =number_format($rate_min_scan->frate,3);
                 
//                  //slab two starts
//                  $Increment_1_sc =$scan_min_vol->min_vol +1; 
 
         
//              // we get the rate for the next slab and their readings
//              $next_slab_sn =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//              ->where('iFromQty','<=',$Increment_1_sc)
//              ->where('iToqty','>=',$Increment_1_sc)
//              ->select('iFromQty','iToqty','fRate')
//              ->first();
        
//                  //  we get the difference of this slab
//              $diff_slab1_sn =($next_slab_sn->iToqty - $next_slab_sn->iFromQty)+1;
 
 
//              //  we check if the remainder fulls fall in this slab or exceed
//              $diff_bil_slab_sn =$diff_scan -$diff_slab1_sn;           
 
//             //counts fits fully in this slab the difference is negative
//              if($diff_bil_slab_sn < 0){
//                  $total_slab_1_sn =($diff_scan * $next_slab_sn->fRate );
 
//                  $rate_2_scan =number_format($next_slab_sn->fRate,3);            
 
//              // two slabs used             
//              $comb_rates_scan =$rate_scan.' | '.$rate_2_scan;
//              $total_scan =($total_slab_1_sn +$total_min_scan);
 
//              $data->comb_rates_scan = $comb_rates_scan;
//              $data->total_scan = $total_scan;          
         
//          }
//          }
 
//          else{
//              $rate_min_scan =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                  ->where('iToqty',$scan_min_vol->min_vol)
//                  ->select('frate')
//                  ->first();
//              $total_min_scan =($rate_min_scan->frate * $scan_min_vol->min_vol);
//              $rate_scan =number_format($rate_min_scan->frate,3);
 
//              $data->comb_rates_scan = $rate_scan;
//              $data->total_scan = $total_min_scan;  
 
//          }
//          }
//         }
//       }

         
         
//       $bill_scan_con =$billing_scan_consolidated[0] ?? '';
//       $scan_con =(object) $bill_scan_con;

//       $t_scan_cmr = $scan_con->tscancmr;
//       $t_scan_pmr = $scan_con->tscanpmr;
//       $tscunits = $scan_con-> scnunit;
//       $scan_rate =$scan_con->comb_rates_scan;
//       $min_scan_vol =$scan_con->scan_min_vol;
//       $tbillmeter = "BILLSCN";
//       $treadingdate = "1900-01-01";
//       $total_scan_con =$scan_con->total_scan;

//       if($tax_rate->TaxRate > 0){
//         if($customer_details->icurrencyid ==0){
//           $tlineamtexcl = $total_scan_con;
//           $tlineamttax = ($total_scan_con * ($tax_rate->TaxRate / 100));
//           $tlineamtincl =($total_scan_con * (1 + $tax_rate->TaxRate / 100));
//           $tfclineamtexcl = 0;
//           $tfclineamttax = 0;
//           $tfclineamtincl = 0;
//         }
//         else{
//           $tfclineamtexcl = $total_scan_con;
//           $tfclineamttax = ($total_scan_con * ($tax_rate->TaxRate / 100));
//           $tfclineamtincl = ($total_scan_con * (1 + $tax_rate->TaxRate / 100));
//           $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//           $tlineamttax = $tfclineamttax * $exc_rate;
//           $tlineamtincl = $tfclineamtincl * $exc_rate;
//         }
//       }
//       else{
//         if($customer_details->icurrencyid ==0){
//           $tlineamtexcl = $total_scan_con;
//           $tlineamttax = 0;
//           $tlineamtincl =$total_scan_con ;
//           $tfclineamtexcl = 0;
//           $tfclineamttax = 0;
//           $tfclineamtincl = 0;
//         }
//         else{
//           $tfclineamtexcl = $total_scan_con;
//           $tfclineamttax = 0;
//           $tfclineamtincl = $total_scan_con;
//           $tlineamtexcl = $tfclineamtexcl * $exc_rate;
//           $tlineamttax = 0;
//           $tlineamtincl = $tfclineamtincl * $exc_rate;
//         }


//       }

//       $tstockid = 17987;
//       $tlinenote ="CMR: $t_scan_cmr\r\nPMR: $t_scan_pmr\r\nCOPIES DONE: $tscunits\r\nMIN. VOL: $min_scan_vol\r\nRATE: $scan_rate";
//       $tlinedesc = "SCAN Billing for $month $today->year";

//          $_btblInvoiceLines_scan = new _btblInvoiceLines;
//          $_btblInvoiceLines_scan->iInvoiceID =$invoice_id->autoindex;
//          $_btblInvoiceLines_scan->iOrigLineID =0;
//          $_btblInvoiceLines_scan->iGrvLineID =0;
//          $_btblInvoiceLines_scan->iLineDocketMode =0; 
//          $_btblInvoiceLines_scan->cDescription =$tlinedesc; 
//          $_btblInvoiceLines_scan->iUnitsOfMeasureStockingID=0; 
//          $_btblInvoiceLines_scan->iUnitsOfMeasureCategoryID=0;
//          $_btblInvoiceLines_scan->iUnitsOfMeasureID=0;
//          $_btblInvoiceLines_scan->fQuantity=1;
//          $_btblInvoiceLines_scan->fQtyChange=1;
//          $_btblInvoiceLines_scan->fQtyToProcess=1; 
//          $_btblInvoiceLines_scan->fQtyLastProcess=0; 
//          $_btblInvoiceLines_scan->fQtyProcessed =0; 
//          $_btblInvoiceLines_scan->fQtyReserved=0; 
//          $_btblInvoiceLines_scan->fQtyReservedChange =0;
//          $_btblInvoiceLines_scan->cLineNotes=$tlinenote; 
//          $_btblInvoiceLines_scan->fUnitPriceExcl=$tlineamtexcl; 
//          $_btblInvoiceLines_scan->fUnitPriceIncl=$tlineamtincl;
//          $_btblInvoiceLines_scan->iUnitPriceOverrideReasonID=0; 
//          $_btblInvoiceLines_scan->fUnitCost=0;
//          $_btblInvoiceLines_scan->fLineDiscount=0; 
//          $_btblInvoiceLines_scan->iLineDiscountReasonID=0;
//          $_btblInvoiceLines_scan->iReturnReasonID=0; 
//          $_btblInvoiceLines_scan->fTaxRate=$tax_rate->TaxRate; 
//          $_btblInvoiceLines_scan->bIsSerialItem=0; 
//          $_btblInvoiceLines_scan->bIsWhseItem=1;
//          $_btblInvoiceLines_scan->fAddCost=0; 
//          $_btblInvoiceLines_scan->cTradeinItem='';
//          $_btblInvoiceLines_scan->iStockCodeID=$tstockid; 
//          $_btblInvoiceLines_scan->iJobID=0;
//          $_btblInvoiceLines_scan->iWarehouseID=6;
//          $_btblInvoiceLines_scan->iTaxTypeID=$customer_details->ideftaxtypeid;
//          $_btblInvoiceLines_scan->iPriceListNameID=1;
//          $_btblInvoiceLines_scan->fQuantityLineTotIncl=$tlineamtincl;
//          $_btblInvoiceLines_scan->fQuantityLineTotExcl=$tlineamtexcl;
//          $_btblInvoiceLines_scan->fQuantityLineTotInclNoDisc=$tlineamtincl;
//          $_btblInvoiceLines_scan->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//          $_btblInvoiceLines_scan->fQuantityLineTaxAmount =$tlineamttax; 
//          $_btblInvoiceLines_scan->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotIncl =$tlineamtincl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotExcl =$tlineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTaxAmount =$tlineamttax; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotIncl =$tlineamtincl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmount =$tlineamttax; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotIncl=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotExcl =0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclNoDisc=0;
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclNoDisc=0;
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmount=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountNoDisc=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotIncl=0;
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotExcl=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotInclNoDisc=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotExclNoDisc=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmount=0;
//          $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountNoDisc=0; 
//          $_btblInvoiceLines_scan->fUnitPriceExclForeign=$tfclineamtexcl; 
//          $_btblInvoiceLines_scan->fUnitPriceInclForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fUnitCostForeign=0;
//          $_btblInvoiceLines_scan->fAddCostForeign=0;
//          $_btblInvoiceLines_scan->fQuantityLineTotInclForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//          $_btblInvoiceLines_scan->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//          $_btblInvoiceLines_scan->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//          $_btblInvoiceLines_scan->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//          $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//          $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclForeign=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclForeign=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountForeign=0; 
//          $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotInclForeign=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotExclForeign=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTotExclNoDiscForeign=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountForeign=0; 
//          $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_scan->iLineRepID=$customer_details->repid; 
//          $_btblInvoiceLines_scan->iLineProjectID=2; 
//          $_btblInvoiceLines_scan->iLedgerAccountID=0; 
//          $_btblInvoiceLines_scan->IModule=0;
//          $_btblInvoiceLines_scan->bChargeCom=1;
//          $_btblInvoiceLines_scan->bIsLotItem=0;
//         //  $_btblInvoiceLines_scan->iLotID=0;
//         //  $_btblInvoiceLines_scan->cLotNumber='';
//         //  $_btblInvoiceLines_scan->dLotExpiryDate=null;
//          $_btblInvoiceLines_scan->iMFPID=0;
//          $_btblInvoiceLines_scan->iLineID=1;
//          $_btblInvoiceLines_scan->iLinkedLineID=0;
//          $_btblInvoiceLines_scan->fQtyLinkedUsed=null;
//          $_btblInvoiceLines_scan->fUnitPriceInclOrig=null;
//          $_btblInvoiceLines_scan->fUnitPriceExclOrig=Null;
//          $_btblInvoiceLines_scan->fUnitPriceInclForeignOrig=Null;
//          $_btblInvoiceLines_scan->fUnitPriceExclForeignOrig=0;
//          $_btblInvoiceLines_scan->iDeliveryMethodID=0;
//          $_btblInvoiceLines_scan->fQtyDeliver=0;
//          $_btblInvoiceLines_scan->dDeliveryDate=$today;
//          $_btblInvoiceLines_scan->iDeliveryStatus=0;
//          $_btblInvoiceLines_scan->fQtyForDelivery=0;
//          $_btblInvoiceLines_scan->bPromotionApplied=0;
//          $_btblInvoiceLines_scan->fPromotionPriceExcl=0;
//          $_btblInvoiceLines_scan->fPromotionPriceIncl=0;
//          $_btblInvoiceLines_scan->cPromotionCode=0;
//          $_btblInvoiceLines_scan->iSOLinkedPOLineID=0;
//          $_btblInvoiceLines_scan->fLength=0;
//          $_btblInvoiceLines_scan->fWidth=0;
//          $_btblInvoiceLines_scan->fHeight=0;
//          $_btblInvoiceLines_scan->iPieces=0;
//          $_btblInvoiceLines_scan->iPiecesToProcess=0;
//          $_btblInvoiceLines_scan->iPiecesLastProcess=0;
//          $_btblInvoiceLines_scan->iPiecesProcessed=0;
//          $_btblInvoiceLines_scan->iPiecesReserved=0;
//          $_btblInvoiceLines_scan->iPiecesDeliver=0;
//          $_btblInvoiceLines_scan->iPiecesForDelivery=0;
//          $_btblInvoiceLines_scan->fQuantityUR=1;
//          $_btblInvoiceLines_scan->fQtyChangeUR=1;
//          $_btblInvoiceLines_scan->fQtyToProcessUR=1;
//          $_btblInvoiceLines_scan->fQtyLastProcessUR=0;
//          $_btblInvoiceLines_scan->fQtyProcessedUR=0;
//          $_btblInvoiceLines_scan->fQtyReservedUR=0;
//          $_btblInvoiceLines_scan->fQtyReservedChangeUR=0;
//          $_btblInvoiceLines_scan->fQtyDeliverUR=0;
//          $_btblInvoiceLines_scan->fQtyForDeliveryUR=0;
//          $_btblInvoiceLines_scan->fQtyLinkedUsedUR=0;
//          $_btblInvoiceLines_scan->iPiecesLinkedUsed=0;
//          $_btblInvoiceLines_scan->iSalesWhseID=0;
//          $_btblInvoiceLines_scan->_btblInvoiceLines_iBranchID=1;
//          $_btblInvoiceLines_scan->udIDSOrdTxCMReadingDate=$today;
//          $_btblInvoiceLines_scan->uiIDSOrdTxCMPrevReading=$t_scan_pmr;
//          $_btblInvoiceLines_scan->uiIDSOrdTxCMCurrReading=$t_scan_cmr;
//          $_btblInvoiceLines_scan->ucIDSOrdTxCMMinVol=$min_scan_vol;
//          $_btblInvoiceLines_scan->ucIDSOrdTxCMRates=$scan_rate;
//          $_btblInvoiceLines_scan->ucIDSOrdTxCMServiceAsset=$scan_con->ucSABillingAsset;
//          $_btblInvoiceLines_scan->ucIDSOrdTxCMMeterType="BILLSCN";

//          $_btblInvoiceLines_scan->save();
//     }
  

//          // A3 MONO CONSOLIDATED SEPARATE

//          $asset_id = DB::table('_smtblServiceAsset As sa')
//          ->where('cCode',$value['ucSABillingAsset'])
//          ->select('AutoIdx')
//          ->first();
 
//           //A3 MONO
//           $a3Mono_counter_elapse =DB::table('_smtblcounterelapsed')
//           ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
//           ->where('cCode','A3BILLMON')
//           ->select('AutoIdx')
//           ->first();
   
            
               
//                if(!empty($a3Mono_counter_elapse))
//                {
//              $billing_a3mono_consolidated = DB::table('_bvARAccountsFull As cl')
//              ->where('DCLink',$customer_details->dclink)
//              ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//              ->whereYear('ReadingDate', '=', $today->year)
//              ->whereMonth('ReadingDate', '=', $today->month)
   
//              ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
//              DB::raw('sum(cmr.A3MPMR) as a3mpmr'),
//              DB::raw('sum(cmr.A3MCMR) as a3mcmr'),
//              DB::raw('sum(cmr.A3MCMR - cmr.A3MPMR) as a3monunit'),   
//              'sa.ucSABillingAsset'
           
//             )
//              ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//              ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//              ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
//              ->get();
 
         
 
//            foreach($billing_a3mono_consolidated as $data){
//              $asset_id = DB::table('_smtblServiceAsset As sa')
//              ->where('cCode',$data->ucSABillingAsset)
//              ->select('AutoIdx')
//              ->first();            
           
 
//              // A3  Mono consolidated calculation
 
//               //A3 MONO
//               $a3Mono_counter_elapse =DB::table('_smtblcounterelapsed')
//               ->where('iServiceAssetId',$asset_id->AutoIdx)
//               ->where('cCode','A3BILLMON')
//               ->select('AutoIdx')
//               ->first();
 
              
//               $a3mono_min_vol = _smtblrateslab::where('iBillingID',$a3Mono_counter_elapse->AutoIdx ?? '')
//                ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//                ->groupBy('fRate',DB::raw('iToqty'))
//                ->first();
 
               
//            if(!empty($a3mono_min_vol) && ($a3mono_min_vol->fRate ?? 0) > 0)
//            {
         
  
//           if($a3mono_min_vol->min_vol >=1000000){
//               $data->a3mono_min_vol = null;
//              }
//           else{
//               $data->a3mono_min_vol =$a3mono_min_vol->min_vol ?? null;
//           }
//           if( $a3mono_min_vol->min_vol  >=1000000 ){
  
//               $total_frate_a3mono =($a3mono_min_vol->fRate * $data->a3monunit);
//               $rate_frate_a3mono =number_format($a3mono_min_vol->fRate,3);
            
  
//               $data->comb_rates_a3m = $rate_frate_a3mono;
//               $data->total_a3m = $total_frate_a3mono;            
  
  
//           }
//           else{
//            $diff_a3mon = ($data->a3munit - $A3min_vol->min_vol);
//            if($diff_a3mon > 0){
//                $rate_min_a3m =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//                ->where('iToqty',$A3min_vol->min_vol)
//                ->select('frate')
//                ->first();
//                $total_min_a3m =($rate_min_a3m->frate * $A3min_vol->min_vol);
 
//                $rate_a3m =$rate_min_a3m->frate;
               
//                //slab two starts
//                $Increment_1a3m =$A3min_vol->min_vol +1; 
 
       
//            // we get the rate for the next slab and their readings
//            $next_slab_a3m =_smtblrateslab::where('iBillingID',$A3counter_elapse->AutoIdx)
//            ->where('iFromQty','<=',$Increment_1a3m)
//            ->where('iToqty','>=',$Increment_1a3m)
//            ->select('iFromQty','iToqty','fRate')
//            ->first();
      
//                //  we get the difference of this slab
//            $diff_slab1_a3m =($next_slab_a3m->iToqty - $next_slab_a3m->iFromQty)+1;
 
 
//            //  we check if the remainder fulls fall in this slab or exceed
//            $diff_bil_slab_a3m =$diff_a3mon -$diff_slab1_a3m;           
 
//           //counts fits fully in this slab the difference is negative
//            if($diff_bil_slab_a3m < 0){
//                $total_slab_1_a3m =($diff_a3mon * $next_slab_a3m->fRate );
 
//                $rate_2_a3m =number_format($next_slab_a3m->fRate,3);            
 
//            // two slabs used             
//            $comb_rates_a3m =$rate_a3m.' | '.$rate_2_a3m;
//            $total_a3m =($total_slab_1_a3m +$total_min_a3m);
 
//            $data->comb_rates_a3m = $comb_rates_a3m;
//            $data->total_a3m = $total_a3m;          
       
//        }
//        }
 
//        else{
//            $rate_min_a3m =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//                ->where('iToqty',$A3min_vol->min_vol)
//                ->select('frate')
//                ->first();
//            $total_min_a3m =($rate_min_a3m->frate * $A3min_vol->min_vol);
//            $rate_a3m =number_format($rate_min_a3m->frate,3);
 
//            $data->comb_rates_a3m = $rate_a3m;
//            $data->total_a3m = $total_min_a3m;  
 
//        }
//        }
     
   
       
       
//        $bill_a3mono_con =$billing_a3mono_consolidated[0] ?? '';
//        $a3mono_consolidated =(object) $bill_a3mono_con;
 
       
//       $t_a3m_cmr = $a3mono_consolidated->a3mcmr;
//       $t_a3m_pmr = $a3mono_consolidated->a3mpmr;
//       $tA3munits = $a3mono_consolidated-> a3monunit;
//       $A3m_rate =$a3mono_consolidated->comb_rates_a3m;
//       $min_A3m_vol =$a3mono_consolidated->a3mono_min_vol;
//       $tbillmeter = "BILLA3MON";
//       $treadingdate = "1900-01-01";
//       $total_A3m_con =$a3mono_consolidated->total_a3m ?? 0;
 
 
//       if($tax_rate->TaxRate > 0){
//        if($customer_details->icurrencyid ==0){
//          $tlineamtexcl = $total_A3m_con;
//          $tlineamttax = ($total_A3m_con * ($tax_rate->TaxRate / 100));
//          $tlineamtincl =($total_A3m_con * (1 + $tax_rate->TaxRate / 100));
//          $tfclineamtexcl = 0;
//          $tfclineamttax = 0;
//          $tfclineamtincl = 0;
//        }
//        else{
//          $tfclineamtexcl = $total_A3m_con;
//          $tfclineamttax = ($total_A3m_con * ($tax_rate->TaxRate / 100));
//          $tfclineamtincl = ($total_A3m_con * (1 + $tax_rate->TaxRate / 100));
//          $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//          $tlineamttax = $tfclineamttax * $exc_rate;
//          $tlineamtincl = $tfclineamtincl * $exc_rate;
//        }
//      }
//      else{
//        if($customer_details->icurrencyid ==0){
//          $tlineamtexcl = $total_A3m_con;
//          $tlineamttax = 0;
//          $tlineamtincl =$total_A3m_con;
//          $tfclineamtexcl = 0;
//          $tfclineamttax = 0;
//          $tfclineamtincl = 0;
//        }
//        else{
//          $tfclineamtexcl = $total_A3m_con;
//          $tfclineamttax = 0;
//          $tfclineamtincl = $total_A3m_con ;
//          $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//          $tlineamttax = 0;
//          $tlineamtincl = $tfclineamtincl * $exc_rate;
//        }
 
 
 
 
//      }
     
//      $tstockid = 17987;
//      $tlinenote ="CMR: $t_a3m_cmr\r\nPMR: $t_a3m_pmr\r\nCOPIES DONE: $tA3munits\r\nMIN. VOL: $min_A3m_vol\r\nRATE: $A3m_rate";
//      $tlinedesc = "A3 Mono Billing for $month $today->year";
 
 
//             $_btblInvoiceLines_A3mono = new _btblInvoiceLines;
//             $_btblInvoiceLines_A3mono->iInvoiceID =$invoice_id->autoindex;
//             $_btblInvoiceLines_A3mono->iOrigLineID =0;
//             $_btblInvoiceLines_A3mono->iGrvLineID =0;
//             $_btblInvoiceLines_A3mono->iLineDocketMode =0; 
//             $_btblInvoiceLines_A3mono->cDescription =$tlinedesc; 
//             $_btblInvoiceLines_A3mono->iUnitsOfMeasureStockingID=0; 
//             $_btblInvoiceLines_A3mono->iUnitsOfMeasureCategoryID=0;
//             $_btblInvoiceLines_A3mono->iUnitsOfMeasureID=0;
//             $_btblInvoiceLines_A3mono->fQuantity=1;
//             $_btblInvoiceLines_A3mono->fQtyChange=1;
//             $_btblInvoiceLines_A3mono->fQtyToProcess=1; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcess=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessed =0; 
//             $_btblInvoiceLines_A3mono->fQtyReserved=0; 
//             $_btblInvoiceLines_A3mono->fQtyReservedChange =0;
//             $_btblInvoiceLines_A3mono->cLineNotes=$tlinenote; 
//             $_btblInvoiceLines_A3mono->fUnitPriceExcl=$tlineamtexcl; 
//             $_btblInvoiceLines_A3mono->fUnitPriceIncl=$tlineamtincl;
//             $_btblInvoiceLines_A3mono->iUnitPriceOverrideReasonID=0; 
//             $_btblInvoiceLines_A3mono->fUnitCost=0;
//             $_btblInvoiceLines_A3mono->fLineDiscount=0; 
//             $_btblInvoiceLines_A3mono->iLineDiscountReasonID=0;
//             $_btblInvoiceLines_A3mono->iReturnReasonID=0; 
//             $_btblInvoiceLines_A3mono->fTaxRate=$tax_rate->TaxRate; 
//             $_btblInvoiceLines_A3mono->bIsSerialItem=0; 
//             $_btblInvoiceLines_A3mono->bIsWhseItem=1;
//             $_btblInvoiceLines_A3mono->fAddCost=0; 
//             $_btblInvoiceLines_A3mono->cTradeinItem='';
//             $_btblInvoiceLines_A3mono->iStockCodeID=$tstockid; 
//             $_btblInvoiceLines_A3mono->iJobID=0;
//             $_btblInvoiceLines_A3mono->iWarehouseID=6;
//             $_btblInvoiceLines_A3mono->iTaxTypeID=$customer_details->ideftaxtypeid;
//             $_btblInvoiceLines_A3mono->iPriceListNameID=1;
//             $_btblInvoiceLines_A3mono->fQuantityLineTotIncl=$tlineamtincl;
//             $_btblInvoiceLines_A3mono->fQuantityLineTotExcl=$tlineamtexcl;
//             $_btblInvoiceLines_A3mono->fQuantityLineTotInclNoDisc=$tlineamtincl;
//             $_btblInvoiceLines_A3mono->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQuantityLineTaxAmount =$tlineamttax; 
//             $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotIncl =$tlineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotExcl =$tlineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmount =$tlineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotIncl =$tlineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmount =$tlineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotIncl=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExcl =0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclNoDisc=0;
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclNoDisc=0;
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmount=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotIncl=0;
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExcl=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclNoDisc=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclNoDisc=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmount=0;
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_A3mono->fUnitPriceExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_A3mono->fUnitPriceInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fUnitCostForeign=0;
//             $_btblInvoiceLines_A3mono->fAddCostForeign=0;
//             $_btblInvoiceLines_A3mono->fQuantityLineTotInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//             $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//             $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//             $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_A3mono->iLineRepID=$customer_details->repid; 
//             $_btblInvoiceLines_A3mono->iLineProjectID=2; 
//             $_btblInvoiceLines_A3mono->iLedgerAccountID=0; 
//             $_btblInvoiceLines_A3mono->IModule=0;
//             $_btblInvoiceLines_A3mono->bChargeCom=1;
//             $_btblInvoiceLines_A3mono->bIsLotItem=0;
//             // $_btblInvoiceLines_A3mono->iLotID=0;
//             // $_btblInvoiceLines_A3mono->cLotNumber='';
//             // $_btblInvoiceLines_A3mono->dLotExpiryDate=null;
//             $_btblInvoiceLines_A3mono->iMFPID=0;
//             $_btblInvoiceLines_A3mono->iLineID=1;
//             $_btblInvoiceLines_A3mono->iLinkedLineID=0;
//             $_btblInvoiceLines_A3mono->fQtyLinkedUsed=null;
//             $_btblInvoiceLines_A3mono->fUnitPriceInclOrig=null;
//             $_btblInvoiceLines_A3mono->fUnitPriceExclOrig=Null;
//             $_btblInvoiceLines_A3mono->fUnitPriceInclForeignOrig=Null;
//             $_btblInvoiceLines_A3mono->fUnitPriceExclForeignOrig=0;
//             $_btblInvoiceLines_A3mono->iDeliveryMethodID=0;
//             $_btblInvoiceLines_A3mono->fQtyDeliver=0;
//             $_btblInvoiceLines_A3mono->dDeliveryDate=$today;
//             $_btblInvoiceLines_A3mono->iDeliveryStatus=0;
//             $_btblInvoiceLines_A3mono->fQtyForDelivery=0;
//             $_btblInvoiceLines_A3mono->bPromotionApplied=0;
//             $_btblInvoiceLines_A3mono->fPromotionPriceExcl=0;
//             $_btblInvoiceLines_A3mono->fPromotionPriceIncl=0;
//             $_btblInvoiceLines_A3mono->cPromotionCode=0;
//             $_btblInvoiceLines_A3mono->iSOLinkedPOLineID=0;
//             $_btblInvoiceLines_A3mono->fLength=0;
//             $_btblInvoiceLines_A3mono->fWidth=0;
//             $_btblInvoiceLines_A3mono->fHeight=0;
//             $_btblInvoiceLines_A3mono->iPieces=0;
//             $_btblInvoiceLines_A3mono->iPiecesToProcess=0;
//             $_btblInvoiceLines_A3mono->iPiecesLastProcess=0;
//             $_btblInvoiceLines_A3mono->iPiecesProcessed=0;
//             $_btblInvoiceLines_A3mono->iPiecesReserved=0;
//             $_btblInvoiceLines_A3mono->iPiecesDeliver=0;
//             $_btblInvoiceLines_A3mono->iPiecesForDelivery=0;
//             $_btblInvoiceLines_A3mono->fQuantityUR=1;
//             $_btblInvoiceLines_A3mono->fQtyChangeUR=1;
//             $_btblInvoiceLines_A3mono->fQtyToProcessUR=1;
//             $_btblInvoiceLines_A3mono->fQtyLastProcessUR=0;
//             $_btblInvoiceLines_A3mono->fQtyProcessedUR=0;
//             $_btblInvoiceLines_A3mono->fQtyReservedUR=0;
//             $_btblInvoiceLines_A3mono->fQtyReservedChangeUR=0;
//             $_btblInvoiceLines_A3mono->fQtyDeliverUR=0;
//             $_btblInvoiceLines_A3mono->fQtyForDeliveryUR=0;
//             $_btblInvoiceLines_A3mono->fQtyLinkedUsedUR=0;
//             $_btblInvoiceLines_A3mono->iPiecesLinkedUsed=0;
//             $_btblInvoiceLines_A3mono->iSalesWhseID=0;
//             $_btblInvoiceLines_A3mono->_btblInvoiceLines_iBranchID=1;
//             $_btblInvoiceLines_A3mono->udIDSOrdTxCMReadingDate=$today;
//             $_btblInvoiceLines_A3mono->uiIDSOrdTxCMPrevReading=$t_a3m_pmr;
//             $_btblInvoiceLines_A3mono->uiIDSOrdTxCMCurrReading=$t_a3m_cmr;
//             $_btblInvoiceLines_A3mono->ucIDSOrdTxCMMinVol=$min_A3m_vol;
//             $_btblInvoiceLines_A3mono->ucIDSOrdTxCMRates=$A3m_rate;
//             $_btblInvoiceLines_A3mono->ucIDSOrdTxCMServiceAsset=$a3mono_consolidated->ucSABillingAsset;
//             $_btblInvoiceLines_A3mono->ucIDSOrdTxCMMeterType="BILLA3MON";
   
//             $_btblInvoiceLines_A3mono->save();  
                 
//        }
//       }
//       }


//       //  A3 COLOR CONSOLIDATED SEPARATE

//      // A3 COLOR consolidated calculation

//      $asset_id = DB::table('_smtblServiceAsset As sa')
//      ->where('cCode',$value['ucSABillingAsset'])
//      ->select('AutoIdx')
//      ->first();

//      //A3 COLOR
//      $a3color_counter_elapse =DB::table('_smtblcounterelapsed')
//      ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
//      ->where('cCode','A3BILLCOL')
//      ->select('AutoIdx')
//      ->first();

//      if(!empty($a3color_counter_elapse))
//        {
//          $billing_a3color_consolidated = DB::table('_bvARAccountsFull As cl')
//          ->where('DCLink',$customer_details->dclink)
//          ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//          ->whereYear('ReadingDate', '=', $today->year)
//          ->whereMonth('ReadingDate', '=', $today->month)

//          ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset',
//          DB::raw('sum(cmr.A3CPMR) as a3Cpmr'),
//          DB::raw('sum(cmr.A3CCMR) as a3Ccmr'),
//          DB::raw('sum(cmr.A3CCMR - cmr.A3CPMR) as a3Colunit'),   
//          'sa.ucSABillingAsset'
       
//         )
//          ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//          ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//          ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset')
//          ->get();

       

//        foreach($billing_a3color_consolidated as $data){

//          $asset_id = DB::table('_smtblServiceAsset As sa')
//          ->where('cCode',$data->ucSABillingAsset)
//          ->select('AutoIdx')
//          ->first();            
       

//      // A3  COLOR consolidated calculation

//          //A3 COLOR
//          $a3color_counter_elapse =DB::table('_smtblcounterelapsed')
//          ->where('iServiceAssetId',$asset_id->AutoIdx)
//          ->where('cCode','A3BILLCOL')
//          ->select('AutoIdx')
//          ->first();

      
//          $a3color_min_vol = _smtblrateslab::where('iBillingID',$a3color_counter_elapse->AutoIdx ?? '')
//            ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//            ->groupBy('fRate',DB::raw('(iToqty)'))
//            ->first();

//            if(!empty($a3color_min_vol))
//            {
 

//            // dd($a3color_min_vol);
//            if($a3color_min_vol->min_vol >=1000000){
//              $data->a3color_min_vol = null;
//             }
//          else{
//              $data->a3color_min_vol =$a3color_min_vol->min_vol ?? null;
//          }
//          if( $a3color_min_vol->min_vol  >=1000000 ){
 
//              $total_frate_a3color =($a3color_min_vol->fRate * $data->a3Colunit);
//              $rate_frate_a3color =number_format($a3color_min_vol->fRate,3);
           
 
//              $data->comb_rates_a3C = $rate_frate_a3color;
//              $data->total_a3C = $total_frate_a3color;            
 
 
//          }
         
//        else{
//          $diff_a3Col = ($data->a3cunit - $A3Cmin_vol->min_vol);
//          if($diff_a3Col > 0){
//              $rate_min_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//              ->where('iToqty',$A3Cmin_vol->min_vol)
//              ->select('frate')
//              ->first();
//              $total_min_a3C =($rate_min_a3C->frate * $A3Cmin_vol->min_vol);

//              $rate_a3C =number_format($rate_min_a3C->frate,3);
             
//              //slab two starts
//              $Increment_1a3C =$A3Cmin_vol->min_vol +1; 

     
//          // we get the rate for the next slab and their readings
//          $next_slab_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//          ->where('iFromQty','<=',$Increment_1a3C)
//          ->where('iToqty','>=',$Increment_1a3C)
//          ->select('iFromQty','iToqty','fRate')
//          ->first();
    
//              //  we get the difference of this slab
//          $diff_slab1_a3C =($next_slab_a3C->iToqty - $next_slab_a3C->iFromQty)+1;


//          //  we check if the remainder fulls fall in this slab or exceed
//          $diff_bil_slab_a3C =$diff_a3Col -$diff_slab1_a3C;           

//         //counts fits fully in this slab the difference is negative
//          if($diff_bil_slab_a3C < 0){
//              $total_slab_1_a3C =($diff_a3Col * $next_slab_a3C->fRate );

//           $rate_2_a3C =number_format($next_slab_a3C->fRate,3);            

//          // two slabs used             
//          $comb_rates_a3C =$rate_a3C.' | '.$rate_2_a3C;
//          $total_a3C =($total_slab_1_a3C +$total_min_a3C);

//          $data->comb_rates_a3C = $comb_rates_a3C;
//          $data->total_a3C = $total_a3C;          
     
//      }
//      }

//      else{
//          $rate_min_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//              ->where('iToqty',$A3Cmin_vol->min_vol)
//              ->select('frate')
//              ->first();
//          $total_min_a3C =($rate_min_a3C->frate * $A3Cmin_vol->min_vol);
//          $rate_a3C =number_format($rate_min_a3C->frate,3);

//          $data->comb_rates_a3C = $rate_a3C;
//          $data->total_a3C = $total_min_a3C;  

//      }
//      }       
//    }
//  }         

     

     
//       $bill_a3color_con =$billing_a3color_consolidated[0] ?? '';
//       $a3color_consolidated =(object) $bill_a3color_con;


 
//           $t_a3C_cmr = $a3color_consolidated->a3Ccmr;
//           $t_a3C_pmr = $a3color_consolidated->a3Cpmr;
//           $tA3Cunits = $a3color_consolidated-> a3Colunit;
//           $A3C_rate =$a3color_consolidated->comb_rates_a3C;
//           $min_A3C_vol =$a3color_consolidated->a3color_min_vol;
//           $tbillmeter = "BILLA3COL";
//           $treadingdate = "1900-01-01";
//           $total_A3C_con =$a3color_consolidated->total_a3C;

//           if($tax_rate->TaxRate > 0){
//           if($customer_details->icurrencyid ==0){
//           $tlineamtexcl = $total_A3C_con;
//           $tlineamttax = ($total_A3C_con * ($tax_rate->TaxRate / 100));
//           $tlineamtincl =($total_A3C_con * (1 + $tax_rate->TaxRate / 100));
//           $tfclineamtexcl = 0;
//           $tfclineamttax = 0;
//           $tfclineamtincl = 0;
//           }
//           else{
//           $tfclineamtexcl = $total_A3C_con;
//           $tfclineamttax = ($total_A3C_con * ($tax_rate->TaxRate / 100));
//           $tfclineamtincl = ($total_A3C_con * (1 + $tax_rate->TaxRate / 100));
//           $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//           $tlineamttax = $tfclineamttax * $exc_rate;
//           $tlineamtincl = $tfclineamtincl * $exc_rate;
//           }
//           }
//           else{
//           if($customer_details->icurrencyid ==0){
//           $tlineamtexcl = $total_A3C_con;
//           $tlineamttax = 0;
//           $tlineamtincl =$total_A3C_con;
//           $tfclineamtexcl = 0;
//           $tfclineamttax = 0;
//           $tfclineamtincl = 0;
//           }
//           else{
//           $tfclineamtexcl = $total_A3C_con;
//           $tfclineamttax = 0;
//           $tfclineamtincl = $total_A3C_con;
//           $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//           $tlineamttax = 0;
//           $tlineamtincl = $tfclineamtincl * $exc_rate;
//           }


//           }

// $tstockid = 17987;
// $tlinenote ="CMR: $t_a3C_cmr\r\nPMR: $t_a3C_pmr\r\nCOPIES DONE: $tA3Cunits\r\nMIN. VOL: $min_A3C_vol\r\nRATE: $A3C_rate";
// $tlinedesc = "A3 Color Billing for $month $today->year";

//         $_btblInvoiceLines_A3Color = new _btblInvoiceLines;
//         $_btblInvoiceLines_A3Color->iInvoiceID =$invoice_id;
//         $_btblInvoiceLines_A3Color->iOrigLineID =0;
//         $_btblInvoiceLines_A3Color->iGrvLineID =0;
//         $_btblInvoiceLines_A3Color->iLineDocketMode =0; 
//         $_btblInvoiceLines_A3Color->cDescription =$tlinedesc; 
//         $_btblInvoiceLines_A3Color->iUnitsOfMeasureStockingID=0; 
//         $_btblInvoiceLines_A3Color->iUnitsOfMeasureCategoryID=0;
//         $_btblInvoiceLines_A3Color->iUnitsOfMeasureID=0;
//         $_btblInvoiceLines_A3Color->fQuantity=1;
//         $_btblInvoiceLines_A3Color->fQtyChange=1;
//         $_btblInvoiceLines_A3Color->fQtyToProcess=1; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcess=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessed =0; 
//         $_btblInvoiceLines_A3Color->fQtyReserved=0; 
//         $_btblInvoiceLines_A3Color->fQtyReservedChange =0;
//         $_btblInvoiceLines_A3Color->cLineNotes=$tlinenote; 
//         $_btblInvoiceLines_A3Color->fUnitPriceExcl=$tlineamtexcl; 
//         $_btblInvoiceLines_A3Color->fUnitPriceIncl=$tlineamtincl;
//         $_btblInvoiceLines_A3Color->iUnitPriceOverrideReasonID=0; 
//         $_btblInvoiceLines_A3Color->fUnitCost=0;
//         $_btblInvoiceLines_A3Color->fLineDiscount=0; 
//         $_btblInvoiceLines_A3Color->iLineDiscountReasonID=0;
//         $_btblInvoiceLines_A3Color->iReturnReasonID=0; 
//         $_btblInvoiceLines_A3Color->fTaxRate=$tax_rate->TaxRate; 
//         $_btblInvoiceLines_A3Color->bIsSerialItem=0; 
//         $_btblInvoiceLines_A3Color->bIsWhseItem=1;
//         $_btblInvoiceLines_A3Color->fAddCost=0; 
//         $_btblInvoiceLines_A3Color->cTradeinItem='';
//         $_btblInvoiceLines_A3Color->iStockCodeID=$tstockid; 
//         $_btblInvoiceLines_A3Color->iJobID=0;
//         $_btblInvoiceLines_A3Color->iWarehouseID=6;
//         $_btblInvoiceLines_A3Color->iTaxTypeID=$customer_details->ideftaxtypeid;
//         $_btblInvoiceLines_A3Color->iPriceListNameID=1;
//         $_btblInvoiceLines_A3Color->fQuantityLineTotIncl=$tlineamtincl;
//         $_btblInvoiceLines_A3Color->fQuantityLineTotExcl=$tlineamtexcl;
//         $_btblInvoiceLines_A3Color->fQuantityLineTotInclNoDisc=$tlineamtincl;
//         $_btblInvoiceLines_A3Color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQuantityLineTaxAmount =$tlineamttax; 
//         $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotIncl =$tlineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotExcl =$tlineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmount =$tlineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotIncl =$tlineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmount =$tlineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotIncl=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExcl =0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclNoDisc=0;
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclNoDisc=0;
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmount=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountNoDisc=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotIncl=0;
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExcl=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclNoDisc=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclNoDisc=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmount=0;
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountNoDisc=0; 
//         $_btblInvoiceLines_A3Color->fUnitPriceExclForeign=$tfclineamtexcl; 
//         $_btblInvoiceLines_A3Color->fUnitPriceInclForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fUnitCostForeign=0;
//         $_btblInvoiceLines_A3Color->fAddCostForeign=0;
//         $_btblInvoiceLines_A3Color->fQuantityLineTotInclForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//         $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//         $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//         $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclNoDiscForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclNoDiscForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountForeign=0; 
//         $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//         $_btblInvoiceLines_A3Color->iLineRepID=$customer_details->repid; 
//         $_btblInvoiceLines_A3Color->iLineProjectID=2; 
//         $_btblInvoiceLines_A3Color->iLedgerAccountID=0; 
//         $_btblInvoiceLines_A3Color->IModule=0;
//         $_btblInvoiceLines_A3Color->bChargeCom=1;
//         $_btblInvoiceLines_A3Color->bIsLotItem=0;
//         // $_btblInvoiceLines_A3Color->iLotID=0;
//         // $_btblInvoiceLines_A3Color->cLotNumber='';
//         // $_btblInvoiceLines_A3Color->dLotExpiryDate=null;
//         $_btblInvoiceLines_A3Color->iMFPID=0;
//         $_btblInvoiceLines_A3Color->iLineID=1;
//         $_btblInvoiceLines_A3Color->iLinkedLineID=0;
//         $_btblInvoiceLines_A3Color->fQtyLinkedUsed=null;
//         $_btblInvoiceLines_A3Color->fUnitPriceInclOrig=null;
//         $_btblInvoiceLines_A3Color->fUnitPriceExclOrig=Null;
//         $_btblInvoiceLines_A3Color->fUnitPriceInclForeignOrig=Null;
//         $_btblInvoiceLines_A3Color->fUnitPriceExclForeignOrig=0;
//         $_btblInvoiceLines_A3Color->iDeliveryMethodID=0;
//         $_btblInvoiceLines_A3Color->fQtyDeliver=0;
//         $_btblInvoiceLines_A3Color->dDeliveryDate=$today;
//         $_btblInvoiceLines_A3Color->iDeliveryStatus=0;
//         $_btblInvoiceLines_A3Color->fQtyForDelivery=0;
//         $_btblInvoiceLines_A3Color->bPromotionApplied=0;
//         $_btblInvoiceLines_A3Color->fPromotionPriceExcl=0;
//         $_btblInvoiceLines_A3Color->fPromotionPriceIncl=0;
//         $_btblInvoiceLines_A3Color->cPromotionCode=0;
//         $_btblInvoiceLines_A3Color->iSOLinkedPOLineID=0;
//         $_btblInvoiceLines_A3Color->fLength=0;
//         $_btblInvoiceLines_A3Color->fWidth=0;
//         $_btblInvoiceLines_A3Color->fHeight=0;
//         $_btblInvoiceLines_A3Color->iPieces=0;
//         $_btblInvoiceLines_A3Color->iPiecesToProcess=0;
//         $_btblInvoiceLines_A3Color->iPiecesLastProcess=0;
//         $_btblInvoiceLines_A3Color->iPiecesProcessed=0;
//         $_btblInvoiceLines_A3Color->iPiecesReserved=0;
//         $_btblInvoiceLines_A3Color->iPiecesDeliver=0;
//         $_btblInvoiceLines_A3Color->iPiecesForDelivery=0;
//         $_btblInvoiceLines_A3Color->fQuantityUR=1;
//         $_btblInvoiceLines_A3Color->fQtyChangeUR=1;
//         $_btblInvoiceLines_A3Color->fQtyToProcessUR=1;
//         $_btblInvoiceLines_A3Color->fQtyLastProcessUR=0;
//         $_btblInvoiceLines_A3Color->fQtyProcessedUR=0;
//         $_btblInvoiceLines_A3Color->fQtyReservedUR=0;
//         $_btblInvoiceLines_A3Color->fQtyReservedChangeUR=0;
//         $_btblInvoiceLines_A3Color->fQtyDeliverUR=0;
//         $_btblInvoiceLines_A3Color->fQtyForDeliveryUR=0;
//         $_btblInvoiceLines_A3Color->fQtyLinkedUsedUR=0;
//         $_btblInvoiceLines_A3Color->iPiecesLinkedUsed=0;
//         $_btblInvoiceLines_A3Color->iSalesWhseID=0;
//         $_btblInvoiceLines_A3Color->_btblInvoiceLines_iBranchID=1;
//         $_btblInvoiceLines_A3Color->udIDSOrdTxCMReadingDate=$today;
//         $_btblInvoiceLines_A3Color->uiIDSOrdTxCMPrevReading=$t_a3C_pmr;
//         $_btblInvoiceLines_A3Color->uiIDSOrdTxCMCurrReading=$t_a3C_cmr;
//         $_btblInvoiceLines_A3Color->ucIDSOrdTxCMMinVol=$min_A3C_vol;
//         $_btblInvoiceLines_A3Color->ucIDSOrdTxCMRates=$A3C_rate;
//         $_btblInvoiceLines_A3Color->ucIDSOrdTxCMServiceAsset=$a3color_consolidated->ucSABillingAsset;
//         $_btblInvoiceLines_A3Color->ucIDSOrdTxCMMeterType="BILLA3COL";

//         $_btblInvoiceLines_A3Color->save();
              
          
//         }  

     
//         //  calculating rental Charges
  
//         $rental_amount = $value['rental_charges'];
           
  
//           $rental_charge = $rental_amount;
  
//           if($rental_charge > 0){
            
//             $tbillmeter = "RENTAL";
//             $rental_amt = $rental_charge;
//             if($tax_rate->TaxRate > 0){
//               if($customer_details->icurrencyid ==0){
//                 $tlineamtexcl = $rental_amt;
//                 $tlineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
//                 $tlineamtincl =($rental_amt * (1 + $tax_rate->TaxRate / 100));
//                 $tfclineamtexcl = 0;
//                 $tfclineamttax = 0;
//                 $tfclineamtincl = 0;
//               }
//               else{
//                 $tfclineamtexcl = $rental_amt;
//                 $tfclineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
//                 $tfclineamtincl = ($rental_amt * (1 + $tax_rate->TaxRate / 100));
//                 $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//                 $tlineamttax = $tfclineamttax * $exc_rate;
//                 $tlineamtincl = $tfclineamtincl * $exc_rate;
//               }
//             }
//             else{
//               if($customer_details->icurrencyid ==0){
//                 $tlineamtexcl = $rental_amt;
//                 $tlineamttax =  0;
//                 $tlineamtincl = $rental_amt;
//                 $tfclineamtexcl = 0;
//                 $tfclineamttax = 0;
//                 $tfclineamtincl = 0;
//               }
//               else{
//                 $tfclineamtexcl = $rental_amt;
//                 $tfclineamttax = 0;
//                 $tfclineamtincl = $rental_amt;
//                 $tfclineamtexcl = $tfclineamtexcl *$exc_rate;
//                 $tfclineamttax = 0;
//                 $tfclineamtincl = $tfclineamtincl * $exc_rate;
//               }
//             }



            
  
//             $tlinenote = "";                    
//             $tlinedesc = "Rental for $month $today->year";          
//             $tstockid = 17987;

            
  
//             $_btblInvoiceLines_rental = new _btblInvoiceLines;
//             $_btblInvoiceLines_rental->iInvoiceID =$invoice_id->autoindex;
//             $_btblInvoiceLines_rental->iOrigLineID =0;
//             $_btblInvoiceLines_rental->iGrvLineID =0;
//             $_btblInvoiceLines_rental->iLineDocketMode =0; 
//             $_btblInvoiceLines_rental->cDescription =$tlinedesc; 
//             $_btblInvoiceLines_rental->iUnitsOfMeasureStockingID=0; 
//             $_btblInvoiceLines_rental->iUnitsOfMeasureCategoryID=0;
//             $_btblInvoiceLines_rental->iUnitsOfMeasureID=0;
//             $_btblInvoiceLines_rental->fQuantity=1;
//             $_btblInvoiceLines_rental->fQtyChange=1;
//             $_btblInvoiceLines_rental->fQtyToProcess=1; 
//             $_btblInvoiceLines_rental->fQtyLastProcess=0; 
//             $_btblInvoiceLines_rental->fQtyProcessed =0; 
//             $_btblInvoiceLines_rental->fQtyReserved=0; 
//             $_btblInvoiceLines_rental->fQtyReservedChange =0;
//             $_btblInvoiceLines_rental->cLineNotes=$tlinenote; 
//             $_btblInvoiceLines_rental->fUnitPriceExcl=$tlineamtexcl; 
//             $_btblInvoiceLines_rental->fUnitPriceIncl=$tlineamtincl;
//             $_btblInvoiceLines_rental->iUnitPriceOverrideReasonID=0; 
//             $_btblInvoiceLines_rental->fUnitCost=0;
//             $_btblInvoiceLines_rental->fLineDiscount=0; 
//             $_btblInvoiceLines_rental->iLineDiscountReasonID=0;
//             $_btblInvoiceLines_rental->iReturnReasonID=0; 
//             $_btblInvoiceLines_rental->fTaxRate=$tax_rate->TaxRate; 
//             $_btblInvoiceLines_rental->bIsSerialItem=0; 
//             $_btblInvoiceLines_rental->bIsWhseItem=1;
//             $_btblInvoiceLines_rental->fAddCost=0; 
//             $_btblInvoiceLines_rental->cTradeinItem='';
//             $_btblInvoiceLines_rental->iStockCodeID=$tstockid; 
//             $_btblInvoiceLines_rental->iJobID=0;
//             $_btblInvoiceLines_rental->iWarehouseID=6;
//             $_btblInvoiceLines_rental->iTaxTypeID=$customer_details->ideftaxtypeid;
//             $_btblInvoiceLines_rental->iPriceListNameID=1;
//             $_btblInvoiceLines_rental->fQuantityLineTotIncl=$tlineamtincl;
//             $_btblInvoiceLines_rental->fQuantityLineTotExcl=$tlineamtexcl;
//             $_btblInvoiceLines_rental->fQuantityLineTotInclNoDisc=$tlineamtincl;
//             $_btblInvoiceLines_rental->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//             $_btblInvoiceLines_rental->fQuantityLineTaxAmount =$tlineamttax; 
//             $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotIncl =$tlineamtincl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotExcl =$tlineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTaxAmount =$tlineamttax; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotIncl =$tlineamtincl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmount =$tlineamttax; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotIncl=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotExcl =0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDisc=0;
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDisc=0;
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmount=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotIncl=0;
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotExcl=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDisc=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDisc=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmount=0;
//             $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_rental->fUnitPriceExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_rental->fUnitPriceInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fUnitCostForeign=0;
//             $_btblInvoiceLines_rental->fAddCostForeign=0;
//             $_btblInvoiceLines_rental->fQuantityLineTotInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_rental->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//             $_btblInvoiceLines_rental->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//             $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//             $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//             $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclForeign=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclForeign=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotInclForeign=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotExclForeign=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_rental->iLineRepID=$customer_details->repid; 
//             $_btblInvoiceLines_rental->iLineProjectID=2; 
//             $_btblInvoiceLines_rental->iLedgerAccountID=0; 
//             $_btblInvoiceLines_rental->IModule=0;
//             $_btblInvoiceLines_rental->bChargeCom=1;
//             $_btblInvoiceLines_rental->bIsLotItem=0;
//             // $_btblInvoiceLines_rental->iLotID=0;
//             // $_btblInvoiceLines_rental->cLotNumber='';
//             // $_btblInvoiceLines_rental->dLotExpiryDate=null;
//             $_btblInvoiceLines_rental->iMFPID=0;
//             $_btblInvoiceLines_rental->iLineID=1;
//             $_btblInvoiceLines_rental->iLinkedLineID=0;
//             $_btblInvoiceLines_rental->fQtyLinkedUsed=null;
//             $_btblInvoiceLines_rental->fUnitPriceInclOrig=null;
//             $_btblInvoiceLines_rental->fUnitPriceExclOrig=Null;
//             $_btblInvoiceLines_rental->fUnitPriceInclForeignOrig=Null;
//             $_btblInvoiceLines_rental->fUnitPriceExclForeignOrig=0;
//             $_btblInvoiceLines_rental->iDeliveryMethodID=0;
//             $_btblInvoiceLines_rental->fQtyDeliver=0;
//             $_btblInvoiceLines_rental->dDeliveryDate=$today;
//             $_btblInvoiceLines_rental->iDeliveryStatus=0;
//             $_btblInvoiceLines_rental->fQtyForDelivery=0;
//             $_btblInvoiceLines_rental->bPromotionApplied=0;
//             $_btblInvoiceLines_rental->fPromotionPriceExcl=0;
//             $_btblInvoiceLines_rental->fPromotionPriceIncl=0;
//             $_btblInvoiceLines_rental->cPromotionCode=0;
//             $_btblInvoiceLines_rental->iSOLinkedPOLineID=0;
//             $_btblInvoiceLines_rental->fLength=0;
//             $_btblInvoiceLines_rental->fWidth=0;
//             $_btblInvoiceLines_rental->fHeight=0;
//             $_btblInvoiceLines_rental->iPieces=0;
//             $_btblInvoiceLines_rental->iPiecesToProcess=0;
//             $_btblInvoiceLines_rental->iPiecesLastProcess=0;
//             $_btblInvoiceLines_rental->iPiecesProcessed=0;
//             $_btblInvoiceLines_rental->iPiecesReserved=0;
//             $_btblInvoiceLines_rental->iPiecesDeliver=0;
//             $_btblInvoiceLines_rental->iPiecesForDelivery=0;
//             $_btblInvoiceLines_rental->fQuantityUR=1;
//             $_btblInvoiceLines_rental->fQtyChangeUR=1;
//             $_btblInvoiceLines_rental->fQtyToProcessUR=1;
//             $_btblInvoiceLines_rental->fQtyLastProcessUR=0;
//             $_btblInvoiceLines_rental->fQtyProcessedUR=0;
//             $_btblInvoiceLines_rental->fQtyReservedUR=0;
//             $_btblInvoiceLines_rental->fQtyReservedChangeUR=0;
//             $_btblInvoiceLines_rental->fQtyDeliverUR=0;
//             $_btblInvoiceLines_rental->fQtyForDeliveryUR=0;
//             $_btblInvoiceLines_rental->fQtyLinkedUsedUR=0;
//             $_btblInvoiceLines_rental->iPiecesLinkedUsed=0;
//             $_btblInvoiceLines_rental->iSalesWhseID=0;
//             $_btblInvoiceLines_rental->_btblInvoiceLines_iBranchID=0;
//             $_btblInvoiceLines_rental->udIDSOrdTxCMReadingDate='';
//             $_btblInvoiceLines_rental->uiIDSOrdTxCMPrevReading='';
//             $_btblInvoiceLines_rental->uiIDSOrdTxCMCurrReading='';
//             $_btblInvoiceLines_rental->ucIDSOrdTxCMMinVol='';
//             $_btblInvoiceLines_rental->ucIDSOrdTxCMRates='';
//             $_btblInvoiceLines_rental->ucIDSOrdTxCMServiceAsset=$mono_consolidated->ucSABillingAsset;
//             $_btblInvoiceLines_rental->ucIDSOrdTxCMMeterType="RENTAL";
  
//             $_btblInvoiceLines_rental->save();
  
//           }
//           // individual items mono
//           $tlineamtexcl_indv = 0;
//           $tlineamttax = 0;
//           $tlineamtincl_indv = 0;
//           $tfclineamtexcl_indv = 0;
//           $tfclineamttax_indv = 0;
//           $tfclineamtincl_indv = 0;
//           $tlinenote_indv = "";
//           $tlinedesc_indv = "";
//           $tstockid_ind = 28587;
//           $tvolstr = "";
//           $tratestr = "";

//         $individual_mono_consolidated = DB::table('_bvARAccountsFull As cl')
//         ->where('DCLink',$customer_details->dclink)
//         ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//         ->whereYear('ReadingDate', '=', $today->year)
//         ->whereMonth('ReadingDate', '=', $today->month)

//         ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset','cmr.moncmr','cmr.monpmr','sa.cDescription','sa.ucSASerialNo')
//         ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//         ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')     
//         ->get();
        

//          foreach($individual_mono_consolidated as $data){

//          $_btblInvoiceLines_indv = new _btblInvoiceLines;
//          $_btblInvoiceLines_indv->iInvoiceID =$invoice_id->autoindex;
//          $_btblInvoiceLines_indv->iOrigLineID =0;
//          $_btblInvoiceLines_indv->iGrvLineID =0;
//          $_btblInvoiceLines_indv->iLineDocketMode =0; 
//          $_btblInvoiceLines_indv->cDescription =$data->cDescription; 
//          $_btblInvoiceLines_indv->iUnitsOfMeasureStockingID=0; 
//          $_btblInvoiceLines_indv->iUnitsOfMeasureCategoryID=0;
//          $_btblInvoiceLines_indv->iUnitsOfMeasureID=0;
//          $_btblInvoiceLines_indv->fQuantity=1;
//          $_btblInvoiceLines_indv->fQtyChange=1;
//          $_btblInvoiceLines_indv->fQtyToProcess=1; 
//          $_btblInvoiceLines_indv->fQtyLastProcess=0; 
//          $_btblInvoiceLines_indv->fQtyProcessed =0; 
//          $_btblInvoiceLines_indv->fQtyReserved=0; 
//          $_btblInvoiceLines_indv->fQtyReservedChange =0;
//          $_btblInvoiceLines_indv->cLineNotes=$tlinenote_indv; 
//          $_btblInvoiceLines_indv->fUnitPriceExcl=$tlineamtexcl_indv; 
//          $_btblInvoiceLines_indv->fUnitPriceIncl=$tlineamtincl_indv;
//          $_btblInvoiceLines_indv->iUnitPriceOverrideReasonID=0; 
//          $_btblInvoiceLines_indv->fUnitCost=0;
//          $_btblInvoiceLines_indv->fLineDiscount=0; 
//          $_btblInvoiceLines_indv->iLineDiscountReasonID=0;
//          $_btblInvoiceLines_indv->iReturnReasonID=0; 
//          $_btblInvoiceLines_indv->fTaxRate=$tax_rate->TaxRate; 
//          $_btblInvoiceLines_indv->bIsSerialItem=0; 
//          $_btblInvoiceLines_indv->bIsWhseItem=1;
//          $_btblInvoiceLines_indv->fAddCost=0; 
//          $_btblInvoiceLines_indv->cTradeinItem='';
//          $_btblInvoiceLines_indv->iStockCodeID=$tstockid_ind; 
//          $_btblInvoiceLines_indv->iJobID=0;
//          $_btblInvoiceLines_indv->iWarehouseID=6;
//          $_btblInvoiceLines_indv->iTaxTypeID=$customer_details->ideftaxtypeid;
//          $_btblInvoiceLines_indv->iPriceListNameID=1;
//          $_btblInvoiceLines_indv->fQuantityLineTotIncl=$tlineamtincl_indv;
//          $_btblInvoiceLines_indv->fQuantityLineTotExcl=$tlineamtexcl_indv;
//          $_btblInvoiceLines_indv->fQuantityLineTotInclNoDisc=$tlineamtincl_indv;
//          $_btblInvoiceLines_indv->fQuantityLineTotExclNoDisc =$tlineamtexcl_indv; 
//          $_btblInvoiceLines_indv->fQuantityLineTaxAmount =0; 
//          $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDisc=0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotIncl =0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotExcl =0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDisc =0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDisc =0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTaxAmount =0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDisc =0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotIncl =0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotExcl =0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDisc=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDisc=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmount =0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDisc =0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotIncl=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotExcl =0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDisc=0;
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDisc=0;
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmount=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDisc=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotIncl=0;
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotExcl=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDisc=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDisc=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmount=0;
//          $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDisc=0; 
//          $_btblInvoiceLines_indv->fUnitPriceExclForeign=0; 
//          $_btblInvoiceLines_indv->fUnitPriceInclForeign=0; 
//          $_btblInvoiceLines_indv->fUnitCostForeign=0;
//          $_btblInvoiceLines_indv->fAddCostForeign=0;
//          $_btblInvoiceLines_indv->fQuantityLineTotInclForeign=0; 
//          $_btblInvoiceLines_indv->fQuantityLineTotExclForeign=0; 
//          $_btblInvoiceLines_indv->fQuantityLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQuantityLineTotExclNoDiscForeign=0;      
//          $_btblInvoiceLines_indv->fQuantityLineTaxAmountForeign=0; 
//          $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotInclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotExclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountForeign=0;
//          $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotInclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotExclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountForeign=0; 
//          $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountForeign=0; 
//          $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotInclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotExclForeign=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountForeign=0; 
//          $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//          $_btblInvoiceLines_indv->iLineRepID=$customer_details->repid; 
//          $_btblInvoiceLines_indv->iLineProjectID=2; 
//          $_btblInvoiceLines_indv->iLedgerAccountID=0; 
//          $_btblInvoiceLines_indv->IModule=0;
//          $_btblInvoiceLines_indv->bChargeCom=1;
//          $_btblInvoiceLines_indv->bIsLotItem=0;
//         //  $_btblInvoiceLines_indv->iLotID=0;
//         //  $_btblInvoiceLines_indv->cLotNumber='';
//         //  $_btblInvoiceLines_indv->dLotExpiryDate=null;
//          $_btblInvoiceLines_indv->iMFPID=0;
//          $_btblInvoiceLines_indv->iLineID=1;
//          $_btblInvoiceLines_indv->iLinkedLineID=0;
//          $_btblInvoiceLines_indv->fQtyLinkedUsed=null;
//          $_btblInvoiceLines_indv->fUnitPriceInclOrig=null;
//          $_btblInvoiceLines_indv->fUnitPriceExclOrig=Null;
//          $_btblInvoiceLines_indv->fUnitPriceInclForeignOrig=Null;
//          $_btblInvoiceLines_indv->fUnitPriceExclForeignOrig=0;
//          $_btblInvoiceLines_indv->iDeliveryMethodID=0;
//          $_btblInvoiceLines_indv->fQtyDeliver=0;
//          $_btblInvoiceLines_indv->dDeliveryDate=$today;
//          $_btblInvoiceLines_indv->iDeliveryStatus=0;
//          $_btblInvoiceLines_indv->fQtyForDelivery=0;
//          $_btblInvoiceLines_indv->bPromotionApplied=0;
//          $_btblInvoiceLines_indv->fPromotionPriceExcl=0;
//          $_btblInvoiceLines_indv->fPromotionPriceIncl=0;
//          $_btblInvoiceLines_indv->cPromotionCode=0;
//          $_btblInvoiceLines_indv->iSOLinkedPOLineID=0;
//          $_btblInvoiceLines_indv->fLength=0;
//          $_btblInvoiceLines_indv->fWidth=0;
//          $_btblInvoiceLines_indv->fHeight=0;
//          $_btblInvoiceLines_indv->iPieces=0;
//          $_btblInvoiceLines_indv->iPiecesToProcess=0;
//          $_btblInvoiceLines_indv->iPiecesLastProcess=0;
//          $_btblInvoiceLines_indv->iPiecesProcessed=0;
//          $_btblInvoiceLines_indv->iPiecesReserved=0;
//          $_btblInvoiceLines_indv->iPiecesDeliver=0;
//          $_btblInvoiceLines_indv->iPiecesForDelivery=0;
//          $_btblInvoiceLines_indv->fQuantityUR=1;
//          $_btblInvoiceLines_indv->fQtyChangeUR=1;
//          $_btblInvoiceLines_indv->fQtyToProcessUR=1;
//          $_btblInvoiceLines_indv->fQtyLastProcessUR=0;
//          $_btblInvoiceLines_indv->fQtyProcessedUR=0;
//          $_btblInvoiceLines_indv->fQtyReservedUR=0;
//          $_btblInvoiceLines_indv->fQtyReservedChangeUR=0;
//          $_btblInvoiceLines_indv->fQtyDeliverUR=0;
//          $_btblInvoiceLines_indv->fQtyForDeliveryUR=0;
//          $_btblInvoiceLines_indv->fQtyLinkedUsedUR=0;
//          $_btblInvoiceLines_indv->iPiecesLinkedUsed=0;
//          $_btblInvoiceLines_indv->iSalesWhseID=0;
//          $_btblInvoiceLines_indv->_btblInvoiceLines_iBranchID=0;
//          $_btblInvoiceLines_indv->udIDSOrdTxCMReadingDate='';
//          $_btblInvoiceLines_indv->uiIDSOrdTxCMPrevReading=$data->monpmr;
//          $_btblInvoiceLines_indv->uiIDSOrdTxCMCurrReading=$data->moncmr;
//          $_btblInvoiceLines_indv->ucIDSOrdTxCMMinVol='';
//          $_btblInvoiceLines_indv->ucIDSOrdTxCMRates='';
//          $_btblInvoiceLines_indv->ucIDSOrdTxCMServiceAsset=$data->ucSASerialNo;
//          $_btblInvoiceLines_indv->ucIDSOrdTxCMMeterType="BILLMON"; 
//          $_btblInvoiceLines_indv->save();


//          //individual color


//          $individual_color_consolidated = DB::table('_bvARAccountsFull As cl')
//          ->where('DCLink',$customer_details->dclink)
//          ->where('sa.ucSASerialNo',$data->ucSASerialNo)
//          ->Where(function($query)
//          {
//              $query->where('cmr.colpmr','!=','')
//              ->orwhere('cmr.colcmr','!=','');
            
//          })

//          ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//          ->whereYear('ReadingDate', '=', $today->year)
//          ->whereMonth('ReadingDate', '=', $today->month)

//          ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset','cmr.colcmr','cmr.colpmr','sa.cDescription','sa.ucSASerialNo')
//          ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//          ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')     
//          ->first();

           
         
//          if(!empty($individual_color_consolidated)){
          
               
//              $tlineamtexcl_indv = 0;
//               $tlineamttax = 0;
//               $tlineamtincl_indv = 0;
//               $tfclineamtexcl_indv = 0;
//               $tfclineamttax_indv = 0;
//               $tfclineamtincl_indv = 0;
//               $tlinenote_indv = "";
//               $tlinedesc_indv = "";
//               $tstockid_ind = 28587;
//               $tvolstr = "";
//               $tratestr = "";

//               $_btblInvoiceLines_indv = new _btblInvoiceLines;
//               $_btblInvoiceLines_indv->iInvoiceID =$invoice_id->autoindex;
//               $_btblInvoiceLines_indv->iOrigLineID =0;
//               $_btblInvoiceLines_indv->iGrvLineID =0;
//               $_btblInvoiceLines_indv->iLineDocketMode =0; 
//                $_btblInvoiceLines_indv->cDescription =$individual_color_consolidated->cDescription; 
//               $_btblInvoiceLines_indv->iUnitsOfMeasureStockingID=0; 
//               $_btblInvoiceLines_indv->iUnitsOfMeasureCategoryID=0;
//               $_btblInvoiceLines_indv->iUnitsOfMeasureID=0;
//               $_btblInvoiceLines_indv->fQuantity=1;
//               $_btblInvoiceLines_indv->fQtyChange=1;
//               $_btblInvoiceLines_indv->fQtyToProcess=1; 
//               $_btblInvoiceLines_indv->fQtyLastProcess=0; 
//               $_btblInvoiceLines_indv->fQtyProcessed =0; 
//               $_btblInvoiceLines_indv->fQtyReserved=0; 
//               $_btblInvoiceLines_indv->fQtyReservedChange =0;
//               $_btblInvoiceLines_indv->cLineNotes=$tlinenote_indv; 
//               $_btblInvoiceLines_indv->fUnitPriceExcl=$tlineamtexcl_indv; 
//               $_btblInvoiceLines_indv->fUnitPriceIncl=$tlineamtincl_indv;
//               $_btblInvoiceLines_indv->iUnitPriceOverrideReasonID=0; 
//               $_btblInvoiceLines_indv->fUnitCost=0;
//               $_btblInvoiceLines_indv->fLineDiscount=0; 
//               $_btblInvoiceLines_indv->iLineDiscountReasonID=0;
//               $_btblInvoiceLines_indv->iReturnReasonID=0; 
//               $_btblInvoiceLines_indv->fTaxRate=$tax_rate->TaxRate; 
//               $_btblInvoiceLines_indv->bIsSerialItem=0; 
//               $_btblInvoiceLines_indv->bIsWhseItem=1;
//               $_btblInvoiceLines_indv->fAddCost=0; 
//               $_btblInvoiceLines_indv->cTradeinItem='';
//               $_btblInvoiceLines_indv->iStockCodeID=$tstockid_ind; 
//               $_btblInvoiceLines_indv->iJobID=0;
//               $_btblInvoiceLines_indv->iWarehouseID=6;
//               $_btblInvoiceLines_indv->iTaxTypeID=$customer_details->ideftaxtypeid;
//               $_btblInvoiceLines_indv->iPriceListNameID=1;
//               $_btblInvoiceLines_indv->fQuantityLineTotIncl=$tlineamtincl_indv;
//               $_btblInvoiceLines_indv->fQuantityLineTotExcl=$tlineamtexcl_indv;
//               $_btblInvoiceLines_indv->fQuantityLineTotInclNoDisc=$tlineamtincl_indv;
//               $_btblInvoiceLines_indv->fQuantityLineTotExclNoDisc =$tlineamtexcl_indv; 
//               $_btblInvoiceLines_indv->fQuantityLineTaxAmount =0; 
//               $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDisc=0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotIncl =0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotExcl =0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDisc =0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDisc =0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTaxAmount =0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDisc =0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotIncl =0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotExcl =0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDisc=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDisc=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmount =0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDisc =0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotIncl=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotExcl =0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDisc=0;
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDisc=0;
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmount=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDisc=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotIncl=0;
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotExcl=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDisc=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDisc=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmount=0;
//               $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDisc=0; 
//               $_btblInvoiceLines_indv->fUnitPriceExclForeign=0; 
//               $_btblInvoiceLines_indv->fUnitPriceInclForeign=0; 
//               $_btblInvoiceLines_indv->fUnitCostForeign=0;
//               $_btblInvoiceLines_indv->fAddCostForeign=0;
//               $_btblInvoiceLines_indv->fQuantityLineTotInclForeign=0; 
//               $_btblInvoiceLines_indv->fQuantityLineTotExclForeign=0; 
//               $_btblInvoiceLines_indv->fQuantityLineTotInclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQuantityLineTotExclNoDiscForeign=0;      
//               $_btblInvoiceLines_indv->fQuantityLineTaxAmountForeign=0; 
//               $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotInclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotExclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountForeign=0;
//               $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotInclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotExclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountForeign=0; 
//               $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountForeign=0; 
//               $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotInclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotExclForeign=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountForeign=0; 
//               $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//               $_btblInvoiceLines_indv->iLineRepID=$customer_details->repid; 
//               $_btblInvoiceLines_indv->iLineProjectID=2; 
//               $_btblInvoiceLines_indv->iLedgerAccountID=0; 
//               $_btblInvoiceLines_indv->IModule=0;
//               $_btblInvoiceLines_indv->bChargeCom=1;
//               $_btblInvoiceLines_indv->bIsLotItem=0;
//               // $_btblInvoiceLines_indv->iLotID=0;
//               // $_btblInvoiceLines_indv->cLotNumber='';
//               // $_btblInvoiceLines_indv->dLotExpiryDate=null;
//               $_btblInvoiceLines_indv->iMFPID=0;
//               $_btblInvoiceLines_indv->iLineID=1;
//               $_btblInvoiceLines_indv->iLinkedLineID=0;
//               $_btblInvoiceLines_indv->fQtyLinkedUsed=null;
//               $_btblInvoiceLines_indv->fUnitPriceInclOrig=null;
//               $_btblInvoiceLines_indv->fUnitPriceExclOrig=Null;
//               $_btblInvoiceLines_indv->fUnitPriceInclForeignOrig=Null;
//               $_btblInvoiceLines_indv->fUnitPriceExclForeignOrig=0;
//               $_btblInvoiceLines_indv->iDeliveryMethodID=0;
//               $_btblInvoiceLines_indv->fQtyDeliver=0;
//               $_btblInvoiceLines_indv->dDeliveryDate=$today;
//               $_btblInvoiceLines_indv->iDeliveryStatus=0;
//               $_btblInvoiceLines_indv->fQtyForDelivery=0;
//               $_btblInvoiceLines_indv->bPromotionApplied=0;
//               $_btblInvoiceLines_indv->fPromotionPriceExcl=0;
//               $_btblInvoiceLines_indv->fPromotionPriceIncl=0;
//               $_btblInvoiceLines_indv->cPromotionCode=0;
//               $_btblInvoiceLines_indv->iSOLinkedPOLineID=0;
//               $_btblInvoiceLines_indv->fLength=0;
//               $_btblInvoiceLines_indv->fWidth=0;
//               $_btblInvoiceLines_indv->fHeight=0;
//               $_btblInvoiceLines_indv->iPieces=0;
//               $_btblInvoiceLines_indv->iPiecesToProcess=0;
//               $_btblInvoiceLines_indv->iPiecesLastProcess=0;
//               $_btblInvoiceLines_indv->iPiecesProcessed=0;
//               $_btblInvoiceLines_indv->iPiecesReserved=0;
//               $_btblInvoiceLines_indv->iPiecesDeliver=0;
//               $_btblInvoiceLines_indv->iPiecesForDelivery=0;
//               $_btblInvoiceLines_indv->fQuantityUR=1;
//               $_btblInvoiceLines_indv->fQtyChangeUR=1;
//               $_btblInvoiceLines_indv->fQtyToProcessUR=1;
//               $_btblInvoiceLines_indv->fQtyLastProcessUR=0;
//               $_btblInvoiceLines_indv->fQtyProcessedUR=0;
//               $_btblInvoiceLines_indv->fQtyReservedUR=0;
//               $_btblInvoiceLines_indv->fQtyReservedChangeUR=0;
//               $_btblInvoiceLines_indv->fQtyDeliverUR=0;
//               $_btblInvoiceLines_indv->fQtyForDeliveryUR=0;
//               $_btblInvoiceLines_indv->fQtyLinkedUsedUR=0;
//               $_btblInvoiceLines_indv->iPiecesLinkedUsed=0;
//               $_btblInvoiceLines_indv->iSalesWhseID=0;
//               $_btblInvoiceLines_indv->_btblInvoiceLines_iBranchID=0;
//               $_btblInvoiceLines_indv->udIDSOrdTxCMReadingDate='';
//               $_btblInvoiceLines_indv->uiIDSOrdTxCMPrevReading=$individual_color_consolidated->colpmr;
//               $_btblInvoiceLines_indv->uiIDSOrdTxCMCurrReading=$individual_color_consolidated->colcmr;
//               $_btblInvoiceLines_indv->ucIDSOrdTxCMMinVol='';
//               $_btblInvoiceLines_indv->ucIDSOrdTxCMRates='';
//               $_btblInvoiceLines_indv->ucIDSOrdTxCMServiceAsset=$individual_color_consolidated->ucSASerialNo;
//               $_btblInvoiceLines_indv->ucIDSOrdTxCMMeterType="BILLCOL"; 
//               $_btblInvoiceLines_indv->save();    
    

           
//              }         



//       }
//       // Individual items SCAN

//       if(!empty($scan_counter_elapse)){
//         $tlineamtexcl_indv = 0;
//         $tlineamttax = 0;
//         $tlineamtincl_indv = 0;
//         $tfclineamtexcl_indv = 0;
//         $tfclineamttax_indv = 0;
//         $tfclineamtincl_indv = 0;
//         $tlinenote_indv = "";
//         $tlinedesc_indv = "";
//         $tstockid_ind = 28587;
//         $tvolstr = "";
//         $tratestr = "";

//         $individual_scan_consolidated = DB::table('_bvARAccountsFull As cl')
//         ->where('DCLink',$customer_details->dclink)
//         ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//         ->whereYear('ReadingDate', '=', $today->year)
//         ->whereMonth('ReadingDate', '=', $today->month)

//         ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset','cmr.ScnPMR','cmr.ScnCMR','sa.cDescription','sa.ucSASerialNo')
//         ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//         ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')     
//         ->get();
        

//         foreach($individual_scan_consolidated as $data){

//           $_btblInvoiceLines_indv = new _btblInvoiceLines;
//           $_btblInvoiceLines_indv->iInvoiceID =$invoice_id->autoindex;
//           $_btblInvoiceLines_indv->iOrigLineID =0;
//           $_btblInvoiceLines_indv->iGrvLineID =0;
//           $_btblInvoiceLines_indv->iLineDocketMode =0; 
//           $_btblInvoiceLines_indv->cDescription =$data->cDescription; 
//           $_btblInvoiceLines_indv->iUnitsOfMeasureStockingID=0; 
//           $_btblInvoiceLines_indv->iUnitsOfMeasureCategoryID=0;
//           $_btblInvoiceLines_indv->iUnitsOfMeasureID=0;
//           $_btblInvoiceLines_indv->fQuantity=1;
//           $_btblInvoiceLines_indv->fQtyChange=1;
//           $_btblInvoiceLines_indv->fQtyToProcess=1; 
//           $_btblInvoiceLines_indv->fQtyLastProcess=0; 
//           $_btblInvoiceLines_indv->fQtyProcessed =0; 
//           $_btblInvoiceLines_indv->fQtyReserved=0; 
//           $_btblInvoiceLines_indv->fQtyReservedChange =0;
//           $_btblInvoiceLines_indv->cLineNotes=$tlinenote_indv; 
//           $_btblInvoiceLines_indv->fUnitPriceExcl=$tlineamtexcl_indv; 
//           $_btblInvoiceLines_indv->fUnitPriceIncl=$tlineamtincl_indv;
//           $_btblInvoiceLines_indv->iUnitPriceOverrideReasonID=0; 
//           $_btblInvoiceLines_indv->fUnitCost=0;
//           $_btblInvoiceLines_indv->fLineDiscount=0; 
//           $_btblInvoiceLines_indv->iLineDiscountReasonID=0;
//           $_btblInvoiceLines_indv->iReturnReasonID=0; 
//           $_btblInvoiceLines_indv->fTaxRate=$tax_rate->TaxRate; 
//           $_btblInvoiceLines_indv->bIsSerialItem=0; 
//           $_btblInvoiceLines_indv->bIsWhseItem=1;
//           $_btblInvoiceLines_indv->fAddCost=0; 
//           $_btblInvoiceLines_indv->cTradeinItem='';
//           $_btblInvoiceLines_indv->iStockCodeID=$tstockid_ind; 
//           $_btblInvoiceLines_indv->iJobID=0;
//           $_btblInvoiceLines_indv->iWarehouseID=6;
//           $_btblInvoiceLines_indv->iTaxTypeID=$customer_details->ideftaxtypeid;
//           $_btblInvoiceLines_indv->iPriceListNameID=1;
//           $_btblInvoiceLines_indv->fQuantityLineTotIncl=$tlineamtincl_indv;
//           $_btblInvoiceLines_indv->fQuantityLineTotExcl=$tlineamtexcl_indv;
//           $_btblInvoiceLines_indv->fQuantityLineTotInclNoDisc=$tlineamtincl_indv;
//           $_btblInvoiceLines_indv->fQuantityLineTotExclNoDisc =$tlineamtexcl_indv; 
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmount =0; 
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotIncl =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExcl =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmount =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotIncl =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExcl =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmount =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotIncl=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExcl =0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDisc=0;
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDisc=0;
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmount=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotIncl=0;
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExcl=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmount=0;
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_indv->fUnitPriceExclForeign=0; 
//           $_btblInvoiceLines_indv->fUnitPriceInclForeign=0; 
//           $_btblInvoiceLines_indv->fUnitCostForeign=0;
//           $_btblInvoiceLines_indv->fAddCostForeign=0;
//           $_btblInvoiceLines_indv->fQuantityLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTotExclNoDiscForeign=0;      
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountForeign=0;
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->iLineRepID=$customer_details->repid; 
//           $_btblInvoiceLines_indv->iLineProjectID=2; 
//           $_btblInvoiceLines_indv->iLedgerAccountID=0; 
//           $_btblInvoiceLines_indv->IModule=0;
//           $_btblInvoiceLines_indv->bChargeCom=1;
//           $_btblInvoiceLines_indv->bIsLotItem=0;
//           // $_btblInvoiceLines_indv->iLotID=0;
//           // $_btblInvoiceLines_indv->cLotNumber='';
//           // $_btblInvoiceLines_indv->dLotExpiryDate=null;
//           $_btblInvoiceLines_indv->iMFPID=0;
//           $_btblInvoiceLines_indv->iLineID=1;
//           $_btblInvoiceLines_indv->iLinkedLineID=0;
//           $_btblInvoiceLines_indv->fQtyLinkedUsed=null;
//           $_btblInvoiceLines_indv->fUnitPriceInclOrig=null;
//           $_btblInvoiceLines_indv->fUnitPriceExclOrig=Null;
//           $_btblInvoiceLines_indv->fUnitPriceInclForeignOrig=Null;
//           $_btblInvoiceLines_indv->fUnitPriceExclForeignOrig=0;
//           $_btblInvoiceLines_indv->iDeliveryMethodID=0;
//           $_btblInvoiceLines_indv->fQtyDeliver=0;
//           $_btblInvoiceLines_indv->dDeliveryDate=$today;
//           $_btblInvoiceLines_indv->iDeliveryStatus=0;
//           $_btblInvoiceLines_indv->fQtyForDelivery=0;
//           $_btblInvoiceLines_indv->bPromotionApplied=0;
//           $_btblInvoiceLines_indv->fPromotionPriceExcl=0;
//           $_btblInvoiceLines_indv->fPromotionPriceIncl=0;
//           $_btblInvoiceLines_indv->cPromotionCode=0;
//           $_btblInvoiceLines_indv->iSOLinkedPOLineID=0;
//           $_btblInvoiceLines_indv->fLength=0;
//           $_btblInvoiceLines_indv->fWidth=0;
//           $_btblInvoiceLines_indv->fHeight=0;
//           $_btblInvoiceLines_indv->iPieces=0;
//           $_btblInvoiceLines_indv->iPiecesToProcess=0;
//           $_btblInvoiceLines_indv->iPiecesLastProcess=0;
//           $_btblInvoiceLines_indv->iPiecesProcessed=0;
//           $_btblInvoiceLines_indv->iPiecesReserved=0;
//           $_btblInvoiceLines_indv->iPiecesDeliver=0;
//           $_btblInvoiceLines_indv->iPiecesForDelivery=0;
//           $_btblInvoiceLines_indv->fQuantityUR=1;
//           $_btblInvoiceLines_indv->fQtyChangeUR=1;
//           $_btblInvoiceLines_indv->fQtyToProcessUR=1;
//           $_btblInvoiceLines_indv->fQtyLastProcessUR=0;
//           $_btblInvoiceLines_indv->fQtyProcessedUR=0;
//           $_btblInvoiceLines_indv->fQtyReservedUR=0;
//           $_btblInvoiceLines_indv->fQtyReservedChangeUR=0;
//           $_btblInvoiceLines_indv->fQtyDeliverUR=0;
//           $_btblInvoiceLines_indv->fQtyForDeliveryUR=0;
//           $_btblInvoiceLines_indv->fQtyLinkedUsedUR=0;
//           $_btblInvoiceLines_indv->iPiecesLinkedUsed=0;
//           $_btblInvoiceLines_indv->iSalesWhseID=0;
//           $_btblInvoiceLines_indv->_btblInvoiceLines_iBranchID=0;
//           $_btblInvoiceLines_indv->udIDSOrdTxCMReadingDate='';
//           $_btblInvoiceLines_indv->uiIDSOrdTxCMPrevReading=$data->ScnPMR;
//           $_btblInvoiceLines_indv->uiIDSOrdTxCMCurrReading=$data->ScnCMR;
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMMinVol='';
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMRates='';
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMServiceAsset=$data->ucSASerialNo;
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMMeterType="BILLSCN"; 
//           $_btblInvoiceLines_indv->save();


//            }

//       }
      

//       if(!empty($a3Mono_counter_elapse)){
//         // individual items A3mono
//         $tlineamtexcl_indv = 0;
//         $tlineamttax = 0;
//         $tlineamtincl_indv = 0;
//         $tfclineamtexcl_indv = 0;
//         $tfclineamttax_indv = 0;
//         $tfclineamtincl_indv = 0;
//         $tlinenote_indv = "";
//         $tlinedesc_indv = "";
//         $tstockid_ind = 28587;
//         $tvolstr = "";
//         $tratestr = "";

//         $individual_a3mono_consolidated = DB::table('_bvARAccountsFull As cl')
//         ->where('DCLink',$customer_details->dclink)
//         ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//         ->whereYear('ReadingDate', '=', $today->year)
//         ->whereMonth('ReadingDate', '=', $today->month)

//         ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset','cmr.A3MPMR','cmr.A3MCMR','sa.cDescription','sa.ucSASerialNo')
//         ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//         ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')     
//         ->get();

        

//         foreach($individual_a3mono_consolidated as $data){
//           $_btblInvoiceLines_indv = new _btblInvoiceLines;
//           $_btblInvoiceLines_indv->iInvoiceID =$invoice_id->autoindex;
//           $_btblInvoiceLines_indv->iOrigLineID =0;
//           $_btblInvoiceLines_indv->iGrvLineID =0;
//           $_btblInvoiceLines_indv->iLineDocketMode =0; 
//           $_btblInvoiceLines_indv->cDescription =$data->cDescription; 
//           $_btblInvoiceLines_indv->iUnitsOfMeasureStockingID=0; 
//           $_btblInvoiceLines_indv->iUnitsOfMeasureCategoryID=0;
//           $_btblInvoiceLines_indv->iUnitsOfMeasureID=0;
//           $_btblInvoiceLines_indv->fQuantity=1;
//           $_btblInvoiceLines_indv->fQtyChange=1;
//           $_btblInvoiceLines_indv->fQtyToProcess=1; 
//           $_btblInvoiceLines_indv->fQtyLastProcess=0; 
//           $_btblInvoiceLines_indv->fQtyProcessed =0; 
//           $_btblInvoiceLines_indv->fQtyReserved=0; 
//           $_btblInvoiceLines_indv->fQtyReservedChange =0;
//           $_btblInvoiceLines_indv->cLineNotes=$tlinenote_indv; 
//           $_btblInvoiceLines_indv->fUnitPriceExcl=$tlineamtexcl_indv; 
//           $_btblInvoiceLines_indv->fUnitPriceIncl=$tlineamtincl_indv;
//           $_btblInvoiceLines_indv->iUnitPriceOverrideReasonID=0; 
//           $_btblInvoiceLines_indv->fUnitCost=0;
//           $_btblInvoiceLines_indv->fLineDiscount=0; 
//           $_btblInvoiceLines_indv->iLineDiscountReasonID=0;
//           $_btblInvoiceLines_indv->iReturnReasonID=0; 
//           $_btblInvoiceLines_indv->fTaxRate=$tax_rate->TaxRate; 
//           $_btblInvoiceLines_indv->bIsSerialItem=0; 
//           $_btblInvoiceLines_indv->bIsWhseItem=1;
//           $_btblInvoiceLines_indv->fAddCost=0; 
//           $_btblInvoiceLines_indv->cTradeinItem='';
//           $_btblInvoiceLines_indv->iStockCodeID=$tstockid_ind; 
//           $_btblInvoiceLines_indv->iJobID=0;
//           $_btblInvoiceLines_indv->iWarehouseID=6;
//           $_btblInvoiceLines_indv->iTaxTypeID=$customer_details->ideftaxtypeid;
//           $_btblInvoiceLines_indv->iPriceListNameID=1;
//           $_btblInvoiceLines_indv->fQuantityLineTotIncl=$tlineamtincl_indv;
//           $_btblInvoiceLines_indv->fQuantityLineTotExcl=$tlineamtexcl_indv;
//           $_btblInvoiceLines_indv->fQuantityLineTotInclNoDisc=$tlineamtincl_indv;
//           $_btblInvoiceLines_indv->fQuantityLineTotExclNoDisc =$tlineamtexcl_indv; 
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmount =0; 
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotIncl =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExcl =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmount =0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotIncl =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExcl =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmount =0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDisc =0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotIncl=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExcl =0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDisc=0;
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDisc=0;
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmount=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotIncl=0;
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExcl=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDisc=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmount=0;
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_indv->fUnitPriceExclForeign=0; 
//           $_btblInvoiceLines_indv->fUnitPriceInclForeign=0; 
//           $_btblInvoiceLines_indv->fUnitCostForeign=0;
//           $_btblInvoiceLines_indv->fAddCostForeign=0;
//           $_btblInvoiceLines_indv->fQuantityLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTotExclNoDiscForeign=0;      
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountForeign=0;
//           $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotInclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExclForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_indv->iLineRepID=$customer_details->repid; 
//           $_btblInvoiceLines_indv->iLineProjectID=2; 
//           $_btblInvoiceLines_indv->iLedgerAccountID=0; 
//           $_btblInvoiceLines_indv->IModule=0;
//           $_btblInvoiceLines_indv->bChargeCom=1;
//           $_btblInvoiceLines_indv->bIsLotItem=0;
//           // $_btblInvoiceLines_indv->iLotID=0;
//           // $_btblInvoiceLines_indv->cLotNumber='';
//           // $_btblInvoiceLines_indv->dLotExpiryDate=null;
//           $_btblInvoiceLines_indv->iMFPID=0;
//           $_btblInvoiceLines_indv->iLineID=1;
//           $_btblInvoiceLines_indv->iLinkedLineID=0;
//           $_btblInvoiceLines_indv->fQtyLinkedUsed=null;
//           $_btblInvoiceLines_indv->fUnitPriceInclOrig=null;
//           $_btblInvoiceLines_indv->fUnitPriceExclOrig=Null;
//           $_btblInvoiceLines_indv->fUnitPriceInclForeignOrig=Null;
//           $_btblInvoiceLines_indv->fUnitPriceExclForeignOrig=0;
//           $_btblInvoiceLines_indv->iDeliveryMethodID=0;
//           $_btblInvoiceLines_indv->fQtyDeliver=0;
//           $_btblInvoiceLines_indv->dDeliveryDate=$today;
//           $_btblInvoiceLines_indv->iDeliveryStatus=0;
//           $_btblInvoiceLines_indv->fQtyForDelivery=0;
//           $_btblInvoiceLines_indv->bPromotionApplied=0;
//           $_btblInvoiceLines_indv->fPromotionPriceExcl=0;
//           $_btblInvoiceLines_indv->fPromotionPriceIncl=0;
//           $_btblInvoiceLines_indv->cPromotionCode=0;
//           $_btblInvoiceLines_indv->iSOLinkedPOLineID=0;
//           $_btblInvoiceLines_indv->fLength=0;
//           $_btblInvoiceLines_indv->fWidth=0;
//           $_btblInvoiceLines_indv->fHeight=0;
//           $_btblInvoiceLines_indv->iPieces=0;
//           $_btblInvoiceLines_indv->iPiecesToProcess=0;
//           $_btblInvoiceLines_indv->iPiecesLastProcess=0;
//           $_btblInvoiceLines_indv->iPiecesProcessed=0;
//           $_btblInvoiceLines_indv->iPiecesReserved=0;
//           $_btblInvoiceLines_indv->iPiecesDeliver=0;
//           $_btblInvoiceLines_indv->iPiecesForDelivery=0;
//           $_btblInvoiceLines_indv->fQuantityUR=1;
//           $_btblInvoiceLines_indv->fQtyChangeUR=1;
//           $_btblInvoiceLines_indv->fQtyToProcessUR=1;
//           $_btblInvoiceLines_indv->fQtyLastProcessUR=0;
//           $_btblInvoiceLines_indv->fQtyProcessedUR=0;
//           $_btblInvoiceLines_indv->fQtyReservedUR=0;
//           $_btblInvoiceLines_indv->fQtyReservedChangeUR=0;
//           $_btblInvoiceLines_indv->fQtyDeliverUR=0;
//           $_btblInvoiceLines_indv->fQtyForDeliveryUR=0;
//           $_btblInvoiceLines_indv->fQtyLinkedUsedUR=0;
//           $_btblInvoiceLines_indv->iPiecesLinkedUsed=0;
//           $_btblInvoiceLines_indv->iSalesWhseID=0;
//           $_btblInvoiceLines_indv->_btblInvoiceLines_iBranchID=0;
//           $_btblInvoiceLines_indv->udIDSOrdTxCMReadingDate='';
//           $_btblInvoiceLines_indv->uiIDSOrdTxCMPrevReading=$data->A3MPMR;
//           $_btblInvoiceLines_indv->uiIDSOrdTxCMCurrReading=$data->A3MCMR;
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMMinVol='';
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMRates='';
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMServiceAsset=$data->ucSASerialNo;
//           $_btblInvoiceLines_indv->ucIDSOrdTxCMMeterType="BILLA3MON"; 
//           $_btblInvoiceLines_indv->save();

//         }

//         if(!empty($a3color_counter_elapse)){

          
//           // individual items A3color
//           $tlineamtexcl_indv = 0;
//           $tlineamttax = 0;
//           $tlineamtincl_indv = 0;
//           $tfclineamtexcl_indv = 0;
//           $tfclineamttax_indv = 0;
//           $tfclineamtincl_indv = 0;
//           $tlinenote_indv = "";
//           $tlinedesc_indv = "";
//           $tstockid_ind = 28587;
//           $tvolstr = "";
//           $tratestr = "";


//           $individual_a3color_consolidated = DB::table('_bvARAccountsFull As cl')
//           ->where('DCLink',$customer_details->dclink)
//           ->where('sa.ucSABillingAsset', '=',$value['ucSABillingAsset']) 
//           ->whereYear('ReadingDate', '=', $today->year)
//           ->whereMonth('ReadingDate', '=', $today->month)
  
//           ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.ucSABillingAsset','cmr.A3CPMR','cmr.A3CCMR','sa.cDescription','sa.ucSASerialNo')
//           ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//           ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')     
//           ->get();
         


//           foreach($individual_a3color_consolidated as $data){
//             $_btblInvoiceLines_indv = new _btblInvoiceLines;
//             $_btblInvoiceLines_indv->iInvoiceID =$invoice_id->autoindex;
//             $_btblInvoiceLines_indv->iOrigLineID =0;
//             $_btblInvoiceLines_indv->iGrvLineID =0;
//             $_btblInvoiceLines_indv->iLineDocketMode =0; 
//             $_btblInvoiceLines_indv->cDescription =$data->cDescription; 
//             $_btblInvoiceLines_indv->iUnitsOfMeasureStockingID=0; 
//             $_btblInvoiceLines_indv->iUnitsOfMeasureCategoryID=0;
//             $_btblInvoiceLines_indv->iUnitsOfMeasureID=0;
//             $_btblInvoiceLines_indv->fQuantity=1;
//             $_btblInvoiceLines_indv->fQtyChange=1;
//             $_btblInvoiceLines_indv->fQtyToProcess=1; 
//             $_btblInvoiceLines_indv->fQtyLastProcess=0; 
//             $_btblInvoiceLines_indv->fQtyProcessed =0; 
//             $_btblInvoiceLines_indv->fQtyReserved=0; 
//             $_btblInvoiceLines_indv->fQtyReservedChange =0;
//             $_btblInvoiceLines_indv->cLineNotes=$tlinenote_indv; 
//             $_btblInvoiceLines_indv->fUnitPriceExcl=$tlineamtexcl_indv; 
//             $_btblInvoiceLines_indv->fUnitPriceIncl=$tlineamtincl_indv;
//             $_btblInvoiceLines_indv->iUnitPriceOverrideReasonID=0; 
//             $_btblInvoiceLines_indv->fUnitCost=0;
//             $_btblInvoiceLines_indv->fLineDiscount=0; 
//             $_btblInvoiceLines_indv->iLineDiscountReasonID=0;
//             $_btblInvoiceLines_indv->iReturnReasonID=0; 
//             $_btblInvoiceLines_indv->fTaxRate=$tax_rate->TaxRate; 
//             $_btblInvoiceLines_indv->bIsSerialItem=0; 
//             $_btblInvoiceLines_indv->bIsWhseItem=1;
//             $_btblInvoiceLines_indv->fAddCost=0; 
//             $_btblInvoiceLines_indv->cTradeinItem='';
//             $_btblInvoiceLines_indv->iStockCodeID=$tstockid_ind; 
//             $_btblInvoiceLines_indv->iJobID=0;
//             $_btblInvoiceLines_indv->iWarehouseID=6;
//             $_btblInvoiceLines_indv->iTaxTypeID=$customer_details->ideftaxtypeid;
//             $_btblInvoiceLines_indv->iPriceListNameID=1;
//             $_btblInvoiceLines_indv->fQuantityLineTotIncl=$tlineamtincl_indv;
//             $_btblInvoiceLines_indv->fQuantityLineTotExcl=$tlineamtexcl_indv;
//             $_btblInvoiceLines_indv->fQuantityLineTotInclNoDisc=$tlineamtincl_indv;
//             $_btblInvoiceLines_indv->fQuantityLineTotExclNoDisc =$tlineamtexcl_indv; 
//             $_btblInvoiceLines_indv->fQuantityLineTaxAmount =0; 
//             $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotIncl =0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotExcl =0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDisc =0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDisc =0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTaxAmount =0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDisc =0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotIncl =0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotExcl =0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDisc=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDisc=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmount =0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDisc =0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotIncl=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotExcl =0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDisc=0;
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDisc=0;
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmount=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotIncl=0;
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotExcl=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDisc=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDisc=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmount=0;
//             $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDisc=0; 
//             $_btblInvoiceLines_indv->fUnitPriceExclForeign=0; 
//             $_btblInvoiceLines_indv->fUnitPriceInclForeign=0; 
//             $_btblInvoiceLines_indv->fUnitCostForeign=0;
//             $_btblInvoiceLines_indv->fAddCostForeign=0;
//             $_btblInvoiceLines_indv->fQuantityLineTotInclForeign=0; 
//             $_btblInvoiceLines_indv->fQuantityLineTotExclForeign=0; 
//             $_btblInvoiceLines_indv->fQuantityLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQuantityLineTotExclNoDiscForeign=0;      
//             $_btblInvoiceLines_indv->fQuantityLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_indv->fQuantityLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotInclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotExclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountForeign=0;
//             $_btblInvoiceLines_indv->fQtyChangeLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotInclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotExclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_indv->fQtyToProcessLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_indv->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotInclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotExclForeign=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotInclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTotExclNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountForeign=0; 
//             $_btblInvoiceLines_indv->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//             $_btblInvoiceLines_indv->iLineRepID=$customer_details->repid; 
//             $_btblInvoiceLines_indv->iLineProjectID=2; 
//             $_btblInvoiceLines_indv->iLedgerAccountID=0; 
//             $_btblInvoiceLines_indv->IModule=0;
//             $_btblInvoiceLines_indv->bChargeCom=1;
//             $_btblInvoiceLines_indv->bIsLotItem=0;
//             // $_btblInvoiceLines_indv->iLotID=0;
//             // $_btblInvoiceLines_indv->cLotNumber='';
//             // $_btblInvoiceLines_indv->dLotExpiryDate=null;
//             $_btblInvoiceLines_indv->iMFPID=0;
//             $_btblInvoiceLines_indv->iLineID=1;
//             $_btblInvoiceLines_indv->iLinkedLineID=0;
//             $_btblInvoiceLines_indv->fQtyLinkedUsed=null;
//             $_btblInvoiceLines_indv->fUnitPriceInclOrig=null;
//             $_btblInvoiceLines_indv->fUnitPriceExclOrig=Null;
//             $_btblInvoiceLines_indv->fUnitPriceInclForeignOrig=Null;
//             $_btblInvoiceLines_indv->fUnitPriceExclForeignOrig=0;
//             $_btblInvoiceLines_indv->iDeliveryMethodID=0;
//             $_btblInvoiceLines_indv->fQtyDeliver=0;
//             $_btblInvoiceLines_indv->dDeliveryDate=$today;
//             $_btblInvoiceLines_indv->iDeliveryStatus=0;
//             $_btblInvoiceLines_indv->fQtyForDelivery=0;
//             $_btblInvoiceLines_indv->bPromotionApplied=0;
//             $_btblInvoiceLines_indv->fPromotionPriceExcl=0;
//             $_btblInvoiceLines_indv->fPromotionPriceIncl=0;
//             $_btblInvoiceLines_indv->cPromotionCode=0;
//             $_btblInvoiceLines_indv->iSOLinkedPOLineID=0;
//             $_btblInvoiceLines_indv->fLength=0;
//             $_btblInvoiceLines_indv->fWidth=0;
//             $_btblInvoiceLines_indv->fHeight=0;
//             $_btblInvoiceLines_indv->iPieces=0;
//             $_btblInvoiceLines_indv->iPiecesToProcess=0;
//             $_btblInvoiceLines_indv->iPiecesLastProcess=0;
//             $_btblInvoiceLines_indv->iPiecesProcessed=0;
//             $_btblInvoiceLines_indv->iPiecesReserved=0;
//             $_btblInvoiceLines_indv->iPiecesDeliver=0;
//             $_btblInvoiceLines_indv->iPiecesForDelivery=0;
//             $_btblInvoiceLines_indv->fQuantityUR=1;
//             $_btblInvoiceLines_indv->fQtyChangeUR=1;
//             $_btblInvoiceLines_indv->fQtyToProcessUR=1;
//             $_btblInvoiceLines_indv->fQtyLastProcessUR=0;
//             $_btblInvoiceLines_indv->fQtyProcessedUR=0;
//             $_btblInvoiceLines_indv->fQtyReservedUR=0;
//             $_btblInvoiceLines_indv->fQtyReservedChangeUR=0;
//             $_btblInvoiceLines_indv->fQtyDeliverUR=0;
//             $_btblInvoiceLines_indv->fQtyForDeliveryUR=0;
//             $_btblInvoiceLines_indv->fQtyLinkedUsedUR=0;
//             $_btblInvoiceLines_indv->iPiecesLinkedUsed=0;
//             $_btblInvoiceLines_indv->iSalesWhseID=0;
//             $_btblInvoiceLines_indv->_btblInvoiceLines_iBranchID=0;
//             $_btblInvoiceLines_indv->udIDSOrdTxCMReadingDate='';
//             $_btblInvoiceLines_indv->uiIDSOrdTxCMPrevReading=$data->A3CPMR;
//             $_btblInvoiceLines_indv->uiIDSOrdTxCMCurrReading=$data->A3CCMR;
//             $_btblInvoiceLines_indv->ucIDSOrdTxCMMinVol='';
//             $_btblInvoiceLines_indv->ucIDSOrdTxCMRates='';
//             $_btblInvoiceLines_indv->ucIDSOrdTxCMServiceAsset=$data->ucSASerialNo;
//             $_btblInvoiceLines_indv->ucIDSOrdTxCMMeterType="BILLA3COL"; 
//             $_btblInvoiceLines_indv->save();

//           }


//         }


//       }

//       $total_inv =DB::table('_btblinvoicelines as lni')
//       ->where('iinvoiceid',$invoice_id->autoindex)
//       ->select(DB::raw('sum(lni.fquantitylinetotincl) as inclamt'),
//             DB::raw('sum(lni.fquantitylinetotexcl) as exclamt'),
//             DB::raw('sum(lni.fquantitylinetaxamount) as taxamt'),
//             DB::raw('sum(lni.fquantitylinetotinclforeign) as fcinclamt'),
//             DB::raw('sum(lni.fquantitylinetotexclforeign) as fcexclamt'),
//             DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt'),
//             DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt')   
      
//       )
//       ->first();

    
//       $tinvamtexcl =$total_inv->exclamt;
//       $tinvamtincl =$total_inv->inclamt;
//       $tinvamttax = $total_inv->taxamt;
//       $tordamtexcl = $tinvamtexcl;
//       $tordamtincl =  $tinvamtincl;
//       $tordamttax = $tinvamttax;
//       $tfcinvamtexcl =$total_inv->fcexclamt;
//       $tfcinvamtincl =$total_inv->fcinclamt;
//       $tfcinvamttax =$total_inv->fctaxamt;
//       $tfcordamtexcl = $tfcinvamtexcl;
//       $tfcordamtincl = $tfcinvamtincl;
//       $tfcordamttax = $tfcinvamttax;

//       $inv_update =InvNum::find($invoice_id->autoindex);
      
//       $inv_update->InvTotExclDEx =$tinvamtexcl;
//       $inv_update->InvTotTaxDEx =$tinvamttax;
//       $inv_update->InvTotInclDEx =$tinvamtincl;
//       $inv_update->InvTotExcl =$tinvamtexcl;
//       $inv_update->InvTotTax =$tinvamttax;
//       $inv_update->InvTotIncl =$tinvamtincl;
//       $inv_update->OrdTotExclDEx=$tordamtexcl;
//       $inv_update->OrdTotTaxDEx=$tordamttax;
//       $inv_update->OrdTotInclDEx=$tordamtincl;
//       $inv_update->OrdTotExcl=$tordamtexcl;
//       $inv_update->OrdTotTax=$tordamttax;
//       $inv_update->OrdTotIncl=$tordamtincl;
//       $inv_update->fInvTotExclDExForeign=$tfcinvamtexcl;
//       $inv_update->fInvTotTaxDExForeign=$tfcinvamttax;
//       $inv_update->fInvTotInclDExForeign=$tfcinvamtincl;
//       $inv_update->fInvTotExclForeign=$tfcinvamtexcl;
//       $inv_update->fInvTotTaxForeign=$tfcinvamttax;
//       $inv_update->fInvTotInclForeign=$tfcinvamtincl;
//       $inv_update->fOrdTotExclDExForeign=$tfcordamtexcl;
//       $inv_update->fOrdTotTaxDExForeign=$tfcordamttax;
//       $inv_update->fOrdTotInclDExForeign=$tfcordamtincl;
//       $inv_update->fOrdTotExclForeign=$tfcordamtexcl;
//       $inv_update->fOrdTotTaxForeign=$tfcordamttax;
//       $inv_update->fOrdTotInclForeign=$tfcordamtincl;
//       $inv_update->InvTotInclExRounding=$tinvamtincl;
//       $inv_update->OrdTotInclExRounding=$tordamtincl;
//       $inv_update->fInvTotInclForeignExRounding=$tfcinvamtincl;
//       $inv_update->fOrdTotInclForeignExRounding=$tfcordamtincl;
//       $inv_update->save();  
//     }






//     // LAST ONE
//     // TYPE 4 INVOICE SEPARATE NON CON ASSET

  
//     elseif($billing_type =='S' && !$con_asset){
         
//     $customer  = $value['account'];
    
//     $customer_details =Client::where('Account',$customer)
//     ->select('dclink','name','post1','post2','post3','post4','email',DB::raw("(CASE WHEN repid = null THEN 0 ELSE repid END) AS repid"),
//     DB::raw("(CASE WHEN icurrencyid = null THEN 0 ELSE icurrencyid END) AS icurrencyid"),'ideftaxtypeid'
//     )
//     ->first();


  

//     // Get exchangerate
    
//     $today=Carbon::parse($request->billing_date);
    
//     $echange_rate =DB::table('currencyhist')
//     ->select('fbuyrate')
//     ->where('icurrencyid',$customer_details->icurrencyid)
//     ->whereRaw('idCurrencyHist in (select max(idCurrencyHist) from currencyhist group by (iCurrencyID))')  
//     ->first();

//       $exc_rate =$echange_rate->fbuyrate  ?? '0';

  
//     // Get tax rate
   
//     $tax_rate =DB::table('taxrate')
//     ->where('idtaxrate',$customer_details->ideftaxtypeid)
//     ->select('TaxRate')
//     ->first();

//    //get po number
//     $po_number =DB::table('_etblDocProfiles')
//     ->where('idDocProfiles',60)
//     ->select('cautonumprefix','iautonumpadlength','inextautonum')
//     ->first();
//     $update =DB::table('_etblDocProfiles')
//     ->where('idDocProfiles',60)
//     ->update([
//     'inextautonum' => $po_number->inextautonum +1
//     ]);

// //  pad po number no to reach 7 integers
//   $padded_num = str_pad( $po_number->inextautonum, $po_number->iautonumpadlength , '0', STR_PAD_LEFT); 

//   //order Number
//   $tsonum = $po_number->cautonumprefix.$padded_num;



//   $month =$today->format('F');

//   $tinvamtexcl = 0;
//   $tinvamtincl = 0;
//   $tinvamttax = 0;
//   $tordamtexcl = 0;
//   $tordamtincl = 0;
//   $tordamttax = 0;
//   $tfcinvamtexcl = 0;
//   $tfcinvamtincl = 0;
//   $tfcinvamttax = 0;
//   $tfcordamtexcl = 0;
//   $tfcordamtincl = 0;
//   $tfcordamttax = 0;
//   $textorderno = $tsonum;
//   $torddesc = "Meter Reading Billing for $month $today->year";
//   $tsdate = $today;
                  

  
//   // generate so order

//   $new_so_order =new InvNum;
//   $new_so_order->DocType =4;
//   $new_so_order->DocVersion=1;
//   $new_so_order->DocState =1;
//   $new_so_order->DocFlag=0;
//   $new_so_order->OrigDocID =0;
//   $new_so_order->InvNumber='';
//   $new_so_order->GrvNumber ='';
//   $new_so_order->GrvID =0;
//   $new_so_order->AccountID=$customer_details->dclink;    
//   $new_so_order->Description =$torddesc;
//   $new_so_order->InvDate =$tsdate;
//   $new_so_order->OrderDate =$tsdate;
//   $new_so_order->DueDate =$tsdate;
//   $new_so_order->DeliveryDate =$tsdate;
//   $new_so_order->TaxInclusive =0;
//   $new_so_order->Email_Sent =1;
//   $new_so_order->Address1 =$customer_details->post1;
//   $new_so_order->Address2 =$customer_details->post2;
//   $new_so_order->Address3 =$customer_details->post3;
//   $new_so_order->Address4 =$customer_details->post4;
//   $new_so_order->Address5 ='';
//   $new_so_order->Address6 ='';
//   $new_so_order->PAddress1 ='';
//   $new_so_order->PAddress2 ='';
//   $new_so_order->PAddress3 ='';
//   $new_so_order->PAddress4 ='';
//   $new_so_order->PAddress5 ='';
//   $new_so_order->PAddress6 ='';
//   $new_so_order->DelMethodID =0;
//   $new_so_order->DocRepID =$customer_details->repid;
//   $new_so_order->OrderNum =$tsonum;
//   $new_so_order->DeliveryNote ='';
//   $new_so_order->InvDisc =0;
//   $new_so_order->InvDiscReasonID =0;
//   $new_so_order->Message1 ='';
//   $new_so_order->Message2 ='';
//   $new_so_order->Message3 ='';
//   $new_so_order->ProjectID =2;
//   $new_so_order->TillID =0;
//   $new_so_order->POSAmntTendered =0;
//   $new_so_order->POSChange =0;
//   $new_so_order->GrvSplitFixedCost =0;
//   $new_so_order->GrvSplitFixedAmnt =0;
//   $new_so_order->OrderStatusID =0;
//   $new_so_order->OrderPriorityID=4;
//   $new_so_order->ExtOrderNum =$textorderno;
//   $new_so_order->ForeignCurrencyID =$customer_details->icurrencyid;
//   $new_so_order->InvDiscAmnt =0; 
//   $new_so_order->InvDiscAmntEx =0;
//   $new_so_order->InvTotExclDEx =$tinvamtexcl;
//   $new_so_order->InvTotTaxDEx =$tinvamttax;
//   $new_so_order->InvTotInclDEx =$tinvamtincl;
//   $new_so_order->InvTotExcl =$tinvamtexcl;
//   $new_so_order->InvTotTax =$tinvamttax;
//   $new_so_order->InvTotIncl =$tinvamtincl;
//   $new_so_order->OrdDiscAmnt = 0;
//   $new_so_order->OrdDiscAmntEx =0;
//   $new_so_order->OrdTotExclDEx =$tordamtexcl;
//   $new_so_order->OrdTotTaxDEx =$tordamttax;
//   $new_so_order->OrdTotInclDEx =$tordamtincl;
//   $new_so_order->OrdTotExcl =$tordamtexcl;
//   $new_so_order->OrdTotTax =$tordamttax;
//   $new_so_order->OrdTotIncl =$tordamtincl;
//   $new_so_order->bUseFixedPrices =0;
//   $new_so_order->iDocPrinted =0;
//   $new_so_order->iINVNUMAgentID =1;
//   $new_so_order->fExchangeRate =$exc_rate;
//   $new_so_order->fGrvSplitFixedAmntForeign =0;
//   $new_so_order->fInvDiscAmntForeign =0;
//   $new_so_order->fInvDiscAmntExForeign =0;
//   $new_so_order->fInvTotExclDExForeign =$tfcinvamtexcl;
//   $new_so_order->fInvTotTaxDExForeign =$tfcinvamttax;
//   $new_so_order->fInvTotInclDExForeign =$tfcinvamtincl;
//   $new_so_order->fInvTotExclForeign =$tfcinvamtexcl;
//   $new_so_order->fInvTotTaxForeign =$tfcinvamttax;
//   $new_so_order->fInvTotInclForeign =$tfcinvamtincl;
//   $new_so_order->fOrdDiscAmntForeign =0;
//   $new_so_order->fOrdDiscAmntExForeign =0;
//   $new_so_order->fOrdTotExclDExForeign =$tfcordamtexcl;
//   $new_so_order->fOrdTotTaxDExForeign =$tfcordamttax;
//   $new_so_order->fOrdTotInclDExForeign =$tfcordamtincl;
//   $new_so_order->fOrdTotExclForeign =$tfcordamtexcl;
//   $new_so_order->fOrdTotTaxForeign =$tfcordamttax;
//   $new_so_order->fOrdTotInclForeign =$tfcinvamtincl;
//   $new_so_order->cTaxNumber ='';
//   $new_so_order->cAccountName=$customer_details->name;
//   $new_so_order->iProspectID =0;
//   $new_so_order->iOpportunityID =0;
//   $new_so_order->InvTotRounding =0;
//   $new_so_order->OrdTotRounding = 0;
//   $new_so_order->fInvTotForeignRounding =0;
//   $new_so_order->fOrdTotForeignRounding =0;
//   $new_so_order->bInvRounding =1;
//   $new_so_order->iInvSettlementTermsID =0;
//   $new_so_order->cSettlementTermInvMsg ='';
//   $new_so_order->iOrderCancelReasonID =0;
//   $new_so_order->iLinkedDocID =0;
//   $new_so_order->bLinkedTemplate =0;
//   $new_so_order->InvTotInclExRounding =$tinvamtincl;
//   $new_so_order->OrdTotInclExRounding =$tordamtincl;
//   $new_so_order->fInvTotInclForeignExRounding =$tfcinvamtincl;
//   $new_so_order->fOrdTotInclForeignExRounding =$tfcordamtincl;
//   $new_so_order->iEUNoTCID =0;
//   $new_so_order->iPOAuthStatus =0;
//   $new_so_order->iPOIncidentID =0;
//   $new_so_order->iSupervisorID =0;
//   $new_so_order->iMergedDocID =0;
//   $new_so_order->iDocEmailed =0;
//   $new_so_order->fDepositAmountForeign =0;
//   $new_so_order->fRefundAmount =0;
//   $new_so_order->bTaxPerLine =1;
//   $new_so_order->fDepositAmountTotal =0;
//   $new_so_order->fDepositAmountUnallocated =0;
//   $new_so_order->fDepositAmountNew =0;
//   $new_so_order->cContact ='';
//   $new_so_order->cTelephone ='';
//   $new_so_order->cFax ='';
//   $new_so_order->cEmail ='1';
//   $new_so_order->cCellular ='';
//   $new_so_order->iInsuranceState =0;
//   $new_so_order->cAuthorisedBy ='';
//   $new_so_order->cClaimNumber ='';
//   $new_so_order->cPolicyNumber ='';
//   $new_so_order->cExcessAccName ='';
//   $new_so_order->cExcessAccCont1 ='';
//   $new_so_order->cExcessAccCont2 ='';
//   $new_so_order->fExcessAmt =0;
//   $new_so_order->fExcessPct =0;
//   $new_so_order->fExcessExclusive =0;
//   $new_so_order->fExcessInclusive =0;
//   $new_so_order->fExcessTax =0;
//   $new_so_order->fAddChargeExclusive =0;
//   $new_so_order->fAddChargeTax =0;
//   $new_so_order->fAddChargeInclusive =0;
//   $new_so_order->fAddChargeExclusiveForeign =0;
//   $new_so_order->fAddChargeTaxForeign =0;
//   $new_so_order->fAddChargeInclusiveForeign =0;
//   $new_so_order->fOrdAddChargeExclusive =0;
//   $new_so_order->fOrdAddChargeTax =0;
//   $new_so_order->fOrdAddChargeInclusive =0;
//   $new_so_order->fOrdAddChargeExclusiveForeign =0;
//   $new_so_order->fOrdAddChargeTaxForeign =0;
//   $new_so_order->fOrdAddChargeInclusiveForeign =0;
//   $new_so_order->iInvoiceSplitDocID =0;
//   $new_so_order->cGIVNumber =0;
//   $new_so_order->bIsDCOrder =0;
//   $new_so_order->iDCBranchID =0;
//   $new_so_order->iSalesBranchID =0;
//   $new_so_order->InvNum_iBranchID =0;
//   // $new_so_order->ulIDSOrdOrderType ='MR';
//   $new_so_order->save();

 
// //  get invoice id to use in entering line items
//   $invoice_id =DB::table('invnum')
//   ->where('ordernum', $tsonum)
//   ->select('autoindex')
//   ->first();

//   //update udf table

//   $update_udf =DB::table('_etblUserHistLink')
//   ->insert([
//  'userdictid' => 60,
//  'Tableid' => $invoice_id->autoindex,
//  'UserValue' => 'MR'       
//   ]);


  
//    // getting mono separate billed Asset
//    $billing_mono_separate = DB::table('_bvARAccountsFull As cl')
//    ->where('DCLink',$customer_details->dclink)
//    ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//    ->whereYear('ReadingDate', '=', $today->year)
//    ->whereMonth('ReadingDate', '=', $today->month)

//    ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode',
//    DB::raw('sum(cmr.moncmr) as mon_cmr_total'),
//    DB::raw('sum(cmr.monpmr) as mon_pmr_total'),
//    DB::raw('sum(cmr.moncmr - cmr.monpmr) as monunit'),        
//    DB::raw('sum(cmr.a3mcmr - cmr.a3mpmr) as a3munit'),
//    DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.cCode','sa.cDescription','sa.ucSASerialNo'
    
//     )
//    ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')  
//    ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//     ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode','sa.cDescription','sa.ucSASerialNo')
//     ->get();

      

//     foreach($billing_mono_separate as $data){
//        $asset_id = DB::table('_smtblServiceAsset As sa')
//        ->where('cCode',$data->cCode)
//        ->select('AutoIdx')
//        ->first();
      
   
//         $rentamt =DB::table('_smtblperiodservice As ps')
//        ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
//        ->Where(function($query)
//             {
//             $query->where('cCode','RENTAL')
//             ->orwhere('cCode','RENTAL CHARGES');
//             }) 
//        ->select('fAmount')
//        ->first();
//        $data->famount =$rentamt->famount ?? null;


//        //mono separate items
//        // "MONO"
//        $counter_elapse =DB::table('_smtblcounterelapsed')
//        ->where('iServiceAssetId',$asset_id->AutoIdx ?? 0)
//        ->where('cCode','BILLMON')
//        ->select('AutoIdx')
//        ->first(); 
                  
      

//        $min_vol = _smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx ?? 0)
//        ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//        ->groupBy('fRate',DB::raw('(iToqty)'))
//        ->first();

//        if(!empty($min_vol) && ($min_vol->fRate ?? 0) > 0 )
//        {
      
//        if($min_vol->min_vol >=1000000){
//            $data->min_mono_vol = null;
//           }
//           else{
//            $data->min_mono_vol =$min_vol->min_vol ?? null;
//           }

       

//        if( $min_vol->min_vol >=1000000 ){

//            $total_frate =($min_vol->fRate * $data->monunit);
//            $rate_frate =number_format($min_vol->fRate,3);            

//            $data->comb_rates = $rate_frate;
//            $data->total = $total_frate;       


//        }
//        else{
//        $diff_mon = ($data->monunit -$min_vol->min_vol);
       
       
//        // the value is bigger than min volume
//        //slab two starts here
//        if($diff_mon > 0){
//        $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iToqty',$min_vol->min_vol)
//        ->select('frate')
//        ->first();
//        $total_min =($rate_min->frate * $min_vol->min_vol);

//        $rate =number_format($rate_min->frate,3);


//        // we increase minimum to move to the next slab 


//        //slab two starts
//        $Increment_1 =$min_vol->min_vol +1;          
       
//        // we get the rate for the next slab and their readings
//        $next_slab =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$Increment_1)
//       ->where('iToqty','>=',$Increment_1)
//       ->select('iFromQty','iToqty','fRate')
//       ->first();
      
//        //  we get the difference of this slab
//       $diff_slab =($next_slab->iToqty - $next_slab->iFromQty)+1;

//    //  we check if the remainder fulls fall in this slab or exceed
//       $diff_bil_slab =$diff_mon -$diff_slab;           

//       //counts fits fully in this slab the difference is negative
//       if($diff_bil_slab < 0){
//        $total_slab_1 =($diff_mon * $next_slab->fRate );

//        $rate_2 =number_format($next_slab->fRate,3);            

//        // two slabs used             
//        $comb_rates =$rate.' | '.$rate_2;
//        $total =($total_slab_1 +$total_min);

//        $data->comb_rates = $comb_rates;
//        $data->total = $total;

     
       
//       }

//       //  slab 3
//       //the counter is still much than the previous slab difference
//       if($diff_bil_slab > 0){
//        $total_slab_1 =($diff_slab * $next_slab->fRate );      
       
//        $rate_2 =number_format($next_slab->fRate,3);
       

//        //we increase the slab to quantity to move to the next slab
//        $increment2 =$next_slab->iToqty +1;
      

//        // we get the slab values rates and quantity
//        $rate_slab2 =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$increment2)
//       ->where('iToqty','>=',$increment2)
//       ->select('iFromQty','iToqty','fRate')
//       ->first();
      

//       $slab2_diff =($rate_slab2->iToqty -$rate_slab2->iFromQty)+1; 

 
//        //  we check if the remainder fully fall in this slab or exceed
//        $remaining_bil_slab =$diff_bil_slab -$slab2_diff; 
       
//        if($remaining_bil_slab < 0){

//        $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

//        $rate_3 =number_format($rate_slab2->fRate,3);

//        // three slabs used

//        $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3;
//        $total =($total_slab_1 +$total_min +$total_slab_2);

//        $data->comb_rates = $comb_rates;
//        $data->total = $total;


//        }

//        // slab four
//        if($remaining_bil_slab > 0){
//        $total_slab_2 =$diff_bil_slab * $rate_slab2->fRate;

//        $rate_3 =number_format($rate_slab2->fRate,3);

//        // increase slab to next

//        $increment3 =$rate_slab2->iToqty +1;
//        $rate_slab3 =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iFromQty','<=',$increment3)
//        ->where('iToqty','>=',$increment3)
//        ->select('iFromQty','iToqty','fRate')
//        ->first();

       
//        $slab3_diff =($rate_slab3->iToqty -$rate_slab3->iFromQty)+1; 

//        $remaing_slab3_diff =($remaining_bil_slab -$slab3_diff);

//        if(!$remaing_slab3_diff){

//        $total_slab_3 =$remaining_bil_slab * $rate_slab3->fRate;
//        $rate_4 =number_format($rate_slab3->fRate,3);

//        // four slabs used

//        $comb_rates =$rate.'|'.$rate_2. '|'.$rate_3. '|'.$rate_4;
//        $total =($total_slab_1 +$total_min +$total_slab_2 +$total_slab_3);

//        $data->comb_rates = $comb_rates;
//        $data->total = $total;

//        }
//        }
//       }             
//        }
//        else{
//         $rate_min =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//        ->where('iToqty',$min_vol->min_vol)
//        ->select('frate')
//        ->first();
//        $total_min =($rate_min->frate * $min_vol->min_vol);
//        $rate =number_format($rate_min->frate,3);

//        $data->comb_rates = $rate;
//        $data->total = $total_min;     
//        } 
     
     
//    }          

       

     
//     $bill_mono_sep =$billing_mono_separate[0] ?? '';
//     $mono_consolidated_sep =(object) $bill_mono_sep;


//    $t_mono_cmr = $mono_consolidated_sep->mon_cmr_total ?? 0;
//    $t_mon_pmr = $mono_consolidated_sep->mon_pmr_total?? 0;
//    $tunits = $mono_consolidated_sep-> monunit ?? 0;
//    $mono_rate =$mono_consolidated_sep->comb_rates ?? 0;
//    $min_mono_vol =$mono_consolidated_sep->min_mono_vol ?? 0;
//    $tbillmeter = "BILLMON";
//    $treadingdate = "1900-01-01";
//    $total_mon =$mono_consolidated_sep->total ?? 0;

  
//      $total_mono_sep =$total_mon;
//      if($tax_rate->TaxRate > 0){
//        if($customer_details->icurrencyid ==0){
//          $tlineamtexcl = $total_mono_sep;
//          $tlineamttax = ($total_mono_sep * ($tax_rate->TaxRate / 100));
//          $tlineamtincl =($total_mono_sep * (1 + $tax_rate->TaxRate / 100));
//          $tfclineamtexcl = 0;
//          $tfclineamttax = 0;
//          $tfclineamtincl = 0;
//        }
//        else{
//          $tfclineamtexcl = $total_mono_sep;
//          $tfclineamttax = ($total_mono_sep * ($tax_rate->TaxRate / 100));
//          $tfclineamtincl = ($total_mono_sep * (1 + $tax_rate->TaxRate / 100));
//          $tfclineamtexcl = $tfclineamtexcl *$exc_rate;
//          $tfclineamttax = $tfclineamttax * $exc_rate;
//          $tfclineamtincl = $tfclineamtincl * $exc_rate;
//        }
//      }
//      else{
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_mono_sep;
//         $tlineamttax = 0;
//         $tlineamtincl =$total_mono_sep;
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_mono_sep;
//         $tfclineamttax = 0;
//         $tfclineamtincl = $total_mono_sep ;
//         $tfclineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tfclineamttax =0;
//         $tfclineamtincl = $tfclineamtincl *$exc_rate;
//       }


//      }
   
  

//    $tstockid = 17987;
//    $tlinenote ="CMR: $t_mono_cmr\r\nPRM: $t_mon_pmr\r\nCOPIES DONE: $tunits\r\nMIN. VOL: $min_mono_vol\r\nRATE: $mono_rate";
//    $tlinedesc = "Mono Billing for $mono_consolidated_sep->cDescription ($mono_consolidated_sep->ucSASerialNo)";

    
//     $_btblInvoiceLines = new _btblInvoiceLines;
//     $_btblInvoiceLines->iInvoiceID =$invoice_id->autoindex;
//     $_btblInvoiceLines->iOrigLineID =0;
//     $_btblInvoiceLines->iGrvLineID =0;
//     $_btblInvoiceLines->iLineDocketMode =0; 
//     $_btblInvoiceLines->cDescription =$tlinedesc; 
//     $_btblInvoiceLines->iUnitsOfMeasureStockingID=0; 
//     $_btblInvoiceLines->iUnitsOfMeasureCategoryID=0;
//     $_btblInvoiceLines->iUnitsOfMeasureID=0;
//     $_btblInvoiceLines->fQuantity=1;
//     $_btblInvoiceLines->fQtyChange=1;
//     $_btblInvoiceLines->fQtyToProcess=1; 
//     $_btblInvoiceLines->fQtyLastProcess=0; 
//     $_btblInvoiceLines->fQtyProcessed =0; 
//     $_btblInvoiceLines->fQtyReserved=0; 
//     $_btblInvoiceLines->fQtyReservedChange =0;
//     $_btblInvoiceLines->cLineNotes=$tlinenote; 
//     $_btblInvoiceLines->fUnitPriceExcl=$tlineamtexcl; 
//     $_btblInvoiceLines->fUnitPriceIncl=$tlineamtincl;
//     $_btblInvoiceLines->iUnitPriceOverrideReasonID=0; 
//     $_btblInvoiceLines->fUnitCost=0;
//     $_btblInvoiceLines->fLineDiscount=0; 
//     $_btblInvoiceLines->iLineDiscountReasonID=0;
//     $_btblInvoiceLines->iReturnReasonID=0; 
//     $_btblInvoiceLines->fTaxRate=$tax_rate->TaxRate; 
//     $_btblInvoiceLines->bIsSerialItem=0; 
//     $_btblInvoiceLines->bIsWhseItem=1;
//     $_btblInvoiceLines->fAddCost=0; 
//     $_btblInvoiceLines->cTradeinItem='';
//     $_btblInvoiceLines->iStockCodeID=$tstockid; 
//     $_btblInvoiceLines->iJobID=0;
//     $_btblInvoiceLines->iWarehouseID=6;
//     $_btblInvoiceLines->iTaxTypeID=$customer_details->ideftaxtypeid;
//     $_btblInvoiceLines->iPriceListNameID=1;
//     $_btblInvoiceLines->fQuantityLineTotIncl=$tlineamtincl;
//     $_btblInvoiceLines->fQuantityLineTotExcl=$tlineamtexcl;
//     $_btblInvoiceLines->fQuantityLineTotInclNoDisc=$tlineamtincl;
//     $_btblInvoiceLines->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines->fQuantityLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//     $_btblInvoiceLines->fQtyChangeLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotIncl=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotExcl =0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDisc=0;
//     $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDisc=0;
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmount=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotIncl=0;
//     $_btblInvoiceLines->fQtyProcessedLineTotExcl=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotInclNoDisc=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotExclNoDisc=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmount=0;
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines->fUnitPriceExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fUnitPriceInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fUnitCostForeign=0;
//     $_btblInvoiceLines->fAddCostForeign=0;
//     $_btblInvoiceLines->fQuantityLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//     $_btblInvoiceLines->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//     $_btblInvoiceLines->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotInclForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotExclForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmountForeign=0; 
//     $_btblInvoiceLines->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotInclForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotExclForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmountForeign=0; 
//     $_btblInvoiceLines->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines->iLineRepID=$customer_details->repid; 
//     $_btblInvoiceLines->iLineProjectID=2; 
//     $_btblInvoiceLines->iLedgerAccountID=0; 
//     $_btblInvoiceLines->IModule=0;
//     $_btblInvoiceLines->bChargeCom=1;
//     $_btblInvoiceLines->bIsLotItem=0;
//     $_btblInvoiceLines->iLotID=0;
//     $_btblInvoiceLines->cLotNumber='';
//     $_btblInvoiceLines->dLotExpiryDate=null;
//     $_btblInvoiceLines->iMFPID=0;
//     $_btblInvoiceLines->iLineID=1;
//     $_btblInvoiceLines->iLinkedLineID=0;
//     $_btblInvoiceLines->fQtyLinkedUsed=null;
//     $_btblInvoiceLines->fUnitPriceInclOrig=null;
//     $_btblInvoiceLines->fUnitPriceExclOrig=Null;
//     $_btblInvoiceLines->fUnitPriceInclForeignOrig=Null;
//     $_btblInvoiceLines->fUnitPriceExclForeignOrig=0;
//     $_btblInvoiceLines->iDeliveryMethodID=0;
//     $_btblInvoiceLines->fQtyDeliver=0;
//     $_btblInvoiceLines->dDeliveryDate=$today;
//     $_btblInvoiceLines->iDeliveryStatus=0;
//     $_btblInvoiceLines->fQtyForDelivery=0;
//     $_btblInvoiceLines->bPromotionApplied=0;
//     $_btblInvoiceLines->fPromotionPriceExcl=0;
//     $_btblInvoiceLines->fPromotionPriceIncl=0;
//     $_btblInvoiceLines->cPromotionCode=0;
//     $_btblInvoiceLines->iSOLinkedPOLineID=0;
//     $_btblInvoiceLines->fLength=0;
//     $_btblInvoiceLines->fWidth=0;
//     $_btblInvoiceLines->fHeight=0;
//     $_btblInvoiceLines->iPieces=0;
//     $_btblInvoiceLines->iPiecesToProcess=0;
//     $_btblInvoiceLines->iPiecesLastProcess=0;
//     $_btblInvoiceLines->iPiecesProcessed=0;
//     $_btblInvoiceLines->iPiecesReserved=0;
//     $_btblInvoiceLines->iPiecesDeliver=0;
//     $_btblInvoiceLines->iPiecesForDelivery=0;
//     $_btblInvoiceLines->fQuantityUR=1;
//     $_btblInvoiceLines->fQtyChangeUR=1;
//     $_btblInvoiceLines->fQtyToProcessUR=1;
//     $_btblInvoiceLines->fQtyLastProcessUR=0;
//     $_btblInvoiceLines->fQtyProcessedUR=0;
//     $_btblInvoiceLines->fQtyReservedUR=0;
//     $_btblInvoiceLines->fQtyReservedChangeUR=0;
//     $_btblInvoiceLines->fQtyDeliverUR=0;
//     $_btblInvoiceLines->fQtyForDeliveryUR=0;
//     $_btblInvoiceLines->fQtyLinkedUsedUR=0;
//     $_btblInvoiceLines->iPiecesLinkedUsed=0;
//     $_btblInvoiceLines->iSalesWhseID=0;
//     $_btblInvoiceLines->_btblInvoiceLines_iBranchID=0;
//     $_btblInvoiceLines->udIDSOrdTxCMReadingDate=$today;
//     $_btblInvoiceLines->uiIDSOrdTxCMPrevReading=$t_mon_pmr;
//     $_btblInvoiceLines->uiIDSOrdTxCMCurrReading=$t_mono_cmr;
//     $_btblInvoiceLines->ucIDSOrdTxCMMinVol=$min_mono_vol;
//     $_btblInvoiceLines->ucIDSOrdTxCMRates=$mono_rate;
//     $_btblInvoiceLines->ucIDSOrdTxCMServiceAsset=$mono_consolidated_sep->cCode;
//     $_btblInvoiceLines->ucIDSOrdTxCMMeterType="BILLMON";

//    $_btblInvoiceLines->save();
//     }
//   }
    







//    // calculation color separate NON CON

//       $asset_id_color = DB::table('_smtblServiceAsset As sa')
//       ->where('cCode',$value['ucSABillingAsset'])
//       ->select('AutoIdx')
//       ->first();
    

//       $color_counter_elapse =DB::table('_smtblcounterelapsed')
//       ->where('iServiceAssetId',$asset_id_color->AutoIdx)
//       ->where('cCode','BILLCOL')
//       ->select('AutoIdx')
//       ->first();  
//       if(!empty($color_counter_elapse)){

 
//      $billing_color_sep = DB::table('_bvARAccountsFull As cl')
//    ->where('DCLink',$customer_details->dclink)
//     ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//    ->whereYear('ReadingDate', '=', $today->year)
//    ->whereMonth('ReadingDate', '=', $today->month)

//    ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//    DB::raw('sum(cmr.ColCMR) as color_cmr_total'),
//    DB::raw('sum(cmr.ColPMR) as color_pmr_total'),
//    DB::raw('sum(cmr.ColCMR - cmr.ColPMR) as colunit'),   
//    DB::raw('sum(cmr.a3ccmr - cmr.a3cpmr) as a3cunit'),'sa.cCode','sa.cDescription','sa.ucSASerialNo'
        
//     )
//    ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//    ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//    ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode','sa.cDescription','sa.ucSASerialNo')
//    ->get();


    
//    foreach($billing_color_sep as $data){
//      $asset_id = DB::table('_smtblServiceAsset As sa')
//      ->where('cCode',$data->cCode)
//      ->select('AutoIdx')
//      ->first();            
   

//     $color_counter_elapse =DB::table('_smtblcounterelapsed')
//     ->where('iServiceAssetId',$asset_id->AutoIdx ?? '')
//     ->where('cCode','BILLCOL')
//     ->select('AutoIdx')
//     ->first();

//     $min_vol_color = _smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx ?? '')
//      ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//      ->groupBy('fRate',DB::raw('(iToqty)'))
//      ->first();

//    if(!empty($min_vol_color) && $min_vol_color->fRate >  0)
//    {
 

//      if($min_vol_color->min_vol >= 1000000){

//      $total_color =$min_vol_color->fRate * $data->colunit;
//      $rate_frate =number_format($min_vol_color->fRate,3);

//      $data->total_color = $total_color;
    
//      $data->comb_rates_color =$rate_frate;   


//      }
//      else{                   

//      $diff_color = ($data->colunit -$min_vol_color->min_vol);
     

//      if($diff_color > 0){
//      $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//      ->where('iToqty',$min_vol_color->min_vol)
//      ->select('frate')
//      ->first();
//       $total_min_color =($rate_min_color->frate * $min_vol_color->min_vol);

//      $rate =number_format($rate_min_color->frate,3);

//      $diff_remaining_color = ($data->colunit -$min_vol_color->min_vol);

//      // we increase minimum to move to the next slab 

//      //slab two starts
//      $Increment_1 =$min_vol_color->min_vol +1;  

//       //we get the rate for the next slab and their readings
//       $next_slab_col =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//       ->where('iFromQty','<=',$Increment_1)
//      ->where('iToqty','>=',$Increment_1)
//      ->select('iFromQty','iToqty','fRate')
//      ->first();

//       //  we get the difference of this slab
//     $diff_slab_color =($next_slab_col->iToqty - $next_slab_col->iFromQty)+1;

//     //  we check if the remainder fulls fall in this slab or exceed
//        $diff_bal_slab =$diff_remaining_color -$diff_slab_color;

//      //   value fully fits here


//      if($diff_bal_slab < 0){
//      $total_slab_2_col =($diff_remaining_color * $next_slab_col->fRate );

//      $rate_2 =number_format($next_slab_col->fRate,3);         

     
//      // two slabs used 
     
//      $comb_rates_color =$rate.' | '.$rate_2;
//      $total_color =($total_min_color + $total_slab_2_col);

//      $data->comb_rates_color = $comb_rates_color;
//      $data->total_color = $total_color;



//      }

//      // slab three
//      if($diff_bal_slab > 0){
//      $total_slab_2_col =($diff_slab_color * $next_slab_col->fRate );  
     
//      $rate_2 =number_format($next_slab_col->fRate,3); 

//      $diff_remaining2_color = ($diff_bal_slab - $diff_slab_color);

//      // we increase minimum to move to the next slab 


//      //slab two starts
//      $Increment_2 =$next_slab_col->iToqty +1;  

//       // we get the slab values rates and quantity
//       $rate_slab3_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//       ->where('iFromQty','<=',$increment2)
//      ->where('iToqty','>=',$increment2)
//      ->select('iFromQty','iToqty','fRate')
//      ->first();

//      // we get slab difference
//      $slab_3_diff =($rate_slab3_color->iToqty - $rate_slab3_color->iFromQty) +1;

//      // we check if balance is still bigger or less

//      $diff_remaining3_color =$diff_remaining2_color -  $slab_3_diff;

//      if($diff_remaining3_color < 0){

//      $total_slab_3_col =($diff_remaining2_color * $rate_slab3_color->fRate );

//      $rate_3 =number_format($rate_slab3_color->fRate,3); 

//       // three slabs used 
     
//       $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3;
//       $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col);

//       $data->comb_rates_color = $comb_rates_color;
//       $data->total_color = $total_color;


//      }
//     if($diff_remaining3_color > 0){

//      $total_slab_3_col =($slab_3_diff * $rate_slab3_color->fRate );

//      $rate_3 =number_format($rate_slab3_color->fRate,3); 

//      // increase to move to the next slab

//      $Increment3 =$rate_slab3_color->iToqty +1;  

//      $rate_slab4_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//      ->where('iFromQty','<=',$increment3)
//      ->where('iToqty','>=',$increment3)
//      ->select('iFromQty','iToqty','fRate')
//      ->first();

//      // we get the difference of the slab

//      $diff_slab4 =($rate_slab4_color->iToqty - $rate_slab4_color->iFromQty)+1;


//      // we check if balance is still bigger or less
//      $diff_remaining4_color =$diff_remaining3_color - $slab_3_diff ;

//      // we check if balance fits in this slab

//      if($diff_remaining4_color < 0){

//      $total_slab_4_col =($diff_remaining3_color * $rate_slab4_color->fRate );

//      $rate_4 =number_format($rate_slab4_color->fRate,3); 
     
//       // four slabs used 
     
//       $comb_rates_color =$rate.' | '.$rate_2. '|'.$rate_3. '|'. $rate_4;
//       $total_color =($total_min_color + $total_slab_2_col +$total_slab_3_col+$total_slab_4_col);

//       $data->comb_rates_color = $comb_rates_color;
//       $data->total_color = $total_color;


//      }

//     }

//  }

// }


//      else{

//      $rate_min_color =_smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx)
//      ->where('iToqty',$min_vol_color->min_vol)
//      ->select('frate')
//      ->first();
//      $total_color =($rate_min_color->frate * $min_vol_color->min_vol);
//      $rate_color =number_format($rate_min_color->frate,3);

//      $data->comb_rates_color = $rate_color;
//      $data->total_color = $total_color;       

     



//      }

//  }

// $min_color_vol = _smtblrateslab::where('iBillingID',$color_counter_elapse->AutoIdx ?? '')
// ->select(DB::raw('min(iToqty) as min_color_vol'))
// ->first();


// if($min_color_vol->min_color_vol >=1000000){
// $data->min_color_vol = null;
// }
// else{
// $data->min_color_vol =$min_color_vol->min_color_vol ?? null;
// }      

      

//   } 
// }       

//   $bill_color_sep =$billing_color_sep[0] ?? '';
//   $color_bill_sep =(object) $bill_color_sep;




//  $t_color_cmr = $color_bill_sep->color_cmr_total;
//  $t_color_pmr = $color_bill_sep->color_pmr_total;
//  $tunits = $color_bill_sep-> colunit;
//  $color_rate =$color_bill_sep->comb_rates_color ?? 0;
//  $min_color_vol =$color_bill_sep->min_color_vol;
//  $tbillmeter = "BILLCOL";
//  $treadingdate = "1900-01-01";
//  $total_col_con =$color_bill_sep->total_color;



//    $total_color_sep =$total_col_con;
//    if($tax_rate->TaxRate > 0){
//      if($customer_details->icurrencyid ==0){
//        $tlineamtexcl = $total_color_sep;
//        $tlineamttax = ($total_color_sep * ($tax_rate->TaxRate / 100));
//        $tlineamtincl =($total_color_sep * (1 + $tax_rate->TaxRate / 100));
//        $tfclineamtexcl = 0;
//        $tfclineamttax = 0;
//        $tfclineamtincl = 0;
//      }
//      else{
//        $tfclineamtexcl = $total_color_sep;
//        $tfclineamttax = ($total_color_sep * ($tax_rate->TaxRate / 100));
//        $tfclineamtincl = ($total_color_sep * (1 + $tax_rate->TaxRate / 100));
//        $tfclineamtexcl = $tfclineamtexcl *$exc_rate;
//        $tfclineamttax = $tfclineamttax * $exc_rate;
//        $tfclineamtincl = $tfclineamtincl * $exc_rate;
//      }
//    }
//    else{
//     if($customer_details->icurrencyid ==0){
//       $tlineamtexcl = $total_color_sep;
//       $tlineamttax = 0;
//       $tlineamtincl =$total_color_sep;
//       $tfclineamtexcl = 0;
//       $tfclineamttax = 0;
//       $tfclineamtincl = 0;
//     }
//     else{
//       $tfclineamtexcl = $total_color_sep;
//       $tfclineamttax = 0;
//       $tfclineamtincl = $total_color_sep;
//       $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//       $tlineamttax = 0;
//       $tlineamtincl = $tfclineamtincl * $exc_rate;
//     }
//    }
 
   

//    $tstockid = 17987;
//    $tlinenote ="CMR: $t_color_cmr\r\nPRM: $t_color_pmr\r\nCOPIES DONE: $tunits\r\nMIN. VOL: $min_color_vol\r\nRATE: $color_rate";
//    $tlinedesc = "Color Billing for $color_bill_sep->cDescription ($color_bill_sep->ucSASerialNo)";

//    $_btblInvoiceLines_color = new _btblInvoiceLines;
//     $_btblInvoiceLines_color->iInvoiceID =$invoice_id->autoindex;
//     $_btblInvoiceLines_color->iOrigLineID =0;
//     $_btblInvoiceLines_color->iGrvLineID =0;
//     $_btblInvoiceLines_color->iLineDocketMode =0; 
//     $_btblInvoiceLines_color->cDescription =$tlinedesc; 
//     $_btblInvoiceLines_color->iUnitsOfMeasureStockingID=0; 
//     $_btblInvoiceLines_color->iUnitsOfMeasureCategoryID=0;
//     $_btblInvoiceLines_color->iUnitsOfMeasureID=0;
//     $_btblInvoiceLines_color->fQuantity=1;
//     $_btblInvoiceLines_color->fQtyChange=1;
//     $_btblInvoiceLines_color->fQtyToProcess=1; 
//     $_btblInvoiceLines_color->fQtyLastProcess=0; 
//     $_btblInvoiceLines_color->fQtyProcessed =0; 
//     $_btblInvoiceLines_color->fQtyReserved=0; 
//     $_btblInvoiceLines_color->fQtyReservedChange =0;
//     $_btblInvoiceLines_color->cLineNotes=$tlinenote; 
//     $_btblInvoiceLines_color->fUnitPriceExcl=$tlineamtexcl; 
//     $_btblInvoiceLines_color->fUnitPriceIncl=$tlineamtincl;
//     $_btblInvoiceLines_color->iUnitPriceOverrideReasonID=0; 
//     $_btblInvoiceLines_color->fUnitCost=0;
//     $_btblInvoiceLines_color->fLineDiscount=0; 
//     $_btblInvoiceLines_color->iLineDiscountReasonID=0;
//     $_btblInvoiceLines_color->iReturnReasonID=0; 
//     $_btblInvoiceLines_color->fTaxRate=$tax_rate->TaxRate; 
//     $_btblInvoiceLines_color->bIsSerialItem=0; 
//     $_btblInvoiceLines_color->bIsWhseItem=1;
//     $_btblInvoiceLines_color->fAddCost=0; 
//     $_btblInvoiceLines_color->cTradeinItem='';
//     $_btblInvoiceLines_color->iStockCodeID=$tstockid; 
//     $_btblInvoiceLines_color->iJobID=0;
//     $_btblInvoiceLines_color->iWarehouseID=6;
//     $_btblInvoiceLines_color->iTaxTypeID=$customer_details->ideftaxtypeid;
//     $_btblInvoiceLines_color->iPriceListNameID=1;
//     $_btblInvoiceLines_color->fQuantityLineTotIncl=$tlineamtincl;
//     $_btblInvoiceLines_color->fQuantityLineTotExcl=$tlineamtexcl;
//     $_btblInvoiceLines_color->fQuantityLineTotInclNoDisc=$tlineamtincl;
//     $_btblInvoiceLines_color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQuantityLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotIncl =$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmount =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotIncl=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExcl =0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDisc=0;
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDisc=0;
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmount=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotIncl=0;
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExcl=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDisc=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDisc=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmount=0;
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDisc=0; 
//     $_btblInvoiceLines_color->fUnitPriceExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fUnitPriceInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fUnitCostForeign=0;
//     $_btblInvoiceLines_color->fAddCostForeign=0;
//     $_btblInvoiceLines_color->fQuantityLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//     $_btblInvoiceLines_color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//     $_btblInvoiceLines_color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotInclForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExclForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountForeign=0; 
//     $_btblInvoiceLines_color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotInclForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExclForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotInclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTotExclNoDiscForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountForeign=0; 
//     $_btblInvoiceLines_color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//     $_btblInvoiceLines_color->iLineRepID=$customer_details->repid; 
//     $_btblInvoiceLines_color->iLineProjectID=2; 
//     $_btblInvoiceLines_color->iLedgerAccountID=0; 
//     $_btblInvoiceLines_color->IModule=0;
//     $_btblInvoiceLines_color->bChargeCom=1;
//     $_btblInvoiceLines_color->bIsLotItem=0;
//     // $_btblInvoiceLines_color->iLotID=0;
//     // $_btblInvoiceLines_color->cLotNumber='';
//     // $_btblInvoiceLines_color->dLotExpiryDate=null;
//     $_btblInvoiceLines_color->iMFPID=0;
//     $_btblInvoiceLines_color->iLineID=1;
//     $_btblInvoiceLines_color->iLinkedLineID=0;
//     $_btblInvoiceLines_color->fQtyLinkedUsed=null;
//     $_btblInvoiceLines_color->fUnitPriceInclOrig=null;
//     $_btblInvoiceLines_color->fUnitPriceExclOrig=Null;
//     $_btblInvoiceLines_color->fUnitPriceInclForeignOrig=Null;
//     $_btblInvoiceLines_color->fUnitPriceExclForeignOrig=0;
//     $_btblInvoiceLines_color->iDeliveryMethodID=0;
//     $_btblInvoiceLines_color->fQtyDeliver=0;
//     $_btblInvoiceLines_color->dDeliveryDate=$today;
//     $_btblInvoiceLines_color->iDeliveryStatus=0;
//     $_btblInvoiceLines_color->fQtyForDelivery=0;
//     $_btblInvoiceLines_color->bPromotionApplied=0;
//     $_btblInvoiceLines_color->fPromotionPriceExcl=0;
//     $_btblInvoiceLines_color->fPromotionPriceIncl=0;
//     $_btblInvoiceLines_color->cPromotionCode=0;
//     $_btblInvoiceLines_color->iSOLinkedPOLineID=0;
//     $_btblInvoiceLines_color->fLength=0;
//     $_btblInvoiceLines_color->fWidth=0;
//     $_btblInvoiceLines_color->fHeight=0;
//     $_btblInvoiceLines_color->iPieces=0;
//     $_btblInvoiceLines_color->iPiecesToProcess=0;
//     $_btblInvoiceLines_color->iPiecesLastProcess=0;
//     $_btblInvoiceLines_color->iPiecesProcessed=0;
//     $_btblInvoiceLines_color->iPiecesReserved=0;
//     $_btblInvoiceLines_color->iPiecesDeliver=0;
//     $_btblInvoiceLines_color->iPiecesForDelivery=0;
//     $_btblInvoiceLines_color->fQuantityUR=1;
//     $_btblInvoiceLines_color->fQtyChangeUR=1;
//     $_btblInvoiceLines_color->fQtyToProcessUR=1;
//     $_btblInvoiceLines_color->fQtyLastProcessUR=0;
//     $_btblInvoiceLines_color->fQtyProcessedUR=0;
//     $_btblInvoiceLines_color->fQtyReservedUR=0;
//     $_btblInvoiceLines_color->fQtyReservedChangeUR=0;
//     $_btblInvoiceLines_color->fQtyDeliverUR=0;
//     $_btblInvoiceLines_color->fQtyForDeliveryUR=0;
//     $_btblInvoiceLines_color->fQtyLinkedUsedUR=0;
//     $_btblInvoiceLines_color->iPiecesLinkedUsed=0;
//     $_btblInvoiceLines_color->iSalesWhseID=0;
//     $_btblInvoiceLines_color->_btblInvoiceLines_iBranchID=0;
//     $_btblInvoiceLines_color->udIDSOrdTxCMReadingDate=$today;
//     $_btblInvoiceLines_color->uiIDSOrdTxCMPrevReading=$t_color_pmr;
//     $_btblInvoiceLines_color->uiIDSOrdTxCMCurrReading=$t_color_cmr;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMMinVol=$min_color_vol;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMRates=$color_rate;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMServiceAsset=$color_bill_sep->cCode;
//     $_btblInvoiceLines_color->ucIDSOrdTxCMMeterType="BILLCOL";

//     $_btblInvoiceLines_color->save();

//   }
//   // calculate Scan non CON

//   $asset_id_scan = DB::table('_smtblServiceAsset As sa')
//   ->where('cCode',$value['ucSABillingAsset'])
//   ->select('AutoIdx')
//   ->first();


//   $scan_counter_elapse =DB::table('_smtblcounterelapsed')
//   ->where('iServiceAssetId',$asset_id_color->AutoIdx)
//   ->where('cCode','BILLSCN')
//   ->select('AutoIdx')
//   ->first();

//   if(!empty($scan_counter_elapse)){
//     $billing_scan_non_con = DB::table('_bvARAccountsFull As cl')
//     ->where('DCLink',$customer_details->dclink)
//     ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//     ->whereYear('ReadingDate', '=', $today->year)
//     ->whereMonth('ReadingDate', '=', $today->month)

//     ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//     DB::raw('sum(cmr.scncmr - cmr.scnpmr) as scnunit'),
//     DB::raw('sum(cmr.scncmr) as tscancmr'),
//     DB::raw('sum(cmr.scnpmr) as tscanpmr'),'sa.cCode','sa.cDescription','sa.ucSASerialNo'        
//      )
//     ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//     ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//     ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cCode','sa.cDescription','sa.ucSASerialNo')
//     ->get();

      
//       foreach($billing_scan_non_con as $data){
//         $asset_id = DB::table('_smtblServiceAsset As sa')
//         ->where('cCode',$data->cCode)
//         ->select('AutoIdx')
//         ->first();            
      

     
//        $scan_counter_elapse =DB::table('_smtblcounterelapsed')
//        ->where('iServiceAssetId',$asset_id->AutoIdx)
//        ->where('cCode','BILLSCN')
//        ->select('AutoIdx')
//        ->first();


//        $scan_min_vol = _smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx ?? '')
//        ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//        ->groupBy('fRate',DB::raw('(iToqty)'))
//        ->first();

//        if(!empty($scan_min_vol))
//        {

//        if($scan_min_vol->min_vol >=1000000){
//            $data->scan_min_vol = null;
//           }
//        else{
//            $data->scan_min_vol =$scan_min_vol->min_vol ?? null;
//        }
//        if( $scan_min_vol->min_vol  >=1000000 ){

//            $total_frate_scan =($scan_min_vol->fRate * $data->scnunit);
//            $rate_frate_scan =number_format($scan_min_vol->fRate,3);         

//            $data->comb_rates_scan = $rate_frate_scan;
//            $data->total_scan = $total_frate_scan;            


//        }

//        else{
//            $diff_scan = ($data->scnunit - $scan_min_vol->min_vol);
//            if($diff_scan > 0){
//                $rate_min_scan =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                ->where('iToqty',$scan_min_vol->min_vol)
//                ->select('frate')
//                ->first();
//                $total_min_scan =($rate_min_scan->frate * $scan_min_vol->min_vol);

//                $rate_scan =number_format($rate_min_scan->frate,3);
               
//                //slab two starts
//                $Increment_1_sc =$scan_min_vol->min_vol +1; 

       
//            // we get the rate for the next slab and their readings
//            $next_slab_sn =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//            ->where('iFromQty','<=',$Increment_1_sc)
//            ->where('iToqty','>=',$Increment_1_sc)
//            ->select('iFromQty','iToqty','fRate')
//            ->first();
      
//                //  we get the difference of this slab
//            $diff_slab1_sn =($next_slab_sn->iToqty - $next_slab_sn->iFromQty)+1;


//            //  we check if the remainder fulls fall in this slab or exceed
//            $diff_bil_slab_sn =$diff_scan -$diff_slab1_sn;           

//           //counts fits fully in this slab the difference is negative
//            if($diff_bil_slab_sn < 0){
//                $total_slab_1_sn =($diff_scan * $next_slab_sn->fRate );

//                $rate_2_scan =number_format($next_slab_sn->fRate,3);            

//            // two slabs used             
//            $comb_rates_scan =$rate_scan.' | '.$rate_2_scan;
//            $total_scan =($total_slab_1_sn +$total_min_scan);

//            $data->comb_rates_scan = $comb_rates_scan;
//            $data->total_scan = $total_scan;          
       
//        }
//        }

//        else{
//            $rate_min_scan =_smtblrateslab::where('iBillingID',$scan_counter_elapse->AutoIdx)
//                ->where('iToqty',$scan_min_vol->min_vol)
//                ->select('frate')
//                ->first();
//            $total_min_scan =($rate_min_scan->frate * $scan_min_vol->min_vol);
//            $rate_scan =number_format($rate_min_scan->frate,3);

//            $data->comb_rates_scan = $rate_scan;
//            $data->total_scan = $total_min_scan;  

//        }
//        }
//       }
//     }

       
       
//     $bill_scan_noncon =$billing_scan_non_con[0] ?? '';
//     $scan_noncon =(object) $bill_scan_noncon;

//     $t_scan_cmr = $scan_noncon->tscancmr;
//     $t_scan_pmr = $scan_noncon->tscanpmr;
//     $tscunits = $scan_noncon-> scnunit;
//     $scan_rate =$scan_noncon->comb_rates_scan;
//     $min_scan_vol =$scan_noncon->scan_min_vol;
//     $tbillmeter = "BILLSCN";
//     $treadingdate = "1900-01-01";
//     $total_scan_con =$scan_noncon->total_scan;

//     if($tax_rate->TaxRate > 0){
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_scan_con;
//         $tlineamttax = ($total_scan_con * ($tax_rate->TaxRate / 100));
//         $tlineamtincl =($total_scan_con * (1 + $tax_rate->TaxRate / 100));
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_scan_con;
//         $tfclineamttax = ($total_scan_con * ($tax_rate->TaxRate / 100));
//         $tfclineamtincl = ($total_scan_con * (1 + $tax_rate->TaxRate / 100));
//         $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tlineamttax = $tfclineamttax * $exc_rate;
//         $tlineamtincl = $tfclineamtincl * $exc_rate;
//       }
//     }
//     else{
//       if($customer_details->icurrencyid ==0){
//         $tlineamtexcl = $total_scan_con;
//         $tlineamttax = 0;
//         $tlineamtincl =$total_scan_con ;
//         $tfclineamtexcl = 0;
//         $tfclineamttax = 0;
//         $tfclineamtincl = 0;
//       }
//       else{
//         $tfclineamtexcl = $total_scan_con;
//         $tfclineamttax = 0;
//         $tfclineamtincl = $total_scan_con;
//         $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//         $tlineamttax = 0;
//         $tlineamtincl = $tfclineamtincl * $exc_rate;
//       }


//     }

//     $tstockid = 17987;
//     $tlinenote ="CMR: $t_scan_cmr\r\nPMR: $t_scan_pmr\r\nCOPIES DONE: $tscunits\r\n  MIN. VOL: $min_scan_vol\r\nRATE: $scan_rate";
//     $tlinedesc = "Scan Billing for $scan_noncon->cDescription ($scan_noncon->ucSASerialNo)";

//     $_btblInvoiceLines_scan = new _btblInvoiceLines;
//        $_btblInvoiceLines_scan->iInvoiceID =$invoice_id;
//        $_btblInvoiceLines_scan->iOrigLineID =0;
//        $_btblInvoiceLines_scan->iGrvLineID =0;
//        $_btblInvoiceLines_scan->iLineDocketMode =0; 
//        $_btblInvoiceLines_scan->cDescription =$tlinedesc; 
//        $_btblInvoiceLines_scan->iUnitsOfMeasureStockingID=0; 
//        $_btblInvoiceLines_scan->iUnitsOfMeasureCategoryID=0;
//        $_btblInvoiceLines_scan->iUnitsOfMeasureID=0;
//        $_btblInvoiceLines_scan->fQuantity=1;
//        $_btblInvoiceLines_scan->fQtyChange=1;
//        $_btblInvoiceLines_scan->fQtyToProcess=1; 
//        $_btblInvoiceLines_scan->fQtyLastProcess=0; 
//        $_btblInvoiceLines_scan->fQtyProcessed =0; 
//        $_btblInvoiceLines_scan->fQtyReserved=0; 
//        $_btblInvoiceLines_scan->fQtyReservedChange =0;
//        $_btblInvoiceLines_scan->cLineNotes=$tlinenote; 
//        $_btblInvoiceLines_scan->fUnitPriceExcl=$tlineamtexcl; 
//        $_btblInvoiceLines_scan->fUnitPriceIncl=$tlineamtincl;
//        $_btblInvoiceLines_scan->iUnitPriceOverrideReasonID=0; 
//        $_btblInvoiceLines_scan->fUnitCost=0;
//        $_btblInvoiceLines_scan->fLineDiscount=0; 
//        $_btblInvoiceLines_scan->iLineDiscountReasonID=0;
//        $_btblInvoiceLines_scan->iReturnReasonID=0; 
//        $_btblInvoiceLines_scan->fTaxRate=$tax_rate->TaxRate; 
//        $_btblInvoiceLines_scan->bIsSerialItem=0; 
//        $_btblInvoiceLines_scan->bIsWhseItem=1;
//        $_btblInvoiceLines_scan->fAddCost=0; 
//        $_btblInvoiceLines_scan->cTradeinItem='';
//        $_btblInvoiceLines_scan->iStockCodeID=$tstockid; 
//        $_btblInvoiceLines_scan->iJobID=0;
//        $_btblInvoiceLines_scan->iWarehouseID=6;
//        $_btblInvoiceLines_scan->iTaxTypeID=$customer_details->ideftaxtypeid;
//        $_btblInvoiceLines_scan->iPriceListNameID=1;
//        $_btblInvoiceLines_scan->fQuantityLineTotIncl=$tlineamtincl;
//        $_btblInvoiceLines_scan->fQuantityLineTotExcl=$tlineamtexcl;
//        $_btblInvoiceLines_scan->fQuantityLineTotInclNoDisc=$tlineamtincl;
//        $_btblInvoiceLines_scan->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//        $_btblInvoiceLines_scan->fQuantityLineTaxAmount =$tlineamttax; 
//        $_btblInvoiceLines_scan->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotIncl =$tlineamtincl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotExcl =$tlineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTaxAmount =$tlineamttax; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotIncl =$tlineamtincl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmount =$tlineamttax; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotIncl=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotExcl =0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclNoDisc=0;
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclNoDisc=0;
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmount=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountNoDisc=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotIncl=0;
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotExcl=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotInclNoDisc=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotExclNoDisc=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmount=0;
//        $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountNoDisc=0; 
//        $_btblInvoiceLines_scan->fUnitPriceExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_scan->fUnitPriceInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fUnitCostForeign=0;
//        $_btblInvoiceLines_scan->fAddCostForeign=0;
//        $_btblInvoiceLines_scan->fQuantityLineTotInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_scan->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//        $_btblInvoiceLines_scan->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//        $_btblInvoiceLines_scan->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//        $_btblInvoiceLines_scan->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//        $_btblInvoiceLines_scan->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclForeign=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclForeign=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountForeign=0; 
//        $_btblInvoiceLines_scan->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotInclForeign=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotExclForeign=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotInclNoDiscForeign=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTotExclNoDiscForeign=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountForeign=0; 
//        $_btblInvoiceLines_scan->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//        $_btblInvoiceLines_scan->iLineRepID=$customer_details->repid; 
//        $_btblInvoiceLines_scan->iLineProjectID=2; 
//        $_btblInvoiceLines_scan->iLedgerAccountID=0; 
//        $_btblInvoiceLines_scan->IModule=0;
//        $_btblInvoiceLines_scan->bChargeCom=1;
//        $_btblInvoiceLines_scan->bIsLotItem=0;
//       //  $_btblInvoiceLines_scan->iLotID=0;
//       //  $_btblInvoiceLines_scan->cLotNumber='';
//       //  $_btblInvoiceLines_scan->dLotExpiryDate=null;
//        $_btblInvoiceLines_scan->iMFPID=0;
//        $_btblInvoiceLines_scan->iLineID=1;
//        $_btblInvoiceLines_scan->iLinkedLineID=0;
//        $_btblInvoiceLines_scan->fQtyLinkedUsed=null;
//        $_btblInvoiceLines_scan->fUnitPriceInclOrig=null;
//        $_btblInvoiceLines_scan->fUnitPriceExclOrig=Null;
//        $_btblInvoiceLines_scan->fUnitPriceInclForeignOrig=Null;
//        $_btblInvoiceLines_scan->fUnitPriceExclForeignOrig=0;
//        $_btblInvoiceLines_scan->iDeliveryMethodID=0;
//        $_btblInvoiceLines_scan->fQtyDeliver=0;
//        $_btblInvoiceLines_scan->dDeliveryDate=$today;
//        $_btblInvoiceLines_scan->iDeliveryStatus=0;
//        $_btblInvoiceLines_scan->fQtyForDelivery=0;
//        $_btblInvoiceLines_scan->bPromotionApplied=0;
//        $_btblInvoiceLines_scan->fPromotionPriceExcl=0;
//        $_btblInvoiceLines_scan->fPromotionPriceIncl=0;
//        $_btblInvoiceLines_scan->cPromotionCode=0;
//        $_btblInvoiceLines_scan->iSOLinkedPOLineID=0;
//        $_btblInvoiceLines_scan->fLength=0;
//        $_btblInvoiceLines_scan->fWidth=0;
//        $_btblInvoiceLines_scan->fHeight=0;
//        $_btblInvoiceLines_scan->iPieces=0;
//        $_btblInvoiceLines_scan->iPiecesToProcess=0;
//        $_btblInvoiceLines_scan->iPiecesLastProcess=0;
//        $_btblInvoiceLines_scan->iPiecesProcessed=0;
//        $_btblInvoiceLines_scan->iPiecesReserved=0;
//        $_btblInvoiceLines_scan->iPiecesDeliver=0;
//        $_btblInvoiceLines_scan->iPiecesForDelivery=0;
//        $_btblInvoiceLines_scan->fQuantityUR=1;
//        $_btblInvoiceLines_scan->fQtyChangeUR=1;
//        $_btblInvoiceLines_scan->fQtyToProcessUR=1;
//        $_btblInvoiceLines_scan->fQtyLastProcessUR=0;
//        $_btblInvoiceLines_scan->fQtyProcessedUR=0;
//        $_btblInvoiceLines_scan->fQtyReservedUR=0;
//        $_btblInvoiceLines_scan->fQtyReservedChangeUR=0;
//        $_btblInvoiceLines_scan->fQtyDeliverUR=0;
//        $_btblInvoiceLines_scan->fQtyForDeliveryUR=0;
//        $_btblInvoiceLines_scan->fQtyLinkedUsedUR=0;
//        $_btblInvoiceLines_scan->iPiecesLinkedUsed=0;
//        $_btblInvoiceLines_scan->iSalesWhseID=0;
//        $_btblInvoiceLines_scan->_btblInvoiceLines_iBranchID=0;
//        $_btblInvoiceLines_scan->udIDSOrdTxCMReadingDate=$today;
//        $_btblInvoiceLines_scan->uiIDSOrdTxCMPrevReading=$t_scan_pmr;
//        $_btblInvoiceLines_scan->uiIDSOrdTxCMCurrReading=$t_scan_cmr;
//        $_btblInvoiceLines_scan->ucIDSOrdTxCMMinVol=$min_scan_vol;
//        $_btblInvoiceLines_scan->ucIDSOrdTxCMRates=$scan_rate;
//        $_btblInvoiceLines_scan->ucIDSOrdTxCMServiceAsset=$total_scan_con->cCode;
//        $_btblInvoiceLines_scan->ucIDSOrdTxCMMeterType="BILLSCN";

//        $_btblInvoiceLines_scan->save();
//   }



  

  
//       // calculate A3MONO non CON

//       $asset_id = DB::table('_smtblServiceAsset As sa')
//       ->where('cCode',$value['ucSABillingAsset'])
//       ->select('AutoIdx')
//       ->first();

//       //A3 MONO
//       $a3Mono_counter_elapse =DB::table('_smtblcounterelapsed')
//       ->where('iServiceAssetId',$asset_id->AutoIdx)
//       ->where('cCode','A3BILLMON')
//       ->select('AutoIdx')
//       ->first();

 
 
//     if(!empty($a3Mono_counter_elapse))
//     {
//     $billing_a3mono_noncon = DB::table('_bvARAccountsFull As cl')
//       ->where('DCLink',$customer_details->dclink)
//       ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//       ->whereYear('ReadingDate', '=', $today->year)
//       ->whereMonth('ReadingDate', '=', $today->month)

//       ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//       DB::raw('sum(cmr.A3MPMR) as a3mpmr'),
//       DB::raw('sum(cmr.A3MCMR) as a3mcmr'),
//       DB::raw('sum(cmr.A3MCMR - cmr.A3MPMR) as a3monunit'),'sa.cDescription','sa.ucSASerialNo','sa.cCode'
    
//       )
//       ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//       ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//       ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cDescription','sa.ucSASerialNo','sa.cCode')
//       ->get();


//       foreach($billing_a3mono_noncon as $data){
//         $asset_id = DB::table('_smtblServiceAsset As sa')
//         ->where('cCode',$data->cCode)
//         ->select('AutoIdx')
//         ->first();            
      

//         // A3  Mono consolidated calculation

//          //A3 MONO
//          $a3Mono_counter_elapse =DB::table('_smtblcounterelapsed')
//          ->where('iServiceAssetId',$asset_id->AutoIdx)
//          ->where('cCode','A3BILLMON')
//          ->select('AutoIdx')
//          ->first();

         
//          $a3mono_min_vol = _smtblrateslab::where('iBillingID',$a3Mono_counter_elapse->AutoIdx ?? '')
//           ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//           ->groupBy('fRate',DB::raw('(iToqty)'))
//           ->first();
//           if(!empty($a3mono_min_vol))
//           {

//      if($a3mono_min_vol->min_vol >=100000000){
//          $data->a3mono_min_vol = null;
//         }
//      else{
//          $data->a3mono_min_vol =$a3mono_min_vol->min_vol ?? null;
//      }
//      if( $a3mono_min_vol->min_vol  >=100000000 ){

//          $total_frate_a3mono =($a3mono_min_vol->fRate * $data->a3monunit);
//          $rate_frate_a3mono =number_format($a3mono_min_vol->fRate,2);
       

//          $data->comb_rates_a3m = $rate_frate_a3mono;
//          $data->total_a3m = $total_frate_a3mono;            


//      }
//      else{
//       $diff_a3mon = ($data->a3munit - $A3min_vol->min_vol);
//       if($diff_a3mon > 0){
//           $rate_min_a3m =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//           ->where('iToqty',$A3min_vol->min_vol)
//           ->select('frate')
//           ->first();
//           $total_min_a3m =($rate_min_a3m->frate * $A3min_vol->min_vol);

//           $rate_a3m =number_format($rate_min_a3m->frate,2);
          
//           //slab two starts
//           $Increment_1a3m =$A3min_vol->min_vol +1; 

  
//       // we get the rate for the next slab and their readings
//       $next_slab_a3m =_smtblrateslab::where('iBillingID',$A3counter_elapse->AutoIdx)
//       ->where('iFromQty','<=',$Increment_1a3m)
//       ->where('iToqty','>=',$Increment_1a3m)
//       ->select('iFromQty','iToqty','fRate')
//       ->first();
 
//           //  we get the difference of this slab
//       $diff_slab1_a3m =($next_slab_a3m->iToqty - $next_slab_a3m->iFromQty)+1;


//       //  we check if the remainder fulls fall in this slab or exceed
//       $diff_bil_slab_a3m =$diff_a3mon -$diff_slab1_a3m;           

//      //counts fits fully in this slab the difference is negative
//       if($diff_bil_slab_a3m < 0){
//           $total_slab_1_a3m =($diff_a3mon * $next_slab_a3m->fRate );

//           $rate_2_a3m =number_format($next_slab_a3m->fRate,2);            

//       // two slabs used             
//       $comb_rates_a3m =$rate_a3m.' | '.$rate_2_a3m;
//       $total_a3m =($total_slab_1_a3m +$total_min_a3m);

//       $data->comb_rates_a3m = $comb_rates_a3m;
//       $data->total_a3m = $total_a3m;          
  
//   }
//   }

//   else{
//       $rate_min_a3m =_smtblrateslab::where('iBillingID',$counter_elapse->AutoIdx)
//           ->where('iToqty',$A3min_vol->min_vol)
//           ->select('frate')
//           ->first();
//       $total_min_a3m =($rate_min_a3m->frate * $A3min_vol->min_vol);
//       $rate_a3m =number_format($rate_min_a3m->frate,2);

//       $data->comb_rates_a3m = $rate_a3m;
//       $data->total_a3m = $total_min_a3m;  

//   }
//   }
// }
// }
  
 
//   $bill_a3mono_noncon =$billing_a3mono_noncon[0] ?? '';
//   $a3mono_noncon =(object) $bill_a3mono_noncon;

//   // dd($a3mono_noncon);
// $t_a3m_cmr = $a3mono_noncon->a3mcmr;
//  $t_a3m_pmr = $a3mono_noncon->a3mpmr;
//  $tA3munits = $a3mono_noncon-> a3monunit;
//  $A3m_rate =$a3mono_noncon->comb_rates_a3m;
//  $min_A3m_vol =$a3mono_noncon->a3mono_min_vol;
//  $tbillmeter = "BILLA3MON";
//  $treadingdate = "1900-01-01";
//  $total_A3m_con =$a3mono_noncon->total_a3m;


//  if($tax_rate->TaxRate > 0){
//   if($customer_details->icurrencyid ==0){
//     $tlineamtexcl = $total_A3m_con;
//     $tlineamttax = ($total_A3m_con * ($tax_rate->TaxRate / 100));
//     $tlineamtincl =($total_A3m_con * (1 + $tax_rate->TaxRate / 100));
//     $tfclineamtexcl = 0;
//     $tfclineamttax = 0;
//     $tfclineamtincl = 0;
//   }
//   else{
//     $tfclineamtexcl = $total_A3m_con;
//     $tfclineamttax = ($total_A3m_con * ($tax_rate->TaxRate / 100));
//     $tfclineamtincl = ($total_A3m_con * (1 + $tax_rate->TaxRate / 100));
//     $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//     $tlineamttax = $tfclineamttax * $exc_rate;
//     $tlineamtincl = $tfclineamtincl * $exc_rate;
//   }
// }
// else{
//   if($customer_details->icurrencyid ==0){
//     $tlineamtexcl = $total_A3m_con;
//     $tlineamttax = 0;
//     $tlineamtincl =$total_A3m_con;
//     $tfclineamtexcl = 0;
//     $tfclineamttax = 0;
//     $tfclineamtincl = 0;
//   }
//   else{
//     $tfclineamtexcl = $total_A3m_con;
//     $tfclineamttax = 0;
//     $tfclineamtincl = $total_A3m_con;
//     $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//     $tlineamttax = 0;
//     $tlineamtincl = $tfclineamtincl * $exc_rate;
//   }




// }
//     $tstockid = 17987;
//     $tlinenote ="CMR: $t_a3m_cmr\r\nPMR: $t_a3m_pmr\r\nCOPIES DONE: $tA3munits\r\nMIN. VOL: $min_A3m_vol\r\nRATE: $A3m_rate";
//     $tlinedesc = "A3 Mono Billing for $a3mono_noncon->cDescription ($a3mono_noncon->ucSASerialNo)";


//     $_btblInvoiceLines_A3mono = new _btblInvoiceLines;
//        $_btblInvoiceLines_A3mono->iInvoiceID =$invoice_id;
//        $_btblInvoiceLines_A3mono->iOrigLineID =0;
//        $_btblInvoiceLines_A3mono->iGrvLineID =0;
//        $_btblInvoiceLines_A3mono->iLineDocketMode =0; 
//        $_btblInvoiceLines_A3mono->cDescription =$tlinedesc; 
//        $_btblInvoiceLines_A3mono->iUnitsOfMeasureStockingID=0; 
//        $_btblInvoiceLines_A3mono->iUnitsOfMeasureCategoryID=0;
//        $_btblInvoiceLines_A3mono->iUnitsOfMeasureID=0;
//        $_btblInvoiceLines_A3mono->fQuantity=1;
//        $_btblInvoiceLines_A3mono->fQtyChange=1;
//        $_btblInvoiceLines_A3mono->fQtyToProcess=1; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcess=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessed =0; 
//        $_btblInvoiceLines_A3mono->fQtyReserved=0; 
//        $_btblInvoiceLines_A3mono->fQtyReservedChange =0;
//        $_btblInvoiceLines_A3mono->cLineNotes=$tlinenote; 
//        $_btblInvoiceLines_A3mono->fUnitPriceExcl=$tlineamtexcl; 
//        $_btblInvoiceLines_A3mono->fUnitPriceIncl=$tlineamtincl;
//        $_btblInvoiceLines_A3mono->iUnitPriceOverrideReasonID=0; 
//        $_btblInvoiceLines_A3mono->fUnitCost=0;
//        $_btblInvoiceLines_A3mono->fLineDiscount=0; 
//        $_btblInvoiceLines_A3mono->iLineDiscountReasonID=0;
//        $_btblInvoiceLines_A3mono->iReturnReasonID=0; 
//        $_btblInvoiceLines_A3mono->fTaxRate=$tax_rate->TaxRate; 
//        $_btblInvoiceLines_A3mono->bIsSerialItem=0; 
//        $_btblInvoiceLines_A3mono->bIsWhseItem=1;
//        $_btblInvoiceLines_A3mono->fAddCost=0; 
//        $_btblInvoiceLines_A3mono->cTradeinItem='';
//        $_btblInvoiceLines_A3mono->iStockCodeID=$tstockid; 
//        $_btblInvoiceLines_A3mono->iJobID=0;
//        $_btblInvoiceLines_A3mono->iWarehouseID=6;
//        $_btblInvoiceLines_A3mono->iTaxTypeID=$customer_details->ideftaxtypeid;
//        $_btblInvoiceLines_A3mono->iPriceListNameID=1;
//        $_btblInvoiceLines_A3mono->fQuantityLineTotIncl=$tlineamtincl;
//        $_btblInvoiceLines_A3mono->fQuantityLineTotExcl=$tlineamtexcl;
//        $_btblInvoiceLines_A3mono->fQuantityLineTotInclNoDisc=$tlineamtincl;
//        $_btblInvoiceLines_A3mono->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQuantityLineTaxAmount =$tlineamttax; 
//        $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotIncl =$tlineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotExcl =$tlineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmount =$tlineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotIncl =$tlineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmount =$tlineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotIncl=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExcl =0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclNoDisc=0;
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclNoDisc=0;
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmount=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountNoDisc=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotIncl=0;
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExcl=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclNoDisc=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclNoDisc=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmount=0;
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountNoDisc=0; 
//        $_btblInvoiceLines_A3mono->fUnitPriceExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_A3mono->fUnitPriceInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fUnitCostForeign=0;
//        $_btblInvoiceLines_A3mono->fAddCostForeign=0;
//        $_btblInvoiceLines_A3mono->fQuantityLineTotInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//        $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//        $_btblInvoiceLines_A3mono->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//        $_btblInvoiceLines_A3mono->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotInclNoDiscForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTotExclNoDiscForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountForeign=0; 
//        $_btblInvoiceLines_A3mono->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//        $_btblInvoiceLines_A3mono->iLineRepID=$customer_details->repid; 
//        $_btblInvoiceLines_A3mono->iLineProjectID=2; 
//        $_btblInvoiceLines_A3mono->iLedgerAccountID=0; 
//        $_btblInvoiceLines_A3mono->IModule=0;
//        $_btblInvoiceLines_A3mono->bChargeCom=1;
//        $_btblInvoiceLines_A3mono->bIsLotItem=0;
//       //  $_btblInvoiceLines_A3mono->iLotID=0;
//       //  $_btblInvoiceLines_A3mono->cLotNumber='';
//       //  $_btblInvoiceLines_A3mono->dLotExpiryDate=null;
//        $_btblInvoiceLines_A3mono->iMFPID=0;
//        $_btblInvoiceLines_A3mono->iLineID=1;
//        $_btblInvoiceLines_A3mono->iLinkedLineID=0;
//        $_btblInvoiceLines_A3mono->fQtyLinkedUsed=null;
//        $_btblInvoiceLines_A3mono->fUnitPriceInclOrig=null;
//        $_btblInvoiceLines_A3mono->fUnitPriceExclOrig=Null;
//        $_btblInvoiceLines_A3mono->fUnitPriceInclForeignOrig=Null;
//        $_btblInvoiceLines_A3mono->fUnitPriceExclForeignOrig=0;
//        $_btblInvoiceLines_A3mono->iDeliveryMethodID=0;
//        $_btblInvoiceLines_A3mono->fQtyDeliver=0;
//        $_btblInvoiceLines_A3mono->dDeliveryDate=$today;
//        $_btblInvoiceLines_A3mono->iDeliveryStatus=0;
//        $_btblInvoiceLines_A3mono->fQtyForDelivery=0;
//        $_btblInvoiceLines_A3mono->bPromotionApplied=0;
//        $_btblInvoiceLines_A3mono->fPromotionPriceExcl=0;
//        $_btblInvoiceLines_A3mono->fPromotionPriceIncl=0;
//        $_btblInvoiceLines_A3mono->cPromotionCode=0;
//        $_btblInvoiceLines_A3mono->iSOLinkedPOLineID=0;
//        $_btblInvoiceLines_A3mono->fLength=0;
//        $_btblInvoiceLines_A3mono->fWidth=0;
//        $_btblInvoiceLines_A3mono->fHeight=0;
//        $_btblInvoiceLines_A3mono->iPieces=0;
//        $_btblInvoiceLines_A3mono->iPiecesToProcess=0;
//        $_btblInvoiceLines_A3mono->iPiecesLastProcess=0;
//        $_btblInvoiceLines_A3mono->iPiecesProcessed=0;
//        $_btblInvoiceLines_A3mono->iPiecesReserved=0;
//        $_btblInvoiceLines_A3mono->iPiecesDeliver=0;
//        $_btblInvoiceLines_A3mono->iPiecesForDelivery=0;
//        $_btblInvoiceLines_A3mono->fQuantityUR=1;
//        $_btblInvoiceLines_A3mono->fQtyChangeUR=1;
//        $_btblInvoiceLines_A3mono->fQtyToProcessUR=1;
//        $_btblInvoiceLines_A3mono->fQtyLastProcessUR=0;
//        $_btblInvoiceLines_A3mono->fQtyProcessedUR=0;
//        $_btblInvoiceLines_A3mono->fQtyReservedUR=0;
//        $_btblInvoiceLines_A3mono->fQtyReservedChangeUR=0;
//        $_btblInvoiceLines_A3mono->fQtyDeliverUR=0;
//        $_btblInvoiceLines_A3mono->fQtyForDeliveryUR=0;
//        $_btblInvoiceLines_A3mono->fQtyLinkedUsedUR=0;
//        $_btblInvoiceLines_A3mono->iPiecesLinkedUsed=0;
//        $_btblInvoiceLines_A3mono->iSalesWhseID=0;
//        $_btblInvoiceLines_A3mono->_btblInvoiceLines_iBranchID=0;
//        $_btblInvoiceLines_A3mono->udIDSOrdTxCMReadingDate=$today;
//        $_btblInvoiceLines_A3mono->uiIDSOrdTxCMPrevReading=$t_a3m_pmr;
//        $_btblInvoiceLines_A3mono->uiIDSOrdTxCMCurrReading=$t_a3m_cmr;
//        $_btblInvoiceLines_A3mono->ucIDSOrdTxCMMinVol=$min_A3m_vol;
//        $_btblInvoiceLines_A3mono->ucIDSOrdTxCMRates=$A3m_rate;
//        $_btblInvoiceLines_A3mono->ucIDSOrdTxCMServiceAsset=$a3mono_noncon->cCode;
//        $_btblInvoiceLines_A3mono->ucIDSOrdTxCMMeterType="BILLA3MON";

//        $_btblInvoiceLines_A3mono->save();


//     }
  

//     // calculate A3 COLOR NON CONSOLIDATED
//     $asset_id = DB::table('_smtblServiceAsset As sa')
//     ->where('cCode',$value['ucSABillingAsset'])
//     ->select('AutoIdx')
//     ->first();

//     //A3 COLOR
//     $a3color_counter_elapse =DB::table('_smtblcounterelapsed')
//     ->where('iServiceAssetId',$asset_id->AutoIdx)
//     ->where('cCode','A3BILLCOL')
//     ->select('AutoIdx')
//     ->first();

//     if(!empty($a3color_counter_elapse))
//           {
//           $billing_a3color_noncon = DB::table('_bvARAccountsFull As cl')
//           ->where('DCLink',$customer_details->dclink)
//           ->where('sa.cCode', '=',$value['ucSABillingAsset']) 
//           ->whereYear('ReadingDate', '=', $today->year)
//           ->whereMonth('ReadingDate', '=', $today->month)

//           ->select('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill',
//           DB::raw('sum(cmr.A3CPMR) as a3Cpmr'),
//           DB::raw('sum(cmr.A3CCMR) as a3Ccmr'),
//           DB::raw('sum(cmr.A3CCMR - cmr.A3CPMR) as a3Colunit'),'sa.cDescription','sa.ucSASerialNo','sa.cCode'   
          
        
//           )
//           ->join('_smtblServiceAsset As sa', 'cl.DCLink', '=', 'sa.icustomerid')
//           ->join('_cplmeterreading As cmr', 'sa.autoidx', '=', 'cmr.assetid')         
//           ->groupBy('cl.name','cl.account','cl.currencycode','cl.ulARJointSeparateBill','sa.cDescription','sa.ucSASerialNo','sa.cCode')
//           ->get();

       

//           foreach($billing_a3color_noncon as $data){

//             $asset_id = DB::table('_smtblServiceAsset As sa')
//             ->where('cCode',$data->cCode)
//             ->select('AutoIdx')
//             ->first();            
          

//         // A3  COLOR consolidated calculation

//             //A3 COLOR
//             $a3color_counter_elapse =DB::table('_smtblcounterelapsed')
//             ->where('iServiceAssetId',$asset_id->AutoIdx)
//             ->where('cCode','A3BILLCOL')
//             ->select('AutoIdx')
//             ->first();

         
//             $a3color_min_vol = _smtblrateslab::where('iBillingID',$a3color_counter_elapse->AutoIdx ?? '')
//               ->select(DB::raw('min(iToqty) as min_vol'),'fRate')
//               ->groupBy('fRate')
//               ->first();

//               if(!empty($a3color_min_vol))
//               {
              
//               if($a3color_min_vol->min_vol >=100000000){
//                 $data->a3color_min_vol = null;
//                }
//             else{
//                 $data->a3color_min_vol =$a3color_min_vol->min_vol ?? null;
//             }
//             if( $a3color_min_vol->min_vol  >=100000000 ){
    
//                 $total_frate_a3color =($a3color_min_vol->fRate * $data->a3Colunit);
//                 $rate_frate_a3color =$a3color_min_vol->fRate;
              
    
//                 $data->comb_rates_a3C = $rate_frate_a3color;
//                 $data->total_a3C = $total_frate_a3color;            
    
    
//             }
            
//           else{
//             $diff_a3Col = ($data->a3cunit - $A3Cmin_vol->min_vol);
//             if($diff_a3Col > 0){
//                 $rate_min_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//                 ->where('iToqty',$A3Cmin_vol->min_vol)
//                 ->select('frate')
//                 ->first();
//                 $total_min_a3C =($rate_min_a3C->frate * $A3Cmin_vol->min_vol);

//                 $rate_a3C =number_format($rate_min_a3C->frate,2);
                
//                 //slab two starts
//                 $Increment_1a3C =$A3Cmin_vol->min_vol +1; 

        
//             // we get the rate for the next slab and their readings
//             $next_slab_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//             ->where('iFromQty','<=',$Increment_1a3C)
//             ->where('iToqty','>=',$Increment_1a3C)
//             ->select('iFromQty','iToqty','fRate')
//             ->first();
       
//                 //  we get the difference of this slab
//             $diff_slab1_a3C =($next_slab_a3C->iToqty - $next_slab_a3C->iFromQty)+1;


//             //  we check if the remainder fulls fall in this slab or exceed
//             $diff_bil_slab_a3C =$diff_a3Col -$diff_slab1_a3C;           

//            //counts fits fully in this slab the difference is negative
//             if($diff_bil_slab_a3C < 0){
//                 $total_slab_1_a3C =($diff_a3Col * $next_slab_a3C->fRate );

//                 $rate_2_a3C =number_format($next_slab_a3C->fRate,2);            

//             // two slabs used             
//             $comb_rates_a3C =$rate_a3C.' | '.$rate_2_a3C;
//             $total_a3C =($total_slab_1_a3C +$total_min_a3C);

//             $data->comb_rates_a3C = $comb_rates_a3C;
//             $data->total_a3C = $total_a3C;          
        
//         }
//         }

//         else{
//             $rate_min_a3C =_smtblrateslab::where('iBillingID',$A3Ccounter_elapse->AutoIdx)
//                 ->where('iToqty',$A3Cmin_vol->min_vol)
//                 ->select('frate')
//                 ->first();
//             $total_min_a3C =($rate_min_a3C->frate * $A3Cmin_vol->min_vol);
//             $rate_a3C =number_format($rate_min_a3C->frate,2);

//             $data->comb_rates_a3C = $rate_a3C;
//             $data->total_a3C = $total_min_a3C;

//         }
//         }   
//       } 
//     }            

        

         
//       $bill_a3color_noncon =$billing_a3color_noncon[0] ?? '';
//       $a3color_noncon =(object) $bill_a3color_noncon;

    
//     $t_a3C_cmr = $a3color_noncon->a3Ccmr;
//     $t_a3C_pmr = $a3color_noncon->a3Cpmr;
//     $tA3Cunits = $a3color_noncon-> a3Colunit;
//     $A3C_rate =$a3color_noncon->comb_rates_a3C;
//     $min_A3C_vol =$a3color_noncon->a3color_min_vol;
//     $tbillmeter = "BILLA3COL";
//     $treadingdate = "1900-01-01";
//     $total_A3C_con =$a3color_noncon->total_a3C;

//     if($tax_rate->TaxRate > 0){
//      if($customer_details->icurrencyid ==0){
//        $tlineamtexcl = $total_A3C_con;
//        $tlineamttax = ($total_A3C_con * ($tax_rate->TaxRate / 100));
//        $tlineamtincl =($total_A3C_con * (1 + $tax_rate->TaxRate / 100));
//        $tfclineamtexcl = 0;
//        $tfclineamttax = 0;
//        $tfclineamtincl = 0;
//      }
//      else{
//        $tfclineamtexcl = $total_A3C_con;
//        $tfclineamttax = ($total_A3C_con * ($tax_rate->TaxRate / 100));
//        $tfclineamtincl = ($total_A3C_con * (1 + $tax_rate->TaxRate / 100));
//        $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//        $tlineamttax = $tfclineamttax * $exc_rate;
//        $tlineamtincl = $tfclineamtincl * $exc_rate;
//      }
//    }
//    else{
//     if($customer_details->icurrencyid ==0){
//       $tlineamtexcl = $total_A3C_con;
//       $tlineamttax = 0;
//       $tlineamtincl =$total_A3C_con;
//       $tfclineamtexcl = 0;
//       $tfclineamttax = 0;
//       $tfclineamtincl = 0;
//     }
//     else{
//       $tfclineamtexcl = $total_A3C_con;
//       $tfclineamttax = 0;
//       $tfclineamtincl = $total_A3C_con;
//       $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//       $tlineamttax = 0;
//       $tlineamtincl = $tfclineamtincl * $exc_rate;
//     }



//    }
//    $tstockid = 17987;
//    $tlinenote ="CMR: $t_a3C_cmr\r\nPMR: $t_a3C_pmr\r\nCOPIES DONE: $tA3Cunits\r\nMIN. VOL: $min_A3C_vol\r\nRATE: $A3C_rate";
//     $tlinedesc = "A3 Color Billing for $a3color_noncon->cDescription ($a3color_noncon->ucSASerialNo)";

// $_btblInvoiceLines_A3Color = new _btblInvoiceLines;
// $_btblInvoiceLines_A3Color->iInvoiceID =$invoice_id;
// $_btblInvoiceLines_A3Color->iOrigLineID =0;
// $_btblInvoiceLines_A3Color->iGrvLineID =0;
// $_btblInvoiceLines_A3Color->iLineDocketMode =0; 
// $_btblInvoiceLines_A3Color->cDescription =$tlinedesc; 
// $_btblInvoiceLines_A3Color->iUnitsOfMeasureStockingID=0; 
// $_btblInvoiceLines_A3Color->iUnitsOfMeasureCategoryID=0;
// $_btblInvoiceLines_A3Color->iUnitsOfMeasureID=0;
// $_btblInvoiceLines_A3Color->fQuantity=1;
// $_btblInvoiceLines_A3Color->fQtyChange=1;
// $_btblInvoiceLines_A3Color->fQtyToProcess=1; 
// $_btblInvoiceLines_A3Color->fQtyLastProcess=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessed =0; 
// $_btblInvoiceLines_A3Color->fQtyReserved=0; 
// $_btblInvoiceLines_A3Color->fQtyReservedChange =0;
// $_btblInvoiceLines_A3Color->cLineNotes=$tlinenote; 
// $_btblInvoiceLines_A3Color->fUnitPriceExcl=$tlineamtexcl; 
// $_btblInvoiceLines_A3Color->fUnitPriceIncl=$tlineamtincl;
// $_btblInvoiceLines_A3Color->iUnitPriceOverrideReasonID=0; 
// $_btblInvoiceLines_A3Color->fUnitCost=0;
// $_btblInvoiceLines_A3Color->fLineDiscount=0; 
// $_btblInvoiceLines_A3Color->iLineDiscountReasonID=0;
// $_btblInvoiceLines_A3Color->iReturnReasonID=0; 
// $_btblInvoiceLines_A3Color->fTaxRate=$tax_rate->TaxRate; 
// $_btblInvoiceLines_A3Color->bIsSerialItem=0; 
// $_btblInvoiceLines_A3Color->bIsWhseItem=1;
// $_btblInvoiceLines_A3Color->fAddCost=0; 
// $_btblInvoiceLines_A3Color->cTradeinItem='';
// $_btblInvoiceLines_A3Color->iStockCodeID=$tstockid; 
// $_btblInvoiceLines_A3Color->iJobID=0;
// $_btblInvoiceLines_A3Color->iWarehouseID=6;
// $_btblInvoiceLines_A3Color->iTaxTypeID=$customer_details->ideftaxtypeid;
// $_btblInvoiceLines_A3Color->iPriceListNameID=1;
// $_btblInvoiceLines_A3Color->fQuantityLineTotIncl=$tlineamtincl;
// $_btblInvoiceLines_A3Color->fQuantityLineTotExcl=$tlineamtexcl;
// $_btblInvoiceLines_A3Color->fQuantityLineTotInclNoDisc=$tlineamtincl;
// $_btblInvoiceLines_A3Color->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
// $_btblInvoiceLines_A3Color->fQuantityLineTaxAmount =$tlineamttax; 
// $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotIncl =$tlineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotExcl =$tlineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmount =$tlineamttax; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotIncl =$tlineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExcl =$tlineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmount =$tlineamttax; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotIncl=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExcl =0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclNoDisc=0;
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclNoDisc=0;
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmount=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountNoDisc=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotIncl=0;
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExcl=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclNoDisc=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclNoDisc=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmount=0;
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountNoDisc=0; 
// $_btblInvoiceLines_A3Color->fUnitPriceExclForeign=$tfclineamtexcl; 
// $_btblInvoiceLines_A3Color->fUnitPriceInclForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fUnitCostForeign=0;
// $_btblInvoiceLines_A3Color->fAddCostForeign=0;
// $_btblInvoiceLines_A3Color->fQuantityLineTotInclForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fQuantityLineTotExclForeign=$tfclineamtexcl; 
// $_btblInvoiceLines_A3Color->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
// $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountForeign=$tfclineamttax; 
// $_btblInvoiceLines_A3Color->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
// $_btblInvoiceLines_A3Color->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
// $_btblInvoiceLines_A3Color->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotInclNoDiscForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTotExclNoDiscForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotInclNoDiscForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTotExclNoDiscForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountForeign=0; 
// $_btblInvoiceLines_A3Color->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
// $_btblInvoiceLines_A3Color->iLineRepID=$customer_details->repid; 
// $_btblInvoiceLines_A3Color->iLineProjectID=2; 
// $_btblInvoiceLines_A3Color->iLedgerAccountID=0; 
// $_btblInvoiceLines_A3Color->IModule=0;
// $_btblInvoiceLines_A3Color->bChargeCom=1;
// $_btblInvoiceLines_A3Color->bIsLotItem=0;
// // $_btblInvoiceLines_A3Color->iLotID=0;
// // $_btblInvoiceLines_A3Color->cLotNumber='';
// // $_btblInvoiceLines_A3Color->dLotExpiryDate=null;
// $_btblInvoiceLines_A3Color->iMFPID=0;
// $_btblInvoiceLines_A3Color->iLineID=1;
// $_btblInvoiceLines_A3Color->iLinkedLineID=0;
// $_btblInvoiceLines_A3Color->fQtyLinkedUsed=null;
// $_btblInvoiceLines_A3Color->fUnitPriceInclOrig=null;
// $_btblInvoiceLines_A3Color->fUnitPriceExclOrig=Null;
// $_btblInvoiceLines_A3Color->fUnitPriceInclForeignOrig=Null;
// $_btblInvoiceLines_A3Color->fUnitPriceExclForeignOrig=0;
// $_btblInvoiceLines_A3Color->iDeliveryMethodID=0;
// $_btblInvoiceLines_A3Color->fQtyDeliver=0;
// $_btblInvoiceLines_A3Color->dDeliveryDate=$today;
// $_btblInvoiceLines_A3Color->iDeliveryStatus=0;
// $_btblInvoiceLines_A3Color->fQtyForDelivery=0;
// $_btblInvoiceLines_A3Color->bPromotionApplied=0;
// $_btblInvoiceLines_A3Color->fPromotionPriceExcl=0;
// $_btblInvoiceLines_A3Color->fPromotionPriceIncl=0;
// $_btblInvoiceLines_A3Color->cPromotionCode=0;
// $_btblInvoiceLines_A3Color->iSOLinkedPOLineID=0;
// $_btblInvoiceLines_A3Color->fLength=0;
// $_btblInvoiceLines_A3Color->fWidth=0;
// $_btblInvoiceLines_A3Color->fHeight=0;
// $_btblInvoiceLines_A3Color->iPieces=0;
// $_btblInvoiceLines_A3Color->iPiecesToProcess=0;
// $_btblInvoiceLines_A3Color->iPiecesLastProcess=0;
// $_btblInvoiceLines_A3Color->iPiecesProcessed=0;
// $_btblInvoiceLines_A3Color->iPiecesReserved=0;
// $_btblInvoiceLines_A3Color->iPiecesDeliver=0;
// $_btblInvoiceLines_A3Color->iPiecesForDelivery=0;
// $_btblInvoiceLines_A3Color->fQuantityUR=1;
// $_btblInvoiceLines_A3Color->fQtyChangeUR=1;
// $_btblInvoiceLines_A3Color->fQtyToProcessUR=1;
// $_btblInvoiceLines_A3Color->fQtyLastProcessUR=0;
// $_btblInvoiceLines_A3Color->fQtyProcessedUR=0;
// $_btblInvoiceLines_A3Color->fQtyReservedUR=0;
// $_btblInvoiceLines_A3Color->fQtyReservedChangeUR=0;
// $_btblInvoiceLines_A3Color->fQtyDeliverUR=0;
// $_btblInvoiceLines_A3Color->fQtyForDeliveryUR=0;
// $_btblInvoiceLines_A3Color->fQtyLinkedUsedUR=0;
// $_btblInvoiceLines_A3Color->iPiecesLinkedUsed=0;
// $_btblInvoiceLines_A3Color->iSalesWhseID=0;
// $_btblInvoiceLines_A3Color->_btblInvoiceLines_iBranchID=0;
// $_btblInvoiceLines_A3Color->udIDSOrdTxCMReadingDate=$today;
// $_btblInvoiceLines_A3Color->uiIDSOrdTxCMPrevReading=$t_a3C_pmr;
// $_btblInvoiceLines_A3Color->uiIDSOrdTxCMCurrReading=$t_a3C_cmr;
// $_btblInvoiceLines_A3Color->ucIDSOrdTxCMMinVol=$min_A3C_vol;
// $_btblInvoiceLines_A3Color->ucIDSOrdTxCMRates=$A3C_rate;
// $_btblInvoiceLines_A3Color->ucIDSOrdTxCMServiceAsset=$a3color_noncon->cCode;
// $_btblInvoiceLines_A3Color->ucIDSOrdTxCMMeterType="BILLA3COL";

// $_btblInvoiceLines_A3Color->save();              

//   }



   

   
//       //  calculating rental Charges

//       $rental_amount = $value['rental_charges'];
         

//         $rental_charge = $rental_amount;

//         if($rental_charge > 0){
          
//           $tbillmeter = "RENTAL";
//           $rental_amt = $rental_charge;
//           if($tax_rate->TaxRate > 0){
//             if($customer_details->icurrencyid ==0){
//               $tlineamtexcl = $rental_amt;
//               $tlineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
//               $tlineamtincl =($rental_amt * (1 + $tax_rate->TaxRate / 100));
//               $tfclineamtexcl = 0;
//               $tfclineamttax = 0;
//               $tfclineamtincl = 0;
//             }
//             else{
//               $tfclineamtexcl = $rental_amt;
//               $tfclineamttax = ($rental_amt * ($tax_rate->TaxRate / 100));
//               $tfclineamtincl = ($rental_amt * (1 + $tax_rate->TaxRate / 100));
//               $tfclineamtexcl = $tfclineamtexcl *$exc_rate;
//               $tfclineamttax = $tfclineamttax * $exc_rate;
//               $tfclineamtincl = $tfclineamtincl * $exc_rate;
//             }
//           }
//           else{
//             if($customer_details->icurrencyid ==0){
//               $tlineamtexcl = $rental_amt;
//               $tlineamttax = 0;
//               $tlineamtincl =$rental_amt;
//               $tfclineamtexcl = 0;
//               $tfclineamttax = 0;
//               $tfclineamtincl = 0;
//             }
//             else{
//               $tfclineamtexcl = $rental_amt;
//               $tfclineamttax = 0;
//               $tfclineamtincl = $rental_amt;
//               $tlineamtexcl = $tfclineamtexcl *$exc_rate;
//               $tlineamttax = 0;
//               $tlineamtincl = $tfclineamtincl * $exc_rate;
//             }

//           }

//           $tlinenote = "";                    
//           $tlinedesc = "Rental for $month $today->year";          
//           $tstockid = 17987;

//           $_btblInvoiceLines_rental = new _btblInvoiceLines;
//           $_btblInvoiceLines_rental->iInvoiceID =$invoice_id->autoindex;
//           $_btblInvoiceLines_rental->iOrigLineID =0;
//           $_btblInvoiceLines_rental->iGrvLineID =0;
//           $_btblInvoiceLines_rental->iLineDocketMode =0; 
//           $_btblInvoiceLines_rental->cDescription =$tlinedesc; 
//           $_btblInvoiceLines_rental->iUnitsOfMeasureStockingID=0; 
//           $_btblInvoiceLines_rental->iUnitsOfMeasureCategoryID=0;
//           $_btblInvoiceLines_rental->iUnitsOfMeasureID=0;
//           $_btblInvoiceLines_rental->fQuantity=1;
//           $_btblInvoiceLines_rental->fQtyChange=1;
//           $_btblInvoiceLines_rental->fQtyToProcess=1; 
//           $_btblInvoiceLines_rental->fQtyLastProcess=0; 
//           $_btblInvoiceLines_rental->fQtyProcessed =0; 
//           $_btblInvoiceLines_rental->fQtyReserved=0; 
//           $_btblInvoiceLines_rental->fQtyReservedChange =0;
//           $_btblInvoiceLines_rental->cLineNotes=$tlinenote; 
//           $_btblInvoiceLines_rental->fUnitPriceExcl=$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fUnitPriceIncl=$tlineamtincl;
//           $_btblInvoiceLines_rental->iUnitPriceOverrideReasonID=0; 
//           $_btblInvoiceLines_rental->fUnitCost=0;
//           $_btblInvoiceLines_rental->fLineDiscount=0; 
//           $_btblInvoiceLines_rental->iLineDiscountReasonID=0;
//           $_btblInvoiceLines_rental->iReturnReasonID=0; 
//           $_btblInvoiceLines_rental->fTaxRate=$tax_rate->TaxRate; 
//           $_btblInvoiceLines_rental->bIsSerialItem=0; 
//           $_btblInvoiceLines_rental->bIsWhseItem=1;
//           $_btblInvoiceLines_rental->fAddCost=0; 
//           $_btblInvoiceLines_rental->cTradeinItem='';
//           $_btblInvoiceLines_rental->iStockCodeID=$tstockid; 
//           $_btblInvoiceLines_rental->iJobID=0;
//           $_btblInvoiceLines_rental->iWarehouseID=6;
//           $_btblInvoiceLines_rental->iTaxTypeID=$customer_details->ideftaxtypeid;
//           $_btblInvoiceLines_rental->iPriceListNameID=1;
//           $_btblInvoiceLines_rental->fQuantityLineTotIncl=$tlineamtincl;
//           $_btblInvoiceLines_rental->fQuantityLineTotExcl=$tlineamtexcl;
//           $_btblInvoiceLines_rental->fQuantityLineTotInclNoDisc=$tlineamtincl;
//           $_btblInvoiceLines_rental->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmount =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotIncl =$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExcl =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmount =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotIncl =$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExcl =$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmount =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotIncl=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExcl =0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDisc=0;
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDisc=0;
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmount=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotIncl=0;
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExcl=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDisc=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDisc=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmount=0;
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDisc=0; 
//           $_btblInvoiceLines_rental->fUnitPriceExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fUnitPriceInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fUnitCostForeign=0;
//           $_btblInvoiceLines_rental->fAddCostForeign=0;
//           $_btblInvoiceLines_rental->fQuantityLineTotInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQuantityLineTotExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmountForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
//           $_btblInvoiceLines_rental->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_rental->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotInclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExclForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotInclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTotExclNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountForeign=0; 
//           $_btblInvoiceLines_rental->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
//           $_btblInvoiceLines_rental->iLineRepID=$customer_details->repid; 
//           $_btblInvoiceLines_rental->iLineProjectID=2; 
//           $_btblInvoiceLines_rental->iLedgerAccountID=0; 
//           $_btblInvoiceLines_rental->IModule=0;
//           $_btblInvoiceLines_rental->bChargeCom=1;
//           $_btblInvoiceLines_rental->bIsLotItem=0;
//           // $_btblInvoiceLines_rental->iLotID=0;
//           // $_btblInvoiceLines_rental->cLotNumber='';
//           // $_btblInvoiceLines_rental->dLotExpiryDate=null;
//           $_btblInvoiceLines_rental->iMFPID=0;
//           $_btblInvoiceLines_rental->iLineID=1;
//           $_btblInvoiceLines_rental->iLinkedLineID=0;
//           $_btblInvoiceLines_rental->fQtyLinkedUsed=null;
//           $_btblInvoiceLines_rental->fUnitPriceInclOrig=null;
//           $_btblInvoiceLines_rental->fUnitPriceExclOrig=Null;
//           $_btblInvoiceLines_rental->fUnitPriceInclForeignOrig=Null;
//           $_btblInvoiceLines_rental->fUnitPriceExclForeignOrig=0;
//           $_btblInvoiceLines_rental->iDeliveryMethodID=0;
//           $_btblInvoiceLines_rental->fQtyDeliver=0;
//           $_btblInvoiceLines_rental->dDeliveryDate=$today;
//           $_btblInvoiceLines_rental->iDeliveryStatus=0;
//           $_btblInvoiceLines_rental->fQtyForDelivery=0;
//           $_btblInvoiceLines_rental->bPromotionApplied=0;
//           $_btblInvoiceLines_rental->fPromotionPriceExcl=0;
//           $_btblInvoiceLines_rental->fPromotionPriceIncl=0;
//           $_btblInvoiceLines_rental->cPromotionCode=0;
//           $_btblInvoiceLines_rental->iSOLinkedPOLineID=0;
//           $_btblInvoiceLines_rental->fLength=0;
//           $_btblInvoiceLines_rental->fWidth=0;
//           $_btblInvoiceLines_rental->fHeight=0;
//           $_btblInvoiceLines_rental->iPieces=0;
//           $_btblInvoiceLines_rental->iPiecesToProcess=0;
//           $_btblInvoiceLines_rental->iPiecesLastProcess=0;
//           $_btblInvoiceLines_rental->iPiecesProcessed=0;
//           $_btblInvoiceLines_rental->iPiecesReserved=0;
//           $_btblInvoiceLines_rental->iPiecesDeliver=0;
//           $_btblInvoiceLines_rental->iPiecesForDelivery=0;
//           $_btblInvoiceLines_rental->fQuantityUR=1;
//           $_btblInvoiceLines_rental->fQtyChangeUR=1;
//           $_btblInvoiceLines_rental->fQtyToProcessUR=1;
//           $_btblInvoiceLines_rental->fQtyLastProcessUR=0;
//           $_btblInvoiceLines_rental->fQtyProcessedUR=0;
//           $_btblInvoiceLines_rental->fQtyReservedUR=0;
//           $_btblInvoiceLines_rental->fQtyReservedChangeUR=0;
//           $_btblInvoiceLines_rental->fQtyDeliverUR=0;
//           $_btblInvoiceLines_rental->fQtyForDeliveryUR=0;
//           $_btblInvoiceLines_rental->fQtyLinkedUsedUR=0;
//           $_btblInvoiceLines_rental->iPiecesLinkedUsed=0;
//           $_btblInvoiceLines_rental->iSalesWhseID=0;
//           $_btblInvoiceLines_rental->_btblInvoiceLines_iBranchID=0;
//           $_btblInvoiceLines_rental->udIDSOrdTxCMReadingDate='';
//           $_btblInvoiceLines_rental->uiIDSOrdTxCMPrevReading='';
//           $_btblInvoiceLines_rental->uiIDSOrdTxCMCurrReading='';
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMMinVol='';
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMRates='';
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMServiceAsset=$color_bill_sep->cCode;
//           $_btblInvoiceLines_rental->ucIDSOrdTxCMMeterType="RENTAL";

//           $_btblInvoiceLines_rental->save();

//         }
           
     

//     $total_inv =DB::table('_btblinvoicelines as lni')
//     ->where('iinvoiceid',$invoice_id->autoindex)
//     ->select(DB::raw('sum(lni.fquantitylinetotincl) as inclamt'),
//           DB::raw('sum(lni.fquantitylinetotexcl) as exclamt'),
//           DB::raw('sum(lni.fquantitylinetaxamount) as taxamt'),
//           DB::raw('sum(lni.fquantitylinetotinclforeign) as fcinclamt'),
//           DB::raw('sum(lni.fquantitylinetotexclforeign) as fcexclamt'),
//           DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt'),
//           DB::raw('sum(lni.fquantitylinetaxamountforeign) as fctaxamt')
    
    
    
//     )
//     ->first();

//     //  dd( $total_inv);
//     $tinvamtexcl =$total_inv->exclamt;
//     $tinvamtincl =$total_inv->inclamt;
//     $tinvamttax = $total_inv->taxamt;
//     $tordamtexcl = $tinvamtexcl;
//     $tordamtincl =  $tinvamtincl;
//     $tordamttax = $tinvamttax;
//     $tfcinvamtexcl =$total_inv->fcexclamt;
//     $tfcinvamtincl =$total_inv->fcinclamt;
//     $tfcinvamttax =$total_inv->fctaxamt;
//     $tfcordamtexcl = $tfcinvamtexcl;
//     $tfcordamtincl = $tfcinvamtincl;
//     $tfcordamttax = $tfcinvamttax;

//     $inv_update =InvNum::find($invoice_id->autoindex);
    
//     $inv_update->InvTotExclDEx =$tinvamtexcl;
//     $inv_update->InvTotTaxDEx =$tinvamttax;
//     $inv_update->InvTotInclDEx =$tinvamtincl;
//     $inv_update->InvTotExcl =$tinvamtexcl;
//     $inv_update->InvTotTax =$tinvamttax;
//     $inv_update->InvTotIncl =$tinvamtincl;
//     $inv_update->OrdTotExclDEx=$tordamtexcl;
//     $inv_update->OrdTotTaxDEx=$tordamttax;
//     $inv_update->OrdTotInclDEx=$tordamtincl;
//     $inv_update->OrdTotExcl=$tordamtexcl;
//     $inv_update->OrdTotTax=$tordamttax;
//     $inv_update->OrdTotIncl=$tordamtincl;
//     $inv_update->fInvTotExclDExForeign=$tfcinvamtexcl;
//     $inv_update->fInvTotTaxDExForeign=$tfcinvamttax;
//     $inv_update->fInvTotInclDExForeign=$tfcinvamtincl;
//     $inv_update->fInvTotExclForeign=$tfcinvamtexcl;
//     $inv_update->fInvTotTaxForeign=$tfcinvamttax;
//     $inv_update->fInvTotInclForeign=$tfcinvamtincl;
//     $inv_update->fOrdTotExclDExForeign=$tfcordamtexcl;
//     $inv_update->fOrdTotTaxDExForeign=$tfcordamttax;
//     $inv_update->fOrdTotInclDExForeign=$tfcordamtincl;
//     $inv_update->fOrdTotExclForeign=$tfcordamtexcl;
//     $inv_update->fOrdTotTaxForeign=$tfcordamttax;
//     $inv_update->fOrdTotInclForeign=$tfcordamtincl;
//     $inv_update->InvTotInclExRounding=$tinvamtincl;
//     $inv_update->OrdTotInclExRounding=$tordamtincl;
//     $inv_update->fInvTotInclForeignExRounding=$tfcinvamtincl;
//     $inv_update->fOrdTotInclForeignExRounding=$tfcordamtincl;
//     $inv_update->save();   
//     }
 }  
        
    return response()->json(['success'=>'Sales Order(s) Created Successfully.'],200);
  
  }
}
