<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_WASABI','wasabi');

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

class Mainwp_Wpvivid_WasabiS3 extends Mainwp_WPvivid_Remote{
    public $options;

    public function __construct($options=array()){
        if(empty($options)) {
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_WASABI')) {
                add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_wasabi_addon'), 11);
                add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_wasabi_addon'), 11);
                add_action('mwp_wpvivid_add_storage_page_wasabi_addon', array($this, 'mwp_wpvivid_add_storage_page_wasabi_addon'));
                add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_wasabi_addon'), 11);
                add_filter('mwp_wpvivid_remote_pic', array($this, 'mwp_wpvivid_remote_pic_wasabi'), 11);
                add_filter('mwp_wpvivid_storage_provider_tran', array($this, 'mwp_wpvivid_storage_provider_wasabi'), 10);
                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_WASABI',1);
            }
        }
        else {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_wasabi_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="wasabi" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_wasabi_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-wasabi.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Wasabi', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_wasabi_addon(){
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_wasabi_addon">
            <div style="padding: 0 10px 10px 0;"><strong>Enter Your Wasabi Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="wasabi-addon" name="name" placeholder="Enter a unique alias: e.g. Wasabi-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="wasabi-addon" name="access" placeholder="Wasabi access key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter your Wasabi access key</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="new-password" option="wasabi-addon" name="secret" placeholder="Wasabi secret key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter your Wasabi secret key</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="wasabi-addon" name="bucket" placeholder="Bucket Name(e.g. test)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span>Enter an existed Space in which you want to create a parent folder</span></i>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="wasabi-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <select id="mwp_wpvivid_wasabi_endpoint_select" style="margin-bottom:5px;">
                                    <option value="us_east1">US East 1</option>
                                    <option value="us_east2">US East 2</option>
                                    <option value="us_west1">US West 1</option>
                                    <option value="us_central1">EU Central 1</option>
                                    <option value="custom">Custom</option>
                                </select>
                                <input id="mwp_wpvivid_wasabi_endpoint" style="width:205px" type="text" class="regular-text" autocomplete="off" option="wasabi-addon" name="endpoint" value="s3.wasabisys.com" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the Wasabi Endpoint for the storage</i>
                            </div>
                        </td>
                    </tr>

                    <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'wasabi-addon', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-select">
                                <label>
                                    <input type="checkbox" option="wasabi-addon" name="uncheckdelete" />Do not check DeleteObject.
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
                                    <p>s3:DeleteObject is a permission for deleting objects from Wasabi. Without it, you are not able to delete backups on S3 from WPvivid.</p>
                                    <i></i>
                                </div>
                            </span>
                                <div style="clear: both;"></div>
                            </div>
                        </td>
                    </tr>
                </form>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="add-remote-addon-global" type="button" value="Save and Sync" remote_type="wasabi" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to Wasabi storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <script>
            jQuery('#mwp_wpvivid_wasabi_endpoint_select').change(function()
            {
                if('us_east1'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint').val('s3.wasabisys.com');
                }
                else if('us_east2'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint').val('s3.us-east-2.wasabisys.com');
                }
                else if('us_west1'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint').val('s3.us-west-1.wasabisys.com');
                }
                else if('us_central1'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint').val('s3.eu-central-1.wasabisys.com');
                }
                else if('custom'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint').val('');
                }
            });
        </script>
        <?php
    }

