<?php

//     Plugin Main class

if ( ! defined( 'ABSPATH' ) )
	exit;

/////// make our main plugin class

class BA_Event {
    public $settings = array();
    public $months_names = array();
    private $payment_methods;
    private $markers_urls = array();
    public $date_format = 'd/m/Y';
    private $event_post_type = 'event';
    private $week_first_day = 0;
    private $week_last_day = 6;
    public function __construct() {

        $this->home_url = get_home_url();

        add_action( 'init', array( $this, 'load_textdomain' )); //// fix plugins_loaded
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueued_assets') );

        add_filter( 'query_vars', array( $this, 'register_query_var') );
        //add rewrite rules in case another plugin flushes rules
        // Call when plugin is initialized on every page load
        add_action( 'init', array( $this, 'rewrite_rule'), 10, 0 );

        add_action( 'init', array( $this, 'create_booking_post_type'), 0);
        add_action( 'init', array( $this, 'create_event_post_type'), 0);

        add_action( 'cmb2_admin_init', array( $this, 'cmb2_metaboxes') );
        add_filter( 'cmb2_render_event_details', array( $this, 'cmb2_event_details'), 10, 5 );
        
        add_action( 'wp_trash_post', array( $this, 'trash_delete_tickets'), 1, 1);
        add_action( 'untrash_post', array( $this, 'restore_tickets'), 1, 1);

        add_action( 'template_redirect', array( $this, 'redirect_after_post'));

        add_action('wp_ajax_get_reserv_code', array( $this, 'get_reserv_code_callback'));
        add_action('wp_ajax_get_amount_booking', array( $this, 'get_amount_booking_callback'));
        
        add_action('wp_ajax_get_hidden_inputs', array( $this, 'get_hidden_inputs'));
        
        add_action('wp_ajax_main_cal_update', array( $this, 'main_cal_update'));
        add_action('wp_ajax_nopriv_main_cal_update', array( $this, 'main_cal_update'));
        
        add_action('wp_ajax_booking_cal_update', array( $this, 'booking_cal_update'));
        add_action('wp_ajax_nopriv_booking_cal_update', array( $this, 'booking_cal_update'));
        
        add_action('wp_ajax_booking_time_update', array( $this, 'booking_time_update'));
        add_action('wp_ajax_nopriv_booking_time_update', array( $this, 'booking_time_update'));
        
        add_action('wp_ajax_booking_price_update', array( $this, 'booking_price_update'));
        add_action('wp_ajax_nopriv_booking_price_update', array( $this, 'booking_price_update'));
        
        add_filter( 'cron_schedules', array( $this, 'cron_add_ten_min' ));
        add_action( 'clear_booking', array( $this, 'clear_booking'));

        add_action( 'wp_insert_post', array( $this, 'update_booking_title'), 10, 3 );
        add_action( 'wp_insert_post', array( $this, 'update_event_post'), 10, 3 );

        register_activation_hook( BA_EVENT_PLUGIN, array( $this, 'activation') );
        register_deactivation_hook( BA_EVENT_PLUGIN, array( $this, 'deactivation') );

        add_shortcode( 'event-calendar', array( $this, 'main_cal_shortcode') );
        add_shortcode( 'all-events', array( $this, 'all_events' ));
        
        add_shortcode( 'event-booking-calendar', array( $this, 'booking_cal_shortcode') );
        add_shortcode( 'event-booking', array( $this, 'booking_page_shortcode') );
        add_shortcode( 'event-thanks', array( $this, 'thanks_page_shortcode') );
        
        add_filter( 'the_content', array( $this, 'event_content'), 100);
        add_filter( 'ba_event_page_content', array( $this, 'event_page_slider'), 10, 1);
        add_filter( 'ba_event_page_content', array( $this, 'event_page_features'), 20, 1);
        add_filter( 'ba_event_page_content', array( $this, 'event_page_map'), 30, 1);
        add_filter( 'ba_event_page_content', array( $this, 'event_page_faq'), 50, 1);
        add_filter( 'ba_event_page_content', array( $this, 'event_page_testi'), 40, 1);
        add_filter( 'ba_event_page_content', array( $this, 'event_page_avcal'), 60, 1);
        
        add_filter( 'ba_event_cash_payment_field', array( $this, 'payment_field_cash'), 10, 3);

        /**************** init settings *******************/
        
        if(get_option('start_of_week') != 0){
           $this->week_first_day = 1;
           $this->week_last_day = 7; 
        }
        
        $this->months_names = $this->get_months_arr();
        
       $this->payment_methods = get_option('event_settings_payments');
       if(empty($this->payment_methods)) {
          $this->payment_methods = array('cash');
          update_option('event_settings_payments', $this->payment_methods);
       } 
        
       $this->settings = get_option('event_settings');
       if(empty($this->settings)) {
       $this->settings['tax'] = '';
       $this->settings['currency'] = '$';
       $this->settings['currency_code'] = 'USD';
       $this->settings['currency_place'] = 1;
       $this->settings['deposit_active'] = 0;
       $this->settings['deposit_amount'] = '';
       $this->settings['cash_activate'] = 1;
       $this->settings['payment_mode2'] = '';
       $this->settings['payment_mode3'] = '';
       $this->settings['add_accept_term'] = 1;
       $this->settings['add_accept_term_adds'] = 0;
       $this->settings['print_button'] = 0;
       $this->settings['confirm_email_from_name'] = get_bloginfo( 'name' );
       $this->settings['confirm_email_from_address'] = get_bloginfo( 'admin_email' );
       $this->settings['confirm_email_header'] = __('Thank you for your request, one of our staff will respond shortly.', BA_EVENT_TEXTDOMAIN);
       $this->settings['confirm_email_subject'] = __('You have new reservation', BA_EVENT_TEXTDOMAIN);
       $this->settings['event_address_active'] = 1;
       $this->settings['event_testi_active'] = 1;
       $this->settings['event_faq_active'] = 1;
       $this->settings['event_avcal_active'] = 1;
       $this->settings['event_features_active'] = 1;
       $this->settings['event_slider_active'] = 1;
       $this->settings['event_show_tickets_count'] = 1;
       $this->settings['date_format'] = 1;
       $this->settings['google_map_active'] = 1;
       $this->settings['google_api'] = '';
       $this->settings['map_zoom'] = 13;
       $this->settings['map_marker'] = 1;
       $this->settings['color_button'] = '#e36f22';
       $this->settings['color_widget_price_bg'] = '#e36f22';
       $this->settings['color_main_cal_month_bg'] = '#7f6e52';
       $this->settings['color_main_cal_month_c'] = '#ffffff';
       $this->settings['color_main_cal_event_bg'] = '#fffacd';
       $this->settings['color_main_cal_event_c'] = '#757575';
       $this->settings['color_main_cal_wd_bg'] = '#d2b48c';
       $this->settings['color_main_cal_ad_bg'] = '#fffacd';
       $this->settings['color_main_cal_nad_bg'] = '#f5f5dc';
       $this->settings['color_main_cal_event_time'] = '#e36f22';
       $this->settings['color_booking_cal_ad_bg'] = '#93c949';
       $this->settings['color_booking_cal_nad_bg'] = '#dadada';
       update_option('event_settings', $this->settings);
       }
       
       $this->event_post_type = isset($this->settings['event_post_type']) ? $this->settings['event_post_type'] : 'event';
       
       $this->date_format = $this->settings['date_format'] ? 'd/m/Y' : 'm/d/Y';
       
       //////////// $markers_urls ///////
       $this->markers_urls[1] = 'css/img/pointer_1.png';
       $this->markers_urls[2] = 'css/img/pointer_2.png';
       $this->markers_urls[3] = 'css/img/pointer_3.png';
       $this->markers_urls[4] = 'css/img/pointer_4.png';
       $this->markers_urls[5] = 'css/img/pointer_5.png';
       $this->markers_urls[6] = 'css/img/pointer_6.png';
       $this->markers_urls[7] = 'css/img/pointer_7.png';
       ////////////////////
       
       if (isset($this->settings['event_testi_active']) && $this->settings['event_testi_active']){
          add_action( 'init', array( $this, 'create_testi_post_type'), 0);
       }
       if (isset($this->settings['event_faq_active']) && $this->settings['event_faq_active']){
          add_action( 'init', array( $this, 'create_faq_post_type'), 0);
       }           
       
} ///// end construct

/////////////////

public function activation() {

    $this->create_page( 'booking', 'BA_EVENT_page_booking', __('Booking', BA_EVENT_TEXTDOMAIN), '[event-booking]');
    
    $this->create_page( 'proceed-with-booking', 'BA_EVENT_page_booking_calendar', __('Proceed with booking', BA_EVENT_TEXTDOMAIN), '[event-booking-calendar]');
    
    $this->create_page( 'calendar', 'BA_EVENT_page_calendar', __('Calendar', BA_EVENT_TEXTDOMAIN), '[event-calendar]');
    
    $this->create_page( 'thank-you', 'BA_EVENT_page_thank_you', __('Confirmation', BA_EVENT_TEXTDOMAIN), '<h3>'.__('Thank you for your request, one of our staff will respond shortly.', BA_EVENT_TEXTDOMAIN).'</h3>
[event-thanks]');

    $this->create_page( 'terms-and-conditions', 'BA_EVENT_page_terms', __('Terms and Conditions', BA_EVENT_TEXTDOMAIN), '');
    $this->create_page( 'additional-conditions', 'BA_EVENT_page_terms_adds', __('Additional conditions', BA_EVENT_TEXTDOMAIN), '');
 
    $this->create_booking_post_type();
    $this->create_event_post_type();
    $this->create_faq_post_type();
    $this->create_testi_post_type();
    $this->rewrite_rule();
    flush_rewrite_rules();
    
    $this->register_payment_method();
    
    wp_schedule_event( time(), 'ten_min', 'clear_booking' );
}

/////////////////////////////

function register_payment_method(){
    $payment_methods = get_option('event_settings_payments');
    if(empty($payment_methods)) {
       $payment_methods = array('cash');
       update_option('event_settings_payments', $payment_methods);
    }
}

///////////////////////////////

function deactivation() {
   	flush_rewrite_rules(); 
    wp_clear_scheduled_hook( 'clear_booking' );
}

// add 10 min interval
function cron_add_ten_min( $schedules ) {
	$schedules['ten_min'] = array(
		'interval' => 60 * 10,
		'display' => 'One time per 10 min'
	);
	return $schedules;
}

function create_page( $slug, $option, $page_title = '', $page_content = '', $post_parent = 0 ) {
    global $wpdb;
    $option_value = get_option( $option );
    if ( $option_value > 0 && get_post( $option_value ) )
      return;
    $page_found = $wpdb->get_var("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = '$slug' LIMIT 1;");
    if ( $page_found ) :
      if ( ! $option_value )
        update_option( $option, $page_found );
      return;
    endif;
    $page_data = array(
          'post_status' 		=> 'publish',
          'post_type' 		=> 'page',
          'post_author' 		=> 1,
          'post_name' 		=> $slug,
          'post_title' 		=> $page_title,
          'post_content' 		=> $page_content,
          'post_parent' 		=> $post_parent,
          'comment_status' 	=> 'closed'
      );
      $page_id = wp_insert_post( $page_data );
      update_option( $option, $page_id );
}

////////////////////////////////////////////

function load_textdomain() {
    $locale = apply_filters( 'plugin_locale', get_locale(), BA_EVENT_TEXTDOMAIN );
	load_textdomain( BA_EVENT_TEXTDOMAIN, WP_LANG_DIR . '/ba-event/ba-event-' . $locale . '.mo' );
	load_plugin_textdomain( BA_EVENT_TEXTDOMAIN, false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
}

function enqueued_assets() {
     wp_enqueue_script( 'jqvalidate-js', plugins_url( "js/jquery.validate.min.js", BA_EVENT_PLUGIN ), array('jquery'), '1.0', true );
     
     $lang = function_exists( 'qtranxf_getLanguage' ) ? qtranxf_getLanguage() : substr(get_locale(), 0, 2);
     if ($lang && $lang != 'en'){
        wp_enqueue_script( 'jqvalidate-msgs-js', plugins_url( 'js/localization/messages_'.$lang.'.min.js', BA_EVENT_PLUGIN ), array('jqvalidate-js'), '1.0', true );
     }
     
    wp_enqueue_script( 'slick-js', plugins_url('js/slick/slick.min.js', BA_EVENT_PLUGIN ), array('jquery'), '1.0', true );
    wp_enqueue_style( 'slick-style', plugins_url('js/slick/slick.css', BA_EVENT_PLUGIN ));
    wp_enqueue_style( 'slick-theme-style', plugins_url('js/slick/slick-theme.css', BA_EVENT_PLUGIN ), array('slick-style'));

     wp_enqueue_script( 'ba-event-js', plugins_url( "js/event.js", BA_EVENT_PLUGIN ), array('jquery'), '1.0', true );

     wp_localize_script( 'ba-event-js', 'lst', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce('lst-nonce')
         )
        );

     wp_enqueue_style( 'ba-event-style', plugins_url( "css/event.css", BA_EVENT_PLUGIN ));

     $color = $this->settings['color_button'];
        $custom_css = "
                input[type=\"button\"].bc-button-color, input[type=\"submit\"].bc-button-color, button.bc-button-color, #pager span.cur_page, #app-page .doc_tab.cur_tab {
                        background-color: {$color};
                        color: #fff;
                }
                #sort_by_block{
                   background-color: {$color};
                   color: #fff;
                }

                #app-page .doc_tab{
                        color: {$color};
                        border-color: {$color};
                }

                #app-page .doc_tab:nth-child(1){
                        border-color: {$color};
                }
                
                #cal-select-month {
                    background-color: ".$this->settings['color_main_cal_month_bg'].";
                    color: ".$this->settings['color_main_cal_month_c'].";
                }
                
                #cal-select-event {
                    background-color: ".$this->settings['color_main_cal_event_bg'].";
                    color: ".$this->settings['color_main_cal_event_c'].";
                }
                
                #main-cal thead th, #primary .post .entry-content table#main-cal thead th, #primary .page .entry-content table#main-cal thead th {
                    background-color: ".$this->settings['color_main_cal_wd_bg'].";
                }
                
                #main-cal .cal-cell-date {
                    background-color: ".$this->settings['color_main_cal_nad_bg'].";
                }
                
                #main-cal .cal-cell-av .cal-cell-date {
                    background-color: ".$this->settings['color_main_cal_ad_bg'].";
                }
                
                #main-cal td li a, #main-cal td li a:hover {
                    color: ".$this->settings['color_main_cal_event_time'].";
                }
                
                #booking-cal .cal-cell-date.cal-av {
                    background-color: ".$this->settings['color_booking_cal_ad_bg'].";
                    border-color: ".$this->settings['color_booking_cal_ad_bg'].";
                }
                
                #booking-cal .cal-cell-date {
                    background-color: ".$this->settings['color_booking_cal_nad_bg'].";
                    border-color: ".$this->settings['color_booking_cal_nad_bg'].";
                }
                
                #booking-cal .cal-cell-date.selected {
                    border-color: #000000;
                }
                
                .ba_event_widget_price{
                   background-color: ".$this->settings['color_widget_price_bg'].";
                }    
                ";
                
                if (isset($this->settings['features_list_img']) && $this->settings['features_list_img']){
                    $custom_css .= "
                ul.event_features_list{
                    list-style-type: none;                    
                }    
                ul.event_features_list li span {
                    background: url(".$this->settings['features_list_img'].") center center no-repeat;
                    position: relative;
                    top: 5px;
                    display: inline-block;
                    height: 26px;
                    width: 26px;
                    background-size: cover;
                    margin-right: 8px;
                }";
                }
        wp_add_inline_style( 'ba-event-style', $custom_css );

}

