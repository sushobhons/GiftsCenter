@extends('frontend.layouts.master')
@section('title','Thank You | Gifts Center')
@section('description','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('keywords','Fragrances, Cosmetics, Watches, Jewelry | Gifts Center')
@section('custom-styles')
    <link href="{{asset('public/css/order-placed.css')}}" rel="stylesheet"/>
    <script type='text/javascript'
            src='//platform-api.sharethis.com/js/sharethis.js#property=5ca5b4dafbd80b0011b66725&product=inline-share-buttons'
            async='async'></script>
    <script>
        fbq('track', 'Purchase', {
            value: {{ number_format($payableAmount, 2, '.', '') }},
            currency: 'JOD',
        });
    </script>
    <script>
        gtag('event', 'purchase', {
            "transaction_id": "{{ $invoiceNumber }}",
            "affiliation": "Gifts Center",
            "value": {{ number_format($payableAmountUSD, 2, '.', '') }},
            "currency": "USD",
            "tax": 0,
            "shipping": 0,
            "items": [
                    @foreach($cartArray as $item)
                {
                    "id": "{{ $item['product_no'] }}",
                    "name": "{{ $item['product_name'] }}",
                    "brand": "{{ $item['brand_name'] }}",
                    "list_position": {{ $loop->index + 1 }}, // Position in the list
                    "quantity": {{ $item['product_qty'] }},
                    "price": "{{ $item['product_price_usd'] }}"
                }@if(!$loop->last),@endif
                @endforeach
            ]
        });
        gtag('event', 'conversion', {
            'send_to': 'AW-866317736/QGPlCIabhdYZEKjri50D',
            'value': {{ number_format($payableAmountUSD, 2, '.', '') }},
            'currency': 'USD',
            'transaction_id': '{{ $invoiceNumber }}'
        });
    </script>
@endsection
@section('main-content')
    <div class="order-placed-sec">
        <img src="{{asset('public/img/blue-tick-img.png')}}" alt="" class="blue-tick-img"/>
        <h3>Congratulations!</h3>
        <p>Your order has been successfully placed</p>
        <div id="rating-div">
            <h4>Rating <span>+{{$ratingReward}} Points</span></h4>
            <h5>How Satisfied Were You?</h5>
            <ul class="rating-list">
                <li><span class="text-span">Not Good</span></li>
                @for ($i = 1; $i <= 5; $i++)
                    <li>
                        <span class="num-span">{{ $i }}</span>
                        <div class="form-group form-check form-radio-check">
                            <label class="form-check-label">
                                <input class="form-radio-input" type="radio" name="rating"
                                       value="{{ $i }}" {{ $i == 5 ? 'checked' : '' }}>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </li>
                @endfor
                <li><span class="text-span">Great</span></li>
            </ul>
            <ul class="excellent-list web-hide">
                <li>Not Good</li>
                <li>Great</li>
            </ul>
            <ul class="openion-list">
                <li>
                    <textarea cols="" rows="" class="form-control text-control" placeholder="Your Opinion" name="review"
                              id="review"></textarea>
                </li>
                <li>
                    <a class="btn btn-primary" id="rating-review-btn" href="">Submit</a>
                </li>
            </ul>
        </div>
        <ul class="openion-list" id="orders-div" style="display: none;">
            <li><a class="btn btn-primary" href="{{ url('/your-orders') }}">View
                    your order</a></li>
        </ul>
{{--        <h4>Share your puchase <span>+{{$shareReward}} Points</span></h4>--}}
{{--        <div class="sharethis-inline-share-buttons"--}}
{{--             data-url="{{ url('/purchase/' . $invoiceNumber) }}"></div>--}}
        {{--        <ul class="share-list">--}}
        {{--            <li>--}}
        {{--                <a href="#" class="sharethis-inline-share-buttons"><img src="{{asset('public/img/facebook-icon.svg')}}" alt="" /></a>--}}
        {{--            </li>--}}
        {{--            <li>--}}
        {{--                <a href="#" class="sharethis-inline-share-buttons"><img src="{{asset('public/img/twitter-icon.svg')}}" alt="" /></a>--}}
        {{--            </li>--}}
        {{--            <li>--}}
        {{--                <a href="#" class="sharethis-inline-share-buttons"><img src="{{asset('public/img/whatsapp-icon.svg')}}" alt="" /></a>--}}
        {{--            </li>--}}
        {{--        </ul>--}}
    </div>
@endsection
@push('styles')

@endpush
@push('scripts')
    <script>
        const invoiceNumber = '{{ $invoiceNumber }}';
        const customerId = '{{ $customerId }}';
        const isGuest = '{{ $isGuest }}';

        const validateHearAboutUs = () => {
            const user = userCode;
            const source = $('#hear-aboutus').val().trim();

            if (!source) {
                showMessage('Please provide a source.');
                return;
            }

            $.ajax({
                url: `${baseUrl}ajax/add-survey.php`,
                type: 'POST',
                data: {source, user},
                dataType: 'json',
                success: (response) => {
                    if (response.result === true) {
                        showMessage('Thank you for letting us know how you found us.');
                    } else {
                        showMessage(response.message);
                    }
                },
                error: (xhr, status, error) => {
                    showMessage('An error occurred while processing your request.');
                }
            });
        }

        const orderReview = () => {
            const rating = $("input[name='rating']:checked").val().trim();
            const review = $('#review').val().trim();

            let msgTxt = "";
            let errorElement = "";

            if (rating === "") {
                msgTxt = "Please rate your satisfaction with us.";
                errorElement = $('#rating');
            } else if (review === "") {
                msgTxt = "Your opinion is important to us.";
                errorElement = $('#review');
            }

            if (msgTxt !== "") {
                if (errorElement !== '') {
                    errorElement.focus();
                }
                showMessage(msgTxt);
            } else {
                $.ajax({
                    url: '{{ url('/order-review') }}',
                    type: "POST",
                    data: {
                        'rating': rating,
                        'review': review,
                        'invoiceNumber': invoiceNumber,
                        'customerId': customerId
                    },
                    dataType: "json",
                    beforeSend: function () {
                        $('#rating_review_btn').fadeOut('slow');
                    },
                    success: function (response) {
                        if (response.result === true) {
                            $("#rating-div").fadeOut('slow');
                            if (isGuest != '1') {
                                $("#orders-div").fadeIn('slow');
                            }else{
                                location.href = '{{ url("/") }}';
                            }
                            showMessage('Thank you for your review. Enjoy your purchase.');
                        } else {
                            showMessage(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        showMessage('An error occurred while processing your request.');
                    }
                });
            }
        };

        const orderShare = () => {
            $.ajax({
                url: '{{ url('/order-share') }}',
                type: "POST",
                data: {
                    'customerId': customerId,
                    'invoiceNumber': invoiceNumber
                },
                dataType: "json",
                beforeSend: function () {
                },
                success: function (response) {
                    if (response.result === true) {
                        $("#share-div").fadeOut('slow');
                    } else {
                        showMessage(response.message);
                    }
                },
                error: function (xhr, status, error) {
                    showMessage('An error occurred while processing your request.');
                }
            });
        };

        $(document).ready(() => {
            // $('.earn-points').on('click', function (e) {
            //     e.preventDefault();
            //     const pointType = $(this).data('type');
            //     if (pointType === 'refer-a-friend') {
            //         if (userCode !== '') {
            //             const shareFriendAlert = $.confirm({
            //                 title: 'Refer a Friend!',
            //                 theme: 'modern',
            //                 closeIcon: true,
            //                 animation: 'scale',
            //                 type: 'white',
            //                 content: `
            //                             <form action="" class="formName">
            //                                 <div class="form-group">
            //                                     <label>Please provide friend's email or phone number</label>
            //                                     <input type="text" placeholder="Email or phone number" class="share-friend form-control" required />
            //                                     <p>Note: Your friend should use this email or phone number during signup to get referral points</p>
            //                                 </div>
            //                             </form>
            //                         `,
            //                 buttons: {
            //                     formSubmit: {
            //                         text: 'Refer',
            //                         btnClass: 'btn-blue',
            //                         action: function () {
            //                             const shareFriend = this.$content.find('.share-friend').val();
            //                             if (!shareFriend) {
            //                                 showMessage('Please provide an email or phone number.');
            //                                 return false;
            //                             } else if (shareFriend !== '' && (!isNaN(shareFriend)) && !(validateRFPhone(shareFriend))) {
            //                                 showMessage('Please enter a valid phone number (9627XXXXXXXX).');
            //                                 return false;
            //                             } else if (shareFriend !== '' && (isNaN(shareFriend)) && !(validateEmail(shareFriend))) {
            //                                 showMessage('Please enter a valid email address.');
            //                                 return false;
            //                             }
            //                             $.ajax({
            //                                 url: `${baseUrl}ajax/share-friend.php`,
            //                                 type: 'POST',
            //                                 data: {
            //                                     'item': shareFriend,
            //                                 },
            //                                 dataType: 'json',
            //                                 beforeSend: () => {},
            //                                 success: (response) => {
            //                                     if (response.result === true) {
            //                                         shareFriendAlert.close();
            //                                         showMessage(response.message);
            //                                     } else {
            //                                         showMessage(response.message);
            //                                     }
            //                                 }
            //                             });
            //                             return false;
            //                         }
            //                     },
            //                     cancel: {
            //                         text: 'Cancel',
            //                         btnClass: 'btn-red',
            //                         action: () => {
            //                             shareFriendAlert.close();
            //                         }
            //                     },
            //                 },
            //                 onContentReady: () => {
            //                     // Bind events
            //                     const jc = this;
            //                     this.$content.find('form').on('submit', (e) => {
            //                         // If the user submits the form by pressing enter in the field.
            //                         e.preventDefault();
            //                         jc.$$formSubmit.trigger('click'); // reference the button and click it
            //                     });
            //                 }
            //             });
            //         } else {
            //             showMessage('Please sign in to refer a friend.');
            //         }
            //     }
            // });

            $('#hear-aboutus').on('change', () => {
                if ($('#hear-aboutus').val().trim() !== '') {
                    validateHearAboutUs();
                }
            });
            $('#rating-review-btn').on('click', function (e) {
                e.preventDefault();
                orderReview();
            });

            $('.sharethis-inline-share-buttons').on('click', () => {
                e.preventDefault();
                orderShare();
            });
        });
    </script>

@endpush
