@extends('MDS.dashboard')

@section('content')
<style>
      table thead th{
    color:white !important;
    font-size: 12px;
    font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
    font-weight: 600;
    white-space: nowrap;
    }
    td{
    font-size: 11px;
    height: 12px !important;
    white-space: nowrap;
    }
</style>
<div class="container">
  
<div class="card">
    <div class="card-header">
      Search Assets
    </div>
    <div class="card-body">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text" id="basic-addon1">Search</span>
            </div>
            <input type="text" class="form-control" name="hint" id="hint"  aria-label="Username" placeholder="Enter Serial / ML-NO/ CON Code / Customer Name">
          </div>
    </div>
  </div>
</div>
<div class="container-fluid">
<table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
     <th scope="col" style="background-color:#00BFFF">Customer Name</th>
     <th scope="col"style="background-color:#00BFFF">Serial No</th>
     <th scope="col"style="background-color:#00BFFF">Description</th>
     <th scope="col"style="background-color:#00BFFF">Location</th>    
     <th scope="col"style="background-color:#00BFFF">cCode</th>
     <th scope="col"style="background-color:#00BFFF">CON Code</th>
    <th scope="col">Contract Start Date</th>
    <th scope="col">Contract End Date</th>
    
    </thead>
    <tbody id="table1">
    
    </tbody>
    </table>  
  </div>


  <script> 
    $(document).ready(function(){ 
   $('#hint').on('keyup',function() {    
     $('#table').dataTable().fnDestroy();
     var hint = $('#hint').val(); 
   
     if (hint.length >= 4 ) {        
     
       $("#overlay").fadeIn(300);　
       $.ajax({ 
       type: "POST", 
       dataType: "json", 
       url: '/mds_cust_assets', 
       data: {'hint': hint,"_token":"{{ csrf_token() }}"},
       
       success: function(data){ 
 
        $('#table').dataTable().fnDestroy();
       $("tbody#table1").html("");
        
        $.each(data, function(index, value){
            var Sdate  = new Date(value.Sdate).toLocaleDateString('en-GB');
            var Edate  = new Date(value.Edate).toLocaleDateString('en-GB');
 
                
 
         
   
     var html = ("<tr><td>"+value.Name+"</td><td>"+value.ucSASerialNo+ "</td><td>"+value.Desc+ "</td><td>"+value.cLocation+ "</td><td>" +value.ConCode+ "</td><td>" +(value.ucSABillingAsset ?? '')+"</td><td>" +Sdate+ "</td><td>" +Edate+ "</td></tr>");
       
     $('tbody#table1').append(html);   
      
   
     });    
       $('#table').DataTable({
         "bLengthChange": false,
         "bPaginate": false,
         "sScrollY": "330px", 
         stateSave: true,
     
         
        "searching": true,
     
         
       });
     
       
       } 
       }).done(function() {
         setTimeout(function(){
         $("#overlay").fadeOut(300);
         },500);
       });
    } 
       }); 
       });        
       </script> 


@endsection