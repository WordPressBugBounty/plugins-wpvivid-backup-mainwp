<?php

class Mainwp_WPvivid_Extension_SettingPage
{
    private $setting;
    private $setting_addon;
    private $select_pro;
    private $site_id;
    public $main_tab;

    public function __construct()
    {
        $this->load_setting_ajax();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_setting_info($setting, $setting_addon=array(), $select_pro=0)
    {
        $this->setting=$setting;
        $this->setting_addon=$setting_addon;
        $this->select_pro=$select_pro;
    }

    public function load_setting_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_set_general_setting_addon', array($this, 'set_general_setting_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_global_general_setting_addon', array($this, 'set_global_general_setting_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_general_setting', array($this, 'set_general_setting'));
        add_action('wp_ajax_mwp_wpvivid_set_global_general_setting', array($this, 'set_global_general_setting'));
        add_action('wp_ajax_mwp_wpvivid_sync_setting', array($this, 'sync_setting'));
        add_action('wp_ajax_mwp_wpvivid_achieve_rollback_remote_addon', array($this, 'achieve_rollback_remote_addon'));
        add_action('wp_ajax_mwp_wpvivid_export_setting_addon', array($this, 'export_setting_addon'));
        add_action('wp_ajax_mwp_wpvivid_import_setting_addon', array($this, 'import_setting_addon'));
    }

    public function export_setting_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            $json['plugin']='WPvivid-Mainwp-Extension';
            $json['data']['settings_addon']=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('settings_addon', array());
            $json['data']['schedule_mould_addon']=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
            $json['data']['incremental_backup_setting']=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
            $json['data']['remote_addon']=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
            $json['data']['menu_capability']=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('menu_capability', array());
            $json['data']['white_label_setting']=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('white_label_setting', array());

            $parse = wp_parse_url(home_url());
            $path = '';
            if(isset($parse['path'])) {
                $parse['path'] = str_replace('/', '_', $parse['path']);
                $parse['path'] = str_replace('.', '_', $parse['path']);
                $path = $parse['path'];
            }
            $parse['host'] = str_replace('/', '_', $parse['host']);
            $parse['host'] = str_replace('.', '_', $parse['host']);
            $domain_tran = $parse['host'].$path;
            $offset=get_option('gmt_offset');
            $date_format = gmdate("Ymd",time()+$offset*60*60);
            $time_format = gmdate("His",time()+$offset*60*60);
            $export_file_name = 'wpvivid_mainwp_setting-'.$domain_tran.'-'.$date_format.'-'.$time_format.'.json';
            if (!headers_sent()) {
                header('Content-Disposition: attachment; filename='.$export_file_name);
                header('Content-Type: application/force-download');
                header('Content-Description: File Transfer');
                header('Cache-Control: must-revalidate');
                header('Content-Transfer-Encoding: binary');
            }

