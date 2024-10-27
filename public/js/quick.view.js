let addedToCartProductImage = '';
let addedToCartProductName = '';
let addedToCartProductBrandName = '';
let addedToCartProductQuantity = '';
let addedToCartProductPrice = '';
let isCartPage = 0;
let cartEditProductId = 0;

// quick look
$(document).on("click", ".quick-look", function (e) {
    e.preventDefault();
    var current_element = $(this);
    var currentProduct = $(this).data('product');
    var current_prdct_type = $(this).data('product-type');
    var current_prdct_key = $(this).data('product-key');

    current_element.fadeOut('slow');
    setTimeout(function () {
        //current_element.empty().html('Quick Look');
        current_element.fadeIn('slow');
    }, 1000);
    quickLook(currentProduct, current_prdct_type, current_prdct_key);
});

function quickLook(productId, productType, productKey) {

    var productType = productType !== null ? productType : '';
    var productKey = productKey !== null ? productKey : '';
    $.ajax({
        url: baseUrl + "/fetch-product-detail",
        type: "POST",
        data: {
            'product': productId,
            'productType': productType,
            'productKey': productKey
        },
        dataType: "json",
        success: function (res) {
            $("#gifts_div").hide('slow');
            productsObject = res.product_arr;
            // product name
            $('#product_name_html').text(res.family_name);
            var pdcontent = res.family_desc + '<a id="prdct_view_more" href="" target="_top">Read More</a>';
            $("#product_description_html").html(pdcontent);
            // brand
            $('#brand_name_html').html(res.brand_name);
            addedToCartProductBrandName = res.brand_name;
            //products section
            var colour_variation_html = "", text_variation_html = "", has_gift = "";
            var main_cat_arr = [10, 23];
            var checked_html = '';

            $.each(productsObject, function (pidx, pobj) {
                //if (pobj.stock > 0) {
                has_gift = pobj.has_gift != '0' ? 'has_gift' : '';
                text_variation_html += '<li><input type="radio" name="ql_text_variation" class="radioInput ql-radio-product-text" value="' + pobj.product_id + '" data-type="' + productType + '" data-key="' + productKey + '" /><span class="quantity-text">' + pobj.fam_name + '</span></li>';
                if (main_cat_arr.indexOf(res.main_category) == -1) {
                    colour_variation_html += '<li class="thumb ' + has_gift + '" id="thumb_' + pobj.product_id + '">';
                    if (pobj.has_offer != '') {
                        //pcontent += '<span class="prdct_offer_text">offer</span>';
                    }
                    if (pobj.photo1 != "") {
                        colour_variation_html += '<span class="colour-box"><input type="radio" name="ql_colour_variation" class="ql-radio-product-colour" value="' + pobj.product_id + '" data-type="' + productType + '" data-key="' + productKey + '"  data-title="' + pobj.fam_name + '"/>';
                        colour_variation_html += '<span class="checkmark"><img src="' + pobj.photo1 + '" alt="" /></span>';
                        colour_variation_html += '</span>';
                    }
                    colour_variation_html += '</li>';
                }
                //}
            });
            $(".ql_variations_content_html").html('');
            console.log(main_cat_arr.includes(res.main_category));
            if (main_cat_arr.includes(res.main_category)) {
                $("#ql_texts_content_html").html(text_variation_html);
                $("#ql_variation_name_html").hide();
            } else {
                $("#ql_colours_content_html").html(colour_variation_html);
                $("#ql_variation_name_html").show();
            }
            qlBuildProductContent(Object.keys(productsObject)[0], productType, productKey);
        }
    });

    //$("#messageModal .modal-body h5").html(msg);
}

// combo select
$(document).on("change", ".ql-radio-product-text", function () {
    var checkedItem = $('input[name="ql_text_variation"]:checked');
    var pid = checkedItem.val();
    var ptype = checkedItem.data('type');
    var pkey = checkedItem.data('key');
    if (pid === "") {
        showMessage("Please select a product.");
        qlBuildProductContent();
    } else {
        qlBuildProductContent(pid, ptype, pkey);
    }

});

