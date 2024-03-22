@extends('Reports.dashboard')
@section('content')

<style>
    .btn{
        margin: 0;
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
<div class="container">
    {{-- <div id="overlay">
        <div class="cv-spinner">
        <span class="spinner"></span>
        </div>
        </div> --}}
<div class="card">
<div class="card-header">
 MDS SALES REPORT 
</div>
<div class="card-body">
    <div class="row">


    <div class="input-group mb-0 col-md-4">
    <input type="" class="form-control form-control start_date"  id="datepicker" placeholder="Start Date"  placeholder="Start Date" required name="start_date">
    </div>
    <div class="input-group mb-0 col-md-4">
    <input type="" class="form-control form-control end_date"  id="datepicker2" placeholder="End Date"  placeholder="End Date" required name="end_date">
    </div>

    <div class="input-group mb col-md-4">
    <button type="button" id="sales_rpt" class="btn btn-primary btn btn-block twoToneButton"> <i class="fa fa-lg fa-refresh"></i> Export</button>
    </div>  




    </div>
</div>
</div>
</div>

<script>
$(document).ready(function(){ 
$("#datepicker, #datepicker2").datepicker({

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
   $('#sales_rpt').click(function() {    
     var start_date = $('.start_date').val(); 
     var end_date = $('.end_date').val(); 
     if (start_date.length == 0 ) { 
       alert('Kindly Select Start Date!!!')
       exit();
     }
     if (end_date.length == 0 ) { 
       alert('Kindly Select End Date!!!')
       exit();
     }
   
     
       $("#overlay").fadeIn(300);　
       $.ajax({
     type: "POST",
      url: "{{route('sales_rpt')}}",
      data: {'start_date':start_date,'end_date':end_date,"_token":"{{ csrf_token() }}"}, 
     cache: false,
     xhrFields:{
             responseType: 'blob'
     },
    
 success: function (response) {
     var link = document.createElement('a');
         link.href = window.URL.createObjectURL(response);
         link.download = `MDS_SalesReport.xlsx`;
         link.click();
         window.location.reload();
     },
     error: function (request, status, error) {
         alert('Error!! in Generating Sales Report Contact Administrator');
         
     }
     
     }).done(function() {   
     window.location.reload(); 
     setTimeout(function(){$("#overlay").hide();},400);  
      
   }).fail(function()  {
     setTimeout(function(){$("#overlay").hide();},400);   
 });
       }); 
       });        
       </script> 




@endsection