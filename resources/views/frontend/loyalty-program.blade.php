@extends('frontend.layouts.master')

@section('title', stripslashes($article->article_name) . ' | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/brand.css')}}" rel="stylesheet"/>
    <style>
            .total-purchase { margin:0; padding:0 7px;}
            .newTable { width:100%; margin:45px 0; text-align:center;}
            .newTable .newTable-Inner { width:100%; max-width:750px; display:inline-block;}
            .newTable ul { list-style: none; float: left; width:25%; padding:20px 0; margin: 0 20px; broder-radius:15px; }
            .newTable ul.listName {margin-top: 63px;}
            .newTable ul.listName li{ font-size:14px;}
            .newTable span {width: 130px; float: left; margin: 0 20px;}
            .newTable span.white ul { width: 130px; float: left; padding:20px 30px; border-radius: 15px;  margin:15px 0 0;}
            .newTable span.white h2 { background:#fff; text-transform:uppercase; font-size:17px; color:#002854; font-weight: 700 !important; border-radius:8px; padding:15px;box-shadow: 3px 3px 4px rgb(0 0 0 / 40%);  margin:0;}						
            .newTable span.silver ul { width: 130px; float: left; padding:20px 30px; background:#f5f5f5; border-radius: 15px; margin:15px 0 0; }
            .newTable span.silver  h2 { background:#f5f5f5;  text-transform:uppercase;  font-size:17px; color:#002854;  font-weight: 700 !important; border-radius:8px; padding:15px;box-shadow: 3px 3px 4px rgb(0 0 0 / 40%);  margin:0;}					 
            .newTable span.blue ul {width: 130px; float: left; padding:20px 30px; background:#d8dfe6;  border-radius: 15px;  margin:15px 0 0;}
            .newTable span.blue  h2 { background:#002854;   font-size:17px; color:#fff;  font-weight: 700 !important; border-radius:8px; padding:15px; text-transform:uppercase; box-shadow: 3px 3px 4px rgb(0 0 0 / 40%);  margin:0 0 0;}						   
            .newTable ul li {background: none; padding:0; text-align: left; font-weight:normal ; font-size: 12px;  color:#002855;  margin-bottom: 0px; line-height: 48px;}
            .newTable ul li strong { font-weight: 700 !important; }

            @media (width: 768px) {
                .newTable { width:100%;overflow-y: scroll;}
                .newTable-Inner{   width: 734px; overflow-y: scroll;}
                .BrandListWrapper { width: 100%; float: left; padding: 0 0;}
            }
            @media (max-width: 767px) {
                .newTable { width:100%;overflow-y: scroll;}
                .newTable-Inner{  width: 750px; overflow-y: scroll;}
            }

            .slider {
                position: relative;
                width: 100%;
                height: 2px;
                background-color: #666;
                margin-bottom: 25px;
                border-radius: 6px;
                border:2px solid #002855 !important;
                .ui-slider-handle {
                    display: block;
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 20px;
                    height: 20px;
                    background-color: #fff;
                    border-radius: 100%;
                    box-shadow: 1px 1px 10px 1px rgba(0, 0, 0, .2);
                    cursor: pointer;
                    outline: none;
                }
            }
            .ui-slider-pip { position:absolute; top:5px;}
            .ui-slider-pip.ui-slider-pip-first { text-align:left !important;}
            .ui-slider-pip.ui-slider-pip-last { left:auto !important; right:0;}
            .ui-slider-pip.ui-slider-pip-last span { text-align:right;}
            .ui-slider-pip.ui-slider-pip-label { text-align:center;}
            .ui-slider-pip .ui-slider-label { font:bold 11px 'Montserrat Light'; text-transform:uppercase;}
            .ui-slider-pip .ui-slider-label span { font-size:16px; display:block; color:#002855;}
            .RewardWrapper { margin-top:0px;}
            .ui-slider-horizontal .ui-slider-handle { top:-21px; z-index:-1; background:none; border-radius:0; border-width:2px; width:0; border-color:#002855;}

            .value {
                display: block;
                position: absolute;
                padding:0;
                border-radius: 5px;
                bottom:22px;
                left: 50%;
                transform: translateX(-50%);
                color: #333;
                font:bold 11px 'Montserrat Light'; text-transform:uppercase;
                white-space:nowrap;
            }
            .value::after {
                content: '';
                position: absolute;
                bottom: 24px;
                left: 50%;
                border: solid transparent;
                height: 0;
                width: 0;
                pointer-events: none;
                border-color: rgba(194, 225, 245, 0);
                border-top-color: #676f73;
                border-width: 5px;
                margin-left: -5px;
                transform: rotate(180deg);
                display:none;
            }
            .ui-state-focus,  .ui-state-hover {
                border-color: transparent !important;
            }

            .ui-slider-pips .ui-slider-pip {
                top:auto;
                bottom:0px;
            }


            @media screen and ( max-width:767px ) {
                .wide-table-sec { overflow-x:scroll; width: 100%; }
                .newTable .newTable-Inner { width:750px; }
                .newTable span h2 { font-size:13px !important;}

            }


        </style>
@endsection
@section('main-content')
    <div class="container">
        <div class="brands-white-sec">
        <div class="common-body GC-Reward">
            <div class="body_content">
                <div class="container-fluid">
                    <div class="BrandListWrapper">
                    @if (auth()->check() && auth()->user()->customer_id != '')
                            <div class="row total-purchase" style="margin: 25px 0;">
                                <div id="srch_range" class="slider" data-min="0" data-max="1000" data-value="{{ $total_purchase }}" data-step="1">
                                    <span class="ui-slider-pip ui-slider-pip-first ui-slider-pip-label ui-slider-pip-0" style="left: 0%">
                                        <span class="ui-slider-line"></span>
                                        <span class="ui-slider-label" data-value="0">0 JOD <br> <span>WHITE</span></span>
                                    </span>
                                    <span class="ui-slider-pip ui-slider-pip-label ui-slider-pip-500" style="left: 47.50%">
                                        <span class="ui-slider-line"></span>
                                        <span class="ui-slider-label" data-value="500">500 JOD<br> <span>SILVER</span></span>
                                    </span>
                                    <span class="ui-slider-pip ui-slider-pip-last ui-slider-pip-label ui-slider-pip-1000" style="left: 100%">
                                        <span class="ui-slider-line"></span>
                                        <span class="ui-slider-label" data-value="1000">1000 JOD <br> <span>BLUE</span></span>
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="RewardWrapper">
                            {!! stripslashes($article['article_content']) !!}
                            <div class="newTable">
                                <div class="wide-table-sec">
                                    <div class="newTable-Inner">
                                        <ul class="listName">
                                            <li></li>
                                            @forelse ($rewards_title_arr as $title)
                                                <li>{{ $title }}</li>
                                            @empty
                                                <!-- Handle the case where $rewards_title_arr is empty -->
                                            @endforelse
                                        </ul>

                                        @forelse ($tiers_arr as $tier)
                                            <span class="{{ $tier['slug'] }}">
                                                <h2>{{ $tier['title'] }}</h2>
                                                <ul>
                                                    @if (!empty($tier['rewards']) && !empty($rewards_title_arr))
                                                        @forelse ($rewards_title_arr as $rk => $rv)
                                                            <li>{{ isset($tier['rewards'][$rk]) ? $tier['rewards'][$rk] : '' }}</li>
                                                        @empty
                                                            <!-- Handle the case where $rewards_title_arr is empty -->
                                                        @endforelse
                                                    @endif
                                                </ul>
                                            </span>
                                        @empty
                                            <!-- Handle the case where $tiers_arr is empty -->
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection
@if (auth()->check() && auth()->user()->customer_id != '')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('public/js/jquery-ui-slider-pips.js') }}"></script>
    <script type="text/javascript">
        var total_purchase = {{ $total_purchase }};
        var labels = {"first": "0JOD <br> WHITE", "rest": "500JOD <br> SILVER", "last": "1000JOD <br> BLUE"};
        
        // Use $ instead of jquery
        $(document).ready(function() {
            $("#srch_range").slider({
                range: false,
                value: total_purchase,
                min: 0,
                max: 1000,
                step: 1,
            });
            
            var rangesliderHandle = $("#srch_range").find('.ui-slider-handle'), rangecurrentValue = rangesliderHandle.parent().attr('data-value');
            rangesliderHandle.append('<span class="value min-value" data-selected-value="' + rangecurrentValue + '">' + rangecurrentValue + ' JOD</span>');
        });
    </script>
@endif