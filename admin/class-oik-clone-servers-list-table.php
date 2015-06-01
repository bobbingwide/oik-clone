<?php // (C) Copyright Bobbing Wide 2015

/**
 * Implement the servers list table for oik-clone
 *
 * Uses the BW_List_table class to implement a list table for a WordPress options field stored as an array
 *
 * The $_args array contains the information that enable this class to cater for options fields
 * which are stored as serialized arrays. 
 * 
 * 'object_type' contains the definition of the option field
 * which is mapped to fields using bw_register_field_for_object_type
 * where each fields is registered using bw_register_field()
 * Note: If there are fields with the same name as option fields, then the current implementation of
 * bw_register_field() may be too simplistic and will need to be extended or replaced.
 *
 * 'actions' contains the actions that may be performed
 * We need to cater for list actions which are performed against an item in the list
 * and the actions performed against a selected instance
 * e.g. 'edit_update', 'add_add' / 'add_new',  'delete_confirm'
 * 
 * Example $args passed to create this class, the OIK_Clone_Servers_List_Table.
 *   
 * `
 * $args = array( "plugin" => "oik-clone", "tab" => "servers", "page" => "oik_clone", "section" => "advanced" );
 * $args['actions'] = array( "edit" => __( 'Edit', 'oik-clone' )
 *													, "view" => __( 'View', 'oik-clone' )
 *													, "delete" => __( 'Delete', 'oik-clone' )
 *													, "add" => __( 'Add', 'oik-clone' )
 *													); 
 * $arg['submit_actions'] = array( "edit_update", __( 'Update', 'oik-clone' )
                                 , "add_entry", __( 'Add', 'oik-clone' )
																 );
														
 *	$args['object_type'] = 'bw_clone_server';			
 * `										
 *
 */
 
class OIK_Clone_Servers_List_Table extends BW_List_Table {

  // public $actions = array(); - part of $_args
	//


	/**
	 * Construct an instance of OIK_Clone_Servers_List_Table
	 * 
	 */
	function __construct( $args=array() ) {
		bw_trace2();
		parent::__construct( $args );
	}

	/**
	 * Return the columns and labels for these settings
	 * 
	 * In a generic solution these columns would be returned 
	 * from args passed when the class is constructed.
	 * 
	 */
  function get_columns() {
	  bw_trace2();
    $columns = array();
		$columns['cb'] = '<input type="checkbox" />';

		/* translators: manage posts column name */
		$columns['slave'] = __( "Server", 'oik-clone' );
    $columns['apikey'] = __( "API key", 'oik-clone' );
    $columns['active'] = __( "Active?", 'oik-clone' );
		$columns['matched'] = __( "Matched to", 'oik-clone' );
		
	  bw_trace2( $columns, "columns" );
		return( $columns );
	}
	
	
  /**
   * Prepare the contents to be displayed
   * 
   * 1. Decide which columns are going to be displayed
   * 2. Work out what page we're on
   * 3. Load the items for the page
   * 4. Fiddle about with matching
   * 5. 
   * 
   */
  function prepare_items() {
	  bw_trace2( $this->screen, "this screen", false );
    $columns = get_column_headers( $this->screen );
    $hidden = array();
    $sortable = array(); 
    $this->_column_headers = array( $columns, $hidden, $sortable );  
    //
    //$this->reset_request_uri();
    $this->items = $this->load_items(); 
    // Now we have to do the matching
    
    bw_trace2( $this );
  }
	
	
  /**
   * Load the items taking pagination into account
   * 
   *
   */
  function load_items() {
    oik_require( "includes/bw_posts.inc" );
		$atts = array();
		
    //$atts = $this->determine_pagination( $atts ); 
		$slaves = bw_get_option( "slaves", "bw_clone_servers" );
		bw_trace2( $slaves, "slaves" );
		
		$items = bw_as_array( $slaves );
		$items = $this->simulate_items( $items );
		
		bw_trace2( $items, "items" );
		            
    $this->record_pagination( $atts, $items );
    return( $items );
  }
	
	function simulate_items( $slaves ) {
		$items = array();
	  foreach ( $slaves as $key => $slave ) {
		  $items[] = array( "key" => $key 
			                , "slave" => $slave 
			                      , "apikey" => "apikey"
														, "active" => "on"
														, "matched" => 0
														);
		}
		return( $items );
	}
															
	
	function record_pagination( $atts, $items ) {
		$page = bw_array_get( $atts, "paged", 1 );
		$posts_per_page = $this->get_items_per_page( "oik_clone_per_page" );
		if ( $posts_per_page ) {
			$count = count( $items );
			$pages = ceil( $count / $posts_per_page );
			$args = array( 'total_items' => $count
									, 'total_pages' => $pages
									, 'per_page' => $posts_per_page
									);
			$this->set_pagination_args( $args );
		}  
	}
	
