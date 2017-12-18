<?php
/**
 * ODT (Open Document format) export for Typography plugin
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars (LarsDW223)
 */

if(!defined('DOKU_INC')) die();

class helper_plugin_typography_odt extends DokuWiki_Plugin {

    protected $closing_stack = NULL; // used in odt_render()

    protected $props = array(
            'ff' => 'font-family:',
            'fc' => 'color:',
            'bg' => 'background-color:',
            'fs' => 'font-size:',
            'fw' => 'font-weight:',
            'fv' => 'font-variant:',
            'lh' => 'line-height:',
            'ls' => 'letter-spacing:',
            'ws' => 'word-spacing:',
            'va' => 'vertical-align:',
            'sp' => 'white-space:',
        );

    public function render(Doku_Renderer $renderer, $indata) {
        list($state, $data) = $indata;

        if (is_null($this->closing_stack)) {
            $this->closing_stack = new SplStack(); //require PHP 5 >= 5.3.0
        }

        switch ($state) {
            case DOKU_LEXER_ENTER:
                $css = '';
                foreach ($data as $type => $val) {
                    $css .= $this->props[$type].$val.'; ';
                }
                if (empty($data['lh'])) {
                    if (method_exists ($renderer, '_odtSpanOpenUseCSSStyle')) {
                        $renderer->_odtSpanOpenUseCSSStyle($css);
                    } else {
                        $renderer->_odtSpanOpenUseCSS('span', 'style="'.$css.'"');
                    }
                    $this->closing_stack->push('span');
                } else {
                    $renderer->p_close();
                    if (method_exists ($renderer, '_odtParagraphOpenUseCSSStyle')) {
                        $renderer->_odtParagraphOpenUseCSSStyle($css);
                    } else {
                        $renderer->_odtParagraphOpenUseCSS('p', 'style="'.$css.'"');
                    }
                    $this->closing_stack->push('p');
                }
                break;
            case DOKU_LEXER_UNMATCHED:
                $renderer->cdata($data);
                break;
            case DOKU_LEXER_EXIT:
                try {
                    $content = $this->closing_stack->pop();
                    if ($content == 'p') {
                        // For closing paragraphs use the renderer's function otherwise the internal
                        // counter in the ODT renderer is corrupted and so would be the ODT file.
                        $renderer->p_close();
                        $renderer->p_open();
                    } else {
                        // Close the span.
                        $renderer->_odtSpanClose();
                    }
                } catch (Exception $e) {
                    // May be included for debugging purposes.
                    //$renderer->doc .= $e->getMessage();
                }
                break;
        }
        return true;
    }
}
