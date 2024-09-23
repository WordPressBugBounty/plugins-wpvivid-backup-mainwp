<?php

class Mainwp_WPvivid_Extension_DB_Option
{
    private static $instance = null;
    private $table_prefix;

    static function get_instance()
    {
        if ( null == Mainwp_WPvivid_Extension_DB_Option::$instance )
        {
            Mainwp_WPvivid_Extension_DB_Option::$instance = new Mainwp_WPvivid_Extension_DB_Option();
        }
        return Mainwp_WPvivid_Extension_DB_Option::$instance;
    }

    function __construct()
    {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'mainwp_';
    }

    function get_table_name( $suffix )
    {
        return $this->table_prefix . $suffix;
    }

    function init_db_options()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        if(!class_exists('dbDelta'))
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $query='CREATE TABLE `'.$this->get_table_name('wpvividmeta').'` (
            `meta_id` bigint(20) unsigned NOT NULL auto_increment,
            `site_id` int(11) NOT NULL,
            `option_name` longtext NOT NULL DEFAULT "",
            `option_value` longtext NOT NULL DEFAULT "",
            PRIMARY KEY (`meta_id`) ) ' . $charset_collate;

        dbDelta( $query );


        $query='CREATE TABLE `'.$this->get_table_name('wpvivid_global_options').'` (
            `option_id` bigint(20) unsigned NOT NULL auto_increment,
            `option_name` longtext NOT NULL DEFAULT "",
            `option_value` longtext NOT NULL DEFAULT "",
            PRIMARY KEY (`option_id`) ) ' . $charset_collate;

        dbDelta( $query );
    }

    function import_settings()
    {
        global $wpdb;
        $sql_site_id = 'SELECT site_id FROM ' . $this->get_table_name( 'wpvivid' ) . ' GROUP BY site_id';
        $option_site_id = $wpdb->get_results( $sql_site_id,ARRAY_A );
        if(isset($option_site_id) && !empty($option_site_id)){
            foreach ($option_site_id as $key => $value){
                $site_id = $value['site_id'];
                $is_imported = $this->wpvivid_get_option($site_id, 'is_imported', false);
                if(!$is_imported) {
                    $need_import_key = array('backup_custom_setting', 'schedule', 'schedule_addon', 'remote', 'remote_addon', 'settings', 'settings_addon', 'report_addon', 'sync_remote_setting');
                    $sql = 'SELECT * FROM ' . $this->get_table_name('wpvivid') . ' WHERE `site_id` = ' . $site_id;
                    $options = $wpdb->get_results($sql, ARRAY_A);
                    if (isset($options[0]) && !empty($options[0])) {
                        foreach ($need_import_key as $option_name) {
                            if (isset($options[0][$option_name])) {
                                $this->wpvivid_update_option($site_id, $option_name, maybe_unserialize($options[0][$option_name]));
                            }
                        }
                    }
                    $this->wpvivid_update_option($site_id, 'is_imported', 1);
                }
            }
        }
    }

    function wpvivid_update_option($site_id, $option, $value)
    {
        $value = maybe_serialize($value);
        global $wpdb;
        $table_name = $this->get_table_name('wpvividmeta');
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $table_name WHERE site_id = %d AND option_name = %s LIMIT 1", $site_id, $option ) );
        if ( is_object( $row ) ) {
            $data['option_value'] = $value;
            $wpdb->update($this->get_table_name('wpvividmeta'), $data, array( 'site_id' => $site_id, 'option_name' => $option ));
        }
        else{
            $data['site_id'] = $site_id;
            $data['option_name'] = $option;
            $data['option_value'] = $value;
            $wpdb->insert( $this->get_table_name( 'wpvividmeta' ), $data );
        }
    }

    function wpvivid_get_option($site_id, $option, $default = false)
    {
        global $wpdb;
        $table_name = $this->get_table_name('wpvividmeta');
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $table_name WHERE site_id = %d AND option_name = %s LIMIT 1", $site_id, $option ) );
        if ( is_object( $row ) ) {
            $value = $row->option_value;
        }
        else{
            $value = $default;
        }
        $value = maybe_unserialize( $value );
        return $value;
    }

    function wpvivid_sync_options($site_id, $options)
    {
        $is_imported = $this->wpvivid_get_option($site_id, 'is_imported', false);
        if($is_imported) {
            foreach ($options as $key => $value) {
                $this->wpvivid_update_option($site_id, $key, $value);
            }
        }
    }

    function import_global_settings()
    {
        $is_imported = $this->wpvivid_get_global_option('is_imported', false);
        if(!$is_imported){
            $need_import_key = array('global', 'select_pro', 'sync_init_addon_first', 'switch_pro_setting_page', 'backup_custom_setting', 'remote', 'remote_addon', 'schedule',
                'schedule_addon', 'settings_addon', 'login_addon', 'settings');
            global $wpdb;
            $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
            $options = $wpdb->get_results( $sql,ARRAY_A );
            if(isset($options[0]) && !empty($options[0])){
                foreach ($need_import_key as $option_name) {
                    if (isset($options[0][$option_name])) {
                        $this->wpvivid_update_global_option($option_name, maybe_unserialize($options[0][$option_name]));
                    }
                }
            }
            $this->wpvivid_update_global_option('is_imported', 1);
        }
    }

    function wpvivid_update_global_option($option, $value)
    {
        $value = maybe_serialize($value);
        global $wpdb;
        $table_name = $this->get_table_name('wpvivid_global_options');
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $table_name WHERE option_name = %s LIMIT 1", $option ) );
        if ( is_object( $row ) ) {
            $data['option_value'] = $value;
            $wpdb->update($this->get_table_name('wpvivid_global_options'), $data, array( 'option_name' => $option ));
        }
        else{
            $data['option_name'] = $option;
            $data['option_value'] = $value;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global_options' ), $data );
        }
    }

    function wpvivid_get_global_option($option, $default = false)
    {
        global $wpdb;
        $table_name = $this->get_table_name('wpvivid_global_options');
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $table_name WHERE option_name = %s LIMIT 1", $option ) );
        if ( is_object( $row ) ) {
            $value = $row->option_value;
        }
        else{
            $value = $default;
        }
        $value = maybe_unserialize( $value );
        return $value;
    }

    function wpvivid_first_init_schedule_to_module()
    {
        $first_init_mould = $this->wpvivid_get_global_option('init_schedule_mould_first', false);
        if(empty($first_init_mould)){
            $global_schedules = $this->wpvivid_get_global_option('schedule_addon', array());
            if(!empty($global_schedules)){
                $schedule_mould = array();
                $schedule_mould_name = 'schedule-mould-1';
                $schedule_mould[$schedule_mould_name] = $global_schedules;
                $this->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);
            }
            $this->wpvivid_update_global_option('init_schedule_mould_first', 1);
        }
    }
}