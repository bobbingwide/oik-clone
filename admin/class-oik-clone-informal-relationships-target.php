<?php // (C) Copyright Bobbing Wide 2015


oik_require( "admin/class-oik-clone-informal-relationships.php", "oik-clone" );

/**
 * OIK_clone_informal_relationships_target class
 *
 * Implements the server end of informal relationships mapping
 *
 * Uses the target IDs array to map the source IDs to target IDs in the informal relationships
 * in the post content.
 *
 * This logic is not responsible for changing the source URL to the target URL...  that's done elsewhere
 * 
 * @TODO Decide what to do if the target ID is 0, which'll happen the related post has not been cloned
 * 
 * 
 *
 */
class OIK_clone_informal_relationships_target extends OIK_clone_informal_relationships {

  public $target_ids;
  
  function __construct( $target_ids ) {
    parent::__construct();
    $this->target_ids = $target_ids; 
  }

  /**
   * Implement OIK_clone_informal_relationships::handle_id
   *
   * In the target we handle the ID by replacing the source ID with the target ID.
   * Using a wrapper function in case there's more logic to apply.
   *
   * @param ID $id - the source ID
   * @param integer $token - index to the tokens array
   */
  function handle_id( $id, $t ) {
    $this->replace_id( $id, $t );
  }
  
  /**
   * Replace ID in the tokens array
   * 
   * @param ID $id - the source ID
   * @param integer $token - index to the tokens array
   */
   function replace_id( $id, $t ) {
     if ( array_key_exists( $id, $this->target_ids ) ) { 
       $tid = $this->target_ids[ $id ];
       if ( $tid ) {
         $this->tokens[ $t ]['token'] = $tid;
       }
     }  
   }  
}
