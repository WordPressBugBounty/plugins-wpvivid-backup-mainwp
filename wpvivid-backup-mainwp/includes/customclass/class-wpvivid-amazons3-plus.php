<?php
if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_AMAZONS3','amazons3');

define('MAINWP_WPVIVID_AMAZONS3_DEFAULT_FOLDER','/wpvivid_backup');

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-base-s3.php';
class Mainwp_WPvivid_AMAZONS3Class extends Mainwp_WPvivid_Remote{

    public $options;
    public $bucket='';

    public function __construct($options=array())
    {
        if(empty($options))
        {
            add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_amazons3'), 11);
            add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_amazons3'), 11);
            add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_amazons3_addon'), 11);
            add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_amazons3_addon'), 11);
            add_action('mwp_wpvivid_add_storage_page_amazons3_addon', array($this, 'mwp_wpvivid_add_storage_page_amazons3_addon'));
            add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_amazons3_addon'), 11);
            add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_amazons3'),11);
            add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_amazons3'),10);
        }
        else
        {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_amazons3()
    {
        ?>
        <div class="mwp-storage-providers" remote_type="amazons3" onclick="select_remote_storage(event, 'mwp_wpvivid_storage_account_amazons3');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-amazon-s3.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Amazon S3', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_amazons3()
    {
        ?>
        <div id="mwp_wpvivid_storage_account_amazons3"  class="storage-account-page" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your Amazon S3 Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="name" placeholder="Enter a unique alias: e.g. Amazon S3-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="access" placeholder="Amazon S3 access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Amazon S3 access key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="amazons3" name="secret" placeholder="Amazon S3 secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Amazon S3 secret key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="bucket" placeholder="Amazon S3 Bucket Name(e.g. test)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span>Enter an existed Bucket to create a custom backup storage directory.</span></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3" name="path" placeholder="Custom Path" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span>Customize the directory where you want to store backups within the Bucket.</span></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="amazons3" name="default" checked />Set as the default remote storage.
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
                                <input type="checkbox" option="amazons3" name="classMode" checked />Storage class: Standard (infrequent access).
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check the option to use Amazon S3 Standard-Infrequent Access (S3 Standard-IA) storage class for data transfer.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="amazons3" name="sse" checked />Server-side encryption.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check the option to use Amazon S3 server-side encryption to protect data.</i>
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
                            <i>Click the button to connect to Amazon S3 storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_tab_amazons3_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="amazons3" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_amazons3_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-amazon-s3.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Amazon S3', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_amazons3_addon(){
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_amazons3_addon">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your Amazon S3 Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3-addon" name="name" placeholder="Enter a unique alias: e.g. Amazon S3-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3-addon" name="access" placeholder="Amazon S3 access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Amazon S3 access key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="amazons3-addon" name="secret" placeholder="Amazon S3 secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Amazon S3 secret key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3-addon" name="bucket" placeholder="Amazon S3 Bucket Name(e.g. test)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span>Enter an existed Bucket to create a custom backup storage directory.</span></i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="amazons3-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'amazons3-addon', 'add'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="amazons3-addon" name="chunk_size" placeholder="Chunk size" value="5" onkeyup="value=value.replace(/\D/g,'')" />MB
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>The block size of uploads and downloads. Reduce it if you encounter a timeout when transferring files.</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="amazons3-addon" name="classMode" checked />Storage class: Standard (infrequent access).
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check the option to use Amazon S3 Standard-Infrequent Access (S3 Standard-IA) storage class for data transfer.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="amazons3-addon" name="sse" checked />Server-side encryption.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check the option to use Amazon S3 server-side encryption to protect data.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="amazons3-addon" name="uncheckdelete" />Do not check DeleteObject.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <div style="float: left; padding-right: 5px;">
                                <i>Tick this option so WPvivid won't check s3:DeleteObject permission of the user in the authentication.</i>
                            </div>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-bottom">
                                    <p>s3:DeleteObject is a permission for deleting objects from Amazon S3. Without it, you are not able to delete backups on S3 from WPvivid.</p>
                                    <i></i>
                                </div>
                            </span>
                            <div style="clear: both;"></div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="add-remote-addon-global" type="button" value="Save and Sync" remote_type="amazons3" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to Amazon S3 storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_edit_storage_page_amazons3_addon(){
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_amazons3_edit" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your Amazon S3 Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3-addon" name="name" placeholder="Enter a unique alias: e.g. Amazon S3-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3-addon" name="access" placeholder="Amazon S3 access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Amazon S3 access key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="edit-amazons3-addon" name="secret" placeholder="Amazon S3 secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Amazon S3 secret key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3-addon" name="bucket" placeholder="Amazon S3 Bucket Name(e.g. test)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span>Enter an existed Bucket to create a custom backup storage directory.</span></i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-amazons3-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'amazons3-addon', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-amazons3-addon" name="chunk_size" placeholder="Chunk size" value="5" onkeyup="value=value.replace(/\D/g,'')" />MB
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>The block size of uploads and downloads. Reduce it if you encounter a timeout when transferring files.</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="edit-amazons3-addon" name="classMode" checked />Storage class: Standard (infrequent access).
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check the option to use Amazon S3 Standard-Infrequent Access (S3 Standard-IA) storage class for data transfer.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="edit-amazons3-addon" name="sse" checked />Server-side encryption.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Check the option to use Amazon S3 server-side encryption to protect data.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="edit-remote-addon-global" type="button" value="Save Changes" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to Amazon S3 storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_amazons3($remote){
        $remote['amazons3']['default_pic'] = '/admin/images/storage-amazon-s3(gray).png';
        $remote['amazons3']['selected_pic'] = '/admin/images/storage-amazon-s3.png';
        $remote['amazons3']['title'] = 'Amazon S3';
        return $remote;
    }

    public function test_connect($is_pro)
    {
        $amazons3 = $this -> getS3();
        if(is_array($amazons3) && $amazons3['result'] === 'failed')
            return $amazons3;
        $temp_file = md5(wp_rand());
        try
        {
            if(isset($this->options['s3Path']))
            {
                $url=$this->options['s3Path'].$temp_file;
            }
            else
            {
                $url=$this->options['path'].'/'.$temp_file;
            }

            if(!$amazons3 -> putObjectString($temp_file,$this -> bucket,$url))
            {
                return array('result'=>'failed','error'=>'We successfully accessed the bucket, but create test file failed.');
            }
            if(!$amazons3 -> deleteObject($this -> bucket,$url))
            {
                return array('result'=>'failed','error'=>'We successfully accessed the bucket, and create test file succeed, but delete test file failed.');
            }
        }catch(Exception $e){
            return array('result'=>'failed','error'=>$e -> getMessage());
        }
        return array('result'=>'success');
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

        if(!isset($this->options['access']))
        {
            $ret['error']="Warning: The access key for Amazon S3 is required.";
            return $ret;
        }

        $this->options['access']=sanitize_text_field($this->options['access']);

        if(empty($this->options['access']))
        {
            $ret['error']="Warning: The access key for Amazon S3 is required.";
            return $ret;
        }

        if(!isset($this->options['secret']))
        {
            $ret['error']="Warning: The storage secret key is required.";
            return $ret;
        }

        $this->options['secret']=sanitize_text_field($this->options['secret']);

        if(empty($this->options['secret']))
        {
            $ret['error']="Warning: The storage secret key is required.";
            return $ret;
        }
        $this->options['secret'] = base64_encode($this->options['secret']);
        $this->options['is_encrypt'] = 1;

        if(!isset($this->options['bucket']))
        {
            $ret['error']="Warning: A Bucket name is required.";
            return $ret;
        }

        $this->options['bucket']=sanitize_text_field($this->options['bucket']);

        if(empty($this->options['bucket']))
        {
            $ret['error']="Warning: A Bucket name is required.";
            return $ret;
        }

        $ret['result']='success';
        $ret['options']=$this->options;
        return $ret;
    }

    private function getS3()
    {
        if(isset($this->options['s3Path']))
        {
            $path_temp = str_replace('s3://','',$this->options['s3Path']);
            if (preg_match("#^/*([^/]+)/(.*)$#", $path_temp, $bmatches))
            {
                $this->bucket = $bmatches[1];
                if(empty($bmatches[2])){
                    $this->options['s3Path'] = '';
                }else{
                    $this->options['s3Path'] = trailingslashit($bmatches[2]);
                }
            } else {
                $this->bucket = $path_temp;
                $this->options['s3Path'] = '';
            }
            $amazons3 = new MainWP_WPvivid_Base_S3($this->options['access'],$this->options['secret']);

            $amazons3 -> setExceptions();
            if($this->options['classMode'])
                $amazons3 -> setStorageClass();
            if($this->options['sse'])
                $amazons3 -> setServerSideEncryption();

            try{
                $region = $amazons3 -> getBucketLocation($this->bucket);
            }catch(Exception $e){
                return array('result' => 'failed','error' => $e -> getMessage());
            }
            $endpoint = $this -> getEndpoint($region);
            if(!empty($endpoint))
                $amazons3 -> setEndpoint($endpoint);
            return $amazons3;
        }
        else
        {
            $this->bucket= $this->options['bucket'];
            $amazons3 = new MainWP_WPvivid_Base_S3($this->options['access'],$this->options['secret']);
            $amazons3 -> setExceptions();
            if($this->options['classMode'])
                $amazons3 -> setStorageClass();
            if($this->options['sse'])
                $amazons3 -> setServerSideEncryption();

            try{
                $region = $amazons3 -> getBucketLocation($this->bucket);
            }catch(Exception $e){
                return array('result' => 'failed','error' => $e -> getMessage());
            }

            $amazons3->setSignatureVersion('v4');
            $amazons3->setRegion($region);

            $endpoint = $this -> getEndpoint($region);
            if(!empty($endpoint))
                $amazons3 -> setEndpoint($endpoint);
            return $amazons3;
        }
    }
    private function getEndpoint($region){
        switch ($region) {
            case 'EU':
            case 'eu-west-1':
                $endpoint = 's3-eu-west-1.amazonaws.com';
                break;
            case 'US':
            case 'us-east-1':
                $endpoint = 's3.amazonaws.com';
                break;
            case 'us-west-1':
            case 'us-east-2':
            case 'us-west-2':
            case 'eu-west-2':
            case 'eu-west-3':
            case 'ap-southeast-1':
            case 'ap-southeast-2':
            case 'ap-northeast-2':
            case 'sa-east-1':
            case 'ca-central-1':
            case 'us-gov-west-1':
            case 'eu-north-1':
            case 'eu-central-1':
                $endpoint = 's3-'.$region.'.amazonaws.com';
                break;
            case 'ap-northeast-1':
                $endpoint = 's3.'.$region.'.amazonaws.com';
                break;
            case 'ap-south-1':
                $endpoint = 's3.'.$region.'.amazonaws.com';
                break;
            case 'cn-north-1':
                $endpoint = 's3.'.$region.'.amazonaws.com.cn';
                break;
            default:
                $endpoint = 's3.amazonaws.com';
                break;
        }
        return $endpoint;
    }

    public function mwp_wpvivid_storage_provider_amazons3($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_AMAZONS3){
            $storage_type = 'Amazon S3';
        }
        return $storage_type;
    }
}