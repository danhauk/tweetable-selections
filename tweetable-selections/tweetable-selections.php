<?php
/*
Plugin Name: Tweetable Selections
Description: This plugin makes it extremely easy for your readers to tweet bits of your content. By simply highlighting the text they want to tweet, they can compose a tweet that includes the highlighted text, link to the article, and your Twitter handle.
Author: Dan Hauk
Version: 0.1
Author URI: http://danhauk.com/
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('TWEETABLE_SELECTIONS_VERSION', '0.1');
define('TWEETABLE_SELECTIONS_URL', plugin_dir_url( __FILE__ ));

// bit.ly library
include_once( dirname(__FILE__) . '/bitly.php' );

// add the admin menus and enqueue front-end scripts
if ( is_admin() ) {
	add_action( 'admin_menu', 'tweetable_selections_menus' );
	add_action( 'admin_init', 'tweetable_selections_process' );
}
else {
	wp_register_style( 'tweetable-selections', plugins_url('tweetable-selections.css', __FILE__) );
    wp_enqueue_style( 'tweetable-selections' );
	wp_enqueue_script( 'tweetable-selections', plugins_url('tweetable-selections.js', __FILE__) );
}

// Wrap the_content in a special div to make only the_content sharable
add_action( 'the_content', 'tweetable_selection_content_div' );
function tweetable_selection_content_div( $content ) {
	return '<div class="tweetable-selection-content">'.$content.'</div>';
}

// Add twitter share button to DOM
add_action( 'wp_footer', 'tweetable_selection_share_div' );
function tweetable_selection_share_div() {
	echo '<div id="tweetable-selection"><a href="javascript:;" id="tweetable-selection--twitter" class="tweetable-selection--twitter" onclick="tweetable_selection_open_win(\'' . tweetable_selections_create_tweet() . '\');">Tweet this</a></div>';
}

// Create the default tweet
function tweetable_selections_create_tweet() {
	if ( get_option( 'tweetable_selections_shortlink' ) == 'shortlink' ) {
        $permalink = wp_get_shortlink();
    } elseif ( get_option( 'tweetable_selections_shortlink' ) == 'bitly' ) {
        $results = bitly_v3_shorten( get_permalink(), 'bit.ly' );
        $permalink = $results['url'];
    }

	$tweet_link = 'https://twitter.com/intent/tweet?url=' . urlencode($permalink);

	if ( get_option( 'tweetable_selections_username' ) != '' ) {
		$username = get_option( 'tweetable_selections_username' );
		$tweet_link .= '&via=' . $username;
	}

	if ( get_option( 'tweetable_selections_hashtag' ) != '' ) {
		$hashtag = get_option( 'tweetable_selections_hashtag' );
        $hashtag = str_replace( '#', '', $hashtag );
		$tweet_link .= '&hashtags=' . $hashtag;
	}

	return $tweet_link;
}


/* ==== ADMIN FUNCTIONS ==== */

// THIS FUNCTION CREATES THE MENU IN THE "SETTINGS" SECTION OF WORDPRESS
function tweetable_selections_menus() {

  add_options_page('Tweetable Selections', 'Tweetable Selections', 8, 'tweetableselectionsoptions', 'tweetable_selections_options');

}

