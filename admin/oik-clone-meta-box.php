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
 * x http://qw/wordpress
 * x http://oik-plugins.com
 *
 *
 * Messages that may be displayed to the user are:
 * - Enable publicize to enable cloning of this post type
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
  $publicize = post_type_supports( $post->post_type, "publicize" );
  if ( $publicize ) {
    $slaves = oik_clone_get_slaves();
    $clones = get_post_meta( $post->ID, "_oik_clone_ids", false );
    //print_r( $clones );
    //print_r( $slaves );
    oik_clone_display_cbs( $slaves, $clones );
  } else {
    p( "Cloning not supported for post types that do not support publicize" );
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
  bw_trace2();
  $cloned = oik_reduce_from_serialized(  $clones );
  foreach ( $slaves as $key =>  $slave ) {
    $cloned_id = bw_array_get( $cloned, $slave, null );
    unset( $cloned[ $slave ] );
    oik_clone_display_cb( $slave, $cloned_id );
  }
  if ( count( $cloned ) ) {
    echo "<br />Previously cloned" ;
    foreach ( $cloned as $slave => $cloned_id ) {
      oik_clone_display_cb( $slave, $cloned_id );
    }
  }
}
  
/**
 * Display a checkbox for cloning / syndication to a slave server
 *
 * The checkbox will nearly always be unset since we don't want to 
 * clone every time we perform an update.
 * 
 * When the $clone_id is known then we can create a link to the post
 * 
 * @param string $slave - the host URL
 * @param ID $clone_id - the ID of the target post, when known
 * 
 */  
function oik_clone_display_cb( $slave, $clone_id ) {
  bw_trace2();
  echo "<br />";
  $name = "slaves[$slave]";
  $text = oik_clone_get_label_or_link( $slave, $clone_id );
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

  $reduced = array();
  foreach ( $serialized as $serial ) {
    foreach ( $serial as $key => $value ) {
      $reduced[ $key ] = $value;
    }
  }
  bw_trace2( $reduced, "reduced" );
  return( $reduced );
}   
    
  


