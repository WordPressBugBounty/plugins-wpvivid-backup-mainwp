<?php

class Mainwp_WPvivid_Extension_BackupPage
{
    private $setting;
    private $setting_addon;
    private $backup_custom_setting;
    private $site_id;

    public function __construct()
    {
        $this->load_backup_ajax();
        $this->load_backup_filter();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_backup_info($setting, $setting_addon=array(), $backup_custom_setting=array())
    {
        $this->setting=$setting;
        $this->setting_addon=$setting_addon;
        $this->backup_custom_setting=$backup_custom_setting;
    }

    public function load_backup_ajax()
    {
        //backup
        add_action('wp_ajax_mwp_wpvivid_get_status',array( $this,'get_status'));
        add_action('wp_ajax_mwp_wpvivid_get_backup_list',array($this,'get_backup_list'));
        add_action('wp_ajax_mwp_wpvivid_get_backup_schedule',array($this,'get_backup_schedule'));
        add_action('wp_ajax_mwp_wpvivid_get_default_remote',array($this,'get_default_remote'));
        add_action('wp_ajax_mwp_wpvivid_prepare_backup',array( $this,'prepare_backup'));
        add_action('wp_ajax_mwp_wpvivid_backup_now',array( $this,'backup_now'));
        add_action('wp_ajax_mwp_wpvivid_view_backup_task_log',array($this,'view_backup_task_log'));
        add_action('wp_ajax_mwp_wpvivid_backup_cancel',array($this, 'backup_cancel'));

        //schedule side bar
        add_action('wp_ajax_mwp_wpvivid_read_last_backup_log',array( $this,'read_last_backup_log'));

        //backup list
        add_action('wp_ajax_mwp_wpvivid_set_security_lock',array($this, 'set_security_lock'));
        add_action('wp_ajax_mwp_wpvivid_view_log',array( $this,'view_log'));
        add_action('wp_ajax_mwp_wpvivid_init_download_page',array($this, 'init_download_page'));
        add_action('wp_ajax_mwp_wpvivid_prepare_download_backup',array($this,'prepare_download_backup'));
        add_action('wp_ajax_mwp_wpvivid_get_download_task', array($this,'get_download_task'));
        add_action('wp_ajax_mwp_wpvivid_download_backup',array($this,'download_backup'));
        add_action('wp_ajax_mwp_wpvivid_delete_backup',array( $this,'delete_backup'));
        add_action('wp_ajax_mwp_wpvivid_delete_backup_array',array($this,'delete_backup_array'));

        //custom addon
        add_action('wp_ajax_mwp_wpvivid_get_database_tables', array($this, 'get_database_tables'));
        add_action('wp_ajax_mwp_wpvivid_get_themes_plugins', array($this, 'get_themes_plugins'));
        add_action('wp_ajax_mwp_wpvivid_get_uploads_tree_data', array($this, 'get_uploads_tree_data'));
        add_action('wp_ajax_mwp_wpvivid_get_content_tree_data', array($this, 'get_content_tree_data'));
        add_action('wp_ajax_mwp_wpvivid_get_content_tree_data_ex', array($this, 'get_content_tree_data_ex'));
        add_action('wp_ajax_mwp_wpvivid_get_custom_tree_data_ex', array($this, 'get_custom_tree_data_ex'));
        add_action('wp_ajax_mwp_wpvivid_get_additional_folder_tree_data', array($this, 'get_additional_folder_tree_data'));
        add_action('wp_ajax_mwp_wpvivid_connect_additional_database_addon', array($this, 'connect_additional_database_addon'));
        add_action('wp_ajax_mwp_wpvivid_add_additional_database_addon', array($this, 'add_additional_database_addon'));
        add_action('wp_ajax_mwp_wpvivid_remove_additional_database_addon', array($this, 'remove_additional_database_addon'));
        add_action('wp_ajax_mwp_wpvivid_get_database_by_filter', array($this, 'get_database_by_filter'));
        add_action('wp_ajax_mwp_wpvivid_update_backup_exclude_extension_addon', array($this, 'update_backup_exclude_extension_addon'));

        //backup addon
        add_action('wp_ajax_mwp_wpvivid_get_default_remote_addon',array($this,'get_default_remote_addon'));
        add_action('wp_ajax_mwp_wpvivid_get_remote_storage_addon', array($this, 'get_remote_storage_addon'));
        add_action('wp_ajax_mwp_wpvivid_prepare_backup_addon', array($this, 'prepare_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_backup_now_addon', array($this, 'backup_now_addon'));
        add_action('wp_ajax_mwp_wpvivid_list_task_addon', array($this, 'list_task_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_ready_task_addon', array($this, 'delete_ready_task_addon'));
        add_action('wp_ajax_mwp_wpvivid_backup_cancel_addon', array($this, 'backup_cancel_addon'));
    }

    public function load_backup_filter()
    {
        add_filter('mwp_wpvivid_get_backup_prefix', array($this, 'wpvivid_get_backup_prefix'));
    }

    public function wpvivid_get_backup_prefix()
    {
        global $mainwp_wpvivid_extension_activator;
        $websites=$mainwp_wpvivid_extension_activator->get_websites_ex();
        $prefix = '';
        $url = '';
        foreach ( $websites as $website )
        {
            if($this->site_id ===  $website['id'])
            {
                $url = $website['url'];
            }
        }
        if($url !== '')
        {
            $url = untrailingslashit($url);
            $parse = wp_parse_url($url);
            $path = '';
            if(isset($parse['path'])) {
                $parse['path'] = str_replace('/', '_', $parse['path']);
                $parse['path'] = str_replace('.', '_', $parse['path']);
                $path = $parse['path'];
            }
            $parse['host'] = str_replace('/', '_', $parse['host']);
            $prefix = $parse['host'].$path;
        }
        return $prefix;
    }

    public function get_status()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_status_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['data'] = $information['wpvivid']['task'];
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_backup_status($site_id, $information['wpvivid']['task'], $information['wpvivid']['backup_list'], $information['wpvivid']['schedule']);
                    $ret['schedule_html'] = Mainwp_WPvivid_Extension_Subpage::output_schedule_backup($information['wpvivid']['schedule']);
                    $ret['backup_list'] = Mainwp_WPvivid_Extension_Subpage::output_backup_list($information['wpvivid']['backup_list']);
                }
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

    public function get_backup_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_backup_list_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['backup_list'] = Mainwp_WPvivid_Extension_Subpage::output_backup_list($information['wpvivid']['backup_list']);
                }
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

    public function get_backup_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_backup_schedule_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['schedule_html'] = Mainwp_WPvivid_Extension_Subpage::output_schedule_backup($information['wpvivid']['schedule']);
                }
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

