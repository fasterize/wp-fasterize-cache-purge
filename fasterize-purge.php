<?php
/*
Plugin Name: Fasterize Cache Purge
Plugin URI: http://wordpress.org/extend/plugins/fasterize-purge/
Description: Sends purge order to URLs of changed posts/pages when they are modified. Works with Fasterize platform.
Version: 1.0.0
Author: Fasterize
Author URI: http://www.fasterize.com/
*/


class FasterizeCachePurge
{
    protected $purgeUrls = array();

    function __construct()
    {
        foreach ($this->getRegisterEvents() as $event)
        {
            add_action($event, array($this, 'purgePost'));
        }
        add_action( 'shutdown', array($this, 'executePurge') );
    }

    protected function getRegisterEvents()
    {
        return array(
            'save_post',
            'deleted_post',
            'trashed_post',
            'edit_post',
            'delete_attachment',
            'switch_theme'
        );
    }

    public function executePurge() {
        $purgeUrls = array_unique($this->purgeUrls);

        foreach($purgeUrls as $url) {
            error_log("purge $url");
            // wp_remote_get($url, array("headers" => array("Pragma" => "no-cache")));
            wp_remote_get("http://lemonde.fr", array("headers" => array("Pragma" => "no-cache")));
        }
    }

    public function purgePost($postId) {

        // If this is a valid post we want to purge the post, the home page and any associated tags & cats
        // If not, purge everything on the site.

        $validPostStatus = array("publish", "trash");
        $thisPostStatus  = get_post_status($postId);

        if ( get_permalink($postId) == true && in_array($thisPostStatus, $validPostStatus) ) {
            // Category & Tag purge based
            $categories = get_the_category($postId);
            if ( $categories ) {
                $category_base = get_option( 'category_base');
                if ( $category_base == '' )
                    $category_base = '/category/';
                $category_base = trailingslashit( $category_base );
                foreach ($categories as $cat) {
                    array_push($this->purgeUrls, home_url( $category_base . $cat->slug . '/' ) );
                }
            }
            $tags = get_the_tags($postId);
            if ( $tags ) {
                $tag_base = get_option( 'tag_base' );
                if ( $tag_base == '' )
                    $tag_base = '/tag/';
                $tag_base = trailingslashit( str_replace( '..', '', $tag_base ) );
                foreach ($tags as $tag) {
                    array_push($this->purgeUrls, home_url( $tag_base . $tag->slug . '/' ) );
                }
            }
            array_push($this->purgeUrls, get_permalink($postId) );
            array_push($this->purgeUrls, home_url() );
        }
    }
}

$purger = new FasterizeCachePurge();

?>
