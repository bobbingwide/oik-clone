<?php // (C) Copyright Bobbing Wide 2014


/**
 * Display a box to connect to a remote server to list content
 * Display a box to list the content
 * Actions: Refresh from server
 */
function oik_clone_wp_api() {
 p( "Upgrade required. To use WP-API please purchase and install the oik-clone-wp-api plugin." );
}

/**
 * Dialog to discover which site to clone and parameters to use
 *
 * @TODO Cache the data when we're talking to a remote site
 * @TODO Implement different levels of Authentication: Basic, OAuth1 and Custom (e.g. oik-plugins authentication )
 * @TODO Implement the page using tabs - so that MultiSite, WP-API and File import are separate pages.
 * 
 *
 */
function oik_clone_source_site() {
  global $blog_urls;
  p( "Choose the source site to compare against." );
 
  //$source = bw_array_get( $_REQUEST, "_oik_clone_source", null );
  $source = oik_clone_source();
  $timeout = bw_array_get( $_REQUEST, "_oik_api_timeout", null );
 
  bw_form();
  stag( "table" );
  bw_textfield( "_oik_clone_source", 80, "Site URL", stripslashes( $source ) );
 
  if ( is_multisite() ) {
    //p( "Or select a site from the list" );
    
    oik_require( "admin/oik-clone-ms.php", "oik-clone" );
    $blog_urls = bw_get_blog_urls();
    bw_select( "_oik_ms_source", "Source blog", $oik_ms_source, array( '#options' => $blog_urls, '#optional' => true ) );
    
  } else { 
    //p( "Doh!" );
  }
  
  bw_textfield( "_oik_api_timeout", 4, "Timeout (secs)", stripslashes( $timeout ));
  etag( "table" );
  p( isubmit( "_oik_clone_list", "List content", null, "button-primary" ) );
  
  
  //h3( "Basic authentication" );
  //bw_textfield( "User name" );
  //bw_textfield( "Password" );
  //h3( "OAuth authentication" );
  //bw_textfield( "thingummy" );
  //p( "tbc" );
  //h3( "oik-plugins authentication" );
  etag( "form" );
 
    
}




/**
 * Find the URL to access WP-API
 *
 * @param array $req - the result from HEAD
 * @return string $api_url - e.g. qw/wp40/wp-json - it shouldn't have a trailing slash 
 * 
 * 
 */
function oik_clone_get_api_url( $req ) {
  $api_url = null;
  $headers = bw_array_get( $req, "headers", null );
  if ( $headers ) {
    $link = bw_array_get( $headers, "link", null );
    if ( $link ) {
      $api_url = oik_clone_parse_links( $link );
    }
  }
  return( $api_url );
}




 
/**
 * Display the selected posts and find out what the user wants to do with them
 *
 * 
 * 
 */
function oik_clone_selection_criteria() {
 
  $api_url = oik_clone_source_site_check();
  if ( $api_url ) {
    //oik_require( "oik-wp-api.php", "oik-batch" );
    
    $args = array( "timeout" => $timeout );
    $json_posts = oik_clone_get_api_route( $api_url, "/posts", $args );
    //print_r( $json_posts );
    $fields = bw_as_array( "ID,title,slug,type,guid" );
    $posts = oik_clone_list_posts( $json_posts, $fields  );
    p( "Displaying results from $api_url" );
    oik_clone_display_posts( $posts, $fields );
    
  } else {
    $oik_ms_source = oik_clone_ms_source();
    if ( $oik_ms_source ) {
      $fields = bw_as_array( "ID,post_title,post_name,post_type,guid" );
      $posts = oik_clone_list_ms_posts( $oik_ms_source, $fields );
      oik_require( "admin/oik-clone-match.php", "oik-clone" );
      $posts = oik_clone_match_posts( $posts, $fields );
      $fields['matched'] = 'matched';
      $fields[] = 'actions';
      //$fields['matched_title'] = 'matched_title';
      oik_clone_display_header();
      oik_clone_display_posts( $posts, $fields );
    } else {
      p( "Nothing selected" );
    } 
  }     
}