// combo select
$(document).on("change", ".ql-radio-product-colour", function () {
    var checkedItem = $('input[name="ql_colour_variation"]:checked');
    var pid = checkedItem.val();
    var ptype = checkedItem.data('type');
    var pkey = checkedItem.data('key');
    var ptitle = checkedItem.data('title');
    $("#ql_variation_name_html").html(ptitle);
    if (pid === "") {
        showMessage("Please select a product.");
        qlBuildProductContent();
    } else {
        qlBuildProductContent(pid, ptype, pkey);
    }

});

function qlFetchProductImages(productId, productType) {
    $.ajax({
        url: baseUrl + "/product-images",
        type: "POST",
        data: {
            'product': productId,
            'product_type': productType
        },
        dataType: "json",
        beforeSend: function () {
            //show modal overlay loader
            $('.quick_look_loader').css("display", "block");
        },
        success: function (res) {
//                var htm = '';
//                if (res.length > 0) {
//                    $.each(res, function (i, v) {
//                        htm += '<li>';
//                        htm += '<img class="etalage_thumb_image img-responsive" src="' + v + '" alt="" width="92" height="81" id="bigpic"/> <img class="etalage_source_image img-responsive" src="' + v + '" alt="" />';
//                        htm += '</li>';
//                    });
//                } else {
//                    htm += '<li>';
//                    htm += '<img class="etalage_thumb_image img-responsive" src="' + base_url + 'images/no-img-available.jpg" alt="" width="92" height="81" id="bigpic"/> <img class="etalage_source_image img-responsive" src="' + base_url + 'images/no-img-available.jpg" alt="" />';
//                    htm += '</li>';
//                }
//
//
//                $('#etalage').html(htm);
//                $('#etalage').etalage({
//                    thumb_image_width: 250,
//                    thumb_image_height: 300,
//                    source_image_width: 900,
//                    source_image_height: 1200,
//                    show_hint: true,
//                    autoplay: false,
//                    click_callback: function (image_anchor, instance_id) {
//                        alert('Callback example:\nYou clicked on an image with the anchor: "' + image_anchor + '"\n(in Etalage instance: "' + instance_id + '")');
//                    }
//                });
            addedToCartProductImage = "{{ url('/') }}/public/images/no-img-available.jpg";
            var file_extnt;
            var htm = '<ul class="gc-start gnr3">';
            if (res.length > 0) {
                $.each(res, function (i, v) {
                    switch (v.item_type) {
                        case 'youtube':
                            htm += '<li>';
                            htm += '<a data-gc-type="iframe" href="https://www.youtube.com/embed/' + v.product_pic + '" data-gc-width="640" data-gc-height="390" data-gc-thumbnail="https://img.youtube.com/vi/' + v.product_pic + '/0.jpg"></a>';
                            htm += '</li>';
                            break;
                        case 'video':
                            htm += '<li>';
                            htm += '<a data-gc-type="video" href="https://ikasco.com/moreproductpic/' + v.product_pic + '" data-gc-thumbnail="https://ikasco.com/moreproductpic/' + v.thumb_pic + '"></a>';
                            htm += '</li>';
                            break;
                        default:
                            htm += '<li>';
                            //  htm += '<img class="img-responsive" src="https://ikasco.com/moreproductpic/' + v.product_pic + '" alt="" />';
                            htm += '<img class="img-responsive" src="' + v.aws + '" alt="" />';
                            htm += '</li>';
                            if (i === 0) {
                                addedToCartProductImage = v.aws;
                            }
                            break;
                    }

                });
            } else {
                htm += '<li>';
                htm += '<img class="img-responsive" src="{{ url(' / ') }}/public/images/no-img-available.jpg" alt="" />';
                htm += '</li>';
            }
            htm += '</ul>';

            $('.quick-detail-img').html(htm);
            $('.quick-detail-img ul').glassCase({
                'widthDisplay': 550,
                'heightDisplay': 450,
                'isSlowZoom': true,
                'isSlowLens': true,
                'isHoverShowThumbs': false,
                'nrThumbsPerRow': 5,
                'thumbsPosition': 'bottom',
                'isOverlayFullImage': true,
                'txtImgThumbVideo': ''
            });
            //hide modal overlay loader
            $('.quick_look_loader').css("display", "none");
        }
    });

}

