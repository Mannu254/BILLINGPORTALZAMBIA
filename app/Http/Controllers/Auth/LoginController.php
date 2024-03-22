<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Auth;
use Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;
    public function redirectTo(){
        switch(Auth::user()->role_id)
        {
          case 1:
          $this->redirectTo = '/admin';
          //  return $this-> redirectTo;
          // dd(Request::session()->get('url.intended'));
          return Request::session()->get('url.intended')?? '/admin';
          break;
          case 2: 
          $this->redirectTo = '/billing';
          return $this-> redirectTo;
          break; 
          case 3: 
          $this->redirectTo = '/landed_cost';
          return $this-> redirectTo;
          break;
          
          case 4:
        $this->redirectTo = '/reports';
          return $this-> redirectTo;
          break;

          case 5:
            $this->redirectTo = '/Service_Manager';
              return $this-> redirectTo;
              break;
          case 6:
            $this->redirectTo = '/AccountManager';
              return $this-> redirectTo;
              break;

          case 7:
            $this->redirectTo = '/BillingManager';
              return $this-> redirectTo;
              break;

                   
          default:
          $this->redirectTo = '/login';
          return $this-> redirectTo;
          
        }
        // return $next($request);
        // return redirect()->guest(route('login'));
    }


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
