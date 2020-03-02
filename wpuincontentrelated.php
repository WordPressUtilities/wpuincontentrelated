<?php

/*
Plugin Name: WPU Incontent Related
Plugin URI: https://github.com/WordPressUtilities/WPUIncontentRelated
Description: Links to related posts in content
Version: 0.1.1
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

class WPUIncontentRelated {
    private $p_interval = 3;

    public function __construct() {
        add_filter('the_content', array(&$this, 'the_content'), 999);
    }

    public function plugins_loaded() {
        add_filter('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function the_content($content) {
        if ((!is_main_query() || is_admin()) || !is_single()) {
            return $content;
        }

        $sections = $this->get_related(get_the_ID());
        $content_parts = explode('</p>', $content);
        $new_content = '';
        $last_part = count($content_parts) - 1;
        foreach ($content_parts as $i => $content_part) {
            $new_content .= $content_part;
            if ($i < $last_part) {
                $new_content .= '</p>';
            } else {
                break;
            }
            $p_num = $i + 1;
            if ($p_num % $this->p_interval == 0 && !empty($sections)) {
                $sec = array_shift($sections);
                $new_content .= $sec;
            }
        }
        return $new_content;
    }

    public function get_related($post_id) {
        $posts = get_posts(array(
            'post_type' => 'post',
            'posts_per_page' => 20,
            'post__not_in' => array($post_id)
        ));

        shuffle($posts);

        $sections = array();
        foreach ($posts as $_post) {
            $sections[] = $this->get_related_html($_post);
        }
        return $sections;

    }

    public function get_related_html($_post) {
        $html = '<section class="incontent-related">';
        $html .= '<span class="incontent-related__title">' . __('Read more :', 'wpuincontentrelated') . '</span> ';
        $html .= '<span class="incontent-related__desc"><a href="' . get_permalink($_post) . '">' . get_the_title($_post) . '</a></span>';
        $html .= '</section>';
        return $html;
    }
}

$WPUIncontentRelated = new WPUIncontentRelated();
