<style>
    th{
       font-weight: bolder;
   }
   </style>
   
   <div class="container-flow">
   <table id="table" class="table table-hover table-bordered table-sm text-center">
   <thead>
   <tr>
   <th  scope="col">Asset Code</th>
   <th  scope="col">Description</th>
   <th  scope="col">Serial Number</th>
   <th  scope="col">Billing Date</th>
   <th  scope="col"> Mono PMR </th>
   <th  scope="col"> Mono CMR</th>
   <th  scope="col"> Color PMR </th>
   <th scope="col">Color CMR</th>
   <th scope="col">Scn PMR</th>
   <th scope="col" >Scn CMR</th>
      
   </tr>
   </thead>
   @foreach ($clients_machines as $cl)
   <tr>
   <td>{{ $cl->cCode }}</td>
   <td>{{ $cl->cDescription}}</td>
   <td>{{ $cl->ucSASerialNo }}</td>
   <td>{{ $cl->billing_date }}</td>
   <td>{{ $cl->mono_pmr }}</td>
   <td></td>
   <td>{{ $cl->color_pmr }}</td>
   <td></td>
   <td>{{ $cl->scan_pmr}}</td>
   <td></td>      
   </tr>
   @endforeach
   </table>
   </div>
   
   