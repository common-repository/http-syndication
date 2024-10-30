=== Plugin Name ===
Contributors: vthierry
Tags: syndication, export, http request, rendering, feed
Requires at least: 3.0.1
Tested up to: 6.4
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

This plugins offers the following "ground" functionnalities:

* 1. Display the content of a post or page without any header, sidebar and footer.
* 2. Importing the content from an external web site
* 3. Importing an internal content of the wordpress site
* 4. A few other deprecated functionalities

= 1. Displaying a post or page without any header, sidebar and footer = 

In order to render the raw content of a post or page without any header, sidebar and footer use the link:

`http://#mywordpressadress?httpsyndication&p=#post_or_page_id&title=#1_or_0`

where:

* `#mywordpressadress` is the wordpress web site adress.
* `#post_or_page_id` is the post or page id (you can read the post id in the http link when you edit the post).
* `#1_or_0` defines whether the post title  (enclosed in a `<h1>` HTML tag) is displayed (default is `1`, i.e., true).

*Remarks:*

* In this text all words prefixed by `#`, e.g., `#tag` or `#id` correspond to a variable, e.g., `#tag` means any tag name or `#id` means an ID label.
* The post content is exported after filtering.

= 2. Importing the content from an external web site =

In order to import an external a post or page contents in another post or page, use the following shortcode:

`[import url="#external_resource_location" ]`


where:

* `#external_resource_location` is the external web site http location.

Inside the  `[import ..]` shortcode, the following options allows to select and adjust the content to import:

* Content selection:
  * `body=1` : in order to only import the `<body..>..</body>` part of the `<html><head>..</head><body..>..</body></html>` page.
  * `tag_id=#id` : only extract the content of the 1st tag of the given id, i.e. the `<#tag id="#id">..</#tag>` part, where `#tag` is any tag (e.g., `div`, `span`, ..). 
  * `tag_class=#class` : only extract the content of the 1st tag of the given class, i.e. the `<#tag class="#class">..</#tag>` part, where `#tag` is any tag (e.g., `div`, `span`, ..); the value `#class` can be a regex to deal with multiple defined classes. 
  * `tag_name=#name` : only extract the content of all tags of the given name, i.e. the `<#name ..>..</#name>` part (for code safety only 100 tags can be extracted). 
* Content filtering:
  * `noscript=1` : in order to delete all `<script..>..</script>` parts, avoiding any active JavaScript running from the imported HTML.
  * `raw_href=1` : in order to provide raw href values, instead of properly relocating relative href in the source HTML code, as done by default.
  * `mediawiki=1` : adds the `?printable=yes&action=render` query to the URL, in order to properly syndicate mediawiki sites.
  * `style=#style` or `class=#class` allows to encapsulate the content in a `<div .` with customized class and/or style attribute.

*Example* :

* `[import url="http://localhost/wordpress/?cat=4687" tag_id="content" style="width:100%"]` allows to import a category rendering with another page.

*How can i know with _id_, _tag_ or _class_ to select?*

* Display the page you want to import and consider the part of the page you want to select
* Use the right-button to select _Inspect_ or _Inspect Element_ in the context menu, that will show the page source at the choosen position
* Look around to find the _<div_ or any other container with the proper _id_, _tag_ or _class_ (providing it is the first tag or class of this kind in the text) 
* Then report the attribute value in your import short-code