    public function mwp_wpvivid_edit_storage_page_wasabi_addon(){
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_wasabi_edit" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your Wasabi Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-wasabi-addon" name="name" placeholder="Enter a unique alias: e.g. Wasabi-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="edit-wasabi-addon" name="access" placeholder="Wasabi access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Wasabi access key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="edit-wasabi-addon" name="secret" placeholder="Wasabi secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your Wasabi secret key.</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-wasabi-addon" name="bucket" placeholder="Bucket Name(e.g. test)" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="edit-wasabi-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <select id="mwp_wpvivid_wasabi_endpoint_select_edit" style="margin-bottom:5px;">
                                <option value="us_east1">US East 1</option>
                                <option value="us_east2">US East 2</option>
                                <option value="us_west1">US West 1</option>
                                <option value="us_central1">EU Central 1</option>
                                <option value="custom">Custom</option>
                            </select>
                            <input id="mwp_wpvivid_wasabi_endpoint_edit" style="width:205px" type="text" class="regular-text" autocomplete="off" option="edit-wasabi-addon" name="endpoint" value="s3.wasabisys.com" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the Wasabi Endpoint for the storage</i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'wasabi-addon', 'edit'); ?>

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
        <script>
            jQuery('#mwp_wpvivid_wasabi_endpoint_select_edit').change(function()
            {
                if('us_east1'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint_edit').val('s3.wasabisys.com');
                }
                else if('us_east2'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint_edit').val('s3.us-east-2.wasabisys.com');
                }
                else if('us_west1'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint_edit').val('s3.us-west-1.wasabisys.com');
                }
                else if('us_central1'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint_edit').val('s3.eu-central-1.wasabisys.com');
                }
                else if('custom'==jQuery(this).val())
                {
                    jQuery('#mwp_wpvivid_wasabi_endpoint').val('');
                }
            });
        </script>
        <?php
    }

    public function mwp_wpvivid_remote_pic_wasabi($remote){
        $remote['wasabi']['default_pic'] = '/admin/images/storage-wasabi(gray).png';
        $remote['wasabi']['selected_pic'] = '/admin/images/storage-wasabi.png';
        $remote['wasabi']['title'] = 'Wasabi';
        return $remote;
    }

    public function mwp_wpvivid_storage_provider_wasabi($storage_type){
        if($storage_type == MAINWP_WPVIVID_REMOTE_WASABI){
            $storage_type = 'Wasabi';
        }
        return $storage_type;
    }

    public function sanitize_options($skip_name=''){
        $ret['result']='failed';
        if(!isset($this->options['name'])) {
            $ret['error']="Warning: An alias for remote storage is required.";
            return $ret;
        }

        $this->options['name']=sanitize_text_field($this->options['name']);

        if(empty($this->options['name'])) {
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

        if(!isset($this->options['access'])) {
            $ret['error']="Warning: The access key for Amazon S3 is required.";
            return $ret;
        }

        $this->options['access']=sanitize_text_field($this->options['access']);

        if(empty($this->options['access'])) {
            $ret['error']="Warning: The access key for Amazon S3 is required.";
            return $ret;
        }

        if(!isset($this->options['secret'])) {
            $ret['error']="Warning: The storage secret key is required.";
            return $ret;
        }

        $this->options['secret']=sanitize_text_field($this->options['secret']);

        if(empty($this->options['secret'])) {
            $ret['error']="Warning: The storage secret key is required.";
            return $ret;
        }
        $this->options['secret'] = base64_encode($this->options['secret']);
        $this->options['is_encrypt'] = 1;

        if(!isset($this->options['bucket'])) {
            $ret['error']="Warning: A Bucket name is required.";
            return $ret;
        }

        $this->options['bucket']=sanitize_text_field($this->options['bucket']);

        if(empty($this->options['bucket'])) {
            $ret['error']="Warning: A Bucket name is required.";
            return $ret;
        }

        if(!isset($this->options['endpoint'])) {
            $ret['error']="Warning: The end-point is required.";
            return $ret;
        }

        $this->options['endpoint']=sanitize_text_field($this->options['endpoint']);

        if(empty($this->options['endpoint'])) {
            $ret['error']="Warning: The end-point is required.";
            return $ret;
        }

        $ret['result']='success';
        $ret['options']=$this->options;
        return $ret;
    }

    public function test_connect($is_pro){
        return array('result' => 'success');
    }
}