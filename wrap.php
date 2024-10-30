<?php 

// Gets the used parameters
$banner = isset($_REQUEST['banner']) ? $_REQUEST['banner'] : false;
$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : false;
$header = isset($_REQUEST['header']) ? $_REQUEST['header'] : false;
$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : false;
$style = isset($_REQUEST['style']) ? $_REQUEST['style'] : '';
$iframe = isset($_REQUEST['iframe']) ? $_REQUEST['iframe'] : false;
$title = $banner ? get_post($banner)->post_title : 
  preg_match('/<title[^>]*>(.*?)<\/title>/ims', file_get_contents($url), $matches) ? $matches[1] : get_post()->post_title;

// Displays either the site header or a minimal header
if ($header) {
  get_header();
  echo "<script>window.document.title = '".preg_replace("/'/", "´", $title)."';</script>";
} else {
 echo '    
<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" '; language_attributes(); echo '>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" '; language_attributes(); echo '>
<![endif]-->
<!--[if !(IE 7) | !(IE 8) ]><!-->
<html'; language_attributes(); echo'>
<!--<![endif]-->
<html>
  <head>
    <meta charset="'; bloginfo( 'charset' ); echo '">
    <title>'.$title.'</title>
    <!--[if lt IE 9]>
    <script src="'.get_template_directory_uri().'/js/html5.js"></script>
    <![endif]-->';
    wp_head(); echo '
  </head>
  <body>';
 }

// Displays the banner
if (get_post($banner)) {
  $content = get_post($banner)->post_content;
  $content = do_shortcode($content);
  $content = str_replace(']]>', ']]&gt;', $content);
  echo $content."\n";
} else {
  echo "<br/><br/>\n";
}

?>  
<?php if(isset($_REQUEST['iframe']) && $_REQUEST['iframe']) { ?>
  <iframe width="100%" height="<?php echo "$height"; ?>" src="<?php echo "$url"; ?>" style="<?php echo "$style"; ?>" scrolling="no"><p>Your browser must support iframes.</p><</iframe>
<?php } else { ?>
    <embed width="100%" height="<?php echo "$height"; ?>" src="<?php echo "$url"; ?>" style="<?php echo "$style"; ?>"><p>Your browser must support the html embed tag.</p></embed>
    <?php } ?>
  </body>
</html>
