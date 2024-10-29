<?php

//  admin class
//


if ( ! defined( 'ABSPATH' ) )
	exit;

require_once BA_EVENT_PLUGIN_DIR . '/classes/customers-list.php';

///// admin class for manage options

class BA_Event_admin
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueued_assets' ) );
        add_filter( 'custom_menu_order', array( $this, 'pms_submenu_order'));
        //add_filter( 'submenu_file', array( $this, 'submenu_current' ));
        
        add_filter( 'manage_booking_posts_columns', array( $this, 'booking_table_head'));
        add_action( 'manage_booking_posts_custom_column', array( $this, 'booking_table_content'), 10, 2 );

        add_filter( 'posts_where', array( $this, 'search_where' ));
        add_filter( 'posts_join', array( $this, 'search_join' ));

        add_action( 'wp_ajax_save_admin_notes', array( $this, 'save_admin_notes_callback'));

        add_action( 'current_screen', array( $this, 'current_screen_callback'));
        add_filter( 'post_updated_messages', array( $this, 'booking_updated_messages'));

        //////////// $markers_urls ///////
       $this->markers_urls[1] = 'css/img/pointer_1.png';
       $this->markers_urls[2] = 'css/img/pointer_2.png';
       $this->markers_urls[3] = 'css/img/pointer_3.png';
       $this->markers_urls[4] = 'css/img/pointer_4.png';
       $this->markers_urls[5] = 'css/img/pointer_5.png';
       $this->markers_urls[6] = 'css/img/pointer_6.png';
       $this->markers_urls[7] = 'css/img/pointer_7.png';
    }

function enqueued_assets() {
         wp_enqueue_style( 'wp-color-picker');
         wp_enqueue_script( 'wp-color-picker');
        if(function_exists( 'wp_enqueue_media' )){
         wp_enqueue_media();
         } else {
           wp_enqueue_style('thickbox');
           wp_enqueue_script('media-upload');
           wp_enqueue_script('thickbox');
           }

     wp_enqueue_script( 'ba-event-admin-js', plugins_url( "admin/js/event-admin.js", BA_EVENT_PLUGIN ), array('jquery'), '1.0', true );
     wp_localize_script( 'ba-event-admin-js', 'lst', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce('lst-nonce')
         )
        );

      wp_enqueue_style('fontawesome2', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css', '', '4.6.3', 'all');
     
     wp_enqueue_style( 'ba-event-style', plugins_url( "css/event.css", BA_EVENT_PLUGIN ));
     
     wp_enqueue_style( 'ba-event-admin-style', plugins_url( "admin/css/event-admin.css", BA_EVENT_PLUGIN ));
             
}

    /**
     * Add options page
     */
    function add_plugin_page()
    {
        // This page will be under "Settings"
          add_menu_page(
            __('BA Event Settings', BA_EVENT_TEXTDOMAIN),
            __('BA Event Settings', BA_EVENT_TEXTDOMAIN),
            'manage_options',
            'ba-event-settings',
            array( $this, 'create_admin_page' ),
            '',
            26
        );

        add_submenu_page( 'edit.php?post_type=booking', __('Central Reservation Office (CRO)', BA_EVENT_TEXTDOMAIN), __('Central Reservation Office (CRO)', BA_EVENT_TEXTDOMAIN), 'manage_options', 'cro', array( $this, 'create_cro_page' ));

        add_submenu_page( 'edit.php?post_type=booking', __('My Customers', BA_EVENT_TEXTDOMAIN), __('My Customers', BA_EVENT_TEXTDOMAIN), 'manage_options', 'customers', array( $this, 'create_customers_page' ));

    }   

///////////////////////////
function pms_submenu_order( $menu_ord ){
    global $submenu;

    // Enable the next line to inspect the $submenu values
    // echo '<pre>'.print_r($submenu,true).'</pre>';

    $arr = array();
    $arr[] = $submenu['edit.php?post_type=booking'][11];
    $arr[] = $submenu['edit.php?post_type=booking'][10];
    $arr[] = $submenu['edit.php?post_type=booking'][5];
    $arr[] = $submenu['edit.php?post_type=booking'][12];
    $submenu['edit.php?post_type=booking'] = $arr;

    return $menu_ord;
}

function submenu_current($submenu_file){
    global $current_screen;
    if( 'booking_page_cro' == $current_screen->base && isset( $_GET['page'] )) {
         if ( $_GET['page'] == 'cro')
         return 'edit.php?post_type=booking&page=cro';
    }
    return $submenu_file;
}

/////////////////////////////////

function search_join ($join){
    global $pagenow, $wpdb;
    if ( isset( $_GET['s'] )){
    if ( is_admin() && $pagenow=='edit.php' && $_GET['post_type']=='booking' && $_GET['s'] != '') {
        $join .='LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
     }
    }
    return $join;
}

function search_where( $where ){
    global $pagenow, $wpdb;
    if ( isset( $_GET['s'] )){
    if ( is_admin() && $pagenow=='edit.php' && $_GET['post_type']=='booking' && $_GET['s'] != '') {
       // $start_date = $_GET['s'];
        $where = preg_replace(
       "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
       "(".$wpdb->posts.".post_title LIKE $1) OR ((".$wpdb->postmeta.".meta_key = 'range_from') AND (".$wpdb->postmeta.".meta_value LIKE $1))", $where );
     }
    }
    return $where;
}

