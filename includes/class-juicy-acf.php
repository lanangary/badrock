<?php
/**
 * Hooks to improve the acf integration with Juicebox themes.
 */

use JuiceBox\Core\Image;
use JuiceBox\Core\Term;
use JuiceBox\Core\Post;
use function Env\env;

class Juicy_ACF
{
    public $PostClass = Post::class;
    public $ImageClass = Image::class;
    public $TermClass = Term::class;

    /**
     * If post field is set to return an ID turn it into an Post class.
     */
    public function field_to_jb_post($value, $post_id, $field)
    {
        if ($field['return_format'] == 'id' && $value !== false && !empty($value)) {
            if (!is_array($value)) {
                return new $this->PostClass($value);
            } else {
                foreach ($value as &$val) {
                    $val = new $this->PostClass($val);
                }
            }
        }

        return $value;
    }

    /**
     * If image field is set to return an ID turn it into an Image class.
     */
    public function field_to_jb_image($value, $post_id, $field)
    {
        if ($field['return_format'] == 'id' && $value !== false && !empty($value)) {
            return new $this->ImageClass($value);
        }

        return $value;
    }

    /**
     * If term field is set to return an ID turn it into an Term class.
     */
    public function field_to_jb_term($value, $term_id, $field)
    {
        if (is_array($value)) {
            if ($field['return_format'] == 'id') {
                $values = [];
                foreach ($value as $val) {
                    $values[] = new $this->TermClass($val);
                }
                return $values;
            }
        } else {
            if ($field['return_format'] == 'id' && $value !== false && !empty($value)) {
                return new $this->TermClass($value);
            }
        }

        return $value;
    }

    /**
     * Convert ACF gallery to an array of Image objects.
     */
    public function field_to_jb_gallery($value, $post_id, $field)
    {
        if ($value !== false && !empty($value)) {
            foreach ($value as &$image) {
                $image = new $this->ImageClass($image['ID']);
            }
        }

        return $value;
    }

    /**
     * Add some additional return values to color swatch fields.
     */
    public function colour_swatch_array_format($value, $post_id, $field)
    {
        if ($field['return_format'] == 'array' && is_array($value)) {
            $value['hex'] = $value['value'];
            $value['class'] = str_slug($value['label']);
        }

        return $value;
    }

    /**
     * Add Google Maps API Key.
     */
    public function my_acf_google_map_api($api)
    {
        $api['key'] = env('GOOGLE_MAPS_API_KEY');

        return $api;
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
