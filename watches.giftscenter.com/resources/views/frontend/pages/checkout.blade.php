@extends('frontend.layouts.master')
@section('title','Checkout | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <!-- Bootstrap core CSS -->
    <link href="{{asset('public/css/bootstrap-datepicker3.min.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/checkout.css')}}" rel="stylesheet"/>
    <link href="{{asset('public/css/bootstrap-select@1.13.14.min.css')}}" rel="stylesheet">
    <link href="{{asset('public/css/flag-icon.min.css')}}" rel="stylesheet">

    <script>
        fbq('track', 'InitiateCheckout', {
            value: {{ number_format($payableAmount, 2, '.', '') }},
            currency: 'JOD',
            content_name: 'Checkout',
            content_category: 'snippets',
            content_ids: '',
            num_ids: ''
        });
    </script>
@endsection
@section('main-content')
    <div class="container">
        <div class="checkout-white-box">
            {{--            <h3>Checkout As Guest</h3>--}}
            <h3>Checkout</h3>
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                            class="nav-link active"
                            id="product-tab"
                            data-toggle="tab"
                            data-target="#addresses"
                            type="button"
                            role="tab"
                            aria-controls="addresses"
                            aria-selected="true"
                    >
                        Delivery
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                            class="nav-link"
                            id="rate-tab"
                            data-toggle="tab"
                            data-target="#stores"
                            type="button"
                            role="tab"
                            aria-controls="stores"
                            aria-selected="false"
                    >
                        Pick up
                    </button>
                </li>
            </ul>
            <form>
                <div class="bottom-buttons-section">
                    <div class="row">
                        <div class="col-md-7 left-content-checkout-sec">
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="addresses" role="tabpanel"
                                     aria-labelledby="product-tab">
                                    <h4>Shipping Address</h4>
                                    <ul class="setDefaultAddress delivery-ul"
                                        style="display: {{ !empty($shippingAddresses) ? '' : 'none' }};"
                                        id="selectable_shipping_details">
                                        @forelse($shippingAddresses as $shippingAddress)
                                            <li id="shipping_addr_{{ $shippingAddress['id'] }}">
                                                <input name="shipping_addresses" type="radio"
                                                       value="{{ $shippingAddress['id'] }}"/>
                                                <label class="chk_address">
                                                    {{ implode(', ', array_filter([$shippingAddress['street_number'], $shippingAddress['street'], $shippingAddress['city'], $shippingAddress['state']])) }}
                                                    @if($shippingAddress['is_default'] == '1')
                                                        {{--                                                    <span>Default Address</span>--}}
                                                    @endif
                                                </label>
                                                <span class="editLink">
                                                    <a href="#" class="edit-shipping-btn"
                                                       data-address="{{ $shippingAddress['id'] }}"
                                                       style="margin-right:5px;">Edit</a>
                                                    <a href="#" class="delete-shipping-btn"
                                                       data-address="{{ $shippingAddress['id'] }}">Delete</a>
                                                </span>
                                            </li>
                                        @empty
                                        @endforelse
                                        <li>
                                            <a href="#" id="add-shipping-btn" class="add_new_addr">Add new address</a>
                                        </li>

                                    </ul>
                                    <ul id="selected_shipping_details" class="row checkout-form-list"
                                        style="display: {{ !empty($shippingAddresses) ? 'none' : '' }};">
                                        <li class="col-md-6"><input class="form-control only-english" type="text"
                                                                    placeholder="Name*"
                                                                    aria-label="Name*" name="ship_name" id="ship_name"/>
                                        </li>
                                        <li class="col-md-6"><input class="form-control" type="tel"
                                                                    placeholder="7xxxxxxxx"
                                                                    aria-label="Phone Number" name="ship_phone"
                                                                    id="ship_phone"/></li>
                                        <li class="col-md-6"><input class="form-control" type="email"
                                                                    placeholder="Email Address"
                                                                    aria-label="Email Address"
                                                                    name="ship_email" id="ship_email"/>
                                        </li>
                                        <li class="col-md-6">
                                            <select name="ship_state" id="ship_state"
                                                    class="form-control select-control">
                                                <option value="" data-value="">City*</option>
                                                @forelse($cities as $city)
                                                    <option value="{{ $city->value ?? '' }}"
                                                            data-value="{{ $city->id ?? '' }}">
                                                        {{ $city->label ?? '' }}
                                                    </option>
                                                @empty
                                                    <!-- No cities available -->
                                                @endforelse
                                            </select>
                                        </li>
                                        <li class="col-md-6">
                                            <select name="ship_city" id="ship_city" class="form-control select-control">
                                                <option value="" data-value="">Area*</option>
                                                @foreach($areas as $area)
                                                    <option value="{{ $area->value ?? '' }}"
                                                            data-value="{{ $area->id ?? '' }}"
                                                            data-ship-charge="{{ $area->ship_charge ?? '' }}">
                                                        {{ $area->label ?? '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="ship_charge" id="ship_charge" value="0.00"/>
                                        </li>
                                        <li class="col-md-6"><input class="form-control" type="text"
                                                                    placeholder="Street Address"
                                                                    aria-label="Street Address"
                                                                    name="ship_street" id="ship_street"/>
                                        </li>
                                        <li class="col-md-6"><input class="form-control" type="text"
                                                                    placeholder="Building Number"
                                                                    aria-label="Building Number"
                                                                    name="ship_street_number" id="ship_street_number"/>
                                        </li>
                                        <li class="col-md-12">
                                            <div class="form-group form-check">
                                                <label class="form-check-label">
                                                    <input name="ship_address_id" type="hidden" value=""
                                                           id="ship_address_id"/>
                                                    <input class="form-check-input" type="checkbox"
                                                           name="default_address"
                                                           value="1" id="default_address"/>
                                                    <span class="checkmark"></span>
                                                    Make this my default address
                                                </label>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                                <div
                                        class="tab-pane fade"
                                        id="stores"
                                        role="tabpanel"
                                        aria-labelledby="rate-tab"
                                >
                                    <h4>Pick up from</h4>
                                    <ul class="row checkout-form-list">
                                        <li class="col-md-12">
                                            <select name="pickfrm_store_id" id="pickfrm_store_id"
                                                    class="form-control select-control">
                                                <option value="0">Store</option>
                                                @forelse($stores as $store)
                                                    <option value="{{ $store->store_id }}">
                                                        {{ $store->store_name }}
                                                    </option>
                                                @empty
                                                    <!-- No stores available -->
                                                @endforelse
                                            </select>
                                        </li>
                                    </ul>
                                </div>
                                <h4 style="display: {{ session('is_guest') !== null ? '' : 'none' }}">Billing
                                    Address</h4>
                                <ul class="row checkout-form-list"
                                    style="display: {{ session('is_guest') !== null ? '' : 'none' }}">
                                    <li class="col-md-6"><input class="form-control" type="text" placeholder="Name*"
                                                                aria-label="Name*" name="bill_name" id="bill_name"
                                                                value="{{ $billingAddress['bill_name'] }}"/>
                                    </li>
                                    <li class="col-md-6"><input class="form-control" type="email"
                                                                placeholder="Email Address" aria-label="Email Address"
                                                                name="bill_email" id="bill_email"
                                                                value="{{ $billingAddress['bill_email'] }}"/>
                                    </li>
                                    <li class="col-md-5">
                                        <div class="country">
                                            <div class="topLang mobileLang">
                                                <select class="selectpicker" name="bill_country"
                                                        id="bill_country" data-live-search="true">
                                                    @foreach(Helper::getCountries() as $value)
                                                        <option
                                                                data-content='<span class="flag-icon flag-icon-{{Str::lower($value->country_code)}}"></span> {{ $value->country_name . " (+" . $value->phone_code.")" }}'
                                                                value="{{ $value->country_name }}"
                                                                data-phonecode="{{ $value->phone_code }}" {{ $value->country_name == $billingAddress['bill_country'] ? 'selected' : '' }}>{{ " +" . $value->phone_code }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="col-md-7">
                                        <input class="form-control" type="tel"
                                               placeholder="Phone Number"
                                               aria-label="Phone Number" name="bill_phone"
                                               id="bill_phone"
                                               value="{{ $billingAddress['bill_phone'] }}"/>
                                        <input type="hidden" id="bill_phone_code" name="bill_phone_code"
                                               value="{{ $billingAddress['bill_phone_code'] }}"/>
                                    </li>

                                </ul>
                            </div>
                            <ul class="row checkout-form-list checkout-form-buttons-list">
                                <li class="col-md-12">
                                    <div class="form-group form-check">
                                        <label class="form-check-label">
                                            <input class="form-check-input" type="checkbox" id="terms_checkbox"/>
                                            <span class="checkmark"></span>
                                            Yes - I Agree to the <a href="{{ url('/page/terms-and-conditions') }}">Terms
                                                &
                                                Conditions*</a>
                                        </label>
                                    </div>
                                </li>
                                <li class="col-md-12"><a class="btn btn-primary" id="continue_to_payment_btn" href="#">CONTINUE</a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-5 right-content-checkout-sec">
                            <div class="right-checkout-box">
                                <div class="top-caln-sec"
                                     style="display:{{ Helper::getSiteConfig('is_deliverable') == 0 ? 'none' : '' }}">
                                    <h4>SHIPPING DATE</h4>
                                    <div id="preffered_date" data-date="{{ now()->format('m/d/Y') }}"></div>
                                    <input type="hidden" id="delivery_date" name="delivery_date"/>
                                </div>
                                <div class="bottom-caln-sec">
                                    <p>{!! Helper::getSiteConfig('is_deliverable') == 0 ? Helper::getSiteConfig('undeliverable_info') : Helper::getSiteConfig('deliverable_info') !!}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @php
        $isGuest = session('is_guest', '');
        $shippingAddresses = json_encode($shippingAddresses, JSON_HEX_APOS);
        $shippingObj = json_decode($shippingAddresses);
        $isDeliverable = Helper::getSiteConfig('is_deliverable');
    @endphp
@endsection
@push('styles')
    <style>

    </style>
@endpush
@push('scripts')
    <script src="{{asset('public/js/bootstrap-datepicker.min.js')}}"></script>
    <script src="{{asset('public/js/bootstrap-select@1.13.14.min.js')}}"></script>
    <script>

        const isGuest = '{{ $isGuest }}';
        const shippingAddresses = '{!! addslashes($shippingAddresses) !!}';
        const shippingObject = JSON.parse(shippingAddresses);
        const isDeliverable = '{{ $isDeliverable }}';
        let selectedCity = '';
        let selectedState = '';
        let pickFromStoreChecked = false;

        $(".selectpicker").selectpicker();

        // Event delegation for tab click
        $(document).on("click", ".nav-tabs button", function () {
            const tabId = $(this).attr("data-target");
            // Toggle the pickFromStoreChecked variable based on the clicked tab
            pickFromStoreChecked = (tabId === "#stores");
            tabId === "#stores" && clearShippingForm();
        });


        // Define a function to fetch cities based on the selected state
        function fetchCity(id) {
            // Ensure the ID is not empty
            if (id !== "") {
                $.ajax({
                    type: "POST",
                    url: "{{ url('/fetch-cities') }}",
                    data: {'id': id},
                    dataType: "json",
                    beforeSend: function () {
                        // You can show loading indicator or perform other actions before sending the request
                    },
                    success: function (response) {
                        // Handle the success response
                        populateCities(response.data);
                    },
                    error: function () {
                        // Handle the error response
                        console.error("Error fetching cities.");
                        clearCityOptions();
                    }
                });
            }
        }

        // Define a function to populate city options in the select element
        function populateCities(data) {
            let citySelect = $('#ship_city');
            citySelect.empty().append($('<option>', {
                value: '',
                text: 'Area*',
                'data-value': '',
                'data-ship-charge': ''
            }));

            // Check if data is available
            if (data && data.length > 0) {
                $.each(data, function (index, city) {
                    citySelect.append($('<option>', {
                        value: city.name,
                        text: city.name,
                        'data-value': city.id,
                        'data-ship-charge': city.ship_charge
                    }));
                });
            } else {
                console.error("No cities found.");
            }
            if (selectedCity !== '') {
                $('#ship_city').val(selectedCity);
                selectedCity = '';
            }
        }

        // Define a function to clear city options in case of an error or empty response
        function clearCityOptions() {
            $('#ship_city').empty().append($('<option>', {
                value: '',
                text: 'Area*',
                'data-value': '',
                'data-ship-charge': ''
            }));
        }

        $(document).on("change", "#bill_country", function (e) {
            e.preventDefault();
            const phoneCode = $(this).find(':selected').data('phonecode');
            $('#bill_phone_code').val(phoneCode);
        });

        // Handle shipping city change
        $(document).on("change", "#ship_state", function (e) {
            e.preventDefault();
            const selectedStateId = $(this).find(':selected').data('value');
            const daysToAdd = (selectedStateId === 1978) ? 1 : 2;
            const startDate = '+' + daysToAdd + 'd';
            initializeCalendar(startDate);
            fetchCity(selectedStateId);
        });

        // Handle shipping area change
        $(document).on("change", "#ship_city", function (e) {
            e.preventDefault();
            const shippingCharge = $(this).find(':selected').data('ship-charge');
            $('#ship_charge').val(shippingCharge);
        });

        // Handle radio button change for shipping addresses
        $(document).on("change", 'input[type=radio][name=shipping_addresses]', function (e) {
            e.preventDefault();
            const selectedShippingAddress = $(this).val();
            const shippingAddress = shippingObject[selectedShippingAddress];
            $('#selected_shipping_details').hide();
            updateShippingDetails(shippingAddress);
        });

        // Handle edit shipping button click
        $(document).on("click", '.edit-shipping-btn', function (e) {
            e.preventDefault();
            const selectedShippingAddress = $(this).data('address');
            const shippingAddress = shippingObject[selectedShippingAddress];
            updateShippingDetails(shippingAddress);
            $('#selectable_shipping_details').hide();
            $('#selected_shipping_details').show();
        });

        // Handle delete shipping button click
        $(document).on("click", '.delete-shipping-btn', function (e) {
            e.preventDefault();
            const selectedShippingAddress = $(this).data('address');
            confirmDeleteShipping(selectedShippingAddress);
        });

        // Handle add shipping button click
        $(document).on("click", '#add-shipping-btn', function (e) {
            e.preventDefault();
            clearShippingForm();
        });

        // Handle continue to payment button click
        $('#continue_to_payment_btn').click(function (event) {
            event.preventDefault();
            if (validateCheckout()) {
                const form = $(this).closest('form');
                $.ajax({
                    url: "{{ url('/set-delivery') }}",
                    method: 'POST',
                    data: form.serialize(),
                    success: function (response) {
                        if (response.result === true) {
                            window.location.href = '{{ url("/payment") }}';
                        } else {
                            showMessage(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        showMessage(xhr.responseText);
                    }
                });
            }
        });

        // Initialize datepicker and set default shipping state
        function initializeCheckout() {
            const keys = Object.keys(shippingObject);
            keys.length > 0 ? $("input[type=radio][name=shipping_addresses]:first").prop('checked', true).trigger("change") : initializeCalendar('+2d');
        }

        $('#preffered_date').on('changeDate', function () {
            // Get the selected date
            var selectedDate = $(this).datepicker('getDate');
            // Format the selected date as 'yyyy-mm-dd'
            var formattedDate = selectedDate.toLocaleDateString('en-CA', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).split('/').join('-'); // Replace slashes with dashes

            $('#delivery_date').val(formattedDate);
        });

        // Function to initialize datepicker
        function initializeCalendar(initialStartDate) {
            const startDate = initialStartDate !== null ? initialStartDate : '+3d';
            $('#preffered_date').datepicker('destroy');
            $('#preffered_date').datepicker({
                format: "yyyy-mm-dd",
                autoclose: true,
                startDate: startDate
            });
        }

        // Function to update shipping details
        function updateShippingDetails(shippingAddress) {
            $('#ship_state').val(shippingAddress.state).trigger('change');
            setTimeout(function () {
                $('#ship_name').val(shippingAddress.customer_name);
                $('#ship_phone').val(shippingAddress.customer_phone);
                $('#ship_email').val(shippingAddress.customer_email);
                $('#ship_street').val(shippingAddress.street);
                $('#ship_street_number').val(shippingAddress.street_number);
                $('#ship_city').val(shippingAddress.city);
                $('#ship_address_id').val(shippingAddress.id);
                $('#ship_charge').val(shippingAddress.ship_charge);
                $('#default_address').prop('checked', shippingAddress.is_default == 1);
                selectedCity = shippingAddress.city;
            }, 600);
            return true;
        }

        // Function to confirm delete shipping address
        function confirmDeleteShipping(selectedShippingAddress) {
            $.confirm({
                title: 'Delete saved address?',
                content: 'Are you sure you want to delete your saved delivery address',
                theme: 'modern',
                closeIcon: true,
                animation: 'scale',
                type: 'white',
                buttons: {
                    'confirm': {
                        text: 'Yes',
                        btnClass: 'btn-blue',
                        action: function () {
                            deleteShippingAddress(selectedShippingAddress);
                        }
                    },
                    cancel: {
                        text: 'No',
                        action: function () {
                            // Do nothing
                        }
                    }
                }
            });
        }

        // Function to clear shipping form
        function clearShippingForm() {
            $("input[type=radio][name=shipping_addresses]").prop('checked', false);
            $('#ship_name').val('');
            $('#ship_phone').val('');
            $('#ship_email').val('');
            $('#ship_street').val('');
            $('#ship_street_number').val('');
            $('#ship_state').val('').trigger("change");
            $('#ship_city').val('');
            $('#ship_address_id').val('');
            $('#default_address').prop('checked', false);
            $('#selected_shipping_details').show();
        }

        // Function to delete shipping address
        function deleteShippingAddress(addressId) {
            $.ajax({
                url: "{{ url('/delete-delivery-address') }}",
                type: "POST",
                data: {
                    'id': addressId
                },
                dataType: "json",
                beforeSend: function () {
                    // Any actions to be performed before sending the request
                },
                success: function (response) {
                    if (response.result === true) {
                        delete shippingObject[addressId];
                        $("#shipping_addr_" + addressId).remove();
                        const addressCount = Object.keys(shippingObject).length;
                        if (addressCount === 0) {
                            clearShippingForm();
                        }
                    } else {
                        showMessage(response.message);
                    }
                }
            });
        }

        function validateCheckout() {
            $("#countinue_to_payment_btn").hide('slow');

            // Retrieve form field values
            var pickfrmStoreId = $.trim($('#pickfrm_store_id').val());
            var shippingName = $.trim($('#ship_name').val());
            var shippingPhone = $.trim($('#ship_phone').val());
            var shippingEmail = $.trim($('#ship_email').val());
            var shippingCity = $.trim($('#ship_city').val());
            var shippingState = $.trim($('#ship_state').val());
            var deliveryDate = $.trim($('#delivery_date').val());
            var billingName = $.trim($('#bill_name').val());
            var billingPhone = $.trim($('#bill_phone').val());
            var billingEmail = $.trim($('#bill_email').val());
            var billingCountry = $.trim($('#bill_country').val());

            // Initialize error message and error element variables
            var errorMessage = "";
            var errorElement = "";

            // Validation checks
            if (pickFromStoreChecked && pickfrmStoreId == '0') {
                errorMessage = "Please select a store for pickup order.";
                errorElement = 'pickfrm_store_id';
            } else if (!pickFromStoreChecked && !shippingName) {
                errorMessage = "Please enter recipient's name.";
                errorElement = 'ship_name';
            } else if (!pickFromStoreChecked && !shippingPhone) {
                errorMessage = "Please enter recipient's mobile number.";
                errorElement = 'ship_phone';
            } else if (!pickFromStoreChecked && shippingPhone && !isValidJordanianMobile(shippingPhone)) {
                errorMessage = "Please enter a valid Jordanian mobile number (79, 78, or 77 without 0).";
                errorElement = 'ship_phone';
            } else if (!pickFromStoreChecked && shippingEmail && !isValidEmail(shippingEmail)) {
                errorMessage = "Please enter a valid email address.";
                errorElement = 'ship_email';
            } else if (!pickFromStoreChecked && !shippingState) {
                errorMessage = "Please select city.";
                errorElement = 'ship_state';
            } else if (!pickFromStoreChecked && !shippingCity) {
                errorMessage = "Please select area.";
                errorElement = 'ship_city';
            } else if (isDeliverable == '1' && !deliveryDate) {
                errorMessage = "Please enter preferable delivery date.";
                errorElement = 'preffered_date';
            } else if (isGuest && !billingName) {
                errorMessage = "Please enter your name.";
                errorElement = 'bill_name';
            } else if (isGuest && !billingPhone) {
                errorMessage = "Please enter your mobile number.";
                errorElement = 'bill_phone';
            } else if (billingPhone && billingCountry !== 'Jordan' && !isValidMobile(billingPhone)) {
                errorMessage = "Please enter a valid mobile number.";
                errorElement = 'bill_phone';
            } else if (billingPhone && billingCountry === 'Jordan' && !isValidJordanianMobile(billingPhone)) {
                errorMessage = "Please enter a valid Jordanian mobile number (79, 78, or 77 without 0).";
                errorElement = 'bill_phone';
            } else if (billingEmail && !isValidEmail(billingEmail)) {
                errorMessage = "Please enter a valid email address.";
                errorElement = 'bill_email';
            } else if (!$('#terms_checkbox').is(':checked')) {
                errorMessage = "Please check accept terms and condition.";
                errorElement = 'terms_checkbox';
            }

            if (errorMessage) {

                $("#countinue_to_payment_btn").show('slow');
                $('#' + errorElement).focus();
                showMessage(errorMessage);
                return false;
            } else {
                return true;
            }
        }

        $(document).ready(() => {
            // Initialize datepicker and set default shipping state
            initializeCheckout();
        });
    </script>

@endpush