function qlBuildProductContent(product, productType, productKey) {
    var productType = productType !== null ? productType : '';
    var productKey = productKey !== null ? productKey : '';
    // add to cart and save item buttons
    $('#ql_add_tocart_icon').removeClass('fa-check').addClass('fa-shopping-cart');
    $('#save_item_icon').removeClass('fa-check').addClass('fa-heart');

    var currentProduct = '';
    if (product == undefined || product == "" || product == null) {
        //first key of object
        currentProduct = Object.keys(productsObject)[0];

    } else {
        currentProduct = product;
    }
    //active thumb
    //$(".thumb").removeClass("active");
    //$("#thumb_" + currentProduct).addClass("active");

    // selected combo
    //$("#combo").val(currentProduct);
    $('input.ql-radio-product-colour[value="' + currentProduct + '"]').prop('checked', true);
    $('input.ql-radio-product-text[value="' + currentProduct + '"]').prop('checked', true);
    $("#ql_variation_name_html").html(productsObject[currentProduct].fam_name);

    //$('#prdctNumber').text(productsObject[currentProduct].title);
    if (productsObject[currentProduct].dmain_price != '') {
        $('#product_price_html').text(productsObject[currentProduct].main_price + ' JD\'s');
        $('#product_discounted_price_html').text(productsObject[currentProduct].dmain_price + ' JD\'s');
    } else {
        $('#product_price_html').html('');
        $('#product_discounted_price_html').text(productsObject[currentProduct].main_price + ' JD\'s');
    }

    if (productsObject[currentProduct].lylty_pnts > 0) {
        $('.earn_point_span').show();
        $('#earn_point').text(productsObject[currentProduct].lylty_pnts);
    } else {
        $('.earn_point_span').hide();
    }
    var redirectUrl = '{{ url(' / ') }}';
    $('#ql_add_to_cart_btn').attr('data-product', currentProduct);
    $('#ql_add_to_cart_btn').attr('data-product-type', productType);
    $('#ql_add_to_cart_btn').attr('data-product-key', productKey);
    $('#ql_add_to_cart_btn').attr('data-title', productsObject[currentProduct].title + productsObject[currentProduct].fam_name);
    $('#ql_add_to_cart_btn').attr('data-price', productsObject[currentProduct].main_price);
    $('#ql_save_item_btn').attr('data-product', currentProduct);
    $('#ql_save_item_btn').attr('data-product-type', productType);
    $('#share_item_btn').attr('data-url', redirectUrl + 'product/' + productsObject[currentProduct].seo_url);
    $('#share_item_btn').attr('data-url', redirectUrl + 'product/' + productsObject[currentProduct].seo_url);
    $('#share_item_btn').attr('data-title', productsObject[currentProduct].title);
    $('#stockStatus').text('');
    // fbq('track', 'ViewContent', {
    //     content_name: productsObject[currentProduct].title,
    //     content_ids: [currentProduct],
    //     content_type: 'product',
    //     value: productsObject[currentProduct].main_price,
    //     currency: 'JOD'
    // });
    var rhtml = '';
    if (productsObject[currentProduct].count_rating > 0) {
        rhtml = '<div class="flexible-stars quick-look-stars" data-gold="sprite-gold-star" data-silver="sprite-silver-star" data-init="' + productsObject[currentProduct].rating + '" data-isLocked="yes"></div>';
        var review = productsObject[currentProduct].count_rating > 0 ? productsObject[currentProduct].count_rating + ' reviews' : 'No reviews yet'
        rhtml += '<span class="text-span">(' + review + ')</span>';
        //rhtml += ' | <span class="LoveNo"><img src="' + redirectUrl + 'images/save2.svg" id="save" data-toggle="tooltip" data-placement="bottom" title="Loves List"> <span id="quick_look_love">' + productObject[currentProduct].love + '</span> Loves</span> </div>';
    }

    $('.rating_review_html').html(rhtml);

    //initiate star ratings
    $('.quick-look-stars').flexibleStars();

    if (productsObject[currentProduct].stock > 0) {
        $("#ql_product_available_stock").attr('max', productsObject[currentProduct].stock);
        $("#ql_add_to_cart_btn").show('slow');      // show add to cart button if product in stock
    } else {
        $('#stock_availability').text('Out of Stock');
        $("#ql_add_to_cart_btn").hide('slow');      // hide add to cart button if product out of stock
    }


    //offers section
    var ocontent = "";
    if (productsObject[currentProduct].offers != undefined && productsObject[currentProduct].stock > 0) {
        if (productsObject[currentProduct].offers.length > 0) {
            $.each(productsObject[currentProduct].offers, function (idx, obj) {
                ocontent += '<div class="offer">';
                ocontent += '<input type="radio" name="selected-offer" value="' + obj.offer_id + '" class="choose-offers" data-product= "' + currentProduct + '" data-mintype= "' + obj.minimum_type + '" data-minval= "' + obj.minimum_value + '" data-giftqty= "' + obj.gift_quantity + '"/>';
                ocontent += '<div class="offer-content">';
                ocontent += '<div class="offer-title">' + obj.offer_name + '</div>';
                ocontent += '<div class="offer-desc">' + obj.offer_desc + '</div>';
                ocontent += '</div>';
                ocontent += '</div>';
            });
            $("#offers_count").html(productsObject[currentProduct].offers.length);
            $("#offers_content").html(ocontent);
            $("#offers_div").show('slow');
        }
    } else {
        $("#offers_count").html('');
        $("#offers_content").html('');
        $("#offers_div").hide('slow');
    }
    // product description view more link
    var view_more_link = '';
    console.log(productsObject[currentProduct]);
    switch (productType) {
        case'offer':
            view_more_link = productUrl + productsObject[currentProduct].seo_url + '/offer' + '/' + productKey;
            break;
        case'sale':
            view_more_link = productUrl + productsObject[currentProduct].seo_url + '/sale';
            break;
        case'collection':
            view_more_link = productUrl + productsObject[currentProduct].seo_url + '/collection';
            break;
        case'segment':
            view_more_link = productUrl + productsObject[currentProduct].seo_url + '/' + productKey;
            break;
        case'bundle':
            view_more_link = productBundleUrl + productsObject[currentProduct].seo_url;
            break;
        default:
            view_more_link = productUrl + productsObject[currentProduct].seo_url;
            break;
    }
    $("#prdct_view_more").attr("href", view_more_link);

    $("#quick_look_modal").modal("show");

    //showing images
    qlFetchProductImages(currentProduct, productType);
}

