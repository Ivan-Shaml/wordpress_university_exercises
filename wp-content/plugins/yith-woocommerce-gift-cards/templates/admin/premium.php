<style>
	.landing{
		margin-right: 15px;
		border: 1px solid #d8d8d8;
		border-top: 0;
	}
    .section{
	    font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
	    background: #fafafa;
    }
    .section h1{
        text-align: center;
        text-transform: uppercase;
        color: #445674;
        font-size: 35px;
        font-weight: 700;
        line-height: normal;
        display: inline-block;
        width: 100%;
        margin: 50px 0 0;
    }
    .section .section-title h2{
        vertical-align: middle;
        padding: 0;
	    line-height: normal;
        font-size: 24px;
        font-weight: 700;
        color: #445674;
        text-transform: uppercase;
	    background: none;
	    border: none;
	    text-align: center;
    }
    .section p{
        margin: 15px 0;
	    font-size: 19px;
	    line-height: 32px;
	    font-weight: 300;
	    text-align: center;
    }
    .section ul li{
        margin-bottom: 4px;
    }
    .section.section-cta{
	    background: #fff;
    }
    .cta-container,
    .landing-container{
	    display: flex;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        padding: 30px 0;
	    align-items: center;
    }
    .landing-container-wide{
	    flex-direction: column;
    }
    .cta-container{
	    display: block;
	    max-width: 860px;
    }
    .landing-container:after{
        display: block;
        clear: both;
        content: '';
    }
    .landing-container .col-1,
    .landing-container .col-2{
        float: left;
        box-sizing: border-box;
        padding: 0 15px;
    }
    .landing-container .col-1{
	    width: 58.33333333%;
    }
    .landing-container .col-2{
	    width: 41.66666667%;
    }
    .landing-container .col-1 img,
    .landing-container .col-2 img,
    .landing-container .col-wide img{
        max-width: 100%;
    }
    .wishlist-cta{
        color: #4b4b4b;
        border-radius: 10px;
        padding: 30px 25px;
	    display: flex;
	    align-items: center;
	    justify-content: space-between;
	    width: 100%;
	    box-sizing: border-box;
    }
    .wishlist-cta:after{
        content: '';
        display: block;
        clear: both;
    }
    .wishlist-cta p{
        margin: 10px 0;
	    line-height: 1.5em;
        display: inline-block;
	    text-align: left;
    }
    .wishlist-cta a.button{
        border-radius: 25px;
        float: right;
        background: #e09004;
        box-shadow: none;
        outline: none;
        color: #fff;
        position: relative;
        padding: 10px 50px 8px;
	    text-align: center;
	    text-transform: uppercase;
	    font-weight: 600;
	    font-size: 20px;
		line-height: normal;
	    border: none;
    }
    .wishlist-cta a.button:hover,
    .wishlist-cta a.button:active,
    .wp-core-ui .yith-plugin-ui .wishlist-cta a.button:focus{
        color: #fff;
        background: #d28704;
        box-shadow: none;
        outline: none;
    }
    .wishlist-cta .highlight{
        text-transform: uppercase;
        background: none;
        font-weight: 500;
    }

    @media (max-width: 991px){
	    .landing-container{
		    display: block;
		    padding: 50px 0 30px;
	    }

	    .landing-container .col-1,
	    .landing-container .col-2{
		    float: none;
		    width: 100%;
	    }

	    .wishlist-cta{
		    display: block;
		    text-align: center;
	    }

	    .wishlist-cta p{
		    text-align: center;
		    display: block;
		    margin-bottom: 30px;
	    }
	    .wishlist-cta a.button{
		    float: none;
		    display: inline-block;
	    }
    }
    .mejs-controls {
        display: none !important;
        visibility: hidden !important;
    }
