<?php

/*
Plugin Name: WPU Incontent Related
Plugin URI: https://github.com/WordPressUtilities/WPUIncontentRelated
Description: Links to related posts in content
Version: 0.2.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
*/

include dirname(__FILE__) . '/inc/classes/wpumatchtags.class.php';

class WPUIncontentRelated {
    private $settings = array(
        'tag_interval' => 3,
        'post_type' => 'post',
        'shuffle_posts' => false
    );

    public function __construct() {
        add_filter('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {
        $this->settings['callback_interval'] = array(&$this, 'check_interval');
        $this->settings = apply_filters('wpuincontentrelated__settings', $this->settings);
        add_filter('the_content', array(&$this, 'the_content'), 999);
    }

    public function the_content($content) {
        if ((!is_main_query() || is_admin()) || !is_single()) {
            return $content;
        }

        $sections = $this->get_related(get_the_ID());

        /* Isolate only root tags and keep only temp matches */
        $wpuMatchTags = new wpuMatchTags($content);
        $wpuMatchTags->replace_tags();
        $content = $wpuMatchTags->get_html();

        /* Explode between root tags */
        $tag_separation = '££££';
        $content_parts = explode($tag_separation, $content);
        $last_part = count($content_parts) - 1;

        /* Merge all parts */
        $new_content = '';
        foreach ($content_parts as $i => $content_part) {
            $new_content .= $content_part;

            /* Stop after last content part */
            if ($i >= $last_part) {
                break;
            }

            /* Add separation */
            $new_content .= $tag_separation;

            /* No more sections available to insert */
            if (empty($sections)) {
                continue;
            }

            /* Stop if wrong interval between tags */
            if (!call_user_func($this->settings['callback_interval'], $i)) {
                continue;
            }

            $new_content .= array_shift($sections);
        }

        /* Replace tmp matches by tags */
        $content = $wpuMatchTags->set_html($new_content);
        $wpuMatchTags->replace_matches();
        $content = $wpuMatchTags->get_html();

        return $content;
    }

    public function check_interval($i) {
        return ($i + 1) % $this->settings['tag_interval'] == 0;
    }

    public function get_related($post_id) {
        $args = apply_filters('wpuincontentrelated__get_related__query', array(
            'post_type' => $this->settings['post_type'],
            'posts_per_page' => 20,
            'orderby' => $this->settings['shuffle_posts'] ? 'rand' : 'date',
            'post__not_in' => array($post_id)
        ));

        $posts = get_posts($args);

        $sections = array();
        foreach ($posts as $i => $_post) {
            $sections[] = $this->get_related_html($_post, $i);
        }
        return $sections;
    }

    public function get_related_html($_post, $i) {
        $html = '<section class="incontent-related">';
        $html .= '<span class="incontent-related__title">' . __('Read more :', 'wpuincontentrelated') . '</span> ';
        $html .= '<span class="incontent-related__desc"><a href="' . get_permalink($_post) . '">' . get_the_title($_post) . '</a></span>';
        $html .= '</section>';
        return apply_filters('wpuincontentrelated__get_related_html__value', $html, $_post, $i);
    }
}

$WPUIncontentRelated = new WPUIncontentRelated();
