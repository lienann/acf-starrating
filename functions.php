<?php

/**
 * AJAX request processing
 * 
 * @return JSON result
 */
 
function acf_srf_callback() {

    // Request processing states.
    $status_list = array(
        'OK'        => __( "Thank you. Your vote was counted.", 'acf-srf' ),
        'ERR'       => __( "Error voting.", 'acf-srf' ), 
        'ERR-R'     => __( "Sorry. You can't vote.", 'acf-srf' ) 
    );
    
    // Default request state.
    $status = 'ERR';

    $result_error = array(
        "status"    => $status,
        "msg"       => $status_list[$status],
    );    
    
    $nonce = $_REQUEST['nonce'];
    
    $score_valid_flag = 0;
    if ( ! empty($_REQUEST['score']) && $_REQUEST['score'] >= ACF_SRF_MIN_NUMBER_OF_STARS && $_REQUEST['score'] <= ACF_SRF_MAX_NUMBER_OF_STARS ) {
        $score_valid_flag = 1;
    }
    
    $ajax_referer_flag = check_ajax_referer( 'srfajax-nonce', 'nonce' );
    
    // Pre check form fields values.
    if ( $ajax_referer_flag == false || $score_valid_flag == 0 || empty($_REQUEST['post-id']) ) {
        echo json_encode( $result_error );
        exit;
    }

    $score      = (int) $_REQUEST['score'];    
    $field_id   = esc_sql($_REQUEST['vote-id']);
    
    if ( ! empty($field_id) ) {
        
        $post_id    = esc_sql($_REQUEST['post-id']);
        $field_data = get_field_object($field_id, $post_id);
        $field_key  = ( ! empty($field_data['key']) ? $field_data['key'] : '' );

        // Before upgrading, check the key field.
        if ( $field_key == $field_id && $field_data['type'] == 'starrating') {

            $number_stars = (int) $field_data['number_stars'];
         
            if ( $score > $number_stars ) {
                echo json_encode( $result_error );
                exit;
            }
            
            // Check the existence of the object.
            $existence_flag = check_existence_object($post_id);
            
            // Check the permissibility of user vote/revote.
            $permission_flag = acf_srf_check_permission($field_data, $post_id);
            
            // Possibility of restrict access to the rating.
            $access_to_voting = 1;
            $access_to_voting = apply_filters( 'acf_srf_access_to_voting', $access_to_voting, $post_id, $field_data['key'] ); 
            
            // Data updating.
            if ( $existence_flag === true && $permission_flag == 1 && $access_to_voting == 1 ) {
                
                if ( acf_srf_update_data($post_id, $field_key, $field_data['revote'], $score) ) {
                
                    $status = 'OK';
                    
                }
            }
            else {
                $status = 'ERR-R';
            }
        
        }
        
    }
        
            
    $result = array(
        "status"    => $status,
        "msg"       => $status_list[$status],
    );


    echo json_encode( $result );
    exit;
}

add_action('wp_ajax_acf_srf', 'acf_srf_callback');
add_action('wp_ajax_nopriv_acf_srf', 'acf_srf_callback');


/**
 * Create the HTML interface "star rating" field.
 * 
 * @param   array   $value - rating average value and votes count
 * @param   array   $field - field options
 * 
 * @return  string  $field - HTML interface "star rating" field
 * 
 */
