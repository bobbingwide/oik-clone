<?php // (C) Copright Bobbing Wide 2015, 2023

/**
 * Class BW_Nav_Tabs_Sections
 *
 * Handle a set of sections within an admin's pages's tabs
 *
 */
class BW_Nav_Tabs_Sections {

  public $page;
	public $tab;
	public $section;
	public $section_label;
	public $sections;
	public $default_section;
	public $default_label;
	
	
	/**
	 * @var BW_Nav_Tabs_Sections - the true instance
	 */
	private static $instance;
	
	/**
	 * 
	 */
	function __construct() {
		$this->sections = null;
	}
	
	/**
	 * Return a single instance of this class
	 *
	 * @return object 
	 */
	public static function instance() {
		if ( !isset( self::$instance ) && !( self::$instance instanceof BW_Nav_Tabs_Sections ) ) {
			self::$instance = new BW_Nav_Tabs_Sections;
		}
		return self::$instance;
	}

	/**
	 *
	 */
	public function page() {
		$page = bw_array_get( $_REQUEST, "page", null );
		$this->page = sanitize_text_field( $page );
		return( $this->page );
	}

	public function tab() { 
		$tab = bw_array_get( $_REQUEST, "tab", null );
		$this->tab = sanitize_text_field( $tab );
		return( $this->tab );
	}

	public function section() {
		$section = bw_array_get( $_REQUEST, "section", null );
		$this->section = sanitize_text_field( $section );
		return( $this->section );
	}
	
	/**
	 * Pseudo validation of the section
	 *
	 * Returns the default section for an invalid section
	 */
	public function validate_section() {
		$this->section_label = bw_array_get( $this->sections, $this->section, null );
		if ( !$this->section_label ) {
		  $this->section = $this->default_section;
		}
		return( $this->section );
	}
	
	public function sections() {
	  bw_trace2( $this->sections, "this sections" );
	  if ( !$this->sections ) { 
			$nav_tab_sections = array();
			if ( $this->default_section ) {
				$nav_tab_sections[ $this->default_section ] = $this->default_label;
			}
			$page = $this->page();
			$tab = $this->tab();
			$nav_tab_sections = apply_filters( "bw_nav_tabs_{$page}_{$tab}", $nav_tab_sections, $page, $tab );
			$this->sections = $nav_tab_sections;
		}
		return( $this->sections );
	}
	
	public function defaults( $default_section=null, $default_label=null ) {
	  $this->default_section = $default_section;
		if ( $default_label ) {
			$this->default_label = $default_label;
		} else {
			$this->default_label = $default_section;
		}
	}
	
	/**
	 * List the sections for the active page & tab
	 */
	public function list_sections() {
		sul( "subsubsub" );
		$count = 1;
		$last = count( $this->sections );
		bw_trace2( $this->sections, "nav-tab_sections" );
		foreach ( $this->sections as $nav_tab_section => $nav_tab_section_label ) {
			stag( "li" );
			bw_nav_tabs_section_link( $nav_tab_section, $nav_tab_section_label, $this->page, $this->tab, $this->section );
			if ( $count < $last ) {
				e( " | " );
			}
			$count++;
			etag( "li" );
		}
		eul();
		sediv( "cleared clear" );
		return( $this->section );
	}



}


/**
 * Implement nav tab sections for admin pages
 *
 * Having selected a particular admin page
 * the user then selects a particular tab
 * and within that there may be multiple sections.
 * 
 * Query parms are:
 * - page=
 * - tab=
 * - section=
 * 
 
 * e.g. from the WooCommerce Shipping tab.

<ul class="subsubsub">
<li>
<a href="http://qw/wordpress/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=" class="current">Shipping Options</a> | 
</li>
<li>
<a href="http://qw/wordpress/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=oik_shipping" class="">Weghit/Cnuroty</a> | 
</li>
<li>
<a href="http://qw/wordpress/wp-admin/admin.php?page=wc-settings&amp;tab=shipping&amp;section=wc_shipping_flat_rate" class="">Flat Rate</a> | 
</li>
...

</ul>

	To achieve this, when we get to a tab, we invoke the following hooks, similar to those for tabs
	apply_filters( "bw_nav_tabs_$page_$tab", $nav_sections, $page, $tab );

*/																																						 
function bw_nav_tabs_section_list( $default_section=null, $default_label=null ) {
  $bw_nts = bw_nav_tabs_sections();
  $bw_nts->defaults( $default_section, $default_label );
	$bw_nts->section();
	$bw_nts->sections();
	$section = $bw_nts->validate_section();
	$section = $bw_nts->list_sections();
	return( $section );
}

/**
 * Build a nav tab section link
 */
function bw_nav_tabs_section_link( $nav_tab_section, $nav_tab_section_label, $page, $tab, $section ) {
	$class = "nav-tab-section-$nav_tab_section"; 
	if ( $nav_tab_section == $section ) {
		$class .= " current";
	}
	$link = admin_url( "admin.php?page=$page&tab=$tab&section=$nav_tab_section" ); 
	alink( $class, $link, $nav_tab_section_label );
}

	
/**
 * Return the main instance of BW_Nav_Tabs_Sections classs
 * 
 * @return object Instance of the BW_Nav_Tabs_Sections class
 */
function bw_nav_tabs_sections() {
	$bw_nts = BW_Nav_Tabs_Sections::instance();
	return( $bw_nts );
}

/**
 * Return the currently selected, validated section
 *
 */
function bw_nav_tabs_section( $default_section=null, $default_label=null ) {
	$bw_nts = bw_nav_tabs_sections();
	$bw_nts->defaults( $default_section, $default_label );
	$bw_nts->section();
	$bw_nts->sections();
	$section = $bw_nts->validate_section();
	return( $section );
}	
	


