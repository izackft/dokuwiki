<?php
# =================================================================================================
# gCalendar "gcal_read.php" - responsible for reading the data into the big gCal_data-array 
# =================================================================================================

/**
 * read the pages given in the pages=(...) parameter into the "gCal_data"-array
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array $options     contains a list of parameters passed to the plugin at the wiki-page
 * @param  array pages        the wiki-pages to read the data from
 * @param  default_date       if year ist omitted it is taken from this date.
 *                            so you can write "31.07." for a birthdate which shows up every year
 */
function read_pages_into_calendar(&$options,&$pages,$default_date) {
  global $gCal_data;    # this array receives all the date-entries
  
  # reset data-array
  $gCal_data = array();

  # memory for wiki-pages allready read to avoid duplicates
  $pages_allready_read = array();

  foreach($pages as $page_key=>$page)
  {
    list($page_name,$page_list) = explode("(",$page,2);
    $page_list = substr($page_list,0,-1);

    if($page_list != ""){
      $page_list = explode("|",$page_list);
    }else{
      $page_list = array($page_name);
    }

    # expand namespaces (i.e. ":wiki:*") expands to all files in that ns
    # if option 'nested' is given, this is done recursive (all subnamespaces)
    $page_list = expand_ns($page_list,isset($options['nested']));

    foreach($page_list as $wikipage)
    {   
        # split section from wikipage
        list($wikipage,$section)=explode('#',$wikipage,2);
        
        $wikipage=cleanID($wikipage);

        # skip pages allready read, except if allowed with option: "showdup"
        if(!isset($options["showdup"]) && in_array($wikipage,$pages_allready_read)) continue;
        $pages_allready_read[] = $wikipage;

        # check if user is allowed to see this page
        $perm = auth_quickaclcheck(cleanID($wikipage));
        if ($perm < AUTH_READ) continue; # to next page

        # if we have more than one page to include or option pagelinks is set to 'show',
        # generate a link to the wiki page, except option pagelinks is set to 'hide'. 
        if( ($options["pagelinks"]=="show") || 
           (($options["pagelinks"]!="hide") && count($page_list)>1) ) {
          $pagelink = " <span class='gCal_pagelink'><a href='".wl($wikipage)."'>".noNS($wikipage)."</a></span>";
        }else{
          $pagelink="";
        }
        
        # now read this page into the calendar-array
        read_wikipage_into_calendar($options,$page_key,$wikipage,$section,$pagelink,$default_date);
    }
  }
}


/**
 * read a page into the gCal_data-array
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array $options     contains a list of parameters passed to the plugin at the wiki-page
 * @param  array page_key     the column number where the data is stored into the gCal_data-array
 * @param  string wikipage    the wiki-page to read the data from
 * @param  string section     optionally given section inside the wikipage to read from
 * @param  default_date       if year ist omitted it is taken from this date.
 *                            so you can write "31.07." for a birthdate which shows every year
 *
 */
function read_wikipage_into_calendar(&$options,$page_key,$wikipage,$section,$pagelink,$default_date) {
  global $gCal_data; # this array contains all the date-entries
  global $conf;

  # read categorypattern from config
  $match_category = $conf['gCal_match_category'];
  if(!is_array($match_category)) return;

  # read eventpattern from config.
  $match_event = $conf['gCal_match_event'];
  if(!is_array($match_event)) return;
  
  # find path to actual wiki-page. skip if file not exists
  $filepath = wikiFN($wikipage);
  if(!file_exists($filepath)) return;

  # read data from wiki-page  
  $handle = fopen ($filepath, "r");

  while (!feof($handle)) {
    $buffer = trim(fgets($handle, 4096));

    # check line against all category-patterns. see user/conf.php
    foreach($match_category as $pattern) {
      if(preg_match($pattern, $buffer, $subpattern)) {
        $category = strtoupper($subpattern[1]);
        break;
      }
    }

    if(is_string($section) && strlen($section)>0 && (strtoupper($section)!=$category)) continue;
    
    # check line against all event-patterns. see user/conf.php
    foreach($match_event as $pattern) {
      if(preg_match($pattern, $buffer, $subpattern)) {
        $buffer = $subpattern[1];

        # grab date- and time-spans  from the beginning of each line
        $start_date = $end_date = fetch_date($buffer,$default_date);
        $end_time = "";
        $start_time = fetch_time($buffer);

        # check for time-spans indicated by a dash
        if($buffer{0}=="-") {
          $buffer = trim(substr($buffer,1)); # remove dash
          $end_date   = fetch_date($buffer,$default_date);
          # end_date equals start_date by default
          if ( strlen($end_date)==0 ) $end_date = $start_date;
          $end_time   = fetch_time($buffer);
        }
  
        $cat = strtoupper(trim($category." ".fetch_inline_category($buffer)));
        
        # insert the event into the whole date-range
        for($d=$start_date ; $d <= $end_date ; $d++) {
          $entry = $buffer;

          # initialize event with event-source
          $event = array("source"=>$subpattern[1]);
          
          # set the category, even when there is no event-text
          $event["categories"] = $cat;
          
          # generate an event, when event-text or start-time or end-time is given
          if(($entry!="") || ($start_time!="") || ($end_time!="")) {
            # special character ">" will be suppressed, when it is the first character
            # of the event-text. can be used to force an event-icon without text
            if($entry{0}==">") $buffer=substr($entry,1);

            # apply basic rendering like bold, italic, links, etc.
            $entry = fast_p_render($entry);

            # add the backlink to the original wikipage
            $entry .= $pagelink;

            # attach start_time at start_date and end_time at end_date. I hope this is allways logical? 
            if(is_array($end_time) && ($d==$end_date)  ) {
                $entry = "- ".$end_time[0]. " ".$entry;
                $event["end_time"] = $end_time[1];
            }
            if(is_array($start_time) && ($d==$start_date)) {
              $entry = $start_time[0]." ".$entry;
              $event["start_time"] = $start_time[1];
            }

            # write the event-entry to global array
            $cat_classes = 'gCal_cat_'.implode(' gCal_cat_',explode(' ',$cat))." ";

            $event["content"] = "<span class='$cat_classes gCal_event'>".$entry."</span>";
          }
          $gCal_data[$page_key][$d][] = $event;
        }
        break;
      }
    }
  }
  fclose($handle);
}


# =================================================================================================
# Utility-functions
# =================================================================================================

/*
 * returns date at the beginning of the text. if date found it is removed from the text.
 */
function fetch_date(&$text,$default_date) {
  global $conf;
  
  if($text=="") return;

  #  '#^'            --> # string has to start with the pattern. 
  #  '(?=($|\D))#'   --> # look-ahead => any non-digit or end-of-string terminates pattern

  if(preg_match('#^'.$conf['gCal_date_dmy'].'(?=($|\D))#',$text,$match)) {
    $d=$match[1];$m=$match[2];$y=$match[3];      
  }elseif(preg_match('#^'.$conf['gCal_date_mdy'].'(?=($|\D))#',$text,$match)) {
    $d=$match[2];$m=$match[1];$y=$match[3];      
  }elseif(preg_match('#^'.$conf['gCal_date_ymd'].'(?=($|\D))#',$text,$match)) {
    $d=$match[3];$m=$match[2];$y=$match[1];
  }else{
    return;
  }

  if(strlen($d)==1) $d='0'.$d;
  if(strlen($m)==1) $m='0'.$m;
  if(strlen($y)==0) $y=date('Y',$default_date);
  if(strlen($y)==2) $y='20'.$y;

  $text=trim(substr($text,strlen($match[0])));
  return $y.$m.$d; // return format yyyymmdd
}


/*
 * returns time at the beginning of the text
 */
function fetch_time(&$text) {
global $conf;

  if($text=="") return;

  # allowed formats are: 1:23 , 01:23, 01:23am. 1:23 Am, etc.
  $pattern = '([0-9]{1,2})\:([0-9]{2})\s*(am|pm|)';
  $pattern  = '#^'.$pattern;   # string has to start with the pattern 
  $pattern .= '(?=($|\\D))#i'; # look-ahead => any non-digit or end-of-string terminates pattern

  if(preg_match($pattern,$text, $match)) {
    $text=trim(substr($text,strlen($match[0])));
    
    $time = str_replace(array('##','#h','#m','#r'),$match,$conf['gCal_time']);
    if(strlen($match[1])==1) $match[1] = '0'.$match[1];
    if(strtolower($match[3])=='pm') $match[1] += 12;
    $euro  = $match[1].":".$match[2];
    
    return array($time,$euro);
  }
}

/*
 * returns the inline-category
 */
function fetch_inline_category(&$text) {
global $conf;

  if($text=="") return;

  # get the preg-expr from $conf. sting has to start with this pattern

  $pattern = '#'.$conf['gCal_inline_Category_visible'].'#i';
  if(preg_match($pattern,$text, $match)) {
    $text=trim($match[1].substr($text,strlen($match[0])));
    return strtoupper($match[1]);
  }
  
  $pattern = '#'.$conf['gCal_inline_Category_hidden'].'#i';
  if(preg_match($pattern,$text, $match)) {
    $text=trim(substr($text,strlen($match[0])));
    return strtoupper($match[1]);
  }
}

/**
 * Expand namespaces in the form :namespace:* to every wiki-page in this ns.
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array page_list    on input  : array of pages and namespaces
 * @param  array page_list    on output : array of pages (namespaces are expanded)
 * @param  boolean nested  if true -> subnamespaces are also expanded into single-pages
 *
 */
function expand_ns($page_list,$nested=false) {
  global $conf;
  $pl = array();
  
  foreach($page_list as $page) {
    if(substr($page,-1)=="*") {
    # when page ends with *, expand namespace
      $start = noNS($conf['start']);
      $dir = wikiFN(substr($page,0,-1).$start);
      $dir = substr($dir,0,-(strlen($start)+4)); # strip filename - only the dir is needed
      
      if(!@$handle=opendir($dir)) continue;
      
      while ($file = readdir ($handle)) { 
        if($file == "." || $file == ".." || trim($file)=="") continue;
        
        if(is_file($dir.$file)) {
          $pl[] = substr($page,0,-1).substr($file,0,-4);
        }elseif($nested && is_dir($dir.$file)){
          $subNS[] = substr($page,0,-2).':'.cleanID($file).':*';
        }
      }
      
      closedir($handle); 
    }else{ 
    # leave page as it is
      $pl[] = $page;
    }
  }
  
  # if $nested is true, add all files of all subnamespaces to the list
  if(is_array($subNS)) {
      $subNS = expand_ns($subNS,$nested,false);
      $pl = array_merge($pl,$subNS);
  }
  
  return $pl;
}


/**
 * quick rendering, where not all the power of p_render is needed
 * do basic formatting and linking. returns the rendered string
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 * @param  string $text   text to be rendered
 *
 * @todo : add more rendering
 */
function fast_p_render($text) {
  global $conf;
  
  # do some fundamental-rendering: bold, italic, underline, ...
  $text = preg_replace('#\*\*(.*)\*\*#sU', '<strong>\1</strong>', $text);
  $text = preg_replace('#(?<!\:)\/\/(.*)(?<!\:)\/\/#sU', '<em>\1</em>', $text);
  $text = preg_replace('#\_\_(.*)\_\_#sU', '<em class="u">\1</em>', $text);
  $text = preg_replace('#\'\'(.*)\'\'#sU', '<code>\1</code>', $text);
  $text = preg_replace('#\\\\\\\\(\s+|$)#m', '<br/>', $text);

  $text = str_replace('=>','&rArr;',$text);
  $text = str_replace('<=','&lArr;',$text);
  $text = str_replace('->','&rarr;',$text);
  $text = str_replace('<-','&larr;',$text);

  # some methods of the class 'Doku_Renderer_xhtml' needed here
  static $drx; if(!isset($drx)) $drx = new Doku_Renderer_xhtml;

  # process all links enclosed in double square brackets  
  preg_match_all("=\[\[.+\]\]=sU",$text,$wiki_links);

  foreach($wiki_links[0] as $wl) {
    # reset rendered content
    $drx->doc = '';

    list($link,$name)=explode("|",substr($wl,2,strlen($wl)-4),2);
    
    if ( preg_match('/^[a-zA-Z\.]+>{1}.*$/u',$link )) {
      // Interwiki
      if(count($drx->interwiki)==0) $drx->interwiki = getInterwiki();

      list($wikiname,$wikiuri) = preg_split('/>/u',$link,2);
      $drx->interwikilink('',$name,strtolower($wikiname),$wikiuri);
    }elseif ( preg_match('/^\\\\\\\\[\w.:?\-;,]+?\\\\/u',$link) ) {
      // Windows Share
      $drx->windowssharelink($link,$name);
    }elseif ( preg_match('#^([a-z0-9\-\.+]+?)://#i',$link) ) { 
      // external link (accepts all protocols)
      $drx->externallink($link,$name);
    }elseif ( preg_match('#([a-z0-9\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i',$link) ) {
      // email-link
      $drx->emaillink($link,$name);
    }elseif ( preg_match('!^#.+!',$link) ){
      // local link
      $drx->locallink($link,$name);
    }else {
      $drx->internallink($link,$name);
    }

    $text=str_replace($wl,$drx->doc,$text);
  }
  
  # remove all html-tags which are not explicitly allowed
  return strip_tags($text,$conf['gCal_allowed_tags']);
}
