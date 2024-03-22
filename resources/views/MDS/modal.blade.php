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
      <th  scope="col">Cust Name</th>
      <th  scope="col">Curr</th>
      <th  scope="col">Order Num</th>
      <th  scope="col">Order Date </th>
      <th  scope="col">Exclusive Amt</th>
      <th  scope="col">Tax Amt</th>
      <th  scope="col">Inclusive Amt</th>
    </tr>
    </thead>
    <tbody id="table">
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