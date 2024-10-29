<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

require_once BA_EVENT_PLUGIN_DIR . '/classes/class-ba-list-table.php';

class BA_Event_Customers_List extends BA_Event_List_Table {

	/** Class constructor */
	public function __construct() {
        global $status, $page;
		parent::__construct( array(
			'singular' => __( 'Customer', BA_EVENT_TEXTDOMAIN ), //singular name of the listed records
			'plural'   => __( 'Customers', BA_EVENT_TEXTDOMAIN ), //plural name of the listed records
			'ajax'     => true //should this table support ajax?
		));

	}
/**
 * Retrieve customer’s data from the database
 *
 * @param int $per_page
 * @param int $page_number
 *
 * @return mixed
 */
public function get_customers( $per_page = 5, $page_number = 1 ) {

    $offset = ( $page_number - 1 ) * $per_page;
    $result = array();
    // main user query
     $args  = array(
      'role__in'  => array('customer'),
      'fields'    => 'all_with_meta',
      'number'    => $per_page,
      'offset'    => $offset // skip the number of users that we have per page
      );

      $order = ! empty( $_REQUEST['order'] ) ? esc_sql( $_REQUEST['order'] ) : 'ASC';

      if ( ! empty( $_REQUEST['orderby'] ) ){
       switch($_REQUEST['orderby']){
          case 'display_name':
        $args['orderby'] = 'display_name';
        $args['order'] = $order;
        break;
          case 'user_email':
        $args['orderby'] = 'user_email';
        $args['order'] = $order;
        break;
        default:
        $args['orderby'] = 'display_name';
        $args['order'] = $order;
        break;
       }
      } else {
        $args['orderby'] = 'display_name';
        $args['order'] = $order;
      }
    // Create the WP_User_Query object
      $wp_user_query = new WP_User_Query($args);
      // Get the results
      $authors = $wp_user_query->get_results();
      $i = 0;
      // check to see if we have users
      if (!empty($authors)){
      foreach ( $authors as $user ) {
         $result[$i]['email'] = $user->user_email;
         $result[$i]['ID'] = $user->ID;
         $result[$i]['name'] = $user->display_name;
         $result[$i]['tel1'] = get_user_meta($user->ID, 'tel1', true);
         $result[$i]['_admin_notes'] = get_user_meta($user->ID, '_admin_notes', true).'<br><button id="edit-notes-button'.$user->ID.'" data-u="'.$user->ID.'" type="button" title="Select">Edit notes</button>';
      //   $result[$i]['edit_notes'] = '<button id="edit-notes-button'.$user->ID.'" data-u="'.$user->ID.'" type="button" title="Select">Edit notes</button>';
         $i++;
         }
       }

  return $result;
}

/**
 * Delete a customer record.
 *
 * @param int $id customer ID
 */
public static function delete_customer( $id ) {
    wp_delete_user( $id );
}

/**
 * Returns the count of records in the database.
 *
 * @return null|string
 */
public function record_count() {

  $args  = array(
      'role__in'  => array('customer'),
      'number'    => -1
      );

  $user_count_query = new WP_User_Query($args);
  $user_count = $user_count_query->get_total();

  return $user_count;
}

/** Text displayed when no customer data is available */
public function no_items() {
  _e( 'No customers avaliable.', BA_EVENT_TEXTDOMAIN );
}

/**
 * Method for name column
 *
 * @param array $item an array of DB data
 *
 * @return string
 */
function column_name( $item ) {

  // create a nonce
  $delete_nonce = wp_create_nonce( 'sp_delete_customer' );

  $title = '<strong>' . $item['name'] . '</strong>';

  $actions = array(
    'delete' => sprintf( '<a href="'.admin_url( 'edit.php?post_type=booking&page=customers' ).'&action=%s&customer=%s&_wpnonce=%s">Delete</a>', 'delete', absint( $item['ID'] ), $delete_nonce )
  );

  return $title . $this->row_actions( $actions );
}

/**
 * Render a column when no column specific method exists.
 *
 * @param array $item
 * @param string $column_name
 *
 * @return mixed
 */
public function column_default( $item, $column_name ) {
  switch ( $column_name ) {
    case 'tel1':
    case 'email':
    case '_admin_notes':
  //  case 'edit_notes':
      return $item[ $column_name ];
    default:
      return print_r( $item, true ); //Show the whole array for troubleshooting purposes
  }
}

/**
 * Render the bulk edit checkbox
 *
 * @param array $item
 *
 * @return string
 */
function column_cb( $item ) {
  return sprintf(
    '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
  );
}

/**
 *  Associative array of columns
 *
 * @return array
 */
function get_columns() {
  $columns = array(
    'cb'      => '<input type="checkbox" />',
    'name'    => __( 'Name', BA_EVENT_TEXTDOMAIN ),
    'email' => __( 'e-mail', BA_EVENT_TEXTDOMAIN ),
    'tel1'    => __( 'Telephone', BA_EVENT_TEXTDOMAIN ),
    '_admin_notes'    => __( 'Admin notes', BA_EVENT_TEXTDOMAIN ),
 //   'edit_notes'    => ''
  );

  return $columns;
}

/**
 * Columns to make sortable.
 *
 * @return array
 */
public function get_sortable_columns() {
  $sortable_columns = array(
    'name' => array( 'name', true ),
    'e-mail' => array( 'e-mail', false ),
    'tel1' => array( 'tel1', false )
  );

  return $sortable_columns;
}

/**
 * Returns an associative array containing the bulk action
 *
 * @return array
 */
public function get_bulk_actions() {
  $actions = array(
    'bulk-delete' => 'Delete'
  );

  return $actions;
}

/**
 * Handles data query and filter, sorting, and pagination.
 */
public function prepare_items() {

  $columns = $this->get_columns();
  $hidden = array();
  $sortable = $this->get_sortable_columns();

  $this->_column_headers = array($columns, $hidden, $sortable);

  /** Process bulk action */
  $this->process_bulk_action();

  $per_page     = 10;
  $current_page = $this->get_pagenum();
  $total_items  = $this->record_count();

  $this->set_pagination_args( array(
    'total_items' => $total_items, //WE have to calculate the total number of items
    'per_page'    => $per_page, //WE have to determine how many items to show on a page
    'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
  ) );


  $this->items = $this->get_customers( $per_page, $current_page );
}

public function process_bulk_action() {

  //Detect when a bulk action is being triggered...
  if ( 'delete' === $this->current_action() ) {

    // In our file that handles the request, verify the nonce.
    $nonce = esc_attr( $_REQUEST['_wpnonce'] );

    if ( ! wp_verify_nonce( $nonce, 'sp_delete_customer' ) ) {
      die( 'Go get a life script kiddies' );
    }
    else {
      self::delete_customer( absint( $_GET['customer'] ) );

      //wp_redirect( admin_url( 'edit.php?post_type=booking&subpage=customers' ) );
     // exit;
     wp_die(__( 'Customer deleted!', BA_EVENT_TEXTDOMAIN ).'<br /><a href="'.admin_url( 'edit.php?post_type=booking&page=customers' ).'">'.__( 'Back to My Customers list', BA_EVENT_TEXTDOMAIN ).'</a>');
    }

  }

  // If the delete bulk action is triggered
  if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
       || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
  ) {

    $delete_ids = esc_sql( $_POST['bulk-delete'] );

    // loop over the array of record IDs and delete them
    foreach ( $delete_ids as $id ) {
      self::delete_customer( $id );

    }

    //wp_redirect( admin_url( 'edit.php?post_type=booking&subpage=customers' ) );
    //exit;
     wp_die(__( 'Customer(s) deleted!', BA_EVENT_TEXTDOMAIN ).'<br /><a href="'.admin_url( 'edit.php?post_type=booking&page=customers' ).'">'.__( 'Back to My Customers list', BA_EVENT_TEXTDOMAIN ).'</a>');
  }
}

}
