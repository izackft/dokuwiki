<?php
# =================================================================================================
# gCalendar "gcal_show.php" - responsible for displaying the calendar-data
# =================================================================================================


/**
 * show the data of the gCal_data-array for a given date-range
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array  options      options from the command "<gcal ....>"
 * @param  array  pages        the columns to show the data for
 * @param  string start_date
 * @param  string end_date
 *
 */
function show_gCal_page(&$options,&$pages,$start_date,$end_date) {
  global $lang;
  global $conf;
  global $ID;

  $act_date = getdate($start_date);

  # calculate some variables depending on parameter mode
  switch(strtolower($options['mode'])) {
     case 'day'   : $header = $header = $lang['gCal_day'];
                    $day_shift=1;
                    if(isset($options['days'])) $day_shift=$options['days'];
                    $month_shift=0;
                    $date_format=$lang['gCal_dv_day'];
                    break;

     case 'week'  : $header = $header = $lang["gCal_weekofyear"].date('W',$start_date)."/".$act_date["year"];
                    $day_shift=7;
                    $month_shift=0;
                    $date_format=$lang['gCal_wv_day'];
                    break;

     case 'month' : $header = $lang['gCal_month_'.$act_date["mon"]]." ".$act_date["year"];
                    $lang['gCal_month_'.$act_date["mon"]]." ".$act_date["year"];
                    $day_shift=0;
                    $month_shift=1;
                    $date_format=$lang['gCal_mv_day'];
                    break;

     default      : return;
  }

  # generate link to current_date. preserve mbdo-mode
  $curr_link_options = array();
  if(isset($_REQUEST['mbdo'])) $curr_link_options['mbdo']=$_REQUEST['mbdo'];
  $curr_link = wl($ID,$curr_link_options);

  # generate link to previus page
  $prev_link_options = $curr_link_options;
  $prev_link_options["day"  ] = $act_date["mday"] - $day_shift;
  $prev_link_options["month"] = $act_date["mon"]  - $month_shift;
  $prev_link_options["year" ] = $act_date["year"];
  $prev_link = wl( $ID, $prev_link_options );

  # generate link to next page
  $next_link_options = $curr_link_options;
  $next_link_options["day"  ] = $act_date["mday"] + $day_shift;
  $next_link_options["month"] = $act_date["mon"]  + $month_shift;
  $next_link_options["year" ] = $act_date["year"];
  $next_link = wl( $ID, $next_link_options );

  # check for fontsize
  if(isset($options['fontsize']))$fontsize = " style='font-size:".$options['fontsize'].";'";

  # show month/week-table ----------------------------------------------------------------------------
  echo "</p><table class='gCal_table' $fontsize>\n";
  show_column_groups($pages,$options);

  if(!isset($options['nonav']) && !isset($options['noheader'])) {
      echo "<tr><th colspan='".(count($pages)+1)."'>"; # the header span over all columns

      # show navigation-table @todo : add better navigation
      echo "<table class='gCal_naviTable'><tr><td class='gCal_header gCal_link_prev'>";
      echo "<a href='$prev_link'>&lt;&lt;&lt;</a>";
      echo "</td><td class='gCal_header'><a href='$curr_link'>$header</a>";
      echo "<br/><span class='gCal_currentDate'>".date($conf['dformat'])."</span>";
      echo "</td><td class='gCal_header gCal_link_next'>";
      echo "<a href='$next_link'>&gt;&gt;&gt;</a>";
      echo "</td></tr></table>";

      echo "</th></tr>\n";

      # show table-header with clickable titles------------------------------------------------------
  }

  if(!isset($options['nohead']) && !isset($options['noheader'])) {
      show_column_headers($pages,$options);
  }

  # create table-body -----------------------------------------------------------------------------

  for($i=$start_date ; $i <= $end_date ; $i = strtotime ( "+1 days", $i ) ) {
    show_day($options,$i,$pages,$date_format);
  }


  # create table-footer with clickable titles------------------------------------------------------

  if($options['mode']!='day' && !isset($options['nofoot']) && !isset($options['noheader'])) {
    show_column_headers($pages,$options);
  }

  # end of table ----------------------------------------------------------------------------------

  echo "</table><p>\n";
}


