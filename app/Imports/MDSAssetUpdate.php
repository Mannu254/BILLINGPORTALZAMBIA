<?php

namespace App\Imports;


use Sessions;
use Carbon\Carbon;
use App\ServiceAsset;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;



class MDSAssetUpdate implements ToCollection,WithStartRow,WithValidation,SkipsEmptyRows,WithCalculatedFormulas
{
    use Importable;
  
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    
    */
    
    public function __construct() {
        

         }

         
    public function collection(Collection  $rows)
    {
        foreach ($rows as $row)

        
    
         
        
                
        {  
            if(!empty($row[0]) && empty($row[1])){ 
                
            $update =DB::table('_smtblServiceAsset')
            ->where('ucSASerialNo',$row[0])
            ->orwhere('cSerialNo',$row[0])
          ->update([
                 'ucSABillingAsset' =>'',
                 'iContractMatrixId'=>'0'
             ]); 
             
         
            
        
        } 
        elseif(!empty($row[0]) && !empty($row[1])){

            $contract_id =DB::table('_smtblContractMatrix')
            ->where('cCode',$row[1])
            ->select('AutoIdx')
            ->first();

            if(is_null($contract_id)){
                Session::flash('error', 'You have Invalid Con Code in your Excel Upload!'); 
            }
            else{           

            
            $update =DB::table('_smtblServiceAsset')
            ->where('ucSASerialNo',$row[0])
            ->orwhere('cSerialNo',$row[0])
          ->update([
                 'ucSABillingAsset' =>$row[1],
                 'iContractMatrixId'=>$contract_id->AutoIdx,
                 'ucSASerialNo'=>$row[0]
             ]); 

            }
            
            


        }  
    } 
       
        
    
      
    
}
    public function startRow(): int
        {
            return 2;
        }
    public function rules(): array
    {
        return [
            // Above is alias for as it always validates in batches
            '*.0' => 'required',            
            
       
                 
        ];
    }
    public function customValidationMessages()
    {
        return [
            
            '*.0.required'    => 'Serial No is required',
           

           
        ];
    }
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {    
        
     
        
        
        
}

   

}
