<?php
/**
 * Plugin Name: http-syndication
 * Plugin URI: https://wordpress.org/plugins/http-syndication/
 * Description: Allows to offer the content of page to other sites and import content from this or other sites.
 * Version: 1.8
 * Author: Thierry.Vieville@inria.fr
 * Author URI: http://www-sop.inria.fr/members/Thierry.Vieville
 * License: GPLv2 or later
 */
class http_syndication
{   
  //
  // Plugin construction
  //

  public function __construct()
  { 
    // Uses the monkeysuffrage/phpuri open php library for converting relative urls to absolute
    include_once plugin_dir_path( __FILE__ ).'/phpuri.php';
    // Offering content to other sites
    add_filter('request', array($this, 'http_syndication_request'), 1, 1);
    // Including content from this or other sites
    add_shortcode('import', array($this, 'import_shortcode'));
    add_filter('http_syndication/url/mediawiki', array($this, 'http_syndication_url_mediawiki'));
    add_filter('http_syndication/url/httpsyndication', array($this, 'http_syndication_url_httpsyndication'));
    add_filter('http_syndication/content/body', array($this, 'http_syndication_content_body'));
    add_filter('http_syndication/content/noscript', array($this, 'http_syndication_content_noscript'));
    add_filter('http_syndication/content/tag_name', array($this, 'http_syndication_content_tag_name'), 10, 2);
    add_filter('http_syndication/content/tag_id', array($this, 'http_syndication_content_tag_id'), 10, 2);
    add_filter('http_syndication/content/tag_class', array($this, 'http_syndication_content_tag_class'), 10, 2);
  }
  function http_syndication_request($request) {  
    // Implements the redirection form a ?w= request to the correct redirection ?p=, ?cat= or ?page_id request
    if (isset($request['w'])) {
      $id = $request['w'];
      unset($request['w']);
     if (get_category($id)) {
	$request['cat'] = $id;
      } else if (get_post($id)) {

	$request[get_post_type($id) == 'page' ? "page_id" : "p"] = $id;
      }
    }
    // Implements the ?httpsyndication and ?httpsyndicationwrap requests
    if (isset($_REQUEST['httpsyndication']) && (isset($request['p']) || isset($request['page_id']))) {
      // Captures the http request if corresponding to a http_syndication and output the related content
      $id = isset($request['p']) ? $request['p'] : $request['page_id'];
      $post = get_post($id);
      if ($post) {
	$content = str_replace(']]>', ']]&gt;', apply_filters('the_content', $post->post_content));
        if ((!isset($_REQUEST['title'])) || $_REQUEST['title'] != 0)
	  echo "<h1>".$post->post_title."</h1>\n";
        echo $content;
      }
      exit(0);
    } else if (isset($_REQUEST['httpsyndicationwrap'])) {
      include_once plugin_dir_path( __FILE__ ).'/wrap.php';
      exit(0);
    } else
      return $request;
  }
  // Implements the internal or external import shortcode
  function import_shortcode($atts, $content) {
    if (isset($atts['banner'])) {
      if (isset($atts['url'])) {
	// Implements external page display with banner
	$banner = $atts['banner'];
	$url = urlencode($atts['url']);
	$height = isset($atts['height']) ? $atts['height'] : 1000;
	$style = urlencode(isset($atts['style']) ? $atts['style'] : "border: none;");
	$newtab = (isset($atts['newtab']) && $atts['newtab'] == 1) ? " target='_blank'" : "";
	return "<a href='".site_url()."?httpsyndicationwrap&banner=$banner&url=$url&height=$height&style=$style'$newtab>$content</a>";
      } else
	return "[import error='a `banner` is specified but without any external `url`, thus nothing to import']";
    } else if (isset($atts['wform'])) {
      return "<form id=\"httpsyndication-wform\" action=\"\">".(isset($atts['label']) ? $atts['label'] : "")."<input type=\"text\" name=\"w\" size=\"6\"/></form>";
    } else if (isset($atts['id'])) {
      $post = get_post($atts['id']);
      // Implements internal import
      if($post != null) {
	if (isset($atts['title']) && $atts['title'] == 1) {
	  return $post->post_title;
	} else {
	  // Gets the filtered contents, using $this->import_ids to detect infinite loops
	  if(!isset($this->import_ids[$atts['id']])) {
	    $this->import_ids[$atts['id']] = true;
	    // Gets the filtered contents
	    $content = $post->post_content;
	    if (isset($atts['content']) && $atts['content'] == 1) {
	      $content = do_shortcode($content);
	    } else {
	      $content = apply_filters('the_content', $content);
	    }
	    $content = str_replace(']]>', ']]&gt;', $content);
	    unset($this->import_ids[$atts['id']]);
	    $content = $this->http_syndication_encapsulate($content, $atts);
	    return self::html_repair($content);
	  } else
	    return "[import error='infinite loop detected (this page imports a page directly or indirectly importing itself)']";
	}
      } else
	return "[import error='The post or page of id = '".$atts['id']."' is undefined']";
    } else if (isset($atts['url'])) { 
      $atts['url'] = preg_replace("/(&amp;|&#038;)/", "&", $atts['url']);
      // Implements external import
      {
	$boolean_filter = array("body", "noscript", "mediawiki", "httpsyndication");
	if (ini_get('allow_url_fopen')) {
	  // Apply filters on url
	  foreach ($atts as $name => $value) {
	    $filter = 'http_syndication/url/'.$name;
	    if (has_filter($filter) && ($value == 1 || !isset($boolean_filter[$name])))
	      $atts['url'] = apply_filters($filter, $atts['url'], $atts);
	  }
	  // Loads the remote content
	  $content = file_get_contents($atts['url']);
	  if ($content !== FALSE) {
	    // Apply filters on content
	    {
	      if (!((isset($atts['raw_ref']) && $atts['raw_ref'] == 1) || (isset($atts['raw_href']) && $atts['raw_href'] == 1) || (isset($atts['mediawiki']) && $atts['mediawiki'] == 1)))
		$content = $this->http_syndication_content_absolute_href($content, $atts['url']);
	      foreach ($atts as $name => $value) {
		$filter = 'http_syndication/content/'.$name;
		if (has_filter($filter) && ($value == 1 || !isset($boolean_filter[$name])))
		  $content = apply_filters($filter, $content, $atts);
	      }
	      $content = $this->http_syndication_encapsulate($content, $atts);
	      return self::html_repair($content);
	    }	      
	  } else 
	    return "[import error='the `".$atts['url']."` URL can not be read (the address is incorrect or the site does not respond)']";
	} else
	  return "[import error='the `allow_url_fopen` option is not validated in your PHP configuration, thus external URL access is not allowed']";
      }
    } else
      return "[import error='neither the external `url` location nor the internal `id` parameter is defined, thus nothing to import']";
  }
  private $import_ids = array();

