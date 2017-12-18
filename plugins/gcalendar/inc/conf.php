<?php
/**
 * gCalendar configuration-file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Frank Hinkel <frank@hi-sys.de>
 */

# match sections as categories. The first subpattern must contain the result
$conf['gCal_match_category'][] = '#^\={2,}\s*(.*?)\s*\={2,}\s*$#s';


# match unordered lists as events. The first subpattern must contain the result
$conf['gCal_match_event'][] = '#^\s*\*{2}([0-9]{1,2}\..*)\*{2}\s*$#s';  // " ** abc **" gets "abc"
$conf['gCal_match_event'][] = '#^\s*\*\s*(.*)\s*$#s';                   // " * abc "    gets "abc"


# match dates in american-style, i.e. 12/31/2006
$conf['gCal_date_mdy'] = '([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4}|[0-9]{2}|)';

# match european styled dates. i.e. 31.12.2006
$conf['gCal_date_dmy'] = '([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4}|[0-9]{2}|)';

# match isodates. i.e. 2006-12-31 or 06-12-31
$conf['gCal_date_ymd'] = '([0-9]{2}|[0-9]{4}|)\-([0-9]{1,2})\-([0-9]{1,2})';


# security-option. only this tags are allowed in calendar-entries
$conf['gCal_allowed_tags'] = '<a><b><br><br/><code><del><div><em><i><p><span><strong><sub><sup>';


# match inline-category in event string.
# the inline-section has to be placed after the time (or date, if time is omitted) 
$conf['gCal_inline_Category_hidden']  = '^\s*\[([A-Za-z_ ]+)\]';
$conf['gCal_inline_Category_visible'] = '^\s*\(([A-Za-z_ ]+)\)';


# how to display times in events -> #h=hour ; #m=minutes ; #r=rest (am/pm) 
$conf['gCal_time'] = "#h<sup><em class='u'>#m</em></sup>#r"; // minutes superscript underlined
#$conf['gCal_time'] = "#h:#m#r"; // use this, if you dont like the superscript-form
