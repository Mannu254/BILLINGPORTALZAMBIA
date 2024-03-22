@extends('MDS.dashboard')

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


  <div class="card">
    <div class="card-body">
      
       
    <div class="form-row">
    <div class="input-group  col-md-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="basic-addon1">Export Current Contract Assets Rates</span>
    </div> 
    <button type="submit" id="export" name="export" class="btn btn-success">Export</button>   
    </div>

    <div class="input-group  col-md-4">
      <div class="input-group-prepend">
      <span class="input-group-text" id="basic-addon1">Export Escalation  Rates</span>
      </div> 
      <button type="submit" id="export_escalation" name="export_escalation" class="btn btn-success">Export</button>   
      </div>
     </div>   
    </div>
    </div>
    

  <div id="overlay">
    <div class="cv-spinner">
      <span class="spinner"></span>
    </div>
    </div>
    












<script> 
  $(document).ready(function(){ 
 $('#export').click(function() {    
   
   
   
     $("#overlay").fadeIn(300);　
     $.ajax({
   type: "POST",
    url: "{{route('export_rates')}}",
    data: {'con_asset':con_asset,"_token":"{{ csrf_token() }}"}, 
   cache: false,
   xhrFields:{
           responseType: 'blob'
   },
  
success: function (response) {
   var link = document.createElement('a');
       link.href = window.URL.createObjectURL(response);
       link.download = `MDSContractRates.xlsx`;
       link.click();
       window.location.reload();
   },
   error: function (request, status, error) {
       alert('Error!! In Exporting Contract Rates Contact Administrator');
       
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
 $('#export_escalation').click(function() {    
   
   
   
     $("#overlay").fadeIn(300);　
     $.ajax({
   type: "POST",
    url: "{{route('export_rates_ecalation')}}",
    data: {"_token":"{{ csrf_token() }}"}, 
   cache: false,
   xhrFields:{
           responseType: 'blob'
   },
  
success: function (response) {
   var link = document.createElement('a');
       link.href = window.URL.createObjectURL(response);
       link.download = `MDSContractRatesEcalation.xlsx`;
       link.click();
       window.location.reload();
   },
   error: function (request, status, error) {
       alert('Error!! In Exporting Contract Rates Contact Administrator');
       
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