  //
  // Implemented hooks
  //

  // Adds the mediawiki url query
  function http_syndication_url_mediawiki($url) {
    return $url . (parse_url($url, PHP_URL_QUERY) === NULL ? "?" : "&") . "printable=yes&action=render";
  }

  // Adds the syndication url query
  function http_syndication_url_httpsyndication($url) {
    return $url . (parse_url($url, PHP_URL_QUERY) === NULL ? "?" : "&") . "httpsyndication";
  }
  // Returns the body content
  function http_syndication_content_body($content) {
    $pos = http_syndication::html_tag_pos($content, "body");
    return $pos === FALSE ? $content : substr($content, $pos['start'], $pos['stop'] - $pos['start']);
  }
  // Returns the content without any script
  function http_syndication_content_noscript($content) {
    while (true) {
      $pos = http_syndication::html_tag_pos($content, "script");
      if ($pos === FALSE) {
	return $content;
      } else {
	$content = substr($content, 0, $pos['begin']) . substr($content, $pos['end']);
      }
    }
  }
  // Returns the contents associated to a tag
  function http_syndication_content_tag_name($content, $atts) {
    $result = "";
    $offset = 0;
    for($nn = 0; $nn < 100; $nn++) {
      $pos = http_syndication::html_tag_pos($content, $atts['tag_name'], $offset);
      if ($pos === FALSE) {
	return $result;
      } else {
	$result .= substr($content, $pos['begin'], $pos['end'] - $pos['begin'])."\n";
	$offset = $pos['end'];
      }
    }
  }
  // Returns the content associated to a tag id attribute
  function http_syndication_content_tag_id($content, $atts) {
    return $this->http_syndication_content_att($content, "id", $atts['tag_id'], $atts);
  }
  // Returns the content associated to a tag class attribute
  function http_syndication_content_tag_class($content, $atts) {
    return $this->http_syndication_content_att($content, "class", $atts['tag_class'], $atts);
  }
  // Parses the content and select the 1st one associated to a given attriburte
  function http_syndication_content_att($content, $att, $id, $atts) {
    $offset = 0;
    if(preg_match('|<\s*([^\s>]*)[^>]*\s'.$att.'\s*=\s*(["\'])'.$id.'(["\'])[^>]*>|', $content, $matches, PREG_OFFSET_CAPTURE, $offset) != 1)
      // if(preg_match('|<\s*([^\s>]*)[^>]*\s'.$att.'\s*=\s*(["\']?)'.$id.'(["\']?)[^>]*>|', $content, $matches, PREG_OFFSET_CAPTURE, $offset) != 1) // This includes tags without quotes, but this not very safe
      return "";
    $begin = $matches[0][1];
    $tag = $matches[1][0];
    $quote1 = $matches[2][0];
    $quote2 = $matches[3][0];
    if($quote1 != $quote2)
      return "";
    $inside = 1;
    $end = $begin + strlen($matches[0][0]);
    //echo "<hr>".preg_replace("/</", "&lt;", print_r($matches, true))."<hr>";
    while ($inside > 0 && preg_match('/<\s*([\/]*)\s*'.$tag.'(>|\s[^>]*>)/', $content, $matches, PREG_OFFSET_CAPTURE, $end) == 1) {
      $inside += $matches[1][0] == "/" ? -1 : +1;
      $end = $matches[0][1] + strlen($matches[0][0]);
     }
    $content = substr($content, $begin, $end - $begin);
    return $content;
  }
  // Encapsulates the content in a div with a given class or style
  function http_syndication_encapsulate($content, $atts) {
    if (isset($atts['class']) || isset($atts['style']))
      $content = "<div".
	(isset($atts['class']) ? " class='".$atts['class']."'" : "").
	(isset($atts['style']) ? " style='".$atts['style']."'" : "").
	">".$content."</div>";
    return $content;
  }
  // Replaces href by their absolute values
  function http_syndication_content_absolute_href($content, $base) {
    if (preg_match('/<\s*base\s*href=["\']([^"\']*)["\']/', $content, $matches, PREG_OFFSET_CAPTURE, 0) == 1)
      $base = $matches[1][0];
    // Loops on all tags
    for($offset = 0, $length = strlen($content); $offset < $length;) {
      $offset = strpos($content, '<', $offset);
      if ($offset === FALSE)
	break;
      // Loops on all url attributes within a tag
      while(true) {   
	if(preg_match('/(src|href)\s*=\s*(["\'])/', $content, $matches, PREG_OFFSET_CAPTURE, $offset) != 1)
	  break;
	$begin = $matches[0][1];
	$quote = $matches[2][0];
	$start = $matches[2][1] + 1;
	if(preg_match('/['.$quote.'>]/', $content, $matches, PREG_OFFSET_CAPTURE, $start) != 1)
	  break;
	$stop = $matches[0][1];
	$rel = substr($content, $start, $stop - $start);
	// Converts, if not a simple fragment, the relative URL to an absolute URL
	if ($rel[0] != '#') {
	  $abs = http_syndication_phpUri::parse($base)->join($rel);
	  $offset = $stop + strlen($abs) - strlen($rel);
	  $content = substr($content, 0, $start) . $abs . substr($content, $stop);
	} else
	  $offset = $stop;
      }
      $offset = strpos($content, '>', $offset);
      if ($offset === FALSE)
	break;
    }
    return $content;
  }

