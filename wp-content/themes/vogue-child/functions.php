<?php
/**
 * Vogue Child functions and definitions
 */

/**
 * Enqueue parent theme style
 */
function vogue_child_enqueue_styles() {
    wp_enqueue_style( 'vogue-parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'vogue_child_enqueue_styles' );




/**
 * 21 Jan 2018: MF: New function to override WooCommerce cart expiry to 12 hours
 */
add_filter('wc_session_expiring', 'so_26545001_filter_session_expiring' );

function so_26545001_filter_session_expiring($seconds) {
    return 60 * 60 * 11; // 11 hours
}

add_filter('wc_session_expiration', 'so_26545001_filter_session_expired' );

function so_26545001_filter_session_expired($seconds) {
    return 60 * 60 * 12; // 12 hours
}


/**
 * 26 Jan 2018: MF: Remove prices from home page
 */
function im380_control_woocommerce_price_display ( $price ) {
	$queried_object = get_queried_object();

	if ( $queried_object ) {
	    $post_slug = $queried_object->post_name;
	}
	if ( is_front_page() ) {
		return '';
	} elseif ($post_slug == "services") {
		return '';
	} elseif ($post_slug == "events") {
		return '';
	} else {
		return $price;
	}
};

add_filter( 'woocommerce_get_price_html', 'im380_control_woocommerce_price_display');

/**
 * 27 Jan 2018: MF: Customise Account page
 */
class WC_Custom_My_Account_Tabs extends WC_Query {
	/**
	 * Adds main filters and actions and inits the endpoints.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );

		if ( ! is_admin() ) {
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_filter( 'woocommerce_account_menu_items', array( $this, 'edit_navigation' ) );
			add_action( 'woocommerce_account_custom_endpoint', array( $this, 'add_custom_tab_content' ) );
		}

		$this->init_query_vars();
	}

	/**
	 * Inits the query vars for WooCommerce
	 */
	public function init_query_vars() {
		$this->query_vars = array(
			'custom' => 'custom',
		);
	}

	/**
	 * Edits the navigation in the page My Account, removing, editing and adding new tabs.
	 *
	 * @param  array          $items
	 * @return array
	 */
	public function edit_navigation( $items ) {
		// Remove tabs
		unset( $items['downloads'] );

		// Rename tabs
		//$items['edit-address'] = 'My Custom Title';

		// Add tabs
		//$items['custom'] = 'Custom Tab';

		return $items;
	}

	/**
	 * Prints the custom tab content from a template in theme-name/woocommerce/myaccount/
	 */
	//public function add_custom_tab_content() {
	//	wc_get_template( 'myaccount/custom.php', array(), '', get_stylesheet() . 'woocommerce/' );
	//}
}

new WC_Custom_My_Account_Tabs();


/**
 * 04 Feb 2018: MF: Override "coupon" text to refer to "Introductory Offer code" throughout checkout pages and process
 */
// rename the "Have a Coupon?" message on the checkout page
function woocommerce_rename_coupon_message_on_checkout() {
	return ' <a href="#" class="showcoupon">' . __( 'Click here to enter your Offer Code', 'woocommerce' ) . '</a>';
}
add_filter( 'woocommerce_checkout_coupon_message', 'woocommerce_rename_coupon_message_on_checkout' );

// rename the coupon field on the checkout page
function woocommerce_rename_coupon_field_on_checkout( $translated_text, $text, $text_domain ) {
	// bail if not modifying frontend woocommerce text
	if ( is_admin() || 'woocommerce' !== $text_domain ) {
		return $translated_text;
	} else {
		switch ( $text ) {
			case 'Coupon code' :
				$translated_text = 'Offer code';
				break;
			case 'Apply coupon' :
				$translated_text = 'Apply code';
				break;
			case 'Coupon has been removed.' :
				$translated_text = 'Offer Code has been removed.';
				break;
		}
	}
	return $translated_text;
}
add_filter( 'gettext', 'woocommerce_rename_coupon_field_on_checkout', 10, 3 );


// define the woocommerce_coupon_message callback
// full list of msg_code values is in /wp-content/plugins/woocommerce/includes/class-wc-coupon.php
function filter_woocommerce_coupon_message( $msg, $msg_code, $instance )
{
	switch ( $msg_code ) {
		case "200" :
    		$msg = "Offer Code successfully applied";
    	break;
    	// 201 code doesn't seem to be used and is now part of wc_ajax in function remove_coupon() - don't know how to override this
    	case "201" :
		    $msg = "Offer Code successfully removed";
    	break;
    }
    return $msg;
};
add_filter( 'woocommerce_coupon_message', 'filter_woocommerce_coupon_message', 10, 3 );

// define the woocommerce_coupon_error callback
// full list of msg_code values is in /wp-content/plugins/woocommerce/includes/class-wc-coupon.php
function filter_woocommerce_coupon_error( $msg, $msg_code, $instance )
{
	switch ( $msg_code ) {
    	case "103" :
			$msg = "Offer Code already applied!";
    	break;
    	case "105" :
		    $msg = "Offer Code does not exist!";
    	break;
		case "111" :
    		$msg = "Please enter an Offer Code!";
    	break;
    }
    return $msg;
};
add_filter( 'woocommerce_coupon_error', 'filter_woocommerce_coupon_error', 10, 3 );


// define the woocommerce_coupon_cart_label callback
function filter_woocommerce_cart_totals_coupon_label( $sprintf, $coupon )
{
    $sprintf = "Offer code: {$coupon->code}";
    return $sprintf;
};
add_filter( 'woocommerce_cart_totals_coupon_label', 'filter_woocommerce_cart_totals_coupon_label', 10, 3 );


/**
 * 04 Feb 2018: MF: Re-organise Single Product Page
 */

remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

add_action( 'woocommerce_before_add_to_cart_form', 'woocommerce_template_single_price', 10 );

//* Remove scheduling form
add_action( 'wp_head', 'bizzwoo_remove_appointment_form', 0 );
function bizzwoo_remove_appointment_form() {
	global $wc_appointment_cart_manager;
	remove_action( 'woocommerce_appointment_add_to_cart', array( $wc_appointment_cart_manager, 'add_to_cart' ), 30 );
}
//* Register new tab
add_filter( 'woocommerce_product_tabs', 'bizzwoo_new_product_booking_tab' );
function bizzwoo_new_product_booking_tab( $tabs ) {

	if ( is_product() ) {
		$product = get_product( get_the_ID() );

		if ( $product->is_type( 'appointment' ) ) {

			// Adds the new tab
			$tabs['test_tab'] = array(
				'title' 	=> __( 'Book an Apppointment', 'woocommerce' ),
				'priority' 	=> 50,
				'callback' 	=> 'bizzwoo_new_product_booking_tab_content'
			);

		}
	}
	return $tabs;
}
//* New tab content
function bizzwoo_new_product_booking_tab_content() {
	global $product;
	// Prepare form
	$appointment_form = new WC_Appointment_Form( $product );

	// Get template
	wc_get_template( 'single-product/add-to-cart/appointment.php', array( 'appointment_form' => $appointment_form ), 'woocommerce-appointments', WC_APPOINTMENTS_TEMPLATE_PATH );
}


//* Remove the description tab
add_filter( 'woocommerce_product_tabs', 'bizzwoo_remove_product_tabs', 98 );
function bizzwoo_remove_product_tabs( $tabs ) {
    if ( is_product() ) {
		$product = get_product( get_the_ID() );

		if ( $product->is_type( 'appointment' ) ) {
			unset( $tabs['description'] );
		}
	}
    return $tabs;
}

//* Add the description instead of scheduling form
add_action( 'woocommerce_appointment_add_to_cart', 'bizzwoo_description_insteadof_form' );
function bizzwoo_description_insteadof_form() {
	if ( is_product() ) {
		$product = get_product( get_the_ID() );

		if ( $product->is_type( 'appointment' ) ) {
			the_content();
		}
	}
}

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 ); // remove categories
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 ); // remove upsell products
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 ); // remove related products

