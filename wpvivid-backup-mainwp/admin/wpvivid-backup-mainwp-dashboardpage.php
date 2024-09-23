<?php

class Mainwp_WPvivid_Extension_DashboardPage
{
    private $select_pro;

    public function __construct()
    {
        $this->load_dashboard_ajax();
    }

    public function set_dashboard_info($select_pro=0)
    {
        $this->select_pro=$select_pro;
    }

    public function load_dashboard_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_refresh_mainwp_status', array($this, 'refresh_mainwp_status'));
        add_action('wp_ajax_mwp_wpvivid_sync_childsite', array($this, 'sync_childsite'));
        add_action('wp_ajax_mwp_wpvivid_check_repair_pro', array($this, 'check_repair_pro'));
        add_action('wp_ajax_mwp_wpvivid_repair_pro', array($this, 'repair_pro'));

        add_action('wp_ajax_mwp_wpvivid_check_free_plugin_status', array($this, 'check_free_plugin_status'));
        add_action('wp_ajax_mwp_wpvivid_check_pro_plugin_status', array($this, 'check_pro_plugin_status'));
        add_action('wp_ajax_mwp_wpvivid_check_staging_plugin_status', array($this, 'check_staging_plugin_status'));
        add_action('wp_ajax_mwp_wpvivid_check_login_status', array($this, 'check_login_status'));

