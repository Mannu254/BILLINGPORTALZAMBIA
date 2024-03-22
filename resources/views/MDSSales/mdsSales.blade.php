<style>
    th {
       font-size: 5px !important;
      
   }
    td {
       font-size: 5px !important;
       
       float: right !important;
   }
</style>

<div class="container-flow">
    <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead>
     <tr>   
    <th  scope="col">Customer Name</th>
    <th  scope="col">Customer Code</th>
    <th  scope="col">Invoice No.</th>
    <th  scope="col">Invoice Date</th>
    <th  scope="col"> Asset</th>
    <th  scope="col">Serial No.</th>
    <th  scope="col">ItemDescription</th>
    <th  scope="col">Project</th>
    <th  scope="col">MonoPages</th>
    <th  scope="col">MonoMinVol</th>
    <th  scope="col">MonoRate</th>
    <th  scope="col">ColorPages</th>
    <th  scope="col">ColorMinVol</th>
    <th  scope="col">ColorRate</th>
    <th  scope="col">ScanPages</th>
    <th  scope="col">ScanMinVol</th>
    <th  scope="col">ScanRate</th>
    <th  scope="col">A3 Mono Pages</th>
    <th  scope="col">A3MonoMinVol</th>
    <th  scope="col">A3MonoRate</th>
    <th  scope="col">A3 Color Pages</th>
    <th  scope="col">A3ColorMinVol</th>
    <th  scope="col">A3ColorRate</th>

    
    <th  scope="col">Mono Amount</th>
    <th  scope="col">Color Amount</th>
    <th  scope="col">Scan Amount</th>
    <th  scope="col">A3 Mono Amount</th>
    <th  scope="col">A3 Color Amount</th>
    <th  scope="col">Rental</th>
    <th  scope="col">Other Amount</th>
    <th  scope="col">Act Inv Amt</th>

    

    <th  scope="col">BilledUnitsMono</th>
    <th  scope="col">BilledUnitsColor</th>
    <th  scope="col">BilledUnitsScan</th>
    <th  scope="col">BilledUnitsA3Mono</th>
    <th  scope="col">BilledUnitsA3Color
    </th>
   
       
    </tr>
    </thead>
    @foreach ($sales as $sa)
    <tr>
    <td>{{ $sa->Name }}</td>
    <td>{{ $sa->Customer_Code }}</td>
    <td>{{ $sa->inv_num }}</td>
    <td>{{ $sa->txdate }}</td> 
    <td>{{ $sa->Asset }}</td> 
    <td>{{ $sa->serialNo }}</td> 
    <td>{{ $sa->ItemDesc }}</td>
    <td>{{ $sa->projectcode }}</td>
    <td>{{ $sa->monoPages }}</td>
    <td>{{ $sa->BillMonoMinVol }}</td>
    <td>{{ $sa->BillMonoRates }}</td>
    <td>{{ $sa->ColorPages}}</td>
    <td>{{ $sa->BillColMinVol }}</td>
    <td>{{ $sa->BillColRates }}</td>

    <td>{{ $sa->ScanPages }}</td>
    <td>{{ $sa->BillScnMinVol }}</td>
    <td>{{ $sa->BillScnRates }}</td>

    <td>{{ $sa->A3MonoPages}}</td>
    <td>{{ $sa->BillA3MMinVol }}</td>
    <td>{{ $sa->BillA3MRates }}</td>

    <td>{{ $sa->A3ColorPages }}</td>
    <td>{{ $sa->BillA3CMinVol }}</td>
    <td>{{ $sa->BillA3CRates }}</td>
    <td>{{ $sa->BillMonoAmt }}</td>
    <td>{{ $sa->BillColAmt }}</td>
    <td>{{ $sa->BillScnAmt}}</td>

    <td>{{ $sa->BillA3MAmt }}</td>
    <td>{{ $sa->BillA3CAmt }}</td>
    <td>{{ $sa->PeriodicAmt }}</td>

    <td>{{ $sa->OtherAmt }}</td>
    <td>{{ $sa->BillMonoAmt +$sa->BillColAmt+$sa->BillScnAmt+$sa->BillA3MAmt+$sa->BillA3CAmt+$sa->PeriodicAmt }}</td>
    <td>{{ $sa->monoPages }}</td>
    <td>{{ $sa->ColorPages }}</td>
    <td>{{ $sa->ScanPages }}</td>
    <td>{{ $sa->A3MonoPages }}</td>
    <td>{{ $sa->A3ColorPages }}</td>


    


    

    

    
    

    

    
    @endforeach
 
    
   

    </tr>
    </table>
    </div>