/**
 * 25 Feb 2018: MF: Loosen default WooCommerce password strength
 * Strength Settings
 * 3 = Strong (default)
 * 2 = Medium
 * 1 = Weak
 * 0 = Very Weak / Anything
 */
 
add_filter( 'woocommerce_min_password_strength', 'im380_change_wc_password_strength');
function im380_change_wc_password_strength() {
    $strength=1;
    return intval($strength);
}

add_filter( 'password_hint', 'im380_change_password_hint' );
function im380_change_password_hint( $hint ) {
    $hint = 'Hint: Passwords should be at least 6 characters long.  We advise you to create a Strong password.  To do this create a password that is at least 12 characters long and use: upper and lower case letters, numbers, and symbols.';
	return $hint;
}

/**
 * 22 Apr 2018: MF: Re-organise Single Product Page for specific Product Categories
 */

function im380_category_specific_single_prod_tabs( $tabs ) {
    if ( is_product() ) {
		global $post;
		$terms = get_the_terms( $post->ID, 'product_cat' );
		foreach ($terms as $term) {
			$product_cat_slugs[] = $term->slug;
		}
		
		if ( in_array('products', $product_cat_slugs ) ) {
			unset( $tabs['description'] );
		} else {
			// Do nothing
		}
	}
    return $tabs;
}