function register_query_var( $vars ) {
   $vars[] = 'reserv_code';
   return $vars;
}

function rewrite_rule() {

        add_rewrite_rule( 'payment-api/?', 'index.php?payment_api=1', 'top' );

        add_rewrite_rule( 'thank-you/reserv-code/([^/]+)/?', 'index.php?pagename=thank-you&reserv_code=$matches[1]', 'top' );

}

///////////////////

function redirect_after_post(){
    
      do_action('ba_event_before_get_post');

  if (!empty($_POST['book'])){
        
      $post_arr = $this->sanitize_booking_post_arr();    
  
      if (!empty($_POST['first_name']) && !empty($_POST['last_name']) && !empty($_POST['email']) && !empty($_POST['tel1']) && !empty($post_arr)){
      
      $post_arr['tel1'] = sanitize_text_field($_POST['tel1']);
      $post_arr['first_name'] = sanitize_text_field($_POST['first_name']);
      $post_arr['last_name'] = sanitize_text_field($_POST['last_name']);
      $post_arr['email'] = sanitize_email($_POST['email']); 
      
      $reserv_id = $this->create_booking($post_arr);
      if ($reserv_id){

      $reserv_code = get_the_title($reserv_id);
      
      do_action('ba_event_post_booking', $reserv_code, $post_arr);

       if ($_POST['payment_method'] == 'cash' && $this->settings['cash_activate']){

           $this->accept_booking($reserv_code, 0, '', 'cash');
           $this->thanks_email($reserv_code);
           
           $Path='thank-you/reserv-code/'.$reserv_code;
           wp_safe_redirect(ba_event_make_url($Path));
           exit;
       }
      } 
    }
  }

}

///////////////////////////////

public function set_html_mail_content_type() {
    return 'text/html';
}

/////////////////////////////////
////// validate format yyyy-mm-dd bool checkdate ( int $month , int $day , int $year )

function validate_date($test_date){
   $output = true;
   
   $date_arr  = explode('-', $test_date);
   if (sizeof($date_arr) != 3 || !checkdate($date_arr[1], $date_arr[2], $date_arr[0])){
      $output = false;
   }
    
   return $output;
}

///////////////

public function format_currency($amount){
  $output = '';

  if ( $this->settings['currency_place'] == 1 )
    $output .= $this->settings['currency'].$amount;
  else
    $output .= $amount.' '.$this->settings['currency'];

  return $output;
}

//////////////////////

public function accept_booking($reserv_code, $paid = 0, $token = '', $paid_by = ''){

     $page = get_page_by_title( $reserv_code, OBJECT, 'booking' );

     if (!empty($page)){
     $booking_id = $page->ID;
     update_post_meta($booking_id, '_booking_accepted', 1);
     update_post_meta($booking_id, '_booking_paid', $paid);
     update_post_meta($booking_id, '_booking_paid_by', $paid_by);
     update_post_meta($booking_id, '_booking_token', $token);
    }
}

//////////////

function delete_tickets($booking_id){ 
       $tickets = get_post_meta( $booking_id, '_booking_tickets', true );
       $tickets = -1 * (int)$tickets;
      // error_log('$tickets: '.$tickets);
      // $meta = get_post_meta($booking_id);
       $event_id = get_post_meta( $booking_id, '_booking_event', true );
       //error_log('$meta: '.print_r($meta, true));
       $event_date = get_post_meta( $booking_id, '_booking_event_date', true );
       $event_time = get_post_meta( $booking_id, '_booking_event_time', true );
      // error_log('$event_date: '.$event_date);
      // error_log('$event_time: '.$event_time); 
       $this->update_av_cal($event_id, $event_date, $event_time, $tickets);  
}

function trash_delete_tickets($booking_id){
    $post_type = get_post_type( $booking_id );
    $post_status = get_post_status( $booking_id );
    if( $post_type == 'booking' && in_array($post_status, array('publish')) ) {
        $this->delete_tickets($booking_id);
    }
}

function restore_tickets($booking_id){
    $post_type = get_post_type( $booking_id );
    if( $post_type == 'booking'){
        //// restore tickets
       $tickets = get_post_meta( $booking_id, '_booking_tickets', true );
       $tickets = (int)$tickets;
       $event_id = get_post_meta( $booking_id, '_booking_event', true );
       $event_date = get_post_meta( $booking_id, '_booking_event_date', true );
       $event_time = get_post_meta( $booking_id, '_booking_event_time', true ); 
       $this->update_av_cal($event_id, $event_date, $event_time, $tickets);
    }
}

public function cancell_booking($reserv_code){
  $output = 0;
  $page = get_page_by_title( $reserv_code, OBJECT, 'booking' );
     if (!empty($page)){
       $this->delete_tickets($page->ID); 
       wp_delete_post( $page->ID, true );
     $output = 1;
    }
  return $output;
}

public function clear_booking(){

  $args = array(
               'post_type' => 'booking',
               'post_status' => 'publish',
               'posts_per_page'=> -1,
               'meta_query' => array(
                  'relation' => 'AND',
                  'date_from' => array(
                    'key'     => '_booking_created',
                    'value'   => (time() - 10*60),
                    'compare' => '<',
                    'type'    => 'NUMERIC',
                    ),
                  'accepted' => array(
                    'key'     => '_booking_accepted',
                    'value'   => 0,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                    ),
                    ),
                  );

    $the_query = new WP_Query( $args );
     while ( $the_query->have_posts() ) : $the_query->the_post();
       $booking_id = get_the_ID();
       $this->delete_tickets($booking_id);
       wp_delete_post( $booking_id, true );
     endwhile;
wp_reset_postdata();
}

////////////////

function get_reserv_code_callback(){

    $home_url = get_home_url();
    $Path='/invoice/'.get_the_title($_POST['id']);

    echo $home_url.$Path;
    wp_die();
}

///////////////

function get_amount_booking_callback(){

   $output = array();

   if (!empty($_POST['event_guests']) && !empty($_POST['event_id'])){
    $rates = $this->get_all_rates_by_ap_id($_POST['event_id'], $_POST['event_guests']);
    $output = $rates;
   } else
   $output = array('total' => '', 'total_tax' => '', 'total_clear' => '');

   echo json_encode($output);

  wp_die();
}
   
////////////////////////////////////

public function get_all_rates_by_ap_id($event_id, $event_guests){
    $output = array();

    $price = 0;
    $price_tax = 0;
    $price_clear = 0;

    $tax_am = (!empty($this->settings['tax'])) ? floatval($this->settings['tax'])/100 : 0;
    $tax = 1 + $tax_am;

    $deposit = 0;
    
    //  [event_guests] => Array ( [18] => 2 [20] => 3 [19] => 1 ) 
    
    $price_arr = (array)get_post_meta($event_id, '_event_price', true);
    
    $event_age_arr = (array)get_post_meta($event_id, '_event_age', true);
    
    foreach ($event_age_arr as $age_id){
        if (isset($event_guests[$age_id])){
            $price_clear_tmp = round((int)$event_guests[$age_id] * (float)$price_arr[$age_id], 2);  
            $price_tax_tmp = round($price_clear_tmp * $tax_am, 2);
            $price_tmp = $price_clear_tmp + $price_tax_tmp;  
            
            $price_clear += $price_clear_tmp;  
            $price_tax += $price_tax_tmp;
            $price += $price_tmp; 
        }
    }

  if ($this->settings['payment_mode2']) $deposit = round($price*($this->settings['deposit_amount']/100), 2);

  $output['total'] = $price;
  $output['total_tax'] = $price_tax;
  $output['total_clear'] = $price_clear;
  $output['deposit'] = $deposit;

  return $output;
}

/////////////////

function create_booking_post_type(){

// Set UI labels for Custom Post Type
	$labels = array(
		'name'                => _x( 'All Reservations', 'Post Type General Name', BA_EVENT_TEXTDOMAIN),
		'singular_name'       => _x( 'Reservation', 'Post Type Singular Name', BA_EVENT_TEXTDOMAIN),
		'menu_name'           => __( 'Reservations', BA_EVENT_TEXTDOMAIN),
		'parent_item_colon'   => __( 'Parent Reservation', BA_EVENT_TEXTDOMAIN),
		'all_items'           => __( 'All Reservations', BA_EVENT_TEXTDOMAIN),
		'view_item'           => __( 'View Reservation', BA_EVENT_TEXTDOMAIN),
		'add_new_item'        => __( 'Make New Reservation', BA_EVENT_TEXTDOMAIN),
		'add_new'             => __( 'Make New Reservation', BA_EVENT_TEXTDOMAIN),
		'edit_item'           => __( 'Modify Reservation', BA_EVENT_TEXTDOMAIN),
		'update_item'         => __( 'Update Reservation', BA_EVENT_TEXTDOMAIN),
		'search_items'        => __( 'Search Reservations', BA_EVENT_TEXTDOMAIN),
		'not_found'           => __( 'Not Found', BA_EVENT_TEXTDOMAIN),
		'not_found_in_trash'  => __( 'Not found in Trash', BA_EVENT_TEXTDOMAIN),
	);

// Set other options for Custom Post Type

	$args = array(
		'label'               => __( 'bookings', BA_EVENT_TEXTDOMAIN),
		'description'         => __( 'Reservations', BA_EVENT_TEXTDOMAIN),
		'labels'              => $labels,
		// Features this CPT supports
		'supports'            => array('title'),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
        'menu_position'        => 31,
        'menu_icon'           => 'dashicons-calendar-alt',
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);

	// Registering your Custom Post Type
	register_post_type( 'booking', $args );

    // remove_post_type_support( 'booking', 'title' );
}

///////////////////////////////////////

function create_event_post_type(){

// Set UI labels for Custom Post Type
	$labels = array(
		'name'                => _x( 'Events', 'Post Type General Name', BA_EVENT_TEXTDOMAIN),
		'singular_name'       => _x( 'Event', 'Post Type Singular Name', BA_EVENT_TEXTDOMAIN),
		'menu_name'           => __( 'Events', BA_EVENT_TEXTDOMAIN),
		'parent_item_colon'   => __( 'Parent Event', BA_EVENT_TEXTDOMAIN),
		'all_items'           => __( 'All Events', BA_EVENT_TEXTDOMAIN),
		'view_item'           => __( 'View Event', BA_EVENT_TEXTDOMAIN),
		'add_new_item'        => __( 'Add New Event', BA_EVENT_TEXTDOMAIN),
		'add_new'             => __( 'Add Event', BA_EVENT_TEXTDOMAIN),
		'edit_item'           => __( 'Edit Event', BA_EVENT_TEXTDOMAIN),
		'update_item'         => __( 'Update Event', BA_EVENT_TEXTDOMAIN),
		'search_items'        => __( 'Search Events', BA_EVENT_TEXTDOMAIN),
		'not_found'           => __( 'Not Found', BA_EVENT_TEXTDOMAIN),
		'not_found_in_trash'  => __( 'Not found in Trash', BA_EVENT_TEXTDOMAIN),
	);

// Set other options for Custom Post Type

	$args = array(
		'label'               => __( 'events', BA_EVENT_TEXTDOMAIN),
		'description'         => __( 'Events', BA_EVENT_TEXTDOMAIN),
		'labels'              => $labels,
		// Features this CPT supports
		'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'hierarchical'        => true,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
        'menu_position'        => 27,
        'menu_icon'           => 'dashicons-location-alt',
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);

	// Registering your Custom Post Type
	register_post_type( 'event', $args );
        //////////////////////////////
        
        $event_features_labels = array(
			'name'              => __( 'Features', BA_EVENT_TEXTDOMAIN ),
			'singular_name'     => __( 'Feature', BA_EVENT_TEXTDOMAIN ),
			'search_items'      => __( 'Search Features', BA_EVENT_TEXTDOMAIN ),
			'all_items'         => __( 'All Features', BA_EVENT_TEXTDOMAIN ),
			'parent_item'       => __( 'Parent Feature', BA_EVENT_TEXTDOMAIN ),
			'parent_item_colon' => __( 'Parent Feature:', BA_EVENT_TEXTDOMAIN ),
			'edit_item'         => __( 'Edit Feature', BA_EVENT_TEXTDOMAIN ),
			'update_itm'        => __( 'Update Feature', BA_EVENT_TEXTDOMAIN ),
			'add_new_item'      => __( 'Add New Feature', BA_EVENT_TEXTDOMAIN ),
			'new_item_name'     => __( 'New Feature', BA_EVENT_TEXTDOMAIN ),
			'menu_name'         => __( 'Features', BA_EVENT_TEXTDOMAIN ),
		);

		register_taxonomy( 'ba-features', 'event', array(
			'labels'            => $event_features_labels,
			'hierarchical'      => false,
			'query_var'         => 'ba-features',
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
		) );
        
        ////////////////////////////////
        
       $event_age_labels = array(
			'name'              => __( 'Age Categories', BA_EVENT_TEXTDOMAIN ),
			'singular_name'     => __( 'Age Category', BA_EVENT_TEXTDOMAIN ),
			'search_items'      => __( 'Search Age Categories', BA_EVENT_TEXTDOMAIN ),
			'all_items'         => __( 'All Age Categories', BA_EVENT_TEXTDOMAIN ),
			'parent_item'       => __( 'Parent Age Category', BA_EVENT_TEXTDOMAIN ),
			'parent_item_colon' => __( 'Parent Age Category:', BA_EVENT_TEXTDOMAIN ),
			'edit_item'         => __( 'Edit Age Category', BA_EVENT_TEXTDOMAIN ),
			'update_itm'        => __( 'Update Age Category', BA_EVENT_TEXTDOMAIN ),
			'add_new_item'      => __( 'Add New Age Category', BA_EVENT_TEXTDOMAIN ),
			'new_item_name'     => __( 'New Age Category', BA_EVENT_TEXTDOMAIN ),
			'menu_name'         => __( 'Age Categories', BA_EVENT_TEXTDOMAIN ),
		);

		register_taxonomy( 'age', 'event', array(
			'labels'            => $event_age_labels,
			'hierarchical'      => false,
			'query_var'         => 'age',
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
		) );
        
}

////////////////////////////////////

