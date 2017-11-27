<?php // (C) Copyright Bobbing Wide 2014, 2015


/**
 * oik-clone Clone list table
 * 
 * Extends BW_List_Table for oik-clone
 * this can be further extended by OIK_Clone_MS_List_Table and OIK_Clone_Self_List_Table
 * But we might find that OIK_Clone_List_Table is good enough for "Self" - lets assume so
 * 
 * 
 */
class OIK_Clone_List_Table extends BW_List_Table {

  /**
   * Store the name of the source from which the posts are imported
   * 
   * Should this not just be a field in $args? 
   */                    
  public $source;
  
  /**
   * Store the name of the source field which identifies the "source" to be used
   */
  public $source_field; 
  
  /**
   * Construct an instance of OIK_Clone_List_Table
   * 
   */
  function __construct( $args=array() ) {
  
    bw_trace2();
    parent::__construct( $args );
    //$this->trac30183();
  }
  
  /**
   * Return the columns to be displayed
   *
   * This function is invoked very early on in the page display. 
   * It's invoked in response to filter "manage_${screen_id}_columns"
   * which is called by 
   * 
   *
   * We want guid but it should be hidden mostly
   * 
   * Action ID  Status Matched by Title Date
   * ------ --  ------ ---------- ----- ----
   *        sx  Source
   *        Actions: Import new
   * 
   *        tx  Target ID
   *        Actions: Edit, Replace, Delete, Compare
   *
   *        ty  Target GUID
   * 
   *        tz  Target title
   *        
   * 
   */
  function get_columns() {
    $columns = array();
    
		$columns['cb'] = '<input type="checkbox" />';

		/* translators: manage posts column name */
		$columns['post_title'] = _x( 'Title', 'column name', 'oik-clone' );
    $columns['matched'] = __( "Matched by", 'oik-clone' );
    $columns['ID'] = __( "ID", 'oik-clone' );
    $columns['post_modified'] = __( "Modified", 'oik-clone' );
    $columns['post_name'] = __( "Slug" );
    $columns['post_type'] = __( "Post type", 'oik-clone' );
    
		/**
		 * Filter the columns displayed in the Posts list table for a specific post type.
		 *
		 * The dynamic portion of the hook name, $post_type, refers to the post type slug.
		 *
		 * @since 3.0.0
		 *
		 * @param array $post_columns An array of column names.
		 */
    $screen_id = $this->screen->id;
    //echo "get_columns screen ID:" . $screen_id;
    
    // There is no need to call this ourselves as this will end up in an infinite loop
    // Any plugin that wants to add additional columns to the 'oik-options_page_oik_clone' 
    // list display will have to filter "manage_oik-options_page_oik_clone_columns".
    // 
		//$columns = apply_filters( "manage_{$screen_id}_columns", $columns );
	  bw_trace2( $columns, "columns" );
    return( $columns );
  } 
  
  /**
   * Determine pagination parameters
   * 
   * Before calling get_posts we need to know which page we're displaying so that we only return the relevant rows
   * 
   * We need to know the pagination stuff here so that we load the correct set of posts.
   * We just need to know posts_per_page and the page number ( 'paged' )
   * but in order to perform pagination we need to use WP_Query which enables us to find the total number of posts
   * 
   * @param array $atts - parameters to be passed to the routine that accesses the content
   * @return array $atts - updated atts array
   * 
   */                                      
  function determine_pagination( $atts ) {
    $page = bw_array_get( $_REQUEST, "paged", 1 );
    $atts['paged'] = $page;
    $atts['posts_per_page'] = $this->get_items_per_page( "oik_clone_per_page" );
    $atts['bw_query'] = new WP_Query(); 
    return( $atts );
  }  
  
  /**
   * Load the items taking pagination into account
   * 
   * Note: This ignores the fact that we're going to do matching - which will cause more rows to be displayed
   * How do we deal with this?
   *
   */
  function load_items() {
    oik_require( "includes/bw_posts.php" );
    $atts = array( "post_type" => "any" 
                 , "orderby" => "ID"
                 , "order" => "DESC"
                 );
    $atts = $this->determine_pagination( $atts );             
    $posts = bw_get_posts( $atts ); 
    $this->record_pagination( $atts );
    return( $posts );
  }
  
  /**
   * Set the pagination args based on what we found
   * 
   * @param array $atts - which is expected to contain the WP_Query object used
   *
   */
  function record_pagination( $atts ) {
    $bw_query = bw_array_get( $atts, "bw_query", null );
    bw_trace2( $bw_query, "bw_query", false );
    if ( $bw_query ) {
      $page = bw_array_get( $atts, "paged", 1 );
      $posts_per_page = bw_array_get( $atts, "posts_per_page", null );
      if ( $posts_per_page ) {
        $count =  $bw_query->found_posts;
        bw_trace2( $bw_query->found_posts, "found_posts", false );
        $pages = ceil( $count / $posts_per_page );
        // $start = ( $page-1 ) * $posts_per_page;
        // $end = min( $start + $posts_per_page, $count ) -1 ;
        // bw_navi_s2eofn( $start, $end, $count );
        $args = array( 'total_items' => $count
                     , 'total_pages' => $pages
                     , 'per_page' => $posts_per_page
                     );
        $this->set_pagination_args( $args );
      }  
    }  
  }
  
  /**
   * Set the source to be used to access the data
   *
   * Also, ensure this is set in the REQUEST_URI
   *
   * @param string $source_field - the name to be used for the source on links
   * @param string $source - the value for the "source"
   */
  function set_source( $source_field, $source=null) {
    $this->source_field = $source_field; 
    $this->source = $source;
    
    bw_trace2( $_SERVER, "server", false );
    
    $_SERVER['REQUEST_URI'] = esc_url( add_query_arg( $source_field, $source ) );
    bw_trace2( $_SERVER, "server" ,false );
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
    $columns = get_column_headers( $this->screen );
    $hidden = array();
    $sortable = array(); 
    $this->_column_headers = array( $columns, $hidden, $sortable );  
    //
    $this->reset_request_uri();
    $this->items = $this->load_items(); 
    // Now we have to do the matching
    
    bw_trace2( $this );
  }
  