//add_filter( 'woocommerce_product_tabs', 'im380_category_specific_single_prod_tabs', 98 );


function im380_new_delivery_details_tab( $tabs ) {



	// Adds the new tab
	$tabs['test_tab'] = array(
			'title' 	=> __( 'Delivery Details', 'woocommerce' ),
			'priority' 	=> 50,
			'callback' 	=> 'im380_new_delivery_details_tab_content'
			);

	return $tabs;
}

//* New tab content
function im380_new_delivery_details_tab_content() {
	echo '<p>All orders are normally despatched within 2 working days.</p>';
	echo '<p>For UK deliveries, items should arrive within 2-5 working days of despatch.</p>';
	echo '<p>Deliveries to all other countries should arrive within 5-15 working days</p>';
}

function im380_wc_custom_single_product() {
	if ( is_product() ) {
		global $post;
		$terms = get_the_terms( $post->ID, 'product_cat' );
		foreach ($terms as $term) {
			$product_cat_slugs[] = $term->slug;
		}
		
		if ( in_array('products', $product_cat_slugs ) ) {
			//add_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 5);
			//add_filter( 'woocommerce_product_tabs', 'im380_category_specific_single_prod_tabs', 98 );
			remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
			add_action('woocommerce_single_product_summary', 'woocommerce_output_product_data_tabs', 25);
			add_filter( 'woocommerce_product_tabs', 'im380_new_delivery_details_tab' );
		} else {
			// Do nothing
		}
	}
}

add_action('template_redirect', 'im380_wc_custom_single_product');

/**
 * 26 Apr 2018: MF: Hide decimales in prices except at checkout
 */
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );

add_filter( 'woocommerce_product_add_to_cart_text', 'customizing_add_to_cart_button_text', 10, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', 'customizing_add_to_cart_button_text', 10, 2 );
function customizing_add_to_cart_button_text( $button_text, $product ) {

    $sold_out = __( "SOLD OUT", "woocommerce" );

    $availability = $product->get_availability();
    $stock_status = $availability['class'];

    // Only for variable products on single product pages
    if ( $product->is_type('variable') && is_product() )
    {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('select').blur( function(){
            if( '' != $('input.variation_id').val() && $('p.stock').hasClass('out-of-stock') )
                $('button.single_add_to_cart_button').html('<?php echo $sold_out; ?>');
            else 
                $('button.single_add_to_cart_button').html('<?php echo $button_text; ?>');

            console.log($('input.variation_id').val());
        });
    });
    </script>
    <?php
    }
    // For all other cases (not a variable product on single product pages)
    elseif ( ! $product->is_type('variable') && ! is_product() ) 
    {
        if($stock_status == 'out-of-stock')
            $button_text = $sold_out;
        //else
        //    $button_text.=' ('.$stock_status.')';
    }
    return $button_text;
}
