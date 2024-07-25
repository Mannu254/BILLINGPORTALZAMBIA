@extends("billing.dashboard")

@section('content')
<style>
    table thead th{
        color:white !important;
        font-size: 12px;
        font-family:apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        font-weight: 600;
        white-space: nowrap;
        margin: 0px !important;
        padding: 0px !important;
        }
        td{
        font-size: 12px;
        white-space: nowrap;
        background-color: #fff !important;
        margin: 0px !important;
        padding: 2px !important;
        }
        input[type='text']{
       font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
       font-size: 12px;
       margin: 0px !important;
        padding: 0px !important;
        border:0px;
        }
        .fixed{
          background-color: #00BFFF;
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
  h6{
    text-align: center;
    margin: 0px !important;
    color: red;
    font-family:serif;
    background-blend-mode: color;
    font-weight: 600;
    animation: blink_text 2s infinite; 
    margin-top: 30px !important;
  }
@keyframes blink_text{
50% {color: yellow;
}
}
table{
  width: 100%;
}



</style>

<h6>ADVANCED BILLING!!</h6>
<hr>
<div class="container-fluid">
  <div id="overlay">
    <div class="cv-spinner">
    <span class="spinner"></span>
    </div>
    </div>
  
      <input type="text" id="billing_date" name="billing_date" hidden value="{{$today}}">
       
  <table id="table" class="table table-hover table-bordered table-sm text-center" style="width: 100%">
    <thead style="background-color:#00BFFF;">
      <tr style="font-family:apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;"><th colspan="5"></th><th colspan="3">MONO</th><th colspan="3">COLOR</th><th colspan="5"></th></tr>
    <tr>
   
    <th class="fixed" scope="col">Customer Name</th>
    <th class="fixed" scope="col">Code</th>
    <th class="fixed" scope="col">Curr</th>
    <th class="fixed" disabled scope="col">Billed Asset</th>
    <th class="fixed"scope="col">Description</th>
    <th scope="col">Units</th>
    <th scope="col">MinVol</th>
    <th>Rates</th>
    <th scope="col">Units</th>
    <th scope="col">MinVol</th>
    <th>Rates</th>
     
    <th scope="col">Rental</th>
    <th scope="col">Software</th>
    <th scope="col">(J/S)</th>
    <th scope="col">Total Normal</th>
    
    <th scope="col">Order Num Generated</th>

    
    </tr>
    </thead>
    <tbody>
    @foreach ($billing_review_data as $data )
    <tr>
    <td>{{ $data->name }}</td>
    <td><input type="text" class="account" style="font-weight:400; color:black;" value="{{ $data->account}}" readonly required  name="account[]" id="account"  size="4"></td>
    <td>TZSH</td>
   
    <td><input type="text" class="ucSABillingAsset" style="font-weight:400; color:black;" value="{{ $data->billingasset}}" readonly required  name="ucSABillingAsset[]" id="ucSABillingAsset"  size="15"></td>
    <td>{{ $data->assetdesc}}</td>
    <td><input type="text" class="monunit" style="font-weight:400; color:black;" value="{{ number_format($data->monunit)}}" readonly required   id="monunit"  size="8"></td>
    <td> <input type="text" class="min_mono_vol" style="font-weight:400; color:black;" value="{{ $data->min_mono_vol ?? 0}}" readonly required   id="min_mono_vol"  size="5"></td>
    <td>{{$data->comb_rates ?? 0}}</td>   
    <td><input type="text" class="colunit" style="font-weight:400; color:black;" value="{{ number_format($data->colunit)}}" readonly required   id="colunit" name="colunit[]"  size="8"></td>
    <td><input type="text" class="min_color_vol" style="font-weight:400; color:black;" value="{{ number_format($data->min_color_vol ?? 0)}}" readonly required name="min_color_vol[]" id="min_color_vol"  size="7"></td>
    <td>{{ $data->comb_rates_color ?? 0}}</td> 
   
    <td><input type="text" name="rental_charges[]" readonly size="8" value="{{number_format($data->famount ?? '0',2)}}"></td>
    <td><input type="text" name="soft_charges[]" readonly size="8" value="{{number_format($data->software ?? '0',2)}}"></td>
    <td><input type="text" class="billing_type" readonly style="font-weight:400; color:black;"  required value="{{$data->ulARJointSeparateBill}}" name="billing_type[]" id="billing_type"  size="8"></td>
    <td><input type="text"  name="total[]" class="total_nom" style="font-weight:400; color:black;" readonly value="{{number_format($data->total_inv_amt,2)}}" size="13"></td>
 

    
    <td><input type="text" class="order_generated"  style="font-weight:400; color:chartreuse;" value="{{$data->OrderNum}}"  name="order_generated[]" readonly id="order_generated"  size=""><input hidden type="checkbox" id="order_num" checked onclick="return false;" class="order_num" name="order_num[]"  value="{{$data->OrderNum}}"><input hidden type="checkbox" id="cust_code" checked onclick="return false;" class="cust_code" name="cust_code[]"  value="{{$data->account ?? ''}}"><input hidden type="text" id="check_if_done"  class="check_if_done" name="check_if_done"  value="{{$data->check_if_done ?? 0}}"></td>
</tr>    
    @endforeach
    <button class="btn btn-primary btn-sm" id="summary" data-toggle="modal" data-target="#exampleModal">
      <i class="fa fa-file-excel-o"></i> Billing Summary Sheet
    </button> 
    
    </tbody>
    <div >
      <button type="submit"  name="submit" id="sales_order" style="float: right; margin-bottom:-10px !important;" class="btn btn-xs mr-auto btn-success sales_order"></i> Generate Sales Order   <i class="fa fa-play-circle"></i> </button>
      </div> 
      <br><br>
    </table>
   
     {{-- </form> --}}
    
  </div>
   <!-- Insert Modal -->
   <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
    <div class="modal-header ">
      
      <button class="btn btn-info btn-xs ml-auto" id="copy_btn">Copy</button>
    </div>
    
   
    <form action="" method="POST" id="search">
    {{ csrf_field() }}
    <div class="modal-body">
   
   
    <table id="table1" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
      <th  scope="col">Code</th> 
      <th  scope="col">Customer Name</th>    
      
      <th  scope="col">Curr</th>
      <th  scope="col">Order Num</th>
      <th  scope="col">Order Date </th>
      <th  scope="col">Exclusive Amt</th>
      <th  scope="col">Tax Amt</th>
      <th  scope="col">Inclusive Amt</th>
    </tr>
    </thead>
    <tbody id="table1">
    </tbody>
    </table>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-sm float-left btn-secondary done" data-dismiss="modal">Done</button>
    </div>
    </form>
    </div>
    </div>
    </div>


  <script>
    $(document).ready(function(){ 
        var table =$('#table').DataTable({
  
   "bLengthChange": false,
 "bPaginate": false,
  "sScrollY": "300px", 
  "sScrollX": "100px", 

 "searching": false,
  "columnDefs": [
    { "orderable": false, "targets":[2,3,5,6,7,8,9,10,11,12,13,14] }
  ],
 fixedColumns:   {
    leftColumns:1
    },   
    "order": [[ 4, 'asc' ]]
});
});
</script>



<script>
  $('#sales_order').click(function(e) {
    e.preventDefault();
    
     $("#overlay").fadeIn(300); 

    var billing_date = $("#billing_date").val(); 
    var values = [];
    

    $("input[name='account[]']").each(function (index) {
      var value = {};

      value['account']  = $("input[name='account[]']").eq(index).val().replace(/,/g, "");
      value['ucSABillingAsset']  = $("input[name='ucSABillingAsset[]']").eq(index).val().replace(/,/g, "");
      value['rental_charges']  = $("input[name='rental_charges[]']").eq(index).val().replace(/,/g, "");
      value['billing_type']  = $("input[name='billing_type[]']").eq(index).val().replace(/,/g, "");
      value['colunit']  = $("input[name='colunit[]']").eq(index).val().replace(/,/g, "");
      value['soft_charges']  = $("input[name='soft_charges[]']").eq(index).val().replace(/,/g, "");  


       

       

       value['total']  = $("input[name='total[]']").eq(index).val().replace(/,/g, "");  
    

    values.push(value);  

     });
     

     
  $.ajax({ 
  type: "POST", 
  dataType: "json", 
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
  url: "{{ route('adv_salesOrder') }}", 
  data: {'billing_date':billing_date,'values': values},
  
  success: function(data){ 
    alert("Sales Order(s) Created Successfully.");   
    $("#sales_order").prop('disabled',true);   
  },
    error: function (request, status, error) {
        alert('Error!! in generating Sales Order Contact Administrator');
        
    }
  }).done(function() {
    window.location.reload(); 
    setTimeout(function(){$("#overlay").hide();},400);   
  }).fail(function()  {
    setTimeout(function(){$("#overlay").hide();},400);   
  }); 
  }); 


</script>

<script>
  $("input[name='account[]']").each(function (index) {
    var order_generated  = $("input[name='order_generated[]']").eq(index).val().replace(/,/g, "");
    var check_if_done  = $(".check_if_done").eq(index).val();

     if (order_generated =='' && check_if_done =='0'){
      $("#sales_order").prop('disabled',false); 
      
    }
    else{
      $("#sales_order").prop('disabled',true); 
      $("input[name='order_generated[]']").eq(index).css('backgroundColor', 'green'); 

      // return false; // breaks 
    }

    
   

  });
</script>
<script>
  $(document).ready(function() {
  $('#summary').on('click', function(e) {
  e.preventDefault();
      var order_num = [];
      var account = [];
  $(".order_num:checked").each(function(){
    order_num.push($(this).val());
  });

  $(".cust_code:checked").each(function(){
    account.push($(this).val());
  });
 
  var selected_values = order_num;
  var account = account;
  $.ajax({
  type: "GET",
   url: "{{route('export_orders')}}",
   data: {'orders':selected_values,'account':account,"_token":"{{ csrf_token() }}"}, 

 
success: function (data) {
$("tbody#table1").html("");
$.each(data, function(index, value){

  var date = new Date(value.OrderDate).toLocaleDateString();
 
  
  var html = ("<tr><td>" + value.Account + "</td><td>" + value.Name + "</td><td>" +'TZSH'+  "</td><td>" +value.OrderNum+ "</td><td>" +date+"</td><td>" +(parseFloat(value.InvTotExcl).toFixed(2)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')+"</td><td>" +(parseFloat(value.InvTotTax).toFixed(2)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')+"</td><td>"+(parseFloat(value.InvTotIncl).toFixed(2)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',')+"</td></tr>");

  $('tbody#table1').append(html);

  });
  }

  
  });
  

  });

  });

</script>

<script>
  var copyBtn = document.querySelector('#copy_btn');
copyBtn.addEventListener('click', function () {
  var urlField = document.querySelector('#table1');
   
  // create a Range object
  var range = document.createRange();  
  // set the Node to select the "range"
  range.selectNode(urlField);
  // add the Range to the set of window selections
  window.getSelection().addRange(range);
   
  // execute 'copy', can't 'cut' in this case
  document.execCommand('copy');
}, false);
 </script>






@endsection