function create_faq_post_type(){

// Set UI labels for Custom Post Type
	$labels = array(
		'name'                => _x( 'FAQ', 'Post Type General Name', BA_EVENT_TEXTDOMAIN ),
		'singular_name'       => _x( 'FAQ', 'Post Type Singular Name', BA_EVENT_TEXTDOMAIN ),
		'menu_name'           => __( 'FAQ', BA_EVENT_TEXTDOMAIN ),
		'parent_item_colon'   => __( 'Parent FAQ', BA_EVENT_TEXTDOMAIN ),
		'all_items'           => __( 'All FAQs', BA_EVENT_TEXTDOMAIN ),
		'view_item'           => __( 'View FAQ', BA_EVENT_TEXTDOMAIN ),
		'add_new_item'        => __( 'Add New FAQ', BA_EVENT_TEXTDOMAIN ),
		'add_new'             => __( 'Add New', BA_EVENT_TEXTDOMAIN ),
		'edit_item'           => __( 'Edit FAQ', BA_EVENT_TEXTDOMAIN ),
		'update_item'         => __( 'Update FAQ', BA_EVENT_TEXTDOMAIN ),
		'search_items'        => __( 'Search FAQ', BA_EVENT_TEXTDOMAIN ),
		'not_found'           => __( 'Not Found', BA_EVENT_TEXTDOMAIN ),
		'not_found_in_trash'  => __( 'Not found in Trash', BA_EVENT_TEXTDOMAIN ),
	);

// Set other options for Custom Post Type

	$args = array(
		'label'               => __( 'faq', BA_EVENT_TEXTDOMAIN ),
		'description'         => __( 'FAQ', BA_EVENT_TEXTDOMAIN ),
		'labels'              => $labels,
		// Features this CPT supports
		'supports'            => array( 'title', 'editor', 'page-attributes' ),
		'hierarchical'        => true,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
        'menu_position'        => 28,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);

	// Registering your Custom Post Type
	register_post_type( 'faq', $args ); 

  return; 
}

/////////////////////////////////////

function create_testi_post_type(){

// Set UI labels for Custom Post Type
	$labels = array(
		'name'                => _x( 'Testimonials', 'Post Type General Name', BA_EVENT_TEXTDOMAIN ),
		'singular_name'       => _x( 'Testimonial', 'Post Type Singular Name', BA_EVENT_TEXTDOMAIN ),
		'menu_name'           => __( 'Testimonials', BA_EVENT_TEXTDOMAIN ),
		'parent_item_colon'   => __( 'Parent Testimonial', BA_EVENT_TEXTDOMAIN ),
		'all_items'           => __( 'All Testimonials', BA_EVENT_TEXTDOMAIN ),
		'view_item'           => __( 'View Testimonial', BA_EVENT_TEXTDOMAIN ),
		'add_new_item'        => __( 'Add New Testimonial', BA_EVENT_TEXTDOMAIN ),
		'add_new'             => __( 'Add New', BA_EVENT_TEXTDOMAIN ),
		'edit_item'           => __( 'Edit Testimonial', BA_EVENT_TEXTDOMAIN ),
		'update_item'         => __( 'Update Testimonial', BA_EVENT_TEXTDOMAIN ),
		'search_items'        => __( 'Search Testimonials', BA_EVENT_TEXTDOMAIN ),
		'not_found'           => __( 'Not Found', BA_EVENT_TEXTDOMAIN ),
		'not_found_in_trash'  => __( 'Not found in Trash', BA_EVENT_TEXTDOMAIN ),
	);

// Set other options for Custom Post Type

	$args = array(
		'label'               => __( 'testi', BA_EVENT_TEXTDOMAIN ),
		'description'         => __( 'Testimonials', BA_EVENT_TEXTDOMAIN ),
		'labels'              => $labels,
		// Features this CPT supports
		'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		'hierarchical'        => true,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
        'menu_position'        => 28,
		'show_in_nav_menus'   => false,
		'show_in_admin_bar'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'post',
	);

	// Registering your Custom Post Type
	register_post_type( 'testi', $args ); 

  return; 
}

///////////////////////////////////////

/**
 * Define the metabox and field configurations.
 */
function cmb2_metaboxes() {

    $prefix = '_booking_';

    $cmb3 = new_cmb2_box( array(
        'id'            => 'booking_metabox',
        'title'         => __( '&nbsp;', BA_EVENT_TEXTDOMAIN ),
        'object_types'  => array( 'booking', ), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ) );

    /////////////////////////////////
    if(!isset($_GET['post'])){
    $cmb3->add_field( array(
    'name'       => __( 'Select Event', BA_EVENT_TEXTDOMAIN ),
   // 'desc'       => __( 'field description (optional)', BA_EVENT_TEXTDOMAIN ),
    'id'         => $prefix . 'event',
    'type'       => 'select',
    'options_cb' => array($this, 'cmb2_get_event_options'),
    'show_option_none' => true,
    'attributes'  => array(
         'required'    => 'required',
         ),
    'after_field' => array($this, 'cmb2_after_row'), // callback
    ) );
    } else {
        $cmb3->add_field( array(
    'name'       => __( 'Event details', BA_EVENT_TEXTDOMAIN ),
   // 'desc'       => __( 'field description (optional)', MY_HOSTEL_TEXTDOMAIN ),
    'id'         => $prefix . 'details',
    'type'       => 'event_details',
    ) );
    
       $cmb3->add_field( array(
    'name' => __( 'Select Event', BA_EVENT_TEXTDOMAIN ),
    'id'   => $prefix . 'event',
    'type' => 'hidden',
    ) );  
    }
    
    //////////////////////////

    $cmb3->add_field( array(
        'name'       => __( 'First name', BA_EVENT_TEXTDOMAIN ),
        //'desc'       => __( 'digits only', BA_EVENT_TEXTDOMAIN ),
        'id'         => $prefix . 'first_name',
        'type'       => 'text',
        'attributes'  => array(
         'required'    => 'required',
         ),
    ) );

    $cmb3->add_field( array(
        'name'       => __( 'Last name', BA_EVENT_TEXTDOMAIN ),
        //'desc'       => __( 'digits only', BA_EVENT_TEXTDOMAIN ),
        'id'         => $prefix . 'last_name',
        'type'       => 'text',
        'attributes'  => array(
         'required'    => 'required',
         ),
    ) );

    $cmb3->add_field( array(
        'name'       => __( 'E-mail', BA_EVENT_TEXTDOMAIN ),
        //'desc'       => __( 'digits only', BA_EVENT_TEXTDOMAIN ),
        'id'         => $prefix . 'email',
        'type'       => 'text_email',
        'attributes'  => array(
         'required'    => 'required',
         ),
    ) );

    $cmb3->add_field( array(
        'name'       => __( 'Phone', BA_EVENT_TEXTDOMAIN ),
        //'desc'       => __( 'digits only', BA_EVENT_TEXTDOMAIN ),
        'id'         => $prefix . 'phone',
        'type'       => 'text',
        'attributes'  => array(
         'required'    => 'required',
         ),
    ) );
    
    do_action('ba_event_add_cmb2_booking_fields', $cmb3);
    
    /////////////////////////////
    
    $prefix = '_event_';

    /**
     * Initiate the metabox
     */
    $cmb = new_cmb2_box( array(
        'id'            => 'event_metabox',
        'title'         => __( 'Details', BA_EVENT_TEXTDOMAIN ),
        'object_types'  => array( $this->event_post_type ), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
        // 'cmb_styles' => false, // false to disable the CMB stylesheet
        // 'closed'     => true, // Keep the metabox closed by default
    ) );

    //////////////////////////////////
    if (isset($this->settings['event_address_active']) && $this->settings['event_address_active']){
        
    $cmb->add_field( array(
        'name'       => __( 'Address', BA_EVENT_TEXTDOMAIN ),
        'desc'       => __( 'Street, etc.', BA_EVENT_TEXTDOMAIN ),
        'id'         => $prefix . 'address',
        'classes' => array( 'q_translatable' ),
        'type'       => 'text',
        //'show_on_cb' => 'cmb2_hide_if_no_cats', // function should return a bool value
        // 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
        // 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
        // 'on_front'        => false, // Optionally designate a field to wp-admin only
        // 'repeatable'      => true,
    ) );
    }
    
    //////////////////////////////////
    if ($this->settings['google_map_active']){

    // Regular text field
    $cmb->add_field( array(
        'name'       => __( 'Location Latitude', BA_EVENT_TEXTDOMAIN ),
       // 'desc'       => __( 'Street, etc.' ),
        'id'         => $prefix . 'location_latitude',
        'type'       => 'text',
    ) );

    // Regular text field
    $cmb->add_field( array(
        'name'       => __( 'Location Longitude', BA_EVENT_TEXTDOMAIN ),
       // 'desc'       => __( 'Street, etc.' ),
        'id'         => $prefix . 'location_longitude',
        'type'       => 'text',
    ) );
    
    } //// end location block

    $cmb->add_field( array(
        'name'       => __( 'Up to Guests', BA_EVENT_TEXTDOMAIN ),
       // 'desc'       => __( 'Street, etc.' ),
        'id'         => $prefix . 'max_guests',
        'type'       => 'text',
        'attributes'  => array(
         'required'    => 'required',
         ),
    ) );
    
$terms = $this->get_age_category_terms();

if (!empty($terms)){    
    
    $cmb->add_field( array(
    'name'           => __( 'Price, ', BA_EVENT_TEXTDOMAIN ).$this->settings['currency'],
    'id'             => $prefix . 'age',
    'type'           => 'multicheck',
    'options_cb'  => array( $this, 'cmb2_get_age_options'),
    // Same arguments you would pass to `get_terms`.
    'get_terms_args' => array(
        'taxonomy'   => 'age',
        'hide_empty' => false,
    ),
) );
} else {
    $cmb->add_field( array(
	'name' => __( 'Price field is not available ', BA_EVENT_TEXTDOMAIN ),
	'desc' => __('Please, ', BA_EVENT_TEXTDOMAIN).'<a href="'.admin_url( 'edit-tags.php?taxonomy=age&post_type=event' ).'">'.__('create age category first.', BA_EVENT_TEXTDOMAIN).'</a>',
	'type' => 'title',
	'id'   => $prefix . 'age_empty',
    ) );
}

//////////// Event dates range ///////////////

//print_r($_POST);

$cmb->add_field( array(
    'name' => __( 'Event Start Date', BA_EVENT_TEXTDOMAIN ),
    'id'   => $prefix . 'start_date',
    'type' => 'text_date_timestamp',
    'date_format' => $this->date_format,
    'attributes'  => array(
         'required'    => 'required',
         ),
  //  'desc' => print_r(get_post_meta($_GET['post'], '_event_start_date', 1), 1).' - '.strtotime("2017-07-17"),
) );

$cmb->add_field( array(
    'name' => __( 'Event End Date', BA_EVENT_TEXTDOMAIN ),
    'id'   => $prefix . 'end_date',
    'type' => 'text_date_timestamp',
    'date_format' => $this->date_format,
    'attributes'  => array(
         'required'    => 'required',
         ),
) );

//////////// Time schedule ////////////////////

$group_field_id = $cmb->add_field( array(
    'id'          => $prefix . 'time_group',
    'type'        => 'group',
    'description' => __( 'Setup time from / time to data', BA_EVENT_TEXTDOMAIN ),
    // 'repeatable'  => false, // use false if you want non-repeatable group
    'options'     => array(
        'group_title'   => __( 'Time group {#}', BA_EVENT_TEXTDOMAIN ), // since version 1.1.4, {#} gets replaced by row number
        'add_button'    => __( 'Add Another Time group', BA_EVENT_TEXTDOMAIN ),
        'remove_button' => __( 'Remove Time group', BA_EVENT_TEXTDOMAIN ),
        'sortable'      => true, // beta
    ),
) );

    $cmb->add_group_field( $group_field_id, array(
    'name' => __( 'Time From', BA_EVENT_TEXTDOMAIN ),
    'desc' => '',
    'id'   => $prefix . 'time_from',
    'type' => 'text_time',
    'time_format' => get_option('time_format'),
    'attributes'  => array(
         'required'    => 'required',
         ),
) );

    $cmb->add_group_field( $group_field_id, array(
    'name' => __( 'Time To', BA_EVENT_TEXTDOMAIN ),
    'desc' => '',
    'id'   => $prefix . 'time_to',
    'type' => 'text_time',
    'time_format' => get_option('time_format'),
    'attributes'  => array(
         'required'    => 'required',
         ),
) );

////////////////////////////

    $cmb->add_field( array(
    'name'    => __( 'Available days', BA_EVENT_TEXTDOMAIN ),
    'desc'    => __( 'Select available days for this event', BA_EVENT_TEXTDOMAIN ),
    'id'      => $prefix . 'week',
    'type'    => 'multicheck_inline',
    'options_cb' => array( $this, 'cmb2_get_week_options'),
) );

   $cmb->add_field( array(
    'name' => __( 'Exclude Dates', BA_EVENT_TEXTDOMAIN ),
    'id'   => $prefix . 'date_ex',
    'type' => 'text_date_timestamp',
    'date_format' => $this->date_format,
    'after_field' => array( $this, 'cmb2_after_date_ex' ),
) );

//////////////// Images /////////////////////

    $cmb->add_field( array(
		'name'         => __( 'Images', BA_EVENT_TEXTDOMAIN ),
		'desc'         => __( 'Upload or add multiple images.', BA_EVENT_TEXTDOMAIN ),
		'id'           => $prefix . 'file_list',
		'type'         => 'file_list',
		'preview_size' => array( 100, 100 ), // Default: array( 50, 50 )
	) );

//////////// Features section ///////////////////

 if (isset($this->settings['event_features_active']) && $this->settings['event_features_active']){
    
    $cmb->add_field( array(
        'name'       => __( 'Features title', BA_EVENT_TEXTDOMAIN ),
       // 'desc'       => __( 'Street, etc.' ),
        'id'         => $prefix . 'features_title',
        'classes' => array( 'q_translatable' ),
        'type'       => 'text',
    ) );
    
    $cmb->add_field( array(
        'name'       => __( 'Features text', BA_EVENT_TEXTDOMAIN ),
       // 'desc'       => __( 'Street, etc.' ),
        'id'         => $prefix . 'features_text',
        'classes' => array( 'q_translatable' ),
        'type'       => 'textarea',
    ) );

   $cmb->add_field( array(
    'name'           => __( 'Features list', BA_EVENT_TEXTDOMAIN ),
    'id'             => $prefix . 'features_list',
    'taxonomy'       => 'ba-features', //Enter Taxonomy Slug
    'type'           => 'taxonomy_multicheck',
    'text'           => array(
        'no_terms_text' => __( 'Sorry, no terms could be found.', BA_EVENT_TEXTDOMAIN )
    ),
    'remove_default' => 'true'
) );

}
    
///////////////// Testi section ///////////////

if (isset($this->settings['event_testi_active']) && $this->settings['event_testi_active']){    
    
    $cmb->add_field( array(
    'name'    => __( 'Testimonials', BA_EVENT_TEXTDOMAIN ),
    'desc'    => __( 'Select testimonials to show with this event', BA_EVENT_TEXTDOMAIN ),
    'id'      => $prefix . 'testi',
    'type'    => 'multicheck',
    'options_cb' => array( $this, 'cmb2_get_testi_options' ),
) );

}

///////////////  FAQ section /////////////////////////

if (isset($this->settings['event_faq_active']) && $this->settings['event_faq_active']){

    $cmb->add_field( array(
    'name'    => __( 'FAQ', BA_EVENT_TEXTDOMAIN ),
    'desc'    => __( 'Select FAQ to show with this event', BA_EVENT_TEXTDOMAIN ),
    'id'      => $prefix . 'faq',
    'type'    => 'multicheck',
    'options_cb' => array( $this, 'cmb2_get_faq_options' ),
) );

}

///////////// Recommended Events ///////////////////////////////
    
    $cmb->add_field( array(
    'name'    => __( 'You may also like', BA_EVENT_TEXTDOMAIN ),
    'desc'    => __( 'Recommended events to show with this event', BA_EVENT_TEXTDOMAIN ),
    'id'      => $prefix . 'events',
    'type'    => 'multicheck',
    'options_cb' => array( $this, 'cmb2_get_event_options' ),
) );

}

