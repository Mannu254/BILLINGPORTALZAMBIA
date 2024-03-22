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
    <div class="input-group  col-md-3">
    <div class="input-group-prepend">
    <span class="input-group-text" id="basic-addon1">Export MDS Assets</span>
    </div>
    <input type="text" required id="con_asset" name="con_asset" class="form-control" placeholder="Enter CON Code" aria-label="Username" aria-describedby="basic-addon1">
    </div>
    <button type="submit" id="export" name="export" class="btn btn-success">Export</button>

  </div>
    <div class="position-fixed container-fluid" style="margin-top: -30px; margin-left:700px;">
     <form action="{{action('BillingManagerController@mds_update_asset')}}" method="POST" enctype="multipart/form-data">
      {{ csrf_field() }}
    <div class="input-group col-md-5" >
      <div class="input-group-prepend">
      <span class="input-group-text" id="basic-addon1">Update MDS Assets</span>
      </div>
      <input type="file" required id="file" name="file" required class="form-control" placeholder="Enter CON Code" aria-label="Username" aria-describedby="basic-addon1">
      <button type="submit" id="" name="" class="btn btn-success">Update</button>
    </div>
      
    </form>
  </div>
   
    
    </div>
    </div>
    
<div class="container-fluid position-fixed">
  <div id="overlay">
    <div class="cv-spinner">
      <span class="spinner"></span>
    </div>
    </div>
    
<div class="card ">
    
    <div class="card-header text-center">
        UPDATE CONTRACT
    </div>
    <div class="card-body">
    <div class="input-group input-group-sm col-md-6">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Contract Code Hint</span>
    </div>
    <input type="text" class="form-control" aria-label="Small" name="contract_hint" id="contract_hint" placeholder="Enter Atleast 4 characters" aria-describedby="inputGroup-sizing-sm">
    </div>
    <hr>
          
    <div class="form-row">
    <div class="input-group input-group col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm" >Code</span>
    </div>
    <input type="text"  class="form-control" readonly name="code" id="code" aria-describedby="inputGroup-sizing-sm">
    </div>
    

    <div class="input-group mb-4  col-md-6">
      <div class="input-group-prepend">
        <label class="input-group-text" id="inputGroup-sizing-sm" for="inputGroupSelect01">Contract Template</label>
      </div>
      <select id="template" name="templatec" class="form-control form-control">
        <option type="text" class="form-control input-group " hidden=""   disabled="disabled" selected="selected" value=""><b>Select Template</b></option>
        @foreach ($templates as $tmp)
        <option value="{{$tmp->AutoIdx}}">{{$tmp->cCode}}</option>
        @endforeach  
        </select>
    </div>
    {{-- <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Contract Template</span>
    </div>
    <input type="text" class="form-control" aria-label="Small" readonly name="template" id="template" aria-describedby="inputGroup-sizing-sm">
    <input type="text" hidden name="template_id" id="template_id">
    </div> --}}
    </div>
    {{-- <input type="text" hidden name="template_id" id="template_id"> --}}

    <div class="form-row">
    <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Description</span>
    </div>
    <input type="text" class="form-control" readonly id="description" name="description" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
    </div>
    <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Customer</span>
    </div>
    <input type="text" class="form-control" id="cust_name" readonly name="cust_name" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
    </div>
    </div>

    <div class="form-row" >
    <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Creation Date</span>
    </div>
    <input type="" class="form-control cdate" style="color:black" readonly disabled name="cdate" id="datepicker" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
    </div>
    <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Review Date</span>
    </div>
    <input type="" class="form-control rdate" style="color:black" disabled readonly aria-label="Small" id="datepicker1" aria-describedby="inputGroup-sizing-sm">
    </div>
    </div>


    <div class="form-row">
    <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">Start Date</span>
    </div>
    <input type="" class="form-control sdate" style="color:black" aria-label="Small" id="datepicker2" name="sdate" aria-describedby="inputGroup-sizing-sm">
    </div>
    <div class="input-group input-group-sm col-md-6 mb-4">
    <div class="input-group-prepend">
    <span class="input-group-text" id="inputGroup-sizing-sm">End Date</span>
    </div>
    <input type="" class="form-control edate" style="color:black" disabled readonly aria-label="Small" id="datepicker3" aria-describedby="inputGroup-sizing-sm">
    </div>
    </div>
    <div class="form-row">
    <div class="form-group input-group-sm col-md-6 mb-4">
    <label for="exampleFormControlTextarea1" id="inputGroup-sizing-sm">Contract Details</label>
    <textarea class="form-control"  id="cContractDetails" readonly name="cContractDetails" rows="1"></textarea>
    </div>
    <div class="form-group input-group-sm col-md-6 mb-4">
    <label for="exampleFormControlTextarea1" id="inputGroup-sizing-sm">Alerts</label>
    <textarea class="form-control" id="cAlerts" readonly name="cAlerts" id="exampleFormControlTextarea1" rows="1"></textarea>
    </div>
    </div>
  
    <button name="update" id="update" class="btn btn-primary btn-sm float-right">Update</button>
  </div>
  
</div>




