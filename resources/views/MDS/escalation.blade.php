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
    <th  scope="col">Customer Name</th>  
    <th  scope="col">Service Asset</th>
    <th  scope="col">Description</th>  
    <th  scope="col">Rate In May</th> 
    <th  scope="col">Reading In May</th> 
    <th  scope="col">MinVol In May</th> 
    <th  scope="col">Rental In May</th> 
    <th  scope="col">TotAmtInc In May</th> 




    <th  scope="col">Rate in June</th>     
    <th  scope="col">Reading In June</th> 
    <th  scope="col">MinVol In June</th> 
    <th  scope="col">Rental In June</th> 
    <th  scope="col">TotAmtInc In June</th> 
    
    
    
    
    </tr>
    </thead>
     @foreach ($billing as $bil)
    <tr>
        <td>{{ $bil->Account }}</td>
        <td>{{ $bil->Name }}</td>
        <td>{{ $bil->ucIDSOrdTxCMServiceAsset }}</td>    
        <td>{{ $bil->ucIDSOrdTxCMMeterType }}</td>  
        
        
        <td>{{ $bil->prate ?? '' }}</td>
        <td>{{ $bil->preading ?? ''}}</td>
        <td>{{ $bil->pMinVol  ?? ''}}</td>
        <td>{{ $bil->PRental_tAmount ?? '' }}</td>
        <td>{{ $bil->PTotAmount ?? ''}}</td>        
        
        

        <td>{{ $bil->ucIDSOrdTxCMRates ?? '' }}</td>
        <td>{{ $bil->uiIDSOrdTxCMCurrReading - $bil->uiIDSOrdTxCMPrevReading }}</td>
        <td>{{ $bil->ucIDSOrdTxCMMinVol ?? ''}}</td>
        <td>{{ $bil->CRental_tAmount ?? '' }}</td>
        <td>{{ $bil->fQuantityLineTotExcl }}</td>


        {{-- current --}}

        
        
   
   

    </tr>
       
   
        
        
           
   
    
      

    
    
    @endforeach
  
    
   

   
    </table>
    </div>








