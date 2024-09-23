<?php
if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}
if(!defined('MAINWP_WPVIVID_REMOTE_S3COMPAT')){
    define('MAINWP_WPVIVID_REMOTE_S3COMPAT','s3compat');
}
define('MAINWP_WPVIVID_S3COMPAT_DEFAULT_FOLDER','/wpvivid_backup');
define('MAINWP_WPVIVID_S3COMPAT_NEED_PHP_VERSION','5.3.9');
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

class Mainwp_Wpvivid_S3Compat extends Mainwp_WPvivid_Remote{
    private $options;
    private $bucket;
    private $region;

    public function __construct($options = array())
    {
        if(empty($options)){
            add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_s3compat'), 11);
            add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_s3compat'), 11);
            add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_s3compat_addon'), 11);
            add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_s3compat_addon'), 11);
            add_action('mwp_wpvivid_add_storage_page_s3compat_addon', array($this, 'mwp_wpvivid_add_storage_page_s3compat_addon'));
            add_action('mwp_wpvivid_edit_storage_page_addon', array($this, 'mwp_wpvivid_edit_storage_page_s3compat_addon'), 11);
            add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_s3compat'),11);
            add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_s3compat'),10);
        }else{
            $this -> options = $options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_s3compat_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="s3compat" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_s3compat_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-digitalocean.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('DigitalOcean Spaces', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_s3compat_addon(){
        ?>
        <div class="storage-account-page-addon" id="mwp_wpvivid_storage_account_s3compat_addon">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your DigitalOcean Spaces Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat-addon" name="name" placeholder="Enter a unique alias: e.g. DOS-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat-addon" name="access" placeholder="S3 access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your S3 access key</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="s3compat-addon" name="secret" placeholder="S3 secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your S3 secret key</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat-addon" name="bucket" placeholder="Bucket Name(e.g. test)" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat-addon" name="endpoint" placeholder="region.digitaloceanspaces.com" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the service Endpoint for the storage</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan=2>
                        <label><input class="s3compat-addon" type="checkbox" option="s3compat-addon" name="use_region" onclick="mwp_wpvivid_check_special_region(this);">Enter the bucket region(if any)
                    </td>
                </tr>

                <tr class="mwp-wpvivid-region-tr-s3compat-addon" style="display: none;">
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat-addon" name="region" placeholder="region, e,g., ru-1" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the region of the s3 bucket.</i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 's3compat-addon', 'add'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="s3compat-addon" name="use_path_style_endpoint" />Use path-style access.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Use path-style to indicate to an S3-compatible storage. <a href="https://docs.wpvivid.com/path-style-access-to-s3-compatible-storage.html" target='_blank'>learn more...</a></i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input class="ui green mini button" option="add-remote-addon-global" type="button" value="Save and Sync" remote_type="s3compat" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Click the button to connect to DigitalOcean Spaces storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <script>
            function mwp_wpvivid_check_special_region(obj)
            {
                var class_name = jQuery(obj).attr('class');
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('.mwp-wpvivid-region-tr-'+class_name).show();
                }
                else
                {
                    jQuery('.mwp-wpvivid-region-tr-'+class_name).hide();
                }
            }
        </script>
        <?php
    }

    public function mwp_wpvivid_edit_storage_page_s3compat_addon(){
        ?>
        <div class="mwp-wpvivid-remote-storage-edit" id="mwp_wpvivid_storage_account_s3compat_edit" style="display: none;">
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;"><strong>Enter Your DigitalOcean Spaces Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-s3compat-addon" name="name" placeholder="Enter a unique alias: e.g. DOS-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="edit-s3compat-addon" name="access" placeholder="S3 access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your S3 access key</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="edit-s3compat-addon" name="secret" placeholder="S3 secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your S3 secret key</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-s3compat-addon" name="bucket" placeholder="Bucket Name(e.g. test)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span><?php echo /* translators: %s: Folder name. */ sprintf(esc_html('Enter an existing Bucket in which you want to create a parent folder for holding %s folders.', 'wpvivid'), esc_html(apply_filters('wpvivid_white_label_display', 'WPvivid backup'))); ?></span></i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-s3compat-addon" name="root_path" value="<?php echo esc_attr(apply_filters('wpvivid_white_label_remote_root_path', 'wpvividbackuppro')); ?>" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="edit-s3compat-addon" name="endpoint" placeholder="region.digitaloceanspaces.com" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the service Endpoint for the storage</i>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td colspan=2>
                        <label><input class="edit-s3compat-addon" type="checkbox" option="edit-s3compat-addon" name="use_region" onclick="mwp_wpvivid_check_special_edit_region(this);">Enter the bucket region(if any)
                    </td>
                </tr>

                <tr class="mwp-wpvivid-region-tr-edit-s3compat-addon" style="display: none;">
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="edit-s3compat-addon" name="region" placeholder="region, e,g., ru-1" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the region of the s3 bucket.</i>
                        </div>
                    </td>
                </tr>

                <?php do_action('mwp_wpvivid_remote_storage_backup_retention', 's3compat-addon', 'edit'); ?>

                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="edit-s3compat-addon" name="use_path_style_endpoint" />Use path-style access.
                            </label>
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Use path-style to indicate to an S3-compatible storage. <a href="https://docs.wpvivid.com/path-style-access-to-s3-compatible-storage.html" target='_blank'>learn more...</a></i>
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
                            <i>Click the button to connect to DigitalOcean Spaces storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <script>
            function mwp_wpvivid_check_special_edit_region(obj)
            {
                var class_name = jQuery(obj).attr('class');
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('.mwp-wpvivid-region-tr-'+class_name).show();
                }
                else
                {
                    jQuery('.mwp-wpvivid-region-tr-'+class_name).hide();
                }
            }
        </script>
        <?php
    }

    public function getClient(){
        $res = $this -> compare_php_version();
        if($res['result'] == 'failed')
            return $res;

        if(isset($this->options['s3directory']))
        {
            $path_temp = str_replace('s3generic://','',$this->options['s3directory'].$this -> options['path']);
            if (preg_match("#^/*([^/]+)/(.*)$#", $path_temp, $bmatches))
            {
                $this->bucket = $bmatches[1];
            } else {
                $this->bucket = $path_temp;
            }
            $this->options['path']=ltrim($this -> options['path'],'/');
            $endpoint_temp = str_replace('https://','',$this->options['endpoint']);
            $explodes = explode('.',$endpoint_temp);
            $this -> region = $explodes[0];
            $this -> options['endpoint'] = 'https://'.trailingslashit($endpoint_temp);
        }
        else
        {
            $endpoint_temp = str_replace('https://','',$this->options['endpoint']);
            $explodes = explode('.',$endpoint_temp);
            $this -> region = $explodes[0];
            $this -> options['endpoint'] = 'https://'.trailingslashit($endpoint_temp);
            $this -> bucket=$this->options['bucket'];
        }

        include_once WPVIVID_PLUGIN_DIR.'/vendor/autoload.php';
        $s3compat = S3Client::factory(
            array(
                'credentials' => array(
                    'key' => $this -> options['access'],
                    'secret' => $this -> options['secret'],
                ),
                'version' => 'latest',
                'region'  => $this -> region,
                'endpoint' => $this -> options['endpoint'],
            )
        );
        return $s3compat;
    }

    public function test_connect($is_pro)
    {
        $s3compat = $this -> getClient();
        if(is_array($s3compat) && $s3compat['result'] == 'failed')
        {
            return $s3compat;
        }

        $temp_file = md5(wp_rand());

        try
        {
            $result = $s3compat->putObject(
                array(
                    'Bucket'=>$this->bucket,
                    'Key' =>  $this->options['path'].'/'.$temp_file,
                    'Body' => $temp_file,
                )
            );
            $etag = $result->get('ETag');
            if(!isset($etag))
            {
                return array('result'=>'failed','error'=>'We successfully accessed the bucket, but create test file failed.');
            }
            $result = $s3compat->deleteObject(array(
                'Bucket' => $this -> bucket,
                'Key'    => $this -> options['path'].'/'.$temp_file,
            ));
            if(empty($result))
            {
                return array('result'=>'failed','error'=>'We successfully accessed the bucket, and create test file succeed, but delete test file failed.');
            }
        }
        catch(S3Exception $e)
        {
            return array('result' => 'failed','error' => $e -> getAwsErrorCode().$e -> getMessage());
        }
        catch(Exception $e)
        {
            return array('result' => 'failed','error' => $e -> getMessage());
        }
        return array('result' => 'success');
    }

    public function mwp_wpvivid_add_storage_tab_s3compat(){
        ?>
        <div class="mwp-storage-providers" remote_type="s3compat" onclick="select_remote_storage(event, 'storage_account_s3compat');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-digitalocean.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('DigitalOcean Spaces', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_s3compat(){
        ?>
        <div id="storage_account_s3compat"  class="storage-account-page" style="display:none;">
            <div class="mwp-wpvivid-block-bottom-space"><strong>Enter Your DigitalOcean Spaces Account</strong></div>
            <table class="wp-list-table widefat plugins" style="width:100%;">
                <tbody>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat" name="name" placeholder="Enter a unique alias: e.g. DOS-001" onkeyup="value=value.replace(/[^a-zA-Z0-9\-_]/g,'')" style="height: 27px;" />
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
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat" name="access" placeholder="DigitalOcean Spaces access key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your DigitalOcean Spaces access key</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="password" class="regular-text" autocomplete="new-password" option="s3compat" name="secret" placeholder="DigitalOcean Spaces secret key" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter your DigitalOcean Spaces secret key</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat" name="bucket" placeholder="Space Name(e.g. test)" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span>Enter an existed Space to create a custom backup storage directory.</span></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat" name="path" placeholder="Custom Path" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i><span>Customize the directory where you want to store backups within the Space.</span></i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-form">
                            <input type="text" class="regular-text" autocomplete="off" option="s3compat" name="endpoint" placeholder="region.digitaloceanspaces.com" style="height: 27px;" />
                        </div>
                    </td>
                    <td class="column-description desc">
                        <div class="mwp-wpvivid-storage-form-desc">
                            <i>Enter the DigitalOcean Endpoint for the storage</i>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="plugin-title column-primary">
                        <div class="mwp-wpvivid-storage-select">
                            <label>
                                <input type="checkbox" option="s3compat" name="default" checked />Set as the default remote storage.
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
                            <i>Click the button to connect to DigitalOcean Spaces storage and add it to the storage list below.</i>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_s3compat($remote){
        $remote['s3compat']['default_pic'] = '/admin/images/storage-digitalocean(gray).png';
        $remote['s3compat']['selected_pic'] = '/admin/images/storage-digitalocean.png';
        $remote['s3compat']['title'] = 'DigitalOcean Spaces';
        return $remote;
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
            $ret['error']="Warning: The access key for S3-Compatible is required.";
            return $ret;
        }

        $this->options['access']=sanitize_text_field($this->options['access']);

        if(empty($this->options['access']))
        {
            $ret['error']="Warning: The access key for S3-Compatible is required.";
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

        if(empty($this->options['bucket']))
        {
            $ret['error']="Warning: A Digital Space is required.";
            return $ret;
        }

        if(!isset($this->options['endpoint']))
        {
            $ret['error']="Warning: The end-point is required.";
            return $ret;
        }

        $this->options['endpoint']=sanitize_text_field($this->options['endpoint']);

        if(empty($this->options['endpoint']))
        {
            $ret['error']="Warning: The end-point is required.";
            return $ret;
        }

        $ret['result']='success';
        $ret['options']=$this->options;
        return $ret;
    }
    private function compare_php_version()
    {
        if(version_compare(MAINWP_WPVIVID_S3COMPAT_NEED_PHP_VERSION,phpversion()) > 0){
            return array('result' => 'failed','error' => 'The required PHP version is higher than '.MAINWP_WPVIVID_S3COMPAT_NEED_PHP_VERSION.'. After updating your PHP version, please try again.');
        }
        return array('result' => 'success');
    }

    public function mwp_wpvivid_storage_provider_s3compat($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_S3COMPAT){
            $storage_type = 'DigitalOcean Spaces';
        }
        return $storage_type;
    }
}