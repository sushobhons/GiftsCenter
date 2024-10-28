@extends('frontend.layouts.master')

@section('title', stripslashes($article->article_name) . ' | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-history.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="container custom-max-container">
        <div class="order-history-white-sec">
            <h3>{!! stripslashes($article->article_name) !!}</h3>
            {!! stripslashes($article->article_content) !!}
        </div>
    </div>
@endsection
