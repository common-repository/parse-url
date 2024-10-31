<?php
include("simple_html_dom.php");

/**
Plugin Name: URL Parser
Plugin URI: http://www.youtube.com/watch?v=t_rBlLUmUcA
Description: URL Parser plugin will be responsible for fetching meta information along with 330 characters from the supplied website url.
This plugin is similar to the plugin present in facebook (wherein you update your status with a link, certain meta information is displayed.
We have taken this plugin to a step further by fetching more than the meta information and displaying it on the web page.
This parser takes care of url forwarding. Optionally, it also takes the image parameter where we need to supply a specific uploaded image 
in the post under the tag 'image'. This parser also takes another optional parameter named 'tag' which specifies under which tag the major 
website information is kept. If you think that the content is stored under the 'p' tag but it is the longest paragraph then you need to 
specify 'tag' = 'maxP'. These parameters are stored in the database as a key-value pair. 

Plugin Usage:
[url_parse url='%WEB_PAGE_URL%' tag = '$TAG_CONTAINING_INFO%']
where,
url = (required) : url of the web-page form where you want to fetch the meta content.
tag = (optional) : tag which contains the important content (if 'p' tag doesn't). One way to find this is using browser's right-click function
				   of 'View Selection Soruce'.	 

For example,
If the url = http://timesofindia.indiatimes.com/india/Italy-regrets-killing-of-Indian-fishermen/articleshow/11990083.cms
and if the text is actually under the tag "div class=Normal" then the shortcode can be used as follows:
[url_parse url='http://timesofindia.indiatimes.com/abc/xyz/def/content.cms' tag = 'div class=Normal'] and next time when one uses any 
URL under the domain 'timesofindia.indiatimes.com' then one need not supply the 'tag' as it will be stored in the database.  

@version: 2.0
@author: Adithya Narayan
@author: Ketan Mujumdar
@license: GPL
*/

error_reporting(0);

add_shortcode( 'url_parse', 'return_complete_info' );

global $wpdb;

function return_complete_info($url)
{
		try
		{
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url[url]);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// 			curl_setopt($ch, CURLOPT_PROXY, '10.255.7.217:8080');//TODO
			$xml_contents = curl_exec ($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
			$website_content = $xml_contents;
			
			if(empty($website_content))
			{
				try 
				{
					$website_content = file_get_html($url[url]);
					
					if(!$website_content)
					{
						$modifiedUrl = addForwardSlash($url[url]);
						$website_content = file_get_html($modifiedUrl);
					}
				}
				catch(Exception $ex)
				{
					echo $ex;
					$modifiedUrl = addForwardSlash($url[url]);
					$website_content = file_get_html($modifiedUrl);
				} 
			}
			 
		}
		catch(Exception $e)
		{
			//logger
			return json_encode(array('ERROR' => 1));
		}
		
        if(! is_same_url($url[url]))
        {
        	print_r('<style>
        		.parse_image{
        			float:left;
        			float: left;
    				margin-right: 5px;
    				margin-top: 5px;
        		}
        		.parse_title{
        			margin-bottom:5px;
        			color:#3366F8;
        		}
        		.parse_title a{
        			color:#3366F8;
        		}
       	      </style>');
       		print_r('<div class="parse_main_div"> <span class="parse_image">');

       		//Check if the image has been supplied
       		$imageFound = false;
       		if($url[image] != NULL)
       		{
	       		$media_query = new WP_Query
	       		(
	       			array(
	       				'post_parent' => get_the_ID(),
	       				'post_type' => 'attachment', 
	       				'post_status' => 'inherit',
			       		'name' => $url[image]
	       				)
	       		);
	       		
	       		if($media_query -> post != null) 
	       		{
	       			print_r(wp_get_attachment_image($media_query -> post -> ID));
	       			$imageFound = true;
	       		}
       		}
       		
       		if(!$imageFound)
       		{
	       		// check if the post has a Post Thumbnail assigned to it.
	        	if ( has_post_thumbnail() ) 
	        	{ 
	  				the_post_thumbnail(array(150,150));
				}
       		}
       		
            print_r('</span><span class="parse_title"><a href="'.$url[url].'">'.get_title($url[url]).'</a></span><br/>');
            print_r('<span class="parse_content"><i>'.get_content($website_content,$url).'</i></span><br/>');
			print_r('</div><div style="clear:both;"></div>');
        }
}

function get_title($url)
{
	$title = getTitle($url);
	
	if(empty($title) || !$title)
	{
		$url = addForwardSlash($url);
		$title = getTitle($url);
	}
	
	return $title;
}


function get_content($content, $url)
{
	//Fetch Meta description
	$meta_desc = get_meta_description($url[url]);
	
	if(!$meta_desc)
	{
		$meta_desc = get_custom_meta_tags($content);
	}
	
	//Create DOM from URL or file
	$paragraphsFound = false;
	
	try
	{
		$paragraphs = getParagraphs($url,false);
		if(!$paragraphs || $paragraphs != null)
		{
			$paragraphsFound = true;
		}
		else
		{
			try
			{
				$paragraphs = getParagraphs($url,true);
			}
			catch(Exception $exc)
			{
				echo $exc;
			}
		}
	}
	catch(Exception $ex)
	{
		try
		{
			$paragraphs = getParagraphs($url,true);
		}
		catch(Exception $exc)
		{
			echo $exc;
		}
	}
	
	if($paragraphs != null && $meta_desc != null)
	{
		$contentToReturn = $meta_desc.'</br></br>'.strip_tags($paragraphs).'...';
		return $contentToReturn; 
	}
	elseif($paragraphs != null && $meta_desc == null)
	{
		return strip_tags($paragraphs);
	}
	elseif ($meta_desc != null && $paragraphs == null)
	{
		return $meta_desc;
	} 
}

function getTitle($url)
{
	$html = file_get_html($url);
	
	if($html || !empty($html))
	{
		foreach($html->find('title') as $element)
		{
			$title = $title . $element;
			return strip_tags($title);
		}
	}
}

function check_url_redirect($url)
{
	$redirect_url = null;
	
	if(function_exists("curl_init")){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, true);
// 		curl_setopt($ch, CURLOPT_PROXY, '10.255.7.217:8080');//TODO
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
	}
	else{
		$url_parts = parse_url($url);
		$sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80));
		$request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1\r\n";
		$request .= 'Host: ' . $url_parts['host'] . "\r\n";
		$request .= "Connection: Close\r\n\r\n";
		fwrite($sock, $request);
		$response = fread($sock, 2048);
		fclose($sock);
	}
	
	
	$header = "Location: ";
	$pos = strpos($response, $header);
	if($pos === false){
		return false;
	}
	else{
		$pos += strlen($header);
		$redirect_url = substr($response, $pos, strpos($response, "\r\n", $pos)-$pos);
		return $redirect_url;
	}
}