*Remarks:* 

  * If the external content can not be read, an error message is produced in the page output.
  * The external content target page must be written in correct HTML (e.g., attributes value must be between quotes), otherwise the parsing may fail.
  * This functionality requires the `allow_url_fopen` option to be validated in the PHP configuration, otherwise an error message is produced in the page output.
  * By construction the import is "dynamic", i.e., each time the page is loaded, the external content is queried and inserted in the page. Good news, to be sure to have an updated information. Bad news, if the external web site is down or slowed down. You may consider having a [cache mechanism](http://wordpress.org/plugins/wp-super-cache) to overcome related caveats.
  * The external HTML content is imported with its external CSS class styles. For another WordPress site, the match is expected to be good. If this is an exotic content management system, the situation is less obvious: Local CSS patch is likely required, or some additional content filter has to be hooked.
  * There is no way to prevent from infinite syndication loops between sites (e.g., a site with a page importing an external page that itself import the inquiring page).
  * Unless using `noscript=1`, the external JavaScript code is imported. Though security flaws are normally not to be expected considering usual JavaScript security rules, clearly malicious code can easily hugely perturb the page rendering.
  * The [embed](http://codex.wordpress.org/Embeds) shortcode is a core WordPress feature. It can embed content from many resources via direct link, and the [iframe](http://www.w3schools.com/tags/tag_iframe.asp) allows to safely and obviously include any external site page in "frame". The [iframe plugin](http://wordpress.org/plugins/iframe) works well and properly make the job. The present import is a 3rd alternative, to be preferred when the former fail or mixing pages content is required.

= 3. Importing an internal content of the wordpress site =

In order to import a post or page contents in another post or page, use the following shortcode:

* `[import id="#post_or_page_id"]` to include the filtered content of another post or page in the current content.
* `[import id="#post_or_page_id" content=1]` to include the raw (with shortcode expanded, but not content filter applied) of another post or page in the current content (to be used only if required).
* `[import id="#post_or_page_id" title=1]` to include the title of another post or page in the current content.

where:

* `#post_or_page_id` is the post or page id (you can read the post id in the http link when you edit the post).

*Remarks:*

* This avoids spurious import, e.g. of social media buttons (contrary to usual [include plugins](http://wordpress.org/plugins/search.php?q=include)).
* Shortcode recursion is managed (i.e. stopped when an infinite loop is detected).

= 4. A few other deprecated functionalities =

These additonal functionalities are deprecated but still available for backward compatibility

* Using the `?w=#id` query allows to refer any page, post or archive by its numerical index.

* Using the `[import wform=1 label="#theinputlabel"]` shortcode, allows to insert a small input field to switch to any page, post or archive given its numerical index.

* Using the `[import banner="#page_id" url="#external_url"]#link-text[/import]` shortcode allows to display another site page in an iframe, with a banner from this site sitting on top of the external page. Inside the  `[import ..]` shortcode, the following options allows to adjust the behavior.
  * `header=false` : in order to include or not the usual site header().php on the top of the banner
  * `height=1000` : in order to adjust the height of the frame for very long external pages.
  * `newtab=1` : in order to open the page and banner in a new browser window.
  * `style="border:none;"` : in order to adjust the height of the frame for very long external pages.

== Installation ==

*Manual installation*

1. Download the [ZIP](http://sparticipatives.gforge.inria.fr/wp-plugins/index/http_syndication.zip) file.
2. In your WordPress `Dashboard -> Plugins -> Add new` page choose `upload plugin in .zip format via this page`
3. Browse and select the http_syndication.zip to upload
4. Activate the plugin through the 'Plugins' menu in WordPress

*Automatic installation*

1. In your WordPress `Dashboard -> Plugins -> Add new` page search for *http syndication*
2. Install the plugin and activate it through the 'Plugins' menu in WordPress

== Changelog ==

= 1.8 =

Improve the documentation.

= 1.7 =

Improve the introduction of style or class in import.

= 1.6 =

Add the title option of the offering content to other sites
Reorganize the doc page, and update code accordingly
Declare deprecated some features, still available.

= 1.5 =

Now wrap with iframe and embed
Better support of remote pages parsing

= 1.4 =

Add the refering content by tiny URL functionnality

= 1.3 =

Adapt the shortcode parsing to editor that escape '&'

= 1.2 =

Add the `[import banner=..]` shortcode functionality.

= 1.1 =

Adding the `[import ..]` shortcode functionality.

Uses the [monkeysuffrage/phpuri](https://github.com/monkeysuffrage/phpuri) open PHP library for converting relative URLs to absolute, thanks!

= 1.0 =

Nothing special with this trivial plugin, debugged with WP_DEBUG.
