@extends('frontend.layouts.master')

@section('title','Order Details | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/sign-in.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/my-account.css')}}" rel="stylesheet"/>
@endsection
@section('main-content')
    <!-- Shop Login -->
    <div class="container custom-max-container">
        <div class="after-login-white-sec">
            <h3>Order Details</h3>
            <div class="odrDtls">
                <div class="row">
                    <div class="col-lg-6">
                        <h2>Order Details</h2>
                        <table class="dtlTbl" border="0">
                            <tr>
                                <td>Customer ID</td>
                                <td>{{ isset($customer->customer_id) ? $customer->customer_id : '' }}</td>
                            </tr>
                            <tr>
                                <td>Customer Name</td>
                                <td>{{ isset($customer->customer_name) ? $customer->customer_name : '' }}</td>
                            </tr>
                            <tr>
                                <td>Order ID</td>
                                <td>{{ $invoiceNumber }}</td>
                            </tr>
                            <tr>
                                <td>Date & Time</td>
                                <td>{{ $saleDateTime }}</td>
                            </tr>
                            <tr>
                                <td>Store Name & ID</td>
                                <td>{{ $storeName }}
                                    {{ $storeNumber }}
                                </td>
                            </tr>
                            <tr>
                                <td>Total Purchase</td>
                                <td>JOD {{ isset($orderTotal) ? number_format($orderTotal, 3) : '' }}</td>
                            </tr>
                            <tr>
                                <td>Credit Purchase</td>
                                <td>{{ isset($payment['Credit']) && $payment['Credit'] != '' ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <td>Gained Loyalty Points</td>
                                <td>{{ isset($gainedLoyalty) && $gainedLoyalty > 0 ? $gainedLoyalty : '--' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-lg-6">
                        <h2>Payment Details</h2>
                        <table class="dtlTbl" border="0">
                            <thead>
                            <tr>
                                <th>Payment Type</th>
                                <th>by</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>USD Cash</td>
                                <td>
                                    {{ isset($payment['USDCash']) && $payment['USDCash'] != '' ? 'USD ' . $payment['USDCash'] . ' (JOD ' . $payment['USDCash'] . ')' : '--' }}
                                </td>
                            </tr>
                            <tr>
                                <td>JOD Cash</td>
                                <td>{{ isset($payment['JODCash']) && $payment['JODCash'] != '' ? 'JOD ' . $payment['JODCash'] : '--' }}</td>
                            </tr>
                            <tr>
                                <td>Card</td>
                                <td>{{ isset($payment['Card']) && $payment['Card'] != '' ? 'JOD ' . $payment['Card'] : '--' }}</td>
                            </tr>
                            <tr>
                                <td>Cash On Delivery</td>
                                <td>{{ isset($payment['COD']) && $payment['COD'] != '' ? 'JOD ' . $payment['COD'] : '--' }}</td>
                            </tr>
                            <tr>
                                <td>Credit</td>
                                <td>{{ isset($payment['Credit']) && $payment['Credit'] != '' ? 'JOD ' . $payment['Credit'] : '--' }}</td>
                            </tr>
                            <tr>
                                <td>Redeemed Loyalty Points</td>
                                <td>{{ isset($redeemedLoyalty) && $redeemedLoyalty > 0 ? $redeemedLoyalty : '--' }}</td>
                            </tr>
                            <tr>
                                <td>Order status</td>
                                <td>{{ isset($deliveryStatus) && $deliveryStatus != '' ? $deliveryStatus : '--' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                    <div class="col-lg-12">
                        <h2>Products</h2>
                        <div class="res-tbl">
                            <table width="100%" border="0" class="order_tbl">
                                <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Item Desc.</th>
                                    <th>Brand ID</th>
                                    <th>Category</th>
                                    <th>Tax %</th>
                                    <th>Dis. %</th>
                                    <th>Qty.</th>
                                    <th>Adv.</th>
                                    <th>Retail Price</th>
                                    <th>Whole Sale</th>
                                    <th>Total</th>
                                    <th>Sales Person</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($orders as $order)
                                    <tr>
                                        <td data-title="Item">{{ $order->product_no ?? '' }}</td>
                                        <td data-title="Item Desc.">{{ $order->title ?? '' }}</td>
                                        <td data-title="Brand ID">{{ $order->brand ?? '' }}</td>
                                        <td data-title="Category">
                                            <a href="#" data-toggle="tooltip" data-placement="top" title="{{ $order->category ?? '' }}">
                                                {{ $order->old_id ?? 'N/A' }}
                                            </a>
                                        </td>
                                        <td data-title="Tax %">{{ $order->tax ?? '' }}%</td>
                                        <td data-title="Disc. %">{{ $order->discount ?? '' }}</td>
                                        <td data-title="Qty.">{{ $order->qty ?? '' }}</td>
                                        <td data-title="Adv.">{{ $order->adv ?? '' }}</td>
                                        <td data-title="Retail Price">{{ $order->retail_price ?? '' }}</td>
                                        <td data-title="Wholesale Price">{{ $order->wholesale_price ?? '' }}</td>
                                        <td data-title="Total">{{ $order->total ?? '' }}</td>
                                        <td data-title="Sales Person">{{ $order->sales_person ?? '' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>





        </div>
    </div>


@endsection

