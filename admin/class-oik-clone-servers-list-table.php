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
 * where each field is registered using bw_register_field()
 * Note: If there are fields with the same name as option fields, then the current implementation of
 * bw_register_field() may be too simplistic and will need to be extended or replaced.
 *
 * 'actions' contains the actions that may be performed
 * We need to cater for 'actions' which are performed against an item in the list
 * e.g. "edit", "view", "delete"
 * and the actions performed against a selected instance; 'submit_actions'
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
 *	
 												); 
 * $args['page_actions'] = array( "add" => __( 'Add new', 'oik-clone' ) );
 * $args['submit_actions'] = array( "edit_update" => __( 'Update', 'oik-clone' )
                                 , "add_entry" => __( 'Add', 'oik-clone' )
																 );
														
 *	$args['object_type'] = 'bw_clone_servers'; 
 *  $args['option_field'] = 'servers'; 		
 * `										
 *
 */
 
class OIK_Clone_Servers_List_Table extends BW_List_Table {

  /**
	 * The fields when working on a single instance
	 */
	public $fields = array();
	
	
  /**
	 * The validated fields when working on a single instance
	 * Keyed with the field name ( no prefix ), value is the field value
	 */
	
	public $validated_fields = array();

	/**
	 * The current action
	 */
	public $action;

	/**
	 * Construct an instance of OIK_Clone_Servers_List_Table
	 *
	 * The values of $args are saved as $this->_args by the parent
	 *
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
		//$columns['slave'] = __( "Server", 'oik-clone' );
    //$columns['apikey'] = __( "API key", 'oik-clone' );
    //$columns['active'] = __( "Active?", 'oik-clone' );
		//$columns['matched'] = __( "Matched to", 'oik-clone' );
		
		$option_name = $this->option_name();
		global $bw_mapping;
		global $bw_fields;
		if ( isset( $bw_mapping['field'][ $option_name ] )) {
			foreach ( $bw_mapping['field'][ $option_name] as $field ) {
				$columns[ $field ] = $bw_fields[ $field ]['#title'];
			}
		}
	  bw_trace2( $columns, "columns" );
		return( $columns );
	}
	
	
  /**
   * Prepare the contents to be displayed
   * 
   * 1. Decide which columns are going to be displayed
   * 2. Work out what page we're on
   * 3. Load the items for the page
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
    bw_trace2( $this );
  }
	
	
  /**
   * Load the items taking pagination into account
   * 
   *
   */
  function load_items() {
    oik_require( "includes/bw_posts.php" );
		$atts = array();
		
    //$atts = $this->determine_pagination( $atts ); 
		//$slaves = bw_get_option( "slaves", "bw_clone_servers" );
		//bw_trace2( $slaves, "slaves" );
		
		//$items = bw_as_array( $slaves );
		//$items = $this->simulate_items( $items );
		$servers = bw_get_option( "servers", "bw_clone_servers" );
		$items = $servers;
		$items = array();
		$items = $this->more_items( $servers, $items );
		
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
	
	/**
	 * Get the "keyed" items 
	 *
	 * When stored in the wp_options table it's just a serialized array
	 * We need to associate a 'key' field, which could be called 'index'
	 * to be able to emulate the logic that uses post ID or user ID
	 * 
	 */
	function more_items( $servers, $items ) {
		//bw_trace2();
		if ( null !== $servers &&  count( $servers ) ) {
			foreach ( $servers as $server => $data ) {
				$data['key'] = $server;
				$items[] = $data;
			}
		}
		bw_trace2( $items );
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
    return( $title ); 
  }
	
	/**
	 * Display the "slave" column
	 *
	 * This is the field that contains the row actions
	 * In a generic solution the row actions should be attached to the first displayed field.
	 */
	function column_slave( $item ) {
		bw_trace2();
	
    $title = $this->column_default( $item, "slave" );
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
	 * The possible actions are 'actions' and 'page_actions'
	 * 
	 */
	function validate_action() {
		$action = bw_array_get( $_REQUEST, "action", null );
		$this->action = sanitize_text_field( $action );
		$actions = $this->_args['actions'];
		$validated_action = bw_array_get( $actions, $action, false );
		if ( !$validated_action ) {
			bw_trace2( $actions, "actions" );
			$actions = $this->_args['page_actions'];
			$validated_action = bw_array_get( $actions, $action, false );
		}
		if ( $validated_action ) {
		  $validated_action = $this->action;
		}
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
			//p( "Performing action: $action!" );
			$method = "perform_action_$action";
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			} else {
				//p( "No method: $method" );
			}
		}	else {
			// No action to perform
		}
	}
	
	/**
	 * Perform the "edit" action to display the edit dialog
	 *
	 * We need to:
	 * - Locate the object to edit... which means we need to know the key
	 * - Display the form
	 * - Display the actions associated with "edit"	 using the labels passed
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
		//p( isubmit( "edit_update", __( "Update", 'oik-clone' ), null, "button-primary" ) );
		$this->submit_button( 'edit_update' );
		etag( "form" );
	}

	/**
	 * Perform the "add" action to display the Add dialog
	 */
	function perform_action_add() {
		bw_form();
		stag( "table", "wide-fat" );
		$this->display_add_fields();
		etag( "table" );
		$this->submit_button( 'add_entry' );
		etag( "form" );
	}
	
	
	/**
	 * Perform the "view" action to display the View dialog
	 */
	function perform_action_view() {
		bw_form();
		stag( "table", "wide-fat" );
		$this->display_view_fields();
		etag( "table" );
		//$this->submit_button( 'add_entry' );
		etag( "form" );
	}
	
	
	
	/**
	 * Display a submit button
	 *
	 * 
	 */
	function submit_button( $submit_action ) {
		$submit_button_label = bw_array_get_dcb( $this->_args['submit_actions'], $submit_action, "Submit" );
		p( isubmit( $submit_action, $submit_button_label, null, "button-primary" ) );
	}

	/**
	 * Perform the "delete" action 
	 *
	 * @TODO Add Confirm deletion dialog if deemed necessary
	 * @TODO nonce checking
	 */
	function perform_action_delete() {
	  $option_name = $this->option_name();
		$option_field = $this->option_field();
		$option = bw_get_option( $option_field, $option_name );
		$key = $this->key();
		if ( $option ) {
		  unset( $option[ $key ] );
			bw_update_option( $option_field, $option, $option_name );
		}
	}

	/**
	 * Return all the fields for the selected option name
	 * 
	 * Similar to bw_get_field_names()
	 * @return array $names - the array of registered fields for the option
	 *  
	 */
	function get_fields() {
		$option_name = $this->option_name();
		$fields = array();
		global $bw_mapping;
		global $bw_fields;
		if ( isset( $bw_mapping['field'][ $option_name ] )) {
			foreach ( $bw_mapping['field'][ $option_name] as $field ) {
				$fields[ $field] = $bw_fields[ $field ];
			}
		}
		$this->fields = $fields;
		return( $this->fields );
	}
	
	/** 
	 * Get a field value
	 *
	 * We may need to prefix the field for the form, so that we don't get confused with other query parms
	 * Also cater for fields which are arrays.
	 *
	 */
	function get_field( $field ) {
		$value = bw_array_get( $_REQUEST, $field, null );
		
		if ( is_array( $value ) ) {
			// @TODO - Don't bother performing any validation yet
			foreach ( $value as $k => $v ) {
				$value[$k] = stripslashes( trim( $v ) ) ;
			}
		} else { 
			$value = stripslashes( trim( $value ) );
		}  
		return( $value ); 
	}
	
	/**
	 * Validate a field
	 * 
	 * Perform field validation/sanitization based on #field_type and $field name
	 * Here we rely on validation being performed by oik-fields.
	 * See bw_field_validation() for the original logiv
	 *
	 * @param string $field - field name of the custom post type's field
	 */
	function validate_field( $field, $data ) {
		bw_trace2();
		$value = $this->get_field( $field );
		$field_type = bw_array_get( $data, "#field_type", null );
		// @TODO Messages for invalid fields
		//$valid = _bw_field_validation_required( $value, $field, $data );
		if ( $field_type ) {
			$value = apply_filters( "bw_field_validation_${field_type}", $value, $field, $data ); 
		}
		$value = apply_filters( "bw_field_validation_${field}", $value, $field, $data );
		$this->validated_fields[ $field ] = $value;
		
	}

	/**
	 * Validate the fields entered on the form
	 */
	function validate_fields() {
		$thingy = apply_filters( "bw_validate_functions", null );
		bw_trace2( $thingy, "thingy", false );
		$fields = $this->get_fields();
		$validated = array();
		foreach ( $fields as $field => $data ) {
			$this->validate_field( $field, $data );  
		}
		$validated = $this->validated();
		return( $validated );
	}

	/**
	 * Check each field has been validated
	 *
	 */
	function validated() {
		$validated = true;
		foreach ( $this->fields as $key => $data ) {
			if ( !array_key_exists( $key, $this->validated_fields ) ) {
				$validated = false;
				break;
			}
		}
		bw_trace2( $validated, "validated?" );
		return( $validated );
	}

	/**
	 * Return the option_name
	 */
	function option_name() {
	  $option_name = $this->_args['object_type'];
		return( $option_name );
	}
	
	/**
	 * Return the option field
	 *
	 * @return string the name of the array field in the option
	 */
	function option_field() {
		$option_field = $this->_args['option_field'];
		return( $option_field );
	}
	
	/**
	 * Return the "key" field value
	 */
	function key() {
		$key = bw_array_get( $_REQUEST, "key", null );
		return( $key );
	}
	
	/**
	 * Update the selected option field
	 * 
	 * 
	 */ 
	function perform_submit_action_edit_update() {
	  bw_trace2();
		$this->validate_fields();
		bw_trace2( $this->validated_fields, "validated fields" );
	  $option_name = $this->option_name();
		$option_field = $this->option_field();
		$servers = bw_get_option( $option_field, $option_name );
		bw_trace2( $servers, "servers", false );
		// p( "Updating a server" );
			
		$key = $this->key();
    $servers[ $key ] = $this->validated_fields;
		bw_update_option( $option_field, $servers, $option_name );
		bw_trace2( $servers, "servers" );
	
	}
	
	/**
	 * Perform the add_entry submit action
	 *
	 * @TODO Check for duplicated key fields e.g. 'slave'
	 */
	function perform_submit_action_add_entry() {
	  bw_trace2();
		$this->validate_fields();
	  $option_name = $this->option_name();
		$option_field = $this->option_field();
		$servers = bw_get_option( $option_field, $option_name );
		bw_trace2( $servers, "servers", false );
		$servers[] = $this->validated_fields;
		bw_trace2( $servers, "servers after", false );
		bw_update_option( $option_field, $servers, $option_name );
	
	}

	/**
	 * Display the fields for the selected item
	 *
	 * If $this->items is not set then we need to load the data ourselves
	 * 
	 */
	function display_fields() {
		$key = $this->key();
		if ( $key !== null ) {
			$option = bw_get_option( $this->option_field(), $this->option_name() );
			$fields = bw_array_get( $option, $key, null );
		  //$fields = $this->items[ $key ];
			//bw_trace2( $fields, "fields" );
			foreach ( $fields as $field => $value ) {
				$data = bw_get_field_data( $field );
				if ( $data ) {
					bw_form_field( $field, $data['#field_type'], $data['#title'], $value, $data['#args'] ); 
				} else {
				  e( ihidden( $field, $value ) );
				}
			}
		}
	}
	
	/**
	 * Display the fields for the selected item
	 * 
	 */
	function display_add_fields() {
	
		$option_name = $this->option_name();
		global $bw_mapping;
		global $bw_fields;
		$value = null;
		if ( isset( $bw_mapping['field'][ $option_name ] )) {
			foreach ( $bw_mapping['field'][ $option_name] as $field ) {
				$columns[ $field ] = $bw_fields[ $field ]['#title'];
				$data = bw_get_field_data( $field );
				if ( $data ) {
					bw_form_field( $field, $data['#field_type'], $data['#title'], $value, $data['#args'] ); 
				} else {
					e( ihidden( $field, $value ) );
				}
			}
		}
		
	}
	
	
	/**
	 * Display the fields for the selected item
	 *
	 * If $this->items is not set then we need to load the data ourselves
	 * 
	 */
	function display_view_fields() {
		$key = $this->key();
		if ( $key !== null ) {
			$option = bw_get_option( $this->option_field(), $this->option_name() );
			$fields = bw_array_get( $option, $key, null );
		  //$fields = $this->items[ $key ];
			//bw_trace2( $fields, "fields" );
			foreach ( $fields as $field => $value ) {
				$data = bw_get_field_data( $field );
				if ( $data ) {
					stag( "tr" );
					stag( "td" );
					e( $data['#title'] );
					etag( "td" );
					stag( "td" );
          bw_theme_field( $field, $value, $data );
					etag( "td" );
					etag( "tr" ); 
				} else {
				  e( ihidden( $field, $value ) );
				}
			}
		}
	}
	
	/**
	 * The submit action must match the label of a submit action
	 * 
	 * @TODO Validate nonce as well
	 * 
	 */
	function validate_submit_action() {
		$submit_actions = bw_array_get( $this->_args, 'submit_actions', array() );
		$submit_action = null;
		bw_trace2( $submit_actions );
		foreach ( $submit_actions as $action => $label ) {
			if ( null === $submit_action ) {
				$submit_label = bw_array_get( $_REQUEST, $action, null );
				if ( $submit_label == $label ) {
				  $submit_action = $action;
					break;
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
			//p( "Performing action: $submit_action" );
			$method = "perform_submit_action_${submit_action}";
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			} else {
				//p( "No method: $method" );
			}
		} else {
		 //p( "No submit action" );
		}
	}
	
	/**
	 * Display the "page_actions" 
	 *
	 * The "page_actions" are the actions which apply to the page
	 * e.g. "Add new"
	 *
	 
	 */
	function display_page_actions() {
	  //e( "This:".  $this->action );
	 	$page_actions = bw_array_get( $this->_args, "page_actions", array() );
		foreach ( $page_actions as $action => $action_string ) {
			if ( $action != $this->action ) {
				e( $this->create_action_link( "pageaction", $action, $action_string ) );
			}
		}
	}


} 
