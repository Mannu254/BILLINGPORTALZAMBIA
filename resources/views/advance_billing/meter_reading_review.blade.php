@extends('billing.dashboard')

@section('content')
<style>
    h4{
        font-weight: 400;
       padding-left:20px !important;
       font-family: serif;
    }
    table thead th{
        color:white !important;
        font-size: 11px;
        font-family:apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        white-space: nowrap;
        margin: 0px !important;
        padding: 0px 1px 0px 1px !important;
        }
        td{
        font-size: 11px;
        white-space: nowrap;
        background-color: #fff !important;
        margin: 0px !important;
        padding: 2px !important;
        }
        input[type='text']{
       font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
       font-size: 11px;
       margin: 0px !important;
        padding: 0px !important;
        border: 0;
        }
        .fixed{
  background-color:#00BFFF;
  
  
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
input[readonly] {
    background-color: #f6f6f6;
    margin: 0px !important;
        padding: 0px !important;
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

</style>
<br>
<div class="container-fluid position-fixed">
<div class="row">
  
<h4>Meter Reading Sheet</h4>


<div id="overlay">
<div class="cv-spinner">
<span class="spinner"></span>
</div>
</div>

<div class="input-group mb col-md-2" style="margin-left:50px;">
<button type="submit"  name="submit" id="update_reading"  class="btn btn-xs mr-auto btn-success update_reading"> Update Meter Reading <i class="fa fa-lg fa-upload"></i></button>
</div> 
<div class="ml-auto" style="margin-right:40px;background-clor:gray;float:left;position:relative;">
 Billing Date:  <input name="billing_date" id="billing_date_check" style="background-color:lightgrey;" readonly value="{{$billing_date}}">
</div>
</div>
</div>
<br>
<hr>
<h6>ADVANCED BILLING!!</h6>
<div class="container-fluid">
  <form action="{{action('AdvancedBillingController@adv_billing_review')}}" method="get">
    {{ csrf_field() }}
    <input name="billing_date" hidden id="billing_date" style="background-color:lightgrey;" readonly value="{{$billing_date}}">
       
  <table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
      <tr><th colspan="6"></th><th colspan="3">MONO  <div id="total_mono" class="total_mono" style="float: right;color:yellow;" ></div></th><th colspan="3">COLOR <div id="total_color" class="total_color" style="float: right;color:yellow;" ></div></th><th colspan="2"></th></tr>
      <tr>
         
          <th class="fixed" scope="col">Customer Name</th>
          <th class="fixed" scope="col">Code</th>
          <th class="fixed" scope="col">SerialNo</th>
          <th class="fixed"scope="col">Description</th>
          <th class="fixed">Billing Asset</th>
          <th class="fixed">Reading Date</th>
          <th scope="col">PMR</th>
          <th scope="col">CMR</th>
          <th scope="col">Copies </th>
          <th scope="col">PMR</th>
          <th scope="col">CMR</th>
          <th scope="col">Copies</th>
      
        
          <th scope="col">Physical Area</th>
          
          <th scope="col">Update Flag</th>
          
    </tr>
    </thead>
    <tbody>
    @foreach ($clients_machines as $machine )
    <tr>
    <td>{{ $machine->Name }}</td>
    <td>{{ $machine->Account }}</td>
    <td>{{ $machine->ucSASerialNo}}</td>
    
    <td>{{ $machine->cDescription}}</td>
    
      @if($machine->ucSABillingAsset =='')
      <td> <input type="text" name="ucSABillingAsset[]" readonly size="12" value="{{ $machine->cCode }}"></td>
      
      
      @else
        <td> <input type="text" name="ucSABillingAsset[]" readonly size="15" value="{{ $machine->ucSABillingAsset }}"></td>
                
      
      @endif      
      
    <td>{{ $machine->ReadingDate}}<input type="text" hidden  name="DCLink[]" readonly size="" value="{{ $machine->DCLink }}"><input type="text" class="asset_id" hidden name="asset_id[]" readonly  value="{{ $machine->AutoIdx }}"></td>
    <td><input type="text" class="mono_pmr" style="font-weight:400; color:black;" value="{{number_format($machine->mono_pmr)}}" required  name="mono_pmr[]" id="mono_pmr"  size="7"></td>    
    <td><input type="text" class="mono_cmr" onkeyup="format(this)" style="font-weight:400; color:black;"  required value="{{number_format($machine->MonCMR)}}" name="mono_cmr[]" id="mono_cmr"  size="7"></td>
    <td><input type="text" class="copies_mono" style="font-weight:600; color:black;"  readonly  name="copies_mono[]" id="copies_mono"  size="6"></td>
    <td><input type="text" class="color_pmr"  style="font-weight:400; color:black;"  required  value="{{number_format($machine->color_pmr)}}"  name="color_pmr[]" id="color_pmr"  size="7"></td>

    
    <td><input type="text" class="color_cmr" onkeyup="format(this)" style="font-weight:400; color:black;" value="{{number_format($machine->ColCMR)}}" required  name="color_cmr[]" id="color_cmr"  size="7"></td>
    <td><input type="text" class="copies_color" style="font-weight:600; color:black;"  readonly  name="copies_color[]" id="copies_color"  size="6"></td>
   
    
       
    <td>{{ $machine->cLocation}}</td>  
    <td><input type="text" id="validation" readonly class="validation" name="validation[]"></td>  
    </tr>    
    @endforeach    
    </tbody>
    
    <div >
    <button type="submit"   id="" style="float: right; margin-bottom:-10px !important;"id="billing_review" class="btn btn-xs mr-auto btn-success billing_review"> Go to Billing Review   <i class="fa fa-chevron-right"></i> </button>
    </div> 
    </table>
   
   
     </form>
    
  </div>

  <script>
  $(document).ready(function(){ 
    var table =$('#table').DataTable({
  
  "bLengthChange": false,
 "bPaginate": false,
  "sScrollY": "320px",
 
 
 "searching": true

  



    
});
    });
</script>
<script>
    
  function format(input) {
  var nStr = input.value + '';  
  nStr = nStr.replace(/\,/g, "");
  x = nStr.split('.');
  x1 = x[0];
  x2 = x.length > 1 ? '.' + x[1] : '';  
  var rgx = /(\d+)(\d{3})/;
  while (rgx.test(x1)) {
  x1 = x1.replace(rgx, '$1' + ',' + '$2');
  }
  input.value = x1 + x2;
  }
    
</script>




{{-- validation on page load  --}}
<script type="text/javascript">
var grandTotalMon = 0;
var grandTotalCol = 0;
  $(document).ready(function() {
  $("input[name='mono_pmr[]']").each(function (index) {
  var mono_cmr = $("input[name='mono_cmr[]']").eq(index).val().replace(/,/g, "");
  var color_cmr = $("input[name='color_cmr[]']").eq(index).val().replace(/,/g, "");
  var mono_pmr = $("input[name='mono_pmr[]']").eq(index).val().replace(/,/g, "");
  var color_pmr = $("input[name='color_pmr[]']").eq(index).val().replace(/,/g, ""); 

 
  var copies_mono =parseInt(mono_cmr)-parseInt(mono_pmr);
  var copies_color =parseInt(color_cmr)-parseInt(color_pmr);
  
  $(".copies_mono").eq(index).val(copies_mono.toLocaleString());
  $(".copies_color").eq(index).val(copies_color.toLocaleString());

  
  grandTotalMon = parseFloat(grandTotalMon) + parseFloat(copies_mono);
  grandTotalCol = parseFloat(grandTotalCol) + parseFloat(copies_color);

  $('#total_mono').html(grandTotalMon.toLocaleString());
  $('#total_color').html(grandTotalCol.toLocaleString());

  if(mono_pmr ==0){
    $(".mono_pmr").eq(index).css('backgroundColor', 'red');
  }

  if(color_pmr ==0 && color_cmr !=0){
    $(".color_pmr").eq(index).css('backgroundColor', 'red');
  }



  
  

  var diff_mono =(mono_cmr-mono_pmr);
  var diff_color =(color_cmr-color_pmr);
  if(diff_mono < 0){       
    $(".mono_cmr").eq(index).css('backgroundColor', 'red');
    $('.validation').eq(index).val('Less Mono CMR Reading!!').css('color','red');
    $("#update_reading").prop('disabled',true);
    $(".billing_review").prop('disabled',true);

    
    
  } 
  if(diff_color < 0){   
    $(".color_cmr").eq(index).css('borderColor', 'red');    
    $('.validation').eq(index).val('Less color CMR Reading!!').css('color','red');
    $("#update_reading").prop('disabled',true);
    $(".billing_review").prop('disabled',true);
  }

 
 
 
  });
  var billing_date = $("#billing_date_check").val();
  const d = new Date(billing_date);
  const date = new Date();
  
    
  
  });
  </script>


  {{-- validating mono_cmr reading  --}}
    <script type="text/javascript">
    var timeout;
    var delay = 1000; 
    $(".color_cmr, .mono_cmr").keyup(function() {
      
      timeout = setTimeout(function(){ 
    $("input[name='mono_pmr[]']").each(function (index){
    var mono_pmr = $(".mono_pmr").eq(index).val().replace(/,/g, "");
    var mono_cmr = $(".mono_cmr").eq(index).val().replace(/,/g, "");
    var color_cmr = $(".color_cmr").eq(index).val().replace(/,/g, "");
    var color_pmr = $(".color_pmr").eq(index).val().replace(/,/g, "");



 
       

    var diff =(mono_cmr-mono_pmr);  
    var diff_c =(color_cmr-color_pmr); 
   
  
    if(diff < 0){   
                 
      $('.validation').eq(index).val('Less Mono CMR than PMR!!').css('color','red');   
      $(".mono_cmr").eq(index).css('backgroundColor', 'red');  
      $("#update_reading").prop('disabled',true); 
      $(".billing_review").prop('disabled',true);
      return false; // breaks        

    }
    if(diff_c < 0){
     $('.validation').eq(index).val('Less Color CMR than PMR!!').css('color','red');   
      $(".color_cmr").eq(index).css('backgroundColor', 'red');  
      $("#update_reading").prop('disabled',true); 
      $(".billing_review").prop('disabled',true);
      return false; // breaks
      

    }
   

  else{
    $("input[name='mono_cmr[]']").eq(index).css('backgroundColor', '');
    $("input[name='color_cmr[]']").eq(index).css('backgroundColor', '');
    
    $('.validation').eq(index).val(''); 
    $("#update_reading").prop('disabled',false);   
    $(".billing_review").prop('disabled',false);        

  }   

    
    
    });
  }, delay);


  });
    </script> 

<script>
 
      
  $('#update_reading').click(function(e) {    
    
    var values = [];


    $("input[name='asset_id[]']").each(function (index) {
      var value = {};

      value['mono_pmr']  = $("input[name='mono_pmr[]']").eq(index).val().replace(/,/g, "");
      value['mono_cmr']  = $("input[name='mono_cmr[]']").eq(index).val().replace(/,/g, "");

      value['color_pmr']  = $("input[name='color_pmr[]']").eq(index).val().replace(/,/g, "");
      value['color_cmr']  = $("input[name='color_cmr[]']").eq(index).val().replace(/,/g, "");

      
   
     value['asset_id']  = $("input[name='asset_id[]']").eq(index).val().replace(/,/g, "");
    
    

    values.push(value);

     });

 
      

var billing_date = $('#billing_date').val();         
 $("#overlay").fadeIn(300); 

  
  $.ajax({ 
  type: "POST", 
  dataType: "json", 
  headers: {
    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
  url: "{{ route('post_reading') }}", 
  data: {'billing_date':billing_date,'values': values},
  
  success: function(data){ 
    alert("Reading Updated Successfully"); 
    
  },
  error: function(xhr, status, error) {
    var msg =JSON.parse(xhr.responseText) 
  alert(msg.error);
  $("#overlay").fadeOut(300);
} 
  }).done(function() {
    setTimeout(function(){
       $("#overlay").fadeOut(300);
     
    },
    
    300);
    
  }); 
  }); 
     
  </script>





<script>
  $(document).ready(function() {  
  $(".color_cmr").keyup(function() {    
  $("input[name='color_cmr[]']").each(function (index) {
  var grandTotalMon = 0;

 var color_cmr = $("input[name='color_cmr[]']").eq(index).val().replace(/,/g, "");
 var color_pmr = $("input[name='color_pmr[]']").eq(index).val().replace(/,/g, ""); 

 var copies_color =parseInt(color_cmr)-parseInt(color_pmr); 

 $(".copies_color").eq(index).val(copies_color.toLocaleString()); 
 grandTotalCol = parseFloat(grandTotalCol) + parseFloat(copies_color); 

 $('#total_color').html(grandTotalCol.toLocaleString());
 
});
});
});
</script>

<script>
$(document).ready(function() {
$(".mono_cmr").keyup(function() {
  var grandTotalMon = 0;

$("input[name='mono_cmr[]']").each(function (index) {
var mono_cmr = $("input[name='mono_cmr[]']").eq(index).val().replace(/,/g, ""); 
var mono_pmr = $("input[name='mono_pmr[]']").eq(index).val().replace(/,/g, "");


var copies_mono =parseInt(mono_cmr)-parseInt(mono_pmr); 
$(".copies_mono").eq(index).val(copies_mono.toLocaleString());

 grandTotalMon = parseInt(grandTotalMon) + parseInt(copies_mono);  
 $('#total_mono').html(grandTotalMon.toLocaleString());

 
});

});
});
</script>

  
@endsection