<?php
/**
 * Hooks to improve the gravity forms with Juicebox themes.
 */


class Juicy_Gravity_Forms
{
    public $grid_system = 'flex';
    public $breakpoint = 'sm';
    public $btn_class = 'btn btn-default';
    public $include_styles = false;

    public function enable_cc()
    {
        return true;
    }

    /**
     * Removes gravity forms styles
     */
    public function remove_gravityforms_style()
    {
        if (!$this->include_styles) {
            wp_deregister_style("gforms_formsmain_css");
            wp_deregister_style("gforms_reset_css");
            wp_deregister_style("gforms_ready_class_css");
            wp_deregister_style("gforms_browsers_css");
        }
    }

    /**
     * Turns all inputs[type="button|submit"] into buttons
     */
    public function form_create_btns($button, $form)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($button);
        $input = $dom->getElementsByTagName('input')->item(0);

        $text = $input->getAttribute('value');
        $input->removeAttribute('value');

        $attrs = [];

        foreach ($input->attributes as $attribute) {
            $attrs[$attribute->name] = $attribute->value;
        }

        foreach ($attrs as $key => $val) {
            $attrs[$key] = "{$key}='{$val}'";
        }

        return "<button ". implode(' ', $attrs) .">{$text}</button>";
    }

    /**
     * Remove wrapping <ul>, add row to form body
     */
    public function edit_form_markup($form_string, $form)
    {
        if (is_admin()) {
            return $form_string;
        }

        $replacements = [
            'gform_fields' => 'gform_fields row',
            '<ul ' => '<div ',
            '<li ' => '<div ',
            '</ul>' => '</div>',
            '</li>' => '</div>'
        ];

        // Simply add a different class to the fields so we can use .row to help with columns
        $form_string = str_replace(array_keys($replacements), array_values($replacements), $form_string);

        return $form_string;
    }

    /**
     * Add `form-control` class to all fields
     */
    public function edit_markup_input($content, $field, $value, $lead_id, $form_id)
    {
        if (is_admin()) {
            return $content;
        }

        $function = 'handle'. ucfirst($field->type);

        if (method_exists($this, $function)) {
            return $this->{$function}($content, $field, $value, $lead_id, $form_id);
        }

        $content = preg_replace("/(<(input|textarea|select)[^>]+) class=('|\").*?('|\")/i", "$1 class=\"form-control\"", $content);

        $replace = [
            'validation_message'    => 'help-block small bold text-uppercase',
            'rows=\'10\''            => ''
        ];

        $content = str_replace(array_keys($replace), array_values($replace), $content);


        return $content;
    }

    /**
     * Add grid columns to the inputs
     */
    public function edit_markup_container($field_container, $field, $form, $css_class, $style, $field_content)
    {
        if (is_admin()) {
            return $field_container;
        }
        $classes = [];

        if (!empty($field['cssClass'])) {
            $classes[] = $field['cssClass'];
        }

        $classes[] = 'input-wrapper';
        $classes[] = $field['type'];
        $classes[] = ($this->grid_system == 'flex' ? "col {$this->breakpoint}-" : "col-{$this->breakpoint}-") . ($field->columns == '' ? '12' : $field->columns);
        $classes[] = 'form-group';

        if ($field->isRequired) {
            $classes[] = 'required';
        }

        if ($field->failed_validation !== '') {
            $classes[] = 'validated';
            $classes[] = $field->failed_validation === true ? 'has-error' : 'has-success';
        }

        if (!empty($css_class)) {
            foreach (explode(' ', $css_class) as $class) {
                $classes[] = $class;
            }
        }

        $field_container = "<div id=\"field_{$form['id']}_{$field->id}\" class=\"". implode(' ', $classes) ."\">{FIELD_CONTENT}</div>";

        if ($field->type == 'hidden') {
            return '{FIELD_CONTENT}';
        }

        return $field_container;
    }

    /**
     * Add grid columns as an option...
     * @param [type] $form_id
     */
    public function add_bootstrap_cols($placement, $form_id)
    {
        if ($placement != 300) {
            return;
        }

        global $__gf_tooltips;

        $__gf_tooltips['form_field_columns_size'] = '<h6>' . __('Grid Columns', 'gravityforms') . '</h6>' . __('How wide would you like this field to display.', 'gravityforms')

        ?>
        <li class="column_setting field_setting">
            <label for="field_columns_size">
                <?php esc_html_e('Grid Columns', 'gravityforms'); ?>
                <?php gform_tooltip('form_field_columns_size') ?>
            </label>
            <select id="field_columns_size" onchange="SetFieldProperty('columns', jQuery(this).val());">
                <option value="3"><?php esc_html_e('3/12', 'gravityforms'); ?></option>
                <option value="4"><?php esc_html_e('4/12', 'gravityforms'); ?></option>
                <option value="6"><?php esc_html_e('6/12', 'gravityforms'); ?></option>
                <option value="8"><?php esc_html_e('8/12', 'gravityforms'); ?></option>
                <option value="9"><?php esc_html_e('9/12', 'gravityforms'); ?></option>
                <option value="12"><?php esc_html_e('Full', 'gravityforms'); ?></option>
            </select>
        </li>
        <?php
    }

    public function disable_honeypot_autocomplete($input)
    {
        $validation_field = str_contains($input, 'validation purposes');

        if ($validation_field) {
            return preg_replace( '/<(input|textarea)/', '<${1} autocomplete="off" ', $input );
        }

        return $input;
    }

    public function field_settings_js()
    {
        ?>

        <script type="text/javascript">
            (function($) {
                $(document).ready(function(){
                    for( i in fieldSettings ) {
                        fieldSettings[i] += ', .column_setting';
                    }
                });

                 $(document).bind( 'gform_load_field_settings', function( event, field, form ) {
                    $('#field_columns_size').val(field.columns);
                } );
            })(jQuery);
        </script>

        <?php
    }
}
