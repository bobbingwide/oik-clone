<?php // (C) Copyright Bobbing Wide 2015


/**
 * Display the oik-clone metabox
 *
 * - This should be a series of checkboxes, one for each target site 
 * - By default the checkboxes should be deselected since we don't want to update the slaves every time we make a change
 * - When the user selects "Update" and at least one of the checkboxes is checked then cloning will be performed
 * 
 * cb host - if slave post is set then we make it a simple link
 *
 * x http://qw/wordpress ( cloned )
 * x http://oik-plugins.com ( cloned )
 *
 *
 * Messages that may be displayed to the user are:
 * - Enable clone to enable cloning of this post type
 * - No slave servers defined
 * - Clone parent post first
 * 
 * Buttons:
 * Clone - not necessary since we use publish to achieve the same thing?
 * Synchronize - with selected
 * Servers - link to wp-admin/admin.phppage=oik-clone&tab=servers
 * Add slave - link to wp-admin/admin.php?page=
 * 
 * 
 * @param object $post - the post object
 * @param object $metabox - the metabox object
 */
function oik_clone_box( $post, $metabox ) {
  //bw_trace2();
  //bw_backtrace();
  oik_require( "admin/oik-save-post.php", "oik-clone" );
  $clone = post_type_supports( $post->post_type, "clone" );
  if ( $clone ) {
    $slaves = oik_clone_get_slaves();
    $clones = get_post_meta( $post->ID, "_oik_clone_ids", false );
    //print_r( $clones );
    //print_r( $slaves );
    oik_clone_display_cbs( $slaves, $clones );
    oik_clone_check_parent_cloned( $post, $slaves );
  } else {
    p( "Cloning not supported for post types that do not support 'clone'" );
  }
}

/**
 * Check if the parent post has been cloned 
 */
function oik_clone_check_parent_cloned( $post, $slaves ) {
  if ( $post->post_parent ) {
    $clones = get_post_meta( $post->post_parent, "_oik_clone_ids", false );
    if ( !count( $clones ) ) {
      echo "<p>Please clone the parent first</p>" ;
    }
  }    
    
}
                                                    
/**
 * Display the checkboxes for cloning
 * 
 * For each slave
 * - see if it's been cloned
 * - if so, create cb with link, and remove from the clone array
 * - if not, just create the cb
 * 
 * For each remaining clone
 * - create cb with link
 */                                                    
function oik_clone_display_cbs( $slaves, $clones ) {
  bw_trace2( null, null, true, BW_TRACE_DEBUG );
  $cloned = oik_reduce_from_serialized(  $clones );
  foreach ( $slaves as $key =>  $slave ) {
		
    //$cloned_id = bw_array_get( $cloned, $slave, null );
		$cloned_id = oik_clone_get_slave_id( $cloned, $slave );
		$cloned_date = oik_clone_get_slave_cloned( $cloned, $slave );
		
    unset( $cloned[ $slave ] );
    oik_clone_display_cb( $slave, $cloned_id, $cloned_date );
  }
  if ( count( $cloned ) ) {
    echo "<br />Previously cloned" ;
    foreach ( $cloned as $slave => $cloned_item ) {
			$cloned_id = oik_clone_get_slave_id( $cloned, $slave );
			$cloned_date = oik_clone_get_slave_cloned( $cloned, $slave ); 
      oik_clone_display_cb( $slave, $cloned_id, $cloned_date );
    }
  }
}

/**
 * Return the slave id from the _oik_clone_ids post meta structure
 * 
 * We need to take into account the fact that for versions up to v1.0.0-beta.0817
 * there was only a slave ID and no cloned date.
 * 
 * We also take into account the situation where the data has become corrupted.
 * `
         [http://oik-plugins.co.uk] => Array
            [id] => Array
                [id] => (integer) 10119
                [cloned] => (integer) 1470411259
            [cloned] => (integer) 1470819881
 * `
 * 
 * @param array $cloned { Nested array of cloned information keyed by slave
 *   @type ID $id post ID of the target post 
 *   @type integer $cloned timestamp last cloned
 *   }   
 * @param string $slave 
 */
function oik_clone_get_slave_id( $cloned, $slave ) {
	$target = bw_array_get( $cloned, $slave, null );
	bw_trace2( $target, "target", true );
	if ( is_array( $target ) ) {
		$slave_id = bw_array_get( $target, "id", null );
		
		if ( is_array( $slave_id ) ) {
			$slave_id = bw_array_get( $slave_id, "id", null );
		}
	} else {
		$slave_id = $target;
	}
	return( $slave_id );
}
	
/**
 * Return the cloned date from the _oik_clone_ids post meta structure
 * 
 * We need to take into account the fact that for versions up to v1.0.0-beta.0817
 * there was only a slave ID and no cloned date. We have to cater for a 0 date in other code.
 *
 */
function oik_clone_get_slave_cloned( $cloned, $slave ) {
	$target = bw_array_get( $cloned, $slave, null );
	if ( is_array( $target ) ) {
		$cloned = bw_array_get( $target, "cloned", null );
	} else {
		$cloned = 0 ;
	}
	return( $cloned );
}

function oik_clone_get_cloned_date( $cloned_date ) {
	if ( $cloned_date ) {
		$formatted = "<br />";
		$formatted .= bw_format_date( $cloned_date, "M j, Y @ G:i" );
	} else {
		$formatted = null;
	}
	return( $formatted ); 
}
  
/**
 * Display a checkbox for cloning / syndication to a slave server
 *
 * The checkbox will nearly always be unset since we don't want to 
 * clone every time we perform an update.
 * 
 * When the $clone_id is known then we can create a link to the post
 * When the $cloned_date is non-zero we can show this too
 * 
 * @param string $slave - the host URL
 * @param ID $clone_id - the ID of the target post, when known
 * @param integer $cloned_date - the post modified date when last cloned
 * 
 */  
function oik_clone_display_cb( $slave, $clone_id, $cloned_date=0 ) {
  bw_trace2();
  echo "<br />";
  $name = "slaves[$slave]";
  $text = oik_clone_get_label_or_link( $slave, $clone_id );
	$text .= oik_clone_get_cloned_date( $cloned_date );
  $lab = label( $name, $text );
  $value = null;
  $icheckbox = icheckbox( $name, $value );
  echo $icheckbox;
  echo $lab;
  //echo $clone_id ;
}

/**
 * Return a simple label or a link
 *
 * If the clone_id is set then we create a simple link to the post on the server
 * else we just display the literal value
 *
 * In the "slave" server we can create links back to the "master" post from which this content
 * was originally created. This allows us to update the "master" from the slave
 * even when the master is not listed as a slave. 
 * 
 * @param string $server - the "other" server 
 * @param string $clone_id - the post ID of the cloned post
 * 
 */
function oik_clone_get_label_or_link( $server, $clone_id ) {
  if ( $clone_id ) {
    $label = retlink( null, "$server/?p=$clone_id" );
  } else {
    $label = $server; 
  }
  return( $label );
}
    

/**
 * Reduce a serialised array to a simpler version
 * 
 * 
 */
function oik_reduce_from_serialized( $serialized ) {
	bw_trace2();
  $reduced = array();
  foreach ( $serialized as $serial ) {
    foreach ( $serial as $key => $value ) {
      $reduced[ $key ] = $value;
    }
  }
  bw_trace2( $reduced, "reduced", true, BW_TRACE_DEBUG );
  return( $reduced );
}   
    
  


