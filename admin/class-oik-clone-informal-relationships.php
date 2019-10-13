<?php // (C) Copyright Bobbing Wide 2015

/**
 * OIK_clone_informal_relationships
 *
 */
abstract class OIK_clone_informal_relationships {

  public $tokens = array();
  public $current = null;
  public $state = '0';
  public $chr;
  public $char_type = '0';
  public $state_types;
  /**
   * token index of the latest start of a shortcode or query parameter
   * We don't have to backtrack past this point
   * 
   */
  public $latest_shortcode_start;
  public $valid_tokens;
  

  function __construct() {
    $this->chr = null;
    $this->char_type = '0';
    $this->set_state_types();
    $this->set_valid_tokens();
    $this->token_init();
  }
  
  function set_mapping( $target_ids ) {
    $this->target_ids = $target_ids;
  }
  
  /**
   * Re-initialise the tokens array
   *
   */
  function token_init() {
    $this->tokens = array();
    $this->current = null;
    $this->state = '0';
  }
  
  /**
   * Get the character type
   *
   * Using simple character codes rather than constants
   * for not a very good reason I suppose
   *
   * type | meaning | Notes
   * ---- | ------- | ----------
   *  1   | digit   | Digits 0 thru 9
   *  2   | significant delimiter | 
   *  0   | anything else
   * 
   * Once we've started an 'anything else' string a digit won't end it.
   * But a significant delimiter will.
   *  
   */
  function get_char_type() {
    $this->char_type = '0';
    switch ( $this->chr ) {
      case '0':
      case '1':
      case '2';
      case '3':
      case '4':
      case '5':
      case '6':
      case '7':
      case '8':
      case '9': 
        $this->char_type = '1';
        break;
        
      case '=':
      case ' ':
      case "\t":
      case "\n":
      case "\r":
      case ',':
      case ']':
      case '"':
      case "'":
      case "[":
      case "?":
      case "&":
      case "/":
      case "\\":
      case "<":
      case ">":
        $this->char_type = '2';
        
    }
    //echo $this->chr . $this->char_type . " " ;
    
  }
  
  /**
   * Define the state types lookup array
   * 
   * 
   * The table represents the action to perform when looking at the 
   * current state and char type combination and the new state to be set
   * Action 'A' means append the character to the current token
   * Action 'W' means write the current token and start a new one
   * 
   * The T in the key was introduced while debugging what turned out to be a very silly programming error.
   *
   */
  function set_state_types() {
    $this->state_types = array( "T00" => array( "action" => "A", "new_state" => "0" )
                              , "T01" => array( "action" => "A", "new_state" => "0" )
                              , "T02" => array( "action" => "W", "new_state" => "2" )
                              , "T10" => array( "action" => "A", "new_state" => "0" )
                              , "T11" => array( "action" => "A", "new_state" => "1" )
                              , "T12" => array( "action" => "W", "new_state" => "2" )
                              , "T20" => array( "action" => "W", "new_state" => "0" )
                              , "T21" => array( "action" => "W", "new_state" => "1" )
                              , "T22" => array( "action" => "W", "new_state" => "2" )
                              );
   //print_r( $this->state_types );                              
  }
  
  /**
   * Define the valid token types for post ID context
   * 
   * - Some parameter names are not valid for IDs but are valid for integers. e.g. posts_per_page=10
   * - Other parameter names are for taxonomy or user IDs... e.g. cat=5, author=1 
   * - We may need to handle these in the future
   * - And then there's a set of parameter names where we do expect to find post IDs
   *
   * The array we create is for the parameter names which can be IDs.
   * If we find anything else we say it's not a post ID, so it won't get mapped
   * 
   * Having populated the array we flip the keys for a quick check in check_token()  
   *                           
   * @TODO - We also have to take into account positional parameters somehow
   */
  function set_valid_tokens() {
    $this->valid_tokens = array( "id"
                               , "ids"
                               , "p"
                               , "exclude"
                               , "include"
                               , "page_id"
                               , "post_parent"
                               , "post_parent__in"
                               , "post_parent__not_in"
                               , "post__in"
                               , "post__not_in"
                               , "meta_value"
                               , "meta_value_num"
	                            , 'productID'
	    , 'mediaId'
	    , 'productId'
                               );
    // $this->valid_tokens = apply_filters( "oik_clone_valid_tokens", $this->valid_tokens );
    $this->valid_tokens = array_flip( $this->valid_tokens );                           
  }                               
  
