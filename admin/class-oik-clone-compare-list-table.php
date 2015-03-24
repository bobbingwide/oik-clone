<?php // (C) Copyright Bobbing Wide 2014

/**
 * Compare posts in a table
 *
 *  
 */
class OIK_Clone_Compare_List_Table { 

  public $source;
  public $target;
  
  function __construct( $args ) {
    parent::__construct();
   //$this->o();
    
    $this->source = $args["source"];
    $this->target = $args["target"]; 
  }
  
  function result( $source_value, $target_value ) {
    if ( $source_value == $target_value ) {
      $result = "=";
    } elseif ( $source_value > $target_value ) {
      $result = ">";
    } else {
      $result = "<";
    }
    return( $result );
  
  }
  
  function colvalue( $value ) {
    if ( $value ) { 
      $value = esc_html( $value );
    } else {
      $value = "&nbsp;";
    }
    return( $value );  
  }
  
  function compare( $field ) {
    $source_value = $this->source->$field;
    $target_value = $this->target->$field;
    $result = $this->result( $source_value, $target_value );
    $source_value = $this->colvalue( $source_value );
    $target_value = $this->colvalue( $target_value );
    bw_tablerow( array( $field, $source_value, $result, $target_value ) );
      
  }
  
  /**
   * Flatten post_meta data
   *
   * @param array $data
   * @return string HTML of post meta data
   *
   */
   function flatten_values( $data ) {
     bw_trace2();
     if ( $data ) {
       $values = implode( ", ", $data ); 
     } else {
       $values = null;
     }  
     //foreach ( $data as $key => $value ) {
     //}
     return( $values );
   }
  
  /**
   * Compare the post_meta data for the two posts 
   *
   * 
   */
  function compare_post_meta() {
    //$this->compare( "post_meta" );
    //$this->compone();
    $source_post_meta = $this->source->post_meta;
    $target_post_meta = $this->target->post_meta;
    //ksort( $source_post_meta );
    //ksort( $target_post_meta ); 
    //$sv = print_r( $source_post_meta, true );
    //$tv = print_r( $target_post_meta, true );
    bw_tablerow( array( "<b>post_meta</b>" ) );
    
    $matched = $this->assoc_array_match( $source_post_meta, $target_post_meta );
    foreach ( $matched as $key => $data ) {
      $data0 = $this->flatten_values( $data[0] );
      $data1 = $this->flatten_values( $data[1] );
      $result = $this->result( $data0, $data1 );
      bw_tablerow( array( $key, $data0, $result, $data1 ) );
    }
  }
  
  /**
   * Match two associative arrays by key
   *
   * @param array $source_post_meta
   * @param array $target_post_meta
   * @return array - matched array
   *
   */                         
  function assoc_array_match( $source_post_meta, $target_post_meta ) {
    ksort( $source_post_meta );
    ksort( $target_post_meta );
    $matched = array(); 
    $s = current( $source_post_meta );
    $skey = key( $source_post_meta );
    //echo $skey . $s;
    $t = current( $target_post_meta );
    $tkey = key( $target_post_meta );
    $count = 0; 
    while ( $skey !== null && $tkey !== null ) {
      if ( $skey < $tkey ) {
        //echo "$skey,$s,";
        $matched[$skey] = array( $s, null );
        $s = next( $source_post_meta );
        $skey = key( $source_post_meta );
      } elseif ( $skey > $tkey ) {
        //echo "$tkey,,$t";
        $matched[$tkey] = array( null, $t );
        $t = next( $target_post_meta );  
        $tkey = key( $target_post_meta ); 
      } else { 
        //echo "$skey,$s,$t"; 
        $matched[$skey] = array( $s, $t );
        $s = next( $source_post_meta );
        $skey = key( $source_post_meta );
        $t = next( $target_post_meta );  
        $tkey = key( $target_post_meta ); 
      }
    }
    while ( $skey !== null ) {
      //echo "more s";
      //echo "$skey,$s,";
      $matched[$skey] = array( $s, null );
      $s = next( $source_post_meta );
      $skey = key( $source_post_meta );
      
      //echo PHP_EOL;
    }
    while ( $tkey !== null ) {
      //echo "more t";
      //echo "$tkey,,$t";
      $matched[$tkey] = array( null, $t );
      $t = next( $target_post_meta );
      $tkey = key( $target_post_meta );
      //echo PHP_EOL;
    }
    return( $matched );
  }
  
  /**
   * 
   * Display the post comparison table
   *
   * Fields to choose from are:
   * `
    [ID] => 2
    [post_author] => 1
    [post_date] => 2012-09-28 10:02:50
    [post_date_gmt] => 2012-09-28 10:02:50
    [post_content] => 
    [post_title] => Sample Page
    [post_excerpt] => 
    [post_status] => publish
    [comment_status] => open
    [ping_status] => open
    [post_password] => 
    [post_name] => sample-page
    [to_ping] => 
    [pinged] => 
    [post_modified] => 2012-09-28 10:02:50
    [post_modified_gmt] => 2012-09-28 10:02:50
    [post_content_filtered] => 
    [post_parent] => 0
    [guid] => http://qw/wpms/site-2/?page_id=2
    [menu_order] => 0
    [post_type] => page
    [post_mime_type] => 
    [comment_count] => 0
    [filter] => raw
    `
   */
  function display() {
    stag( "table", "wp-list-table widefat" );
    stag( "thead");
    bw_tablerow( array( "Field", "Source", "Comparison", "Target" ) );
    etag( "thead" );
    $this->compare( "ID" ); 
    $this->compare( "post_title" );
    $this->compare( "post_name" );
    $this->compare( "post_modified" );
    $this->compare( "post_content" );
    $this->compare( "post_excerpt" );
    $this->compare( "post_type" );
    $this->compare( "post_mime_type" );
    $this->compare( "post_status" );
    $this->compare( "post_parent" );
    $this->compare( "guid" );
    
    $this->compare_post_meta(); 
    
    etag( "table" );
  }

}