/**
 * Show the data of the given day.
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array  options      options from the command "<gcal ....>"
 * @param  day                 the day to be displayed
 * @param  array pages         the columns to show the data for
 * @param  string date_format  to format the date in the first column
 *
 */
function show_day(&$options,$day,&$pages,$date_format) {
  global $gCal_data; # this array contains all the date-entries
  global $lang;
  global $ID;

  $date = date("Ymd", $day);
  if(isset($options['dayshift'])) $dayshift=$options['dayshift']*60*60*24;else $dayshift=0;

  $weekday = $lang['gCal_weekday_'.date("w", $day)];
  $wd_class = "gCal_wd_".date("w", $day)." ";

  # marker for today. dayshift is only used for test-purposes
  if(isset($options['dayshift'])) $dayshift=$options['dayshift']*60*60*24;else $dayshift=0;
  if(date("Y.m.d",$day)==date("Y.m.d",time()+$dayshift)) {
    $todaystyle="gCal_today";
    $todayframe="gCal_todayFrame";
  }else{
    $todaystyle="";
    $todayframe="";
  }

  $date_cell  = "<span class='gCal_mv_day'>".date($date_format, $day)."</span>";
  $date_cell .= "<br/><span class='gCal_mv_weekday'>".$weekday."</span>";

  # when in compact-mode dont show empty dates
  if((date("Y.m.d",$day)!=date("Y.m.d",time()+$dayshift)) &&
     (isset($options['compact']) || isset($options['dropempty'])) ) {
    $no_entry=true;
    foreach($pages as $page_key=>$page) {
      if(isset($gCal_data[$page_key][$date])) {
        $no_entry=false;
        break;
      }
    }
    if($no_entry) {
      # if option "dropempty" is given drop the complete empty line.
      if(isset($options['dropempty'])) return;
      # otherwise omit shrink the line
      $date_cell  = "<span class='gCal_mv_day'>".date($date_format, $day)."</span>";
    }
  }

  echo "<tr><td class='gCal_wd gCal_wd_datecell $wd_class$todaystyle'>$date_cell</td>";


  foreach($pages as $page_key=>$page) {
     list($page,$pagelist) = explode("(",$page,2);
     $pagelist = substr($pagelist,-1);

     $cell = "";
     $cat ="";
     $cat_classes = "";
     $style = "";
     $events = $gCal_data[$page_key][$date];

     if(is_array($events)) {
         mergesort($events,'cmp_events');

         foreach($events as $event) {
             if(isset($event["content"])) {
               if($cell!="") $cell .= "\n";
               $cell .= $event["content"];
             }

             if(isset($event["categories"])) {
               $cat[] = strtoupper($event["categories"]);
             }
         }

         if(is_array($cat)) {
             $cat_classes .= 'gCal_cell_cat_'.implode(' gCal_cell_cat_',$cat)." ";
         }
     }


     $class = trim(trim($cat_classes)." ".trim($wd_class)." ".$todayframe);

     echo "<td class='gCal_wd $class'>$cell</td>";
  }

  echo "</tr>\n";
}

/**
 * echos the colgroup-tag of calendar table to define the widths of the page-columns
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array pages        the columns to show the data for
 * @param  array options      the options from the gcal-command
 */
function show_column_groups(&$pages,&$options) {
  global $conf;

  # setup array with column-widths. either user defined or automatic
  $width_list = array();
  if(isset($options["width"]) && $options["width"]{0}=="(" && substr($options["width"],-1)==")")
    $width_list = explode(",",substr($options["width"],1,-1));

  # first width-value is for the date-column. Use 10% as default value.
  if(!isset($width_list[0])) $width_list[0]="10%";

  # calculate all columns with same width as default
  $page_count = count($pages);
  if($page_count>0 && $options["width"]!='auto')
    $column_width=floor((100-$width_list[0])/$page_count)."%";
  else
    $column_width="*";

  echo "<colgroup>";
    echo "<col width='".$width_list[0]."'>";

    foreach($pages as $key=>$page_id) {
      $width = (isset($width_list[$key+1]))?$width_list[$key+1]:$column_width;
      echo "<col width='$width'>";
    }
  echo "</colgroup>";
}