  /**
   * Find out what action to take
   * 
   * Look up the state type combination in the state_types array
   */
  function get_action( $state_type ) {  
    $action = $this->state_types[ $state_type ]['action'];
    //echo "State_type:" . $state_type;
    //print_r( $this->state_types[ $state_type ] );
    //echo "Action:" . $action;
    return( $action );
  }
  
  /**
   * Write the next token
   *
   * The value of $this->current may be null
   * Each token consists of two fields
   * - token - the string value (may be null)
   * - type - the same as character type ( 1-digits, 2-significant delimiter, 0-everything else 
   */
  function write_token() {
    $this->tokens[] = array( "token" => $this->current, "type" => $this->state );
    $this->current = $this->chr;
  }
  
  /**
   * Append the character to the current token
   */
  function append_char() {
    $this->current .= $this->chr;
  }
                           
  /**
   * Split the string into tokens
   *
   */
  function tokenize( $content ) {
    $this->token_init();
    $content_array = str_split( $content );
    foreach ( $content_array as $this->chr ) {
      $this->get_char_type();
      $state_type = "T" . $this->state . $this->char_type;
      $action = $this->get_action( $state_type );
      
      
      if ( $action == "A" ) {
        $this->append_char();
      } elseif ( $action == "W" ) { 
        $this->write_token();
      } else {
        echo $action;
        print_r( $this->state_types[ $state_type ] );
        gobng();
      }
      $this->state = $this->state_types[ $state_type ]['new_state'];
      //echo "After:" . $state_type . $action . $this->state . "!" . PHP_EOL;
    }
    $this->write_token();
    //print_r( $this->tokens );
  }
  
  /**
   * Find the IDs for the informal relationships 
   * 
   *
   */
  function find_ids( $content ) {
    $this->tokenize( $content );
    $this->identify_ids();
  }

  /**
   * Determine the IDs from the tokens 
   *
   * Find each post ID and think about handling it. 
   * 
   * To cut a long story short, we're looking for an integer token ( type='1' ) which is enclosed by two delimiter tokens ( type='2' ).
   * Anything else indicates the wrong type of delimiter.
   * 
   * Note: It shouldn't be possible to have adjacent tokens of the same type; except the first and last, which are edge cases
   */
  function identify_ids() {
    $this->latest_shortcode_start = 0;
    $ct = count( $this->tokens );
    $ct--;
    for ( $t = 1; $t < $ct; $t++ ) {
      $this->set_latest_shortcode_start( $t-1 );
      $match = $this->tokens[ $t-1 ]['type'];
      $match .= $this->tokens[ $t ]['type'];
      $match .= $this->tokens[ $t+1 ]['type'];
      if ( $match == "212" ) {
        $this->maybe_add_id( $this->tokens[$t]['token'], $t );
      } else {
        //echo $match . PHP_EOL;
      } 
    }
  }
  
  /** 
   * Set the latest shortcode start index
   *
   * Processing depends on the token value. 
   * Remember that we're going to backtrack to this position
   * So we can move it forward as quickly as we like, can't we?
   * @TODO Probably not. It's horribly complicated isn't it! 
   * 
   */
  function set_latest_shortcode_start( $index ) {
    switch ( $this->tokens[ $index ]['token'] ) {
      case "[":
      case "?":
      case "&":
        $this->latest_shortcode_start = $index;
        //echo "Latest: $index" . PHP_EOL;
        break;
        
      case "]":
      case "/":
      case "\\":
      case "<":
      case ">":
        $this->latest_shortcode_start = 0;
        break;
        
      default: 
        // Leave as is
    }
  }
  
