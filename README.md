# oik-clone 
* Contributors: bobbingwide
* Donate link: http://www.oik-plugins.com/oik/oik-donate/
* Tags: clone, compare, update, MultiSite
* Requires at least: 4.1
* Tested up to: 4.2-beta2
* Stable tag: 0.2
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html
* Text Domain: oik-clone
* Domain Path: /languages/

## Description 
Clone content between sites


oik-clone provides tools to merge and synchronise content between WordPress sites.

Version 0.1 was developed to merge content between Multisites, using a pull mechanism.
It also supports post cloning in a single WordPress installation, though I can't really see a true need for it.

Version 0.2 provides a push mechanism, allowing content to be optionally cloned during post updates.

oik-clone is dependent on the oik base plugin.
Both plugins need to be installed and activated on each site.

This is a prototype solution to address a specific problem - performance comparison and improvement in multiple implementations.
SEO is not a consideration.


## Installation 
1. Upload the contents of the oik-clone plugin to the `/wp-content/plugins/oik-clone' directory
1. Activate the oik-clone plugin through the 'Plugins' menu in WordPress
1. Use oik options > Clone to access the admin interface

Install on both the client and server machines.


## Frequently Asked Questions 


# What does the Multi-Site tab do?

The initial version of this plugin is designed for use on WordPress MultiSite
where you have cloned an existing site into a new version, developed the new version
and now want to copy the new contents back into the existing site.

# What does the Self tab do? 

The Self tab allows for introspection.
It provides the same actions as the Multisite tab, but the source and target site is the same.

- You can use it to look at all your content in one big listing
- You may find duplicated content
- You can compare similar content
- You can duplicate content by performing Import


# How does the "Clone on update" work? 





# Is this suitable for pushing from Staging to Production? 

It depends.


# Does this use the REST API? 
Not yet. That was the plan.
But it was a lot more complicated than first thought of.
So I developed the solution as an extensible solution.
The oik-clone base logic supports
- Self
- Multisite

Extension plugins, yet to be published, will support
- REST
- WXR


# Why is this dependent upon oik? 
Some plugins use Composer to manage their dependencies, pulling in library functions from
third party plugins hosted on GitHub and elsewhere.

The oik suite of plugins use common APIs from the oik base plugin.
There should be little overhead.
Even though oik implements over 80 shortcodes none of them are registered until shortcodes are actually needed.
oik attempts to OWN the problem; Only When Necessary.

One day perhaps the lowest level of oik base functions will be exported to a library
but in the mean time you have to download and activate oik to make this work.

# Are there limitations? 

Yes. Work is needed in the following areas.

- Taxonomies are not handled
- Hierarchical content requires parent content to be cloned first
- Search and replace is not performed against content
- Search and replace is not performed against post_meta data which contains post IDs

# What authentication method is used? 
Simple validation of an API key.





## Screenshots 
1. oik-clone in action

## Upgrade Notice 
# 0.2 
Prototype for cloning content on Update

# 0.1 
Prototype for WordPress Multi Site cloned sites

## Changelog 
# 0.2 
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


# 0.1 
* Added: New plugin.
* Added: Multisite capability, developed for Rathmore Financial and wp-a2z.com / subdomain.wp-a2z.org
* Added: Self capability (default) developed for testing in a non Multisite environment
* Added: Classes inherit from BW_List_Table, forked from WP_List_Table
* Depends: on oik v2.4 or higher



## Further reading 
If you want to read more about the oik plugins then please visit the
[oik plugin](http://www.oik-plugins.com/oik)
**"the oik plugin - for often included key-information"**

For other cloning plugins


* [selective-importers](https://wordpress.org/plugins/selective-importers/) - Importers that put the incoming content into a queue, where you can select which posts to import
*

Other techniques
* https://managewp.com/wordpress-migrating-content-and-media

