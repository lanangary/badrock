<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.juicebox.com.au
 * @since      1.0.0
 *
 * @package    Juicy
 * @subpackage Juicy/admin/partials
 */
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">
    <h1>Juicy Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'juicy_settings' ); ?>
        <?php do_settings_sections( 'juicy' ); ?>
        <?php submit_button(); ?>
    </form>
</div>