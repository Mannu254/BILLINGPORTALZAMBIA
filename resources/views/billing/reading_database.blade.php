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
  z-index: 4000 !important;
  width: 100%;
  height:100%;
  display: none;
  background: rgba(0,0,0,0.6);
}
.cv-spinner {
  height: 100%;
  display: flex;
  z-index: 2000;
  justify-content: center;
  align-items: center;  
}
.spinner {
  width: 40px;
  z-index: 2000;
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
<div id="overlay">
  <div class="cv-spinner">
  <span class="spinner"></span>
  </div>
  </div>
<div class="container-fluid" style="position: fixed">
<div class="card">
<div class="card-body">
<form action="{{route('post_excel_reading')}}" method="POST" enctype="multipart/form-data">
{{ csrf_field() }}
<div class="row">

 <div class="input-group col-md-3">
<div class="input-group-prepend">
<span class="input-group-text">Upload</span>
</div>
<div class="custom-file">
<input type="file" required class="custom-file-input" id="file" name="file">
<label class="custom-file-label" for="fileName">Choose file</label>
</div>
</div>   
<div class="input-group col-md-2">
<input type="" class="form-control form-control" id="datepicker" value="{{$session->session_date ?? ''}}" placeholder="Select Reading Date" required name="reading_date">
</div>   


            
<div class="input-group   col-md-2">
<button type="submit" id="" class="btn btn-primary btn btn-block twoToneButton"> <i class="fa fa-lg fa-arrow-circle-o-up"></i> Upload Readings</button>
</div>   


</div>
</form> 
</div>
</div>
</div>
<br><br>

<hr>



<script>
    $(document).ready(function(){ 
$("#datepicker,#datepicker2").datepicker({

    changeMonth:true,
    changeYear:true,
    showOn: "button",
    buttonImage: "http://jqueryui.com/resources/demos/datepicker/images/calendar.gif",
    buttonImageOnly: true,
    defaultDate: new Date()
})

});
</script>
<script type="application/javascript">
    $('input[type="file"]').change(function(e){
    var fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
    });
</script>


<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
    <div class="modal-header ">
    <h5 class="modal-title w-100 text-center" id="exampleModalLabel"style="">Enter Country Details</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <form action="{{ action('BillingExecutiveController@post_single_reading')}}" method="POST">
       {{ csrf_field() }}
    <div class="modal-body">
    <div class="form-row">
    <div class="form-group col-md-3">
    <label class="required">Customer Name</label>
    <input type="text" class="form-control form-control-sm" required id="cust_name" name="cust_name"  readonly>
    </div>
    <div class="form-group col-md-2">
    <label class="required">Serial No</label>
    <input type="text" class="form-control form-control-sm" id="serial_no" required name="serial_no"  placeholder="Search...">
    <div style="z-index:1000; !iportant;" id="serial_hint"></div>
    </div>
    <div class="form-group col-md-4">
    <label class="required">Description</label>
    <input type="text" class="form-control form-control-sm" required name="description" id="description" readonly>
    </div>
    <div class="form-group col-md-2">
    <label class="required">Mono CMR</label>
    <input type="number" min="0" class="form-control form-control-sm" required name="mono_cmr"  placeholder="Mono CMR">
    </div>
    </div>
    <div class="form-row">
    <div class="form-group col-md-3">
    <label>Color CMR</label>
    <input type="number" min="0" class="form-control form-control-sm"  name="color_cmr"  placeholder="Color CMR">
    </div>
    <div class="form-group col-md-2">
    <label>A3 Mono CMR</label>
    <input type="number" min="0" class="form-control form-control-sm"  name="a3_mono_cmr"  placeholder="A3 Mono CMR">
    </div>
    <div class="form-group col-md-2">
    <label>A3 Color CMR</label>
    <input type="number" min="0" class="form-control form-control-sm"  name="a3_color_cmr"  placeholder="A3 Color CMR">
    </div>
    <div class="form-group col-md-2">
    <label>Scan CMR</label>
    <input type="number" min="0" class="form-control form-control-sm"  name="scan_cmr"  placeholder="Scan CMR">
    </div>
    <div class="form-group col-md-2">
    <label class="required">Reading Date</label>
    <input type="date" required class="form-control form-control-sm" value="<?php echo date("Y-m-d"); ?>"  name="reading_date">
    </div>
    </div>
    <div class="modal-footer">
     <button type="submit" class="btn btn  btn-primary"><i class="fa fa-lg fa-arrow-circle-o-up"></i> Upload</button>
    </div>
    </div>
    </form>
    </div>
    </div>
    </div>


<script>
$(function() {         
$(document).on('click','li',function(){ 
    var timeout;
    var delay = 300; 
    timeout = setTimeout(function(){ 
   var serial_no =$('#serial_no').val();
   $.ajax({ 
    type: "POST", 
    dataType: "json", 
    url: '/search_serial', 
    data: {'serial_no': serial_no,"_token":"{{ csrf_token() }}"},

    success: function(data){ 
        $('#cust_name').val(data.Name);
        $('#description').val(data.cDescription);  
  
    
} 
});
}, delay);
}); 
}); 

</script>

<script>
    $(document).ready(function(){
    $('#serial_no').on('keyup',function(){
    var serial =$(this).val();

    if(serial.length >= 4)
    {
    $.ajax({ 
    type: "POST", 
    dataType: "json", 
    url: '/search_hint', 
    data: {'serial': serial,"_token":"{{ csrf_token() }}"},

    success: function(data){ 
        $('#serial_hint').html(data);
          
  
    
} 
});
}
});

$(document).on('click','li',function(){
    var value = $(this).text();
    $('#serial_no').val(value);
    $('#serial_hint').html('');


});


});
</script>

<script> 
   $(document).ready(function(){ 
  $('#customer_get').click(function() {    
    $('#table').dataTable().fnDestroy();
    var customer_id = $('#customer_id').val(); 
  
    if (customer_id.length == 0 ) { 
      alert('Kindly Select At least One Customers to fetch!!!')
      exit();
    }
      $("#overlay").fadeIn(300);　
      $.ajax({ 
      type: "POST", 
      dataType: "json", 
      url: '/cust_assets', 
      data: {'customer_id': customer_id,"_token":"{{ csrf_token() }}"},
      
      success: function(data){ 

       $('#table').dataTable().fnDestroy();
      $("tbody#table1").html("");
       
       $.each(data, function(index, value){

  
    var input_cylce ='<input type="text" id="billing_cycle" size =7   class="billing_cycle" name="billing_cycle[]">';
    var ucSASerialNo ='<input type="text" id="ucSASerialNo" size =10 hidden  class="ucSASerialNo" value="'+value.ucSASerialNo+'" name="ucSASerialNo[]">';  
    var cust_name ='<input type="" id="cust_name"    class="cust_name" hidden value="'+value.Name+'" name="cust_name[]">';  
    var cDescription ='<input type="text" id="cDescription" size =10 hidden  class="cDescription"value="'+value.cDescription+'" name="cDescription[]">'; 
    var asset_code ='<input type="text" id="asset_code" size =10 hidden  class="asset_code"value="'+value.cCode+'" name="asset_code[]">'; 

    var branch ='<input type="text" id="branch" size =7   class="branch" name="branch[]">';
    var mode ='<input type="text" id="mode" size =7   class="mode" name="mode[]">';
    var mono_cmr ='<input type="text" id="mono_cmr" size =7   class="mono_cmr" required value="'+value.mono_cmr+'" name="mono_cmr[]">';
    var color_cmr ='<input type="text" id="color_cmr" size =7   class="color_cmr" value="'+value.color_cmr+'" name="color_cmr[]">';
    var a3mono_cmr ='<input type="text" id="a3mono_cmr" size =7   class="a3mono_cmr" value="'+value.a3mono_cmr+'" name="a3mono_cmr[]">';
    var a3color_cmr ='<input type="text" id="a3color_cmr" size =7   class="a3color_cmr" value="'+value.a3color_cmr+'" name="a3color_cmr[]">';
    var scan_pmr ='<input type="text" id="scan_pmr" size =7   class="scan_pmr" value="'+value.scan_cmr+'" name="scan_pmr[]">';
    var remarks ='<input type="text" id="remarks" size =15   class="remarks" name="remarks[]">';      
  
    var html = ("<tr><td>"+value.Name+"</td><td>" +ucSASerialNo+ "" +value.ucSASerialNo+ "</td><td>" +cust_name+ " "+asset_code+" " +value.cCode+ "</td><td>"+cDescription+"" +value.cDescription+ "</td><td>"+mono_cmr+"</td><td>"+color_cmr+"</td><td>"+a3mono_cmr+"</td><td>"+a3color_cmr+"</td><td>"+scan_pmr+"</td><td>" +input_cylce+ "</td><td>" +branch+ "</td><td>" +mode+ "</td><td>"+remarks+"</td><td>" +value.cLocation+ "</td></tr>");
      
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
    $(document).ready(function() {
    setTimeout(function(){
      $("#overlay").fadeOut(300); 
          }, 1000);
  });
  window.onbeforeunload = function() {  
    $("#overlay").fadeIn(300); 
  }
  window.onpageshow = function(event) {
      if (event.persisted) {
      $("#overlay").fadeOut(600); 
    }
    };
   </script>
  

@endSection