////////////////////
///////////////////////////////////////

function cmb2_after_date_ex($field_args, $field){
    
    $ex_arr = array();
    $ex_text = '';
    $ex_inputs = '';
    
    if (isset($_GET['post']))
          $ex_arr = get_post_meta($_GET['post'], '_event_excluded_dates', true);      
          
    if (!empty($ex_arr)){
        $date_format = get_option('date_format');
        foreach($ex_arr as $key => $timestamp){
            $ex_text .= '<div>'.date($this->date_format, $timestamp).'<span title="'.__('Delete', BA_EVENT_TEXTDOMAIN).'" class="del-ex-date dashicons dashicons-trash"></span><input type="hidden" name="_event_excluded_dates[]" value="'.date($this->date_format, $timestamp).'"></div>';
          //  $ex_inputs .= '<input type="hidden" name="_event_excluded_dates[]" value="'.$timestamp.'">';
        }
    }      
    
    echo '<span class="button" id="exclude_date">'.__('Exclude selected date', BA_EVENT_TEXTDOMAIN).'</span>
    <div id="excluded_dates">'.$ex_text.'</div>';
   return;
}

////////////////////////////////

function get_age_category_terms(){

    $args = array(
        'taxonomy'   => 'age',
        'hide_empty' => false,
    );

    $terms = (array) cmb2_utils()->wp_at_least( '4.5.0' )
        ? get_terms( $args )
        : get_terms( $args['taxonomy'], $args );
    
    return $terms;
}

/////////////////////////

function cmb2_get_age_options($field) {

    $terms = $this->get_age_category_terms();
    
    $price_arr = array();
        if (isset($_GET['post']))
          $price_arr = get_post_meta($_GET['post'], '_event_price', true);

    // Initate an empty array
    $term_options = array();
    if ( ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            $price = (!empty($price_arr)) ? $price_arr[$term->term_id] : '';
            
            $term_options[ $term->term_id ] = '<span class="age_title">'. $term->name . '</span> '.$this->settings['currency'].' <input class="set-age-price" name="_event_price['.$term->term_id.']" type="text" value="'.$price.'">';
        }
    }
    return $term_options;
}

//////////////////

/////////////////////////////////////

function cmb2_get_testi_options(){
    return $this->cmb2_get_posts_options('testi');
}

function cmb2_get_faq_options(){
    return $this->cmb2_get_posts_options('faq');
}

function cmb2_get_event_options(){
    return $this->cmb2_get_posts_options($this->event_post_type);
}


function cmb2_get_posts_options($post_type) {
    $args = array(
        'post_type'   => $post_type,
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC',
    );
    $posts = get_posts( $args );
    $post_options = array();
    if ( $posts ) {
        foreach ( $posts as $post ) {
          $post_options[ $post->ID ] = $post->post_title;
        }
    }
    return $post_options;
}

///////////////////////////////////////
    /**
	 * Get months locale names.
     * @return array
	 */
    function get_months_arr(){ 
    
    return array(
      'January' => __('January', BA_EVENT_TEXTDOMAIN ),
      'February' => __('February', BA_EVENT_TEXTDOMAIN ),
      'March' => __('March', BA_EVENT_TEXTDOMAIN ),
      'April' => __('April', BA_EVENT_TEXTDOMAIN ),
      'May' => __('May', BA_EVENT_TEXTDOMAIN ),
      'June' => __('June', BA_EVENT_TEXTDOMAIN ),
      'July' => __('July', BA_EVENT_TEXTDOMAIN ),
      'August' => __('August', BA_EVENT_TEXTDOMAIN ),
      'September' => __('September', BA_EVENT_TEXTDOMAIN ),
      'October' => __('October', BA_EVENT_TEXTDOMAIN ),
      'November' => __('November', BA_EVENT_TEXTDOMAIN ),
      'December' => __('December', BA_EVENT_TEXTDOMAIN ),  
     );
          
    }

//////////////////////////////////////////

function get_week_days_arr(){ 
    
   return !$this->week_first_day ? array(
      0 => __('Sun', BA_EVENT_TEXTDOMAIN ),
      1 => __('Mon', BA_EVENT_TEXTDOMAIN ),
      2 => __('Tue', BA_EVENT_TEXTDOMAIN ),
      3 => __('Wed', BA_EVENT_TEXTDOMAIN ),
      4 => __('Thu', BA_EVENT_TEXTDOMAIN ),
      5 => __('Fri', BA_EVENT_TEXTDOMAIN ),
      6 => __('Sat', BA_EVENT_TEXTDOMAIN ),   
   ) : array(
      1 => __('Mon', BA_EVENT_TEXTDOMAIN ),
      2 => __('Tue', BA_EVENT_TEXTDOMAIN ),
      3 => __('Wed', BA_EVENT_TEXTDOMAIN ),
      4 => __('Thu', BA_EVENT_TEXTDOMAIN ),
      5 => __('Fri', BA_EVENT_TEXTDOMAIN ),
      6 => __('Sat', BA_EVENT_TEXTDOMAIN ),
      7 => __('Sun', BA_EVENT_TEXTDOMAIN ),   
   );     
}

function get_week_day($i){
    $arr = $this->get_week_days_arr();
    if ($i > 7 || $i < 0) $i = 1; 
    return !$this->week_first_day ? $arr[$i%7] : $arr[$i];
}

function cmb2_get_week_options(){
   return $this->get_week_days_arr();    
}

function is_week_day_av($event_id, $i){
    $days_arr = get_post_meta($event_id, '_event_week', true);
    if (empty($days_arr)){
       return false;
    } else {
       return (in_array($i, $days_arr)) ? true : false; 
    }
}

function is_excluded_date($event_id, $date){
    /// $date in Y-m-d format
    $ex_arr = get_post_meta($event_id, '_event_excluded_dates', true);
    if (empty($ex_arr)){
       return false;
    } else {
       return (in_array(strtotime($date), $ex_arr)) ? true : false; 
    }
}

/////////////////

function cmb2_after_row(){

 echo '<h4>'.__( 'Availability calendar', BA_EVENT_TEXTDOMAIN ).'</h4>
      ';
      
 $output = '';     
      
 $output .= '<div id="booking-cal-block" data-event="">
    </div>';
        $output .= '<div id="av_times_block" class="center-block">
        </div>';
  
  $output .= '<div id="select_price_block" class="center-block">
         </div>
         
         <div id="pre_booking"></div>';
         
  echo $output;          

}

function cmb2_after_row_amount(){

 echo '<div id="spin_amount"></div>';

}

function cmb2_event_details($field, $value, $object_id, $object_type, $field_type){
     
     $output ='';
      
      $event_id = get_post_meta( $object_id, '_booking_event', true );
      
      $output .= '<div id="thanks-block">';

      $output .= __('Reservation number: ', BA_EVENT_TEXTDOMAIN). get_the_title($object_id) . '<br><br>
      </div>';

      $output .= '<div id="thanks">
      ';


      $output .= '<table><tbody>
          
          <tr><td>'.__('Event Title: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_the_title($event_id).'
          </td></tr>

          <tr><td>'.__('Event Date: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.date(get_option('date_format'), get_post_meta( $object_id, '_booking_date_timestamp', true )).'
          </td></tr>
          
          <tr><td>'.__('Event Time: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.date(get_option('time_format'), get_post_meta( $object_id, '_booking_event_time', true )).'
          </td></tr>
          
          <tr><td>'.__('Event Address: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_post_meta( $event_id, '_event_address', true ).'
          </td></tr> 
          
          <tr><td>'.__('Tickets: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$this->get_tickets_list($event_id, get_post_meta( $object_id, '_booking_event_guests', true )).'
          </td></tr>

          <tr><td>'.__('TOTAL PRICE: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$this->format_currency(get_post_meta( $object_id, '_booking_price', true )).'
          </td></tr>
      </tbody></table>';

      $output .= '</div>';  
    
    echo $output;
}

//////////////////////
///////////////////////////////////

function get_max_price($event_id){
    $output = 0;
    
    $price_arr = (array)get_post_meta($event_id, '_event_price', true);
    
    foreach($price_arr as $price)
     if ($output < $price) $output = $price;
    
    return $output;
}

/////////////////////////

function get_event_preview_micro($event_id){
   $output = '';
   
   $image = wp_get_attachment_image_src( get_post_thumbnail_id( $event_id ), 'ba-event-micro' ); 
    
   $output .= '
                <div class="ba_event_widget_box" style="background:url(\''.$image[0].'\') center center no-repeat; background-size: cover;">
                                <span class="ba_event_widget_price">'.$this->format_currency($this->get_max_price($event_id)).'</span>
                                <a class="ba_event_widget_info" href="'.get_permalink($event_id).'" class="ba_event_widget_btn"><h4>'.get_the_title($event_id).'</h4></a>
                 </div>';
   return $output; 
}

//////////////////////////////////

public function get_event_may_like(){
    
    global $post;
    
    $event_arr = get_post_meta($post->ID, '_event_events', true);
    $output = '';
    if (!empty($event_arr)){  
       foreach ($event_arr as $event_id){
          $output .= $this->get_event_preview_micro($event_id);
       }
   }
   return $output; 
}

//////////////////////////////////

function all_events($atts){
    $output = '';
    
    $args = shortcode_atts( array(
        'title' => ''
	), $atts, 'all-events' );
    
    $output .= $this->get_event_preview();
    
    return $output;
}

////////////////////////////

function get_event_preview(){
   $output = ''; 
   
   $paged = is_front_page() ? (get_query_var('page') ? get_query_var('page') : 1) : (get_query_var('paged') ? get_query_var('paged') : 1);
   
   $args = array(
               'post_type'   => $this->event_post_type,
               'post_status' => 'publish',
               'paged'=> $paged,
               'orderby' => 'menu_order',
               'order' => 'ASC',
                  );
                  
    $args = apply_filters('ba_event_get_all_events_query', $args);              

    $the_query = new WP_Query( $args );
    $max_num_pages = $the_query->max_num_pages;
      $event_list = '';
     while ( $the_query->have_posts() ) : $the_query->the_post();
       $event['ID'] = get_the_ID();
       $image = wp_get_attachment_image_src( get_post_thumbnail_id( $event['ID'] ), 'ba-event-micro' );
       $event['image_src'] = $image[0];
       $time_arr = $this->get_time_arr($event['ID']);
       $time_lines = '';
       
       foreach ($time_arr as $times){
                    $time_lines .= $times['_event_time_from'].' - '. $times['_event_time_to'] . ', ';
                }
       
       $event['time_lines'] = substr($time_lines, 0, -2);
       $event['max_price'] = $this->format_currency($this->get_max_price($event['ID']));
       $event['address'] = get_post_meta($event['ID'], '_event_address', 1);
       $event['url'] = get_permalink($event['ID']);
       $event['title'] = get_the_title($event['ID']);
       
       $event_list_tmp ='
                <div class="ba_event_list_block">
                        <a href="'.$event['url'].'">
                        <div class="ba_event_list_img" style="background: url(\''.$event['image_src'].'\') center center no-repeat; background-size: cover;">
                            <span class="ba_event_widget_price">'.$event['max_price'].'</span>       
                        </div>
                        </a>

                        <div class="ba_event_list_info">
                                <a href="'.$event['url'].'"><h3>'.$event['title'].'</h3></a>
                                <div class="event_time_range"><span class="event_time_range_icon"></span>'.$event['time_lines'].'</div>
                                <div class="event_address"><span class="event_address_icon"></span>
                                        '.$event['address'].'
                                </div>
                        </div>
                </div> <!-- -->
                 ';
        $event_list_tmp = apply_filters('ba_event_get_all_events_item', $event_list_tmp, $event);
                 
        $event_list .= $event_list_tmp;         
                
     endwhile;
/* Restore original Post Data */
wp_reset_postdata();  
   
   if($event_list) $output .= '<div class="ba_event_list_row">'.$event_list.'</div>
   '.ba_event_pager($max_num_pages, $paged);
    
   return $output; 
}

//////////////////////
/////////////////////////////////////////

function update_event_post( $post_id, $post, $update ) {

    if (( 'event' != $post->post_type )||( ! is_admin() )) {
        return;
    }
    
    // we are in admin edit screen now, so we have valid $_POST
    
     if (isset($_POST['_event_price'])) {
        update_post_meta($post->ID, '_event_price', $_POST['_event_price']);
        $age_ids = $_POST['_event_age'];
        $age_ids = array_map( 'intval', $age_ids );
        $age_ids = array_unique( $age_ids );
        // assign age taxonomy terms to the event
        $term_taxonomy_ids = wp_set_object_terms( $post->ID, $age_ids, 'age', false );
     }
     
     if (isset($_POST['_event_excluded_dates'])) {
        $res_arr = array();
        foreach ($_POST['_event_excluded_dates'] as $ind => $date){
            $res_arr[$ind] = strtotime($this->date_to_sql($date));
        }
        $res_arr = array_unique($res_arr);
        $res_arr = array_values($res_arr);
        update_post_meta($post->ID, '_event_excluded_dates', $res_arr);
     }
     update_post_meta($post->ID, '_event_date_ex', '');
     
} 
//////////////////////
function update_booking_title( $post_id, $post, $update ) {

    // If this isn't a 'booking' post, don't update it.
    if ( 'booking' != $post->post_type || !is_admin() ) {
        return;
    }
    
    // we are in admin edit screen now, so we have valid $_POST
    
    if (!empty($_POST['event_id']) && !empty($_POST['event_date']) && !empty($_POST['event_time']) && !empty($_POST['event_guests'])){
        update_post_meta($post_id, '_booking_event', $_POST['event_id']);
        update_post_meta($post_id, '_booking_event_guests', $_POST['event_guests']);
        update_post_meta($post_id, '_booking_event_time', $_POST['event_time']);
        update_post_meta($post_id, '_booking_event_date', $_POST['event_date']);
        update_post_meta($post_id, '_booking_date_timestamp', strtotime($_POST['event_date']));
        $tickets = $this->get_asked_tickets($_POST['event_id'], $_POST['event_guests']);
        $this->update_av_cal($_POST['event_id'], $_POST['event_date'], $_POST['event_time'], $tickets);
        update_post_meta($post_id, '_booking_tickets', $tickets);
        
        $price_arr = $this->get_all_rates_by_ap_id($_POST['event_id'], get_post_meta( $post_id, '_booking_event_guests', true ));
        update_post_meta($post_id, '_booking_price', $price_arr['total']);
        update_post_meta($post_id, '_booking_price_tax', $price_arr['total_tax']);
        update_post_meta($post_id, '_booking_price_clear', $price_arr['total_clear']);
        
        error_log('processing $_POST ...... ');
    }
    
   // error_log(get_post_meta( $post_id, '_booking_tickets', true ));
    
    $user_id = $this->create_customer(array());

    if (strpos( $post->post_title , '-')) {
        return; //// break iteration updates
    }  //// continue for first admin update only to change title and accepted status
    
  update_post_meta($post_id, '_booking_accepted', 1);
  update_post_meta($post_id, '_booking_paid', 0);
  update_post_meta($post_id, '_booking_paid_by', 'cash');
  update_post_meta($post_id, '_booking_token', '');
  update_post_meta($post_id, '_booking_created', time());

  $title = date('ymd').'-'.date('His').mt_rand(10, 99);
  
  remove_action( 'wp_insert_post', array( $this, 'update_booking_title'), 10, 3 );

  wp_update_post( array(
      'ID'           => $post_id,
      'post_title'   => $title,
  ) );

}

