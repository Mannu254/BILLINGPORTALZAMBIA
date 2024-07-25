@extends('layouts.app')

@section('content')
<style>
    /* * * * * General CSS * * * * */
*,
*::before,
*::after {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 14px;
  font-weight: 400;
  color: #666666;
  background: #eaeff4;
}


.container {
margin-top: 7%;
  position: relative;
  width: 100%;
  max-width: 890px !important;
 height: 420px !important;
  display: flex;
  background: #ffffff;
  box-shadow: 0 0 15px rgba(0, 0, 0, .1);
}

.credit {
  position: relative;
  margin: 25px auto 0 auto;
  width: 100%;
  text-align: center;
  color: #666666;
  font-size: 14px;
  font-weight: 400;
}

.credit a {
  color: #222222;
  font-size: 12px;
 
 
}

/* * * * * Login Form CSS * * * * */




.btn {
  display: inline-block;
  padding: 7px 20px;
  font-size: 14px;
  letter-spacing: 1px;
  text-decoration: none;
  border-radius: 5px;
  color: #ffffff;
  outline: none;
  border: 1px solid #ffffff;
  transition: .3s;
  -webkit-transition: .3s;
}

.btn:hover {
  color: #4CAF50;
  background: #ffffff;
}

.col-left,
.col-right {
  width: 52%;
  padding: 45px 35px;
  display: flex;
}

.col-left {
  width: 60%;
  
  background-image: url("../storage/uploads/tzlogo.png");
  background-size: cover;
  
  -webkit-clip-path: polygon(98% 17%, 100% 34%, 98% 51%, 100% 68%, 98% 84%, 100% 100%, 0 100%, 0 0, 100% 0);
  clip-path: polygon(98% 17%, 100% 34%, 98% 51%, 100% 68%, 98% 84%, 100% 100%, 0 100%, 0 0, 100% 0);
}

@media(max-width: 575.98px) {
  .container {
    flex-direction: column;
    box-shadow: none;
  }

  .col-left,
  .col-right {
    width: 100%;
    margin: 0;
    padding: 30px;
    -webkit-clip-path: none;
    clip-path: none;
  }
}

.login-text {
  position: relative;
  width: 100%;
  color: #ffffff;
  text-align: center;
}

.login-form {
  position: relative;
  width: 100%;
  color: #666666;
}

.login-form p:last-child {
  margin: 0;
}

.login-form p a {
  color: #4CAF50;
  font-size: 12px;
  text-decoration: none;

}

.login-form p:last-child a:last-child {
  float: right;
}

.login-form label {
  display: block;
  width: 100%;
 
}

.login-form p:last-child label {
  width: 100%;
  float: left;
}

.login-form label span {
  color: #ff574e;
  padding-left: 2px;
}

.login-form input {
  display: block;
  width: 100%;
  height: 40px;
  padding: 0 40px;
  font-size: 14px;
  margin-top: 20px;
  letter-spacing: 1px;
  outline: none;
  border: 1px solid #cccccc;
  border-radius: 5px;
}


.login-form input:focus {
  border-color: #ff574e;
}

.login-form input.btn {
  color: #ffffff;
  background:#00BFFF;
  border-color:#00BFFF;
  outline: none;
  cursor: pointer;
}

.login-form input.btn:hover {
  color: #4CAF50;
  background: #ffffff;
}
.AppFormRight{
    margin-top:130px;
    z-index: 1;
    font-weight:400;
    font-family:'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', 'Lucida Sans', Arial, sans-serif
}
input[type="checkbox"] {
  margin-top: 3px; 
  height: 1.5rem;
  margin-right: 35px !important;
  
}



    
</style>

<div class="container">
<div class="col-left">
<div class="login-text">
<div class="AppFormRight position-relative d-flex justify-content-center flex-column align-items-center text-center p-6 text-white">
<h4 class="position-relative px-4 pb-2 mb-3">MFI TANZANIA BILLING PORTAL </h4></div>
</div>
</div>
<div class="col-right">
<div class="login-form">
<h2>Login</h2>
<form method="POST" action="{{ route('login') }}">
            @csrf
 <p>
<input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
@error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
</p>
<p>
<input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
@error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror</p><p>
<input class="btn" type="submit" value="Sign In" /></p>
<p class="row mt-8"> @if (Route::has('password.request'))<a class="btn btn-link" href="{{ route('password.request') }}">
                {{ __('Forgot Your Password?') }}</a>
@endif

</p>
<div style="float:right;margin-right:60px !important; div2">

<input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
<label class="" for="remember">{{ __('Remember Me ?') }}</label>
</div>
</form>
</div>
</div>

  </div>
@endsection
