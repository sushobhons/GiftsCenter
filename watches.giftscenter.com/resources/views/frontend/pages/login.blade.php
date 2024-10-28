@extends('frontend.layouts.master')

@section('title','Sign In | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/sign-in.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <!-- Shop Login -->
    <div class="login-sec">
        <div class="login-white-box">
            <h3>MEMBER SIGN IN</h3>
            <h4>Welcome back! Sign in for faster checkout.</h4>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="ratesDes" role="tabpanel" aria-labelledby="rate-tab">
                    <form class="form" method="post" action="{{route('login.submit')}}">
                        @csrf
                        <ul class="row sign-in-list">
                            <li class="col-md-12">
                                <input class="form-control" type="email" name="email" placeholder="Email ID"
                                       required="required" value="{{old('email')}}"/>
                                @error('email')
                                <span class="text-danger">{{$message}}</span>
                                @enderror
                            </li>
                            <li class="col-md-12">
                                <input class="form-control" type="text" name="otp" placeholder="OTP"
                                       required="required" value="{{old('otp')}}">
                                @error('otp')
                                <span class="text-danger">{{$message}}</span>
                                @enderror
                            </li>

    
                            <li class="col-md-12">
                                <button class="btn btn-primary" type="submit">Login</button>
                            </li>
                        </ul>
                    </form>
                </div>
                <p>
                <a href="{{ url('/user/register') }}" class="regis-btn">Register</a></p>
            </div>
            <div class="bottom-sign-in-sec">
                <span class="or-span-text">Or</span>
                <p>Would you like to order as guest?</p>
                <a href="#" class="guest-btn">CHECKOUT AS GUEST</a>
            </div>
        </div>
    </div>
    <!--/ End Login -->
@endsection
