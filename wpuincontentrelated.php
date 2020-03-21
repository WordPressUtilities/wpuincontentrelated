<?php

/*
Plugin Name: WPU Incontent Related
Plugin URI: https://github.com/WordPressUtilities/WPUIncontentRelated
Description: Links to related posts in content
Version: 0.5.0
Author: Darklg
Author URI: http://darklg.me/
License: MIT License
License URI: http://opensource.org/licenses/MIT
Dependencies: WPUPostMetas
*/

include dirname(__FILE__) . '/inc/classes/wpumatchtags.class.php';

class WPUIncontentRelated {
    private $wp_head_end = false;
    private $sections = array();
    private $settings = array(
        'tag_start' => 3,
        'tag_interval' => 3,
        'tag_separation' => '££££',
        'post_type' => 'post',
        'shuffle_posts' => false
    );

    public function __construct() {
        add_filter('plugins_loaded', array(&$this, 'plugins_loaded'));
    }

    public function plugins_loaded() {
        $this->settings['callback_interval'] = array(&$this, 'check_interval');
        $this->settings = apply_filters('wpuincontentrelated__settings', $this->settings);
        add_action('wp_head', array(&$this, 'wp_head'), 999);
        add_filter('the_content', array(&$this, 'the_content'), 999);

        add_filter('wputh_post_metas_boxes', array(&$this, 'wputh_post_metas_boxes'), 10, 3);
        add_filter('wputh_post_metas_fields', array(&$this, 'wputh_post_metas_fields'), 10, 3);

    }

    public function wp_head() {
        $this->wp_head_end = true;
    }

    public function the_content($content) {
        if ((!is_main_query() || is_admin()) || !is_single()) {
            return $content;
        }

        if (!$this->wp_head_end) {
            return $content;
        }

        $post_id = get_the_ID();
        $disable_incontent_related = get_post_meta($post_id, 'wpuincontentrelated_disable_for_post', 1);
        if ($disable_incontent_related == '1') {
            return $content;
        }

        if (apply_filters('wpuincontentrelated__get_related__disable_for_post', false, $post_id)) {
            return $content;
        }

        $sections = $this->get_related($post_id);

        /* Isolate only root tags and keep only temp matches */
        $wpuMatchTags = new wpuMatchTags($content);
        $wpuMatchTags->replace_tags();
        $content = $wpuMatchTags->get_html();

        /* Explode between root tags */
        $content_parts = explode($this->settings['tag_separation'], $content);
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
            $new_content .= $this->settings['tag_separation'];

            /* No more sections available to insert */
            if (empty($sections)) {
                continue;
            }

            /* Stop if wrong interval between tags */
            if (!call_user_func($this->settings['callback_interval'], $i)) {
                continue;
            }

            $_section = array_shift($sections);
            $new_content .= $_section['html'];
        }

        /* Replace tmp matches by tags */
        $content = $wpuMatchTags->set_html($new_content);
        $wpuMatchTags->replace_matches();
        $content = $wpuMatchTags->get_html();

        $this->sections = $sections;

        return $content;
    }

    public function get_sections() {
        return $this->sections;
    }

    public function check_interval($i) {
        return (($i - $this->settings['tag_start'] + 1) % $this->settings['tag_interval'] == 0);
    }

    public function get_related($post_id) {
        $args = array(
            'post_type' => $this->settings['post_type'],
            'posts_per_page' => 20,
            'orderby' => $this->settings['shuffle_posts'] ? 'rand' : 'date',
            'post__not_in' => array($post_id)
        );

        $excluded_cats = apply_filters('wpuincontentrelated__get_related__excluded_cats', array());
        $categ = get_the_category($post_id);
        if (is_array($categ)) {
            foreach ($categ as $_cat_item) {
                if (in_array($_cat_item->term_id, $excluded_cats)) {
                    continue;
                }
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'category',
                        'terms' => $_cat_item->term_id
                    )
                );
            }
        }

        $args = apply_filters('wpuincontentrelated__get_related__query', $args);

        $posts = apply_filters('wpuincontentrelated__get_related__posts', get_posts($args));

        $sections = array();
        foreach ($posts as $i => $_post) {
            $sections[] = array(
                'i' => $i,
                'post' => $_post,
                'html' => $this->get_related_html($_post, $i)
            );
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

    /* ----------------------------------------------------------
      Admin
    ---------------------------------------------------------- */

    public function wputh_post_metas_boxes($boxes) {
        $boxes['box_wpuincontentrelated'] = array(
            'name' => 'WPU Incontent Related',
            'post_type' => $this->settings['post_type']
        );
        return $boxes;
    }

    public function wputh_post_metas_fields($fields) {
        $fields['wpuincontentrelated_disable_for_post'] = array(
            'box' => 'box_wpuincontentrelated',
            'type' => 'select',
            'name' => 'Disable',
            'help' => 'Hide InContent related for this post'
        );
        return $fields;
    }

}

$WPUIncontentRelated = new WPUIncontentRelated();
