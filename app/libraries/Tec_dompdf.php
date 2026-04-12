<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
 *  ==============================================================================
 *  Author  : Mian Saleem
 *  Email   : saleem@biomaussanpos.com
 *  For     : DOMPDF
 *  Web     : https://github.com/dompdf/dompdf
 *  License : LGPL-2.1
 *      : https://github.com/dompdf/dompdf/blob/master/LICENSE.LGPL
 *  ==============================================================================
 */

use Dompdf\Dompdf;
use ArUtil\I18N\Arabic;
use ArUtil\I18N\Identifier;

class Tec_dompdf extends DOMPDF
{
    public function __construct()
    {
        parent::__construct();
    }

    public function generate($content, $name = 'download.pdf', $output_type = null, $footer = null, $margin_bottom = null, $header = null, $margin_top = null, $orientation = 'P')
    {
        $html = '';
        if (is_array($content)) {
            foreach ($content as $page) {
                $html .= $header ? '<header>' . $header . '</header>' : '';
                $html .= '<footer>' . ($footer ? $footer . '<br><span class="pagenum"></span>' : '<span class="pagenum"></span>') . '</footer>';
                $html .= '<div class="page">' . $page['content'] . '</div>';
            }
        } else {
            $html .= $header ? '<header>' . $header . '</header>' : '';
            $html .= $footer ? '<footer>' . $footer . '</footer>' : '';
            $html .= $content;
        }

        // Fix arabic characters
        $Arabic = new Arabic('Glyphs');
        $p      = Identifier::identify($html);
        for ($i = count($p) - 1; $i >= 0; $i -= 2) {
            $utf8ar = $Arabic->utf8Glyphs(substr($html, $p[$i - 1], $p[$i] - $p[$i - 1]));
            $html   = substr_replace($html, $utf8ar, $p[$i - 1], $p[$i] - $p[$i - 1]);
        }

        $html = $this->sanitize_html_for_dompdf($html);
        if (trim($html) === '') {
            throw new RuntimeException('No hay contenido HTML válido para renderizar PDF.');
        }

        // $this->set_option('debugPng', true);
        // $this->set_option('debugLayout', true);
        $this->set_option('isPhpEnabled', true);
        $this->set_option('isHtml5ParserEnabled', true);
        $this->set_option('isRemoteEnabled', true);
        $this->loadHtml($html);
        $this->setPaper('A4', ($orientation == 'P' ? 'portrait' : 'landscape'));
        $this->getOptions()->setIsFontSubsettingEnabled(true);
        $this->render();

        if ($output_type == 'F') {
            file_put_contents($name, $this->output());
            return $name;
        }

        if ($output_type == 'S') {
            $output = $this->output();
            write_file('assets/uploads/' . $name, $output);
            return 'assets/uploads/' . $name;
        }
        $this->stream($name);
        return true;
    }

    private function sanitize_html_for_dompdf($html)
    {
        $html = (string) $html;

        if (function_exists('mb_convert_encoding')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        }

        $html = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $html);

        if (stripos($html, '<html') === false) {
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
        } elseif (stripos($html, '<meta charset=') === false) {
            $html = preg_replace('/<head([^>]*)>/i', '<head$1><meta charset="UTF-8">', $html, 1);
        }

        if (!class_exists('DOMDocument')) {
            return $html;
        }

        $previousUseErrors = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        if (!$loaded) {
            return $html;
        }

        $normalized = $dom->saveHTML();
        return $normalized !== false ? $normalized : $html;
    }
}
