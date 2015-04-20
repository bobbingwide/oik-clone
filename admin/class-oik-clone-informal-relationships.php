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
  public $source_ids;
  public $state_types;
  public $source;
  

  function __construct( $source_ids=null ) {
    $this->chr = null;
    $this->char_type = '0';
    $this->set_state_types();
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
   *  2   | significant delimeter | 
   *  0   | anything else
   * 
   * Once we've started an anything else string a digit won't end it.
   * But a significant delimeter will
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
   * - type - the same as character type ( 1-digits, 2-significant delimeter, 0-everything else 
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
   * To cut a long story short, we're looking for an integer token ( type='1' ) which is enclosed by two delimeter tokens ( type='2' ).
   * Anything else indicates the wrong type of delimeter.
   * 
   * Note: It shouldn't be possible to have adjacent tokens of the same type; except the first and last, which are edge cases
   */
  function identify_ids() {
    $ct = count( $this->tokens );
    $ct--;
    for ( $t = 1; $t < $ct; $t++ ) {
      $match = $this->tokens[ $t-1 ]['type'];
      $match .= $this->tokens[ $t ]['type'];
      $match .= $this->tokens[ $t+1 ]['type'];
      if ( $match == "212" ) {
        $this->maybe_add_id( $this->tokens[$t]['token'], $t );
      } 
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
    if ( $intvalid ) {
      $this->handle_id( $id, $t );
      /**
      if ( $this->source ) {
        $this->add_id( $intvalid );
      }
      if ( $this->target ) {
        $this->replace_id( $intvalid, $t );
      }  
      */
    }
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