/////////////////

function create_customer($post_arr){
  $output = 0;

    if ( !empty($post_arr['email']) || !empty($_POST['_booking_email']) ){

         if (!empty($post_arr['email'])){
         $email = $post_arr['email'];
         $first_name = $post_arr['first_name'];
         $last_name = $post_arr['last_name'];
         $tel1 = $post_arr['tel1'];
         } else {
         $email = sanitize_email($_POST['_booking_email']);
         $first_name = sanitize_text_field($_POST['_booking_first_name']);
         $last_name = sanitize_text_field($_POST['_booking_last_name']);
         $tel1 = sanitize_text_field($_POST['_booking_phone']);
         }

        $user_id = username_exists( $email );
        
        if ( !$user_id and email_exists($email) == false ) {
          $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
          $user_id = wp_create_user( $email, $random_password, $email );
          $output = $user_id;

          wp_update_user(
          array(
          'ID'       => $user_id,
          'nickname' => $email,
          'display_name' => $first_name.' '.$last_name,
          )
          );
          $user = new WP_User( $user_id );
          $user->set_role( 'customer' );

          update_user_meta($user_id, 'email', $email);
          update_user_meta($user_id, '_admin_notes', __('new customer', BA_EVENT_TEXTDOMAIN));

          } else {
            $output = $user_id;
            wp_update_user(
            array(
            'ID'       => $user_id,
            'display_name' => $first_name.' '.$last_name,
           )
          );
            }
          update_user_meta($user_id, 'first_name', $first_name);
          update_user_meta($user_id, 'last_name', $last_name);
          update_user_meta($user_id, 'tel1', $tel1);
    }
  return $output;
}

/////////////////////////////////

function create_booking($post_arr){

       $post_id = 0;
       $user_id = $this->create_customer($post_arr);

       if ($user_id){

       $price_arr = $this->get_all_rates_by_ap_id($post_arr['event_id'], $post_arr['event_guests']);  

       $post_id = wp_insert_post(array (
    'post_type' => 'booking',
    'post_title' => date('ymd').'-'.date('His').mt_rand(10, 99),
    'post_content' => '',
    'post_status' => 'publish',
    'comment_status' => 'closed',
    'post_author'   => 1,
    'meta_input'   => array(
        '_booking_event' => $post_arr['event_id'],
        '_booking_phone' => $post_arr['tel1'],
        '_booking_email' => $post_arr['email'],
        '_booking_event_guests' => $post_arr['event_guests'],
        '_booking_tickets' => $post_arr['tickets'],
        '_booking_first_name' => $post_arr['first_name'],
        '_booking_last_name' => $post_arr['last_name'],
        '_booking_date_timestamp' => strtotime($post_arr['event_date']),
        '_booking_event_date' => $post_arr['event_date'],
        '_booking_event_time' => $post_arr['event_time'],
        '_booking_price' => $price_arr['total'],
        '_booking_price_tax' => $price_arr['total_tax'],
        '_booking_price_clear' => $price_arr['total_clear'],
        '_booking_accepted' => 0,
        '_booking_paid' => 0,
        '_booking_paid_by' => '',
        '_booking_token' => '',
        '_booking_created' => time(),
    ),
       ));
       
       if ($post_id){
          // update av calendar
          $this->update_av_cal($post_arr['event_id'], $post_arr['event_date'], $post_arr['event_time'], $post_arr['tickets']);
       }

       do_action('ba_event_create_booking', $post_id);

     }
  return $post_id;
}

/////////////////////

function event_content($content){
   global $post;
   $output = $content;
    
   if (is_single() && $post->post_type == $this->event_post_type){
       remove_filter( 'the_content', array( $this, 'event_content'));
       $output = apply_filters( 'ba_event_page_content', $content);
       add_filter( 'the_content', array( $this, 'event_content'));
   } 
    
   return $output; 
}

/////////////////////////

function event_page_slider($content){
    global $post;
    
    $output = $content;
    
    if ($this->settings['event_slider_active'])
    $output .= ba_event_get_event_slider($post->ID);
 
    return $output;
}

////////////////////////

function event_page_features($content){
    global $post;
    
    $output = $content;
    
    if ($this->settings['event_features_active'])
    $output .= ba_event_get_features($post->ID);
 
    return $output; 
}

////////////////////////

function event_page_map($content){
    global $post;
    
    $output = '';
    
    if ($this->settings['event_address_active']){
        $output .= '<div id="ba_event_address">' .__('Address: ', BA_EVENT_TEXTDOMAIN) . get_post_meta( $post->ID, '_event_address', true ) . '</div>';
    }
    
    if ($this->settings['google_map_active']){
        $output .= '<div id="ba_event_map">' . $this->get_event_map($post->ID).'</div>';
    }
    
    if ($output){
        $output = '<div id="ba_event_address_block">'.$output.'</div>';
    }
    
    $output = $content.$output;
 
    return $output; 
}

////////////////////////

function event_page_testi($content){
    global $post;
    
    $output = $content;
    
    if ($this->settings['event_testi_active'])
    $output .= ba_event_get_testimonials($post->ID);
 
    return $output; 
}

////////////////////////////

function event_page_faq($content){
    global $post;
    
    $output = $content;
    
    if ($this->settings['event_faq_active'])
    $output .= ba_event_get_faq($post->ID);
 
    return $output; 
}

///////////////////////////////

function event_page_avcal($content){
    global $post;
    
    $output = $content;
    
    if ($this->settings['event_avcal_active']){
      $output .= '
      <div id="ba_event_booking_cal">
         <h2 class="event_cal_title">
                '.__('Booking calendar', BA_EVENT_TEXTDOMAIN).'
         </h2>
         <div id="booking-cal-block" data-event="'.$post->ID.'">
         '.$this->get_booking_calendar($post->ID).'
         </div>
         <div id="av_times_block" class="center-block"><h3>'.__('Select a date to view alailable time', BA_EVENT_TEXTDOMAIN).'</h3>
         </div>
         <div id="select_price_block" class="center-block">
         </div>'. $this->get_booking_cal_form($post->ID).'
     </div>';
    }
 
    return $output; 
}

///////////////////////////////

////////// Thank You e-mail  /////

public function thanks_email($reserv_code){

     if ($reserv_code){

      $page = get_page_by_title( $reserv_code, OBJECT, 'booking' );

      if (!empty($page)){
      $event_id = get_post_meta( $page->ID, '_booking_event', true );
      
      add_filter( 'wp_mail_content_type', array($this, 'set_html_mail_content_type') );
      
      $date_obj = new DateTime( get_post_meta( $page->ID, '_booking_event_date', true ) );
      
      $fields = apply_filters('ba_event_extra_booking_fields_view', array(), $page->ID);
      $fields_output = '';
    
    if (!empty($fields)){
        foreach ($fields as $field_title => $field_content){
            $fields_output .= '
            '.$field_title.': '.$field_content.'
            ';
        }
    }
    
    $header_img = $this->settings['confirm_email_header_image'] ? '<div align="center"><img src="'.$this->settings['confirm_email_header_image'].'" alt="header" style="max-height: 150px;"/></div>' : '';
    
       $body = '<!DOCTYPE html>
<html dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html;
charset=UTF-8">
<title>'.__('Tour booking', BA_EVENT_TEXTDOMAIN).'</title>
</head>
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">

<div style="width:80% ;background-color: #FFFFFF;margin:auto;color:#003366">
    '.$header_img.'
	<div align="center">

       <div><p style="height:10px;"><font size="3" face="Arial"><strong><em>'.__($this->settings['confirm_email_title']).'</em></strong></font></p></div><br>
            
        <div align="left">
		<p style="height:25px;"><font color="#434343" size="2" face="Arial"><em>'.str_replace(array("\n"), "<br/>", sprintf(__($this->settings['confirm_email_header']), get_post_meta( $page->ID, '_booking_first_name', true ).' '.get_post_meta( $page->ID, '_booking_last_name', true )) ).'</em></font></p>
		</div>
		</br>
	</div>
<table width="100%"  style="border:1px solid #aaa;" cellpadding="2" cellspacing="3">

   <tr>
    <td style="background-color:'.$this->settings['color_main_cal_wd_bg'].'; padding-left:10px;">
     <i><font size="3" face="Arial">';
     
    $body .= '<h4 style="padding-top:10px;">'.__('Event title: ', BA_EVENT_TEXTDOMAIN).get_the_title($event_id).'</h4>';   
    
    $body .= '<h4>'.__('Event Address: ', BA_EVENT_TEXTDOMAIN).get_post_meta( $event_id, '_event_address', true ).'</h4>
    <h4>'.__('Status: Booked', BA_EVENT_TEXTDOMAIN).'</h4></font></i>';
    
    $body .= '    </td>
  </tr>
  <tr>
    <td>
    <table width="100%" style="border:1px  solid #aaa; color:#434343;font-family:Arial; font-size:14" border="0" cellpadding="2" cellspacing="5">
        <tr>
            <td width="50%"><strong>'.__('Your Confirmation Number: ', BA_EVENT_TEXTDOMAIN).'</strong></td>
            <td width="50%"><strong>'.$reserv_code.'</strong></td>
        </tr>
         <tr>
            <td colspan="2"> </td>
        </tr>
    </table>';
    
    
    $body .= '    </td>
    </tr>
     <tr>
    <td></td>
  </tr><tr>
    <td>
        <table width="100%"  style="border:1px  solid #aaa; color:#434343;font-family:Arial; font-size:14" cellspacing="3" cellpadding="5">
                    <tr >
                        <th colspan="2" style="background-color:'.$this->settings['color_main_cal_month_bg'].'; color:'.$this->settings['color_main_cal_month_c'].'"  align="left">'.__('TOUR DETAILS', BA_EVENT_TEXTDOMAIN).'</th>
                    </tr>
                    <tr>
                        <td width="50%">'.__('Event Date', BA_EVENT_TEXTDOMAIN).'</td>
                        <td width="50%">'.$date_obj->format(get_option('date_format')).'</td>
                    </tr>
                    <tr>
                        <td>'.__('Event Time start', BA_EVENT_TEXTDOMAIN).'</td>
                        <td>'.date(get_option('time_format'), get_post_meta( $page->ID, '_booking_event_time', true )).'</td>
                    </tr>
                    
                    <tr><th width="30%" align="left">'.__('Tickets', BA_EVENT_TEXTDOMAIN).'</th><td width="30%" align="left"><strong>';
            
    $prices = (array)get_post_meta($event_id, '_event_price', true);
    $event_age_arr = (array)get_post_meta($event_id, '_event_age', true);
    $event_guests = (array)get_post_meta($page->ID, '_booking_event_guests', true);
    
    arsort($prices, SORT_NUMERIC);
   
   foreach ($prices as $age_id => $price){
        if (isset($event_guests[$age_id]) && $event_guests[$age_id]){
            $term = get_term_by( 'id', $age_id, 'age' );
            $body .= $term->name.' x '.$event_guests[$age_id].' x '.$this->format_currency($price).'
        <br>';
        }
   }   
                                     
                    $body .= '</strong></td></tr>';
                    
                    $body .= '<tr>
                        <td width="50%">'.__('First name: ', BA_EVENT_TEXTDOMAIN).'</td>
                        <td width="50%">'.get_post_meta( $page->ID, '_booking_first_name', true ).'</td>
                    </tr>
                    
                    <tr>
                        <td width="50%">'.__('Last name: ', BA_EVENT_TEXTDOMAIN).'</td>
                        <td width="50%">'.get_post_meta( $page->ID, '_booking_last_name', true ).'</td>
                    </tr>
                    
                    <tr>
                        <td width="50%">'.__('Contacts: ', BA_EVENT_TEXTDOMAIN).'</td>
                        <td width="50%">'.get_post_meta( $page->ID, '_booking_email', true ).' '.get_post_meta( $page->ID, '_booking_phone', true ).'</td>
                    </tr>';
                    
        $body .= '            
         </table>
    </td>
  </tr><tr><td> 
    </td>
  </tr>
  
  <tr>
     <td>
        <table width="100%" style="border:1px solid #aaa; color:#434343;font-family:Arial; font-size:14" cellspacing="3" cellpadding="5">
                    <tr>
                        <th colspan="2"  style="background-color:'.$this->settings['color_main_cal_month_bg'].'; color:'.$this->settings['color_main_cal_month_c'].'" align="left">'.__('PAYMENT DETAILS', BA_EVENT_TEXTDOMAIN).'</th>
                    </tr>
                    <tr>

                        <td width="51%"><b>'.__('Total Amount:', BA_EVENT_TEXTDOMAIN).'</b></td>
                        <td width="49%"><b>'.$this->format_currency(get_post_meta( $page->ID, '_booking_price', true )).'</b></td>
                    </tr>
                    <tr>

                        <td width="51%"><b>'.__('PAID:', BA_EVENT_TEXTDOMAIN).'</b></td>
                        <td width="49%"><b>'.$this->format_currency(get_post_meta( $page->ID, '_booking_paid', true )).'</b></td>
                    </tr></table>
    </td>
  </tr>';
  
  $footer_logo = $this->settings['confirm_email_footer_image'] ? '</br></br>
<p align="center"><img src="'.$this->settings['confirm_email_footer_image'].'" alt="logo" style="max-height: 150px;"></p>' : '';
  
  $body .= '</table><br>
<table width="100%" border="0">
      <tr>
<td>         
	<div align="left">
	<p><em><font face="Arial" color="#434343" size="2">'.str_replace(array("\n"), "<br/>", __($this->settings['confirm_email_footer']) ).'</em></font></p>
	</div>
</td>
      </tr>
</table>
    '.$footer_logo.'
    </div>
    </body>
</html>';

/*
      $body = __($this->settings['confirm_email_header']);

      $body .= '

      '.__('Booking details', BA_EVENT_TEXTDOMAIN).'
      
      '.__('Reservation number: ', BA_EVENT_TEXTDOMAIN).$reserv_code.'

      '.__('Event title: ', BA_EVENT_TEXTDOMAIN).get_the_title($event_id).'
      '.__('Event Address: ', BA_EVENT_TEXTDOMAIN).get_post_meta( $event_id, '_event_address', true ).'     
      '.__('Event Date: ', BA_EVENT_TEXTDOMAIN).$date_obj->format(get_option('date_format')).'
      '.__('Event Time: ', BA_EVENT_TEXTDOMAIN).date(get_option('time_format'), get_post_meta( $page->ID, '_booking_event_time', true )).'
      '.__('Tickets: ', BA_EVENT_TEXTDOMAIN).get_post_meta( $page->ID, '_booking_tickets', true ).'
      ';
      
       $price_arr = (array)get_post_meta($event_id, '_event_price', true);
       $event_age_arr = (array)get_post_meta($event_id, '_event_age', true);
       $event_guests = (array)get_post_meta($page->ID, '_booking_event_guests', true);
   
   foreach($event_age_arr as $age_id){
        $ages_new[(int)$price_arr[$age_id]] = $age_id;
    }
    krsort($ages_new, SORT_NUMERIC);
    reset($ages_new);
   
   foreach ($ages_new as $age_id){
        if (isset($event_guests[$age_id]) && $event_guests[$age_id]){
            $term = get_term_by( 'id', $age_id, 'age' );
            $body .= $term->name.' x '.$event_guests[$age_id].' x '.$this->format_currency($price_arr[$age_id]).'
        ';
        }
   }
      $body .= '
      '.__('First name: ', BA_EVENT_TEXTDOMAIN).get_post_meta( $page->ID, '_booking_first_name', true ).'
      '.__('Last name: ', BA_EVENT_TEXTDOMAIN).get_post_meta( $page->ID, '_booking_last_name', true ).'
      '.__('Contacts: ', BA_EVENT_TEXTDOMAIN).get_post_meta( $page->ID, '_booking_email', true ).' '.get_post_meta( $page->ID, '_booking_phone', true ).'
      '.$fields_output;

      $body .= '

      '.__('TOTAL PRICE: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(get_post_meta( $page->ID, '_booking_price', true ));

      $body .= '

      '.__('PAID: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(get_post_meta( $page->ID, '_booking_paid', true ));

      $body .= '

      --
      ';
      */
      
      $headers[] = 'From: '.$this->settings['confirm_email_from_name'].' <'.$this->settings['confirm_email_from_address'].'>';

      wp_mail( get_post_meta( $page->ID, '_booking_email', true ), $this->settings['confirm_email_subject'], $body, $headers);
      wp_mail( get_bloginfo( 'admin_email' ), $this->settings['confirm_email_subject'], $body, $headers);

      remove_filter( 'wp_mail_content_type', array($this, 'set_html_mail_content_type') );
      
      }
  }

}

