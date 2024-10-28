@extends('frontend.layouts.master')

@section('title','Store Details | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-history.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="cms-top-heading">
        <h2>{{ $store->store_name ?? '' }}</h2>
        <h3>{{ $store->address ?? '' }}</h3>
        <p>{{ $store->ph_no ?? '' }}</p>
    </div>
    <div class="container custom-max-container">
        <div class="order-history-white-sec store-details-white-sec">
            <div id="MapCanvas"></div>
        </div>
    </div>
@endsection
<!-------------------for google map------------------->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCcjOE3xj0VquoaDm1Zo1Oi0Cu3vdQdv5U&v=3.exp"></script>
<script>
    function initialize() {
        var mapCanvas = document.getElementById('MapCanvas');
        var bounds = new google.maps.LatLngBounds();

        // Use Blade syntax to directly embed values
        var lat = {{ !empty($store) ? $store->latitude : 36.77826 }};
        var lon = {{ !empty($store) ? $store->longitude : -119.417932 }};

        var myLatLng = {lat: lat, lng: lon};

        var map = new google.maps.Map(mapCanvas, {
            zoom: 16,
            center: myLatLng
        });

        var marker = new google.maps.Marker({
            position: myLatLng,
            map: map,
            title: '{{ !empty($store) ? $store->address : "" }}'
        });
    }

    google.maps.event.addDomListener(window, 'load', initialize);
</script>