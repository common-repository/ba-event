<?php

class ba_event_yml_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'ba_event_yml_widget',
// Widget name will appear in UI
__('BA Event You may like', BA_EVENT_TEXTDOMAIN),
// Widget description
array( 'description' => __('Show "you may like" events on the event page', BA_EVENT_TEXTDOMAIN), )
);
}

// Creating widget front-end
// This is where the action happens
 function widget( $args, $instance ) {
  Global $BA_Event_var, $post;
  
  $event_post_type = isset($BA_Event_var->settings['event_post_type']) ? $BA_Event_var->settings['event_post_type'] : 'event';
  
  if (is_single() && $post->post_type == $event_post_type){
    
  extract( $args );
  $title = apply_filters('widget_title', $instance['title']);
      echo $before_widget;
      if ( $title ) {
        echo $before_title . $title . $after_title;
        }

     echo '<div id="widget-ba-event-yml">'.$BA_Event_var->get_event_may_like().'</div>';

     echo $after_widget;
   }  
}

// Updating widget
 function update( $new_instance, $old_instance ) {
$instance = $old_instance;
$instance['title'] = strip_tags($new_instance['title']);
return $instance;
}

// Widget Backend
 function form( $instance ) {

    if (isset($instance['title'])) $title = esc_attr($instance['title']);
      else $title = '';

        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
<?php
}

} // Class ends here

// Register and load the widget
function ba_event_yml_widget_load_widget() {
	register_widget( 'ba_event_yml_widget' );
}
add_action( 'widgets_init', 'ba_event_yml_widget_load_widget' );