/////////////////////////////

function get_extra_booking_fields($booking_id){
    
    $output = '';
    
    $fields = apply_filters('ba_event_extra_booking_fields_view', array(), $booking_id);
    
    if (!empty($fields)){
        foreach ($fields as $field_title => $field_content){
            $output .= '
            <tr><td>'.$field_title.'
            </td><td>'.$field_content.'
            </td></tr>
            ';
        }
    }
    
    return $output;
}

////////// Thank You page  /////

function thanks_page_shortcode(){

   $output = '';

   $reserv_code = '';
    if (!empty($_POST['custom'])) $reserv_code = $_POST['custom'];
    elseif (get_query_var('reserv_code')) $reserv_code = get_query_var('reserv_code');

     if ($reserv_code){
      // validate $reserv_code
      $page = get_page_by_title( $reserv_code, OBJECT, 'booking' );
      if (!empty($page)){ 
        
      $event_id = get_post_meta( $page->ID, '_booking_event', true );

      $output .= '<div id="thanks-block">';

      $output .= __('Reservation number: ', BA_EVENT_TEXTDOMAIN). $reserv_code . '<br><br>
      </div>';

      $output .= '<h2 id="title1">'.__('Your reservation details', BA_EVENT_TEXTDOMAIN).'</h2>';

      $output .= '<div id="thanks">';
      
      $date_obj = new DateTime( get_post_meta( $page->ID, '_booking_event_date', true ) );
          
      $output .= '<table><tbody>
          <tr><td>'.__('First name: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_post_meta( $page->ID, '_booking_first_name', true ).'
          </td></tr>
          <tr><td>'.__('Last name: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_post_meta( $page->ID, '_booking_last_name', true ).'
          </td></tr>
          
          <tr><td>'.__('Event Title: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_the_title($event_id).'
          </td></tr>

          <tr><td>'.__('Event Date: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$date_obj->format(get_option('date_format')).'
          </td></tr>
          
          <tr><td>'.__('Event Time: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.date(get_option('time_format'), get_post_meta( $page->ID, '_booking_event_time', true )).'
          </td></tr>
          
          <tr><td>'.__('Event Address: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_post_meta( $event_id, '_event_address', true ).'
          </td></tr> 
          
          <tr><td>'.__('Tickets: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$this->get_tickets_list($event_id, get_post_meta( $page->ID, '_booking_event_guests', true )).'
          </td></tr>
          
          <tr><td>'.__('Contacts: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_post_meta( $page->ID, '_booking_email', true ).'<br>'.get_post_meta( $page->ID, '_booking_phone', true ).'
          </td></tr>';
          
          $output .= $this->get_extra_booking_fields($page->ID);
          
          $output .= '<tr><td>'.__('TOTAL PRICE: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$this->format_currency(get_post_meta( $page->ID, '_booking_price', true )).'
          </td></tr>
      </tbody></table>';

      if ($this->settings['print_button'])
      $output .= '<input type="button" class="bc-button-color" id="print" value="'.__('PRINT', BA_EVENT_TEXTDOMAIN).'">';

      $output .= '</div>';
      }
  }

   return $output;
}

/////////////////////////////////

function get_event_booking_details($event_id, $event_date, $event_time, $event_guests_arr){
    
    $output = '';
    
    $date_obj = new DateTime( $event_date );
    
    $output .= '<table id="event_booking_details"><tbody>
          
          <tr><td>'.__('Event Title: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_the_title($event_id).'
          </td></tr>

          <tr><td>'.__('Event Date: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$date_obj->format(get_option('date_format')).'
          </td></tr>
          
          <tr><td>'.__('Event Time: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.date(get_option('time_format'), $event_time).'
          </td></tr>
          
          <tr><td>'.__('Event Address: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.get_post_meta( $event_id, '_event_address', true ).'
          </td></tr> 
          
          <tr><td>'.__('Tickets: ', BA_EVENT_TEXTDOMAIN).'
          </td><td>'.$this->get_tickets_list($event_id, $event_guests_arr).'
          </td></tr>

      </tbody></table>';
    return $output;  
}

////////////////////////////////

function sanitize_booking_post_arr(){
    $output = array();
    //Array ( [event_id] => 44 [event_date] => 2017-06-27 [event_time] => 39600 [event_guests] => Array ( [18] => 2 [20] => 3 [19] => 1 ) )
    /// validate event post by id, event_date, event_time, event_guests
  if (!empty($_POST['event_id']) && $this->is_post_event(intval($_POST['event_id'])) && !empty($_POST['event_date']) && $this->validate_date($_POST['event_date']) && !empty($_POST['event_time']) && intval($_POST['event_time']) >= 0 && intval($_POST['event_time']) <= 86400 && !empty($_POST['event_guests']) && is_array($_POST['event_guests'])){   
  $event_id = intval($_POST['event_id']);
  $event_date = $_POST['event_date']; //// validated above
  $event_time = intval($_POST['event_time']);
  $event_guests_arr = $_POST['event_guests'];
   //// check event time and tickets availabilty 
   $tickets = $this->is_event_av($event_id, $event_date, $event_time);
   $asked_tickets = $this->get_asked_tickets($event_id, $event_guests_arr);
  
   if ($tickets && $tickets >= $asked_tickets){
      $output['event_id'] = $event_id;
      $output['event_date'] = $event_date;
      $output['event_time'] = $event_time;
      $output['event_guests'] = $event_guests_arr;
      $output['tickets'] = $asked_tickets;
   }  
  }   
    return $output;
}

//////////// Booking Page  ////////

function booking_page_shortcode(){

  $output = '';
  
  $post_arr = $this->sanitize_booking_post_arr();

  if (!empty($post_arr)){
    
  $event_id = $post_arr['event_id'];
  $event_date = $post_arr['event_date'];
  $event_time = $post_arr['event_time'];
  $event_guests_arr = $post_arr['event_guests'];

  $price_arr = $this->get_all_rates_by_ap_id($event_id, $event_guests_arr);

  $amount = ( strtolower($this->settings['currency_code']) == 'jpy') ?  $price_arr['total'] : round($price_arr['total'], 2)*100;

  $deposit = ( strtolower($this->settings['currency_code']) == 'jpy') ?  $price_arr['deposit'] : round($price_arr['deposit'], 2)*100;

  $output .= '<h2 id="title1">'.__('Your booking', BA_EVENT_TEXTDOMAIN).'</h2>';
  
  $output .= $this->get_event_booking_details($event_id, $event_date, $event_time, $event_guests_arr);

  $output .= '<div id="booking-block">
  <span>'.__('Please review your information, then fill out the booking form. All fields marked with * must be filled in.', BA_EVENT_TEXTDOMAIN).'</span>
  <form id="booking" name="booking" method="post">
  <h3>'.__('Guest', BA_EVENT_TEXTDOMAIN).'</h3>

  <div>
  <label for="first_name">'.__('First Name*', BA_EVENT_TEXTDOMAIN).'</label><br>
  <input id="first_name" name="first_name" type="text" value="">
  </div>

  <div>
  <label for="last_name">'.__('Last Name*', BA_EVENT_TEXTDOMAIN).'</label><br>
  <input id="last_name" name="last_name" type="text" value="">
  </div>

  <h3>'.__('Contacts', BA_EVENT_TEXTDOMAIN).'</h3>
  <div>
  <label for="email">'.__('Email*', BA_EVENT_TEXTDOMAIN).'</label><br>
  <input id="email" name="email" type="text" value="">
  </div>

  <div>
  <label for="tel1">'.__('Phone/Mobile*', BA_EVENT_TEXTDOMAIN).'</label><br>
  <input id="tel1" name="tel1" type="text" value="">
  </div>';

  $output .= apply_filters('ba_event_booking_form_fields', '');

  if ($this->settings['add_accept_term'])
  $output .= '
  <span>
  <input id="accept_term" name="accept_term" type="checkbox" value="1">
  <label for="accept_term">'.__('I have read and accept the ', BA_EVENT_TEXTDOMAIN).'<a href="'.get_permalink(get_option('BA_EVENT_page_terms')).'" target="_blank">'.get_the_title(get_option('BA_EVENT_page_terms')).'.</a></label>
  </span>';

  if ($this->settings['add_accept_term_adds'])
  $output .= '
  <span>
  <input id="accept_term_adds" name="accept_term_adds" type="checkbox" value="1">
  <label for="accept_term_adds">'.__('I have read and accept the ', BA_EVENT_TEXTDOMAIN).'<a href="'.get_permalink(get_option('BA_EVENT_page_terms_adds')).'" target="_blank">'.get_the_title(get_option('BA_EVENT_page_terms_adds')).'.</a></label>
  </span>
  ';
  
  $output .= $this->get_booking_payment_methods($price_arr, $amount, $deposit);

  //////////////////////

  $output .= '<div class="btn-submit">
  <input type="submit" class="bc-button-color" name="book" id="book" value="'.__('BOOK NOW', BA_EVENT_TEXTDOMAIN).'">
  </div>
  <input type="hidden" name="event_date" value="'.$event_date.'">
  <input type="hidden" name="event_time" value="'.$event_time.'">
  <input type="hidden" name="event_id" value="'.$event_id.'">';
  
   foreach ( $event_guests_arr as $age_id => $guests )
     $output .= '
     <input type="hidden" name="event_guests['.$age_id.']" value="'.$guests.'">
     ';
     
  $output .= '</form>

  </div>';
  
    } ///// end if $post_arr
    else {
        $output .= '<h2 id="title1">'.__('There are no available tickets for selected event. Please, choose another date, time or event.', BA_EVENT_TEXTDOMAIN).'</h2>';
    }

  return $output;
}

//////////////////////////////////////

function get_booking_payment_methods($price_arr, $amount, $deposit){
   $output = '';
    
   if (!empty($this->payment_methods)){
    $output .= '<div id="payment_methods">';
    $check_payments = false;
    $i = 0;
    foreach($this->payment_methods as $method){
      if ((isset($this->settings[$method.'_activate']) && $this->settings[$method.'_activate'] && ($method == 'cash' || $method != 'cash' && ( $this->settings['payment_mode2'] || $this->settings['payment_mode3']))) || (sizeof($this->payment_methods) == 1 && $method == 'cash')){
        $checked = $i ? '' : ' checked';
        $output .= apply_filters('ba_event_'.$method.'_payment_field', '', $checked, $amount);
        if ($method != 'cash') $check_payments = true;
        $i++;
      }        
    }
    
    ///// fix no payment settings
    if (!$i){
        $output .= apply_filters('ba_event_cash_payment_field', '', ' checked', $amount);
    }
    
    $output .= '</div>';
    
    if ($check_payments){
        
       if ( $this->settings['payment_mode2'] && $this->settings['payment_mode3']){

       $output .= '<div id="select_amount" data-f="'.$amount.'" data-d="'.$deposit.'">
        <h3>'.__('Select payment amount', BA_EVENT_TEXTDOMAIN).'</h3>
        <span>
        <input id="full_amount" name="amount" type="radio" value="full" checked>
        <label for="full_amount">'.__('Full amount: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(round($price_arr['total'], 2)).'</label>
        </span>
        <span>
        <input id="deposit_amount" name="amount" type="radio" value="deposit">
        <label for="deposit_amount">'.__('Deposit: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(round($price_arr['deposit'], 2)).'</label>
        </span>
        </div>';
        
       } elseif ($this->settings['payment_mode2']){
        $output .= '<div id="select_amount" data-f="'.$amount.'" data-d="'.$deposit.'">
        <h3>'.__('Deposit amount to pay now: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(round($price_arr['deposit'], 2)).'</h3>
        <input name="amount" type="hidden" value="deposit">
        </div>
        ';
        } elseif ($this->settings['payment_mode3']){
            $output .= '<div id="select_amount" data-f="'.$amount.'" data-d="'.$deposit.'">
            <h3>'.__('Amount to pay now: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(round($price_arr['total'], 2)).'</h3>
            <input name="amount" type="hidden" value="full">
            </div>
            ';
        }
         
    } else {
     $output .= '<div id="select_amount" data-f="'.$amount.'" data-d="'.$deposit.'">
     <h3>'.__('Amount to pay later: ', BA_EVENT_TEXTDOMAIN).$this->format_currency(round($price_arr['total'], 2)).'</h3>
     <input type="hidden" name="amount" value="full">
     </div>
     ';
  } ///// end if $check_payments
     
   } 
    
   return $output; 
}

/////////////////////////////////

function payment_field_cash($value, $checked){
   $output = '
   <span class="b-payment-radio">
     <input id="cash" name="payment_method" type="radio" value="cash"'.$checked.'>
     <label for="cash">'.__('Pay Cash', BA_EVENT_TEXTDOMAIN).'</label>
   </span>
   ';
    
   return $output; 
}

//////////////////////////////////////
/////////////// Av cal ///////////////////

function is_event_av($event_id, $date, $time_from){
    $output = 0;
    // $date in Y-m-d format
    $now_timestamp = strtotime(date("Y-m-d"));
    $selected_timestamp = strtotime($date);
    
    if ($selected_timestamp >= $now_timestamp ){
    
     $date_obj = new DateTime( $date );
    
     if ( $this->is_event_live($event_id, $date) && $this->is_week_day_av($event_id, $this->get_week_day_num($date_obj)) && !$this->is_excluded_date($event_id, $date)){
        // return array of times
        $output = $this->get_event_quote($event_id, $date, $time_from);
     } 
    }    
    return $output;
}

/////////////////////////

function is_event_live($event_id, $date){
   // $date in Y-m-d format
   $start_timestamp = get_post_meta($event_id, '_event_start_date', 1);
   $end_timestamp = get_post_meta($event_id, '_event_end_date', 1);
   $current = strtotime($date);
   return $start_timestamp <= $current && $current <= $end_timestamp ? 1 : 0;
}

///////////////////////////

function is_post_event($event_id){
    
    if ($event_id > 0){
    $args = array(
        'post_type'   => $this->event_post_type,
        'post_status' => 'publish',
        'p' => $event_id,
    );
    $posts = get_posts( $args );
    
    return $posts ? 1 : 0;
    } else {
        return 0;
    }
}

//////////////////////////

function get_duration($time_from, $time_to){
    $output = '';   
    $timestamp1 = strtotime('1970-01-01 ' . $time_from);
    $timestamp2 = strtotime('1970-01-01 ' . $time_to); 
    $output = date("g\h i\m", $timestamp2 - $timestamp1);
    
    return $output;
}

/////////////////////////

function update_av_cal($event_id, $date, $time_from, $tickets){
   $av_cal = (array)get_post_meta($event_id, '_event_av_cal', true);
   // $date in Y-m-d format
   $timestamp = strtotime($date);
   
   $av_cal[$timestamp][$time_from] = (isset($av_cal[$timestamp][$time_from])) ? ($av_cal[$timestamp][$time_from] + $tickets) : $tickets;
   
   // if ($av_cal[$timestamp][$time_from] < 0) unset($av_cal[$timestamp][$time_from]);
   
   update_post_meta($event_id, '_event_av_cal', $av_cal); 
    
   return; 
}

////////////////////////////////////

function get_event_quote($event_id, $date, $time_from){ 
   $output = 0;
   $max_guests = (int)get_post_meta($event_id, '_event_max_guests', true);
   // $date in Y-m-d format
   
   $av_cal = (array)get_post_meta($event_id, '_event_av_cal', true);
   $timestamp = strtotime($date);
   
   $output = (isset($av_cal[$timestamp][$time_from])) ? ($max_guests - $av_cal[$timestamp][$time_from]) : $max_guests;
      
   if ($output < 0) $output = 0;
   
   return $output; 
}

///////////////////////////

function get_asked_tickets($event_id, $event_guests){
   $output = 0;
   
   $price_arr = (array)get_post_meta($event_id, '_event_price', true);
    
   $event_age_arr = (array)get_post_meta($event_id, '_event_age', true);
   
   foreach ($event_age_arr as $age_id){
        if (isset($event_guests[$age_id])){
            $output += (float)$price_arr[$age_id] > 0 && (int)$event_guests[$age_id] > 0 ? (int)$event_guests[$age_id] : 0;
        }
   }
    
   return $output; 
}

/////////////////////////////

function get_tickets_list($event_id, $event_guests){

   $output = '';
   
   $prices = (array)get_post_meta($event_id, '_event_price', true);
    
   $event_age_arr = (array)get_post_meta($event_id, '_event_age', true);
   /*
   foreach($event_age_arr as $age_id){
        $ages_new[(int)$prices[$age_id]] = $age_id;
    }
    krsort($ages_new, SORT_NUMERIC);
    reset($ages_new);
    */
    arsort($prices, SORT_NUMERIC);
   
   foreach ($prices as $age_id => $price){
        if (isset($event_guests[$age_id]) && $event_guests[$age_id]){
            $term = get_term_by( 'id', $age_id, 'age' );
            $output .= '<li>'.$term->name.' x '.$event_guests[$age_id].' x '.$this->format_currency($price).'</li>';
            //$output += (float)$price > 0 ? (int)$event_guests[$age_id] : 0;
        }
   }
   
   if ($output) $output = '<ul class="tickets_list">'.$output.'</ul>';
    
   return $output; 
}

///////////////////////////

function main_cal_thead_tr(){
   $output = '';
   $arr = $this->get_week_days_arr(); 
   foreach ($arr as $day_name) 
    $output .= '<th>'.$day_name.'</th>
    ';
   
   return $output; 
}

///////////////////////////

function main_cal_month_select_list($date){
   $output = ''; 
   // $date in Y-m-01 format
   $date_timestamp = strtotime($date);
   
   $this->months_names = $this->get_months_arr();
   
   $date_cur = date('Y-m', $date_timestamp);
   $date_cur_name = $this->months_names[date('F', $date_timestamp)].date(' Y', $date_timestamp);
   
   $output .= '<select id="cal-select-month">
   <option selected="selected" value="'.$date_cur.'">'.$date_cur_name.'</option>';
   
   for ($i = 1; $i <= 11; $i++){
      $date_next_timestamp = strtotime('+'.$i.' month', $date_timestamp);
      $date_next = date('Y-m', $date_next_timestamp);
      $date_next_name = $this->months_names[date('F', $date_next_timestamp)].date(' Y', $date_next_timestamp);
      $output .= '<option value="'.$date_next.'">'.$date_next_name.'</option>
      ';
   }
   
   $output .= '</select>';
   
   return $output; 
}

///////////////////////

function main_cal_event_select_list($limit = -1){
    $output = '';
    $args = array(
        'post_type'   => $this->event_post_type,
        'post_status' => 'publish',
        'numberposts' => $limit,
        'orderby'  => 'title',
        'order'    => 'ASC',
    );
    $posts = get_posts( $args );
    if ( $posts ) {
        $output .= '<select id="cal-select-event">
        <option selected="selected" value="0">'.__('All Events', BA_EVENT_TEXTDOMAIN).'</option>';
   
        foreach ( $posts as $post ) {
            $output .= '<option value="'.$post->ID.'">'.$post->post_title.'</option>';
        }
        
        $output .= '</select>';
    } 
    return $output;   
}
//////////////////////////////////

function get_time_arr($event_id, $date = ''){
    $time_arr = (array)get_post_meta($event_id, '_event_time_group', true);
    return $time_arr;
}

//////////////////////////////////

function get_event_calendar_block($date, $with_links = true){
    $output = '';
    // $date in Y-m-01 format
        // make cal table header
        
        $output .= '<div id="main-cal-block">
        <div id="main-cal-header">
           '.$this->main_cal_month_select_list($date).$this->main_cal_event_select_list().'
        </div>
        <div id="main-cal-body">
          
          </div> <!--  END main-cal-body  -->
        </div>';
    
    return $output;
}

///////////////////////

function get_week_day_num($date_obj){
    $day_num = !$this->week_first_day ? $date_obj->format("w") : $date_obj->format("N");
    return $day_num;
}

//////////////////////

function get_next_week_day_num($w){
    $w = ($w+1)%7;
    if ($this->week_first_day && $w==0){
       $w = 7; 
    }
    return $w;
}

/////////////////////////

function is_week_day_weekend($w){
   return ($this->week_first_day && $w==7) || (!$this->week_first_day && $w==6) ? true : false; 
    
}

////////////////////////

function get_event_calendar($date, $with_links = true){
    $output = '';
    // $date in Y-m-01 format
    
    $now_timestamp = strtotime(date("Y-m-d"));
    
    // get month before and month after
    
    $date_prev = date('Y-m-d', strtotime('-1 month', strtotime($date)));
    $date_prev_obj = new DateTime( $date_prev );
    $days_prev_total = $date_prev_obj->format("t");
    
    // get asked month info
    
    $date_obj = new DateTime( $date );
    $day_num = $this->get_week_day_num($date_obj);
    $days_total = $date_obj->format("t");
    $date_my = $date_obj->format("Y-m-");
    
    $cal_arr = array();
    
    $args = array(
        'post_type'   => $this->event_post_type,
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby'  => 'title',
        'order'    => 'ASC',
    );
    $posts = get_posts( $args );
    if ( $posts ) {
        foreach ( $posts as $post ) {
          // $post->ID $post->post_title _event_time_group[1][_event_time_from]
          $start_timestamp = get_post_meta($post->ID, '_event_start_date', 1);
          $end_timestamp = get_post_meta($post->ID, '_event_end_date', 1);
          
          $ex_arr = (array)get_post_meta($post->ID, '_event_excluded_dates', true);
          $days_arr = (array)get_post_meta($post->ID, '_event_week', true);
          $w = $day_num;
          for ($i = 1; $i <= $days_total; $i++){
             $date_i = $i < 10 ? '0'.$i : $i;
             $date_cur = $date_my.$date_i;
             $date_cur_timestamp = strtotime($date_cur);
             
             if ( $start_timestamp <= $date_cur_timestamp && $date_cur_timestamp <= $end_timestamp && in_array($w, $days_arr) && !in_array($date_cur_timestamp, $ex_arr)){
                // magic here
                $time_arr = $this->get_time_arr($post->ID);
                $time_lines = '';
                $time_first = time("+1y");
                foreach ($time_arr as $times){
                    $time_from_stmp = strtotime('1970-01-01 ' . $times['_event_time_from']);
                    if ($time_first > $time_from_stmp) $time_first = $time_from_stmp;
                    $av_tickets = $this->get_event_quote($post->ID, $date_cur, $time_from_stmp);
                    $free_tickets_class = $av_tickets ? 'av_tickets' : 'no_av_tickets';
                    
                    $time_lines .= $with_links ? '<li><a href="'.ba_event_make_url_from_permalink(get_permalink(get_option('BA_EVENT_page_booking_calendar')), 'event_id='.$post->ID.'&event_date='.$date_cur.'&event_time='.$time_from_stmp).'" class="call-cell-time" data-event="'.$post->ID.'" data-date="'.$date_cur.'" data-time-from="'.$time_from_stmp.'">'.$times['_event_time_from'].'</a><span>('.$this->get_duration($times['_event_time_from'], $times['_event_time_to']).')</span></li>' : '<li>'.$times['_event_time_from'].'<span>('.$this->get_duration($times['_event_time_from'], $times['_event_time_to']).') - </span><span class="'.$free_tickets_class.'">'.$av_tickets.'</span>'.__(' tickets left', BA_EVENT_TEXTDOMAIN).'</li>';
                }
                
                $cal_arr[$i][] = array( 
                  'ind' => $time_first,
                  'title' => $with_links ? '<a href="'.get_permalink($post->ID).'">'.$post->post_title.'</a>' : $post->post_title,
                  'time_lines' => $time_lines,
                  'event_id' => $post->ID
                );
             }
             $w = $this->get_next_week_day_num($w);
          } 
        }
        
        /// sort $cal_arr by first time_from
        
        foreach($cal_arr as $key => $cal_date_arr){
          $tmp = Array();
          foreach($cal_date_arr as &$ma) $tmp[] = &$ma["ind"];
          array_multisort($tmp, SORT_ASC, $cal_arr[$key]);
        }
        
        /// make calendar output .........
        $first_day = $day_num;
        $last_day = ($days_total + $day_num - 1)%7;
        
        if ($this->week_first_day && $last_day == 0){
            $last_day = 7;
        }
        
        // make cal table header
        
        $output .= '
          <table id="main-cal">
            <thead><tr>
              '.$this->main_cal_thead_tr().'
            </tr></thead>
            <tbody>
              <tr>
        ';
        
        // make start empty cells
        
        for ($i=$this->week_first_day;$i < $first_day; $i++)
          $output .= '<td class="cal-cell cal-cell-empty">
             <div class="cal-cell-date">'.($days_prev_total + 1 + $i - $first_day).'</div>
             <div class="cal-cell-inner"></div> 
          </td>
          ';
        
        // make dates cells
        $w = $first_day;
        for ($i = 1; $i <= $days_total; $i++){
            $date_i = $i < 10 ? '0'.$i : $i;
            $date_cur_timestamp = strtotime($date_my.$date_i);
            $now_timestamp;
            $cal_cell_inner = '';
            $cal_add_class = '';
            
            if (($now_timestamp <= $date_cur_timestamp) && isset($cal_arr[$i])){
            foreach ($cal_arr[$i] as $cal_date_arr)
              $cal_cell_inner .= '<div class="cal-cell-inner" data-id="'.$cal_date_arr['event_id'].'">
                <h4>'.$cal_date_arr['title'].'</h4>
                <ul>
                '.$cal_date_arr['time_lines'].'
                </ul>
                </div>';
                $cal_add_class = ' cal-cell-av';
            }
            
            $output .= '<td class="cal-cell'.$cal_add_class.'">
             <div class="cal-cell-date">'.$i.'</div>
             '.$cal_cell_inner.' 
          </td>
          ';
           
           if($this->is_week_day_weekend($w)) $output .= '</tr>
           <tr>
           ';
          
           $w = $this->get_next_week_day_num($w); 
        }
        
        // make end empty cells
            $j = 1;
          for ($i = $last_day; $i < $this->week_last_day; $i++){
            $output .= '<td class="cal-cell cal-cell-empty">
             <div class="cal-cell-date">'.$j.'</div>
             <div class="cal-cell-inner"></div> 
          </td>
          ';
            $j++;
          }
        
        // make table footer
        
        $output .= '</tr>
             </tbody>
          </table>';  
    } 
    return $output;
}

///////////// Main calendar page ////////////////

function main_cal_shortcode(){
  $output = '';
  
  $output .= $this->get_event_calendar_block(date('Y-m-01'));

  return $output;
}

////////////////////////////

function get_event_ages($event_id){
   $output = '';
   
   $ages = (array)get_post_meta($event_id, '_event_age', true); 
    
   return $output; 
}

/////////////////////////////////

function get_hidden_inputs(){
   $output = '';
   
   $event_id = $_POST['event_id'];
   
   $ages = (array)get_post_meta($event_id, '_event_age', true);
  $age_lines = '';
  foreach($ages as $age_id){
    $output .= '<input type="hidden" name="event_guests['.$age_id.']" value="0">';
  }
  
   $output .= '<input type="hidden" name="event_id" value="'.$event_id.'">
          <input type="hidden" name="event_date" value="">
          <input type="hidden" name="event_time" value="">
          '.$age_lines.'
          </form>'; 
  
   echo $output; 
   wp_die(); 
}

/////////////////////////////////

function get_booking_cal_form($event_id, $date='', $event_time=''){
  $output = '';
  
  $ages = (array)get_post_meta($event_id, '_event_age', true);
  $age_lines = '';
  foreach($ages as $age_id){
    $age_lines .= '<input type="hidden" name="event_guests['.$age_id.']" value="0">';
  }
  
  $output .= '<form id="pre_booking" name="pre_booking" action="'.get_permalink(get_option('BA_EVENT_page_booking')).'" method="post">
          <input type="hidden" name="event_id" value="'.$event_id.'">
          <input type="hidden" name="event_date" value="'.$date.'">
          <input type="hidden" name="event_time" value="'.$event_time.'">
          '.$age_lines.'
          </form>';  
    
  return $output; 
}

///////////////////////////////

function booking_price_update(){
    $output = '';
    
    /// validate event post by id, event date, event time
    if(isset($_POST['event_id']) && $this->is_post_event(intval($_POST['event_id'])) && isset($_POST['event_date']) && $this->validate_date($_POST['event_date']) && isset($_POST['event_time']) && intval($_POST['event_time']) >= 0 && intval($_POST['event_time']) <= 86400) {
      $output .= $this->get_booking_age_prices(intval($_POST['event_id']), $_POST['event_date'], intval($_POST['event_time']));
    }
    echo $output;
    wp_die();    
}

////////////////////////////////////////

function get_booking_age_prices($event_id, $date, $event_time=''){
    $output = '';
    
    $av_tickets = $this->get_event_quote($event_id, $date, $event_time);
    
    $ages = (array)get_post_meta($event_id, '_event_age', true);
    $prices = (array)get_post_meta($event_id, '_event_price', true);
    
    /*
    foreach($ages as $age_id){
        $ages_new[(int)$prices[$age_id]] = $age_id;
    }
    krsort($ages_new, SORT_NUMERIC);
    reset($ages_new);
    */
    arsort($prices, SORT_NUMERIC);
    
    $age_lines = '';
    foreach($prices as $age_id => $price){
      
      $add_class = $price ? ' as-adult' : ' as-free';
        
      /// make select list
      $select_list = '<select name="select_tickets_'.$age_id.'" id="select_tickets_'.$age_id.'" class="select-tickets'.$add_class.'" data-age-id="'.$age_id.'" data-av-tickets="'.$av_tickets.'" data-price="'.$price.'">
      ';
      
      for ($i = 0; $i <= $av_tickets; $i++){
         $select_list .= '<option value="'.$i.'">'.$i.'</option>
         ';
      }
      
      $select_list .= '</select>';
      
      $term = get_term_by( 'id', $age_id, 'age' );  
        
      $age_lines .= '<tr>
         <td class="age_title">'.$term->name.'</td>
         <td class="age_desc">'.apply_filters('translate_text', $term->description).'</td>
         <td class="select_t">'.$select_list.'</td>
         <td class="age_price" data-price="'.$price.'">'.$this->format_currency($price).'</td>
       </tr>
      ';
    }
    
    if($age_lines){
        
       $amount = $this->settings['currency_place'] == 1 ? $this->settings['currency'].'<span>0</span>' : '<span>0</span> '.$this->settings['currency']; 
        
       $output .= '<h3>'.__('How many tickets will you need?', BA_EVENT_TEXTDOMAIN).'</h3>
         <table>
           <tfoot><tr>
             <td></td>
             <td class="cell-total-text">'.__('Total:', BA_EVENT_TEXTDOMAIN).'</td>
             <td class="cell-total-tickets">0</td>
             <td class="cell-total-amount">'.$amount.'</td>
           </tr></tfoot>
           <tbody>
           '.$age_lines.'
           </tbody>
         </table>
         
         <button id="go_to_booking" type="submit" class="btn center-block bc-button-color">'.__('Proceed with Booking', BA_EVENT_TEXTDOMAIN).'</button>'; 
        
    }
  
    return $output;
}

///////////////////////////

function booking_cal_shortcode(){
  $output = '';
  // Array ( [event_id] => 44 [event_date] => 2016-12-22 [event_time] => 39600 )
  
  /// validate event post by id
  if (isset($_GET['event_id']) && $this->is_post_event(intval($_GET['event_id']))){
    
  $event_id = intval($_GET['event_id']);
  
  $output .= '<h2 class="booking-cal-title center-block">'.get_the_title($event_id).'</h2>';
  /// validate date
  $event_date = isset($_GET['event_date']) && $this->validate_date($_GET['event_date']) ? $_GET['event_date'] : date("Y-m-d");
  /// validate time
  $event_time = isset($_GET['event_time']) && intval($_GET['event_time']) >= 0 && intval($_GET['event_time']) <= 86400 ? intval($_GET['event_time']) : '';
  
  $output .= '<div id="booking-cal-block" data-event="'.$event_id.'">
  '.$this->get_booking_calendar($event_id, $event_date, true).'
    </div>';
    
    if ($this->is_event_av($event_id, $event_date, $event_time) && $event_time!=''){
        $output .= '<div id="av_times_block" class="center-block">'.$this->get_booking_times($event_id, $event_date, $event_time).'
    </div>';
  
  $output .= '<div id="select_price_block" class="center-block">
         '.$this->get_booking_age_prices($event_id, $event_date, $event_time).'
         </div>';        
    } else {
        $output .= '<div id="av_times_block" class="center-block"><h3>'.__('Select a date to view alailable time', BA_EVENT_TEXTDOMAIN).'</h3>
    </div>';
  
  $output .= '<div id="select_price_block" class="center-block">
         </div>';
    }
  
  $output .= $this->get_booking_cal_form($event_id, $event_date, $event_time);
  }
  
  return $output;
}

//////////////////////////////

function booking_time_update(){
    $output = '';
    
    /// validate event post by id, event date
    if(isset($_POST['event_id']) && $this->is_post_event(intval($_POST['event_id']))){
      if (isset($_POST['event_date']) && $this->validate_date($_POST['event_date'])){
      $output .= $this->get_booking_times(intval($_POST['event_id']), $_POST['event_date']);
      
    } else {
        $output .= '
           <h3>'.__('Select a date to view alailable time', BA_EVENT_TEXTDOMAIN).'</h3>
        ';  
    }     
    } 
    echo $output;
    wp_die();
}

////////////////////////

function get_booking_times($event_id, $date, $event_time=''){
    $output = '';
    $time_arr = (array)get_post_meta($event_id, '_event_time_group', true);
    $av_times = array();
    
    $time_lines = '';
    
    foreach ($time_arr as $times){
          
        $time_from_stmp = strtotime('1970-01-01 ' . $times['_event_time_from']);
        $av_tickets = $this->get_event_quote($event_id, $date, $time_from_stmp);
        $av_times[$time_from_stmp] = $times['_event_time_from'];
        
        if ($av_tickets){ 
            
           $tickets_hint = current_user_can( 'administrator' ) || $this->settings['event_show_tickets_count'] ? '<span class="av_tickets">('.$av_tickets.__(' tickets available', BA_EVENT_TEXTDOMAIN).')</span>' : ''; 
            
           $checked = ($time_from_stmp == $event_time) ? ' checked="checked"' : ''; 
            
           $time_lines .= '<li class="event_time">
            <input type="radio" id="event_time_'.$time_from_stmp.'" name="event_time" value="'.$time_from_stmp.'"'.$checked.'>
            <label for="event_time_'.$time_from_stmp.'">'.$times['_event_time_from'].'</label>
            '.$tickets_hint.'
        </li>';     
        }        
    }
    
    if ($time_lines){
        $output .= '
           <h3>'.__('Select time', BA_EVENT_TEXTDOMAIN).'</h3>
        <ul>
        '.$time_lines.'
        </ul>';
    } else {
        $output .= '
           <h3>'.__('Select a date to view alailable time', BA_EVENT_TEXTDOMAIN).'</h3>
        ';
    } 
    
    return $output;
}

///////////////////////////////////

function main_cal_update(){
    $output = '';
    
    $with_links = (isset($_POST['with_links'])) ? $_POST['with_links'] : true;
    
    if(isset($_POST['cur_month']))
      $output .= $this->get_event_calendar($_POST['cur_month'].'-01', $with_links);
    
    echo $output;
    wp_die();
}

///////////////////////////////////

function booking_cal_update(){
    $output = '';
    
    /// validate event post by id. event date will be validated in get_booking_calendar()
    if(isset($_POST['event_id']) && $this->is_post_event(intval($_POST['event_id'])) && isset($_POST['event_date']))
      $output .= $this->get_booking_calendar(intval($_POST['event_id']), $_POST['event_date']);
    
    echo $output;
    wp_die();
}

///////////////////////////

function get_booking_calendar($event_id, $date = '', $is_date_selected = false){
    $output = '';
    // $date in Y-m-d format
    
    $this->months_names = $this->get_months_arr();
    
    if (!$this->validate_date($date)) $date = date("Y-m-d");
    
    $now_timestamp = strtotime(date("Y-m-d"));
    
    $selected_timestamp = $is_date_selected ?  strtotime($date) : 0;
    
    // get month before and month after
    
    $date_prev = date('Y-m-d', strtotime('-1 month', strtotime($date)));
    $date_prev_obj = new DateTime( $date_prev );
    $days_prev_total = $date_prev_obj->format("t");
    
    $date_next = date('Y-m-d', strtotime('+1 month', strtotime($date)));
    $date_next_obj = new DateTime( $date_next );
    
    // get asked month info
    
    $date_obj = new DateTime( $date );
    $days_total = $date_obj->format("t");
    $date_my = $date_obj->format("Y-m-");
    
    $date_start_month = new DateTime( $date_obj->format("Y-m-01") );
    $day_num = $this->get_week_day_num($date_start_month);
    
    $time_arr = $this->get_time_arr($event_id);
    $title = get_the_title($event_id);
    
    $av_tickets = 0;
    $av_times = array();
    
    foreach ($time_arr as $times){
        $time_from_stmp = strtotime('1970-01-01 ' . $times['_event_time_from']);
        $av_tickets += $this->get_event_quote($event_id, $date, $time_from_stmp);
        $av_times[$time_from_stmp] = $times['_event_time_from'];
    }    
    
    $cal_arr = array();
    
    $args = array(
        'post_type'   => $this->event_post_type,
        'post_status' => 'publish',
        'numberposts' => 1,
        'p'  => $event_id,
    );
    $posts = get_posts( $args );
    if ( $posts ) {
        foreach ( $posts as $post ) {
          // $post->ID $post->post_title _event_time_group[1][_event_time_from]
          $start_timestamp = get_post_meta($post->ID, '_event_start_date', 1);
          $end_timestamp = get_post_meta($post->ID, '_event_end_date', 1);
          
          $ex_arr = (array)get_post_meta($post->ID, '_event_excluded_dates', true);
          $days_arr = (array)get_post_meta($post->ID, '_event_week', true);
          $w = $day_num;
          for ($i = 1; $i <= $days_total; $i++){
             $date_i = $i < 10 ? '0'.$i : $i;
             $date_cur = $date_my.$date_i;
             $date_cur_timestamp = strtotime($date_cur);
             
             if ($start_timestamp <= $date_cur_timestamp && $date_cur_timestamp <= $end_timestamp && in_array($w, $days_arr) && !in_array($date_cur_timestamp, $ex_arr)){
             
             $av_tickets_in = 0;
             // check available tickets
             reset($time_arr);
             foreach ($time_arr as $times){
               $time_from_stmp = strtotime('1970-01-01 ' . $times['_event_time_from']); 
               $av_tickets_in += $this->get_event_quote($post->ID, $date_cur, $time_from_stmp);
             } 
             
                // magic here
                $cal_arr[$i] = $av_tickets_in;
             }
             
             $w = $this->get_next_week_day_num($w);
          } 
        }
              
        /// make calendar output .........
        $first_day = $day_num;
        $last_day = ($days_total + $day_num - 1)%7;
        
        if ($this->week_first_day && $last_day == 0){
            $last_day = 7;
        }
        
        // make cal table header
        
        $output .= '
          <div id="booking-cal-before" class="center-block" data-event="'.$event_id.'"><span class="navLeft nav-arrow" data-event-date="'.$date_prev_obj->format("Y-m-01").'">&#10094;</span>'.$this->months_names[$date_obj->format("F")].$date_obj->format(" Y").'<span class="navRight nav-arrow" data-event-date="'.$date_next_obj->format("Y-m-01").'">&#10095;</span></div>
          <table id="booking-cal">
            <thead><tr>
              '.$this->main_cal_thead_tr().'
            </tr></thead>
            <tbody>
              <tr>
        ';
        
        // make start empty cells
        
        for ($i=$this->week_first_day;$i < $first_day; $i++)
          $output .= '<td class="cal-cell">
             <div class="cal-cell-date cal-cell-empty">'.($days_prev_total + 1 + $i - $first_day).'</div> 
          </td>
          ';
        
        // make dates cells
        $w = $first_day;
        for ($i = 1; $i <= $days_total; $i++){
            $date_i = $i < 10 ? '0'.$i : $i;
            $date_cur_timestamp = strtotime($date_my.$date_i);
            $now_timestamp;
            $class = '';
            $tickets = 0;
            
            if (($now_timestamp <= $date_cur_timestamp) && isset($cal_arr[$i])){
              $tickets = $cal_arr[$i];
              $class = $cal_arr[$i] ? ' cal-av' : ' cal-sold-out';
              if ($selected_timestamp == $date_cur_timestamp) $class .= ' selected';
            } else $class = ' cal-non-av';
            
            $tickets_hint = current_user_can( 'administrator' ) || $this->settings['event_show_tickets_count'] ? ' title="'.$tickets.__(' tickets', BA_EVENT_TEXTDOMAIN).'"' : '';
            
            $output .= '<td class="cal-cell">
             <div class="cal-cell-date'.$class.'" data-tickets="'.$tickets.'"'.$tickets_hint.' data-event-date="'.$date_my.$date_i.'">'.$i.'</div> 
          </td>
          ';
           
           if($this->is_week_day_weekend($w)) $output .= '</tr>
           <tr>
           ';
          
           $w = $this->get_next_week_day_num($w); 
        }
        
        // make end empty cells
            $j = 1;
          for ($i = $last_day; $i < $this->week_last_day; $i++){
            $output .= '<td class="cal-cell">
             <div class="cal-cell-date cal-cell-empty">'.$j.'</div>
          </td>
          ';
            $j++;
          }
        
        // make table footer
        
        $output .= '</tr>
             </tbody>
          </table>';  
    } 
    return $output;
}

//////// get_event_map

function get_event_map($event_id){
    
  $lat = get_post_meta($event_id, '_event_location_latitude', 1);
  $long = get_post_meta($event_id, '_event_location_longitude', 1);  
    
  $output = '';
  
  if($lat && $long)
  $output .= '    <script>

function initMap() {
var map = new google.maps.Map(document.getElementById(\'ba_event_map\'), {
    zoom: 13,
    center: {lat: '.$lat.', lng: '.$long.'},
    scrollwheel: false,
});

var image = {
    url: \''.plugins_url( $this->markers_urls[$this->settings['map_marker']], BA_EVENT_PLUGIN ).'\',
    size: new google.maps.Size(40, 50),
    origin: new google.maps.Point(0, 0),
    anchor: new google.maps.Point(20, 50)
};
  // Shapes define the clickable region of the icon. The type defines an HTML
  // <area> element \'poly\' which traces out a polygon as a series of X,Y points.
  // The final coordinate closes the poly by connecting to the first coordinate.
var shape = {
    coords: [1, 1, 1, 50, 40, 50, 40, 1],
    type: \'poly\'
};
  
var marker = new google.maps.Marker({
    position: {lat: '.$lat.', lng: '.$long.'},
    map: map,
    icon: image,
    shape: shape
});
  
google.maps.event.addDomListener(window, "resize", function() {
    var center = map.getCenter();
    google.maps.event.trigger(map, "resize");
    map.setCenter(center); 
});

}
    </script>

<script async defer
        src="https://maps.googleapis.com/maps/api/js?key='.$this->settings['google_api'].'&signed_in=true&callback=initMap"></script>
';

  return $output;
}

/////////////

function date_to_sql($date){
  if ($date){
  $date_arr = explode('/', $date);
  if (sizeof($date_arr) == 3){
      return $this->date_format == 'd/m/Y' ? $date_arr[2].'-'.$date_arr[1].'-'.$date_arr[0] : $date_arr[2].'-'.$date_arr[0].'-'.$date_arr[1];
    }  
  }
  else return '';
}

function date_from_sql($date){

  if ($date!=="0000-00-00"){
  $date_arr = explode('-', $date);
  if (sizeof($date_arr) == 3)
       return $this->date_format == 'd/m/Y' ? $date_arr[2].'/'.$date_arr[1].'/'.$date_arr[0] : $date_arr[1].'/'.$date_arr[2].'/'.$date_arr[0];
  }
  else return '';
}

   /// next - end of class
}

///////// ***********************************///////////////

Global $BA_Event_var;

$BA_Event_var = new BA_Event();


