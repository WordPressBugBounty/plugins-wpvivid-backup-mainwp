<?php
/**
 * Created by PhpStorm.
 * User: alienware`x
 * Date: 2019/2/14
 * Time: 16:06
 */

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

define('MAINWP_WPVIVID_REMOTE_ONEDRIVE','onedrive');
define('MAINWP_WPVIVID_ONEDRIVE_DEFAULT_FOLDER','wpvivid_backup');
define('MAINWP_WPVIVID_ONEDRIVE_RETRY_TIMES','3');

class Mainwp_WPvivid_one_drive extends Mainwp_WPvivid_Remote
{
    public $options;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_ONE_DRIVE'))
            {
                add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_one_drive'), 10);
                add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_one_drive'), 10);
                add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_one_drive_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_one_drive_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_one_drive_addon', array($this, 'mwp_wpvivid_add_storage_page_one_drive_addon'));
                add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_one_drive'),10);
                add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_one_drive'),10);
                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_ONE_DRIVE',1);
            }
        }
        else
        {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_one_drive()
    {
        ?>
        <div class="mwp-storage-providers" remote_type="one_drive" onclick="select_remote_storage(event, 'storage_account_one_drive');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-microsoft-onedrive.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Microsoft OneDrive', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_one_drive()
    {
        ?>
        <div id="storage_account_one_drive" class="storage-account-page" style="display:none;">
            <p>Global configuration is not available for OneDrive due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_tab_one_drive_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="one_drive" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_one_drive_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-microsoft-onedrive.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Microsoft OneDrive', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_one_drive_addon(){
        ?>
        <div id="mwp_wpvivid_storage_account_one_drive_addon" class="storage-account-page-addon">
            <p>Global configuration is not available for OneDrive due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']='success';

        if(!isset($this->options['name']))
        {
            $ret['error']="Warning: An alias for remote storage is required.";
            return $ret;
        }

        $ret['options']=$this->options;
        return $ret;
    }

    public function test_connect($is_pro)
    {
        return array('result' => 'success');
    }

    public function mwp_wpvivid_remote_pic_one_drive($remote)
    {
        $remote['onedrive']['default_pic'] = '/admin/images/storage-microsoft-onedrive(gray).png';
        $remote['onedrive']['selected_pic'] = '/admin/images/storage-microsoft-onedrive.png';
        $remote['onedrive']['title'] = 'Microsoft OneDrive';
        return $remote;
    }

    public function mwp_wpvivid_storage_provider_one_drive($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_ONEDRIVE){
            $storage_type = 'Microsoft OneDrive';
        }
        return $storage_type;
    }
}