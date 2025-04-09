<?php
/**
 * Adds hooks to improve Timber integration with Juicebox themes.
 */

use Timber\Timber;
use JuiceBox\Config\Menus;
use JuiceBox\Core\Menu;
use Timber\URLHelper;
use Timber\TextHelper;
use function Env\env;

class Juicy_Timber
{
    protected $MenuClass = Menu::class;

    public function timber_init()
    {
        // Set additional Timber twig directories.
        Timber::$locations = array(
            get_stylesheet_directory() . '/src/',
            get_stylesheet_directory() . '/src/JuiceBox/Modules/',
            get_stylesheet_directory() . '/src/JuiceBox/Components/'
        );
    }

    public function remove_original_timber_filters()
    {
        Juicy_General::remove_filters_for_anonymous_class('timber/twig/filters', \Timber\Twig::class, 'add_timber_filters', 10);
    }

    /* This is where you can add your own functions to twig */
    public function add_to_twig($twig)
    {
        $twig->addFunction(new Twig\TwigFunction('theme_option', function ($option) {
            return get_field($option, 'option');
        }));

        $twig->addFunction(new Twig\TwigFunction('icon', [$this, 'get_icon']));

        if (function_exists('d')) {
            $twig->addFunction(
                new \Twig\TwigFunction(
                    'd',
                    function ($var) {
                        d($var);
                    }
                )
            );
        }

        if (function_exists('dd')) {
            $twig->addFunction(
                new \Twig\TwigFunction(
                    'dd',
                    function ($var) {
                        dd($var);
                    }
                )
            );
        }

        $twig->addFunction(new Twig\TwigFunction('remote_svg', [$this, 'remote_svg']));
        $twig->addFunction(new Twig\TwigFunction('s3_svg', [$this, 'remote_svg']));
        $twig->addFunction(new Twig\TwigFunction('getAnchor', [$this, 'getAnchor']));

        add_filter( 'timber/twig/default_filters', function( $filters ) {
            return array_merge($filters, [
                'resize' => [
                  'callable' => [Juicy_ImageHelper::class, 'resize']
                ],
                'letterbox' => [
                  'callable' => [Juicy_ImageHelper::class, 'letterbox'],
                ],

                /* debugging filters */
                /* get_type filter is not found, need to investigate further */
                // 'get_type' => [
                //   'callable' => 'get_type',
                // ],
                /* Notice: [ Timber ] {{ my_object | get_class }} is deprecated since Timber version 2.0.0! Use {{ function('get_class', my_object) }} instead. */
                'get_class' => [
                  'callable' => 'get_class',
                ],

                /* other filters */
                'stripshortcodes' => [
                  'callable' => 'stripshortcodes',
                ],
                'array' => [
                  'callable' => [$this, 'to_array'],
                ],
                'excerpt' => [
                  'callable' => [$this, 'wp_trim_words'],
                ],
                'excerpt_chars' => [
                  'callable' => ['Timber\TextHelper','trim_characters'],
                ],
                'function' => [
                  'callable' => [$this, 'exec_function'],
                ],
                'pretags' => [
                  'callable' => [$this, 'twig_pretags'],
                ],
                'sanitize' => [
                  'callable' => [$this, 'sanitize_title'],
                ],
                'shortcodes' => [
                  'callable' => 'do_shortcode',
                ],
                'time_ago' => [
                  'callable' => [$this, 'time_ago'],
                ],
                'wpautop' => [
                  'callable' => [$this, 'wpautop'],
                ],
                'list' => [
                  'callable' => [$this, 'add_list_separators'],
                ],
                'pluck' => [
                  'callable' => ['Timber\Helper', 'pluck'],
                ],
                'relative' => [
                  'callable' => function ($link) { return URLHelper::get_rel_url($link, true); },
                ],
                'date' => [
                  'callable' => [$this, 'intl_date'],
                ],
                'truncate' => [
                  'callable' => function ($text, $len) { return TextHelper::trim_words($text, $len);},
                ],
                'apply_filters' => [
                  'callable' => function () {
                      $args = func_get_args();
                      $tag = current(array_splice($args, 1, 1));
                      return apply_filters_ref_array($tag, $args);
                  },
                ],
            ]);
        } );

        $twig->addFilter(new \Twig\TwigFilter('tel', function ($str) {
            return filter_var($str, FILTER_SANITIZE_NUMBER_FLOAT);
        }));

        $twig->addFilter(new \Twig\TwigFilter('google_direction_safe', function ($str) {
            $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
            $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
            return str_replace($entities, $replacements, urlencode($str));
        }));

        $twig->addFilter(new \Twig\TwigFilter('wrap_embed', [$this, 'wrap_embed']));

        return $twig;
    }

