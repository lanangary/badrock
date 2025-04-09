<?php

use Timber\ImageHelper as TimberImageHelper;
use function Env\env;

class Juicy_ImageHelper extends TimberImageHelper
{
    public static function letterbox($src, $w, $h, $color = false, $force = false, $use_timber = false)
    {
        if ($src instanceof \JuiceBox\Core\Image) {
            $src = $src->src();
        }

        if (empty(env('CLOUDINARY_URL', '')) || $use_timber) {
            return parent::letterbox($src, $w, $h, strpos($color, '#')  === 0 ? $color : '#' . $color, $force);
        }

        $base_url = env('CLOUDINARY_URL') . '/image/fetch/';

        $base_filters = 'c_pad';

        $filters = $base_filters . ',b_rgb:' . str_replace('#', '', $color);

        if ($w && is_numeric($w)) {
            $filters .= ',w_' . $w;
        }

        if ($h && is_numeric($h)) {
            $filters .= ',h_' . $h;
        }

        return $base_url . $filters . '/' . $src;
    }

    public static function resize($src, $w = 0, $h = 0, $filters = 'c_fill,g_auto', $use_timber = false)
    {
        if ($src instanceof \JuiceBox\Core\Image) {
            $src = $src->src();
        }

        if (empty(env('CLOUDINARY_URL', '')) || $use_timber) {
            // Maintaining backwards compat.
            if ($filters == 'c_fill,g_auto') {
                $filters = 'center';
            }
            $crop = $filters;
            return parent::resize($src, $w, $h, $crop);
        }

        $base_url = env('CLOUDINARY_URL') . '/image/fetch/';

        // Filters to use on every image.
        $base_filters = 'f_auto,dpr_auto';

        if (!empty($filters)) {
            if ($filters == 'center') {
                $filters = 'c_fill,g_auto';
            }

            $filters .= self::ends_with($filters, '/') ? $base_filters : (',' . $base_filters);
        } else {
            $filters = $base_filters;
        }

        if ($w && is_numeric($w)) {
            $filters .= ',w_' . $w;
        }

        if ($h && is_numeric($h)) {
            $filters .= ',h_' . $h;
        }

        return $base_url . $filters . '/' . $src;
    }

    public static function ends_with($haystack, $needle){
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }
}
