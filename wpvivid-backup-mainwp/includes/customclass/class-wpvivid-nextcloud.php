<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_NEXTCLOUD','nextcloud');

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

class Mainwp_WPvivid_NextcloudClass extends Mainwp_WPvivid_Remote
{
    public $options;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_NEXTCLOUD'))
            {
                add_action('mwp_wpvivid_add_storage_page_nextcloud_addon', array($this, 'mwp_wpvivid_add_storage_page_nextcloud_addon'));
                add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_nextcloud_addon'), 11);
                add_filter('mwp_wpvivid_storage_provider_tran', array($this, 'mwp_wpvivid_storage_provider_nextcloud'), 10);
                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_NEXTCLOUD',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_page_nextcloud_addon()
    {
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_nextcloud_addon">
            <div style="padding: 0 10px 10px 0;"><strong>Enter Your Nextcloud Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud-addon" name="name" placeholder="Enter a unique alias: e.g. NEXTCLOUD-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" autocomplete="off" option="nextcloud-addon" name="host" placeholder="Host" style="width:220px;" />
                                <span style="width:10px;">/</span>
                                <input type="text" value="remote.php/dav" style="width:120px;" readonly="readonly" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the storage hostname.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud-addon" name="username" placeholder="Username" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the username.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="off" option="nextcloud-addon" name="password" placeholder="Password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="nextcloud-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(esc_html('Customize a root directory in the storage for holding WPvivid backup directories.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'nextcloud-addon', 'add'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="nextcloud-addon" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
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
                            <input class="ui green mini button" option="add-remote-addon-global" type="button" value="Save and Sync" remote_type="nextcloud" />
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

    public function mwp_wpvivid_edit_storage_page_nextcloud_addon()
    {
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_nextcloud_edit" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your Nextcloud Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <form>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud-addon" name="name" placeholder="Enter a unique alias: e.g. NEXTCLOUD-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" />
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
                                <input type="text" autocomplete="off" option="edit-nextcloud-addon" name="host" placeholder="Host" style="width:220px;" />
                                <span style="width:10px;">/</span>
                                <input type="text" value="remote.php/dav" style="width:120px;" readonly="readonly" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the storage hostname.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud-addon" name="username" placeholder="Username" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the username.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="password" class="regular-text" autocomplete="off" option="edit-nextcloud-addon" name="password" placeholder="Password" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i>Enter the password.</i>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input type="text" class="regular-text" autocomplete="off" option="edit-nextcloud-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
                            </div>
                        </td>
                        <td class="column-description desc">
                            <div class="mwp-wpvivid-storage-form-desc">
                                <i><span><?php echo sprintf(esc_html('Customize a root directory in the storage for holding WPvivid backup directories.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                            </div>
                        </td>
                    </tr>

                    <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 'nextcloud-addon', 'edit'); ?>

                    <tr>
                        <td class="plugin-title column-primary">
                            <div class="mwp-wpvivid-storage-form">
                                <input style="width: 50px" type="text" class="regular-text" autocomplete="off" option="edit-nextcloud-addon" name="chunk_size" placeholder="Chunk size" value="3" onkeyup="value=value.replace(/\D/g,'')" />MB
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

    public function mwp_wpvivid_storage_provider_nextcloud($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_NEXTCLOUD)
        {
            $storage_type = 'NextCloud';
        }
        return $storage_type;
    }

    public function sanitize_options($skip_name='')
    {
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

        if(!isset($this->options['host']))
        {
            $ret['error']="Warning: The hostname for Nextcloud is required.";
            return $ret;
        }

        $this->options['host']=sanitize_text_field($this->options['host']);

        if(empty($this->options['host']))
        {
            $ret['error']="Warning: The hostname for Nextcloud is required.";
            return $ret;
        }

        if(!isset($this->options['username']))
        {
            $ret['error']="Warning: The username for Nextcloud is required.";
            return $ret;
        }

        $this->options['username']=sanitize_text_field($this->options['username']);

        if(empty($this->options['username']))
        {
            $ret['error']="Warning: The username for Nextcloud is required.";
            return $ret;
        }

        if(!isset($this->options['password']))
        {
            $ret['error']="Warning: The password is required.";
            return $ret;
        }

        if(!isset($this->options['root_path']))
        {
            $ret['error']="Warning: The root path is required.";
            return $ret;
        }

        $this->options['root_path']=sanitize_text_field($this->options['root_path']);

        if(empty($this->options['root_path']))
        {
            $ret['error']="Warning: The root path is required.";
            return $ret;
        }

        $ret['result']='success';
        $ret['options']=$this->options;
        return $ret;
    }

    public function test_connect($is_pro)
    {
        return array('result' => 'success');
    }
}