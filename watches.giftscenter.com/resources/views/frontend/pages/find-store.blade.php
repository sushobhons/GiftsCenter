@extends('frontend.layouts.master')

@section('title','Find Store | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-history.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="container custom-max-container">
        <div class="order-history-white-sec find-store-white-sec">
            <div class="find-store-sec">
                <ul class="row find-store-list">
                    {{-- Loop through the $store_arr array --}}
                    @foreach($stores as $store)
                        <li class="col-lg-4 col-md-6">
                            <a href="{{ url('/store').'/'. $store['store_id'] }}">
                                <div class="adrs_detail">
                                    <h2><?php echo stripslashes($store['store_name']) ?></h2>
                                    <ul>
                                        <li><?php echo stripslashes($store['address']) ?></li>
                                        <li><?php echo stripslashes($store['ph_no']) ?>
                                                <?php if (isset($store["extension"]) && $store["extension"] != ""): ?>
                                            EXT : <?php echo $store["extension"]; ?>
                                                  <?php endif; ?>
                                        </li>
                                        <li><?php echo isset($store["opening_hours"]) && $store["opening_hours"] != "" ? $store["opening_hours"] : ''; ?></li>
                                    </ul>
                                </div>
                            </a>
                        </li>
                    @endforeach

                </ul>
            </div>
        </div>
    </div>
@endsection
