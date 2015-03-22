=== ACF: Star Rating Field ===
Contributors: lienann
Tags: acf, acf4, advanced custom fields, star rating, rate, rating, 5 star, post rating, user rating
Requires at least: 3.5
Tested up to: 4.1.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

"Star rating" field. Add-on to Advanced Custom Fields plugin.

== Description ==

Add the possibility to use rating field in ACF.

Plug-in provide three calculation method for voting:

1. calculate by cookies (any visitor);
2. by IP (any visitor);
3. by user id (registered users only).

If "calculated by cookies" is selected, the only users which use browser with 
cookies enabled will be able to vote

In field settings you can also:

1. open|close vote;
2. tune the number of stars (1 to 20);
3. specify the method of re-voting - possible(period)|never

Use the_field($field_key, $post_id) or get_field($field_key, $post_id) function
in page template for field output (see ACF documentation).

In admin panel the rating is inactive.

**Attention!** Before removing the plugin files read uninstall.php

**Languages:** English, Français, Русский

I apologize for possible mistakes in plugin translation.
I will be glad to accept the help with the correct translation of a plugin into
English and to correction of my mistakes.

= Gratitudes: =

Thanks to Ivan Shamshur for JS.

French Translation - thanks to Nicolas Kern.

= Compatibility =

This ACF field type is compatible with: ACF 4


For developers: https://github.com/lienann/acf-starrating

== Installation ==

1. Copy the `acf-starrating` folder into your `wp-content/plugins` folder
2. Activate the Star Rating Field plugin via the plugins admin page
3. Create a new field via ACF and select the Star Rating type
4. Add the_field ($field_key, $post->ID) function in the template of your theme.
Please refer to the description and FAQ for more info regarding the field type
settings.

== Changelog ==

= 1.0.2 =
* Added French Translation. Thanks to Nicolas Kern.

= 1.0.1 =
* Fixed bug with cookie setup.
* Updated documentations.

= 1.0.0 =
* Initial Release.

== Screenshots ==

1. "Star rating" field appearance.
2. Field settings in ACF.
3. Field settings in ACF.

== Frequently Asked Questions ==

= How to display field on the page? =

Add the_field($field_key, $post_id) or get_field($field_key, $post_id) into page
template where it is necessary for you (use $field_name only, if you are sure that field value exists):

`<?php
    // add fields in the Loop
    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            if ( function_exists( 'the_field' ) ) {
                the_field( 'quality', $post->ID );
            }
            the_content(); 
        } // end while
    } // end if
?>`
`<?php
    // display rating field for post_id=123
    if ( function_exists( 'the_field' ) ) {
        the_field( 'interest', '123' );
        the_field( 'field_62ad11se531h', '123' );
    }
?>`
`<?php
    // display rating field of user_id = 1
    // to pass $post_id value use 'user_' + user ID format
    if ( function_exists( 'get_field' ) ) {
        $field = get_field( 'field_53ac25b2e521', 'user_1' );
        echo $field;
    }
?>`

For detailed information about this functions see ACF documentation.

= How to display vote results and number of votes? =

Use get_field() function of ACF plugin (with the third option = FALSE), to display 
vote result on the page:
`<?php
    // display voting results of post_id = 123
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( 'interest', '123', FALSE );
        $avrg  = ( ! empty($value['avrg']) ? $value['avrg'] : 0 ) ;
        $votes = ( ! empty($value['votes']) ? $value['votes'] : 0 ) ;
        echo "rating: $avrg; votes: $votes";
    }
?>`
