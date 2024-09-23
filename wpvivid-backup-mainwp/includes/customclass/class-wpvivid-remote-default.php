<?php
if (!defined('WPVIVID_PLUGIN_DIR')){
    die;
}

require_once WPVIVID_PLUGIN_DIR .'/includes/customclass/class-wpvivid-remote.php';
class WPvivid_Remote_Defult extends WPvivid_Remote{
    public function test_connect($is_pro)
    {
        return array('result' => MAINWP_WPVIVID_FAILED,'error'=> 'Type incorrect.');
    }

    public function upload($task_id, $files, $callback = '')
    {
        return array('result' => MAINWP_WPVIVID_FAILED,'error'=> 'Type incorrect.');
    }

    public function download( $file, $local_path, $callback = '')
    {
        return array('result' => MAINWP_WPVIVID_FAILED,'error'=> 'Type incorrect.');
    }

    public function cleanup($files)
    {
        return array('result' => MAINWP_WPVIVID_FAILED,'error'=> 'Type incorrect.');
    }
}