  /**
   * Return the bulk actions supported
   * 
   * Delete - delete the selected ID 
   * Update - update from the source
   * Edit - ?
   * View - ?
   */
    function get_bulk_actions() {
      $actions = array();
      //$actions = array( 'delete' => 'Delete'
      //                , 'update' => 'Update'
      //                );
      return $actions;
    }
    
    
  /**
    * Display the post_title column 
    *
    * The output includes the actions that may be performed
    *
     * Row actions 
     <div class="row-actions">
     <span class="edit">
     <a href="http://qw/wordpress/wp-admin/post.php?post=28389&amp;action=edit" title="Edit this item">Edit</a> | 
     </span>
     <span class="inline hide-if-no-js">
     <a href="#" class="editinline" title="Edit this item inline">Quick&nbsp;Edit</a> | </span>
     <span class="trash">
     <a class="submitdelete" title="Move this item to the Trash" 
        href="http://qw/wordpress/wp-admin/post.php?post=28389&amp;action=trash&amp;_wpnonce=632928a290">Trash</a> | </span>
     <span class="view"><a href="http://qw/wordpress/oik-plugins/oik-clone/" title="View 'oik-clone'" rel="permalink">View</a>
     </span>
     </div>
   */
  function column_post_title( $item ) {
    $item = (array) $item;
    $title = $this->column_default( $item, "post_title" );
    $actions = array();
    $args = array( "source" => $item['source'] );  
    //$actions['view'] = $this->create_action_link( $item, "view", "View", $args );
    //$actions['edit'] = $this->create_action_link( $item, "edit", "Edit", $args );
    
    if ( isset( $item['target'] ) ) {
      $args['target'] = $item['target'];
      $actions['update'] = $this->create_action_link( $item, "update", __( "Update", 'oik-clone' ), $args );
      $actions['compare'] = $this->create_action_link( $item, "compare", __( "Compare", 'oik-clone' ), $args );
    } else {
      $actions['import'] = $this->create_action_link( $item, "import", __( "Import new", 'oik-clone' ), $args );
    }
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
    if ( $this->source && $this->source_field ) {
      $args[ $this->source_field ] = $this->source;
    }
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
   *
   * Starting with a list of $items we create a new list 
   * that combines the current items and the matched items
   * Do we need to do this as OO code. Should it be a separate classs, or an interface?
  
   * Here we add the filters that we want to use
   * then perform the matching for each post returned
   * The results are used to create a new list of posts 
   * @param array $posts - array of posts from the target
   * @param array $fields - 
   * 
   */
  function match_posts() {
    oik_require( "admin/oik-clone-match.php", "oik-clone" );
    oik_clone_match_filters();
    $posts = $this->items;
    $new_items = array();
    foreach ( $posts as $key => $data ) {
      $data->matched = "source";
      $data->source = $data->ID;
      // $data->target = $data->ID;
      $new_items[] = $data;
      bw_trace2( $data ); 
     
      $ID = $data->ID;
      
      // This is the match by 'magic' logic which needs to be replaced by the official code
       
      
      $new_item = clone $data;
      // get an array of matched posts
      // now append them to the new_items list
      //
      // $matched_posts = oik_clone_match_post( $ID, $data );
      //if ( $matched_posts ) {
      // $posts[$key]["matched_posts"] = $matched_posts;
      /*
      $new_item->ID = 1;     
      $new_item->source = $ID; // 
      $new_item->target = 1; // this should be the ID of the matched post
      $new_item->matched = "magic";
      $new_items[] = $new_item;
      */
      
      $matched_items = $this->match_post( $data );
      if ( count( $matched_items ) ) {
        foreach ( $matched_items as $new_item ) {
          $new_items[] = $new_item;
        }
      }
      
      
     }
     $this->items = $new_items; 
      
     bw_trace2( $new_items );
   } 
    
  /**
   * Return an array of posts that match the given one
   * 
   * Other routines have been given the chance to add their filters to perform matching
   * Each one needs to return the set of posts that they consider to be a match
   * using oik_clone_add_to_matched() to add the set of posts into the list
   * 
   * @param object $data - the post object to match against
   * @return array - the array of matched posts
   */
  function match_post( $data ) {
    $matched = array();
    $matched = apply_filters( "oik_clone_match_post", $matched, $data );
    //$this->vienna();
    return( $matched );
  }

  /**
   * Ensure the pagination links don't attempt to perform any actions
   * 
   * REQUEST_URI is used by BW_List_Table::pagination() to build the paging links
   * we need to ensure that only pagination is performed.
   * So we need to remove the fields that can be set on the action links
   * ie. action should not be set.
   * Elsewhere, in set_source(), we set the name and value of the field that 
   * tells us the source of the data being loaded. 
   *  
   */
  function reset_request_uri() {
    //$request_uri = $_SERVER['REQUEST_URI'];
    $request_uri = remove_query_arg( array( "action", "source", "target" ) );
    //$request_uri = add_query_arg( "_
    $_SERVER['REQUEST_URI'] = $request_uri; // Don't esc_url() here ... too early.
    //$this->o();
  }  
  
  /**
   * Ultra foxed
   */
  function trac30183() {
    bw_trace2();
    bw_backtrace();
    error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
    @ini_set('display_errors', true); //Ensure that Fatal errors are displayed.
    $this->vienna();
    bw_trace2();
  }
  
    

}

