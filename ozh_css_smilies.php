<?php
/*
Plugin Name: Ozh' CSS Smilies
Plugin URI: http://planetozh.com/blog/2014/12/revisiting-wordpress-smilies-in-a-pure-css-way/
Description: &#12484;
Version: 1.0
Author: Ozh
Author URI: http://ozh.org/
*/


// Disable WordPress default smilies
remove_action( 'init', 'smilies_init', 5 );
remove_filter( 'the_content', 'convert_smilies' );
remove_filter( 'the_excerpt', 'convert_smilies' );
remove_filter( 'comment_text', 'convert_smilies', 20);

// Add our own hooks
add_action( 'init', 'ozh_css_smilies' );
add_filter( 'the_content', 'ozh_css_convert_smilies' );
add_filter( 'the_excerpt', 'ozh_css_convert_smilies' );
add_filter( 'comment_text', 'ozh_css_convert_smilies', 20);
add_action( 'wp_enqueue_scripts', 'ozh_css_smilies_style' );

/**
 * Enqueue the CSS for the cute smilies
 *
 */
function ozh_css_smilies_style() {
    wp_enqueue_style( 'ozh_css_smilies', plugins_url( 'ozh_css_smilies.css', __FILE__ ) );
}


/**
 * Define the smiley list
 *
 * Completely stolen from WordPress, the only thing that has changed is the
 * array $wpsmiliestrans that defines our new smileys
 *
 * @global array $wpsmiliestrans
 * @global array $wp_smiliessearch
 */
function ozh_css_smilies() {
    global $wpsmiliestrans, $wp_smiliessearch;

    // don't bother setting up smilies if they are disabled
    if ( !get_option( 'use_smilies' ) )
        return;

    // Possible smilies array: 'what to type' => 'class names'
    $wpsmiliestrans = array(
        '8-)' => 'icon_cool emotion_positive',
        '8-O' => 'icon_eek emotion_negative',
        ':-(' => 'icon_sad emotion_negative',
        ':-)' => 'icon_smile emotion_positive',
        ':-?' => 'icon_confused',
        ':-D' => 'icon_biggrin emotion_positive',
        ':-P' => 'icon_razz',
        ':-o' => 'icon_surprised',
        ':-x' => 'icon_mad emotion_negative',
        ':-|' => 'icon_neutral',
        ';-)' => 'icon_wink emotion_positive',
        '8O' => 'icon_eek emotion_negative',
        ':(' => 'icon_sad emotion_negative',
        ':)' => 'icon_smile emotion_positive',
        ':?' => 'icon_confused',
        ':D' => 'icon_biggrin emotion_positive',
        ':P' => 'icon_razz',
        ':o' => 'icon_surprised',
        ':x' => 'icon_mad emotion_negative',
        ':|' => 'icon_neutral',
        ';)' => 'icon_wink emotion_positive',
    );
    
    /* From this point in this function : nothing changed from WordPress */

    /*
     * NOTE: we sort the smilies in reverse key order. This is to make sure
     * we match the longest possible smilie (:???: vs :?) as the regular
     * expression used below is first-match
     */
    krsort($wpsmiliestrans);

    $spaces = wp_spaces_regexp();

    // Begin first "subpattern"
    $wp_smiliessearch = '/(?<=' . $spaces . '|^)';

    $subchar = '';
    foreach ( (array) $wpsmiliestrans as $smiley => $name ) {
        $firstchar = substr($smiley, 0, 1);
        $rest = substr($smiley, 1);

        // new subpattern?
        if ($firstchar != $subchar) {
            if ($subchar != '') {
                $wp_smiliessearch .= ')(?=' . $spaces . '|$)';  // End previous "subpattern"
                $wp_smiliessearch .= '|(?<=' . $spaces . '|^)'; // Begin another "subpattern"
            }
            $subchar = $firstchar;
            $wp_smiliessearch .= preg_quote($firstchar, '/') . '(?:';
        } else {
            $wp_smiliessearch .= '|';
        }
        $wp_smiliessearch .= preg_quote($rest, '/');
    }

    $wp_smiliessearch .= ')(?=' . $spaces . '|$)/m';
}


/**
 * Convert text smilies into the appropriate markup.
 *
 * This function is 100% what WordPress originally does, except that the function callback is changed
 *
 * @param string $text Content that probably contains text smileys.
 * @return string Converted content with text smilies replaced with markup.
 */
function ozh_css_convert_smilies( $text ) {
    global $wp_smiliessearch;
    $output = '';
    if ( get_option( 'use_smilies' ) && ! empty( $wp_smiliessearch ) ) {
        // HTML loop taken from texturize function, could possible be consolidated
        $textarr = preg_split( '/(<.*>)/U', $text, -1, PREG_SPLIT_DELIM_CAPTURE ); // capture the tags as well as in between
        $stop = count( $textarr );// loop stuff

        // Ignore proessing of specific tags
        $tags_to_ignore = 'code|pre|style|script|textarea';
        $ignore_block_element = '';

        for ( $i = 0; $i < $stop; $i++ ) {
            $content = $textarr[$i];

            // If we're in an ignore block, wait until we find its closing tag
            if ( '' == $ignore_block_element && preg_match( '/^<(' . $tags_to_ignore . ')>/', $content, $matches ) )  {
                $ignore_block_element = $matches[1];
            }

            // If it's not a tag and not in ignore block
            if ( '' ==  $ignore_block_element && strlen( $content ) > 0 && '<' != $content[0] ) {
                $content = preg_replace_callback( $wp_smiliessearch, 'ozh_css_translate_smiley', $content );
            }

            // did we exit ignore block
            if ( '' != $ignore_block_element && '</' . $ignore_block_element . '>' == $content )  {
                $ignore_block_element = '';
            }

            $output .= $content;
        }
    } else {
        // return default text.
        $output = $text;
    }
    return $output;
}

/**
 * Convert one smiley text string to the HTML markup
 *
 * @param array $matches Single match. Smiley code to convert to HTML.
 * @return string HTML string for smiley.
 */
function ozh_css_translate_smiley( $matches ) {
    global $wpsmiliestrans;
    
    if ( count( $matches ) == 0 )
        return '';

    $smiley  = trim( reset( $matches ) );
    $classes = $wpsmiliestrans[ $smiley ];
    return sprintf( '<span class="ozh_css_smiley %s"><span>%s</span></span>', esc_attr( $classes ), esc_attr( $smiley ) );
}

