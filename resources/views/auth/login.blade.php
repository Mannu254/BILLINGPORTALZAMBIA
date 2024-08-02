@extends('layouts.app')

@section('content')
<style>

 a:hover {
    text-decoration: underline;
    outline: none;
}
ul {
    margin: 0;
    padding: 0;
    list-style: none;
}
P{
  color: white;
}



.bg-gradient2 {
    background: #0ebdca;
  
}

.bg-overlay-gradient {
    background: #d8d8da;
    background: -webkit-linear-gradient(to right, #797cd2, #393e9e);
    background: linear-gradient(to right, #797cd2, #393e9e);
    opacity: 0.9;
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
}

.home-table-center {
    display: table-cell;
    vertical-align: middle;
}

.home-table {
    display: table;
    width: 100%;
    
}

.header_title {
    font-size: 44px;
    line-height: 1.2;
    max-width: 850px;
    text-transform: capitalize;
}

.small_title p {
    font-size: 16px;
    border-radius: 30px;
    padding: 4px 18px;
    background-color: rgba(255, 255, 255, 0.1);
    display: inline-block;
}

.header_subtitle {
    line-height: 1.8;
    max-width: 450px;
    color: rgba(255, 255, 255, 0.6) !important;
}

.home-desk {
    position: relative;
    top: 60px;
    z-index: 100;
}


.btn-custom {
    background-color: #0ebdca;
    border: 2px solid #8a9595;
    color: #fff;
    font-size: 14px;
    transition: all 0.5s;
    border-radius: 5px;
    letter-spacing: 1px;
    text-transform: capitalize;
}
.btn-custom:hover{
    opacity: 0.8;
}
.account_box{
    border-radius: 12px;
    box-shadow: 10px -10px 0 4px #edc373;
    padding: 50px 40px;
}

.account_box h5 {
    font-size: 16px;
    max-width: 100%;
    line-height: 1.4;
}
.account_box .form-control{
    box-shadow: none !important;
    color: #fff;
    height: 46px;
    border: none;
    border-radius: 3px;
    font-size: 14px;
    background-color: rgba(255, 255, 255, 0.1);
}

.account_box .form-control::-webkit-input-placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.account_box .form-control::-moz-placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.account_box .form-control:-ms-input-placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.account_box .form-control:-moz-placeholder {
    color: rgba(255, 255, 255, 0.6);
}

    
</style>
<br><br>

<div class="bg-gradient">          
  <div class="home-table">
      <div class="home-table-center">
          <div class="container">
              <div class="row justify-content-center">
                  <div class="col-lg-5">                      
                      <div class="account_box bg-gradient2">
                        <div class="text-center">
                          <p class="mb-4 pb-3">MFI ZAMBIA BILLING</p>
                      </div>
                          <form method="POST" action="{{ route('login')}}">
                           @csrf
                              <div class="col-lg-12 mt-3">
                                  <label class="text-white">Email</label>
                                  <input id="email" type="email" class="form-control  @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Email">
                                  @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                              </div>
                              <div class="col-lg-12 mt-3">
                                  <label class="text-white">Password</label>
                                  <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Password">
                                  @error('password')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                              </div>
                              <div class="col-lg-12 mt-3">
                                  <div class="custom-control">                                   
                                      <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                       <label class="text-white">Remember me ?</label>                         
                                      
                                  </div>
                              </div>
                              <div class="col-lg-12">
                                  <button class="btn btn-custom w-100 mt-3">Sign In</button>
                              </div>
                              <div>
                                @if (Route::has('password.request'))
                                  <p class="mb-0 text-center mt-3"><a href="{{ route('password.request') }}" class="text-white font-weight-bold">Forgot your password ?</a></p>
                                  @endif
                              </div>
                          </form>
                      </div>
                     
                  </div>
              </div>
          </div>
      </div>
  </div>
</div>
@endsection
