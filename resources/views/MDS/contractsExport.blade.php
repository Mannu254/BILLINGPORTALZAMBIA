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
    <th  scope="col">Customer Name</th>
    <th  scope="col">Account</th>
    <th  scope="col">Billing Asset</th>       
    <th  scope="col">Description</th>  
    <th  scope="col">Billing Type</th>  
    <th  scope="col">FromQty</th>  
    <th  scope="col">ToQty</th> 
    <th  scope="col">Rate</th>  
    
    </tr>
    </thead>
     @foreach ($contracts as $con)
    <tr>
        <td>{{ $con->name }}</td>
        <td>{{ $con->account }}</td>
        <td>{{ $con->billingasset }}</td>
        <td>{{ $con->assetdesc }}</td> 
       
    </tr>
    
       @if(!empty($con->slabs))
        @foreach ($con->slabs  as $sl)
        
        <tr>
        <td>{{ $con->name }}</td>    
        <td></td>
        <td></td>   
        <td></td>
        <td>{{ $sl->cDescription }}</td>        
        <td>{{ $sl->iFromQty }}</td>
        @if( $sl->iToqty > 2000000 )
        <td>AboveCopies</td>
        @else
        <td>{{ $sl->iToqty }}</td>
        @endif
        <td>{{ $sl->frate }}</td>
    </tr>
   
        
        @endforeach
        @endif

    
    
    @endforeach   
    </table>
    </div>