  //
  // Utility routines
  //

  // Returns the start/stop indexes of a non-recursive begin/end tag couple
  static function html_tag_pos($string, $tag, $offset = 0) {
    if(preg_match('|<\s*'.$tag.'|', $string, $matches, PREG_OFFSET_CAPTURE, $offset) != 1)
      return FALSE;
    $begin = $matches[0][1];
    $start = strpos($string, '>', $begin);
    if ($start === FALSE)
      return FALSE;
    if(preg_match('|</\s*'.$tag.'|', $string, $matches, PREG_OFFSET_CAPTURE, $offset) != 1)
      return FALSE;
    $stop = $matches[0][1];
    $end = strpos($string, '>', $stop);
    if ($end === FALSE)
      return FALSE;
    // Returns the indexes enclosing the tag and its content: #begin <tag ..> #start content #stop </tag> #end
    return array('begin' => $begin, 'start' => $start + 1, 'stop' => $stop, 'end' => $end + 1);
  }

  // Ensures hat all tags are closed, no more used, juste here for info.
  static function html_repair($html_fragment) {
/*
    $tidy = tidy_parse_string($html_fragment, array(
						    'clean' => true,
						    'output-xhtml' => true,
						    'show-body-only' => true,
						    'wrap' => 0,
						    ), 'UTF8'); 
    $tidy->cleanRepair();
*/
    return $html_fragment; // tidy_get_output($tidy); 
  }
}

$http_syndication = new http_syndication();
?>
