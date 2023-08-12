=== oik-clone ===
Contributors: bobbingwide
Donate link: https://www.oik-plugins.com/oik/oik-donate/
Tags: clone, compare, update, MultiSite
Requires at least: 5.5
Tested up to: 6.3
Stable tag: 2.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: oik-clone
Domain Path: /languages/

== Description ==
Clone content between sites

oik-clone provides tools to merge and synchronize content between WordPress sites.

Features:

- push content on Update to multiple targets
- pushes post content, post meta data and taxonomy terms
- pushes the attached file, for attachments
- maintains relationships: e.g. post_parent and fields referencing other content
- maintains informal relationships: e.g. in post_content
- reconciliation of content with a selected slave server.
- cloning of new content to a selected slave server
- pull content from other sites in a MultiSite installation
- compare and update or import from self or a MultiSite site
- clone virtual field - to display clones of current content

New for version 2.2.0 

- Capability to clone comments attached to a post. Needed for the documentation of Gutenberg comment related blocks.

New for version 2.0.0

- Admin UI for reconciliation between the local installation and a selected slave site.
- Also supports cloning of new content to the slave.
- Automatically reclones content if the featured image was not previously cloned.

This was originally developed as:

- Batch reconciliation: push and pull between the local installation and a selected slave site.
- Per post setting to support selective cloning to slave servers


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
Yes, there's a new beta test version for 2.0.0

The June version improves the cloning process. It reduces the amount of user activity required to clone new posts with newly added ( uncloned ) featured images.

The January 2020 beta version supports reconciliation of posts that have been updated in the slave
and a 'Do Not Clone' capability.

= What does the Slave tab do?
Use this admin page to reconcile content with a slave server or to push new content to the slave server.

= What does the Multi-Site tab do? =

The initial version of this plugin was designed for use on WordPress MultiSite
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

- If you have a complex content structure or a lot of new/changed content then the answer is to consider using the batch routines.
- If you have a simple content structure - just posts and pages - then you may find this useful - to both push and pull.

To support pushing from Staging to Production requires additional work to identify the network of posts to be cloned.
This can be achieved using a the [clone] shortcode in a widget.

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

- Hierarchical content requires parent content to be cloned first.
- Sometimes you need to clone the tree, update something and clone again.
- Limitation on media file size imposed by servers

= What authentication method is used? =

Simple validation of an API key.
Other methods will be implemented in future versions.


== Screenshots ==
1. Clone on update meta box - select targets
2. Clone on update meta box - with cloned post links
3. Clone on update meta box - Previously cloned 
4. Do Not Clone meta box 
5. Clone admin - Slave tab - Slave Post Selection
6. Clone admin - Slave tab - Slave Post list
7. Clone admin - Slave tab - Import new post from Slave
8. Clone admin - Slave tab - Master posts to clone

== Upgrade Notice ==
= 2.3.1 = 
Tested with WordPress 6.3

== Changelog ==
= 2.3.1 =
* Fixed: Avoid Fatal when Settings Advanced tab list is empty #74
* Tested: With WordPress 6.3 and WordPress MultiSite
* Tested: With PHP 8.0
* Tested with PHPUnit 9

== Further reading ==
If you want to read more about the oik plugins then please visit the
[oik plugin](https://www.oik-plugins.com/oik) 
**"the oik plugin - oik information kit"**