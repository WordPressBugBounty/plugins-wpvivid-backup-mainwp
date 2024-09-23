<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_SFTP','sftp');
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR .'/includes/customclass/class-wpvivid-remote.php';

class Mainwp_WPvivid_SFTPClass extends Mainwp_WPvivid_Remote
{
    private $timeout = 20;
    private $error_str=false;
    private $options=array();

    public function __construct($options=array())
    {
        if(empty($options))
        {
            add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_sftp'), 10);
            add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_sftp'), 10);
            add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_sftp_addon'), 10);
            add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_sftp_addon'), 10);
            add_action('mwp_wpvivid_add_storage_page_sftp_addon', array($this, 'mwp_wpvivid_add_storage_page_sftp_addon'));
            add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_sftp_addon'), 10);
            add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_sftp'),10);
            add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_sftp'),10);
        }
        else
        {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_sftp()
    {
        ?>
        <div class="mwp-storage-providers" remote_type="sftp" onclick="select_remote_storage(event, 'mwp_wpvivid_storage_account_sftp');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-sftp.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('SFTP', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_sftp()
    {
        ?>
        <div class="storage-account-page" id="mwp_wpvivid_storage_account_sftp" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your SFTP Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp" name="name" placeholder="Enter a unique alias: e.g. SFTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="sftp" name="host" placeholder="Server Address" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the server address.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp" name="username" placeholder="User Name" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the user name.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="sftp" name="password" placeholder="User Password" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the user password.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp" name="port" placeholder="Port" onkeyup="value=value.replace(/\D/g,'')" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the server port.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp" name="path" placeholder="Absolute path must exist(e.g. /var)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter an absolute path and a custom subdirectory (optional) for holding the backups of current website. For example, /var/customfolder/</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="sftp" name="default" checked />Set as the default remote storage.
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
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="add-remote" type="button" value="Test and Add" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to SFTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_tab_sftp_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="sftp" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_sftp_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-sftp.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('SFTP', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_sftp_addon(){
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_sftp_addon">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your SFTP Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp-addon" name="name" placeholder="Enter a unique alias: e.g. SFTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="sftp-addon" name="host" placeholder="Server Address" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the server address.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp-addon" name="username" placeholder="User Name" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the user name.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="sftp-addon" name="password" placeholder="User Password" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the user password.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp-addon" name="port" placeholder="Port" onkeyup="value=value.replace(/\D/g,'')" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the server port.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp-addon" name="path" placeholder="Absolute path must exist(e.g. /var)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter an absolute path and a custom subdirectory (optional) for holding the backups of current website. For example, /var/customfolder/</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="sftp-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder under the absolute path for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'sftp-addon', 'add'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="add-remote-addon-global" type="button" value="Save and Sync" remote_type="sftp" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to SFTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_edit_storage_page_sftp_addon(){
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_sftp_edit" style="display: none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your SFTP Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-sftp-addon" name="name" placeholder="Enter a unique alias: e.g. SFTP-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="edit-sftp-addon" name="host" placeholder="Server Address" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the server address.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-sftp-addon" name="username" placeholder="User Name" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the user name.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="edit-sftp-addon" name="password" placeholder="User Password" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the user password.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-sftp-addon" name="port" placeholder="Port" onkeyup="value=value.replace(/\D/g,'')" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the server port.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-sftp-addon" name="path" placeholder="Absolute path must exist(e.g. /var)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter an absolute path and a custom subdirectory (optional) for holding the backups of current website. For example, /var/customfolder/</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-sftp-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder under the absolute path for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'sftp-addon', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="edit-remote-addon-global" type="button" value="Save Changes" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to SFTP server and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_sftp($remote)
    {
        $remote['sftp']['default_pic'] = '/admin/images/storage-sftp(gray).png';
        $remote['sftp']['selected_pic'] = '/admin/images/storage-sftp.png';
        $remote['sftp']['title'] = 'SFTP';
        return $remote;
    }

    public function test_connect($is_pro)
    {
        $host = $this->options['host'];
        $username = $this->options['username'];
        $password = $this->options['password'];
        $path = $this->options['path'];

        $port = empty($this->options['port'])?22:$this->options['port'];

        $conn = $this->do_connect($host,$username,$password,$port);
        if(!is_subclass_of($conn,'Net_SSH2'))
        {
            return $conn;
        }
        $str = $this->do_chdir($conn,$path);
        if($str['result'] == 'success')
        {
            if($conn->put(trailingslashit($path) . 'testfile', 'test data', NET_SFTP_STRING))
            {
                $this -> _delete($conn ,trailingslashit($path) . 'testfile');
                return array('result'=>'success');
            }
            return array('result'=>'failed','error'=>'Failed to create a test file. Please try again later.');
        }else{
            return $str;
        }
    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']='failed';
        if(!isset($this->options['name']))
        {
            $ret['error']="Warning: An alias for remote storage is required.";
            return $ret;
        }

        $this->options['name']=sanitize_text_field($this->options['name']);

        if(empty($this->options['name']))
        {
            $ret['error']="Warning: An alias for remote storage is required.";
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

        if(!isset($this->options['host']))
        {
            $ret['error']="Warning: The IP Address is required.";
            return $ret;
        }

        $this->options['host']=sanitize_text_field($this->options['host']);

        if(empty($this->options['host']))
        {
            $ret['error']="Warning: The IP Address is required.";
            return $ret;
        }

        if(!isset($this->options['username']))
        {
            $ret['error']="Warning: The username is required.";
            return $ret;
        }

        $this->options['username']=sanitize_text_field($this->options['username']);

        if(empty($this->options['username']))
        {
            $ret['error']="Warning: The username is required.";
            return $ret;
        }

        if(!isset($this->options['password'])||empty($this->options['password']))
        {
            $ret['error']="Warning: The password is required.";
            return $ret;
        }

        $this->options['password']=sanitize_text_field($this->options['password']);

        if(empty($this->options['password']))
        {
            $ret['error']="Warning: The password is required.";
            return $ret;
        }
        $this->options['password'] = base64_encode($this->options['password']);
        $this->options['is_encrypt'] = 1;

        if(!isset($this->options['port']))
        {
            $ret['error']="Warning: The port number is required.";
            return $ret;
        }

        $this->options['port']=sanitize_text_field($this->options['port']);

        if(empty($this->options['port']))
        {
            $ret['error']="Warning: The port number is required.";
            return $ret;
        }

        if(!isset($this->options['path'])||empty($this->options['path']))
        {
            $ret['error']="Warning: The storage path is required.";
            return $ret;
        }

        $this->options['path']=sanitize_text_field($this->options['path']);

        if(empty($this->options['path']))
        {
            $ret['error']="Warning: The storage path is required.";
            return $ret;
        }

        $ret['result']='success';
        $ret['options']=$this->options;
        return $ret;
    }

	function do_connect($host,$username,$password,$port)
    {
        include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-extend-sftp.php';
        $conn = new WPvivid_Net_SFTP($host,$port,$this -> timeout);
        $conn -> setTimeout($this->timeout);
        $ret = $conn->login($username,$password);
        if(!$ret)
        {
            return array('result'=>'failed','error'=>'Login failed. You have entered the incorrect credential(s). Please try again.');
        }

		return $conn;
	}

	function do_chdir($conn,$path)
    {
        @$conn->mkdir($path);
        // See if the directory now exists
        if (!$conn->chdir($path))
        {
            @$conn->disconnect();
            return array('result'=>'failed','error'=>'Failed to create a backup. Make sure you have sufficient privileges to perform the operation.');
        }

		return array('result'=>'success');
	}

    public function get_last_error()
    {
        if($this->error_str===false)
        {
            $this->error_str='connection time out.';
        }
        return $this->error_str;
    }

    function _delete($conn , $file)
    {
        $result = $conn ->delete($file , true);
        return $result;
    }

    public function mwp_wpvivid_storage_provider_sftp($storage_type)
    {
        if ($storage_type == MAINWP_WPVIVID_REMOTE_SFTP) {
            $storage_type = 'SFTP';
        }
        return $storage_type;
    }
}