<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}
if(!defined('MAINWP_WPVIVID_REMOTE_DROPBOX')){
    define('MAINWP_WPVIVID_REMOTE_DROPBOX','dropbox');
}
define('MAINWP_WPVIVID_DROPBOX_DEFAULT_FOLDER','/');
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-base-dropbox.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';
class Mainwp_WPvivid_Dropbox extends Mainwp_WPvivid_Remote {

    private $options;
    private $redirect_url = 'https://auth.wpvivid.com/dropbox';

    public function __construct($options = array())
    {
        if(empty($options)){
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_DROPBOX'))
            {
                add_action('mwp_wpvivid_add_storage_tab',array($this,'mwp_wpvivid_add_storage_tab_dropbox'), 10);
                add_action('mwp_wpvivid_add_storage_page',array($this,'mwp_wpvivid_add_storage_page_dropbox'), 10);
                add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_dropbox_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_dropbox_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_dropbox_addon', array($this, 'mwp_wpvivid_add_storage_page_dropbox_addon'));
                add_filter('mwp_wpvivid_remote_pic',array($this,'mwp_wpvivid_remote_pic_dropbox'),10);
                add_filter('mwp_wpvivid_storage_provider_tran',array($this,'mwp_wpvivid_storage_provider_dropbox'),10);

                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_DROPBOX',1);
            }
        }else{
            $this -> options = $options;
        }
    }

    public function test_connect($is_pro)
    {
        return array('result' => 'success');
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

    public function mwp_wpvivid_add_storage_tab_dropbox(){
        ?>
        <div class="mwp-storage-providers" remote_type="dropbox" onclick="select_remote_storage(event, 'storage_account_dropbox');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-dropbox.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Dropbox', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }
    public function mwp_wpvivid_add_storage_page_dropbox(){
        ?>
        <div id="storage_account_dropbox" class="storage-account-page" style="display:none;">
            <p>Global configuration is not available for Dropbox due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_tab_dropbox_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="dropbox" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_dropbox_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-dropbox.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('Dropbox', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_dropbox_addon(){
        ?>
        <div id="mwp_wpvivid_storage_account_dropbox_addon" class="storage-account-page-addon">
            <p>Global configuration is not available for Dropbox due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_dropbox($remote)
    {
        $remote['dropbox']['default_pic'] = '/admin/images/storage-dropbox(gray).png';
        $remote['dropbox']['selected_pic'] = '/admin/images/storage-dropbox.png';
        $remote['dropbox']['title'] = 'Dropbox';
        return $remote;
    }

    public function mwp_wpvivid_get_out_of_date_dropbox($out_of_date_remote, $remote)
    {
        if($remote['type'] == MAINWP_WPVIVID_REMOTE_DROPBOX){
            $root_path=apply_filters('wpvivid_get_root_path', $remote['type']);
            $out_of_date_remote = $root_path.$remote['path'];
        }
        return $out_of_date_remote;
    }

    public function mwp_wpvivid_storage_provider_dropbox($storage_type)
    {
        if($storage_type == MAINWP_WPVIVID_REMOTE_DROPBOX){
            $storage_type = 'Dropbox';
        }
        return $storage_type;
    }
}