function getParagraphs($url,$addForwardSlash)
{
	global $wpdb;
	$tag = null;
	$tagFound = false;
	
	//Check for url forwarding
	while(($newurl = check_url_redirect($url[url])) !== false)
	{
		$url[url] = $newurl;
	}
	
	$modifiedURL = $url[url];
	
	if($addForwardSlash)
	{
		$modifiedURL = addForwardSlash($url[url]);
	}
	
	$html = file_get_html($modifiedURL);
	
	/*
	 * Check from which site's URL we want to retrieve information and accordingly
	 * fetch that particular tag from the table. We will look into the database only 
	 * if a match isn't found and if the url and the tag values shave been supplied.
	 * 
	 * We intend to save only the starting DNS of the URL into the database.
	 */
	
	$domainURL = strtok($modifiedURL,"/");
	
	//Ignore the first token as it will be http/https or some other protocol being used
	$domainURL = strtok("/");
	
	if($domainURL !== null)
	{
		$results = $wpdb->get_results("select tag from " .$wpdb->prefix."url_parse where url like '". $domainURL."'");
		
		if($results !== null)
		{
			$tag = $results[0]->tag;
			
			if($tag !== null)
			{
				$tagFound = true;
			}
		}
		
		if(!$tagFound && $url[tag] !== null)
		{
			$tag = $url[tag];
			$tagFound = true;
				
			$results = $wpdb->insert(
			$wpdb->prefix.'url_parse',
						array(
									'url' => $domainURL,
									'tag' => $url[tag]
						)
					);
		}
	}
	
	if($html || !empty($html))
	{
		/*
		 * Find all paragraphs but extract only the first 2. If tag is supplied 
		 * use that to fetch the content else use 'p' tag to fetch content. 
		 */
		
		if($tag !== null)
		{
			if($tag !== 'maxP')
			{
				/*
				 * Split tag with space. We expect the tag to be separated
				 * with space.
				 */
				
				$tagArray = null;
				$token = strtok($tag, " ");
				
				for($tokenCount = 0; $token !== false ; $tokenCount ++)
				{
					$tagArray[$tokenCount] = $token;
					$token = strtok(" ");
				}
				
				//TODO : Need to think of a generic solution for various examples.
				if($tokenCount > 2)
				{
					for($arrayCount = 2 ; $arrayCount < count($tagArray) ; $arrayCount ++)
					{
						$tagArray[1] = $tagArray[1]." ".$tagArray[$arrayCount];
					}
				}
				$actualTag = $tagArray[0];
				$attribute = $tagArray[1];
				
				foreach($html->find($actualTag.'['.$attribute.']') as $element)
				{
					if(($count < 2) || (strlen(strip_tags($paragraphs)) < 330))
					{
						$paragraphs = $paragraphs . $element;
						$count++;
					}
				}
			}
			else
			{
				//Fetch the maximum paragraph from the content
				$maxLength = 0;
				foreach($html->find('p') as $element)
				{
					if(strlen(strip_tags($element)) > $maxLength)
					{
						$paragraphs = "".strip_tags($element);
						$maxLength = strlen(strip_tags($element));
					}
				}
			}
		}
		else
		{
			foreach($html->find('p') as $element)
			{
				if(($count < 2) || (strlen(strip_tags($paragraphs)) < 330))
				{
					$paragraphs = $paragraphs . $element;
					$count++;
				}
			}
		}
	}
	
	if((strlen(strip_tags($paragraphs)) > 330))
	{
		$paragraphs = substr(strip_tags($paragraphs), 0,330);
	}
	return $paragraphs;
}

