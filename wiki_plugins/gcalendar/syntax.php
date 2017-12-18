<?php
/**
 * plugin gcalendar
 *
 * Syntax <gcal par1=val1 par2=val2 .... > => space delimited list of parameters
 *
 * @license    GNU_GPL_v2
 * @author     Frank Hinkel<Frank [at] hi-sys [dot] de>
 */

global $lang;
global $conf;

require_once(DOKU_PLUGIN.'syntax.php');

if(!defined('DOKU_GCAL')) define('DOKU_GCAL',dirname(__FILE__)."/");
include_once(DOKU_GCAL.'inc/conf.php');          # load common-settings
include_once(DOKU_GCAL.'user/local_conf.php');   # load user-settings
include_once(DOKU_GCAL.'lang/en/lang.php');      # the localisation is loaded in the renderer


/**
 *  class syntax_plugin_gcalendar
 *
 *  @author     Frank Hinkel<Frank [at] hi-sys [dot] de>
 */
class syntax_plugin_gcalendar extends DokuWiki_Syntax_Plugin {

    function getInfo(){
        $lcDate = substr('$LastChangedDate: 2006-12-19 22:42:04 +0100 (Di, 19 Dez 2006) $',18,10);
        $rev    = substr('$LastChangedRevision: 89 $',22);
        $rev    = trim(substr($rev,0,-1));

        return array(
            'author' => 'Frank Hinkel',
            'email'  => 'frank.hinkel@hi-sys.de',
            'date'   => $lcDate.' Rev '.$rev,
            'name'   => 'gcalendar',
            'desc'   => 'Transforms unordered lists of dates in different wiki-pages into a group-calendar',
            'url'    => 'http://wiki.splitbrain.org/plugin:gcalendar',
            'revision' => $rev
        );
    }
    function getType(){ return 'substition'; }
    function getPType(){ return 'normal'; }
    function getAllowedTypes() {
        return array('substition','protected','disabled');
    }
    function getSort(){ return 333; } # really dont know what to put here !?!
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<gcal>|<gcal .*?>',$mode,'plugin_gcalendar');
    }
    function handle($match, $state, $pos, &$handler){

        switch ($state) {
          case DOKU_LEXER_SPECIAL :
            return array($state, $match);

          default:
            return array($state);
        }
    }

    function render($mode, &$renderer, $indata) {
    global $lang;
    global $conf;

        if($mode == 'xhtml'){
          list($state, $data) = $indata;

          switch ($state) {
            case DOKU_LEXER_SPECIAL :
              # remove leading "<gcal " and trailing ">"
              $data = trim(substr($data,6,strlen($data)-7));
              $data = html_entity_decode($data);

              # read parameters into options-array for better access
              $options_arr = explode(' ',$data);
              if(!is_array($options_arr)) return false;

              $options = array();
              foreach($options_arr as $values) {
                list($k,$v)=split('=',$values);
                if(!isset($v)) $v=$k;
                $options[$k]=$v;
              }

              # possibility to switch language.
              if(!isset($options['lang'])) $options['lang'] = $conf['lang'];
              include(DOKU_GCAL.'lang/'.$options['lang'].'/lang.php');

              # caching disabled, because calendar needs to be up-to-date
              $renderer->info['cache'] = isset($options['cache']);

              # sometimes the table-of-contents (toc) is not wished. The option "notoc" turns it off.
              if(isset($options['notoc'])) $renderer->info['toc'] = false;

              ob_start();

              # first check the debug-option. If set echo complete command to user page.
              if(isset($options['debug'])) {
                echo "<font color='red'><b>&lt;gcal ".$data."></b></font><br/>";
              }

              require_once('inc/gcal_main.php'); // once - so we can use the plugin more than once at a wiki-page
              render_gcal($options);  // show the group-calendar

              $content = ob_get_contents();
              ob_end_clean();

              $renderer->doc .= $content;

              break;
          }
          return true;
        }

        return false; //  unsupported mode
    }
}