////////////////////////////

function booking_updated_messages($messages){
   global $post, $post_ID;

  $messages['booking'] = array(
    0 => '', // Unused. Messages start at index 1.
    1 => __('Reservation updated.'),
    2 => __('Custom field updated.'),
    3 => __('Custom field deleted.'),
    4 => __('Reservation updated.'),
    /* translators: %s: date and time of the revision */
    5 => isset($_GET['revision']) ? sprintf( __('Reservation restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
    6 => __('Reservation saved.'),
    7 => __('Reservation saved.'),
    8 => __('Reservation submitted.'),
    9 => sprintf( __('Reservation scheduled for: <strong>%1$s</strong>.'),
      // translators: Publish box date format, see http://php.net/date
      date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )),
    10 => __('Reservation draft updated.'),
  );
  return $messages;
}

/////////////////////////
function current_screen_callback($screen) {
    if( is_object($screen) && $screen->post_type == 'booking' ) {
        add_filter('gettext', array( $this, 'wps_translation'), 10, 3);
    }
}

function wps_translation($translation, $text, $domain) {
        $translations = get_translations_for_domain( $domain);
        if ( $text == 'Published on: <b>%1$s</b>') {
            return $translations->translate( 'Created on: <b>%1$s</b>' );
        }
        if ( $text == 'Publish <b>immediately</b>') {
            return $translations->translate( 'Save <b>immediately</b>' );
        }
         if ( $text == 'Publish') {
            return $translations->translate( 'Save' );
        }
    return $translation;
}

/////////////////////

function booking_table_head( $defaults ) {
    global $BA_Event_var;
    $defaults['date_created']   = __('Date of Booking', BA_EVENT_TEXTDOMAIN);
    $defaults['event']  = __('Event title', BA_EVENT_TEXTDOMAIN);          
    $defaults['event_date']    = __('Event Date', BA_EVENT_TEXTDOMAIN);
    $defaults['event_time']   = __('Event Time', BA_EVENT_TEXTDOMAIN);
    $defaults['tickets']   = __('Tickets', BA_EVENT_TEXTDOMAIN);
    $defaults['price'] = __('Total amount, ', BA_EVENT_TEXTDOMAIN).$BA_Event_var->settings['currency'];

    unset($defaults['date']);
    return $defaults;
}

///////////////////////////////////

function booking_table_content( $column_name, $post_id ) {
    global $BA_Event_var;
    if ($column_name == 'event') {
      $app_id = get_post_meta( $post_id, '_booking_event', true );  
      echo  get_the_title($app_id);
    }
    if ($column_name == 'date_created') {
      if (get_post_meta( $post_id, '_booking_created', true ))
    echo date( get_option("date_format"), get_post_meta( $post_id, '_booking_created', true ));
    }
    if ($column_name == 'event_date') {
      if (get_post_meta( $post_id, '_booking_date_timestamp', true ))
    echo date( get_option("date_format"), get_post_meta( $post_id, '_booking_date_timestamp', true ));
    }
    if ($column_name == 'event_time') {
      if (get_post_meta( $post_id, '_booking_event_time', true ))
    echo date( get_option("time_format"), get_post_meta( $post_id, '_booking_event_time', true ));
    }
    
    if ($column_name == 'tickets') {
        $app_id = get_post_meta( $post_id, '_booking_event', true );
        echo $BA_Event_var->get_tickets_list($app_id, get_post_meta( $post_id, '_booking_event_guests', true ));
    }

    if ($column_name == 'price') {
    echo $BA_Event_var->format_currency(get_post_meta( $post_id, '_booking_price', true ));
    }

}

//////////////// create_customers_page

function create_customers_page(){
     $this->options = get_option( 'event_settings' );
     $this->customers_obj = new BA_Event_Customers_List();
     ?>
        <div class="wrap">
            <h2><?php echo __('My Customers', BA_EVENT_TEXTDOMAIN); ?></h2>

        <div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<div id="post-body-content">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
							<?php
						 	$this->customers_obj->prepare_items();
						 	$this->customers_obj->display(); ?>
						</form>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>

        </div>
        <?php

}

/////////////////////////

function save_admin_notes_callback(){
   $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : 0;
   $text = isset($_POST['text']) ? $_POST['text'] : 0;
   $output = '';

   if (($user_id)&&($text)){
      update_user_meta($user_id, '_admin_notes', $text);
      $output = $text;
   }
   echo $output;
   wp_die();
}

//////////////////////

function create_cro_page(){
    
    Global $BA_Event_var;
        // Set class property
        $this->options = get_option( 'event_settings' );
        
        ?>
        <div class="wrap">
            <h2><?php echo __('Central Reservation Office (CRO)', BA_EVENT_TEXTDOMAIN); ?></h2>
        </div>
        <?php

        echo $BA_Event_var->get_event_calendar_block(date('Y-m-01'), 0).'
        <div id="cro_footer">
        <a href="'.admin_url( 'post-new.php?post_type=booking' ).'">'.__('Add new reservation', BA_EVENT_TEXTDOMAIN).'</a>
        <a href="'.admin_url( 'edit.php?post_type=booking' ).'">'.__('View all reservations', BA_EVENT_TEXTDOMAIN).'</a>
        </div>';

}

///////////////////////////////////////    

    /**
     * Options page callback
     */
    function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'event_settings' );
        ?>
        <div class="wrap">
            <h2><?php echo __('BA Event Settings', BA_EVENT_TEXTDOMAIN); ?></h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'ba-event-settings' );
                do_settings_sections( 'ba-event-settings' );
                submit_button();
            ?>
            </form>
        </div>

        <script>