    public function getAnchor($module){
        if (!isset($module['anchor_link_title'])) {
            return;
        }

        if (!empty($module['anchor_link_title'])) {
            $id = str_slug($module['anchor_link_title']);
            return 'id="' . $id . '" ';
        }
    }

    public function add_to_context($context)
    {
        $context['WP_ENV'] = env('WP_ENV');
        $context['theme_menus'] = array();
        foreach (Menus::$menus as $key => $value) {
            $context['theme_menus'][$key] = Timber::get_menu($key);
        }

        if (function_exists('get_fields')) {
            $context['options'] = get_fields('option');
        }

        $context['is_home'] = is_home();
        $context['is_front_page'] = is_front_page();
        $context['is_logged_in'] = is_user_logged_in();

        return $context;
    }

    public function remote_svg($image)
    {
        if (! $image instanceof \JuiceBox\Core\Image) {
            return $image;
        }

        $transient_name = 'file_' . $image->id . '_contents';
        $transient_modified_name = $transient_name . '_modified';
        $file_contents_set = (false !== ($file_contents = get_transient($transient_name)));
        $file_modified_set = (false !== ($file_modified = get_transient($transient_modified_name)));

        if (! $file_contents_set || ! $file_modified_set || $file_modified != $image->post_modified) {
            // S3 files are served gzipped, so we have to uncompress them.
            $file_contents = file_get_contents('compress.zlib://' . $image->src());
            $file_modified = $image->post_modified;
            set_transient($transient_name, $file_contents);
            set_transient($transient_modified_name, $file_modified);
        }

        return $file_contents;
    }

    public function get_icon($icon)
    {
        return "<i class=\"icon-{$icon}\"></i>";
    }

    public function to_array($arr)
    {
        if (is_array($arr)) {
            return $arr;
        }
        $arr = array($arr);
        return $arr;
    }

    public function exec_function($function_name)
    {
        $args = func_get_args();
        array_shift($args);
        if (is_string($function_name)) {
            $function_name = trim($function_name);
        }
        return call_user_func_array($function_name, ($args));
    }

    /**
     *
     *
     * @param array   $matches
     * @return string
     */
    public function convert_pre_entities($matches)
    {
        return str_replace($matches[1], htmlentities($matches[1]), $matches[0]);
    }

    /**
     *
     *
     * @param string  $date
     * @param string  $format (optional)
     * @return string
     */
    public function intl_date($date, $format = null)
    {
        if ($format === null) {
            $format = get_option('date_format');
        }

        if ($date instanceof \DateTime) {
            $timestamp = $date->getTimestamp() + $date->getOffset();
        } elseif (is_numeric($date) && (strtotime($date) === false || strlen($date) !== 8)) {
            $timestamp = intval($date);
        } else {
            $timestamp = strtotime($date);
        }

        return date_i18n($format, $timestamp);
    }

    /**
     * @param int|string $from
     * @param int|string $to
     * @param string $format_past
     * @param string $format_future
     * @return string
     */
    public static function time_ago($from, $to = null, $format_past = '%s ago', $format_future = '%s from now')
    {
        $to = $to === null ? time() : $to;
        $to = is_int($to) ? $to : strtotime($to);
        $from = is_int($from) ? $from : strtotime($from);

        if ($from < $to) {
            return sprintf($format_past, human_time_diff($from, $to));
        } else {
            return sprintf($format_future, human_time_diff($to, $from));
        }
    }

    /**
     * @param array $arr
     * @param string $first_delimiter
     * @param string $second_delimiter
     * @return string
     */
    public function add_list_separators($arr, $first_delimiter = ',', $second_delimiter = 'and')
    {
        $length = count($arr);
        $list = '';
        foreach ($arr as $index => $item) {
            if ($index < $length - 2) {
                $delimiter = $first_delimiter.' ';
            } elseif ($index == $length - 2) {
                $delimiter = ' '.$second_delimiter.' ';
            } else {
                $delimiter = '';
            }
            $list = $list.$item.$delimiter;
        }
        return $list;
    }

    /**
     * Filter for adding wrappers around oEmbeds
     */
    public function wrap_embed($html)
    {
        $html = preg_replace('/(width|height|frameborder|scrolling)="[a-z0-9]*"\s/i', "", $html); // Strip width, height, frameborder, scrolling #1
        $html = preg_replace('/(webkitallowfullscreen mozallowfullscreen)\s/i', "", $html); // Strip vendor attributes

        return '<div class="embed-responsive">' . $html . '</div>'; // Wrap in div element and return #3 and #4
    }
}
