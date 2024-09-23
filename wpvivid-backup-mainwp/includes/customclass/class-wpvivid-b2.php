<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_B2','b2');

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

class Mainwp_WPvivid_B2Class extends Mainwp_WPvivid_Remote
{
    public $options;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_B2'))
            {
                add_action('mwp_wpvivid_add_storage_page_b2_addon', array($this, 'mwp_wpvivid_add_storage_page_b2_addon'));
                add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_b2_addon'), 11);
                add_filter('mwp_wpvivid_storage_provider_tran', array($this, 'mwp_wpvivid_storage_provider_b2'), 10);
                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_B2',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_page_b2_addon(){
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_b2_addon">
            <div style="padding: 0 10px 10px 0;"><strong>Enter Your Backblaze Storage Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2-addon" name="name" placeholder="Enter a unique alias: e.g. B2-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="b2-addon" name="appkeyid" placeholder="Application key id" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter your Application Key ID.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2-addon" name="appkey" placeholder="Application key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter your Application Key.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2-addon" name="bucket" placeholder="Backblaze Bucket Name(e.g. test)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Enter an existing Bucket in which you want to create a parent folder for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="b2-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'b2-addon', 'add'); ?>
                    
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="b2-addon" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>The block size of uploads and downloads. Reduce it if you encounter a timeout when transferring files.</i>
                            </div>
                        </td>
                    </tr>
                </form>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="add-remote-addon-global" type="button" value="Save and Sync" remote_type="b2" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to B2 storage and add it to the storage list below</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_edit_storage_page_b2_addon()
    {
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_b2_edit" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your Backblaze Storage Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2-addon" name="name" placeholder="Enter a unique alias: e.g. B2-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2-addon" name="appkeyid" placeholder="Application key id" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter your Application Key ID.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2-addon" name="appkey" placeholder="Application key" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter your Application Key.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2-addon" name="bucket" placeholder="Backblaze Bucket Name(e.g. test)" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Enter an existing Bucket in which you want to create a parent folder for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-b2-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php /* translators: %s: Folder name. */ echo sprintf(esc_html('Customize a parent folder in the Bucket for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'b2-addon', 'edit'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-b2-addon" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>The block size of uploads and downloads. Reduce it if you encounter a timeout when transferring files.</i>
                            </div>
                        </td>
                    </tr>
                </form>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="edit-remote-addon-global" type="button" value="Save Changes" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to B2 storage and add it to the storage list below</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_storage_provider_b2($storage_type){
        if($storage_type == MAINWP_WPVIVID_REMOTE_B2){
            $storage_type = 'Backblaze';
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

        if(!isset($this->options['appkeyid']))
        {
            $ret['error']="Warning: The app key id for Backblaze is required.";
            return $ret;
        }

        $this->options['appkeyid']=sanitize_text_field($this->options['appkeyid']);

        if(empty($this->options['appkeyid']))
        {
            $ret['error']="Warning: The app key id for Backblaze is required.";
            return $ret;
        }

        if(!isset($this->options['appkey']))
        {
            $ret['error']="Warning: The storage app key is required.";
            return $ret;
        }

        $this->options['appkey']=sanitize_text_field($this->options['appkey']);

        if(empty($this->options['appkey']))
        {
            $ret['error']="Warning: The storage app key is required.";
            return $ret;
        }

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

    public function test_connect($is_pro){
        return array('result' => 'success');
    }
}