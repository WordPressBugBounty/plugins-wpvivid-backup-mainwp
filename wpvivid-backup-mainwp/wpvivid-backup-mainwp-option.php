<?php

class Mainwp_WPvivid_Extension_Option
{
    private static $instance = null;
    private $table_prefix;

    static function get_instance()
    {
        if ( null == Mainwp_WPvivid_Extension_Option::$instance )
        {
            Mainwp_WPvivid_Extension_Option::$instance = new Mainwp_WPvivid_Extension_Option();
        }
        return Mainwp_WPvivid_Extension_Option::$instance;
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

    function init_options()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        if(!class_exists('dbDelta'))
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );


        $query='CREATE TABLE `'.$this->get_table_name('wpvivid').'` (
            `site_id` int(11) NOT NULL,
            `individual` int NOT NULL DEFAULT 0,
            `is_pro` int NOT NULL DEFAULT 0,
            `is_install` int NOT NULL DEFAULT 0,
            `is_login` int NOT NULL DEFAULT 0,
            `latest_version` longtext NOT NULL DEFAULT "",
            `time_zone` int NOT NULL DEFAULT 0,
            `need_update` int NOT NULL DEFAULT 0,
            `current_version` longtext NOT NULL DEFAULT "",
            `backup_custom_setting` longtext NOT NULL DEFAULT "",
            `schedule` longtext NOT NULL DEFAULT "",
            `schedule_addon` longtext NOT NULL DEFAULT "",
            `remote` longtext NOT NULL DEFAULT "",
            `remote_addon` longtext NOT NULL DEFAULT "",
            `settings_addon` longtext NOT NULL DEFAULT "",
            `report_addon` longtext NOT NULL DEFAULT "",
            `sync_remote_setting` longtext NOT NULL DEFAULT "",
            `sync_error` longtext NOT NULL DEFAULT "",
            `settings` longtext NOT NULL DEFAULT "",
            PRIMARY KEY (`site_id`) ) ' . $charset_collate;

        dbDelta( $query );


        $query='CREATE TABLE `'.$this->get_table_name('wpvivid_global').'` (
            `global` int(11) NOT NULL,
            `select_pro` longtext NOT NULL DEFAULT "",
            `sync_init_addon_first` longtext NOT NULL DEFAULT "",
            `switch_pro_setting_page` longtext NOT NULL DEFAULT "",
            `backup_custom_setting` longtext NOT NULL DEFAULT "",
            `remote` longtext NOT NULL DEFAULT "",
            `remote_addon` longtext NOT NULL DEFAULT "",
            `schedule` longtext NOT NULL DEFAULT "",
            `schedule_addon` longtext NOT NULL DEFAULT "",
            `settings_addon` longtext NOT NULL DEFAULT "",
            `login_addon` longtext NOT NULL DEFAULT "",
            `settings` longtext NOT NULL DEFAULT "",
            PRIMARY KEY (`global`) ) ' . $charset_collate;

        dbDelta( $query );
    }

    function wpvivid_update_single_option($site_id, $option, $value){
        global $wpdb;

        $data[$option] = $value;

        if($this->is_set_options( $site_id,$option )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }

    function wpvivid_get_single_option($site_id, $option, $default = false){
        global $wpdb;
        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0][$option])){
            $value = $options[0][$option];
        }
        else{
            $value = $default;
        }
        $value = maybe_unserialize( $value );
        return $value;
    }

    function sync_options($site_id,$data)
    {
        global $wpdb;
        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql ,ARRAY_A);
        if(isset($options[0])==false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }

    function set_global_options($data)
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql ,ARRAY_A);
        if(isset($options[0])==false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
        return true;
    }

    function is_vaild_child_site($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wp' ) . ' WHERE `id` = %d', $site_id );
        $options = $wpdb->get_results($sql, ARRAY_A);
        if(isset($options[0])){
            return true;
        }
        else{
            return false;
        }
    }

    function is_set_options($site_id,$option_name)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT '.$option_name.' FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql ,ARRAY_A);
        if(isset($options[0])==false)
        {
            return false;
        }
        else
        {
            if(empty($options[0]))
                return false;
            else
                return true;
        }
    }

    function delete_site($site_id)
    {
        global $wpdb;
        $sql = $wpdb->prepare( 'DELETE FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id`=%d ', $site_id );
        $wpdb->query( $sql );
    }


    function get_options($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0])){
            //$options = @unserialize($options[0]);
            return $options[0];
        }
        else
            return false;

        return $options;
    }//
    function get_individual($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['individual']))
            return $options[0]['individual'];
        else
            return false;
    }//
    function set_individual($site_id,$value)
    {
        global $wpdb;

        $data['individual']=intval($value);

        if($this->is_set_options( $site_id,'individual' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_is_pro($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['is_pro']))
            return $options[0]['is_pro'];
        else
            return false;
    }//
    function get_is_install($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['is_install']))
            return $options[0]['is_install'];
        else
            return false;
    }//
    function set_install($site_id,$value)
    {
        global $wpdb;

        $data['is_install']=intval($value);

        if($this->is_set_options( $site_id,'is_install' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_is_login($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['is_login']))
            return $options[0]['is_login'];
        else
            return false;
    }//
    function set_login($site_id,$value)
    {
        global $wpdb;

        $data['is_login']=intval($value);

        if($this->is_set_options( $site_id,'is_login' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_latest_version($site_id){
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['latest_version']))
            return $options[0]['latest_version'];
        else
            return false;
    }//
    function set_latest_version($site_id, $version){
        global $wpdb;

        $data['latest_version']=$version;

        if($this->is_set_options( $site_id,'latest_version' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function time_zone($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['time_zone']))
            return $options[0]['time_zone'];
        else
            return false;
    }//
    function get_need_update($site_id){
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['need_update']))
            return $options[0]['need_update'];
        else
            return false;
    }//
    function set_need_update($site_id,$value){
        global $wpdb;

        $data['need_update']=intval($value);

        if($this->is_set_options( $site_id,'need_update' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function set_sync_error($site_id, $value){
        global $wpdb;

        $data['sync_error']=intval($value);

        if($this->is_set_options( $site_id,'sync_error' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_current_version($site_id){
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['current_version']))
            return $options[0]['current_version'];
        else
            return false;
    }//
    function set_current_version($site_id,$version){
        global $wpdb;

        $data['current_version']=$version;

        if($this->is_set_options( $site_id,'current_version' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//

    function get_setting($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['settings']))
            $options = @unserialize($options[0]['settings']);
        else
            return false;
        return $options;
    }//
    function update_setting($site_id,$options)
    {
        global $wpdb;

        $data['settings']=serialize($options);

        if($this->is_set_options( $site_id,'settings' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_setting_addon($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['settings_addon']))
            $options = @unserialize($options[0]['settings_addon']);
        else
            return false;
        return $options;
    }//
    function update_setting_addon($site_id,$options)
    {
        global $wpdb;

        $data['settings_addon']=serialize($options);

        if($this->is_set_options( $site_id,'settings_addon' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_schedule($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['schedule']))
            $options = @unserialize($options[0]['schedule']);
        else
            return false;
        return $options;
    }//
    function update_schedule($site_id,$options)
    {
        global $wpdb;

        $data['schedule']=serialize($options);

        if($this->is_set_options( $site_id,'schedule' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_schedule_addon($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['schedule_addon']))
            $options = @unserialize($options[0]['schedule_addon']);
        else
            return false;
        return $options;
    }//
    function get_remote($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote']))
            $options = @unserialize($options[0]['remote']);
        else
            return false;
        return $options;
    }//
    public static function get_oldest_backup_id($report_list)
    {
        $oldest_id='not set';
        $oldest=0;
        foreach ($report_list as $key=>$value)
        {
            if ($oldest == 0) {
                $oldest = $value['backup_time'];
                $oldest_id = $key;
            } else {
                if ($oldest > $value['backup_time']) {
                    $oldest_id = $key;
                }
            }
        }
        return $oldest_id;
    }//
    function clean_out_of_date_report($report_list, $max_report_count)
    {
        $size=sizeof($report_list);
        while($size>$max_report_count)
        {
            $oldest_id=self::get_oldest_backup_id($report_list);

            if($oldest_id!='not set')
            {
                unset($report_list[$oldest_id]);
            }
            $new_size=sizeof($report_list);
            if($new_size==$size)
            {
                break;
            }
            else
            {
                $size=$new_size;
            }
        }
        return $report_list;
    }//
    function set_report_addon($site_id, $report_option)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['report_addon'])) {
            $options = @unserialize($options[0]['report_addon']);
            foreach ($report_option as $key => $value){
                $options[$key] = $value;
                $options = $this->clean_out_of_date_report($options, 10);
                $data['report_addon']=serialize($options);
                $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
            }
        }
        else{
            $data['site_id']=$site_id;
            foreach ($report_option as $key => $value){
                $options[$key] = $value;
                $options = $this->clean_out_of_date_report($options, 10);
                $data['report_addon']=serialize($options);
                $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
            }
        }
    }//
    function get_report_addon($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['report_addon']))
            $options = @unserialize($options[0]['report_addon']);
        else
            return false;
        return $options;
    }//
    function set_sync_remote_setting($site_id, $remote_id, $remote_option){
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['sync_remote_setting'])) {
            $options = @unserialize($options[0]['sync_remote_setting']);
            $options[$remote_id] = $remote_option;
            $data['sync_remote_setting']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
        else{
            $data['site_id']=$site_id;
            $options[$remote_id] = $remote_option;
            $data['sync_remote_setting']=serialize($options);
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
    }//
    function get_sync_remote_setting($site_id){
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['sync_remote_setting']))
            $options = @unserialize($options[0]['sync_remote_setting']);
        else
            return false;
        return $options;
    }//
    function set_backup_custom_setting($site_id, $options){
        global $wpdb;

        $data['backup_custom_setting']=serialize($options);

        if($this->is_set_options( $site_id,'backup_custom_setting' )===false)
        {
            $data['site_id']=$site_id;
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function get_backup_custom_setting($site_id)
    {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['backup_custom_setting']))
            $options = @unserialize($options[0]['backup_custom_setting']);
        else
            return false;
        return $options;
    }//

    function is_set_global_options()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql ,ARRAY_A);
        if(isset($options[0])==false)
        {
            return false;
        }
        else
        {
            if(empty($options[0]))
                return false;
            else
                return true;
        }
    }
    function get_global_setting()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['settings']))
            $options = @unserialize($options[0]['settings']);
        else
            return false;
        return $options;
    }//
    function get_global_setting_addon()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['settings_addon']))
            $options = @unserialize($options[0]['settings_addon']);
        else
            return false;
        return $options;
    }//
    function update_global_setting($options)
    {
        global $wpdb;

        $data['settings']=serialize($options);
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function update_global_setting_addon($options)
    {
        global $wpdb;

        $data['settings_addon']=serialize($options);
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_global_schedule()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['schedule']))
            $options = @unserialize($options[0]['schedule']);
        else
            return false;
        return $options;
    }//
    function get_global_schedule_addon()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['schedule_addon']))
            $options = @unserialize($options[0]['schedule_addon']);
        else
            return false;
        return $options;
    }//
    function update_global_schedule($options)
    {
        global $wpdb;

        $data['schedule']=serialize($options);
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function update_global_schedule_addon($options)
    {
        global $wpdb;

        $data['schedule_addon']=serialize($options);
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_global_remote()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote']))
            $options = @unserialize($options[0]['remote']);
        else
            return false;
        return $options;
    }//
    function get_global_remote_addon()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote_addon']))
            $options = @unserialize($options[0]['remote_addon']);
        else
            return false;
        return $options;
    }//
    function add_global_remote_addon($remote_option,$default=0)
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote_addon'])) {
            $options = @unserialize($options[0]['remote_addon']);

            $id=uniqid('wpvivid-remote-');
            $options['upload'][$id]=$remote_option;
            if($default)
            {
                $remote_ids[]=$id;
                $options['history']['remote_selected']=$remote_ids;
            }
            $data['remote_addon']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
        else {
            $options=array();
            $id=uniqid('wpvivid-remote-');
            $options['upload'][$id]=$remote_option;
            if($default)
            {
                $remote_ids[]=$id;
                $options['history']['remote_selected']=$remote_ids;
            }
            $data['remote_addon']=serialize($options);
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        return $id;
    }//
    function delete_global_remote_addon($remote_id){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote_addon']))
        {
            $options = @unserialize($options[0]['remote_addon']);

            if(isset($options['upload'][$remote_id]))
            {
                unset($options['upload'][$remote_id]);
            }

            $data['remote_addon']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function update_global_remote_addon($remote_id, $remote_option, $default){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote_addon'])) {
            $options = @unserialize($options[0]['remote_addon']);
            if(isset($options['upload'][$remote_id])){
                $options['upload'][$remote_id] = $remote_option;
            }
            $data['remote_addon']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function set_global_backup_custom_setting($options){
        global $wpdb;

        $data['backup_custom_setting']=serialize($options);
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_global_backup_custom_setting(){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['backup_custom_setting']))
            $options = @unserialize($options[0]['backup_custom_setting']);
        else
            return false;
        return $options;
    }//
    function add_global_remote($remote_option,$default=0)
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote']))
        {
            $options = @unserialize($options[0]['remote']);

            $id=uniqid('wpvivid-remote-');
            $options['upload'][$id]=$remote_option;
            if($default)
            {
                $remote_ids[]=$id;
                $options['history']['remote_selected']=$remote_ids;
            }
            $data['remote']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
        else
        {
            $options=array();
            $id=uniqid('wpvivid-remote-');
            $options['upload'][$id]=$remote_option;
            if($default)
            {
                $remote_ids[]=$id;
                $options['history']['remote_selected']=$remote_ids;
            }
            $data['remote']=serialize($options);
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
    }//?
    function delete_global_remote($remote_id)
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote']))
        {
            $options = @unserialize($options[0]['remote']);

            if(isset($options['upload'][$remote_id]))
            {
                unset($options['upload'][$remote_id]);
            }

            if(in_array($remote_id, $options['history']['remote_selected']))
            {
                $options['history']['remote_selected']=array();
            }

            $data['remote']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//?
    function update_global_remote_default($remote_id)
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['remote']))
        {
            $options = @unserialize($options[0]['remote']);

            $remote_ids[]=$remote_id;
            $options['history']['remote_selected']=$remote_ids;

            $data['remote']=serialize($options);
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//?
    function set_global_select_pro($value){
        global $wpdb;

        $data['select_pro']=$value;
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_global_select_pro(){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['select_pro']))
            return $options[0]['select_pro'];
        else
            return false;
    }//
    function set_switch_pro_setting_page($value){
        global $wpdb;
        $data['switch_pro_setting_page']=$value;
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_switch_pro_setting_page(){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['switch_pro_setting_page']))
            return $options[0]['switch_pro_setting_page'];
        else
            return false;
    }//
    function set_sync_init_addon_first($value){
        global $wpdb;

        $data['sync_init_addon_first']=$value;
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_sync_init_addon_first(){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['sync_init_addon_first']))
            return $options[0]['sync_init_addon_first'];
        else
            return false;
    }//
    function update_login_addon($options)
    {
        global $wpdb;

        $data['login_addon']=serialize($options);
        if($this->is_set_global_options()===false)
        {
            $data['global']=1;
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function get_login_addon()
    {
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['login_addon']))
            $options = @unserialize($options[0]['login_addon']);
        else
            return false;
        return $options;
    }//

    function get_wpvivid_option($site_id, $option, $default = null) {
        global $wpdb;

        $sql = $wpdb->prepare( 'SELECT * FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['wpvivid_setting']) && !empty($options[0]['wpvivid_setting'])){
            $wpvivid_settings = unserialize(base64_decode($options[0]['wpvivid_setting']));
            $value = (isset($wpvivid_settings[$option]) && !empty($wpvivid_settings[$option])) ? $wpvivid_settings[$option] : $default;
        }
        else{
            $value = $default;
        }
        return $value;
    }//
    function update_wpvivid_option($site_id, $option, $value){
        global $wpdb;

        $wpvivid_option = $this->is_set_wpvivid_setting($site_id);
        if($wpvivid_option===false)
        {
            $wpvivid_setting[$option] = serialize($value);
            $wpvivid_setting['site_id']=$site_id;
            $data = base64_encode(serialize($wpvivid_setting));
            $wpdb->insert( $this->get_table_name( 'wpvivid' ), $data );
        }
        else
        {
            $wpvivid_setting = unserialize(base64_decode($wpvivid_option['wpvivid_setting']));
            $wpvivid_setting[$option] = ($value);
            $data['wpvivid_setting'] = base64_encode(serialize($wpvivid_setting));
            $wpdb->update($this->get_table_name('wpvivid'),$data,array( 'site_id' => intval( $site_id )));
        }
    }//
    function is_set_wpvivid_setting($site_id)
    {
        global $wpdb;
        $option_name = 'wpvivid_setting';
        $sql = $wpdb->prepare( 'SELECT '.$option_name.' FROM ' . $this->get_table_name( 'wpvivid' ) . ' WHERE `site_id` = %d ', $site_id );
        $options = $wpdb->get_results( $sql ,ARRAY_A);
        if(isset($options[0])==false)
        {
            return false;
        }
        else
        {
            if(empty($options[0]))
                return false;
            else
                return $options[0];
        }
    }//
    function get_global_wpvivid_option($option, $default = null){
        global $wpdb;

        $sql = 'SELECT * FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql,ARRAY_A );
        if(isset($options[0]['wpvivid_setting']) && !empty($options[0]['wpvivid_setting'])){
            $wpvivid_settings = unserialize(base64_decode($options[0]['wpvivid_setting']));
            $value = (isset($wpvivid_settings[$option]) && !empty($wpvivid_settings[$option])) ? $wpvivid_settings[$option] : $default;
        }
        else{
            $value = $default;
        }
        return $value;
    }//
    function update_global_wpvivid_option($option, $value){
        global $wpdb;

        $wpvivid_option = $this->is_set_global_wpvivid_setting();
        if($wpvivid_option===false)
        {
            $wpvivid_setting[$option] = serialize($value);
            $wpvivid_setting['global']=1;
            $data = base64_encode(serialize($wpvivid_setting));
            $wpdb->insert( $this->get_table_name( 'wpvivid_global' ), $data );
        }
        else
        {
            $wpvivid_setting = unserialize(base64_decode($wpvivid_option['wpvivid_setting']));
            $wpvivid_setting[$option] = ($value);
            $data['wpvivid_setting'] = base64_encode(serialize($wpvivid_setting));
            $wpdb->update($this->get_table_name('wpvivid_global'),$data,array( 'global' => intval( 1 )));
        }
    }//
    function is_set_global_wpvivid_setting()
    {
        global $wpdb;
        $option_name = 'wpvivid_setting';
        $sql = 'SELECT '.$option_name.' FROM ' . $this->get_table_name( 'wpvivid_global' ) . ' WHERE `global` = 1 ';
        $options = $wpdb->get_results( $sql ,ARRAY_A);
        if(isset($options[0])==false)
        {
            return false;
        }
        else
        {
            if(empty($options[0]))
                return false;
            else
                return $options[0];
        }
    }//
}