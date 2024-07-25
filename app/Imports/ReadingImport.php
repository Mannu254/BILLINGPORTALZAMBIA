<?php

namespace App\Imports;
use Session;


use Sessions;
use Carbon\Carbon;
use App\ServiceAsset;
use App\Monthlyreading;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
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



class ReadingImport implements ToCollection,WithStartRow,WithValidation,SkipsEmptyRows,WithCalculatedFormulas
{
    use Importable;
  
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    
    */
    
    public function __construct($reading_date) {
        $this->reading_date = $reading_date;

        

        
          }
    
    public function collection(Collection  $rows)
    {
        foreach ($rows as $row)
         
        
                
        {  
             if(!empty($row[9]!=0)){                
              
            $today_current = Carbon::parse($this->reading_date);
           
            
            Monthlyreading::UpdateOrCreate([
             'serial_no'          => "$row[1]",            
            // 'created_at' => Monthlyreading::whereYear('created_at', $today_current->year)->first()->created_at ?? null,
            // 'created_at' => Monthlyreading::whereMonth('created_at', $today_current->month)->first()->created_at ?? null
            'created_at' => Monthlyreading::whereYear('created_at', $today_current->year)->first()->created_at ?? $today_current,
            'created_at' => Monthlyreading::whereMonth('created_at', $today_current->month)->first()->created_at ?? $today_current
           
            ],[
            'customer_name'      => $row[0],
             'serial_no'         => trim($row[1]), 
            'asset_code'         => $row[2],             
            'description'        => $row[3], 
            'billing_cycle'      => $row[4],
            'branch'             => $row[5], 
            'physical_area'      => $row[6], 
            'reading_date'       => $this->reading_date,
            'mode_collection'    => $row[8],             
            'mono_cmr'           => $row[9] ?? 0,
            'color_cmr'          => $row[10] ?? 0,
            'copies_mono'        =>0,
            'copies_col'         =>0,
            'a3mono_cmr'         => 0, 
            'a3color_cmr'        => 0,  
            'scan_cmr'           => 0,  
            'remarks'            => '',  
            'user_id'           => Auth::user()->id,
           
            ]);
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
            '*.1' => 'required',
        //   '*.1' => Rule::exists(ServiceAsset::class, 'ucSASerialNo'),                   
                      
             '*.9' => 'numeric|nullable',
             '*.10' => 'numeric|nullable',
            
            
                 
        ];
    }
    public function customValidationMessages()
    {
        return [
            '*.0.required'    => 'Customer Name is Mandantory!!',
            '*.1.required'    => 'Serial No is required',
            //   '*.1.*'          => 'Invalid Serial No ',
                         
            // '*.9.numeric'    => 'Mono CMR is required', 
            // '*.9.numeric'    => 'Invalid Mono Value', 
            // '*.10.numeric'    => 'Invalid Color Value',            
            
            
            // '*.13.numeric'    => 'Invalid A3Mono Value',
            // '*.14.numeric'    => 'Invalid A3Color Value', 
            // '*.15.numeric'    => 'Invalid Scan Value',  

           
        ];
    }
    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures)
    {    
        //  dd($failures);
        // $data = [];
        // foreach ($failures as $failure) {
        //     $failure->row(); // row that went wrong
        //     $failure->attribute(); // either heading key (if using heading row concern) or column index
        //     $failure->errors(); // Actual error messages from Laravel validator
        //     $failure->values(); 

        //    $data[] =[$failure->values()[1]];    

           
            

            // The values of the row that has failed.                  
    
    
           
            
        // }
        // $invalid_serial =(json_encode($data, JSON_PRETTY_PRINT));
        

        // Session::flash('error','You have Invalid Serial Numbers, Not Uploaded '.$invalid_serial); 
            
        // return redirect()->back()->with('data', $data); 
      
       
     
        
        
        
}

   

}
