<?php

namespace App\Http\Controllers;

use App\Role;
use App\User;
use App\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use DB;
use Session;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    
    {
    $users = DB::table('users')
    ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
    ->leftJoin('countries', 'users.country_id', '=', 'countries.id')
    ->select('users.*','roles.role_name','countries.country_name')
    ->get();
     $countries=Country::all();
      $roles =Role::all();  
      $countries =Country::all();
    return view('admin.users.index',compact('roles','countries','users'));
    }


    public function status_change(Request $request)
        {
            
        $user = User::find($request->user_id); 
        $user->status = $request->status; 
        $user->save(); 

    Session::flash('success','User Status Updated Successfully'); 
    return response()->json(['success'=>'Status change successfully.']); 
     

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
        $this-> validate($request,[
            'fname' => 'required',
            'lname' => 'required',
            'email' => 'required|unique:users,email',
            'country_id'=>'required',
            'telephone' => 'numeric|required',
            'password' => 'min:4|required_with:conf_password|same:conf_password',
            'role_id' => 'required',
                    
         ]);
             $user = new User;
             $user ->fname =$request -> input('fname');
             $user ->lname =$request -> input('lname');
             $user ->email =$request -> input('email');
             $user ->telephone =$request -> input('telephone');
             $user ->role_id =$request -> input('role_id');
             $user ->country_id =$request -> input('country_id');
                     
             $user ->password =Hash::make($request ->input('password'));
             $user ->save();
            
            return redirect()->back()->with('success', 'User Created Successfully');
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
        $this-> validate($request,[
            'fname' => 'required',
            'lname' => 'required',
            'email' => 'required',
            'country_id'=>'required',
            'telephone' => 'numeric|required',
            'password' => 'min:4|required_with:conf_password|same:conf_password',
            'role_id' => 'required',
                    
         ]);
             $user =  User::find($id);
             $user ->fname =$request -> input('fname');
             $user ->lname =$request -> input('lname');
             $user ->email =$request -> input('email');
             $user ->telephone =$request -> input('telephone');
             $user ->role_id =$request -> input('role_id');
             $user ->country_id =$request -> input('country_id');
                     
             $user ->save();
            
            return redirect()->back()->with('success', 'User Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
