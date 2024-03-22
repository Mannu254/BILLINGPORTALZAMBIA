<style>
    th {
       font-size: 5px !important;
       font-weight: bolder
      
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
    <th  scope="col">Serial No</th>
    <th  scope="col">CON Code</th>    
    <th  scope="col">Customer</th>       
    </tr>
    </thead>
    @foreach ($assets as $sa)
    <tr>
    <td>{{ $sa->ucSASerialNo }}</td>
    <td>{{ $sa->ucSABillingAsset }}</td>    
    <td>{{ $sa->Name }}</td>  

    

    
    @endforeach
 
    
   

    </tr>
    </table>
    </div>








