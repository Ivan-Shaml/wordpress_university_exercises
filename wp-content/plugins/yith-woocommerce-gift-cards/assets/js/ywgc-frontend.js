;(function ($) {

    if (typeof ywgc_data === "undefined") {
        return;
    }

    //Manage the picture changed event
    $(document).on('ywgc-picture-changed', function ( event, type, id ) {


            $( '.ywgc-template-design' ).remove();
            $( '.ywgc-design-type' ).remove();


            $( 'form.cart' ).append('<input type="hidden" class="ywgc-design-type" name="ywgc-design-type" value="'+ type +'">');

            $( 'form.cart' ).append('<input type="hidden" class="ywgc-template-design" name="ywgc-template-design" value="' + id + '">');
        }
    );

    $(function () {

        var datePicker = $(".datepicker");
        datePicker.datepicker({dateFormat: ywgc_data.date_format, minDate: +1, maxDate: "+1Y"});

        $('.ywgc-choose-design-preview .ywgc-design-list li:first-child .ywgc-preset-image img').click();

    });


    show_hide_add_to_cart_button();


    /**
     * Manage the selected design images
     */
    var wc_gallery_image = $( '.product-type-gift-card .woocommerce-product-gallery__image a' );
    var wc_gallery_image_placeholder = $( '.product-type-gift-card .woocommerce-product-gallery__image--placeholder' );

    $( '.ywgc-preset-image.ywgc-default-product-image img' ).addClass( 'selected_design_image' );

    $(document).on( 'click', 'form.gift-cards_form.cart .ywgc-preset-image img', function (e) {
        e.preventDefault();

        var id = $(this).parent().data('design-id');

        $( '.ywgc-preset-image img' ).removeClass( 'selected_design_image' );
        $( this ).addClass( 'selected_design_image' );

        $(document).trigger('ywgc-picture-changed', ['template', id]);

        if ( $( this ).hasClass( 'selected_design_image' ) ){

            var image_url = $(this).parent().data('design-url');
            var srcset = $( this ).attr('srcset');
            var html_content = '<img src="' + image_url + '" class="wp-post-image size-full" alt="" data-caption="" data-src="' + image_url + '" data-large_image="' + image_url + '" data-large_image_width="1024" data-large_image_height="1024" sizes="(max-width: 600px) 100vw, 600px" ' + srcset + 'width="600" height="600">';

            if ( wc_gallery_image.length != 0 ){
                $( '.product-type-gift-card .woocommerce-product-gallery__image a' ).html(html_content);
            }
            else{
                $( '.woocommerce-product-gallery__image--placeholder img' ).remove;
                wc_gallery_image_placeholder.html(html_content);
            }

        }
    });

    $(document).on( 'click', '.ywgc-preset-image img', function (e) {
        e.preventDefault();

        var id = $(this).parent().data('design-id');

        $( '.ywgc-preset-image img' ).removeClass( 'selected_design_image' );
        $( '.ywgc-preset-image' ).removeClass( 'selected_image_parent' );

        $( this ).addClass( 'selected_design_image' );
        $( this ).parent().addClass('selected_image_parent');

        $(document).trigger('ywgc-picture-changed', ['template', id]);

    });


    /**
     * Display the gift card form cart/checkout
     * */
    $( document ).on( 'click', 'a.ywgc-show-giftcard', show_gift_card_form );

    function show_gift_card_form() {
        $( '.ywgc_enter_code' ).slideToggle( 300, function () {
            if ( ! $( '.yith_wc_gift_card_blank_brightness' ).length ){

                $( '.ywgc_enter_code' ).find( ':input:eq( 0 )' ).focus();

                $(".ywgc_enter_code").keyup( function( event ) {
                    if ( event.keyCode === 13 ) {
                        $( "button.ywgc_apply_gift_card_button" ).click();
                    }
                });
            }

        });
        return false;
    }

    /** Show the edit gift card button */
    $("button.ywgc-do-edit").css("display", "inline");


    function update_gift_card_amount(amount) {
        //copy the button value to the preview price
        $('.ywgc-form-preview-amount').text( amount );
    }

    function show_gift_card_editor(val) {
        $('button.gift_card_add_to_cart_button').attr('disabled', !val);
    }

    /** This code manage the amount buttons actions */
    function show_hide_add_to_cart_button() {

        var amount_buttons = $('button.ywgc-amount-buttons');
        var amount_buttons_hidden_inputs = $('input.ywgc-amount-buttons');
        var first_amount_button = $('button.ywgc-amount-buttons:first');

        //Auto-select the 1st amount button
        first_amount_button.addClass('selected_button');
        if ( first_amount_button.hasClass('selected_button') )
            $('input.ywgc-amount-buttons:first').attr('name', 'gift_amounts');

        //copy the 1st button value to the preview price
        $('.ywgc-form-preview-amount').text( first_amount_button.data('wc-price') );

        // select a button
        amount_buttons.on('click', function (e) {
            e.preventDefault();

            amount_buttons.removeClass('selected_button');
            amount_buttons_hidden_inputs.removeClass('selected_button');
            amount_buttons_hidden_inputs.removeAttr('name');
            $(this).addClass('selected_button');
            $(this).next().addClass('selected_button');

        });


        var amount = first_amount_button.data('wc-price');

        //Manage the amount button selection
        amount_buttons.on( 'click', function (e) {
            e.preventDefault();

            amount_buttons_hidden_inputs.removeAttr('name');

            if (!amount_buttons.data('price')) {
                show_gift_card_editor(false);
            }
            else {
                show_gift_card_editor(true);
                amount = $('input.selected_button').data('wc-price');
                $('input.selected_button').attr('name', 'gift_amounts');
            }
            update_gift_card_amount(amount);


        });

    }

    $(document).on('input', '#ywgc-edit-message', function (e) {
        $(".ywgc-card-message").html($('#ywgc-edit-message').val());
    });

    $(document).on('change', '.gift-cards-list select', function (e) {
        show_hide_add_to_cart_button();
    });

    $(document).on('click', 'a.customize-gift-card', function (e) {
        e.preventDefault();
        $('div.summary.entry-summary').after('<div class="ywgc-customizer"></div>');
    });


    function set_giftcard_value(value) {
        $("div.ywgc-card-amount span.amount").html(value);
    }

    $('.variations_form.cart').on('found_variation', function (ev, variation) {
        if (typeof variation !== "undefined") {
            var price_html = variation.price_html != '' ? $(variation.price_html).html() : $(".product-type-variable").find(".woocommerce-Price-amount.amount").first().html();
            set_giftcard_value(price_html);

        }
    });


    function show_edit_gift_cards(element, visible) {
        var container = $(element).closest("div.ywgc-gift-card-content");
        var edit_container = container.find("div.ywgc-gift-card-edit-details");
        var details_container = container.find("div.ywgc-gift-card-details");

        if (visible) {
            //go to edit
            edit_container.removeClass("ywgc-hide");
            edit_container.addClass("ywgc-show");

            details_container.removeClass("ywgc-show");
            details_container.addClass("ywgc-hide");
        }
        else {
            //go to details
            edit_container.removeClass("ywgc-show");
            edit_container.addClass("ywgc-hide");

            details_container.removeClass("ywgc-hide");
            details_container.addClass("ywgc-show");
        }
    }

    $(document).on('click', 'button.ywgc-apply-edit', function (e) {

        var clicked_element = $(this);

        var container = clicked_element.closest("div.ywgc-gift-card-content");

        var sender = container.find('input[name="ywgc-edit-sender"]').val();
        var recipient = container.find('input[name="ywgc-edit-recipient"]').val();
        var message = container.find('textarea[name="ywgc-edit-message"]').val();
        var item_id = container.find('input[name="ywgc-item-id"]').val();

        var gift_card_element = container.find('input[name="ywgc-gift-card-id"]');
        var gift_card_id = gift_card_element.val();

        //  Apply changes, if apply button was clicked
        if (clicked_element.hasClass("apply")) {
            var data = {
                'action': 'edit_gift_card',
                'gift_card_id': gift_card_id,
                'item_id': item_id,
                'sender': sender,
                'recipient': recipient,
                'message': message
            };

            container.block({
                message: null,
                overlayCSS: {
                    background: "#fff url(" + ywgc_data.loader + ") no-repeat center",
                    opacity: .6
                }
            });

            $.post(ywgc_data.ajax_url, data, function (response) {
                if (response.code > 0) {
                    container.find("span.ywgc-sender").text(sender);
                    container.find("span.ywgc-recipient").text(recipient);
                    container.find("span.ywgc-message").text(message);

                    if (response.code == 2) {
                        gift_card_element.val(response.values.new_id);
                    }
                }

                container.unblock();

                //go to details
                show_edit_gift_cards(clicked_element, false);
            });
        }
    });

    $(document).on('click', 'button.ywgc-cancel-edit', function (e) {

        var clicked_element = $(this);

        //go to details
        show_edit_gift_cards(clicked_element, false);
    });

    $(document).on('click', 'button.ywgc-do-edit', function (e) {

        var clicked_element = $(this);
        //go to edit
        show_edit_gift_cards(clicked_element, true);
    });

    $(document).on('click', '.ywgc-gift-card-content a.edit-details', function (e) {
        e.preventDefault();
        $(this).addClass('ywgc-hide');
        $('div.ywgc-gift-card-details').toggleClass('ywgc-hide');
    });


    $('.ywgc-single-recipient input[name="ywgc-recipient-email[]"]').each(function (i, obj) {
        $(this).on('input', function () {
            $(this).closest('.ywgc-single-recipient').find('.ywgc-bad-email-format').remove();
        });
    });

    function validateEmail(email) {
        var test_email = new RegExp('^[A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,}$', 'i');
        return test_email.test(email);
    }

    $(document).on('submit', '.gift-cards_form', function (e) {
        var can_submit = true;
        $('.ywgc-single-recipient input[name="ywgc-recipient-email[]"]').each(function (i, obj) {

            if ($(this).val() && !validateEmail($(this).val())) {
                $(this).closest('.ywgc-single-recipient').find('.ywgc-bad-email-format').remove();
                $(this).after('<span class="ywgc-bad-email-format">' + ywgc_data.email_bad_format + '</span>');
                can_submit = false;
            }
        });
        if (!can_submit) {
            e.preventDefault();
        }
    });
    /** Manage the WooCommerce 2.6 changes in the cart template
     * with AJAX
     * @since 1.4.0
     */

    $(document).on(
        'click',
        'a.ywgc-remove-gift-card ',
        remove_gift_card_code);

    function remove_gift_card_code(evt) {
        evt.preventDefault();
        var $table = $(evt.currentTarget).parents('table');
        var gift_card_code = $(evt.currentTarget).data('gift-card-code');

        block($table);

        var data = {
            security: ywgc_data.gift_card_nonce,
            code: gift_card_code,
            action: 'ywgc_remove_gift_card_code'
        };

        $.ajax({
            type: 'POST',
            url: ywgc_data.ajax_url,
            data: data,
            dataType: 'html',
            success: function (response) {
                show_notice(response);
                $(document.body).trigger('removed_gift_card');
                unblock($table);
            },
            complete: function () {
                update_cart_totals();
            }
        });
    }

    /**
     * Apply the gift card code the same way WooCommerce do for Coupon code
     *
     * @param {JQuery Object} $form The cart form.
     */
    $( document ).on( 'click', 'button.ywgc_apply_gift_card_button', function ( e ) {
        e.preventDefault();
        var parent = $( this ).closest( 'div.ywgc_enter_code' );
        block( parent );

        var $text_field = parent.find( 'input[ name="gift_card_code" ]' );
        var gift_card_code = $text_field.val();

        var data = {
            security: ywgc_data.gift_card_nonce,
            code: gift_card_code,
            action: 'ywgc_apply_gift_card_code'
        };

        $.ajax({
            type: 'POST',
            url: ywgc_data.ajax_url,
            data: data,
            dataType: 'html',
            success: function ( response ) {
                show_notice( response );
                $( document.body ).trigger( 'applied_gift_card' );
            },
            complete: function () {

                unblock( parent );
                $text_field.val( '' );

                update_cart_totals();
            }
        });
    });

    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var block = function ($node) {
        $node.addClass('processing').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });
    };

    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unblock = function ($node) {
        $node.removeClass('processing').unblock();
    };

    /**
     * Gets a url for a given AJAX endpoint.
     *
     * @param {String} endpoint The AJAX Endpoint
     * @return {String} The URL to use for the request
     */
    var get_url = function (endpoint) {
        return ywgc_data.wc_ajax_url.toString().replace(
            '%%endpoint%%',
            endpoint
        );
    };

    /**
     * Clear previous notices and shows new one above form.
     *
     * @param {Object} The Notice HTML Element in string or object form.
     */
    var show_notice = function ( html_element ) {
        $( '.woocommerce-error, .woocommerce-message' ).remove();
        $( ywgc_data.notice_target ).after( html_element );
        if ( $( '.ywgc_have_code' ).length )
            $( '.ywgc_enter_code' ).slideUp( '300' );
    };

    /**
     * Update the cart after something has changed.
     */
    function update_cart_totals() {
        block($('div.cart_totals'));

        $.ajax({
            url: get_url('get_cart_totals'),
            dataType: 'html',
            success: function (response) {
                $('div.cart_totals').replaceWith(response);
            }
        });

        $(document.body).trigger('update_checkout');
    }

    /**
     * Integration with YITH Quick View and some third party themes
     */
    $(document).on('qv_loader_stop yit_quick_view_loaded flatsome_quickview', function () {

        show_hide_add_to_cart_button();

    });


    /**
     * Add new gift card button
     */
    $(document).on( 'click', 'button.yith-add-new-gc-my-account-button', function (e) {
        e.preventDefault();
        $( this ).parent().prev('.form-link-gift-card-to-user').toggle( 'slow' );
    });


    /**
     * manage recipient and sender fields to display them automatically in the preview
     */
    var recipient_name_input = $( '.ywgc-recipient-name input' );
    recipient_name_input.on('change keyup', function (e) {
        e.preventDefault();
        var recipient_name = recipient_name_input.val();
        $('.ywgc-form-preview-to-content').text( recipient_name );

    });

    var sender_name_input = $( '.ywgc-sender-name input' );
    sender_name_input.on('change keyup', function (e) {
        e.preventDefault();
        var sender_name = sender_name_input.val();
        $('.ywgc-form-preview-from-content').text( sender_name );
    });

    var message_input = $( '.ywgc-message textarea' );
    message_input.on('change keyup', function (e) {
        e.preventDefault();
        var message = message_input.val();
        $('.ywgc-form-preview-message').text( message );
    });



})(jQuery);