	function column_cb( $item ) {
    $item = (array) $item;
    $title = $this->column_default( $item, "cb" );
    $actions = array();
		
    //$args = array( "slave" => $item['slave'] ); 
		$args = array( "key" => $item[ 'key' ] ); 
		$actions['edit'] = $this->create_action_link( $item, "edit", "Edit", $args );
		$actions['view'] = $this->create_action_link( $item, "view", "View", $args );
		$actions['delete'] = $this->create_action_link( $item, "delete", "Delete", $args );
    $title .= $this->row_actions( $actions );
    return( $title ); 
  }
	
  /**
   * Create an action link for the given action
   * 
   * We need to add the page and tab to the args array passed
   * which should already consist of the other parameters needed to implement the action
   * such as the source and target IDs and other parameters for this specific instance
   * 
    //$ID = $item['ID'];
    //&amp;source=$ID
   * Is this better than using add_query_arg()? 
   * 
   */  
  function create_action_link( $item, $action, $action_string, $args=array() ) {
    //bw_trace2( $this->screen );
    //bw_trace2( $this->_args );
    $args['page'] = $this->_args['page'];
    $args['tab'] = $this->_args['tab'];
		$args['section'] = $this->_args['section'];
		
    $flatargs = null;
    if ( count( $args ) ) {
      foreach ( $args as $key => $value ) {
        $flatargs .= "&amp;$key=$value"; 
      }
    }
    $link = retlink( null, admin_url("admin.php?action=$action${flatargs}"), $action_string ); 
    return( $link );
  }
	
	/**
	 * Validate the action against the possible actions
	 * 
	 */
	function validate_action() {
	  $action = bw_array_get( $_REQUEST, "action", null );
		$this->action = sanitize_text_field( $action );
		$actions = $this->_args['actions'];
		bw_trace2( $actions, "actions" );
		$validated_action = bw_array_get( $actions, $action, false );
		return( $validated_action );
	}
	
	public function section() {
		$section = bw_array_get( $_REQUEST, "section", null );
		$this->section = sanitize_text_field( $section );
		return( $this->section );
	}
	
	/**
	 * Perform the requested action
	 * 
	 * Do we need to see the submit button action here?
	 *
	 */
	
	function perform_action() {
	  $action = $this->validate_action();
		if ( $action ) {
			p( "Performing action: $action!" );
			$method = "perform_action_$action";
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			} else {
				p( "No method: $method" );
			}
		}
	}
	
	/**
	 * Perform the "edit" action to display the edit dialog
	 *
	 * We need to:
	 * - Locate the object to edit... which means we need to know the key
	 * - Display the form
	 * - Display the actions associated with "edit"
	 * 
	 * Somewhere along the lines we actually need to perform the edit updates
	 * 
	 * 
	 */
	function perform_action_edit() {
		bw_form();
		stag( "table", "wide-fat" );
		$this->display_fields();
		etag( "table" );
		p( isubmit( "edit_update", "Change", null, "button-primary" ) );
		etag( "form" );
	}

	function display_fields() {
	  $key = bw_array_get( $_REQUEST, "key", null );
		if ( $key !== null ) {
		  $fields = $this->items[ $key ];
			bw_trace2( $fields, "fields" );
		}
	}
	
	/**
	 * The submit action must match the label of a submit action
	 */
	function validate_submit_action() {
		$submit_actions = $this->_args['submit_actions'];
		$submit_action = null;
		foreach ( $submit_actions as $action => $label ) {
			if ( null === $submit_action ) {
				$submit_label = bw_array_get( $_REQUEST, $action, null );
				if ( $submit_label == $label ) {
				  $submit_action = $action;
				}
			}
		}
		return( $submit_action );
				
	
	}

	/**
	 * perform the submit action
	 *
	 */
	function perform_submit_action() {
		$submit_action = $this->validate_submit_action();
		if ( $submit_action ) {
			p( "Performing action: $submit_action" );
			$method = "perform_submit_action_${submit_action}";
			if ( method_exists( $this, $method ) ) {
				$this->method();
			} else {
				p( "No method: $method" );
			}
		}
	}
		

} 
