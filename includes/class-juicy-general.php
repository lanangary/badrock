<?php
/**
 * General hooks to improve wordpress with Juicebox themes.
 */

class Juicy_General {

    /**
     * Prevent robots crawling our dev domains.
     * This is already at the server level,
     * though this provides an additional layer of security.
     */
    public function dev_robots_disallow( $output, $public ) {
        if (preg_match('/.+\.box$/', $_SERVER['HTTP_HOST'])
            || preg_match('/.+\.dev.juicebox.com.au$/', $_SERVER['HTTP_HOST'])
            || preg_match('/.+\.cloudsites.net.au$/', $_SERVER['HTTP_HOST'])) {
            $output = "User-agent: *\nDisallow: /";
        }

        return $output;
    }

    /**
     * Remove link from post update messages if post type is not publicly queryable
     */
    public function not_queryable_remove_post_update_link( $messages ) {
        $obj = get_post_type_object( get_post()->post_type );

        if( ! $obj->publicly_queryable ) {
            foreach( $messages as &$message_type ) {
                foreach( $message_type as &$message ) {
                    $message = preg_replace('/<a[^>]*>.*<\/a>/','',$message);
                }
            }
        }

        return $messages;
    }

    /**
     * Allows you to remove method for an hook when,
     * it's a class method and the class doesn't have variable,
     * but you know the class name.
     */
    public static function remove_filters_for_anonymous_class( $hook_name = '', $class_name ='', $method_name = '', $priority = 0 ) {
        global $wp_filter;
        // Take only filters on right hook name and priority
        if ( !isset($wp_filter[$hook_name][$priority]) || !is_array($wp_filter[$hook_name][$priority]) )
            return false;
        // Loop on filters registered
        foreach( (array) $wp_filter[$hook_name][$priority] as $unique_id => $filter_array ) {
            // Test if filter is an array ! (always for class/method)
            if ( isset($filter_array['function']) && is_array($filter_array['function']) ) {
                // Test if object is a class, class and method is equal to param !
                if ( is_object($filter_array['function'][0]) && get_class($filter_array['function'][0]) && get_class($filter_array['function'][0]) == $class_name && $filter_array['function'][1] == $method_name ) {
                    // Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/)
                    if( is_a( $wp_filter[$hook_name], 'WP_Hook' ) ) {
                        unset( $wp_filter[$hook_name]->callbacks[$priority][$unique_id] );
                        break;
                    }
                    else {
                        unset($wp_filter[$hook_name][$priority][$unique_id]);
                        break;
                    }
                }
            }
        }
        return false;
    }

}