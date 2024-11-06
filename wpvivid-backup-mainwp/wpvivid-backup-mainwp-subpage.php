<?php

class Mainwp_WPvivid_Extension_Subpage
{
    static public function renderSubpage()
    {
        global $mainwp_wpvivid_extension_activator;
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $sites_ids[] = sanitize_text_field($_GET['id']);
            if($mainwp_wpvivid_extension_activator->check_site_id_secure(sanitize_text_field($_GET['id']))) {
                $option = array('plugin_upgrades' => true, 'plugins' => true);
                global $mainwp_wpvivid_extension_activator;
                $dbwebsites = apply_filters('mainwp_getdbsites', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $sites_ids, array(), $option);
                $activated = false;
                foreach ($dbwebsites as $website) {
                    $plugins = json_decode($website->plugins, 1);
                    if (is_array($plugins) && count($plugins) != 0) {
                        $check_pro = $mainwp_wpvivid_extension_activator->mwp_check_wpvivid_pro($plugins, $website->id);
                        foreach ($plugins as $plugin) {
                            if ((strcmp($plugin['slug'], "wpvivid-backuprestore/wpvivid-backuprestore.php") === 0)) {
                                if ($plugin['active']) {
                                    $activated = true;
                                }
                                break;
                            }
                        }
                    }
                    break;
                }
                if(!$activated){
                    ?>
                    <div class="ui yellow message">WPvivid backup plugin is not installed or activated on the site.</div>
                    <?php
                    return;
                }
            }

            ?>
            <script>
                function mwp_wpvivid_save_override(site_id) {
                    var individual = 0;
                    if (document.getElementById('mwp_wpvivid_override_settings').checked) {
                        individual = 1;
                    } else {
                        individual = 0;
                    }

                    var ajax_data = {
                        'action': 'mwp_wpvivid_set_individual',
                        'individual': individual,
                        'site_id': site_id
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

                function mwp_wpvivid_click_remote_page() {
                    <?php
                    if($check_pro)
                    {
                        $white_label_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
                        $is_mu = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'is_mu', array());
                        if(!$white_label_setting){
                            if($is_mu)
                            {
                                $location = 'network/admin.php?page=wpvivid-remote';
                            }
                            else
                            {
                                $location = 'admin.php?page=wpvivid-remote';
                            }
                        }
                        else{
                            $slug = strtolower($white_label_setting['white_label_slug']);
                            $slug_page = strtolower($white_label_setting['white_label_slug']);
                            if($is_mu)
                            {
                                $location = 'network/admin.php?page='.$slug.'-remote';
                            }
                            else
                            {
                                $location = 'admin.php?page='.$slug.'-remote';
                            }
                        }
                    }
                    else
                    {
                        $location = 'admin.php?page=WPvivid';
                    }
                    ?>
                    var location = "admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_attr($_GET['id']); ?>&location=<?php echo esc_attr(base64_encode($location)); ?>&_opennonce=<?php echo esc_attr(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
                    window.open(location, '_blank');
                }
            </script>

            <?php
            $override = '';
            $individual = Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option(sanitize_text_field($_GET['id']), 'individual', false);
            if(empty($individual)){
                $individual = false;
            }
            if ($individual) {
                $override = 'checked';
            }
            ?>

            <div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-wpvivid-menu">
                <?php
                self::mainwp_wpvivid_add_tab_backup();
                if($check_pro){
                    self::mainwp_wpvivid_add_tab_backup_restore();
                }
                self::mainwp_wpvivid_add_tab_schedule();
                self::mainwp_wpvivid_add_tab_setting();
                if($check_pro){
                    self::mainwp_wpvivid_add_tab_capability();
                    self::mainwp_wpvivid_add_tab_white_label();
                }
                self::mainwp_wpvivid_add_tab_remote();
                ?>
            </div>
            <div>
                <div style="background: #fff; margin: 10px 10px 0 10px;">
                    <div class="postbox" style="padding: 10px; margin-bottom: 0;">
                        <div style="float: left; margin-top: 7px; margin-right: 25px;"><?php esc_html_e('Override Settings'); ?></div>
                        <div class="ui toggle checkbox" style="float: left; margin-top:4px; margin-right: 10px;">
                            <input type="checkbox" id="mwp_wpvivid_override_settings" <?php echo esc_attr($override); ?> />
                            <label for="mwp_wpvivid_override_settings"></label>
                        </div>
                        <div style="float: left;"><input class="ui green mini button" type="button" value="Save" onclick="mwp_wpvivid_save_override(<?php echo esc_js(sanitize_key($_GET['id'])); ?>)" /></div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
                <div style="clear: both;"></div>
                <?php
                self::mainwp_wpvivid_add_page_backup($check_pro);
                if($check_pro){
                    self::mainwp_wpvivid_add_page_backup_restore($check_pro);
                }
                self::mainwp_wpvivid_add_page_schedule($check_pro);
                self::mainwp_wpvivid_add_page_setting($check_pro);
                if($check_pro){
                    self::mainwp_wpvivid_add_page_capability($check_pro);
                    self::mainwp_wpvivid_add_page_white_label($check_pro);
                }
                ?>
            </div>
            <?php
        }
        else{
            ?>
            <div style="padding: 10px; background: #fff;">Not a valid website.</div>
            <?php
        }
    }

