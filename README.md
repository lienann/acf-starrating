# ACF Star Rating Field

Add-on to ACF plugin. Add the possibility to use "Star rating" field in ACF.

-----------------------

### Description

Plug-in provide three calculation method for voting:

1. calculate by cookies (any visitor);
2. by IP (any visitor);
3. by user id (registered users only).

If "calculated by cookies" is selected, the only users which use browser with cookies enabled will be able to vote

In field settings you can also:

1. open|close vote;
2. tune the number of stars (1 to 20);
3. specify the method of re-voting - possible(period)|never

Use the_field($field_key, $post_id) or get_field($field_key, $post_id) function
in page template for field output (see ACF documentation).

Attention! Before removing the plugin files read uninstall.php

### Compatibility

This ACF field type is compatible with:
* ACF 4

### Installation

1. Copy the `acf-starrating` folder into your `wp-content/plugins` folder
2. Activate the Star Rating Field plugin via the plugins admin page
3. Create a new field via ACF and select the Star Rating type
4. Add the_field ($field_key, $post->ID) function in the template of your theme.
Please refer to the description and FAQ (readme.txt) for more info regarding the field type
settings.

### Changelog
Please see `readme.txt` for changelog