//add to cart
$(document).on("click", "#ql_add_to_cart_btn", function (e) {
    e.preventDefault();
    qlAddToCart();
});
function qlAddToCart() {

    var currentProduct = $('#ql_add_to_cart_btn').attr('data-product');
    var current_prdct_type = $('#ql_add_to_cart_btn').attr('data-product-type');
    var current_prdct_key = $('#ql_add_to_cart_btn').attr('data-product-key');
    addedToCartProductName = $('#ql_add_to_cart_btn').attr('data-title');
    addedToCartProductPrice = $('#ql_add_to_cart_btn').attr('data-price');
    addedToCartProductQuantity = $('#ql_product_available_stock').val();
    var offer = $(".choose-offers:checked");
    var offer_id = offer.val();
    //var offer_id = 56;//20/04/2019 apply point offer by default
    var gift = $(".choose-gifts:checked");
    var gift_id = gift.val();

    if (authUserId == '' && productsObject[currentProduct].is_voucher == '1') {
        showMessage('Sorry!Please sign in to add voucher in basket');
        return false;
    }

    if (addedToCartProductQuantity !== undefined && addedToCartProductQuantity > 0) {
        $.ajax({
            url: baseUrl + "/add-to-cart",
            type: "POST",
            data: {
                'product': currentProduct,
                'product_type': current_prdct_type,
                'product_key': current_prdct_key,
                'quantity': addedToCartProductQuantity,
                'offer': offer_id,
                'cart_edit_product': cartEditProductId
            },
            dataType: "json",
            beforeSend: function () {
                $('.alert').fadeOut('slow');
                $('#ql_add_to_cart_btn').addClass('disabled');
                $('#ql_add_tocart_icon').removeClass('fa-shopping-cart');
                $('#ql_add_tocart_icon').addClass('fa-circle-o-notch fa-spin');
            },
            success: function (response) {
                $('#ql_add_to_cart_btn').removeClass('disabled');
                $('#ql_add_tocart_icon').removeClass('fa-circle-o-notch fa-spin');
                $('#ql_add_tocart_icon').addClass('fa-shopping-cart');
                cartEditProductId = 0;
                if (response.result === true) {
                    fbq('track', 'AddToCart', {
                        content_name: addedToCartProductName,
                        content_ids: [currentProduct],
                        content_type: 'product',
                        value: addedToCartProductPrice,
                        currency: 'JOD'
                    });

                    // checking if there is any selected gift for this product
                    if (gift_id !== undefined) {
                        addGiftToCart();
                    }
                    $(".cart_count_html").text(response.data);
                    offer.prop('checked', false);

                    qlShowAddToCartMessage();

                    if (isCartPage === 1) {
                        fetchCart();
                    }
                } else {
                    showMessage(response.message);
                }

            }
        });
    }
}

