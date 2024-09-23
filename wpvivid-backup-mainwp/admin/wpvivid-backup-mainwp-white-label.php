<?php

class Mainwp_WPvivid_Extension_White_Label
{
    private $white_label_addon;
    private $site_id;

    public function __construct()
    {
        $this->load_white_label_ajax();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_white_label_info($white_label_addon = array())
    {
        $this->white_label_addon=$white_label_addon;
    }

    public function load_white_label_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_sync_white_label', array($this, 'sync_white_label'));
        add_action('wp_ajax_mwp_wpvivid_global_set_white_label_setting', array($this, 'global_set_white_label_setting'));
        add_action('wp_ajax_mwp_wpvivid_set_white_label_setting', array($this, 'set_white_label_setting'));
    }

    public function sync_white_label()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['id']);

                $white_label = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('white_label_setting', array());
                if(empty($white_label)){
                    $white_label = array();
                }
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'white_label_setting', $white_label);

                $post_data['mwp_action'] = 'wpvivid_set_white_label_setting_addon_mainwp';
                $post_data['setting'] = wp_json_encode($white_label);
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

    public function global_set_white_label_setting()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['setting']) && !empty($_POST['setting']) && is_string($_POST['setting'])) {
                $json_setting = stripslashes(sanitize_text_field($_POST['setting']));
                $setting = json_decode($json_setting, true);
                if (is_null($setting))
                {
                    echo 'json decode failed';
                    die();
                }
                $ret = $mainwp_wpvivid_extension_activator->mwp_check_white_label_option($setting);
                if($ret['result']!='success')
                {
                    echo wp_json_encode($ret);
                    die();
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('white_label_setting', $setting);
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

    public function set_white_label_setting()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['setting']) && !empty($_POST['setting']) && is_string($_POST['setting'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $json_setting = stripslashes(sanitize_text_field($_POST['setting']));
                $setting = json_decode($json_setting, true);
                if (is_null($setting))
                {
                    echo 'json decode failed';
                    die();
                }
                $ret = $mainwp_wpvivid_extension_activator->mwp_check_white_label_option($setting);
                if($ret['result']!='success')
                {
                    echo wp_json_encode($ret);
                    die();
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'white_label_setting', $setting);

                $post_data['mwp_action'] = 'wpvivid_set_white_label_setting_addon_mainwp';
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
            $this->mwp_wpvivid_synchronize_white_label($check_addon);
        }
        else{
            $white_label_setting = $this->white_label_addon;
            $white_label_display = empty($white_label_setting['white_label_display']) ? 'WPvivid Backup' : $white_label_setting['white_label_display'];
            $white_label_slug = empty($white_label_setting['white_label_slug']) ? 'WPvivid' : $white_label_setting['white_label_slug'];
            $white_label_support_email = empty($white_label_setting['white_label_support_email']) ? 'pro.support@wpvivid.com' : $white_label_setting['white_label_support_email'];
            $white_label_website_protocol = empty($white_label_setting['white_label_website_protocol']) ? 'https' : $white_label_setting['white_label_website_protocol'];
            $white_label_website = empty($white_label_setting['white_label_website']) ? 'wpvivid.com' : $white_label_setting['white_label_website'];

            $white_label_author = empty($white_label_setting['white_label_author']) ? 'wpvivid.com' : $white_label_setting['white_label_author'];
            $wpvivid_access_white_label_slug= empty($white_label_setting['access_white_label_page_slug']) ? 'wpvivid_white_label' : $white_label_setting['access_white_label_page_slug'];
            $show_sidebar= empty($white_label_setting['show_sidebar']) ? 'show' : $white_label_setting['show_sidebar'];
            if($show_sidebar=='show')
            {
                $show_sidebar_link='checked';
                $hide_sidebar_link='';
            }
            else
            {
                $show_sidebar_link='';
                $hide_sidebar_link='checked';
            }
            $show_submit_ticket=empty($white_label_setting['show_submit_ticket']) ? 'show' : $white_label_setting['show_submit_ticket'];
            if($show_submit_ticket=='show')
            {
                $show_submit_ticket_link='checked';
                $hide_submit_ticket_link='';
            }
            else
            {
                $show_submit_ticket_link='';
                $hide_submit_ticket_link='checked';
            }
            ?>
            <div style="margin: 10px;">
                <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
                    <div class="mwp-wpvivid-welcome-bar-left">
                        <p><span class="dashicons dashicons-admin-generic mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-blue"></span><span class="mwp-wpvivid-page-title">White Label</span></p>
                        <span class="about-description">This tab allows you to configure WPvivid Backup Pro white label settings.</span>
                    </div>
                    <div class="mwp-wpvivid-welcome-bar-right"></div>
                    <div class="mwp-wpvivid-nav-bar mwp-wpvivid-clear-float">
                        <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                        <span> To restore backups of a white-labeled website, the current website needs to be white labeled with the same brand name.</span>
                    </div>
                </div>

                <div class="postbox">
                    <div class="mwp-wpvivid-setting-block mwp-wpvivid-block-bottom-space">
                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Plugin Name', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <input type="text" placeholder="WPvivid" option="mwp_white_label_setting" name="white_label_display" class="all-options" value="<?php echo esc_attr($white_label_display); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9_ ]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" />
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php /* translators: %s: Plugin name. */ echo sprintf(esc_html('Enter your preferred plugin name to replace %s on the plugin UI and WP dashboard.', 'wpvivid'), esc_html($white_label_display)); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Slug', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <input type="text" placeholder="WPvivid" option="mwp_white_label_setting" name="white_label_slug" class="all-options" value="<?php echo esc_attr($white_label_slug); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" />
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php /* translators: %s: Plugin slug. */ echo sprintf(esc_html('Enter your preferred slug to replace %s in all slugs, default storage directory paths, backup file names, default staging database names and table prefixes.', 'wpvivid'), esc_html($white_label_slug)); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Support Email', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <input type="text" placeholder="pro.support@wpvivid.com" option="mwp_white_label_setting" name="white_label_support_email" class="all-options" value="<?php echo esc_attr($white_label_support_email); ?>" />
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php /* translators: %s: Email. */ echo sprintf(esc_html('Enter your support email to replace %s in the plugin\'s Debug tab.', 'wpvivid'), esc_html($white_label_support_email)); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Author', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <input type="text" placeholder="wpvivid.com" option="mwp_white_label_setting" name="white_label_author" class="all-options" value="<?php echo esc_attr($white_label_author); ?>" />
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php /* translators: %s: Author. */ echo sprintf(esc_html('Enter your preferred author name of the plugin to replace %s.', 'wpvivid'), esc_html($white_label_author)); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Author URL', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <select option="mwp_white_label_setting" name="white_label_website_protocol" style="margin-bottom: 3px;">
                                <?php
                                if($white_label_website_protocol === 'http'){
                                    $http_protocol  = 'selected';
                                    $https_protocol = '';
                                }
                                else{
                                    $http_protocol  = '';
                                    $https_protocol = 'selected';
                                }
                                ?>
                                <option value="https" <?php echo esc_attr($https_protocol); ?>>https://</option>
                                <option value="http" <?php echo esc_attr($http_protocol); ?>>http://</option>
                            </select>
                            <input type="text" placeholder="pro.wpvivid.com" option="mwp_white_label_setting" name="white_label_website" class="all-options" value="<?php echo esc_attr($white_label_website); ?>" />
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php /* translators: 1: Website protocol, 2: Website domain. */ echo sprintf(esc_html('Enter your service URL to replace %1$s://%2$s in the plugin UI.'), esc_html($white_label_website_protocol), esc_html($white_label_website)); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Documentation Links', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label class="wpvivid-radio" style="padding-right:1em;">
                                <input type="radio" option="mwp_white_label_setting" name="show_sidebar" value="show" <?php echo esc_attr($show_sidebar_link); ?> />Show links
                            </label>
                            <label class="wpvivid-radio" style="padding-right:1em;">
                                <input type="radio" option="mwp_white_label_setting" name="show_sidebar" value="hide" <?php echo esc_attr($hide_sidebar_link); ?> />Hide Links
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Show or hide links to WPvivid documentation and support in the sidebar.'); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('Submit A Ticket Link', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label class="wpvivid-radio" style="padding-right:1em;">
                                <input type="radio" option="mwp_white_label_setting" name="show_submit_ticket" value="show" <?php echo esc_attr($show_submit_ticket_link); ?> />Show the link
                            </label>
                            <label class="wpvivid-radio" style="padding-right:1em;">
                                <input type="radio" option="mwp_white_label_setting" name="show_submit_ticket" value="hide" <?php echo esc_attr($hide_submit_ticket_link); ?> />Hide the link
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Show or hide the Submit A Ticket link in the sidebar.', 'wpvivid'); ?></div>

                        <div class="mwp-wpvivid-block-bottom-space"><strong><?php esc_html_e('White Label Settings Access URL', 'wpvivid'); ?></strong></div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label>
                                <input type="text" placeholder="wpvivid_white_label" option="mwp_white_label_setting" name="access_white_label_page_slug" class="all-options" value="<?php echo esc_attr($wpvivid_access_white_label_slug); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9_]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" />
                                <span></span>
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php esc_html_e('Enter a slug and add it at the end of the url of your WPvivid plugin page to access the white label settings.'); ?></div>
                        <div class="mwp-wpvivid-block-bottom-space"><?php echo 'Current access url is: http(s)://child-site/wp-admin/admin.php?page='.esc_html($white_label_slug).'-dashboard&'.esc_html($wpvivid_access_white_label_slug).'=1'; ?></div>

                    </div>
                </div>

                <div>
                    <?php
                    if($global){
                        ?>
                        <input class="ui green mini button" id="mwp_wpvivid_global_white_label_save" type="button" value="<?php esc_attr_e( 'Save Changes and Sync', 'wpvivid' ); ?>" />
                        <?php
                    }
                    else{
                        ?>
                        <input class="ui green mini button" id="mwp_wpvivid_white_label_save" type="button" value="<?php esc_attr_e( 'Save Changes', 'wpvivid' ); ?>" />
                        <?php
                    }
                    ?>
                </div>
            </div>
            <script>
                jQuery('#mwp_wpvivid_global_white_label_save').on('click', function(){
                    var setting_data = mwp_wpvivid_ajax_data_transfer('mwp_white_label_setting');
                    var ajax_data = {
                        'action': 'mwp_wpvivid_global_set_white_label_setting',
                        'setting': setting_data
                    };
                    jQuery('#mwp_wpvivid_global_white_label_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);

                            jQuery('#mwp_wpvivid_global_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                            if (jsonarray.result === 'success') {
                                window.location.href = window.location.href + "&synchronize=1&addon=1";
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }
                        catch (err) {
                            alert(err);
                            jQuery('#mwp_wpvivid_global_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        jQuery('#mwp_wpvivid_global_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                });

                jQuery('#mwp_wpvivid_white_label_save').on('click', function(){
                    var setting_data = mwp_wpvivid_ajax_data_transfer('mwp_white_label_setting');
                    var ajax_data = {
                        'action': 'mwp_wpvivid_set_white_label_setting',
                        'setting': setting_data,
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    jQuery('#mwp_wpvivid_white_label_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);

                            jQuery('#mwp_wpvivid_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                            if (jsonarray.result === 'success') {
                                location.reload();
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }
                        catch (err) {
                            alert(err);
                            jQuery('#mwp_wpvivid_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        jQuery('#mwp_wpvivid_white_label_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                });
            </script>
            <?php
        }
    }

    public function mwp_wpvivid_synchronize_white_label($check_addon){
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->render_sync_websites_page('mwp_wpvivid_sync_white_label', $check_addon);
        ?>
        <script>
            jQuery('#mwp_wpvivid_sync_white_label').click(function(){
                mwp_wpvivid_sync_white_label();
            });

            function mwp_wpvivid_sync_white_label(){
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
                    mwp_wpvivid_sync_site(website_ids,check_addon,'mwp_wpvivid_sync_white_label','Extensions-Wpvivid-Backup-Mainwp&tab=white_label','mwp_wpvivid_white_label_tab');
                }
            }
        </script>
        <?php
    }
}