function acf_srf_create_field( $value = array(), $field ) {

    $image_star_url     = plugins_url( '/images/stars.png', __FILE__ );
    $image_loader_url   = plugins_url( '/images/ajax-loader.gif', __FILE__ );        

    $number_of_stars   = ( ! empty($field['number_stars']) ? $field['number_stars']: 0 );
    
    // Votes result and count
    $avrg   = ( ! empty($value['avrg']) ? 0+$value['avrg']: 0 );
    $votes  = ( ! empty($value['votes']) ? 0+$value['votes']: 0 );

    // Rating activity flag. Default TRUE - rating inactive.
    $readOnly_flag = 1;
        
    // If you on the admin page and post-id is not defined - rating inactive.
    if ( ! is_admin() && ! empty($field['post-id']) ) {

        if ( ! empty($field['key']) ) 
            {

            $permission_flag = acf_srf_check_permission($field, $field['post-id']);

            if ( $permission_flag == 1 ) {
                $readOnly_flag = 0;
            }
            
        }     
                                
    }
    
    $rand = rand(10, 99999);
    
    $html  = '<div class="acf-input-wrap">';
    // These fields are needed for ACF to save the field value when editing
    // object (post, user etc).
    $html .= '  <input name="' . $field['name'] . '[avrg]" value="' . $avrg . '" id="' . $field['id'] . '-' . $rand . '" type="hidden"/> ';
    $html .= '  <input name="' . $field['name'] . '[votes]" value="' . $votes . '" id="' . $field['id'] . '-' . $rand . '-votes" type="hidden"/> ';    
    // These fields are used jQuery script to pass values.
    $html .= '  <div id="rating_' . $field['key'] . '-' . $rand . '">';
    $html .= '      <input name="vote-id" value="' . $field['key'] . '" type="hidden">';
    $html .= '      <input name="val" value="' . $avrg . '" type="hidden">';
    $html .= '      <input name="votes" value="' . $votes . '" type="hidden">';

    if ( ! empty($field['post-id']) ) {
        $html .= '      <input name="post-id" value="' . $field['post-id'] . '" id="post-id" type="hidden"/> ';
    }
    
    $html .= '  </div>';
    $html .= '  <script type="text/javascript" >';
    $html .= '      jQuery(function(){';
    $html .= '          jQuery("#rating_' . $field['key'] . '-' . $rand . '").rating({';
    $html .= '              fx:         "full",';
    $html .= '              readOnly:   ' . $readOnly_flag .',';
    $html .= '              image:      "' . $image_star_url .'",';
    $html .= '              loader:     "' . $image_loader_url .'",';
    $html .= '              stars:      "' . $number_of_stars .'",';
    // AJAX processing only for the active rating.
    if ( $readOnly_flag == 0 ) {
        $html .= '              url:        "srfajax.url",';
        $html .= '              callback:   function(responce){';
        $html .= '                  this.vote_success.fadeOut(2000);';
        // alert answer
        // $html .= "                  alert('Общий бал: '+this._data.val);";
        $html .= '                  }';
    }
    
    $html .= '          });';
    $html .= '      });';
    $html .= '  </script>';
    $html .= '</div>';

    return $html;

}
 

/**
 * Rating's vote time to live. 
 * 
 * @param string $revote_value - period of time when revote impossible (from
 * settings of field)
 * 
 * @return integer the value of time which has to pass before revote will be
 * allowed (in seconds)
 * 
 */

function acf_srf_get_ttl( $revote_value = 'never' ) {
    
    $ttl = YEAR_IN_SECONDS*100;
    
    $time_array = array(
        'never'     => YEAR_IN_SECONDS*100 , 
        'hour'      => HOUR_IN_SECONDS,
        'day'       => DAY_IN_SECONDS,
        'week'      => WEEK_IN_SECONDS,
        'month'     => DAY_IN_SECONDS*30,
        'year'      => YEAR_IN_SECONDS
    );
    
    $ttl = ( ! empty($time_array[$revote_value]) ? $time_array[$revote_value] : $ttl );

    return $ttl;
    
}

/**
 * 
 * Check the permissibility of user vote in current rating (and permissibility of revote).
 * 
 * @param array $field - field settings
 * @param string $post_id - ID and object type
 * 
 * @return integer permission flag
 * 
 */

