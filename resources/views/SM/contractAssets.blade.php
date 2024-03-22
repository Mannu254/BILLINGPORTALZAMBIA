@extends('SM.dashboard')

@section('content')
<style>
     span{
        color: black;
        font-weight: 600;
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
.btn{
  margin:0px;
}
.hide{
  display:none;
}
#basic-addon1{
  font-weight:bold;
  color: black;
}
#inputGroup-sizing-sm{
  font-family:Arial, Helvetica, sans-serif;
  font-size: 12px;
  color: black;

}
input[type=text] {
 color: black; 
  
}

</style>
<div id="overlay">
<div class="cv-spinner">
<span class="spinner"></span>
</div>
</div>
<br><br><br>


<div class="container-fluid">    
<div class="card">
    <div class="card-header">
      Assets Reports
    </div>
    <div class="card-body">
   
        <div class="form-row">
            <div class="input-group mb-4 col-md-8">
            <div class="input-group-prepend">
            <span class="input-group-text" id="basic-addon1">Service Assets</span>
            </div>    
            <button type="submit" id="export_all" name="export" class="btn btn-success btn-sm">Export All Assets DSBase</button>
        
          </div>
              <div class="input-group mb-4 col-md-4">
            <div class="input-group-prepend">
            <span class="input-group-text" id="basic-addon1">Service Assets</span>
            </div>    
            <button type="submit" id="inContract" name="inContract" class="btn btn-success btn-sm">Export Assets In Contract Only</button>
        
          </div>    

    </div>
    <br>
    <hr>
    <br>
    <div class="form-row">
      <div class="input-group  col-md-4 mr-auto">
      <div class="input-group-prepend">
      <span class="input-group-text" id="basic-addon1">Contracts Renewed In Month of</span>
      </div>
      <input type="text" required placeholder="Select Month and Year" id="datepicker" name="date_month" class="form-control date_month">
      <button type="submit" id="export_monthly" name="export_monthly" class="btn btn-success btn-sm">Export</button>
      </div>
      
        <div class="input-group col-md-4">
      <div class="input-group-prepend">
      <span class="input-group-text" id="basic-addon1">Service Assets</span>
      </div>    
      <button type="submit" id="NotInContract" name="NotInContract" class="btn btn-success btn-sm">Export Assets Not in Contract</button>
  
    </div>



</div>
 
    


    
  </div>
</div>
</div>



<script> 
    $(document).ready(function(){ 
   $('#export_all').click(function() {     
     
       $("#overlay").fadeIn(300);　
       $.ajax({
     type: "POST",
      url: "{{route('asset_export_all')}}",
      data: {"_token":"{{ csrf_token() }}"}, 
     cache: false,
     xhrFields:{
             responseType: 'blob'
     },
    
  success: function (response) {
     var link = document.createElement('a');
         link.href = window.URL.createObjectURL(response);
         link.download = `DSAssetBase.xlsx`;
         link.click();
         window.location.reload();
     },
     error: function (request, status, error) {
         alert('Error!! In Exporting Assets Contact Administrator');
         
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
  $(document).ready(function(){ 
 $('#inContract').click(function() {    
   
 
 
   
     $("#overlay").fadeIn(300);　
     $.ajax({
   type: "POST",
    url: "{{route('asset_inContract')}}",
    data: {"_token":"{{ csrf_token() }}"}, 
   cache: false,
   xhrFields:{
           responseType: 'blob'
   },
  
success: function (response) {
   var link = document.createElement('a');
       link.href = window.URL.createObjectURL(response);
       link.download = `AssetsInContract.xlsx`;
       link.click();
       window.location.reload();
   },
   error: function (request, status, error) {
       alert('Error!! In Exporting Assets Contact Administrator');
       
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



{{-- renewed in a month --}}
<script> 
  $(document).ready(function(){ 
 $('#export_monthly').click(function() {    
  var date = $('.date_month').val(); 

  
  if (date.length == 0 ) { 
     alert('kindly select Month and Date!!')
     exit();
   } 
 
    
     $("#overlay").fadeIn(300);　
     $.ajax({
   type: "POST",
    url: "{{route('contract_renew_monthly')}}",
    data: {"date":date,"_token":"{{ csrf_token() }}"}, 
   cache: false,
   xhrFields:{
           responseType: 'blob'
   },
  
success: function (response) {
   var link = document.createElement('a');
       link.href = window.URL.createObjectURL(response);
       link.download = `ContractsRenewed.xlsx`;
       link.click();
       window.location.reload();
   },
   error: function (request, status, error) {
       alert('Error!! In Exporting Assets Contact Administrator');
       
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



{{-- not in contract  --}}
<script> 
  $(document).ready(function(){ 
 $('#NotInContract').click(function() {   
 
   
     $("#overlay").fadeIn(300);　
     $.ajax({
   type: "POST",
    url: "{{route('NotIncontract')}}",
    data: {"_token":"{{ csrf_token() }}"}, 
   cache: false,
   xhrFields:{
           responseType: 'blob'
   },
  
success: function (response) {
   var link = document.createElement('a');
       link.href = window.URL.createObjectURL(response);
       link.download = `AssetsNotInContract.xlsx`;
       link.click();
       window.location.reload();
   },
   error: function (request, status, error) {
       alert('Error!! In Exporting Assets Contact Administrator');
       
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
      $("#datepicker").datepicker( {
    format: "mm-yyyy",
    startView: "months", 
    minViewMode: "months"
});
  </script>



@endsection