<?php

require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR. '/includes/customclass/class-wpvivid-sftpclass.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR .'/includes/customclass/class-wpvivid-ftpclass.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-amazons3-plus.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-s3compat.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-google-drive.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-dropbox.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-one-drive.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-wasabi.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-b2.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-pcloud.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-webdav.php';
require_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/customclass/class-wpvivid-nextcloud.php';

class Mainwp_WPvivid_Remote_collection
{
    private $remote_collection=array();

    public function __construct()
    {
        add_filter('wpvivid_remote_register', array($this, 'init_remotes'),10);
        $this->remote_collection=apply_filters('wpvivid_remote_register',$this->remote_collection);
        $this->load_hooks();
    }

    public function get_remote($remote)
    {
        if(is_array($remote)&&array_key_exists('type',$remote)&&array_key_exists($remote['type'],$this->remote_collection))
        {
            $class_name =$this->remote_collection[$remote['type']];

            if(class_exists($class_name))
            {
                $object = new $class_name($remote);
                return $object;
            }
        }
        $object = new $this ->remote_collection['default']();
        return  $object;
    }

    public function add_remote($remote_option, $is_pro=false)
    {
        $remote=$this->get_remote($remote_option);

        $ret=$remote->sanitize_options();

        if($ret['result']=='success')
        {
            $remote_option=$ret['options'];
            $ret=$remote->test_connect($is_pro);
            if($ret['result']=='success')
            {
                $ret=array();
                $default=$remote_option['default'];
                unset($remote_option['default']);
                if($is_pro){
                    Mainwp_WPvivid_Extension_Option::get_instance()->add_global_remote_addon($remote_option, $default);
                }
                else {
                    Mainwp_WPvivid_Extension_Option::get_instance()->add_global_remote($remote_option, $default);
                }
                $ret['result']='success';
            }
        }

        return $ret;
    }

    public function init_remotes($remote_collection)
    {
        $remote_collection['sftp']='Mainwp_WPvivid_SFTPClass';
        $remote_collection['ftp']='Mainwp_WPvivid_FTPClass';
        $remote_collection['amazons3']='Mainwp_WPvivid_AMAZONS3Class';
        $remote_collection['s3compat'] = 'Mainwp_Wpvivid_S3Compat';
        $remote_collection[MAINWP_WPVIVID_REMOTE_GOOGLEDRIVE] = 'Mainwp_Wpvivid_Google_drive';
        $remote_collection[MAINWP_WPVIVID_REMOTE_ONEDRIVE] = 'Mainwp_Wpvivid_one_drive';
        $remote_collection['dropbox']='Mainwp_WPvivid_Dropbox';
        $remote_collection['wasabi']='Mainwp_Wpvivid_WasabiS3';
        $remote_collection['b2']='Mainwp_WPvivid_B2Class';
        $remote_collection['pcloud']='Mainwp_WPvivid_pCloud';
        $remote_collection['webdav']='Mainwp_WPvivid_WebDavClass';
        $remote_collection['nextcloud']='Mainwp_WPvivid_NextcloudClass';
        return $remote_collection;
    }

    public function load_hooks()
    {
        foreach ($this->remote_collection as $class_name)
        {
            $object = new $class_name();
        }
    }

    public function check_remote_options($remote_option){
        $remote=$this->get_remote($remote_option);
        $ret=$remote->sanitize_options();
        return $ret;
    }
}