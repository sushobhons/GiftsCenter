@extends('frontend.layouts.master')
@section('title','Order Processing | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-history.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="container custom-max-container">
        <div class="order-history-white-sec">
            <div class="loader" sty><img src="{{asset('public/img/loader.png')}}"/></div>
            <h5>Please Wait &#45; We are processing your order, do not click away from this page. This process can take up to 30 seconds</h5>
        </div>
    </div>
    <form id="order" name="order" action="{{ url('/order-placed') }}" method="post">
        @csrf

        <!-- Hidden input fields -->
        <input type="hidden" id="gift_wrap" value="" name="gift_wrap" />
        <input type="hidden" id="gift_box" value="" name="gift_box" />
        <input type="hidden" id="gift_message" value="" name="gift_message" />
        <input type="hidden" id="payment_type" value="" name="payment_type" />
    </form>
@endsection
@push('scripts')
    <script type="text/javascript">
        $(document).ready(() => {
            // Declare variables with let or const
            const gift_wrap = sessionStorage.getItem('add_gift_wrap') || 0;
            const gift_box = sessionStorage.getItem('add_gift_box') || 0;
            const gift_message = sessionStorage.getItem('gift_message') || '';
            const payment_type = sessionStorage.getItem('payment_type') || '';

            // Set values to corresponding hidden input fields
            $("#gift_wrap").val(gift_wrap);
            $("#gift_box").val(gift_box);
            $("#gift_message").val(gift_message);
            $("#payment_type").val(payment_type);

            // Clear sessionStorage
            sessionStorage.clear();

            // Submit the form
            $("form#order").submit();
        });
    </script>
@endpush