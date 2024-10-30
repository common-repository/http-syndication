<?php
  /**
   * A php library for converting relative urls to absolute.
   * Website: https://github.com/monkeysuffrage/phpuri
   *
   * <pre>
   * echo phpUri::parse('https://www.google.com/')->join('foo');
   * //==> https://www.google.com/foo
   * </pre>
   *
   * Licensed under The MIT License
   * Redistributions of files must retain the above copyright notice.
   *
   * @author P Guardiario <pguardiario@gmail.com>
   * @version 1.0
   * @vthierry patch: simply s/phpUri/http_syndication_phpUri/ to vais name conflicts and eliminating spurious comments
   */
class http_syndication_phpUri {
  public $scheme;
  public $authority;
  public $path;
  public $query;
  public $fragment;
  private function __construct($string){
    preg_match_all('/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$/', $string, $matches);
    $this->scheme = $matches[2][0];
    $this->authority = $matches[4][0];
    $this->path = $matches[5][0];
    $this->query = $matches[7][0];
    $this->fragment = $matches[9][0];
  }
  private function to_str(){
    $ret = "";
    if(!empty($this->scheme)) $ret .= "$this->scheme:";
    if(!empty($this->authority)) $ret .= "//$this->authority";
    $ret .= $this->normalize_path($this->path);
    if(!empty($this->query)) $ret .= "?$this->query";
    if(!empty($this->fragment)) $ret .= "#$this->fragment";
    return $ret;
  }
  private function normalize_path($path){
    if(empty($path)) return '';
    $normalized_path = $path;
    $normalized_path = preg_replace('`//+`', '/' , $normalized_path, -1, $c0);
    $normalized_path = preg_replace('`^/\\.\\.?/`', '/' , $normalized_path, -1, $c1);
    $normalized_path = preg_replace('`/\\.(/|$)`', '/' , $normalized_path, -1, $c2);
    $normalized_path = preg_replace('`/[^/]*?/\\.\\.(/|$)`', '/' , $normalized_path, -1, $c3);
    $num_matches = $c0 + $c1 + $c2 + $c3;
    return ($num_matches > 0) ? $this->normalize_path($normalized_path) : $normalized_path;
  }
  /**
   * Parse an url string.
   * @param string $url the url to parse
   * @return phpUri
   */
  public static function parse($url){
    $uri = new http_syndication_phpUri($url);
    return $uri;
  }
  /**
   * Join with a relative url.
   * @param string $relative the relative url to join
   * @return string
   */
  public function join($relative){
    $uri = new http_syndication_phpUri($relative);
    switch(true){
    case !empty($uri->scheme): break;
    case !empty($uri->authority): break;
    case empty($uri->path):
      $uri->path = $this->path;
      if(empty($uri->query)) $uri->query = $this->query;
    case strpos($uri->path, '/') === 0: break;
    default:
      $base_path = $this->path;
      if(strpos($base_path, '/') === false){
	$base_path = '';
      } else {
	$base_path = preg_replace ('/\/[^\/]+$/' ,'/' , $base_path);
      }
      if(empty($base_path) && empty($this->authority)) $base_path = '/';
      $uri->path = $base_path . $uri->path;
    }
    if(empty($uri->scheme)){
      $uri->scheme = $this->scheme;
      if(empty($uri->authority)) $uri->authority = $this->authority;
    }
    return $uri->to_str();
  }
}
?>