// THIS FUNCTION CREATES THE OPTIONS PAGE WITH ALL OPTIONS
function tweetable_selections_options() {
?>
        <div class="tweetable_selections_admin_wrap">
            <h1><?php _e( 'Tweetable Selections' ); ?></h1>
            <p><?php _e( 'Configure the options below to customize the tweet by adding your username for attribution, a default hashtag, and selecting link style.' ); ?></p>

            <form method="post" action="options.php" id="options">
            
            <?php wp_nonce_field( 'update-options '); ?>
            <?php settings_fields( 'tweetable-selections-group' ); ?>

            <table class="form-table">
                <tr>
                    <th colspan="2"><hr></th>
                </tr>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tweetable_selections_username"><?php _e( 'Twitter username' ); ?></label>
                    </th>
                    <td>
                        @<input type="text" name="tweetable_selections_username" value="<?php echo get_option( 'tweetable_selections_username' ); ?>" />
                        <p class="description"><?php _e( 'Don\'t include "twitter.com" or "@", just the username' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tweetable_selections_hashtag"><?php _e( 'Default hashtag' ); ?></label>
                    </th>
                    <td>
                        #<input type="text" name="tweetable_selections_hashtag" value="<?php echo get_option( 'tweetable_selections_hashtag' ); ?>" />
                        <p class="description"><?php _e( 'If you want a hashtag to be added to each tweet by default, add it here' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th colspan="2"><hr></th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tweetable_selections_shortlink"><?php _e( 'Use the shortlink' ); ?></label>
                    </th>
                    <td>
                        <input type="radio" name="tweetable_selections_shortlink" id="tweetable_selections_shortlink" value="shortlink" <?php if ( get_option( 'tweetable_selections_shortlink', 'shortlink' ) == 'shortlink' ) { echo 'checked'; } ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="tweetable_selections_enable_bitly"><?php _e( 'Enable Bitly integration' ); ?></label>
                    </th>
                    <td>
                        <input type="radio" name="tweetable_selections_shortlink" id="tweetable_selections_enable_bitly" value="bitly" <?php if ( get_option( 'tweetable_selections_shortlink' ) == 'bitly' ) { echo 'checked'; } ?> />
                    </td>
                </tr>
            </table>

            <?php 
            if ( get_option( 'tweetable_selections_shortlink' ) == 'bitly' ) {
                $tweetable_selections_bitlydisplay = 'block';
            } else {
                $tweetable_selections_bitlydisplay = 'none';
            }
            ?>

            <div id="tweetable-selections-bitly-enabled" style="display:<?php echo $tweetable_selections_bitlydisplay; ?>">
                <p><?php _e( 'To connect your bit.ly account you need an API key.' ); ?></p>
                <p><strong><?php _e( 'Follow the steps below:' ); ?></strong></p>
                <ol>
                    <li><?php _e( 'Login to your Bitly account or signup at' ); ?> <a href="https://bitly.com">https://bitly.com</a></li>
                    <li><?php _e( 'Go to the OAuth applications settings page at' ); ?> <a href="https://bitly.com/a/oauth_apps">https://bitly.com/a/oauth_apps</a></li>
                    <li><?php _e( 'Confirm your password and click "Generate Token"' ); ?></li>
                    <li><?php _e( 'Add your Generic Access Token below' ); ?></li>
                </ol>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="tweetable_selections_bitly"><?php _e( 'Bitly Generic Access Token '); ?></label>
                        </th>
                        <td>
                            <input type="text" name="tweetable_selections_bitly" id="tweetable_selections_bitly" class="regular-text" value="<?php echo get_option( 'tweetable_selections_bitly' ); ?>" />
                        </td>
                    </tr>
                </table>
            </div>

            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="tweetable-selections-username" />

            <p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>" /></p>

            </form>

            <script>
            window.onload = function() {
                var tweetable_selections_bitly_check = document.getElementById('tweetable_selections_enable_bitly');
                var tweetable_selections_bitly_explain = document.getElementById('tweetable-selections-bitly-enabled');

                tweetable_selections_bitly_check.addEventListener('click', function() {
                    if ( tweetable_selections_bitly_explain.style.display != 'none') {
                        tweetable_selections_bitly_explain.style.display = 'none';
                    }
                    else {
                        tweetable_selections_bitly_explain.style.display = 'block';
                    }
                });
            }
            </script>
        </div>

<?php

}

// THIS FUNCTION SAVES THE OPTIONS FROM THE PREVIOUS FUNCTION
function tweetable_selections_process() { // whitelist options
  register_setting( 'tweetable-selections-group', 'tweetable_selections_username' );
  register_setting( 'tweetable-selections-group', 'tweetable_selections_hashtag' );
  register_setting( 'tweetable-selections-group', 'tweetable_selections_shortlink' );
  register_setting( 'tweetable-selections-group', 'tweetable_selections_bitly' );
}