/**
 * Perform checks on the source site
 *
 * Confirm that the source site supports WP-API processing
 * 
 */
function oik_clone_source_site_check() {
  $api_url = null;
  
  $source = bw_array_get( $_REQUEST, "_oik_clone_source", null );
  if ( $source ) {
    oik_require("shortcodes/oik-link.php" );
    
    $url = bw_link_url( $source, null );
    $url .= '/';
    oik_require( "includes/oik-remote.inc" );
    
    $req = bw_remote_head( $url );
    
    if ( is_wp_error( $req ) ) {
      p( "Error" );
    } else {
      $api_url = oik_clone_get_api_url( $req );
      p( $api_url );
    }
  }
  return( $api_url );
}




/**
 */
function oik_clone_get_api_route( $api_url, $route='/', $args=null ) {
  $url = $api_url . $route;
  $req = bw_remote_geth( $url, $args );
  //print_r( $req );
  //bw_trace2( $req, "req_array", false );
  list( $request, $json ) = $req;
  return( $json );
}


/** 
 * Parse the link from this ugly string
 * 
 *  [link] => </wp40/wp-json/posts/?page=2>; rel="next", 
 *            <http://qw/wp40/wp-json/posts/503>; rel="item"; title="sb in shortcode", 
 *             <http://qw/wp40/wp-json/posts/497>; rel="item"; title="Premium plugins", 
              <http://qw/wp40/wp-json/posts/495>; rel="item"; title="cookies", 
              <http://qw/wp40/wp-json/posts/493>; rel="item"; title="bbboing tested", 
              <http://qw/wp40/wp-json/posts/480>; rel="item"; title="Use oik-css v0.6 for inline shortcodes", 
              <http://qw/wp40/wp-json/posts/477>; rel="item"; title="Demonstrating the diy shortcode", 
              <http://qw/wp40/wp-json/posts/463>; rel="item"; title="bw_plug oik-external-link-warning", 
              <http://qw/wp40/wp-json/posts/454>; rel="item"; title="[bw_users]", 
              <http://qw/wp40/wp-json/posts/450>; rel="item"; title="attachments", 
              <http://qw/wp40/wp-json/posts/447>; rel="item"; title="bw_logo"
 * }
 * code copied and cobbled from client-cli/lib/locator.php
 */
function oik_clone_parse_links( $links ) {
  if ( ! is_array( $links ) ) {
			$links = explode( ',', $links );
	}

	$real_links = array();
	foreach ( $links as $link ) {
		$parts = explode( ';', $link );
		$link_vars = array();
		foreach ( $parts as $part ) {
			$part = trim( $part, ' ' );
			if ( ! strpos( $part, '=' ) ) {
				$link_vars['url'] = trim( $part, '<>' );
				continue;
		  }
      list( $key, $val ) = explode( '=', $part );
	    $real_val = trim( $val, '\'" ' );
	  	$link_vars[ $key ] = $real_val;
	  }

	  $real_links[] = $link_vars;
  }
  $url = get_rel_link( $real_links );
  return( $url );
}




/**
 * Find the URL to access WP-API
 *
 * @param array $req - the result from HEAD
 * @return string $api_url - e.g. qw/wp40/wp-json - it shouldn't have a trailing slash 
 * 
 * 
 */
function oik_clone_get_api_url( $req ) {
  $api_url = null;
  $headers = bw_array_get( $req, "headers", null );
  if ( $headers ) {
    $link = bw_array_get( $headers, "link", null );
    if ( $link ) {
      $api_url = oik_clone_parse_links( $link );
    }
  }
  return( $api_url );
}




