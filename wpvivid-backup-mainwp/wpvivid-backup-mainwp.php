<?php

/**
 * Plugin Name: WPvivid Backup MainWP
 * Plugin URI: https://mainwp.com/
 * Description: WPvivid Backup for MainWP enables you to create and download backups of a specific child site, set backup schedules, connect with your remote storage and set settings for all of your child sites directly from your MainWP dashboard.
 * Version: 0.9.37
 * Author: WPvivid Team
 * Author URI: https://wpvivid.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/copyleft/gpl.html
 * Documentation URI: https://docs.wpvivid.com/wpvivid-backup-for-mainwp.html
 */

define('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR',dirname(__FILE__));
define('MAINWP_WPVIVID_EXTENSION_PLUGIN_URL',plugins_url('',__FILE__));
define('MAINWP_WPVIVID_SUCCESS','success');
define('MAINWP_WPVIVID_FAILED','failed');

use MainWP\Dashboard;

class Mainwp_WPvivid_Extension_Activator
{
    protected $plugin_handle = 'wpvivid-backup-mainwp';
    protected $product_id = 'WPvivid Backup MainWP';
    protected $version = '0.9.37';
    protected $childEnabled;
    public $childKey;
    public $childFile;
    protected $mainwpMainActivated;

    public $remote;

    public $login;
    public $setting;
    public $dashboard;
    public $schedule;
    public $incremental_schedule;
    public $white_label;
    public $remote_page;
    public $capability;
    public $backup_page;
    public $backup_restore_page;
    private $mainwp_wpvivid_backups_db_version = '1.0';

    public function __construct()
    {
        $this->load_dependencies();

        $this->remote=new Mainwp_WPvivid_Remote_collection();
        $this->childFile = __FILE__;
        add_filter( 'mainwp_getextensions', array( &$this, 'get_this_extension' ) );
        add_action( 'admin_init', array( &$this, 'admin_init' ) );

        $primary_backup         = get_option( 'mainwp_primaryBackup', null );
        if ( 'wpvivid' == $primary_backup ) {
            add_filter( 'mainwp_managesites_getbackuplink', array( $this, 'managesites_backup_link' ), 10, 2 );
        }

        $this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', false );
        if ( $this->mainwpMainActivated !== false )
        {
            $this->activate_this_plugin();
        } else {
            add_action( 'mainwp_activated', array( &$this, 'activate_this_plugin' ) );
        }

        $this->init_database();

        $this->load_ajax_hook();
        add_filter( 'mainwp_getsubpages_sites', array( &$this, 'managesites_subpage' ), 10, 1 );
        add_filter( 'mainwp_sync_others_data', array( $this, 'sync_others_data' ), 10, 2 );
        add_action( 'mainwp_site_synced', array( $this, 'synced_site' ), 10, 2 );

        //add_filter( 'mainwp-sync-extensions-options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );

        add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 10, 1 );
        add_filter( 'mainwp_getprimarybackup_methods', array( $this, 'primary_backups_method' ), 10, 1 );

        add_filter('mwp_wpvivid_set_schedule_notice', array($this, 'set_schedule_notice'), 10, 2);
        add_filter('mwp_wpvivid_add_remote_storage_list', array( $this, 'add_remote_storage_list' ), 10);

        add_filter( 'mainwp_plugins_install_checks', array( $this, 'wpvivid_mainwp_plugins_install_checks' ), 10, 1 );

