=== oik-clone ===
Contributors: bobbingwide
Donate link: http://www.oik-plugins.com/oik/oik-donate/
Tags: clone, compare, update, MultiSite
Requires at least: 4.1
Tested up to: 4.2-beta3
Stable tag: 0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: oik-clone
Domain Path: /languages/

== Description ==
Clone content between sites


oik-clone provides tools to merge and synchronise content between WordPress sites.

Features:

- push content on Update to multiple targets
- pushes post content and post meta data
- pushes the attached file, for attachments
- maintains relationships: e.g. post_parent and fields referencing other content
- pull content from other sites in a MultiSite installation
- compare and update or import from self or a MultiSite site


oik-clone is dependent on the oik base plugin.
Both plugins need to be installed and activated on each site.

This is a prototype solution to address a specific problem - performance comparison and improvement in multiple implementations. 
SEO is not a consideration.




== Installation ==
1. Upload the contents of the oik-clone plugin to the `/wp-content/plugins/oik-clone' directory
1. Activate the oik-clone plugin through the 'Plugins' menu in WordPress
1. Use oik options > Clone to access the admin interface

Install on both the client and server machines.


== Frequently Asked Questions ==

= Is there a beta test version? =

A beta test version will be produced when the following requirements have been satisfied:

- Support Attachments
- Support Hierarchical taxonomies

In the mean time, these are development versions.
Some of the versions are being Alpha tested on the oik-plugins servers.

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


= Does this use the REST API? =
Not yet. That was the plan. 
But it was a lot more complicated than first thought of. 
So I developed the solution as an extensible solution.
The oik-clone base logic supports
- Self
- Multisite

Extension plugins, yet to be published, may support
- REST
- WXR
 

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

- Hierarchical taxonomies are not handled - 
- Hierarchical content requires parent content to be cloned first - partially solved in v0.5
- Search and replace is not performed against content

= What authentication method is used? =

Simple validation of an API key.
Other methods will be implemented.


== Screenshots ==
1. oik-clone in action

== Upgrade Notice ==
= 0.6 = 
Now supports pushing of attachments and the attached media file. Nearly ready for beta test.

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
* Added: Currently hardcoded for _plugin_ref and "noderef" type fields
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
**"the oik plugin - for often included key-information"**

For other cloning plugins


* [selective-importers](https://wordpress.org/plugins/selective-importers/) - Importers that put the incoming content into a queue, where you can select which posts to import
* 

Other techniques
* https://managewp.com/wordpress-migrating-content-and-media

