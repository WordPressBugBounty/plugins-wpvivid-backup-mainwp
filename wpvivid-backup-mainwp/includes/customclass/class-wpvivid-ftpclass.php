<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_FTP','ftp');

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR .'/includes/customclass/class-wpvivid-remote.php';
class Mainwp_WPvivid_FTPClass extends Mainwp_WPvivid_Remote
{
    private $time_out = 20;
    private $options=array();

    public function __construct($options=array())
    {
        if(empty($options))
        {
            add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_ftp'), 9);
            add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_ftp'), 9);
            add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_ftp_addon'), 9);
            add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_ftp_addon'), 9);
            add_action('mwp_wpvivid_add_storage_page_ftp_addon', array($this, 'mwp_wpvivid_add_storage_page_ftp_addon'));
            add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_ftp_addon'), 9);
            add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_ftp'),9);
            add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_ftp'),10);
        }else{
            $this->options = $options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_ftp()
    {
        ?>
        <div class="mwp-storage-providers mwp-storage-providers-active" remote_type="ftp" onclick="select_remote_storage(event, 'mwp_wpvivid_storage_account_ftp');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-ftp.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('FTP', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_ftp()
    {
        ?>
        <div class="storage-account-page" id="mwp_wpvivid_storage_account_ftp">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your FTP Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp" name="name" placeholder="Enter an unique alias: e.g. FTP-001" class="regular-text" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>A name to help you identify the storage if you have multiple remote storage connected.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp" name="server" placeholder="FTP server (server's port 21)" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i style="margin-right: 10px;">Enter the FTP server.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="ftp" name="username" placeholder="FTP login" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your FTP server user name.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="ftp" name="password" placeholder="FTP password" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the FTP server password.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp" name="path" placeholder="Absolute path must exist(e.g. /home/username)" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter an absolute path and a custom subdirectory (optional) for holding the backups of current website. For example, /home/username/customfolder</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="ftp" name="default" checked />Set as the default remote storage.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Once checked, all this sites backups sent to a remote storage destination will be uploaded to this storage by default.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="ftp" name="passive" checked />Uncheck this to enable FTP active mode.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Uncheck the option to use FTP active mode when transferring files. Make sure the FTP server you are configuring supports the active FTP mode.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" type="button" option="add-remote" value="Test and Add">
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to FTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_tab_ftp_addon(){
        ?>
        <div class="mwp-storage-providers-addon mwp-storage-providers-addon-active" remote_type="ftp" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_ftp_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-ftp.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('FTP', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_ftp_addon(){
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_ftp_addon">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your FTP Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp-addon" name="name" placeholder="Enter an unique alias: e.g. FTP-001" class="regular-text" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>A name to help you identify the storage if you have multiple remote storage connected.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp-addon" name="server" placeholder="Server Address" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i style="margin-right: 10px;">Enter the server address.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp-addon" name="port" value="21" placeholder="FTP server port" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i style="margin-right: 10px;">Enter the custom server port.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="ftp-addon" name="username" placeholder="FTP login" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your FTP server user name.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="ftp-addon" name="password" placeholder="FTP password" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the FTP server password.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="ftp-addon" name="path" placeholder="Absolute path must exist(e.g. /home/username)" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>The root directory created by WPvivid backup plugin for holding the backups is </i><i id="mwp_wpvivid_ftp_root_path">/the_absolute_path</i><i>/wpvividbackuppro/</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="ftp-addon" name="root_path" value="wpvividbackuppro" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder under the absolute path for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'ftp-addon', 'add'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="ftp-addon" name="use_ftps" />Check this option to enable FTP-SSL connection.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check this option to enable FTP-SSL connection while transferring files. Make sure the FTP server you are configuring supports FTPS connections.</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="ftp-addon" name="passive" checked />Uncheck this to enable FTP active mode.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Uncheck the option to use FTP active mode when transferring files. Make sure the FTP server you are configuring supports the active FTP mode.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" type="button" option="add-remote-addon-global" value="Save and Sync" remote_type="ftp">
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to FTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_edit_storage_page_ftp_addon(){
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_ftp_edit" style="display: none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your FTP Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="edit-ftp-addon" name="name" placeholder="Enter an unique alias: e.g. FTP-001" class="regular-text" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>A name to help you identify the storage if you have multiple remote storage connected.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="edit-ftp-addon" name="server" placeholder="Server Address" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i style="margin-right: 10px;">Enter the server address.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="edit-ftp-addon" name="port" value="21" placeholder="FTP server port" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i style="margin-right: 10px;">Enter the custom server port.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-ftp-addon" name="username" placeholder="FTP login" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your FTP server user name.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="edit-ftp-addon" name="password" placeholder="FTP password" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the FTP server password.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" autocomplete="off" option="edit-ftp-addon" name="path" placeholder="Absolute path must exist(e.g. /home/username)" class="regular-text" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>The root directory created by WPvivid backup plugin for holding the backups is </i><i id="mwp_wpvivid_ftp_root_path">/the_absolute_path</i><i>/wpvividbackuppro/</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-ftp-addon" name="root_path" value="wpvividbackuppro" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder under the absolute path for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'ftp-addon', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="edit-ftp-addon" name="use_ftps" />Check this option to enable FTP-SSL connection.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check this option to enable FTP-SSL connection while transferring files. Make sure the FTP server you are configuring supports FTPS connections.</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="edit-ftp-addon" name="passive" checked />Uncheck this to enable FTP active mode.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Uncheck the option to use FTP active mode when transferring files. Make sure the FTP server you are configuring supports the active FTP mode.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" type="button" option="edit-remote-addon-global" value="Save Changes">
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to FTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_ftp($remote){
        $remote['ftp']['default_pic'] = '/admin/images/storage-ftp(gray).png';
        $remote['ftp']['selected_pic'] = '/admin/images/storage-ftp.png';
        $remote['ftp']['title'] = 'FTP';
        return $remote;
    }

    public function test_connect($is_pro)
    {
        $passive =$this->options['passive'];
        $host = $this->options['host'];
        $username = $this->options['username'];
        $password = $this->options['password'];
        $path = $this->options['path'];
        $port = empty($this->options['port'])?21:$this->options['port'];
        $conn = $this -> do_connect($host,$username,$password,$port);
        if(is_array($conn) && array_key_exists('result',$conn))
            return $conn;
        ftp_pasv($conn,$passive);
        if($is_pro){
            $ret= $this->do_chdir($conn,$path);
            if($ret['result']=='success') {
                $path=$this->options['path'].'wpvividbackuppro/';
                $ret= $this->do_chdir($conn,$path);
                if($ret['result']=='success') {
                    $custom_path = 'localhost_child-site';
                    $path= $this->options['path'].'wpvividbackuppro/'.$custom_path;
                    $this->do_chdir($conn,$path);
                    $path= $this->options['path'].'wpvividbackuppro/'.$custom_path.'/rollback';
                    return $this->do_chdir($conn,$path);
                }
                else {
                    return $ret;
                }
            }
            else {
                return $ret;
            }
        }
        else {
            return $this->do_chdir($conn, $path);
        }
    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']='failed';
        if(!isset($this->options['name']))
        {
            $ret['error']=__('Warning: An alias for remote storage is required.','mainwp-wpvivid-extension');
            return $ret;
        }

        $this->options['name']=sanitize_text_field($this->options['name']);

        if(empty($this->options['name']))
        {
            $ret['error']=__('Warning: An alias for remote storage is required.','mainwp-wpvivid-extension');
            return $ret;
        }

        $remoteslist=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
        if(isset($remoteslist) && !empty($remoteslist)) {
            foreach ($remoteslist['upload'] as $key => $value) {
                if (isset($value['name']) && $value['name'] == $this->options['name'] && $skip_name != $value['name']) {
                    $ret['error'] = "Warning: The alias already exists in storage list.";
                    return $ret;
                }
            }
        }

        $this->options['server']=sanitize_text_field($this->options['server']);

        if(empty($this->options['server']))
        {
            $ret['error']="Warning: The FTP server is required.";
            return $ret;
        }
        $res = explode(':',$this -> options['server']);
        if(sizeof($res) > 1){
            $this ->options['host'] = $res[0];
            if($res[1] != 21){
                $ret['error']='Currently, only port 21 is supported.';
                return $ret;
            }

        }else{
            $this -> options['host'] = $res[0];
        }

        if(!isset($this->options['port'])){
            $ret['error']="Warning: The servers port is required.";
            return $ret;
        }

        if(empty($this->options['port'])){
            $ret['error']="Warning: The servers port is required.";
            return $ret;
        }

        if(!isset($this->options['username']))
        {
            $ret['error']="Warning: The FTP login is required.";
            return $ret;
        }

        $this->options['username']=sanitize_text_field($this->options['username']);

        if(empty($this->options['username']))
        {
            $ret['error']="Warning: The FTP login is required.";
            return $ret;
        }

        if(!isset($this->options['password'])||empty($this->options['password']))
        {
            $ret['error']="Warning: The FTP password is required.";
            return $ret;
        }

        $this->options['password']=sanitize_text_field($this->options['password']);

        if(empty($this->options['password']))
        {
            $ret['error']="Warning: The FTP password is required.";
            return $ret;
        }
        $this->options['password'] = base64_encode($this->options['password']);
        $this->options['is_encrypt'] = 1;

        if(!isset($this->options['path'])||empty($this->options['path']))
        {
            $ret['error']="Warning: The storage path is required.";
            return $ret;
        }

        $this->options['path']=sanitize_text_field($this->options['path']);
        $this->options['path']=trailingslashit($this->options['path']);

        if(empty($this->options['path']))
        {
            $ret['error']="Warning: The storage path is required.";
            return $ret;
        }

        $ret['result']='success';
        $ret['options']=$this->options;
        return $ret;
    }

    public function do_connect($server,$username,$password,$port = 21)
    {
        $conn = ftp_connect( $server, $port, $this ->time_out );

        if($conn)
        {
            if(ftp_login($conn,$username,$password))
            {
                return $conn;
            }
            else
            {
                return array('result'=>'failed','error'=>'Login failed. You have entered the incorrect credential(s). Please try again.');
            }
        }
        else{
            return array('result'=>'failed','error'=>'Login failed. The connection has timed out. Please try again later.');
        }
	}
    public function do_chdir($conn,$path){
        if(!@ftp_chdir($conn,$path))
        {
            if ( ! ftp_mkdir( $conn, $path ) ) {
                return array('result'=>'failed','error'=>'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
            }
            if (!@ftp_chdir($conn,$path)){
                return array('result'=>'failed','error'=>'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
            }
        }
        return array('result'=>'success');
    }

    public function mwp_wpvivid_get_out_of_date_ftp($out_of_date_remote, $remote)
    {
        if($remote['type'] == MAINWP_WPVIVID_REMOTE_FTP){
            $out_of_date_remote = $remote['path'];
        }
        return $out_of_date_remote;
    }

    public function mwp_wpvivid_storage_provider_ftp($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_FTP){
            $storage_type = 'FTP';
        }
        return $storage_type;
    }
}