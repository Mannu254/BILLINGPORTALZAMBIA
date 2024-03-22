<style>
    th {
       font-size: 5px !important;
       font-weight: bolder;
      
   }
    td {
       font-size: 5px !important;
       
       float: right !important;
   }
</style>
<h3>{{"MFI Managed Document Solutions Limited"}}</h3>
<h6></h6>
 
<h4>For Customer:  {{$details ->account}}-{{$details ->name}}</h4>
<h6></h6>
<h4>Order Number:  {{$details ->OrderNum}}  Dated:  {{ \Carbon\Carbon::parse($today)->format('d/m/Y')}}</h4>


<div class="container-flow">
    <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead>
      <tr>
    <th>Asset Code</th>
    <th>Serial Number</th>
    <th>Model</th>
    <th>Asset Location</th>

    <th>MonCMR</th>
    <th>MonPMR</th>
    <th>Mon Pgs</th>
    <th>Mon MV</th>
    <th>Mon Rate</th>
    <th>Mon Amount</th>

    <th>ColCMR</th>
    <th>ColPMR</th>
    <th>Col Pgs</th>
    <th>Col MV</th>
    <th>Col Rate</th>
    <th>Col Amount</th>
        <th>Rental</th>       
    </tr>
    </thead>
    <tbody>
    @foreach($summary_cons as $con_mon)
    <tr>
      
    <td>{{ $con_mon->ucIDSOrdTxCMServiceAsset }}</td>
    <td>{{ $con_mon->ucIDSOrdTxCMServiceAsset }}</td>
    <td>Service Asset For Consolidated Billing </td>
    <td>{{ "MFI INTERNAL" }}</td>
    <td>{{ $con_mon->uiIDSOrdTxCMCurrReading }}</td>
    <td>{{ $con_mon->uiIDSOrdTxCMPrevReading }}</td>
    <td>{{ $con_mon->uiIDSOrdTxCMCurrReading - $con_mon->uiIDSOrdTxCMPrevReading }}</td>
    <td>{{ $con_mon->ucIDSOrdTxCMMinVol }}</td>
    <td>{{ $con_mon->ucIDSOrdTxCMRates }}</td>
    <td>{{ $con_mon->fQuantityLineTotExcl }}</td>


    <td>{{ $con_mon->cons_color->uiIDSOrdTxCMCurrReading ?? 0 }}</td>
    <td>{{ $con_mon->cons_color->uiIDSOrdTxCMPrevReading ?? 0 }}</td>
    <td>{{ ($con_mon->cons_color->uiIDSOrdTxCMCurrReading ?? 0) - ($con_mon->cons_color->uiIDSOrdTxCMPrevReading ?? 0) }}</td>
    <td>{{ $con_mon->cons_color->ucIDSOrdTxCMMinVol ?? 0 }}</td>
    <td>{{ $con_mon->cons_color->ucIDSOrdTxCMRates ?? 0 }}</td>
    <td>{{ $con_mon->cons_color->fQuantityLineTotExcl ?? 0 }}</td>


    
    {{-- <td>{{ $con_mon->cons_scan->uiIDSOrdTxCMCurrReading ?? '0' }}</td>
    <td>{{ $con_mon->cons_scan->uiIDSOrdTxCMPrevReading ?? '0' }}</td>
    <td>{{ ($con_mon->cons_scan->uiIDSOrdTxCMCurrReading ?? 0) - ($con_mon->cons_scan->uiIDSOrdTxCMPrevReading ?? 0) }}</td>
    <td>{{ $con_mon->cons_scan->ucIDSOrdTxCMMinVol ?? '0' }}</td>
    <td>{{ $con_mon->cons_scan->ucIDSOrdTxCMRates ?? '0'}}</td>
    <td>{{ $con_mon->cons_scan->fQuantityLineTotExcl ?? '0' }}</td>


    <td>{{ $con_mon->cons_a3m->uiIDSOrdTxCMCurrReading ?? '0' }}</td>
    <td>{{ $con_mon->cons_a3m->uiIDSOrdTxCMPrevReading ?? '0' }}</td>
    <td>{{ ($con_mon->cons_a3m->uiIDSOrdTxCMCurrReading ?? 0) - ($con_mon->cons_a3m->uiIDSOrdTxCMPrevReading ?? 0) }}</td>
    <td>{{ $con_mon->cons_a3m->ucIDSOrdTxCMMinVol ?? '0' }}</td>
    <td>{{ $con_mon->cons_a3m->ucIDSOrdTxCMRates ?? '0'}}</td>
    <td>{{ $con_mon->cons_a3m->fQuantityLineTotExcl ?? '0' }}</td>


    <td>{{ $con_mon->cons_a3C->uiIDSOrdTxCMCurrReading ?? '0' }}</td>
    <td>{{ $con_mon->cons_a3C->uiIDSOrdTxCMPrevReading ?? '0' }}</td>
    <td>{{ ($con_mon->cons_a3C->uiIDSOrdTxCMCurrReading ?? 0) - ($con_mon->cons_a3C->uiIDSOrdTxCMPrevReading ?? 0) }}</td>
    <td>{{ $con_mon->cons_a3C->ucIDSOrdTxCMMinVol ?? '0' }}</td>
    <td>{{ $con_mon->cons_a3C->ucIDSOrdTxCMRates ?? '0'}}</td>
    <td>{{ $con_mon->cons_a3C->fQuantityLineTotExcl ?? '0' }}</td>     --}}
    @endforeach
    <td style="color: black; font-weight:bolder">{{$rental->fQuantityLineTotExcl ?? '0'}}</td>
  </tr>
  

    @foreach($individual_asset as $sa)
    <tr>
    <td>{{ $sa->cCode }}</td>
    <td>{{ $sa->ucSASerialNo }}</td>
    <td>{{$sa->cDescription}}</td>
    <td>{{ $sa->cLocation}}</td>
    <td>{{ $sa->MonCMR }}</td>
    <td>{{ $sa->MonPMR }}</td>
    <td>{{ $sa->MonCMR - $sa->MonPMR }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>


    <td>{{ $sa->ColCMR }}</td>
    <td>{{ $sa->ColPMR }}</td>
    <td>{{ $sa->ColCMR - $sa->ColPMR }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>


    
    {{-- <td>{{ $sa->ScnCMR }}</td>
    <td>{{ $sa->ScnPMR ?? '0' }}</td>
    <td>{{ ($sa->ScnCMR ?? 0) - ($sa->ScnPMR ?? 0) }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>


    <td>{{ $sa->A3MCMR ?? '0' }}</td>
    <td>{{ $sa->A3MPMR ?? '0' }}</td>
    <td>{{ ($sa->A3MCMR  ?? 0) - ($sa->A3MPMR ?? 0) }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>


    <td>{{ $sa->A3CCMR ?? '0' }}</td>
    <td>{{ $sa->A3CPMR ?? '0' }}</td>
    <td>{{ ($sa->A3CCMR ?? 0) - ($sa->A3CPMR ?? 0) }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td>
    <td>{{ "0" }}</td> --}}

  </tr>
    @endforeach   
  </tbody>
  <tfoot>
    <tr>
      <th id="total" colspan="6" style="color: black; font-weight:bolder">Total</th>
      <td style="color: black; font-weight:bolder">{{($individual_asset_totals->mon_cmr_total ?? 0) -($individual_asset_totals->mon_pmr_total ?? 0)}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{$total_mon->fQuantityLineTotExcl ?? 0}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{($individual_asset_totals->col_cmr_total ?? 0) -($individual_asset_totals->col_pmr_total ?? 0)}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{$total_col->fQuantityLineTotExcl ?? 0}}</td>
     
      {{-- <td style="color: black; font-weight:bolder">{{($individual_asset_totals->scn_cmr_total ?? 0) -($individual_asset_totals->scn_pmr_total ?? 0)}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{$total_scn->fQuantityLineTotExcl ?? 0}}</td>
      <td></td>
      <td></td>        
      <td style="color: black; font-weight:bolder">{{($individual_asset_totals->a3m_cmr_total ?? 0) -($individual_asset_totals->a3m_pmr_total ?? 0)}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{$total_a3m->fQuantityLineTotExcl ?? 0}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{($individual_asset_totals->a3c_cmr_total ?? 0) -($individual_asset_totals->a3c_pmr_total ?? 0)}}</td>
      <td></td>
      <td></td>
      <td style="color: black; font-weight:bolder">{{$total_a3c->fQuantityLineTotExcl ?? 0}}</td> --}}
      <td style="color: black; font-weight:bolder">{{$rental->fQuantityLineTotExcl ?? 0}}</td>
    </tr> 
    

     

    
    </table>
    </div>