/**
 * displays the clickable (or not) column-headers
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array pages        the columns to show the data for
 * @param  array options      the options from the gcal-command
 */
function show_column_headers(&$pages,&$options) {
  global $lang;
  global $conf;

  # show the date-column
  echo "<tr>";
  echo "<th class='gCal_columnHead'>".$lang['gCal_day']."</th>";

  # show the column headers of the pages-array
  foreach($pages as $key=>$page_ID) {
    # convert page-ID to page-title
    list($page_name,$page_list) = explode("(",$page_ID,2);
    $page_list = substr($page_list,0,-1);

    if($page_list=="") {
      $page_ID = substitute_asterisk($page_ID);

      $name = noNS($page_ID);
      if ($conf['useheading']) {
        // get page title
        $title = p_get_first_heading($page_ID);
        if($title) $name = $title;
      }
      $head = "<a title='$pagelist' href='".wl($page_ID)."'>".$name."</a>";
    }else if($page_name=="") {
      $page_list=explode("|",$page_list);
      $head=$page_name;

      $titles = array();

      foreach($page_list as $pl) {
        $pl = substitute_asterisk($pl);

        $name = noNS($pl);
        if ($conf['useheading']) {
          // get page title
          $title = p_get_first_heading($pl);
          if ($title) $name = $title;
        }

        $titles[] = "<span><a title='$pagelist' href='".wl($pl)."'>".$name."</a></span>";
      }

      $head = implode(' ',$titles);
    }else{
      $head=$page_name;
    }

    # get column-width from list or take default value
    echo "<th class='gCal_columnHead'>$head</th>";
  }

  echo "</tr>\n";
}


/**
 * substitute asterisk by $conf['start'] (no Namespace). I.e. ":calendar:*" gets ":calendar:start"
 *
 * @author Frank Hinkel <frank@hi-sys.de>
 *
 * @param  array page_ID
 */
function substitute_asterisk($page_ID) {
  global $conf;

  if( (substr($page_ID,-2)==':*') || ($page_ID=="*")) {
    $page_ID = substr($page_ID,0,-1);
    $page_ID.= noNS($conf['start']);
  }

  return $page_ID;
}

/**
 * order the events of a given day by time. used mergesort instead of usort, because it is stable.
 * which means that events stay in their original order when they compare to be equal.
 */
function mergesort(&$array, $cmp_function = 'strcmp') {
   // Arrays of size < 2 require no action.
   if (count($array) < 2) return;
   // Split the array in half
   $halfway = count($array) / 2;
   $array1 = array_slice($array, 0, $halfway);
   $array2 = array_slice($array, $halfway);
   // Recurse to sort the two halves
   mergesort($array1, $cmp_function);
   mergesort($array2, $cmp_function);

   // If all of $array1 is <= all of $array2, just append them.
   if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
       $array = array_merge($array1, $array2);
       return;
   }
   // Merge the two sorted arrays into a single sorted array
   $array = array();
   $ptr1 = $ptr2 = 0;
   while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
       if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
           $array[] = $array1[$ptr1++];
       }
       else {
           $array[] = $array2[$ptr2++];
       }
   }
   // Merge the remainder
   while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
   while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
   return;
}


function cmp_events($e1,$e2) {
  $t1 = $e1['start_time'];if($t1=="")$t1 = $e1['end_time'];
  $t2 = $e2['start_time'];if($t2=="")$t2 = $e2['end_time'];

  if($t1==$t2) {
    $t1 = $e1['end_time'];
    $t2 = $e2['end_time'];
    if($t1==$t2) return 0;
  }

  return ($t1 < $t2) ? -1 : 1;
}


