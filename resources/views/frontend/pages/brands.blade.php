@extends('frontend.layouts.master')

@section('title', 'Brands | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/brand.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="container">
        <div class="brands-white-sec">
            @if(count($brands)>0)
                <ul class="brands-list">
                    @foreach ($brands as $alphabetKey => $alphabetBrands)
                        <li>
                            <h3>{{ $alphabetKey }}</h3>
                            <ul>
                                @foreach($alphabetBrands as $brandKey => $brand)
                                    <li>
                                        <a href="{{route('products.brand',['brandSlug' => $brand['brand_slug']])}}">{{$brand['brand_name']}}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection