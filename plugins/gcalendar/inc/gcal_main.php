<?php
# =================================================================================================
# gCalendar main-part
# =================================================================================================

/**
 * main-part of the gcalendar-plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Frank Hinkel <frank@hi-sys.de>
 *
 * the options-array contains all parameters passed to the gcal statement
 */

include_once('gcal_read.php'); # responsible for reading the data into the gCal_data-array
include_once('gcal_show.php'); # responsible for displaying the calendar-data 


/**
 * Main render-function of the plugin.
 * This function is called from the function "render" in "syntax.php"
 *
 * @author Frank Hinkel <frank.hinkel@hi-sys.de>
 *
 * @param  array $options contains a list of parameters passed to the plugin at the wiki-page
 * @param  object $renderer  
 */
function render_gcal(&$options) {
  global $ID;
  global $conf;
  
  # generate style.css and print.css files
  copy_css();

  # set month as default mode
  if(!isset($options['mode'])) $options['mode']='month';

  # url-pars override pars on wiki-page
  if(isset($_REQUEST['mode']))   $options['mode']   = $_REQUEST['mode'];
  if(isset($_REQUEST['offset'])) $options['offset'] = $_REQUEST['offset'];
  if(isset($_REQUEST['pages']))  $options['pages']  = $_REQUEST['pages'];

  # if no page is given in the options use actual page
  if(!isset($options['pages'])) $options['pages'] = '('.$ID.')';

  # fetch parameter "pages" and explode the list "(page1,page2,...)" to an array

  # get options "pages" and remove the parens
  $pages = $options['pages'];
  $pages = substr($pages,1,strlen($pages)-2);
  

  # expand some macros
  $user = $_SESSION[$conf['title']]['auth']['user'];
  if($user=="") $user="guest";
  $pages = str_replace('@USER@',$user,$pages);
  $pages = str_replace('@ID@',$ID,$pages);
  
  # explode the comma-seperated list to an array
  $pages = explode(',',$pages);

  # grab date from actual timestamp
  $today = getdate();
  $day   = $today["mday"];
  $year  = $today["year"];
  $month = $today["mon"];
  
  # grab date from options (gCal-command in wiki-page)
  if(isset($options["month"])) $month = $options["month"] + $options["offset"] + $_SESSION["offset"];
  if(isset($options["year"]))  $year  = $options["year"];
  if(isset($options["day"]))   $day   = $options["day"];

  # grab date from request (url)
  if(isset($_REQUEST["day"]))   $day   = $_REQUEST["day"];
  if(isset($_REQUEST["month"])) $month = $_REQUEST["month"];
  if(isset($_REQUEST["year"]))  $year  = $_REQUEST["year"];

  # calculate given offset according to selected mode
  if(isset($options["offset"])) {
    switch($options["mode"]) {
      case "day"   : $day   += $options["offset"];     break;
      case "week"  : $day   += $options["offset"] * 7; break;
      case "month" : $month += $options["offset"]    ; break;
    }
  }

  # finally this is our reference-date for further calculations
  $reference_date = mktime(0, 0, 0, $month, $day, $year);

  # for debug only. add-dayshift if given
  if(isset($options['dayshift'])) $reference_date += 60*60*24*$options['dayshift'];

  # calculate the date-range to display according to selected mode
  switch(strtolower($options["mode"])) {
    case "day"   : $start_date = $reference_date;
                   $end_date   = $reference_date; 
                   if(isset($options["days"])) {
                     $end_date = strtotime ( "+".($options["days"]-1)." days", $end_date);
                   }
                   break;

    case "week"  : $shift = date('w',$reference_date)-1;
                   if($shift==-1) $shift=6;
                   $start_date = strtotime( "-".$shift."days", $reference_date );
                   if(isset($options["days"])) {
                     $end_date = strtotime( "+".($options["days"]-1)." days", $start_date);
                   } else {
                     $end_date   = strtotime( "+1 week -1 day", $start_date );
                   }
                   break;

    case "month" :;# month-view is the default case
    default      : $start_date = mktime(0, 0, 0, $month, 1, $year);
                   $end_date   = strtotime( "+1 month -1 day", $start_date );
  }
  
  # read every given wiki-page (pages-parameter) into the calendar-array
  read_pages_into_calendar($options,$pages,$reference_date);

  # start output --------------------------------------------------------------
  show_gCal_page($options,$pages,$start_date,$end_date);
}


/**
 * copy the $in_files over to style.css and print.css if
 * at least one of the $in_files is newer than "style.css"   
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 */     
function copy_css() {
  $in_files=array(
      "inc/standard.css",
      "gCal_cell_cat_" => "user/background.css",
      "gCal_cat_"      => "user/events.css",
      "user/other.css"
  );

  $infile_time = 0;
  foreach($in_files as $file) {
      $t = filemtime(DOKU_GCAL.$file);
      if($t>$infile_time) $infile_time = $t;
  }
  if(!file_exists(DOKU_GCAL."style.css") ||
     (filemtime(DOKU_GCAL."style.css") < $infile_time)) {
        append($in_files,"style.css");      
        $result = @copy(DOKU_GCAL."style.css",DOKU_GCAL."print.css");
  }
}


function append($source_arr, $destfile) {
    $out_handle = fopen (DOKU_GCAL.$destfile, "w");
    if(!$out_handle) return;
    
    fwrite($out_handle,"/*\n");
    fwrite($out_handle," * WARNING:\n");
    fwrite($out_handle," * This file was automatically created by the gCalendar-plugin.\n");
    fwrite($out_handle," * Do NOT edit this file, because your changes will be overwritten !\n");
    fwrite($out_handle," */\n");

    foreach($source_arr as $class_prefix => $infile) {
        if(file_exists(DOKU_GCAL.$infile)) {
            $in_handle = fopen(DOKU_GCAL.$infile,"r");
            if(!$in_handle) continue;

            $text = " copied from file '$infile' ";
            $text = str_pad ($text, 93, "#", STR_PAD_BOTH);  
            fwrite($out_handle,"\n/* $text */\n\n");
            
            while (!feof($in_handle)) {
              $line = fgets($in_handle, 4096);
              
              if(is_string($class_prefix) && strlen($class_prefix)>0) {
                # did I allready mentioned that I hate the complexity of reg-exps ?
                # but I love its power ! Btw I dont think thats good coding-style.
                $line = preg_replace('#((\,|^)\s*\.)(\w*)(\W)#Ue', "'\\1'.$class_prefix.strtoupper('\\3').'\\4'", $line);
              }
              fwrite($out_handle,$line);
            }

            fclose($in_handle);
        }
    }
    fclose($out_handle);
    
}