function qlShowAddToCartMessage() {
    $("#quick_look_modal").modal("hide");
    $("#added_to_cart_image_html").attr('src', addedToCartProductImage);
    $("#added_to_cart_brand_html").html(addedToCartProductBrandName);
    $("#added_to_cart_product_html").html(addedToCartProductName);
    $("#added_to_cart_quantity_html").html(addedToCartProductQuantity);
    $("#added_to_cart_price_html").html(addedToCartProductPrice + ' JD\'s');
    $("#added_to_cart_modal").modal("show");
}

// Increase quantity
$(document).on("click", ".ql-quantity-arrow-plus", function () {
    var inputField = $(this).siblings('.quantity-num');
    var currentValue = parseInt(inputField.val());

    if (currentValue < parseInt(inputField.attr('max'))) {
        inputField.val(currentValue + 1);
    }
});

// Decrease quantity
$(document).on("click", ".ql-quantity-arrow-minus", function () {
    var inputField = $(this).siblings('.quantity-num');
    var currentValue = parseInt(inputField.val());

    if (currentValue > parseInt(inputField.attr('min'))) {
        inputField.val(currentValue - 1);
    }
});

$(document).on("click", "#ql_save_item_btn", function (e) {
    e.preventDefault();
    qlAddToWishlist();
});

function qlAddToWishlist() {

    var currentProduct = $('#ql_save_item_btn').attr('data-product');

    if (authUserId == '' && productsObject[currentProduct].is_voucher == '1') {
        showMessage('Sorry! Please sign in to save this item in your loves list.');
        return false;
    }

    $.ajax({
            url: baseUrl + "/add-to-wishlist",
            type: "POST",
            data: {
                'product': currentProduct,
            },
            dataType: "json",
            beforeSend: function () {
                $('#ql_save_item_btn').addClass('disabled');
                $('#ql_save_item_icon').removeClass('fa-heart');
                $('#ql_save_item_icon').addClass('fa-circle-o-notch fa-spin');
            },
            success: function (response) {
                $('#ql_save_item_btn').removeClass('disabled');
                $('#ql_save_item_icon').removeClass('fa-circle-o-notch fa-spin');
                $('#ql_save_item_icon').addClass('fa-heart');
                if (response.result === true) {
                    $('#ql_save_item_icon').css('color', '#f95040');
                    $(".wish_count_html").text(response.data);
                } else {
                    showMessage(response.message);
                }

            }
        });
}