function acf_srf_check_permission( $field, $post_id ) {
    
    if ( empty($field) || empty($post_id) ) return 0;
    
    if ( isset($field['status']) && isset($field['status'][0]) && $field['status'][0] == 'closed' ) {
        return 0;
    }
    
    global $wpdb;
    
    $permission_flag = 0;
    
    $current_time       = time();

    $voting_rules       = ( ! empty($field['voting_rules']) ? $field['voting_rules']: 0 );
    
    // Possibility of revote.
    $revote             = ( ! empty($field['revote']) ? $field['revote']: 'never' ); 
    
    $vote_ttl           = acf_srf_get_ttl($revote);   

    // Field key (used to copy in the log).
    $field_key_short    = str_replace('field_', '', esc_sql($field['key']));
                
    $check_user_revote_flag = 0;

    // Verify permissions for different voting rules.
    switch ( $voting_rules ) {

        // vote by cookies
        case 'everyone': 

            // Check if cookies is enabled.
            if ( empty( $_COOKIE['acf-srf_test_cookie'] ) ) break;

            
            $cookie_name = 'str-'.$field['key'];

            // Check user cookie.
            if ( ! empty($_COOKIE[$cookie_name]) && ! empty($_COOKIE[$cookie_name][$post_id]) ){  

                $cookie_value = esc_sql($_COOKIE[$cookie_name][$post_id]);

                $user_vote_data = $wpdb->get_row($wpdb->prepare("SELECT acfsrf_id, rating, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->acfsrf WHERE user_cookie = '%s' AND meta_id = '%s' AND field_key = '%s' ORDER BY timestamp LIMIT 1", $cookie_value, esc_sql($post_id), $field_key_short), ARRAY_A);    

                // If the cookie value not in the database, to allow a vote.
                if ( empty($user_vote_data) ) {
                    $permission_flag = 1;
                }
                else {
                    $check_user_revote_flag = 1;
                }
                
            }
            else {
                // If user does not have a cookie, to allow a vote.
                $permission_flag = 1;
            }

            break;

        // vote by IP address                    
        case 'everyoneip': 
            
            $ip = esc_sql($_SERVER['REMOTE_ADDR']);

            $user_vote_data = $wpdb->get_row($wpdb->prepare("SELECT acfsrf_id, rating, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->acfsrf WHERE user_ip = '%s' AND meta_id = '%s' AND field_key = '%s' ORDER BY timestamp LIMIT 1", $ip, esc_sql($post_id), $field_key_short), ARRAY_A);    
            
            // If the IP not in the database, to allow a vote.
            if ( empty($user_vote_data) ) {
                $permission_flag = 1;
            }
            else {
                $check_user_revote_flag = 1;
            }

            break;

        // vote by user ID for logged in users
        case 'logged': 
            
            if ( ! is_user_logged_in() ) return 0;
            
            $user_id = get_current_user_id();

            $user_vote_data = $wpdb->get_row($wpdb->prepare("SELECT acfsrf_id, rating, UNIX_TIMESTAMP(timestamp) as timestamp FROM $wpdb->acfsrf WHERE user_id = '%d' AND meta_id = '%s' AND field_key = '%s' ORDER BY timestamp LIMIT 1", $user_id, esc_sql($post_id), $field_key_short), ARRAY_A);    
            
            // If the User ID not in the database, to allow a vote.
            if ( empty($user_vote_data) ) {
                $permission_flag = 1;
            }
            else {
                $check_user_revote_flag = 1;
            }

            break;
    }    
    
    // Check if revote is permitted.
    if ( $check_user_revote_flag == 1 ) {
        
        $user_date_of_vote = ( ! empty($user_vote_data['timestamp']) ? $user_vote_data['timestamp'] : time() );  

        if ( $user_date_of_vote + $vote_ttl < $current_time && $vote_ttl != 0 ) {
            $permission_flag = 1;
        }
        
    }
    
    return $permission_flag;
    
}

/**
 * 
 * Check the existence of the object (only for post, user, taxonomy term and comment). Add voice will be possible only for these objects. This Function is used only to update/insert data in the database.
 * 
 * @param string $post_id - ID and object type
 * 
 * @return boolean existence flag
 * 
 */

