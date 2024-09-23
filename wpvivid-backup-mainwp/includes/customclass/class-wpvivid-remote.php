<?php
if (!defined('MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR')){
    die;
}
abstract class Mainwp_WPvivid_Remote{
    public $current_file_name = '';
    public $current_file_size = '';
    public $last_time = 0;
    public $last_size = 0;

    public $object;
    public $remote;

    abstract public function test_connect($is_pro);
}