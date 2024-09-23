<?php

if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}

define('MAINWP_WPVIVID_REMOTE_PCLOUD','pCloud');

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-remote.php';

class Mainwp_WPvivid_pCloud extends Mainwp_WPvivid_Remote{
    public $options;

    public function __construct($options=array()){
        if(empty($options)) {
            if(!defined('MAINWP_WPVIVID_INIT_STORAGE_TAB_PCLOUD')) {
                add_action('mwp_wpvivid_add_storage_tab_addon', array($this, 'mwp_wpvivid_add_storage_tab_pcloud_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_addon', array($this, 'mwp_wpvivid_add_storage_page_pcloud_addon'), 10);
                add_action('mwp_wpvivid_add_storage_page_pcloud_addon', array($this, 'mwp_wpvivid_add_storage_page_pcloud_addon'));
                add_filter('mwp_wpvivid_remote_pic', array($this, 'mwp_wpvivid_remote_pic_pcloud'), 11);
                add_filter('mwp_wpvivid_storage_provider_tran', array($this, 'mwp_wpvivid_storage_provider_pcloud'), 10);
                define('MAINWP_WPVIVID_INIT_STORAGE_TAB_PCLOUD',1);
            }
        }
        else {
            $this->options=$options;
        }
    }

    public function mwp_wpvivid_add_storage_tab_pcloud_addon(){
        ?>
        <div class="mwp-storage-providers-addon" remote_type="pcloud" onclick="select_remote_storage_addon(event, 'mwp_wpvivid_storage_account_pcloud_addon');">
            <img src="<?php echo esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/storage-pcloud.png'); ?>" style="vertical-align:middle;"/><?php esc_html_e('pCloud', 'mainwp-wpvivid-extension'); ?>
        </div>
        <?php
    }

    public function mwp_wpvivid_add_storage_page_pcloud_addon(){
        ?>
        <div id="mwp_wpvivid_storage_account_pcloud_addon" class="storage-account-page-addon">
            <p>Global configuration is not available for pCloud due to authorization mechanism （tokens will be expired）. Please get authorization in child-sites</p>
        </div>
        <?php
    }

    public function mwp_wpvivid_remote_pic_pcloud($remote){
        $remote['pCloud']['default_pic'] = '/admin/images/storage-pcloud(gray).png';
        $remote['pCloud']['selected_pic'] = '/admin/images/storage-pcloud.png';
        $remote['pCloud']['title'] = 'pCloud';
        return $remote;
    }

    public function mwp_wpvivid_storage_provider_pcloud($storage_type){
        if($storage_type == MAINWP_WPVIVID_REMOTE_PCLOUD){
            $storage_type = 'pCloud';
        }
        return $storage_type;
    }

    public function test_connect($is_pro){
        return array('result' => 'success');
    }
}