<?php
/**
 * Plugin googlecal: Inserts an Google Calendar iframe
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Kite <Kite@puzzlers.org>,  Christopher Smith <chris@jalakai.co.uk>
 * @seealso    (http://www.dokuwiki.org/plugin:iframe)
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_googlecal extends DokuWiki_Syntax_Plugin {

    function getType() { return 'substition'; }
    
    function getPType(){ return 'block'; }
    
    function getSort() { return 319; }
    
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{cal>[^}]*?}}', $mode, 'plugin_googlecal');
    }

    function handle($match, $state, $pos, &$handler){        
        if(preg_match('/{{cal>(.*)/', $match)) {             // Hook for future features
            // Handle the simplified style of calendar tag
            $match = html_entity_decode(substr($match, 6, -2));
            
            // Split on pipes, $disp is new and optional
            @list($url, $alt, $disp) = explode('|',$match,3);
            $matches = array();
            
            // '/^\s*([^\[|]+)(?:\[(?:([^,\]]*),)?([^,\]]*)\])?(?:\s*(?:\|\s*(.*))?)?$/mD'
            if (preg_match('/(.*)\[(.*)\]$/', trim($url), $matches)) {
                $url = $matches[1];
                if (strpos($matches[2],',') !== false) {
                    @list($w, $h) = explode(',',$matches[2],2);
                } else {
                    $h = $matches[2];
                    $w = '100%';
                }
            } else {
                $w = '100%';
                $h = '600';
            }
            
            // Only parameter for $disp right now is "a" for Agenda
            if ($disp == 'a') $disp = 'showTitle=0&showPrint=0&showTabs=0&showCalendars=0&showTz=0&mode=AGENDA&wkst=1&bgcolor=%23FFFFFF&';
            if (!isset($disp)) $disp = '';
            
            if (!isset($alt)) $alt = '';
            
            if (!$this->getConf('js_ok') && substr($url,0,11) == 'javascript:') {
                return array('error', $this->getLang('gcal_No_JS'));
            }
            
            //builds and fills the data-array
            return array('wiki', hsc(trim("$url")), hsc(trim($alt)), hsc(trim($disp)), hsc(trim($w)), hsc(trim($h)));
        } else {
            return array('error', $this->getLang("gcal_Bad_iFrame"));  // this is an error
        } // matched {{cal>...
    }

    function render($mode, &$renderer, $data) {
        list($style, $url, $alt, $disp, $w, $h) = $data;
        
        if($mode == 'xhtml'){
            // Two styles: wiki and error
            switch($style) {
                case 'wiki':
                    $renderer->doc .= "<iframe src='http://www.google.com/calendar/embed?".$disp."src=$url&amp;height=$h&amp;title=$alt' title='$alt'  width='$w' height='$h' frameborder='0'></iframe>\n".
				      "<noscript><iframe src='http://www.google.com/calendar/htmlembed?".$disp."src=$url&amp;height=$h&amp;title=$alt' title='$alt'  width='$w' height='$h' frameborder='0'></iframe></noscript>\n";
                    break;
                case 'error':
                    $renderer->doc .= "<div class='error'>$url</div>";
                    break;
                default:
                    $renderer->doc .= "<div class='error'>" . $this->getLang('gcal_Invalid_mode') . "</div>";
                    break;
            }
            return true;
        }
        return false;
    }
}