    static function mainwp_wpvivid_add_tab_backup(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_backup" class="item active" onclick="mwp_switch_wpvivid_tab('backup');"><?php esc_html_e('Backup'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_tab_backup_restore(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_backup_restore" class="item" onclick="mwp_switch_wpvivid_tab('backup_restore');"><?php esc_html_e('Backup & Restore'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_tab_schedule()
    {
        ?>
        <a href="#" id="mwp_wpvivid_tab_schedule" class="item" onclick="mwp_switch_wpvivid_tab('schedule');"><?php esc_html_e('Schedule'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_tab_remote()
    {
        ?>
        <a href="#" id="mwp_wpvivid_tab_remote" class="item" onclick="mwp_wpvivid_click_remote_page();"><?php esc_html_e('Remote Storage'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_tab_setting(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_setting" class="item" onclick="mwp_switch_wpvivid_tab('setting');"><?php esc_html_e('Settings'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_tab_capability(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_capability" class="item" onclick="mwp_switch_wpvivid_tab('capability');"><?php esc_html_e('Modules'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_tab_white_label(){
        ?>
        <a href="#" id="mwp_wpvivid_tab_white_label" class="item" onclick="mwp_switch_wpvivid_tab('white_label');"><?php esc_html_e('White Label'); ?></a>
        <?php
    }

    static function mainwp_wpvivid_add_page_backup($check_pro){
        ?>
        <div id="mwp_wpvivid_page_backup" style="width:100%; background: #fff;">
            <?php self::renderSubBackuppage($check_pro); ?>
        </div>
        <?php
    }

    static function mainwp_wpvivid_add_page_backup_restore($check_pro){
        ?>
        <div id="mwp_wpvivid_page_backup_restore" style="display: none; background: #fff;">
            <?php self::renderSubBackupRestorepage($check_pro); ?>
        </div>
        <?php
    }

    static function mainwp_wpvivid_add_page_schedule($check_pro)
    {
        ?>
        <div id="mwp_wpvivid_page_schedule" style="display: none; background: #fff;">
            <?php self::renderSubSchedulepage($check_pro); ?>
        </div>
        <?php
    }

    static function mainwp_wpvivid_add_page_setting($check_pro){
        ?>
        <div id="mwp_wpvivid_page_setting" style="display: none; background: #fff;">
            <?php self::renderSubSettingpage($check_pro); ?>
        </div>
        <?php
    }

    static function mainwp_wpvivid_add_page_capability($check_pro){
        ?>
        <div id="mwp_wpvivid_page_capability" style="display: none; background: #fff;">
            <?php self::renderSubCapabilitypage($check_pro); ?>
        </div>
        <?php
    }

    static function mainwp_wpvivid_add_page_white_label($check_pro){
        ?>
        <div id="mwp_wpvivid_page_white_label" style="display: none; background: #fff;">
            <?php self::renderSubWhiteLabelpage($check_pro); ?>
        </div>
        <?php
    }

    static function renderSubBackuppage($check_pro)
    {
        global $mainwp_wpvivid_extension_activator;
        $setting=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'settings', array());
        $setting_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'settings_addon', array());
        $backup_custom_setting=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
        $mainwp_wpvivid_extension_activator->backup_page->set_site_id(sanitize_text_field($_GET['id']));
        $mainwp_wpvivid_extension_activator->backup_page->set_backup_info($setting, $setting_addon, $backup_custom_setting);
        $mainwp_wpvivid_extension_activator->backup_page->render($check_pro);
    }

    static function renderSubBackupRestorepage($check_pro)
    {
        global $mainwp_wpvivid_extension_activator;
        $setting=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'settings', array());
        $setting_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'settings_addon', array());
        $remote=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'remote', array());
        $mainwp_wpvivid_extension_activator->backup_restore_page->set_site_id(sanitize_text_field($_GET['id']));
        $mainwp_wpvivid_extension_activator->backup_restore_page->set_backup_restore_info($setting, $setting_addon, $remote);
        $mainwp_wpvivid_extension_activator->backup_restore_page->render($check_pro);
    }

    static function renderSubSchedulepage($check_pro)
    {
        global $mainwp_wpvivid_extension_activator;
        $schedule=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'schedule', array());
        $schedule_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'schedule_addon', array());
        $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option(sanitize_text_field($_GET['id']), 'time_zone', '');
        if(empty($time_zone)){
            $time_zone = 0;
        }
        $mainwp_wpvivid_extension_activator->schedule->set_schedule_info($schedule, $schedule_addon, array(), $time_zone);
        $mainwp_wpvivid_extension_activator->schedule->set_site_id(sanitize_text_field($_GET['id']));
        $mainwp_wpvivid_extension_activator->schedule->render($check_pro);
    }

    static function renderSubSettingpage($check_pro)
    {
        global $mainwp_wpvivid_extension_activator;
        $setting=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'settings', array());
        $setting_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'settings_addon', array());
        $mainwp_wpvivid_extension_activator->setting->set_site_id(sanitize_text_field($_GET['id']));
        $mainwp_wpvivid_extension_activator->setting->set_setting_info($setting, $setting_addon);
        $mainwp_wpvivid_extension_activator->setting->render($check_pro);
    }

    static function renderSubCapabilitypage($check_pro)
    {
        global $mainwp_wpvivid_extension_activator;
        $capability_addon = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'menu_capability', array());
        if(empty($capability_addon)){
            $capability_addon = array();
            $capability_addon['menu_manual_backup'] = '1';
            $capability_addon['menu_export_site'] = '1';
            $capability_addon['menu_import_site'] = '1';
            $capability_addon['menu_backup_schedule'] = '1';
            $capability_addon['menu_backup_restore'] = '1';
            $capability_addon['menu_cloud_storage'] = '1';
            $capability_addon['menu_image_optimization'] = '1';
            $capability_addon['menu_staging'] = '1';
            $capability_addon['menu_database_snapshot'] = '1';
            $capability_addon['menu_unused_image_cleaner'] = '1';
            $capability_addon['menu_export_import'] = '1';
            $capability_addon['menu_rollback'] = '1';
            $capability_addon['menu_role_capabilities'] = '1';
            $capability_addon['menu_setting'] = '1';
            $capability_addon['menu_debug'] = '1';
            $capability_addon['menu_pro_page'] = '1';
        }

        if(!isset($capability_addon['menu_manual_backup']))
        {
            $capability_addon = array();
            $capability_addon['menu_manual_backup'] = '1';
            $capability_addon['menu_export_site'] = '1';
            $capability_addon['menu_import_site'] = '1';
            $capability_addon['menu_backup_schedule'] = '1';
            $capability_addon['menu_backup_restore'] = '1';
            $capability_addon['menu_cloud_storage'] = '1';
            $capability_addon['menu_image_optimization'] = '1';
            $capability_addon['menu_staging'] = '1';
            $capability_addon['menu_database_snapshot'] = '1';
            $capability_addon['menu_unused_image_cleaner'] = '1';
            $capability_addon['menu_export_import'] = '1';
            $capability_addon['menu_rollback'] = '1';
            $capability_addon['menu_role_capabilities'] = '1';
            $capability_addon['menu_setting'] = '1';
            $capability_addon['menu_debug'] = '1';
            $capability_addon['menu_pro_page'] = '1';
        }

        if(!isset($capability_addon['menu_database_snapshot']))
        {
            $capability_addon['menu_database_snapshot'] = '1';
        }
        if(!isset($capability_addon['menu_staging']))
        {
            $capability_addon['menu_staging'] = '1';
        }
        if(!isset($capability_addon['menu_rollback']))
        {
            $capability_addon['menu_rollback'] = '1';
        }

        $mainwp_wpvivid_extension_activator->capability->set_site_id(sanitize_text_field($_GET['id']));
        $mainwp_wpvivid_extension_activator->capability->set_capability_info($capability_addon);
        $mainwp_wpvivid_extension_activator->capability->render($check_pro);
    }

    static function renderSubWhiteLabelpage($check_pro)
    {
        global $mainwp_wpvivid_extension_activator;
        $white_label_addon = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
        if(empty($white_label_addon)){
            $white_label_addon = array();
        }
        $mainwp_wpvivid_extension_activator->white_label->set_site_id(sanitize_text_field($_GET['id']));
        $mainwp_wpvivid_extension_activator->white_label->set_white_label_info($white_label_addon);
        $mainwp_wpvivid_extension_activator->white_label->render($check_pro);
    }

    static public function output_backup_status($site_id, $tasks,$backup_list,$schedule)
    {
        global $mainwp_wpvivid_extension_activator;
        $html='';
        foreach ($tasks as $task_id => $task)
        {
            if($task['status']['str']=='running')
            {
                $html='<div class="mwp-action-progress-bar">
                            <div class="mwp-action-progress-bar-percent" style="height:24px;width:'.esc_attr($task['task_info']['backup_percent']).'"></div>
                        </div>
                        <div style="float: left; '.esc_attr($task['task_info']['display_estimate_backup']).'">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">'.__('Database Size:', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.esc_html($task['task_info']['db_size']).'</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">'.__('File Size:', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.esc_html($task['task_info']['file_size']).'</span></div>
                        </div>
                        <div style="float: left;">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">'.__('Total Size:', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.esc_html($task['task_info']['total']).'</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">'.__('Uploaded:', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.esc_html($task['task_info']['upload']).'</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">'.__('Speed:', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.esc_html($task['task_info']['speed']).'</span></div>
                        </div>
                        <div style="float: left;">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">'.__('Network Connection:', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.esc_html($task['task_info']['network_connection']).'</span></div>
                        </div>
                        <div style="clear:both;"></div>
                        <div style="padding:10px; float: left; width:100%;"><p id="mwp_wpvivid_current_doing">'.esc_html($task['task_info']['descript'], 'mainwp-wpvivid-extension').'</p></div>
                        <div style="clear: both;"></div>
                        <div>
                            <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" id="mwp_wpvivid_backup_cancel_btn" type="button" value="'.esc_attr( 'Cancel' ).'" onclick="mwp_wpvivid_cancel_backup();" style="'.esc_attr($task['task_info']['css_btn_cancel']).'" /></div>
                            <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" type="button" value="'.esc_attr( 'Log' ).'" onclick="mwp_wpvivid_read_log(\'mwp_wpvivid_view_backup_task_log\');" /></div>
                            <div style="clear: both;"></div>
                        </div>
                        <div style="clear: both;"></div>';
                break;
            }
            else if($task['status']['str'] === 'completed'){
                $options[$task_id]['task_id'] = $task_id;
                $options[$task_id]['backup_time'] = $task['status']['start_time'];
                $options[$task_id]['status'] = 'Succeeded';
                $mainwp_wpvivid_extension_activator->set_backup_report($site_id, $options);
            }
            else if($task['status']['str'] === 'error'){
                $options[$task_id]['task_id'] = $task_id;
                $options[$task_id]['backup_time'] = $task['status']['start_time'];
                $options[$task_id]['status'] = 'Failed, '.$task['status']['error'];
                $mainwp_wpvivid_extension_activator->set_backup_report($site_id, $options);
            }
        }
        return $html;
    }

    static public function output_backup_status_addon_ex($site_id, $information){
        global $mainwp_wpvivid_extension_activator;
        $tasks = $information['tasks'];
        $ret['result']='success';
        $ret['progress_html']=false;
        $ret['success_notice_html'] =false;
        $ret['error_notice_html'] =false;
        $ret['need_update']=false;
        $ret['last_msg_html']=false;
        $ret['running_backup_taskid']='';
        $ret['wait_resume']=false;
        $ret['next_resume_time']=false;
        $ret['need_refresh_remote']=false;
        $ret['backup_finish_info']=false;
        $ret['task_no_response']=false;

        foreach ($tasks as $task_id => $task) {
            if(!isset($task['id']))
            {
                continue;
            }

            $ret['task_id']=$task['id'];
            $ret['need_update']=true;

            if ($task['status']['str'] === 'ready' || $task['status']['str'] === 'running' || $task['status']['str'] === 'wait_resume' || $task['status']['str'] === 'no_responds') {
                $ret['running_backup_taskid']=$task_id;

                if($task['status']['str']==='wait_resume') {
                    $ret['wait_resume']=true;
                    $ret['next_resume_time']=$task['data']['next_resume_time'];
                }

                $ret['progress_html'] = '<div class="mwp-action-progress-bar">
                            <div class="mwp-action-progress-bar-percent" style="height:24px;width:' . esc_attr($task['task_info']['backup_percent']) . '"></div>
                        </div>
                        <div style="float: left;">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Total Size:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['total']) . '</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Uploaded:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['upload']) . '</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Speed:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['speed']) . '</span></div>
                        </div>
                        <div style="float: left;">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Network Connection:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['network_connection']) . '</span></div>
                        </div>
                        <div style="clear:both;"></div>
                        <div style="padding:10px; float: left; width:100%;"><p id="mwp_wpvivid_current_doing">' . esc_html($task['task_info']['descript'], 'mainwp-wpvivid-extension') . '</p></div>
                        <div style="clear: both;"></div>
                        <div>
                            <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" id="mwp_wpvivid_backup_cancel_btn_addon" type="button" value="' . esc_attr('Cancel') . '" style="' . esc_attr($task['task_info']['css_btn_cancel']) . '" /></div>
                            <div style="clear: both;"></div>
                        </div>
                        <div style="clear: both;"></div>';
            }
            else if($task['status']['str'] === 'completed'){
                $options[$task_id]['task_id'] = $task_id;
                $options[$task_id]['backup_time'] = $task['status']['start_time'];
                $options[$task_id]['status'] = 'Succeeded';
                $mainwp_wpvivid_extension_activator->set_backup_report($site_id, $options);
            }
            else if($task['status']['str'] === 'error'){
                $options[$task_id]['task_id'] = $task_id;
                $options[$task_id]['backup_time'] = $task['status']['start_time'];
                $options[$task_id]['status'] = 'Failed, '.$task['status']['error'];
                $mainwp_wpvivid_extension_activator->set_backup_report($site_id, $options);
            }
        }
        if($information['success_notice_html'] !== false){
            $ret['success_notice_html'] = __('<div class="notice notice-success is-dismissible inline" style="margin: 0; padding-top: 10px; margin-left: 0px !important;"><p>Backup task have been completed.</p>
                                    <button type="button" class="notice-dismiss" onclick="mwp_click_dismiss_notice(this);">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                    </button>
                                    </div>');
        }
        if($information['error_notice_html'] !== false){
            $ret['error_notice_html'] = '<div class="notice notice-error inline" style="margin: 0; padding: 10px; margin-left: 0px !important;"><p>'.esc_html($information['error_notice_html']).'</p></div>';
        }
        $ret['need_refresh_remote'] = $information['need_refresh_remote'];
        return $ret;
    }

    static public function output_backup_status_addon($site_id, $information){
        global $mainwp_wpvivid_extension_activator;
        $tasks = $information['tasks'];
        $ret['result']='success';
        $ret['need_update']=false;
        $ret['running_backup_taskid']='';
        $ret['progress_html']=false;
        $ret['success_notice_html']=false;
        $ret['error_notice_html']=false;
        $ret['need_refresh_remote']=false;
        $ret['wait_resume']=false;
        $ret['next_resume_time']=false;
        foreach ($tasks as $task_id => $task) {
            $ret['need_update']=true;

            if ($task['status']['str'] === 'ready' || $task['status']['str'] === 'running' || $task['status']['str'] === 'wait_resume' || $task['status']['str'] === 'no_responds') {
                $ret['running_backup_taskid']=$task_id;

                if($task['status']['str']==='wait_resume') {
                    $ret['wait_resume']=true;
                    $ret['next_resume_time']=$task['data']['next_resume_time'];
                }

                $ret['progress_html'] = '<div class="mwp-action-progress-bar">
                            <div class="mwp-action-progress-bar-percent" style="height:24px;width:' . esc_attr($task['task_info']['backup_percent']) . '"></div>
                        </div>
                        <div style="float: left; ' . esc_attr($task['task_info']['display_estimate_backup']) . '">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Database Size:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['db_size']) . '</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('File Size:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['file_size']) . '</span></div>
                        </div>
                        <div style="float: left;">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Total Size:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['total']) . '</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Uploaded:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['upload']) . '</span></div>
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Speed:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['speed']) . '</span></div>
                        </div>
                        <div style="float: left;">
                            <div class="mwp-backup-basic-info"><span class="mwp-wpvivid-span">' . __('Network Connection:', 'mainwp-wpvivid-extension') . '</span><span class="mwp-wpvivid-span">' . esc_html($task['task_info']['network_connection']) . '</span></div>
                        </div>
                        <div style="clear:both;"></div>
                        <div style="padding:10px; float: left; width:100%;"><p id="mwp_wpvivid_current_doing">' . esc_html($task['task_info']['descript'], 'mainwp-wpvivid-extension') . '</p></div>
                        <div style="clear: both;"></div>
                        <div>
                            <div class="mwp-backup-log-btn" style="float: left;"><input class="ui green mini button" id="mwp_wpvivid_backup_cancel_btn_addon" type="button" value="' . esc_attr('Cancel') . '" style="' . esc_attr($task['task_info']['css_btn_cancel']) . '" /></div>
                            <div style="clear: both;"></div>
                        </div>
                        <div style="clear: both;"></div>';
            }
            else if($task['status']['str'] === 'completed'){
                $options[$task_id]['task_id'] = $task_id;
                $options[$task_id]['backup_time'] = $task['status']['start_time'];
                $options[$task_id]['status'] = 'Succeeded';
                $mainwp_wpvivid_extension_activator->set_backup_report($site_id, $options);
            }
            else if($task['status']['str'] === 'error'){
                $options[$task_id]['task_id'] = $task_id;
                $options[$task_id]['backup_time'] = $task['status']['start_time'];
                $options[$task_id]['status'] = 'Failed, '.$task['status']['error'];
                $mainwp_wpvivid_extension_activator->set_backup_report($site_id, $options);
            }
        }
        if($information['success_notice_html'] !== false){
            $ret['success_notice_html'] = __('<div class="notice notice-success is-dismissible inline" style="margin: 0; padding-top: 10px; margin-left: 0px !important;"><p>Backup task have been completed.</p>
                                    <button type="button" class="notice-dismiss" onclick="mwp_click_dismiss_notice(this);">
                                    <span class="screen-reader-text">Dismiss this notice.</span>
                                    </button>
                                    </div>');
        }
        if($information['error_notice_html'] !== false){
            $ret['error_notice_html'] = '<div class="notice notice-error inline" style="margin: 0; padding: 10px; margin-left: 0px !important;"><p>'.esc_html($information['error_notice_html']).'</p></div>';
        }
        $ret['need_refresh_remote'] = $information['need_refresh_remote'];
        return $ret;
    }

    static public function output_schedule_backup($schedule){
        $html = '';
        if($schedule['enable']){
            $schedule_status='Enabled';
            $next_backup_time=date("l, F d, Y H:i", $schedule['next_start']);
        }
        else{
            $schedule_status='Disabled';
            $next_backup_time='N/A';
        }
        $message = $schedule['last_message'];
        if(empty($message)){
            $last_message=__('The last backup message not found.', 'mainwp-wpvivid-extension');
        }
        else{
            $action_type = 'mwp_wpvivid_read_last_backup_log';
            $log_html = '<a onclick="mwp_wpvivid_read_log(\''.esc_js($action_type).'\', \''.esc_js($message['log_file_name']).'\');" style="cursor:pointer;">   Log</a>';
            if($message['status']['str'] == 'completed'){
                $backup_status='Succeeded';
                $last_message=$backup_status.', '.$message['status']['start_time'].$log_html;
            }
            elseif($message['status']['str'] == 'error'){
                $backup_status='Failed';
                $last_message=$backup_status.', '.$message['status']['start_time'].$log_html;
            }
            elseif($message['status']['str'] == 'cancel'){
                $backup_status='Failed';
                $last_message=$backup_status.', '.$message['status']['start_time'].$log_html;
            }
            else{
                $last_message=__('The last backup message not found.', 'mainwp-wpvivid-extension');
            }
        }
        $html.='<p><strong>Schedule Status:</strong>'.$schedule_status.'</p>';
        $html.='<p><strong>Server Time: </strong>'.date("l, F d, Y H:i",time()).'</p>';
        $html.='<p><strong>Last Backup: </strong>'.$last_message.'</p>';
        $html.='<p><strong>Next Backup:</strong>'.$next_backup_time.'</p>';
        $html.='<div style="clear:both;"></div>';
        return $html;
    }

    static public function output_backup_list($backup_list){
        $html='';
        foreach ($backup_list as $key=>$backup){
            $row_style = '';
            if($backup['type'] == 'Migration' || $backup['type'] == 'Upload')
            {
                if($backup['type'] == 'Migration')
                {
                    $upload_title = 'Received Backup: ';
                }
                else if($backup['type'] == 'Upload')
                {
                    $upload_title = 'Uploaded Backup: ';
                }
                else
                {
                    $upload_title='undefined';
                }
                $row_style = 'border: 2px solid #006799; box-sizing:border-box; -moz-box-sizing:border-box; -webkit-box-sizing:border-box;';
            }
            else if($backup['type'] == 'Manual' || $backup['type'] == 'Cron')
            {
                $row_style = '';
                $upload_title='';
            }
            else
            {
                $row_style = '';
                $upload_title='undefined';
            }

            if(empty($backup['lock'])){
                $backup_lock='/admin/images/unlocked.png';
                $lock_status='unlock';
            }
            else{
                if($backup['lock'] == 0){
                    $backup_lock='/admin/images/unlocked.png';
                    $lock_status='unlock';
                }
                else{
                    $backup_lock='/admin/images/locked.png';
                    $lock_status='lock';
                }
            }

            $remote=array();
            $remote=apply_filters('mwp_wpvivid_remote_pic', $remote);
            $remote_pic_html='';
            $save_local_pic_y = '/admin/images/storage-local.png';
            $save_local_pic_n = '/admin/images/storage-local(gray).png';
            $local_title = 'Localhost';
            if($backup['save_local'] == 1 || $backup['type'] == 'Migration'){
                $remote_pic_html .= '<img  src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $save_local_pic_y) . '" style="vertical-align:middle; " title="' . esc_attr($local_title) . '"/>';
            }
            else{
                $remote_pic_html .= '<img  src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $save_local_pic_n) . '" style="vertical-align:middle; " title="' . esc_attr($local_title) . '"/>';
            }
            $b_has_remote=false;
            if(is_array($remote)) {
                foreach ($remote as $key1 => $value1) {
                    foreach ($backup['remote'] as $storage_type) {
                        $b_has_remote=true;
                        if ($key1 === $storage_type['type']) {
                            $pic = $value1['selected_pic'];
                        } else {
                            $pic = $value1['default_pic'];
                        }
                    }
                    if(!$b_has_remote){
                        $pic = $value1['default_pic'];
                    }
                    $title = $value1['title'];
                    $remote_pic_html .= '<img  src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $pic) . '" style="vertical-align:middle; " title="' . esc_attr($title) . '"/>';
                }
            }

            $html .= '<tr style="'.$row_style.'">
                <th class="check-column"><input name="check_backup" type="checkbox" id="'.$key.'" value="'.$key.'" onclick="mwp_wpvivid_click_check_backup(\''.$key.'\');" /></th>
                <td class="tablelistcolumn">
                    <div style="float:left;padding:0 10px 10px 0;">
                        <div class="backuptime"><strong>'.$upload_title.'</strong>'.date('M d, Y H:i',$backup['create_time']).'</div>
                        <div class="common-table">
                            <span class="mwp-wpvivid-span" title="To lock the backup, the backup can only be deleted manually" id="wpvivid_lock_'.$key.'">
                            <img src="'.MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.$backup_lock.'" name="'.$lock_status.'" onclick="mwp_wpvivid_set_backup_lock(\''.$key.'\', \''.$lock_status.'\');" style="vertical-align:middle; cursor:pointer;"/>
                            </span>
                            <span>|</span> <span class="mwp-wpvivid-span">'.__('Type: ', 'mainwp-wpvivid-extension').'</span><span class="mwp-wpvivid-span">'.$backup['type'].'</span>
                            <span>|</span> <span class="mwp-wpvivid-span" title="Backup log"><a href="#" onclick="mwp_wpvivid_read_log(\'mwp_wpvivid_view_log\', \''.$key.'\');"><img src="'.MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/Log.png'.'" style="vertical-align:middle;cursor:pointer;"/><span>'.__('Log', 'mainwp-wpvivid-extension').'</span></a></span>
                        </div>
                    </div>
                </td>
                <td class="tablelistcolumn">
                    <div style="float:left;padding:10px 10px 10px 0;">'.$remote_pic_html.'</div>
                </td>
                <td class="tablelistcolumn" style="min-width:100px;">
                    <div id="wpvivid_file_part_'.esc_attr($key).'" style="float:left;padding:10px 10px 10px 0;">
                        <div style="cursor:pointer;" onclick="mwp_wpvivid_initialize_download(\''.$key.'\');" title="Prepare to download the backup">
                            <img id="wpvivid_download_btn_'.$key.'" src="'.MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/download.png'.'" style="vertical-align:middle;" /><span>'.__('Download', 'mainwp-wpvivid-extension').'</span>
                            <div class="spinner" id="wpvivid_download_loading_'.$key.'" style="float:right;width:auto;height:auto;padding:10px 180px 10px 0;background-position:0 0;"></div>
                        </div>
                    </div>
                </td>
                <td class="tablelistcolumn" style="min-width:100px;">
                    <div style="cursor:pointer;padding:10px 0 10px 0;" onclick="mwp_wpvivid_initialize_restore(\''.$key.'\',\''.date('M d, Y H:i',$backup['create_time']).'\',\''.$backup['type'].'\');" title="You will be redirected to child-site when clicking the button" style="float:left;padding:10px 10px 10px 0;">
                        <img src="'.MAINWP_WPVIVID_EXTENSION_PLUGIN_URL. '/admin/images/Restore.png'.'" style="vertical-align:middle;" /><span>'.__('Restore', 'mainwp-wpvivid-extension').'</span>
                    </div>
                </td>
                <td class="tablelistcolumn">
                    <div class="backuplist-delete-backup" style="padding:10px 0 10px 0;">
                        <img src="'.MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/Delete.png'.'" style="vertical-align:middle; cursor:pointer;" title="Delete the backup" onclick="mwp_wpvivid_delete_backup(\''.$key.'\');"/>
                    </div>
                </td>
            </tr>';
        }
        return $html;
    }

    static public function output_init_download_page($backup_id, $information){
        $html = '';
        $file_count=0;
        $file_part_num=1;
        foreach($information['files'] as $file_name => $value){
            if($file_part_num < 10){
                $format_part=sprintf("%02d", $file_part_num);
            }
            else{
                $format_part=$file_part_num;
            }
            if($value['status'] == 'need_download'){
                $information['files'][$file_name]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                <span>Part'.esc_html($format_part).'</span></br>
                                                <span id=\''.esc_attr($backup_id).'-text-part-'.esc_attr($file_part_num).'\'><a onclick="mwp_wpvivid_prepare_download(\''.esc_js($file_part_num).'\', \''.esc_js($backup_id).'\', \''.esc_js($file_name).'\');" style="cursor: pointer;">Prepare to Download</a></span></br>
                                                <div style="width:100%;height:5px; background-color:#dcdcdc;">
                                                   <div id=\''.esc_attr($backup_id).'-progress-part-'.esc_attr($file_part_num).'\' style="background-color:#7fb100; float:left;width:0;height:5px;"></div>
                                                </div>
                                                <span>size:</span><span>'.esc_html($value['size']).'</span>
                                             </div>';
            }
            else if($value['status'] == 'running'){
                $information['files'][$file_name]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                <span>Part'.esc_html($format_part).'</span></br>
                                                <span id=\''.esc_attr($backup_id).'-text-part-'.esc_attr($file_part_num).'\'><a >Retriving(remote storage to web server)</a></span></br>
                                                <div style="width:100%;height:5px; background-color:#dcdcdc;">
                                                    <div id=\''.esc_attr($backup_id).'-progress-part-'.esc_attr($file_part_num).'\' style="background-color:#7fb100; float:left;width:'.esc_attr($value['progress_text']).'%;height:5px;"></div>
                                                </div>
                                                <span>size:</span><span>'.esc_html($value['size']).'</span>
                                             </div>';
            }
            else if($value['status'] == 'timeout'){
                $information['files'][$file_name]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                 <span>Part'.esc_html($format_part).'</span></br>
                                                 <span id=\''.esc_attr($backup_id).'-text-part-'.esc_attr($file_part_num).'\'><a onclick="mwp_wpvivid_prepare_download(\''.esc_js($file_part_num).'\', \''.esc_js($backup_id).'\', \''.esc_js($file_name).'\');" style="cursor: pointer;">Prepare to Download</a></span></br>
                                                 <div style="width:100%;height:5px; background-color:#dcdcdc;">
                                                    <div id=\''.esc_attr($backup_id).'-progress-part-'.esc_attr($file_part_num).'\' style="background-color:#7fb100; float:left;width:'.esc_attr($value['progress_text']).'%;height:5px;"></div>
                                                 </div>
                                                 <span>size:</span><span>'.esc_html($value['size']).'</span>
                                             </div>';
            }
            else if($value['status'] == 'completed'){
                $information['files'][$file_name]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                 <span>Part'.esc_html($format_part).'</span></br>
                                                 <span id=\''.esc_attr($backup_id).'-text-part-'.esc_attr($file_part_num).'\'><a onclick="mwp_wpvivid_download(\''.esc_js($backup_id).'\', \''.esc_js($file_name).'\');" style="cursor: pointer;">Download</a></span></br>
                                                 <div style="width:100%;height:5px; background-color:#dcdcdc;">
                                                    <div id=\''.esc_attr($backup_id).'-progress-part-'.esc_attr($file_part_num).'\' style="background-color:#7fb100; float:left;width:100%;height:5px;"></div>
                                                 </div>
                                                 <span>size:</span><span>'.esc_html($value['size']).'</span>
                                             </div>';
            }
            else if($value['status'] == 'error'){
                $information['files'][$file_name]['html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px;">
                                                 <span>Part'.esc_html($format_part).'</span></br>
                                                 <span id=\''.esc_attr($backup_id).'-text-part-'.esc_attr($file_part_num).'\'><a onclick="mwp_wpvivid_prepare_download(\''.esc_js($file_part_num).'\', \''.esc_js($backup_id).'\', \''.esc_js($file_name).'\');" style="cursor: pointer;">Prepare to Download</a></span></br>
                                                 <div style="width:100%;height:5px; background-color:#dcdcdc;">
                                                    <div id=\''.esc_attr($backup_id).'-progress-part-'.esc_attr($file_part_num).'\' style="background-color:#7fb100; float:left;width:0;height:5px;"></div>
                                                 </div>
                                                 <span>size:</span><span>'.esc_html($value['size']).'</span>
                                             </div>';
            }
            $file_count++;
            $file_part_num++;
        }
        if ($file_count % 2 != 0) {
            $file_count++;
            if($file_count < 10){
                $format_part=sprintf("%02d", $file_count);
            }
            else{
                $format_part=$file_count;
            }
            $information['files']['place_html']='<div style="float:left;margin:10px 10px 10px 0;text-align:center; width:180px; color:#cccccc;">
                                        <span>Part'.esc_html($format_part).'</span></br>
                                        <span>Download</span></br>
                                        <div style="width:100%;height:5px; background-color:#dcdcdc;">
                                            <div style="background-color:#7fb100; float:left;width:0;height:5px;"></div>
                                        </div>
                                        <span>size:</span><span>0</span>
                                    </div>';
        }
        else{
            $information['files']['place_html']='';
        }
        return $information;
    }

    static public function output_default_remote($remote_storage_type){
        $html='';
        $remote=array();
        $remote=apply_filters('mwp_wpvivid_remote_pic', $remote);
        if(is_array($remote))
        {
            foreach ($remote as $key => $value)
            {
                $title = $value['title'];
                if ($key === $remote_storage_type)
                {
                    $pic = $value['selected_pic'];
                } else {
                    $pic = $value['default_pic'];
                }
                $html .= '<img  src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $pic) . '" style="vertical-align:middle; " title="' . esc_attr($title) . '"/>';
            }
        }
        return $html;
    }

    static public function output_database_table($base_tables, $other_tables){
        $html = '';
        $base_table_html = '';
        $base_tables_html = '';
        $has_base_table = false;
        $base_table_all_check = true;
        foreach ($base_tables as $base_table){
            if($base_table["table_check"] !== 'checked'){
                $base_table_all_check = false;
            }
            $has_base_table = true;
            $base_table_html .= '<div class="wpvivid-text-line">
                                <input type="checkbox" option="mwp_base_db" name="Database" value="'.esc_html($base_table["table_name"]).'" '.esc_html($base_table["table_check"]).' />
                                <span class="wpvivid-text-line">'.esc_html($base_table["table_name"]).'|Rows:'.$base_table["table_row"].'|Size:'.$base_table["table_size"].'</span>
                            </div>';

        }

        $other_table_html = '';
        $other_tables_html = '';
        $has_other_table = false;
        $other_table_all_check = true;
        foreach ($other_tables as $other_table){
            if($other_table['table_check'] !== 'checked'){
                $other_table_all_check = false;
            }
            $has_other_table = true;
            $other_table_html .= '<div class="wpvivid-text-line">
                                 <input type="checkbox" option="mwp_other_db" name="Database" value="'.esc_html($other_table["table_name"]).'" '.esc_html($other_table["table_check"]).' />
                                 <span class="wpvivid-text-line">'.esc_html($other_table["table_name"]).'|Rows:'.$other_table["table_row"].'|Size:'.$other_table["table_size"].'</span>
                             </div>';
        }

        if($base_table_all_check){
            $base_table_all_check = 'checked';
        }
        else{
            $base_table_all_check = '';
        }
        if($other_table_all_check){
            $other_table_all_check = 'checked';
        }
        else{
            $other_table_all_check = '';
        }

        //$base_table_html .= '<div style="clear:both;"></div>';
        //$other_table_html .= '<div style="clear:both;"></div>';

        $ret['database_html'] = '<div style="padding-left:2em;margin-top:1em;">
								    <div style="border-bottom:1px solid rgb(204, 204, 204);"></div>
								 </div>';

        if($has_base_table) {
            $base_all_check = '';
            if($base_table_all_check){
                $base_all_check = 'checked';
            }

            $base_tables_html .= '<div style="width:30%;float:left;box-sizing:border-box;padding-left:2em;padding-right:0.5em;">
                                    <div style="margin-top: 10px; margin-bottom: 10px;">
                                        <p>
                                            <span class="dashicons dashicons-list-view mwp-wpvivid-dashicons-blue"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="mwp-wpvivid-database-table-check mwp-wpvivid-database-base-table-check" '.esc_attr($base_all_check).'></span>
												<span><strong>Wordpress default tables</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="padding-bottom:0.5em;"><span><input type="text" class="mwp-wpvivid-select-base-table-text" placeholder="Filter Tables">
									    <input type="button" value="Filter" class="button mwp-wpvivid-select-base-table-button" style="position: relative; z-index: 1;"></span>
									</div>
                                    <div class="mwp-wpvivid-database-base-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
                                        '.$base_table_html.'
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>';
        }

        if($has_other_table) {
            $other_all_check = '';
            if($other_table_all_check){
                $other_all_check = 'checked';
            }

            $other_tables_html .= '<div style="width:70%;float:left;box-sizing:border-box;padding-left:0.5em;">
                                    <div style="margin-top: 10px; margin-bottom: 10px;">
                                        <p>
                                            <span class="dashicons dashicons-list-view mwp-wpvivid-dashicons-green"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="mwp-wpvivid-database-table-check mwp-wpvivid-database-other-table-check" '.esc_attr($other_all_check).'></span>
												<span><strong>Tables created by plugins or themes</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="padding-bottom:0.5em;"><span><input type="text" class="mwp-wpvivid-select-other-table-text" placeholder="Filter Tables">
									    <input type="button" value="Filter" class="button mwp-wpvivid-select-other-table-button" style="position: relative; z-index: 1;"></span>
									</div>
                                    <div class="mwp-wpvivid-database-other-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
                                        '.$other_table_html.'
                                    </div>
                                 </div>';
        }

        $div = '<div style="clear:both;"></div>';
        $div .= '<div style="margin-bottom: 10px;"></div>';
        $database_table_html = $ret['database_html'] . $base_tables_html . $other_tables_html;

        $html = $database_table_html;

        return $html;
    }

    static public function output_filter_database_table($table_type, $table_arr){
        $base_table_html = '';
        $option = 'mwp_base_db';
        if($table_type === 'base_table')
        {
            $option = 'mwp_base_db';
        }
        else if($table_type === 'other_table')
        {
            $option = 'mwp_other_db';
        }
        foreach ($table_arr as $table_info)
        {
            $base_table_html .= '<div class="wpvivid-text-line">
                                <input type="checkbox" option="'.esc_html($option).'" name="Database" value="'.esc_html($table_info["table_name"]).'" '.esc_html($table_info["table_check"]).' />
                                <span class="wpvivid-text-line">'.esc_html($table_info["table_name"]).'|Rows:'.$table_info["table_row"].'|Size:'.$table_info["table_size"].'</span>
                            </div>';

        }
        return $base_table_html;
    }

    static public function output_edit_schedule_database_table($base_tables, $other_tables, $global, $site_id='', $schedule_id=''){
        $exclude_tables = array();
        if($global)
        {

        }
        else
        {
            $schedule_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'schedule_addon', array());
            if(isset($schedule_addon[$schedule_id]))
            {
                $schedule_info = $schedule_addon[$schedule_id];
                if(isset($schedule_info['backup']['custom_dirs']['exclude-tables'])){
                    $exclude_tables = $schedule_info['backup']['custom_dirs']['exclude-tables'];
                }
            }
        }

        $html = '';
        $base_table_html = '';
        $base_tables_html = '';
        $has_base_table = false;
        $base_table_all_check = true;
        foreach ($base_tables as $base_table){
            $checked = 'checked';
            if (in_array($base_table["table_name"], $exclude_tables))
            {
                $checked = '';
                $base_table_all_check = false;
            }
            $has_base_table = true;
            $base_table_html .= '<div class="wpvivid-text-line">
                                <input type="checkbox" option="mwp_base_db" name="Database" value="'.esc_html($base_table["table_name"]).'" '.esc_html($checked).' />
                                <span class="wpvivid-text-line">'.esc_html($base_table["table_name"]).'|Rows:'.$base_table["table_row"].'|Size:'.$base_table["table_size"].'</span>
                            </div>';

        }

        $other_table_html = '';
        $other_tables_html = '';
        $has_other_table = false;
        $other_table_all_check = true;
        foreach ($other_tables as $other_table){
            $checked = 'checked';
            if (in_array($other_table["table_name"], $exclude_tables))
            {
                $checked = '';
                $other_table_all_check = false;
            }
            $has_other_table = true;
            $other_table_html .= '<div class="wpvivid-text-line">
                                 <input type="checkbox" option="mwp_other_db" name="Database" value="'.esc_html($other_table["table_name"]).'" '.esc_html($checked).' />
                                 <span class="wpvivid-text-line">'.esc_html($other_table["table_name"]).'|Rows:'.$other_table["table_row"].'|Size:'.$other_table["table_size"].'</span>
                             </div>';
        }

        if($base_table_all_check){
            $base_table_all_check = 'checked';
        }
        else{
            $base_table_all_check = '';
        }
        if($other_table_all_check){
            $other_table_all_check = 'checked';
        }
        else{
            $other_table_all_check = '';
        }

        $ret['database_html'] = '<div style="padding-left:2em;margin-top:1em;">
								    <div style="border-bottom:1px solid rgb(204, 204, 204);"></div>
								 </div>';

        if($has_base_table) {
            $base_all_check = '';
            if($base_table_all_check){
                $base_all_check = 'checked';
            }

            $base_tables_html .= '<div style="width:30%;float:left;box-sizing:border-box;padding-left:2em;padding-right:0.5em;">
                                    <div>
                                        <p>
                                            <span class="dashicons dashicons-list-view mwp-wpvivid-dashicons-blue"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="mwp-wpvivid-database-table-check mwp-wpvivid-database-base-table-check" '.esc_attr($base_all_check).'></span>
												<span><strong>Wordpress default tables</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="padding-bottom:0.5em;"><span><input type="text" class="mwp-wpvivid-select-base-table-text" placeholder="Filter Tables">
									    <input type="button" value="Filter" class="button mwp-wpvivid-select-base-table-button" style="position: relative; z-index: 1;"></span>
									</div>
                                    <div class="mwp-wpvivid-database-base-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
                                        '.$base_table_html.'
                                    </div>
                                    <div style="clear:both;"></div>
                                </div>';
        }

        if($has_other_table) {
            $other_all_check = '';
            if($other_table_all_check){
                $other_all_check = 'checked';
            }

            $other_tables_html .= '<div style="width:70%;float:left;box-sizing:border-box;padding-left:0.5em;">
                                    <div>
                                        <p>
                                            <span class="dashicons dashicons-list-view mwp-wpvivid-dashicons-green"></span>
                                            <label title="Check/Uncheck all">
                                                <span><input type="checkbox" class="mwp-wpvivid-database-table-check mwp-wpvivid-database-other-table-check" '.esc_attr($other_all_check).'></span>
												<span><strong>Tables created by plugins or themes</strong></span>
											</label>
                                        </p>
                                    </div>
                                    <div style="padding-bottom:0.5em;"><span><input type="text" class="mwp-wpvivid-select-other-table-text" placeholder="Filter Tables">
									    <input type="button" value="Filter" class="button mwp-wpvivid-select-other-table-button" style="position: relative; z-index: 1;"></span>
									</div>
                                    <div class="mwp-wpvivid-database-other-list" style="height:250px;border:1px solid rgb(204, 204, 204);padding:0.2em 0.5em;overflow:auto;">
                                        '.$other_table_html.'
                                    </div>
                                 </div>';
        }

        $div = '<div style="clear:both;"></div>';
        $div .= '<div style="margin-bottom: 10px;"></div>';
        $database_table_html = $base_tables_html . $other_tables_html;

        $html = $database_table_html;

        return $html;
    }

    static public function output_themes_plugins_table($themes, $plugins){
        $html = '';
        $theme_html = '';
        $themes_html = '';
        $themes_all_check = true;
        foreach ($themes as $theme){
            if($theme["theme_check"] !== 'checked'){
                $themes_all_check = false;
            }

                $theme_html .= '<div class="mwp-wpvivid-custom-database-table-column">
                                            <label style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                            title="'.esc_html($theme['theme_name']).'|Size:'.$theme["theme_size"].'">
                                                <input type="checkbox" option="mwp_themes" name="Themes" value="'.esc_attr($theme['theme_name']).'" '.esc_html($theme['theme_check']).' />
                                                '.esc_html($theme['theme_name']).'|Size:'.$theme["theme_size"].'
                                            </label>
                                        </div>';

        }

        $plugin_html = '';
        $plugins_html = '';
        $plugins_all_check = true;

        foreach ($plugins as $plugin){
            if($plugin["plugin_check"] !== 'checked'){
                $plugins_all_check = false;
            }

                $plugin_html .= '<div class="mwp-wpvivid-custom-database-table-column">
                                            <label style="width:100%;overflow: hidden;text-overflow: ellipsis;white-space: nowrap; padding-top: 3px;" 
                                            title="'.esc_html($plugin['plugin_display_name']).'|Size:'.$plugin["plugin_size"].'">
                                            <input type="checkbox" option="mwp_plugins" name="Plugins" value="'.esc_attr($plugin['plugin_slug_name']).'" '.esc_html($plugin['plugin_check']).' />
                                            '.esc_html($plugin['plugin_display_name']).'|Size:'.$plugin["plugin_size"].'</label>
                                        </div>';

        }

        if($themes_all_check){
            $themes_all_check = 'checked';
        }
        else{
            $themes_all_check = '';
        }
        if($plugins_all_check){
            $plugins_all_check = 'checked';
        }
        else{
            $plugins_all_check = '';
        }

        $theme_html .= '<div style="clear:both;"></div>';
        $plugin_html .= '<div style="clear:both;"></div>';

        $themes_html .= '<div class="mwp-wpvivid-custom-database-wp-table-header" style="border:1px solid #e5e5e5;">
                                        <label><input type="checkbox" class="mwp-wpvivid-themes-plugins-table-check mwp-wpvivid-themes-all-check" '.esc_attr($themes_all_check).' />Themes</label>
                                     </div>
                                     <div class="mwp-wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        '.$theme_html.'
                                     </div>';

        $plugins_html .= '<div class="mwp-wpvivid-custom-database-other-table-header" style="border:1px solid #e5e5e5;">
                                        <label><input type="checkbox" class="mwp-wpvivid-themes-plugins-table-check mwp-wpvivid-plugins-all-check" '.esc_attr($plugins_all_check).' />Plugins</label>
                                     </div>
                                     <div class="mwp-wpvivid-database-table-addon" style="border:1px solid #e5e5e5; border-top: none; padding: 0 4px 4px 4px; max-height: 300px; overflow-y: auto; overflow-x: hidden;">
                                        '.$plugin_html.'
                                     </div>';
        $div = '<div style="clear:both;"></div>';
        $div .= '<div style="margin-bottom: 10px;"></div>';
        $themes_plugins_html = $themes_html . $div . $plugins_html;

        $html = $themes_plugins_html;
        return $html;
    }

    static public function output_additional_database_table($database_array){
        $database_html = '';
        foreach ($database_array as $database)
        {
            $database_html .= '<div class="wpvivid-text-line"><span class="dashicons dashicons-plus-alt wpvivid-icon-16px mwp-wpvivid-add-additional-db" option="mwp_additional_db" name="'.$database.'"></span><span class="wpvivid-text-line">'.esc_html($database).'</span></div>';
        }
        return $database_html;
    }

    static public function output_additional_database_list($data){
        $html = '';
        foreach ($data as $database => $db_info)
        {
            $html .= '<div class="wpvivid-text-line" database-name="'.$database.'" database-host="'.$db_info['db_host'].'" database-user="'.$db_info['db_user'].'" database-pass="'.$db_info['db_pass'].'"><span class="dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-additional-database-remove" database-name="'.$database.'"></span><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line" option="additional_db_custom" name="'.$database.'">'.$database.'@'.$db_info['db_host'].'</span></div>';
        }
        return $html;
    }

    static public function output_init_download_page_addon($files, $backup_id, $page=1){
        $files_list=new Mainwp_WPvivid_Files_List();
        $files_list->set_files_list($files, $backup_id, $page);
        $files_list->prepare_items();
        ob_start();
        $files_list->display();
        $html = ob_get_clean();
        return $html;
    }

    static public function output_download_progress_addon($backup_files){
        $ret=array();
        foreach ($backup_files as $file => $data){
            if($data['status'] === 'need_download'){
                $ret[$file]['status']='need_download';
                $ret[$file]['html']='<div class="mwp-wpvivid-block-bottom-space">
                                                                        <span class="mwp-wpvivid-block-right-space">Retriving (remote storage to web server)</span><span class="mwp-wpvivid-block-right-space">|</span><span>File Size: </span><span class="mwp-wpvivid-block-right-space">'.$data['file_size'].'</span><span class="mwp-wpvivid-block-right-space">|</span><span>Downloaded Size: </span><span>0</span>
                                                                   </div>
                                                                   <div style="width:100%;height:10px; background-color:#dcdcdc;">
                                                                        <div style="background-color:#0085ba; float:left;width:0%;height:10px;"></div>
                                                                   </div>';
            }
            else if($data['status'] === 'running'){
                $ret[$file]['status']='running';
                $ret[$file]['html']='<div class="mwp-wpvivid-block-bottom-space">
                                                                            <span class="mwp-wpvivid-block-right-space">Retriving (remote storage to web server)</span><span class="mwp-wpvivid-block-right-space">|</span><span>File Size: </span><span class="mwp-wpvivid-block-right-space">'.$data['file_size'].'</span><span class="mwp-wpvivid-block-right-space">|</span><span>Downloaded Size: </span><span>'.$data['downloaded_size'].'</span>
                                                                        </div>
                                                                        <div style="width:100%;height:10px; background-color:#dcdcdc;">
                                                                            <div style="background-color:#0085ba; float:left;width:'.$data['progress_text'].'%;height:10px;"></div>
                                                                        </div>';
            }
            else if($data['status'] === 'timeout') {
                $ret[$file]['status']='completed';
                $ret[$file]['html']='<div class="mwp-wpvivid-block-bottom-space">
                                                                            <span>Download timeout, please retry.</span>
                                                                         </div>
                                                                         <div>
                                                                            <span>' . __('File Size: ', 'wpvivid') . '</span><span class="mwp-wpvivid-block-right-space">' . $data['file_size'] . '</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space"><a class="mwp-wpvivid-prepare-download" style="cursor: pointer;">Prepare to Download</a></span>
                                                                        </div>';
            }
            else if($data['status'] === 'completed'){
                $ret[$file]['status']='completed';
                $ret[$file]['html']='<span>'.__('File Size: ', 'wpvivid').'</span><span class="mwp-wpvivid-block-right-space">'.$data['file_size'].'</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-ready-download"><a style="cursor: pointer;">Download</a></span>';
            }
            else if($data['status'] === 'error'){
                $ret[$file]['status']='error';
                $ret[$file]['html']='<div class="mwp-wpvivid-block-bottom-space">
                                                                            <span>'.$data['error'].'</span>
                                                                         </div>
                                                                         <div>
                                                                            <span>'.__('File Size: ', 'wpvivid').'</span><span class="mwp-wpvivid-block-right-space">'.$data['file_size'].'</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space"><a class="mwp-wpvivid-prepare-download" style="cursor: pointer;">Prepare to Download</a></span>
                                                                         </div>';
            }
        }
        return $ret;
    }

    static public function output_remote_backup_page_addon($remote_list, $select_remote_id){
        $has_remote = false;
        foreach ($remote_list as $key => $value) {
            if ($key === 'remote_selected') {
                continue;
            }
            else {
                $has_remote = true;
            }
        }
        if($select_remote_id === '') {
            $first_remote_path = 'Common';
            foreach ($remote_list as $key => $value) {
                if ($key === 'remote_selected') {
                    continue;
                }
                else {
                    if(isset($value['custom_path'])) {
                        if(isset($value['root_path']))
                        {
                            $path = $value['path'].untrailingslashit($value['root_path']).'/'.$value['custom_path'];
                        }
                        else
                        {
                            $path = $value['path'].'wpvividbackuppro/'.$value['custom_path'];
                        }
                    }
                    else {
                        $path = $value['path'];
                    }
                    if($first_remote_path === 'Common'){
                        $first_remote_path = $path;
                    }
                }
            }
            $path = $first_remote_path;
        }
        else{
            if (isset($remote_list[$select_remote_id]))
            {
                if(isset($remote_list[$select_remote_id]['custom_path']))
                {
                    if(isset($remote_list[$select_remote_id]['root_path']))
                    {
                        $path = $remote_list[$select_remote_id]['path'].untrailingslashit($remote_list[$select_remote_id]['root_path']).'/'.$remote_list[$select_remote_id]['custom_path'];
                    }
                    else
                    {
                        $path = $remote_list[$select_remote_id]['path'].'wpvividbackuppro/'. $remote_list[$select_remote_id]['custom_path'];
                    }
                }
                else
                {
                    $path = $remote_list[$select_remote_id]['path'];
                }
            }
            else
            {
                $path='Common';
            }
        }
        $remote_storage_option = '';
        foreach ($remote_list as $key=>$value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            $check_status = '';
            if($key === $select_remote_id){
                $check_status = 'selected';
            }
            $value['type'] = apply_filters('mwp_wpvivid_storage_provider_tran', $value['type']);
            $remote_storage_option .= '<option value="' . $key . '" '.$check_status.'>' . $value['type'] . ' -> ' . $value['name'] . '</option>';
        }

        if($has_remote){
            $html = '<div class="mwp-quickstart-storage-setting">
                        <div style="padding: 10px 0;">
                            <div class="mwp-wpvivid-font-right-space" style="float: left;">Current Folder Path:</div>
                            <div id="mwp_wpvivid_remote_folder" style="float: left;">'.$path.'</div>
                            <div style="clear: both;"></div>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="clear: both;"></div>

                    <div class="mwp-wpvivid-block-bottom-space">
                        <div style="float: left;">
                            <div>Display all backups stored in account
                                <select id="mwp_wpvivid_select_remote_storage" onchange="mwp_wpvivid_select_remote_storage_folder();">'.$remote_storage_option.'</select> under
                                <select id="mwp_wpvivid_select_remote_folder" onchange="mwp_wpvivid_select_remote_storage_folder();">
                                    <option value="Common">'.$path.'</option>
                                    <option value="Migrate">Migration</option>
                                    <option value="Rollback">Rollback</option>
                                    <option value="Incremental">Incremental</option>
                                </select> folder.
                            </div>
                        </div>
                        <div style="float: left; margin-left: 5px; height: 30px; line-height: 30px;">
                            <a onclick="mwp_wpvivid_explanation_folders();" style="cursor: pointer;">Explanation about these folders.</a>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
    
                    <div class="mwp-wpvivid-click-popup mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_explanation_folders" style="display: none; padding: 0 0 0 10px;">
                        <ul>
                            <li><i id="mwp_wpvivid_explanation_backup_folder">'.$path.'</i> Folder is where the manual backups and scheduled backups are stored on your cloud storage. 
                                <a href="https://wpvivid.com/wpvivid-backup-pro-custom-backup-folder" target="_blank">Learn more</a></li>
                            <li><i>Migrate</i> Folder is where the backups for migration are stored on your cloud storage.
                                <a href="https://wpvivid.com/wpvivid-backup-pro-migration-folder" target="_blank">Learn more</a></li>
                            <li><i id="mwp_wpvivid_explanation_rollback_folder">'.$path.'/rollback</i> Folder is where the backups created before updating are stored. You can disable this feature in Settings. 
                                <a href="https://wpvivid.com/wpvivid-backup-pro-rollback-folder" target="_blank">Learn more</a></li>
                        </ul>
                    </div>
                    <div style="clear: both;"></div>
    
                    <div style="margin-bottom: 10px;">
                        <input class="ui green mini button" id="mwp_wpvivid_sync_remote_folder" type="button" value="Scan The Folder" onclick="mwp_wpvivid_select_remote_folder();" style="float: left;"/>
                        <div class="spinner" id="mwp_wpvivid_scanning_remote_folder" style="float: left;"></div>
                        <div style="clear: both;"></div>
                    </div>
                    <div class="mwp-wpvivid-remote-sync-error" style="display: none;"></div>
                    <div style="clear: both;"></div>';
        }
        else{
            $html = '<div class="mwp-quickstart-storage-setting mwp-wpvivid-block-bottom-space">
                        <div style="padding: 10px 0;">
                            <span style="margin-right: 0;">There is no remote storage available, please set it up first and sync.</span>
                        </div>
                    </div>';
        }

        return $html;
    }

    static public function output_remote_list_addon($remote_list)
    {
        $remote_list_html='<option value="all_backup" selected="selected">--</option>';
        foreach ($remote_list as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }

            $remote_list_html.='<option value="'.$value['id'].'">'.$value['name'].'</option>';
        }
        return $remote_list_html;
    }

    static public function output_rollback_remote_list_addon($site_id, $remote_list)
    {
        $remote_list_html = '';
        $setting_addon=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'settings_addon', array());
        $rollback_remote = isset($setting_addon['wpvivid_rollback_remote']) ? $setting_addon['wpvivid_rollback_remote'] : 0;
        $remote_id = isset($setting_addon['wpvivid_rollback_remote_id']) ? $setting_addon['wpvivid_rollback_remote_id'] : 0;
        if($rollback_remote)
        {
            $remote_list_html .= '<option value="-1">Choose cloud storage</option>';
        }
        else
        {
            $remote_list_html .= '<option value="-1" selected="selected" >Choose cloud storage</option>';
        }

        foreach ($remote_list as $key => $value)
        {
            if($key === 'remote_selected')
            {
                continue;
            }

            if(!isset($remote_option['id']))
            {
                $remote_option['id'] = $key;
            }

            if($remote_id==$key&&$rollback_remote)
            {
                $remote_list_html .= '<option value="'.$value['id'].'" selected="selected">'.$value['name'].'</option>';
            }
            else
            {
                $remote_list_html .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
            }
        }
        return $remote_list_html;
    }
}