<script>
 
    $(function() {      
      
  $('#contract_hint').on('keyup change',function() { 
    
    var contract_hint = $('#contract_hint').val(); 
    
    $('#template').val('');
    $('#template_id').val('');
    $('#description').val('');
    $('#cust_name').val('');
    $('.cdate').val('');
    $('.rdate').val('');
    $('.sdate').val('');
    $('.edate').val('');
    $('#cAlerts').val('');
    $("#cContractDetails").text('');   
  
    if(contract_hint.length >=4){
      $.ajax({ 
      type: "POST", 
      dataType: "json", 
      url: '/mds_contract_search', 
      data: {'contract_hint': contract_hint,"_token":"{{ csrf_token() }}"},
      
      success: function(data){


        var cdate  = new Date(data.dCreationDate).toLocaleDateString('en-GB');
        var dRdate  = new Date(data.dReviewDate).toLocaleDateString('en-GB');
        var sdate  = new Date(data.dStartDate).toLocaleDateString('en-GB');
        var edate  = new Date(data.dEndDate).toLocaleDateString('en-GB');

        

        
        
        
        $('#code').val(data.cCode);
        $('#template').val(data.template.cCode);
        $('#template_id').val(data.template.AutoIdx);

        $('#description').val(data.cDescription);
        $('#cust_name').val(data.Account + "-" + data.Name);
        $('.cdate').val(cdate);
        $('.rdate').val(dRdate);
        $('.sdate').val(sdate);
        $('.edate').val(edate);
        $('#cAlerts').val(data.cAlerts);
        $("#cContractDetails").text(data.cContractDetails);  
        
        $("select").val(data.template.AutoIdx);
     
  
      }   
      
      
      });
    }
      }); 
      });        
      </script> 

<script>
    $(document).ready(function(){ 
    $("#datepicker2,#datepicker,#datepicker1,#datepicker3").datepicker({

    changeMonth:true,
    changeYear:true,
    showOn: "button",
    buttonImage: "http://jqueryui.com/resources/demos/datepicker/images/calendar.gif",
    buttonImageOnly: true,
    format: 'dd/mm/yyyy',
    defaultDate: new Date(),
    constrainInput: true,
    
})

});

$( ".cdate" ).datepicker( "option", "readonly", true );
 $('.cdate,.rdate,.edate').datepicker({}).next('button').attr('disabled', 'true')



</script>



<script>
 
    $(function() {         
  $('.sdate,#template').on('change keyup',function() { 
        var sdate = $('.sdate').val();
     
    var template_id = $('#template').find(":selected").val();
      
    
      $.ajax({ 
      type: "POST", 
      dataType: "json", 
      url: '/mds_contract_date_change', 
      data: {'sdate': sdate,'template_id':template_id,"_token":"{{ csrf_token() }}"},
      
      success: function(data){         
        var dRdate  = new Date(data.new_rev_date);
        var edate  = new Date(data.new_end_date);           
      
        
        $('.rdate').val(dRdate.toLocaleDateString('en-GB'));        
        $('.edate').val(edate.toLocaleDateString('en-GB'));    
  
      }        
      });
      }); 
      });        
      </script> 

      <script>
        $('#update').on('click',function() { 
          $("#overlay").fadeIn(300);　  
          var timeout;
          var delay = 2000; 
          timeout = setTimeout(function(){
          var sdate = $('.sdate').val(); 
          var edate = $('.edate').val(); 
          var rdate = $('.rdate').val(); 
          var code = $('#code').val();
          var template_id = $('#template').find(":selected").val();
          if(sdate =='' || edate=='' || rdate =='' || code=='' || template_id==''){
            alert('You have some Missing Data, Kindly Check')
           

          }
          
           
          $.ajax({ 
      type: "POST", 
      dataType: "json", 
      url: '/mds_contract_update_date', 
      data: {'sdate': sdate,'edate':edate,'rdate':rdate,'code':code,'template_id':template_id,"_token":"{{ csrf_token() }}"},
      
      success: function(data){   
        alert("Contract Updated Successfully"); 
      
    },
    error: function(xhr, status, error) {
      var msg =JSON.parse(xhr.responseText) 
    alert('Error in Contract Update, Contract Administrator');
    $("#overlay").fadeOut(300);
  }    
       
          
      }     
      
      ).done(function() {
      setTimeout(function(){
        $("#overlay").fadeOut(300);
      },
      300);
      });
    }, delay);
      
      

     }); 

      </script>




<script> 
  $(document).ready(function(){ 
 $('#export').click(function() {    
   
   var con_asset = $('#con_asset').val(); 
   if (con_asset.length == 0 ) { 
     alert('You have not Searched any Code to Export!!!')
     exit();
   }

 
   
     $("#overlay").fadeIn(300);　
     $.ajax({
   type: "POST",
    url: "{{route('mds_asset_export')}}",
    data: {'con_asset':con_asset,"_token":"{{ csrf_token() }}"}, 
   cache: false,
   xhrFields:{
           responseType: 'blob'
   },
  
success: function (response) {
   var link = document.createElement('a');
       link.href = window.URL.createObjectURL(response);
       link.download = `MDSCustomerAssets.xlsx`;
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
@endsection