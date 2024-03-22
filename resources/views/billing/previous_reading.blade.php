@extends('billing.dashboard')

@section('content')
<style>
.card{

    background-color: lightgray;
    height: 55px;
  
}
.btn{
    margin:0px !important;
}
.required:after {
    content:" *";
    color: red;
  }
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
    #serial_hint li:hover {
        background-color: #ccc;
        
   }
   .twoToneButton,.clearAll{
    margin: 0px !important;
    height:31px;
  }
  .multiselect-container {
   /* margin-top:85px; */
   position: fixed;
  width: 100% !important;
  font-size: 12px;
  
 }
 button.multiselect {
  background-color: initial;
  border: 1px solid #ced4da;
}
 button.multiselect.dropdown-toggle.btn.btn-default {
    border: 1px solid;
    margin:0px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}


.no-outline:focus {
  outline: none;
}
#overlay{	
  position: fixed;
  top: 0;
  z-index: 100;
  width: 100%;
  height:100%;
  display: none;
  background: rgba(0,0,0,0.6);
}
.cv-spinner {
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;  
}
.spinner {
  width: 40px;
  height: 40px;
  border: 4px #ddd solid;
  border-top: 4px #2e93e6 solid;
  border-radius: 50%;
  animation: sp-anime 0.8s infinite linear;
}
@keyframes sp-anime {
  100% { 
    transform: rotate(360deg); 
  }
}
.is-hide{
  display:none;
}

</style>


<div class="container-fluid">
    <div id="overlay">
        <div class="cv-spinner">
        <span class="spinner"></span>
        </div>
        </div>

    <div class="row">
        <div class="form-group" >
        <select class="form-control form-control-sm dropdown" style="z-index: 1000" id="customer_id" name="customer_id[]"  multiple="multiple">
        @foreach ($clients as $customer)
        <option value="{{$customer->DCLink}}" style="z-index: 1000 !important; position:absolute;">{{$customer->Account}}-{{$customer->Name}}</option>
        @endforeach 
        </select>
        </div>        
        <button class='clearAll btn btn-primary btn-xs'><i class="fa fa-lg fa-times-circle"></i></button>
       
       <div class="input-group mb-5 col-md-2">
        <input type="" class="form-control "  id="datepicker" value="<?php echo date("m/d/Y"); ?>"  placeholder="Reading Date" required name="reading_date">
        </div>
    
            
        
        <div class="input-group mb col-md-3">
        <button type="button" id="customer_get" class="btn btn-primary btn btn-block twoToneButton"> <i class="fa fa-lg fa-refresh"></i> Get Customer Previous Readings</button>
        </div>    
   
    </div>
    <hr>
    <form action="" method="post">
      {{ csrf_field() }}
        
        
  
  <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
     <th scope="col" style="background-color:#00BFFF">Customer Name</th>
     <th scope="col"style="background-color:#00BFFF">Serial No</th>
    <th scope="col">Asset Code</th>
    <th scope="col">Description</th>
    <th scope="col">Reading Date</th>
    <th scope="col">Mono CMR</th>
    <th scope="col">Color CMR</th>
    <th scope="col">A3MONO CMR</th>
    <th scope="col">A3MCOLOR CMR</th>
    <th scope="col">SCAN CMR</th>

    <th scope="col">Billing Cycle</th>
    <th scope="col">Branch</th>
   
    <th scope="col">Collection Mode</th>
    
    
    
    <th scope="col">Remarks</th>
    <th scope="col">Physical Area</th>
    </thead>
    <tbody id="table1">
    
    </tbody>
    </table>
   
  </form>
  
  </div>


  
<script>
    $(document).ready(function(){ 
$("#datepicker").datepicker({

    changeMonth:true,
    changeYear:true,
    showOn: "button",
    buttonImage: "http://jqueryui.com/resources/demos/datepicker/images/calendar.gif",
    buttonImageOnly: true,
    defaultDate: new Date()
})

});
</script>





<script> 
   $(document).ready(function(){ 
  $('#customer_get').click(function() {    
    $('#table').dataTable().fnDestroy();
    var customer_id = $('#customer_id').val(); 
    var reading_date = $('#datepicker').val();
      
    if (customer_id.length ==0 ) { 
      alert('Kindly Select At least One Customers to fetch!!!')
      exit();
    }
    if (reading_date.length == 0) { 
      alert('Kindly Select Reading Date Month')
      exit();
    }
      $("#overlay").fadeIn(300);　
      $.ajax({ 
      type: "POST", 
      dataType: "json", 
      url: '/previous_reading', 
      data: {'customer_id': customer_id,'reading_date': reading_date,"_token":"{{ csrf_token() }}"},
      
      success: function(data){ 

       $('#table').dataTable().fnDestroy();
      $("tbody#table1").html("");
       
       $.each(data, function(index, value){               
   
    
   
  

    
    var mono_cmr ='<input type="text" id="mono_cmr" size =7 readonly  class="mono_cmr" required value="'+value.mono_cmr+'" name="mono_cmr[]">';
    var color_cmr ='<input type="text" id="color_cmr" size =7  readonly class="color_cmr" value="'+value.color_cmr+'" name="color_cmr[]">';
    var a3mono_cmr ='<input type="text" id="a3mono_cmr" size =7  readonly class="a3mono_cmr" value="'+value.a3mono_cmr+'" name="a3mono_cmr[]">';
    var a3color_cmr ='<input type="text" id="a3color_cmr" size =7 readonly  class="a3color_cmr" value="'+value.a3color_cmr+'" name="a3color_cmr[]">';
    var scan_pmr ='<input type="text" id="scan_pmr" size =7  readonly class="scan_pmr" value="'+value.scan_cmr+'" name="scan_pmr[]">';
    var remarks ='<input type="text" id="remarks" size =15 readonly  class="remarks" name="remarks[]">';      
  
    var html = ("<tr><td>"+value.Name+"</td><td>" +value.ucSASerialNo+ "</td><td>" +value.cCode+ "</td><td>" +value.cDescription+ "</td><td>" +value.reading_date+ "</td><td>"+mono_cmr+"</td><td>"+color_cmr+"</td><td>"+a3mono_cmr+"</td><td>"+a3color_cmr+"</td><td>"+scan_pmr+"</td><td>" +value.billing_cycle+ "</td><td>" +value.branch+ "</td><td>" +value.mode_collection+ "</td><td>"+value.remarks+"</td><td>" +value.cLocation+ "</td></tr>");
      
    $('tbody#table1').append(html);   
     
  
    });    
      $('#table').DataTable({
        "bLengthChange": false,
        "bPaginate": false,
        "sScrollY": "330px", 
        stateSave: true,
    
       "sScrollX": "100%", 
       "searching": true,
       fixedColumns:   {
      leftColumns:2
      }
        
      });
    
      
      } 
      }).done(function() {
        setTimeout(function(){
        $("#overlay").fadeOut(300);
        },500);
      }); 
      }); 
      });        
      </script> 
  
    <script>
    $(document).ready(function(){
    $('#customer_id').multiselect({
    nonSelectedText: 'Select Customers', 
    // enableFiltering: true,
    enableCaseInsensitiveFiltering: true,
    buttonWidth:'600px',
    maxHeight:450,
    includeFilterClearBtn:true, 
    
    });
    });
    </script>
  
    
    <script>
      $('.clearAll').on('click', function() {
      $("#customer_id").multiselect("clearSelection");
      });
    </script>
   
  

@endSection