function get_custom_meta_tags($content)
{
	$has_desc = preg_match_all('/<meta name=["\']?description["\']? content=["\']?(.*)[^>]["\']?>/',$content,$matches);
	if($has_desc)
	{
		return $matches[1][0];
	}
	else
	{
		return false;
	} 	
}


function get_meta_description($url)
{
		$meta_info = false;
		
		try
		{
			$meta_info = get_meta_tags($url);
			
			if(!$meta_info)
			{
				$url = addForwardSlash($url);
				$meta_info = get_meta_tags($url);
			}			
		}
		catch(Exception $ex)
		{
			echo $ex;
			
			try
			{
				if(!$meta_info)
				{
					$url = addForwardSlash($url);
					$meta_info = get_meta_tags($url);
				}
			}
			catch(Exception $exc)
			{
				echo $exc;
			}
		}
		
        if(!empty($meta_info['description']))
        {
    	    return $meta_info['description'];
        }
        else
        {
 	    	return false;
        }
}

function addForwardSlash($url)
{
	$reversedURL = strrev($url);
	if(strpos($reversedURL, "/") !== 0)
	{
		$url = $url.'/';
	}
	
	return $url;
} 

function get_current_url()
{
        return $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
}

function is_same_url($url)
{
        preg_match('/^(http|https|ftp):\/\/(.*)/',$url,$matches);

        if($matches[2]===get_current_url())
        {
                return true;
        }

        return false;
}

/*
* This function will create a table to store the url,tag pair in database table
*/
function create_table()
{
	global $wpdb;
	global $create_table_version;

	$table_name = $wpdb->prefix . "url_parse";

	$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . "(
	  id mediumint(10) NOT NULL AUTO_INCREMENT,
	  url VARCHAR(60) NOT NULL,
	  tag VARCHAR(60) NOT NULL,
	  UNIQUE KEY id (url,tag),
	  PRIMARY KEY id (id)
	)DEFAULT CHARACTER SET = utf8,
	  COLLATE = utf8_general_ci;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option("create_table_version", $create_table_version);
}

/*
 * Add known initial data to the table
*/
function add_initial_data()
{
	global $wpdb;

	$wpdb->insert(
	$wpdb->prefix.'url_parse',
	array(
							'url' => "timesofindia.indiatimes.com",
							'tag' => "div class=Normal"
	)
	);

	$wpdb->insert(
	$wpdb->prefix.'url_parse',
	array(
							'url' => "economictimes.indiatimes.com",
							'tag' => "div class=Normal"
	)
	);

	$wpdb->insert(
	$wpdb->prefix.'url_parse',
	array(
							'url' => "www.indiatimes.com",
							'tag' => "div class=lftcolum2 plusFont"
	)
	);
}

register_activation_hook(__FILE__,'create_table');
register_activation_hook(__FILE__,'add_initial_data');

?>