(function( $ ) {
	// Add Color Picker to all inputs that have 'color-field' class
	$(function() {
	$('.color-field').wpColorPicker();
	});
})( jQuery );
       </script>

        <?php
    }
////////////////////////////////////////////////
///////////////////////////////////////////////
////////////////////////////////////////////////
    /**
     * Register and add settings
     */
    function page_init()
    {
        register_setting(
            'ba-event-settings', // Option group
            'event_settings', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        ///////// General

        add_settings_section(
            'setting_section_1', // ID
            __('General',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info1' ), // Callback
            'ba-event-settings' // Page
        );
        
         add_settings_field(
            'date_format', // ID
            __('Date format',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'date_format_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_post_type', // ID
            __('Event Post Type (post type from the plugin is selected by default)',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_post_type' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_address_active', // ID
            __('Add Address field to Event?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_address_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_slider_active', // ID
            __('Add Slideshow to Event?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_slider_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_features_active', // ID
            __('Add Features to Event?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_features_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_testi_active', // ID
            __('Add Testimonials to Event?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_testi_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_faq_active', // ID
            __('Add FAQ to Event?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_faq_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_avcal_active', // ID
            __('Add Booking Calendar to Event?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_avcal_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        add_settings_field(
            'event_show_tickets_count', // ID
            __('Show tickets count on the Booking Calendar?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'event_show_tickets_count' ), // Callback
            'ba-event-settings', // Page
            'setting_section_1' // Section
        );
        
        ///////////////////////
        
        add_settings_section(
            'setting_section_color', // ID
            __('Color settings', BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info1' ), // Callback
            'ba-event-settings' // Page
        );

        add_settings_field(
            'color_button', // ID
            __('Main buttons background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_button', 'color' => '#e36f22') // Args array
        );
        
        add_settings_field(
            'color_widget_price_bg', // ID
            __('Widget price background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_widget_price_bg', 'color' => '#e36f22') // Args array
        );
        
        add_settings_field(
            'color_main_cal_month_bg', // ID
            __('Main Calendar month selection background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_month_bg', 'color' => '#7f6e52') // Args array
        );
        
        add_settings_field(
            'color_main_cal_month_c', // ID
            __('Main Calendar month selection color',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_month_c', 'color' => '#ffffff') // Args array
        );
        
        add_settings_field(
            'color_main_cal_event_bg', // ID
            __('Main Calendar event selection background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_event_bg', 'color' => '#fffacd') // Args array
        );
        
        add_settings_field(
            'color_main_cal_event_c', // ID
            __('Main Calendar event selection color',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_event_c', 'color' => '#757575') // Args array
        );
        
        add_settings_field(
            'color_main_cal_wd_bg', // ID
            __('Main Calendar week days background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_wd_bg', 'color' => '#d2b48c') // Args array
        );
        
        add_settings_field(
            'color_main_cal_ad_bg', // ID
            __('Main Calendar available date background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_ad_bg', 'color' => '#fffacd') // Args array
        );
        
        add_settings_field(
            'color_main_cal_nad_bg', // ID
            __('Main Calendar not available date background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_nad_bg', 'color' => '#f5f5dc') // Args array
        );
        
        add_settings_field(
            'color_main_cal_event_time', // ID
            __('Main Calendar event time color',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_main_cal_event_time', 'color' => '#e36f22') // Args array
        );
        
        add_settings_field(
            'color_booking_cal_ad_bg', // ID
            __('Booking Calendar available date background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_booking_cal_ad_bg', 'color' => '#93c949') // Args array
        );
        
        add_settings_field(
            'color_booking_cal_nad_bg', // ID
            __('Booking Calendar not available date background',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'color_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'color_booking_cal_nad_bg', 'color' => '#dadada') // Args array
        );
        
        add_settings_field(
            'features_list_img', // ID
            __('Checkmark image for features list',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'img_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_color',  // Section
            array('option' => 'features_list_img') // Args array
        );
        
        ///////// Google

        add_settings_section(
            'setting_section_2', // ID
            __('Google map',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info2' ), // Callback
            'ba-event-settings' // Page
        );

        add_settings_field(
            'google_map_active', // ID
            __('Show google map on Event page?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'google_map_active_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_2' // Section
        );

        add_settings_field(
            'google_api', // ID
            __('Google API key (see ',BA_EVENT_TEXTDOMAIN).'<a href="https://developers.google.com/maps/documentation/embed/get-api-key" target="blank">Google Maps API docs</a>'.__(' for details).',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'google_api_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_2' // Section
        );

        add_settings_field(
            'map_zoom', // ID
            __('Map zoom',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'map_zoom_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_2' // Section
        );

        add_settings_field(
            'map_marker', // ID
            __('Select map marker',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'map_marker_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_2' // Section
        );
        
        ////////// E-mails
        add_settings_section(
            'setting_section_3', // ID
            __('E-mails',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info3' ), // Callback
            'ba-event-settings' // Page
        );
        
        add_settings_field(
            'confirm_email_from_address', // ID
            __('Confirmation email From address (info@your-domain.com)',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'confirm_email_from_address' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3' // Section
        );
        
        add_settings_field(
            'confirm_email_from_name', // ID
            __('Confirmation email From name',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'confirm_email_from_name' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3' // Section
        );
        
        add_settings_field(
            'confirm_email_subject', // ID
            __('Confirmation email subject',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'confirm_email_subject_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3' // Section
        );
        
        add_settings_field(
            'confirm_email_header_image', // ID
            __('Upload header image',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'img_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3', // Section
            array('option' => 'confirm_email_header_image')
        );
        
        add_settings_field(
            'confirm_email_title', // ID
            __('Confirmation email title',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'textarea_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3', // Section
            array('option' => 'confirm_email_title')
        );

        add_settings_field(
            'confirm_email_header', // ID
            __('Confirmation email body',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'confirm_email_header_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3' // Section
        );
        
        add_settings_field(
            'confirm_email_footer', // ID
            __('Confirmation email footer text',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'textarea_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3', // Section
            array('option' => 'confirm_email_footer')
        );
        
        add_settings_field(
            'confirm_email_footer_image', // ID
            __('Upload footer image',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'img_field_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_3', // Section
            array('option' => 'confirm_email_footer_image')
        );

        ///////// Payment

        add_settings_section(
            'setting_section_4', // ID
            __('Payment settings',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info4' ), // Callback
            'ba-event-settings' // Page
        );

        add_settings_field(
            'tax', // ID
            __('Taxes, % (will be added to prices)',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'tax_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_4' // Section
        );

        add_settings_field(
            'currency', // ID
            __('Currency symbol ($, &euro;, etc.)',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'currency_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_4' // Section
        );

        add_settings_field(
            'currency_code', // ID
            __('Currency 3 letters code (ISO 4217: USD, EUR, etc.)',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'currency_code_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_4' // Section
        );

        add_settings_field(
            'currency_place', // ID
            __('Place currency symbol',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'currency_place_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_4' // Section
        );
        
        do_action('ba_event_options');

        ///////// Booking Modes

        add_settings_section(
            'setting_section_5', // ID
            __('Booking Modes',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info5' ), // Callback
            'ba-event-settings' // Page
        );

        add_settings_field(
            'cash_activate', // ID
            __('Book now. Pay Later: Customers do not have to pay online payment to make reservation.', BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'cash_activate_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_5' // Section
        );

        add_settings_field(
            'payment_mode2', // ID
            __('Deposit to Guarantee the Reservation: Customers pay online deposit to guarantee the reservation.',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'payment_mode2_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_5' // Section
        );

        add_settings_field(
            'payment_mode3', // ID
            __('Pre-Paid Reservation: Customers pay online full amount of reservation.',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'payment_mode3_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_5' // Section
        );
        
        add_settings_field(
            'deposit_amount', // ID
            __('Deposit amount (% from total)', BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'deposit_amount_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_5' // Section
        );
        
        do_action('ba_event_options_payment');

        ///////// Booking settings

        add_settings_section(
            'setting_section_9', // ID
            __('Booking settings',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_section_info9' ), // Callback
            'ba-event-settings' // Page
        );

        add_settings_field(
            'add_accept_term', // ID
            __('Add "I have read and accept the Terms and Conditions" required field to booking form?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'add_accept_term_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_9' // Section
        );

        add_settings_field(
            'add_accept_term_adds', // ID
            __('Add new required field to booking form?',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'add_accept_term_adds_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_9' // Section
        );

        add_settings_field(
            'print_button', // ID
            __('Add Print button to Confirmation page',BA_EVENT_TEXTDOMAIN), // Title
            array( $this, 'print_button_callback' ), // Callback
            'ba-event-settings', // Page
            'setting_section_9' // Section
        );
        /////////////////////  
}

////////////////////////////////

/**
* Sanitize each setting field as needed
*
* @param array $input Contains all settings fields as array keys
*/
function sanitize( $input ){
    
        $new_input = array();
        
        $new_input['date_format'] = intval($input['date_format']);
        
        $new_input['event_post_type'] = sanitize_text_field($input['event_post_type']);
        $new_input['features_list_img'] = sanitize_text_field($input['features_list_img']);
        
        $new_input['event_address_active'] = isset($input['event_address_active']) ? intval($input['event_address_active']) : '';
        $new_input['event_features_active'] = isset($input['event_features_active']) ? intval($input['event_features_active']) : '';
        $new_input['event_slider_active'] = isset($input['event_slider_active']) ? intval($input['event_slider_active']) : '';
        $new_input['event_testi_active'] = isset($input['event_testi_active']) ? intval($input['event_testi_active']) : '';
        $new_input['event_faq_active'] = isset($input['event_faq_active']) ? intval($input['event_faq_active']) : '';
        $new_input['event_avcal_active'] = isset($input['event_avcal_active']) ? intval($input['event_avcal_active']) : '';  
        $new_input['event_show_tickets_count'] = isset($input['event_show_tickets_count']) ? intval($input['event_show_tickets_count']) : '';

        $new_input['cash_activate'] = isset($input['cash_activate']) ? intval($input['cash_activate']) : 0;
        $new_input['payment_mode2'] = isset($input['payment_mode2']) ? intval($input['payment_mode2']) : 0;
        $new_input['payment_mode3'] = isset($input['payment_mode3']) ? intval($input['payment_mode3']) : 0;

        if ( !$new_input['cash_activate'] && !$new_input['payment_mode2'] && !$new_input['payment_mode3'] )
        $new_input['cash_activate'] = 1;

        $new_input['add_accept_term'] = intval($input['add_accept_term']);
        $new_input['add_accept_term_adds'] = intval($input['add_accept_term_adds']);
        $new_input['print_button'] = intval($input['print_button']);

        $new_input['confirm_email_subject'] = sanitize_text_field($input['confirm_email_subject']);
        $new_input['confirm_email_header'] = sanitize_textarea_field($input['confirm_email_header']);
        $new_input['confirm_email_from_address'] = sanitize_email($input['confirm_email_from_address']);
        $new_input['confirm_email_from_name'] = sanitize_text_field($input['confirm_email_from_name']);
        
        $new_input['confirm_email_title'] = sanitize_text_field($input['confirm_email_title']);
        $new_input['confirm_email_header_image'] = sanitize_text_field($input['confirm_email_header_image']);
        $new_input['confirm_email_footer'] = sanitize_text_field($input['confirm_email_footer']);
        $new_input['confirm_email_footer_image'] = sanitize_text_field($input['confirm_email_footer_image']);
        
        if (!$new_input['confirm_email_from_address']) $new_input['confirm_email_from_address'] = get_bloginfo( 'admin_email' );
        if (!$new_input['confirm_email_from_name']) $new_input['confirm_email_from_name'] = get_bloginfo( 'name' );

        $new_input['tax'] = floatval($input['tax']);
        $new_input['currency'] = sanitize_text_field($input['currency']);
        $new_input['currency_code'] = sanitize_text_field($input['currency_code']);
        $new_input['currency_place'] = intval($input['currency_place']);

        $new_input['google_api'] = $input['google_api'];
        $new_input['map_zoom'] = intval($input['map_zoom']);
        $new_input['google_map_active'] = intval($input['google_map_active']);
        $new_input['map_marker'] = intval($input['map_marker']);
        $new_input['deposit_amount'] = $input['deposit_amount'] == intval($input['deposit_amount']) ? intval($input['deposit_amount']): floatval($input['deposit_amount']);

        $new_input['color_button'] = sanitize_text_field($input['color_button']);
        $new_input['color_widget_price_bg'] = sanitize_text_field($input['color_widget_price_bg']);
        $new_input['color_main_cal_month_bg'] = sanitize_text_field($input['color_main_cal_month_bg']);
       $new_input['color_main_cal_month_c'] = sanitize_text_field($input['color_main_cal_month_c']);
       $new_input['color_main_cal_event_bg'] = sanitize_text_field($input['color_main_cal_event_bg']);
       $new_input['color_main_cal_event_c'] = sanitize_text_field($input['color_main_cal_event_c']);
       $new_input['color_main_cal_wd_bg'] = sanitize_text_field($input['color_main_cal_wd_bg']);
       $new_input['color_main_cal_ad_bg'] = sanitize_text_field($input['color_main_cal_ad_bg']);
       $new_input['color_main_cal_nad_bg'] = sanitize_text_field($input['color_main_cal_nad_bg']);
       $new_input['color_main_cal_event_time'] = sanitize_text_field($input['color_main_cal_event_time']);
       $new_input['color_booking_cal_ad_bg'] = sanitize_text_field($input['color_booking_cal_ad_bg']);
       $new_input['color_booking_cal_nad_bg'] = sanitize_text_field($input['color_booking_cal_nad_bg']);

        $new_input = apply_filters('ba_event_sanitize_options', $new_input, $input);

        return $new_input;
}

////////////////////////////

/**
* Print the Section text
*/
function print_section_info1(){
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
}

    function print_section_info2()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }

    function print_section_info3()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }

    function print_section_info4()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }

    function print_section_info5()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }
    
    function print_section_info6()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }
    
    function print_section_info7()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }
    
    function print_section_info8()
    {
        // echo __('Enter your settings below:', BA_EVENT_TEXTDOMAIN);
    }

    function print_section_info9()
    {
      // echo __('Select Payment Modes:', BA_EVENT_TEXTDOMAIN);
    }
    
//////////////////////////////////////

function event_address_active_callback(){
        $check = isset($this->options['event_address_active']) ?  $this->options['event_address_active'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_address_active]" name="event_settings[event_address_active]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_address_active" for="event_settings[event_address_active]">'.__('Add', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

//////////////////////////////////////

function event_features_active_callback(){
        $check = isset($this->options['event_features_active']) ?  $this->options['event_features_active'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_features_active]" name="event_settings[event_features_active]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_features_active" for="event_settings[event_features_active]">'.__('Add', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

/////////////////event_slider_active

function event_slider_active_callback(){
        $check = isset($this->options['event_slider_active']) ?  $this->options['event_slider_active'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_slider_active]" name="event_settings[event_slider_active]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_slider_active" for="event_settings[event_slider_active]">'.__('Add', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

//////////////////////////////////////

function event_testi_active_callback(){
        $check = isset($this->options['event_testi_active']) ?  $this->options['event_testi_active'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_testi_active]" name="event_settings[event_testi_active]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_testi_active" for="event_settings[event_testi_active]">'.__('Add', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

//////////////////////////////////////

function event_faq_active_callback(){
        $check = isset($this->options['event_faq_active']) ?  $this->options['event_faq_active'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_faq_active]" name="event_settings[event_faq_active]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_faq_active" for="event_settings[event_faq_active]">'.__('Add', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

//////////////////////////////event_avcal_active

function event_avcal_active_callback(){
        $check = isset($this->options['event_avcal_active']) ?  $this->options['event_avcal_active'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_avcal_active]" name="event_settings[event_avcal_active]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_avcal_active" for="event_settings[event_avcal_active]">'.__('Add', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

////////////////////////////event_show_tickets_count

function event_show_tickets_count(){
        $check = isset($this->options['event_show_tickets_count']) ?  $this->options['event_show_tickets_count'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[event_show_tickets_count]" name="event_settings[event_show_tickets_count]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_event_show_tickets_count" for="event_settings[event_show_tickets_count]">'.__('Show', BA_EVENT_TEXTDOMAIN).'</label></p>';
}
    
//////////////////////////////////////    

function color_field_callback($args){
        printf(
            '<input type="text" id="'.$args['option'].'" class="color-field" name="event_settings['.$args['option'].']" value="%s" />',
            isset( $this->options[$args['option']] ) ? esc_attr( $this->options[$args['option']]) : $args['color']
        );
}

////////////////////////////////////
    
    function text_field_callback($args){
        $add_class = isset($args['translate']) ? ' class="q_translatable"' : '';
        
        printf(
            '<input type="text"'.$add_class.' id="'.$args['option'].'" name="event_settings['.$args['option'].']" value="%s" />',
            isset( $this->options[$args['option']] ) ? esc_attr( $this->options[$args['option']]) : ''
        );
    }

/////////////////////////////////////////
    
function textarea_callback($args){
        printf(
            '<textarea id="'.$args['option'].'" class="q_translatable" name="event_settings['.$args['option'].']" rows=5>%s</textarea>',
            isset( $this->options[$args['option']] ) ? esc_attr( $this->options[$args['option']]) : ''
        );
}    

/////////////////////////////////////

function img_field_callback($args){
       $img_src = isset( $this->options[$args['option']] ) ? $this->options[$args['option']] : '';

        echo '<div id="'.$args['option'].'_upload_block"><img id="'.$args['option'].'_preview" src="'.$img_src.'" class="ba_event_img_field_preview" width="100px"/>';
        echo '<input type="text" id="'.$args['option'].'" name="event_settings['.$args['option'].']" value="'.$img_src.'" /><input type="button" class="'.$args['option'].'_upload" value="'.__('Upload', BA_EVENT_TEXTDOMAIN).'"></div>';

        echo '<script>
    jQuery(document).ready(function($) {
        $(\'.'.$args['option'].'_upload\').click(function(e) {
            e.preventDefault();

            var custom_uploader = wp.media({
                title: \'Custom Image\',
                button: {
                    text: \'Upload Image\'
                },
                multiple: false  // Set this to true to allow multiple files to be selected
            })
            .on(\'select\', function() {
                var attachment = custom_uploader.state().get(\'selection\').first().toJSON();
                $(\'#'.$args['option'].'_preview\').attr(\'src\', attachment.url);
                $(\'#'.$args['option'].'\').val(attachment.url);

            })
            .open();
        });
    });
</script>';
}

//////////////////////////////////////
function color_button_callback(){
        printf(
            '<input type="text" id="color_button" class="color-field" name="event_settings[color_button]" value="%s" />',
            isset( $this->options['color_button'] ) ? esc_attr( $this->options['color_button']) : '#81d742'
        );
}

function google_api_callback(){
        printf(
            '<input type="text" id="google_api" name="event_settings[google_api]" value="%s" />',
            isset( $this->options['google_api'] ) ? esc_attr( $this->options['google_api']) : ''
        );
}

function map_zoom_callback(){
        printf(
            '<input type="text" id="map_zoom" name="event_settings[map_zoom]" value="%s" />',
            isset( $this->options['map_zoom'] ) ? esc_attr( $this->options['map_zoom']) : '13'
        );
}

function tax_callback(){
        printf(
            '<input type="text" id="tax" name="event_settings[tax]" value="%s" />',
            isset( $this->options['tax'] ) ? esc_attr( $this->options['tax']) : ''
        );
}

function currency_callback(){
        printf(
            '<input type="text" id="currency" name="event_settings[currency]" value="%s" />',
            isset( $this->options['currency'] ) ? esc_attr( $this->options['currency']) : '$'
        );
}

function currency_code_callback(){
        printf(
            '<input type="text" id="currency" name="event_settings[currency_code]" value="%s" />',
            isset( $this->options['currency_code'] ) ? esc_attr( $this->options['currency_code']) : 'USD'
        );
}

function deposit_amount_callback(){
        printf(
            '<input type="text" id="deposit_amount" name="event_settings[deposit_amount]" value="%s" />',
            isset( $this->options['deposit_amount'] ) ? esc_attr( $this->options['deposit_amount']) : ''
        );
}

////////////// map_marker

function map_marker_callback(){

        $check = isset($this->options['map_marker']) ?  $this->options['map_marker'] : 1;

        foreach ($this->markers_urls as $key => $url){
           if ($key == $check) $checked = ' checked';
           else $checked = '';

           echo '<div class="map_marker_block"><label class="map_marker_img" id="event_settings_map_marker'.$key.'" for="event_settings[map_marker]'.$key.'"><img src="'.plugins_url( $url, BA_EVENT_PLUGIN ).'"></label><input id="event_settings[map_marker]'.$key.'" name="event_settings[map_marker]" type="radio" value="'.$key.'"'.$checked.'/></div>';
        }
    }

function google_map_active_callback(){

        $check = isset($this->options['google_map_active']) ?  $this->options['google_map_active'] : 0;

        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked1 = 'checked';
           else $checked2 = 'checked';

        echo '<p><input id="event_settings[google_map_active]1" name="event_settings[google_map_active]" type="radio" value="1" '.$checked1.'/><label id="event_settings_google_map_active1" for="event_settings[google_map_active]1">'.__('Yes', BA_EVENT_TEXTDOMAIN).'</label></p>';
       echo '<p><input id="event_settings[google_map_active]2" name="event_settings[google_map_active]" type="radio" value="0" '.$checked2.'/><label id="event_settings_google_map_active2" for="event_settings[google_map_active]2">'.__('No', BA_EVENT_TEXTDOMAIN).'</label></p>';

}

/////////////////////

function date_format_callback(){

        $check = isset($this->options['date_format']) ?  $this->options['date_format'] : 0;

        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked1 = 'checked';
           else $checked2 = 'checked';

        echo '<p><input id="event_settings[date_format]1" name="event_settings[date_format]" type="radio" value="1" '.$checked1.'/><label id="event_settings_date_format1" for="event_settings[date_format]1">'.__('d/m/Y', BA_EVENT_TEXTDOMAIN).'</label></p>';
        echo '<p><input id="event_settings[date_format]2" name="event_settings[date_format]" type="radio" value="0" '.$checked2.'/><label id="event_settings_date_format2" for="event_settings[date_format]2">'.__('m/d/Y', BA_EVENT_TEXTDOMAIN).'</label></p>';       

}

////////////////////////////

function event_post_type(){
  $args = array(
   'public'   => true,
   '_builtin' => false
   );
  $output = 'objects';
  $operator = 'and';
  $post_types = get_post_types( $args, $output, $operator );
  echo '<select id="event_post_type" name="event_settings[event_post_type]">'; 
  $check = isset($this->options['event_post_type']) ?  $this->options['event_post_type'] : 'event';
   foreach ( $post_types  as $post_type ) {       
     $selected = $check == $post_type->name ? ' selected' : '';
     $title = $post_type->name == 'event' ? $post_type->name.' (created by BA Event Plugin)' : '';
     echo '<option value="' . $post_type->name . '"'.$selected.'>' . $title . '</option>';
   }
   
   echo '</select>';
}

//////////////////////payment_mode

function cash_activate_callback(){
        $check = isset($this->options['cash_activate']) ?  $this->options['cash_activate'] : 0;        
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[cash_activate]" name="event_settings[cash_activate]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_cash_activate" for="event_settings[cash_activate]">'.__('Activate', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

function payment_mode2_callback(){
        $check = isset($this->options['payment_mode2']) ?  $this->options['payment_mode2'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[payment_mode2]" name="event_settings[payment_mode2]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_payment_mode2" for="event_settings[payment_mode2]">'.__('Activate', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

function payment_mode3_callback(){
        $check = isset($this->options['payment_mode3']) ?  $this->options['payment_mode3'] : 0;
        $checked1 = $check ? ' checked' : '';

        echo '<p><input id="event_settings[payment_mode3]" name="event_settings[payment_mode3]" type="checkbox" value="1"'.$checked1.'/><label id="event_settings_payment_mode3" for="event_settings[payment_mode3]">'.__('Activate', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

//////////////////////////////

function deposit_active_callback(){

        $check = isset($this->options['deposit_active']) ?  $this->options['deposit_active'] : 0;

        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked2 = 'checked';
           else $checked1 = 'checked';

        echo '<p><input id="event_settings[deposit_active]1" name="event_settings[deposit_active]" type="radio" value="0" '.$checked1.'/><label id="event_settings_deposit_active1" for="event_settings[deposit_active]1">'.__('No', BA_EVENT_TEXTDOMAIN).'</label></p>';
       echo '<p><input id="event_settings[deposit_active]2" name="event_settings[deposit_active]" type="radio" value="1" '.$checked2.'/><label id="event_settings_deposit_active2" for="event_settings[deposit_active]2">'.__('Yes', BA_EVENT_TEXTDOMAIN).'</label></p>';

}

function currency_place_callback(){

        $check = isset($this->options['currency_place']) ?  $this->options['currency_place'] : 1;

        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked1 = 'checked';
           else $checked2 = 'checked';

        echo '<p><input id="event_settings[currency_place]1" name="event_settings[currency_place]" type="radio" value="1" '.$checked1.'/><label id="event_settings_currency_place1" for="event_settings[currency_place]1">'.__('Before amount', BA_EVENT_TEXTDOMAIN).'</label></p>';
       echo '<p><input id="event_settings[currency_place]2" name="event_settings[currency_place]" type="radio" value="2" '.$checked2.'/><label id="event_settings_currency_place2" for="event_settings[currency_place]2">'.__('After amount', BA_EVENT_TEXTDOMAIN).'</label></p>';

}

///////////////////

function add_accept_term_callback(){
    $check = isset($this->options['add_accept_term']) ?  $this->options['add_accept_term'] : 0;
        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked1 = 'checked';
           else $checked2 = 'checked';

        echo '<p><input id="event_settings[add_accept_term]1" name="event_settings[add_accept_term]" type="radio" value="1" '.$checked1.'/><label id="event_settings_add_accept_term1" for="event_settings[add_accept_term]1">'.__('Yes', BA_EVENT_TEXTDOMAIN).'</label></p>';
       echo '<p><input id="event_settings[add_accept_term]2" name="event_settings[add_accept_term]" type="radio" value="0" '.$checked2.'/><label id="event_settings_add_accept_term2" for="event_settings[add_accept_term]2">'.__('No', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

function add_accept_term_adds_callback(){
    $check = isset($this->options['add_accept_term_adds']) ?  $this->options['add_accept_term_adds'] : 0;
        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked1 = 'checked';
           else $checked2 = 'checked';

        echo '<p><input id="event_settings[add_accept_term_adds]1" name="event_settings[add_accept_term_adds]" type="radio" value="1" '.$checked1.'/><label id="event_settings_add_accept_term_adds1" for="event_settings[add_accept_term_adds]1">'.__('Yes', BA_EVENT_TEXTDOMAIN).'</label></p>';
       echo '<p><input id="event_settings[add_accept_term_adds]2" name="event_settings[add_accept_term_adds]" type="radio" value="0" '.$checked2.'/><label id="event_settings_add_accept_term_adds2" for="event_settings[add_accept_term_adds]2">'.__('No', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

function print_button_callback(){
    $check = isset($this->options['print_button']) ?  $this->options['print_button'] : 0;
        $checked1 = '';
        $checked2 = '';
        if ($check==1) $checked1 = 'checked';
           else $checked2 = 'checked';

        echo '<p><input id="event_settings[print_button]1" name="event_settings[print_button]" type="radio" value="1" '.$checked1.'/><label id="event_settings_print_button1" for="event_settings[print_button]1">'.__('Yes', BA_EVENT_TEXTDOMAIN).'</label></p>';
       echo '<p><input id="event_settings[print_button]2" name="event_settings[print_button]" type="radio" value="0" '.$checked2.'/><label id="other_settings" for="event_settings[print_button]2">'.__('No', BA_EVENT_TEXTDOMAIN).'</label></p>';
}

////////////////////////////////

function confirm_email_subject_callback(){
        printf(
            '<input type="text" id="confirm_email_subject" class="q_translatable" name="event_settings[confirm_email_subject]" value="%s" />',
            isset( $this->options['confirm_email_subject'] ) ? esc_attr( $this->options['confirm_email_subject']) : ''
        );
}

function confirm_email_from_address(){
        printf(
            '<input type="text" id="confirm_email_from_address" name="event_settings[confirm_email_from_address]" value="%s" />',
            isset( $this->options['confirm_email_from_address'] ) ? esc_attr( $this->options['confirm_email_from_address']) : get_bloginfo( 'admin_email' )
        );
}

function confirm_email_from_name(){
        printf(
            '<input type="text" id="confirm_email_from_name" class="q_translatable" name="event_settings[confirm_email_from_name]" value="%s" />',
            isset( $this->options['confirm_email_from_name'] ) ? esc_attr( $this->options['confirm_email_from_name']) : ''
        );
}

function confirm_email_header_callback(){
        printf(
            '<textarea id="confirm_email_header" class="q_translatable" name="event_settings[confirm_email_header]" rows=5>%s</textarea>',
            isset( $this->options['confirm_email_header'] ) ? esc_attr( $this->options['confirm_email_header']) : ''
        );
}

///////////////////
   
}  /////////////// end of class

	$BA_Event_admin = new BA_Event_admin();


