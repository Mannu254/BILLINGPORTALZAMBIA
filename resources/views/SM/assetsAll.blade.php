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
    <th  scope="col">Account</th>
    <th  scope="col">AssetCode</th>
    <th  scope="col">MCContractsCode</th>       
    <th  scope="col">Customer Name</th>  
    <th  scope="col">SalesRep</th> 
    <th  scope="col">CustomerArea</th> 
    <th  scope="col">CustomerRevisedArea</th> 
    <th  scope="col">ServiceArea</th> 
    <th  scope="col">Machine Location</th>
    <th  scope="col">cSerialNo</th>
    <th  scope="col">Machine Description</th>
    <th  scope="col">MCtartDate</th>
    <th  scope="col">MCFEndDate</th>
    <th  scope="col">Installation Date</th>
    <th  scope="col">MeterType</th>
    <th  scope="col">Technician</th>   
    
    
    </tr>
    </thead>
     @foreach ($assets_all as $sa)
    <tr>
        <td>{{ $sa->Account }}</td>
        <td>{{ $sa->Acode }}</td>
        <td>{{ $sa->ucSABillingAsset }}</td>
        <td>{{ $sa->cname }}</td>  
        <td>{{ $sa->salesRep }}</td>  
        <td>{{ $sa->ulARRegionArea }}</td> 
        <td>{{ $sa->ulARRegionArea }}</td>
        <td>{{ $sa->service_area }}</td>
        <td>{{ $sa->cLocation }}</td>
        <td>{{ $sa->cSerialNo }}</td>
        <td>{{ $sa->asset_desc }}</td>
        <td>{{ \Carbon\Carbon::parse($sa->udSAContractStart)->format('d-m-Y')}}</td>
        <td>{{ \Carbon\Carbon::parse($sa->udSAContractEnd)->format('d-m-Y') }}</td>
        <td>{{ \Carbon\Carbon::parse($sa->dInstallationDate)->format('d-m-Y') }}</td>
        <td>{{ $sa->cInvSegValue4Desc }}</td>
        <td>{{ $sa->cFirstName }}</td>

        

       
        
        

        


        

        

        

        



        
    
   
    

    

    
    @endforeach
  
    
   

    </tr>
    </table>
    </div>








