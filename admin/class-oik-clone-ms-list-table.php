<?php // (C) Copyright Bobbing Wide 2014

/**
 * oik-clone Clone MS list table
 * 
 * Extends OIK_Clone_List_Table which extends BW_List_Table for oik-clone
 * 
 * 
 */
class OIK_Clone_MS_List_Table extends OIK_Clone_List_Table {

  
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
    
    switch_to_blog( $this->source );
  
    parent::prepare_items();
    
    restore_current_blog();
    
    
    bw_trace2( $this );
  }

}
