<?php

class wpuMatchTags {

    private $nb_passes = 10;
    private $start_part = '$$$$';
    private $end_part = '££££';
    private $matches_tmp = array();

    public function __construct($html) {
        $this->html = $html;
    }

    public function replace_tags() {
        for ($i = 0; $i < $this->nb_passes; $i++) {
            $this->html = $this->replace_attributes($this->html, '/=\"([^"]*)\"/isU');
            $this->html = $this->replace_attributes($this->html, '/<(img|br|hr)([^>]*)>/isU');
            $this->html = $this->replace_attributes($this->html, '/<([^>]*)\/>/isU');
            $this->html = $this->replace_attributes($this->html, '/<([^>\/]*)>([^<>]*)<\/([^>]*)>/isU');
        }
    }

    public function replace_matches() {
        for ($y = 0; $y < $this->nb_passes; $y++) {
            foreach ($this->matches_tmp as $k => $match) {
                $this->html = str_replace($k, $match, $this->html);
            }
        }
    }

    public function replace_attributes($html, $regexp) {
        preg_match_all($regexp, $html, $matches);
        $i = count($this->matches_tmp);
        foreach ($matches[0] as $match) {
            $key = $this->start_part . $i . $this->end_part;
            $this->matches_tmp[$key] = $match;
            $html = str_replace($match, $key, $html);
            $i++;
        }
        return $html;
    }

    public function get_html() {
        return $this->html;
    }

    public function set_html($html) {
        $this->html = $html;
    }
}
