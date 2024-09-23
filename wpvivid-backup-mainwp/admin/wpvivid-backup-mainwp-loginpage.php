<?php

class Mainwp_WPvivid_Extension_LoginPage
{
    public function __construct()
    {
        $this->load_login_ajax();
    }

    public function load_login_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_connect_account', array($this, 'connect_account'));
    }

    public function connect_account()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try
        {
            if(isset($_POST['license']))
            {
                if(empty($_POST['license']))
                {
                    $ret['result']='failed';
                    $ret['error']='A license is required.';
                    echo wp_json_encode($ret);
                    die();
                }

                $user_info=sanitize_text_field($_POST['license']);

                $server=new Mainwp_WPvivid_Connect_server();
                $ret=$server->get_mainwp_status( $user_info, true);
                if($ret['result']=='success')
                {
                    $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();

                    $pro_info['user_info']=$ret['user_info'];
                    $login_options['wpvivid_pro_account'] = $pro_info;
                    $login_options['wpvivid_pro_login_cache'] = $ret['status'];

                    $mainwp_wpvivid_extension_activator->set_global_login_addon($login_options);
                }
                else{
                    $ret['result']='failed';
                    if(!isset($ret['error']))
                    {
                        $ret['error'] = 'Failed to login.';
                    }
                }
                echo wp_json_encode($ret);
            }
            else{
                $ret['result']='failed';
                $ret['error']='A license is required.';
                echo wp_json_encode($ret);
                die();
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function render()
    {
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
        ?>
        <div id="mwp_wpvivid_pro_notice">
            <?php
            if(isset($_REQUEST['login_success']))
            {
                ?>
                <script>
                location.href='<?php echo esc_url(apply_filters('wpvivid_get_admin_url', '')) . 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=dashboard&sync=1'; ?>';
                </script>
                <?php
                return;
            }
            else if(isset($_REQUEST['active_success']))
            {
                echo '<div class="notice notice-success is-dismissible inline" style="margin: 0; padding-top: 10px;"><p>'.esc_html('Your license has been activated successfully').'</p></div>';
            }
            else if(isset($_REQUEST['error']))
            {
                $error=$_REQUEST['error'];
                echo '<div class="notice notice-error inline is-dismissible" style="margin: 0; padding-top: 10px; margin-left: 10px !important;"><p>'.esc_html($error).'</p></div>';
                if($login_options !== false){
                    if(isset($login_options['connect_server_last_error']) && !empty($login_options['connect_server_last_error'])){
                        unset($login_options['connect_server_last_error']);
                        $mainwp_wpvivid_extension_activator->set_global_login_addon($login_options);
                    }
                }
            }
            else{
                if($login_options !== false){
                    if(isset($login_options['connect_server_last_error']) && !empty($login_options['connect_server_last_error'])){
                        $last_error = $login_options['connect_server_last_error'];
                        if(is_string($last_error)) {
                            echo '<div class="notice notice-error is-dismissible" style="margin: 0; padding-top: 10px;"><p>' . esc_html($last_error) . '</p></div>';
                        }
                        unset($login_options['connect_server_last_error']);
                        $mainwp_wpvivid_extension_activator->set_global_login_addon($login_options);
                    }
                }
            }
            ?>
        </div>
        <div style="margin-top: 10px;">
            This tab allows you to login to your WPvivid Backup Pro account. Once logged in, you can install , claim, and update WPvivid Backup Pro 2.0 on child sites in bulk from MainWP dashboard.
        </div>
        <?php
        if(isset($_REQUEST['switch'])) {
            $this->output_login_page();
        }
        else{
            if($this->check_license())
            {
                $this->output_user_info_page();
            }
            else
            {
                $this->output_login_page();
            }
        }
        ?>
        <script>
            function mwp_wpvivid_display_pro_notice(notice_type, notice_message){
                if(notice_type === 'Success'){
                    var div = "<div class='notice notice-success is-dismissible inline'><p>" + notice_message + "</p>" +
                        "<button type='button' class='notice-dismiss' onclick='mwp_click_dismiss_pro_notice(this);'>" +
                        "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                        "</button>" +
                        "</div>";
                }
                else{
                    var div = "<div class=\"notice notice-error inline\"><p>Error: " + notice_message + "</p></div>";
                }
                jQuery('#mwp_wpvivid_pro_notice').show();
                jQuery('#mwp_wpvivid_pro_notice').html(div);
            }

            function mwp_click_dismiss_pro_notice(obj){
                jQuery(obj).parent().remove();
            }

            function mwp_wpvivid_lock_login(lock,error='') {
                if(lock) {
                    jQuery('#mwp_wpvivid_active_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#mwp_wpvivid_login_box_progress').show();
                    jQuery('#mwp_wpvivid_login_box_progress').addClass('is-active');
                    jQuery('#mwp_wpvivid_connect_result').hide();
                    jQuery('#mwp_wpvivid_connect_result').html('');
                }
                else {
                    jQuery('#mwp_wpvivid_log_progress_text').html('');
                    jQuery('#mwp_wpvivid_login_box_progress').hide();
                    jQuery('#mwp_wpvivid_login_box_progress').removeClass('is-active');
                    jQuery('#mwp_wpvivid_active_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    if(error!=='') {
                        mwp_wpvivid_display_pro_notice('Error', error);
                    }
                }
            }

            function mwp_wpvivid_login_progress(log) {
                jQuery('#mwp_wpvivid_log_progress_text').html(log);
            }

            function mwp_wpvivid_is_running(is_running){
                if(is_running){
                    jQuery('.mwp-wpvivid-login-btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
                else{
                    jQuery('.mwp-wpvivid-login-btn').css({'pointer-events': 'auto', 'opacity': '1'});
                }
            }

            jQuery('#mwp_wpvivid_active_btn').click(function(){
                jQuery('#mwp_wpvivid_pro_notice').hide();
                mwp_wpvivid_connect_account_and_active();
            });

            function mwp_wpvivid_connect_account_and_active(){
                var license = jQuery('#mwp_wpvivid_account_pw').val();

                var ajax_data = {
                    'action': 'mwp_wpvivid_connect_account',
                    'license': license
                };
                mwp_wpvivid_lock_login(true);
                mwp_wpvivid_login_progress('Logging in to your WPvivid Backup Pro account');
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        mwp_wpvivid_login_progress('You have successfully logged in');
                        location.href='<?php echo 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=login&login_success'; ?>';
                    }
                    else {
                        mwp_wpvivid_lock_login(false,jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('connect server and active', textStatus, errorThrown);
                    mwp_wpvivid_lock_login(false,error_message);
                });
            }
        </script>
        <?php
    }

    public function output_login_page(){
        $membership = 'N/A';
        $expire = 'N/A';
        $current_version = 'N/A';
        ?>
        <div style="margin-top: 10px;">
            <div class="postbox" id="mwp_wpvivid_login_box">
                <table class="wp-list-table widefat plugins" style="width: 100%;">
                    <tbody>
                    <tr>
                        <td class="column-primary" style="margin: 10px;">
                            <div>
                                <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/pro.png'); ?>" style="width:100px; height:100px;">
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div>
                                <form action="">
                                    <!--<div style="margin-top: 10px; margin-bottom: 15px;"><input type="text" class="regular-text" id="mwp_wpvivid_account_user" placeholder="Email" autocomplete="off" required /></div>-->
                                    <div style="margin-bottom: 15px;"><input type="password" class="regular-text" id="mwp_wpvivid_account_pw" placeholder="Father License" autocomplete="new-password" required /></div>
                                    <div style="margin-bottom: 10px; float: left; margin-left: 0; margin-right: 10px;"><input class="ui green mini button mwp-wpvivid-login-btn" id="mwp_wpvivid_active_btn" type="button" value="Login"/></div>
                                    <div class="spinner" id="mwp_wpvivid_login_box_progress" style="float: left; margin-left: 0; margin-right: 10px;"></div>
                                    <div style="float: left; margin-top: 4px;"><span id="mwp_wpvivid_log_progress_text"></span></div>
                                    <div style="clear: both;"></div>
                                </form>
                                <div id="mwp_wpvivid_connect_result" style="display: none; margin-bottom: 10px;"></div>
                                <div style="background-color:#f5f5f5; padding:5px;">
                                    <i>Tip: You can find the father license from My Account Area > License on <a href="https://wpvivid.com" target="_blank">wpvivid.com</a>.</i>
                                </div>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div style="padding-left: 5px;">
                                <div style="margin-bottom: 10px;"><strong>WPvivid Backup Pro</strong></div>
                                <div style="margin-bottom: 10px;"><i>WPvivid Backup Pro works on top of free version, providing full flexibility and a series of robust customization options to meet your diverse website backup and migration needs.</i></div>
                            </div>
                            <div style="border-left:4px solid #00a0d2;padding-left:10px;">
                                <div>
                                    <div style="margin-right: 5px; float: left; margin-bottom: 5px;">Current Version: </div><div style="float: left; margin-bottom: 5px;"><?php echo esc_html($current_version); ?></div>
                                    <div style="clear: both;"></div>
                                </div>
                                <div>
                                    <div style="margin-right: 5px; float: left; margin-bottom: 5px;">Membership Plan: </div><div style="float: left; margin-bottom: 5px;"><?php echo esc_html($membership); ?></div>
                                    <div style="clear: both;"></div>
                                </div>
                                <div>
                                    <div style="margin-right: 5px; float: left;">Expiration Date: </div><div style="float: left;"><?php echo esc_html($expire); ?></div>
                                    <div style="clear: both;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function output_user_info_page(){
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
        $addons_cache = isset($login_options['wpvivid_pro_login_cache']) ? $login_options['wpvivid_pro_login_cache'] : false;
        $current_version = 'N/A';

        if(isset($addons_cache['pro']['version'])){
            $current_version = $addons_cache['pro']['version'];
        }
        else if($addons_cache['dashboard']['version']){
            $current_version = $addons_cache['dashboard']['version'];
        }
        ?>
        <div style="margin-top: 10px;">
            <div class="postbox">
                <table class="wp-list-table widefat plugins" style="width: 100%;">
                    <tbody>
                    <tr>
                        <td class="column-primary" style="margin: 10px;">
                            <div>
                                <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/pro.png'); ?>" style="width:100px; height:100px;">
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div style="margin-top: 10px;">

                                    <div style="float: left; margin-bottom: 10px; margin-right: 10px;">You are WPvivid Pro user<a href="https://pro.wpvivid.com" target="_blank"> Go to My Account</a></div>
                                    <div style="float: left; margin-bottom: 10px;">
                                        <a href="#" class="mwp-wpvivid-login-btn" id="mwp_wpvivid_change_btn">Switch Accounts</a>
                                    </div>
                                    <div style="clear: both;"></div>

                                    <div style="margin-bottom: 10px; float: left;">
                                        <input id="mwp_wpvivid_switch_dashboard_page" type="button" class="ui green mini button mwp-wpvivid-login-btn" value="Install & Claim WPvivid Backup Pro on Child Sites">
                                    </div>

                                <div class="spinner" id="mwp_wpvivid_user_info_box_progress" style="float: left;"></div>
                                <div style="float: left; margin-top: 4px;"><span id="mwp_wpvivid_user_info_log_progress_text"></span></div>
                                <div style="clear: both;"></div>
                                <div id="mwp_wpvivid_action_result" style="display: none; margin-bottom: 10px;"></div>
                                <div style="background-color:#f5f5f5; padding:5px;">
                                    <i>Tip: You can find the father license from My Account Area > License on <a href="https://wpvivid.com" target="_blank">wpvivid.com</a>.</i>
                                </div>
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div style="padding-left: 5px;">
                                <div style="margin-bottom: 10px;"><strong>WPvivid Backup Pro</strong></div>
                                <div style="margin-bottom: 10px;"><i>WPvivid Backup Pro works on top of free version, providing full flexibility and a series of robust customization options to meet your diverse website backup and migration needs.</i></div>
                            </div>
                            <div style="border-left:4px solid #00a0d2;padding-left:10px;">
                                <div>
                                    <div style="margin-right: 5px; float: left; margin-bottom: 5px;">Current Version: </div><div style="float: left; margin-bottom: 5px;"><?php echo esc_html($current_version); ?></div>
                                    <div style="clear: both;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <script>
            jQuery( '#mwp_wpvivid_login_table' ).DataTable( {
                "columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
                "order": [ [ 1, "asc" ] ],
                "language": { "emptyTable": "No websites were found with the WPvivid Backup plugin installed." },
                "drawCallback": function( settings ) {
                    jQuery('#mwp_wpvivid_login_table .ui.checkbox').checkbox();
                    jQuery( '#mwp_wpvivid_login_table .ui.dropdown').dropdown();
                },
            } );

            function mwp_wpvivid_user_info_lock_login(lock, error = ''){
                if(lock){
                    jQuery('.mwp-wpvivid-login-btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#mwp_wpvivid_user_info_box_progress').show();
                    jQuery('#mwp_wpvivid_user_info_box_progress').addClass('is-active');
                    jQuery('#mwp_wpvivid_action_result').hide();
                    jQuery('#mwp_wpvivid_action_result').html('');
                }
                else{
                    jQuery('.mwp-wpvivid-login-btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_user_info_box_progress').hide();
                    jQuery('#mwp_wpvivid_user_info_box_progress').removeClass('is-active');
                    jQuery('#mwp_wpvivid_user_info_log_progress_text').html('');
                    if(error!=='') {
                        mwp_wpvivid_display_pro_notice('Error', error);
                    }
                }
            }

            function mwp_wpvivid_user_info_progress(log) {
                jQuery('#mwp_wpvivid_user_info_log_progress_text').html(log);
            }

            jQuery('#mwp_wpvivid_change_btn').on('click', function(){
                var descript = 'Are you sure switch accounts?';
                var ret = confirm(descript);
                if(ret === true) {
                    location.href='<?php echo esc_url(apply_filters('wpvivid_get_admin_url', '')) . 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=login&switch=1'; ?>';
                }
            });

            jQuery('#mwp_wpvivid_switch_dashboard_page').on('click', function(){
                location.href='<?php echo esc_url(apply_filters('wpvivid_get_admin_url', '')) . 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&tab=dashboard'; ?>';
            });
        </script>
        <?php
    }

    public function check_license()
    {
        global $mainwp_wpvivid_extension_activator;
        $login_options = $mainwp_wpvivid_extension_activator->get_global_login_addon();
        if ($login_options === false||!isset($login_options['wpvivid_pro_account']))
        {
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
                    return false;
                }
            }
        }
    }
}