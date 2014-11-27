<?php

/* 
 * Please use additional SQL queries in block 1 OR block 2 below in the text to delete plugin data.
 */

if( ! defined('WP_UNINSTALL_PLUGIN') )
    exit();

global $wpdb;
$wpdb->acfsrf = $wpdb->prefix.'acfsrf';

/**
 * Block 1.
 * Warning! Use carefully.
 * Uncomment strings below "begin of block 1" before uninstall, if you want to delete plugin data from database. Plugin data stored in tables: postmeta, usermeta, options.
 */

/*
 * begin of block 1
 */

/*
$res = $wpdb->get_var("DELETE FROM meta2 "
        . "USING $wpdb->usermeta meta, $wpdb->acfsrf arf, $wpdb->usermeta meta2 "
        . "WHERE arf.meta_id = CONCAT('user_', meta2.user_id) "
        . "AND meta.meta_value = CONCAT('field_', arf.field_key) "
        . "AND ( meta.meta_key =  CONCAT('_', meta2.meta_key) OR meta.meta_key = meta2.meta_key ) ");

$res = $wpdb->get_var("DELETE FROM meta2 "
        . "USING $wpdb->postmeta meta, $wpdb->acfsrf arf, $wpdb->postmeta meta2 "
        . "WHERE arf.meta_id = meta2.post_id "
        . "AND meta.meta_value = CONCAT('field_', arf.field_key) "
        . "AND ( meta.meta_key =  CONCAT('_', meta2.meta_key) OR meta.meta_key = meta2.meta_key ) ");

$res = $wpdb->get_var("DELETE FROM meta2 "
        . "USING $wpdb->options meta, $wpdb->acfsrf arf, $wpdb->options meta2 "
        . "WHERE arf.meta_id NOT LIKE 'user_%' "
        . "AND arf.meta_id LIKE '_%' "
        . "AND LOCATE(arf.meta_id+'_', meta2.option_name, 1) <= 2 "
        . "AND meta.option_value = CONCAT('field_', arf.field_key) "
        . "AND ( meta.option_name =  CONCAT('_', meta2.option_name) OR meta.option_name = meta2.option_name ) ");
 * 
 */
 

/*
 * end of block 1
 */
        

/**
 * Block 2
 * Warning! Use carefully.
 * Uncomment strings below "begin of block 2" before uninstall, if you want to delete plugin data from database using API ACF and Wordpress.
 * Use this block only if server have enough resources to complete operation without server overload.
 */

/*
 * begin of block 2
 */

/*
$results = $wpdb->get_results("SELECT DISTINCT meta_id, field_key "
        . "FROM $wpdb->acfsrf");

foreach ($results as $row) {
    do_action('acf/delete', $row->meta_id, 'field_' . $row->field_key);
}
 * 
 */
        
/*
 * end of block 2
 */
               
        
// Remove statistics table.   
$res = $wpdb->get_var("DROP TABLE $wpdb->acfsrf");