</style>
<div class="landing">
    <div class="section section-cta section-odd">
        <div class="cta-container">
            <div class="wishlist-cta">
                <p><?php echo sprintf (esc_html__('Upgrade to the %1$spremium version%2$s%3$sof %1$sYITH WooCommerce Gift Cards%2$s to benefit from all features!','yith-woocommerce-gift-cards'),'<span class="highlight">','</span>','<br/>');?></p>
                <a href="<?php echo YWGC_Plugin_FW_Loader::get_instance()->get_premium_landing_uri(); ?>" target="_blank" class="wishlist-cta-button button btn">
                   <?php _e('Upgrade','yith-woocommerce-gift-cards');?>
                </a>
            </div>
        </div>
    </div>

    <div class="section section-even clear" style="background-color: white">
        <h1><?php _e('Premium Features', 'yith-woocommerce-gift-cards');?></h1>

        <div class="landing-container">
            <div class="col-2">
	            <div class="section-title">
		            <h2><?php _e('Gift cards, the most efficient way to increase sales.', 'yith-woocommerce-gift-cards');?></h2>
	            </div>
	            <p><?php _e( 'A gift card makes life for both your customer and the person who receives it a lot easier. This is the reason why gift cards have become more and more popular, to such an extent that 98% of shops – and not only virtual shops – use them to increase sales volume and to loyalize customers. Thanks to YITH WooCommerce Gift Cards you will be able to create digital or printable gift cards of any amount and give your customers the possibility to customize them to make a special gift for their friends and family.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
            <div class="col-1">
	            <img src="https://yithemes.com/wp-content/uploads/2019/11/gift-card-message.jpeg" />
            </div>
        </div>
    </div>

    <div class="section section-odd clear">
        <div class="landing-container">
	        <div class="col-1">
                <video class="wp-video-shortcode" id="video-772130-1_html5" loop="1" autoplay="1" preload="metadata" style="background-color: #fafafa; width: 890.5px; height: 691.99px;" width="1140" height="886"><source type="video/mp4" src="https://yithemes.com/wp-content/uploads/2019/11/VIRTUAL_GIFT_CARD_8.mp4?_=1"></video>
	        </div>
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Provide a modern and usable interface to build the perfect gift card ', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'We have updated the gift card page layout to guarantee a more modern look and a usable experience for your customers. Customizing gift cards has never been so quick and easy.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Set up an effective image gallery with unlimited categories', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Create unlimited design categories to organize the gift card images: Christmas, Birthdays, Friendship, Family... ', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
	        <div class="col-1">
		        <img src="https://yithemes.com/wp-content/uploads/2019/11/003.png" />
	        </div>
        </div>
    </div>

    <div class="section section-odd clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/004.png"/>
            </div>
	        <div class="col-2">
		        <div class="section-title">
			        <h2><?php _e('Let your customers upload their images or photos and build a unique gift card!', 'yith-woocommerce-gift-cards');?></h2>
		        </div>
		        <p><?php _e( 'With the custom uploader, your customers can upload an image or photo to send a special and customized gift card to their loved ones.', 'yith-woocommerce-gift-cards' ) ?></p>
	        </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Set fixed amounts or let the customer choose a custom amount ', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Offer a versatile gift card system allowing your customers to enter a custom amount or set specific amounts to choose from.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
	        <div class="col-1">
		        <img src="https://yithemes.com/wp-content/uploads/2019/11/005.png" />
	        </div>
        </div>
    </div>


    <div class="section section-odd clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/006.png" />
            </div>
	        <div class="col-2">
		        <div class="section-title">
			        <h2><?php _e('Multiple recipient & scheduling delivery date options ', 'yith-woocommerce-gift-cards');?></h2>
		        </div>
		        <p><?php _e( 'Let your customers choose if they want to send multiple gift cards to different recipients or choose a specific delivery date.', 'yith-woocommerce-gift-cards' ) ?></p>
	        </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('An advanced management of all aspects of gift cards purchased in your site.', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Set an expiration date for your gift cards, apply discounts, manage stock, check the gift card delivery status, suspend a gift card.
Now you can enjoy a full and powerful control of each aspect of your gift cards.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
	        <div class="col-1">
		        <img src="https://yithemes.com/wp-content/uploads/2019/11/007.png" />
	        </div>
        </div>
    </div>

    <div class="section section-odd clear">
        <div class="landing-container landing-container-wide">
            <div class="col-wide">
                <div class="section-title">
                    <h2><?php _e('A wide range of notifications and e-mail options ', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Your customers can receive a notification when the gift card is sent to the recipient and when the gift card has been used.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
            <div class="col-wide">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/001-2.png" />
            </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('An advanced customization of cart and checkout page options', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Choose where to display the gift card coupon form in cart and checkout page, customize the design and allow your customers to review and edit wrong delivery info directly from the cart page.
From version 3.0 users can enter a gift card code in the default WooCommerce coupon form, to avoid a duplicated input field and improve usability. ', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
	        <div class="col-1">
		        <img src="https://yithemes.com/wp-content/uploads/2019/11/cart.png"  />
	        </div>
        </div>
    </div>

    <div class="section section-odd clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/11.png" />
            </div>
	        <div class="col-2">
		        <div class="section-title">
			        <h2><?php _e('Create and sell gift cards to print and send physically, for those who love to touch their gifts.', 'yith-woocommerce-gift-cards');?></h2>
		        </div>
		        <p><?php _e( 'If virtual gift cards are a limitation for you, sell gift cards to print and send them physically to your customers.', 'yith-woocommerce-gift-cards' ) ?></p>
	        </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Allow your customers to send a gift card and suggest a specific product of your store ', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'With the “Gift this product “ option your customer can buy a gift card with the same value as the product he likes and suggest the product to the recipient.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/008-scaled.png" />
            </div>
        </div>
    </div>

    <div class="section section-odd clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/12/smart-coupons.png" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Switch easily from Smart Coupons to YITH Gift Card', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Thanks to the integration, you can dismiss to use Smart Coupons and convert Store Credit / Gift Certificate coupons created with it, into new gift cards. ', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Add a QR Code for your gift cards', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Thanks to the new QR integration, you can now add a QR code on your gift cards and use them even more easily and quickly.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/009.png" />
            </div>
        </div>
    </div>


    <div class="section section-odd clear">
        <div class="landing-container">
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2019/11/010.png" />
            </div>
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Create and edit gift cards from backend, without proceeding to checkout or creating an order in your shop. ', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'A quick process to create gift card codes directly from backend, without creating products and without creating orders. The best solution if you need to create or edit physical gift cards or create gift card codes in a fast and simple way.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
        </div>
    </div>

    <div class="section section-even clear">
        <div class="landing-container">
            <div class="col-2">
                <div class="section-title">
                    <h2><?php _e('Allow your customers to send a virtual gift card or download a printable PDF version', 'yith-woocommerce-gift-cards');?></h2>
                </div>
                <p><?php _e( 'Do your customers want to print the gift card at home and put it into a beautiful envelope? No problem. They can get a PDF file and print it easily: they\'ll have a beautiful printed gift card to hand over.', 'yith-woocommerce-gift-cards' ) ?></p>
            </div>
            <div class="col-1">
                <img src="https://yithemes.com/wp-content/uploads/2015/10/printable-pdf-giftcard.png" />
            </div>
        </div>
    </div>

    <div class="section section-cta section-odd">
        <div class="cta-container">
            <div class="wishlist-cta">
                <p><?php echo sprintf (esc_html__('Upgrade to the %1$spremium version%2$s%3$sof %1$sYITH WooCommerce Gift Cards%2$s to benefit from all features!','yith-woocommerce-gift-cards'),'<span class="highlight">','</span>','<br/>');?></p>
                <a href="<?php echo YWGC_Plugin_FW_Loader::get_instance()->get_premium_landing_uri()?>" target="_blank" class="wishlist-cta-button button btn">
                    <?php _e( 'Upgrade', 'yith-woocommerce-gift-cards' ); ?>
                </a>
            </div>
        </div>
    </div>
</div>
