@extends('MDS.dashboard')

@section('content')
<style>
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
   table .btn{
        margin:0px;
        padding: 0px;
    }
    #overlay{	
        position: fixed;
        top: 0;
        z-index: 10000;
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
    <div id="overlay">
        <div class="cv-spinner">
        <span class="spinner"></span>
        </div>
        </div>
  
<div class="card">
    <div class="card-header">
      Search Contract Rental Charges
    </div>
    <div class="card-body">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text" id="basic-addon1">Search</span>
            </div>
            <input type="text" class="form-control" name="hint_con_rental" id="hint_con_rental"  aria-label="Username" placeholder="Enter Rental CON Code">
          </div>
    </div>
  </div>
</div>
<div class="container-fluid">
<table id="table" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
     <th scope="col" style="background-color:#00BFFF">Customer Name</th>
     <th scope="col"style="background-color:#00BFFF">Type</th>
     <th scope="col"style="background-color:#00BFFF">Description</th>
     <th scope="col"style="background-color:#00BFFF">Code</th>
     
     <th scope="col"style="background-color:#00BFFF">Amount</th>
     <th scope="col"style="background-color:#00BFFF">Action</th>
    
    
    </thead>
    <tbody id="table1">
    
    </tbody>
    </table>  
  </div>


 

  <script> 
    $(document).ready(function(){ 
   $('#hint_con_rental').on('keyup',function() {    
     $('#table').dataTable().fnDestroy();
     var hint = $('#hint_con_rental').val(); 
   
     if (hint.length >= 4 ){        
     
      
       $.ajax({ 
       type: "POST", 
       dataType: "json", 
       url: '/rental_cont_code', 
       data: {'hint': hint,"_token":"{{ csrf_token() }}"},
       
       success: function(data){ 
 
        $('#table').dataTable().fnDestroy();
       $("tbody#table1").html("");
        
        $.each(data, function(index, value){            

    //   var url ="<button class='btn btn-xs btn-primary' onClick='ShowModal(this)' data-id="+value.Idx+"><i class='fa fa-pencil-square-o'></i></button>";
      var url ='<a href="#" id="save" data-id=' +value.Idx+' class="btn btn-primary">Update</a>';

           
      var html = ("<tr><td>"+value.Name+"</td><td>RENTAL</td><td>"+value.desc+ "</td><td>" +value.code+ "</td><td>"+'<input type="number"  value='+Math.round(value.fAmount)+' id="famout" class="famout" name="famount">'+"</td><td>" +url+"</td></tr>");
        
     $('tbody#table1').append(html);   
      
   
     });    
       $('#table').DataTable({
         "bLengthChange": false,
         "bPaginate": false,
         "sScrollY": "330px", 
         stateSave: true,
     
         
        "searching": true,
     
         
       });
     
       
       } 
       }).done(function() {
         setTimeout(function(){
         $("#overlay").fadeOut(300);
         },500);
       });
    } 
       }); 
       });        
       </script> 










{{-- update rental charges --}}
<script>
    $(document).on('click', '#save', function(e){
    e.preventDefault()
    var data_id =$(this).attr('data-id');
    var amount = $("input[name='famount']").val(); 
    $("#overlay").fadeIn(300);   
    
    $.ajax({ 
    type: "POST", 
    dataType: "json", 
    url: '/update_rental', 
    data: {'data_id': data_id,'amount':amount,"_token":"{{ csrf_token() }}"}, 
    success: function(data){ 
      alert("Rental Charge Updated Successfully"); 
      
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








@endsection