        add_action('wp_ajax_mwp_wpvivid_active_plugin', array($this, 'active_plugin'));
        add_action('wp_ajax_mwp_wpvivid_upgrade_plugin', array($this, 'upgrade_plugin'));
    }

    public function refresh_mainwp_status()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try
        {
            $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
            if($login_options !== false && isset($login_options['wpvivid_pro_account']))
            {
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
                if($ret['result']=='success')
                {
                    $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
                    $login_options['wpvivid_pro_login_cache'] = $ret['status'];
                    $mainwp_wpvivid_extension_activator->set_global_login_addon($login_options);
                }
                else{
                    $ret['result']='failed';
                    if(!isset($ret['error'])){
                        $ret['error'] = 'Failed to connect to authentication server, please try again later.';
                    }
                }
                echo wp_json_encode($ret);
                die();
            }
            else{
                $ret['result'] = 'failed';
                $ret['error'] = 'Failed to get previously entered login information, please login again.';
                echo wp_json_encode($ret);
                die();
            }

        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function sync_childsite()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['wp_id']) && isset($_POST['isGlobalSync'])){
                MainWP\Dashboard\MainWP_Updates_Overview::dismiss_sync_errors( false );
                $website = null;
                $wp_id   = isset( $_POST['wp_id'] ) ? intval( sanitize_key($_POST['wp_id']) ) : false;
                if ( $wp_id )
                {
                    $website = MainWP\Dashboard\MainWP_DB::instance()->get_website_by_id( $wp_id );
                }
                if ( $website != null )
                {
                    MainWP\Dashboard\MainWP_Sync::sync_website( $website );
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function check_repair_pro()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $plugins = $mainwp_wpvivid_extension_activator->mwp_wpvivid_get_website_plugins_list($site_id);
                $need_repair = false;
                $slug_name = '';
                if (is_array($plugins) && count($plugins) != 0)
                {
                    foreach ($plugins as $plugin)
                    {
                        if (strpos($plugin['slug'], 'wpvivid-backup-pro.php') !== false)
                        {
                            if ((strcmp($plugin['slug'], "wpvivid-backup-pro/wpvivid-backup-pro.php") !== 0))
                            {
                                $need_repair = true;
                                $slug_name = $plugin['slug'];
                                $mainwp_wpvivid_extension_activator->set_is_login($site_id, 0);
                                break;
                            }

                            //use for update, if lower 2.0.9, uninstall
                            if (version_compare($plugin['version'], '2.0.9', '<=')) {
                                $need_repair = true;
                                $slug_name = $plugin['slug'];
                                $mainwp_wpvivid_extension_activator->set_is_login($site_id, 0);
                                break;
                            }
                        }
                    }
                }

                if($need_repair){
                    $_POST['websiteId'] = sanitize_key($_POST['site_id']);
                    $_POST['plugins'][] = $slug_name;
                    do_action('mainwp_deletePlugin');
                }

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

    public function repair_pro()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        if(isset($_POST['site_id']) && !empty($_POST['site_id'])) {
            $_POST['websiteId'] = sanitize_key($_POST['site_id']);
            do_action('mainwp_deletePlugin');
        }
        die();
    }

    public function check_free_plugin_status()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && isset($_POST['type']) && !empty($_POST['type']))
            {
                $ret['result'] = 'success';

                $site_id = sanitize_key($_POST['site_id']);
                $action_type = sanitize_text_field($_POST['type']);
                $plugins = $mainwp_wpvivid_extension_activator->mwp_wpvivid_get_website_plugins_list($site_id);

                if($action_type === 'install'){
                    if(!isset($_POST['plugins']))
                    {
                        $ret['result']='failed';
                        $ret['error']='Please select the plugin from the list to install.';
                        echo wp_json_encode($ret);
                        die();
                    }
                    $plugins_addons=array_map( 'sanitize_text_field', $_POST['plugins'] );
                }
                else{
                    $plugins_addons = array();
                    $addons_info = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'addons_info', array());
                    if(isset($addons_info) && !empty($addons_info)){
                        if(isset($addons_info['backup_pro'])){
                            if($addons_info['backup_pro']['action'] !== 'Install'){
                                $plugins_addons[] = 'backup_pro';
                            }
                        }
                        else{
                            $plugins_addons[] = 'backup_pro';
                        }

                        if(isset($addons_info['imgoptim_pro'])){
                            if($addons_info['imgoptim_pro']['action'] !== 'Install'){
                                $plugins_addons[] = 'imgoptim_pro';
                            }
                        }
                        else{
                            $plugins_addons[] = 'imgoptim_pro';
                        }
                    }
                    else{
                        $plugins_addons[] = 'backup_pro';
                        $plugins_addons[] = 'imgoptim_pro';
                    }
                }

                if(in_array('backup_pro', $plugins_addons)){
                    //check backup free
                    $reg_backup_string = 'wpvivid-backuprestore/wpvivid-backuprestore.php';
                    $is_install_backup_free = false;
                    if (is_array($plugins) && count($plugins) != 0)
                    {
                        foreach ($plugins as $plugin)
                        {
                            if ((strcmp($plugin['slug'], $reg_backup_string) === 0))
                            {
                                $is_install_backup_free = true;

                                if (!$plugin['active'])
                                {
                                    //active backup free
                                    $website = MainWP\Dashboard\MainWP_DB::instance()->get_website_by_id( $site_id );
                                    $information = MainWP\Dashboard\MainWP_Connect::fetch_url_authed(
                                        $website,
                                        'plugin_action',
                                        array(
                                            'action' => 'activate',
                                            'plugin' => $reg_backup_string,
                                        )
                                    );

                                    if ( ! isset( $information['status'] ) || ( 'SUCCESS' !== $information['status'] ) ) {
                                        die( wp_json_encode( array( 'error' => __( 'Active WPvivid Backup Free Failed.', 'mainwp' ) ) ) );
                                    }
                                }

                                break;
                            }
                        }
                    }
                    if(!$is_install_backup_free)
                    {
                        //install backup free
                        include_once(ABSPATH . '/wp-admin/includes/plugin-install.php');
                        $api = plugins_api('plugin_information', array(
                            'slug' => 'wpvivid-backuprestore',
                            'fields' => array('sections' => false),
                        ));
                        $url = $api->download_link;

                        MainWP\Dashboard\MainWP_Utility::end_session();

                        $type = 'plugin';
                        //Fetch info..
                        $post_data = array(
                            'type' => $type,
                        );

                        $post_data['activatePlugin'] = 'yes';
                        $post_data['overwrite'] = true;

                        // hook to support addition data: wpadmin_user, wpadmin_passwd
                        $post_data = apply_filters( 'mainwp_perform_install_data', $post_data );

                        $post_data['url'] = wp_json_encode( $url );

                        $output         = new stdClass();
                        $output->ok     = array();
                        $output->errors = array();
                        $websites       = array( MainWP_DB::Instance()->getWebsiteById( $site_id ) );
                        MainWP\Dashboard\MainWP_Connect::fetch_urls_authed( $websites, 'installplugintheme', $post_data, array(
                            MainWP\Dashboard\MainWP_Install_Bulk::get_class_name(),
                            'install_plugin_theme_handler',
                        ), $output, null, array( 'upgrade' => true ) );

                        if(isset($output->ok) && !empty($output->ok)){
                            Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_update_single_option($site_id, 'is_install', 1);
                        }
                        else{
                            die( wp_json_encode( array( 'error' => __( 'Install WPvivid Backup Free Failed.', 'mainwp' ) ) ) );
                        }
                    }
                    else{
                        //update backup free
                        $dbwebsites = $mainwp_wpvivid_extension_activator->mwp_get_child_websites();
                        foreach ($dbwebsites as $website) {
                            if ($website)
                            {
                                if ($website->id === $site_id)
                                {
                                    $plugin_upgrades = json_decode($website->plugin_upgrades, 1);
                                    if (is_array($plugin_upgrades) && count($plugin_upgrades) > 0)
                                    {
                                        if (isset($plugin_upgrades['wpvivid-backuprestore/wpvivid-backuprestore.php']))
                                        {
                                            $upgrade = $plugin_upgrades['wpvivid-backuprestore/wpvivid-backuprestore.php'];
                                            if (isset($upgrade['update']))
                                            {
                                                $websiteId = $site_id;
                                                $type      = 'plugin';
                                                $slugs     = array();
                                                $slugs[]   = $reg_backup_string;
                                                $error     = '';
                                                if ( 'plugin' === $type && ! MainWP\Dashboard\mainwp_current_user_have_right( 'dashboard', 'update_plugins' ) ) {
                                                    $error = MainWP\Dashboard\mainwp_do_not_have_permissions( __( 'update plugins', 'mainwp' ), false );
                                                }

                                                if ( ! empty( $error ) ) {
                                                    die( wp_json_encode( array( 'error' => $error ) ) );
                                                }

                                                if ( MainWP\Dashboard\MainWP_Utility::ctype_digit( $websiteId ) ) {
                                                    $website = MainWP\Dashboard\MainWP_DB::instance()->get_website_by_id( $websiteId );
                                                    if ( MainWP\Dashboard\MainWP_System_Utility::can_edit_website( $website ) ) {
                                                        $information = MainWP\Dashboard\MainWP_Connect::fetch_url_authed(
                                                            $website,
                                                            'upgradeplugintheme',
                                                            array(
                                                                'type' => $type,
                                                                'list' => urldecode( implode( ',', $slugs ) ),
                                                            )
                                                        );
                                                        if ( isset( $information['sync'] ) ) {
                                                            unset( $information['sync'] );
                                                        }

                                                        if($information && $information['upgrades'][$reg_backup_string]){
                                                        }
                                                        else{
                                                            die( wp_json_encode( array( 'error' => __( 'Update WPvivid Backup Free Failed.', 'mainwp' ) ) ) );
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if(in_array('imgoptim_pro', $plugins_addons)){
                    //check imgopt free
                    $reg_imgopt_string = 'wpvivid-imgoptim/wpvivid-imgoptim.php';
                    $is_install_imgopt_free = false;
                    if (is_array($plugins) && count($plugins) != 0)
                    {
                        foreach ($plugins as $plugin)
                        {
                            if ((strcmp($plugin['slug'], $reg_imgopt_string) === 0))
                            {
                                $is_install_imgopt_free = true;

                                if (!$plugin['active'])
                                {
                                    //active imgopt free
                                    $website = MainWP\Dashboard\MainWP_DB::instance()->get_website_by_id( $site_id );
                                    $information = MainWP\Dashboard\MainWP_Connect::fetch_url_authed(
                                        $website,
                                        'plugin_action',
                                        array(
                                            'action' => 'activate',
                                            'plugin' => $reg_imgopt_string,
                                        )
                                    );

                                    if ( ! isset( $information['status'] ) || ( 'SUCCESS' !== $information['status'] ) ) {
                                        die( wp_json_encode( array( 'error' => __( 'Active WPvivid Imgopt Free Failed.', 'mainwp' ) ) ) );
                                    }
                                }

                                break;
                            }
                        }
                    }
                    if(!$is_install_imgopt_free)
                    {
                        //install imgopt free
                        include_once(ABSPATH . '/wp-admin/includes/plugin-install.php');
                        $api = plugins_api('plugin_information', array(
                            'slug' => 'wpvivid-imgoptim',
                            'fields' => array('sections' => false),
                        ));
                        $url = $api->download_link;

                        MainWP\Dashboard\MainWP_Utility::end_session();

                        $type = 'plugin';
                        //Fetch info..
                        $post_data = array(
                            'type' => $type,
                        );

                        $post_data['activatePlugin'] = 'yes';
                        $post_data['overwrite'] = true;

                        // hook to support addition data: wpadmin_user, wpadmin_passwd
                        $post_data = apply_filters( 'mainwp_perform_install_data', $post_data );

                        $post_data['url'] = wp_json_encode( $url );

                        $output         = new stdClass();
                        $output->ok     = array();
                        $output->errors = array();
                        $websites       = array( MainWP_DB::Instance()->getWebsiteById( $site_id ) );
                        MainWP\Dashboard\MainWP_Connect::fetch_urls_authed( $websites, 'installplugintheme', $post_data, array(
                            MainWP\Dashboard\MainWP_Install_Bulk::get_class_name(),
                            'install_plugin_theme_handler',
                        ), $output, null, array( 'upgrade' => true ) );

                        if(isset($output->ok) && !empty($output->ok)){
                        }
                        else{
                            die( wp_json_encode( array( 'error' => __( 'Install WPvivid Imgopt Free Failed.', 'mainwp' ) ) ) );
                        }
                    }
                    else{
                        //update imgopt free
                        $dbwebsites = $mainwp_wpvivid_extension_activator->mwp_get_child_websites();
                        foreach ($dbwebsites as $website) {
                            if ($website)
                            {
                                if ($website->id === $site_id)
                                {
                                    $plugin_upgrades = json_decode($website->plugin_upgrades, 1);
                                    if (is_array($plugin_upgrades) && count($plugin_upgrades) > 0)
                                    {
                                        if (isset($plugin_upgrades['wpvivid-imgoptim/wpvivid-imgoptim.php']))
                                        {
                                            $upgrade = $plugin_upgrades['wpvivid-imgoptim/wpvivid-imgoptim.php'];
                                            if (isset($upgrade['update']))
                                            {
                                                $websiteId = $site_id;
                                                $type      = 'plugin';
                                                $slugs     = array();
                                                $slugs[]   = $reg_imgopt_string;
                                                $error     = '';
                                                if ( 'plugin' === $type && ! MainWP\Dashboard\mainwp_current_user_have_right( 'dashboard', 'update_plugins' ) ) {
                                                    $error = MainWP\Dashboard\mainwp_do_not_have_permissions( __( 'update plugins', 'mainwp' ), false );
                                                }

                                                if ( ! empty( $error ) ) {
                                                    die( wp_json_encode( array( 'error' => $error ) ) );
                                                }

                                                if ( MainWP\Dashboard\MainWP_Utility::ctype_digit( $websiteId ) ) {
                                                    $website = MainWP\Dashboard\MainWP_DB::instance()->get_website_by_id( $websiteId );
                                                    if ( MainWP\Dashboard\MainWP_System_Utility::can_edit_website( $website ) ) {
                                                        $information = MainWP\Dashboard\MainWP_Connect::fetch_url_authed(
                                                            $website,
                                                            'upgradeplugintheme',
                                                            array(
                                                                'type' => $type,
                                                                'list' => urldecode( implode( ',', $slugs ) ),
                                                            )
                                                        );
                                                        if ( isset( $information['sync'] ) ) {
                                                            unset( $information['sync'] );
                                                        }

                                                        if($information && $information['upgrades'][$reg_imgopt_string]){
                                                        }
                                                        else{
                                                            die( wp_json_encode( array( 'error' => __( 'Update WPvivid Imgopt Free Failed.', 'mainwp' ) ) ) );
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

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

    public function check_pro_plugin_status()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && isset($_POST['type']) && !empty($_POST['type']))
            {
                $ret['result'] = 'success';
                $site_id = sanitize_key($_POST['site_id']);
                $action_type = sanitize_text_field($_POST['type']);

                $server=new Mainwp_WPvivid_Connect_server();
                $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
                $user_info='';
                if($login_options !== false && isset($login_options['wpvivid_pro_account']))
                {
                    if(isset($login_options['wpvivid_pro_account']['user_info']))
                    {
                        $user_info = $login_options['wpvivid_pro_account']['user_info'];
                    }
                    else
                    {
                        $output['result'] = 'failed';
                        $output['error'] = 'Failed to get login account, please try again later.';
                        wp_send_json( $output );
                    }
                }
                else{
                    $output['result'] = 'failed';
                    $output['error'] = 'Failed to get login account, please try again later.';
                    wp_send_json( $output );
                }


                if($action_type === 'install'){
                    if(!isset($_POST['plugins']))
                    {
                        $ret['result']='failed';
                        $ret['error']='Please select the plugin from the list to install.';
                        echo wp_json_encode($ret);
                        die();
                    }
                    $plugins_addons=array_map( 'sanitize_text_field', $_POST['plugins'] );
                }
                else{
                    $plugins_addons = array();
                    $addons_info = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'addons_info', array());
                    if(isset($addons_info) && !empty($addons_info)){
                        if(isset($addons_info['backup_pro'])){
                            if($addons_info['backup_pro']['action'] !== 'Install'){
                                $plugins_addons[] = 'backup_pro';
                            }
                        }
                        else{
                            $plugins_addons[] = 'backup_pro';
                        }

                        if(isset($addons_info['imgoptim_pro'])){
                            if($addons_info['imgoptim_pro']['action'] !== 'Install'){
                                $plugins_addons[] = 'imgoptim_pro';
                            }
                        }
                        else{
                            $plugins_addons[] = 'imgoptim_pro';
                        }

                        if(isset($addons_info['white_label'])){
                            if($addons_info['white_label']['action'] !== 'Install'){
                                $plugins_addons[] = 'white_label';
                            }
                        }
                        else{
                            $plugins_addons[] = 'white_label';
                        }

                        if(isset($addons_info['role_cap'])){
                            if($addons_info['role_cap']['action'] !== 'Install'){
                                $plugins_addons[] = 'role_cap';
                            }
                        }
                        else{
                            $plugins_addons[] = 'role_cap';
                        }
                    }
                    else{
                        $plugins_addons[] = 'backup_pro';
                        $plugins_addons[] = 'imgoptim_pro';
                        $plugins_addons[] = 'white_label';
                        $plugins_addons[] = 'role_cap';
                    }
                }

                //plugins
                $addons = array();
                if(in_array('backup_pro', $plugins_addons)){
                    $addons['wpvivid-backup-pro-all-in-one'] = 'wpvivid-backup-pro-all-in-one';
                    $addons['wpvivid-backup-pro-addons-1'] = 'wpvivid-backup-pro-addons-1';
                }
                if(in_array('imgoptim_pro', $plugins_addons)){
                    $addons['wpvivid-imgoptim-pro'] = 'wpvivid-imgoptim-pro';
                }
                if(in_array('white_label', $plugins_addons)){
                    $addons['wpvivid-white-label-addons'] = 'wpvivid-white-label-addons';
                }
                if(in_array('role_cap', $plugins_addons)){
                    $addons['wpvivid-role-cap-addons'] = 'wpvivid-role-cap-addons';
                }

                if(empty($addons))
                {
                    $output['result'] = 'failed';
                    $output['error'] = 'Failed to get WPvivid Dashboard download url, please try again later.';
                    wp_send_json( $output );
                }

                $ret=$server->get_dashboard_download_link($user_info,$addons);
                if($ret['result']=='success')
                {
                    $url = $ret['download_link'];

                    MainWP\Dashboard\MainWP_Utility::end_session();

                    $type = 'plugin';
                    //Fetch info..
                    $post_data = array(
                        'type' => $type,
                    );

                    $post_data['activatePlugin'] = 'yes';
                    $post_data['overwrite'] = true;

                    // hook to support addition data: wpadmin_user, wpadmin_passwd
                    $post_data = apply_filters( 'mainwp_perform_install_data', $post_data );

                    $post_data['url'] = wp_json_encode( $url );

                    $output         = new stdClass();
                    $output->ok     = array();
                    $output->errors = array();
                    $websites       = array( MainWP_DB::Instance()->getWebsiteById( $site_id ) );
                    MainWP\Dashboard\MainWP_Connect::fetch_urls_authed( $websites, 'installplugintheme', $post_data, array(
                        MainWP\Dashboard\MainWP_Install_Bulk::get_class_name(),
                        'install_plugin_theme_handler',
                    ), $output, null, array( 'upgrade' => true ) );

                    if(isset($output->ok) && !empty($output->ok))
                    {
                        die( wp_json_encode( array( 'result' => 'success' ) ) );
                    }
                    else{
                        die( wp_json_encode( array( 'result' => 'failed','error' => $output->errors ) ) );
                    }
                }
                else
                {
                    $output['result'] = 'failed';
                    $output['error'] = 'Failed to get WPvivid Dashboard download url, please try again later.';
                    wp_send_json( $output );
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function check_staging_plugin_status()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && isset($_POST['type']) && !empty($_POST['type']))
            {
                $ret['result'] = 'success';
                $site_id = sanitize_key($_POST['site_id']);
                $action_type = sanitize_text_field($_POST['type']);

                $server=new Mainwp_WPvivid_Connect_server();
                $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
                $user_info='';
                if($login_options !== false && isset($login_options['wpvivid_pro_account']))
                {
                    if(isset($login_options['wpvivid_pro_account']['user_info']))
                    {
                        $user_info = $login_options['wpvivid_pro_account']['user_info'];
                    }
                    else
                    {
                        $output['result'] = 'failed';
                        $output['error'] = 'Failed to get login account, please try again later.';
                        wp_send_json( $output );
                    }
                }
                else{
                    $output['result'] = 'failed';
                    $output['error'] = 'Failed to get login account, please try again later.';
                    wp_send_json( $output );
                }

                $need_download_staging = false;
                if($action_type === 'install'){
                    if(!isset($_POST['is_install_staging']) || $_POST['is_install_staging'] == '0')
                    {
                        $need_download_staging = false;
                    }
                    else
                    {
                        $need_download_staging = true;
                    }
                }
                else{
                    $plugins_addons = array();
                    $addons_info = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'addons_info', array());
                    if(isset($addons_info) && !empty($addons_info)){
                        if(isset($addons_info['staging_pro'])){
                            if($addons_info['staging_pro']['action'] !== 'Install'){
                                $need_download_staging = true;
                            }
                        }
                        else{
                            $need_download_staging = false;
                        }
                    }
                    else{
                        $need_download_staging = false;
                    }
                }

                $addons = array();
                $addons['wpvivid-staging']='wpvivid-staging';

                if($need_download_staging)
                {
                    $ret=$server->get_staging_download_link($user_info, $addons);
                    if($ret['result']=='success')
                    {
                        $url = $ret['download_link'];

                        MainWP\Dashboard\MainWP_Utility::end_session();

                        $type = 'plugin';
                        //Fetch info..
                        $post_data = array(
                            'type' => $type,
                        );

                        $post_data['activatePlugin'] = 'yes';
                        $post_data['overwrite'] = true;

                        // hook to support addition data: wpadmin_user, wpadmin_passwd
                        $post_data = apply_filters( 'mainwp_perform_install_data', $post_data );

                        $post_data['url'] = wp_json_encode( $url );

                        $output         = new stdClass();
                        $output->ok     = array();
                        $output->errors = array();
                        $websites       = array( MainWP_DB::Instance()->getWebsiteById( $site_id ) );
                        MainWP\Dashboard\MainWP_Connect::fetch_urls_authed( $websites, 'installplugintheme', $post_data, array(
                            MainWP\Dashboard\MainWP_Install_Bulk::get_class_name(),
                            'install_plugin_theme_handler',
                        ), $output, null, array( 'upgrade' => true ) );

                        if(isset($output->ok) && !empty($output->ok))
                        {
                            die( wp_json_encode( array( 'result' => 'success' ) ) );
                        }
                        else{
                            die( wp_json_encode( array( 'result' => 'failed','error' => $output->errors ) ) );
                        }
                    }
                    else
                    {
                        $output['result'] = 'failed';
                        $output['error'] = 'Failed to get WPvivid Dashboard download url, please try again later.';
                        wp_send_json( $output );
                    }
                }
                else
                {
                    die( wp_json_encode( array( 'result' => 'success' ) ) );
                }
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function check_login_status()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);

                $ret['result'] = 'success';

                $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
                if($login_options !== false && isset($login_options['wpvivid_pro_account']))
                {
                    if(isset($login_options['wpvivid_pro_account']['user_info']))
                    {
                        $user_info = $login_options['wpvivid_pro_account']['user_info'];
                        $server=new Mainwp_WPvivid_Connect_server();

                        $ret=$server->active_site($user_info, $site_id);
                        if($ret['result']=='success')
                        {
                            if($ret['status']['check_active']){
                                $data = array();
                                $data['wpvivid_dashboard_info'] = $ret['status'];
                                $ret = $server->get_mainwp_encrypt_token($ret['token']);
                                if($ret['result']=='success')
                                {
                                    $data['wpvivid_pro_user']['token'] = $ret['token'];

                                    $post_data['mwp_action'] = 'wpvivid_login_account_addon_mainwp';
                                    $post_data['login_info'] = $data;

                                    $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                                    if (isset($information['error']))
                                    {
                                        $ret['result'] = 'failed';
                                        $ret['error'] = $information['error'];
                                    } else {
                                        $ret['result'] = 'success';
                                        $mainwp_wpvivid_extension_activator->set_is_login($site_id, 1);
                                        if(isset($information['need_update']))
                                        {
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
                                        $mainwp_wpvivid_extension_activator->set_need_update($site_id, $need_update);
                                        if(isset($information['current_version'])){
                                            $current_version = $information['current_version'];
                                            $mainwp_wpvivid_extension_activator->set_current_version($site_id, $current_version);
                                        }
                                    }
                                }
                                else{
                                    $ret['result'] = 'failed';
                                    $ret['error'] = 'Failed to encrypt token, please login again.';
                                }
                            }
                            else{
                                $ret['result'] = 'failed';
                                $ret['error'] = 'Failed to activate the site, please login again.';
                            }
                        }
                        else{
                            $ret['result'] = 'failed';
                            $ret['error'] = 'Failed to activate the site, please login again.';
                        }
                    }
                    else
                    {
                        $ret['result'] = 'failed';
                        $ret['error'] = 'Failed to get login account, please try again later.';
                        echo wp_json_encode($ret);
                    }
                }
                else{
                    $ret['result'] = 'failed';
                    $ret['error'] = 'Failed to get previously entered login information, please login again.';
                }
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

    public function active_plugin()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        if(isset($_POST['site_id']) && !empty($_POST['site_id'])) {
            $_POST['websiteId'] = sanitize_key($_POST['site_id']);
            do_action('mainwp_activePlugin');
        }
        die();
    }

    public function upgrade_plugin()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        if(isset($_POST['site_id']) && !empty($_POST['site_id'])) {
            $_POST['websiteId'] = sanitize_key($_POST['site_id']);
            do_action('mainwp_upgradePluginTheme');
        }
        die();
    }

    public function need_login()
    {
        ?>
        <div class="notice notice-warning inline" style="margin: 0; padding-top: 10px; margin-bottom: 10px;"><p>Notice: Please <a onclick="mwp_wpvivid_switch_login_page();" style="cursor: pointer;">login to your WPvivid Backup Pro account</a> first.</p>
            <button type="button" class="notice-dismiss" onclick="mwp_click_dismiss_notice(this);">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
        <script>
            function mwp_wpvivid_switch_login_page()
            {
                location.href='<?php echo 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=login'; ?>';
            }
        </script>
        <?php
    }

    public function check_license()
    {
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
        if ($login_options === false||!isset($login_options['wpvivid_pro_account']))
        {
            $this->need_login();
            return false;
        }
        else
        {
            if(isset($login_options['wpvivid_pro_account']['user_info']))
            {
               return true;
            }
            else
            {
                $server=new Mainwp_WPvivid_Connect_server();
                if(isset($login_options['wpvivid_pro_account']['license']))
                {
                    $license = $login_options['wpvivid_pro_account']['license'];
                    $user_info=$server->get_token($license,'','');
                }
                else {
                    $email = $login_options['wpvivid_pro_account']['email'];
                    $password = $login_options['wpvivid_pro_account']['password'];
                    $user_info=$server->get_token('',$email,$password);
                }

                if($user_info!==false)
                {
                    $login_options['wpvivid_pro_account']['user_info']=$user_info;
                    $mainwp_wpvivid_extension_activator->set_global_login_addon($login_options);
                    return true;
                }
                else
                {
                    $this->need_login();
                    return false;
                }
            }
        }
    }

    public function need_product()
    {
        ?>
        <div class="notice notice-warning inline" style="margin: 0; padding-top: 10px; margin-bottom: 10px;"><p>Notice: No products available for account. If you have already purchased the product, please log in again.</p>
            <button type="button" class="notice-dismiss" onclick="mwp_click_dismiss_notice(this);">
                <span class="screen-reader-text">Dismiss this notice.</span>
            </button>
        </div>
        <?php
    }

    public function check_product()
    {
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
        if(isset($login_options['wpvivid_pro_login_cache']['plugins'])){
            return true;
        }
        else{
            $this->need_product();
            return false;
        }
    }

    public function render()
    {
        global $mainwp_wpvivid_extension_activator;
        if ($this->select_pro)
        {
            $select_pro_check = 'checked';
        } else {
            $select_pro_check = '';
        }

        ?>
        <div style="padding: 10px;">
            <div class="mwp-wpvivid-block-bottom-space" style="background: #fff;">
                <div class="postbox" style="padding: 10px; margin-bottom: 0;">
                    <div style="float: left; margin-top: 7px; margin-right: 25px;"><?php esc_html_e('Switch to WPvivid Backup Pro'); ?></div>
                    <div class="ui toggle checkbox mwp-wpvivid-pro-swtich" style="float: left; margin-top:4px; margin-right: 10px;">
                        <input type="checkbox" <?php echo esc_attr($select_pro_check); ?> />
                        <label for=""></label>
                    </div>
                    <div style="float: left;"><input class="ui green mini button" type="button" value="Save" onclick="mwp_wpvivid_switch_pro_setting();"/></div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div style="clear: both;"></div>
            <?php
            if($this->select_pro)
            {
                if($this->check_license())
                {
                    $this->render_pro();
                }
                else
                {
                    return;
                }
            }
            else
            {
                $this->render_free();
            }
            ?>
        </div>
        <script>
            function mwp_wpvivid_switch_pro_setting(){
                if(jQuery('.mwp-wpvivid-pro-swtich').find('input:checkbox').prop('checked')){
                    var pro_setting = 1;
                }
                else{
                    var pro_setting = 0;
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_switch_pro_setting',
                    'pro_setting': pro_setting
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            location.reload();
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_refresh_dashboard_page() {
                location.href='<?php echo 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=dashboard'; ?>';
            }


            var mwp_wpvivid_get_mainwp_status = false;
            var mwp_wpvivid_has_select_update = false;
            var mwp_wpvivid_has_update = false;
            var mwp_wpvivid_update_bulkMaxThreads = 1;
            var mwp_wpvivid_update_bulkCurrentThreads = 0;
            var mwp_wpvivid_has_select_login = false;
            var mwp_wpvivid_has_login = false;
            var mwp_wpvivid_is_install_backup = false;
            var mwp_wpvivid_is_install_imgopt = false;
            var mwp_wpvivid_login_bulkMaxThreads = 1;
            var mwp_wpvivid_login_bulkCurrentThreads = 0;

            jQuery('#mwp_wpvivid_plugin_doaction_btn').on('click', function()
            {
                var bulk_act = jQuery( '#mwp_wpvivid_plugin_action' ).val();
                mwp_wpvivid_plugin_do_bulk_action( bulk_act );
            });

            function mwp_wpvivid_update_install_v2_confirm(type)
            {
                if(type === 'update'){
                    var descript = 'Are you sure you want to update to the latest version of WPvivid Backup Pro 2.0 on the site(s)?';
                }
                else{
                    var descript = 'Are you sure you want to install and claim WPvivid Backup Pro 2.0 on the site(s)?';
                }
                var ret = confirm(descript);
                return ret;
            }

            function mwp_wpvivid_loop_next_thread(selector, type)
            {
                if(type === 'install'){
                    mwp_wpvivid_login_bulkCurrentThreads--;
                    mwp_wpvivid_plugin_login_start_next(selector);
                }
                else{
                    mwp_wpvivid_update_bulkCurrentThreads--;
                    mwp_wpvivid_plugin_upgrade_start_next_ex(selector);
                }
            }

            function mwp_wpvivid_sync_child_site(site_id)
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_sync_childsite',
                    'wp_id': site_id,
                    'isGlobalSync': true
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('sync plugin', textStatus, errorThrown);
                }, 0);
            }

            function mwp_wpvivid_check_has_install_claim_item(selector)
            {
                var check_res = false;
                var has_item = false;
                jQuery(selector).each(function(){
                    has_item = true;
                    check_res = true;
                });
                if(!has_item){
                    alert('Please select at least one item.');
                }
                return check_res;
            }

            function mwp_wpvivid_check_has_update_item(selector)
            {
                var check_res = false;
                var has_item = false;
                var has_need_update_item = false;
                jQuery(selector).each(function(){
                    has_item = true;
                    if(jQuery(this).closest('tr').hasClass('need-update')){
                        has_need_update_item = true;
                        check_res = true;
                        return false;
                    }
                });
                if(!has_item){
                    alert('Please select at least one item.');
                }
                else if(!has_need_update_item){
                    alert('There is no item need update.');
                }
                return check_res;
            }

            function mwp_wpvivid_plugin_do_bulk_action(act)
            {
                var selector = '';
                switch (act)
                {
                    case 'update-selected':
                        selector = '#the-mwp-wpvivid-list  tr';
                        jQuery( selector ).addClass( 'queue' );
                        mwp_wpvivid_plugin_upgrade_start_next( selector );
                        break;
                    case 'update-selected-ex':
                        if(mwp_wpvivid_update_install_v2_confirm('update')){
                            selector = '#the-mwp-wpvivid-list tr .check-column input[type="checkbox"]:checked';
                            jQuery( selector ).addClass( 'queue' );
                            if(mwp_wpvivid_check_has_update_item(selector)){
                                mwp_wpvivid_plugin_upgrade_start_next_ex( selector );
                            }
                        }
                        break;
                    case 'login-selected':
                        if(mwp_wpvivid_update_install_v2_confirm('claim')) {
                            selector = '#the-mwp-wpvivid-list tr .check-column input[type="checkbox"]:checked';
                            jQuery( selector ).addClass( 'queue' );
                            if(mwp_wpvivid_check_has_install_claim_item(selector)){
                                mwp_wpvivid_refresh_mainwp_status(selector);
                            }
                        }
                        break;
                }
            }

            //claim pro
            function mwp_wpvivid_refresh_mainwp_status(selector)
            {
                var pObj = jQuery(selector + '.queue:first');
                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.install-login-status' );
                var StatusText = parent.find( '.mwp-wpvivid-status' );
                var current_status = StatusText.html();
                statusEl.html( '' );
                statusEl.html( '<i class="notched circle loading icon"></i>' );
                StatusText.html('Checking account status');
                var ajax_data = {
                    'action': 'mwp_wpvivid_refresh_mainwp_status'
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success') {
                            mwp_wpvivid_get_mainwp_status = true;
                            mwp_wpvivid_plugin_login_start_next(selector);
                        }
                        else{
                            statusEl.html('<i class="red times icon" title="' + jsonarray.error + '"></i>');
                            StatusText.html(current_status);
                        }
                    }
                    catch(err) {
                        statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        StatusText.html(current_status);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('prepare install plugin', textStatus, errorThrown);
                    statusEl.html('<i class="red times icon" title="' + error_message + '"></i>');
                    StatusText.html(current_status);
                });
            }

            function mwp_wpvivid_plugin_login_start_next(selector)
            {
                while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (mwp_wpvivid_login_bulkCurrentThreads < mwp_wpvivid_login_bulkMaxThreads)) {
                    objProcess.removeClass('queue');
                    var type = 'install';
                    mwp_wpvivid_check_repair_pro(objProcess, selector, type);
                }
            }

            function mwp_wpvivid_check_repair_pro(pObj, selector, type)
            {
                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.install-login-status' );
                var StatusText = parent.find( '.mwp-wpvivid-status' );
                var current_status = StatusText.html();
                var site_id = parent.attr( 'website-id' );
                mwp_wpvivid_login_bulkCurrentThreads++;
                statusEl.html( '' );
                statusEl.html( '<i class="notched circle loading icon"></i>' );
                StatusText.html('Checking WPvivid Free installation');
                var ajax_data = {
                    'action': 'mwp_wpvivid_check_repair_pro',
                    'site_id': site_id
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success' || jsonarray.result) {
                            mwp_wpvivid_check_free(pObj, selector, type);
                            return;
                        }
                        else if(jsonarray.result === 'failed'){
                            statusEl.html('<i class="red times icon" title="' + jsonarray.error + '"></i>');
                            StatusText.html(current_status);
                        }
                    }
                    catch(err) {
                        statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        StatusText.html(current_status);
                    }
                    mwp_wpvivid_loop_next_thread(selector, type);
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('prepare install plugin', textStatus, errorThrown);
                    statusEl.html('<i class="red times icon" title="' + error_message + '"></i>');
                    StatusText.html(current_status);
                    mwp_wpvivid_loop_next_thread(selector, type);
                });
            }

            //update pro
            function mwp_wpvivid_plugin_upgrade_start_next_ex(selector){
                while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (mwp_wpvivid_update_bulkCurrentThreads < mwp_wpvivid_update_bulkMaxThreads)) {
                    objProcess.removeClass('queue');
                    var type = 'update';
                    mwp_wpvivid_update_one_click(objProcess, selector, type);
                }
            }

            function mwp_wpvivid_update_one_click(pObj, selector, type){
                mwp_wpvivid_update_bulkCurrentThreads++;
                mwp_wpvivid_check_free(pObj, selector, type);
            }

            function mwp_wpvivid_check_free(pObj, selector, type)
            {
                var json = {};
                json['plugins_list'] = Array();
                jQuery('#mwp_wpvivid_install_content_selector').find('input:checkbox[option=wpvivid_install_plugins]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        json['plugins_list'].push(jQuery(this).val());
                    }
                });

                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.install-login-status' );
                var StatusText = parent.find( '.mwp-wpvivid-status' );
                var current_status = StatusText.html();
                var site_id = parent.attr( 'website-id' );
                statusEl.html( '' );
                statusEl.html( '<i class="notched circle loading icon"></i>' );
                StatusText.html('Checking WPvivid Free installation');
                var ajax_data = {
                    'action': 'mwp_wpvivid_check_free_plugin_status',
                    'site_id': site_id,
                    'plugins': json['plugins_list'],
                    'type': type
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success') {
                            mwp_wpvivid_check_pro(pObj, selector, type);
                            return;
                        }
                        else if(jsonarray.result === 'failed'){
                            statusEl.html('<i class="red times icon" title="' + jsonarray.error + '"></i>');
                            StatusText.html(current_status);
                        }
                    }
                    catch(err) {
                        statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        StatusText.html(current_status);
                    }
                    mwp_wpvivid_loop_next_thread(selector, type);
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('prepare install plugin', textStatus, errorThrown);
                    statusEl.html('<i class="red times icon" title="' + error_message + '"></i>');
                    StatusText.html(current_status);
                    mwp_wpvivid_loop_next_thread(selector, type);
                });
            }

            function mwp_wpvivid_check_pro(pObj, selector, type)
            {
                var json = {};
                json['plugins_list'] = Array();
                jQuery('#mwp_wpvivid_install_content_selector').find('input:checkbox[option=wpvivid_install_plugins]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        json['plugins_list'].push(jQuery(this).val());
                    }
                });

                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.install-login-status' );
                var StatusText = parent.find( '.mwp-wpvivid-status' );
                var current_status = StatusText.html();
                var site_id = parent.attr( 'website-id' );
                statusEl.html( '' );
                statusEl.html( '<i class="notched circle loading icon"></i>' );
                StatusText.html('Checking WPvivid Pro installation');
                var ajax_data = {
                    'action': 'mwp_wpvivid_check_pro_plugin_status',
                    'site_id': site_id,
                    'plugins': json['plugins_list'],
                    'type': type
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success') {
                            mwp_wpvivid_check_staging_pro(pObj, selector, type);
                            return;
                        }
                        else{
                            statusEl.html('<i class="red times icon" title="' + jsonarray.error + '"></i>');
                            StatusText.html(current_status);
                        }
                    }
                    catch(err) {
                        statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        StatusText.html(current_status);
                    }
                    mwp_wpvivid_loop_next_thread(selector, type);
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('prepare install plugin', textStatus, errorThrown);
                    statusEl.html('<i class="red times icon" title="' + error_message + '"></i>');
                    StatusText.html(current_status);
                    mwp_wpvivid_loop_next_thread(selector, type);
                });
            }

            function mwp_wpvivid_check_staging_pro(pObj, selector, type)
            {
                var is_install_staging='0';
                jQuery('#mwp_wpvivid_install_content_selector').find('input:checkbox[option=wpvivid_install_staging]').each(function()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        is_install_staging='1';
                    }
                });

                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.install-login-status' );
                var StatusText = parent.find( '.mwp-wpvivid-status' );
                var current_status = StatusText.html();
                var site_id = parent.attr( 'website-id' );
                statusEl.html( '' );
                statusEl.html( '<i class="notched circle loading icon"></i>' );
                StatusText.html('Checking WPvivid Pro installation');
                var ajax_data = {
                    'action': 'mwp_wpvivid_check_staging_plugin_status',
                    'site_id': site_id,
                    'is_install_staging': is_install_staging,
                    'type': type
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success') {
                            mwp_wpvivid_check_login(pObj, selector, type);
                            return;
                        }
                        else{
                            statusEl.html('<i class="red times icon" title="' + jsonarray.error + '"></i>');
                            StatusText.html(current_status);
                        }
                    }
                    catch(err) {
                        statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        StatusText.html(current_status);
                    }
                    mwp_wpvivid_loop_next_thread(selector, type);
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('prepare install plugin', textStatus, errorThrown);
                    statusEl.html('<i class="red times icon" title="' + error_message + '"></i>');
                    StatusText.html(current_status);
                    mwp_wpvivid_loop_next_thread(selector, type);
                });
            }

            function mwp_wpvivid_check_login(pObj, selector, type)
            {
                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.install-login-status' );
                var StatusText = parent.find( '.mwp-wpvivid-status' );
                var current_status = StatusText.html();
                var site_id = parent.attr( 'website-id' );
                var slug = 'wpvivid-backup-pro';
                statusEl.html( '<i class="notched circle loading icon"></i>' );
                StatusText.html('Checking WPvivid Pro claim');
                var ajax_data = {
                    'action': 'mwp_wpvivid_check_login_status',
                    'site_id': site_id,
                    'slug': slug
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray.result === 'success') {
                            statusEl.html( '<i class="green checkmark icon" title="test sf"></i>' );
                            parent.removeClass('need-claim');
                            parent.removeClass('negative');
                            StatusText.html('Latest version');
                            mwp_wpvivid_sync_child_site(site_id);
                        }
                        else if(jsonarray.result === 'failed'){
                            statusEl.html('<i class="red times icon" title="' + jsonarray.error + '"></i>');
                            StatusText.html(current_status);
                        }
                    }
                    catch(err) {
                        statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        StatusText.html(current_status);
                    }
                    mwp_wpvivid_loop_next_thread(selector, type);
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('prepare install plugin', textStatus, errorThrown);
                    statusEl.html('<i class="red times icon" title="' + error_message + '"></i>');
                    StatusText.html(current_status);
                    mwp_wpvivid_loop_next_thread(selector, type);
                });
            }

            //update free
            function mwp_wpvivid_loop_free_update_thread(selector){
                mwp_wpvivid_update_bulkCurrentThreads--;
                mwp_wpvivid_plugin_upgrade_start_next( selector );
            }

            function mwp_wpvivid_plugin_upgrade_start_next(selector) {
                while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (mwp_wpvivid_update_bulkCurrentThreads < mwp_wpvivid_update_bulkMaxThreads)) {
                    objProcess.removeClass('queue');
                    if (objProcess.closest('tr').find('.check-column input[type="checkbox"]:checked').length === 0) {
                        continue;
                    }
                    mwp_wpvivid_has_select_update = true;
                    if(objProcess.hasClass('need-update')) {
                        mwp_wpvivid_has_update = true;
                        mwp_wpvivid_plugin_upgrade_start_specific(objProcess, true, selector);
                    }
                }
                if(!mwp_wpvivid_has_select_update){
                    alert('Please select at least one item.');
                }
                else if(!mwp_wpvivid_has_update){
                    alert('There is no item need update.');
                }
            }

            function mwp_wpvivid_plugin_upgrade_start_specific(pObj, bulk, selector) {
                var parent = pObj.closest( 'tr' );
                var statusEl = parent.find( '.updating' );
                var slug = parent.attr( 'plugin-slug' );
                var latest_version = parent.attr( 'latest-version' );
                var textVersion = parent.find( '.mwp-wpvivid-current-version' );
                var textStatus = parent.find( 'mwp-wpvivid-status' );

                statusEl.html( '' );
                if ( bulk ) {
                    mwp_wpvivid_update_bulkCurrentThreads++;
                }
                statusEl.html( '<i class="notched circle loading icon"></i>' );

                if(slug === 'wpvivid-backuprestore/wpvivid-backuprestore.php'){
                    var ajax_data = {
                        'action': 'mwp_wpvivid_upgrade_plugin',
                        'site_id': parent.attr( 'website-id' ),
                        'type': 'plugin',
                        'slugs[]': [slug]
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data){
                        statusEl.html( '' );
                        pObj.removeClass( 'queue' );
                        try {
                            if(data && data['upgrades'][slug]){
                                statusEl.html( '<i class="green checkmark icon"></i>' );
                                parent.removeClass('need-update');
                                parent.removeClass('warning');
                                textVersion.html(latest_version + ' (WPvivid Backup)');
                                textStatus.html('Latest version');
                            }
                            else{
                                statusEl.html('<i class="red times icon"></i>');
                            }
                        }
                        catch(err) {
                            statusEl.html( '<i class="red times icon" title="'+err+'"></i>' );
                        }
                        if ( bulk ) {
                            mwp_wpvivid_loop_free_update_thread(selector);
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('upgrading plugin', textStatus, errorThrown);
                        statusEl.html( '<i class="red times icon" title="'+error_message+'"></i>' );
                        if ( bulk ) {
                            mwp_wpvivid_loop_free_update_thread(selector);
                        }
                    }, 0);
                }
                else{
                    mwp_wpvivid_loop_free_update_thread(selector);
                }
            }
        </script>
        <?php
    }

    public function render_pro()
    {
        if($this->check_product())
        {
            $this->gen_select_sites();
            ?>
            <div class="mwp-wpvivid-block-bottom-space"></div>
            <?php
            $this->get_dashboard_tab();
        }
    }

    public function render_free()
    {
        $this->gen_select_sites();
        ?>
        <div class="mwp-wpvivid-block-bottom-space"></div>
        <?php
        $this->get_dashboard_tab();
    }

    public function gen_select_sites()
    {
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();

        ?>
        <div class="mainwp-actions-bar" style="border: 1px solid #dadada;">
            <div class="ui grid">
                <div class="ui two column row">
                    <div style="padding-left: 0;">
                        <div style="float: left;margin-left: 10px">
                            <select class="ui dropdown" id="mwp_wpvivid_plugin_action" onchange="mwp_wpvivid_action_selector();">
                                <?php
                                if($this->select_pro)
                                {
                                    ?>
                                    <option value="default"><?php esc_html_e( 'All sites' ); ?></option>
                                    <option value="update-selected-ex"><?php esc_html_e( 'Update WPvivid plugins' ); ?></option>
                                    <option value="login-selected"><?php esc_html_e( 'Install & Claim WPvivid plugins' ); ?></option>
                                    <?php
                                }
                                else{
                                    ?>
                                    <option value="update-selected"><?php esc_html_e( 'Update the selected plugins' ); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <input type="button" value="<?php esc_html_e( 'Apply' ); ?>" class="ui basic button action" id="mwp_wpvivid_plugin_doaction_btn">
                        </div>
                        <?php
                        if($this->select_pro)
                        {
                            ?>
                            <div style="margin: 12px 0 0 10px; float: left;">
                                <a onclick="mwp_wpvivid_explanation_action();" style="cursor: pointer;">What are these options?</a>
                            </div>
                            <?php
                        }
                        ?>
                        <div style="clear: both;"></div>
                    </div>
                </div>
                <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_install_content_selector" style="display: none;">
                    <?php
                    if(isset($login_options['wpvivid_pro_login_cache']['plugins']) && !empty($login_options['wpvivid_pro_login_cache']['plugins']))
                    {
                        foreach ($login_options['wpvivid_pro_login_cache']['plugins'] as $slug=>$item)
                        {
                            $active=false;
                            $is_staging=false;

                            if(isset($item['install']['data']['addons']))
                            {
                                foreach ($item['install']['data']['addons'] as $addon)
                                {
                                    if($addon['active']===1)
                                    {
                                        $active=true;
                                        $is_staging=false;
                                        break;
                                    }
                                }
                            }
                            else
                            {
                                if(isset($item['active']) && $item['active']===1 && $slug==='staging_pro')
                                {
                                    $active=true;
                                    $is_staging=true;
                                }
                            }

                            if($active)
                            {
                                if($slug === 'backup_pro'){
                                    $disable = 'disabled';
                                    $checked = 'checked';
                                }
                                else{
                                    $disable = '';
                                    $checked = '';
                                }

                                if($is_staging)
                                {
                                    $option='wpvivid_install_staging';
                                }
                                else
                                {
                                    $option='wpvivid_install_plugins';
                                }
                                ?>
                                <div>
                                    <label>
                                        <input type="checkbox" option="<?php echo esc_attr($option); ?>" value="<?php echo esc_attr($slug); ?>" <?php echo esc_attr($checked.' '.$disable); ?> />
                                        <span>Install <?php echo esc_html($item['name']); ?></span>
                                    </label>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
                <div id="mwp_wpvivid_explanation_action" style="display: none; margin-bottom: 10px; padding: 0 0 0 15px;">
                    <ul style="margin: 0;">
                        <li>Update WPvivid plugins : This option allows you to update WPvivid plugins to the latest versions on the selected child sites.</li>
                        <li>Install and Claim WPvivid plugins : This option allows you to install and claim WPvivid plugins on the selected child sites.</li>
                    </ul>
                </div>
                <script>
                    function mwp_wpvivid_explanation_action() {
                        if(jQuery('#mwp_wpvivid_explanation_action').is(":hidden")) {
                            jQuery('#mwp_wpvivid_explanation_action').show();
                        }
                        else{
                            jQuery('#mwp_wpvivid_explanation_action').hide();
                        }
                    }
                    function mwp_wpvivid_action_selector(){
                        var bulk_act = jQuery( '#mwp_wpvivid_plugin_action' ).val();
                        if(bulk_act === 'update-selected-ex'){
                            jQuery('#mwp_wpvivid_install_content_selector').hide();
                        }
                        else if(bulk_act === 'login-selected'){
                            jQuery('#mwp_wpvivid_install_content_selector').show();
                        }
                        else if(bulk_act === 'default'){
                            jQuery('#mwp_wpvivid_install_content_selector').hide();
                        }
                    }

                    jQuery('input:checkbox[option=wpvivid_install_plugins]').on('click', function()
                    {
                        var value = jQuery(this).val();
                        if(value === 'white_label' || value === 'role_cap')
                        {
                            if(jQuery(this).prop('checked'))
                            {
                                jQuery('input:checkbox[option=wpvivid_install_plugins][value=white_label]').prop('checked', true);
                                jQuery('input:checkbox[option=wpvivid_install_plugins][value=role_cap]').prop('checked', true);
                            }
                            else{
                                jQuery('input:checkbox[option=wpvivid_install_plugins][value=white_label]').prop('checked', false);
                                jQuery('input:checkbox[option=wpvivid_install_plugins][value=role_cap]').prop('checked', false);
                            }
                        }
                    });
                </script>
            </div>
        </div>
        <?php
    }

    public function get_dashboard_tab()
    {
        global $mainwp_wpvivid_extension_activator;
        $selected_group=0;
        if ( isset( $_POST['mwp_wpvivid_plugin_groups_select'] ) ) {
            $selected_group = intval(sanitize_text_field($_POST['mwp_wpvivid_plugin_groups_select']));
        }

        $select_pro=$mainwp_wpvivid_extension_activator->get_global_select_pro();
        if($select_pro)
        {
            $websites_with_plugin=$mainwp_wpvivid_extension_activator->get_websites_ex();
            ?>
            <table class="ui single line selectable stackable table" id="mwp_wpvivid_sites_table" style="width: 100%; table-layout: fixed;">
                <thead>
                <tr>
                    <th id="cb" class="no-sort collapsing check-column"><div class="ui checkbox"><input id="cb-select-all-top" type="checkbox"></div></th>
                    <th><?php esc_html_e('Site'); ?></th>
                    <th class="no-sort collapsing"><i class="sign in icon"></i></th>
                    <th><?php esc_html_e('URL'); ?></th>
                    <th id="last_sync" class="manage-last_sync-column sorting_desc"><?php esc_html_e('Last Backup'); ?></th>
                    <th><?php esc_html_e('Report'); ?></th>
                    <th><?php esc_html_e('Current Version'); ?></th>
                    <th><?php esc_html_e('Status'); ?></th>
                    <th><?php esc_html_e('Schedule & Cloud Storage'); ?></th>
                    <th><?php esc_html_e('Settings'); ?></th>
                    <th><?php esc_html_e('Backup Now'); ?></th>
                </tr>
                </thead>
                <tbody id="the-mwp-wpvivid-list">
                    <?php self::get_websites_row_ex($websites_with_plugin,$selected_group); ?>
                </tbody>
                <tfoot>
                <tr>
                    <th id="cb" class="no-sort collapsing check-column"><div class="ui checkbox"><input id="cb-select-all-bottom" type="checkbox"></div></th>
                    <th><?php esc_html_e('Site'); ?></th>
                    <th class="no-sort collapsing"><i class="sign in icon"></i></th>
                    <th><?php esc_html_e('URL'); ?></th>
                    <th class="manage-last_sync-column sorting_desc"><?php esc_html_e('Last Backup'); ?></th>
                    <th><?php esc_html_e('Report'); ?></th>
                    <th><?php esc_html_e('Current Version'); ?></th>
                    <th><?php esc_html_e('Status'); ?></th>
                    <th><?php esc_html_e('Schedule & Cloud Storage'); ?></th>
                    <th><?php esc_html_e('Settings'); ?></th>
                    <th><?php esc_html_e('Backup Now'); ?></th>
                </tr>
                </tfoot>
            </table>
            <?php
        }
        else{
            $websites_with_plugin=$mainwp_wpvivid_extension_activator->get_websites();
            $has_update = false;
            foreach ( $websites_with_plugin as $website ) {
                $website_id = $website['id'];
                $class_active = (isset($website['active']) && !empty($website['active'])) ? '' : 'negative';
                if ($website['pro']) {
                    $need_update = $mainwp_wpvivid_extension_activator->get_need_update($website_id);
                    $class_update = $need_update == '1' ? 'warning' : '';
                } else {
                    $class_update = (isset($website['upgrade'])) ? 'warning' : '';
                }
                $class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;
                if($class_update === 'warning'){
                    $has_update = true;
                }
            }
            if($has_update){
                ?>
                <div class="notice notice-warning is-dismissible inline" style="margin: 0; padding-top: 10px; margin-bottom: 10px;"><p>There are plugins available to update. Select the checkboxes of websites in list and click on Apply button to start updating.</p>
                    <button type="button" class="notice-dismiss" onclick="mwp_click_dismiss_notice(this);">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
                <?php
            }
            ?>

            <table class="ui single line table" id="mwp_wpvivid_sites_table" style="width: 100%;">
                <thead>
                <tr>
                    <th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
                    <th><?php esc_html_e('Site'); ?></th>
                    <th class="no-sort collapsing"><i class="sign in icon"></i></th>
                    <th><?php esc_html_e('URL'); ?></th>
                    <th><?php esc_html_e('Report'); ?></th>
                    <th><?php esc_html_e('Current Version'); ?></th>
                    <th><?php esc_html_e('Status'); ?></th>
                    <th><?php esc_html_e('Settings'); ?></th>
                    <th><?php esc_html_e('Backup Now'); ?></th>
                </tr>
                </thead>
                <tbody id="the-mwp-wpvivid-list">
                <?php
                if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 )
                {
                    self::get_websites_row($websites_with_plugin,$selected_group);
                }
                ?>
                </tbody>
                <tfoot>
                <tr>
                    <th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
                    <th><?php esc_html_e('Site'); ?></th>
                    <th class="no-sort collapsing"><i class="sign in icon"></i></th>
                    <th><?php esc_html_e('URL'); ?></th>
                    <th><?php esc_html_e('Report'); ?></th>
                    <th><?php esc_html_e('Current Version'); ?></th>
                    <th><?php esc_html_e('Status'); ?></th>
                    <th><?php esc_html_e('Settings'); ?></th>
                    <th><?php esc_html_e('Backup Now'); ?></th>
                </tr>
                </tfoot>
            </table>
            <?php
        }
        ?>

        <script>
            jQuery( '#mwp_wpvivid_sites_table' ).DataTable( {
                //"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
                //"order": [ [ 1, "asc" ] ],
                //
                //"stateSave":  true,
                "stateDuration": 0, // forever
                //"scrollX": true,
                "pagingType": "full_numbers",
                "order": [],
                "columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
                //
                "pageLength": 50,
                "language": { "emptyTable": "No websites were found with the WPvivid Backup plugin installed." },
                "drawCallback": function( settings ) {
                    jQuery( '#mwp_wpvivid_sites_table .ui.dropdown').dropdown();
                    jQuery('#mwp_wpvivid_sites_table .ui.checkbox').checkbox();
                },
            } );
        </script>
        <?php
    }

    static public function get_websites_row($websites,$selected_group=0)
    {
        $plugin_name = 'WPvivid Backup';
        foreach ( $websites as $website )
        {
            $website_id = $website['id'];
            if($website['individual']) {
                $individual='Individual';
            }
            else {
                $individual='General';
            }
            $latest_version = (isset($website['upgrade']['new_version'])) ? $website['upgrade']['new_version'] : $website['version'];
            $plugin_slug = ( isset( $website['slug'] ) ) ? $website['slug'] : '';

            $class_install = '';
            $class_active = '';
            $class_update = '';
            if($website['class'] === 'need-install'){
                $class_install = 'negative need-install';
            }
            else if($website['class'] === 'need-active'){
                $class_active = 'negative need-active';
            }
            else if($website['class'] === 'need-update'){
                $class_update = 'warning need-update';
            }
            ?>
            <tr class="<?php echo esc_attr($class_install.' '.$class_active.' '.$class_update); ?>" website-id="<?php echo esc_attr($website_id); ?>" plugin-name="<?php echo esc_attr($plugin_name); ?>" plugin-slug="<?php echo esc_attr($plugin_slug); ?>" is-pro="<?php echo esc_attr($website['pro']); ?>" version="<?php echo esc_attr(isset($website['version']) ? $website['version'] : ''); ?>" latest-version="<?php echo esc_attr($latest_version); ?>">
                <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
                <td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo esc_html($website_id); ?>"><?php echo esc_html(stripslashes( $website['name'] )); ?></a></td>
                <td><a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($website_id); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>" target="_blank"><i class="sign in icon"></i></a></td>
                <td><a href="<?php echo esc_url($website['url']); ?>" target="_blank"><?php echo esc_html($website['url']); ?></a></td>
                <td><a onclick="mwp_wpvivid_check_report('<?php echo esc_js($website['id']); ?>', '<?php echo esc_js($website['pro']); ?>', '<?php echo esc_js($website['name']); ?>');" style="cursor: pointer;">Report</a></td>
                <td><span class="updating"></span><span class="mwp-wpvivid-current-version"><?php echo esc_html($website['version']); ?></span></td>
                <td><span class="install-login-status"></span><span class="mwp-wpvivid-status"><?php echo esc_html($website['status']); ?></span></td>
                <td><span><?php echo esc_html($individual); ?></span></td>
                <td><span><a href="admin.php?page=ManageSitesWPvivid&id=<?php echo esc_html($website_id); ?>"><i class="fa fa-hdd-o"></i> <?php esc_html_e( 'Backup Now', 'mainwp-wpvivid-extension' ); ?></a></span></td>
            </tr>
            <?php
        }
        ?>
        <script>
            function mwp_wpvivid_check_report(website_id, is_pro, website_name){
                window.location.href = window.location.href + "&check_report=1&website_id="+website_id+"&pro="+is_pro+"&website_name="+website_name;
            }
        </script>
        <?php
    }

    static public function get_websites_row_ex($websites,$selected_group=0)
    {
        global $mainwp_wpvivid_extension_activator;
        $plugin_name = 'WPvivid Backup Pro';
        foreach ( $websites as $website )
        {
            $website_id = $website['id'];

            $last_backup = 'Never';
            $report = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($website_id, 'report_addon', array());
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

                $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($website_id, 'time_zone', '');
                if(empty($time_zone)){
                    $time_zone = 0;
                }

                foreach ($report as $task_id => $report_option) {
                    if(isset($report_option['task_id']) && !empty($report_option['task_id']))
                    {
                        $last_backup = date("F d, Y H:i", $report_option['backup_time'] + $time_zone * 60 * 60);
                        $last_backup .= '<br>';
                        $last_backup .= sanitize_text_field($report_option['status']);
                        break;
                    }
                    /*if($report_option['status'] === 'Succeeded') {
                        $last_backup = date("F d, Y H:i", $report_option['backup_time']);
                        break;
                    }*/
                }
            }
            else{
                $last_backup = 'Never';
            }

            if($website['individual']) {
                $individual='Individual';
            }
            else {
                $individual='General';
            }

            $schedule_addon = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($website_id, 'schedule_addon', array());
            if(isset($schedule_addon) && !empty($schedule_addon)){
                $schedule_css = 'dashicons dashicons-calendar-alt mwp-wpvivid-dashicons-green';
            }
            else{
                $schedule_css = 'dashicons dashicons-calendar-alt mwp-wpvivid-dashicons-grey';
            }
            $remote = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($website_id, 'remote', array());
            if(isset($remote) && !empty($remote)){
                $remote_css = 'dashicons dashicons-admin-site-alt3 mwp-wpvivid-dashicons-grey';
                if(isset($remote['upload']) && !empty($remote['upload'])) {
                    foreach ($remote['upload'] as $key => $value) {
                        if ($key === 'remote_selected') {
                            continue;
                        } else {
                            $remote_css = 'dashicons dashicons-admin-site-alt3 mwp-wpvivid-dashicons-green';
                        }
                    }
                }
            }
            else{
                $remote_css = 'dashicons dashicons-admin-site-alt3 mwp-wpvivid-dashicons-grey';
            }

            $plugin_slug = ( isset( $website['slug'] ) ) ? $website['slug'] : '';
            $latest_version = $mainwp_wpvivid_extension_activator->get_latest_version($website_id);
            if($latest_version == ''){
                $latest_version = $mainwp_wpvivid_extension_activator->get_current_version($website_id);
            }
            $class_install = '';
            $class_login = '';
            $class_active = '';
            $class_update = '';
            $check_login_status = true;
            if($website['class'] === 'need-install-wpvivid'){
                $class_install = 'negative need-claim need-install-wpvivid';
                $check_login_status = false;
            }
            else if($website['class'] === 'need-active-wpvivid'){
                $class_active = 'negative need-claim need-active-wpvivid';
                $check_login_status = false;
            }
            else if($website['class'] === 'need-install-wpvivid-pro'){
                $class_install = 'negative need-claim need-install-wpvivid-pro';
                $check_login_status = false;
            }
            else if($website['class'] === 'need-active-wpvivid-pro'){
                $class_active = 'negative need-claim need-active-wpvivid-pro';
                $check_login_status = false;
            }
            else if($website['class'] === 'need-login'){
                $class_login = 'negative need-claim need-login';
                $check_login_status = false;
            }
            if($check_login_status) {
                if ($website['class-update'] === 'need-update-wpvivid') {
                    $class_update = 'warning need-update';
                } else if ($website['class-update'] === 'need-update-wpvivid-pro') {
                    $class_update = 'warning need-update';
                }
            }

            ?>
            <tr class="<?php echo esc_attr($class_install.' '.$class_login.' '.$class_active.' '.$class_update); ?>" website-id="<?php echo esc_attr($website_id); ?>" plugin-name="<?php echo esc_attr($plugin_name); ?>" plugin-slug="<?php echo esc_attr($plugin_slug); ?>" is-pro="<?php echo esc_attr($website['pro']); ?>" version="<?php echo esc_attr(isset($website['version']) ? $website['version'] : ''); ?>" latest-version="<?php echo esc_attr($latest_version); ?>">
                <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
                <td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo esc_html($website_id); ?>"><?php echo esc_html(stripslashes( $website['name'] )); ?></a></td>
                <td style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><a href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($website_id); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>" target="_blank"><i class="sign in icon"></i></a></td>
                <td style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><a href="<?php echo esc_url($website['url']); ?>" target="_blank"><?php echo esc_html($website['url']); ?></a></td>
                <td class="collapsing center aligned backup column-backup" style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><span><?php echo esc_html($last_backup); ?></span></td>
                <td><a onclick="mwp_wpvivid_check_report('<?php echo esc_js($website['id']); ?>', '<?php echo esc_js($website['pro']); ?>', '<?php echo esc_js($website['name']); ?>');" style="cursor: pointer;">Report</a></td>
                <td style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><span class="updating"></span><span class="mwp-wpvivid-current-version"><?php echo esc_html($website['version']); ?></span></td>
                <td style="overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"><span class="install-login-status"></span><span class="mwp-wpvivid-status"><?php echo esc_html($website['status']); ?></span></td>
                <td><span class="<?php echo esc_attr($schedule_css); ?>" style="margin-right: 10px;"></span><span class="<?php echo esc_attr($remote_css); ?>" style="margin-top: 2px;"></span></td>
                <td><span><?php echo esc_html($individual); ?></span></td>
                <td><span><a href="admin.php?page=ManageSitesWPvivid&id=<?php echo esc_html($website_id); ?>"><i class="fa fa-hdd-o"></i> <?php esc_html_e( 'Backup Now', 'mainwp-wpvivid-extension' ); ?></a></span></td>
            </tr>
            <?php
        }
        ?>
        <script>
            function mwp_wpvivid_check_report(website_id, is_pro, website_name){
                window.location.href = window.location.href + "&check_report=1&website_id="+website_id+"&pro="+is_pro+"&website_name="+website_name;
            }
        </script>
        <?php
    }
}