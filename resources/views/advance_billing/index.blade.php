@extends('billing.dashboard')

@section('content')
<style>
  .btn{
    font-family: 'Open Sans',sans-serif;
    font-size: 12px;

  }
  table thead th{
        color:white !important;
        background-color:deepskyblue !important;
        font-size: 12px;
        font-family:apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        font-weight: 600;
        white-space: nowrap;
        }
        td{
        font-size: 12px;
        height: 12px !important;
        white-space: nowrap;
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
#selected{
  margin-left: auto;
  padding-right: 50px;
  color: red;
  font-family: 'Open Sans',sans-serif;
    font-size: 12px;

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
input {
  border-top-style: hidden;
  border-right-style: hidden;
  border-left-style: hidden;
  border-bottom-style: hidden;  
  background-color: #eee;
}

.no-outline:focus {
  outline: none;
}
h6{
    text-align: center;
    margin: 0px !important;
    color: red;
    font-family:serif;
    background-blend-mode: color;
    font-weight: 600;
    animation: blink_text 2s infinite; }
@keyframes blink_text{
50% {color: yellow;}
}
table{
  width: 100%;
}
.card{
  height: 60px;
}
</style>
<div class="container-fluid position-fixed; position:absolute;">
<div class="card" style="background-color:lightgrey">
<div class="card-body">
 
<div class="row">

<div class="form-group " >
<select class="form-control form-control-sm dropdown" style="z-index: 1000" id="customer_id" name="customer_id[]"  multiple="multiple">

@foreach ($clients as $customer)
<option value="{{$customer->DCLink}}" style="z-index: 1000 !important; position:absolute;">{{$customer->Account}}-{{$customer->Name}}</option>
@endforeach 
</select>
</div>

<button class='clearAll btn btn-danger btn-xs'><i class="fa fa-lg fa-times-circle"></i></button>

  <div class="input-group mb col-md-3">
  <button type="button" id="customer_get" class="btn btn-danger btn btn-block twoToneButton"> <i class="fa fa-lg fa-refresh"></i>   Get Selected Customer List Adv. Billing</button>
  </div>
  
 
  </div>

<div id="overlay">
<div class="cv-spinner">
  <span class="spinner"></span>
</div>
</div>
<div class="" id="selected"></div>
</div>
</div>
</div>
</div>
<h6>ADVANCED BILLING!!</h6>
<hr>
<div class="container-fluid">
  <form action="{{action('AdvancedBillingController@adv_meter_reading_review')}}" method="get">
    {{ csrf_field() }}
    <div class="container-fluid" style="width: 160px;">
    <div class="input-group">
    <input type="" class="form-control form-control-sm" style="text-align: center;color:chartreuse;background-color:black" readonly id="" value="{{$session->session_date ?? ''}}" placeholder="Select Billing Date" required name="billing_date">
    </div></div>
      

<table id="table" class="table table-hover table-bordered table-sm text-center" style="width: 100%">
  <thead style="background-color:#00BFFF;">
  <tr>
  <th></th>
  <th scope="col" style="background-color:#00BFFF">Customer Name</th>
  <th scope="col">CustCode</th>
  <th scope="col">ContractStart</th>
  <th scope="col">ContractEnd</th>
  <th scope="col">Machines</th>
  <th scope="col">Reading Sage</th>
  <th scope="col">Reading Pending</th>
  <th scope="col">Jan</th>
  <th scope="col">Feb</th>
  <th scope="col">Mar</th>
  <th scope="col">Apr</th>
  <th scope="col">May</th>
  <th scope="col">Jun</th>
  <th scope="col">Jul</th>
  <th scope="col">Aug</th>
  <th scope="col">Sep</th>
  <th scope="col">Oct</th>
  <th scope="col">Nov</th>
  <th scope="col">Dec</th>
  <th scope="col">Cycle</th>
  </tr>
  </thead>
  <tbody id="table1">
  
  </tbody>
  </table>
  <button type="submit"  name="submit"  class="btn btn-xs mr-auto btn-info"></i> Go to Meter Reading Review <i class="fa fa-arrow-right"></i></button>
</form>

</div>



<script>
 
  $(function() {         
$('#customer_get').click(function() { 
  $('#table').dataTable().fnDestroy();
  var customer_id = $('#customer_id').val(); 

  if (customer_id.length == 0 ) { 
    alert('Kindly Select Customers to fetch!!!')
    exit();
  }
   $("#overlay").fadeIn(300);　
    $.ajax({ 
    type: "POST", 
    dataType: "json", 
    url: '/cust_search', 
    data: {'customer_id': customer_id,"_token":"{{ csrf_token() }}"},
    
    success: function(data){ 
    $('#table').dataTable().fnDestroy();
    $("tbody#table1").html("");
     
     $.each(data, function(index, value){
      
    var date_start = new Date(value.startdate).toLocaleString('en-us',{month:'short', year:'numeric', day:'numeric'}).split(':');
    var end_start = new Date(value.Enddate).toLocaleString('en-us',{month:'short', year:'numeric', day:'numeric'}).split(':');
    var url ='<a href="#" id="save" data-id=' +value.cSimpleCode+'><i class="fa fa- fa-plus-circle"></i></a>';
   var input ='<input type="checkbox" id="cust_id" checked onclick="return false;" class="cust_id" name="cust_id[]"  value ='+value.DCLink+'>';
   var input_cylce ='<input type="text" id="billing_cyle" size =5 readonly  onclick="return false;" class="billing_cyle" name="billing_cyle[]"  value ='+value.billcylce+'>';

    var html = ("<tr><td>" + input + "</td><td>" + value.Name + "</td><td>" +value.Account+ "</td><td>" +date_start+ "</td><td>" +end_start+ "</td><td>" +value.total_machine+ "</td><td>" +value.total_asset_billed_count+ "</td><td>" +value.pending_billing+ "</td><td>"+(value.jan)+"</td><td>"+(value.feb)+"</td><td>"+value.mar+"</td><td>"+value.apr+"</td><td>"+(value.may)+"</td><td>"+(value.jun)+"</td><td>"+value.jul+"</td><td>"+(value.aug)+"</td><td>" +(value.sep)+ "</td><td>"+(value.oct)+"</td><td>"+(value.nov)+"</td><td>"+(value.dec)+"</td><td>"+input_cylce+"</td></tr>");
    
    $('tbody#table1').append(html);
 
   

    });
    
    $('#table').DataTable({
      "bLengthChange": false,
      "bPaginate": false,
      "sScrollY": "200px", 
  
     "sScrollX": "100%", 
     "searching": false,
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
  maxHeight:400,
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
 
    


  

@endsection