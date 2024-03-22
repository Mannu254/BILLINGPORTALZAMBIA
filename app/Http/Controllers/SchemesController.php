<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\_cplcalcbase;
use App\_cplcostmaster;
use App\_cplScheme;

class SchemesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        
        $schemes =_cplScheme::
        select('_cpl_schemes.*','_cplcostmasters.cost_name','_cplcalcbases.calc_base_name')
        ->join('_cplcostmasters','_cplcostmasters.id','=','_cpl_schemes.cost_code')
        ->join('_cplcalcbases','_cplcalcbases.id','=','_cpl_schemes.calcbase')
        ->get();

        
        $cost_types =_cplcostmaster::all();
        $cal_bases =_cplcalcbase::all();

        // dd($schemes);
        

        return view('landedcost.scheme',compact('cal_bases','cost_types','schemes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            
            'scheme_name' => 'required|regex:/^[a-zA-Z]/',
            'cost_type' => 'required',
            'calc_base' => 'required',
            'rate' => 'required|numeric',
            'vat' => 'required|numeric'
    
        ]);

        $scheme = new _cplScheme;
        $scheme->scheme_name = $request->input('scheme_name');
        $scheme->cost_code =$request->input('cost_type');
        $scheme->calcbase =$request->input('calc_base');
        $scheme->rate =$request->input('rate');
        $scheme->vat =$request->input('vat');

        $scheme->save();

        return redirect()->back()->with('success','Scheme Created Successfully');

        



        
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            
            'scheme_name' => 'required|regex:/^[a-zA-Z]/',
            'cost_type' => 'required',
            'calc_base' => 'required',
            'rate' => 'required|numeric',
            'vat' => 'required|numeric'
    
        ]);

        $scheme_update = _cplScheme::find($id);
        $scheme_update->rate =$request->input('rate');

        $scheme_update->save();

        return redirect()->back()->with('success','Scheme Updated Successfully');


     
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        _cplScheme::where('id', $id)->delete();
        return redirect()->back()->with('success', 'Scheme Deleted Successfully');   

    }
}