            echo wp_json_encode($json);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
        exit;
    }

    public function import_setting_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['data']) && !empty($_POST['data']) && is_string($_POST['data'])) {
                $data = sanitize_text_field($_POST['data']);
                $data = stripslashes($data);
                $json = json_decode($data, true);
                if (is_null($json)) {
                    die();
                }
                if (json_last_error() === JSON_ERROR_NONE && is_array($json) && array_key_exists('plugin', $json) && $json['plugin'] == 'WPvivid-Mainwp-Extension') {
                    foreach ($json['data'] as $option_name=>$option)
                    {
                        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option($option_name, $option);
                    }
                    $ret['result'] = 'success';
                    echo wp_json_encode($ret);
                } else {
                    $ret['result'] = 'failed';
                    $ret['error'] = __('The selected file is not the setting file for WPvivid. Please upload the right file.', 'wpvivid-backuprestore');
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

    public function achieve_rollback_remote_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_achieve_rollback_remote_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    if(isset($information['wpvivid_remote_list']) && !empty($information['wpvivid_remote_list']))
                    {
                        $remote_list = $information['wpvivid_remote_list'];
                    }
                    else
                    {
                        $remote_list = array();
                    }
                    $ret['remote_list_html'] = Mainwp_WPvivid_Extension_Subpage::output_rollback_remote_list_addon($site_id, $remote_list);
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

    public function set_general_setting_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['setting']) && !empty($_POST['setting']) && is_string($_POST['setting'])) {
                $setting = array();
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_set_general_setting_addon_mainwp';
                $json = stripslashes(sanitize_text_field($_POST['setting']));
                $setting = json_decode($json, true);
                $options=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'settings_addon', array());

                $setting_data['wpvivid_common_setting']['clean_old_remote_before_backup'] = $setting['mwp_clean_old_remote_before_backup_addon'];
                $setting_data['wpvivid_common_setting']['estimate_backup'] = $setting['mwp_estimate_backup_addon'];
                $setting_data['wpvivid_common_setting']['show_admin_bar'] = $setting['mwp_show_admin_bar_addon'];
                $setting_data['wpvivid_common_setting']['ismerge'] = $setting['mwp_ismerge_addon'];
                $setting_data['wpvivid_common_setting']['retain_local'] = $setting['mwp_retain_local_addon'];
                $setting_data['wpvivid_common_setting']['remove_out_of_date'] = $setting['mwp_remove_out_of_date_addon'];
                $setting_data['wpvivid_common_setting']['uninstall_clear_folder'] = $setting['mwp_uninstall_clear_folder_addon'];
                $setting_data['wpvivid_common_setting']['hide_admin_update_notice'] = $setting['mwp_hide_admin_update_notice_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_backup_count'] = $setting['mwp_manual_max_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_backup_db_count'] = $setting['mwp_manual_max_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_remote_backup_count'] = $setting['mwp_manual_max_remote_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_remote_backup_db_count'] = $setting['mwp_manual_max_remote_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_backup_count'] = $setting['mwp_schedule_max_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_backup_db_count'] = $setting['mwp_schedule_max_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_remote_backup_count'] = $setting['mwp_schedule_max_remote_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_remote_backup_db_count'] = $setting['mwp_schedule_max_remote_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['incremental_max_db_count'] = $setting['mwp_incremental_max_db_count_addon'];
                $setting_data['wpvivid_common_setting']['incremental_max_backup_count'] = $setting['mwp_incremental_max_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['incremental_max_remote_backup_count'] = $setting['mwp_incremental_max_remote_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['default_backup_local'] = $setting['mwp_default_backup_local_addon'];
                $setting_data['wpvivid_local_setting']['path'] = $setting['mwp_path_addon'];
                $setting_data['wpvivid_local_setting']['save_local'] = isset($options['wpvivid_local_setting']['save_local']) ? $options['wpvivid_local_setting']['save_local'] : 1;
                $setting_data['wpvivid_common_setting']['backup_prefix'] = $setting['mwp_backup_prefix_addon'];
                $setting_data['wpvivid_common_setting']['encrypt_db'] = $setting['mwp_encrypt_db_addon'];
                $setting_data['wpvivid_common_setting']['encrypt_db_password'] = $setting['mwp_encrypt_db_password_addon'];

                //
                $setting_data['wpvivid_common_setting']['use_adaptive_settings'] = $setting['mwp_use_adaptive_settings_addon'];
                $setting_data['wpvivid_common_setting']['auto_delete_backup_log'] = $setting['mwp_auto_delete_backup_log'];
                $setting_data['wpvivid_common_setting']['backup_database_use_primary_key'] = $setting['mwp_backup_database_use_primary_key'];
                $setting_data['wpvivid_common_setting']['backup_upload_use_cm_store'] = $setting['mwp_backup_upload_use_cm_store'];
                $setting_data['wpvivid_common_setting']['db_connect_method'] = $setting['mwp_db_connect_method_addon'];
                $setting_data['wpvivid_common_setting']['zip_method'] = $setting['mwp_zip_method_addon'];
                $setting_data['wpvivid_common_setting']['backup_params'] = $setting['mwp_backup_params_addon'];
                if($setting_data['wpvivid_common_setting']['backup_params'] === 'low')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = '500';
                    $setting_data['wpvivid_common_setting']['max_file_size'] = '200';
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = '1000';
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '400';
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = 300;
                    $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
                }
                else if($setting_data['wpvivid_common_setting']['backup_params'] === 'mid')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = '2000';
                    $setting_data['wpvivid_common_setting']['max_file_size'] = '1024';
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = '3000';
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '1024';
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = 500;
                    $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
                }
                else if($setting_data['wpvivid_common_setting']['backup_params'] === 'high')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = '10000';
                    $setting_data['wpvivid_common_setting']['max_file_size'] = '4080';
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = '6000';
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '4080';
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = 900;
                    $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
                }
                else if($setting_data['wpvivid_common_setting']['backup_params'] === 'custom')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = $setting['mwp_compress_file_count_addon'];
                    $setting_data['wpvivid_common_setting']['max_file_size'] = $setting['mwp_max_file_size_addon'];
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = $setting['mwp_max_backup_table_addon'];
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = $setting['mwp_max_sql_file_size_addon'];
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = $setting['mwp_exclude_file_size_addon'];
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = $setting['mwp_max_execution_time_addon'];
                    $setting_data['wpvivid_common_setting']['memory_limit'] = $setting['mwp_memory_limit_addon'].'M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = $setting['mwp_migrate_size_addon'];
                }

                //
                if(isset($setting['mwp_wpvivid_uc_quick_scan_addon']))
                    $setting_data['wpvivid_uc_quick_scan'] = boolval($setting['mwp_wpvivid_uc_quick_scan_addon']);
                if(isset($setting['mwp_wpvivid_uc_delete_media_when_delete_file_addon']))
                    $setting_data['wpvivid_uc_delete_media_when_delete_file'] = boolval($setting['mwp_wpvivid_uc_delete_media_when_delete_file_addon']);
                if(isset($setting['mwp_wpvivid_uc_ignore_webp_addon']))
                    $setting_data['wpvivid_uc_ignore_webp'] = boolval($setting['mwp_wpvivid_uc_ignore_webp_addon']);
                if(isset($setting['mwp_wpvivid_uc_scan_limit_addon']))
                    $setting_data['wpvivid_uc_scan_limit'] = intval($setting['mwp_wpvivid_uc_scan_limit_addon']);
                if(isset($setting['mwp_wpvivid_uc_files_limit_addon']))
                    $setting_data['wpvivid_uc_files_limit'] = intval($setting['mwp_wpvivid_uc_files_limit_addon']);

                //
                if(isset($setting['mwp_region_addon']))
                    $setting_data['wpvivid_optimization_options']['region']=$setting['mwp_region_addon'];
                if(isset($setting['mwp_auto_optimize_type_addon']))
                    $setting_data['wpvivid_optimization_options']['auto_optimize_type']=$setting['mwp_auto_optimize_type_addon'];
                if(isset($setting['mwp_auto_schedule_cycles_addon']))
                    $setting_data['wpvivid_optimization_options']['auto_schedule_cycles']=$setting['mwp_auto_schedule_cycles_addon'];
                if(isset($setting['mwp_optimize_type_addon']))
                    $setting_data['wpvivid_optimization_options']['optimize_type']=$setting['mwp_optimize_type_addon'];
                if(isset($setting['mwp_custom_folders_addon']))
                    $setting_data['wpvivid_optimization_options']['custom_folders']=$setting['mwp_custom_folders_addon'];
                if(isset($setting['mwp_quality_addon']))
                    $setting_data['wpvivid_optimization_options']['quality']=$setting['mwp_quality_addon'];
                if(isset($setting['mwp_custom_quality_addon']))
                    $setting_data['wpvivid_optimization_options']['custom_quality']=$setting['mwp_custom_quality_addon'];
                if(isset($setting['mwp_opt_gif_addon']))
                    $setting_data['wpvivid_optimization_options']['opt_gif']=$setting['mwp_opt_gif_addon'];
                if(isset($setting['mwp_keep_exif_addon']))
                    $setting_data['wpvivid_optimization_options']['keep_exif']=$setting['mwp_keep_exif_addon'];
                if(isset($setting['mwp_optimize_gif_color_addon']))
                    $setting_data['wpvivid_optimization_options']['optimize_gif_color']=$setting['mwp_optimize_gif_color_addon'];
                if(isset($setting['mwp_gif_colors_addon']))
                    $setting_data['wpvivid_optimization_options']['gif_colors']=$setting['mwp_gif_colors_addon'];
                if(isset($setting['mwp_resize_addon']))
                    $setting_data['wpvivid_optimization_options']['resize']['enable']=$setting['mwp_resize_addon'];
                if(isset($setting['mwp_resize_width_addon']))
                    $setting_data['wpvivid_optimization_options']['resize']['width']=$setting['mwp_resize_width_addon'];
                if(isset($setting['mwp_resize_height_addon']))
                    $setting_data['wpvivid_optimization_options']['resize']['height']=$setting['mwp_resize_height_addon'];
                if(isset($setting['mwp_convert_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['convert']=intval($setting['mwp_convert_addon']);
                if(isset($setting['mwp_gif_convert_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['gif_convert']=intval($setting['mwp_gif_convert_addon']);
                if(isset($setting['mwp_display_enable_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['display_enable']=$setting['mwp_display_enable_addon'];
                if(isset($setting['mwp_webp_display_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['display']=$setting['mwp_webp_display_addon'];
                if(isset($setting['mwp_enable_exclude_path_addon']))
                    $setting_data['wpvivid_optimization_options']['enable_exclude_path']=$setting['mwp_enable_exclude_path_addon'];
                if(isset($setting['mwp_exclude_path_addon']))
                    $setting_data['wpvivid_optimization_options']['exclude_path']=$setting['mwp_exclude_path_addon'];
                if(isset($setting['mwp_enable_exclude_file_addon']))
                    $setting_data['wpvivid_optimization_options']['enable_exclude_file']=$setting['mwp_enable_exclude_file_addon'];
                if(isset($setting['mwp_exclude_file_addon']))
                    $setting_data['wpvivid_optimization_options']['exclude_file']=$setting['mwp_exclude_file_addon'];
                if(isset($setting['mwp_image_backup_addon']))
                    $setting_data['wpvivid_optimization_options']['backup']=$setting['mwp_image_backup_addon'];
                if(isset($setting['mwp_image_backup_path_addon']))
                    $setting_data['wpvivid_optimization_options']['backup_path']=$setting['mwp_image_backup_path_addon'];
                if(isset($setting['mwp_image_optimization_memory_limit_addon']))
                    $setting_data['wpvivid_optimization_options']['image_optimization_memory_limit']=max(256,intval($setting['mwp_image_optimization_memory_limit_addon']));
                if(isset($setting['mwp_max_allowed_optimize_count_addon']))
                    $setting_data['wpvivid_optimization_options']['max_allowed_optimize_count']=max(1,intval($setting['mwp_max_allowed_optimize_count_addon']));

                //
                $setting_data['wpvivid_staging_options']['staging_db_insert_count'] = intval($setting['mwp_staging_db_insert_count_addon']);
                $setting_data['wpvivid_staging_options']['staging_db_replace_count'] = intval($setting['mwp_staging_db_replace_count_addon']);
                $setting_data['wpvivid_staging_options']['staging_file_copy_count'] = intval($setting['mwp_staging_file_copy_count_addon']);
                $setting_data['wpvivid_staging_options']['staging_exclude_file_size'] = intval($setting['mwp_staging_exclude_file_size_addon']);
                $setting_data['wpvivid_staging_options']['staging_memory_limit'] = $setting['mwp_staging_memory_limit_addon'].'M';
                $setting_data['wpvivid_staging_options']['staging_max_execution_time'] = intval($setting['mwp_staging_max_execution_time_addon']);
                $setting_data['wpvivid_staging_options']['staging_request_timeout']= intval($setting['mwp_staging_request_timeout_addon']);
                $setting_data['wpvivid_staging_options']['staging_resume_count'] = intval($setting['mwp_staging_resume_count_addon']);
                $setting_data['wpvivid_staging_options']['not_need_login']= intval($setting['mwp_not_need_login_addon']);
                $setting_data['wpvivid_staging_options']['staging_overwrite_permalink'] = intval($setting['mwp_staging_overwrite_permalink_addon']);
                $setting_data['wpvivid_staging_options']['staging_keep_setting']= intval($setting['mwp_staging_keep_setting_addon']);


                if(isset($_POST['lazyload'])) {
                    $lazyload_json = stripslashes(sanitize_text_field($_POST['lazyload']));
                    $lazyload_setting = json_decode($lazyload_json, true);
                    $setting_data['wpvivid_optimization_options']['lazyload']['enable']=$lazyload_setting['mwp_enable_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['jpg|jpeg|jpe']=$lazyload_setting['mwp_jpg_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['png']=$lazyload_setting['mwp_png_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['gif']=$lazyload_setting['mwp_gif_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['svg']=$lazyload_setting['mwp_svg_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['content']=$lazyload_setting['mwp_content_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['thumbnails']=$lazyload_setting['mwp_thumbnails_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['noscript']=$lazyload_setting['mwp_noscript_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['js']=$lazyload_setting['mwp_js_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['animation']=$lazyload_setting['mwp_lazyload_display_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['enable_exclude_file']=$lazyload_setting['mwp_enable_exclude_file_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['exclude_file']=$lazyload_setting['mwp_exclude_file_addon'];
                }

                if(isset($_POST['cdn'])) {
                    $cdn_json = stripslashes(sanitize_text_field($_POST['cdn']));
                    $cdn_setting = json_decode($cdn_json, true);
                    $setting_data['wpvivid_optimization_options']['cdn']['enable']=$cdn_setting['mwp_enable_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['cdn_url']=$cdn_setting['mwp_cdn_url_addon'];
                    if($cdn_setting['mwp_enable_addon']&&empty($cdn_setting['mwp_cdn_url_addon']))
                    {
                        $ret['result']='failed';
                        $ret['error']='CDN URL cannot be empty.';
                        echo wp_json_encode($ret);
                        die();
                    }
                    $setting_data['wpvivid_optimization_options']['cdn']['relative_path']=$cdn_setting['mwp_relative_path_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['cdn_https']=$cdn_setting['mwp_cdn_https_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['include_dir']=$cdn_setting['mwp_include_dir_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['exclusions']=$cdn_setting['mwp_exclusions_addon'];
                }

                if(isset($_POST['rollback'])) {
                    $rollback_json = stripslashes(sanitize_text_field($_POST['rollback']));
                    $rollback_setting = json_decode($rollback_json, true);
                    $setting_data['wpvivid_max_rollback_count']['max_plugins_count']=$rollback_setting['mwp_wpvivid_max_plugins_count_addon'];
                    $setting_data['wpvivid_max_rollback_count']['max_themes_count']=$rollback_setting['mwp_wpvivid_max_themes_count_addon'];
                    $setting_data['wpvivid_max_rollback_count']['max_core_count']=$rollback_setting['mwp_wpvivid_max_core_count_addon'];
                    $setting_data['wpvivid_auto_backup_db_before_update']=$rollback_setting['mwp_wpvivid_auto_backup_db_before_update_addon'];
                    $setting_data['wpvivid_common_setting']['rollback_max_backup_count'] = $rollback_setting['mwp_wpvivid_rollback_max_backup_count_addon'];


                    $setting_data['wpvivid_common_setting']['rollback_max_remote_backup_count'] = $rollback_setting['mwp_wpvivid_rollback_max_remote_backup_count_addon'];
                    $setting_data['wpvivid_rollback_retain_local'] = $rollback_setting['mwp_wpvivid_rollback_retain_local_addon'];
                    if($rollback_setting['mwp_wpvivid_manual_backup_remote_selector'] == '-1')
                    {
                        $setting_data['wpvivid_rollback_remote'] = 0;
                    }
                    else
                    {
                        $setting_data['wpvivid_rollback_remote'] = 1;
                        $setting_data['wpvivid_rollback_remote_id'] = $rollback_setting['mwp_wpvivid_manual_backup_remote_selector'];
                    }

                }

                if(empty($options)){
                    $options = array();
                }
                foreach ($setting_data as $option_name => $option) {
                    $options[$option_name] = $setting_data[$option_name];
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'settings_addon', $options);

                $post_data['setting'] = wp_json_encode($options);

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

    public function set_global_general_setting_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            $setting = array();
            $schedule = array();
            if (isset($_POST['setting']) && !empty($_POST['setting']) && is_string($_POST['setting'])) {
                $json = stripslashes(sanitize_text_field($_POST['setting']));
                $setting = json_decode($json, true);
                $options = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('settings_addon', array());

                //
                $setting_data['wpvivid_common_setting']['clean_old_remote_before_backup'] = $setting['mwp_clean_old_remote_before_backup_addon'];
                $setting_data['wpvivid_common_setting']['estimate_backup'] = $setting['mwp_estimate_backup_addon'];
                $setting_data['wpvivid_common_setting']['show_admin_bar'] = $setting['mwp_show_admin_bar_addon'];
                $setting_data['wpvivid_common_setting']['ismerge'] = $setting['mwp_ismerge_addon'];
                $setting_data['wpvivid_common_setting']['retain_local'] = $setting['mwp_retain_local_addon'];
                $setting_data['wpvivid_common_setting']['remove_out_of_date'] = $setting['mwp_remove_out_of_date_addon'];
                $setting_data['wpvivid_common_setting']['uninstall_clear_folder'] = $setting['mwp_uninstall_clear_folder_addon'];
                $setting_data['wpvivid_common_setting']['hide_admin_update_notice'] = $setting['mwp_hide_admin_update_notice_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_backup_count'] = $setting['mwp_manual_max_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_backup_db_count'] = $setting['mwp_manual_max_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_remote_backup_count'] = $setting['mwp_manual_max_remote_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['manual_max_remote_backup_db_count'] = $setting['mwp_manual_max_remote_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_backup_count'] = $setting['mwp_schedule_max_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_backup_db_count'] = $setting['mwp_schedule_max_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_remote_backup_count'] = $setting['mwp_schedule_max_remote_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['schedule_max_remote_backup_db_count'] = $setting['mwp_schedule_max_remote_backup_db_count_addon'];
                $setting_data['wpvivid_common_setting']['incremental_max_db_count'] = $setting['mwp_incremental_max_db_count_addon'];
                $setting_data['wpvivid_common_setting']['incremental_max_backup_count'] = $setting['mwp_incremental_max_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['incremental_max_remote_backup_count'] = $setting['mwp_incremental_max_remote_backup_count_addon'];
                $setting_data['wpvivid_common_setting']['default_backup_local'] = $setting['mwp_default_backup_local_addon'];
                $setting_data['wpvivid_local_setting']['path'] = $setting['mwp_path_addon'];
                $setting_data['wpvivid_local_setting']['save_local'] = isset($options['wpvivid_local_setting']['save_local']) ? $options['wpvivid_local_setting']['save_local'] : 1;
                $setting_data['wpvivid_common_setting']['backup_prefix'] = $setting['mwp_backup_prefix_addon'];
                $setting_data['wpvivid_common_setting']['encrypt_db'] = $setting['mwp_encrypt_db_addon'];
                $setting_data['wpvivid_common_setting']['encrypt_db_password'] = $setting['mwp_encrypt_db_password_addon'];

                $setting_data['wpvivid_email_setting_addon']['send_to'] = $setting['mwp_send_to'];
                $setting_data['wpvivid_email_setting_addon']['always'] = $setting['mwp_always_addon'];
                $email_enable = '0';
                foreach($setting['mwp_send_to'] as $email => $value){
                    if($value['email_enable'] == '1'){
                        $email_enable = '1';
                    }
                }
                $setting_data['wpvivid_email_setting_addon']['email_enable'] = $email_enable;
                $setting_data['wpvivid_email_setting_addon']['use_mail_title'] = $setting['mwp_use_mail_title_addon'];
                $setting_data['wpvivid_email_setting_addon']['mail_title'] = $setting['mwp_mail_title_addon'];
                $setting_data['wpvivid_email_setting_addon']['email_attach_log'] = $setting['mwp_email_attach_log_addon'];

                //
                $setting_data['wpvivid_common_setting']['use_adaptive_settings'] = $setting['mwp_use_adaptive_settings_addon'];
                $setting_data['wpvivid_common_setting']['auto_delete_backup_log'] = $setting['mwp_auto_delete_backup_log'];
                $setting_data['wpvivid_common_setting']['backup_database_use_primary_key'] = $setting['mwp_backup_database_use_primary_key'];
                $setting_data['wpvivid_common_setting']['backup_upload_use_cm_store'] = $setting['mwp_backup_upload_use_cm_store'];
                $setting_data['wpvivid_common_setting']['db_connect_method'] = $setting['mwp_db_connect_method_addon'];
                $setting_data['wpvivid_common_setting']['zip_method'] = $setting['mwp_zip_method_addon'];
                $setting_data['wpvivid_common_setting']['backup_params'] = $setting['mwp_backup_params_addon'];
                if($setting_data['wpvivid_common_setting']['backup_params'] === 'low')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = '500';
                    $setting_data['wpvivid_common_setting']['max_file_size'] = '200';
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = '1000';
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '400';
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = 300;
                    $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
                }
                else if($setting_data['wpvivid_common_setting']['backup_params'] === 'mid')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = '2000';
                    $setting_data['wpvivid_common_setting']['max_file_size'] = '1024';
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = '3000';
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '1024';
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = 500;
                    $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
                }
                else if($setting_data['wpvivid_common_setting']['backup_params'] === 'high')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = '10000';
                    $setting_data['wpvivid_common_setting']['max_file_size'] = '4080';
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = '6000';
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = '4080';
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = 0;
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = 900;
                    $setting_data['wpvivid_common_setting']['memory_limit'] = '512M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = '2048';
                }
                else if($setting_data['wpvivid_common_setting']['backup_params'] === 'custom')
                {
                    $setting_data['wpvivid_common_setting']['compress_file_count'] = $setting['mwp_compress_file_count_addon'];
                    $setting_data['wpvivid_common_setting']['max_file_size'] = $setting['mwp_max_file_size_addon'];
                    $setting_data['wpvivid_common_setting']['max_backup_table'] = $setting['mwp_max_backup_table_addon'];
                    $setting_data['wpvivid_common_setting']['max_sql_file_size'] = $setting['mwp_max_sql_file_size_addon'];
                    $setting_data['wpvivid_common_setting']['exclude_file_size'] = $setting['mwp_exclude_file_size_addon'];
                    $setting_data['wpvivid_common_setting']['max_execution_time'] = $setting['mwp_max_execution_time_addon'];
                    $setting_data['wpvivid_common_setting']['memory_limit'] = $setting['mwp_memory_limit_addon'].'M';
                    $setting_data['wpvivid_common_setting']['migrate_size'] = $setting['mwp_migrate_size_addon'];
                }

                //
                if(isset($setting['mwp_wpvivid_uc_quick_scan_addon']))
                    $setting_data['wpvivid_uc_quick_scan'] = boolval($setting['mwp_wpvivid_uc_quick_scan_addon']);
                if(isset($setting['mwp_wpvivid_uc_delete_media_when_delete_file_addon']))
                    $setting_data['wpvivid_uc_delete_media_when_delete_file'] = boolval($setting['mwp_wpvivid_uc_delete_media_when_delete_file_addon']);
                if(isset($setting['mwp_wpvivid_uc_ignore_webp_addon']))
                    $setting_data['wpvivid_uc_ignore_webp'] = boolval($setting['mwp_wpvivid_uc_ignore_webp_addon']);
                if(isset($setting['mwp_wpvivid_uc_scan_limit_addon']))
                    $setting_data['wpvivid_uc_scan_limit'] = intval($setting['mwp_wpvivid_uc_scan_limit_addon']);
                if(isset($setting['mwp_wpvivid_uc_files_limit_addon']))
                    $setting_data['wpvivid_uc_files_limit'] = intval($setting['mwp_wpvivid_uc_files_limit_addon']);

                //
                if(isset($setting['mwp_region_addon']))
                    $setting_data['wpvivid_optimization_options']['region']=$setting['mwp_region_addon'];
                if(isset($setting['mwp_auto_optimize_type_addon']))
                    $setting_data['wpvivid_optimization_options']['auto_optimize_type']=$setting['mwp_auto_optimize_type_addon'];
                if(isset($setting['mwp_auto_schedule_cycles_addon']))
                    $setting_data['wpvivid_optimization_options']['auto_schedule_cycles']=$setting['mwp_auto_schedule_cycles_addon'];
                if(isset($setting['mwp_optimize_type_addon']))
                    $setting_data['wpvivid_optimization_options']['optimize_type']=$setting['mwp_optimize_type_addon'];
                if(isset($setting['mwp_custom_folders_addon']))
                    $setting_data['wpvivid_optimization_options']['custom_folders']=$setting['mwp_custom_folders_addon'];
                if(isset($setting['mwp_quality_addon']))
                    $setting_data['wpvivid_optimization_options']['quality']=$setting['mwp_quality_addon'];
                if(isset($setting['mwp_custom_quality_addon']))
                    $setting_data['wpvivid_optimization_options']['custom_quality']=$setting['mwp_custom_quality_addon'];
                if(isset($setting['mwp_opt_gif_addon']))
                    $setting_data['wpvivid_optimization_options']['opt_gif']=$setting['mwp_opt_gif_addon'];
                if(isset($setting['mwp_keep_exif_addon']))
                    $setting_data['wpvivid_optimization_options']['keep_exif']=$setting['mwp_keep_exif_addon'];
                if(isset($setting['mwp_optimize_gif_color_addon']))
                    $setting_data['wpvivid_optimization_options']['optimize_gif_color']=$setting['mwp_optimize_gif_color_addon'];
                if(isset($setting['mwp_gif_colors_addon']))
                    $setting_data['wpvivid_optimization_options']['gif_colors']=$setting['mwp_gif_colors_addon'];
                if(isset($setting['mwp_resize_addon']))
                    $setting_data['wpvivid_optimization_options']['resize']['enable']=$setting['mwp_resize_addon'];
                if(isset($setting['mwp_resize_width_addon']))
                    $setting_data['wpvivid_optimization_options']['resize']['width']=$setting['mwp_resize_width_addon'];
                if(isset($setting['mwp_resize_height_addon']))
                    $setting_data['wpvivid_optimization_options']['resize']['height']=$setting['mwp_resize_height_addon'];
                if(isset($setting['mwp_convert_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['convert']=intval($setting['mwp_convert_addon']);
                if(isset($setting['mwp_gif_convert_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['gif_convert']=intval($setting['mwp_gif_convert_addon']);
                if(isset($setting['mwp_display_enable_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['display_enable']=$setting['mwp_display_enable_addon'];
                if(isset($setting['mwp_webp_display_addon']))
                    $setting_data['wpvivid_optimization_options']['webp']['display']=$setting['mwp_webp_display_addon'];
                if(isset($setting['mwp_enable_exclude_path_addon']))
                    $setting_data['wpvivid_optimization_options']['enable_exclude_path']=$setting['mwp_enable_exclude_path_addon'];
                if(isset($setting['mwp_exclude_path_addon']))
                    $setting_data['wpvivid_optimization_options']['exclude_path']=$setting['mwp_exclude_path_addon'];
                if(isset($setting['mwp_enable_exclude_file_addon']))
                    $setting_data['wpvivid_optimization_options']['enable_exclude_file']=$setting['mwp_enable_exclude_file_addon'];
                if(isset($setting['mwp_exclude_file_addon']))
                    $setting_data['wpvivid_optimization_options']['exclude_file']=$setting['mwp_exclude_file_addon'];
                if(isset($setting['mwp_image_backup_addon']))
                    $setting_data['wpvivid_optimization_options']['backup']=$setting['mwp_image_backup_addon'];
                if(isset($setting['mwp_image_backup_path_addon']))
                    $setting_data['wpvivid_optimization_options']['backup_path']=$setting['mwp_image_backup_path_addon'];
                if(isset($setting['mwp_image_optimization_memory_limit_addon']))
                    $setting_data['wpvivid_optimization_options']['image_optimization_memory_limit']=max(256,intval($setting['mwp_image_optimization_memory_limit_addon']));
                if(isset($setting['mwp_max_allowed_optimize_count_addon']))
                    $setting_data['wpvivid_optimization_options']['max_allowed_optimize_count']=max(1,intval($setting['mwp_max_allowed_optimize_count_addon']));

                //
                $setting_data['wpvivid_staging_options']['staging_db_insert_count'] = intval($setting['mwp_staging_db_insert_count_addon']);
                $setting_data['wpvivid_staging_options']['staging_db_replace_count'] = intval($setting['mwp_staging_db_replace_count_addon']);
                $setting_data['wpvivid_staging_options']['staging_file_copy_count'] = intval($setting['mwp_staging_file_copy_count_addon']);
                $setting_data['wpvivid_staging_options']['staging_exclude_file_size'] = intval($setting['mwp_staging_exclude_file_size_addon']);
                $setting_data['wpvivid_staging_options']['staging_memory_limit'] = $setting['mwp_staging_memory_limit_addon'].'M';
                $setting_data['wpvivid_staging_options']['staging_max_execution_time'] = intval($setting['mwp_staging_max_execution_time_addon']);
                $setting_data['wpvivid_staging_options']['staging_request_timeout']= intval($setting['mwp_staging_request_timeout_addon']);
                $setting_data['wpvivid_staging_options']['staging_resume_count'] = intval($setting['mwp_staging_resume_count_addon']);
                $setting_data['wpvivid_staging_options']['not_need_login']= intval($setting['mwp_not_need_login_addon']);
                $setting_data['wpvivid_staging_options']['staging_overwrite_permalink'] = intval($setting['mwp_staging_overwrite_permalink_addon']);
                $setting_data['wpvivid_staging_options']['staging_keep_setting']= intval($setting['mwp_staging_keep_setting_addon']);


                if(isset($_POST['lazyload'])) {
                    $lazyload_json = stripslashes(sanitize_text_field($_POST['lazyload']));
                    $lazyload_setting = json_decode($lazyload_json, true);
                    $setting_data['wpvivid_optimization_options']['lazyload']['enable']=$lazyload_setting['mwp_enable_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['jpg|jpeg|jpe']=$lazyload_setting['mwp_jpg_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['png']=$lazyload_setting['mwp_png_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['gif']=$lazyload_setting['mwp_gif_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['extensions']['svg']=$lazyload_setting['mwp_svg_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['content']=$lazyload_setting['mwp_content_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['thumbnails']=$lazyload_setting['mwp_thumbnails_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['noscript']=$lazyload_setting['mwp_noscript_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['js']=$lazyload_setting['mwp_js_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['animation']=$lazyload_setting['mwp_lazyload_display_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['enable_exclude_file']=$lazyload_setting['mwp_enable_exclude_file_addon'];
                    $setting_data['wpvivid_optimization_options']['lazyload']['exclude_file']=$lazyload_setting['mwp_exclude_file_addon'];
                }

                if(isset($_POST['cdn'])) {
                    $cdn_json = stripslashes(sanitize_text_field($_POST['cdn']));
                    $cdn_setting = json_decode($cdn_json, true);
                    $setting_data['wpvivid_optimization_options']['cdn']['enable']=$cdn_setting['mwp_enable_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['cdn_url']=$cdn_setting['mwp_cdn_url_addon'];
                    if($cdn_setting['mwp_enable_addon']&&empty($cdn_setting['mwp_cdn_url_addon']))
                    {
                        $ret['result']='failed';
                        $ret['error']='CDN URL cannot be empty.';
                        echo wp_json_encode($ret);
                        die();
                    }
                    $setting_data['wpvivid_optimization_options']['cdn']['relative_path']=$cdn_setting['mwp_relative_path_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['cdn_https']=$cdn_setting['mwp_cdn_https_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['include_dir']=$cdn_setting['mwp_include_dir_addon'];
                    $setting_data['wpvivid_optimization_options']['cdn']['exclusions']=$cdn_setting['mwp_exclusions_addon'];
                }

                if(isset($_POST['rollback'])) {
                    $rollback_json = stripslashes(sanitize_text_field($_POST['rollback']));
                    $rollback_setting = json_decode($rollback_json, true);
                    $setting_data['wpvivid_max_rollback_count']['max_plugins_count']=$rollback_setting['mwp_wpvivid_max_plugins_count_addon'];
                    $setting_data['wpvivid_max_rollback_count']['max_themes_count']=$rollback_setting['mwp_wpvivid_max_themes_count_addon'];
                    $setting_data['wpvivid_max_rollback_count']['max_core_count']=$rollback_setting['mwp_wpvivid_max_core_count_addon'];
                    $setting_data['wpvivid_auto_backup_db_before_update']=$rollback_setting['mwp_wpvivid_auto_backup_db_before_update_addon'];
                    $setting_data['wpvivid_common_setting']['rollback_max_backup_count'] = $rollback_setting['mwp_wpvivid_rollback_max_backup_count_addon'];
                    $setting_data['wpvivid_common_setting']['rollback_max_remote_backup_count'] = $rollback_setting['mwp_wpvivid_rollback_max_remote_backup_count_addon'];
                    $setting_data['wpvivid_rollback_retain_local'] = $rollback_setting['mwp_wpvivid_rollback_retain_local_addon'];
                    if($rollback_setting['mwp_wpvivid_manual_backup_remote_selector'] == '-1')
                    {
                        $setting_data['wpvivid_rollback_remote'] = 0;
                    }
                    else
                    {
                        $setting_data['wpvivid_rollback_remote'] = 1;
                    }
                }

                if(empty($options)){
                    $options = array();
                }
                foreach ($setting_data as $option_name => $option) {
                    $options[$option_name] = $setting_data[$option_name];
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('settings_addon', $options);

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

    public function set_general_setting()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['setting']) && !empty($_POST['setting']) && is_string($_POST['setting'])) {
                $setting = array();
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_set_general_setting_mainwp';
                $json = stripslashes(sanitize_text_field($_POST['setting']));
                $setting = json_decode($json, true);

                $options=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'settings', array());

                //
                $setting_data['wpvivid_common_setting']['max_backup_count'] = $setting['mwp_max_backup_count'];
                $setting_data['wpvivid_common_setting']['estimate_backup'] = $setting['mwp_estimate_backup'];
                $setting_data['wpvivid_common_setting']['ismerge'] = $setting['mwp_ismerge'];
                $setting_data['wpvivid_common_setting']['retain_local'] = $setting['mwp_retain_local'];
                $setting_data['wpvivid_local_setting']['path'] = $setting['mwp_path'];
                $setting_data['wpvivid_common_setting']['domain_include'] = $setting['mwp_domain_include'];
                //
                $setting_data['wpvivid_common_setting']['db_connect_method'] = $setting['mwp_db_connect_method'];
                $setting_data['wpvivid_common_setting']['zip_method'] = $setting['mwp_zip_method'];
                $setting_data['wpvivid_common_setting']['max_file_size'] = $setting['mwp_max_file_size'].'M';
                $setting_data['wpvivid_common_setting']['exclude_file_size'] = intval($setting['mwp_exclude_file_size']);
                $setting_data['wpvivid_common_setting']['max_execution_time'] = intval($setting['mwp_max_execution_time']);
                $setting_data['wpvivid_common_setting']['memory_limit'] = $setting['mwp_memory_limit'].'M';
                $setting_data['wpvivid_common_setting']['compress_file_count'] = intval($setting['mwp_compress_file_count']);
                $setting_data['wpvivid_common_setting']['max_sql_file_size'] = intval($setting['mwp_max_sql_file_size']);
                $setting_data['wpvivid_common_setting']['migrate_size'] = $setting['mwp_migrate_size'];
                $setting_data['wpvivid_common_setting']['restore_max_execution_time'] = intval($setting['mwp_restore_max_execution_time']);
                $setting_data['wpvivid_common_setting']['restore_memory_limit'] = $setting['mwp_restore_memory_limit'].'M';
                $setting_data['wpvivid_common_setting']['replace_rows_pre_request'] = intval($setting['mwp_replace_rows_pre_request']);
                $setting_data['wpvivid_common_setting']['sql_file_buffer_pre_request'] = intval($setting['mwp_sql_file_buffer_pre_request']);
                $setting_data['wpvivid_common_setting']['max_resume_count'] = intval($setting['mwp_max_resume_count']);
                $setting_data['wpvivid_common_setting']['use_index'] = intval($setting['mwp_use_index']);
                $setting_data['wpvivid_common_setting']['unzip_files_pre_request'] =intval($setting['mwp_unzip_files_pre_request']);
                //
                $setting_data['wpvivid_local_setting']['save_local'] = isset($options['wpvivid_local_setting']['save_local']) ? $options['wpvivid_local_setting']['save_local'] : 1;
                $setting_data['wpvivid_common_setting']['log_save_location'] = $setting['mwp_path'] . '/wpvivid_log';
                $setting_data['wpvivid_common_setting']['show_admin_bar'] = isset($options['wpvivid_common_setting']['show_admin_bar']) ? $options['wpvivid_common_setting']['show_admin_bar'] : 1;
                //

                foreach ($setting_data as $option_name => $option) {
                    $options[$option_name] = $setting_data[$option_name];
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'settings', $options);

                $post_data['setting'] = wp_json_encode($options);

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

    public function set_global_general_setting()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            $setting = array();
            $schedule = array();
            if (isset($_POST['setting']) && !empty($_POST['setting']) && is_string($_POST['setting'])) {
                $json = stripslashes(sanitize_text_field($_POST['setting']));
                $setting = json_decode($json, true);
                $options = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('settings', array());

                //
                $setting_data['wpvivid_common_setting']['max_backup_count'] = $setting['mwp_max_backup_count'];
                $setting_data['wpvivid_common_setting']['estimate_backup'] = $setting['mwp_estimate_backup'];
                $setting_data['wpvivid_common_setting']['ismerge'] = $setting['mwp_ismerge'];
                $setting_data['wpvivid_common_setting']['retain_local'] = $setting['mwp_retain_local'];
                $setting_data['wpvivid_local_setting']['path'] = $setting['mwp_path'];
                $setting_data['wpvivid_common_setting']['domain_include'] = $setting['mwp_domain_include'];
                //
                $setting_data['wpvivid_common_setting']['db_connect_method'] = $setting['mwp_db_connect_method'];
                $setting_data['wpvivid_common_setting']['zip_method'] = $setting['mwp_zip_method'];
                $setting_data['wpvivid_common_setting']['max_file_size'] = $setting['mwp_max_file_size'].'M';
                $setting_data['wpvivid_common_setting']['exclude_file_size'] = intval($setting['mwp_exclude_file_size']);
                $setting_data['wpvivid_common_setting']['max_execution_time'] = intval($setting['mwp_max_execution_time']);
                $setting_data['wpvivid_common_setting']['memory_limit'] = $setting['mwp_memory_limit'].'M';
                $setting_data['wpvivid_common_setting']['compress_file_count'] = intval($setting['mwp_compress_file_count']);
                $setting_data['wpvivid_common_setting']['max_sql_file_size'] = intval($setting['mwp_max_sql_file_size']);
                $setting_data['wpvivid_common_setting']['migrate_size'] = $setting['mwp_migrate_size'];
                $setting_data['wpvivid_common_setting']['restore_max_execution_time'] = intval($setting['mwp_restore_max_execution_time']);
                $setting_data['wpvivid_common_setting']['restore_memory_limit'] = $setting['mwp_restore_memory_limit'].'M';
                $setting_data['wpvivid_common_setting']['replace_rows_pre_request'] = intval($setting['mwp_replace_rows_pre_request']);
                $setting_data['wpvivid_common_setting']['sql_file_buffer_pre_request'] = intval($setting['mwp_sql_file_buffer_pre_request']);
                $setting_data['wpvivid_common_setting']['max_resume_count'] = intval($setting['mwp_max_resume_count']);
                $setting_data['wpvivid_common_setting']['use_index'] = intval($setting['mwp_use_index']);
                $setting_data['wpvivid_common_setting']['unzip_files_pre_request'] =intval($setting['mwp_unzip_files_pre_request']);
                //
                $setting_data['wpvivid_local_setting']['save_local'] = isset($options['wpvivid_local_setting']['save_local']) ? $options['wpvivid_local_setting']['save_local'] : 1;
                $setting_data['wpvivid_common_setting']['log_save_location'] = $setting['mwp_path'] . '/wpvivid_log';
                $setting_data['wpvivid_common_setting']['show_admin_bar'] = isset($options['wpvivid_common_setting']['show_admin_bar']) ? $options['wpvivid_common_setting']['show_admin_bar'] : 1;
                //

                if(empty($options)){
                    $options = array();
                }
                foreach ($setting_data as $option_name => $option) {
                    $options[$option_name] = $setting_data[$option_name];
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('settings', $options);

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

    public function sync_setting()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['id']);
                $check_addon = '0';
                if(isset($_POST['addon']) && !empty($_POST['addon']) && is_string($_POST['addon'])) {
                    $check_addon = sanitize_text_field($_POST['addon']);
                }
                if($check_addon == '1'){
                    $post_data['mwp_action'] = 'wpvivid_set_general_setting_addon_mainwp';
                    $setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('settings_addon', array());
                    Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'settings_addon', $setting);
                }
                else {
                    $post_data['mwp_action'] = 'wpvivid_set_general_setting_mainwp';
                    $setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('settings', array());
                    Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'settings', $setting);
                }
                $post_data['setting'] = wp_json_encode($setting);
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

    public function render($check_pro, $global=false)
    {
        if(isset($_GET['synchronize']) && isset($_GET['addon']))
        {
            $check_addon = sanitize_text_field($_GET['addon']);
            $this->mwp_wpvivid_synchronize_setting($check_addon);
        }
        else {
            ?>
            <div style="padding: 10px;">
                <?php
                if($global){
                    if($this->select_pro){
                        $select_pro_check = 'checked';
                    }
                    else{
                        $select_pro_check = '';
                    }
                    ?>
                    <div class="mwp-wpvivid-block-bottom-space" style="background: #fff;">
                        <div class="postbox" style="padding: 10px; margin-bottom: 0;">
                            <div style="float: left; margin-top: 7px; margin-right: 25px;"><?php esc_html_e('Switch to WPvivid Backup Pro'); ?></div>
                            <div class="ui toggle checkbox mwp-wpvivid-pro-swtich" style="float: left; margin-top:4px; margin-right: 10px;">
                                <input type="checkbox" <?php echo esc_attr($select_pro_check); ?> />
                                <label for=""></label>
                            </div>
                            <div style="float: left;"><input class="ui green mini button" type="button" value="Save" onclick="mwp_wpvivid_switch_pro_setting();" /></div>
                            <div style="clear: both;"></div>
                        </div>
                    </div>
                    <div style="clear: both;"></div>
                    <?php
                    if($this->select_pro){
                        $this->mwp_wpvivid_setting_page_addon($global);
                    }
                    else{
                        $this->mwp_wpvivid_setting_page($global);
                    }
                    ?>
                    <?php
                }
                else{
                    if($check_pro){
                        $this->mwp_wpvivid_setting_page_addon($global);
                    }
                    else{
                        $this->mwp_wpvivid_setting_page($global);
                    }
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

                function mwp_wpvivid_swtich_global_setting_tab(evt, contentName){
                    var i, tabcontent, tablinks;
                    tabcontent = document.getElementsByClassName("mwp-global-setting-tab-content");
                    for (i = 0; i < tabcontent.length; i++) {
                        tabcontent[i].style.display = "none";
                    }
                    tablinks = document.getElementsByClassName("mwp-global-setting-nav-tab");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
                    }
                    document.getElementById(contentName).style.display = "block";
                    evt.currentTarget.className += " nav-tab-active";
                }
            </script>
            <?php
        }
    }

    public function mwp_wpvivid_setting_page_addon($global){
        ?>
        <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
            <div class="mwp-wpvivid-welcome-bar-left">
                <p><span class="dashicons dashicons-admin-generic mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-blue"></span><span class="mwp-wpvivid-page-title">Settings</span></p>
                <span class="about-description">Settings for all WPvivid plugins.</span>
            </div>
            <div class="mwp-wpvivid-welcome-bar-right"></div>
            <div class="mwp-wpvivid-nav-bar mwp-wpvivid-clear-float">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span> All default settings are optimized for most users, leave it as default or feel free to modify as per your preferences.</span>
            </div>
        </div>
        <?php
        if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
        include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
        $this->main_tab=new Mainwp_WPvivid_Tab_Page_Container();

        $args['is_parent_tab']=0;
        $args['transparency']=1;
        $args['global']=$global;
        $this->main_tab->add_tab('General Settings','general_addon',array($this, 'output_general_setting_addon'), $args);
        $this->main_tab->add_tab('Advanced Settings','advance_addon',array($this, 'output_advance_setting_addon'), $args);
        $this->main_tab->add_tab('Unused Image Cleaner', 'unused_image_addon', array($this, 'output_unused_image_setting_addon'), $args);
        $this->main_tab->add_tab('Image Optimization', 'image_optimization_addon', array($this, 'output_image_optimization_setting_addon'), $args);
        $this->main_tab->add_tab('Lazyload Settings', 'lazyload_addon', array($this, 'output_lazyload_setting_addon'), $args);
        $this->main_tab->add_tab('CDN Settings', 'cdn_addon', array($this, 'output_cdn_settings_addon'), $args);
        $this->main_tab->add_tab('Staging Settings', 'staging_addon', array($this, 'output_staging_setting_addon'), $args);
        $this->main_tab->add_tab('Rollback Settings', 'rollback_addon', array($this, 'output_rollback_setting_addon'), $args);
        $this->main_tab->display();
        ?>
        <?php
        if ($global === false) {
            $save_change_id = 'mwp_wpvivid_setting_general_save_addon';
        } else {
            $save_change_id = 'mwp_wpvivid_global_setting_general_save_addon';
        }
        ?>
        <div style="padding:1em 1em 0 0;"><input class="ui green mini button" id="<?php echo esc_attr($save_change_id); ?>" type="button" value="<?php esc_attr_e('Save Changes and Sync'); ?>" /></div>
        <script>
            jQuery('#mwp_wpvivid_setting_general_save_addon').click(function(){
                mwp_wpvivid_set_general_settings_addon();
            });
            jQuery('#mwp_wpvivid_global_setting_general_save_addon').click(function(){
                mwp_wpvivid_set_global_general_settings_addon();
            });
            function mwp_wpvivid_set_general_settings_addon()
            {
                var setting_data = mwp_wpvivid_ajax_data_transfer('mwp-setting-addon');
                var lazyload_data = mwp_wpvivid_ajax_data_transfer('mwp-lazyload-addon');
                var cdn_data = mwp_wpvivid_ajax_data_transfer('mwp-cdn-addon');
                var rollback_data = mwp_wpvivid_ajax_data_transfer('mwp-rollback-addon');
                var ajax_data = {
                    'action': 'mwp_wpvivid_set_general_setting_addon',
                    'setting': setting_data,
                    'lazyload': lazyload_data,
                    'cdn': cdn_data,
                    'rollback': rollback_data,
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_setting_general_save_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#mwp_wpvivid_setting_general_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            location.reload();
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#mwp_wpvivid_setting_general_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_setting_general_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_set_global_general_settings_addon()
            {
                var json = {};
                json['mwp_send_to']={};
                var email_array = {};
                var email_check = '';
                jQuery('#mwp_wpvivid_email_list tr').each(function(){
                    email_check = '1';
                    var email_send_to = jQuery(this).find('td:eq(0) label').html();
                    email_array['email_address'] = email_send_to;
                    email_array['email_enable'] = email_check;
                    json['mwp_send_to'][email_send_to] = email_array;
                    email_array = {};
                });

                var setting_data = mwp_wpvivid_ajax_data_transfer('mwp-setting-addon');

                var json1 = JSON.parse(setting_data);
                jQuery.extend(json1, json);
                setting_data=JSON.stringify(json1);

                var lazyload_data = mwp_wpvivid_ajax_data_transfer('mwp-lazyload-addon');
                var cdn_data = mwp_wpvivid_ajax_data_transfer('mwp-cdn-addon');
                var rollback_data = mwp_wpvivid_ajax_data_transfer('mwp-rollback-addon');
                var ajax_data = {
                    'action': 'mwp_wpvivid_set_global_general_setting_addon',
                    'setting': setting_data,
                    'lazyload': lazyload_data,
                    'cdn': cdn_data,
                    'rollback': rollback_data
                };

                jQuery('#mwp_wpvivid_global_setting_general_save_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        jQuery('#mwp_wpvivid_global_setting_general_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            window.location.href = window.location.href + "&synchronize=1&addon=1";
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#mwp_wpvivid_global_setting_general_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_global_setting_general_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_encrypt_db').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    jQuery('#mwp_wpvivid_encrypt_db_pw').attr('readonly', false);
                }
                else{
                    jQuery('#mwp_wpvivid_encrypt_db_pw').attr('readonly', true);
                }
            });

            jQuery('#mwp_wpvivid_send_email_test').click(function()
            {
                var mail = jQuery('#mwp_wpvivid_mail').val();
                if(mail !== '') {
                    var repeat = false;
                    jQuery('#mwp_wpvivid_email_list tr').each(function(){
                        var email_address = jQuery(this).find('td:eq(0)').find('label').html();
                        if(mail === email_address){
                            repeat = true;
                        }
                    });
                    if(!repeat) {
                        var html = '';
                        html += '<tr>';
                        html += '<td class="row-title" option="email_list"><label for="tablecell">'+mail+'</label></td>';
                        html += '<td onclick="mwp_wpvivid_remove_mail(this);">';
                        html += '<a href="#"><span class="dashicons dashicons-trash wpvivid-dashicons-grey"></span></a>';
                        html += '</td>';
                        html += '</tr>';
                        jQuery('#mwp_wpvivid_email_list').append(html);
                    }
                    else{
                        alert('Email alreay in list.');
                    }
                }
                else{
                    alert('Mail is required.');
                }
            });

            function mwp_wpvivid_remove_mail(obj)
            {
                jQuery(obj).parents("tr:first").remove();
            }

            function mwp_wpvivid_export_settings() {
                location.href =ajaxurl+'?_wpnonce='+ajax_object.ajax_nonce+'&action=mwp_wpvivid_export_setting_addon';
            }

            jQuery('#mwp_wpvivid_setting_export').click(function(){
                mwp_wpvivid_export_settings();
            });

            function mwp_wpvivid_import_settings(){
                var files = jQuery('input[name="fileTrans"]').prop('files');

                if(files.length == 0){
                    alert('Choose a settings file and import it by clicking Import button.');
                    return;
                }
                else{
                    var reader = new FileReader();
                    reader.readAsText(files[0], "UTF-8");
                    reader.onload = function(evt){
                        var fileString = evt.target.result;
                        var ajax_data = {
                            'action': 'mwp_wpvivid_import_setting_addon',
                            'data': fileString
                        };
                        mwp_wpvivid_post_request(ajax_data, function(data){
                            try {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success') {
                                    alert('The plugin settings were imported successfully.');
                                    location.reload();
                                }
                                else {
                                    alert('Error: ' + jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function(XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = mwp_wpvivid_output_ajaxerror('importing the previously-exported settings', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            }

            jQuery('#mwp_wpvivid_setting_import').click(function(){
                mwp_wpvivid_import_settings();
            });

            jQuery('input:radio[option=mwp-setting-addon][name=mwp_backup_params_addon]').click(function()
            {
                if(jQuery(this).prop('checked'))
                {
                    var value = jQuery(this).prop('value');
                    if(value=='custom')
                    {
                        jQuery('#mwp_wpvivid_custom_backup_params_addon').show();
                    }
                    else
                    {
                        jQuery('#mwp_wpvivid_custom_backup_params_addon').hide();
                    }
                }
            });
        </script>
        <?php
    }

    public function output_general_setting_addon($global){
        $wpvivid_clean_old_remote_before_backup = 'checked';
        if(isset($this->setting_addon['wpvivid_common_setting']['clean_old_remote_before_backup'])){
            $wpvivid_clean_old_remote_before_backup = $this->setting_addon['wpvivid_common_setting']['clean_old_remote_before_backup'] == '1' ? 'checked' : '';
        }
        $wpvivid_setting_estimate_backup = 'checked';
        if(isset($this->setting_addon['wpvivid_common_setting']['estimate_backup'])){
            $wpvivid_setting_estimate_backup = $this->setting_addon['wpvivid_common_setting']['estimate_backup'] == '1' ? 'checked' : '';
        }
        $show_admin_bar = 'checked';
        if(isset($this->setting_addon['wpvivid_common_setting']['show_admin_bar'])){
            $show_admin_bar = $this->setting_addon['wpvivid_common_setting']['show_admin_bar'] == '1' ? 'checked' : '';
        }
        $wpvivid_setting_ismerge = 'checked';
        if(isset($this->setting_addon['wpvivid_common_setting']['ismerge'])){
            $wpvivid_setting_ismerge = $this->setting_addon['wpvivid_common_setting']['ismerge'] == '1' ? 'checked' : '';
        }
        $wpvivid_save_local = '';
        if(isset($this->setting_addon['wpvivid_common_setting']['retain_local'])){
            $wpvivid_save_local = $this->setting_addon['wpvivid_common_setting']['retain_local'] == '1' ? 'checked' : '';
        }
        $wpvivid_remove_out_of_date = '';
        if(isset($this->setting_addon['wpvivid_common_setting']['remove_out_of_date'])){
            $wpvivid_remove_out_of_date = $this->setting_addon['wpvivid_common_setting']['remove_out_of_date'] == '1' ? 'checked' : '';
        }
        $uninstall_clear_folder = '';
        if(isset($this->setting_addon['wpvivid_common_setting']['uninstall_clear_folder'])){
            $uninstall_clear_folder = $this->setting_addon['wpvivid_common_setting']['uninstall_clear_folder'] == '1' ? 'checked' : '';
        }
        $hide_admin_update_notice = '';
        if(isset($this->setting_addon['wpvivid_common_setting']['hide_admin_update_notice'])){
            $hide_admin_update_notice = $this->setting_addon['wpvivid_common_setting']['hide_admin_update_notice'] == '1' ? 'checked' : '';
        }


        if(isset($this->setting_addon['wpvivid_common_setting']['default_backup_local'])) {
            if($this->setting_addon['wpvivid_common_setting']['default_backup_local']){
                $default_backup_local = 'checked';
                $default_backup_remote = '';
            }
            else{
                $default_backup_local = '';
                $default_backup_remote = 'checked';
            }
        }
        else{
            $default_backup_local = 'checked';
            $default_backup_remote = '';
        }

        $wpvivid_local_directory = isset($this->setting_addon['wpvivid_local_setting']['path']) ? $this->setting_addon['wpvivid_local_setting']['path'] : 'wpvividbackups';

        if($global)
        {
            if(!isset($this->setting_addon['wpvivid_common_setting']['backup_prefix'])){
                $prefix = '';
            }
            else{
                $prefix = $this->setting_addon['wpvivid_common_setting']['backup_prefix'];
            }
        }
        else
        {
            if(!isset($this->setting_addon['wpvivid_common_setting']['backup_prefix'])){
                $prefix = '';
                $prefix = apply_filters('mwp_wpvivid_get_backup_prefix', $prefix);
            }
            else{
                $prefix = $this->setting_addon['wpvivid_common_setting']['backup_prefix'];
            }
        }


        if(isset($this->setting_addon['wpvivid_common_setting']['encrypt_db']))
        {
            if($this->setting_addon['wpvivid_common_setting']['encrypt_db'] == '1')
            {
                $encrypt_db_check='checked';
                $encrypt_db_disable='';
            }
            else{
                $encrypt_db_check='';
                $encrypt_db_disable='readonly="readonly"';
            }

        }
        else
        {
            $encrypt_db_check='';
            $encrypt_db_disable='readonly="readonly"';
        }

        if(isset($this->setting_addon['wpvivid_common_setting']['encrypt_db_password']))
        {
            $password=$this->setting_addon['wpvivid_common_setting']['encrypt_db_password'];
        }
        else
        {
            $password='';
        }


        ?>
        <div style="margin-top: 10px;">
            <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">General</label></td>
                    <td>
                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_clean_old_remote_before_backup_addon" <?php echo esc_attr($wpvivid_clean_old_remote_before_backup); ?> />
                                <span><?php esc_html_e('Remove the oldest backups stored in remote storage before creating a backup if the current backups reached the limit of backup retention for remote storage. It is recommended to uncheck this option if there is a unstable connection between your site and remote storge'); ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_estimate_backup_addon" <?php echo esc_attr($wpvivid_setting_estimate_backup); ?> />
                                <span><?php esc_html_e('Calculate the size of files, folder and database before backing up'); ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_show_admin_bar_addon" <?php echo esc_attr($show_admin_bar); ?>>
                                <span><?php esc_html_e('Show WPvivid backup plugin on top admin bar'); ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_ismerge_addon" <?php echo esc_attr($wpvivid_setting_ismerge); ?> />
                                <span><?php esc_html_e('Merge all the backup files into single package when a backup completes. This will save great disk spaces, though takes longer time. We recommended you check the option especially on sites with insufficient server resources.'); ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_retain_local_addon" <?php echo esc_attr($wpvivid_save_local); ?> />
                                <span><?php esc_html_e('Keep storing the backups in localhost after uploading to remote storage'); ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_remove_out_of_date_addon" <?php echo esc_attr($wpvivid_remove_out_of_date); ?>>
                                <span><?php esc_html_e('The out-of-date backups will be removed if the current value of backup retention is lower than the previous one, which is irreversible'); ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_uninstall_clear_folder_addon" <?php echo esc_attr($uninstall_clear_folder); ?>>
                                <span><?php echo 'Delete the '.esc_html($wpvivid_local_directory).' folder when deleting WPvivid Backup Pro. Caution: This folder may contain WPvivid Pro and Free backups, once deleted, any backups in it will be permanently lost!'; ?></span>
                            </label>
                        </p>

                        <p>
                            <label>
                                <input type="checkbox" option="mwp-setting-addon" name="mwp_hide_admin_update_notice_addon" <?php echo esc_attr($hide_admin_update_notice); ?>>
                                <span><?php esc_html_e('Do not show the plugin update notice on my website pages.'); ?></span>
                            </label>
                        </p>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup Retention</label></td>
                    <td>
                        <?php
                        if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
                        include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
                        $this->backup_count_retain_tab=new Mainwp_WPvivid_Tab_Page_Container();

                        $args['is_parent_tab']=0;
                        $args['transparency']=1;
                        $args['global']=$global;
                        $this->backup_count_retain_tab->add_tab('Manual Backup','manual_backup_addon',array($this, 'output_manual_backup_count_setting_page_addon'), $args);
                        $this->backup_count_retain_tab->add_tab('Schedule(General)','general_schedule_addon',array($this, 'output_schedule_backup_count_setting_page_addon'), $args);
                        $this->backup_count_retain_tab->add_tab('Schedule(Incremental)', 'incremental_schedule_addon', array($this, 'output_incremental_schedule_backup_count_setting_page_addon'), $args);
                        //$this->backup_count_retain_tab->add_tab('Rollback', 'rollback_schedule_addon', array($this, 'output_rollback_backup_count_setting_page_addon'), $args);
                        $this->backup_count_retain_tab->display();
                        ?>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Default location for backups:</label></td>
                    <td>
                        <p>Set the default location for backups:</p>
                        <p></p>
                        <fieldset>
                            <label style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_default_backup_local_addon" value="1" <?php echo esc_attr($default_backup_local); ?> />
                                <span><?php esc_html_e('Localhost(web server)'); ?></span>
                            </label>
                            <label style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_default_backup_local_addon" value="0" <?php echo esc_attr($default_backup_remote); ?> />
                                <span><?php esc_html_e('Cloud Storage'); ?></span>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup folder</label></td>
                    <td>
                        <p>
                            <input type="text" option="mwp-setting-addon" name="mwp_path_addon" value="<?php echo esc_attr($wpvivid_local_directory); ?>" onkeyup="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" /> Name your folder, this folder must be writable for creating backup files.
                        </p>
                        <p></p>
                        <div>
                            <input type="text" id="mwp_wpvivid_backup_prefix" placeholder="Enter prefix (e.g. test)" value="<?php echo esc_attr($prefix); ?>" option="mwp-setting-addon" name="mwp_backup_prefix_addon" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" /> Add a prefix to all backup files
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Only letters (except for wpvivid) and numbers are allowed. This will help you identify backups if you store backups of many websites in one directory.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="row-title" style="min-width:200px;"><label for="tablecell">Database encryption</label></td>
                    <td>
                        <p>
                            <label>
                                <input type="checkbox" id="mwp_wpvivid_encrypt_db" option="mwp-setting-addon" name="mwp_encrypt_db_addon" <?php echo esc_attr($encrypt_db_check); ?> />
                                <span><?php esc_html_e('Enable database encryption'); ?></span>
                            </label>
                        </p>
                        <p>
                            <input type="password" class="all-options" id="mwp_wpvivid_encrypt_db_pw" option="mwp-setting-addon" name="mwp_encrypt_db_password_addon" value="<?php echo esc_attr($password); ?>" <?php echo esc_attr($encrypt_db_disable); ?> /> Enter a password here to encrypt your database backups.
                        </p>
                        <p>
                            <code>The password is also required to decrypt your backups, we are not able to reset it for you or decrypt your backups, so please do write it down and store it safely. Backups encrypted with this option can only be decrypted with WPvivid Backup Pro.</code>
                        </p>
                    </td>
                </tr>

                <?php
                if($global)
                {
                    $wpvivid_setting_email_always='';
                    $wpvivid_setting_email_failed='';
                    if(isset($this->setting_addon['wpvivid_email_setting_addon']['always'])) {
                        if ($this->setting_addon['wpvivid_email_setting_addon']['always']) {
                            $wpvivid_setting_email_always = 'checked';
                        } else {
                            $wpvivid_setting_email_failed = 'checked';
                        }
                    }
                    else{
                        $wpvivid_setting_email_always = 'checked';
                    }
                    if(isset($this->setting_addon['wpvivid_email_setting_addon']['email_attach_log'])){
                        if ($this->setting_addon['wpvivid_email_setting_addon']['email_attach_log']) {
                            $wpvivid_email_attach_log = 'checked';
                        } else {
                            $wpvivid_email_attach_log = '';
                        }
                    }
                    else{
                        $wpvivid_email_attach_log = 'checked';
                    }
                    if(isset($this->setting_addon['wpvivid_email_setting_addon']['use_mail_title'])){
                        if($this->setting_addon['wpvivid_email_setting_addon']['use_mail_title']){
                            $wpvivid_use_mail_title = 'checked';
                            $wpvivid_mail_title_style = '';
                        }
                        else{
                            $wpvivid_use_mail_title = '';
                            $wpvivid_mail_title_style = 'readonly="readonly"';
                        }
                    }
                    else{
                        $wpvivid_use_mail_title = 'checked';
                        $wpvivid_mail_title_style = '';
                    }

                    $mail_title = isset($this->setting_addon['wpvivid_email_setting_addon']['mail_title']) ? $this->setting_addon['wpvivid_email_setting_addon']['mail_title'] : 'child-site';
                    ?>
                    <tr>
                        <td class="row-title" style="min-width:200px;"><label for="tablecell">Email report</label></td>
                        <td>
                            <div style="padding:0 1em 1em 0;">
                                <span class="dashicons  dashicons-warning wpvivid-dashicons-red"></span>
                                <span>Configure you email server(SMTP) with a <a href="https://wpvivid.com/8-best-smtp-plugins-for-wordpress.html">WordPress SMTP plugin</a> before using the feature</span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>WordPress uses the PHP Mail function to send its emails by default, which is not supported by many hosts and can cause issues if it is not set properly.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                            </div>
                            <p>
                                <input type="text" placeholder="example@yourdomain.com" option="setting" name="send_to" class="regular-text" id="mwp_wpvivid_mail">
                                <input class="button-secondary" id="mwp_wpvivid_send_email_test" type="submit" value="Test and Add" title="Send an email for testing mail function">
                            </p>
                            <div id="mwp_wpvivid_send_email_res" style="display: none;"></div>
                            <div>
                                <table class="widefat">
                                    <tr>
                                        <th class="row-title">Email Address</th>
                                        <th>Action</th>
                                    </tr>
                                    <tbody id="mwp_wpvivid_email_list">
                                    <?php
                                    if(isset($this->setting_addon['wpvivid_email_setting_addon']['send_to'])){
                                        foreach ($this->setting_addon['wpvivid_email_setting_addon']['send_to'] as $mail => $value){
                                            ?>
                                            <tr>
                                                <td class="row-title" option="mwp_email_list"><label for="tablecell"><?php echo esc_html($value['email_address']); ?></label></td>
                                                <td onclick="mwp_wpvivid_remove_mail(this);"><a href="#"><span class="dashicons dashicons-trash wpvivid-dashicons-grey"></span></a></td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="padding:1em 1em 0 0;">
                                <p></p>

                                <label style="float:left; padding-right:1em;">
                                    <input type="radio" option="mwp-setting-addon" name="mwp_always_addon" value="1" <?php echo esc_attr($wpvivid_setting_email_always); ?> />
                                    <span>Always send an email notification when a backup is complete</span>
                                </label>
                                <label style="float:left; padding-right:1em;">
                                    <input type="radio" option="mwp-setting-addon" name="mwp_always_addon" value="0" <?php echo esc_attr($wpvivid_setting_email_failed); ?> />
                                    <span>Only send an email notification when a backup fails</span>
                                </label>

                                <div style="clear: both;"></div>
                                <p></p>

                                <p>
                                    <label>
                                        <input type="checkbox" option="mwp-setting-addon" name="mwp_email_attach_log_addon" <?php echo esc_attr($wpvivid_email_attach_log); ?> />
                                        <span>Attach the log when sending a report</span>
                                    </label>
                                </p>

                                <div>
                                    <label>
                                        <input type="checkbox" option="mwp-setting-addon" name="mwp_use_mail_title_addon" <?php echo esc_attr($wpvivid_use_mail_title); ?> />
                                        <span>Comment the email subject</span>
                                        <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                            <div class="mwp-wpvivid-bottom">
                                                <!-- The content you need -->
                                                <p>Add a custom subject to WPvivid backup email reports for easy identification. The default subject is the domain name of the current website.</p>
                                                <i></i> <!-- do not delete this line -->
                                            </div>
                                        </span>
                                    </label>
                                </div>
                                <p><input type="text" id="mwp_wpvivid_mail_title" option="mwp-setting-addon" name="mwp_mail_title_addon" value="<?php echo esc_attr($mail_title); ?>" placeholder="" <?php echo esc_attr($wpvivid_mail_title_style); ?> /></p>
                                <p>
                                    <span>e.g. [</span><span><?php echo esc_html($mail_title); ?></span><span><?php /* translators: %s: Plugin name. */ echo sprintf(esc_html(': Backup Succeeded]12-04-2019 07:04:57 - By %s.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid Backup Plugin'))); ?></span>
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="row-title" style="min-width:200px;"><label for="tablecell">Export/Import Extension Settings</label></td>
                        <td>
                            <table class="widefat" style="border:none;box-shadow:none;">
                                <tr>
                                    <td>
                                        <p><input id="mwp_wpvivid_setting_export" type="button" name="" value="Export">Export settings of WPvivid Backup for MainWP extension to your local computer.</p>
                                    </td>
                                    <td>
                                        <p><input type="file" name="fileTrans" id="mwp_wpvivid_select_import_file"></p>
                                        <p><input id="mwp_wpvivid_setting_import" type="button" name="" value="Import">Import exported settings of WPvivid Backup for MainWP extension to the current site.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </table>
        </div>
        <?php
    }

    public function output_manual_backup_count_setting_page_addon()
    {
        $manual_max_backup_count=isset($this->setting_addon['wpvivid_common_setting']['manual_max_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['manual_max_backup_count'] : '30';
        $manual_max_backup_count=intval($manual_max_backup_count);
        $manual_max_backup_db_count=isset($this->setting_addon['wpvivid_common_setting']['manual_max_backup_db_count']) ? $this->setting_addon['wpvivid_common_setting']['manual_max_backup_db_count'] : '30';
        $manual_max_backup_db_count=intval($manual_max_backup_db_count);

        if(isset($this->setting_addon['wpvivid_common_setting']['manual_max_remote_backup_count']))
        {
            $max_remote_backup_count=$this->setting_addon['wpvivid_common_setting']['manual_max_remote_backup_count'];
        }
        else if(isset($this->setting_addon['wpvivid_common_setting']['max_remote_backup_count']))
        {
            $max_remote_backup_count=$this->setting_addon['wpvivid_common_setting']['max_remote_backup_count'];
        }
        else
        {
            $max_remote_backup_count=30;
        }
        $max_remote_backup_count=intval($max_remote_backup_count);

        if(isset($this->setting_addon['wpvivid_common_setting']['manual_max_remote_backup_db_count']))
        {
            $max_remote_backup_db_count=$this->setting_addon['wpvivid_common_setting']['manual_max_remote_backup_db_count'];
        }
        else if(isset($this->setting_addon['wpvivid_common_setting']['max_remote_backup_db_count']))
        {
            $max_remote_backup_db_count=$this->setting_addon['wpvivid_common_setting']['max_remote_backup_db_count'];
        }
        else
        {
            $max_remote_backup_db_count=30;
        }
        $max_remote_backup_db_count=intval($max_remote_backup_db_count);
        ?>
        <div style="margin-top: 10px;">
            <div>
                <p>Manual Backup</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_manual_max_backup_count_addon" id="mwp_manual_max_backup_count" value="<?php echo esc_attr($manual_max_backup_count); ?>"> (localhost)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_manual_max_backup_db_count_addon" id="mwp_manual_max_backup_db_count" value="<?php echo esc_attr($manual_max_backup_db_count); ?>"> (localhost)Database backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_manual_max_remote_backup_count_addon" id="mwp_manual_max_remote_backup_count" value="<?php echo esc_attr($max_remote_backup_count); ?>" onkeyup="wpvivid_set_max_remote_backup_count(this);"> (remote storage)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_manual_max_remote_backup_db_count_addon" id="mwp_manual_max_remote_backup_db_count" value="<?php echo esc_attr($max_remote_backup_db_count); ?>" onkeyup="wpvivid_set_max_remote_backup_db_count(this);"> (remote storage)Database backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function output_schedule_backup_count_setting_page_addon()
    {
        $schedule_max_backup_count=isset($this->setting_addon['wpvivid_common_setting']['schedule_max_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['schedule_max_backup_count'] : '30';
        $schedule_max_backup_count=intval($schedule_max_backup_count);
        $schedule_max_backup_db_count=isset($this->setting_addon['wpvivid_common_setting']['schedule_max_backup_db_count']) ? $this->setting_addon['wpvivid_common_setting']['schedule_max_backup_db_count'] : '30';
        $schedule_max_backup_db_count=intval($schedule_max_backup_db_count);

        if(isset($this->setting_addon['wpvivid_common_setting']['schedule_max_remote_backup_count']))
        {
            $max_remote_backup_count=$this->setting_addon['wpvivid_common_setting']['schedule_max_remote_backup_count'];
        }
        else if(isset($this->setting_addon['wpvivid_common_setting']['max_remote_backup_count']))
        {
            $max_remote_backup_count=$this->setting_addon['wpvivid_common_setting']['max_remote_backup_count'];
        }
        else
        {
            $max_remote_backup_count=30;
        }
        $max_remote_backup_count=intval($max_remote_backup_count);

        if(isset($this->setting_addon['wpvivid_common_setting']['schedule_max_remote_backup_db_count']))
        {
            $max_remote_backup_db_count=$this->setting_addon['wpvivid_common_setting']['schedule_max_remote_backup_db_count'];
        }
        else if(isset($this->setting_addon['wpvivid_common_setting']['max_remote_backup_db_count']))
        {
            $max_remote_backup_db_count=$this->setting_addon['wpvivid_common_setting']['max_remote_backup_db_count'];
        }
        else
        {
            $max_remote_backup_db_count=30;
        }
        $max_remote_backup_db_count=intval($max_remote_backup_db_count);

        ?>
        <div style="margin-top: 10px;">
            <div>
                <p>Schedule(General)</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_schedule_max_backup_count_addon" id="mwp_schedule_max_backup_count" value="<?php echo esc_attr($schedule_max_backup_count); ?>"> (localhost)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_schedule_max_backup_db_count_addon" id="mwp_schedule_max_backup_db_count" value="<?php echo esc_attr($schedule_max_backup_db_count); ?>"> (localhost)Database backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_schedule_max_remote_backup_count_addon" id="mwp_schedule_max_remote_backup_count" value="<?php echo esc_attr($max_remote_backup_count); ?>" onkeyup="wpvivid_set_max_remote_backup_count(this);"> (remote storage)File Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_schedule_max_remote_backup_db_count_addon" id="mwp_schedule_max_remote_backup_db_count" value="<?php echo esc_attr($max_remote_backup_db_count); ?>" onkeyup="wpvivid_set_max_remote_backup_db_count(this);"> (remote storage)Database backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function output_incremental_schedule_backup_count_setting_page_addon()
    {
        $incremental_max_db_count=isset($this->setting_addon['wpvivid_common_setting']['incremental_max_db_count']) ? $this->setting_addon['wpvivid_common_setting']['incremental_max_db_count'] : '3';
        $incremental_max_db_count=intval($incremental_max_db_count);
        $incremental_max_backup_count=isset($this->setting_addon['wpvivid_common_setting']['incremental_max_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['incremental_max_backup_count'] : '3';
        $incremental_max_backup_count=intval($incremental_max_backup_count);
        $incremental_max_remote_backup_count=isset($this->setting_addon['wpvivid_common_setting']['incremental_max_remote_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['incremental_max_remote_backup_count'] : '3';
        $incremental_max_remote_backup_count=intval($incremental_max_remote_backup_count);
        ?>
        <div style="margin-top: 10px;">
            <div>
                <p>Schedule(Incremental)</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="3" option="mwp-setting-addon" name="mwp_incremental_max_db_count_addon" id="mwp_incremental_max_db_count" value="<?php echo esc_attr($incremental_max_db_count); ?>"> (localhost)Incremental Database Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="3" option="mwp-setting-addon" name="mwp_incremental_max_backup_count_addon" id="mwp_incremental_max_backup_count" value="<?php echo esc_attr($incremental_max_backup_count); ?>"> (localhost) Cycles of incremental backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="3" option="mwp-setting-addon" name="mwp_incremental_max_remote_backup_count_addon" id="mwp_incremental_max_remote_backup_count" value="<?php echo esc_attr($incremental_max_remote_backup_count); ?>"> (remote storage) Cycles of incremental backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function output_rollback_backup_count_setting_page_addon()
    {
        $rollback_max_backup_count=isset($this->setting_addon['wpvivid_common_setting']['rollback_max_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['rollback_max_backup_count'] : '30';
        $rollback_max_backup_count=intval($rollback_max_backup_count);
        $rollback_max_remote_backup_count=isset($this->setting_addon['wpvivid_common_setting']['rollback_max_remote_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['rollback_max_remote_backup_count'] : '30';
        $rollback_max_remote_backup_count=intval($rollback_max_remote_backup_count);
        ?>
        <div style="margin-top: 10px;">
            <div>
                <p>Rollback</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_rollback_max_backup_count_addon" id="mwp_rollback_max_backup_count" value="<?php echo esc_attr($rollback_max_backup_count); ?>"> (localhost)Rollback Backups retained.</p>
                <p><input type="text" class="wpvivid-backup-count-retention" placeholder="30" option="mwp-setting-addon" name="mwp_rollback_max_remote_backup_count_addon" id="mwp_rollback_max_remote_backup_count" value="<?php echo esc_attr($rollback_max_remote_backup_count); ?>"> (remote storage)Rollback Backups retained.</p>
            </div>
        </div>
        <?php
    }

    public function output_advance_setting_addon($global){
        $use_adaptive_settings='';
        if(isset($this->setting_addon['wpvivid_common_setting']['use_adaptive_settings']))
        {
            $use_adaptive_settings = $this->setting_addon['wpvivid_common_setting']['use_adaptive_settings'] == '1' ? 'checked' : '';
        }

        $auto_delete_backup_log='';
        if(isset($this->setting_addon['wpvivid_common_setting']['auto_delete_backup_log']))
        {
            $auto_delete_backup_log = $this->setting_addon['wpvivid_common_setting']['auto_delete_backup_log'] == '1' ? 'checked' : '';
        }

        $backup_database_use_primary_key='checked';
        if(isset($this->setting_addon['wpvivid_common_setting']['backup_database_use_primary_key']))
        {
            $backup_database_use_primary_key = $this->setting_addon['wpvivid_common_setting']['backup_database_use_primary_key'] == '1' ? 'checked' : '';
        }

        $backup_upload_use_cm_store='checked';
        if(isset($this->setting_addon['wpvivid_common_setting']['backup_upload_use_cm_store']))
        {
            $backup_upload_use_cm_store = $this->setting_addon['wpvivid_common_setting']['backup_upload_use_cm_store'] == '1' ? 'checked' : '';
        }

        $db_method_wpdb = 'checked';
        $db_method_pdo  = '';
        if(isset($this->setting_addon['wpvivid_common_setting']['db_connect_method'])){
            if($this->setting_addon['wpvivid_common_setting']['db_connect_method'] === 'wpdb'){
                $db_method_wpdb = 'checked';
                $db_method_pdo  = '';
            }
            else{
                $db_method_wpdb = '';
                $db_method_pdo  = 'checked';
            }
        }

        if(isset($this->setting_addon['wpvivid_common_setting']['zip_method'])){
            if($this->setting_addon['wpvivid_common_setting']['zip_method'] === 'ziparchive'){
                $zip_method_archive = 'checked';
                $zip_method_pclzip  = '';
            }
            else{
                $zip_method_archive = '';
                $zip_method_pclzip  = 'checked';
            }
        }
        else{
            $zip_method_archive = '';
            $zip_method_pclzip  = 'checked';
        }

        $compress_file_count=isset($this->setting_addon['wpvivid_common_setting']['compress_file_count'])?$this->setting_addon['wpvivid_common_setting']['compress_file_count']:500;
        $max_file_size=isset($this->setting_addon['wpvivid_common_setting']['max_file_size'])?$this->setting_addon['wpvivid_common_setting']['max_file_size']:200;
        $max_backup_table=isset($this->setting_addon['wpvivid_common_setting']['max_backup_table'])?$this->setting_addon['wpvivid_common_setting']['max_backup_table']:1000;
        $max_sql_file_size=isset($this->setting_addon['wpvivid_common_setting']['max_sql_file_size'])?$this->setting_addon['wpvivid_common_setting']['max_sql_file_size']:400;
        $exclude_file_size=isset($this->setting_addon['wpvivid_common_setting']['exclude_file_size'])?$this->setting_addon['wpvivid_common_setting']['exclude_file_size']:0;
        $max_execution_time=isset($this->setting_addon['wpvivid_common_setting']['max_execution_time'])?$this->setting_addon['wpvivid_common_setting']['max_execution_time']:900;
        $restore_max_execution_time=isset($this->setting_addon['wpvivid_common_setting']['restore_max_execution_time'])?$this->setting_addon['wpvivid_common_setting']['restore_max_execution_time']:1800;
        $memory_limit=isset($this->setting_addon['wpvivid_common_setting']['memory_limit'])?$this->setting_addon['wpvivid_common_setting']['memory_limit']:'256M';
        $restore_memory_limit=isset($this->setting_addon['wpvivid_common_setting']['restore_memory_limit'])?$this->setting_addon['wpvivid_common_setting']['restore_memory_limit']:'256M';
        $migrate_size=isset($this->setting_addon['wpvivid_common_setting']['migrate_size'])?$this->setting_addon['wpvivid_common_setting']['migrate_size']:'2048';

        if(isset($this->setting_addon['wpvivid_common_setting']['backup_params']))
        {
            if($this->setting_addon['wpvivid_common_setting']['backup_params'] === 'low')
            {
                $backup_params_low    = 'checked';
                $backup_params_mid    = '';
                $backup_params_high   = '';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
            else if($this->setting_addon['wpvivid_common_setting']['backup_params'] === 'mid')
            {
                $backup_params_low    = '';
                $backup_params_mid    = 'checked';
                $backup_params_high   = '';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
            else if($this->setting_addon['wpvivid_common_setting']['backup_params'] === 'high')
            {
                $backup_params_low    = '';
                $backup_params_mid    = '';
                $backup_params_high   = 'checked';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
            else if($this->setting_addon['wpvivid_common_setting']['backup_params'] === 'custom')
            {
                $backup_params_low    = '';
                $backup_params_mid    = '';
                $backup_params_high   = '';
                $backup_params_custom = 'checked';
                $backup_custom_setting_display = '';
            }
            else
            {
                $backup_params_low    = 'checked';
                $backup_params_mid    = '';
                $backup_params_high   = '';
                $backup_params_custom = '';
                $backup_custom_setting_display = 'display: none;';
            }
        }
        else if(isset($this->setting_addon['wpvivid_common_setting']['compress_file_count']))
        {
            $backup_params_low    = '';
            $backup_params_mid    = '';
            $backup_params_high   = '';
            $backup_params_custom = 'checked';
            $backup_custom_setting_display = '';
        }
        else
        {
            $backup_params_low    = 'checked';
            $backup_params_mid    = '';
            $backup_params_high   = '';
            $backup_params_custom = '';
            $backup_custom_setting_display = 'display: none;';
        }

        ?>
        <div style="margin-top: 10px;"></div>
        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Learning Mode</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_use_adaptive_settings_addon" <?php echo esc_attr($use_adaptive_settings); ?> />
                            <span><?php esc_html_e('Enable Learning Mode'); ?></span>
                        </label>
                    </p>
                    <p><code>Designed for servers with limited resources. Enabling it can improve backup success rates, but may result in longer backup time.</code></p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Auto Delete Backup Log</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_auto_delete_backup_log" <?php echo esc_attr($auto_delete_backup_log); ?> />
                            <span><?php esc_html_e('Automatically delete corresponding logs when deleting backups'); ?></span>
                        </label>
                    </p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Large Database Mode</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_backup_database_use_primary_key" <?php esc_attr_e($backup_database_use_primary_key); ?> />
                            <span><?php esc_html_e('This mode optimizes backup process for high-volume data. You can try to enable it if backups fail or time out due to a very large database'); ?></span>
                        </label>
                    </p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">Large Uploads Mode</label>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_backup_upload_use_cm_store" <?php esc_attr_e($backup_upload_use_cm_store); ?> />
                            <span><?php esc_html_e('This mode improves backup performance for extensive media libraries. You can try to enable it if backups are slow or fail because of a large number of files in the uploads folder. This mode only supports ZipArchive, it will not work with PclZip'); ?></span>
                        </label>
                    </p>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Database access method</label></td>
                <td>
                    <div>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_db_connect_method_addon" value="wpdb" <?php echo esc_attr($db_method_wpdb); ?> />
                                <span><strong><?php esc_html_e('WPDB'); ?></strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>WPDB option has a better compatibility, but the speed of backup and restore is slower.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_db_connect_method_addon" value="pdo" <?php echo esc_attr($db_method_pdo); ?> />
                                <span><strong><?php esc_html_e('PDO'); ?></strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>It is recommended to choose PDO option if pdo_mysql extension is installed on your server, which lets you backup and restore your site faster.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Backup compression method</label></td>
                <td>
                    <div>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_zip_method_addon" value="ziparchive" <?php echo esc_attr($zip_method_archive); ?> />
                                <span><strong><?php esc_html_e('ZipArchive'); ?></strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>ZipArchive has a better flexibility which provides a higher backup success rate and speed. It is also the default zip method WPvivid pro uses. Using this method requires the ZIP extension to be installed within your PHP.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_zip_method_addon" value="pclzip" <?php echo esc_attr($zip_method_pclzip); ?> />
                                <span><strong><?php esc_html_e('PclZip'); ?></strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>PclZip is a much slower but more stable zip method that is included in every WordPress install. WPvivid will automatically switch to PclZip if the ZIP extension is not installed within your PHP.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Parameters of backups/restore</label></td>
                <td>
                    <div>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_backup_params_addon" value="low" <?php esc_attr_e($backup_params_low); ?> />
                                <span><strong>Low (Balanced)</strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Low (Balanced): Use this default setting for minimal server resource usage, but expect longer backup times. Best for shared hosting or limited resources. Backups are split into 200MB chunks.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_backup_params_addon" value="mid" <?php esc_attr_e($backup_params_mid); ?> />
                                <span><strong>Mid (Standard)</strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Mid (Standard): This mode offers a good balance between backup speed and resource usage. It's suitable for most web hosting environments.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_backup_params_addon" value="high" <?php esc_attr_e($backup_params_high); ?> />
                                <span><strong>High (Accelerated)</strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>High (Accelerated): This mode uses more server resources to reduce backup time, but is only recommended for dedicated servers. If backups time out or get stuck, consider Mid or Low mode. Backups are split into 4GB chunks.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_backup_params_addon" value="custom" <?php esc_attr_e($backup_params_custom); ?> />
                                <span><strong>Custom (Advanced)</strong></span>
                                <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                    <div class="mwp-wpvivid-bottom">
                                        <!-- The content you need -->
                                        <p>Custom (Advanced): This mode allows fine-tuning of backup parameters. Incorrect configuration can lead to backup failures. It is recommended to use only with specific guidance from our support team.</p>
                                        <i></i> <!-- do not delete this line -->
                                    </div>
                                </span>
                            </label>
                        </fieldset>
                    </div>
                    <p></p>
                    <div id="mwp_wpvivid_custom_backup_params_addon" style="<?php esc_attr_e($backup_custom_setting_display); ?>">
                        <div>
                            <span><input type="text" placeholder="<?php echo esc_attr($compress_file_count); ?>" option="mwp-setting-addon" name="mwp_compress_file_count_addon" id="compress_file_count" class="all-options" value="<?php echo esc_attr($compress_file_count); ?>" onkeyup="value=value.replace(/\D/g,'')"></span><span>The number of files compressed to the backup zip each time</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>When taking a backup, the plugin will compress this number of files to the backup zip each time. The default value is 500. The lower the value, the longer time the backup will take, but the higher the backup success rate. If you encounter a backup timeout issue, try to decrease this value.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="200" option="mwp-setting-addon" name="mwp_max_file_size_addon" id="wpvivid_max_zip" class="all-options" value="<?php echo esc_attr(str_replace('M', '', $max_file_size)); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, split a backup every this size</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Some web hosting providers limit large zip files (e.g. 200MB), and therefore splitting your backup into many parts is an ideal way to avoid hitting the limitation if you are running a big website. Please try to adjust the value if you are encountering backup errors. When you set a value of 0MB, backups will be split every 4GB.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="1000" option="mwp-setting-addon" name="mwp_max_backup_table_addon" class="all-options" value="<?php echo esc_attr($max_backup_table); ?>" onkeyup="value=value.replace(/\D/g,'')">The number of database tables compressed to each zip</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>When taking a database backup, the plugin will compress this number of tables to each backup zip. The default value is 1000 which works for most websites.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="200" option="mwp-setting-addon" name="mwp_max_sql_file_size_addon" class="all-options" value="<?php echo esc_attr(str_replace('M', '', $max_sql_file_size)); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, split a sql file every this size</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Some web hosting providers limit large files (e.g. 200MB), and therefore splitting your sql files into many parts is an ideal way to avoid hitting the limitation if you are running a big website. Please try to adjust the value if you are encountering backup errors. If you use a value of 0 MB, any sql files won't be split.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="0" option="mwp-setting-addon" name="mwp_exclude_file_size_addon" id="wpvivid_ignore_large" class="all-options" value="<?php echo esc_attr($exclude_file_size); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, exclude files larger than this size</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Using the option will ignore the file larger than the certain size in MB when backing up, '0' (zero) means unlimited.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="900" option="mwp-setting-addon" name="mwp_max_execution_time_addon" id="wpvivid_option_timeout" class="all-options" value="<?php echo esc_attr($max_execution_time); ?>" onkeyup="value=value.replace(/\D/g,'')">Seconds</span><span>, maximum PHP script execution time for a backup task</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut the process of backup down. If the progress of backup encounters a time-out, that means you have a medium or large sized website, please try to scale the value bigger.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="256" option="mwp-setting-addon" name="mwp_memory_limit_addon" class="all-options" value="<?php echo esc_attr(str_replace('M', '', $memory_limit)); ?>" onkeyup="value=value.replace(/\D/g,'')">MB</span><span>, maximum PHP memory for a backup task</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Adjust this value to apply for a temporary PHP memory limit for WPvivid backup plugin to run a backup. We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting providers may not support this.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                        <p></p>
                        <div>
                            <span><input type="text" placeholder="2048" option="mwp-setting-addon" name="mwp_migrate_size_addon" class="all-options" value="<?php echo esc_attr($migrate_size); ?>" onkeyup="value=value.replace(/\D/g,'')">KB</span><span>, chunk size</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>e.g.  if you choose a chunk size of 2MB, a 8MB file will use 4 chunks. Decreasing this value will break the ISP's transmission limit, for example:512KB</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }

    public function output_unused_image_setting_addon($global){
        $scan_limit=isset($this->setting_addon['wpvivid_uc_scan_limit'])?$this->setting_addon['wpvivid_uc_scan_limit']:20;
        $files_limit=isset($this->setting_addon['wpvivid_uc_files_limit'])?$this->setting_addon['wpvivid_uc_files_limit']:100;

        $quick_scan=isset($this->setting_addon['wpvivid_uc_quick_scan'])?$this->setting_addon['wpvivid_uc_quick_scan']:false;

        if($quick_scan)
        {
            $quick_scan='checked';
        }
        else
        {
            $quick_scan='';
        }

        $delete_media_when_delete_file=isset($this->setting_addon['wpvivid_uc_delete_media_when_delete_file'])?$this->setting_addon['wpvivid_uc_delete_media_when_delete_file']:false;

        if($delete_media_when_delete_file)
        {
            $delete_media_when_delete_file='checked';
        }
        else
        {
            $delete_media_when_delete_file='';
        }

        $ignore_webp=isset($this->setting_addon['wpvivid_uc_ignore_webp'])?$this->setting_addon['wpvivid_uc_ignore_webp']:false;

        if($ignore_webp)
        {
            $ignore_webp='checked';
        }
        else
        {
            $ignore_webp='';
        }
        ?>
        <div style="margin-top: 10px;"></div>
        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">General</label></td>
                <td>
                    <p></p>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" id="wpvivid_uc_quick_scan" option="mwp-setting-addon" name="mwp_wpvivid_uc_quick_scan_addon" <?php echo esc_attr($quick_scan); ?> />
                            <span>Enable Quick Scan</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Checking this option will speed up your scans but may produce lower accuracy.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </label>
                    </div>

                    <p></p>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" id="wpvivid_uc_delete_media_when_delete_file" option="mwp-setting-addon" name="mwp_wpvivid_uc_delete_media_when_delete_file_addon" <?php echo esc_attr($delete_media_when_delete_file); ?> />
                            <span>Delete Unused Image URL in Database</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>With this option checked, when the image is deleted, the corresponding image url in the database that is not used anywhere on your website will also be deleted.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </label>
                    </div>

                    <p></p>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" id="wpvivid_uc_ignore_webp" option="mwp-setting-addon" name="mwp_wpvivid_uc_ignore_webp_addon" <?php echo esc_attr($ignore_webp); ?> />
                            <span>Ignore webp files</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Do not scan webp files.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </label>
                    </div>

                    <p></p>
                    <div>
                        <input type="text" placeholder="20" id="wpvivid_uc_scan_limit" option="mwp-setting-addon" name="mwp_wpvivid_uc_scan_limit_addon" value="<?php echo esc_attr($scan_limit); ?>" onkeyup="value=value.replace(/\D/g,'')" />
                        <span>Posts Quantity Processed Per Request</span>
                        <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>Set how many posts to process per request. The value should be set depending on your server performance and the recommended value is 20.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                    </div>

                    <p></p>
                    <div><input type="text" placeholder="100" id="wpvivid_uc_files_limit" option="mwp-setting-addon" name="mwp_wpvivid_uc_files_limit_addon" value="<?php echo esc_attr($files_limit); ?>" onkeyup="value=value.replace(/\D/g,'')" />
                        <span>Media Files Quantity Processed Per Request</span>
                        <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>Set how many media files to process per request. The value should be set depending on your server performance and the recommended value is 100.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }

    public function output_image_optimization_setting_addon($global){
        if(isset($this->setting_addon['wpvivid_optimization_options']))
        {
            $options = $this->setting_addon['wpvivid_optimization_options'];
        }
        else
        {
            $options = array();
        }

        if(isset($options['webp'])&&is_array($options['webp']))
        {
            $convert=$options['webp']['convert'];
            $display_enable=$options['webp']['display_enable'];
            $display=$options['webp']['display'];
            $gif_webp_convert=isset($options['webp']['gif_convert'])?$options['webp']['gif_convert']:false;
        }
        else
        {
            $convert='';
            $display_enable='';
            $display='pic';
            $gif_webp_convert='';
        }

        if($convert)
        {
            $convert='checked';
        }

        if($gif_webp_convert)
        {
            $gif_webp_convert='checked';
        }

        $keep_exif=isset($options['keep_exif'])?$options['keep_exif']:true;

        if($keep_exif)
        {
            $keep_exif='checked';
        }

        $quality=isset($options['quality'])?$options['quality']:'lossless';
        $custom_quality=isset($options['custom_quality'])?$options['custom_quality']:80;
        $custom_quality=min(99,$custom_quality);
        $custom_quality=max(1,$custom_quality);
        if($quality=='lossless')
        {
            $lossless='checked';
            $lossy='';
            $super='';
            $custom='';
            $custom_css='style="display:none"';
        }
        else if($quality=='lossy')
        {
            $lossy='checked';
            $lossless='';
            $super='';
            $custom='';
            $custom_css='style="display:none"';
        }
        else if($quality=='super')
        {
            $lossy='';
            $lossless='';
            $super='checked';
            $custom='';
            $custom_css='style="display:none"';
        }
        else
        {
            $lossy='';
            $lossless='';
            $super='';
            $custom='checked';
            $custom_css='';
        }

        $optimize_gif_color=isset($options['optimize_gif_color'])?$options['optimize_gif_color']:false;
        $gif_colors=isset($options['gif_colors'])?$options['gif_colors']:64;

        if($optimize_gif_color)
        {
            $optimize_gif_color='checked';
        }

        if($display_enable)
        {
            $display_enable='checked';

            if($display=='pic')
            {
                $display_pic='checked';
                $display_rewrite='';
            }
            else
            {
                $display_pic='';
                $display_rewrite='checked';
            }
        }
        else
        {
            $display_pic='checked';
            $display_rewrite='';
        }

        if(isset($options['resize']))
        {
            $resize=$options['resize']['enable'];
            $resize_width=$options['resize']['width'];
            $resize_height=$options['resize']['height'];
        }
        else
        {
            $resize=true;
            $resize_width=2560;
            $resize_height=2560;
        }

        if($resize)
        {
            $resize='checked';
        }

        if(!isset($options['skip_size']))
        {
            $options['skip_size']=array();
        }

        global $_wp_additional_image_sizes;
        $intermediate_image_sizes = get_intermediate_image_sizes();
        $image_sizes=array();
        $image_sizes[ 'og' ]['skip']=isset($options['skip_size']['og'])?$options['skip_size']['og']:false;

        foreach ( $intermediate_image_sizes as $size_key )
        {
            if ( in_array( $size_key, array( 'thumbnail', 'medium', 'large' ), true ) )
            {
                $image_sizes[ $size_key ]['width']  = get_option( $size_key . '_size_w' );
                $image_sizes[ $size_key ]['height'] = get_option( $size_key . '_size_h' );
                $image_sizes[ $size_key ]['crop']   = (bool) get_option( $size_key . '_crop' );
                if(isset($options['skip_size'][$size_key])&&$options['skip_size'][$size_key])
                {
                    $image_sizes[ $size_key ]['skip']=true;
                }
                else
                {
                    $image_sizes[ $size_key ]['skip']=false;
                }
            }
            else if ( isset( $_wp_additional_image_sizes[ $size_key ] ) )
            {
                $image_sizes[ $size_key ] = array(
                    'width'  => $_wp_additional_image_sizes[ $size_key ]['width'],
                    'height' => $_wp_additional_image_sizes[ $size_key ]['height'],
                    'crop'   => $_wp_additional_image_sizes[ $size_key ]['crop'],
                );
                if(isset($options['skip_size'][$size_key])&&$options['skip_size'][$size_key])
                {
                    $image_sizes[ $size_key ]['skip']=true;
                }
                else
                {
                    $image_sizes[ $size_key ]['skip']=false;
                }
            }
        }

        if ( ! isset( $sizes['medium_large'] ) || empty( $sizes['medium_large'] ) )
        {
            $width  = intval( get_option( 'medium_large_size_w' ) );
            $height = intval( get_option( 'medium_large_size_h' ) );

            $image_sizes['medium_large'] = array(
                'width'  => $width,
                'height' => $height,
            );

            if(isset($options['skip_size']['medium_large'])&&$options['skip_size']['medium_large'])
            {
                $image_sizes[ 'medium_large' ]['skip']=true;
            }
            else
            {
                $image_sizes[ 'medium_large' ]['skip']=false;
            }
        }

        $auto_optimize_type=isset($options['auto_optimize_type'])?$options['auto_optimize_type']:'upload';
        if($auto_optimize_type=='upload')
        {
            $is_auto='checked';
            $is_auto_schedule='';
            $is_no_auto='';
            $auto_schedule_text='The schedule is disabled.';
        }
        else if($auto_optimize_type=='nooptimize')
        {
            $is_auto='';
            $is_auto_schedule='';
            $is_no_auto='checked';
        }
        else{
            $is_auto='';
            $is_auto_schedule='checked';
            $is_no_auto='';
            $auto_schedule_text='The schedule is enabled.';
        }

        $auto_schedule_cycles=isset($options['auto_schedule_cycles'])?$options['auto_schedule_cycles']:'wpvivid_5minutes';

        $backup=isset($options['backup'])?$options['backup']:true;

        if($backup)
        {
            $backup='checked';
        }
        else
        {
            $backup='';
        }

        $backup_path=isset($options['backup_path'])?$options['backup_path']:'wpvivid_image_optimization';

        $backup_path_placeholder='.../wp-content/'.$backup_path;
        $backup_path_prefix='.../wp-content/';

        $enable_exclude_file=isset($options['enable_exclude_file'])?$options['enable_exclude_file']:true;
        if($enable_exclude_file)
        {
            $enable_exclude_file='checked';
        }
        else
        {
            $enable_exclude_file='';
        }
        $exclude_file=isset($options['exclude_file'])?$options['exclude_file']:'';
        $enable_exclude_path=isset($options['enable_exclude_path'])?$options['enable_exclude_path']:true;
        if($enable_exclude_path)
        {
            $enable_exclude_path='checked';
        }
        else
        {
            $enable_exclude_path='';
        }
        $exclude_path=isset($options['exclude_path'])?$options['exclude_path']:'';

        $region=isset($options['region'])?$options['region']:'us2';
        if($region=='us1')
        {
            $selected='us2';
        }
        else if($region=='us2')
        {
            $selected='us2';
        }
        else if($region=='eu1')
        {
            $selected='eu1';
        }
        else
        {
            $selected='us2';
        }

        $optimize_type = isset($options['optimize_type']) ? $options['optimize_type'] : 'media_library';
        $folders = isset($options['custom_folders']) ? $options['custom_folders'] : '';
        if($optimize_type=='media_library')
        {
            $media_library='checked';
            $custom_folders='';
        }
        else
        {
            $media_library='';
            $custom_folders='checked';
        }

        $memory_limit=isset($options['image_optimization_memory_limit'])?$options['image_optimization_memory_limit']:256;

        $memory_limit=max(256,intval($memory_limit));


        $gif=isset($options['opt_gif'])?$options['opt_gif']:true;

        if($gif)
        {
            $gif='checked';
        }
        else
        {
            $gif='';
        }

        $max_allowed_optimize_count=isset($options['max_allowed_optimize_count'])?$options['max_allowed_optimize_count']:15;

        ?>
        <div style="margin-top: 10px;"></div>
        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Cloud Servers</label></td>
                <td>
                    <div>
                        <span>
                            <select option="mwp-setting-addon" name="mwp_region_addon">
                                <option value="us2">North American - Pro</option>
                                <option value="eu1">Europe - Pro</option>
                            </select>
                        </span>
                        <p>Choosing the server closest to your website can speed up optimization process.</p>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Optimize images after uploading</label></td>
                <td>
                    <div>
                        <div>
                            <input type="radio" option="mwp-setting-addon" name="mwp_auto_optimize_type_addon" value="nooptimize" <?php echo esc_attr($is_no_auto); ?> />
                            <span><?php esc_html_e('Do not optimize', 'wpvivid-imgoptim'); ?></span>
                        </div>
                        <p></p>
                        <div>
                            <input type="radio" option="mwp-setting-addon" name="mwp_auto_optimize_type_addon" value="upload" <?php echo esc_attr($is_auto); ?> />
                            <span><?php esc_html_e('Optimize immediately','wpvivid-imgoptim')?></span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-bottom">
                                    <!-- The content you need -->
                                    <p>With the option checked, our plugin will optimize images immediately upon upload, but it won't optimize existing images. You have to click 'Optimize Now' button on the plugin's Image Bulk Optimization page to optimize the existing images.</p>
                                    <i></i>
                                    <!-- do not delete this line -->
                                </div>
                            </span>
                        </div>
                        <div>
                            <div style="margin-bottom:1em;">
                                <div>
                                    <p></p>
                                    <div>
                                        <input type="radio" option="mwp-setting-addon" name="mwp_auto_optimize_type_addon"  value="schedule" <?php echo esc_attr($is_auto_schedule); ?>>
                                        <span>Optimize in background (schedule)</span>
                                        <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                            <div class="mwp-wpvivid-bottom">
                                                <!-- The content you need -->
                                                <p>Set up a schedule to check and auto-optimize unoptimized images after uploading. This option is designed and recommended for servers with limited resources. For servers with sufficient resources, it is recommended to use the 'SIBO' option to optimize all images in 1-click.</p>
                                                <i></i> <!-- do not delete this line -->
                                            </div>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div style="padding-left: 1.5em;">
                                <p>
                                    <span class="dashicons dashicons-clock wpvivid-dashicons-green" style="padding-top:0.2em;"></span>
                                    <span>Schedule Cycles: </span>
                                    <span>Process unoptimized images every </span>
                                    <span>
                                        <select option="mwp-setting-addon" name="mwp_auto_schedule_cycles_addon">
                                            <option value="wpvivid_2minutes">2</option>
                                            <option value="wpvivid_3minutes">3</option>
                                            <option value="wpvivid_4minutes">4</option>
                                            <option value="wpvivid_5minutes">5</option>
                                            <option value="wpvivid_6minutes">6</option>
                                            <option value="wpvivid_7minutes">7</option>
                                            <option value="wpvivid_8minutes">8</option>
                                            <option value="wpvivid_9minutes">9</option>
                                            <option value="wpvivid_10minutes">10</option>
                                        </select>
                                    </span>
                                    <span> min.</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Customize what will be optimized</label></td>
                <td>
                    <div>
                        <div>
                            <p>
                                <input type="radio" option="mwp-setting-addon" name="mwp_optimize_type_addon" value="media_library" <?php echo esc_attr($media_library); ?> />
                                <span class="dashicons dashicons-format-gallery wpvivid-dashicons-blue" style="padding-top:0.2em;"></span>
                                <span><strong>Media Library</strong></span>
                            </p>
                        </div>
                        <div>
                            <div style="margin-top:1em;">
                                <div style="border-top:1px solid #eee;">
                                    <p></p>
                                    <div>
                                        <input type="radio" option="mwp-setting-addon" name="mwp_optimize_type_addon" value="custom_folders" <?php echo esc_attr($custom_folders); ?> />
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span><strong>Custom Folders</strong></span>
                                        <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                            <div class="mwp-wpvivid-bottom">
                                                    <!-- The content you need -->
                                                <p>Optimize the images under the custom folders entered.</p>
                                                <p>One folder path per line.</p>
                                                <i></i> <!-- do not delete this line -->
                                            </div>
                                        </span>
                                    </div>
                                    <p></p>
                                </div>
                            </div>
                            <div>
                                <textarea option="mwp-setting-addon" name="mwp_custom_folders_addon" style="width:100%; height:100px; text-align:left;" placeholder="Examples:
/custom-folder
/var/www/html/wp-content/custom-folder"><?php echo esc_textarea($folders); ?></textarea>
                                <p><span>Tip: the setting will effect both real-time and automatic optimization.</span></p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Compression mode</label></td>
                <td>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="mwp-setting-addon" name="mwp_quality_addon" value="lossless" <?php echo esc_attr($lossless); ?> />
                            <span><?php esc_html_e('Lossless')?></span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="mwp-setting-addon" name="mwp_quality_addon" value="lossy" <?php echo esc_attr($lossy); ?> />
                            <span><?php esc_html_e('Lossy')?></span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="mwp-setting-addon" name="mwp_quality_addon" value="super" <?php echo esc_attr($super); ?> />
                            <span><?php esc_html_e('Super')?></span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="mwp-setting-addon" name="mwp_quality_addon" value="custom" <?php echo esc_attr($custom); ?> />
                            <span><?php esc_html_e('Custom')?></span>
                        </label>
                    </fieldset>
                    <p></p>
                    <div id="mwp_wpvivid_imgoptim_custom_compress" <?php echo esc_attr($custom_css); ?> >
                        <input id="mwp_wpvivid_imgoptim_custom_compress_slider" type="range" value="<?php echo esc_attr($custom_quality) ?>" min="1" max="99"/>
                        <output id="mwp_wpvivid_imgoptim_custom_compress_output" ><?php echo esc_html($custom_quality) ?></output>
                        <input style="display: none" type="text" readonly option="mwp-setting-addon" name="mwp_custom_quality_addon" value="<?php echo esc_attr($custom_quality) ?>">
                    </div>
                    <div style="border:1px solid #eee; padding:0 1em 0 1em;margin:1em 0 1em 0;">
                        <p><span>Lossless: </span><span>Compress the image by up to 10%</span></p>
                        <p><span>Lossy: </span><span>Compress the image by up to 20%(conservatively)</span></p>
                        <p><span>Super: </span><span>Compress the image by up to 30-40%(optimistically)</span></p>
                        <p><span>Custom: </span><span>A lower value means a higher compression rate, but a reduction in image quality. The recommended value is 80</span></p>
                    </div>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_opt_gif_addon" <?php echo esc_attr($gif); ?>>
                            <span class="wpvivid-checkbox-checkmark"><?php esc_html_e('Compress GIF Images','wpvivid-imgoptim')?></span>
                        </label>
                    </div>
                    <p></p>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_keep_exif_addon" <?php echo esc_attr($keep_exif); ?>>
                            <span><?php esc_html_e('Leave EXIF data','wpvivid-imgoptim')?></span>
                        </label>
                    </div>
                    <p></p>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_optimize_gif_color_addon" <?php echo esc_attr($optimize_gif_color); ?>>
                            <span><?php esc_html_e('With the option checked, you can choose the number of colors in each GIF when it is being optimized, from 2-256. The lower the number, the smaller the GIF size, but it may result in image quality loss. Choose the one with the best size/quality ratio for your needs.The recommended value is 64.','wpvivid-imgoptim')?></span>
                        </label>
                        <p></p>
                        <select id="mwp_wpvivid_imgoptim_optimize_gif_colors" option="mwp-setting-addon" name="mwp_gif_colors_addon" style="margin-bottom: 3px;">
                            <option value="2">2 colors</option>
                            <option value="4">4 colors</option>
                            <option value="8">8 colors</option>
                            <option value="16">16 colors</option>
                            <option value="32">32 colors</option>
                            <option value="64">64 colors</option>
                            <option value="128">128 colors</option>
                            <option value="256">256 colors</option>
                        </select>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php esc_html_e('Resizing large images','wpvivid-imgoptim')?></label></td>
                <td>
                    <div>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox"  option="mwp-setting-addon" name="mwp_resize_addon" <?php echo esc_attr($resize); ?> />
                            <span><?php esc_html_e('Enable auto-resizing large images','wpvivid-imgoptim')?></span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                            <div class="mwp-wpvivid-bottom">
                                <!-- The content you need -->
                                <p>This option allows you to enter a width and height, so large images will be proportionately resized upon upload. For example, if you set 1280 px for the width, all large images will be resized in proportion to 1280 px in width upon upload.</p>
                                <i></i> <!-- do not delete this line -->
                            </div>
                        </span>
                        </label>
                    </div>
                    <p></p>
                    <label style="display: inline-block;min-width: 60px" for="mwp_wpvivid_resize_width">Width</label><input id="mwp_wpvivid_resize_width" placeholder="2560" type="text" option="mwp-setting-addon" name="mwp_resize_width_addon" value="<?php echo esc_attr($resize_width); ?>" onkeyup="value=value.replace(/\D/g,'')" /> px
                    <p></p>
                    <label style="display: inline-block;min-width: 60px" for="mwp_wpvivid_resize_height">Height</label><input id="mwp_wpvivid_resize_height" placeholder="2560" type="text" option="mwp-setting-addon" name="mwp_resize_height_addon" value="<?php echo esc_attr($resize_height); ?>" onkeyup="value=value.replace(/\D/g,'')" /> px
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php esc_html_e('Convert images','wpvivid-imgoptim')?></label></td>
                <td>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_convert_addon" <?php echo esc_attr($convert); ?>/>
                            <span><?php esc_html_e('Convert JPG and PNG to Webp','wpvivid-imgoptim')?></span>
                        </label>
                    </fieldset>
                    <p></p>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_gif_convert_addon" <?php echo esc_attr($gif_webp_convert); ?>/>
                            <span><?php esc_html_e('Convert GIF to Webp','wpvivid-imgoptim')?></span>
                        </label>
                    </fieldset>
                    <p></p>
                    <div class="mwp-wpvivid-one-coloum" style="border:1px solid #f1f1f1;">
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_display_enable_addon" <?php echo esc_attr($display_enable); ?>>
                            <span><?php esc_html_e('Enable Webp format on your site, ','wpvivid-imgoptim')?></span>
                            <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-convert-to-webp-notes.html" target="_blank">Learn more</a>
                        </label>
                        <p></p>
                        <fieldset>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_webp_display_addon" value="pic" <?php echo esc_attr($display_pic); ?> />
                                <span>Use <code>picture</code> tag (Does not support images in CSS)</span>
                            </label>
                            <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                                <input type="radio" option="mwp-setting-addon" name="mwp_webp_display_addon" value="rewrite" <?php echo esc_attr($display_rewrite); ?> />
                                <span>Use <code>rewrite</code> rule (Only supports Apache servers)</span>
                            </label>
                        </fieldset>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <span>Exclude images by folder/file path</span>
                </td>
                <td>
                    <label class="wpvivid-checkbox">
                        <input type="checkbox" option="mwp-setting-addon" name="mwp_enable_exclude_path_addon" <?php echo esc_attr($enable_exclude_path); ?> />
                        <span><?php esc_html_e('Exclude by directory path','wpvivid-imgoptim')?></span>
                    </label>
                    <p></p>
                    <textarea placeholder="Example:&#10;/wp-content/uploads/19/03/&#10;/wp-content/upload/19/04/" option="mwp-setting-addon" name="mwp_exclude_path_addon" style="width:100%; height:200px; overflow-x:auto;"><?php echo esc_textarea($exclude_path); ?></textarea>
                    <p>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-setting-addon" name="mwp_enable_exclude_file_addon" <?php echo esc_attr($enable_exclude_file); ?> />
                            <span><?php esc_html_e('Exclude by file path','wpvivid-imgoptim')?></span>
                        </label>
                    </p>
                    <textarea placeholder="Example:&#10;/wp-content/uploads/19/03/test1.png&#10;/wp-content/upload/19/03/test2.jpg" option="mwp-setting-addon" name="mwp_exclude_file_addon" style="width:100%; height:200px; overflow-x:auto;"><?php echo esc_textarea($exclude_file); ?></textarea>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php esc_html_e('Image backup','wpvivid-imgoptim')?></label></td>
                <td>
                    <label class="wpvivid-checkbox">
                        <input type="checkbox" option="mwp-setting-addon" name="mwp_image_backup_addon" <?php echo esc_attr($backup); ?> />
                        <span><?php esc_html_e('Enable image backup before optimization','wpvivid-imgoptim')?></span>
                    </label>
                    <p></p>
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                    <span><?php esc_html_e('Image backup folder','wpvivid-imgoptim')?>:</span>
                    <div id="mwp_wpvivid_image_custom_backup_path_placeholder">
                        <span><code><?php echo esc_html($backup_path_placeholder); ?></code></span>
                        <input id="mwp_wpvivid_image_custom_backup_path_placeholder_btn" type="button" class="ui green mini button" value="Change">
                    </div>
                    <div id="mwp_wpvivid_image_custom_backup_path" style="display: none">
                        <span><code><?php echo esc_html($backup_path_prefix); ?></code></span>
                        <input type="text" option="mwp-setting-addon" name="mwp_image_backup_path_addon" class="all-options" value="<?php echo esc_attr($backup_path); ?>" onkeyup="value=value.replace(/[^\a-\z\A-\Z0-9_]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" />
                    </div>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">
                        <?php esc_html_e('Max memory limit','wpvivid-imgoptim')?>
                    </label>
                </td>
                <td>
                    <input type="text" placeholder="256" option="mwp-setting-addon" name="mwp_image_optimization_memory_limit_addon" value="<?php echo esc_attr($memory_limit); ?>" onkeyup="value=value.replace(/\D/g,'')" /> M
                    <span style="margin-top: 4px" class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                        <div class="mwp-wpvivid-bottom">
                            <!-- The content you need -->
                            <p>The maximum PHP memory for image optimization. Try to increase the value if you encounter a memory exhausted error.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </td>
            </tr>

            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell">
                        <?php esc_html_e('Max optimized count','wpvivid')?>
                    </label>
                </td>
                <td>
                    <input type="text" placeholder="15" option="mwp-setting-addon" name="mwp_max_allowed_optimize_count_addon" value="<?php echo esc_attr($max_allowed_optimize_count); ?>" onkeyup="value=value.replace(/\D/g,'')" /> Image(s)
                </td>
            </tr>
        </table>

        <script>
            jQuery('#mwp_wpvivid_image_custom_backup_path_placeholder_btn').click(function()
            {
                jQuery('#mwp_wpvivid_image_custom_backup_path_placeholder').hide();
                jQuery('#mwp_wpvivid_image_custom_backup_path').show();
            });

            jQuery(document).ready(function($)
            {
                jQuery('select[option=mwp-setting-addon][name=mwp_region_addon]').val('<?php echo esc_js($selected); ?>');
            });
        </script>
        <?php
    }

    public function output_lazyload_setting_addon($global){
        if(isset($this->setting_addon['wpvivid_optimization_options']))
        {
            $options = $this->setting_addon['wpvivid_optimization_options'];
        }
        else
        {
            $options = array();
        }

        $options['lazyload']=isset($options['lazyload'])?$options['lazyload']:array();
        $enable=isset($options['lazyload']['enable'])?$options['lazyload']['enable']:false;
        if($enable)
        {
            $enable='checked';
        }
        else
        {
            $enable='';
        }


        if(isset($options['lazyload']['extensions']))
        {
            $jpg=array_key_exists('jpg|jpeg|jpe',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['jpg|jpeg|jpe']:true;
            $png=array_key_exists('png',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['png']:true;
            $gif=array_key_exists('gif',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['gif']:true;
            $svg=array_key_exists('svg',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['svg']:true;
            if($jpg)
                $jpg='checked';
            if($png)
                $png='checked';
            if($gif)
                $gif='checked';
            if($svg)
                $svg='checked';
        }
        else
        {
            $jpg='checked';
            $png='checked';
            $gif='checked';
            $svg='checked';
        }

        $content=isset($options['lazyload']['content'])?$options['lazyload']['content']:true;
        $thumbnails=isset($options['lazyload']['thumbnails'])?$options['lazyload']['thumbnails']:true;

        if($content)
            $content='checked';
        if($thumbnails)
            $thumbnails='checked';

        $js=isset($options['lazyload']['js'])?$options['lazyload']['js']:'footer';

        if($js=='footer')
        {
            $footer='checked';
            $header='';
        }
        else
        {
            $footer='';
            $header='checked';
        }

        $noscript=isset($options['lazyload']['noscript'])?$options['lazyload']['noscript']:true;

        if($noscript)
            $noscript='checked';

        $animation=isset($options['lazyload']['animation'])?$options['lazyload']['animation']:'fadein';
        if($animation=='fadein')
        {
            $fade_in='checked';
            $spinner='';
            $placeholder='';
        }
        else
        {
            $fade_in='checked';
            $spinner='';
            $placeholder='';
        }

        $enable_exclude_file=isset($options['lazyload']['enable_exclude_file'])?$options['lazyload']['enable_exclude_file']:true;
        if($enable_exclude_file)
        {
            $enable_exclude_file='checked';
        }
        else
        {
            $enable_exclude_file='';
        }
        $exclude_file=isset($options['lazyload']['exclude_file'])?$options['lazyload']['exclude_file']:'';

        ?>
        <div style="margin-top: 10px;"></div>
        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php esc_html_e('Enable/Disable lazyload', 'wpvivid-imgoptim'); ?></label>
                </td>
                <td>
                    <span>
                        <label class="mwp-wpvivid-switch">
                            <input type="checkbox" option="mwp-lazyload-addon" name="mwp_enable_addon" <?php echo esc_attr($enable); ?> >
                            <span class="mwp-wpvivid-slider mwp-wpvivid-round"></span>
                        </label>
                        <span>
                            <strong><?php esc_html_e('Enable lazyload', 'wpvivid-imgoptim'); ?></strong>
                        </span>
                        <?php esc_html_e('Once enabled, the plugin will delay loading images on your website site pages until visitors scroll down to them, hence speeding up your website pages loading time and improving your Google PageSpeed Insights score.', 'wpvivid-imgoptim'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php esc_html_e('Media type to lazyload', 'wpvivid-imgoptim'); ?></label>
                </td>
                <td>
                    <label class="wpvivid-checkbox">

                        <input type="checkbox" option="mwp-lazyload-addon" name="mwp_jpg_addon" <?php echo esc_attr($jpg); ?> />
                        <span>.jpg | .jpeg</span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">

                        <input type="checkbox" option="mwp-lazyload-addon" name="mwp_png_addon" <?php echo esc_attr($png); ?> />
                        <span>.png</span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">

                        <input type="checkbox" option="mwp-lazyload-addon" name="mwp_gif_addon" <?php echo esc_attr($gif); ?> />
                        <span>.gif</span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">

                        <input type="checkbox" option="mwp-lazyload-addon" name="mwp_svg_addon" <?php echo esc_attr($svg); ?> />
                        <span>.svg</span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php esc_html_e('Lazyload works on locations', 'wpvivid'); ?></label></td>
                <td>
                    <label class="wpvivid-checkbox">

                        <input type="checkbox" option="mwp-lazyload-addon" name="mwp_content_addon" <?php echo esc_attr($content); ?>>
                        <span>Content</span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">

                        <input type="checkbox" option="mwp-lazyload-addon" name="mwp_thumbnails_addon" <?php echo esc_attr($thumbnails); ?>>
                        <span>Thumbnails</span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php esc_html_e('Browsers compatibility', 'wpvivid'); ?></label></td>
                <td>
                    <div>
                        <label class="wpvivid-checkbox">

                            <input type="checkbox" option="mwp-lazyload-addon" name="mwp_noscript_addon" <?php echo esc_attr($noscript); ?> />
                            <span>Use <code>noscript</code> tag</span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php esc_html_e('Location where scripts insert', 'wpvivid-imgoptim'); ?></label>
                </td>
                <td>
                    <p><?php esc_html_e('The', 'wpvivid-imgoptim'); ?> <code>wp_header()</code> <?php esc_html_e('and', 'wpvivid-imgoptim'); ?> <code>wp_footer()</code> <?php esc_html_e('function are required for your theme', 'wpvivid-imgoptim'); ?></p>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="mwp-lazyload-addon" name="mwp_js_addon" value="footer" <?php echo esc_attr($footer); ?> />
                            <span>footer</span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="mwp-lazyload-addon" name="mwp_js_addon" value="header" <?php echo esc_attr($header); ?> />
                            <span>header</span>
                        </label>
                    </fieldset>
                    <p><?php esc_html_e('The plugin will load it’s scripts in the footer by default to speed up page loading times. Switch to the header option if you have problems', 'wpvivid-imgoptim'); ?>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php esc_html_e('Animation', 'wpvivid'); ?></label>
                </td>
                <td>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio"option="mwp-lazyload-addon" name="mwp_lazyload_display_addon" value="fadein" <?php echo esc_attr($fade_in); ?> />
                            <span><?php esc_html_e('Fade in', 'wpvivid'); ?></span>
                        </label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <span>Exclude images from lazy loading</span>
                </td>
                <td>
                    <p>
                        <label class="wpvivid-checkbox">
                            <input type="checkbox" option="mwp-lazyload-addon" name="mwp_enable_exclude_file_addon" <?php echo esc_attr($enable_exclude_file); ?> />
                            <span><?php esc_html_e('Exclude by file path','wpvivid-imgoptim')?></span>
                        </label>
                    </p>
                    <textarea placeholder="Example:&#10;test1.png&#10;test2.jpg" option="mwp-lazyload-addon" name="mwp_exclude_file_addon" style="width:100%; height:200px; overflow-x:auto;"><?php echo esc_textarea($exclude_file); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }

    public function output_cdn_settings_addon($global){
        if(isset($this->setting_addon['wpvivid_optimization_options']))
        {
            $options = $this->setting_addon['wpvivid_optimization_options'];
        }
        else
        {
            $options = array();
        }

        $options['cdn']=isset($options['cdn'])?$options['cdn']:array();

        $enable=isset($options['cdn']['enable'])?$options['cdn']['enable']:false;
        if($enable)
        {
            $enable='checked';
        }
        else
        {
            $enable='';
        }

        $cdn_url=isset($options['cdn']['cdn_url'])?$options['cdn']['cdn_url']:get_site_url();

        //$cdn_og_url=isset($options['cdn']['cdn_og_url'])?$options['cdn']['cdn_og_url']:get_option('home');

        $include_dir=isset($options['cdn']['include_dir'])?$options['cdn']['include_dir']:'wp-content,wp-includes';

        $exclusions=isset($options['cdn']['exclusions'])?$options['cdn']['exclusions']:'.php,.js,.css';

        $relative_path=isset($options['cdn']['relative_path'])?$options['cdn']['relative_path']:true;
        if($relative_path)
        {
            $relative_path='checked';
        }
        else
        {
            $relative_path='';
        }

        $cdn_https=isset($options['cdn']['cdn_https'])?$options['cdn']['cdn_https']:false;
        if($cdn_https)
        {
            $cdn_https='checked';
        }
        else
        {
            $cdn_https='';
        }

        ?>
        <div style="margin-top: 10px;">
            <div>
                <label class="mwp-wpvivid-switch">
                    <input type="checkbox" option="mwp-cdn-addon" name="mwp_enable_addon" <?php echo esc_attr($enable); ?>>
                    <span class="mwp-wpvivid-slider mwp-wpvivid-round"></span>
                </label> <span>Enable CDN to deliver your content.</span>
            </div>
            <div style="margin:1em 0 1em 0;">
                <div style="border:1px solid #f1f1f1; margin-bottom:1em;" >
                    <div>
                        <div style="padding-left:1em; margin-top: 1em; margin-bottom: 1em;">
                            <p>
                                <span class="dashicons dashicons-admin-generic wpvivid-dashicons-green"></span><span>
                                    <strong>CDN Settings</strong>
                                </span>
                            </p>
                        </div>
                        <div class="mwp-wpvivid-two-col" style="padding-left:1em;">
                            <div style="border-left:4px solid #eee;padding-left:0.5em;padding-right:1em; margin-top: 1em; margin-bottom: 1em;">
                                <p>Please enter <code>CDN Url</code> (without trailing '/') to deliver your content via CDN service. </p>
                                <p>
                                    <input type="text" option="mwp-cdn-addon" name="mwp_cdn_url_addon" value="<?php echo esc_attr($cdn_url); ?>" placeholder="CDN Url,example:http://exampleCDN.com" style="width:100%;border:1px solid #aaa;">
                                </p>
                            </div>

                            <div style="border-left:4px solid #eee;padding-left:0.5em; margin-top: 1em; margin-bottom: 1em;">
                                <p>
                                    <span><strong>Relative Path &  CDN Https</strong></span>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" option="mwp-cdn-addon" name="mwp_relative_path_addon" <?php echo esc_attr($relative_path); ?> /><span>Enable CDN for relative path.</span>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" option="mwp-cdn-addon" name="mwp_cdn_https_addon" <?php echo esc_attr($cdn_https); ?> /><span>Enable CDN for https connections.</span>
                                    </label>
                                </p>
                            </div>
                        </div>
                        <div class="mwp-wpvivid-two-col" style="padding-left:1em;">
                            <div style="border-left:4px solid #eee;padding-left:0.5em;padding-right:1em; margin-top: 1em; margin-bottom: 1em;">
                                <p><span><strong>Included Directories</span></strong></p>
                                <p>Assets under the directories will be pointed to your CDN url. Separate directories by comma (,) .</p>
                                <p>
                                    <input type="text" placeholder="wp-contents,wp-includes" style="width:100%;border:1px solid #aaa;" option="mwp-cdn-addon" name="mwp_include_dir_addon" value="<?php echo esc_attr($include_dir); ?>">
                                </p>

                            </div>
                            <div style="border-left:4px solid #eee; padding-left:0.5em;padding-right:0.5em; margin-top: 1em; margin-bottom: 1em;">
                                <p><span><strong>Excluded Extension/Directories</span></strong></p>
                                <p>Enter the exclusions (extension and directories) separated by comma (,) .
                                <p><input type="text" placeholder=".php" style="width:100%;border:1px solid #aaa;" option="mwp-cdn-addon" name="mwp_exclusions_addon" value="<?php echo esc_attr($exclusions); ?>"></p>
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function output_staging_setting_addon($global){
        $wpvivid_staging_db_insert_count = isset($this->setting_addon['wpvivid_staging_options']['staging_db_insert_count']) ? $this->setting_addon['wpvivid_staging_options']['staging_db_insert_count'] : 10000;
        $wpvivid_staging_db_replace_count = isset($this->setting_addon['wpvivid_staging_options']['staging_db_replace_count']) ? $this->setting_addon['wpvivid_staging_options']['staging_db_replace_count'] : 5000;
        $wpvivid_staging_file_copy_count = isset($this->setting_addon['wpvivid_staging_options']['staging_file_copy_count']) ? $this->setting_addon['wpvivid_staging_options']['staging_file_copy_count'] : 500;
        $wpvivid_staging_exclude_file_size = isset($this->setting_addon['wpvivid_staging_options']['staging_exclude_file_size']) ? $this->setting_addon['wpvivid_staging_options']['staging_exclude_file_size'] : 30;
        $wpvivid_staging_memory_limit = isset($this->setting_addon['wpvivid_staging_options']['staging_memory_limit']) ? $this->setting_addon['wpvivid_staging_options']['staging_memory_limit'] : '256M';
        $wpvivid_staging_memory_limit = str_replace('M', '', $wpvivid_staging_memory_limit);
        $wpvivid_staging_max_execution_time = isset($this->setting_addon['wpvivid_staging_options']['staging_max_execution_time']) ? $this->setting_addon['wpvivid_staging_options']['staging_max_execution_time'] : 900;
        $wpvivid_staging_resume_count = isset($this->setting_addon['wpvivid_staging_options']['staging_resume_count']) ? $this->setting_addon['wpvivid_staging_options']['staging_resume_count'] : '6';

        $staging_request_timeout = isset($this->setting_addon['wpvivid_staging_options']['staging_request_timeout']) ? $this->setting_addon['wpvivid_staging_options']['staging_request_timeout'] : '1500';

        $staging_keep_setting = isset($this->setting_addon['wpvivid_staging_options']['staging_keep_setting']) ? $this->setting_addon['wpvivid_staging_options']['staging_keep_setting'] : true;
        if($staging_keep_setting)
        {
            $staging_keep_setting='checked';
        }
        else
        {
            $staging_keep_setting='';
        }

        $staging_not_need_login=isset($this->setting_addon['wpvivid_staging_options']['not_need_login']) ? $this->setting_addon['wpvivid_staging_options']['not_need_login'] : true;
        if($staging_not_need_login)
        {
            $staging_not_need_login_check='checked';
        }
        else
        {
            $staging_not_need_login_check='';
        }

        $staging_overwrite_permalink = isset($this->setting_addon['wpvivid_staging_options']['staging_overwrite_permalink']) ? $this->setting_addon['wpvivid_staging_options']['staging_overwrite_permalink'] : true;
        if($staging_overwrite_permalink){
            $staging_overwrite_permalink_check = 'checked';
        }
        else{
            $staging_overwrite_permalink_check = '';
        }

        ?>
        <div style="margin-top: 10px;">
            <div class="postbox mwp-wpvivid-setting-block mwp-wpvivid-block-bottom-space">
                <div style="margin-bottom: 20px;"><strong><?php esc_html_e('Staging Settings'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('DB Copy Count'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_db_insert_count_addon" value="<?php echo esc_attr($wpvivid_staging_db_insert_count); ?>"
                           placeholder="10000" onkeyup="value=value.replace(/\D/g,'')" />
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e('Number of DB rows, that are copied within one ajax query. The higher value makes the database copy process faster.
                Please try a high value to find out the highest possible value. If you encounter timeout errors, try lower values until no
                more errors occur.'); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('DB Replace Count'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_db_replace_count_addon" value="<?php echo esc_attr($wpvivid_staging_db_replace_count); ?>"
                           placeholder="5000" onkeyup="value=value.replace(/\D/g,'')" />
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e('Number of DB rows, that are processed within one ajax query. The higher value makes the DB replacement process faster. 
                If timeout erros occur, decrease the value because this process consumes a lot of memory.'); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('File Copy Count'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_file_copy_count_addon" value="<?php echo esc_attr($wpvivid_staging_file_copy_count); ?>"
                           placeholder="500" onkeyup="value=value.replace(/\D/g,'')" />
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e('Number of files to copy that will be copied within one ajax request. The higher value makes the file file copy process 
                faster. Please try a high value to find out the highest possible value. If you encounter timeout errors, try lower values until 
                no more errors occur.'); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Max File Size'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_exclude_file_size_addon" value="<?php echo esc_attr($wpvivid_staging_exclude_file_size); ?>"
                           placeholder="30" onkeyup="value=value.replace(/\D/g,'')" />
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e('Maximum size of the files copied to a staging site. All files larger than this value will be ignored. If you set the value
                 of 0 MB, all files will be copied to a staging site.'); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Staging Memory Limit'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_memory_limit_addon" value="<?php echo esc_attr($wpvivid_staging_memory_limit); ?>"
                           placeholder="256" onkeyup="value=value.replace(/\D/g,'')" />MB
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e('Adjust this value to apply for a temporary PHP memory limit for WPvivid backup plugin while creating a staging site.
                We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting
                providers may not support this.'); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('PHP script execution timeout'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_max_execution_time_addon" value="<?php echo esc_attr($wpvivid_staging_max_execution_time); ?>"
                           placeholder="900" onkeyup="value=value.replace(/\D/g,'')" />
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e('The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut down the progress of 
                creating a staging site. If the progress  encounters a time-out, that means you have a medium or large sized website. Please try to
                scale the value bigger.'); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Delay Between Requests', 'wpvivid'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <input type="text" class="all-options" option="mwp-setting-addon" name="mwp_staging_request_timeout_addon" value="<?php echo esc_attr($staging_request_timeout); ?>"
                           placeholder="1500" onkeyup="value=value.replace(/\D/g,'')" />ms
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php esc_html_e( 'A lower value will help speed up the process of creating a staging site. However, if your server has a limit on the number of requests, a higher value is recommended.', 'wpvivid' ); ?>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <strong>Retrying </strong>
                    <select option="mwp-setting-addon" name="mwp_staging_resume_count_addon">
                        <?php
                        for($resume_count=3; $resume_count<10; $resume_count++){
                            if($resume_count === $wpvivid_staging_resume_count){
                                echo '<option selected="selected" value="'.esc_attr($resume_count).'">'.esc_html($resume_count).'</option>';
                            }
                            else{
                                echo '<option value="'.esc_attr($resume_count).'">'.esc_html($resume_count).'</option>';
                            }
                        }
                        ?>
                    </select><strong><?php esc_html_e(' times when encountering a time-out error', 'wpvivid'); ?></strong>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting-addon" name="mwp_not_need_login_addon" <?php echo esc_attr($staging_not_need_login_check); ?> />
                        <span><strong><?php esc_html_e('Anyone can visit the staging site', 'wpvivid'); ?></strong></span>
                    </label>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <span>When the option is checked, anyone will be able to visit the staging site without the need to login. Uncheck it to request a login to visit the staging site.</span>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting-addon" name="mwp_staging_overwrite_permalink_addon" <?php echo esc_attr($staging_overwrite_permalink_check); ?> />
                        <span><strong><?php esc_html_e('Keep permalink when transferring website', 'wpvivid'); ?></strong></span>
                    </label>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <span>When checked, this option allows you to keep the current permalink structure when you create a staging site or push a staging site to live.</span>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting-addon" name="mwp_staging_keep_setting_addon" <?php echo esc_attr($staging_keep_setting); ?> />
                        <span><strong><?php esc_html_e('Keep staging sites when deleting the plugin', 'wpvivid'); ?></strong></span>
                    </label>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <span>With this option checked, all staging sites you have created will be retained when the plugin is deleted, just in case you still need them later. The sites will show up again after the plugin is reinstalled.</span>
                </div>
            </div>
        </div>
        <?php
    }

    public function output_rollback_setting_addon($global)
    {
        $max_plugins_count = isset($this->setting_addon['wpvivid_max_rollback_count']['max_plugins_count']) ? $this->setting_addon['wpvivid_max_rollback_count']['max_plugins_count'] : 5;
        $max_themes_count = isset($this->setting_addon['wpvivid_max_rollback_count']['max_themes_count']) ? $this->setting_addon['wpvivid_max_rollback_count']['max_themes_count'] : 5;
        $max_core_count = isset($this->setting_addon['wpvivid_max_rollback_count']['max_core_count']) ? $this->setting_addon['wpvivid_max_rollback_count']['max_core_count'] : 5;
        $auto_backup_db_before_update = isset($this->setting_addon['wpvivid_auto_backup_db_before_update']) ? $this->setting_addon['wpvivid_auto_backup_db_before_update'] : false;
        if($auto_backup_db_before_update)
        {
            $auto_backup_db_before_update=' checked';
            $auto_backup_db_setting_display='';
        }
        else
        {
            $auto_backup_db_before_update="";
            $auto_backup_db_setting_display='display: none;';
        }

        $rollback_max_backup_count=isset($this->setting_addon['wpvivid_common_setting']['rollback_max_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['rollback_max_backup_count'] : 30;
        $rollback_max_backup_count=intval($rollback_max_backup_count);
        $rollback_max_remote_backup_count=isset($this->setting_addon['wpvivid_common_setting']['rollback_max_remote_backup_count']) ? $this->setting_addon['wpvivid_common_setting']['rollback_max_remote_backup_count'] : 30;
        $rollback_max_remote_backup_count=intval($rollback_max_remote_backup_count);

        $rollback_remote = isset($this->setting_addon['wpvivid_rollback_remote']) ? $this->setting_addon['wpvivid_rollback_remote'] : 0;
        $rollback_retain_local = isset($this->setting_addon['wpvivid_rollback_retain_local']) ? $this->setting_addon['wpvivid_rollback_retain_local'] : 0;
        if($rollback_retain_local)
        {
            $rollback_retain_local = 'checked';
        }
        else
        {
            $rollback_retain_local = '';
        }

        ?>
        <div style="margin-top: 10px;">
            <div style="margin-bottom: 10px;">
                <p>Note: Once the retention is set up and reached, the oldest versioning backup will be deleted accordingly through a daily cron.</p>
                <p>
                    <input type="text" class="mwp-wpvivid-rollback-count-retention" option="mwp-rollback-addon" name="mwp_wpvivid_max_plugins_count_addon" value="<?php echo $max_plugins_count;?>"> versioning backups retained for plugins.
                </p>
                <p>
                    <input type="text" class="mwp-wpvivid-rollback-count-retention" option="mwp-rollback-addon" name="mwp_wpvivid_max_themes_count_addon" value="<?php echo $max_themes_count;?>"> versioning backups retained for themes.
                </p>
                <p>
                    <input type="text" class="mwp-wpvivid-rollback-count-retention" option="mwp-rollback-addon" name="mwp_wpvivid_max_core_count_addon" value="<?php echo $max_core_count;?>"> versioning backups retained for Wordpress core.
                </p>
            </div>
            <div style="margin-bottom: 10px;">

                <p>
                    <select option="mwp-rollback-addon" name="mwp_wpvivid_manual_backup_remote_selector">
                        <?php
                        if($global)
                        {
                            if($rollback_remote)
                            {
                                ?>
                                <option value="-1">Backup to Localhost</option>
                                <option value="1" selected="selected">Backup to Remote</option>
                                <?php
                            }
                            else
                            {
                                ?>
                                <option value="-1" selected="selected">Backup to Localhost</option>
                                <option value="1">Backup to Remote</option>
                                <?php
                            }
                        }
                        else
                        {
                            ?>
                            <option value="-1">Backup to Localhost</option>
                            <?php
                        }
                        ?>
                    </select>
                    <span>Choose cloud storage for versioning backups (files + database).</span>
                </p>
                <p>
                    <label>
                        <input type="checkbox" option="mwp-rollback-addon" name="mwp_wpvivid_rollback_retain_local_addon" <?php echo $rollback_retain_local; ?>> Keep storing the backups in localhost after uploading to remote storage.
                    </label>
                </p>

                <p>
                    <label>
                        <input type="checkbox" option="mwp-rollback-addon" name="mwp_wpvivid_auto_backup_db_before_update_addon" <?php esc_attr_e($auto_backup_db_before_update) ?> />
                        <span>Back up the database before updates. You can manage the backups in Backup Manager.</span>
                    </label>
                </p>
                <div id="mwp_wpvivid_auto_backup_db_count_display" style="<?php echo esc_attr($auto_backup_db_setting_display); ?>">
                    <p>
                        <label>
                            <input type="text" class="mwp-wpvivid-rollback-count-retention" placeholder="30" option="mwp-rollback-addon" name="mwp_wpvivid_rollback_max_backup_count_addon" value="<?php esc_attr_e($rollback_max_backup_count); ?>">
                            <span> database backups retained in localhost.</span>
                        </label>
                    </p>
                    <p>
                        <label>
                            <input type="text" class="mwp-wpvivid-rollback-count-retention" placeholder="30" option="mwp-rollback-addon" name="mwp_wpvivid_rollback_max_remote_backup_count_addon" value="<?php esc_attr_e($rollback_max_remote_backup_count); ?>">
                            <span> database backups retained in remote storage.</span>
                        </label>
                    </p>
                </div>
            </div>
        </div>
        <script>
            jQuery('.mwp-wpvivid-rollback-count-retention').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery(this).val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery(this).val('');
                }
            });

            jQuery('[option=mwp-rollback-addon][name=mwp_wpvivid_auto_backup_db_before_update_addon]').on('click', function(){
                if(jQuery(this).prop('checked'))
                {
                    jQuery('#mwp_wpvivid_auto_backup_db_count_display').show();
                }
                else
                {
                    jQuery('#mwp_wpvivid_auto_backup_db_count_display').hide();
                }
            });

            var mwp_wpvivid_get_rollback_remote_retry_times = 0;
            function mwp_wpvivid_get_rollback_remote_retry(error_msg){
                var need_retry_get_rollback_remote = false;
                mwp_wpvivid_get_rollback_remote_retry_times++;
                if(mwp_wpvivid_get_rollback_remote_retry_times < 3){
                    need_retry_get_rollback_remote = true;
                }
                if(need_retry_get_rollback_remote){
                    setTimeout(function(){
                        mwp_wpvivid_init_get_rollback_remote();
                    }, 3000);
                }
                else{
                }
            }

            function mwp_wpvivid_init_get_rollback_remote()
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_achieve_rollback_remote_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('select[option=mwp-rollback-addon][name=mwp_wpvivid_manual_backup_remote_selector]').html(jsonarray.remote_list_html);
                        }
                        else {
                            mwp_wpvivid_get_rollback_remote_retry(jsonarray.error);
                        }
                    }
                    catch (err) {
                        mwp_wpvivid_get_rollback_remote_retry(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    mwp_wpvivid_get_rollback_remote_retry(error_message);
                });
            }

            jQuery(document).ready(function($)
            {
                mwp_wpvivid_init_get_rollback_remote();
            });
        </script>
        <?php
    }

    public function mwp_wpvivid_setting_page($global){
        if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
            include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
        $this->main_tab=new Mainwp_WPvivid_Tab_Page_Container();

        $args['is_parent_tab']=0;
        $args['transparency']=1;
        $args['global']=$global;
        $this->main_tab->add_tab('General Settings','general',array($this, 'output_general_setting'), $args);
        $this->main_tab->add_tab('Advanced Settings','advance',array($this, 'output_advance_setting'), $args);
        $this->main_tab->display();
        ?>
        <?php
        if ($global === false) {
            $save_change_id = 'mwp_wpvivid_setting_general_save';
        } else {
            $save_change_id = 'mwp_wpvivid_global_setting_general_save';
        }
        ?>
        <div><input class="ui green mini button" id="<?php echo esc_attr($save_change_id); ?>" type="button" value="<?php esc_attr_e('Save Changes'); ?>" /></div>

        <script>
            jQuery('#mwp_wpvivid_setting_general_save').click(function(){
                mwp_wpvivid_set_general_settings();
            });
            jQuery('#mwp_wpvivid_global_setting_general_save').click(function(){
                mwp_wpvivid_set_global_general_settings();
            });
            function mwp_wpvivid_set_general_settings()
            {
                var setting_data = mwp_wpvivid_ajax_data_transfer('mwp-setting');
                var ajax_data = {
                    'action': 'mwp_wpvivid_set_general_setting',
                    'setting': setting_data,
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_setting_general_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#mwp_wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            location.reload();
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#mwp_wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_set_global_general_settings()
            {
                var setting_data = mwp_wpvivid_ajax_data_transfer('mwp-setting');
                var ajax_data = {
                    'action': 'mwp_wpvivid_set_global_general_setting',
                    'setting': setting_data,
                };
                jQuery('#mwp_wpvivid_global_setting_general_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        jQuery('#mwp_wpvivid_global_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            window.location.href = window.location.href + "&synchronize=1&addon=0";
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#mwp_wpvivid_global_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_global_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function output_general_setting($global){
        $display_backup_count = isset($this->setting['wpvivid_common_setting']['max_backup_count']) ? $this->setting['wpvivid_common_setting']['max_backup_count'] : '3';
        $display_backup_count = intval($display_backup_count);

        $wpvivid_setting_estimate_backup ='checked';
        if(isset($this->setting['wpvivid_common_setting']['estimate_backup'])){
            $wpvivid_setting_estimate_backup = $this->setting['wpvivid_common_setting']['estimate_backup'] == '1' ? 'checked' : '';
        }

        $wpvivid_setting_ismerge = 'checked';
        if(isset($this->setting['wpvivid_common_setting']['ismerge'])){
            $wpvivid_setting_ismerge = $this->setting['wpvivid_common_setting']['ismerge'] == '1' ? 'checked' : '';
        }

        $wpvivid_save_local = '';
        if(isset($this->setting['wpvivid_common_setting']['retain_local'])){
            $wpvivid_save_local = $this->setting['wpvivid_common_setting']['retain_local'] == '1' ? 'checked' : '';
        }

        $wpvivid_local_directory = isset($this->setting['wpvivid_local_setting']['path']) ? $this->setting['wpvivid_local_setting']['path'] : 'wpvividbackups';

        $wpvivid_domain_prefix = 'checked';
        if(isset($this->setting['wpvivid_common_setting']['domain_include'])){
            $wpvivid_domain_prefix = $this->setting['wpvivid_common_setting']['domain_include'] == '1' ? 'checked' : '';
        }
        ?>
        <div style="margin-top: 10px;">
            <div class="postbox mwp-wpvivid-setting-block mwp-wpvivid-block-bottom-space">
                <div style="margin-bottom: 20px;"><strong><?php esc_html_e('General Settings'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <select class="mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_max_backup_count">
                        <?php
                        for($local_count=1; $local_count<8; $local_count++){
                            if($local_count === $display_backup_count){
                                echo '<option selected="selected" value="'.esc_attr($local_count).'">'.esc_html($local_count).'</option>';
                            }
                            else{
                                echo '<option value="'.esc_attr($local_count).'">'.esc_html($local_count).'</option>';
                            }
                        }
                        ?>
                    </select><strong><?php esc_html_e('backups retained'); ?></strong>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting" name="mwp_estimate_backup" <?php echo esc_attr($wpvivid_setting_estimate_backup); ?> />
                        <span><?php esc_html_e('Calculate the size of files, folder and database before backing up'); ?></span>
                    </label>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting" name="mwp_ismerge" <?php echo esc_attr($wpvivid_setting_ismerge); ?> />
                        <span><?php esc_html_e('Merge all the backup files into single package when a backup completes. This will save great disk spaces, though takes longer time. We recommended you check the option especially on sites with insufficient server resources.'); ?></span>
                    </label>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting" name="mwp_retain_local" <?php echo esc_attr($wpvivid_save_local); ?> />
                        <span><?php esc_html_e('Keep storing the backups in localhost after uploading to remote storage'); ?></span>
                    </label>
                </div>
                <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Name your folder, this folder must be writable for creating backup files.'); ?></div>
                <div class="mwp-wpvivid-block-bottom-space"><input type="text" class="all-options" option="mwp-setting" name="mwp_path" value="<?php echo esc_attr($wpvivid_local_directory); ?>" onkeyup="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" /></div>
                <div>
                    <div class="mwp-wpvivid-block-bottom-space mwp-wpvivid-font-right-space" style="float: left;"><?php esc_html_e('Child-Site Storage Directory: '); ?></div>
                    <div class="mwp-wpvivid-block-bottom-space" style="float: left;"><?php esc_html_e('http(s)://child-site/wp-content/'); ?></div>
                    <div class="mwp-wpvivid-block-bottom-space" style="float: left;"><?php echo esc_html($wpvivid_local_directory); ?></div>
                    <div style="clear: both;"></div>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="checkbox" option="mwp-setting" name="mwp_domain_include" <?php echo esc_attr($wpvivid_domain_prefix); ?> />
                        <span><?php esc_html_e('Display domain(url) of current site in backup name. (e.g. domain_wpvivid-5ceb938b6dca9_2019-05-27-07-36_backup_all.zip)'); ?></span>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    public function output_advance_setting($global){
        if(isset($this->setting['wpvivid_common_setting']['max_file_size']))
        {
            $wpvivid_max_file_size = $this->setting['wpvivid_common_setting']['max_file_size'];
        }
        else if(isset($this->setting['wpvivid_compress_setting']['max_file_size']))
        {
            $wpvivid_max_file_size = $this->setting['wpvivid_compress_setting']['max_file_size'];
        }
        else
        {
            $wpvivid_max_file_size = '200M';
        }

        if(isset($this->setting['wpvivid_common_setting']['exclude_file_size']))
        {
            $wpvivid_exclude_file_size = $this->setting['wpvivid_common_setting']['exclude_file_size'];
        }
        else if(isset($this->setting['wpvivid_compress_setting']['exclude_file_size']))
        {
            $wpvivid_exclude_file_size = $this->setting['wpvivid_compress_setting']['exclude_file_size'];
        }
        else
        {
            $wpvivid_exclude_file_size = 0;
        }

        //$wpvivid_max_file_size = isset($this->setting['wpvivid_compress_setting']['max_file_size']) ? $this->setting['wpvivid_compress_setting']['max_file_size'] : '200M';
        //$wpvivid_exclude_file_size = isset($this->setting['wpvivid_compress_setting']['exclude_file_size']) ? $this->setting['wpvivid_compress_setting']['exclude_file_size'] : 0;
        $wpvivid_max_exec_time =  isset($this->setting['wpvivid_common_setting']['max_execution_time']) ? $this->setting['wpvivid_common_setting']['max_execution_time'] : 300;
        $wpvivid_memory_limit = isset($this->setting['wpvivid_common_setting']['memory_limit']) ? $this->setting['wpvivid_common_setting']['memory_limit'] : '256M';
        $migrate_size=isset($this->setting['wpvivid_common_setting']['migrate_size'])?$this->setting['wpvivid_common_setting']['migrate_size']:2048;
        $compress_file_count=isset($this->setting['wpvivid_common_setting']['compress_file_count'])?$this->setting['wpvivid_common_setting']['compress_file_count']:500;
        $max_sql_file_size=isset($this->setting['wpvivid_common_setting']['max_sql_file_size'])?$this->setting['wpvivid_common_setting']['max_sql_file_size']:200;

        //restore
        $restore_max_execution_time=isset($this->setting['wpvivid_common_setting']['restore_max_execution_time'])?$this->setting['wpvivid_common_setting']['restore_max_execution_time']:300;
        $restore_memory_limit=isset($this->setting['wpvivid_common_setting']['restore_memory_limit'])?$this->setting['wpvivid_common_setting']['restore_memory_limit']:'512M';
        $replace_rows_pre_request=isset($this->setting['wpvivid_common_setting']['replace_rows_pre_request'])?$this->setting['wpvivid_common_setting']['replace_rows_pre_request']:10000;
        $sql_file_buffer_pre_request=isset($this->setting['wpvivid_common_setting']['sql_file_buffer_pre_request'])?$this->setting['wpvivid_common_setting']['sql_file_buffer_pre_request']:'5';
        $use_index=isset($this->setting['wpvivid_common_setting']['use_index'])?$this->setting['wpvivid_common_setting']['use_index']:1;
        if($use_index)
            {
            $use_index=' checked';
        }
        else
        {
            $use_index=' ';
        }
        $unzip_files_pre_request=isset($this->setting['wpvivid_common_setting']['unzip_files_pre_request'])?$this->setting['wpvivid_common_setting']['unzip_files_pre_request']:1000;

        $wpvivid_resume_time = isset($this->setting['wpvivid_common_setting']['max_resume_count']) ? $this->setting['wpvivid_common_setting']['max_resume_count'] : '6';
        $wpvivid_resume_time = intval($wpvivid_resume_time);

        $db_method_wpdb = 'checked';
        $db_method_pdo  = '';
        if(isset($this->setting['wpvivid_common_setting']['db_connect_method'])){
            if($this->setting['wpvivid_common_setting']['db_connect_method'] === 'wpdb'){
                $db_method_wpdb = 'checked';
                $db_method_pdo  = '';
            }
            else{
                $db_method_wpdb = '';
                $db_method_pdo  = 'checked';
            }
        }

        $zip_method_archive = 'checked';
        $zip_method_pclzip  = '';
        if(isset($this->setting['wpvivid_common_setting']['zip_method'])){
            if($this->setting['wpvivid_common_setting']['zip_method'] === 'ziparchive'){
                $zip_method_archive = 'checked';
                $zip_method_pclzip  = '';
            }
            else{
                $zip_method_archive = '';
                $zip_method_pclzip  = 'checked';
            }
        }

        ?>
        <div style="margin-top: 10px;">
            <div class="postbox mwp-wpvivid-setting-block mwp-wpvivid-block-bottom-space">
                <div style="margin-bottom: 20px;"><strong><?php esc_html_e('Advanced Settings'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Database access method'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="radio" option="mwp-setting" name="mwp_db_connect_method" value="wpdb" <?php echo esc_attr($db_method_wpdb); ?> />
                        <span class="mwp-wpvivid-block-right-space"><strong>WPDB</strong></span><span><?php esc_html_e('WPDB option has a better compatibility, but the speed of backup and restore is slower.', 'wpvivid'); ?></span>
                    </label>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="radio" option="mwp-setting" name="mwp_db_connect_method" value="pdo" <?php echo esc_attr($db_method_pdo); ?> />
                        <span class="mwp-wpvivid-block-right-space"><strong>PDO</strong></span><span><?php esc_html_e('It is recommended to choose PDO option if pdo_mysql extension is installed on your server, which lets you backup and restore your site faster.', 'wpvivid'); ?></span>
                    </label>
                </div>

                <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Backup compression method'); ?></strong></div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="radio" option="mwp-setting" name="mwp_zip_method" value="ziparchive" <?php echo esc_attr($zip_method_archive); ?> />
                        <span class="mwp-wpvivid-block-right-space"><strong>ZipArchive</strong></span><span><?php esc_html_e('ZipArchive has a better flexibility which provides a higher backup success rate and speed. WPvivid Backup Plugin uses ZipArchive method by default. Using this method requires the ZIP extension to be installed within your PHP.', 'wpvivid'); ?></span>
                    </label>
                </div>
                <div class="mwp-wpvivid-block-bottom-space">
                    <label>
                        <input type="radio" option="mwp-setting" name="mwp_zip_method" value="pclzip" <?php echo esc_attr($zip_method_pclzip); ?> />
                        <span class="mwp-wpvivid-block-right-space"><strong>PCLZIP</strong></span><span><?php esc_html_e('PclZip is a much slower but more stable zip method that is included in every WordPress install. WPvivid will automatically switch to PclZip if the ZIP extension is not installed within your PHP.', 'wpvivid'); ?></span>
                    </label>
                </div>

                <div>
                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Compress Files Every'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_max_file_size" value="<?php echo esc_attr(str_replace('M', '', $wpvivid_max_file_size)); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Some web hosting providers limit large zip files (e.g. 200MB), and therefore splitting your backup into many parts is an ideal way to avoid hitting the limitation if you are running a big website.  Please try to adjust the value if you are encountering backup errors. When you set a value of 0MB, backups will be split every 4GB.'); ?></div>

                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Exclude the files which are larger than'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_exclude_file_size" value="<?php echo esc_attr($wpvivid_exclude_file_size); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Using the option will ignore the file larger than the certain size in MB when backing up, \'0\' (zero) means unlimited.'); ?></div>

                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('PHP script execution timeout for backup'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_max_execution_time" value="<?php echo esc_attr($wpvivid_max_exec_time); ?>" onkeyup="value=value.replace(/\D/g,'')" />Seconds
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut the process of backup down. If the progress of backup encounters a time-out, that means you have a medium or large sized website, please try to scale the value bigger.'); ?></div>

                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('PHP Memory Limit for backup'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_memory_limit" value="<?php echo esc_attr(str_replace('M', '', $wpvivid_memory_limit)); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Adjust this value to apply for a temporary PHP memory limit for WPvivid backup plugin to run a backup. We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting providers may not support this.'); ?></div>


                    <!---------->

                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('The number of files compressed to the backup zip each time'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_compress_file_count" value="<?php echo esc_attr($compress_file_count); ?>" onkeyup="value=value.replace(/\D/g,'')" />Files
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('When taking a backup, the plugin will compress this number of files to the backup zip each time. The default value is 500. The lower the value, the longer time the backup will take, but the higher the backup success rate. If you encounter a backup timeout issue, try to decrease this value.'); ?></div>


                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Split a sql file every this size'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_max_sql_file_size" value="<?php echo esc_attr($max_sql_file_size); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Some web hosting providers limit large zip files (e.g. 200MB), and therefore splitting your backup into many parts is an ideal way to avoid hitting the limitation if you are running a big website. Please try to adjust the value if you are encountering backup errors. If you use a value of 0 MB, any backup files won\'t be split.'); ?></div>


                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Chunk Size'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_migrate_size" value="<?php echo esc_attr($migrate_size); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('e.g. if you choose a chunk size of 2MB, a 8MB file will use 4 chunks. Decreasing this value will break the ISP\'s transmission limit, for example:512KB'); ?></div>


                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('PHP script execution timeout for restore'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_restore_max_execution_time" value="<?php echo esc_attr($restore_max_execution_time); ?>" onkeyup="value=value.replace(/\D/g,'')" />Seconds
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('The time-out is not your server PHP time-out. With the execution time exhausted, our plugin will shut the process of restore down. If the progress of restore encounters a time-out, that means you have a medium or large sized website, please try to scale the value bigger.'); ?></div>






                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('PHP Memory Limit for restoration'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_restore_memory_limit" value="<?php echo esc_attr(str_replace('M', '', $restore_memory_limit)); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Adjust this value to apply for a temporary PHP memory limit for WPvivid backup plugin in restore process. We set this value to 256M by default. Increase the value if you encounter a memory exhausted error. Note: some web hosting providers may not support this.'); ?></div>






                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Maximum rows of data to be processed per request for restoration'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_replace_rows_pre_request" value="<?php echo esc_attr($replace_rows_pre_request); ?>" onkeyup="value=value.replace(/\D/g,'')" />rows
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('The smaller it is, the slower the restoration will be, but the lower the chance of a timeout error.'); ?></div>


                    <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Maximum size of sql file to be imported per request for restoration'); ?></strong></div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input type="text" class="all-options mwp-wpvivid-font-right-space" option="mwp-setting" name="mwp_sql_file_buffer_pre_request" value="<?php echo esc_attr($sql_file_buffer_pre_request); ?>" onkeyup="value=value.replace(/\D/g,'')" />MB
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Maximum rows of data to be processed per request.'); ?></div>



                    <!---------->

                    <div class="mwp-wpvivid-block-bottom-space">
                        <strong>Retrying </strong>
                        <select option="mwp-setting" name="mwp_max_resume_count">
                            <?php
                            for($resume_count=3; $resume_count<10; $resume_count++){
                                if($resume_count === $wpvivid_resume_time){
                                    echo '<option selected="selected" value="'.esc_attr($resume_count).'">'.esc_html($resume_count).'</option>';
                                }
                                else{
                                    echo '<option value="'.esc_attr($resume_count).'">'.esc_html($resume_count).'</option>';
                                }
                            }
                            ?>
                        </select><strong><?php esc_html_e(' times when encountering a time-out error', 'wpvivid'); ?></strong>
                    </div>


                    <div>
                        <div>
                            <div>
                                <input type="checkbox" option="mwp-setting" name="mwp_use_index" style="margin-right: 0px;" <?php echo esc_attr($use_index); ?> />
                                <strong><?php esc_html_e('Extract files by index for restoration', 'wpvivid-backuprestore'); ?></strong>
                            </div>
                            <div><p><?php esc_html_e('Specify the number of files to be extracted per request. The lower the number is, the slower the restoration, but the lower the chance of a timeout error or restore failure.'); ?></p></div>
                        </div>
                        <div class="setting-tab-block">
                            <input type="text" option="mwp-setting" name="mwp_unzip_files_pre_request" class="all-options" value="<?php echo esc_attr($unzip_files_pre_request); ?>" onkeyup="value=value.replace(/\D/g,'')" />Files are unzipped every PHP request
                        </div>
                    </div>


                </div>
            </div>
        </div>
        <?php
    }

    public function mwp_wpvivid_synchronize_setting($check_addon)
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->render_sync_websites_page('mwp_wpvivid_sync_setting', $check_addon);
        ?>
        <script>
            function mwp_wpvivid_sync_setting()
            {
                var website_ids= [];
                mwp_wpvivid_sync_index=0;
                jQuery('.mwp-wpvivid-sync-row').each(function()
                {
                    jQuery(this).children('td:first').each(function(){
                        if (jQuery(this).children().children().prop('checked')) {
                            var id = jQuery(this).attr('website-id');
                            website_ids.push(id);
                        }
                    });
                });
                if(website_ids.length>0)
                {
                    jQuery('#mwp_wpvivid_sync_setting').css({'pointer-events': 'none', 'opacity': '0.4'});
                    var check_addon = '<?php echo esc_js($check_addon); ?>';
                    mwp_wpvivid_sync_site(website_ids,check_addon,'mwp_wpvivid_sync_setting','Extensions-Wpvivid-Backup-Mainwp&tab=settings','mwp_wpvivid_settings_tab');
                }
            }
            jQuery('#mwp_wpvivid_sync_setting').click(function(){
                mwp_wpvivid_sync_setting();
            });
        </script>
        <?php
    }
}