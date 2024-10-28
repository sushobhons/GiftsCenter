@extends('frontend.layouts.master')
@section('title', 'Request a Product | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{ asset('public/css/sign-in.css') }}" rel="stylesheet"/>
@endsection
@section('main-content')
    <div class="login-sec">
        <div class="login-white-box">
            <p><i class="icon fa fa-info-circle"></i> Cannot find what you're looking for? Type your request here, and
                we'll prepare your product and email you.</p>
            <ul class="row sign-in-list">
                <li class="col-md-12">
                    <textarea class="form-control" name="request" rows="5" id="request_product_text"
                              style="height: 135px"></textarea>
                </li>
                <li class="col-md-12">
                    <a id="request_product_btn" class="SignBtn btn btn-primary" href="">Submit</a>
                </li>
            </ul>
        </div>
    </div>
@endsection
@push('scripts')
    <script type="text/javascript">
        $(document).ready(() => {
            $('#request_product_btn').click(function (e) {
                e.preventDefault();
                validateRequestProduct();
            });
        });

        function validateRequestProduct() {
            const comment = $.trim($('#request_product_text').val());

            if (authUserId === "") {
                showMessage('Sorry! Please sign in to request a product.');
                return false;
            }

            if (comment === "") {
                showMessage("Please enter your request");
                $('#request_product_text').focus();
                return false;
            }

            $.ajax({
                url: "{{ url('save-request-product') }}",
                type: "POST",
                data: {'comment': comment},
                dataType: "json",
                beforeSend: function () {
                    $('#request_product_btn').prop('disabled', true).text('Submitting...');
                },
                complete: function () {
                    $('#request_product_btn').prop('disabled', false).text('Submit');
                },
                success: function (response) {
                    showMessage(response.message);
                    if (response.result === true) {
                        $('#request_product_text').val('');
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });

        }
    </script>
@endpush
