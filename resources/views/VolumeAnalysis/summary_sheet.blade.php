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
h6{
    margin-left: 5px;
}



</style>

<br>

<h6>Summary Sheet Report</h6>
<hr>
<div class="container-fluid">
    <div id="overlay">
        <div class="cv-spinner">
        <span class="spinner"></span>
        </div>
        </div>

    <div class="row">
        <div class="form-group mb-5 col-md-6">
        <select class="form-control form-control-xs selectpicker"   id="customer_id" name="customer_id" data-live-search="true" data-title="Select Customer" id="state_list">
        @foreach ($clients as $customer)
        <option value="{{$customer->DCLink}}">{{$customer->Account}}-{{$customer->Name}}</option>
        @endforeach 
        </select>
        </div>        
        

        
            <div class="input-group mb-5 col-md-2">
                <input type="text" required placeholder="Select Month and Year" id="datepicker" name="date_month" class="form-control date_month">
                </div>
        
        <div class="input-group mb col-md-2">
        <button type="button" id="summary_sheet" class="btn btn-primary btn btn-block twoToneButton"> <i class="fa fa-lg fa-refresh"></i> Export</button>
        </div>  
           

   
   
    </div>
    
  </div>
  


  <script>
$("#datepicker").datepicker( {
    changeMonth:true,
    changeYear:true,
    showOn: "button",
    buttonImage: "http://jqueryui.com/resources/demos/datepicker/images/calendar.gif",
    buttonImageOnly: true,
    defaultDate: new Date()
});
</script>

<script type="application/javascript">
    $('input[type="file"]').change(function(e){
    var fileName = e.target.files[0].name;
    $('.custom-file-label').html(fileName);
    });
</script>








<script> 
   $(document).ready(function(){ 
  $('#summary_sheet').click(function() {    
    var customer_id = $('#customer_id').val(); 
    var date_month = $('.date_month').val(); 
    
    if (date_month.length == 0 ) { 
      alert('Kindly Select  Date!!!')
      exit();
    }  
  
    if (customer_id.length == 0 ) { 
      alert('Kindly Select At least One Customers to Export !!!')
      exit();
    }
      $("#overlay").fadeIn(300);　
      $.ajax({
    type: "POST",
     url: "{{route('summary')}}",
     data: {'customer_id':customer_id,'date_month':date_month,"_token":"{{ csrf_token() }}"}, 
    cache: false,
    xhrFields:{
            responseType: 'blob'
    },
   
success: function (response) {
    var link = document.createElement('a');
        link.href = window.URL.createObjectURL(response);
        link.download = `SummarySheet.xlsx`;
        link.click();
        window.location.reload();
    },
    error: function (request, status, error) {
        alert('Error!! in Summary Sheet Export Contact Administrator');
        
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




  
  
    
    <script>
      $('.clearAll').on('click', function() {
      $("#customer_id").multiselect("clearSelection");
      });
    </script>
  
  

@endSection