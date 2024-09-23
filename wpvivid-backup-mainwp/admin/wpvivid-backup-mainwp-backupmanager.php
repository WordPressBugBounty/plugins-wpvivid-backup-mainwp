<?php

class Mainwp_WPvivid_Custom_Backup_Manager
{
    public $site_id;
    public $parent_id;
    public $advanced_id;
    public $is_global;
    public $option;
    public $is_mu_single;
    private $backup_custom_setting;

    public function __construct($backup_custom_setting = array())
    {
        $this->backup_custom_setting = $backup_custom_setting;
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_parent_id($parent_id, $option, $is_mu_single, $is_global)
    {
        $this->parent_id    = $parent_id;
        $this->option       = $option;
        $this->is_mu_single = $is_mu_single;
        $this->is_global    = $is_global;
    }

    public function wpvivid_set_advanced_id($advanced_id)
    {
        $this->advanced_id = $advanced_id;
    }

    public function output_custom_backup_db_table()
    {
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
            $database_check = 'checked="checked"';
            $additional_database_check = '';
            $additional_database_list = '';
        }
        else
        {
            $database_check = 'checked="checked"';
            $additional_database_check = '';

            if($this->option === 'manual_backup' || $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup')
            {
                $type = 'manual_backup';
                $custom_backup_history = array();
                if(isset($_GET['id']))
                {
                    $custom_backup_history=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
                }

                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['custom_dirs']['database_check'])) {
                        if ($custom_backup_history['custom_dirs']['database_check'] != '1') {
                            $database_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['additional_database_check'])) {
                        if ($custom_backup_history['custom_dirs']['additional_database_check'] == '1') {
                            $additional_database_check = 'checked="checked"';
                        }
                    }
                }

                $additional_database_list = '';
                if(isset($custom_backup_history['custom_dirs']['additional_database_list']))
                {
                    foreach ($custom_backup_history['custom_dirs']['additional_database_list'] as $database => $db_info)
                    {
                        $additional_database_list .= '<div class="wpvivid-text-line" database-name="'.$database.'" database-host="'.$db_info['db_host'].'" database-user="'.$db_info['db_user'].'" database-pass="'.$db_info['db_pass'].'"><span class="dashicons dashicons-trash wpvivid-icon-16px wpvivid-additional-database-remove" database-name="'.$database.'"></span><span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue wpvivid-icon-16px-nopointer"></span><span class="wpvivid-text-line" option="additional_db_custom" name="'.$database.'">'.$database.'@'.$db_info['db_host'].'</span></div>';
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_db_history = array();//self::get_incremental_option('db');
                if(isset($custom_incremental_db_history) && !empty($custom_incremental_db_history)){
                    if(isset($custom_incremental_db_history['custom_dirs']['database_check']))
                    {
                        if ($custom_incremental_db_history['custom_dirs']['database_check'] != '1')
                        {
                            $database_check = '';
                        }
                    }

                    if(!empty($custom_incremental_db_history['additional_database_option']))
                    {
                        if(isset($custom_incremental_db_history['additional_database_option']['additional_database_check']))
                        {
                            if ($custom_incremental_db_history['additional_database_option']['additional_database_check'] == '1')
                            {
                                $additional_database_check = 'checked';
                            }
                        }
                    }
                }

                $additional_database_list = '';
            }
        }


        $database_style = 'display: none;';
        if($type === 'manual_backup')
        {
            $key = 'general';
        }
        else
        {
            $key = 'incremental';
        }

        ?>
        <div>
            <span class="dashicons dashicons-admin-site-alt3 wpvivid-dashicons-blue"></span>
            <span><strong>Databases</strong></span>
        </div>

        <!-- Database Tables -->
        <div style="padding-left:2em;">
            <p style="margin: 1em 0;">
                <span><input type="checkbox" class="mwp-wpvivid-custom-database-check" <?php echo esc_attr($database_check); ?>><span class="mwp-wpvivid-handle-base-database-detail" style="cursor:pointer;"><strong>Database</strong></span></span>
                <?php
                if($this->is_global == '0')
                {
                    ?>
                    <span class="dashicons wpvivid-dashicons-grey mwp-wpvivid-handle-base-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                    <?php
                }
                ?>
            </p>
        </div>

        <?php
        if($this->is_global == '0')
        {
            ?>
            <div class="mwp-wpvivid-custom-database-info mwp-wpvivid-base-database-detail" style="display: none;">
                <div class="spinner is-active wpvivid-database-loading" style="margin: 0 5px 10px 0; float: left;"></div>
                <div style="float: left;">Archieving database tables</div>
                <div style="clear: both;"></div>
            </div>
            <?php
        }
        ?>
        <div style="clear:both;"></div>

        <?php
        if($this->is_global == '0')
        {
            ?>
            <!-- Additional Database -->
            <div style="padding-left:2em;">
                <p style="margin: 1em 0;">
                    <span><input type="checkbox" class="wpvivid-custom-additional-database-check" <?php echo esc_attr($additional_database_check); ?>><span class="mwp-wpvivid-handle-additional-database-detail" style="cursor:pointer;"><strong><span style="color:green;"><i>(optional)</i></span>Include Additional Databases</strong></span></span>
                    <span class="dashicons wpvivid-dashicons-grey mwp-wpvivid-handle-additional-database-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                </p>
            </div>
            <div class="mwp-wpvivid-additional-database-detail" style="display: none;">
                <div style="padding-left:2em;padding-right:1em;">
                    <div style="padding: 0px 1em 1em; border: 1px solid rgb(204, 204, 204);">
                        <div style="border-bottom:1px solid #ccc; margin-top: 10px; margin-bottom: 10px;">
                            <p style="margin-bottom: 10px;">
                                <span>Host: </span><span><input type="text" class="mwp-wpvivid-additional-database-host" style="width: 120px;"></span>
                                <span>User Name: </span><span><input type="text" class="mwp-wpvivid-additional-database-user" style="width: 120px;"></span>
                                <span>Password: </span><span><input type="password" class="mwp-wpvivid-additional-database-pass" style="width: 120px;"></span>
                                <span><input type="submit" value="Connect" class="button ui green mini button mwp-wpvivid-connect-additional-database" ></span>
                            </p>
                        </div>
                        <div style="width:50%;float:left;box-sizing:border-box;padding-right:0.5em;">
                            <div style="margin-top: 10px; margin-bottom: 10px;">
                                <p><span class="dashicons dashicons-excerpt-view wpvivid-dashicons-blue"></span>
                                    <span><strong>Databases</strong></span>
                                    <span>( click "<span class="dashicons dashicons-plus-alt wpvivid-icon-16px"></span>" icon to add the database to backup list )</span>
                                </p>
                            </div>
                            <div class="mwp-wpvivid-additional-database-add" style="height:100px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;"></div>
                            <div style="clear:both;"></div>
                        </div>
                        <div style="width:50%; float:left;box-sizing:border-box;padding-left:0.5em;">
                            <div style="margin-top: 10px; margin-bottom: 10px;">
                                <p>
                                    <span class="dashicons dashicons-list-view wpvivid-dashicons-orange"></span>
                                    <span><strong>Databases will be backed up</strong></span>
                                    <span>( click <span class="dashicons dashicons-trash wpvivid-icon-16px"></span> icon to exclude the database )</span>
                                </p>
                            </div>
                            <div class="mwp-wpvivid-additional-database-list" style="height:100px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                <?php
                                echo esc_html($additional_database_list);
                                ?>
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        <div style="clear:both;"></div>
        <?php
    }

    public function output_custom_backup_file_table()
    {
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
            $core_check = 'checked="checked"';
            $themes_check = 'checked="checked"';
            $plugins_check = 'checked="checked"';
            $uploads_check = 'checked="checked"';
            $content_check = 'checked="checked"';
            $additional_folder_check = '';
        }
        else
        {
            $core_check = 'checked="checked"';
            $themes_check = 'checked="checked"';
            $plugins_check = 'checked="checked"';
            $uploads_check = 'checked="checked"';
            $content_check = 'checked="checked"';
            $additional_folder_check = '';

            if($this->option === 'manual_backup' || $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup')
            {
                $type = 'manual_backup';
                $custom_backup_history = array();
                if(isset($_GET['id']))
                {
                    $custom_backup_history=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
                }

                //$custom_backup_history = array();//self::wpvivid_get_new_backup_history();
                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['custom_dirs']['core_check'])) {
                        if ($custom_backup_history['custom_dirs']['core_check'] != '1') {
                            $core_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['themes_check'])) {
                        if($custom_backup_history['custom_dirs']['themes_check'] != '1'){
                            $themes_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['plugins_check'])){
                        if ($custom_backup_history['custom_dirs']['plugins_check'] != '1') {
                            $plugins_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['uploads_check'])) {
                        if ($custom_backup_history['custom_dirs']['uploads_check'] != '1') {
                            $uploads_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['content_check'])) {
                        if ($custom_backup_history['custom_dirs']['content_check'] != '1') {
                            $content_check = '';
                        }
                    }
                    if(isset($custom_backup_history['custom_dirs']['other_check'])) {
                        if ($custom_backup_history['custom_dirs']['other_check'] == '1') {
                            $additional_folder_check = 'checked="checked"';
                        }
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_file_history = array();//self::get_incremental_option('files');
                if(isset($custom_incremental_file_history) && !empty($custom_incremental_file_history)) {
                    if(isset($custom_incremental_file_history['custom_dirs']['core_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['core_check'] != '1')
                        {
                            $core_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['themes_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['themes_check'] != '1')
                        {
                            $themes_check = '';
                        }
                    }
                    if(isset($custom_incremental_file_history['custom_dirs']['plugins_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['plugins_check'] != '1')
                        {
                            $plugins_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['uploads_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['uploads_check'] != '1')
                        {
                            $uploads_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['content_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['content_check'] != '1')
                        {
                            $content_check = '';
                        }
                    }

                    if(isset($custom_incremental_file_history['custom_dirs']['other_check']))
                    {
                        if ($custom_incremental_file_history['custom_dirs']['other_check'] == '1')
                        {
                            $additional_folder_check = 'checked';
                        }
                    }
                }
            }
        }

        if($core_check === '')
        {
            $core_style = 'display: none;';
        }
        else
        {
            $core_style = '';
        }

        if($content_check === '')
        {
            $content_style = 'display: none;';
        }
        else
        {
            $content_style = '';
        }

        if($themes_check === '')
        {
            $themes_style = 'display: none;';
        }
        else
        {
            $themes_style = '';
        }

        if($plugins_check === '')
        {
            $plugins_style = 'display: none;';
        }
        else
        {
            $plugins_style = '';
        }

        if($uploads_check === '')
        {
            $uploads_style = 'display: none;';
        }
        else
        {
            $uploads_style = '';
        }

        if($core_check === '' && $content_check === '' && $themes_check === '' && $plugins_check === '' && $uploads_check === '')
        {
            $file_style = 'display: none;';
        }
        else
        {
            $file_style = '';
        }

        if($type = 'manual_backup')
        {
            $key = 'general';
        }
        else
        {
            $key = 'incremental';
        }
        $website_size = get_option('wpvivid_custom_select_website_size_ex', array());
        $core_size=isset($website_size[$key]['core_size'])?$website_size[$key]['core_size']:0;
        $content_size=isset($website_size[$key]['content_size'])?$website_size[$key]['content_size']:0;
        $themes_size=isset($website_size[$key]['themes_size'])?$website_size[$key]['themes_size']:0;
        $plugins_size=isset($website_size[$key]['plugins_size'])?$website_size[$key]['plugins_size']:0;
        $uploads_size=isset($website_size[$key]['uploads_size'])?$website_size[$key]['uploads_size']:0;
        $file_size = size_format($core_size+$themes_size+$plugins_size+$uploads_size+$content_size, 2);
        $core_size = size_format($core_size, 2);
        $themes_size = size_format($themes_size, 2);
        $plugins_size = size_format($plugins_size, 2);
        $uploads_size = size_format($uploads_size, 2);
        $content_size = size_format($content_size, 2);
        if(isset($website_size[$key]) && !empty($website_size[$key]))
        {
        }
        else
        {
            $core_style = 'display: none;';
            $content_style = 'display: none;';
            $themes_style = 'display: none;';
            $plugins_style = 'display: none;';
            $uploads_style = 'display: none;';
            $file_style = 'display: none;';
        }

        ?>
        <div style="margin-top:1em;">
            <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
            <span><strong>Files & Folders </strong></span>
        </div>

        <div style="padding-left:2em;">
            <p style="margin: 1em 0;"><span><input type="checkbox" class="mwp-wpvivid-custom-core-check" <?php echo esc_attr($core_check); ?>><span><strong>Wordpress Core</strong></span></span></p>
            <p style="margin: 1em 0;"><span><input type="checkbox" class="mwp-wpvivid-custom-content-check" <?php echo esc_attr($content_check); ?>><span><strong>wp-content</strong></span></span></p>
            <p style="margin: 1em 0;"><span><input type="checkbox" class="mwp-wpvivid-custom-themes-check" <?php echo esc_attr($themes_check); ?>><span><strong>themes</strong></span></span></p>
            <p style="margin: 1em 0;"><span><input type="checkbox" class="mwp-wpvivid-custom-plugins-check" <?php echo esc_attr($plugins_check); ?>><span><strong>plugins</strong></span></span></p>
            <p style="margin: 1em 0;"><span><input type="checkbox" class="mwp-wpvivid-custom-uploads-check" <?php echo esc_attr($uploads_check); ?>><span><strong>uploads</strong></span></span></p>
            <?php
            if($this->is_global == '0')
            {
                ?>
                <p style="margin: 1em 0;">
                    <input type="checkbox" class="mwp-wpvivid-custom-additional-folder-check" <?php echo esc_attr($additional_folder_check); ?>>
                    <span class="mwp-wpvivid-handle-additional-folder-detail" style="cursor:pointer;"><strong><span style="color:green;">(optional)</span>Include Non-wordpress Files/Folders</strong></span>
                    <span class="dashicons wpvivid-dashicons-grey mwp-wpvivid-handle-additional-folder-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                </p>
                <?php
            }
            ?>
        </div>
        <div style="clear:both;"></div>

        <?php
        if($this->is_global == '0')
        {
            ?>
            <div class="mwp-wpvivid-additional-folder-detail" style="display: none;">
                <div style="padding-left:2em;padding-right:1em;">
                    <div style="padding: 0 1em 1em; border: 1px solid rgb(204, 204, 204);">
                        <div>
                            <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;">
                                <div style="margin-top: 10px; margin-bottom: 10px;">
                                    <p>
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span><strong>Folders</strong></span>
                                        <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue mwp-wpvivid-refresh-include-tree">Refresh<span>
                                    </p>
                                </div>

                                <div style="height:250px;">
                                    <div class="mwp-wpvivid-custom-additional-folder-tree-info" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow:auto;">Tree Viewer</div>
                                </div>
                                <div style="clear:both;"></div>

                                <div style="padding:1.5em 0 0 0;"><input class="ui green mini button mwp-wpvivid-include-additional-folder-btn" type="submit" value="Include Files/Folders"></div>
                            </div>
                            <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                                <div style="margin-top: 10px; margin-bottom: 10px;">
                                    <p>
                                        <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                        <span><strong>Non-WordPress Files/Folders Will Be Backed Up</strong></span>
                                    </p>
                                </div>
                                <div class="mwp-wpvivid-custom-include-additional-folder-list" style="height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                    <?php
                                    if($this->is_mu_single !== '1')
                                    {
                                        echo esc_html($this->mwp_wpvivid_get_include_list($type));
                                    }
                                    ?>
                                </div>
                                <div style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue mwp-wpvivid-clear-custom-include-list" style="float:right;">Empty Included Files/Folders</span></div>
                            </div>
                            <div style="clear:both;"></div>
                        </div>
                        <div style="clear:both;"></div>
                        <div style="padding:1em 0 0 0;">
                            <span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        ?>
        <div style="clear:both;"></div>
        <?php
    }

    public function output_advanced_option_table()
    {
        $exclude_file_type = '';
        if($this->is_mu_single === '1')
        {
            $type = 'manual_backup';
        }
        else
        {
            if($this->option === 'manual_backup' || $this->option === 'schedule_backup' || $this->option === 'update_schedule_backup')
            {
                $type = 'manual_backup';
                $custom_backup_history = array();
                if(isset($_GET['id']))
                {
                    $custom_backup_history=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
                }

                //$custom_backup_history = array();//self::wpvivid_get_new_backup_history();
                if(isset($custom_backup_history) && !empty($custom_backup_history))
                {
                    if(isset($custom_backup_history['exclude_file_type']))
                    {
                        $exclude_file_type = $custom_backup_history['exclude_file_type'];
                    }
                }
            }
            else
            {
                $type = 'incremental_backup';
                $custom_incremental_file_history = array();//self::get_incremental_file_settings();
            }
        }

        ?>
        <div>
            <p>
                <span class="dashicons dashicons-admin-generic wpvivid-dashicons-blue"></span>
                <span class="mwp-wpvivid-handle-advanced-option-detail" style="cursor:pointer;"><strong>Advanced Settings</strong></span>
                <span class="dashicons wpvivid-dashicons-grey mwp-wpvivid-handle-advanced-option-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
        </div>

        <p></p>

        <div class="mwp-wpvivid-advanced-option-detail" style="padding-left:2em; display: none;">
            <p>
                <span class="mwp-wpvivid-handle-tree-detail" style="cursor:pointer;"><strong>Exclude Files/Folders Inside /wp-content/ Folder</strong></span>
                <span class="dashicons wpvivid-dashicons-grey mwp-wpvivid-handle-tree-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
            </p>
            <?php
            if($this->is_global == '0')
            {
                ?>
                <div class="mwp-wpvivid-tree-detail" style="padding:0 1em 1em;border:1px solid #ccc; display: none;">
                    <div>
                        <div style="width:30%;float:left;box-sizing:border-box;padding-right:0.5em;">
                            <div style="margin-top: 10px; margin-bottom: 10px;">
                                <p>
                                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                    <span><strong>Folders</strong></span>
                                    <span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue mwp-wpvivid-refresh-exclude-tree">Refresh<span>
                                </p>
                            </div>

                            <div style="height:250px;">
                                <div class="mwp-wpvivid-custom-exclude-tree-info" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow:auto;">Tree Viewer</div>
                            </div>
                            <div style="clear:both;"></div>

                            <div style="padding:1.5em 0 0 0;"><input class="ui green mini button mwp-wpvivid-custom-tree-exclude-btn" type="submit" value="Exclude Files/Folders/File Types"></div>
                        </div>
                        <div style="width:70%; float:left;box-sizing:border-box;padding-left:0.5em;">
                            <div style="margin-top: 10px; margin-bottom: 10px;">
                                <p>
                                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                                    <span><strong>Excluded Files/Folders</strong></span>
                                </p>
                            </div>
                            <div class="mwp-wpvivid-custom-exclude-list" style="margin-top:10px;height:250px;border:1px solid #ccc;padding:0.2em 0.5em;overflow-y:auto;">
                                <?php
                                if($this->is_mu_single !== '1')
                                {
                                    echo esc_html($this->mwp_wpvivid_get_exclude_list($type));
                                }
                                ?>
                            </div>

                            <div style="padding:1em 0 0 0;"><span class="wpvivid-rectangle wpvivid-grey-light wpvivid-hover-blue mwp-wpvivid-clear-custom-exclude-list" style="float:right;">Empty Excluded Files/Folders</span></div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                    <div style="clear:both;"></div>
                    <div style="padding:1em 0 0 0;">
                        <span><code>CTRL</code> + <code>Left Click</code> to select multiple files or folders.</span>
                    </div>
                </div>
                <?php
            }
            else
            {
                ?>
                <div class="mwp-wpvivid-tree-detail" style="display: none;">
                    <textarea class="mwp-wpvivid-exclude-path" placeholder="Example:&#10;/wp-content/uploads/19/03/&#10;/wp-content/uploads/19/04/" style="width:100%; height:200px; overflow-x:auto;"><?php echo ''; ?></textarea>
                </div>
                <?php
            }
            ?>

            <p></p>

            <div>
                <p>
                    <span class="mwp-wpvivid-handle-exclude-file-type-detail" style="cursor:pointer;"><strong>Exclude File Types</strong></span>
                    <span class="dashicons wpvivid-dashicons-grey mwp-wpvivid-handle-exclude-file-type-detail dashicons-arrow-down-alt2" style="cursor:pointer;"></span>
                </p>
            </div>

            <p></p>

            <div class="mwp-wpvivid-exclude-file-type-detail" style="display: none;">
                <input class="mwp-wpvivid-custom-exclude-extension" style="width:100%; padding: 0.5em;border:1px solid #ccc;" value="<?php echo esc_attr($exclude_file_type); ?>" placeholder="Exclude file types, separate by comma - for example: gif, jpg, webp, pdf" />
            </div>
        </div>
        <?php
    }

    public function mwp_wpvivid_get_include_list($type)
    {
        $ret = '';
        $backup_history=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
        if($type === 'manual_backup')
        {
            $backup_history=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
        }
        else if($type === 'incremental_backup')
        {
            $backup_history = array();
        }

        if(!empty($backup_history))
        {
            if(isset($backup_history['custom_dirs']['other_list']) && !empty($backup_history['custom_dirs']['other_list']))
            {
                $include_folders = $backup_history['custom_dirs']['other_list'];
                foreach ($include_folders as $index => $value)
                {
                    $type = 'folder';
                    $path = $value;
                    $class_type = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    $ret .= '<div class="wpvivid-text-line" type="'.$type.'">
                            <span class="dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree"></span><span class="'.$class_type.'"></span><span class="wpvivid-text-line">'.$path.'</span>
                         </div>';
                }
            }
        }
        return $ret;
    }

    public function mwp_wpvivid_get_exclude_list($type)
    {
        $ret = '';
        $exclude_path=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
        //$exclude_path = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
        if($type === 'manual_backup')
        {
            $exclude_path=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'backup_custom_setting_ex', array());
            //$exclude_path = WPvivid_Custom_Backup_Manager::wpvivid_get_new_backup_history();
        }
        else if($type === 'incremental_backup')
        {
            $exclude_path = array();
            //$exclude_path = WPvivid_Custom_Backup_Manager::get_incremental_file_settings();
        }

        if(!empty($exclude_path))
        {
            if(isset($exclude_path['exclude_files']) && !empty($exclude_path['exclude_files']))
            {
                $exclude_files = $exclude_path['exclude_files'];
                foreach ($exclude_files as $index => $value)
                {
                    if($value['type'] === 'folder')
                    {
                        $class_type = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    }
                    else if($value['type'] === 'file')
                    {
                        $class_type = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }
                    else
                    {
                        $class_type = 'dashicons dashicons-media-code wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                    }
                    $ret .= '<div class="wpvivid-text-line" type="'.$value['type'].'">
                            <span class="dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree"></span><span class="'.$class_type.'"></span><span class="wpvivid-text-line">'.$value['path'].'</span>
                         </div>';
                }
            }
        }
        return $ret;
    }

    public function load_js()
    {
        ?>
        <script>
            function mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, parent_id)
            {
                if(obj.is(":hidden")) {
                    handle_obj.each(function(){
                        if(jQuery(this).hasClass('dashicons-arrow-down-alt2')){
                            jQuery(this).removeClass('dashicons-arrow-down-alt2');
                            jQuery(this).addClass('dashicons-arrow-up-alt2');
                        }
                    });
                    obj.show();
                }
                else{
                    handle_obj.each(function(){
                        if(jQuery(this).hasClass('dashicons-arrow-up-alt2')){
                            jQuery(this).removeClass('dashicons-arrow-up-alt2');
                            jQuery(this).addClass('dashicons-arrow-down-alt2');
                        }
                    });
                    obj.hide();
                }
            }

            function mwp_wpvivid_init_custom_include_tree(parent_id, is_mu_single, refresh=0)
            {
                if (refresh) {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-folder-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-folder-tree-info').on('activate_node.jstree', function (e, data) {
                    }).jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id
                                };
                                tree_node = JSON.stringify(tree_node);
                                var ajax_data = {
                                    'action': 'mwp_wpvivid_get_custom_tree_data_ex',
                                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=ajax_object.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: ajax_object.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        if(jsonarray.result === 'success') {
                                            callback.call(this, jsonarray.content_tree_data);
                                        }
                                        else{
                                            alert(jsonarray.error);
                                        }
                                        jQuery('#'+parent_id).find('.mwp-wpvivid-include-additional-folder-btn').attr('disabled', false);
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        },
                        "plugins": ["sort"],
                        "sort": function(a, b) {
                            a1 = this.get_node(a);
                            b1 = this.get_node(b);
                            if (a1.icon === b1.icon) {
                                return (a1.text.toLowerCase() > b1.text.toLowerCase()) ? 1 : -1;
                            } else {
                                return (a1.icon > b1.icon) ? 1 : -1;
                            }
                        }
                    });
                }
            }

            function mwp_wpvivid_check_custom_tree_repeat(tree_type, value, parent_id)
            {
                if(tree_type === 'additional-folder'){
                    var list = 'mwp-wpvivid-custom-include-additional-folder-list';
                }
                else if(tree_type === 'exclude-folder'){
                    var list = 'mwp-wpvivid-custom-exclude-list';
                }

                var brepeat = false;
                jQuery('#'+parent_id).find('.'+list+' div').find('span:eq(2)').each(function (){
                    if (value === this.innerHTML) {
                        brepeat = true;
                    }
                });
                return brepeat;
            }

            function mwp_wpvivid_get_filter_database_list(table_type, text, option, parent_id)
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_database_by_filter',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'table_type': table_type,
                    'filter_text': text,
                    'option_type': option
                };

                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(table_type === 'base_table')
                            {
                                jQuery('#'+parent_id).find('.mwp-wpvivid-database-base-list').html(jsonarray.database_html);
                            }
                            else if(table_type === 'other_table')
                            {
                                jQuery('#'+parent_id).find('.mwp-wpvivid-database-other-list').html(jsonarray.database_html);
                            }
                            else if(table_type === 'diff_prefix_table')
                            {
                                jQuery('#'+parent_id).find('.mwp-wpvivid-database-diff-prefix-list').html(jsonarray.database_html);
                            }
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('get list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-handle-base-database-detail', function()
            {
                var handle_obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-handle-base-database-detail');
                var obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-base-database-detail');
                mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo esc_js($this->parent_id); ?>');
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-handle-additional-database-detail', function()
            {
                var handle_obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-handle-additional-database-detail');
                var obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-detail');
                mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo esc_js($this->parent_id); ?>');
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-handle-additional-folder-detail', function()
            {
                var handle_obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-handle-additional-folder-detail');
                var obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-folder-detail');
                mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo esc_js($this->parent_id); ?>');
                mwp_wpvivid_init_custom_include_tree('<?php echo esc_js($this->parent_id); ?>', '<?php echo esc_js($this->is_mu_single); ?>');
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on("click", '.mwp-wpvivid-refresh-include-tree', function()
            {
                mwp_wpvivid_init_custom_include_tree('<?php echo esc_js($this->parent_id); ?>', '<?php echo esc_js($this->is_mu_single); ?>', 1);
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-include-additional-folder-btn', function()
            {
                var select_folders = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-custom-additional-folder-tree-info').jstree(true).get_selected(true);
                var tree_path = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-custom-additional-folder-tree-info').find('.jstree-anchor:first').attr('id');
                tree_path = tree_path.replace('_anchor', '');
                var list_obj = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-custom-include-additional-folder-list');
                var tree_type = 'additional-folder';

                jQuery.each(select_folders, function (index, select_item) {
                    if (select_item.id !== tree_path) {
                        var value = select_item.id;
                        value = value.replace(tree_path, '');
                        if (!mwp_wpvivid_check_custom_tree_repeat(tree_type, value, '<?php echo esc_js($this->parent_id); ?>')) {
                            var class_name = select_item.icon;
                            if(class_name === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                                var type = 'folder';
                            }
                            else{
                                var type = 'file';
                            }
                            var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                                "<span class='dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree'></span>" +
                                "<span class='"+class_name+"'></span>" +
                                "<span class='wpvivid-text-line'>" + value + "</span>" +
                                "</div>";
                            list_obj.append(tr);
                        }
                    }
                });
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-remove-custom-exlcude-tree', function()
            {
                jQuery(this).parent().remove();
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-clear-custom-include-list', function()
            {
                jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-custom-include-additional-folder-list').html('');
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-connect-additional-database', function()
            {
                var db_user = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-user').val();
                var db_pass = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-pass').val();
                var db_host = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-host').val();
                if(db_user !== ''){
                    if(db_host !== ''){
                        var db_json = {};
                        db_json['db_user'] = db_user;
                        db_json['db_pass'] = db_pass;
                        db_json['db_host'] = db_host;
                        var db_connect_info = JSON.stringify(db_json);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_connect_additional_database_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'database_info': db_connect_info
                        };
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-connect-additional-database').css({'pointer-events': 'none', 'opacity': '0.4'});
                        mwp_wpvivid_post_request(ajax_data, function (data) {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray !== null) {
                                if (jsonarray.result === 'success') {
                                    jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                    jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-add').html(jsonarray.html);
                                }
                                else {
                                    jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                    alert(jsonarray.error);
                                }
                            }
                            else {
                                jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                alert('Login Failed. Please check the credentials you entered and try again.');
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-connect-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Host is required.');
                    }
                }
                else{
                    alert('User Name is required.');
                }
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-add-additional-db', function()
            {
                var db_name = jQuery(this).attr('name');
                var db_user = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-user').val();
                var db_pass = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-pass').val();
                var db_host = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-host').val();
                if(db_user !== ''){
                    if(db_host !== ''){
                        var db_json = {};
                        db_json['db_user'] = db_user;
                        db_json['db_pass'] = db_pass;
                        db_json['db_host'] = db_host;
                        db_json['additional_database_list'] = Array();
                        db_json['additional_database_list'].push(db_name);

                        var database_info = JSON.stringify(db_json);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_add_additional_database_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'database_info': database_info
                        };
                        mwp_wpvivid_post_request(ajax_data, function (data) {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result == 'success') {
                                jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-list').html('');
                                jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-list').append(jsonarray.html);
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                    else{
                        alert('Host is required.');
                    }
                }
                else{
                    alert('User Name is required.');
                }
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-additional-database-remove', function()
            {
                var database_name = jQuery(this).attr('database-name');
                var ajax_data = {
                    'action': 'mwp_wpvivid_remove_additional_database_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'database_name': database_name
                };
                jQuery(this).css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function(data){
                    jQuery(this).css({'pointer-events': 'auto', 'opacity': '1'});
                    var jsonarray = jQuery.parseJSON(data);
                    if(jsonarray.result == 'success'){
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-list').html('');
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-additional-database-list').append(jsonarray.html);
                    }
                    else{
                        alert(jsonarray.error);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(this).css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('retrieving the last backup log', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-select-base-table-button', function()
            {
                var text = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-select-base-table-text').val();
                mwp_wpvivid_get_filter_database_list('base_table', text, '<?php echo esc_js($this->option); ?>', '<?php echo esc_js($this->parent_id); ?>');
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-select-other-table-button', function()
            {
                var text = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-select-other-table-text').val();
                mwp_wpvivid_get_filter_database_list('other_table', text, '<?php echo esc_js($this->option); ?>', '<?php echo esc_js($this->parent_id); ?>');
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-select-diff-prefix-table-button', function()
            {
                var text = jQuery('#<?php echo esc_js($this->parent_id); ?>').find('.mwp-wpvivid-select-diff-prefix-table-text').val();
                mwp_wpvivid_get_filter_database_list('diff_prefix_table', text, '<?php echo esc_js($this->option); ?>', '<?php echo esc_js($this->parent_id); ?>');
            });
            
            //
            function mwp_wpvivid_init_custom_exclude_tree(parent_id, is_mu_single, refresh=0)
            {
                if (refresh) {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-exclude-tree-info').jstree("refresh");
                }
                else {
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-exclude-tree-info').on('activate_node.jstree', function (e, data) {
                    }).jstree({
                        "core": {
                            "check_callback": true,
                            "multiple": true,
                            "data": function (node_id, callback) {
                                var tree_node = {
                                    'node': node_id
                                };
                                tree_node = JSON.stringify(tree_node);
                                var ajax_data = {
                                    'action': 'mwp_wpvivid_get_content_tree_data_ex',
                                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                                    'tree_node': tree_node
                                };
                                ajax_data.nonce=ajax_object.ajax_nonce;
                                jQuery.ajax({
                                    type: "post",
                                    url: ajax_object.ajax_url,
                                    data: ajax_data,
                                    success: function (data) {
                                        var jsonarray = jQuery.parseJSON(data);
                                        if(jsonarray.result === 'success') {
                                            callback.call(this, jsonarray.content_tree_data);
                                        }
                                        else{
                                            alert(jsonarray.error);
                                        }
                                        jQuery('#'+parent_id).find('.mwp-wpvivid-include-additional-folder-btn').attr('disabled', false);
                                    },
                                    error: function (XMLHttpRequest, textStatus, errorThrown) {
                                        alert("error");
                                    },
                                    timeout: 30000
                                });
                            },
                            'themes': {
                                'stripes': true
                            }
                        },
                        "plugins": ["sort"],
                        "sort": function(a, b) {
                            a1 = this.get_node(a);
                            b1 = this.get_node(b);
                            if (a1.icon === b1.icon) {
                                return (a1.text.toLowerCase() > b1.text.toLowerCase()) ? 1 : -1;
                            } else {
                                return (a1.icon > b1.icon) ? 1 : -1;
                            }
                        }
                    });
                }
            }

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on('click', '.mwp-wpvivid-handle-advanced-option-detail', function()
            {
                var handle_obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-handle-advanced-option-detail');
                var obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-advanced-option-detail');
                mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo esc_js($this->advanced_id); ?>');
                mwp_wpvivid_init_custom_exclude_tree('<?php echo esc_js($this->advanced_id); ?>', '<?php echo esc_js($this->is_mu_single); ?>');
                var showContent = jQuery('.mwp-wpvivid-custom-exclude-list');
                showContent[0].scrollTop = showContent[0].scrollHeight;
            });

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on("click", '.mwp-wpvivid-refresh-exclude-tree', function()
            {
                mwp_wpvivid_init_custom_exclude_tree('<?php echo esc_js($this->advanced_id); ?>', '<?php echo esc_js($this->is_mu_single); ?>', 1);
            });

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on('click', '.mwp-wpvivid-handle-tree-detail', function()
            {
                var handle_obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-handle-tree-detail');
                var obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-tree-detail');
                mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo esc_js($this->advanced_id); ?>');
            });

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on('click', '.mwp-wpvivid-handle-exclude-file-type-detail', function()
            {
                var handle_obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-handle-exclude-file-type-detail');
                var obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-exclude-file-type-detail');
                mwp_wpvivid_handle_custom_open_close_ex(handle_obj, obj, '<?php echo esc_js($this->advanced_id); ?>');
            });

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on('click', '.mwp-wpvivid-custom-tree-exclude-btn', function()
            {
                var select_folders = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-custom-exclude-tree-info').jstree(true).get_selected(true);
                var tree_type = 'exclude-folder';
                var list_obj = jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.mwp-wpvivid-custom-exclude-list');
                jQuery.each(select_folders, function (index, select_item) {
                    var value = select_item.id;
                    if (!mwp_wpvivid_check_custom_tree_repeat(tree_type, value, '<?php echo esc_js($this->advanced_id); ?>')) {
                        var class_name = select_item.icon;
                        if(class_name === 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer'){
                            var type = 'folder';
                        }
                        else{
                            var type = 'file';
                        }
                        var tr = "<div class='wpvivid-text-line' type='"+type+"'>" +
                            "<span class='dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree'></span>" +
                            "<span class='"+class_name+"'></span>" +
                            "<span class='wpvivid-text-line'>" + value + "</span>" +
                            "</div>";
                        list_obj.append(tr);
                        var showContent = jQuery('.mwp-wpvivid-custom-exclude-list');
                        showContent[0].scrollTop = showContent[0].scrollHeight;
                    }
                });
            });

            jQuery('#<?php echo esc_js($this->parent_id); ?>').on('click', '.mwp-wpvivid-database-table-check', function()
            {
                if(jQuery(this).prop('checked'))
                {
                    if(jQuery(this).hasClass('mwp-wpvivid-database-base-table-check'))
                    {
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('input:checkbox[option=mwp_base_db][name=Database]').prop('checked', true);
                    }
                    else if(jQuery(this).hasClass('mwp-wpvivid-database-other-table-check'))
                    {
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('input:checkbox[option=mwp_other_db][name=Database]').prop('checked', true);
                    }
                }
                else
                {
                    if (jQuery(this).hasClass('mwp-wpvivid-database-base-table-check'))
                    {
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('input:checkbox[option=mwp_base_db][name=Database]').prop('checked', false);
                    }
                    else if (jQuery(this).hasClass('mwp-wpvivid-database-other-table-check'))
                    {
                        jQuery('#<?php echo esc_js($this->parent_id); ?>').find('input:checkbox[option=mwp_other_db][name=Database]').prop('checked', false);
                    }
                }
            });

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on('click', '.mwp-wpvivid-remove-custom-exlcude-tree', function()
            {
                jQuery(this).parent().remove();
            });

            jQuery('#<?php echo esc_js($this->advanced_id); ?>').on('click', '.mwp-wpvivid-clear-custom-exclude-list', function()
            {
                var list = 'mwp-wpvivid-custom-exclude-list';
                jQuery('#<?php echo esc_js($this->advanced_id); ?>').find('.'+list).html('');
            });

            //
            function mwp_wpvivid_get_custom_setting_json_ex(parent_id)
            {
                var json = {};
                //core
                json['core_check'] = '0';
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-core-check').prop('checked')){
                    json['core_check'] = '1';
                }

                //themes
                json['themes_check'] = '0';
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-themes-check').prop('checked')){
                    json['themes_check'] = '1';
                }

                //plugins
                json['plugins_check'] = '0';
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-plugins-check').prop('checked')){
                    json['plugins_check'] = '1';
                }

                //content
                json['content_check'] = '0';
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-content-check').prop('checked')){
                    json['content_check'] = '1';
                }

                //uploads
                json['uploads_check'] = '0';
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-uploads-check').prop('checked')){
                    json['uploads_check'] = '1';
                }

                //additional folders/files
                json['other_check'] = '0';
                json['other_list'] = [];
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-folder-check').prop('checked')){
                    json['other_check'] = '1';
                }
                if(json['other_check'] == '1'){
                    jQuery('#'+parent_id).find('.mwp-wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                        var folder_name = this.innerHTML;
                        json['other_list'].push(folder_name);
                    });
                }

                //database
                json['database_check'] = '0';
                json['exclude-tables'] = Array();
                json['include-tables'] = Array();
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-database-check').prop('checked')){
                    json['database_check'] = '1';
                }
                jQuery('#'+parent_id).find('input[option=mwp_base_db][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['exclude-tables'].push(jQuery(value).val());
                    }
                });
                jQuery('#'+parent_id).find('input[option=mwp_other_db][type=checkbox]').each(function(index, value){
                    if(!jQuery(value).prop('checked')){
                        json['exclude-tables'].push(jQuery(value).val());
                    }
                });
                jQuery('#'+parent_id).find('input[option=mwp_diff_prefix_db][type=checkbox]').each(function(index, value){
                    if(jQuery(value).prop('checked')){
                        json['include-tables'].push(jQuery(value).val());
                    }
                });

                //additional database
                json['additional_database_check'] = '0';
                json['additional_database_list'] = {};
                if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-database-check').prop('checked')){
                    json['additional_database_check'] = '1';
                }
                jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-list').find('div').each(function(index, value){
                    var database_name = jQuery(this).attr('database-name');
                    var database_host = jQuery(this).attr('database-host');
                    var database_user = jQuery(this).attr('database-user');
                    var database_pass = jQuery(this).attr('database-pass');
                    json['additional_database_list'][database_name] = {};
                    json['additional_database_list'][database_name]['db_host'] = database_host;
                    json['additional_database_list'][database_name]['db_user'] = database_user;
                    json['additional_database_list'][database_name]['db_pass'] = database_pass;
                });

                return json;
            }

            function mwp_wpvivid_create_incremental_json_ex(parent_id, incremental_type)
            {
                var json = {};
                if(incremental_type === 'files'){
                    //core
                    json['core_check'] = '0';
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-core-check').prop('checked')){
                        json['core_check'] = '1';
                    }

                    //themes
                    json['themes_check'] = '0';
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-themes-check').prop('checked')){
                        json['themes_check'] = '1';
                    }

                    //plugins
                    json['plugins_check'] = '0';
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-plugins-check').prop('checked')){
                        json['plugins_check'] = '1';
                    }

                    //content
                    json['content_check'] = '0';
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-content-check').prop('checked')){
                        json['content_check'] = '1';
                    }

                    //uploads
                    json['uploads_check'] = '0';
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-uploads-check').prop('checked')){
                        json['uploads_check'] = '1';
                    }

                    //additional folders/files
                    json['other_check'] = '0';
                    json['other_list'] = [];
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-folder-check').prop('checked')){
                        json['other_check'] = '1';
                    }
                    if(json['other_check'] == '1'){
                        jQuery('#'+parent_id).find('.mwp-wpvivid-custom-include-additional-folder-list div').find('span:eq(2)').each(function (){
                            var folder_name = this.innerHTML;
                            json['other_list'].push(folder_name);
                        });
                    }
                }
                else if(incremental_type === 'database'){
                    //database
                    json['database_check'] = '0';
                    json['exclude-tables'] = Array();
                    json['include-tables'] = Array();
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-database-check').prop('checked')){
                        json['database_check'] = '1';
                    }
                    jQuery('#'+parent_id).find('input[option=mwp_base_db][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['exclude-tables'].push(jQuery(value).val());
                        }
                    });
                    jQuery('#'+parent_id).find('input[option=mwp_other_db][type=checkbox]').each(function(index, value){
                        if(!jQuery(value).prop('checked')){
                            json['exclude-tables'].push(jQuery(value).val());
                        }
                    });
                    jQuery('#'+parent_id).find('input[option=mwp_diff_prefix_db][type=checkbox]').each(function(index, value){
                        if(jQuery(value).prop('checked')){
                            json['include-tables'].push(jQuery(value).val());
                        }
                    });

                    //additional database
                    json['additional_database_check'] = '0';
                    json['additional_database_list'] = {};
                    if(jQuery('#'+parent_id).find('.mwp-wpvivid-custom-additional-database-check').prop('checked')){
                        json['additional_database_check'] = '1';
                    }
                    jQuery('#'+parent_id).find('.mwp-wpvivid-additional-database-list').find('div').each(function(index, value){
                        var database_name = jQuery(this).attr('database-name');
                        var database_host = jQuery(this).attr('database-host');
                        var database_user = jQuery(this).attr('database-user');
                        var database_pass = jQuery(this).attr('database-pass');
                        json['additional_database_list'][database_name] = {};
                        json['additional_database_list'][database_name]['db_host'] = database_host;
                        json['additional_database_list'][database_name]['db_user'] = database_user;
                        json['additional_database_list'][database_name]['db_pass'] = database_pass;
                    });
                }
                return json;
            }

            function mwp_wpvivid_get_exclude_json(advanced_id)
            {
                var json = [];
                jQuery('#'+advanced_id).find('.mwp-wpvivid-custom-exclude-list div').find('span:eq(2)').each(function ()
                {
                    var item={};
                    item['path']=this.innerHTML;
                    var type = jQuery(this).closest('div').attr('type');
                    item['type']=type;
                    json.push(item);
                });
                return json;
            }

            function mwp_wpvivid_get_global_exclude_json(advanced_id)
            {
                var exclude_path = jQuery('#'+advanced_id).find('.mwp-wpvivid-exclude-path').val();
                return exclude_path;
            }

            function mwp_wpvivid_get_exclude_file_type(advanced_id)
            {
                var exclude_file_type = jQuery('#'+advanced_id).find('.mwp-wpvivid-custom-exclude-extension').val();
                return exclude_file_type;
            }
        </script>
        <?php
    }
}