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
          'sa.ucSABillingAsset'
           
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

              if(!empty($min_vol->fRate)){

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
          DB::raw('sum(cmr.ColCMR - cmr.ColPMR) as colunit'),'sa.ucSABillingAsset'
          
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
          $tlinenote ="TOTAL COPIES DONE: $tunits\r\nEXCESS COPIES DONE: $data->colunit - $min_vol_color->min_vol =$excess_copiesC";
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

        $software = $value['soft_charges'];


           
  
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
          
          
          //software charges

          if($software > 0){   
            
            
            $tbillmeter = "SOFTWARE";
            $rental_amt = $software;
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
            $tlinedesc = "Software Charges From 1st to $lastday $month $today->year";          
            $tstockid = 17987;
  
            $_btblInvoiceLines_software = new _btblInvoiceLines;
            $_btblInvoiceLines_software->iInvoiceID =$invoice_id;
            $_btblInvoiceLines_software->iOrigLineID =0;
            $_btblInvoiceLines_software->iGrvLineID =0;
            $_btblInvoiceLines_software->iLineDocketMode =0; 
            $_btblInvoiceLines_software->cDescription =$tlinedesc; 
            $_btblInvoiceLines_software->iUnitsOfMeasureStockingID=0; 
            $_btblInvoiceLines_software->iUnitsOfMeasureCategoryID=0;
            $_btblInvoiceLines_software->iUnitsOfMeasureID=0;
            $_btblInvoiceLines_software->fQuantity=1;
            $_btblInvoiceLines_software->fQtyChange=1;
            $_btblInvoiceLines_software->fQtyToProcess=1; 
            $_btblInvoiceLines_software->fQtyLastProcess=0; 
            $_btblInvoiceLines_software->fQtyProcessed =0; 
            $_btblInvoiceLines_software->fQtyReserved=0; 
            $_btblInvoiceLines_software->fQtyReservedChange =0;
            $_btblInvoiceLines_software->cLineNotes=$tlinenote; 
            $_btblInvoiceLines_software->fUnitPriceExcl=$tlineamtexcl; 
            $_btblInvoiceLines_software->fUnitPriceIncl=$tlineamtincl;
            $_btblInvoiceLines_software->iUnitPriceOverrideReasonID=0; 
            $_btblInvoiceLines_software->fUnitCost=0;
            $_btblInvoiceLines_software->fLineDiscount=0; 
            $_btblInvoiceLines_software->iLineDiscountReasonID=0;
            $_btblInvoiceLines_software->iReturnReasonID=0; 
            $_btblInvoiceLines_software->fTaxRate=$tax_rate->TaxRate; 
            $_btblInvoiceLines_software->bIsSerialItem=0; 
            $_btblInvoiceLines_software->bIsWhseItem=1;
            $_btblInvoiceLines_software->fAddCost=0; 
            $_btblInvoiceLines_software->cTradeinItem='';
            $_btblInvoiceLines_software->iStockCodeID=$tstockid; 
            $_btblInvoiceLines_software->iJobID=0;
            $_btblInvoiceLines_software->iWarehouseID=6;
            $_btblInvoiceLines_software->iTaxTypeID=$customer_details->ideftaxtypeid;
            $_btblInvoiceLines_software->iPriceListNameID=1;
            $_btblInvoiceLines_software->fQuantityLineTotIncl=$tlineamtincl;
            $_btblInvoiceLines_software->fQuantityLineTotExcl=$tlineamtexcl;
            $_btblInvoiceLines_software->fQuantityLineTotInclNoDisc=$tlineamtincl;
            $_btblInvoiceLines_software->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
            $_btblInvoiceLines_software->fQuantityLineTaxAmount =$tlineamttax; 
            $_btblInvoiceLines_software->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
            $_btblInvoiceLines_software->fQtyChangeLineTotIncl =$tlineamtincl; 
            $_btblInvoiceLines_software->fQtyChangeLineTotExcl =$tlineamtexcl; 
            $_btblInvoiceLines_software->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
            $_btblInvoiceLines_software->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
            $_btblInvoiceLines_software->fQtyChangeLineTaxAmount =$tlineamttax; 
            $_btblInvoiceLines_software->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotIncl =$tlineamtincl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotExcl =$tlineamtexcl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTaxAmount =$tlineamttax; 
            $_btblInvoiceLines_software->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotIncl=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotExcl =0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotInclNoDisc=0;
            $_btblInvoiceLines_software->fQtyLastProcessLineTotExclNoDisc=0;
            $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmount=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmountNoDisc=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotIncl=0;
            $_btblInvoiceLines_software->fQtyProcessedLineTotExcl=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotInclNoDisc=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotExclNoDisc=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTaxAmount=0;
            $_btblInvoiceLines_software->fQtyProcessedLineTaxAmountNoDisc=0; 
            $_btblInvoiceLines_software->fUnitPriceExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_software->fUnitPriceInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fUnitCostForeign=0;
            $_btblInvoiceLines_software->fAddCostForeign=0;
            $_btblInvoiceLines_software->fQuantityLineTotInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fQuantityLineTotExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_software->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
            $_btblInvoiceLines_software->fQuantityLineTaxAmountForeign=$tfclineamttax; 
            $_btblInvoiceLines_software->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
            $_btblInvoiceLines_software->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_software->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_software->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
            $_btblInvoiceLines_software->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
            $_btblInvoiceLines_software->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
            $_btblInvoiceLines_software->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotInclForeign=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotExclForeign=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotInclNoDiscForeign=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTotExclNoDiscForeign=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmountForeign=0; 
            $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotInclForeign=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotExclForeign=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotInclNoDiscForeign=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTotExclNoDiscForeign=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTaxAmountForeign=0; 
            $_btblInvoiceLines_software->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
            $_btblInvoiceLines_software->iLineRepID=$customer_details->repid; 
            $_btblInvoiceLines_software->iLineProjectID=2; 
            $_btblInvoiceLines_software->iLedgerAccountID=0; 
            $_btblInvoiceLines_software->IModule=0;
            $_btblInvoiceLines_software->bChargeCom=1;
            $_btblInvoiceLines_software->bIsLotItem=0;
            // $_btblInvoiceLines_rental->iLotID=0;
            // $_btblInvoiceLines_rental->cLotNumber='';
            // $_btblInvoiceLines_rental->dLotExpiryDate=null;
            $_btblInvoiceLines_software->iMFPID=0;
            $_btblInvoiceLines_software->iLineID=1;
            $_btblInvoiceLines_software->iLinkedLineID=0;
            $_btblInvoiceLines_software->fQtyLinkedUsed=null;
            $_btblInvoiceLines_software->fUnitPriceInclOrig=null;
            $_btblInvoiceLines_software->fUnitPriceExclOrig=Null;
            $_btblInvoiceLines_software->fUnitPriceInclForeignOrig=Null;
            $_btblInvoiceLines_software->fUnitPriceExclForeignOrig=0;
            $_btblInvoiceLines_software->iDeliveryMethodID=0;
            $_btblInvoiceLines_software->fQtyDeliver=0;
            $_btblInvoiceLines_software->dDeliveryDate=$today;
            $_btblInvoiceLines_software->iDeliveryStatus=0;
            $_btblInvoiceLines_software->fQtyForDelivery=0;
            $_btblInvoiceLines_software->bPromotionApplied=0;
            $_btblInvoiceLines_software->fPromotionPriceExcl=0;
            $_btblInvoiceLines_software->fPromotionPriceIncl=0;
            $_btblInvoiceLines_software->cPromotionCode=0;
            $_btblInvoiceLines_software->iSOLinkedPOLineID=0;
            $_btblInvoiceLines_software->fLength=0;
            $_btblInvoiceLines_software->fWidth=0;
            $_btblInvoiceLines_software->fHeight=0;
            $_btblInvoiceLines_software->iPieces=0;
            $_btblInvoiceLines_software->iPiecesToProcess=0;
            $_btblInvoiceLines_software->iPiecesLastProcess=0;
            $_btblInvoiceLines_software->iPiecesProcessed=0;
            $_btblInvoiceLines_software->iPiecesReserved=0;
            $_btblInvoiceLines_software->iPiecesDeliver=0;
            $_btblInvoiceLines_software->fQuantityUR=1;
            $_btblInvoiceLines_software->fQtyChangeUR=1;
            $_btblInvoiceLines_software->fQtyToProcessUR=1;
            $_btblInvoiceLines_software->fQtyLastProcessUR=0;
            $_btblInvoiceLines_software->fQtyProcessedUR=0;
            $_btblInvoiceLines_software->fQtyReservedUR=0;
            $_btblInvoiceLines_software->fQtyReservedChangeUR=0;
            $_btblInvoiceLines_software->fQtyDeliverUR=0;
            $_btblInvoiceLines_software->fQtyForDeliveryUR=0;
            $_btblInvoiceLines_software->fQtyLinkedUsedUR=0;
            $_btblInvoiceLines_software->iPiecesLinkedUsed=0;
            $_btblInvoiceLines_software->iSalesWhseID=0;
            $_btblInvoiceLines_software->_btblInvoiceLines_iBranchID=1;
            $_btblInvoiceLines_software->udIDSOrdTxCMReadingDate='';
            $_btblInvoiceLines_software->uiIDSOrdTxCMPrevReading='';
            $_btblInvoiceLines_software->uiIDSOrdTxCMCurrReading='';
            $_btblInvoiceLines_software->ucIDSOrdTxCMMinVol='';
            $_btblInvoiceLines_software->ucIDSOrdTxCMRates='';
            $_btblInvoiceLines_software->ucIDSOrdTxCMServiceAsset=$mono_consolidated->ucSABillingAsset;
            $_btblInvoiceLines_software->ucIDSOrdTxCMMeterType="SOFTWARE";
  
            $_btblInvoiceLines_software->save();
  
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




if($billing_type =='S' && $con_asset ){      

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
$software = $value['soft_charges'];


 

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


//software charges

if($software > 0){   
            
            
  $tbillmeter = "SOFTWARE";
  $rental_amt = $software;
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
  $tlinedesc = "Software Charges From 1st to $lastday $month $today->year";          
  $tstockid = 17987;

  $_btblInvoiceLines_software = new _btblInvoiceLines;
  $_btblInvoiceLines_software->iInvoiceID =$invoice_id;
  $_btblInvoiceLines_software->iOrigLineID =0;
  $_btblInvoiceLines_software->iGrvLineID =0;
  $_btblInvoiceLines_software->iLineDocketMode =0; 
  $_btblInvoiceLines_software->cDescription =$tlinedesc; 
  $_btblInvoiceLines_software->iUnitsOfMeasureStockingID=0; 
  $_btblInvoiceLines_software->iUnitsOfMeasureCategoryID=0;
  $_btblInvoiceLines_software->iUnitsOfMeasureID=0;
  $_btblInvoiceLines_software->fQuantity=1;
  $_btblInvoiceLines_software->fQtyChange=1;
  $_btblInvoiceLines_software->fQtyToProcess=1; 
  $_btblInvoiceLines_software->fQtyLastProcess=0; 
  $_btblInvoiceLines_software->fQtyProcessed =0; 
  $_btblInvoiceLines_software->fQtyReserved=0; 
  $_btblInvoiceLines_software->fQtyReservedChange =0;
  $_btblInvoiceLines_software->cLineNotes=$tlinenote; 
  $_btblInvoiceLines_software->fUnitPriceExcl=$tlineamtexcl; 
  $_btblInvoiceLines_software->fUnitPriceIncl=$tlineamtincl;
  $_btblInvoiceLines_software->iUnitPriceOverrideReasonID=0; 
  $_btblInvoiceLines_software->fUnitCost=0;
  $_btblInvoiceLines_software->fLineDiscount=0; 
  $_btblInvoiceLines_software->iLineDiscountReasonID=0;
  $_btblInvoiceLines_software->iReturnReasonID=0; 
  $_btblInvoiceLines_software->fTaxRate=$tax_rate->TaxRate; 
  $_btblInvoiceLines_software->bIsSerialItem=0; 
  $_btblInvoiceLines_software->bIsWhseItem=1;
  $_btblInvoiceLines_software->fAddCost=0; 
  $_btblInvoiceLines_software->cTradeinItem='';
  $_btblInvoiceLines_software->iStockCodeID=$tstockid; 
  $_btblInvoiceLines_software->iJobID=0;
  $_btblInvoiceLines_software->iWarehouseID=6;
  $_btblInvoiceLines_software->iTaxTypeID=$customer_details->ideftaxtypeid;
  $_btblInvoiceLines_software->iPriceListNameID=1;
  $_btblInvoiceLines_software->fQuantityLineTotIncl=$tlineamtincl;
  $_btblInvoiceLines_software->fQuantityLineTotExcl=$tlineamtexcl;
  $_btblInvoiceLines_software->fQuantityLineTotInclNoDisc=$tlineamtincl;
  $_btblInvoiceLines_software->fQuantityLineTotExclNoDisc =$tlineamtexcl; 
  $_btblInvoiceLines_software->fQuantityLineTaxAmount =$tlineamttax; 
  $_btblInvoiceLines_software->fQuantityLineTaxAmountNoDisc=$tlineamttax; 
  $_btblInvoiceLines_software->fQtyChangeLineTotIncl =$tlineamtincl; 
  $_btblInvoiceLines_software->fQtyChangeLineTotExcl =$tlineamtexcl; 
  $_btblInvoiceLines_software->fQtyChangeLineTotInclNoDisc =$tlineamtincl; 
  $_btblInvoiceLines_software->fQtyChangeLineTotExclNoDisc =$tlineamtexcl; 
  $_btblInvoiceLines_software->fQtyChangeLineTaxAmount =$tlineamttax; 
  $_btblInvoiceLines_software->fQtyChangeLineTaxAmountNoDisc =$tlineamttax; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotIncl =$tlineamtincl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotExcl =$tlineamtexcl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotInclNoDisc=$tlineamtincl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotExclNoDisc=$tlineamtexcl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTaxAmount =$tlineamttax; 
  $_btblInvoiceLines_software->fQtyToProcessLineTaxAmountNoDisc =$tlineamttax; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotIncl=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotExcl =0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotInclNoDisc=0;
  $_btblInvoiceLines_software->fQtyLastProcessLineTotExclNoDisc=0;
  $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmount=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmountNoDisc=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotIncl=0;
  $_btblInvoiceLines_software->fQtyProcessedLineTotExcl=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotInclNoDisc=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotExclNoDisc=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTaxAmount=0;
  $_btblInvoiceLines_software->fQtyProcessedLineTaxAmountNoDisc=0; 
  $_btblInvoiceLines_software->fUnitPriceExclForeign=$tfclineamtexcl; 
  $_btblInvoiceLines_software->fUnitPriceInclForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fUnitCostForeign=0;
  $_btblInvoiceLines_software->fAddCostForeign=0;
  $_btblInvoiceLines_software->fQuantityLineTotInclForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fQuantityLineTotExclForeign=$tfclineamtexcl; 
  $_btblInvoiceLines_software->fQuantityLineTotInclNoDiscForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fQuantityLineTotExclNoDiscForeign=$tfclineamtexcl;      
  $_btblInvoiceLines_software->fQuantityLineTaxAmountForeign=$tfclineamttax; 
  $_btblInvoiceLines_software->fQuantityLineTaxAmountNoDiscForeign=$tfclineamttax; 
  $_btblInvoiceLines_software->fQtyChangeLineTotInclForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fQtyChangeLineTotExclForeign=$tfclineamtexcl; 
  $_btblInvoiceLines_software->fQtyChangeLineTotInclNoDiscForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fQtyChangeLineTotExclNoDiscForeign=$tfclineamtexcl; 
  $_btblInvoiceLines_software->fQtyChangeLineTaxAmountForeign=$tfclineamttax;
  $_btblInvoiceLines_software->fQtyChangeLineTaxAmountNoDiscForeign=$tfclineamttax; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotInclForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotExclForeign=$tfclineamtexcl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotInclNoDiscForeign=$tfclineamtincl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTotExclNoDiscForeign=$tfclineamtexcl; 
  $_btblInvoiceLines_software->fQtyToProcessLineTaxAmountForeign=$tfclineamttax; 
  $_btblInvoiceLines_software->fQtyToProcessLineTaxAmountNoDiscForeign=$tfclineamttax; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotInclForeign=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotExclForeign=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotInclNoDiscForeign=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTotExclNoDiscForeign=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmountForeign=0; 
  $_btblInvoiceLines_software->fQtyLastProcessLineTaxAmountNoDiscForeign=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotInclForeign=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotExclForeign=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotInclNoDiscForeign=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTotExclNoDiscForeign=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTaxAmountForeign=0; 
  $_btblInvoiceLines_software->fQtyProcessedLineTaxAmountNoDiscForeign=0; 
  $_btblInvoiceLines_software->iLineRepID=$customer_details->repid; 
  $_btblInvoiceLines_software->iLineProjectID=2; 
  $_btblInvoiceLines_software->iLedgerAccountID=0; 
  $_btblInvoiceLines_software->IModule=0;
  $_btblInvoiceLines_software->bChargeCom=1;
  $_btblInvoiceLines_software->bIsLotItem=0;
  // $_btblInvoiceLines_rental->iLotID=0;
  // $_btblInvoiceLines_rental->cLotNumber='';
  // $_btblInvoiceLines_rental->dLotExpiryDate=null;
  $_btblInvoiceLines_software->iMFPID=0;
  $_btblInvoiceLines_software->iLineID=1;
  $_btblInvoiceLines_software->iLinkedLineID=0;
  $_btblInvoiceLines_software->fQtyLinkedUsed=null;
  $_btblInvoiceLines_software->fUnitPriceInclOrig=null;
  $_btblInvoiceLines_software->fUnitPriceExclOrig=Null;
  $_btblInvoiceLines_software->fUnitPriceInclForeignOrig=Null;
  $_btblInvoiceLines_software->fUnitPriceExclForeignOrig=0;
  $_btblInvoiceLines_software->iDeliveryMethodID=0;
  $_btblInvoiceLines_software->fQtyDeliver=0;
  $_btblInvoiceLines_software->dDeliveryDate=$today;
  $_btblInvoiceLines_software->iDeliveryStatus=0;
  $_btblInvoiceLines_software->fQtyForDelivery=0;
  $_btblInvoiceLines_software->bPromotionApplied=0;
  $_btblInvoiceLines_software->fPromotionPriceExcl=0;
  $_btblInvoiceLines_software->fPromotionPriceIncl=0;
  $_btblInvoiceLines_software->cPromotionCode=0;
  $_btblInvoiceLines_software->iSOLinkedPOLineID=0;
  $_btblInvoiceLines_software->fLength=0;
  $_btblInvoiceLines_software->fWidth=0;
  $_btblInvoiceLines_software->fHeight=0;
  $_btblInvoiceLines_software->iPieces=0;
  $_btblInvoiceLines_software->iPiecesToProcess=0;
  $_btblInvoiceLines_software->iPiecesLastProcess=0;
  $_btblInvoiceLines_software->iPiecesProcessed=0;
  $_btblInvoiceLines_software->iPiecesReserved=0;
  $_btblInvoiceLines_software->iPiecesDeliver=0;
  $_btblInvoiceLines_software->fQuantityUR=1;
  $_btblInvoiceLines_software->fQtyChangeUR=1;
  $_btblInvoiceLines_software->fQtyToProcessUR=1;
  $_btblInvoiceLines_software->fQtyLastProcessUR=0;
  $_btblInvoiceLines_software->fQtyProcessedUR=0;
  $_btblInvoiceLines_software->fQtyReservedUR=0;
  $_btblInvoiceLines_software->fQtyReservedChangeUR=0;
  $_btblInvoiceLines_software->fQtyDeliverUR=0;
  $_btblInvoiceLines_software->fQtyForDeliveryUR=0;
  $_btblInvoiceLines_software->fQtyLinkedUsedUR=0;
  $_btblInvoiceLines_software->iPiecesLinkedUsed=0;
  $_btblInvoiceLines_software->iSalesWhseID=0;
  $_btblInvoiceLines_software->_btblInvoiceLines_iBranchID=1;
  $_btblInvoiceLines_software->udIDSOrdTxCMReadingDate='';
  $_btblInvoiceLines_software->uiIDSOrdTxCMPrevReading='';
  $_btblInvoiceLines_software->uiIDSOrdTxCMCurrReading='';
  $_btblInvoiceLines_software->ucIDSOrdTxCMMinVol='';
  $_btblInvoiceLines_software->ucIDSOrdTxCMRates='';
  $_btblInvoiceLines_software->ucIDSOrdTxCMServiceAsset=$mono_consolidated->ucSABillingAsset;
  $_btblInvoiceLines_software->ucIDSOrdTxCMMeterType="SOFTWARE";

  $_btblInvoiceLines_software->save();

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

 }  
        
    return response()->json(['success'=>'Sales Order(s) Created Successfully.'],200);
  
  }
}
