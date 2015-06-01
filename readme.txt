=== oik-clone ===
Contributors: bobbingwide
Donate link: http://www.oik-plugins.com/oik/oik-donate/
Tags: clone, compare, update, MultiSite
Requires at least: 4.1
Tested up to: 4.2.2
Stable tag: 0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: oik-clone
Domain Path: /languages/

== Description ==
Clone content between sites


oik-clone provides tools to merge and synchronise content between WordPress sites.

Features:

- push content on Update to multiple targets
- pushes post content, post meta data and taxonomy terms
- pushes the attached file, for attachments
- maintains relationships: e.g. post_parent and fields referencing other content
- maintains informal relationships: e.g. in post_content
- pull content from other sites in a MultiSite installation
- compare and update or import from self or a MultiSite site


oik-clone is dependent on the oik base plugin; using the oik-plugin as a library of functions sitting on top of WordPress.
Both plugins need to be installed and activated on each site.

This solution was developed to address a couple of specific problems
 - performance comparison and improvement in multiple implementations. SEO is not a consideration.
 - synchronization of multiple sites for performance analysis



== Installation ==
1. Upload the contents of the oik-clone plugin to the `/wp-content/plugins/oik-clone' directory
1. Activate the oik-clone plugin through the 'Plugins' menu in WordPress
1. Use oik options > Clone to access the admin interface

Install on both the client and server machines.


== Frequently Asked Questions ==

= Is there a beta test version? =

Beta versions for v1.0 are suffixed -beta.mmdd

Yes. v0.8 was the first Beta test version.

Beta testing of v0.8 was performed on the oik-plugins servers and WP-a2z sites.

Previously v0.7 was Alpha tested on the oik-plugins servers.

= What does the Multi-Site tab do? =

The initial version of this plugin is designed for use on WordPress MultiSite
where you have cloned an existing site into a new version, developed the new version
and now want to copy the new contents back into the existing site.

= What does the Self tab do? =

The Self tab allows for introspection.
It provides the same actions as the Multisite tab, but the source and target site is the same.
 
- You can use it to look at all your content in one big listing
- You may find duplicated content
- You can compare similar content
- You can duplicate content by performing Import


= How does the "Clone on update" work? =

This'll be documented in the FAQs on the site



= Is this suitable for pushing from Staging to Production? =

It depends. 
If you have a complex content structure or a lot of new/changed content then the answer is "No, not yet"
If you have a simple content structure - just posts and pages
then you may find this useful.

To support pushing from Staging to Production requires additional work to identify the network of posts to be cloned.



= Does this use the REST API? =
Not yet, though that was the plan. 

It was more complicated than I first thought. 
So I developed the solution as an extensible solution.
For the pull model the oik-clone base logic supports
- Self
- Multisite

Extension plugins, yet to be published, may support
- REST
- WXR

For the push model the oik-clone base logic supports
- External sites, which includes Multisite 
 

= Why is this dependent upon oik? =
Some plugins use Composer to manage their dependencies, pulling in library functions from 
third party plugins hosted on GitHub and elsewhere.

The oik suite of plugins use common APIs from the oik base plugin.
There should be little overhead.
Even though oik implements over 80 shortcodes none of them are registered until shortcodes are actually needed.
oik attempts to OWN the problem; Only When Necessary.

One day perhaps the lowest level of oik base functions will be exported to a library
but in the mean time you have to download and activate oik to make this work.

= Are there limitations? =

Yes. Work is needed in the following areas.

- Hierarchical content requires parent content to be cloned first - partially solved in v0.5
- Limitation on media file size imposed by servers

= What authentication method is used? =

Simple validation of an API key.
Other methods will be implemented in future versions.


== Screenshots ==
1. Clone on update meta box - select targets
2. Clone on update meta box - with cloned post links
3. Clone on update meta box - Previously cloned  

== Upgrade Notice ==
= 1.0-beta.0601 =
Improved discovery of target post using slug. 

= 1.0-beta.0511 =
Required where the target doesn't have oik-fields active.
Now partially internationalized, with support for the bbboing language ( bb_BB locale ).
Depends on oik v2.6-alpha.0511 or higher

= 1.0-beta.0422 =
Improved support for larger media files. Improved mapping of informal relationships in post content. 

= 0.9 =
Improved mapping of informal relationships in post content.

= 0.8 - 
Supports mapping of informal relationships in post content.

= 0.7 = 
Supports pushing of hierarchical taxonomies. For Alpha testing on oik-plugins sites. 

= 0.6 = 
Now supports pushing of attachments and the attached media file. Nearly ready for Beta test.

= 0.5 = 
Supports mapping of post IDs in post_meta data. Not quite ready for beta test.

= 0.4 = 
Now supports cloning of hiearchical content - maintaining the post_parent on updates.

= 0.3 =
Now has basic support for cloning non-hierarchical taxonomies

= 0.2 =
Prototype for cloning content on Update

= 0.1 =
Prototype for WordPress Multi Site cloned sites

== Changelog ==
= 1.0-beta.0611 =
* Changed: Added logic to find target post by slug, when not found by GUID. See oik_clone_find_target_by_slug()
* Added: Prototype logic for improved Servers settings
* Added: 
* Changed: Added sanitization when dealing with $_SERVER['REQUEST_URI']
 

= 1.0-beta.0511 =
* Changed: Improved security using esc_url() around add_query_arg()
* Fixed: is_relationship_field() method loads "includes/bw_fields.inc". Needed if oik-fields is not activated.
* Fixed: temporary fix for multisite pull; only calls oik_clone_filter_all_post_meta if it exists.
* Fixed: temporary fix in oik_clone_publicize(); commented out "No slaves to clone" message which was being displayed in WPMS pull
* Added: Logic to disable heartbeat processing based on a constant "HEARTBEAT". Disables processing if HEARTBEAT is false
* Added: Logic to force use of oik_clone_update_slave_simple(), when constant "OIK_CLONE_FORCE_SIMPLE" is set
* Added: prototype filter function for "heartbeat_settings" to control heartbeat interval
* Added: partially localized version in the bbboing language ( locale bb_BB ) 

= 1.0-beta.0422 =
* Changed: Media files are now passed using multipart forms. See [api oik_clone_update_slave_multipart()] 
* Changed: MD5 calculation and checking is performed against the original file, not the base64 encoded version
* Changed: [api bw_remote_post_file()] 
* Added: [api oik_clone_load_media_from_files]
* Changed: [api OIK_clone_post_file::file_contents] handles media passed through $args['body']['media']
* Fixed: [api OIK_clone_post_file::attach_body()] passes each top level array entry as a separate field
* Added: [api OIK_clone_post_file::detach_media] to detect media passed through $args['body']['media']
* Fixed: Informal relationship mapping: detect the end of query args to prevent out of context IDs from being mapped

= 0.9 =
* Changed: Improved detection of IDs for informal relationship mapping. Only maps IDs with selected parameter names.
* Fixed: oik_clone_add_attachment() no longer calls oik_clone_edit_attachments(); too early
* Changed: Added logic to perform mapping of "_bw_image_link" post meta when stored as an integer, representing a post ID

= 0.8 =
* Added: "oik_clone_apply_informal_mapping" filter and functions to apply informal relationship mapping on the target
* Added: "oik_clone_build_list" filter and function to identify informal relationships
* Added: OIK_clone_post_file class 
* Added: [cloned] shortcode to display cloned status of a post
* Added: oik_clone_post_file.php - to replace current logic for posting an attached media file. Not yet integrated.
* Changed: Increase timeout on oik_clone_update_slave() to cater for media file upload
* Changed: oik_clone_load_media_file logic only applied for attachments
* Fixed: Set correct description for new taxonomy terms
* Fixed: Taxonomies should be updated on new post creation. 
* TODO: oik_clone_apply_mapping() not yet performed in the pull model
* Added: OIK_clone_informal_relationships class
* Added: OIK_clone_informal_relationships_source class
* Added: OIK_clone_informal_relationships_target class
* Tested: Up to WordPress 4.2-beta4 and MultiSite

= 0.7 = 
* Added: Pushing of hierarchical taxonomies
* Added: Hierarchical taxonomies loaded using oik_clone_load_hierarchical_terms()
* Added: Flat taxonomies loaded using oik_clone_load_flat_terms()
* Added: Both routines use class OIK_clone_taxonomies
* Added: admin/class-oik-clone-taxonomies.php 
* Changed: Now uses oik_clone_lazy_update_taxonomies2()
* Added: Some screenshots of the Clone on update meta box

= 0.6 = 
* Added: Cloning for "attachments" 
* Added: Implements actions for "edit_attachment" and "add_attachment"
* Added: Both actions invoke oik_clone_lazy_edit_attachment() if the attachment post type supports "publicize"
* Added: admin/oik-clone-media.php
* Changed: No longer populating the slaves array with defaults
* Changed: oik_clone_publicize() now accepts $load_media parameter
* Changed: Currently the media file is loaded regardless of the value of the $target ID
* Changed: "media" block is also passed to the server, JSON encoded
* Fixed: Checking of target ID for the slave
* Changed: tracing in oik_clone_reply_with_json()  
* Changed: oik_clone_find_target_by_GUID() needs to support finding "attachment"s 
* Changed: oik_clone_attempt_import() will load the media file for attachments.
* Changed: This is passed to the insert_post
* Changed: After initial creation oik_clone_update_attachment_metadata() is called
* Changed: Delete and insert all post meta does not alter _wp_attachment_metadata

= 0.5 = 
* Added: target server checks the mapping of posts and applies valid mapping updates.
* Added: Currently hardcoded for "_thumbnail_id" and "noderef" type fields
* Note: Not fully tested for multiple select noderef fields.
* Note: Not tested in the Self/MultiSite tabs

= 0.4 = 
* Added: New logic for cloning relationships between posts.
* Added: AJAX request includes the known post mapping from master to slave server
* Added: The server tests this mapping to determine the correct post ID for the post_parent
* Added: OIK_clone_relationships class implements client end
* Added: OIK_clone_mapping class implements server end
* Added: Plugins can implement "oik_clone_build_list" filter for handling client field mappings
* Added: Base logic supports oik-fields "noderef" field type and "_thumbnail_id"
* Note: Logic for handling relationships in post_meta data is not yet complete on the server end
* Note: bw_trace2() calls are being used for debug tracing

= 0.3 = 
* Added: Simple message on hierarchical posts if the parent has not been cloned
* Added: admin/oik-clone-taxonomies.php to implement logic to clone taxonomy terms
* Changed: Should MultiSite be hypenated or spaced or neither? 
* Changed: Slaves tab now called Settings and is the default tab.
* Changed: oik_clone_load_post loads taxonomies into post->post_taxonomies
* Changed: oik_clone_perform_import() and oik_clone_update_target() call oik_clone_update_taxonomies()
* Fixed: Doesn't crash for post_status 'trash'. Doesn't delete clones either.
* Fixed: oik_clone_get_target_slaves() may not find any values  

= 0.2 =
* Added: Displays "Clone on update" meta box for post types that support 'publicize'
* Added: Performs updates only when post status is "publish" - does not yet support "inherit" for attachments/ other CPTs
* Added: Optionally performs cloning when content is updated.
* Added: Client uses AJAX requests to push content to the slave servers
* Added: Server supports AJAX requests to clone content pushed from the master to the slave
* Added: oik-clone admin now has a Servers (Settings) tab
* Added: Implements a very basic level of Authentication. Client accesses OIK_APIKEY constant  
* Changed: Self tab is no longer the default
* Fixed: Quotes in the post title caused SQL messages
* Changed: oik_clone_match_post_by_GUID() requires includes/bw_posts.inc when used in the slave server
* Fixed: oik_clone_update_target() should not pass the post_meta array to wp_update_post()
* Changed: oik_clone_delete_all_post_meta() and oik_clone_insert_all_post_meta() should not process "_oik_clone_ids"
* Tested: Supported on WordPress 4.1 and WPMS 4.1 and above

= 0.1 =
* Added: New plugin. 
* Added: Multisite capability, developed for Rathmore Financial and wp-a2z.com / subdomain.wp-a2z.org
* Added: Self capability (default) developed for testing in a non Multisite environment
* Added: Classes inherit from BW_List_Table, forked from WP_List_Table 
* Depends: on oik v2.4 or higher 
 


== Further reading ==
If you want to read more about the oik plugins then please visit the
[oik plugin](http://www.oik-plugins.com/oik) 
**"the oik plugin - oik information kit"**

For other cloning plugins see: 
[oik-clone FAQs](http://www.oik-plugins.com/oik) 


