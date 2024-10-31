=== Parse URL ===

Contributors: Adithya Narayan, Ketan Mujumdar
Tags: url,parse,facebook url parse plugin
Requires at least: 2.0
Tested up to: 2.0
Stable tag: 2.0
Version: 2.0
License: GPL

== Description ==

URL Parse plugin will be responsible for fetching meta information along with 330 characters from the supplied website url.
Optionally, it also takes the image parameter where we need to supply a specific uploaded image in the post. This parser takes care
of url forwarding. Optionally this parser takes 2 parameters named url_name and tag_name. These parameters are stored in the database
as key-value pair. url_name is the primary DNS and tag_name is the tag within which the actual text is stored.

For example,
If the url = http://timesofindia.indiatimes.com/india/Italy-regrets-killing-of-Indian-fishermen/articleshow/11990083.cms
and if the text is actually under the tag "div class=Normal" then the shortcode can be used as follows:
[url_parse url='http://timesofindia.indiatimes.com/india/Italy-regrets-killing-of-Indian-fishermen/articleshow/11990083.cms' 
url_name = 'timesofindia.indiatimes.com' tag_name = 'div class=Normal'] and next time when one uses any URL of timesofindia.indiatimes.com
then you need not supply the url_name and tag_name as it will be stored in the database.  

Plugin Usage:
[url_parse url='%WEB_PAGE_URL%' tag = '$TAG_CONTAINING_INFO%']
where,
WEB_PAGE_URL = url link of the web page; currently supports http/https protocol supoorting web pages; required
TAG_CONTAINING_INFO = the tag under which the information is contained; Generally certain browsers are helpful in finding this information. 
					 For e.g. in mozilla firefox when you select a certain text/element in a web-page and right-click ,you get an option
					 'View Selection Source'. This can be used to examine under which tag the content is placed. Alternatively you can skim
					 through the page source to find out;  optional

There is database support provided for storing the tags for a particular URL. For example, if a web page contains certain information under
a particular 'tag' and you specify it, the logic is intelligent enough to store it which will help one get the data required from the same page
without specifying the 'tag' again.   

== Installation ==

1. Upload 'url-parse-plugin.php', 'url_parse_table.php' and 'simple_html_dom.php' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Query : When does the table get created ?
Ans : url_parse_table.php is responsible for creating the table when you upload the php files. A hook has been registered and some initial data is also added.

Query : How do i add data in the table for my web page/ url ?
Ans : For example,If the url = http://timesofindia.indiatimes.com/india/Italy-regrets-killing-of-Indian-fishermen/articleshow/11990083.cms
and if the text is actually under the tag "div class=Normal" then the shortcode can be used as follows:
[url_parse url='http://timesofindia.indiatimes.com/abc/xyz/def/content.cms' tag = 'div class=Normal'] and next time when one uses any 
URL under the domain 'timesofindia.indiatimes.com' then one need not supply the 'tag' as it will be stored in the database.

== Changelog ==
== Upgrade Notice ==
== Screenshots ==