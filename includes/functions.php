<?php

//      General functions

if ( ! defined( 'ABSPATH' ) )
	exit;
    
// Add support for Post Thumbnails.
add_theme_support( 'post-thumbnails' );
add_image_size( 'ba-event-thumbnail', 360, 200, true );
add_image_size( 'ba-event-full', 1920, 1080, false );
add_image_size( 'ba-event-micro', 720, 400, false );    

///////////////////////////////////////

function ba_event_make_url($str, $attr = ''){  // $str without pre /
   $url = apply_filters( 'wpml_home_url', get_home_url().'/' );
   $url_parts = parse_url($url); 
   if (!isset($url_parts['query'])){
       $add_attr = $attr ? '?'.$attr : '';
       return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '/'. $str . $add_attr;
   } else {
       $add_attr = $attr ? '&'.$attr : '';
       return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '/'. $str . '?' . $url_parts['query'] . $add_attr;
   }     
}

////////////////////////

function ba_event_make_url_from_permalink($url, $attr = ''){
   $url_parts = parse_url(rtrim($url,"/")); 
   if (!isset($url_parts['query'])){
       $add_attr = $attr ? '?'.$attr : '';
       return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '/'. $add_attr;
   } else {
       $add_attr = $attr ? '&'.$attr : '';
       return $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '/'. '?' . $url_parts['query'] . $add_attr;
   }     
}

///////////////////////////////////////
/***Tuning****/

add_action( 'do_meta_boxes', 'ba_event_remove_plugin_metaboxes' );
function ba_event_remove_plugin_metaboxes(){
        remove_meta_box( 'tagsdiv-age', 'event', 'side' );
}

///////////////////////////////////////

add_filter('post_type_link','ba_event_change_booking_link', 10, 2);
function ba_event_change_booking_link( $permalink, $post ) {
    if( $post->post_type == 'booking' ) {
        $permalink = home_url( 'thank-you/reserv-code/'.$post->post_title );
    }
    return $permalink;
}

///////////////////////////////////////

function ba_event_get_event_slider($event_id){
    
    $output = '';
    
    $files = get_post_meta( $event_id, '_event_file_list', 1 );
    //$attachment_id_arr = array_keys((array)$files);

    if(!empty($files)){
        $slider_show = '';
        $slider_mini = '';
// Loop through them and output an image
      foreach ( $files as $attachment_id => $val ) {
        $src_arr = wp_get_attachment_image_src( $attachment_id, 'ba-event-full' );
        $src_arr1 = wp_get_attachment_image_src( $attachment_id, 'ba-event-thumbnail' );
        $slider_show .= '<div style="background: url(\''.$src_arr[0].'\') center center no-repeat; background-size: cover;"></div>
        ';
        $slider_mini .= '<div style="background: url(\''.$src_arr1[0].'\') center center no-repeat; background-size: cover;"></div>
        ';
      }
      
      if ($slider_show)
         $output = '
           <div class="event_slider_show">
             '.$slider_show.'
             </div>
           <div class="event_slider_mini">    
             '.$slider_mini.'
             </div>';
    }
    
    return $output;
}

//////////////////////////////////////////

function ba_event_get_features($event_id){
    
    $output = '<div id="ba_event_features">';
    
    $output .= '<h2 class="event_features_title">
                            '.get_post_meta($event_id, '_event_features_title', 1).'
                        </h2>';
    
    $output .= '<p class="event_features_text">
                            '.get_post_meta($event_id, '_event_features_text', 1).'
                        </p>';
    
    $features_arr = get_the_terms($event_id, 'ba-features');
  //  error_log(print_r($features_arr, true));
    if(!empty($features_arr)){
       $term_list = ''; 
       foreach ( $features_arr as $term ){
          $term_list .= '<li><span></span>'.$term->name.'</li>
                        ';
       } 
       if ($term_list) 
         $output .= '<ul class="event_features_list">
                            '.$term_list.'
                        </ul>';
    }
    
    $output .= '</div>';

    return $output;
}

//////////////////////////

function ba_event_get_testimonials($event_id){
    $output = '';
    
    $testi_arr = get_post_meta($event_id, '_event_testi', true);
    
    if (!empty($testi_arr)){
        
       foreach ($testi_arr as $testi_id){
       $post_object = get_post( $testi_id );
       $image = wp_get_attachment_image_src( get_post_thumbnail_id( $testi_id ), 'thumbnail' );
       $img_src = !empty($image) ? $image[0] : 'http://0.gravatar.com/avatar/9faf10eb44f32c4bbba91413588c01d6?s=64&d=mm&f=y&r=g';
       
       $output .= '
                    <div class="ba_event_testi_box">
                        <div class="ba_event_testi_content">
                            <span class="ba_event_testi_quote"></span>
                            <p>
                            '.apply_filters('translate_text', $post_object->post_content).'
                            </p>
                        </div>
                        <div class="ba_event_testi_author">
                            <span class="testi_author_photo" style="background: url(\''.$img_src.'\') center center no-repeat; background-size: cover;"></span>
                            <h4 class="testi_author_name">'.get_the_title($testi_id).'</h4>
                        </div>
                    </div>
                    ';
          }
     }
     
    if ($output){
        $output = '<div id="ba_event_testi">
                        <h2 class="event_testi_title">
                            '.__('Testimonials', BA_EVENT_TEXTDOMAIN).'
                        </h2>
                        '.$output.'</div>';
    } 
              
    return $output;
}

//////////////////////////////////////

function ba_event_get_faq($event_id){
    $output = '';
    $faq_arr = get_post_meta($event_id, '_event_faq', true);
    
    if (!empty($faq_arr)){
        
       foreach ($faq_arr as $faq_id){
          $post_object = get_post( $faq_id );
          $output .= '
          <div class="ba_event_faq_box">
                        <div class="toggle_faq_box">
                            <span class="toggle_icon_box">
                                <span class="chev_down"></span>
                            </span>
                            <h3 class="ba_event_faq_box_title">'.get_the_title($faq_id).'</h3>
                            <p class="ba_event_faq_box_collapse">
                                '.apply_filters('translate_text', $post_object->post_content).'
                            </p>
                        </div>
          </div>';
        }            
    }
    
    if ($output){
        $output = '<div id="ba_event_faq">
                        <h2 class="ba_event_faq_title">
                            '.__('FAQ', BA_EVENT_TEXTDOMAIN).'
                        </h2>
                        '.$output.'
                   </div>';
    }    
    
    return $output;
}

//////////////////////////////////

function ba_event_pager($max_num_pages, $paged){

     $pl_args = array(
     'base'     => add_query_arg('paged','%#%'),
     'format'   => '',
     'total'    => $max_num_pages,
     'current'  => max(1, $paged),
     //How many numbers to either side of current page, but not including current page.
     'end_size' => 1,
     //Whether to include the previous and next links in the list or not.
     'mid_size' => 2,
     'prev_text' => __('&laquo; Previous', BA_EVENT_TEXTDOMAIN), // text for previous page
     'next_text' => __('Next &raquo;', BA_EVENT_TEXTDOMAIN), // text for next page
     );

     // for ".../page/n"
    if($GLOBALS['wp_rewrite']->using_permalinks())
      $pl_args['base'] = user_trailingslashit(trailingslashit(get_pagenum_link(1)).'page/%#%/', 'paged');
      
      $pl_args = apply_filters('ba_event_pager_args', $pl_args);

      return '<div class="ba_event_pager">'.paginate_links($pl_args).'</div>';
}
