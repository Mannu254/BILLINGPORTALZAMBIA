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
        <th></th>
        <th></th>
        <th></th>
        <th></th>
        @foreach ($period as $pd)
        <th style="color:royalblue" scope="col">{{$pd->format("M-y")}}</th>
        <th></th>
        @endforeach
        <th colspan="2" style="text-align: center">Total</th>
        <th colspan="2"style="text-align: center" >Average</th>
      </tr>
    <tr>   
    <th  scope="col">Name</th>
    <th  scope="col">Serial No</th>
    <th  scope="col">Description</th>
    <th  scope="col">Physical Area</th>
    @foreach ($period as $pd)
    <th style="color:royalblue" scope="col">Mon </th>
    <th style="color:red" scope="col"> Col </th>
      @endforeach
      <th  scope="col"> Mono</th>
      <th  scope="col">Color</th>
      <th  scope="col">Mono</th>
      <th  scope="col">Color</th>
   
       
    </tr>
    </thead>
    @foreach ($clients as $cl)
    <tr>
    <td>{{ $cl->Name }}</td>
    <td>{{ $cl->ucSASerialNo }}</td>
    <td>{{ $cl->cDescription }}</td>
    <td>{{ $cl->cLocation }}</td>
     @foreach ($cl->data as $dt) 
     
     
         
        
        
    
    

    <td>{{ $dt['mono_vol'] }}</td>
    <td>{{ $dt['col_vol'] }}</td>

     @endforeach

     <td>{{ $cl->total_mon_vol }}</td>
     <td>{{ $cl->total_col_vol }}</td>
     <td>{{ $cl->average_mono }}</td>
     <td>{{ $cl->average_color }}</td>
      @endforeach
   

    </tr>
    </table>
    </div>