function check_existence_object($post_id) {
    
    if ( empty($post_id) ) return FALSE;
    
    global $wpdb;
    
    $check_flag = 1;
    if ( is_numeric($post_id) ) {
        
        $res = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE ID = '%s' LIMIT 1", $post_id) );   
        $check_flag = 0;
        
    }
    elseif ( substr($post_id, 0, 5) == 'user_' ) {
        
        $tmp_id = str_replace('user_', '', $post_id);
        $res = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM $wpdb->users WHERE ID = '%s' LIMIT 1", esc_sql($tmp_id)) );
        $check_flag = 0;
        
    }
    elseif ( substr($post_id, 0, 8) == 'comment_' ) {
        
        $tmp_id = str_replace('comment_', '', $post_id);
        $res = $wpdb->get_var( $wpdb->prepare("SELECT comment_ID FROM $wpdb->comments WHERE comment_ID = '%s' LIMIT 1", esc_sql($tmp_id)) );
        $check_flag = 0;
            
    }
    elseif ( strpos($post_id, '_') != false && substr_count($post_id, '_') == 1 ) {
        
        $name = explode("_", $post_id);
        $taxonomy = esc_sql($name[0]);
        $id = esc_sql($name[1]);
        $res = $wpdb->get_var( $wpdb->prepare("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = '%s' LIMIT 1", $taxonomy) );
        if ( ! empty($res) ) {
            $res = $wpdb->get_var( $wpdb->prepare("SELECT term_taxonomy_id FROM $wpdb->term_taxonomy WHERE taxonomy = '%s' AND term_id = '%s' LIMIT 1", $taxonomy, $id) );
            $check_flag = 0;
        }
        
    }
    
    if ( ! empty($res) || $check_flag == 1 ) {
        return TRUE;
    }
    
    return FALSE;
}

/**
 * Data updating.
 * 
 * @param string $post_id       - ID and object type
 * @param string $field_key     - field key
 * @param string $revote        - repetitive vote possibility setting
 * @param string $score         - user's score
 * 
 * @return boolean status of updating
 */

function acf_srf_update_data($post_id, $field_key, $revote, $score ) {
    
    if ( empty($post_id) || empty($field_key) ) return FALSE;
    
    global $wpdb;
    
    $domain_name        = esc_html($_SERVER['SERVER_NAME']);
    $ip                 = esc_sql($_SERVER['REMOTE_ADDR']);
    $user_id            = get_current_user_id();
    $current_time       = current_time('mysql');
    // Lifetime voice setting.
    $vote_ttl           = acf_srf_get_ttl($revote); 
    // Field key (to copy the database).
    $field_key_short    = str_replace('field_', '', $field_key);

    // Data of previous votings.
    $voting_result  = (array) get_field($field_key, $post_id, FALSE);
    $avrg           = ( ! empty($voting_result['avrg'])  ? 0+$voting_result['avrg'] : 0 );
    $votes          = ( ! empty($voting_result['votes']) ? 0+$voting_result['votes']: 0 );

    // Calculate new average value using new vote data.

    $new_voting_result = array(
            'avrg'  => ($avrg * $votes + $score) / ($votes + 1),
            'votes' => $votes + 1
        );

    // Create/update cookie, if possible.
    if ( ! empty( $_COOKIE['acf-srf_test_cookie'] ) ) {

        $cookie_name            = 'str-' . $field_key;
        $cookie_element_name    = 'str-' . $field_key . '[' . $post_id . ']';

        // Remove old cookie.
        if ( ! empty($_COOKIE[$cookie_name]) && ! empty($_COOKIE[$cookie_name][$post_id]) ){ 
            setcookie( $cookie_element_name, $_COOKIE[$cookie_name][$post_id], time()- YEAR_IN_SECONDS, SITECOOKIEPATH, $domain_name);
        }

        $cookie_new_value = wp_generate_password(15, false);

        setcookie( $cookie_element_name, $cookie_new_value, time() + YEAR_IN_SECONDS, SITECOOKIEPATH, $domain_name);

    }

    // Record in the log.
    $wpdb->insert(
        $wpdb->acfsrf,
        array(  'field_key'     => $field_key_short,
                'meta_id'       => $post_id, 
                'rating'        => $score,
                'timestamp'     => $current_time,
                'user_id'       => $user_id,
                'user_ip'       => $ip,
                'user_cookie'   => $cookie_new_value
            ),
        array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    // Record the final results of voting on this field.
    update_field($field_key, $new_voting_result, $post_id);   
    
    return TRUE;
    
}