  /**
   * Apply sanity checks to the ID
   *
   * OK. So we've found an integer. We need to check the context.
   * 
   * In the absence of any other logic, post IDs are not normally littered willy nilly in content.
   * 
   * Places where we might expect to see them are:
   * - in shortcodes  e.g. [gallery ids=123,456] [bw_link 123] [bw_pages post_parent=456]
   * - in ugly URLs e.g http://example.com/?p=123 
   * 
   * Anywhere else it's probably just a number e.g. 1 + 1 = 2
   * Ignore 0's as well
   * 
   * 
   */
  function maybe_add_id( $id, $t ) {
    $intvalid = intval( $id );
    if ( $intvalid && $this->is_in_context( $t ) ) {
      //echo "Token $t a goodun" . PHP_EOL;
      $this->handle_id( $id, $t );
    }
  }
  
  /**
   * Check if this is an ID in context 
   *
   * We want to know whether or not we think this is an ID.
   * So we check the context, by backtracking no further than the start of the shortcode
   * to see what sort of parameter we might be processing.
   *
   * If we find an "=" then we check the previous token
   * If we don't then we keep going until we see a space.
   
   *
   */
  function is_in_context( $t ) {
    $in_context = null;
    if ( $this->latest_shortcode_start ) {
      $t--;
      while ( $in_context === null && ( $t > ( $this->latest_shortcode_start ) ) ) {
        $in_context = $this->check_context( $t );
        $t--;
      }
      // If we've got back to the start and not yet confirmed it
      // then perhaps this is a positional parameter
      // so let's err on the true side rather than false.
      if ( null === $in_context ) { 
        $in_context = true;
      }
    }
    return( $in_context );
  }
  
  /**
   * Return true if the ID is in context
   *
   * Note: In shortcode processing the parameter names are lower cased
   * so we should convert them as well
   * 
   * @param integer $t - the index of the token
   * 
   */
  function check_context( $t ) {
    $in_context = null;
    
    //echo "$t $in_context" . PHP_EOL;
    //echo $this->tokens[ $t ]['token'] . PHP_EOL;
    if ( $this->tokens[ $t ]['token'] == "=" ) {
      $token_type = $this->tokens[ $t-1 ]['type'];
      //echo "token_type: $token_type" . PHP_EOL;
      if ( $token_type == "0" ) {
        $token_value = strtolower( $this->tokens[ $t-1 ]['token'] ); 
        $in_context = $this->check_token( $token_value  );  
      }
    }
    return( $in_context );
  }
  
  /**
   * Return true if the token is a valid context token
	 *
	 * @TODO Some of the valid_tokens are only valid when paired with another token
	 * e.g. `meta_value=2` is only valid when the `meta_key` points to a relationship field	such as `_plugin_ref`
   * 
   * @return bool - true if it's a valid context, false otherwise
   */
  function check_token( $token_value ) {
    //echo "Token value: $token_value" . PHP_EOL;
    $in_context = isset( $this->valid_tokens[ $token_value ] );
    //print_r( $this->valid_tokens );
    //var_dump( $in_context );
    return( $in_context );
  }   
  
  abstract function handle_id( $id, $t );
   
  /**
   * Map the IDs and reassemble the content
   *
   * Is this just a target method?
   *
   */
  function map_ids( $string ) {
    $this->find_ids( $string );
    $new_string = $this->join_tokens();
    return( $new_string );
  }
   
  /**
   * Reassemble the tokens back into a string
   *
   */
  function join_tokens() {
    $content = null;
    foreach ( $this->tokens as $key => $token ) {
      $content .= $token['token'];
    }
    return( $content );
  }

}
