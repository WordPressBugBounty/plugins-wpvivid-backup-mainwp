<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

define('MAINWP_WPVIVID_REMOTE_GOOGLEDRIVE','googledrive');
define('MAINWP_WPVIVID_GOOGLEDRIVE_DEFAULT_FOLDER','wpvivid_backup');
define('MAINWP_WPVIVID_GOOGLEDRIVE_SECRETS',MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR.'/includes/customclass/client_secrets.json');
define('MAINWP_WPVIVID_GOOGLEDRIVE_UPLOAD_SIZE',1024*1024*2);
define('MAINWP_WPVIVID_GOOGLE_NEED_PHP_VERSION','5.5');
class Mainwp_Wpvivid_Google_drive extends Mainwp_WPvivid_Remote
{
    public $options;

    public $google_drive_secrets;

    public function __construct($options=array())
    {
        if(empty($options))
        {
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_GOOGLE_DRIVE'))
            {
                add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_google_drive'), 10);
                add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_google_drive'), 10);
                add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_google_drive_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_google_drive_addon'), 9);
                add_action('mwp_wpvivid_add_storage_page_google_drive_addon', array($this, 'mwp_wpvivid_add_storage_page_google_drive_addon'));
                add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_google_drive'),10);
                add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_google_drive'),10);
                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_GOOGLE_DRIVE',1);
            }
        }
        else
        {
            $this->options=$options;
        }
        $this->google_drive_secrets = array("web"=>array(
            "client_id"=>"134809148507-32crusepgace4h6g47ota99jjrvf4j1u.apps.googleusercontent.com",
            "project_id"=>"wpvivid-auth",
            "auth_uri"=>"https://accounts.google.com/o/oauth2/auth",
            "token_uri"=>"https://oauth2.googleapis.com/token",
            "auth_provider_x509_cert_url"=>"https://www.googleapis.com/oauth2/v1/certs",
            "client_secret"=>"GmD5Kmg_1fTcf0ciNEomposy",
            "redirect_uris"=>array("https://auth.wpvivid.com/google_drive")
        ));
    }

    public function mwp_wpvivid_add_storage_tab_google_drive()
    {
        ?>
        <div class="mwp-storage-providers" remote_type="googledrive" onclick="select_remote_storage(event, 'storage_account_google_drive');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/stroage-google-drive.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Google Drive', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_google_drive()
    {
        ?>
        <div id="storage_account_google_drive" class="storage-account-page" style="display:none;">
            <p>Global configuration is not available for GoogleDrive due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_tab_google_drive_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="googledrive" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_google_drive_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/stroage-google-drive.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Google Drive', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_google_drive_addon(){
        ?>
        <div id="mwp_wpvivid_storage_account_google_drive_addon" class="storage-account-page-addon">
            <p>Global configuration is not available for GoogleDrive due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_google_drive($remote)
    {
        $remote['googledrive']['default_pic'] = '/admin/images/stroage-google-drive(gray).png';
        $remote['googledrive']['selected_pic'] = '/admin/images/stroage-google-drive.png';
        $remote['googledrive']['title'] = 'Google Drive';
        return $remote;
    }

    public function sanitize_options($skip_name='')
    {
        $ret['result']='success';

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
        foreach ($remoteslist['upload'] as $key=>$value)
        {
            if(isset($value['name'])&&$value['name'] == $this->options['name']&&$skip_name!=$value['name'])
            {
                $ret['error']="Warning: The alias already exists in storage list.";
                return $ret;
            }
        }

        $ret['options']=$this->options;
        return $ret;
    }

    public function test_connect($is_pro)
    {
        return array('result' => 'success');
    }

    public function mwp_wpvivid_storage_provider_google_drive($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_GOOGLEDRIVE){
            $storage_type = 'Google Drive';
        }
        return $storage_type;
    }
}