<?php

class Mainwp_WPvivid_Extension_Capability
{
    private $capability_addon;
    private $site_id;

    public function __construct()
    {
        $this->load_capability_ajax();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_capability_info($capability_addon = array())
    {
        $this->capability_addon=$capability_addon;
    }

    public function load_capability_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_sync_menu_capability', array($this, 'sync_menu_capability'));
        add_action('wp_ajax_mwp_wpvivid_save_menu_capability_addon', array($this, 'save_menu_capability_addon'));
        add_action('wp_ajax_mwp_wpvivid_save_global_menu_capability_addon', array($this, 'save_global_menu_capability_addon'));
    }

    public function sync_menu_capability()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['id']);
                $post_data['mwp_action'] = 'wpvivid_set_menu_capability_addon_mainwp';

                $capability_addon = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('menu_capability', array());
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
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'menu_capability', $capability_addon);

                $post_data['menu_cap'] = wp_json_encode($capability_addon);
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

    public function save_menu_capability_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['caps']) && !empty($_POST['caps']) && is_string($_POST['caps'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_set_menu_capability_addon_mainwp';

                $json = stripslashes(sanitize_text_field($_POST['caps']));
                $caps = json_decode($json, true);
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'menu_capability', $caps);

                $post_data['menu_cap'] = wp_json_encode($caps);
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

    public function save_global_menu_capability_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['caps']) && !empty($_POST['caps']) && is_string($_POST['caps'])) {
                $json = stripslashes(sanitize_text_field($_POST['caps']));
                $caps = json_decode($json, true);
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('menu_capability', $caps);

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

    public function render($check_pro, $global=false)
    {
        if(isset($_GET['synchronize']) && isset($_GET['addon']))
        {
            $check_addon = sanitize_text_field($_GET['addon']);
            $this->mwp_wpvivid_synchronize_menu_capability($check_addon);
        }
        else{
            $cap_list = $this->capability_addon;
            ?>
            <div style="margin: 10px;">
                <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
                    <div class="mwp-wpvivid-welcome-bar-left">
                        <p><span class="dashicons dashicons-admin-generic mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-blue"></span><span class="mwp-wpvivid-page-title">Modules</span></p>
                        <span class="about-description">In this tab, you have the option to choose to hide WPvivid Backup Pro plugin modules on child sites.</span>
                    </div>
                    <div class="mwp-wpvivid-welcome-bar-right"></div>
                </div>

                <div style="margin-top:10px; margin-bottom: 10px;"><p><strong>Select Modules and Hide</strong></p></div>
                <table class="wp-list-table widefat plugins">
                    <thead>
                    <tr>
                        <th>Menu</th>
                        <th>Display</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($cap_list as $key=>$value){
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($this->menu_transfer($key)); ?>
                            </td>
                            <td>
                                <?php
                                if($value == '1'){
                                    ?>
                                    <input type="checkbox" name="mwp_wpvivid_caps" value="<?php echo esc_attr($key); ?>" checked />
                                    <?php
                                }
                                else{
                                    ?>
                                    <input type="checkbox" name="mwp_wpvivid_caps" value="<?php echo esc_attr($key); ?>" />
                                    <?php
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <div style="margin-top: 10px;">
                    <?php
                    if ($global === false) {
                        $save_change_id = 'mwp_wpvivid_capability_save_addon';
                    } else {
                        $save_change_id = 'mwp_wpvivid_global_capability_save_addon';
                    }
                    ?>
                    <input class="ui green mini button" id="<?php echo esc_attr($save_change_id); ?>" type="button" value="<?php esc_attr_e('Save Changes and Sync'); ?>" />
                </div>
            </div>
            <script>
                jQuery('#mwp_wpvivid_capability_save_addon').click(function(){
                    var cap_option = {};
                    jQuery('input:checkbox[name=mwp_wpvivid_caps]').each(function()
                    {
                        var value = jQuery(this).val();
                        if(jQuery(this).prop('checked')) {
                            cap_option[value]=1;
                        }
                        else {
                            cap_option[value]=0;
                        }
                    });
                    var caps=JSON.stringify(cap_option);
                    var ajax_data= {
                        'action': 'mwp_wpvivid_save_menu_capability_addon',
                        'caps':caps,
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    jQuery('#mwp_wpvivid_capability_save_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        jQuery('#mwp_wpvivid_capability_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
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
                        jQuery('#mwp_wpvivid_capability_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                });

                jQuery('#mwp_wpvivid_global_capability_save_addon').click(function(){
                    var cap_option = {};
                    jQuery('input:checkbox[name=mwp_wpvivid_caps]').each(function()
                    {
                        var value = jQuery(this).val();
                        if(jQuery(this).prop('checked')) {
                            cap_option[value]=1;
                        }
                        else {
                            cap_option[value]=0;
                        }
                    });
                    var caps=JSON.stringify(cap_option);
                    var ajax_data= {
                        'action': 'mwp_wpvivid_save_global_menu_capability_addon',
                        'caps':caps
                    };
                    jQuery('#mwp_wpvivid_global_capability_save_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        jQuery('#mwp_wpvivid_global_capability_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                window.location.href = window.location.href + "&synchronize=1&addon=1";
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        jQuery('#mwp_wpvivid_global_capability_save_addon').css({'pointer-events': 'auto', 'opacity': '1'});
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                });
            </script>
            <?php
        }
    }

    public function menu_transfer($menu)
    {
        switch ($menu){
            case 'menu_manual_backup':
                $menu = 'Manual Backup';
                break;
            case 'menu_export_site':
                $menu = 'Export Site';
                break;
            case 'menu_import_site':
                $menu = 'Import Site';
                break;
            case 'menu_backup_schedule':
                $menu = 'Backup Schedule';
                break;
            case 'menu_backup_restore':
                $menu = 'Backup Manager';
                break;
            case 'menu_cloud_storage':
                $menu = 'Cloud Storage';
                break;
            case 'menu_database_snapshot':
                $menu = 'Database Snapshots';
                break;
            case 'menu_image_optimization':
                $menu = 'Image Optimization';
                break;
            case 'menu_unused_image_cleaner':
                $menu = 'Unused Image Cleaner';
                break;
            case 'menu_staging':
                $menu = 'Staging Sites';
                break;
            case 'menu_export_import':
                $menu = 'Export/Import Page';
                break;
            case 'menu_rollback':
                $menu = 'Rollback';
                break;
            case 'menu_role_capabilities':
                $menu = 'Roles & Capabilities';
                break;
            case 'menu_setting':
                $menu = 'Settings';
                break;
            case 'menu_debug':
                $menu = 'Debug';
                break;
            case 'menu_tools':
                $menu = 'Tools';
                break;
            case 'menu_log':
                $menu = 'Log';
                break;
            case 'menu_pro_page':
                $menu = 'License';
                break;
            default:
                break;
        }
        return $menu;
    }

    public function mwp_wpvivid_synchronize_menu_capability($check_addon){
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->render_sync_websites_page('mwp_wpvivid_sync_menu_capability', $check_addon);
        ?>
        <script>
            function mwp_wpvivid_sync_menu_capability()
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
                    jQuery('#mwp_wpvivid_sync_menu_capability').css({'pointer-events': 'none', 'opacity': '0.4'});
                    var check_addon = '<?php echo esc_js($check_addon); ?>';
                    mwp_wpvivid_sync_site(website_ids,check_addon,'mwp_wpvivid_sync_menu_capability','Extensions-Wpvivid-Backup-Mainwp&tab=menu','mwp_wpvivid_menu_tab');
                }
            }
            jQuery('#mwp_wpvivid_sync_menu_capability').click(function(){
                mwp_wpvivid_sync_menu_capability();
            });
        </script>
        <?php
    }
}