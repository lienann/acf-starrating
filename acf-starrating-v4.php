<?php

class acf_field_starrating extends acf_field {

    // vars
    var $settings, // will hold info such as dir / path
        $defaults; // will hold default field options


    /*
    *  __construct
    *
    *  Set name / label needed for actions / filters
    *
    *  @since	3.6
    *  @date	23/01/13
    */

    function __construct()
    {
        // vars
        $this->name     = 'starrating';
        $this->label    = __('Star Rating','acf-srf');
        $this->category = __("jQuery",'acf'); // Basic, Content, Choice, etc
        $this->defaults = array(
            'number_stars'  => '5',
            'voting_rules'  => 'everyone',
            'revote'        => 'never',
            'status'        => ''
        );


        // do not delete!
        parent::__construct();


        // settings
        $this->settings = array(
            'path' => apply_filters('acf/helpers/get_path', __FILE__),
            'dir' => apply_filters('acf/helpers/get_dir', __FILE__),
            'version' => '1.0.0'
        );

    }


    /*
    *  create_options()
    *
    *  Create extra options for your field. This is rendered when editing a field.
    *  The value of $field['name'] can be used (like below) to save extra data to the $field
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field	- an array holding all the field's data
    */

    function create_options( $field )
    {
        // defaults?
        /*
        $field = array_merge($this->defaults, $field);
        */

        // key is needed in the field names to correctly save the data
        $key = $field['name'];

        // Create Field Options HTML
        ?>
        <tr class="field_option field_option_<?php echo $this->name; ?>">
                       
            <td class="label">
                <label><?php _e("Number of stars",'acf-srf'); ?></label>
                <p class="description"><?php _e("From 1 to 20",'acf-srf'); ?></p>
            </td>
            <td>
                <?php

                do_action('acf/create_field', array(
                    'type'  =>  'number',
                    'name'  =>  'fields['.$key.'][number_stars]',
                    'value' =>  (! empty($field['number_stars'])?$field['number_stars']:'5' )
                ));

                ?>
            </td>
        </tr>
               
        <tr class="field_option field_option_<?php echo $this->name; ?>">
                       
            <td class="label">
                <label><?php _e("Voting rules",'acf-srf'); ?></label>
                <p class="description"></p>
            </td>
            <td>
                <?php

		do_action('acf/create_field', array(
                    'type'	=>	'select',
                    'name'	=>	'fields['.$key.'][voting_rules]',
                    'value'	=>	$field['voting_rules'],
                    'layout'    => 'horizontal', 
                    'choices'   => array(
                        'everyone' => __("Everyone visitors (used cookie)",'acf-srf'), 
                        'everyoneip' => __("Everyone visitors (used ip)",'acf-srf'), 
                        'logged'    => __("Only logged users (used user ID)",'acf-srf')
                        )
		));                

                ?>
            </td>
        </tr>       
        
        <tr class="field_option field_option_<?php echo $this->name; ?>">
                       
            <td class="label">
                <label><?php _e("Revote",'acf-srf'); ?></label>
                <p class="description"></p>
            </td>
            <td>
                <?php

		do_action('acf/create_field', array(
                    'type'	=>	'select',
                    'name'	=>	'fields['.$key.'][revote]',
                    'value'	=>	$field['revote'],
                    'layout'    =>      'horizontal', 
                    'choices'   =>      array(
                        'never'     => __("Never",'acf-srf'), 
                        'hour'      => __("Hour later",'acf-srf'),
                        'day'       => __("Day later",'acf-srf'),
                        'week'      => __("Week later",'acf-srf'),
                        'month'     => __("Month later",'acf-srf'),
                        'year'      => __("Year later",'acf-srf')
                        )
		));                

                ?>
            </td>
        </tr>
        
        <tr class="field_option field_option_<?php echo $this->name; ?>">
                       
            <td class="label">
                <label><?php _e("Voting status",'acf-srf'); ?></label>
                <p class="description"><?php _e("Closed if the vote?",'acf-srf'); ?></p>
            </td>
            <td>
                <?php

		do_action('acf/create_field', array(
                    'type'	=>	'checkbox',
                    'name'	=>	'fields['.$key.'][status]',
                    'value'	=>	$field['status'],
                    'layout'    =>      'horizontal', 
                    'choices'   =>      array(
                        'closed'     => __("Closed",'acf-srf') 
                        )
		));                

                ?>
            </td>
        </tr>   
        
        <?php

    }


    /*
    *  create_field()
    *
    *  Create the HTML interface for your field
    *
    *  @param	$field - an array holding all the field's data
    *
    *  @type	action
    *  @since	3.6
    *  @date	23/01/13
    */


    function create_field( $field )
    {
        
        $field = array_merge($this->defaults, $field);

        // Data on the average value and the number of voters.
        if ( empty($field['value']) ) { $field['value'] = array(); }
        
        $html = acf_srf_create_field ($field['value'], $field);

        echo $html;
        
    }


    /*
    *  load_field()
    *
    *  This filter is applied to the $field after it is loaded from the database
    *
    *  @type	filter
    *  @since	3.6
    *  @date	23/01/13
    *
    *  @param	$field - the field array holding all the field options
    *
    *  @return	$field - the field array holding all the field options
    */

    function load_field( $field )
    {
        $number = (int) $field['number_stars'];
        
        // Restriction on the number of stars.                       
        if ( $number < ACF_SRF_MIN_NUMBER_OF_STARS ) {
            $number = ACF_SRF_MIN_NUMBER_OF_STARS;
        }
        elseif( $number > ACF_SRF_MAX_NUMBER_OF_STARS ) {
            $number = ACF_SRF_MAX_NUMBER_OF_STARS;
        }
        
        $field['number_stars'] = $number;
        return $field;
    }

    
    /*
    * format_value_for_api()
    *
    * This filter is applied to the $value after it is loaded from the db and before it is passed back to the API functions such as the_field
    *
    * @type filter
    * @since 3.6
    * @date 23/01/13
    *
    * @param $value - the value which was loaded from the database
    * @param $post_id - the $post_id from which the value was loaded
    * @param $field - the field array holding all the field options
    *
    * @return $value - the modified value
    */
    function format_value_for_api( $value = array(), $post_id, $field )
    {
        $field = array_merge($this->defaults, $field);

        if ( ! empty($field['key']) && substr($field['key'], 0, 6) === 'field_' ) {
            // Add post id to the settings for later use in AJAX.
            if ( ! empty($post_id) ) {
                $field['post-id'] = $post_id;
            }
            $value = acf_srf_create_field($value, $field);
        }
        return $value;
    }

}


// create field
new acf_field_starrating();

?>
