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
#table2 input[type=number] {
  font-size: 13px !important;
  
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
      Search Contract
    </div>
    <div class="card-body">
        <div class="input-group mb-3">
            <div class="input-group-prepend">
              <span class="input-group-text" id="basic-addon1">Search</span>
            </div>
            <input type="text" class="form-control" name="hint_con" id="hint_con"  aria-label="Username" placeholder="Enter CON Code">
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
     <th scope="col"style="background-color:#00BFFF">Code</th>
     <th scope="col"style="background-color:#00BFFF">Description</th>
     <th scope="col"style="background-color:#00BFFF">Action</th>
    
    
    </thead>
    <tbody id="table1">
    
    </tbody>
    </table>  
  </div>


  <div class="modal fade" id="slabsmodal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
    <div class="modal-header ">
      
      
      <button type="button" class="btn btn-sm ml-auto btn-secondary" data-dismiss="modal">X</button>
    </div>
    
   
    <form action="" method="POST" id="search">
    {{ csrf_field() }}
    <div class="modal-body">
   
   
    <table id="table1" class="table table-hover table-bordered table-sm text-center">
    <thead style="background-color:#00BFFF;">
    <tr>
      <th  scope="col">ID</th>
      <th  scope="col">FromQty</th>
      <th  scope="col">ToQty</th>
      <th  scope="col">Rate</th>
     
      
    </tr>
    </thead>
    <tbody id="table2">
    </tbody>
    </table>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-sm mr-auto btn-secondary" data-dismiss="modal">Close</button>
    <button type="submit" id="update_rates" class="btn btn-sm float-right btn-primary" >Update</button>
    </div>
    </form>
    </div>
    </div>
    </div>


  <script> 
    $(document).ready(function(){ 
   $('#hint_con').on('keyup',function() {    
     $('#table').dataTable().fnDestroy();
     var hint = $('#hint_con').val(); 

      $("tbody#table1").html("");
   
     if (hint.length >= 4 ){     
      
       $.ajax({ 
       type: "POST", 
       dataType: "json", 
       url: '/cont_code', 
       data: {'hint': hint,"_token":"{{ csrf_token() }}"},
       
       success: function(data){ 
 
        $('#table').dataTable().fnDestroy();
       $("tbody#table1").html("");
        
        $.each(data, function(index, value){            

      var url ="<button class='btn btn-xs btn-primary' onClick='ShowModal(this)' data-id="+value.AIdx+"><i class='fa fa-pencil-square-o'></i></button>";
           
      var html = ("<tr><td>"+value.Name+"</td><td>Counter</td><td>"+value.ctype+ "</td><td>" +value.desc+ "</td><td>" +url+"</td></tr>");
        
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


<script>


function ShowModal(elem){
var dataId = $(elem).data("id");
$('#slabsmodal').modal('show');
   
    $.ajax({
    type: "GET",
     url: "{{route('slabs_rate')}}",
     data: {'dataId':dataId,"_token":"{{ csrf_token() }}"}, 
  
   
success: function (data) {
  $("tbody#table2").html("");
  $.each(data, function(index, value){

           
    var html = ("<tr><td>"+'<input type="number" readonly value='+ value.AutoIdx +' id="id" class="qty" name="id[]">'+" </td><td>"+'<input type="number" value='+ value.iFromQty +' id="fromqty" class="fromqty" name="fromqty[]">'+"</td><td>"+'<input type="number" value='+ value.iToqty +' id="toqty" class="toqty" name="toqty[]">'+"</td><td>"+'<input type="number" value='+value.frate+' id="rate" class="rate" name="rate[]">'+"  </td></tr>");

    $('tbody#table2').append(html);

    });
    }

    
    });



}       

        
</script>




<script>
$('#update_rates').click(function(e) { 
    e.preventDefault();    
    
    var values = [];

    $("input[name='id[]']").each(function (index) {
      var value = {};

      value['id']  = $("input[name='id[]']").eq(index).val().replace(/,/g, "");
      value['rate']  = $("input[name='rate[]']").eq(index).val().replace(/,/g, ""); 
      
      value['fromqty']  = $("input[name='fromqty[]']").eq(index).val().replace(/,/g, ""); 
      value['toqty']  = $("input[name='toqty[]']").eq(index).val().replace(/,/g, ""); 

      values.push(value);

     });
      
        
    $("#overlay").fadeIn(300);   
    $.ajax({ 
    type: "POST", 
    dataType: "json", 
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
    url: "{{ route('update_rates') }}", 
    data: {'values': values},
    
    success: function(data){ 
      alert("Rates Updated Successfully"); 
      
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