/**
 * 
 * In order to store OAuth authentication details for a remote site we need to be able to keep
 * the same information as WP-CLI does when using OAuth2
 * Where should this be stored? 
 * Options are:
 * - Oauth post type using post meta data
 * - user post meta - keyed the same way as WP-CLI caches its data
 * 
 * So how do we decide which "service" the user is using at the other site? 
 * I suppose we let them select it from those they have authorized.
 * 
 
string(29) "http://qw/wp40/oauth1/request"

    [Authorization] => OAuth oauth_version="1.0",oauth_nonce="b488a5fbf6a607072e332719c3278d0d",oauth_timestamp="1413205251",oauth_co
nsumer_key="qSWU6OQiP2aU",oauth_signature_method="HMAC-SHA1",oauth_signature="JPRP%2FFVf2lxxrsnmEp3Sw6byrYQ%3D"


Authorized!
Key: tWjAkyJzaSnMJ3gT4VOnzbf8
Secret: o5LhV1QveyCImx3TGb5Wvw7GjOOUoY7LugnjX1QwDEYV3oPD


Consumer key: qSWU6OQiP2aU
Consumer secret: ZxchyIePEPUkItiLZDcLCdu9tRRk42kMCq0sDySmeshn0Nle
Token key: ezhyYEwnz36Iy7DniaZYxp5c
Token secret: OwJu7pDqmIMz6eD4wmwQjs4PVOtlaQIHsUy92ocbmA1HDXSh

The information is stored in the wp_options table:
with an option_name of of oauth1_access_<i>Token key</i>

e.g. 

Key: oauth1_access_ezhyYEwnz36Iy7DniaZYxp5c


a:4:{s:3:"key";s:24:"ezhyYEwnz36Iy7DniaZYxp5c";
s:6:"secret";s:48:"OwJu7pDqmIMz6eD4wmwQjs4PVOtlaQIHsUy92ocbmA1HDXSh";
s:8:"consumer";i:524;
s:4:"user";i:2;}

WP-CLI stores the information in the cache
api/oauth1-blah

where blah is made from the URL you're using


But how do we actually use this information?
It must be the key that we pass to the server.
No, it's more complicated than that.

How does this make it any more secure?


OK, so now we're failing with 
23768456/23870904 F=160 check_oauth_signature(6) 
string_to_sign 
GET&http%3A%2F%2Fqw%2Fwp40%2Fwp-json%2Fusers&oauth_consumer_key%3DqSWU6OQiP2aU%26oauth_nonce%3D627d24e513113d719f3d6b668931637f%26oauth_signature_method%3DHMAC-SHA1%26oauth_timestamp%3D1413222926%26oauth_token%3DezhyYEwnz36Iy7DniaZYxp5c%26oauth_version%3D1.0

 
 
O:20:"Requests_Auth_OAuth1":3:
{s:11:" * consumer";
O:13:"OAuthConsumer":3:
{s:3:"key";s:12:"bLK1ksDVDeRE";
s:6:"secret";s:48:"v89aXyXXgP4ict0a8QxyZbqFMWRrvTCm1yUiCG4l0imV1haF";
s:12:"callback_url";N;}
s:8:" * token";
O:10:"OAuthToken":2:
{s:3:"key";s:24:"V5skAh71Ex7svZQ6Gwrqiggo";
s:6:"secret";s:48:"aeGSxz7pvN9ddOr08vecA2MCNGfrycQJCMeehgj3oc1I0gpf";}
s:19:" * signature_method";O:30:"OAuthSignatureMethod_HMAC_SHA1":0:{}
}
  

   
There's no substitute for reading RFC 5849.
Once you've got the fields in your grubby little hands
then you can start making authenticated HTTP requests.
   
https://www.drupal.org/node/349516
 
Examples:

Using the REST API to update the API reference




Cloning content from another site (e.g. a staging site) using PULL
- that's what we're trying to do here
- PULL will work for things that we're able to get when NOT logged in
- otherwise can we make it work when the user IS logged in and we're just sharing cookies?
- is this really naughty?


Cloning content to another site ( e.g. a production site ) using PUSH

Publishing new posts to Facebook, Twitter or somewhere else

Summarizing total orders across multiple WooCommerce shops

*/
 
