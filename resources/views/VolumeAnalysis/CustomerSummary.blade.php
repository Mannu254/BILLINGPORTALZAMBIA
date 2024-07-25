<style>
  th {
     font-size: 10px !important;
     font-weight: bolder;
     font-family: 'Roboto', sans-serif !important;
    
 }
  td {
     font-size: 8px !important;
     font-family: 'Roboto', sans-serif !important;
     white-space: nowrap;
     color: black;
     float: right !important;
 }
 p,h5,h3,h4{
  font-family: 'Roboto', sans-serif !important;
  font-weight: 400;
  padding: 0px !important;
  margin:0px !important;

 }
 .div1 table td{
    border:0.5px solid black;
    padding: 2px;
}
 
</style>

<table width="100%">
<tr>
  <h4>{{"MFI Managed Document Solutions Limited"}},</h4>
  <h4>Plot No.56, 57, 58 & 84, Nkrumah Road,</h4>
  <h5>P.O. Box No. 3444, Dar es Salaam, Tanzania,</h5>
  <h5><i class="fa fa-envelope-o"></i>mailto:info@groupmfi.com</h5>
    <td valign="top"><img src="{{asset('/storage/uploads/MFI_logo1.png')}}" style="width: 250px; height: 100px;"/></td>
    <td align="right">
        <h3>Customer Name: {{$details ->account}}-{{$details ->name}}</h3>                    
         <h3>Order Number:{{$details ->OrderNum}}</h3> 
         <h3>Dated:  {{ \Carbon\Carbon::parse($today)->format('d/m/Y')}}</h3>            
        
    </td>
</tr>

</table>
<hr>

<div class="container-fluid div1">
  <table id="table" class="table table-hover table-bordered table-sm text-center">
  <thead>
    <tr>
  <th>Asset Code</th>
  <th>Serial Number</th>
  <th>Model</th>
  <th>Asset Location</th>
  <th>Mon CMR</th>
  <th>Mon PMR</th>
  <th>Mon Pgs</th>
  <th>Mon MV</th>
  <th>Mon <br> Rate</th>
  <th>Mon <br> Amount</th>

  <th>Col CMR</th>
  <th>Col PMR</th>
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
  <td> BILLING ASSET </td>
  <td>{{ " INTERNAL" }}</td>
  <td>{{ $con_mon->uiIDSOrdTxCMCurrReading ?? '' }}</td>
  <td>{{ $con_mon->uiIDSOrdTxCMPrevReading ?? '' }}</td>
  <td>{{ ($con_mon->uiIDSOrdTxCMCurrReading ?? 0) - ($con_mon->uiIDSOrdTxCMPrevReading ?? 0) }}</td>
  <td>{{ $con_mon->ucIDSOrdTxCMMinVol }}</td>
  <td>{{ $con_mon->ucIDSOrdTxCMRates }}</td>
  <td>{{ $con_mon->fQuantityLineTotExcl }}</td>
  <td>{{ $con_mon->cons_color->uiIDSOrdTxCMCurrReading ?? '' }}</td>
  <td>{{ $con_mon->cons_color->uiIDSOrdTxCMPrevReading  ?? '' }}</td>
  <td>{{ ($con_mon->cons_color->uiIDSOrdTxCMCurrReading ?? 0) - ($con_mon->cons_color->uiIDSOrdTxCMPrevReading  ?? 0) }}</td>
  <td>{{ $con_mon->cons_color->ucIDSOrdTxCMMinVol ?? ''}}</td>
  <td>{{ $con_mon->cons_color->ucIDSOrdTxCMRates ?? ''}}</td>
  <td>{{ $con_mon->cons_color->fQuantityLineTotExcl ?? '' }}</td>  
  @endforeach
  <td style="color: black; font-weight:bolder">{{$rental->fQuantityLineTotExcl ?? ''}}</td>
</tr>


  @foreach($individual_asset as $sa)
  <tr>
  <td>{{ $sa->cCode }}</td>
  <td>{{ $sa->ucSASerialNo }}</td>
  <td>{{$sa->cDescription}}</td>
  <td style="font-size:6px !important;">{{ $sa->cLocation}}</td> 
  
  <td>{{ $sa->MonCMR ?? '' }}</td>
  <td>{{ $sa->MonPMR ?? ''}}</td>
  <td>{{ $sa->MonCMR - $sa->MonPMR }}</td>
  <td>{{ "" }}</td>
  <td>{{ "" }}</td>
  <td>{{ "" }}</td>


  <td>{{ $sa->ColCMR ?? ''}}</td>
  <td>{{ $sa->ColPMR  ?? ''}}</td>
  <td>{{ $sa->ColCMR - $sa->ColPMR }}</td>
  <td>{{ "" }}</td>
  <td>{{ "" }}</td>
  <td>{{ "" }}</td>


  
 

</tr>
  @endforeach   
</tbody>
<tfoot>
  <tr>
    <th id="total" colspan="6" style="color: black; font-weight:bolder">Totals</th>
    <td style="color: black; font-weight:bolder font-size:13px">{{($individual_asset_totals->mon_cmr_total ?? 0) -($individual_asset_totals->mon_pmr_total ?? 0)}}</td>
    <td></td>
    <td></td>
    <td style="color: black; font-weight:bolder; font-size:13px">{{number_format($total_mon->fQuantityLineTotExcl ?? 0,2)}}</td>
    <td></td>
    <td></td>
    <td style="color: black; font-weight:bolder; font-size:13px">{{($individual_asset_totals->col_cmr_total ?? 0) -($individual_asset_totals->col_pmr_total ?? 0)}}</td>
    <td></td>
    <td></td>
    <td style="color: black; font-weight:bolder; font-size:13px">{{number_format($total_col->fQuantityLineTotExcl ?? 0,2)}}</td>     
   
    <td style="color: black; font-weight:bolder; font-size:13px">{{number_format($rental->fQuantityLineTotExcl ?? 0)}}</td>
  </tr> 
  <tr></tr>
  <tr>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
   <th></th>
    <th id="total" colspan="4" style="color: black; font-weight:bolder; font-size:13px; float:right !important">Grand Total:  {{number_format($details->InvTotExcl,2)}}</th>   

  </tr>
  <tr></tr>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <th></th>
  <tr> <th id="total" colspan="25" style="color: black; font-weight:bolder; font-size:13px">Received By: ..............................................................  Date  ................................... </th>
  </tr> 
  </table>
  </div>