    public function get_default_remote()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_default_remote_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['default_remote_storage'] = $information['remote_storage_type'];
                    $ret['default_remote_pic'] = Mainwp_WPvivid_Extension_Subpage::output_default_remote($information['remote_storage_type']);
                }
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

    public function prepare_backup()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup']) && !empty($_POST['backup']) && is_array($_POST['backup'])){
                $site_id = sanitize_key($_POST['site_id']);
                if(isset($_POST['backup']['mwp_backup_files']) && isset($_POST['backup']['mwp_local']) &&
                    isset($_POST['backup']['mwp_remote']) && isset($_POST['backup']['mwp_ismerge']) && isset($_POST['backup']['mwp_lock'])) {
                    $post_data['backup']['backup_files'] = sanitize_text_field($_POST['backup']['mwp_backup_files']);
                    $post_data['backup']['local'] = sanitize_text_field($_POST['backup']['mwp_local']);
                    $post_data['backup']['remote'] = sanitize_text_field($_POST['backup']['mwp_remote']);
                    $post_data['backup']['ismerge'] = sanitize_text_field($_POST['backup']['mwp_ismerge']);
                    $post_data['backup']['lock'] = sanitize_text_field($_POST['backup']['mwp_lock']);
                    $post_data['mwp_action'] = 'wpvivid_prepare_backup_mainwp';
                    $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                    if (isset($information['error'])) {
                        $ret['result'] = 'failed';
                        $ret['error'] = $information['error'];
                    } else {
                        $ret['result'] = 'success';
                        $ret['data'] = $information['task_id'];
                    }
                    echo wp_json_encode($ret);
                }
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

    public function backup_now()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['task_id']) && !empty($_POST['task_id']) && is_string($_POST['task_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['task_id'] = sanitize_key($_POST['task_id']);
                $post_data['mwp_action'] = 'wpvivid_backup_now_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['data'] = $information;
                }
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

    public function view_backup_task_log()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['id'] = sanitize_key($_POST['id']);
                $post_data['mwp_action'] = 'wpvivid_view_backup_task_log_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = $information;
                }
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

    public function backup_cancel()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_backup_cancel_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = $information;
                }
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

    public function read_last_backup_log()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['log_file_name']) && !empty($_POST['log_file_name']) && is_string($_POST['log_file_name'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['log_file_name'] = sanitize_text_field($_POST['log_file_name']);
                $post_data['mwp_action'] = 'wpvivid_read_last_backup_log_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = $information;
                }
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

    public function set_security_lock()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['lock']) && is_string($_POST['lock'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['lock'] = sanitize_text_field($_POST['lock']);
                $post_data['mwp_action'] = 'wpvivid_set_security_lock_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['backup_list'] = Mainwp_WPvivid_Extension_Subpage::output_backup_list($information['wpvivid']['backup_list']);
                }

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

    public function view_log()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['id'] = sanitize_key($_POST['id']);
                $post_data['mwp_action'] = 'wpvivid_view_log_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = $information;
                }
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

    public function init_download_page()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['mwp_action'] = 'wpvivid_init_download_page_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = Mainwp_WPvivid_Extension_Subpage::output_init_download_page($post_data['backup_id'], $information);
                    $ret['result'] = 'success';
                }
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

    public function prepare_download_backup()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['file_name']) && !empty($_POST['file_name']) && is_string($_POST['file_name'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['file_name'] = sanitize_text_field($_POST['file_name']);
                $post_data['mwp_action'] = 'wpvivid_prepare_download_backup_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                }
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

    public function get_download_task()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['mwp_action'] = 'wpvivid_get_download_task_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = Mainwp_WPvivid_Extension_Subpage::output_init_download_page($post_data['backup_id'], $information);
                    $ret['result'] = 'success';
                }
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

    public function download_backup()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_REQUEST['site_id']) && !empty($_REQUEST['site_id']) && is_string($_REQUEST['site_id']) &&
                isset($_REQUEST['backup_id']) && !empty($_REQUEST['backup_id']) && is_string($_REQUEST['backup_id']) &&
                isset($_REQUEST['file_name']) && !empty($_REQUEST['file_name']) && is_string($_REQUEST['file_name'])){
                $site_id = sanitize_text_field($_REQUEST['site_id']);
                $post_data['backup_id'] = sanitize_key($_REQUEST['backup_id']);
                $post_data['file_name'] = sanitize_text_field($_REQUEST['file_name']);
                $post_data['mwp_action'] = 'wpvivid_download_backup_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['download_url'] = $information['download_url'];
                }
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

    public function delete_backup()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['force']) && is_string($_POST['force'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['force'] = sanitize_text_field($_POST['force']);
                $post_data['mwp_action'] = 'wpvivid_delete_backup_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['backup_list'] = Mainwp_WPvivid_Extension_Subpage::output_backup_list($information['wpvivid']['backup_list']);
                }
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

    public function delete_backup_array()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_array($_POST['backup_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $backup_ids = array_map( 'sanitize_key', $_POST['backup_id'] );
                foreach ($backup_ids as $backup_id){
                    $post_data['backup_id'][] = sanitize_key($backup_id);
                }
                $post_data['mwp_action'] = 'wpvivid_delete_backup_array_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['backup_list'] = Mainwp_WPvivid_Extension_Subpage::output_backup_list($information['wpvivid']['backup_list']);
                }
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

    public function get_database_tables()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_database_tables_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['database_tables'] = Mainwp_WPvivid_Extension_Subpage::output_database_table($information['base_tables'], $information['other_tables']);
                }
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

    public function get_themes_plugins()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_themes_plugins_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['themes_plugins_table'] = Mainwp_WPvivid_Extension_Subpage::output_themes_plugins_table($information['themes'], $information['plugins']);
                }
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

    public function get_uploads_tree_data()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['tree_node']) && !empty($_POST['tree_node']) && is_string($_POST['tree_node'])){
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['tree_node']));
                $post_data['tree_node'] = json_decode($json, true);
                $post_data['mwp_action'] = 'wpvivid_get_uploads_tree_data_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['uploads_tree_data'] = $information['nodes'];
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

    public function get_content_tree_data()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['tree_node']) && !empty($_POST['tree_node']) && is_string($_POST['tree_node'])){
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['tree_node']));
                $post_data['tree_node'] = json_decode($json, true);
                $post_data['mwp_action'] = 'wpvivid_get_content_tree_data_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['content_tree_data'] = $information['nodes'];
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

    public function get_content_tree_data_ex()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['tree_node']) && !empty($_POST['tree_node']) && is_string($_POST['tree_node'])){
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['tree_node']));
                $post_data['tree_node'] = json_decode($json, true);
                $post_data['mwp_action'] = 'wpvivid_get_content_tree_data_ex_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['content_tree_data'] = $information['nodes'];
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

    public function get_custom_tree_data_ex()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['tree_node']) && !empty($_POST['tree_node']) && is_string($_POST['tree_node'])){
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['tree_node']));
                $post_data['tree_node'] = json_decode($json, true);
                $post_data['mwp_action'] = 'wpvivid_get_custom_tree_data_ex_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['content_tree_data'] = $information['nodes'];
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

    public function get_additional_folder_tree_data()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['tree_node']) && !empty($_POST['tree_node']) && is_string($_POST['tree_node'])){
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['tree_node']));
                $post_data['tree_node'] = json_decode($json, true);
                $post_data['mwp_action'] = 'wpvivid_get_additional_folder_tree_data_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['additional_folder_tree_data'] = $information['nodes'];
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

    public function connect_additional_database_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])){
                $site_id = sanitize_key($_POST['site_id']);
                $data = sanitize_text_field($_POST['database_info']);
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $post_data['mwp_action'] = 'wpvivid_connect_additional_database_addon_mainwp';
                $post_data['db_user'] = sanitize_text_field($json['db_user']);
                $post_data['db_pass'] = sanitize_text_field($json['db_pass']);
                $post_data['db_host'] = sanitize_text_field($json['db_host']);
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_table($information['database_array']);
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

    public function add_additional_database_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])){
                $site_id = sanitize_key($_POST['site_id']);
                $data = sanitize_text_field($_POST['database_info']);
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $post_data['mwp_action'] = 'wpvivid_add_additional_database_addon_mainwp';
                $post_data['db_user'] = $json['db_user'];
                $post_data['db_pass'] = $json['db_pass'];
                $post_data['db_host'] = $json['db_host'];
                $post_data['additional_database_list'] = $json['additional_database_list'];
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_list($information['data']);
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

    public function remove_additional_database_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['database_name']) && !empty($_POST['database_name']) && is_string($_POST['database_name'])){
                $site_id = sanitize_key($_POST['site_id']);
                $database_name = sanitize_text_field($_POST['database_name']);
                $post_data['mwp_action'] = 'wpvivid_remove_additional_database_addon_mainwp';
                $post_data['database_name'] = $database_name;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_list($information['data']);
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

    public function get_database_by_filter()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['table_type'])&&isset($_POST['filter_text'])&& isset($_POST['option_type']))
            {
                $site_id     = sanitize_key($_POST['site_id']);
                $table_type  = sanitize_text_field($_POST['table_type']);
                $filter_text = sanitize_text_field($_POST['filter_text']);
                $option_type = sanitize_text_field($_POST['option_type']);
                $post_data['mwp_action'] = 'wpvivid_get_database_by_filter_mainwp';
                $post_data['table_type'] = $table_type;
                $post_data['filter_text'] = $filter_text;
                $post_data['option_type'] = $option_type;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['database_html'] = Mainwp_WPvivid_Extension_Subpage::output_filter_database_table($table_type, $information['database_tables']);
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

    public function update_backup_exclude_extension_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']) &&
                isset($_POST['exclude_content']) && !empty($_POST['exclude_content']) && is_string($_POST['exclude_content'])){
                $site_id = sanitize_key($_POST['site_id']);
                $type = sanitize_text_field($_POST['type']);
                $exclude_content = sanitize_text_field($_POST['exclude_content']);
                $mainwp_wpvivid_extension_activator->mwp_wpvivid_update_backup_exclude_extension_rule($site_id, $type, $exclude_content);
                $post_data['mwp_action'] = 'wpvivid_update_backup_exclude_extension_addon_mainwp';
                $post_data['type'] = $type;
                $post_data['exclude_content'] = $exclude_content;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
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

    public function get_default_remote_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_default_remote_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['default_remote_storage'] = $information['remote_storage_type'];
                    $ret['default_remote_pic'] = Mainwp_WPvivid_Extension_Subpage::output_default_remote($information['remote_storage_type']);
                }
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

    public function get_remote_storage_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_remote_storage_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['remoteslist'] = $information['remoteslist'];
                    $ret['has_remote'] = $information['has_remote'];
                    //Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'remotelist', $ret['remoteslist']);
                }
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

    public function prepare_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup']) && !empty($_POST['backup']) && is_string($_POST['backup'])){
                $site_id = sanitize_key($_POST['site_id']);
                $data = sanitize_text_field($_POST['backup']);
                $data = stripslashes($data);
                $json = json_decode($data, true);

                if(isset($json['mwp_lock'])){
                    $post_data['backup']['lock'] = $json['mwp_lock'];
                }
                if(isset($json['mwp_backup_to'])){
                    $post_data['backup']['backup_to'] = $json['mwp_backup_to'];
                }
                if(isset($json['mwp_backup_files'])){
                    $post_data['backup']['backup_files'] = $json['mwp_backup_files'];
                }
                if(isset($json['backup_prefix'])){
                    $post_data['backup']['backup_prefix'] = $json['backup_prefix'];
                }
                if(isset($json['custom_dirs'])){
                    $mainwp_wpvivid_extension_activator->mwp_wpvivid_update_backup_custom_setting($site_id, $json['custom_dirs']);
                    if(isset($json['custom_dirs']['uploads_list']) && !empty($json['custom_dirs']['uploads_list'])){
                        foreach ($json['custom_dirs']['uploads_list'] as $key => $value){
                            if($value['type'] === 'mwp-wpvivid-custom-li-folder-icon'){
                                $value['type'] = 'wpvivid-custom-li-folder-icon';
                            }
                            else if($value['type'] === 'mwp-wpvivid-custom-li-file-icon'){
                                $value['type'] = 'wpvivid-custom-li-file-icon';
                            }
                            $json['custom_dirs']['uploads_list'][$key] = $value;
                        }
                    }
                    if(isset($json['custom_dirs']['content_list']) && !empty($json['custom_dirs']['content_list'])){
                        foreach ($json['custom_dirs']['content_list'] as $key => $value){
                            if($value['type'] === 'mwp-wpvivid-custom-li-folder-icon'){
                                $value['type'] = 'wpvivid-custom-li-folder-icon';
                            }
                            else if($value['type'] === 'mwp-wpvivid-custom-li-file-icon'){
                                $value['type'] = 'wpvivid-custom-li-file-icon';
                            }
                            $json['custom_dirs']['content_list'][$key] = $value;
                        }
                    }
                    if(isset($json['custom_dirs']['other_list']) && !empty($json['custom_dirs']['other_list'])){
                        foreach ($json['custom_dirs']['other_list'] as $key => $value){
                            if($value['type'] === 'mwp-wpvivid-custom-li-folder-icon'){
                                $value['type'] = 'wpvivid-custom-li-folder-icon';
                            }
                            else if($value['type'] === 'mwp-wpvivid-custom-li-file-icon'){
                                $value['type'] = 'wpvivid-custom-li-file-icon';
                            }
                            $json['custom_dirs']['other_list'][$key] = $value;
                        }
                    }
                    $post_data['backup']['custom_dirs'] = $json['custom_dirs'];
                }

                $post_data['backup']['exclude_files'] = $json['exclude_files'];
                $post_data['backup']['exclude_file_type'] = $json['exclude_file_type'];
                if(isset($json['remote_id_select'])){
                    $post_data['backup']['remote_id_select'] = $json['remote_id_select'];
                }
                $child_site_pro_version = $mainwp_wpvivid_extension_activator->get_current_version($site_id);
                if(version_compare($child_site_pro_version,'2.2.22','>='))
                {
                    $post_data['mwp_action']='wpvivid_prepare_backup_addon_mainwp_ex';
                }
                else
                {
                    $post_data['mwp_action']='wpvivid_prepare_backup_addon_mainwp';
                }
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['data'] = $information['task_id'];
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

    public function backup_now_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['task_id']) && !empty($_POST['task_id']) && is_string($_POST['task_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['task_id'] = sanitize_key($_POST['task_id']);
                $child_site_pro_version = $mainwp_wpvivid_extension_activator->get_current_version($site_id);
                if(version_compare($child_site_pro_version,'2.2.22','>='))
                {
                    $post_data['mwp_action'] = 'wpvivid_backup_now_addon_mainwp_ex';
                }
                else
                {
                    $post_data['mwp_action'] = 'wpvivid_backup_now_addon_mainwp';
                }
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['data'] = $information;
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

    public function list_task_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $child_site_pro_version = $mainwp_wpvivid_extension_activator->get_current_version($site_id);
                if(version_compare($child_site_pro_version,'2.2.22','>='))
                {
                    $post_data['mwp_action'] = 'wpvivid_list_tasks_addon_mainwp_ex';
                }
                else
                {
                    $post_data['mwp_action'] = 'wpvivid_list_tasks_addon_mainwp';
                }
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    if(version_compare($child_site_pro_version,'2.2.22','>='))
                    {
                        $ret = Mainwp_WPvivid_Extension_Subpage::output_backup_status_addon_ex($site_id, $information);
                    }
                    else
                    {
                        $ret = Mainwp_WPvivid_Extension_Subpage::output_backup_status_addon($site_id, $information);
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

    public function delete_ready_task_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_delete_ready_task_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
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

    public function backup_cancel_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_backup_cancel_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret = $information;
                }
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

    public function render($check_pro, $global=false)
    {
        ?>
        <div style="margin: 10px;">
        <?php
        if($check_pro){
            $this->mwp_wpvivid_backup_progress_addon();
            ?>
            <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_backup_notice"></div>
            <div class="postbox mwp-quickbackup-addon">
                <?php $this->mwp_wpvivid_welcome_bar_addon();?>
                <div class="wpvivid-canvas mwp-wpvivid-clear-float">
                    <!---  backup progress --->
                    <?php
                    $this->mwp_wpvivid_backup_content_selector_addon();
                    ?>
                </div>
            </div>
            <?php
            $this->mwp_wpvivid_backup_js_addon();
        }
        else{
            $this->mwp_wpvivid_backup_progress();
            $this->mwp_wpvivid_backup_manual();
            $this->mwp_wpvivid_backup_schedule();
            $this->mwp_wpvivid_backup_list();
            $this->mwp_wpvivid_backup_js();
        }
        ?>
        </div>
        <?php
    }

    function mwp_wpvivid_welcome_bar_addon()
    {
        if(isset($this->setting['wpvivid_local_setting']['path']) && !empty($this->setting['wpvivid_local_setting']['path'])){
            $local_path = $this->setting['wpvivid_local_setting']['path'];
        }
        else{
            $local_path = 'wpvividbackups';
        }
        ?>
        <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
            <div class="mwp-wpvivid-welcome-bar-left">
                <p><span class="dashicons dashicons-backup mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-blue"></span><span class="mwp-wpvivid-page-title">Back Up Manually</span></p>
                <p><span class="about-description">The page allows you to manually create a backup of the website for restoration or migration.</span></p>
            </div>
            <div class="mwp-wpvivid-welcome-bar-right"></div>
            <div class="mwp-wpvivid-nav-bar mwp-wpvivid-clear-float">
                <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                <span style="line-height: 20px;">Local Storage Directory:</span>
                <span>
                    <code>
                        <?php esc_html_e('http(s)://child-site/wp-content/'); ?><?php echo esc_html($local_path); ?>
                    </code>
                </span>
                <span><a href="#" onclick="mwp_switch_wpvivid_tab('setting');"><?php esc_html_e(' rename directory', 'wpvivid'); ?></a></span>
                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                    <div class="mwp-wpvivid-bottom">
                        <p>Click to change WPvivid Pro custom backup folder.</p>
                        <i></i> <!-- do not delete this line -->
                    </div>
                </span>
                <span><a href="#" onclick="mwp_switch_wpvivid_tab('backup_restore');"><?php esc_html_e(' or view backups list', 'wpvivid'); ?></a></span>
                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                    <div class="mwp-wpvivid-bottom">
                        <!-- The content you need -->
                        <p>Click to browse and manage all your backups.</p>
                        <i></i> <!-- do not delete this line -->
                    </div>
                </span>
            </div>
        </div>
        <?php
    }

    function mwp_wpvivid_backup_to_addon()
    {
        if(isset($this->setting['wpvivid_common_setting']['default_backup_local'])) {
            if ($this->setting['wpvivid_common_setting']['default_backup_local']) {
                $default_backup_local = 'checked';
                $default_backup_remote = '';
                $default_remote_seletor = 'display: none;';
            } else {
                $default_backup_local = '';
                $default_backup_remote = 'checked';
                $default_remote_seletor = '';
            }
        }
        else{
            $default_backup_local = 'checked';
            $default_backup_remote = '';
            $default_remote_seletor = 'display: none;';
        }
        ?>
        <div>
            <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
            <div style="padding-left:2em;">
                <label class="">
                    <input type="radio" option="mwp_backup" name="mwp_backup_to" value="local" <?php echo esc_attr($default_backup_local); ?> />Backup to localhost
                </label>
                <span style="padding:0 1em;"></span>

                <label class="">
                    <input type="radio" option="mwp_backup" name="mwp_backup_to" value="remote" <?php echo esc_attr($default_backup_remote); ?> />Backup to remote storage
                </label>
                <span style="padding:0 0.2em;"></span>
                <span id="mwp_wpvivid_manual_backup_remote_selector_part" style="<?php echo esc_attr($default_remote_seletor); ?>">
                    <select id="mwp_wpvivid_manual_backup_remote_selector">
                        <?php
                        $remoteslist=array();
                        foreach ($remoteslist as $key=>$remote_option)
                        {
                            if($key=='remote_selected')
                            {
                                continue;
                            }
                            if(!isset($remote_option['id']))
                            {
                                $remote_option['id'] = $key;
                            }
                            ?>
                            <option value="<?php echo esc_attr($remote_option['id']); ?>" selected="selected"><?php echo esc_html($remote_option['name']); ?></option>
                            <?php
                        }
                        ?>
                        <option value="all">All activated remote storage</option>
                    </select>
                </span>
            </div>
        </div>
        <div style="clear: both;"></div>
        <p></p>
        <?php
    }

    function mwp_wpvivid_backup_type_addon()
    {
        ?>
        <fieldset >
            <label style="padding-right:2em;">
                <input type="radio" option="mwp_backup" name="mwp_backup_files" value="files+db" checked="checked">
                <span>Wordpress Files + Database</span>
            </label>
            <label style="padding-right:2em;">
                <input type="radio" option="mwp_backup" name="mwp_backup_files" value="db">
                <span>Database</span>
            </label>
            <label style="padding-right:2em;">
                <input type="radio" option="mwp_backup" name="mwp_backup_files" value="files">
                <span>Wordpress Files</span>
            </label>
            <label style="padding-right:2em;">
                <input type="radio" option="mwp_backup" name="mwp_backup_files" value="custom">
                <span>Custom content</span>
            </label>
        </fieldset>
        <script>
            jQuery('input:radio[option=mwp_backup][name=mwp_backup_files]').click(function()
            {
                if(this.value === 'custom')
                {
                    jQuery('#wpvivid_custom_manual_backup').show();
                }
                else
                {
                    jQuery('#wpvivid_custom_manual_backup').hide();
                }
            });
        </script>
        <?php
    }

    function mwp_wpvivid_backup_content_selector_addon()
    {
        $prefix = '';
        $prefix = apply_filters('mwp_wpvivid_get_backup_prefix', $prefix);
        ?>
        <div class="mwp-wpvivid-one-coloum" style="padding: 0;">
            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float">
                <?php $this->mwp_wpvivid_backup_to_addon();?>
                <div style="">
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                    <div style="padding:0.5em;margin-bottom:0.5em;background:#eaf1fe;border-radius:8px;">
                        <?php
                        $this->mwp_wpvivid_backup_type_addon();
                        ?>
                    </div>
                </div>
                <div id="wpvivid_custom_manual_backup" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                        $custom_backup_manager->set_site_id($this->site_id);
                        $custom_backup_manager->set_parent_id('wpvivid_custom_manual_backup','manual_backup','0','0');
                        $custom_backup_manager->output_custom_backup_db_table();
                        $custom_backup_manager->output_custom_backup_file_table();
                        ?>
                    </div>
                </div>

                <p></p>

                <!--Advanced Option (Exclude)-->
                <div id="wpvivid_custom_manual_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_manual_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <p></p>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_backup" name="backup_prefix" id="mwp_wpvivid_set_manual_prefix" value="<?php echo esc_attr($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php echo esc_attr($prefix); ?>">
                    </p>
                </div>

                <p></p>

                <div style="border-top:1px solid #f1f1f1;padding-top:1em; padding-bottom: 1em;">
                    <input class="ui green mini button" style="width: 200px; height: 50px; font-size: 20px; margin-bottom: 10px; pointer-events: auto; opacity: 1;" id="mwp_wpvivid_backup_btn_addon" type="submit" value="Backup Now">
                </div>
                <div style="text-align: left;">
                    <input type="checkbox" id="wpvivid_backup_lock" option="backup" name="lock">
                    <span>Marking this backup can only be deleted manually</span>
                </div>
                <div style="clear:both;"></div>
            </div>
        </div>
        <?php
    }



    function mwp_wpvivid_backup_progress_addon(){
        if(isset($this->setting['wpvivid_common_setting']['estimate_backup'])) {
            if ($this->setting['wpvivid_common_setting']['estimate_backup']) {
                $mwp_wpvivid_setting_estimate_backup = '';
            } else {
                $mwp_wpvivid_setting_estimate_backup = 'display: none;';
            }
        }
        else{
            $mwp_wpvivid_setting_estimate_backup = '';
        }
        ?>

        <div class="postbox" id="mwp_wpvivid_backup_progress_addon" style="display: none;">
            <div class="mwp-action-progress-bar">
                <div class="mwp-action-progress-bar-percent" style="height:24px;width:0"></div>
            </div>
            <div style="float: left; <?php echo esc_attr($mwp_wpvivid_setting_estimate_backup); ?>">
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Database Size:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('File Size:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
            </div>
            <div style="float: left;">
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Total Size:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Uploaded:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Speed:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
            </div>
            <div style="float: left;">
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Network Connection:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
            </div>
            <div style="clear:both;"></div>
            <div style="margin-left:10px; float: left; width:100%;"><p id="mwp_wpvivid_current_doing"></p></div>
            <div style="clear: both;"></div>
            <div>
                <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" id="mwp_wpvivid_backup_cancel_btn_addon" type="button" value="<?php esc_attr_e( 'Cancel', 'mainwp-wpvivid-extension' ); ?>" /></div>
                <div style="clear: both;"></div>
            </div>
            <div style="clear: both;"></div>
        </div>
        <script>
            jQuery('#mwp_wpvivid_backup_progress_addon').on('click', 'input', function(){
                if(jQuery(this).attr('id') === 'mwp_wpvivid_backup_cancel_btn_addon')
                {
                    mwp_wpvivid_cancel_backup_addon();
                }
            });

            function mwp_wpvivid_cancel_backup_addon(){
                var ajax_data= {
                    'action': 'mwp_wpvivid_backup_cancel_addon',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_backup_cancel_btn_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        jQuery('#mwp_wpvivid_current_doing').html(jsonarray.msg);
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_backup_cancel_btn_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('cancelling the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    function mwp_wpvivid_backup_js_addon(){
        ?>
        <script>
            function mwp_wpvivid_refresh_custom_backup_info(){
                mwp_wpvivid_get_database_tables();
                mwp_wpvivid_get_themes_plugins();
            }

            var mwp_wpvivid_get_database_retry_times = 0;
            var mwp_wpvivid_get_themes_retry_times = 0;

            function mwp_wpvivid_get_database_retry(){
                var need_retry_custom_database = false;
                mwp_wpvivid_get_database_retry_times++;
                if(mwp_wpvivid_get_database_retry_times < 10){
                    need_retry_custom_database = true;
                }
                if(need_retry_custom_database){
                    setTimeout(function(){
                        mwp_wpvivid_get_database_tables();
                    }, 3000);
                }
                else{
                    var refresh_btn = '<input class="ui green mini button" type="button" value="Refresh" onclick="mwp_wpvivid_refresh_database_tables();">';
                    jQuery('#mwp_wpvivid_manual_backup_custom_module').find('.mwp-wpvivid-custom-database-info').html(refresh_btn);
                    jQuery('#mwp_wpvivid_schedule_add_custom_module').find('.mwp-wpvivid-custom-database-info').html(refresh_btn);
                    jQuery('#mwp_wpvivid_schedule_update_custom_module').find('.mwp-wpvivid-custom-database-info').html(refresh_btn);
                }
            }

            function mwp_wpvivid_refresh_database_tables(){
                mwp_wpvivid_get_database_retry_times = 0;
                var custom_database_loading = '<div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>' +
                    '<div style="float: left;">Archieving themes and plugins</div>' +
                    '<div style="clear: both;"></div>';
                jQuery('#mwp_wpvivid_manual_backup_custom_module').find('.mwp-wpvivid-custom-database-info').html(custom_database_loading);
                jQuery('#mwp_wpvivid_schedule_add_custom_module').find('.mwp-wpvivid-custom-database-info').html(custom_database_loading);
                jQuery('#mwp_wpvivid_schedule_update_custom_module').find('.mwp-wpvivid-custom-database-info').html(custom_database_loading);
                mwp_wpvivid_get_database_tables();
            }

            function mwp_wpvivid_get_database_tables(){
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_database_tables',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data){
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success') {
                            mwp_wpvivid_get_database_retry_times = 0;
                            jQuery('#wpvivid_custom_manual_backup').find('.mwp-wpvivid-custom-database-info').html(json.database_tables);
                            jQuery('#wpvivid_custom_schedule_backup').find('.mwp-wpvivid-custom-database-info').html(json.database_tables);
                            jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-database-info').html(json.database_tables);
                        }
                        else{
                            mwp_wpvivid_get_database_retry();
                        }
                    }
                    catch(err) {
                        mwp_wpvivid_get_database_retry();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    mwp_wpvivid_get_database_retry();
                });
            }

            function mwp_wpvivid_get_themes_retry(){
                var need_retry_custom_themes = false;
                mwp_wpvivid_get_themes_retry_times++;
                if(mwp_wpvivid_get_themes_retry_times < 10){
                    need_retry_custom_themes = true;
                }
                if(need_retry_custom_themes){
                    setTimeout(function(){
                        mwp_wpvivid_get_themes_plugins();
                    }, 3000);
                }
                else{
                    var refresh_btn = '<input class="ui green mini button" type="button" value="Refresh" onclick="mwp_wpvivid_refresh_themes_plugins();">';
                    jQuery('#mwp_wpvivid_manual_backup_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(refresh_btn);
                    jQuery('#mwp_wpvivid_schedule_add_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(refresh_btn);
                    jQuery('#mwp_wpvivid_schedule_update_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(refresh_btn);
                }
            }

            function mwp_wpvivid_refresh_themes_plugins(){
                mwp_wpvivid_get_themes_retry_times = 0;
                var custom_themes_loading = '<div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>' +
                    '<div style="float: left;">Archieving database tables</div>' +
                    '<div style="clear: both;"></div>';
                jQuery('#mwp_wpvivid_manual_backup_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(custom_themes_loading);
                jQuery('#mwp_wpvivid_schedule_add_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(custom_themes_loading);
                jQuery('#mwp_wpvivid_schedule_update_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(custom_themes_loading);
                mwp_wpvivid_get_themes_plugins();
            }

            function mwp_wpvivid_get_themes_plugins(){
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_themes_plugins',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data){
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success') {
                            mwp_wpvivid_get_themes_retry_times = 0;
                            jQuery('#mwp_wpvivid_manual_backup_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(json.themes_plugins_table);
                            jQuery('#mwp_wpvivid_schedule_add_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(json.themes_plugins_table);
                            jQuery('#mwp_wpvivid_schedule_update_custom_module').find('.mwp-wpvivid-custom-themes-plugins-info').html(json.themes_plugins_table);
                        }
                        else{
                            mwp_wpvivid_get_themes_retry();
                        }
                    }
                    catch(err) {
                        mwp_wpvivid_get_themes_retry();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    mwp_wpvivid_get_themes_retry();
                });
            }

            function mwp_wpvivid_get_uploads_tree(parent_id, refresh){
                if(refresh){
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-uploads-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-uploads-tree-info').jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id
                                };
                                tree_node = JSON.stringify(tree_node);
                                var ajax_data = {
                                    'action': 'mwp_wpvivid_get_uploads_tree_data',
                                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=ajax_object.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: ajax_object.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        if(jsonarray.result === 'success') {
                                            callback.call(this, jsonarray.uploads_tree_data);
                                        }
                                        else{
                                            alert(jsonarray.error);
                                        }
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        }
                    });
                }
            }

            function mwp_wpvivid_get_content_tree(parent_id, refresh){
                if(refresh){
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-content-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-content-tree-info').jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id
                                };
                                tree_node = JSON.stringify(tree_node);
                                var ajax_data = {
                                    'action': 'mwp_wpvivid_get_content_tree_data',
                                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=ajax_object.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: ajax_object.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        if(jsonarray.result === 'success') {
                                            callback.call(this, jsonarray.content_tree_data);
                                        }
                                        else{
                                            alert(jsonarray.error);
                                        }
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        }
                    });
                }
            }

            function mwp_wpvivid_get_additional_folder_tree(parent_id, refresh){
                if(refresh){
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-folder-tree-info').jstree('refresh');
                }
                else {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-folder-tree-info').jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id
                                };
                                tree_node = JSON.stringify(tree_node);
                                var ajax_data = {
                                    'action': 'mwp_wpvivid_get_additional_folder_tree_data',
                                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=ajax_object.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: ajax_object.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        if(jsonarray.result === 'success') {
                                            callback.call(this, jsonarray.additional_folder_tree_data);
                                        }
                                        else{
                                            alert(jsonarray.error);
                                        }
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        }
                    });
                }
            }

            function mwp_wpvivid_additional_database_connect(parent_id){
                var db_user = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-user').val();
                var db_pass = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-pass').val();
                var db_host = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-host').val();
                if(db_user !== ''){
                    if(db_host !== ''){
                        var db_json = {};
                        db_json['db_user'] = db_user;
                        db_json['db_pass'] = db_pass;
                        db_json['db_host'] = db_host;
                        var db_connect_info = JSON.stringify(db_json);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_connect_additional_database_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'database_info': db_connect_info
                        };
                        jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-connect').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-close').css({'pointer-events': 'none', 'opacity': '0.4'});
                        mwp_wpvivid_post_request(ajax_data, function (data){
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-connect').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-close').css({'pointer-events': 'auto', 'opacity': '1'});
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray !== null) {
                                if (jsonarray.result === 'success') {
                                    var div = '<div class="mwp-wpvivid-additional-db-account" style="border: 1px solid #e5e5e5; border-bottom: 0; margin-top: 0; margin-bottom: 0; padding: 10px; box-sizing:border-box;">' + jsonarray.html + '</div>';
                                    jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-list').append(div);
                                    jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-connect').hide();
                                    jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-close').hide();
                                }
                                else {
                                    alert(jsonarray.error);
                                }
                            }
                            else {
                                alert('Login Failed. Please check the credentials you entered and try again.');
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-connect').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-close').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Host is required.');
                    }
                }
                else{
                    alert('User Name is required.');
                }
            }

            function mwp_wpvivid_additional_database_add(parent_id){
                var db_user = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-user').val();
                var db_pass = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-pass').val();
                var db_host = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-host').val();
                if(db_user !== ''){
                    if(db_host !== ''){
                        var json = {};
                        json['db_user'] = db_user;
                        json['db_pass'] = db_pass;
                        json['db_host'] = db_host;
                        json['additional_database_list'] = Array();
                        jQuery('#'+parent_id).find('input:checkbox[option=additional_db]').each(function () {
                            if (jQuery(this).prop('checked')) {
                                json['additional_database_list'].push(this.value);
                            }
                        });
                        var database_info = JSON.stringify(json);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_add_additional_database_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'database_info': database_info
                        };
                        jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-add').css({'pointer-events': 'none', 'opacity': '0.4'});
                        jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-table-close').css({'pointer-events': 'none', 'opacity': '0.4'});
                        mwp_wpvivid_post_request(ajax_data, function (data){
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-add').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-table-close').css({'pointer-events': 'auto', 'opacity': '1'});
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-list').html(jsonarray.html);
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-add').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#'+parent_id).find('.mwp-wpvivid-additional-db-table-close').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Host is required.');
                    }
                }
                else{
                    alert('User Name is required.');
                }
            }

            function mwp_wpvivid_additional_database_remove(parent_id, database_name){
                var ajax_data = {
                    'action': 'mwp_wpvivid_remove_additional_database_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'database_name': database_name
                }
                jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-remove').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data){
                    jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-remove').css({'pointer-events': 'auto', 'opacity': '1'});
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-list').html(jsonarray.html);
                    }
                    else {
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-remove').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_add_extension_rule(obj, type, value){
                var ajax_data = {
                    'action': 'mwp_wpvivid_update_backup_exclude_extension_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'type': type,
                    'exclude_content': value
                };
                jQuery(obj).css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data){
                    jQuery(obj).css({'pointer-events': 'auto', 'opacity': '1'});
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(obj).css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_get_remote_storage_addon(){
                var ajax_data={
                    'action': 'mwp_wpvivid_get_remote_storage_addon',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };

                mwp_wpvivid_post_request(ajax_data, function (data){
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success')
                        {
                            var html = '';
                            /*if(!json.has_remote){
                                mwp_wpvivid_has_remote = false;
                            }*/
                            mwp_wpvivid_has_remote = false;
                            var default_remote_count=0;
                            jQuery.each(json.remoteslist, function (index, value) {
                                if(index==='remote_selected')
                                {
                                    return true;
                                }

                                jQuery.each(json.remoteslist.remote_selected, function (remote_selected_index, remote_selected_value){
                                    if(remote_selected_value === index)
                                    {
                                        mwp_wpvivid_has_remote = true;
                                        default_remote_count++;
                                        if(typeof value.id === 'undefined')
                                        {
                                            value.id = index;
                                        }
                                        html += '<option value="'+value.id+'" selected="selected">'+value.name+'</option>';
                                    }
                                });
                            });
                            if(default_remote_count > 1)
                            {
                                html += '<option value="all" selected="selected">All activated remote storage</option>';
                            }
                            else
                            {
                                html += '<option value="all">All activated remote storage</option>';
                            }
                            jQuery('#mwp_wpvivid_manual_backup_remote_selector').html(html);
                            jQuery('#mwp_wpvivid_create_schedule_backup_remote_selector').html(html);
                            jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector').html(html);
                            jQuery('#mwp_wpvivid_incremental_backup_remote_selector').html(html);
                        }
                    }
                    catch(err)
                    {
                        mwp_wpvivid_get_remote_storage_addon();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    mwp_wpvivid_get_remote_storage_addon();
                });
            }
            mwp_wpvivid_get_remote_storage_addon();

            jQuery('input:radio[option=mwp_backup][name=mwp_backup_to]').click(function(){
                mwp_wpvivid_switch_backup_btn('backup');
                var value = jQuery(this).prop('value');
                if(value === 'remote' || value === 'staging_remote' || value === 'migrate_remote'){
                    if(!mwp_wpvivid_has_remote){
                        alert('There is no default remote storage configured. Please set it up first.');
                        jQuery('input:radio[option=mwp_backup][name=mwp_backup_to][value=local]').prop('checked', true);
                    }
                    else{
                        if(value === 'remote'){
                            jQuery('#mwp_wpvivid_manual_backup_remote_selector_part').show();
                        }
                        else if(value === 'staging_remote'){
                            mwp_wpvivid_switch_backup_btn('staging');
                        }
                        else if(value === 'migrate_remote'){
                            mwp_wpvivid_switch_backup_btn('migrate');
                        }
                    }
                }
                else
                {
                    jQuery('#mwp_wpvivid_manual_backup_remote_selector_part').hide();
                }
            });

            function mwp_wpvivid_switch_backup_btn(type){
                jQuery('#mwp_wpvivid_backup_btn_addon').val('Backup Now');
                jQuery('#mwp_wpvivid_backup_btn_addon').css({'width': '200'});
                if(type === 'backup'){
                    jQuery('#mwp_wpvivid_backup_btn_addon').val('Backup Now');
                    jQuery('#mwp_wpvivid_backup_btn_addon').css({'width': '200'});
                }
                else if(type === 'staging'){
                    jQuery('#mwp_wpvivid_backup_btn_addon').val('Clone the Site Now');
                    jQuery('#mwp_wpvivid_backup_btn_addon').css({'width': '250'});
                }
                else if(type === 'migrate'){
                    jQuery('#mwp_wpvivid_backup_btn_addon').val('Migrate');
                    jQuery('#mwp_wpvivid_backup_btn_addon').css({'width': '160'});
                }
            }

            /*jQuery('input:radio[option=mwp_backup][name=mwp_backup_files]').click(function(){
                if(this.value === 'custom'){
                    jQuery('#mwp_wpvivid_manual_backup_custom_module_part').show();
                    mwp_wpvivid_popup_schedule_tour_addon('show', 'manual_backup');
                }
                else{
                    jQuery('#mwp_wpvivid_manual_backup_custom_module_part').hide();
                    mwp_wpvivid_popup_schedule_tour_addon('hide', 'manual_backup');
                }
            });*/

            /*function mwp_wpvivid_popup_schedule_tour_addon(style, type) {
                var popup = document.getElementById("mwp_wpvivid_"+type+"_custom_module");
                if (popup != null) {
                    if(style === 'show') {
                        if(popup.classList.contains('hide')){
                            popup.classList.remove('hide');
                        }
                        popup.classList.add(style);
                    }
                    else if(style === 'hide'){
                        if(popup.classList.contains('show')){
                            popup.classList.remove('hide');
                            popup.classList.add(style);
                        }
                    }
                }
            }*/

            jQuery('#mwp_wpvivid_backup_btn_addon').on('click', function(){
                mwp_wpvivid_clear_notice('mwp_wpvivid_backup_notice');
                mwp_wpvivid_prepare_backup_addon();
            });

            var mwp_wpvivid_prepare_backup=false;
            var mwp_wpvivid_running_backup_taskid='';
            var mwp_task_retry_times = 0;

            function mwp_wpvivid_create_custom_backup_json(parent_id){
                var json = {};
                jQuery('#'+parent_id).find('.mwp-wpvivid-custom-check').each(function(){
                    if(jQuery(this).hasClass('mwp-wpvivid-custom-core-check')){
                        json['core_list'] = Array();
                        if(jQuery(this).prop('checked')){
                            json['core_check'] = '1';
                        }
                        else{
                            json['core_check'] = '0';
                        }
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-custom-database-check')){
                        json['database_list'] = Array();
                        if(jQuery(this).prop('checked')){
                            json['database_check'] = '1';
                            jQuery('#'+parent_id).find('input:checkbox[name=Database]').each(function(){
                                if(!jQuery(this).prop('checked')){
                                    json['database_list'].push(jQuery(this).val());
                                }
                            });
                        }
                        else{
                            json['database_check'] = '0';
                        }
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-custom-themes-plugins-check')){
                        json['themes_list'] = Array();
                        json['plugins_list'] = Array();
                        if(jQuery(this).prop('checked')){
                            json['themes_check'] = '0';
                            json['plugins_check'] = '0';
                            jQuery('#'+parent_id).find('input:checkbox[option=mwp_themes][name=Themes]').each(function(){
                                if(jQuery(this).prop('checked')){
                                    json['themes_check'] = '1';
                                }
                                else{
                                    json['themes_list'].push(jQuery(this).val());
                                }
                            });
                            jQuery('#'+parent_id).find('input:checkbox[option=mwp_plugins][name=Plugins]').each(function(){
                                if(jQuery(this).prop('checked')) {
                                    json['plugins_check'] = '1';
                                }
                                else{
                                    json['plugins_list'].push(jQuery(this).val());
                                }
                            });
                        }
                        else{
                            json['themes_check'] = '0';
                            json['plugins_check'] = '0';
                        }
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-custom-uploads-check')){
                        json['uploads_list'] = {};
                        if(jQuery(this).prop('checked')){
                            json['uploads_check'] = '1';
                            jQuery('#'+parent_id).find('.mwp-wpvivid-custom-exclude-uploads-list ul').find('li div:eq(1)').each(function(){
                                var folder_name = this.innerHTML;
                                json['uploads_list'][folder_name] = {};
                                json['uploads_list'][folder_name]['name'] = folder_name;
                                json['uploads_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                            });
                            json['upload_extension'] = jQuery('#'+parent_id).find('.mwp-wpvivid-uploads-extension').val();
                        }
                        else{
                            json['uploads_check'] = '0';
                        }
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-custom-content-check')){
                        json['content_list'] = {};
                        if(jQuery(this).prop('checked')){
                            json['content_check'] = '1';
                            jQuery('#'+parent_id).find('.mwp-wpvivid-custom-exclude-content-list ul').find('li div:eq(1)').each(function(){
                                var folder_name = this.innerHTML;
                                json['content_list'][folder_name] = {};
                                json['content_list'][folder_name]['name'] = folder_name;
                                json['content_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                            });
                            json['content_extension'] = jQuery('#'+parent_id).find('.mwp-wpvivid-content-extension').val();
                        }
                        else{
                            json['content_check'] = '0';
                        }
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-custom-additional-folder-check')){
                        json['other_list'] = {};
                        if(jQuery(this).prop('checked')){
                            json['other_check'] = '1';
                            jQuery('#'+parent_id).find('.mwp-wpvivid-custom-include-additional-folder-list ul').find('li div:eq(1)').each(function(){
                                var folder_name = this.innerHTML;
                                json['other_list'][folder_name] = {};
                                json['other_list'][folder_name]['name'] = folder_name;
                                json['other_list'][folder_name]['type'] = jQuery(this).prev().get(0).classList.item(0);
                            });
                            json['other_extension'] = jQuery('#'+parent_id).find('.mwp-wpvivid-additional-folder-extension').val();
                        }
                        else{
                            json['other_check'] = '0';
                        }
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-custom-additional-database-check')){
                        if(jQuery(this).prop('checked')) {
                            json['additional_database_check'] = '1';
                        }
                        else{
                            json['additional_database_check'] = '0';
                        }
                    }
                });
                return json;
            }

            function mwp_wpvivid_delete_ready_task(error){
                var ajax_data={
                    'action': 'mwp_wpvivid_delete_ready_task_addon',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            mwp_wpvivid_add_notice('Backup', 'Error', error);
                            jQuery('#mwp_wpvivid_backup_btn_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#mwp_wpvivid_backup_progress_addon').hide();
                        }
                    }
                    catch(err){
                        mwp_wpvivid_add_notice('Backup', 'Error', err);
                        jQuery('#mwp_wpvivid_backup_btn_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#mwp_wpvivid_backup_progress_addon').hide();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        mwp_wpvivid_delete_ready_task(error);
                    }, 3000);
                });
            }

            function mwp_wpvivid_prepare_backup_addon() {
                var backup_data = mwp_wpvivid_ajax_data_transfer('mwp_backup');

                backup_data = JSON.parse(backup_data);
                var exclude_dirs = mwp_wpvivid_get_exclude_json('wpvivid_custom_manual_advanced_option');
                var custom_option = {
                    'exclude_files': exclude_dirs
                };
                jQuery.extend(backup_data, custom_option);

                var exclude_file_type = mwp_wpvivid_get_exclude_file_type('wpvivid_custom_manual_advanced_option');
                var exclude_file_type_option = {
                    'exclude_file_type': exclude_file_type
                };
                jQuery.extend(backup_data, exclude_file_type_option);
                backup_data = JSON.stringify(backup_data);

                jQuery('input:radio[option=mwp_backup]').each(function ()
                {
                    if(jQuery(this).prop('checked'))
                    {
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).prop('value');
                        if(value === 'custom')
                        {
                            backup_data = JSON.parse(backup_data);
                            var custom_dirs = mwp_wpvivid_get_custom_setting_json_ex('wpvivid_custom_manual_backup');
                            var custom_option = {
                                'custom_dirs': custom_dirs
                            };
                            jQuery.extend(backup_data, custom_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                    }
                });

                jQuery('input:radio[option=mwp_backup][name=mwp_backup_to]').each(function ()
                {
                    if (jQuery(this).prop('checked'))
                    {
                        if (this.value === 'remote')
                        {
                            backup_data = JSON.parse(backup_data);
                            var remote_id_select = jQuery('#mwp_wpvivid_manual_backup_remote_selector').val();
                            var local_remote_option = {
                                'remote_id_select': remote_id_select
                            };
                            jQuery.extend(backup_data, local_remote_option);
                            backup_data = JSON.stringify(backup_data);
                        }
                    }
                });

                var ajax_data={
                    'action': 'mwp_wpvivid_prepare_backup_addon',
                    'site_id':'<?php echo esc_html($this->site_id); ?>',
                    'backup': backup_data
                };
                jQuery('#mwp_wpvivid_backup_btn_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_prepare_backup=true;
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    mwp_wpvivid_prepare_backup=false;
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success')
                        {
                            mwp_wpvivid_backup_now_addon(json.data);
                        }
                        else
                        {
                            mwp_wpvivid_delete_ready_task(json.error);
                        }
                    }
                    catch(err) {
                        mwp_wpvivid_delete_ready_task(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    mwp_wpvivid_prepare_backup=false;
                    var error_message = mwp_wpvivid_output_ajaxerror('preparing the backup', textStatus, errorThrown);
                    mwp_wpvivid_delete_ready_task(error_message);
                });
            }

            function mwp_wpvivid_backup_now_addon(task_id) {
                var ajax_data = {
                    'action': 'mwp_wpvivid_backup_now_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'task_id': task_id
                };
                mwp_wpvivid_need_update = true;
                mwp_wpvivid_post_request(ajax_data, function (data) {
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                });
            }

            function mwp_wpvivid_list_task_addon(){
                var ajax_data={
                    'action': 'mwp_wpvivid_list_task_addon',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    setTimeout(function () {
                        mwp_wpvivid_manage_task_addon();
                    }, 3000);
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        mwp_wpvivid_list_task_data(jsonarray);
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        mwp_wpvivid_need_update = true;
                        mwp_wpvivid_manage_task_addon();
                    }, 3000);
                });
            }

            function mwp_wpvivid_list_task_data(data){
                var b_has_data = false;
                var update_backup=false;

                if(data.progress_html!==false) {
                    jQuery('#mwp_wpvivid_backup_progress_addon').show();
                    jQuery('#mwp_wpvivid_backup_progress_addon').html(data.progress_html);
                }
                else {
                    if(!mwp_wpvivid_prepare_backup) {
                        jQuery('#mwp_wpvivid_backup_progress_addon').hide();
                    }
                }
                if (data.success_notice_html !== false) {
                    jQuery('#mwp_wpvivid_backup_notice').show();
                    jQuery('#mwp_wpvivid_backup_notice').append(data.success_notice_html);
                    update_backup=true;
                }
                if(data.error_notice_html !== false) {
                    jQuery('#mwp_wpvivid_backup_notice').show();
                    jQuery('#mwp_wpvivid_backup_notice').append(data.error_notice_html);
                    update_backup=true;
                }
                if(update_backup) {
                    jQuery( document ).trigger( 'mwp_wpvivid_update_local_backup');
                }
                if(data.need_refresh_remote !== false){
                    jQuery( document ).trigger( 'mwp_wpvivid_update_remote_backup');
                }
                if(data.need_update) {
                    mwp_wpvivid_need_update = true;
                }
                if(data.running_backup_taskid!== '') {
                    b_has_data = true;
                    mwp_task_retry_times = 0;
                    mwp_wpvivid_running_backup_taskid = data.running_backup_taskid;
                    jQuery('#mwp_wpvivid_backup_btn_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                    if(data.wait_resume) {
                        if (data.next_resume_time !== 'get next resume time failed.') {
                            mwp_wpvivid_resume_backup_addon(mwp_wpvivid_running_backup_taskid, data.next_resume_time);
                        }
                        else {
                            wpvivid_delete_backup_task(mwp_wpvivid_running_backup_taskid);
                        }
                    }
                }
                else {
                    if(!mwp_wpvivid_prepare_backup) {
                        jQuery('#mwp_wpvivid_backup_cancel_btn_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#mwp_wpvivid_backup_btn_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                    mwp_wpvivid_running_backup_taskid='';
                }
                if (!b_has_data) {
                    mwp_task_retry_times++;
                    if (mwp_task_retry_times < 5) {
                        mwp_wpvivid_need_update = true;
                    }
                }
            }

            function mwp_wpvivid_switch_staging(){
                <?php
                $white_label_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
                if(!$white_label_setting){
                    $location = 'admin.php?page=wpvivid-staging&from-mainwp';
                }
                else{
                    $slug_page = strtolower($white_label_setting['white_label_slug']);
                    $location = 'admin.php?page='.$slug_page.'-staging&from-mainwp';
                }
                ?>
                location.href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($this->site_id); ?>&location=<?php echo esc_html(base64_encode($location)); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
            }

            function mwp_wpvivid_manage_task_addon() {
                if(mwp_wpvivid_need_update === true){
                    mwp_wpvivid_need_update = false;
                    mwp_wpvivid_list_task_addon();
                }
                else{
                    setTimeout(function(){
                        mwp_wpvivid_manage_task_addon();
                    }, 3000);
                }
            }

            function mwp_wpvivid_active_cron_addon(){
                var next_get_time = 3 * 60 * 1000;
                mwp_wpvivid_cron_task_addon();
                setTimeout("mwp_wpvivid_active_cron_addon()", next_get_time);
                setTimeout(function(){
                    mwp_wpvivid_need_update=true;
                }, 10000);
            }

            function mwp_wpvivid_cron_task_addon(){
                var site_url = '<?php echo esc_url(home_url()); ?>';
                jQuery.get(site_url+'/wp-cron.php');
            }

            function mwp_wpvivid_resume_backup_addon(backup_id, next_resume_time){
                if(next_resume_time < 0){
                    next_resume_time = 0;
                }
                next_resume_time = next_resume_time * 1000;
                setTimeout("mwp_wpvivid_cron_task_addon()", next_resume_time);
                setTimeout(function(){
                    mwp_task_retry_times = 0;
                    mwp_wpvivid_need_update=true;
                }, next_resume_time);
            }

            jQuery(document).ready(function(){
                mwp_wpvivid_refresh_custom_backup_info();
                mwp_wpvivid_active_cron_addon();
                mwp_wpvivid_manage_task_addon();
            });
        </script>
        <?php
    }

    function mwp_wpvivid_backup_progress(){
        if(isset($this->setting['wpvivid_common_setting']['estimate_backup']) && $this->setting['wpvivid_common_setting']['estimate_backup'])
        {
            $mwp_wpvivid_setting_estimate_backup='';
        }
        else{
            $mwp_wpvivid_setting_estimate_backup='display: none;';
        }
        ?>

        <div class="postbox" id="mwp_wpvivid_backup_progress" style="display: none;">
            <div class="mwp-action-progress-bar">
                <div class="mwp-action-progress-bar-percent" style="height:24px;width:0"></div>
            </div>
            <div style="float: left; <?php echo esc_attr($mwp_wpvivid_setting_estimate_backup); ?>">
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Database Size:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('File Size:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
            </div>
            <div style="float: left;">
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Total Size:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Uploaded:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Speed:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
            </div>
            <div style="float: left;">
                <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span"><?php esc_html_e('Network Connection:', 'mainwp-wpvivid-extension'); ?></span><span class="mwp-wpvivid-span">N/A</span></div>
            </div>
            <div style="clear:both;"></div>
            <div style="margin-left:10px; float: left; width:100%;"><p id="mwp_wpvivid_current_doing"></p></div>
            <div style="clear: both;"></div>
            <div>
                <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" id="mwp_wpvivid_backup_cancel_btn" type="button" value="<?php esc_attr_e( 'Cancel', 'mainwp-wpvivid-extension' ); ?>" onclick="mwp_wpvivid_cancel_backup();" /></div>
                <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" type="button" value="<?php esc_attr_e( 'Log', 'mainwp-wpvivid-extension' ); ?>" onclick="mwp_wpvivid_read_log('mwp_wpvivid_view_backup_task_log');" /></div>
                <div style="clear: both;"></div>
            </div>
            <div style="clear: both;"></div>
        </div>
        <script>
            function mwp_wpvivid_cancel_backup(){
                var ajax_data= {
                    'action': 'mwp_wpvivid_backup_cancel',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_backup_cancel_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        jQuery('#mwp_wpvivid_current_doing').html(jsonarray.msg);
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_backup_cancel_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('cancelling the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    function mwp_wpvivid_backup_manual(){
        if(isset($this->setting['wpvivid_local_setting']['path']))
        {
            $path = $this->setting['wpvivid_local_setting']['path'];
        }
        else
        {
            $path = '';
        }
        ?>
        <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_backup_notice"></div>
        <div class="postbox mwp-quickbackup" style="margin-bottom: 10px;">
            <h2><span><?php esc_html_e( 'Back Up Manually','mainwp-wpvivid-extension'); ?></span></h2>
            <div class="mwp-quickstart-storage-setting">
                <span class="mwp-list-top-chip backup" name="ismerge" value="1"><?php esc_html_e('Child-Site Storage Directory: ', 'mainwp-wpvivid-extension'); ?></span>
                <span class="mwp-list-top-chip"><?php esc_html_e('http(s)://child-site/wp-content/'); ?><?php echo esc_html($path); ?></span>
                <span class="mwp-list-top-chip"><a href="#" onclick="mwp_switch_wpvivid_tab('setting');"><?php esc_html_e(' rename directory', 'mainwp-wpvivid-extension'); ?></a></span>
            </div>

            <div class="mwp-quickstart-archive-block">
                <legend class="screen-reader-text"><span>input type="radio"</span></legend>
                <label>
                    <input type="radio" option="mwp_backup" name="mwp_backup_files" value="files+db" checked />
                    <span><?php esc_html_e( 'Database + Files (WordPress Files)', 'mainwp-wpvivid-extension' ); ?></span>
                </label><br>
                <label>
                    <input type="radio" option="mwp_backup" name="mwp_backup_files" value="files" />
                    <span><?php esc_html_e( 'WordPress Files (Exclude Database)', 'mainwp-wpvivid-extension' ); ?></span>
                </label><br>
                <label>
                    <input type="radio" option="mwp_backup" name="mwp_backup_files" value="db" />
                    <span><?php esc_html_e( 'Only Database', 'mainwp-wpvivid-extension' ); ?></span>
                </label><br>
                <label style="display: none;">
                    <input type="checkbox" option="mwp_backup" name="mwp_ismerge" value="1" checked />
                </label><br>
            </div>

            <div class="mwp-quickstart-storage-block">
                <legend class="screen-reader-text"><span>input type="checkbox"</span></legend>
                <label>
                    <input type="radio" option="mwp_backup_ex" name="mwp_local_remote" value="local" checked />
                    <span><?php esc_html_e( 'Save Backups to Child-Site Local', 'mainwp-wpvivid-extension' ); ?></span>
                </label>

                <div style="clear:both;"></div>
                <label>
                    <input type="radio" option="mwp_backup_ex" name="mwp_local_remote" value="remote" />
                    <span><?php esc_html_e( 'Send Backup to Remote Storage', 'mainwp-wpvivid-extension' ); ?></span>
                </label><br>
                <div id="mwp_wpvivid_upload_storage" style="cursor:pointer;" title="Highlighted icon illuminates that you have choosed a remote storage to store backups">
                </div>
            </div>

            <div class="mwp-quickstart-btn" style="padding-top:20px;">
                <div class="mwp-wpvivid-block-bottom-space">
                    <input class="ui green mini button mwp-quickbackup-btn" id="mwp_wpvivid_backup_btn"  style="margin: 0 auto !important;" type="button" value="<?php esc_attr_e( 'Backup Now', 'mainwp-wpvivid-extension'); ?>" onclick="mwp_wpvivid_prepare_backup();" />
                </div>
                <div class="mwp-schedule-tab-block" style="text-align:center;">
                    <fieldset>
                        <label>
                            <input type="checkbox" option="mwp_backup" name="mwp_lock" />
                            <span><?php esc_html_e( 'This backup can only be deleted manually', 'mainwp-wpvivid-extension' ); ?></span>
                        </label>
                    </fieldset>
                </div>
            </div>

            <div class="mwp-custom-info" style="float:left; width:100%;">
                <strong><?php esc_html_e('Tips', 'mainwp-wpvivid-extension'); ?></strong><?php esc_html_e(': The settings is only for manual backup, which won\'t affect schedule settings.', 'mainwp-wpvivid-extension'); ?>
            </div>
        </div>
        <script>
            function mwp_wpvivid_get_default_remote(){
                var ajax_data={
                    'action': 'mwp_wpvivid_get_default_remote',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };

                mwp_wpvivid_post_request(ajax_data, function (data){
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success')
                        {
                            if(json.default_remote_storage === ''){
                                mwp_wpvivid_has_remote = false;
                            }
                            jQuery('#mwp_wpvivid_upload_storage').html(json.default_remote_pic);
                            jQuery('#mwp_schedule_upload_storage').html(json.default_remote_pic);
                            jQuery('#mwp_wpvivid_schedule_upload_storage').html(json.default_remote_pic);
                        }
                    }
                    catch(err)
                    {
                        mwp_wpvivid_get_default_remote();
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    mwp_wpvivid_get_default_remote();
                });
            }
            mwp_wpvivid_get_default_remote();

            jQuery('input:radio[option=mwp_backup_ex][name=mwp_local_remote]').click(function(){
                var value = jQuery(this).prop('value');
                if(value === 'remote'){
                    if(!mwp_wpvivid_has_remote){
                        alert('There is no default remote storage configured. Please set it up first.');
                        jQuery('input:radio[option=mwp_backup_ex][name=mwp_local_remote][value=local]').prop('checked', true);
                    }
                }
            });

            function mwp_wpvivid_prepare_backup()
            {
                mwp_wpvivid_clear_notice('mwp_wpvivid_backup_notice');
                var backup_data = mwp_wpvivid_ajax_data_transfer('mwp_backup');
                backup_data = JSON.parse(backup_data);
                jQuery('input:radio[option=mwp_backup_ex]').each(function() {
                    if(jQuery(this).prop('checked'))
                    {
                        var key = jQuery(this).prop('name');
                        var value = jQuery(this).prop('value');
                        var json = new Array();
                        if(value == 'local'){
                            json['mwp_local']='1';
                            json['mwp_remote']='0';
                        }
                        else if(value == 'remote'){
                            json['mwp_local']='0';
                            json['mwp_remote']='1';
                        }
                    }
                    jQuery.extend(backup_data, json);
                });

                var ajax_data={
                    'action': 'mwp_wpvivid_prepare_backup',
                    'site_id':'<?php echo esc_html($this->site_id); ?>',
                    'backup': backup_data
                };
                jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success')
                        {
                            mwp_wpvivid_backup_now(json.data);
                        }
                        else
                        {
                            mwp_wpvivid_add_notice('Backup', 'Error', json.error);
                            jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                    }
                    catch(err)
                    {
                        mwp_wpvivid_add_notice('Backup', 'Error', err);
                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    mwp_wpvivid_add_notice('Backup', 'Error', errorThrown);
                    jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                });
            }

            function mwp_wpvivid_backup_now(task_id) {
                var ajax_data={
                    'action': 'mwp_wpvivid_backup_now',
                    'site_id':'<?php echo esc_html($this->site_id); ?>',
                    'task_id':task_id
                };
                mwp_wpvivid_need_update = true;
                mwp_wpvivid_post_request(ajax_data, function (data) {
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                });
            }
        </script>
        <?php
    }

    function mwp_wpvivid_backup_schedule(){
        ?>
        <div class="postbox mwp-qucikbackup-schedule" style="margin-bottom: 10px;">
            <h2><span><?php esc_html_e( 'Backup Schedule','mainwp-wpvivid-extension'); ?></span></h2>
            <div class="mwp-schedule-block" id="mwp_wpvivid_backup_schedule">
            </div>
        </div>
        <div style="clear:both;"></div>
        <script>
            function mwp_wpvivid_get_backup_schedule(){
                var ajax_data={
                    'action': 'mwp_wpvivid_get_backup_schedule',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success') {
                            jQuery('#mwp_wpvivid_backup_schedule').html(json.schedule_html);
                        }
                        else {
                            alert(json.error);
                        }
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        mwp_wpvivid_get_backup_schedule();
                    }, 3000);
                });
            }
            mwp_wpvivid_get_backup_schedule();
        </script>
        <?php
    }

    function mwp_wpvivid_backup_list(){
        ?>
        <h2 class="nav-tab-wrapper mwp-wpvivid-intab" id="wpvivid_backup_tab" style="padding-bottom:0!important;">
            <?php
            $this->mwp_wpvivid_tab_backup_list();
            $this->mwp_wpvivid_tab_log();
            ?>
        </h2>
        <script>
            function mwp_wpvivid_switchrestoreTabs(evt,contentName) {
                // Declare all variables
                var i, tabcontent, tablinks;

                // Get all elements with class="table-list-content" and hide them
                tabcontent = document.getElementsByClassName("mwp-wpvivid-backup-tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                }

                // Get all elements with class="table-nav-tab" and remove the class "nav-tab-active"
                tablinks = document.getElementsByClassName("mwp-wpvivid-backup-nav-tab");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
                }

                // Show the current tab, and add an "storage-menu-active" class to the button that opened the tab
                document.getElementById(contentName).style.display = "block";
                evt.currentTarget.className += " nav-tab-active";
            }
        </script>
        <?php
        $this->mwp_wpvivid_page_backup_list();
        $this->mwp_wpvivid_page_log();
        ?>
        <?php
    }

    function mwp_wpvivid_tab_backup_list(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_backup_list" class="nav-tab mwp-wpvivid-backup-nav-tab nav-tab-active" onclick="mwp_wpvivid_switchrestoreTabs(event,'mwp-page-backuplist')" style="background: #ffffff;"><?php esc_html_e('Backups', 'mainwp-wpvivid-extension'); ?></a>
        <?php
    }

    function mwp_wpvivid_tab_log(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_backup_log" class="nav-tab mwp-wpvivid-backup-nav-tab delete" onclick="mwp_wpvivid_switchrestoreTabs(event,'mwp-page-log')" style="display: none; background: #ffffff;">
            <div style="margin-right: 15px;"><?php esc_html_e('Log', 'mainwp-wpvivid-extension'); ?></div>
            <div class="mwp-nav-tab-delete-img">
                <img src="<?php echo esc_url(plugins_url( 'images/delete-tab.png', __FILE__ )); ?>" style="vertical-align:middle; cursor:pointer;" onclick="mwp_wpvivid_close_tab(event, 'mwp_wpvivid_tab_backup_log', 'mwp-wpvivid-backup', 'mwp_wpvivid_tab_backup_list');" />
            </div>
        </a>
        <?php
    }

    function mwp_wpvivid_page_backup_list(){
        ?>
        <div class="mwp-wpvivid-backup-tab-content mwp_wpvivid_tab_backup_list" id="mwp-page-backuplist" style="border-top: none;">
            <table class="wp-list-table widefat plugins" style="border-collapse: collapse; border-top: none;">
                <thead>
                <tr style="border-bottom: 0;">
                    <td></td>
                    <th><?php esc_html_e( 'Backup','mainwp-wpvivid-extension'); ?></th>
                    <th><?php esc_html_e( 'Storage','mainwp-wpvivid-extension'); ?></th>
                    <th><?php esc_html_e( 'Download','mainwp-wpvivid-extension'); ?></th>
                    <th><?php esc_html_e( 'Restore','mainwp-wpvivid-extension'); ?></th>
                    <th><?php esc_html_e( 'Delete','mainwp-wpvivid-extension'); ?></th>
                </tr>
                </thead>
                <tbody class="mwp-wpvivid-backuplist" id="mwp_wpvivid_backuplist">

                </tbody>
                <tfoot>
                <tr>
                    <th><input type="checkbox" id="mwp_wpvivid_backuplist_all_check" value="1" onclick="mwp_wpvivid_select_inbatches();" /></th>
                    <th class="row-title" colspan="5"><a onclick="mwp_wpvivid_delete_backups_inbatches();" style="cursor: pointer;"><?php esc_html_e('Delete the selected backups', 'mainwp-wpvivid-extension'); ?></a></th>
                </tr>
                </tfoot>
            </table>
        </div>
        <script>
            function mwp_wpvivid_initialize_restore(backup_id, backup_time, backup_type, restore_type='backup'){
                <?php
                $location = 'admin.php?page=WPvivid';
                ?>
                location.href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($this->site_id); ?>&location=<?php echo esc_html(base64_encode($location)); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
            }

            function mwp_wpvivid_reset_backup_list(){
                jQuery('#mwp_wpvivid_backuplist tr').each(function(i){
                    jQuery(this).children('td').each(function (j) {
                        if (j == 2) {
                            var backup_id = jQuery(this).parent().children('th').find("input[type=checkbox]").attr("id");
                            var download_btn = '<div id="wpvivid_file_part_' + backup_id + '" style="float:left;padding:10px 10px 10px 0px;">' +
                                '<div style="cursor:pointer;" onclick="mwp_wpvivid_initialize_download(\'' + backup_id + '\');" title="Prepare to download the backup">' +
                                '<img id="wpvivid_download_btn_' + backup_id + '" src="<?php echo esc_url(plugins_url( 'images/download.png', __FILE__ )); ?>" style="vertical-align:middle;" />Download' +
                                '<div class="spinner" id="wpvivid_download_loading_' + backup_id + '" style="float:right;width:auto;height:auto;padding:10px 180px 10px 0;background-position:0 0;"></div>' +
                                '</div>' +
                                '</div>';
                            jQuery(this).html(download_btn);
                        }
                    });
                });
            }

            function mwp_wpvivid_delete_backup(backup_id){
                var name = '';
                jQuery('#mwp_wpvivid_backuplist tr').each(function(i){
                    jQuery(this).children('td').each(function (j) {
                        if (j == 0) {
                            var id = jQuery(this).parent().children('th').find("input[type=checkbox]").attr("id");
                            if(id === backup_id){
                                name = jQuery(this).parent().children('td').eq(0).find('img').attr('name');
                            }
                        }
                    });
                });
                var descript = '';
                var force_del = 0;
                if(name === 'lock') {
                    descript = '<?php esc_html_e('This backup is locked, are you sure to remove it? This backup will be deleted permanently from your hosting (localhost) and remote storages.', 'mainwp-wpvivid-extension'); ?>';
                    force_del = 1;
                }
                else{
                    descript = '<?php esc_html_e('Are you sure to remove this backup? This backup will be deleted permanently from your hosting (localhost) and remote storages.', 'mainwp-wpvivid-extension'); ?>';
                    force_del = 0;
                }
                var ret = confirm(descript);
                if(ret === true){
                    var ajax_data={
                        'action': 'mwp_wpvivid_delete_backup',
                        'site_id':'<?php echo esc_html($this->site_id); ?>',
                        'backup_id': backup_id,
                        'force': force_del
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data){
                        try {
                            var json = jQuery.parseJSON(data);
                            if (json.result === 'success') {
                                jQuery('#mwp_wpvivid_backuplist').html('');
                                jQuery('#mwp_wpvivid_backuplist').append(json.backup_list);
                            }
                            else {
                                alert(json.error);
                            }
                        }
                        catch(err) {
                            alert(err);
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            function mwp_wpvivid_click_check_backup(backup_id){
                var name = "";
                var all_check = true;
                jQuery('#mwp_wpvivid_backuplist tr').each(function (i) {
                    jQuery(this).children('th').each(function (j) {
                        if(j === 0) {
                            var id = jQuery(this).find("input[type=checkbox]").attr("id");
                            if (id === backup_id) {
                                name = jQuery(this).parent().children('td').eq(0).find("img").attr("name");
                                if (name === "unlock") {
                                    if (jQuery(this).find("input[type=checkbox]").prop('checked') === false) {
                                        all_check = false;
                                    }
                                }
                                else {
                                    jQuery(this).find("input[type=checkbox]").prop('checked', false);
                                    all_check = false;
                                }
                            }
                            else {
                                if (jQuery(this).find("input[type=checkbox]").prop('checked') === false) {
                                    all_check = false;
                                }
                            }
                        }
                    });
                });
                if(all_check === true){
                    jQuery('#mwp_wpvivid_backuplist_all_check').prop('checked', true);
                }
                else{
                    jQuery('#mwp_wpvivid_backuplist_all_check').prop('checked', false);
                }
            }

            function mwp_wpvivid_select_inbatches(){
                var name = '';
                if(jQuery('#mwp_wpvivid_backuplist_all_check').prop('checked')) {
                    jQuery('#mwp_wpvivid_backuplist tr').each(function (i) {
                        jQuery(this).children('th').each(function (j) {
                            if (j == 0) {
                                name = jQuery(this).parent().children('td').eq(0).find("img").attr("name");
                                if(name === 'unlock') {
                                    jQuery(this).find("input[type=checkbox]").prop('checked', true);
                                }
                                else{
                                    jQuery(this).find("input[type=checkbox]").prop('checked', false);
                                }
                            }
                        });
                    });
                }
                else{
                    jQuery('#mwp_wpvivid_backuplist tr').each(function (i) {
                        jQuery(this).children('th').each(function (j) {
                            if (j == 0) {
                                jQuery(this).find("input[type=checkbox]").prop('checked', false);
                            }
                        });
                    });
                }
            }

            function mwp_wpvivid_delete_backups_inbatches(){
                var delete_backup_array = new Array();
                var count = 0;
                jQuery('#mwp_wpvivid_backuplist tr').each(function (i) {
                    jQuery(this).children('th').each(function (j) {
                        if (j == 0) {
                            if(jQuery(this).find('input[type=checkbox]').prop('checked')){
                                delete_backup_array[count] = jQuery(this).find('input[type=checkbox]').attr('id');
                                count++;
                            }
                        }
                    });
                });
                if( count === 0 ){
                    alert('<?php esc_html_e('Please select at least one item.','mainwp-wpvivid-extension'); ?>');
                }
                else {
                    var descript = '<?php esc_html_e('Are you sure to remove the selected backups? These backups will be deleted permanently from your hosting (localhost).', 'mainwp-wpvivid-extension'); ?>';
                    var ret = confirm(descript);
                    if (ret === true) {
                        var ajax_data = {
                            'action': 'mwp_wpvivid_delete_backup_array',
                            'site_id':'<?php echo esc_html($this->site_id); ?>',
                            'backup_id': delete_backup_array
                        };
                        mwp_wpvivid_post_request(ajax_data, function (data) {
                            try {
                                var json = jQuery.parseJSON(data);
                                if (json.result === 'success') {
                                    jQuery('#mwp_wpvivid_backuplist_all_check').prop('checked', false);
                                    jQuery('#mwp_wpvivid_backuplist').html('');
                                    jQuery('#mwp_wpvivid_backuplist').append(json.backup_list);
                                }
                                else {
                                    alert(json.error);
                                }
                            }
                            catch(err) {
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            }

            function mwp_wpvivid_set_backup_lock(backup_id, lock_status){
                if(lock_status === "lock"){
                    var lock=0;
                }
                else{
                    var lock=1;
                }
                var ajax_data={
                    'action': 'mwp_wpvivid_set_security_lock',
                    'site_id':'<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id,
                    'lock': lock
                };
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success') {
                            jQuery('#mwp_wpvivid_backuplist').html('');
                            jQuery('#mwp_wpvivid_backuplist').append(json.backup_list);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_get_backup_list(){
                var ajax_data={
                    'action': 'mwp_wpvivid_get_backup_list',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success') {
                            jQuery('#mwp_wpvivid_backuplist').html('');
                            jQuery('#mwp_wpvivid_backuplist').append(json.backup_list);
                        }
                        else {
                            alert(json.error);
                        }
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        mwp_wpvivid_get_backup_list();
                    }, 3000);
                });
            }
            mwp_wpvivid_get_backup_list();
        </script>
        <?php
    }

    function mwp_wpvivid_page_log(){
        ?>
        <div class="mwp-wpvivid-backup-tab-content mwp_wpvivid_tab_backup_log" id="mwp-page-log" style="display:none; border-top: none;">
            <div class="postbox mwp-restore_log" id="wpvivid_display_log_content" style="border-top: none;">
                <div></div>
            </div>
        </div>
        <?php
    }

    function mwp_wpvivid_backup_js(){
        ?>
        <script>
            mwp_wpvivid_activate_cron();

            function mwp_wpvivid_cron_task(){
                var site_url = '<?php echo esc_url(home_url()); ?>';
                jQuery.get(site_url+'/wp-cron.php');
            }

            function mwp_wpvivid_resume_backup(backup_id, next_resume_time){
                if(next_resume_time < 0){
                    next_resume_time = 0;
                }
                next_resume_time = next_resume_time * 1000;
                setTimeout("mwp_wpvivid_cron_task()", next_resume_time);
                setTimeout(function(){
                    task_retry_times = 0;
                    mwp_wpvivid_need_update=true;
                }, next_resume_time);
            }

            function mwp_wpvivid_get_status()
            {
                var ajax_data={
                    'action': 'mwp_wpvivid_get_status',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    try {
                        var json = jQuery.parseJSON(data);
                        if (json.result === 'success')
                        {
                            if(json.data.length !== 0) {
                                jQuery.each(json.data, function (index, value) {
                                    if (value.status.str == 'ready'){
                                        jQuery('#mwp_wpvivid_backup_progress').show();
                                        jQuery('#mwp_wpvivid_backup_progress').html(json.html);
                                        mwp_wpvivid_need_update = true;
                                    }
                                    else if (value.status.str == 'running') {
                                        mwp_running_backup_taskid = index;
                                        jQuery('#mwp_wpvivid_backup_progress').show();
                                        jQuery('#mwp_wpvivid_backup_progress').html(json.html);
                                        mwp_wpvivid_need_update = true;
                                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                                    }
                                    else if (value.status.str == 'wait_resume'){
                                        mwp_running_backup_taskid = index;
                                        jQuery('#mwp_wpvivid_backup_progress').show();
                                        jQuery('#mwp_wpvivid_backup_progress').html(json.html);
                                        mwp_wpvivid_resume_backup(index, value.data.next_resume_time);
                                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                                    }
                                    else if (value.status.str === 'no_responds') {
                                        mwp_running_backup_taskid = index;
                                        jQuery('#mwp_wpvivid_backup_progress').show();
                                        jQuery('#mwp_wpvivid_backup_progress').html(value.progress_html);
                                        mwp_wpvivid_need_update = true;
                                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                                    }
                                    else if (value.status.str === 'completed') {
                                        mwp_running_backup_taskid = '';
                                        jQuery('#mwp_wpvivid_backup_progress').html(value.progress_html);
                                        jQuery('#mwp_wpvivid_backup_progress').hide();
                                        mwp_wpvivid_get_backup_list();
                                        mwp_wpvivid_get_backup_schedule();
                                        mwp_wpvivid_need_update = true;
                                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                                        mwp_wpvivid_add_notice('Backup', 'Success', '');
                                    }
                                    else if (value.status.str === 'error') {
                                        mwp_running_backup_taskid = '';
                                        jQuery('#mwp_wpvivid_backup_progress').html(value.progress_html);
                                        jQuery('#mwp_wpvivid_backup_progress').hide();
                                        mwp_wpvivid_get_backup_list();
                                        mwp_wpvivid_get_backup_schedule();
                                        mwp_wpvivid_need_update = true;
                                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                                        var error_info = "Backup error: " + value.status.error + ", task id: " + index;
                                        mwp_wpvivid_add_notice('Backup', 'Error', error_info);
                                    }
                                    else {
                                        jQuery('#mwp_wpvivid_backup_progress').hide();
                                        jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                                    }
                                });
                            }
                            else{
                                jQuery('#mwp_wpvivid_backup_progress').hide();
                                jQuery('#mwp_wpvivid_backup_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                        }
                        else
                        {
                            alert(json.error);
                        }
                    }
                    catch(err)
                    {
                        jQuery('#wpvivid_ajax_result').html(err);
                    }
                    setTimeout(function ()
                    {
                        mwp_wpvivid_manage_task();
                    }, 3000);
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_ajax_result').html(errorThrown);
                    setTimeout(function ()
                    {
                        mwp_wpvivid_get_status();
                    }, 3000);
                });

            }

            function mwp_wpvivid_manage_task() {
                if(mwp_wpvivid_need_update === true){
                    mwp_wpvivid_need_update = false;
                    mwp_wpvivid_get_status();
                }
                else{
                    setTimeout(function(){
                        mwp_wpvivid_manage_task();
                    }, 3000);
                }
            }
            mwp_wpvivid_manage_task();

            function mwp_wpvivid_get_donwnloadlink(site_id, location) {
                return 'admin.php?page=SiteOpen&websiteid=' + site_id + '&location=' + window.btoa(location) + '&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>';
            }

            function mwp_wpvivid_download(backup_id, file_name){
                var loc = 'admin-ajax.php?backup_id='+backup_id+'&file_name='+file_name+'&action=wpvivid_download_backup_mainwp';
                var url =  mwp_wpvivid_get_donwnloadlink(site_id, loc);
                var win=window.open(url, '_blank');
                setTimeout(function(){
                    win.close();
                }, 5000);
            }
        </script>
        <?php
    }
}