        if(!defined( 'DOING_CRON' ))
        {
            if(wp_get_schedule('mwp_wpvivid_check_version_event')===false)
            {
                wp_schedule_event(time()+10, 'hourly', 'mwp_wpvivid_check_version_event');
            }
            if(wp_get_schedule('mwp_wpvivid_refresh_latest_pro_version_event')===false)
            {
                wp_schedule_event(time()+10, 'daily', 'mwp_wpvivid_refresh_latest_pro_version_event');
            }
        }
    }

    public function wpvivid_mainwp_plugins_install_checks($plugins)
    {
        global $mainwp_wpvivid_extension_activator;

        $select_pro=$mainwp_wpvivid_extension_activator->get_global_select_pro();

        if($select_pro)
        {
        }
        else
        {
            $plugins[] = array(
                'page' => 'Extensions-Wpvivid-Backup-Mainwp',
                'slug' => 'wpvivid-backuprestore/wpvivid-backuprestore.php',
                'name' => 'WPvivid Backup Plugin',
            );
        }

        return $plugins;
    }

    public function managesites_backup_link( $input, $site_id )
    {
        if ( $site_id )
        {
            $last_backup = 'Never';
            $report = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'report_addon', array());
            if(isset($report) && !empty($report)){
                usort($report, function($a, $b){
                    if($a['backup_time'] === $b['backup_time']){
                        return 0;
                    }
                    else if($a['backup_time'] > $b['backup_time']){
                        return -1;
                    }
                    else{
                        return 1;
                    }
                });

                $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'time_zone', '');
                if(empty($time_zone)){
                    $time_zone = 0;
                }

                foreach ($report as $task_id => $report_option) {
                    if($report_option['status'] === 'Succeeded') {
                        //$last_backup = date("H:i:s - m/d/Y", $report_option['backup_time']);
                        $last_backup = date("F d, Y H:i", $report_option['backup_time'] + $time_zone * 60 * 60);
                        break;
                    }
                }
                $output = $last_backup . '<br />';
            }
            else{
                $last_backup = 'Never';
                $output = '<span class="mainwp-red">Never</span><br/>';
            }

            if ( mainwp_current_user_can( 'dashboard', 'execute_backups' ) ) {
                $output .= sprintf( '<a href="admin.php?page=ManageSitesWPvivid&id=%s">' . __( 'Backup Now', 'mainwp' ) . '</a>', $site_id );
            }
            return $output;
        }
        else {
            return $input;
        }
    }

    public function wpvivid_cron_schedules($schedules)
    {
        if(!isset($schedules["hourly"])){
            $schedules["hourly"] = array(
                'interval' => 3600,
                'display' => __('Once Hourly'));
        }
        return $schedules;
    }

    public function load_dependencies()
    {
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. 'wpvivid-backup-mainwp-setting.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. 'wpvivid-backup-mainwp-subpage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. 'wpvivid-backup-mainwp-option.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. 'wpvivid-backup-mainwp-db-option.php';

        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-backupmanager.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-backuprestorepage.php';

        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/includes/class-wpvivid-mainwp-connect-server.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/includes/class-wpvivid-crypt.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/includes/class-wpvivid-remote-collection.php';

        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-loginpage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-settingpage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-dashboardpage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-schedulepage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-incremental-backup.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-white-label.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-remotepage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-capabilitypage.php';
        include_once dirname(__FILE__) .DIRECTORY_SEPARATOR. '/admin/wpvivid-backup-mainwp-backuppage.php';

        $this->login=new Mainwp_WPvivid_Extension_LoginPage();
        $this->setting=new Mainwp_WPvivid_Extension_SettingPage();
        $this->dashboard=new Mainwp_WPvivid_Extension_DashboardPage();
        $this->schedule=new Mainwp_WPvivid_Extension_SchedulePage();
        $this->incremental_schedule=new Mainwp_WPvivid_Extension_Incremental_Backup();
        $this->white_label=new Mainwp_WPvivid_Extension_White_Label();
        $this->remote_page=new Mainwp_WPvivid_Extension_RemotePage();
        $this->capability=new Mainwp_WPvivid_Extension_Capability();
        $this->backup_page=new Mainwp_WPvivid_Extension_BackupPage();
        $this->backup_restore_page=new Mainwp_WPvivid_Extension_BackupRestorePage();
    }

    public function load_ajax_hook()
    {
        add_action('wp_ajax_mwp_wpvivid_switch_pro_setting', array($this, 'switch_pro_setting'));
        add_action('wp_ajax_mwp_wpvivid_set_individual', array( $this, 'set_individual'));

        //check pro need update
        add_action('mwp_wpvivid_check_version_event',array( $this,'mwp_wpvivid_check_version_event'));
        add_action('mwp_wpvivid_refresh_latest_pro_version_event',array($this, 'mwp_wpvivid_refresh_latest_pro_version_event'));

        add_filter('mwp_wpvivid_custom_backup_data_transfer', array($this, 'mwp_wpvivid_custom_backup_data_transfer'), 10, 3);
    }

    public function init_database()
    {
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->import_settings();
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->import_global_settings();

        $currentVersion = get_site_option( 'mainwp_wpvivid_backups_db_version' );

        $query_wpvividmeta = $this->query("SHOW TABLES LIKE '" . Mainwp_WPvivid_Extension_DB_Option::get_instance()->get_table_name('wpvividmeta') . "'");
        if ( @self::num_rows( $query_wpvividmeta ) == 0 )
        {
            $currentVersion = false;
        }

        $query_wpvivid_global_options = $this->query("SHOW TABLES LIKE '" . Mainwp_WPvivid_Extension_DB_Option::get_instance()->get_table_name('wpvivid_global_options') . "'");
        if ( @self::num_rows( $query_wpvivid_global_options ) == 0 )
        {
            $currentVersion = false;
        }

        $query_wpvivid = $this->query("SHOW TABLES LIKE '" . Mainwp_WPvivid_Extension_Option::get_instance()->get_table_name('wpvivid') . "'");
        if ( @self::num_rows( $query_wpvivid ) == 0 )
        {
            $currentVersion = false;
        }

        $query_wpvivid_global = $this->query("SHOW TABLES LIKE '" . Mainwp_WPvivid_Extension_Option::get_instance()->get_table_name('wpvivid_global') . "'");
        if ( @self::num_rows( $query_wpvivid_global ) == 0 )
        {
            $currentVersion = false;
        }

        if ( $currentVersion == $this->mainwp_wpvivid_backups_db_version )
        {
            return;
        }

        Mainwp_WPvivid_Extension_Option::get_instance()->init_options();
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->init_db_options();

        update_option('mainwp_wpvivid_backups_db_version', $this->mainwp_wpvivid_backups_db_version);
    }

    public static function use_mysqli() {
        /** @var $wpdb wpdb */
        if ( ! function_exists( 'mysqli_connect' ) ) {
            return false; }

        global $wpdb;
        return ( $wpdb->dbh instanceof mysqli );
    }

    public static function num_rows( $result ) {
        if ( $result === false ) {
            return 0;
        }
        if ( self::use_mysqli() ) {
            return mysqli_num_rows( $result );
        } else {
            return mysql_num_rows( $result );
        }
    }

    public function query( $sql ) {
        if ( null == $sql ) {
            return false; }
        /** @var $wpdb wpdb */
        global $wpdb;
        $result = @self::_query( $sql, $wpdb->dbh );

        if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
            return false; }
        return $result;
    }

    public static function _query( $query, $link ) {
        if ( self::use_mysqli() ) {
            return mysqli_query( $link, $query );
        } else {
            return mysql_query( $query, $link );
        }
    }

    public function sync_others_data( $data, $pWebsite = null )
    {
        if ( ! is_array( $data ) )
        {
            $data = array();
        }

        $data['syncWPvividData'] = 1;

        return $data;
    }

    public function handle_custom_tree_data($options){
        if(isset($options['uploads_option']['exclude_uploads_list']) && !empty($options['uploads_option']['exclude_uploads_list'])){
            foreach ($options['uploads_option']['exclude_uploads_list'] as $key => $value){
                if($value['type'] === 'wpvivid-custom-li-folder-icon'){
                    $value['type'] = 'mwp-wpvivid-custom-li-folder-icon';
                }
                else if($value['type'] === 'wpvivid-custom-li-file-icon'){
                    $value['type'] = 'mwp-wpvivid-custom-li-file-icon';
                }
                $options['uploads_option']['exclude_uploads_list'][$key] = $value;
            }
        }
        if(isset($options['content_option']['exclude_content_list']) && !empty($options['content_option']['exclude_content_list'])){
            foreach ($options['content_option']['exclude_content_list'] as $key => $value){
                if($value['type'] === 'wpvivid-custom-li-folder-icon'){
                    $value['type'] = 'mwp-wpvivid-custom-li-folder-icon';
                }
                else if($value['type'] === 'wpvivid-custom-li-file-icon'){
                    $value['type'] = 'mwp-wpvivid-custom-li-file-icon';
                }
                $options['content_option']['exclude_content_list'][$key] = $value;
            }
        }
        if(isset($options['other_option']['include_other_list']) && !empty($options['other_option']['include_other_list'])){
            foreach ($options['other_option']['include_other_list'] as $key => $value){
                if($value['type'] === 'wpvivid-custom-li-folder-icon'){
                    $value['type'] = 'mwp-wpvivid-custom-li-folder-icon';
                }
                else if($value['type'] === 'wpvivid-custom-li-file-icon'){
                    $value['type'] = 'mwp-wpvivid-custom-li-file-icon';
                }
                $options['other_option']['include_other_list'][$key] = $value;
            }
        }
        return $options;
    }

    public function synced_site( $pWebsite, $information = array() )
    {
        if ( is_array( $information ) )
        {
            if ( isset( $information['syncWPvividData'] ) )
            {
                if(isset($information['syncWPvividSetting'])){
                    $data['settings'] = isset($information['syncWPvividSetting']['setting']) ? serialize($information['syncWPvividSetting']['setting']) : '';
                    $data_meta['settings'] = isset($information['syncWPvividSetting']['setting']) ? ($information['syncWPvividSetting']['setting']) : '';//
                    $data['settings_addon'] = isset($information['syncWPvividSetting']['setting_addon']) ? serialize($information['syncWPvividSetting']['setting_addon']) : '';
                    $data_meta['settings_addon'] = isset($information['syncWPvividSetting']['setting_addon']) ? ($information['syncWPvividSetting']['setting_addon']) : '';//
                    $data['schedule'] = isset($information['syncWPvividSetting']['schedule']) ? serialize($information['syncWPvividSetting']['schedule']) : '';
                    $data_meta['schedule'] = isset($information['syncWPvividSetting']['schedule']) ? ($information['syncWPvividSetting']['schedule']) : '';//
                    $data['schedule_addon'] = isset($information['syncWPvividSetting']['schedule_addon']) ? serialize($information['syncWPvividSetting']['schedule_addon']) : '';
                    $data_meta['schedule_addon'] = isset($information['syncWPvividSetting']['schedule_addon']) ? ($information['syncWPvividSetting']['schedule_addon']) : '';//
                    $data['remote'] = isset($information['syncWPvividSetting']['remote']) ? serialize($information['syncWPvividSetting']['remote']) : '';
                    $data_meta['remote'] = isset($information['syncWPvividSetting']['remote']) ? ($information['syncWPvividSetting']['remote']) : '';//
                    if(isset($information['syncWPvividSetting']['backup_custom_setting_ex'])) {
                        $information['syncWPvividSetting']['backup_custom_setting_ex'] = $this->handle_custom_tree_data($information['syncWPvividSetting']['backup_custom_setting_ex']);
                        $data['backup_custom_setting_ex'] = serialize($information['syncWPvividSetting']['backup_custom_setting_ex']);
                        $data_meta['backup_custom_setting_ex'] = ($information['syncWPvividSetting']['backup_custom_setting_ex']);
                    }
                    else{
                        $data['backup_custom_setting_ex'] = '';
                        $data_meta['backup_custom_setting_ex'] = '';
                    }
                    $data_meta['menu_capability'] = isset($information['syncWPvividSetting']['menu_capability']) ? $information['syncWPvividSetting']['menu_capability'] : '';
                    $data_meta['white_label_setting'] = isset($information['syncWPvividSetting']['white_label_setting']) ? $information['syncWPvividSetting']['white_label_setting'] : '';
                    $data_meta['incremental_backup_setting'] = isset($information['syncWPvividSetting']['incremental_backup_setting']) ? $information['syncWPvividSetting']['incremental_backup_setting'] : array();

                    $data_meta['dashboard_version'] = isset($information['syncWPvividSetting']['dashboard_version']) ? $information['syncWPvividSetting']['dashboard_version'] : '';
                    $data_meta['addons_info'] = isset($information['syncWPvividSetting']['addons_info']) ? $information['syncWPvividSetting']['addons_info'] : array();

                    if(isset($information['syncWPvividSetting']['is_mu'])){
                        $data_meta['is_mu'] = $information['syncWPvividSetting']['is_mu'] === true ? 1 : 0;
                    }
                    else{
                        $data_meta['is_mu'] = 0;
                    }

                    Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_sync_options($pWebsite->id, $data_meta);
                    $data['need_update'] = isset($information['syncWPvividSetting']['need_update']) ? $information['syncWPvividSetting']['need_update'] : '';
                    $data['current_version'] = isset($information['syncWPvividSetting']['current_version']) ? $information['syncWPvividSetting']['current_version'] : '';
                    if(isset($information['syncWPvividSetting']['is_pro'])) {
                        $data['is_pro'] = $information['syncWPvividSetting']['is_pro'] === true ? 1 : 0;
                        if($data['is_pro'] === 1){
                            $sync_first = $this->get_global_first_init();
                            if(!$sync_first){
                                $this->set_global_first_init('first');
                            }
                        }
                    }
                    else{
                        $data['is_pro'] = 0;
                    }
                    if(isset($information['syncWPvividSetting']['is_install'])){
                        $data['is_install'] = $information['syncWPvividSetting']['is_install'] === true ? 1 : 0;
                    }
                    else{
                        $data['is_install'] = 0;
                    }
                    if(isset($information['syncWPvividSetting']['is_login'])){
                        $data['is_login'] = $information['syncWPvividSetting']['is_login'] === true ? 1 : 0;
                    }
                    else{
                        $data['is_login'] = 0;
                    }
                    $data['latest_version'] = isset($information['syncWPvividSetting']['latest_version']) ? $information['syncWPvividSetting']['latest_version'] : '';
                    $data['time_zone'] = isset($information['syncWPvividSetting']['time_zone']) ? $information['syncWPvividSetting']['time_zone'] : 0;
                    $last_backup_report = isset($information['syncWPvividSetting']['last_backup_report']) ? $information['syncWPvividSetting']['last_backup_report'] : array();
                    $this->set_backup_report($pWebsite->id, $last_backup_report);
                    //$data['report_addon'] = isset($information['syncWPvividSetting']['report_addon']) ? base64_encode(serialize($information['syncWPvividSetting']['report_addon'])) : '';

                    $login_options = $this->get_global_login_addon();
                    $tmp_version = 0;
                    if(isset($login_options['wpvivid_pro_login_cache']['pro']['version'])){
                        $tmp_version = $login_options['wpvivid_pro_login_cache']['pro']['version'];
                    }
                    else if(isset($login_options['wpvivid_pro_login_cache']['dashboard']['version'])){
                        $tmp_version = $login_options['wpvivid_pro_login_cache']['dashboard']['version'];
                    }
                    if(isset($login_options['wpvivid_pro_login_cache']))
                    {
                        if (isset($data['current_version']))
                        {
                            if(version_compare($tmp_version, $data['current_version'],'>'))
                            {
                                $data['need_update']=1;
                                $data['latest_version']=$tmp_version;
                            }
                            else{
                                $data['need_update']=0;
                            }
                        }
                        else{
                            $data['need_update']=1;
                            $data['latest_version']=$tmp_version;
                        }
                    }

                    unset($data['backup_custom_setting_ex']);
                    Mainwp_WPvivid_Extension_Option::get_instance()->sync_options($pWebsite->id,$data);
                    unset($data['wpvivid_setting']);
                    unset($data['need_update']);
                    unset($data['current_version']);
                    unset($data['is_pro']);
                    unset($data['is_install']);
                    unset($data['is_login']);
                    unset($data['latest_version']);
                    unset($data['time_zone']);
                    //unset($data['report_addon']);
                    if(!Mainwp_WPvivid_Extension_Option::get_instance()->is_set_global_options()) {
                        Mainwp_WPvivid_Extension_Option::get_instance()->set_global_options($data);
                    }
                    unset( $information['syncWPvividSetting'] );
                    unset( $information['syncWPvividData'] );
                }
                else{
                    $this->set_sync_error($pWebsite->id, 2);
                }
            }
            else{
                $this->set_sync_error($pWebsite->id, 1);
            }
        }
    }

    public function delete_site_data($website)
    {
        if ( $website )
        {
            Mainwp_WPvivid_Extension_Option::get_instance()->delete_site($website->id );
        }
    }

    /*public function mainwp_sync_extensions_options($values = array()) {
        $values['wpvivid-backup-mainwp'] = array(
            'plugin_name' => 'WPvivid Backup Plugin',
            'plugin_slug' => 'wpvivid-backuprestore/wpvivid-backuprestore.php'
        );
        return $values;
    }*/

    public function primary_backups_method( $methods )
    {
        $methods[] = array( 'value' => 'wpvivid', 'title' => 'WPvivid Backup for MainWP' );
        return $methods;
    }

    public function set_schedule_notice($notice_type, $message)
    {
        $html = '';
        if($notice_type)
        {
            $html .= '<div class="notice notice-success is-dismissible inline" style="margin: 0; padding-top: 10px; margin-bottom: 10px; margin-left: 0px !important;"><p>'.esc_html($message).'</p>
                                    <button type="button" class="notice-dismiss" onclick="mwp_click_dismiss_notice(this);">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                    </button>
                                    </div>';
        }
        else{
            $html .= '<div class="notice notice-error inline" style="margin: 0; padding: 10px; margin-bottom: 10px; margin-left: 0px !important;"><p>' . esc_html($message) . '</p></div>';
        }
        return $html;
    }

    public function check_site_id_secure($site_id)
    {
        if(Mainwp_WPvivid_Extension_Option::get_instance()->is_vaild_child_site($site_id)){
            return true;
        }
        else{
            return false;
        }
    }

    public function admin_init()
    {
        wp_enqueue_style('Mainwp Wpvivid Extension', plugin_dir_url(__FILE__) . 'admin/css/wpvivid-backup-mainwp-admin.css', array(), $this->version, 'all');
        wp_enqueue_style('Mainwp Wpvivid Extension'.'jstree', plugin_dir_url(__FILE__) . 'admin/js/jstree/dist/themes/default/style.min.css', array(), $this->version, 'all');

        wp_enqueue_script('Mainwp Wpvivid Extension', plugin_dir_url(__FILE__) . 'admin/js/wpvivid-backup-mainwp-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script('Mainwp Wpvivid Extension'.'jstree', plugin_dir_url(__FILE__) . 'admin/js/jstree/dist/jstree.min.js', array('jquery'), $this->version, false);
        wp_localize_script('Mainwp Wpvivid Extension', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce'=>wp_create_nonce('wpvivid_mainwp_ajax')));
        if(isset($_GET['id']) && !empty($_GET['id'])) {
            $site_id=sanitize_key($_GET['id']);
            wp_add_inline_script( 'Mainwp Wpvivid Extension', 'site_id='.$site_id);
        }
    }

    public function managesites_subpage( $subPage )
    {
        $subPage[] = array(
            'title' => __( 'WPvivid Backups', 'mainwp' ),
            'slug' => 'WPvivid',
            'sitetab' => true,
            'menu_hidden' => true,
            'callback' => array( $this, 'render' ),
        );
        return $subPage;
    }

    function render()
    {
        do_action( "mainwp_pageheader_sites", "WPvivid" );
        Mainwp_WPvivid_Extension_Subpage::renderSubpage();
        do_action( "mainwp_pagefooter_sites", "WPvivid" );
    }

    function get_this_extension( $pArray )
    {
        $extension['plugin']=__FILE__;
        $extension['mainwp']=false;
        $extension['callback']=array(&$this, 'settings');
        $extension['icon']=MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/logo.png';
        $pArray[] = $extension;
        return $pArray;
    }

    function activate_this_plugin()
    {
        $this->mainwpMainActivated = apply_filters( 'mainwp_activated_check', $this->mainwpMainActivated );
        $this->childEnabled = apply_filters( 'mainwp_extension_enabled_check', __FILE__ );
        $this->childKey = $this->childEnabled['key'];
    }

    function settings()
    {
        do_action( 'mainwp_pageheader_extensions', $this->childFile );
        Mainwp_WPvivid_Extension_Setting::renderSetting();
        do_action( 'mainwp_pagefooter_extensions', $this->childFile );
    }

    public function switch_pro_setting()
    {
        $this->mwp_ajax_check_security();
        try{
            if(isset($_POST['pro_setting']) && is_string($_POST['pro_setting'])){
                $pro_setting = sanitize_text_field($_POST['pro_setting']);
                if($pro_setting == '1'){
                    $this->set_global_switch_pro_setting_page(1);
                }
                else{
                    $this->set_global_switch_pro_setting_page(0);
                }
                $this->set_global_select_pro($pro_setting);
                $ret['result'] = 'success';
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function set_individual()
    {
        $this->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['individual']) && is_string($_POST['individual'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $individual = sanitize_text_field($_POST['individual']);
                $individual = intval($individual);
                Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'individual', $individual);
                $ret['result'] = 'success';
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }





    public function mwp_wpvivid_get_website_plugins_list($site_id)
    {
        $plugins = array();
        $dbwebsites = $this->mwp_get_child_websites();
        foreach ($dbwebsites as $website)
        {
            if ($website)
            {
                if ($website->id === $site_id)
                {
                    $plugins = json_decode($website->plugins, 1);
                }
            }
        }
        return $plugins;
    }

    public function get_is_login($site_id)
    {
        $is_login_pro = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'is_login', false);
        if(empty($is_login_pro)){
            $is_login_pro = false;
        }
        return $is_login_pro;
    }

    public function set_is_login($site_id, $is_login)
    {
        Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'is_login', $is_login);
    }

    public function get_latest_version($site_id)
    {
        $latest_version = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'latest_version', '');
        if(empty($latest_version)){
            $latest_version = '';
        }
        return $latest_version;
    }

    public function set_latest_version($site_id, $version)
    {
        Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'latest_version', $version);
    }

    public function get_current_version($site_id)
    {
        $current_version = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'current_version', '');
        return $current_version;
    }

    public function set_current_version($site_id, $version)
    {
        Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'current_version', $version);
    }

    public function get_need_update($site_id)
    {
        $need_update = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'need_update', '');
        return $need_update;
    }

    public function set_need_update($site_id, $need_update)
    {
        Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'need_update', $need_update);
    }

    public function set_sync_error($site_id, $sync_error)
    {
        Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'sync_error', $sync_error);
    }

    public function set_backup_report($site_id, $option)
    {
        $reports = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'report_addon', array());
        if(!empty($reports)){
            foreach ($option as $key => $value){
                $reports[$key] = $value;
                $reports = $this->clean_out_of_date_report($reports, 10);
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'report_addon', $reports);
            }
        }
        else{
            Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'report_addon', $option);
        }
    }

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
    }

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
    }



    public function get_global_first_init()
    {
        $sync_init_addon_first=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('sync_init_addon_first', '');
        return $sync_init_addon_first;
    }

    public function set_global_first_init($first)
    {
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('sync_init_addon_first', $first);
    }

    public function get_global_switch_pro_setting_page()
    {
        $switch_pro_setting_page=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('switch_pro_setting_page', '');
        return $switch_pro_setting_page;
    }

    public function set_global_switch_pro_setting_page($pro_setting_page)
    {
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('switch_pro_setting_page', $pro_setting_page);
    }

    public function get_global_select_pro()
    {
        $select_pro=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('select_pro', '');
        return $select_pro;
    }

    public function set_global_select_pro($select_pro)
    {
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('select_pro', $select_pro);
    }

    public function get_global_login_addon()
    {
        $login_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('login_addon', array());
        return $login_addon;
    }

    public function set_global_login_addon($login_addon)
    {
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('login_addon', $login_addon);
    }

    public function add_remote_storage_list($html)
    {
        $html = '';
        $options=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote', array());
        $remoteslist=$options['upload'];
        $history=$options['history'];
        $default_remote_storage='';
        if(isset($history['remote_selected'])) {
            foreach ($history['remote_selected'] as $value) {
                $default_remote_storage = $value;
            }
        }
        $i=1;
        foreach ($remoteslist as $key=>$value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            if ($key === $default_remote_storage)
            {
                $check_status = 'checked';
            }
            else
            {
                $check_status='';
            }
            $storage_type = $value['type'];
            $storage_type=apply_filters('wpvivid_storage_provider_tran', $storage_type);
            $html .= '<tr>
                <td>'.esc_html($i++).'</td>
                <td><input type="checkbox" name="remote_storage" value="'.esc_attr($key).'" '.esc_attr($check_status).' /></td>
                <td>'.esc_html($storage_type).'</td>
                <td class="row-title"><label for="tablecell">'.esc_html($value['name']).'</label></td>
                <td>
                    <div><img src="'.esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/Delete.png').'" onclick="mwp_wpvivid_delete_remote_storage(\''.esc_js($key).'\');" style="vertical-align:middle; cursor:pointer;" title="Remove the remote storage"/></div>
                </td>
                </tr>';
        }
        return $html;
    }

    public function mwp_wpvivid_check_version_event(){
        $websites=$this->get_websites_ex();
        foreach ( $websites as $website ){
            $site_id = $website['id'];
            if($website['slug'] === 'wpvivid-backup-pro/wpvivid-backup-pro.php'){
                $post_data['mwp_action'] = 'wpvivid_get_wpvivid_info_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $this->childFile, $this->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    if(isset($information['need_update'])){
                        if($information['need_update']){
                            $need_update = 1;
                        }
                        else{
                            $need_update = 0;
                        }
                    }
                    else{
                        $need_update = 0;
                    }
                    $login_options = $this->get_global_login_addon();
                    if(isset($login_options['wpvivid_pro_login_cache'])){

                        $tmp_version = 0;
                        if(isset($login_options['wpvivid_pro_login_cache']['pro']['version'])){
                            $tmp_version = $login_options['wpvivid_pro_login_cache']['pro']['version'];
                        }
                        else if(isset($login_options['wpvivid_pro_login_cache']['dashboard']['version'])){
                            $tmp_version = $login_options['wpvivid_pro_login_cache']['dashboard']['version'];
                        }

                        if (isset($information['current_version'])) {
                            if(version_compare($tmp_version, $information['current_version'],'>')){
                                $this->set_need_update($site_id, 1);
                                $this->set_current_version($site_id, $information['current_version']);
                                $this->set_latest_version($site_id, $tmp_version);
                            }
                            else{
                                $this->set_need_update($site_id, 0);
                                $this->set_current_version($site_id, $information['current_version']);
                            }
                        }
                        else{
                            $this->set_need_update($site_id, 1);
                            $this->set_latest_version($site_id, $tmp_version);
                        }
                    }
                    else {
                        $this->set_need_update($site_id, $need_update);
                        if (isset($information['current_version'])) {
                            $current_version = $information['current_version'];
                            $this->set_current_version($site_id, $current_version);
                        }
                    }
                    if(isset($information['last_backup_report'])){
                        $last_backup_report = $information['last_backup_report'];
                        $this->set_backup_report($site_id, $last_backup_report);
                    }
                }
            }
        }
    }

    public function mwp_wpvivid_refresh_latest_pro_version_event(){
        $login_options = $this->get_global_login_addon();
        if($login_options !== false && isset($login_options['wpvivid_pro_account'])) {
            if(!isset($login_options['wpvivid_pro_account']['user_info']))
            {
                $ret['result'] = 'failed';
                $ret['error'] = 'Failed to get previously entered login information, please login again.';
                echo wp_json_encode($ret);
                die();
            }
            $user_info=$login_options['wpvivid_pro_account']['user_info'];
            $server=new Mainwp_WPvivid_Connect_server();
            $ret=$server->get_mainwp_status($user_info,false);
            if($ret['result']=='success') {

                $login_options = $this->get_global_login_addon();
                $login_options['wpvivid_pro_login_cache'] = $ret['status'];
                $this->set_global_login_addon($login_options);

                $need_update = false;
                if($login_options === false || !isset($login_options['wpvivid_pro_account'])){
                    $login_options = array();
                    $need_update = true;
                }
                else{
                    if(isset($login_options['wpvivid_pro_login_cache'])){
                        $tmp_version = 0;
                        if(isset($login_options['wpvivid_pro_login_cache']['pro']['version'])){
                            $tmp_version = $login_options['wpvivid_pro_login_cache']['pro']['version'];
                        }
                        else if(isset($login_options['wpvivid_pro_login_cache']['dashboard']['version'])){
                            $tmp_version = $login_options['wpvivid_pro_login_cache']['dashboard']['version'];
                        }
                        if(version_compare($ret['status']['dashboard']['version'], $tmp_version,'>')){
                            $need_update = true;
                        }
                        else{
                            $need_update = false;
                        }
                    }
                    else{
                        $need_update = true;
                    }
                }
                if($need_update) {
                    $this->check_child_site_need_update($ret['status']['dashboard']['version']);
                }
            }
        }
    }

    public function check_child_site_need_update($new_version)
    {
        $dbwebsites = $this->mwp_get_child_websites();
        foreach ($dbwebsites as $website)
        {
            if ($website)
            {
                $old_version = $this->get_latest_version($website->id);
                if(version_compare($new_version, $old_version,'>')){
                    $this->set_need_update($website->id, 1);
                    $this->set_latest_version($website->id, $new_version);
                }
            }
        }
    }

















    public function mwp_wpvivid_update_backup_exclude_extension_rule($site_id, $type, $value){
        $history = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'backup_custom_setting_ex', array());
        if(!$history){
            $history = array();
        }
        if($type === 'uploads'){
            $history['uploads_option']['uploads_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['uploads_option']['uploads_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        if($type === 'content'){
            $history['content_option']['content_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['content_option']['content_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        if($type === 'additional_folder'){
            $history['other_option']['other_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['other_option']['other_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'backup_custom_setting_ex', $history);
    }



    public function mwp_wpvivid_update_global_backup_exclude_extension_rule($type, $value){
        $history = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('backup_custom_setting', array());
        if(!$history){
            $history = array();
        }
        if($type === 'uploads'){
            $history['uploads_option']['uploads_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['uploads_option']['uploads_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        if($type === 'content'){
            $history['content_option']['content_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['content_option']['content_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        if($type === 'additional_folder'){
            $history['other_option']['other_extension_list'] = array();
            $str_tmp = explode(',', $value);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $history['other_option']['other_extension_list'][] = $str_tmp[$index];
                }
            }
        }
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('backup_custom_setting', $history);
    }





    public function mwp_wpvivid_update_backup_custom_setting($site_id, $options){
        $custom_option = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'backup_custom_setting_ex', array());
        $custom_option['exclude_files'] = $options['exclude_files'];
        $custom_option['custom_dirs'] = $options['custom_dirs'];
        $custom_option['exclude_file_type'] = $options['exclude_file_type'];

        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'backup_custom_setting_ex', $custom_option);
    }

    public function mwp_wpvivid_update_global_backup_custom_setting($options){
        $history = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('backup_custom_setting', array());

        $custom_option['database_option']['database_check'] = $options['database_check'];

        $custom_option['themes_option']['themes_check'] = $options['themes_check'];

        $custom_option['plugins_option']['plugins_check'] = $options['plugins_check'];

        $custom_option['uploads_option']['uploads_check'] = $options['uploads_check'];
        $custom_option['uploads_option']['uploads_extension_list'] = array();
        if(isset($options['upload_extension'])){
            $str_tmp = explode(',', $options['upload_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $custom_option['uploads_option']['uploads_extension_list'][] = $str_tmp[$index];
                }
            }
        }

        $custom_option['content_option']['content_check'] = $options['content_check'];
        $custom_option['content_option']['content_extension_list'] = array();
        if(isset($options['content_extension'])){
            $str_tmp = explode(',', $options['content_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $custom_option['content_option']['content_extension_list'][] = $str_tmp[$index];
                }
            }
        }

        $custom_option['core_option']['core_check'] = $options['core_check'];

        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('backup_custom_setting', $custom_option);
    }

    public function set_incremental_file_settings($site_id, $options)
    {
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
            $history = array();
        }
        else{
            $history = isset($incremental_backup_setting['incremental_history']) ? $incremental_backup_setting['incremental_history'] : array();
            if(empty($history)){
                $history = array();
            }
        }

        $custom_option['database_option']['database_check'] = isset($options['database_check']) ? $options['database_check'] : 0;
        $custom_option['database_option']['exclude_table_list'] = isset($options['database_list']) ? $options['database_list'] : array();

        $custom_option['themes_option']['themes_check'] = isset($options['themes_check']) ? $options['themes_check'] : 0;
        $custom_option['themes_option']['exclude_themes_list'] = isset($options['themes_list']) ? $options['themes_list'] : array();

        $custom_option['plugins_option']['plugins_check'] = isset($options['plugins_check']) ? $options['plugins_check'] : 0;
        $custom_option['plugins_option']['exclude_plugins_list'] = isset($options['plugins_list']) ? $options['plugins_list'] : array();

        $custom_option['uploads_option']['uploads_check'] = isset($options['uploads_check']) ? $options['uploads_check'] : 0;
        $custom_option['uploads_option']['exclude_uploads_list'] = isset($options['uploads_list']) ? $options['uploads_list'] : array();
        $custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = isset($options['content_check']) ? $options['content_check'] : 0;
        $custom_option['content_option']['exclude_content_list'] = isset($options['content_list']) ? $options['content_list'] : array();
        $custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = isset($options['core_check']) ? $options['core_check'] : 0;

        $custom_option['other_option']['other_check'] = isset($options['other_check']) ? $options['other_check'] : 0;
        $custom_option['other_option']['include_other_list'] = isset($options['other_list']) ? $options['other_list'] : array();
        $custom_option['other_option']['other_extension_list'] = isset($options['other_extension']) ? $options['other_extension'] : array();

        $custom_option['additional_database_option']['additional_database_check'] = isset($options['additional_database_check']) ? $options['additional_database_check'] : 0;
        if(isset($history['incremental_file']['additional_database_option'])) {
            $custom_option['additional_database_option'] = $history['incremental_file']['additional_database_option'];
        }

        $incremental_backup_setting['incremental_history']['incremental_file'] = $custom_option;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_incremental_db_setting($site_id, $options){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
            $history = array();
        }
        else{
            $history = isset($incremental_backup_setting['incremental_history']) ? $incremental_backup_setting['incremental_history'] : array();
            if(empty($history)){
                $history = array();
            }
        }

        $custom_option['database_option']['database_check'] = isset($options['database_check']) ? $options['database_check'] : 0;
        $custom_option['database_option']['exclude_table_list'] = isset($options['database_list']) ? $options['database_list'] : array();

        $custom_option['themes_option']['themes_check'] = isset($options['themes_check']) ? $options['themes_check'] : 0;
        $custom_option['themes_option']['exclude_themes_list'] = isset($options['themes_list']) ? $options['themes_list'] : array();

        $custom_option['plugins_option']['plugins_check'] = isset($options['plugins_check']) ? $options['plugins_check'] : 0;
        $custom_option['plugins_option']['exclude_plugins_list'] = isset($options['plugins_list']) ? $options['plugins_list'] : array();

        $custom_option['uploads_option']['uploads_check'] = isset($options['uploads_check']) ? $options['uploads_check'] : 0;
        $custom_option['uploads_option']['exclude_uploads_list'] = isset($options['uploads_list']) ? $options['uploads_list'] : array();
        $custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = isset($options['content_check']) ? $options['content_check'] : 0;
        $custom_option['content_option']['exclude_content_list'] = isset($options['content_list']) ? $options['content_list'] : array();
        $custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = isset($options['core_check']) ? $options['core_check'] : 0;

        $custom_option['other_option']['other_check'] = isset($options['other_check']) ? $options['other_check'] : 0;
        $custom_option['other_option']['include_other_list'] = isset($options['other_list']) ? $options['other_list'] : array();
        $custom_option['other_option']['other_extension_list'] = isset($options['other_extension']) ? $options['other_extension'] : array();

        if(isset($history['incremental_db']['additional_database_option'])) {
            $custom_option['additional_database_option'] = $history['incremental_db']['additional_database_option'];
        }
        $custom_option['additional_database_option']['additional_database_check'] = isset($options['additional_database_check']) ? $options['additional_database_check'] : 0;

        $incremental_backup_setting['incremental_history']['incremental_db'] = $custom_option;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_incremental_remote_retain_count($site_id, $count){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $incremental_backup_setting['incremental_remote_backup_count'] = $count;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_incremental_enable($site_id, $status){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $incremental_backup_setting['enable_incremental_schedules'] = $status;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_incremental_schedules($site_id, $schedules){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $incremental_backup_setting['incremental_schedules'] = $schedules;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_incremental_backup_data($site_id, $data){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $incremental_backup_setting['incremental_backup_data'] = $data;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_incremental_output_msg($site_id, $msg){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $incremental_backup_setting['incremental_output_msg'] = $msg;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'incremental_backup_setting', $incremental_backup_setting);
    }

    public function mwp_wpvivid_custom_backup_data_transfer($options, $data, $type)
    {
        if(!isset($data['database_check'])){
            $data['database_check'] = 0;
        }
        $options['backup_select']['db'] = intval($data['database_check']);
        if(!isset($data['database_list'])){
            $data['database_list'] = array();
        }
        $options['exclude_tables'] = $data['database_list'];

        if(!isset($data['themes_check'])){
            $data['themes_check'] = 0;
        }
        $options['backup_select']['themes'] = intval($data['themes_check']);
        if(!isset($data['themes_list'])){
            $data['themes_list'] = array();
        }
        $options['exclude_themes'] = $data['themes_list'];

        if(!isset($data['plugins_check'])){
            $data['plugins_check'] = 0;
        }
        $options['backup_select']['plugin'] = intval($data['plugins_check']);
        if(!isset($data['plugins_list'])){
            $data['plugins_list'] = array();
        }
        $options['exclude_plugins'] = $data['plugins_list'];

        if(!isset($data['uploads_check'])){
            $data['uploads_check'] = 0;
        }
        $options['backup_select']['uploads'] = intval($data['uploads_check']);
        $upload_exclude_list = array();
        if(isset($data['uploads_list'])) {
            foreach ($data['uploads_list'] as $key => $value){
                $upload_exclude_list[] = $key;
            }
        }
        else{
            $data['uploads_list'] = array();
        }
        $options['exclude_uploads'] = $upload_exclude_list;
        $upload_exclude_file_list=array();
        $upload_extension_tmp = array();
        if(isset($data['upload_extension']) && !empty($data['upload_extension'])) {
            $str_tmp = explode(',', $data['upload_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $upload_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $upload_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['upload_extension'] = $upload_extension_tmp;
        }
        else{
            $data['upload_extension'] = array();
        }
        $options['exclude_uploads_files'] = $upload_exclude_file_list;

        if(!isset($data['content_check'])){
            $data['content_check'] = 0;
        }
        $options['backup_select']['content'] = intval($data['content_check']);
        $content_exclude_list=array();
        if(isset($data['content_list'])) {
            foreach ($data['content_list'] as $key => $value){
                $content_exclude_list[] = $key;
            }
        }
        else{
            $data['content_list'] = array();
        }
        $options['exclude_content'] = $content_exclude_list;
        $content_exclude_file_list=array();
        $content_extension_tmp = array();
        if(isset($data['content_extension']) && !empty($data['content_extension'])) {
            $str_tmp = explode(',', $data['content_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $content_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $content_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['content_extension'] = $content_extension_tmp;
        }
        else{
            $data['content_extension'] = array();
        }
        $options['exclude_content_files'] = $content_exclude_file_list;

        if(!isset($data['core_check'])){
            $data['core_check'] = 0;
        }
        $options['backup_select']['core'] = intval($data['core_check']);

        if(!isset($data['other_check'])){
            $data['other_check'] = 0;
        }
        $options['backup_select']['other'] = intval($data['other_check']);
        $other_include_list=array();
        if(isset($data['other_list'])) {
            foreach ($data['other_list'] as $key => $value){
                $other_include_list[] = $key;
            }
        }
        else{
            $data['other_list'] = array();
        }
        $options['custom_other_root'] = $other_include_list;
        $other_exclude_file_list=array();
        $other_extension_tmp = array();
        if(isset($data['other_extension']) && !empty($data['other_extension'])) {
            $str_tmp = explode(',', $data['other_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $other_exclude_file_list[] = '.*\.'.$str_tmp[$index].'$';
                    $other_extension_tmp[] = $str_tmp[$index];
                }
            }
            $data['other_extension'] = $other_extension_tmp;
        }
        else{
            $data['other_extension'] = array();
        }
        $options['exclude_custom_other_files'] = $other_exclude_file_list;
        $options['exclude_custom_other']=array();

        if(!isset($data['additional_database_check'])){
            $data['additional_database_check'] = 0;
        }
        $options['backup_select']['additional_db'] = intval($data['additional_database_check']);
        if($options['backup_select']['additional_db'] === 1){

            if(isset($history['additional_database_option']['additional_database_list']) && !empty($history['additional_database_option']['additional_database_list'])) {
                $options['additional_database_list'] = $history['additional_database_option']['additional_database_list'];
            }
            else{
                $options['additional_database_list'] = array();
            }
        }

        return $options;
    }

    public function check_incremental_schedule_option($data){
        //$ret['schedule']['file_start_time_zone'] = $data['file_start_time_zone'];
        //$ret['schedule']['db_start_time_zone'] = $data['db_start_time_zone'];
        $ret['schedule']['incremental_recurrence'] =$data['recurrence'];
        $ret['schedule']['incremental_recurrence_week'] =$data['recurrence_week'];
        $ret['schedule']['incremental_recurrence_day'] =$data['recurrence_day'];
        $ret['schedule']['incremental_files_recurrence'] =$data['incremental_files_recurrence'];
        $ret['schedule']['incremental_db_recurrence'] =$data['incremental_db_recurrence'];
        $ret['schedule']['incremental_db_recurrence_week'] = $data['incremental_db_recurrence_week'];
        $ret['schedule']['incremental_db_recurrence_day'] = $data['incremental_db_recurrence_day'];
        $ret['schedule']['incremental_backup_status'] = $data['incremental_backup_status'];
        $ret['schedule']['incremental_files_start_backup'] = $data['incremental_files_start_backup'];

        /*if(isset($data['custom']['files'])){
            $ret['schedule']['backup_files']=array();
            $ret['schedule']['backup_files'] = apply_filters('mwp_wpvivid_custom_backup_data_transfer', $ret['schedule']['backup_files'], $data['custom']['files'], 'incremental_backup_file');
        }
        if(isset($data['custom']['db'])){
            $ret['schedule']['backup_db']=array();
            $ret['schedule']['backup_db'] = apply_filters('mwp_wpvivid_custom_backup_data_transfer', $ret['schedule']['backup_db'], $data['custom']['db'], 'incremental_backup_db');
        }*/
        $data['save_local_remote']=sanitize_text_field($data['save_local_remote']);

        if(!empty($data['save_local_remote']))
        {
            if($data['save_local_remote'] == 'remote')
            {
                $ret['schedule']['backup']['remote']=1;
                $ret['schedule']['backup']['local']=0;
            }
            else
            {
                $ret['schedule']['backup']['remote']=0;
                $ret['schedule']['backup']['local']=1;
            }
        }

        if(isset($data['backup_prefix']) && !empty($data['backup_prefix']))
        {
            $ret['schedule']['backup']['backup_prefix'] = $data['backup_prefix'];
        }

        if(isset($data['db_current_day']))
        {
            $ret['schedule']['db_current_day'] = $data['db_current_day'];
        }

        if(isset($data['files_current_day']))
        {
            $ret['schedule']['files_current_day'] = $data['files_current_day'];
        }

        $ret['schedule']['files_current_day_hour'] = $data['files_current_day_hour'];
        $ret['schedule']['files_current_day_minute'] = $data['files_current_day_minute'];
        $ret['schedule']['db_current_day_hour'] = $data['db_current_day_hour'];
        $ret['schedule']['db_current_day_minute'] = $data['db_current_day_minute'];

        $ret['schedule']['backup_db'] = $data['backup_db'];
        $ret['schedule']['backup_files'] = $data['backup_files'];

        $ret['schedule']['exclude_files'] = $data['exclude_files'];
        $ret['schedule']['exclude_file_type'] = $data['exclude_file_type'];

        return $ret;
    }

    public function mwp_add_incremental_schedule($schedule){
        $schedule_data=array();
        $schedule_data['id']=uniqid('wpvivid_incremental_schedule');
        $schedule_data['files_schedule_id']=uniqid('wpvivid_incremental_files_schedule_event');
        $schedule_data['db_schedule_id']=uniqid('wpvivid_incremental_db_schedule_event');

        $schedule['backup']['ismerge']=1;
        $schedule['backup']['lock']=0;
        $schedule_data= $this->mwp_set_incremental_schedule_data($schedule_data,$schedule);

        $schedules=array();
        $schedules[$schedule_data['id']]=$schedule_data;
        return $schedules;
    }

    public function mwp_set_incremental_schedule_data($schedule_data,$schedule){
        //$schedule_data['file_start_time_zone'] = $schedule['file_start_time_zone'];
        //$schedule_data['db_start_time_zone'] = $schedule['db_start_time_zone'];
        $schedule_data['incremental_recurrence']=$schedule['incremental_recurrence'];
        $schedule_data['incremental_recurrence_week']=$schedule['incremental_recurrence_week'];
        $schedule_data['incremental_recurrence_day']=$schedule['incremental_recurrence_day'] ;
        $schedule_data['incremental_files_recurrence']=$schedule['incremental_files_recurrence'];
        $schedule_data['incremental_db_recurrence']=$schedule['incremental_db_recurrence'];
        $schedule_data['incremental_db_recurrence_week']=$schedule['incremental_db_recurrence_week'];
        $schedule_data['incremental_db_recurrence_day']=$schedule['incremental_db_recurrence_day'];
        $schedule_data['db_current_day']=$schedule['db_current_day'];
        $schedule_data['files_current_day']=$schedule['files_current_day'];
        $schedule_data['incremental_backup_status'] = $schedule['incremental_backup_status'];
        $schedule_data['incremental_files_start_backup']=$schedule['incremental_files_start_backup'];
        $schedule_data['files_current_day_hour'] = $schedule['files_current_day_hour'];
        $schedule_data['files_current_day_minute'] = $schedule['files_current_day_minute'];
        $schedule_data['db_current_day_hour'] = $schedule['db_current_day_hour'];
        $schedule_data['db_current_day_minute'] = $schedule['db_current_day_minute'];

        $schedule_data['backup_files'] = $schedule['backup_files'];
        $schedule_data['backup_db'] = $schedule['backup_db'];

        $schedule_data['exclude_files'] = $schedule['exclude_files'];
        $schedule_data['exclude_file_type'] = $schedule['exclude_file_type'];

        $schedule_data['backup']=$schedule['backup'];
        return $schedule_data;
    }

    public function set_global_incremental_file_settings($incremental_schedule_mould_name, $options){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
            $history = array();
        }
        else{
            $history = isset($incremental_backup_setting['incremental_history']) ? $incremental_backup_setting['incremental_history'] : array();
            if(empty($history)){
                $history = array();
            }
        }

        $custom_option['database_option']['database_check'] = isset($options['database_check']) ? $options['database_check'] : 0;

        $custom_option['themes_option']['themes_check'] = isset($options['themes_check']) ? $options['themes_check'] : 0;

        $custom_option['plugins_option']['plugins_check'] = isset($options['plugins_check']) ? $options['plugins_check'] : 0;

        $custom_option['uploads_option']['uploads_check'] = isset($options['uploads_check']) ? $options['uploads_check'] : 0;

        $upload_extension_tmp = array();
        if(isset($options['upload_extension']) && !empty($options['upload_extension'])) {
            $str_tmp = explode(',', $options['upload_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $upload_extension_tmp[] = $str_tmp[$index];
                }
            }
            $custom_option['uploads_option']['uploads_extension_list'] = $upload_extension_tmp;
        }
        else{
            $custom_option['uploads_option']['uploads_extension_list'] = array();
        }
        //$custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = isset($options['content_check']) ? $options['content_check'] : 0;

        $content_extension_tmp = array();
        if(isset($options['content_extension']) && !empty($options['content_extension'])) {
            $str_tmp = explode(',', $options['content_extension']);
            for($index=0; $index<count($str_tmp); $index++){
                if(!empty($str_tmp[$index])) {
                    $content_extension_tmp[] = $str_tmp[$index];
                }
            }
            $custom_option['content_option']['content_extension_list'] = $content_extension_tmp;
        }
        else{
            $custom_option['content_option']['content_extension_list'] = array();
        }
        //$custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = isset($options['core_check']) ? $options['core_check'] : 0;

        $incremental_backup_setting[$incremental_schedule_mould_name]['incremental_history']['incremental_file'] = $custom_option;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_global_incremental_db_settings($incremental_schedule_mould_name, $options){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
            $history = array();
        }
        else{
            $history = isset($incremental_backup_setting['incremental_history']) ? $incremental_backup_setting['incremental_history'] : array();
            if(empty($history)){
                $history = array();
            }
        }

        $custom_option['database_option']['database_check'] = isset($options['database_check']) ? $options['database_check'] : 0;

        $custom_option['themes_option']['themes_check'] = isset($options['themes_check']) ? $options['themes_check'] : 0;

        $custom_option['plugins_option']['plugins_check'] = isset($options['plugins_check']) ? $options['plugins_check'] : 0;

        $custom_option['uploads_option']['uploads_check'] = isset($options['uploads_check']) ? $options['uploads_check'] : 0;
        $custom_option['uploads_option']['uploads_extension_list'] = isset($options['upload_extension']) ? $options['upload_extension'] : array();

        $custom_option['content_option']['content_check'] = isset($options['content_check']) ? $options['content_check'] : 0;
        $custom_option['content_option']['content_extension_list'] = isset($options['content_extension']) ? $options['content_extension'] : array();

        $custom_option['core_option']['core_check'] = isset($options['core_check']) ? $options['core_check'] : 0;

        $incremental_backup_setting[$incremental_schedule_mould_name]['incremental_history']['incremental_db'] = $custom_option;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_global_incremental_remote_retain_count($incremental_schedule_mould_name, $count){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $incremental_backup_setting[$incremental_schedule_mould_name]['incremental_remote_backup_count'] = $count;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('incremental_backup_setting', $incremental_backup_setting);
    }

    public function set_global_incremental_schedules($incremental_schedule_mould_name, $schedule){
        $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
        if(empty($incremental_backup_setting)){
            $incremental_backup_setting = array();
        }

        $ret = $this->check_incremental_schedule_option($schedule);
        $schedules = $this->mwp_add_incremental_schedule($ret['schedule']);

        $incremental_backup_setting[$incremental_schedule_mould_name]['incremental_schedules'] = $schedules;
        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('incremental_backup_setting', $incremental_backup_setting);
    }























    public function mwp_check_white_label_option($data)
    {
        $ret['result']='failed';
        if(!isset($data['white_label_display']))
        {
            $ret['error']=__('The white label is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_display']=sanitize_text_field($data['white_label_display']);
        if(empty($data['white_label_display']))
        {
            $ret['error']=__('The white label is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_slug']))
        {
            $ret['error']=__('The slug is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_slug']=sanitize_text_field($data['white_label_slug']);
        if(empty($data['white_label_slug']))
        {
            $ret['error']=__('The slug is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_support_email']))
        {
            $ret['error']=__('The support email is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_support_email']=sanitize_text_field($data['white_label_support_email']);
        if(empty($data['white_label_support_email']))
        {
            $ret['error']=__('The support email is required.', 'wpvivid');
            return $ret;
        }

        if(!isset($data['white_label_website']))
        {
            $ret['error']=__('The website is required.', 'wpvivid');
            return $ret;
        }
        $data['white_label_website']=sanitize_text_field($data['white_label_website']);
        if(empty($data['white_label_website']))
        {
            $ret['error']=__('The website is required.', 'wpvivid');
            return $ret;
        }

        $ret['result']='success';
        return $ret;
    }

    public function mwp_ajax_check_security($role='administrator')
    {
        check_ajax_referer( 'wpvivid_mainwp_ajax', 'nonce' );
        if(!is_admin()||!current_user_can($role))
            die();
    }

    public function mwp_check_wpvivid_pro($plugins, $website_id){
        $check_pro = false;
        $is_pro=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($website_id, 'is_pro', false);
        if($is_pro){
            $check_pro = true;
        }
        return $check_pro;
    }

    public function get_websites()
    {
        $websites = apply_filters( 'mainwp_getsites', $this->childFile, $this->childKey, null );
        $sites_ids = array();
        if ( is_array( $websites ) )
        {
            foreach ( $websites as $site ) {
                $sites_ids[] = $site['id'];
            }
            unset( $websites );
        }
        $option = array( 'plugin_upgrades' => true, 'plugins' => true );
        $selected_group=array();
        if ( isset( $_POST['mwp_wpvivid_plugin_groups_select'] ) && $_POST['mwp_wpvivid_plugin_groups_select']!=0)
        {
            $selected_group[] = intval(sanitize_text_field($_POST['mwp_wpvivid_plugin_groups_select']));
        }
        if(!empty($selected_group))
        {
            $sites_ids=array();
        }

        $dbwebsites = apply_filters( 'mainwp_getdbsites', $this->childFile, $this->childKey, $sites_ids, $selected_group, $option );
        $websites_with_plugin=array();
        foreach ( $dbwebsites as $website )
        {
            if ( $website )
            {
                $plugins = json_decode( $website->plugins, 1 );
                if ( is_array( $plugins ) && count( $plugins ) != 0 )
                {
                    $site = array('id' => $website->id, 'name' => $website->name, 'url' => $website->url);
                    $check_pro = $this->mwp_check_wpvivid_pro($plugins, $website->id);
                    if(!$check_pro) {
                        $site['pro'] = 0;
                        $site['install'] = 0;
                        $site['active'] = 0;
                        $site['login'] = 0;
                        $site['version'] = 'N/A';
                        $site['slug'] = 'wpvivid-backuprestore'; //wpvivid-backup-pro
                        $site['individual'] = 0;
                        $site['status'] = 'Not Install';
                        $site['class'] = 'need-install';

                        foreach ($plugins as $plugin) {
                            $reg_string = 'wpvivid-backuprestore/wpvivid-backuprestore.php';
                            if ((strcmp($plugin['slug'], $reg_string) === 0)) {
                                $site['pro'] = 0;
                                $site['install'] = 1;
                                $site['slug'] = $plugin['slug'];
                                $site['version'] = esc_html($plugin['version']).' (WPvivid Backup)';


                                $individual = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site['id'], 'individual', false);
                                if ($individual) {
                                    $site['individual'] = 1;
                                } else {
                                    $site['individual'] = 0;
                                }

                                if ($plugin['active']) {
                                    $site['active'] = 1;
                                    $plugin_upgrades = json_decode($website->plugin_upgrades, 1);
                                    if (is_array($plugin_upgrades) && count($plugin_upgrades) > 0) {
                                        if (isset($plugin_upgrades['wpvivid-backuprestore/wpvivid-backuprestore.php'])) {
                                            $upgrade = $plugin_upgrades['wpvivid-backuprestore/wpvivid-backuprestore.php'];
                                            if (isset($upgrade['update'])) {
                                                $site['upgrade'] = $upgrade['update'];
                                                $site['status'] = 'New version available';
                                                $site['class'] = 'need-update';
                                            }
                                            else{
                                                $site['status'] = 'Latest version';
                                                $site['class'] = '';
                                            }
                                        }
                                        else{
                                            $site['status'] = 'Latest version';
                                            $site['class'] = '';
                                        }
                                    }
                                    else{
                                        $site['status'] = 'Latest version';
                                        $site['class'] = '';
                                    }
                                } else {
                                    $site['active'] = 0;
                                    $site['status'] = 'Not Actived';
                                    $site['class'] = 'need-active';
                                }
                                //$site['report'] = Mainwp_WPvivid_Extension_Option::get_instance()->get_report_addon($site['id']);
                                //$site['sync_remote_setting'] = Mainwp_WPvivid_Extension_Option::get_instance()->get_sync_remote_setting($site['id']);
                                break;
                            }
                        }
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $find = trim(sanitize_text_field($_GET['search']));
                            if (stripos($site['name'], $find) !== false || stripos($site['url'], $find) !== false) {
                                $websites_with_plugin[$site['id']] = $site;
                            }
                        } else {
                            $websites_with_plugin[$site['id']] = $site;
                        }
                    }
                }
            }
        }

        return $websites_with_plugin;
    }

    public function mwp_get_child_websites(){
        $websites = apply_filters( 'mainwp_getsites', $this->childFile, $this->childKey, null );
        $sites_ids = array();
        if ( is_array( $websites ) ) {
            foreach ( $websites as $site ) {
                $sites_ids[] = $site['id'];
            }
            unset( $websites );
        }
        $option = array( 'plugin_upgrades' => true, 'plugins' => true );
        $selected_group=array();
        if ( isset( $_POST['mwp_wpvivid_plugin_groups_select'] ) && $_POST['mwp_wpvivid_plugin_groups_select']!=0) {
            $selected_group[] = intval(sanitize_text_field($_POST['mwp_wpvivid_plugin_groups_select']));
        }
        if(!empty($selected_group)) {
            $sites_ids=array();
        }

        $dbwebsites = apply_filters( 'mainwp_getdbsites', $this->childFile, $this->childKey, $sites_ids, $selected_group, $option );
        return $dbwebsites;
    }

    public function get_websites_ex()
    {
        $websites = apply_filters( 'mainwp_getsites', $this->childFile, $this->childKey, null );
        $sites_ids = array();
        if ( is_array( $websites ) ) {
            foreach ( $websites as $site ) {
                $sites_ids[] = $site['id'];
            }
            unset( $websites );
        }
        $option = array( 'plugin_upgrades' => true, 'plugins' => true );
        $selected_group=array();
        if ( isset( $_POST['mwp_wpvivid_plugin_groups_select'] ) && $_POST['mwp_wpvivid_plugin_groups_select']!=0) {
            $selected_group[] = intval(sanitize_text_field($_POST['mwp_wpvivid_plugin_groups_select']));
        }
        if(!empty($selected_group)) {
            $sites_ids=array();
        }

        $login_options = $this->get_global_login_addon();
        if($login_options !== false && isset($login_options['wpvivid_pro_login_cache'])){
            $addons_cache = $login_options['wpvivid_pro_login_cache'];
            if(isset($addons_cache['pro']['version'])){
                $latest_version = $addons_cache['pro']['version'];
            }
            else if(isset($addons_cache['dashboard']['version'])){
                $latest_version = $addons_cache['dashboard']['version'];
            }
            else{
                $latest_version = false;
            }
        }
        else{
            $latest_version = false;
        }

        $dbwebsites = apply_filters( 'mainwp_getdbsites', $this->childFile, $this->childKey, $sites_ids, $selected_group, $option );
        $websites_with_plugin=array();
        foreach ( $dbwebsites as $website ){
            if ( $website )
            {
                $plugins = json_decode( $website->plugins, 1 );
                if ( is_array( $plugins ) && count( $plugins ) != 0 )
                {
                    $site = array('id' => $website->id, 'name' => $website->name, 'url' => $website->url);
                    $check_pro = $this->mwp_check_wpvivid_pro($plugins, $website->id);

                    $site['pro'] = 1;
                    $site['slug'] = 'wpvivid-backup-pro';
                    $site['version'] = 'N/A';
                    $site['individual'] = 0;
                    $site['install-wpvivid'] = 0;
                    $site['active-wpvivid'] = 0;
                    $site['install-wpvivid-pro'] = 0;
                    $site['active-wpvivid-pro'] = 0;
                    $site['login'] = 0;
                    $site['check-status'] = 0;
                    $site['status'] = 'WPvivid Backup Pro not claimed';
                    $site['class'] = 'need-install-wpvivid';
                    $site['class-update'] = '';
                    $wpvivid_need_update = false;

                    $wpvivid_status = false;
                    foreach ($plugins as $plugin){
                        $reg_string = 'wpvivid-backuprestore/wpvivid-backuprestore.php';
                        if ((strcmp($plugin['slug'], $reg_string) === 0)) {
                            $site['install-wpvivid'] = 1;
                            if ($plugin['active']) {
                                $site['active-wpvivid'] = 1;

                                $plugin_upgrades = json_decode($website->plugin_upgrades, 1);
                                if (is_array($plugin_upgrades) && count($plugin_upgrades) > 0) {
                                    if (isset($plugin_upgrades['wpvivid-backuprestore/wpvivid-backuprestore.php'])) {
                                        $upgrade = $plugin_upgrades['wpvivid-backuprestore/wpvivid-backuprestore.php'];
                                        if (isset($upgrade['update'])) {
                                            $site['status'] = 'New version available';
                                            $site['class-update'] = 'need-update-wpvivid';
                                            $wpvivid_need_update = true;
                                        }
                                    }
                                }

                                $wpvivid_status = true;
                            } else {
                                $site['active-wpvivid'] = 0;
                                $site['status'] = 'WPvivid Backup Pro not claimed';
                                $site['class'] = 'need-active-wpvivid';
                            }
                            break;
                        }
                    }

                    if($wpvivid_status){
                        $site['status'] = 'WPvivid Backup Pro not claimed';
                        $site['class'] = 'need-install-wpvivid-pro';
                        foreach ($plugins as $plugin) {
                            $reg_string = 'wpvivid-backup-pro/wpvivid-backup-pro.php';
                            if ((strcmp($plugin['slug'], $reg_string) === 0)) {
                                $site['install-wpvivid-pro'] = 1;
                                $site['slug'] = $plugin['slug'];

                                $individual = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site['id'], 'individual', false);
                                if ($individual) {
                                    $site['individual'] = 1;
                                } else {
                                    $site['individual'] = 0;
                                }

                                if ($plugin['active']) {
                                    $site['active-wpvivid-pro'] = 1;

                                    $wpvivid_pro_need_update_pro = false;
                                    if($latest_version !== false){
                                        if(version_compare($latest_version, $plugin['version'],'>')){
                                            $is_login_pro = $this->get_is_login($site['id']);
                                            if($is_login_pro !== false){
                                                if(intval($is_login_pro) !== 1){
                                                    $wpvivid_pro_need_update_pro = true;
                                                    $site['status'] = 'WPvivid Backup Pro not claimed';
                                                    $site['class'] = 'need-install-wpvivid-pro';
                                                }
                                            }
                                            else{
                                                $wpvivid_pro_need_update_pro = true;
                                                $site['status'] = 'WPvivid Backup Pro not claimed';
                                                $site['class'] = 'need-install-wpvivid-pro';
                                            }
                                        }
                                    }

                                    if(!$wpvivid_pro_need_update_pro){
                                        $is_login_pro = $this->get_is_login($site['id']);
                                        if($is_login_pro !== false){
                                            if(intval($is_login_pro) === 1){
                                                $site['login'] = 1;
                                                $need_update = $this->get_need_update($site['id']);
                                                if($need_update == '1'){
                                                    $site['status'] = 'New version available';
                                                    $site['class-update'] = 'need-update-wpvivid-pro';
                                                    $site['class'] = '';
                                                }
                                                else{
                                                    if(!$wpvivid_need_update) {
                                                        $site['status'] = 'Latest version';
                                                        $site['class'] = '';
                                                        $site['check-status'] = 1;
                                                    }
                                                    else{
                                                        $site['status'] = 'New version available';
                                                        $site['class'] = '';
                                                        $site['class-update'] = 'need-update-wpvivid';
                                                    }
                                                }
                                                $site['version'] = $this->get_current_version($site['id']);
                                                $site['version'] = $site['version'].' (WPvivid Backup Pro)';
                                            }
                                            else{
                                                $site['login'] = 0;
                                                $site['status'] = 'WPvivid Backup Pro not claimed';
                                                $site['class'] = 'need-login';
                                            }
                                        }
                                        else{
                                            $site['status'] = 'WPvivid Backup Pro not claimed';
                                            $site['class'] = 'need-login';
                                        }
                                    }
                                } else {
                                    $site['active-wpvivid-pro'] = 0;
                                    $site['status'] = 'WPvivid Backup Pro not claimed';
                                    $site['class'] = 'need-active-wpvivid-pro';
                                }

                                break;
                            }
                        }
                    }
                    if (isset($_GET['search']) && !empty($_GET['search'])) {
                        $find = trim(sanitize_text_field($_GET['search']));
                        if (stripos($site['name'], $find) !== false || stripos($site['url'], $find) !== false) {
                            $websites_with_plugin[$site['id']] = $site;
                        }
                    } else {
                        $websites_with_plugin[$site['id']] = $site;
                    }
                }
            }
        }
        return $websites_with_plugin;
    }

    public function render_sync_websites_page($submit_id, $check_addon = false, $schedule_mould_name = '')
    {
        global $mainwp_wpvivid_extension_activator;

        if(intval($check_addon) === 1){
            $websites_with_plugin=$mainwp_wpvivid_extension_activator->get_websites_ex();
        }
        else{
            $websites_with_plugin=$mainwp_wpvivid_extension_activator->get_websites();
        }

        ?>

        <div style="padding: 10px;">
            <h2 style="margin-top: 10px;">Saving settings to child sites ...</h2><br>
            <?php
            if($submit_id === 'mwp_wpvivid_sync_schedule' && intval($check_addon) === 1){
                ?>
                <div class="mwp-wpvivid-block-bottom-space">
                    <span>Schedule Name:</span><span class="mwp_wpvivid_schedule_mould_name"><?php echo esc_html($schedule_mould_name); ?></span>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <div>
                        <label>
                            <input type="radio" name="mwp_wpvivid_default_schedule" value="default_only" checked />
                            <span>Set as the only active schedule (This will disable and replace existing schedules on the child sites)</span>
                        </label>
                    </div>
                    <div>
                        <label>
                            <input type="radio" name="mwp_wpvivid_default_schedule" value="default_append" />
                            <span>Set as an additional active schedule (This will add the new schedule to the child sites and will not disable existing schedules)</span>
                        </label>
                    </div>
                </div>
                <?php
            }
            else if($submit_id === 'mwp_wpvivid_sync_incremental_schedule' && intval($check_addon) === 1){
                ?>
                <div class="mwp-wpvivid-block-bottom-space">
                    <span>Schedule Name:</span><span class="mwp_wpvivid_schedule_mould_name"><?php echo esc_html($schedule_mould_name); ?></span>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <span>This will disable all existing schedules on the child sites.</span>
                </div>
                <?php
            }
            ?>
            <table class="ui single line table">
                <thead>
                <tr>
                    <th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox" checked /></span></th>
                    <th><?php esc_html_e( 'Site' ); ?></th>
                    <th><?php esc_html_e( 'URL' ); ?></th>
                    <th><?php esc_html_e( 'Status' ); ?></th>
                </tr>
                </thead>
                <tbody class="list:sites">
                <?php
                if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 )
                {
                    foreach ( $websites_with_plugin as $website )
                    {
                        $website_id = $website['id'];


                        if(intval($check_addon) !== intval($website['pro']))
                        {
                            continue;
                        }

                        if(intval($check_addon) === 1)
                        {
                            if(!$website['check-status'])
                            {
                                continue;
                            }
                        }
                        else {
                            if(!$website['install'])
                            {
                                continue;
                            }

                            if(!$website['active'])
                            {
                                continue;
                            }
                        }

                        if($website['individual'])
                        {
                            continue;
                        }

                        ?>
                        <tr class="mwp-wpvivid-sync-row">
                            <td class="check-column" website-id="<?php echo esc_attr($website_id); ?>"><span class="ui checkbox"><input type="checkbox" name="checked[]" checked /></span></td>
                            <td>
                                <a href="admin.php?page=managesites&dashboard=<?php echo esc_html($website_id); ?>"><?php echo esc_html(stripslashes($website['name'])); ?></a><br/>
                            </td>
                            <td>
                                <a href="<?php echo esc_attr($website['url']); ?>" target="_blank"><?php echo esc_html($website['url']); ?></a><br/>
                            </td>
                            <td class="mwp-wpvivid-progress" website-id="<?php echo esc_attr($website_id); ?>">
                                <span>Ready to update</span>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="9">No websites were found with the WPvivid Backup plugin installed.</td></tr>';
                }
                ?>
                </tbody>
                <tfoot>
                <tr>
                    <th class="row-title" colspan="4"><input class="ui green mini button"
                                                             id="<?php echo esc_attr($submit_id) ?>" type="button"
                                                             value="<?php esc_attr_e('Start Syncing Changes', 'mainwp-wpvivid-extension'); ?>"/></th>
                </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    public function render_sync_websites_remote_page($submit_id, $check_addon = false){
        global $mainwp_wpvivid_extension_activator;

        $websites_with_plugin=$mainwp_wpvivid_extension_activator->get_websites_ex();

        ?>
        <div style="padding: 10px;">
            <h2 style="margin-top: 10px;">Saving settings to child sites ...</h2><br>
            <table class="ui single line table">
                <thead>
                <tr>
                    <th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox" checked /></span></th>
                    <th><?php esc_html_e( 'Site' ); ?></th>
                    <th><?php esc_html_e( 'URL' ); ?></th>
                    <th><?php esc_html_e(' Custom Path' ); ?></th>
                    <th><?php esc_html_e( 'Status' ); ?></th>
                </tr>
                </thead>
                <tbody class="list:sites" id="mwp_wpvivid_sync_remote_list">
                <?php
                if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 )
                {
                    foreach ( $websites_with_plugin as $website )
                    {
                        $website_id = $website['id'];
                        if(!$website['install'])
                        {
                            continue;
                        }

                        if(!$website['active'])
                        {
                            continue;
                        }

                        if(intval($check_addon) !== intval($website['pro']))
                        {
                            continue;
                        }

                        if(intval($check_addon) === 1)
                        {
                            if(!$website['login'])
                            {
                                continue;
                            }
                        }

                        if($website['individual'])
                        {
                            continue;
                        }

                        ?>
                        <tr class="mwp-wpvivid-sync-row">
                            <td class="check-column" website-id="<?php echo esc_attr($website_id); ?>"><span class="ui checkbox"><input type="checkbox" name="checked[]" checked /></span></td>
                            <td>
                                <a href="admin.php?page=managesites&dashboard=<?php echo esc_url($website_id); ?>"><?php echo esc_html(stripslashes($website['name'])); ?></a><br/>
                            </td>
                            <td>
                                <a href="<?php echo esc_url($website['url']); ?>" target="_blank"><?php echo esc_html($website['url']); ?></a><br/>
                            </td>
                            <td>
                                <span>Domain</span>
                                <input class="ui green mini button remote-path-edit" type="button" value="<?php esc_attr_e('Edit', 'mainwp-wpvivid-extension'); ?>" />
                            </td>
                            <td class="mwp-wpvivid-progress" website-id="<?php echo esc_attr($website_id); ?>">
                                <span>Ready to update</span>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="9">No websites were found with the WPvivid Backup plugin installed.</td></tr>';
                }
                ?>
                </tbody>
                <tfoot>
                <tr>
                    <th class="row-title" colspan="5"><input class="ui green mini button"
                                                             id="<?php echo esc_attr($submit_id) ?>" type="button"
                                                             value="<?php esc_attr_e('Start Syncing Changes', 'mainwp-wpvivid-extension'); ?>"/></th>
                </tr>
                </tfoot>
            </table>
        </div>
        <?php
    }

    public function render_check_report_page($website_id, $pro, $website_name){
        $report = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($website_id, 'report_addon', array());
        ?>
        <div style="padding: 10px;">
            <div class="mwp-wpvivid-block-bottom-space">Note: The list below includes the last 10 backup information.</div>
            <div class="mwp-wpvivid-block-bottom-space"><span>Site Title: </span><span><?php echo esc_html($website_name); ?></span></div>
            <table class="widefat mwp-wpvivid-block-bottom-space">
                <thead>
                    <th>Backup Time</th>
                    <th>Status</th>
                </thead>
                <tbody>
                <?php
                if(isset($report) && !empty($report)) {
                    usort($report, function($a, $b){
                        if($a['backup_time'] === $b['backup_time']){
                            return 0;
                        }
                        else if($a['backup_time'] > $b['backup_time']){
                            return -1;
                        }
                        else{
                            return 1;
                        }
                    });

                    $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($website_id, 'time_zone', '');
                    if(empty($time_zone)){
                        $time_zone = 0;
                    }

                    foreach ($report as $task_id => $report_option) {
                        if(isset($report_option['task_id']) && !empty($report_option['task_id']))
                        {
                            ?>
                            <tr>
                                <td><?php echo esc_html(date("H:i:s - m/d/Y", $report_option['backup_time'] + $time_zone * 60 * 60)); ?></td>
                                <td><?php echo esc_html($report_option['status']); ?></td>
                            </tr>
                            <?php
                        }
                    }
                }
                ?>
                </tbody>
            </table>
            <div>
                <a href="admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=dashboard" class="ui green mini button">Return to WPvivid Backup Dashboard</a>
            </div>
        </div>
        <?php
    }
}

global $mainwp_wpvivid_extension_activator;
$mainwp_wpvivid_extension_activator = new Mainwp_WPvivid_Extension_Activator();