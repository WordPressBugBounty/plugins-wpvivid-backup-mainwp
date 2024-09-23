<?php

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Mainwp_WPvivid_Files_List extends WP_List_Table
{
    public $page_num;
    public $file_list;
    public $backup_id;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'files',
                'screen' => 'files'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox"/>';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name )
        {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) )
            {
                $class[] = 'hidden';
            }

            if ( $column_key === $primary )
            {
                $class[] = 'column-primary';
            }

            if ( $column_key === 'cb' )
            {
                $class[] = 'check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['wpvivid_file'] = __( 'File', 'wpvivid' );
        return $columns;
    }

    public function _column_wpvivid_file( $file )
    {
        echo '<td class="tablelistcolumn">
                    <div style="padding:0 0 10px 0;">
                        <span>'. esc_html($file['key']).'</span>
                    </div>
                    <div class="mwp-wpvivid-download-status" style="padding:0;">';
        if($file['status']=='completed')
        {
            echo '<span>'.esc_html(__('File Size: ', 'wpvivid')).'</span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-download-file-size">'.esc_html($file['size']).'</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-ready-download"><a style="cursor: pointer;">Download</a></span>';
        }
        else if($file['status']=='file_not_found')
        {
            echo '<span>' .esc_html(__('File not found', 'wpvivid')) . '</span>';
        }
        else if($file['status']=='need_download')
        {
            echo '<span>'.esc_html(__('File Size: ', 'wpvivid')).'</span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-download-file-size">'.esc_html($file['size']).'</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space"><a class="mwp-wpvivid-prepare-download" style="cursor: pointer;">Prepare to Download</a></span>';
        }
        else if($file['status']=='running')
        {
            echo '<div class="mwp-wpvivid-block-bottom-space">
                        <span class="mwp-wpvivid-block-right-space">Retriving (remote storage to web server)</span><span class="mwp-wpvivid-block-right-space">|</span><span>File Size: </span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-download-file-size">'.esc_html($file['size']).'</span><span class="mwp-wpvivid-block-right-space">|</span><span>Downloaded Size: </span><span>'.esc_html($file['downloaded_size']).'</span>
                    </div>
                    <div style="width:100%;height:10px; background-color:#dcdcdc;">
                        <div style="background-color:#0085ba; float:left;width:'.esc_attr($file['progress_text']).'%;height:10px;"></div>
                    </div>';
        }
        else if($file['status']=='timeout')
        {
            echo '<div class="mwp-wpvivid-block-bottom-space">
                        <span>Download timeout, please retry.</span>
                    </div>
                    <div>
                        <span>'.esc_html(__('File Size: ', 'wpvivid')).'</span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-download-file-size">'.esc_html($file['size']).'</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space"><a class="mwp-wpvivid-prepare-download" style="cursor: pointer;">Prepare to Download</a></span>
                    </div>';
        }
        else if($file['status']=='error')
        {
            echo '<div class="mwp-wpvivid-block-bottom-space">
                        <span>'.esc_html($file['error']).'</span>
                    </div>
                    <div>
                        <span>'.esc_html(__('File Size: ', 'wpvivid')).'</span><span class="mwp-wpvivid-block-right-space mwp-wpvivid-download-file-size">'.esc_html($file['size']).'</span><span class="mwp-wpvivid-block-right-space">|</span><span class="mwp-wpvivid-block-right-space"><a class="mwp-wpvivid-prepare-download" style="cursor: pointer;">Prepare to Download</a></span>
                    </div>';
        }

        echo '</div></td>';

        //size
    }

    public function set_files_list($file_list,$backup_id,$page_num=1)
    {
        $this->file_list=$file_list;
        $this->backup_id=$backup_id;
        $this->page_num=$page_num;
    }

    public function get_pagenum()
    {
        if($this->page_num=='first')
        {
            $this->page_num=1;
        }
        else if($this->page_num=='last')
        {
            $this->page_num=$this->_pagination_args['total_pages'];
        }
        $pagenum = $this->page_num ? $this->page_num : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
        {
            $pagenum = $this->_pagination_args['total_pages'];
        }

        return max( 1, $pagenum );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $total_items =sizeof($this->file_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->file_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->file_list);
    }

    private function _display_rows($file_list)
    {
        $page=$this->get_pagenum();

        $page_file_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_file_list = array_splice( $file_list, 0, 10);
            $count++;
        }
        foreach ( $page_file_list as $key=>$file)
        {
            $file['key']=$key;
            $this->single_row($file);
        }
    }

    public function single_row($file)
    {
        ?>
        <tr slug="<?php echo esc_attr($file['key']); ?>">
            <?php $this->single_row_columns( $file ); ?>
        </tr>
        <?php
    }

    protected function pagination( $which )
    {
        if ( empty( $this->_pagination_args ) )
        {
            return;
        }

        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;
        if ( isset( $this->_pagination_args['infinite_scroll'] ) )
        {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ( 'top' === $which && $total_pages > 1 )
        {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }
        /* translators: %s: Number of comments. */
        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current              = $this->get_pagenum();

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='first-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='prev-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-filelist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-filelist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        /* translators: 1: Current page, 2: Total pages. */
        $page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='next-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='last-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    protected function display_tablenav( $which ) {
        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'margin: 0 0 10px 0';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display()
    {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo esc_attr(implode( ' ', $this->get_table_classes() )); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo esc_attr(" data-wp-lists='list:$singular'");
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }
}

class Mainwp_WPvivid_New_Backup_List extends WP_List_Table
{
    public $page_num;
    public $backup_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'backup',
                'screen' => 'backup'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox"/>';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name )
        {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) )
            {
                $class[] = 'hidden';
            }

            if ( $column_key === $primary )
            {
                $class[] = 'column-primary';
            }

            if ( $column_key === 'cb' )
            {
                $class[] = 'wpvivid-check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_date2'] = __( 'Date', 'wpvivid' );
        $columns['wpvivid_content2'] =__( 'Content', 'wpvivid'  );
        $columns['wpvivid_storage'] =__( 'Storage', 'wpvivid'  );
        $columns['wpvivid_size2'] =__( 'Size', 'wpvivid'  );
        $columns['wpvivid_action2'] = __( 'Actions', 'wpvivid' );
        $columns['wpvivid_comment2'] = __( 'Comment', 'wpvivid'  );
        return $columns;
    }

    public function column_cb( $backup )
    {
        echo '<input type="checkbox"/>';
    }

    public function _column_wpvivid_date2( $backup )
    {
        $type_display = $backup['type'];

        if (empty($backup['lock']))
        {
            $lock_class = 'dashicons-unlock';
        }
        else
        {
            if ($backup['lock'] == 0)
            {
                $lock_class = 'dashicons-unlock';
            }
            else
            {
                $lock_class = 'dashicons-lock';
            }
        }

        $backups_lock=get_option('wpvivid_remote_backups_lock');
        if(isset($backups_lock[$backup['id']]))
        {
            $lock_class = 'dashicons-lock';
        }

        //$offset=get_option('gmt_offset');
        //$localtime = $backup['create_time'] + $offset * 60 * 60;
        $localtime = $backup['create_time'];

        $offset=get_option('gmt_offset');
        $utc_time = $backup['create_time'] - $offset * 60 * 60;

        if(isset($backup['log']))
        {
            $log_name = basename($backup['log']);
        }
        else
        {
            $log_name = '';
        }

        echo '<td class="tablelistcolumn">
                    <div class="backuptime"  title="">
						<span class="dashicons '.esc_attr($lock_class).' wpvivid-dashicons-blue mwp-wpvivid-lock" style="cursor:pointer;"></span>																
						<span>'.esc_html(date('M-d-Y H:i', $localtime)).'</span>
						<span style="margin:0 0 0 5px; opacity: 0.5;">|</span>
						<span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-grey" style="cursor:pointer;" title="Backup Log" onclick="mwp_wpvivid_backup_open_log(\''.esc_js($log_name).'\', \'all_list\');"></span>
						<span style="margin:0 5px 0 0; opacity: 0.5;">|</span> <span><strong>Type: </strong></span>
						<span><strong>' . esc_html($type_display) . '</strong></span>
					</div>	                 
                </td>';
    }

    public function _column_wpvivid_content2( $backup )
    {
        $content=$this->get_backup_content_ex($backup);

        echo '<td>';
        echo '    <div>';
        echo '         <span>';

        if( $content['files'])
        {
            echo '                <span class="dashicons dashicons-open-folder wpvivid-dashicons-orange" title="Backup Wordpress Files"></span>';
        }
        else if( $content['custom'])
        {
            echo '                <span class="dashicons dashicons-open-folder wpvivid-dashicons-orange" title="Backup Wordpress Files"></span>';
        }
        else
        {
            echo "                <span class='dashicons dashicons-open-folder wpvivid-dashicons-grey' title='Backup Wordpress Files'></span>";
        }

        if( $content['db'])
        {
            echo "                <span class='dashicons dashicons-database wpvivid-dashicons-blue' title='Backup Wordpresss DB'></span>";
        }
        else
        {
            echo '                <span class="dashicons dashicons-database wpvivid-dashicons-grey" title="Backup Wordpresss DB"></span>';
        }
        echo '         </span>';
        echo '    </div>';
        echo ' </td>';
    }

    public function _column_wpvivid_storage( $backup )
    {
        echo "<td>";
        if($this->is_localhost($backup))
        {
            echo "<span><span class='dashicons dashicons-desktop'></span><span>Localhost</span></span>";
        }
        else
        {
            $this->get_storage_icon($backup);
        }
        echo "</td>";
    }

    public function _column_wpvivid_size2( $backup )
    {
        $size=0;
        foreach ($backup['backup']['files'] as $file)
        {
            $size+=$file['size'];
        }
        $size=size_format($size,2);
        echo '<td class="tablelistcolumn">
                    <div>'.esc_html($size).'</div>
                </td>';

    }

    public function _column_wpvivid_action2( $backup )
    {
        if($this->is_localhost($backup))
        {
            $can_download=apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-download-localhost-backup');
        }
        else
        {
            $can_download=apply_filters('wpvivid_current_user_can',true,'wpvivid-can-mange-download-remote-backup');
        }

        if($can_download )
        {
            echo '<td class="tablelistcolumn" style="min-width:100px;">
                    <div class="wpvivid-download">
                        <div style="cursor:pointer;">
                            <span class="dashicons dashicons-visibility wpvivid-dashicons-grey mwp-wpvivid-view-backup mwp-wpvivid-download" title="View details"></span>
							<span class="dashicons dashicons-download wpvivid-dashicons-grey mwp-wpvivid-view-backup mwp-wpvivid-download" title="Prepare to download the backup"></span>
							<span class="dashicons dashicons-update-alt wpvivid-dashicons-grey mwp-wpvivid-restore" title="Restore"></span>
							<span class="dashicons dashicons-trash wpvivid-dashicons-grey mwp-backuplist-delete-backup" title="Delete"></span>
                        </div>
                    </div>
                </td>';
        }
        else
        {
            echo '<td class="tablelistcolumn" style="min-width:100px;">
                    <div class="wpvivid-download">
                        <div style="cursor:pointer;">                           
							<span class="dashicons dashicons-trash wpvivid-dashicons-grey backuplist-delete-backup" title="Delete"></span>
                        </div>
                    </div>
                </td>';
        }
    }

    public function _column_wpvivid_comment2( $backup )
    {
        if(isset($backup['backup_prefix']) && !empty($backup['backup_prefix']))
        {
            $backup_prefix = $backup['backup_prefix'];
        }
        else{
            $backup_prefix = 'N/A';
        }
        echo '<td class="tablelistcolumn">
                    <div>'.esc_html($backup_prefix).'</div>
                </td>';
    }

    public function set_backup_list($backup_list,$page_num=1)
    {
        $this->backup_list=$backup_list;
        $this->page_num=$page_num;
    }

    public function get_pagenum()
    {
        if($this->page_num=='first')
        {
            $this->page_num=1;
        }
        else if($this->page_num=='last')
        {
            $this->page_num=$this->_pagination_args['total_pages'];
        }
        $pagenum = $this->page_num ? $this->page_num : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
        {
            $pagenum = $this->_pagination_args['total_pages'];
        }

        return max( 1, $pagenum );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        if(empty($this->backup_list))
        {
            $total_items=0;
        }
        else
        {
            $total_items =sizeof($this->backup_list);
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 30,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->backup_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->backup_list);
    }

    public function get_backup_content_ex($backup)
    {
        $content['db']=$backup['has_db'];
        $content['files']=$backup['has_files'];
        $content['custom']=$backup['has_custom'];

        return $content;
    }

    public function is_localhost($backup)
    {
        if(empty($backup['remote']))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function get_storage_icon($backup)
    {

        if(sizeof($backup['remote'])>1)
        {
            $assets_url=MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images';
            echo "<span>";
            foreach ($backup['remote'] as $remote)
            {
                if($remote['type']=='amazons3')
                {
                    echo "<img src='".esc_url($assets_url)."/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Amazon'/>";
                }
                else if($remote['type']=='b2')
                {
                    echo "<img src='".esc_url($assets_url)."/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Backblaze'/>";
                }
                else if($remote['type']=='dropbox')
                {
                    echo "<img src='".esc_url($assets_url)."/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Dropbox'/>";
                }
                else if($remote['type']=='ftp')
                {
                    echo "<img src='".esc_url($assets_url)."/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='FTP'/>";
                }
                else if($remote['type']=='googledrive')
                {
                    echo "<img src='".esc_url($assets_url)."/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='GoogleDrive'/>";
                }
                else if($remote['type']=='nextcloud')
                {
                    echo "<img src='".esc_url($assets_url)."/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Nextcloud'/>";
                }
                else if($remote['type']=='onedrive')
                {
                    echo "<img src='".esc_url($assets_url)."/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='OneDrive'/>";
                }
                else if($remote['type']=='onedrive_shared')
                {
                    echo "<img src='".esc_url($assets_url)."/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='OneDrive Shared Drives'/>";
                }
                else if($remote['type']=='pCloud')
                {
                    echo "<img src='".esc_url($assets_url)."/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='pCloud'/>";
                }
                else if($remote['type']=='s3compat')
                {
                    echo "<img src='".esc_url($assets_url)."/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='S3 Compatible Storage'/>";
                }
                else if($remote['type']=='sftp')
                {
                    echo "<img src='".esc_url($assets_url)."/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='sFTP'/>";
                }
                else if($remote['type']=='wasabi')
                {
                    echo "<img src='".esc_url($assets_url)."/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='Wasabi'/>";
                }
                else if($remote['type']=='webdav')
                {
                    echo "<img src='".esc_url($assets_url)."/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' title='WebDav'/>";
                }
            }
            echo "</span>";
        }
        else
        {
            $remote=array_shift($backup['remote']);
            $assets_url=MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images';
            if($remote['type']=='amazons3')
            {
                echo "<span><img src='".esc_url($assets_url)."/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Amazon S3</span></span>";
            }
            else if($remote['type']=='b2')
            {
                echo "<span><img src='".esc_url($assets_url)."/backblaze-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Backblaze</span></span>";
            }
            else if($remote['type']=='dropbox')
            {
                echo "<span><img src='".esc_url($assets_url)."/dropbox-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Dropbox</span></span>";
            }
            else if($remote['type']=='ftp')
            {
                echo "<span><img src='".esc_url($assets_url)."/ftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>FTP</span></span>";
            }
            else if($remote['type']=='googledrive')
            {
                echo "<span><img src='".esc_url($assets_url)."/google-drive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>GoogleDrive</span></span>";
            }
            else if($remote['type']=='nextcloud')
            {
                echo "<span><img src='".esc_url($assets_url)."/nextcloud.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Nextcloud</span></span>";
            }
            else if($remote['type']=='onedrive')
            {
                echo "<span><img src='".esc_url($assets_url)."/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>OneDrive</span></span>";
            }
            else if($remote['type']=='onedrive_shared')
            {
                echo "<span><img src='".esc_url($assets_url)."/onedrive-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>OneDrive Shared Drives</span></span>";            }
            else if($remote['type']=='pCloud')
            {
                echo "<span><img src='".esc_url($assets_url)."/pcloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>pCloud</span></span>";
            }
            else if($remote['type']=='s3compat')
            {
                echo "<span><img src='".esc_url($assets_url)."/amazon-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>S3 Compatible Storage</span></span>";
            }
            else if($remote['type']=='sftp')
            {
                echo "<span><img src='".esc_url($assets_url)."/sftp-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>sFTP</span></span>";
            }
            else if($remote['type']=='wasabi')
            {
                echo "<span><img src='".esc_url($assets_url)."/wasabi-cloud-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>Wasabi</span></span>";
            }
            else if($remote['type']=='webdav')
            {
                echo "<span><img src='".esc_url($assets_url)."/webdav-icon.png' style='vertical-align:middle;width:1rem;padding-right:0.2rem;' /><span>WebDav</span></span>";
            }
        }

    }

    private function _display_rows($backup_list)
    {
        $page=$this->get_pagenum();

        $page_backup_list=array();
        $temp_page_backup_list=array();

        if(empty($backup_list))
        {
            return;
        }

        foreach ( $backup_list as $key=>$backup)
        {
            $page_backup_list[$key]=$backup;
        }

        $count=0;
        while ( $count<$page )
        {
            $temp_page_backup_list = array_splice( $page_backup_list, 0, 30);
            $count++;
        }

        foreach ( $temp_page_backup_list as $key=>$backup)
        {
            $this->single_row($backup);
        }
    }

    public function single_row($backup)
    {
        $row_style = 'display: table-row;';
        $class='';
        if ($backup['type'] == 'Migration' || $backup['type'] == 'Upload')
        {
            $class .= 'wpvivid-upload-tr';
        }
        ?>
        <tr style="<?php echo esc_attr($row_style); ?>" class='mwp-wpvivid-backup-row <?php echo esc_attr($class); ?>' id="<?php echo esc_attr($backup['id']); ?>">
            <?php $this->single_row_columns( $backup ); ?>
        </tr>
        <?php
    }

    protected function pagination( $which )
    {
        if ( empty( $this->_pagination_args ) )
        {
            return;
        }

        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;
        if ( isset( $this->_pagination_args['infinite_scroll'] ) )
        {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ( 'top' === $which && $total_pages > 1 )
        {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }
        /* translators: %s: Number of comments. */
        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current              = $this->get_pagenum();

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='first-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='prev-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-backuplist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-backuplist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        /* translators: 1: Current page, 2: Total pages. */
        $page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='next-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='last-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    protected function display_tablenav( $which ) {
        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'margin: 0 0 10px 0';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
    }
}

class Mainwp_WPvivid_Backup_List extends WP_List_Table
{
    public $page_num;
    public $backup_list;
    public $time_zone;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'backup',
                'screen' => 'backup'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped mwp-wpvivid-backup-list' );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox"/>';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name )
        {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) )
            {
                $class[] = 'hidden';
            }

            if ( $column_key === $primary )
            {
                $class[] = 'column-primary';
            }

            if ( $column_key === 'cb' )
            {
                $class[] = 'check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['wpvivid_backup'] = __( 'Backup', 'wpvivid' );
        $columns['wpvivid_storage'] = __( 'Storage', 'wpvivid' );
        $columns['wpvivid_comment'] =__( 'Comment', 'wpvivid'  );
        $columns['wpvivid_download'] = __( 'Download', 'wpvivid'  );
        if(current_user_can('wpvivid-can-restore')||current_user_can('administrator'))
            $columns['wpvivid_restore'] = __( 'Restore', 'wpvivid'  );

        $columns['wpvivid_delete'] = __( 'Delete', 'wpvivid'  );
        return $columns;
    }

    public function column_cb( $backup )
    {
        echo '<input type="checkbox"/>';
    }

    public function _column_wpvivid_backup( $backup )
    {
        $upload_title = '';

        if ($backup['type'] == 'Migration' || $backup['type'] == 'Upload')
        {
            if ($backup['type'] == 'Migration')
            {
                $upload_title = 'Received Backup: ';
            } else if ($backup['type'] == 'Upload')
            {
                $upload_title = 'Uploaded Backup: ';
            }
        }

        if (empty($backup['lock']))
        {
            $backup_lock = '/admin/images/unlocked.png';
            $lock_status = '';
        }
        else {
            if ($backup['lock'] == 0)
            {
                $backup_lock = '/admin/images/unlocked.png';
                $lock_status = '';
            } else {
                $backup_lock = '/admin/images/locked.png';
                $lock_status = 'lock';
            }
        }

        $offset=$this->time_zone;
        $localtime = $backup['create_time'] + $offset * 60 * 60;

        echo '<td class="tablelistcolumn">
                    <div style="float:left;padding:0 10px 10px 0;">
                        <div style="float: left; margin-right: 2px;"><strong>' . esc_html($upload_title) . '</strong></div>
                        <div class="backuptime" style="float: left;">' . esc_html(date('M d, Y H:i', $localtime)) . '</div>
                        <div style="clear: both;"></div>
                        <div class="common-table">
                            <span class="mwp-wpvivid-lock '.esc_attr($lock_status).'"  title="To lock the backup, the backup can only be deleted manually">
                            <img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $backup_lock) . '" style="vertical-align:middle; cursor:pointer;"/>
                            </span>
                            <span style="margin:0 5px 0 0; opacity: 0.5;">|</span> <span>' . esc_html(__('Type: ', 'wpvivid')) . '</span><span>' . esc_html($backup['type']) . '</span>
                            <span style="margin:0 0 0 5px; opacity: 0.5;">|</span> <span title="Backup log"><a href="#" name="'.esc_attr(basename($backup['log'])).'" class="mwp-wpvivid-log"><img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Log.png') . '" style="vertical-align:middle;cursor:pointer;"/><span style="margin:0;">' . esc_html(__('Log')) . '</span></a></span>
                        </div>
                    </div>
                </td>';
    }

    public function _column_wpvivid_storage( $backup )
    {
        echo '<td class="tablelistcolumn">
                    <div style="float:left;padding:10px 10px 10px 0;">';

        $remote=array();
        $remote=apply_filters('mwp_wpvivid_remote_pic', $remote);

        $save_local_pic_y = '/admin/images/storage-local.png';
        $save_local_pic_n = '/admin/images/storage-local(gray).png';
        $local_title = 'Localhost';


        $b_output=false;
        if (is_array($remote))
        {
            foreach ($remote as $key1 => $value1)
            {
                $title = $value1['title'];
                foreach ($backup['remote'] as $storage_type)
                {
                    if ($key1 === $storage_type['type'])
                    {
                        $pic = $value1['selected_pic'];
                        echo  '<img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $pic) . '" style="vertical-align:middle; " title="' . esc_attr($title) . '"/>';
                        $b_output=true;
                    }
                }
            }
        }

        if(!$b_output)
        {
            if ($backup['save_local'] == 1 || $backup['type'] == 'Migration')
            {
                echo '<img  src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $save_local_pic_y) . '" style="vertical-align:middle; " title="' . esc_attr($local_title) . '"/>';
            } else {
                echo '<img  src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $save_local_pic_n) . '" style="vertical-align:middle; " title="' . esc_attr($local_title) . '"/>';
            }
        }
         echo '</div>
                </td>';
    }

    public function _column_wpvivid_comment( $backup )
    {
        if(isset($backup['backup_prefix']) && !empty($backup['backup_prefix']))
        {
            $backup_prefix = $backup['backup_prefix'];
        }
        else{
            $backup_prefix = 'N/A';
        }
        echo '<td class="tablelistcolumn wpvivid-list-td-center">
                    <div style="padding:14px 10px 10px 0;">'.esc_html($backup_prefix).'</div>
                </td>';
    }

    public function _column_wpvivid_download( $backup )
    {
        echo '<td class="tablelistcolumn" style="min-width:100px;">
                    <div class="mwp-wpvivid-download" style="float:left;padding:10px 10px 10px 0;">
                        <div style="cursor:pointer;" title="Prepare to download the backup">
                            <img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/download.png') . '" style="vertical-align:middle;" />
                            <span>' . esc_html(__('Download', 'wpvivid')) . '</span>
                        </div>
                    </div>
                </td>';
    }

    public function _column_wpvivid_restore( $backup )
    {
        echo '<td class="tablelistcolumn" style="min-width:100px;">
                    <div>
                      <div class="mwp-wpvivid-restore" style="cursor:pointer;float:left;padding:10px 0 10px 0;">
                            <img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Restore.png') . '" style="vertical-align:middle;" /><span>' . esc_html(__('Restore', 'wpvivid')) . '</span>
                       </div>
                    </div>
                </td>';
    }

    public function _column_wpvivid_delete( $backup )
    {
        echo '<td class="tablelistcolumn">
                    <div class="mwp-backuplist-delete-backup" style="padding:10px 0 10px 0;">
                        <img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Delete.png') . '" style="vertical-align:middle; cursor:pointer;" title="Delete the backup"/>
                    </div>
                </td>';
    }

    public function set_backup_list($backup_list,$page_num=1,$time_zone=0)
    {
        $this->backup_list=$backup_list;
        $this->page_num=$page_num;
        $this->time_zone=$time_zone;
    }

    public function get_pagenum()
    {
        if($this->page_num=='first')
        {
            $this->page_num=1;
        }
        else if($this->page_num=='last')
        {
            $this->page_num=$this->_pagination_args['total_pages'];
        }
        $pagenum = $this->page_num ? $this->page_num : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
        {
            $pagenum = $this->_pagination_args['total_pages'];
        }

        return max( 1, $pagenum );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $total_items =sizeof($this->backup_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->backup_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->backup_list);
    }

    private function _display_rows($backup_list)
    {
        $page=$this->get_pagenum();

        $page_backup_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_backup_list = array_splice( $backup_list, 0, 10);
            $count++;
        }
        foreach ( $page_backup_list as $key=>$backup)
        {
            $backup['key']=$key;
            $this->single_row($backup);
        }
    }

    public function single_row($backup)
    {
        $row_style = 'display: table-row;';
        $class='';
        if ($backup['type'] == 'Migration' || $backup['type'] == 'Upload')
        {
            $class .= 'wpvivid-upload-tr';
        }
        ?>
        <tr style="<?php echo esc_attr($row_style); ?>" class='mwp-wpvivid-backup-row <?php echo esc_attr($class); ?>' id="<?php echo esc_attr($backup['key']); ?>">
            <?php $this->single_row_columns( $backup ); ?>
        </tr>
        <?php
    }

    protected function pagination( $which )
    {
        if ( empty( $this->_pagination_args ) )
        {
            return;
        }

        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;
        if ( isset( $this->_pagination_args['infinite_scroll'] ) )
        {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ( 'top' === $which && $total_pages > 1 )
        {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }
        /* translators: %s: Number of comments. */
        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current              = $this->get_pagenum();

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='first-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='prev-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-backuplist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-backuplist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        /* translators: 1: Current page, 2: Total pages. */
        $page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='next-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='last-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    protected function display_tablenav( $which ) {
        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'margin: 0 0 10px 0';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display()
    {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo esc_attr(implode( ' ', $this->get_table_classes() )); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo esc_attr(" data-wp-lists='list:$singular'");
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

            <tfoot>
            <tr>
                <td class="manage-column column-cb check-column" style="padding-left: 6px;">
                    <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
                    <input type="checkbox" id="cb-select-all-2" />
                </td>
                <th class="row-title" colspan="6"><a class="mwp-wpvivid-delete-array" style="cursor: pointer;"><?php esc_html_e('Delete the selected backups', 'wpvivid'); ?></a></th>
            </tr>
            </tfoot>

        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }
}

class Mainwp_WPvivid_Incremental_List extends WP_List_Table
{
    public $page_num;
    public $incremental_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'incremental',
                'screen' => 'incremental'
            )
        );
    }

    protected function get_table_classes()
    {
        return array( 'widefat striped' );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb'])) {
            static $cb_counter = 1;
            $columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . __('Select All') . '</label>'
                . '<input id="cb-select-all-' . $cb_counter . '" type="checkbox"/>';
            $cb_counter++;
        }

        foreach ( $columns as $column_key => $column_display_name )
        {
            $class = array( 'manage-column', "column-$column_key" );

            if ( in_array( $column_key, $hidden ) )
            {
                $class[] = 'hidden';
            }

            if ( $column_key === $primary )
            {
                $class[] = 'column-primary';
            }

            if ( $column_key === 'cb' )
            {
                $class[] = 'check-column';
            }

            $tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            $id    = $with_id ? "id='$column_key'" : '';

            if ( ! empty( $class ) )
            {
                $class = "class='" . join( ' ', $class ) . "'";
            }

            echo "<$tag $scope $id $class>$column_display_name</$tag>";
        }
    }

    public function get_columns()
    {
        $columns = array();
        $columns['wpvivid_incremental_backup_date'] = __( 'Date', 'wpvivid' );
        $columns['wpvivid_incremental_backup_action'] = __( 'Action', 'wpvivid' );
        return $columns;
    }

    public function _column_wpvivid_incremental_backup_date($incremental)
    {
        $time = date('F d, Y', $incremental['path']);

        echo'<td class="tablelistcolumn" style="width: 95%;">
                    <div>
                        '.esc_html($time).'
                    </div>
               </td>';
    }

    public function _column_wpvivid_incremental_backup_action($incremental)
    {
        echo '<td class="tablelistcolumn"><div class="mwp-wpvivid-incremental-child" style="padding:0; width: 5%;">';
        echo '<input type="button" value="scan" />';
        echo '</div></td>';
    }

    public function set_incremental_list($incremental_list,$page_num=1)
    {
        $this->incremental_list=$incremental_list;
        $this->page_num=$page_num;
    }

    public function get_pagenum()
    {
        if($this->page_num=='first')
        {
            $this->page_num=1;
        }
        else if($this->page_num=='last')
        {
            $this->page_num=$this->_pagination_args['total_pages'];
        }
        $pagenum = $this->page_num ? $this->page_num : 0;

        if ( isset( $this->_pagination_args['total_pages'] ) && $pagenum > $this->_pagination_args['total_pages'] )
        {
            $pagenum = $this->_pagination_args['total_pages'];
        }

        return max( 1, $pagenum );
    }

    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = array();
        $this->_column_headers = array($columns, $hidden, $sortable);

        if(!empty($this->incremental_list)){
            $total_items = sizeof($this->incremental_list);
        }
        else{
            $total_items = 0;
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 5,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->incremental_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->incremental_list);
    }

    private function _display_rows($incremental_list)
    {
        $page=$this->get_pagenum();

        $page_incremental_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_incremental_list = array_splice( $incremental_list, 0, 5);
            $count++;
        }
        foreach ( $page_incremental_list as $key=>$incremental)
        {
            $this->single_row($incremental);
        }
    }

    public function single_row($incremental)
    {
        ?>
        <tr id="<?php echo esc_attr($incremental['og_path']); ?>">
            <?php $this->single_row_columns( $incremental ); ?>
        </tr>
        <?php
    }

    protected function pagination( $which )
    {
        if ( empty( $this->_pagination_args ) )
        {
            return;
        }

        $total_items     = $this->_pagination_args['total_items'];
        $total_pages     = $this->_pagination_args['total_pages'];
        $infinite_scroll = false;
        if ( isset( $this->_pagination_args['infinite_scroll'] ) )
        {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ( 'top' === $which && $total_pages > 1 )
        {
            $this->screen->render_screen_reader_content( 'heading_pagination' );
        }
        /* translators: %s: Number of comments. */
        $output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

        $current              = $this->get_pagenum();

        $page_links = array();

        $total_pages_before = '<span class="paging-input">';
        $total_pages_after  = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ( $current == 1 ) {
            $disable_first = true;
            $disable_prev  = true;
        }
        if ( $current == 2 ) {
            $disable_first = true;
        }
        if ( $current == $total_pages ) {
            $disable_last = true;
            $disable_next = true;
        }
        if ( $current == $total_pages - 1 ) {
            $disable_last = true;
        }

        if ( $disable_first ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='first-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'First page' ),
                '&laquo;'
            );
        }

        if ( $disable_prev ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='prev-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Previous page' ),
                '&lsaquo;'
            );
        }

        if ( 'bottom' === $which ) {
            $html_current_page  = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf(
                "%s<input class='current-page' id='current-page-selector-filelist' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-filelist" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
                $current,
                strlen( $total_pages )
            );
        }
        $html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
        /* translators: 1: Current page, 2: Total pages. */
        $page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

        if ( $disable_next ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='next-page button' value='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                $current,
                __( 'Next page' ),
                '&rsaquo;'
            );
        }

        if ( $disable_last ) {
            $page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf(
                "<div class='last-page button'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></div>",
                __( 'Last page' ),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if ( ! empty( $infinite_scroll ) ) {
            $pagination_links_class .= ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

        if ( $total_pages ) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;
    }

    protected function display_tablenav( $which ) {
        $css_type = '';
        if ( 'top' === $which ) {
            wp_nonce_field( 'bulk-' . $this->_args['plural'] );
            $css_type = 'margin: 0 0 10px 0';
        }
        else if( 'bottom' === $which ) {
            $css_type = 'margin: 10px 0 0 0';
        }

        $total_pages     = $this->_pagination_args['total_pages'];
        if ( $total_pages >1)
        {
            ?>
            <div class="tablenav <?php echo esc_attr( $which ); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav( $which );
                $this->pagination( $which );
                ?>

                <br class="clear" />
            </div>
            <?php
        }
    }

    public function display()
    {
        $singular = $this->_args['singular'];

        $this->display_tablenav( 'top' );

        $this->screen->render_screen_reader_content( 'heading_list' );
        ?>
        <table class="wp-list-table <?php echo esc_attr(implode( ' ', $this->get_table_classes() )); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"
                <?php
                if ( $singular ) {
                    echo esc_attr(" data-wp-lists='list:$singular'");
                }
                ?>
            >
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }
}

class Mainwp_WPvivid_Extension_BackupRestorePage
{
    private $setting;
    private $setting_addon;
    private $remote_addon;
    private $site_id;
    public $main_tab;

    public function __construct()
    {
        $this->load_backup_restore_ajax();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_backup_restore_info($setting, $setting_addon=array(), $remote_addon=array())
    {
        $this->setting=$setting;
        $this->setting_addon=$setting_addon;
        $this->remote_addon=$remote_addon;
    }

    public function load_backup_restore_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_rescan_local_folder_addon', array($this, 'rescan_local_folder_addon'));
        add_action('wp_ajax_mwp_wpvivid_achieve_local_backup_addon', array($this, 'achieve_local_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_security_lock_addon', array($this, 'set_security_lock_addon'));

        //new backup list ajax start
        add_action('wp_ajax_mwp_wpvivid_achieve_backup_list_addon', array($this, 'achieve_backup_list_addon'));
        add_action('wp_ajax_mwp_wpvivid_scan_remote_backup_addon', array($this, 'scan_remote_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_scan_remote_backup_continue_addon', array($this, 'scan_remote_backup_continue_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_backup_ex_addon', array($this, 'delete_backup_ex_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_backup_array_ex_addon', array($this, 'delete_backup_array_ex_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_security_lock_ex_addon', array($this, 'set_security_lock_ex_addon'));
        //new backup list ajax end

        add_action('wp_ajax_mwp_wpvivid_delete_local_backup_addon', array($this, 'delete_local_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_local_backup_array_addon', array($this, 'delete_local_backup_array_addon'));
        add_action('wp_ajax_mwp_wpvivid_achieve_remote_backup_addon', array($this, 'achieve_remote_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_remote_security_lock_addon', array($this, 'set_remote_security_lock_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_remote_backup_addon', array($this, 'delete_remote_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_remote_backup_array_addon', array($this, 'delete_remote_backup_array_addon'));
        add_action('wp_ajax_mwp_wpvivid_achieve_remote_backup_info_addon', array($this, 'achieve_remote_backup_info_addon'));
        add_action('wp_ajax_mwp_wpvivid_archieve_incremental_remote_folder_list_addon', array($this, 'archieve_incremental_remote_folder_list_addon'));
        add_action('wp_ajax_mwp_wpvivid_achieve_incremental_child_path_addon', array($this, 'achieve_incremental_child_path_addon'));
        add_action('wp_ajax_mwp_wpvivid_prepare_download_backup_addon',array($this,'prepare_download_backup_addon'));
        add_action('wp_ajax_mwp_wpvivid_get_download_progress_addon', array($this, 'get_download_progress_addon'));
        add_action('wp_ajax_mwp_wpvivid_init_download_page_addon', array($this, 'init_download_page_addon'));
        add_action('wp_ajax_mwp_wpvivid_get_backup_addon_list', array($this, 'get_backup_addon_list'));
        add_action('wp_ajax_mwp_wpvivid_view_log_addon', array($this, 'view_log_addon'));
    }

    public function rescan_local_folder_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_rescan_local_folder_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    if(isset($information['incomplete_backup']))
                    {
                        $ret['incomplete_backup']=$information['incomplete_backup'];
                    }
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function achieve_local_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])){
                $site_id = sanitize_key($_POST['site_id']);
                $folder  = sanitize_text_field($_POST['folder']);
                $page    = sanitize_text_field($_POST['page']);
                $post_data['mwp_action'] = 'wpvivid_achieve_local_backup_addon_mainwp';
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'time_zone', '');
                    if(empty($time_zone)){
                        $time_zone = 0;
                    }
                    $table->set_backup_list($information['list_data'], $page, $time_zone);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function set_security_lock_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['lock'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['lock'] = sanitize_text_field($_POST['lock']);
                $post_data['mwp_action'] = 'wpvivid_set_security_lock_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    if($information['lock_status'] === 'lock'){
                        $backup_lock = '/admin/images/locked.png';
                    }
                    else{
                        $backup_lock = '/admin/images/unlocked.png';
                    }
                    $ret['html'] = '<img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $backup_lock) . '"  style="vertical-align:middle; cursor:pointer;"/>';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function achieve_backup_list_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_storage']) && !empty($_POST['backup_storage']) && is_string($_POST['backup_storage']) &&
                isset($_POST['backup_folder']) && !empty($_POST['backup_folder']) && is_string($_POST['backup_folder'])){
                $site_id        = sanitize_key($_POST['site_id']);
                $backup_storage = sanitize_text_field($_POST['backup_storage']);
                $backup_folder  = sanitize_text_field($_POST['backup_folder']);
                if(isset($_POST['get_remote_storage']) && sanitize_text_field($_POST['get_remote_storage']))
                {
                    $get_remote_storage='1';
                }
                else
                {
                    $get_remote_storage='0';
                }
                $post_data['mwp_action'] = 'wpvivid_achieve_backup_list_addon_mainwp';

                $post_data['backup_storage'] = $backup_storage;
                $post_data['backup_folder'] = $backup_folder;
                if(isset($_POST['remote_storage']) && !empty($_POST['remote_storage']) && is_string($_POST['remote_storage']))
                {
                    $remote_storage = sanitize_text_field($_POST['remote_storage']);
                    $post_data['remote_storage'] = $remote_storage;
                }
                else
                {
                    $post_data['remote_storage'] = 'all_backups';
                }
                $post_data['get_remote_storage'] = $get_remote_storage;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $child_site_pro_version = $mainwp_wpvivid_extension_activator->get_current_version($site_id);
                    if(version_compare($child_site_pro_version,'2.2.23','>='))
                    {
                        $ret['error'] = $information['error'];
                    }
                    else
                    {
                        if($information['error'] !== 'Unknown function')
                        {
                            $ret['error'] = $information['error'];
                        }
                        else
                        {
                            $ret['error'] = 'To use upgraded backup manager, please update WPvivid Backup Pro on the child site to 2.2.23 and sync site.';
                        }
                    }
                }
                else {
                    $table = new Mainwp_WPvivid_New_Backup_List();
                    if(isset($_POST['page']))
                    {
                        $table->set_backup_list($information['backups'], sanitize_text_field($_POST['page']));
                    }
                    else
                    {
                        $table->set_backup_list($information['backups']);
                    }
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();

                    if(isset($information['wpvivid_remote_list']) && !empty($information['wpvivid_remote_list']))
                    {
                        $remote_list = $information['wpvivid_remote_list'];
                    }
                    else
                    {
                        $remote_list = array();
                    }
                    $ret['remote_list_html'] = Mainwp_WPvivid_Extension_Subpage::output_remote_list_addon($remote_list);
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function scan_remote_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_folder']) && !empty($_POST['backup_folder']) && is_string($_POST['backup_folder']) &&
                isset($_POST['remote_storage']) && !empty($_POST['remote_storage']) && is_string($_POST['remote_storage'])){
                $site_id        = sanitize_key($_POST['site_id']);
                $backup_folder  = sanitize_text_field($_POST['backup_folder']);
                $remote_storage = sanitize_text_field($_POST['remote_storage']);
                $post_data['mwp_action'] = 'wpvivid_scan_remote_backup_addon_mainwp';
                $post_data['backup_folder'] = $backup_folder;
                $post_data['remote_storage'] = $remote_storage;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_New_Backup_List();
                    $table->set_backup_list($information['backups']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['finished'] = $information['finished'];
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function scan_remote_backup_continue_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id        = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_scan_remote_backup_continue_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_New_Backup_List();
                    $table->set_backup_list($information['backups']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['finished'] = $information['finished'];
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_backup_ex_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['backup_storage']) && !empty($_POST['backup_storage']) && is_string($_POST['backup_storage']) &&
                isset($_POST['backup_folder']) && !empty($_POST['backup_folder']) && is_string($_POST['backup_folder']) &&
                isset($_POST['remote_storage']) && !empty($_POST['remote_storage']) && is_string($_POST['remote_storage'])){
                $site_id        = sanitize_key($_POST['site_id']);
                $backup_id      = sanitize_text_field($_POST['backup_id']);
                $backup_storage = sanitize_text_field($_POST['backup_storage']);
                $backup_folder  = sanitize_text_field($_POST['backup_folder']);
                $remote_storage = sanitize_text_field($_POST['remote_storage']);

                $post_data['mwp_action'] = 'wpvivid_delete_backup_ex_addon_mainwp';
                $post_data['backup_id'] = $backup_id;
                $post_data['backup_storage'] = $backup_storage;
                $post_data['backup_folder'] = $backup_folder;
                $post_data['remote_storage'] = $remote_storage;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_New_Backup_List();
                    $table->set_backup_list($information['backups']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_backup_array_ex_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) &&
                isset($_POST['backup_storage']) && !empty($_POST['backup_storage']) && is_string($_POST['backup_storage']) &&
                isset($_POST['backup_folder']) && !empty($_POST['backup_folder']) && is_string($_POST['backup_folder']) &&
                isset($_POST['remote_storage']) && !empty($_POST['remote_storage']) && is_string($_POST['remote_storage'])){
                $site_id        = sanitize_key($_POST['site_id']);
                $backup_ids = array_map( 'sanitize_key', $_POST['backup_id'] );
                foreach ($backup_ids as $backup_id){
                    $post_data['backup_id'][] = sanitize_key($backup_id);
                }
                $backup_storage = sanitize_text_field($_POST['backup_storage']);
                $backup_folder  = sanitize_text_field($_POST['backup_folder']);
                $remote_storage = sanitize_text_field($_POST['remote_storage']);

                $post_data['mwp_action'] = 'wpvivid_delete_backup_array_ex_addon_mainwp';
                $post_data['backup_storage'] = $backup_storage;
                $post_data['backup_folder'] = $backup_folder;
                $post_data['remote_storage'] = $remote_storage;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_New_Backup_List();
                    $table->set_backup_list($information['backups']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function set_security_lock_ex_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['lock'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['lock'] = sanitize_text_field($_POST['lock']);
                $post_data['mwp_action'] = 'wpvivid_set_security_lock_ex_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function download_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['file_name']) && !empty($_POST['file_name']) && is_string($_POST['file_name'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['file_name'] = sanitize_text_field($_POST['file_name']);

                $websites = apply_filters('mainwp_getdbsites', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, array($site_id), array());
                $website = null;
                if ( $websites && is_array( $websites ) ) {
                    $website = current( $websites );
                }


                $open_location = 'admin-ajax.php?backup_id='.$post_data['backup_id'].'&file_name='.$post_data['file_name'].'&action=wpvivid_download_backup_mainwp&_mwpNoneName=_wpnonce&_mwpNoneValue=wpvivid_download';
                $url=Mainwp_WPvivid_Extension_Setting::mwp_wpvivid_get_getdata_authed( $website, ( null === $open_location || '' === $open_location ) ? 'index.php' : $open_location );

                echo '<div style="font-size: 30px; text-align: center; margin-top: 5em;"><?php _e(\'You will be redirected to your website immediately.\'); ?></div>
                <form method="POST" action="<?php esc_attr_e(Mainwp_WPvivid_Extension_Setting::mwp_wpvivid_get_getdata_authed( $website, ( null === $open_location || \'\' === $open_location ) ? \'index.php\' : $open_location )); ?>" id="redirectForm"></form>';


                $ret['result'] = 'success';
                echo wp_json_encode($ret);

            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_ready_download_files()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && isset($_POST['backup_id']))
            {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['mwp_action'] = 'wpvivid_get_ready_download_files_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['files'] = $information['files'];
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function read_file_content()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && isset($_POST['file_name']) && isset($_POST['chunk_size']) && isset($_POST['offset_size']))
            {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['file_name'] = sanitize_text_field($_POST['file_name']);
                $post_data['chunk_size'] = sanitize_key($_POST['chunk_size']);
                $post_data['offset_size'] = sanitize_key($_POST['offset_size']);
                $post_data['mwp_action'] = 'wpvivid_read_file_content_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['content'] = base64_decode($information['content']);
                    $file_path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'test_download_file.zip';
                    if(!file_exists($file_path))
                    {
                        $handle=fopen($file_path,'w');
                        fclose($handle);
                    }

                    $handle=fopen($file_path,'rb+');
                    fseek($handle, $post_data['offset_size']);
                    fwrite($handle, $ret['content']);

                    /*header('Content-Type: application/octet-stream');
                    header('Content-Transfer-Encoding: binary');
                    print($ret['content']);
                    ob_flush();
                    flush();*/
                    die();
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_local_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])){
                $site_id = sanitize_key($_POST['site_id']);
                $backup_id = sanitize_key($_POST['backup_id']);
                $folder  = sanitize_text_field($_POST['folder']);
                $page    = sanitize_text_field($_POST['page']);
                $post_data['mwp_action'] = 'wpvivid_delete_local_backup_addon_mainwp';
                $post_data['backup_id'] = $backup_id;
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'time_zone', '');
                    if(empty($time_zone)){
                        $time_zone = 0;
                    }
                    $table->set_backup_list($information['list_data'], $page, $time_zone);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_local_backup_array_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_array($_POST['backup_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $backup_ids = array_map( 'sanitize_key', $_POST['backup_id'] );
                $folder  = sanitize_text_field($_POST['folder']);
                $page    = sanitize_text_field($_POST['page']);
                foreach ($backup_ids as $backup_id){
                    $post_data['backup_id'][] = sanitize_key($backup_id);
                }
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                $post_data['mwp_action'] = 'wpvivid_delete_local_backup_array_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $time_zone=Mainwp_WPvivid_Extension_Option::get_instance()->wpvivid_get_single_option($site_id, 'time_zone', '');
                    if(empty($time_zone)){
                        $time_zone = 0;
                    }
                    $table->set_backup_list($information['list_data'], $page, $time_zone);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function achieve_remote_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])) {
                $site_id   = sanitize_key($_POST['site_id']);
                $remote_id = sanitize_key($_POST['remote_id']);
                $folder    = sanitize_text_field($_POST['folder']);
                $page      = sanitize_text_field($_POST['page']);
                $post_data['mwp_action'] = 'wpvivid_achieve_remote_backup_addon_mainwp';
                $post_data['remote_id'] = $remote_id;
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                if(isset($_POST['incremental_path'])&&!empty($_POST['incremental_path']))
                {
                    $post_data['incremental_path'] = sanitize_text_field($_POST['incremental_path']);
                }
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $table->set_backup_list($information['list_data'], $page);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();

                    $table = new Mainwp_WPvivid_Incremental_List();
                    $table->set_incremental_list($information['incremental_list']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['incremental_list'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function set_remote_security_lock_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['lock'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['lock'] = sanitize_text_field($_POST['lock']);
                $post_data['mwp_action'] = 'wpvivid_set_remote_security_lock_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    if($information['lock_status'] === 'lock'){
                        $backup_lock = '/admin/images/locked.png';
                    }
                    else{
                        $backup_lock = '/admin/images/unlocked.png';
                    }
                    $ret['html'] = '<img src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . $backup_lock) . '"  style="vertical-align:middle; cursor:pointer;"/>';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_remote_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])){
                $site_id = sanitize_key($_POST['site_id']);
                $backup_id = sanitize_key($_POST['backup_id']);
                $folder  = sanitize_text_field($_POST['folder']);
                $page    = sanitize_text_field($_POST['page']);
                $post_data['mwp_action'] = 'wpvivid_delete_remote_backup_addon_mainwp';
                $post_data['backup_id'] = $backup_id;
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $table->set_backup_list($information['list_data'], $page);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_remote_backup_array_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_array($_POST['backup_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $backup_ids = array_map( 'sanitize_key', $_POST['backup_id'] );
                $folder  = sanitize_text_field($_POST['folder']);
                $page    = sanitize_text_field($_POST['page']);
                foreach ($backup_ids as $backup_id){
                    $post_data['backup_id'][] = sanitize_key($backup_id);
                }
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                $post_data['mwp_action'] = 'wpvivid_delete_remote_backup_array_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $table->set_backup_list($information['list_data'], $page);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function achieve_remote_backup_info_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if (isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_achieve_remote_backup_info_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                }
                else {
                    $table = new Mainwp_WPvivid_Backup_List();
                    $table->set_backup_list($information['select_list_data'], 0);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['select_list_html'] = ob_get_clean();
                    $ret['remote_part_html'] = Mainwp_WPvivid_Extension_Subpage::output_remote_backup_page_addon($information['remote_list'], $information['select_remote_id']);
                    $ret['remote_list'] = $information['remote_list'];
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function archieve_incremental_remote_folder_list_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id']) &&
                isset($_POST['folder']) && !empty($_POST['folder']) && is_string($_POST['folder']) &&
                isset($_POST['page'])){
                $site_id = sanitize_key($_POST['site_id']);
                $remote_id = sanitize_key($_POST['remote_id']);
                $folder  = sanitize_text_field($_POST['folder']);
                $page    = sanitize_text_field($_POST['page']);

                $post_data['mwp_action'] = 'wpvivid_archieve_incremental_remote_folder_list_addon_mainwp';
                $post_data['remote_id'] = $remote_id;
                $post_data['folder'] = $folder;
                $post_data['page'] = $page;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $table = new Mainwp_WPvivid_Incremental_List();
                    $table->set_incremental_list($information['incremental_list'], $page);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['incremental_list'] = ob_get_clean();
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function achieve_incremental_child_path_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id']) &&
                isset($_POST['incremental_path']) && !empty($_POST['incremental_path']) && is_string($_POST['incremental_path'])){
                $site_id = sanitize_key($_POST['site_id']);
                $remote_id = sanitize_key($_POST['remote_id']);
                $incremental_path = sanitize_text_field($_POST['incremental_path']);

                $post_data['mwp_action'] = 'wpvivid_achieve_incremental_child_path_addon_mainwp';
                $post_data['remote_id'] = $remote_id;
                $post_data['incremental_path'] = $incremental_path;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $table = new Mainwp_WPvivid_Backup_List();
                    $table->set_backup_list($information['list_data']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['html'] = ob_get_clean();
                    //$ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_list($information['data']);
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function prepare_download_backup_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id']) &&
                isset($_POST['file_name']) && !empty($_POST['file_name']) && is_string($_POST['file_name'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['backup_id'] = sanitize_key($_POST['backup_id']);
                $post_data['file_name'] = sanitize_text_field($_POST['file_name']);
                $post_data['mwp_action'] = 'wpvivid_new_prepare_download_backup_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                }
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function get_download_progress_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $backup_id = sanitize_key($_POST['backup_id']);
                $post_data['mwp_action'] = 'wpvivid_new_get_download_progress_addon_mainwp';
                $post_data['backup_id'] = $backup_id;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['need_update'] = $information['need_update'];
                    $ret['files'] = Mainwp_WPvivid_Extension_Subpage::output_download_progress_addon($information['files']);
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function init_download_page_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $backup_id = sanitize_key($_POST['backup_id']);
                $post_data['mwp_action'] = 'wpvivid_new_init_download_page_addon_mainwp';
                $post_data['backup_id'] = $backup_id;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_init_download_page_addon($information['files'], $backup_id);
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_backup_addon_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['backup_id']) && !empty($_POST['backup_id']) && is_string($_POST['backup_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $backup_id = sanitize_key($_POST['backup_id']);
                $post_data['mwp_action'] = 'wpvivid_init_download_page_addon_mainwp';
                $post_data['backup_id'] = $backup_id;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    if(isset($_POST['page'])) {
                        $page = sanitize_text_field($_POST['page']);
                    }
                    else{
                        $page = 1;
                    }
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_init_download_page_addon($information['files'], $backup_id, $page);
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function view_log_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                if(isset($_POST['log']) && !empty($_POST['log']) && is_string($_POST['log']))
                {
                    $log = sanitize_text_field($_POST['log']);
                    $post_data['log'] = $log;
                }
                else
                {
                    $post_data['log'] = '';
                }
                $post_data['mwp_action'] = 'wpvivid_view_log_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['data'] = $information['data'];
                }
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function render($check_pro, $global=false)
    {
        ?>
        <div style="margin: 10px;">
            <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
                <div class="mwp-wpvivid-welcome-bar-left">
                    <p><span class="dashicons dashicons-update-alt mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-green"></span><span class="mwp-wpvivid-page-title">Backup Manager & Restoration</span></p>
                    <span class="about-description">The page allows you to browse and manage all your backups, upload backups and restore the website from backups.</span>
                </div>
                <div class="mwp-wpvivid-welcome-bar-right"></div>
                <div class="mwp-wpvivid-nav-bar mwp-wpvivid-clear-float">
                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                    <span> Please <strong>do not</strong> change the backup file name, otherwise, the plugin will <strong>be unable to</strong> recognize the backup to perform restore or migration.</span>
                </div>
            </div>

            <?php
            if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
                include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
            $this->main_tab=new Mainwp_WPvivid_Tab_Page_Container();

            $args['is_parent_tab']=0;
            $args['transparency']=1;
            $this->main_tab->add_tab('All Backups', 'all_list', array($this, 'output_all_list'), $args);
            $args['can_delete']=1;
            $args['hide']=1;
            $this->main_tab->add_tab('Download','download',array($this, 'output_download'), $args);
            $this->main_tab->add_tab('Logs','log',array($this, 'output_log'), $args);
            $this->main_tab->display();
            ?>
        </div>
        <?php
    }

    public function output_localhost(){
        if(isset($this->setting['wpvivid_local_setting']['path']) && !empty($this->setting['wpvivid_local_setting']['path'])){
            $local_path = $this->setting['wpvivid_local_setting']['path'];
        }
        else{
            $local_path = 'wpvividbackups';
        }
        ?>
        <div style="margin-top: 10px;">
            <div class="mwp-quickstart-storage-setting">
                <div style="padding: 10px 0 0 0;">
                    <div class="mwp-wpvivid-block-bottom-space mwp-wpvivid-font-right-space" style="float: left;"><?php esc_html_e('Child-Site Storage Directory: '); ?></div>
                    <div style="float: left;">
                        <div class="mwp-wpvivid-block-bottom-space mwp-wpvivid-font-right-space" style="float: left;"><?php esc_html_e('http(s)://child-site/wp-content/'); ?><?php echo esc_html($local_path); ?></div>
                        <small>
                            <div class="mwp-wpvivid-tooltip" style="float: left; margin-top: 4px; line-height: 100%;">?
                                <div class="mwp-wpvivid-tooltiptext">The backups will be uploaded to <?php echo esc_html('http(s)://child-site/wp-content/'.$local_path); ?></div>
                            </div>
                        </small>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div style="clear: both;"></div>

            <div class="mwp-wpvivid-block-bottom-space mwp-wpvivid-font-right-space" style="float: left; height: 30px; line-height: 30px;">Displays all backups stored under</div>
            <div class="mwp-wpvivid-block-bottom-space mwp-wpvivid-font-right-space" style="float: left;">
                <select id="mwp_wpvivid_select_local_backup_folder" onchange="mwp_wpvivid_get_local_backup_folder();">
                    <option value="wpvivid" selected="selected">wpvividbackups</option>
                    <option value="rollback">Rollback</option>
                    <option value="incremental">Incremental</option>
                </select>
            </div>
            <div class="mwp-wpvivid-block-bottom-space" style="float: left; height: 30px; line-height: 30px;">
                <a onclick="mwp_wpvivid_rollback_folder_descript();" style="cursor: pointer;">what is Rollback folder?</a>
            </div>
            <div style="clear: both;"></div>

            <div class="mwp-wpvivid-click-popup mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_rollbackup_folder_desc" style="display: none;">
                <div>Rollback folder stores all backups before updating themes, plugins or WordPress core files.</div>
            </div>
            <div style="clear: both;"></div>

            <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_scan_local_backup">
                <div class="mwp-wpvivid-block-bottom-space">
                    <div class="mwp-wpvivid-block-bottom-space mwp-wpvivid-block-right-space" style="float: left;">
                        <input type="button" class="ui green mini button" id="mwp_wpvivid_rescan_local_folder_btn" value="Scan uploaded backup or received backup" onclick="mwp_wpvivid_rescan_local_folder();" style="float: left;" />
                    </div>
                    <small>
                        <div class="mwp-wpvivid-tooltip" style="float: left; margin-top: 10px; line-height: 100%;">?
                            <div class="mwp-wpvivid-tooltiptext">Scan all uploaded or received backups in directory <?php echo esc_html('http(s)://child-site/wp-content/'.$local_path); ?></div>
                        </div>
                    </small>
                    <div class="spinner" id="mwp_wpvivid_scanning_local_folder" style="float: left;"></div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div class="mwp-wpvivid-local-remote-backup-list" id="mwp_wpvivid_backup_list"></div>
        </div>
        <script>
            function mwp_wpvivid_rollback_folder_descript(){
                if(jQuery('#mwp_wpvivid_rollbackup_folder_desc').is(":hidden"))
                {
                    jQuery('#mwp_wpvivid_rollbackup_folder_desc').show();
                }
                else{
                    jQuery('#mwp_wpvivid_rollbackup_folder_desc').hide();
                }
            }

            function mwp_wpvivid_rescan_local_folder(){
                var ajax_data = {
                    'action': 'mwp_wpvivid_rescan_local_folder_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_rescan_local_folder_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_scanning_local_folder').addClass('is-active');
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_local_folder').removeClass('is-active');
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            mwp_wpvivid_get_local_backup_folder();
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_local_folder').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('scanning backup list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_get_local_backup_folder(page=0){
                if(page === 0){
                    var current_page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                    }
                }
                var value = jQuery('#mwp_wpvivid_select_local_backup_folder').val();
                if(value === 'rollback' || value == 'incremental')
                {
                    jQuery('#mwp_wpvivid_scan_local_backup').hide();
                }
                else
                {
                    jQuery('#mwp_wpvivid_scan_local_backup').show();
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_achieve_local_backup_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'folder': value,
                    'page':page
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    setTimeout(function () {
                        mwp_wpvivid_get_local_backup_folder();
                    }, 3000);
                });
            }

            jQuery('#mwp_wpvivid_backup_list').on("click",'.first-page',function() {
                mwp_wpvivid_get_local_backup_folder('first');
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_local_backup_folder(page-1);
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_local_backup_folder(page+1);
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.last-page',function() {
                mwp_wpvivid_get_local_backup_folder('last');
            });

            jQuery('#mwp_wpvivid_backup_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_local_backup_folder(page);
                }
            });

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-lock', function(){
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                if(Obj.hasClass('lock')) {
                    var lock=0;
                }
                else {
                    var lock=1;
                }
                var ajax_data= {
                    'action': 'mwp_wpvivid_set_security_lock_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id,
                    'lock': lock
                };
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(lock)
                            {
                                Obj.addClass('lock');
                            }
                            else
                            {
                                Obj.removeClass('lock');
                            }
                            Obj.html(jsonarray.html);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-restore', function() {
                <?php
                $white_label_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
                $is_mu = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'is_mu', array());
                if(!$white_label_setting){
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                }
                else{
                    $slug = $white_label_setting['white_label_slug'];
                    $slug_page = strtolower($white_label_setting['white_label_slug']);
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                }
                ?>
                location.href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($this->site_id); ?>&location=<?php echo esc_html(base64_encode($location)); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
            });

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-backuplist-delete-backup', function(){
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                var current_page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                var value = jQuery('#mwp_wpvivid_select_local_backup_folder').val();
                var descript = '<?php esc_html_e('Are you sure to remove this backup? This backup will be deleted permanently from your hosting (localhost) and remote storages.', 'wpvivid'); ?>';

                var ret = confirm(descript);
                if(ret === true) {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_local_backup_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'backup_id': backup_id,
                        'folder': value,
                        'page':page
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                            }
                            else if(jsonarray.result === 'failed') {
                                alert(jsonarray.error);
                            }
                        }
                        catch(err){
                            alert(err);
                        }

                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-delete-array', function(){
                var delete_backup_array = new Array();
                var count = 0;
                var current_page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                var folder = jQuery('#mwp_wpvivid_select_local_backup_folder').val();
                jQuery('#mwp_wpvivid_backup_list .mwp-wpvivid-backup-row input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        delete_backup_array[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });
                if( count === 0 )
                {
                    alert('<?php esc_html_e('Please select at least one item.','wpvivid'); ?>');
                }
                else
                {
                    var descript = '<?php esc_html_e('Are you sure to remove the selected backups? These backups will be deleted permanently from your hosting (localhost).', 'wpvivid'); ?>';

                    var ret = confirm(descript);
                    if (ret === true)
                    {
                        var ajax_data = {
                            'action': 'mwp_wpvivid_delete_local_backup_array_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'backup_id': delete_backup_array,
                            'folder': folder,
                            'page':page
                        };

                        mwp_wpvivid_post_request(ajax_data, function (data) {
                            try {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success') {
                                    jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                                }
                                else if(jsonarray.result === 'failed') {
                                    alert(jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            });

            jQuery(document).ready(function($) {
                jQuery(document).on('mwp_wpvivid_update_local_backup', function(event)
                {
                    jQuery('#mwp_wpvivid_select_local_backup_folder').val('wpvivid');
                    mwp_wpvivid_get_local_backup_folder();
                });
                mwp_wpvivid_get_local_backup_folder();
            });
        </script>
        <?php
    }

    public function output_remote_ex(){
        $remoteslist=$this->remote_addon['upload'];
        $has_remote = false;
        $path = 'Common';
        $remote_storage_option = '';
        $remote_array = array();
        $first_remote_path = '';
        foreach ($remoteslist as $key => $value)
        {
            if($key === 'remote_selected') {
                continue;
            }
            else{
                $has_remote = true;
                $value['type']=apply_filters('mwp_wpvivid_storage_provider_tran', $value['type']);
                $remote_storage_option.='<option value="'.$key.'">'.$value['type'].' -> '.$value['name'].'</option>';
                if(isset($value['custom_path']))
                {
                    $path = $value['path'].'wpvividbackuppro/'. $value['custom_path'];
                }
                else
                {
                    $path = $value['path'];
                }
                $remote_array[$key]['path'] = $path;
                if($first_remote_path === ''){
                    $first_remote_path = $path;
                }
            }
        }
        $path = $first_remote_path;
        ?>
        <div style="margin-top: 10px;">
            <?php
            if($has_remote) {
                ?>
                <div class="mwp-quickstart-storage-setting">
                    <div style="padding: 10px 0;">
                        <div class="mwp-wpvivid-font-right-space" style="float: left;">Current Folder Path:</div>
                        <div id="mwp_wpvivid_remote_folder" style="float: left;"><?php echo esc_html($path); ?></div>
                        <div style="clear: both;"></div>
                    </div>
                    <div style="clear: both;"></div>
                </div>
                <div style="clear: both;"></div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <div style="float: left;">
                        <div>Display all backups stored in account
                            <select id="mwp_wpvivid_select_remote_storage" onchange="mwp_wpvivid_select_remote_storage_folder();"><?php echo esc_html($remote_storage_option); ?></select> under
                            <select id="mwp_wpvivid_select_remote_folder" onchange="mwp_wpvivid_select_remote_storage_folder();">
                                <option value="Common"><?php echo esc_html($path); ?></option>
                                <option value="Migrate">Migration</option>
                                <option value="Rollback">Rollback</option>
                                <option value="Incremental">Incremental</option>
                            </select> folder.
                        </div>
                    </div>
                    <div style="float: left; margin-left: 5px; height: 30px; line-height: 30px;">
                        <a onclick="mwp_wpvivid_explanation_folders();" style="cursor: pointer;">Explanation about these
                            folders.</a>
                    </div>
                    <div style="clear: both;"></div>
                </div>

                <div class="mwp-wpvivid-click-popup mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_explanation_folders" style="display: none; padding: 0 0 0 10px;">
                    <ul>
                        <li><i id="mwp_wpvivid_explanation_backup_folder"><?php echo esc_html($path); ?></i> Folder is where the manual backups and scheduled backups are stored on your cloud storage. <a href="https://wpvivid.com/wpvivid-backup-pro-custom-backup-folder" target="_blank">Learn more</a></li>
                        <li><i>Migrate</i> Folder is where the backups for migration are stored on your cloud storage.<a href="https://wpvivid.com/wpvivid-backup-pro-migration-folder" target="_blank">Learn more</a></li>
                        <li><i id="mwp_wpvivid_explanation_rollback_folder"><?php echo esc_html($path); ?>/rollback</i> Folder is where the backups created before updating are stored. You can disable this feature in Settings. <a href="https://wpvivid.com/wpvivid-backup-pro-rollback-folder" target="_blank">Learn more</a></li>
                        <li><i id="mwp_wpvivid_explanation_incremental_folder"><?php echo esc_html($path); ?>/incremental</i> Folder is where the incremental backups are stored on your cloud storage.</li>
                    </ul>
                </div>
                <div style="clear: both;"></div>

                <div style="margin-bottom: 10px;">
                    <input class="ui green mini button" id="mwp_wpvivid_sync_remote_folder" type="button" value="Scan The Folder" onclick="mwp_wpvivid_select_remote_folder();" style="float: left;"/>
                    <div class="spinner" id="mwp_wpvivid_scanning_remote_folder" style="float: left;"></div>
                    <div style="clear: both;"></div>
                </div>
                <div class="mwp-wpvivid-remote-sync-error" style="display: none;"></div>
                <div style="clear: both;"></div>
                <?php
            }
            else{
                ?>
                <div class="mwp-quickstart-storage-setting mwp-wpvivid-block-bottom-space">
                    <div style="padding: 10px 0;">
                        <span style="margin-right: 0;">There is no remote storage available, please set it up first.</span>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="mwp-wpvivid-local-remote-backup-list" id="mwp_wpvivid_remote_backups_list"></div>
        </div>
        <script>
            var mwp_remote_folder = '';
            var mwp_incremental_remote_folder = '';
            var mwp_remote_list_array = {};
            <?php
            foreach ($remote_array as $key => $value) {
            ?>
            var key = '<?php echo esc_js($key); ?>';
            mwp_remote_list_array[key] = Array();
            mwp_remote_list_array[key]['path'] = '<?php echo esc_js($value['path']); ?>';
            <?php
            }
            ?>

            function mwp_wpvivid_select_remote_storage_folder(){
                var value = jQuery('#mwp_wpvivid_select_remote_folder').val();
                var remote_id = jQuery('#mwp_wpvivid_select_remote_storage').val();
                var common_folder = '';
                var rollback_folder = '';
                var incremental_folder = '';
                jQuery.each(mwp_remote_list_array, function(index, value){
                    if(remote_id === index){
                        common_folder = value.path;
                        rollback_folder = common_folder + "/rollback";
                        incremental_folder = common_folder + "/incremental";
                    }
                });
                jQuery('option[value=Common]').text(common_folder);
                jQuery('#mwp_wpvivid_explanation_backup_folder').html(common_folder);
                jQuery('#mwp_wpvivid_explanation_rollback_folder').html(rollback_folder);
                jQuery('#mwp_wpvivid_explanation_incremental_folder').html(incremental_folder);
                if(value === 'Common'){
                    jQuery('#mwp_wpvivid_remote_folder').html(common_folder);
                }
                else if(value === 'Staging'){
                    jQuery('#mwp_wpvivid_remote_folder').html('staging');
                }
                else if(value === 'Migrate'){
                    jQuery('#mwp_wpvivid_remote_folder').html('migrate');
                }
                else if(value === 'Rollback'){
                    jQuery('#mwp_wpvivid_remote_folder').html(rollback_folder);
                }
                else if(value === 'Incremental'){
                    jQuery('#mwp_wpvivid_remote_folder').html(incremental_folder);
                }
            }

            function mwp_wpvivid_explanation_folders(){
                if(jQuery('#mwp_wpvivid_explanation_folders').is(":hidden")) {
                    jQuery('#mwp_wpvivid_explanation_folders').show();
                }
                else{
                    jQuery('#mwp_wpvivid_explanation_folders').hide();
                }
            }

            function mwp_wpvivid_select_remote_folder(){
                mwp_wpvivid_get_remote_backup_folder();
            }

            function mwp_wpvivid_get_remote_backup_folder(page=0){
                if(page === 0){
                    var current_page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                    }
                }
                var remote_id = jQuery('#mwp_wpvivid_select_remote_storage').val();
                var remote_folder = jQuery('#mwp_wpvivid_select_remote_folder').val();
                var is_incremental = false;
                mwp_remote_folder=remote_folder;
                if(mwp_remote_folder === 'Incremental'){
                    is_incremental = true;
                    var ajax_data = {
                        'action': 'mwp_wpvivid_achieve_remote_backup_addon',
                        'remote_id': remote_id,
                        'folder': remote_folder,
                        'incremental_path': mwp_incremental_remote_folder,
                        'page':page
                    };
                }
                else{
                    var ajax_data = {
                        'action': 'mwp_wpvivid_achieve_remote_backup_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'remote_id': remote_id,
                        'folder': remote_folder,
                        'page':page
                    };
                }
                jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_scanning_remote_folder').addClass('is-active');
                jQuery('.mwp-wpvivid-remote-sync-error').hide();
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray !== null) {
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                            }
                            else {
                                jQuery('.mwp-wpvivid-remote-sync-error').show();
                                jQuery('.mwp-wpvivid-remote-sync-error').html(jsonarray.error);
                                jQuery('#mwp_wpvivid_remote_backups_list').html('');
                            }
                        }
                        else{
                            jQuery('#mwp_wpvivid_remote_backups_list').html('');
                        }
                    }
                    catch (err)
                    {
                        jQuery('.mwp-wpvivid-remote-sync-error').show();
                        jQuery('.mwp-wpvivid-remote-sync-error').html(err);
                        jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    jQuery('.mwp-wpvivid-remote-sync-error').show();
                    jQuery('.mwp-wpvivid-remote-sync-error').html(error_message);
                });
            }

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.first-page',function() {
                mwp_wpvivid_get_remote_backup_folder('first');
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_remote_backup_folder(page-1);
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_remote_backup_folder(page+1);
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.last-page',function() {
                mwp_wpvivid_get_remote_backup_folder('last');
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_remote_backup_folder(page);
                }
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-lock', function(){
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                if(Obj.hasClass('lock')) {
                    var lock=0;
                }
                else {
                    var lock=1;
                }
                var ajax_data= {
                    'action': 'mwp_wpvivid_set_remote_security_lock_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id,
                    'lock': lock
                };
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(lock)
                            {
                                Obj.addClass('lock');
                            }
                            else
                            {
                                Obj.removeClass('lock');
                            }
                            Obj.html(jsonarray.html);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-restore', function() {
                <?php
                $white_label_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
                $is_mu = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'is_mu', array());
                if(!$white_label_setting){
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                }
                else{
                    $slug = $white_label_setting['white_label_slug'];
                    $slug_page = strtolower($white_label_setting['white_label_slug']);
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                }
                ?>
                location.href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($this->site_id); ?>&location=<?php echo esc_html(base64_encode($location)); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-backuplist-delete-backup', function(){
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                var current_page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                var descript = '<?php esc_html_e('Are you sure to remove this backup? This backup will be deleted permanently from your hosting (localhost) and remote storages.', 'wpvivid'); ?>';
                var ret = confirm(descript);
                if(ret === true) {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_remote_backup_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'backup_id': backup_id,
                        'folder': mwp_remote_folder,
                        'page':page
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                            }
                            else if(jsonarray.result === 'failed') {
                                alert(jsonarray.error);
                            }
                        }
                        catch(err){
                            alert(err);
                        }

                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-delete-array', function(){
                var delete_backup_array = new Array();
                var count = 0;
                var current_page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                jQuery('#mwp_wpvivid_remote_backups_list .mwp-wpvivid-backup-row input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        delete_backup_array[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });
                if( count === 0 )
                {
                    alert('<?php esc_html_e('Please select at least one item.','wpvivid'); ?>');
                }
                else
                {
                    var descript = '<?php esc_html_e('Are you sure to remove the selected backups? These backups will be deleted permanently from your hosting (localhost).', 'wpvivid'); ?>';
                    var ret = confirm(descript);
                    if (ret === true)
                    {
                        var ajax_data = {
                            'action': 'mwp_wpvivid_delete_remote_backup_array_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'backup_id': delete_backup_array,
                            'folder': mwp_remote_folder,
                            'page':page
                        };

                        mwp_wpvivid_post_request(ajax_data, function (data) {
                            try {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success') {
                                    jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                                }
                                else if(jsonarray.result === 'failed') {
                                    alert(jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            });

            jQuery(document).ready(function($) {
                jQuery(document).on('mwp_wpvivid_update_remote_backup', function(event) {
                    mwp_wpvivid_get_remote_backup_folder();
                });
                mwp_wpvivid_get_remote_backup_folder();
            });
        </script>
        <?php
    }

    public function output_remote(){
        ?>
        <div style="margin-top: 10px;">
            <div id="mwp_wpvivid_achieve_remote_backup_step_1">
                <div class="spinner is-active" id="mwp_wpvivid_achieving_remote_backup_info" style="float: left;"></div>
                <div style="margin-top: 4px; float: left;">Archieving Remote Storage Info</div>
                <div style="clear: both;"></div>
            </div>
            <div id="mwp_wpvivid_achieve_remote_backup_error"></div>
            <div id="mwp_wpvivid_achieve_remote_backup_step_2">
                <div id="mwp_wpvivid_remote_list_part"></div>
                <div class="mwp-wpvivid-local-remote-backup-list mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_incremental_path_list"></div>
                <div class="mwp-wpvivid-local-remote-backup-list" id="mwp_wpvivid_remote_backups_list"></div>
            </div>
        </div>
        <script>
            var mwp_remote_folder = '';
            var mwp_incremental_remote_folder = '';
            var mwp_remote_list_array = {};

            function mwp_wpvivid_select_remote_storage_folder(){
                var value = jQuery('#mwp_wpvivid_select_remote_folder').val();
                var remote_id = jQuery('#mwp_wpvivid_select_remote_storage').val();
                var common_folder = '';
                var rollback_folder = '';
                var incremental_folder = '';
                jQuery.each(mwp_remote_list_array, function(index, value){
                    if(remote_id === index){
                        common_folder = value.path;
                        rollback_folder = common_folder + "/rollback";
                        incremental_folder = common_folder + "/incremental";
                    }
                });
                jQuery('option[value=Common]').text(common_folder);
                jQuery('#mwp_wpvivid_explanation_backup_folder').html(common_folder);
                jQuery('#mwp_wpvivid_explanation_rollback_folder').html(rollback_folder);
                jQuery('#mwp_wpvivid_explanation_incremental_folder').html(incremental_folder);
                jQuery('#mwp_wpvivid_incremental_path_list').hide();
                if(value === 'Common'){
                    jQuery('#mwp_wpvivid_remote_folder').html(common_folder);
                }
                else if(value === 'Staging'){
                    jQuery('#mwp_wpvivid_remote_folder').html('staging');
                }
                else if(value === 'Migrate'){
                    jQuery('#mwp_wpvivid_remote_folder').html('migrate');
                }
                else if(value === 'Rollback'){
                    jQuery('#mwp_wpvivid_remote_folder').html(rollback_folder);
                }
                else if(value === 'Incremental'){
                    jQuery('#mwp_wpvivid_remote_folder').html(incremental_folder);
                }
            }

            function mwp_wpvivid_explanation_folders(){
                if(jQuery('#mwp_wpvivid_explanation_folders').is(":hidden")) {
                    jQuery('#mwp_wpvivid_explanation_folders').show();
                }
                else{
                    jQuery('#mwp_wpvivid_explanation_folders').hide();
                }
            }

            function mwp_wpvivid_select_remote_folder(){
                mwp_wpvivid_get_remote_backup_folder();
            }

            function mwp_wpvivid_get_remote_backup_folder(page=0){
                var is_page_turn = true;
                if(page === 0){
                    is_page_turn = false;
                    var current_page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                    }
                }
                var remote_id = jQuery('#mwp_wpvivid_select_remote_storage').val();
                var remote_folder = jQuery('#mwp_wpvivid_select_remote_folder').val();
                var is_incremental = false;
                mwp_remote_folder=remote_folder;

                if(mwp_remote_folder === 'Incremental'){
                    is_incremental = true;
                    var ajax_data = {
                        'action': 'mwp_wpvivid_achieve_remote_backup_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'remote_id': remote_id,
                        'folder': remote_folder,
                        'incremental_path': mwp_incremental_remote_folder,
                        'page':page
                    };
                }
                else{
                    var ajax_data = {
                        'action': 'mwp_wpvivid_achieve_remote_backup_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'remote_id': remote_id,
                        'folder': remote_folder,
                        'page':page
                    };
                }

                jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_scanning_remote_folder').addClass('is-active');
                jQuery('.mwp-wpvivid-remote-sync-error').hide();
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray !== null) {
                            if (jsonarray.result === 'success') {
                                if(is_page_turn){
                                    jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                                }
                                else if(!is_incremental) {
                                    jQuery('#mwp_wpvivid_incremental_path_list').hide();
                                    jQuery('#mwp_wpvivid_incremental_path_list').html('');
                                    jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                                }
                                else{
                                    jQuery('#mwp_wpvivid_incremental_path_list').show();
                                    jQuery('#mwp_wpvivid_incremental_path_list').html(jsonarray.incremental_list);
                                    jQuery('#mwp_wpvivid_remote_backups_list').html('');
                                }
                            }
                            else {
                                jQuery('.mwp-wpvivid-remote-sync-error').show();
                                jQuery('.mwp-wpvivid-remote-sync-error').html(jsonarray.error);
                                jQuery('#mwp_wpvivid_remote_backups_list').html('');
                            }
                        }
                        else{
                            jQuery('#mwp_wpvivid_remote_backups_list').html('');
                        }
                    }
                    catch (err)
                    {
                        jQuery('.mwp-wpvivid-remote-sync-error').show();
                        jQuery('.mwp-wpvivid-remote-sync-error').html(err);
                        jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    jQuery('.mwp-wpvivid-remote-sync-error').show();
                    jQuery('.mwp-wpvivid-remote-sync-error').html(error_message);
                });
            }

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.first-page',function() {
                mwp_wpvivid_get_remote_backup_folder('first');
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_remote_backup_folder(page-1);
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_remote_backup_folder(page+1);
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("click",'.last-page',function() {
                mwp_wpvivid_get_remote_backup_folder('last');
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_remote_backup_folder(page);
                }
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-lock', function(){
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                if(Obj.hasClass('lock')) {
                    var lock=0;
                }
                else {
                    var lock=1;
                }
                var ajax_data= {
                    'action': 'mwp_wpvivid_set_remote_security_lock_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id,
                    'lock': lock
                };
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(lock)
                            {
                                Obj.addClass('lock');
                            }
                            else
                            {
                                Obj.removeClass('lock');
                            }
                            Obj.html(jsonarray.html);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-restore', function() {
                <?php
                $white_label_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
                $is_mu = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'is_mu', array());
                if(!$white_label_setting){
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                }
                else{
                    $slug = $white_label_setting['white_label_slug'];
                    $slug_page = strtolower($white_label_setting['white_label_slug']);
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                }
                ?>
                location.href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($this->site_id); ?>&location=<?php echo esc_html(base64_encode($location)); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-backuplist-delete-backup', function(){
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                var current_page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                var descript = '<?php esc_html_e('Are you sure to remove this backup? This backup will be deleted permanently from your hosting (localhost) and remote storages.', 'wpvivid'); ?>';
                var ret = confirm(descript);
                if(ret === true) {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_remote_backup_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'backup_id': backup_id,
                        'folder': mwp_remote_folder,
                        'page':page
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                            }
                            else if(jsonarray.result === 'failed') {
                                alert(jsonarray.error);
                            }
                        }
                        catch(err){
                            alert(err);
                        }

                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-delete-array', function(){
                var delete_backup_array = new Array();
                var count = 0;
                var current_page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_remote_backups_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                jQuery('#mwp_wpvivid_remote_backups_list .mwp-wpvivid-backup-row input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        delete_backup_array[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });
                if( count === 0 )
                {
                    alert('<?php esc_html_e('Please select at least one item.','wpvivid'); ?>');
                }
                else
                {
                    var descript = '<?php esc_html_e('Are you sure to remove the selected backups? These backups will be deleted permanently from your hosting (localhost).', 'wpvivid'); ?>';
                    var ret = confirm(descript);
                    if (ret === true)
                    {
                        var ajax_data = {
                            'action': 'mwp_wpvivid_delete_remote_backup_array_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'backup_id': delete_backup_array,
                            'folder': mwp_remote_folder,
                            'page':page
                        };

                        mwp_wpvivid_post_request(ajax_data, function (data) {
                            try {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success') {
                                    jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                                }
                                else if(jsonarray.result === 'failed') {
                                    alert(jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            });

            var mwp_wpvivid_get_remote_backup_info_retry_times = 0;
            function mwp_wpvivid_get_remote_backup_info_retry(error_msg){
                var need_retry_get_remote_backup_info = false;
                mwp_wpvivid_get_remote_backup_info_retry_times++;
                if(mwp_wpvivid_get_remote_backup_info_retry_times < 3){
                    need_retry_get_remote_backup_info = true;
                }
                if(need_retry_get_remote_backup_info){
                    setTimeout(function(){
                        mwp_wpvivid_get_remote_backup_info();
                    }, 3000);
                }
                else{
                    var refresh_btn = '<div>'+error_msg+'</div>' +
                        '<input class="ui green mini button" type="button" value="Retry" onclick="mwp_wpvivid_refresh_get_remote_backup_info();">';
                    jQuery('#mwp_wpvivid_achieve_remote_backup_step_1').hide();
                    jQuery('#mwp_wpvivid_achieve_remote_backup_step_2').hide();
                    jQuery('#mwp_wpvivid_achieve_remote_backup_error').show();
                    jQuery('#mwp_wpvivid_achieving_remote_backup_info').removeClass('is-active');
                    jQuery('#mwp_wpvivid_achieve_remote_backup_error').html(refresh_btn);
                }
            }

            function mwp_wpvivid_refresh_get_remote_backup_info(){
                mwp_wpvivid_get_remote_backup_info_retry_times = 0;
                mwp_wpvivid_get_remote_backup_info();
            }

            function mwp_wpvivid_get_remote_backup_info(){
                var ajax_data = {
                    'action': 'mwp_wpvivid_achieve_remote_backup_info_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_achieve_remote_backup_step_1').show();
                jQuery('#mwp_wpvivid_achieve_remote_backup_step_2').hide();
                jQuery('#mwp_wpvivid_achieve_remote_backup_error').hide();
                jQuery('#mwp_wpvivid_achieving_remote_backup_info').addClass('is-active');
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_achieve_remote_backup_step_1').hide();
                            jQuery('#mwp_wpvivid_achieve_remote_backup_step_2').show();
                            jQuery('#mwp_wpvivid_achieve_remote_backup_error').hide();
                            jQuery('#mwp_wpvivid_achieving_remote_backup_info').removeClass('is-active');
                            jQuery('#mwp_wpvivid_remote_list_part').html(jsonarray.remote_part_html);
                            jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.select_list_html);
                            jQuery.each(jsonarray.remote_list, function(key, value){
                                if(key !== 'remote_selected') {
                                    mwp_remote_list_array[key] = Array();
                                    if(typeof value.custom_path !== 'undefined'){
                                        if(typeof value.root_path !== 'undefined'){
                                            var path = value.path + value.root_path + value.custom_path;
                                        }
                                        else {
                                            var path = value.path + 'wpvividbackuppro/' + value.custom_path;
                                        }
                                    }
                                    else{
                                        var path = value.path;
                                    }
                                    mwp_remote_list_array[key]['path'] = path;
                                }
                            });
                        }
                        else {
                            mwp_wpvivid_get_remote_backup_info_retry(jsonarray.error);
                        }
                    }
                    catch (err) {
                        mwp_wpvivid_get_remote_backup_info_retry(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    mwp_wpvivid_get_remote_backup_info_retry(error_message);
                });
            }

            jQuery('#mwp_wpvivid_incremental_path_list').on('click', '.first-page', function(){
                mwp_wpvivid_archieve_incremental_remote_folder_list('first')
            });

            jQuery('#mwp_wpvivid_incremental_path_list').on('click', '.prev-page', function(){
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_archieve_incremental_remote_folder_list(page-1);
            });

            jQuery('#mwp_wpvivid_incremental_path_list').on('click', '.next-page', function(){
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_archieve_incremental_remote_folder_list(page+1);
            });

            jQuery('#mwp_wpvivid_incremental_path_list').on('click', '.last-page', function(){
                mwp_wpvivid_archieve_incremental_remote_folder_list('last');
            });

            jQuery('#mwp_wpvivid_incremental_path_list').on('keypress', '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_archieve_incremental_remote_folder_list(page);
                }
            });

            function mwp_wpvivid_archieve_incremental_remote_folder_list(page=0){
                if(page === 0){
                    var current_page = jQuery('#mwp_wpvivid_incremental_path_list').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_incremental_path_list').find('.current-page').val();
                    }
                }
                var remote_id = jQuery('#mwp_wpvivid_select_remote_storage').val();
                var remote_folder = 'Common';
                var ajax_data = {
                    'action': 'mwp_wpvivid_archieve_incremental_remote_folder_list_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'remote_id': remote_id,
                    'folder': remote_folder,
                    'page':page
                };
                jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_scanning_remote_folder').addClass('is-active');
                jQuery('.mwp-wpvivid-remote-sync-error').hide();
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray !== null) {
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_incremental_path_list').show();
                                jQuery('#mwp_wpvivid_incremental_path_list').html(jsonarray.incremental_list);
                                jQuery('#mwp_wpvivid_remote_backups_list').html('');
                            }
                            else {
                                jQuery('.mwp-wpvivid-remote-sync-error').show();
                                jQuery('.mwp-wpvivid-remote-sync-error').html(jsonarray.error);
                                jQuery('#mwp_wpvivid_remote_backups_list').html('');
                            }
                        }
                        else{
                            jQuery('#mwp_wpvivid_remote_backups_list').html('');
                        }
                    }
                    catch (err)
                    {
                        jQuery('.mwp-wpvivid-remote-sync-error').show();
                        jQuery('.mwp-wpvivid-remote-sync-error').html(err);
                        jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    jQuery('.mwp-wpvivid-remote-sync-error').show();
                    jQuery('.mwp-wpvivid-remote-sync-error').html(error_message);
                });
            }

            jQuery('#mwp_wpvivid_incremental_path_list').on('click', '.mwp-wpvivid-incremental-child', function(){
                var incremental_path = jQuery(this).closest('tr').attr('id');
                var remote_id = jQuery('#mwp_wpvivid_select_remote_storage').val();
                mwp_incremental_remote_folder = incremental_path;

                var ajax_data = {
                    'action': 'mwp_wpvivid_achieve_incremental_child_path_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'remote_id': remote_id,
                    'incremental_path': incremental_path
                };
                jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('.mwp-wpvivid-incremental-child').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_scanning_remote_folder').addClass('is-active');
                jQuery('.mwp-wpvivid-remote-sync-error').hide();

                mwp_wpvivid_post_request(ajax_data, function (data){
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_remote_backups_list').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('.mwp-wpvivid-incremental-child').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if(jsonarray !== null) {
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_remote_backups_list').html(jsonarray.html);
                            }
                            else {
                                jQuery('.mwp-wpvivid-remote-sync-error').show();
                                jQuery('.mwp-wpvivid-remote-sync-error').html(jsonarray.error);
                                jQuery('#mwp_wpvivid_remote_backups_list').html('');
                            }
                        }
                        else{
                            jQuery('#mwp_wpvivid_remote_backups_list').html('');
                        }
                    }
                    catch (err)
                    {
                        jQuery('.mwp-wpvivid-remote-sync-error').show();
                        jQuery('.mwp-wpvivid-remote-sync-error').html(err);
                        jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_remote_backups_list').html('');
                    jQuery('#mwp_wpvivid_sync_remote_folder').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('.mwp-wpvivid-incremental-child').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_scanning_remote_folder').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    jQuery('.mwp-wpvivid-remote-sync-error').show();
                    jQuery('.mwp-wpvivid-remote-sync-error').html(error_message);
                });
            });

            jQuery(document).ready(function($) {
                jQuery(document).on('mwp_wpvivid_update_remote_backup', function(event) {
                    mwp_wpvivid_get_remote_backup_folder();
                });
                mwp_wpvivid_get_remote_backup_info();
            });
        </script>
        <?php
    }

    public function output_all_list()
    {
        ?>
        <div id="mwp_wpvivid_achieve_all_backup_step_1">
            <div class="spinner is-active" id="mwp_wpvivid_achieving_all_backup_info" style="float: left;"></div>
            <div style="margin-top: 4px; float: left;">Archieving Remote Storage Info</div>
            <div style="clear: both;"></div>
        </div>
        <div id="mwp_wpvivid_achieve_all_backup_error"></div>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <div>
                    <div id="mwp_wpvivid_achieve_all_backup_step_2">
                        <!--<div id="mwp_wpvivid_select_backup_storage_part"></div>
                        <div class="mwp-wpvivid-local-remote-backup-list mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_incremental_path_list"></div>
                        <div class="mwp-wpvivid-local-remote-backup-list" id="mwp_wpvivid_remote_backups_list"></div>-->
                        <div>
                    <span style="float: left;">
                        <select id="mwp_wpvivid_select_backup_storage">
                            <option value="all_backups" selected="selected">All Backups</option>
                            <option value="localhost">Localhost</option>
                            <option value="cloud">Cloud Storage</option>
                        </select>
                    </span>

                            <span id="mwp_wpvivid_select_remote_storage_box" style="float: left;display: none">
                        <select id="mwp_wpvivid_select_remote_storage">
                        </select>
                    </span>

                            <span style="float: left;">
                        <select id="mwp_wpvivid_select_backup_folder">
                            <option value="all_backup" selected="selected">--</option>
                            <option value="Manual">Manual Backups</option>
                            <option value="Cron">Scheduled Backups</option>
                            <option id="mwp_wpvivid_uploaded_option" value="Upload">Uploaded Backups</option>
                            <option id="mwp_wpvivid_migrated_option" value="Migrate">Migrate Backups</option>
                            <option value="Rollback">Rollback backups</option>
                            <option value="Incremental">Incremental</option>
                        </select>
                    </span>

                            <span>
                        <input id="mwp_wpvivid_get_scan_backup" type="submit" class="button action top-action" value="Scan">
                    </span>
                            <span class="spinner is-active" id="mwp_wpvivid_backup_scanning" style="float: left; display: none;"></span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex mwp-wpvivid-tooltip-padding-top">
                        <div class="mwp-wpvivid-bottom">
                            <p>Choose the backups you want to browse.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                            <div style="clear: both;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tablenav-pages">
                <input type="submit" class="button-primary" id="mwp_wpvivid_rescan_local_folder_btn" value="Scan uploaded backup or received backup">
            </div>
            <br class="clear">
        </div>

        <div class="mwp-wpvivid-local-remote-backup-list mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_backup_list">
            <?php
            $all_backups=array();
            $table=new Mainwp_WPvivid_New_Backup_List();
            $table->set_backup_list($all_backups);
            $table->prepare_items();
            $table->display();
            ?>
        </div>

        <div>
            <input class="button-primary" id="mwp_wpvivid_delete_localhost_array" type="submit" value="Delete the selected backups">
        </div>

        <script>
            jQuery('#mwp_wpvivid_backup_list').on('click', 'thead tr td input', function()
            {
                mwp_wpvivid_control_backup_select(jQuery(this));
            });

            jQuery('#mwp_wpvivid_backup_list').on('click', 'tfoot tr td input', function()
            {
                mwp_wpvivid_control_backup_select(jQuery(this));
            });

            function mwp_wpvivid_control_backup_select(obj)
            {
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('#mwp_wpvivid_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#mwp_wpvivid_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', true);
                    });

                    jQuery('#mwp_wpvivid_backup_list tbody tr').each(function()
                    {
                        jQuery(this).children('th').each(function (j)
                        {
                            if (j == 0)
                            {
                                if(jQuery(this).parent().children('td').eq(0).find('.backuptime').find('span').eq(0).hasClass('dashicons-unlock'))
                                {
                                    jQuery(this).closest('tr').find('th input').prop('checked', true);
                                }
                                else
                                {
                                    jQuery(this).closest('tr').find('th input').prop('checked', false);
                                }
                            }
                        });
                    });
                }
                else
                {
                    jQuery('#mwp_wpvivid_backup_list thead tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#mwp_wpvivid_backup_list tfoot tr td input').each(function()
                    {
                        jQuery(this).prop('checked', false);
                    });

                    jQuery('#mwp_wpvivid_backup_list tbody tr').each(function ()
                    {
                        jQuery(this).children('th').each(function (j)
                        {
                            if (j == 0)
                            {
                                jQuery(this).find("input[type=checkbox]").prop('checked', false);
                            }
                        });
                    });
                }
            }

            jQuery("#mwp_wpvivid_select_backup_storage").change(function()
            {
                if(jQuery(this).val()=='cloud')
                {
                    jQuery("#mwp_wpvivid_select_remote_storage_box").show();
                    jQuery("#mwp_wpvivid_migrated_option").show();
                    jQuery("#mwp_wpvivid_uploaded_option").hide();

                    var backup_folder = jQuery('#mwp_wpvivid_select_backup_folder').val();
                    if(backup_folder=='uploaded')
                    {
                        jQuery('#mwp_wpvivid_select_backup_folder').val("all_backup").change();
                    }
                }
                else if(jQuery(this).val()=='localhost')
                {
                    jQuery("#mwp_wpvivid_select_remote_storage_box").hide();
                    jQuery("#mwp_wpvivid_migrated_option").hide();
                    jQuery("#mwp_wpvivid_uploaded_option").show();

                    var backup_folder = jQuery('#mwp_wpvivid_select_backup_folder').val();
                    if(backup_folder=='migrate')
                    {
                        jQuery('#mwp_wpvivid_select_backup_folder').val("all_backup").change();
                    }
                }
                else
                {
                    jQuery("#mwp_wpvivid_select_remote_storage_box").hide();
                    jQuery("#mwp_wpvivid_migrated_option").show();
                    jQuery("#mwp_wpvivid_uploaded_option").show();
                }
            });

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-restore', function()
            {
                <?php
                $white_label_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'white_label_setting', array());
                $is_mu = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option(sanitize_text_field($_GET['id']), 'is_mu', array());
                if(!$white_label_setting){
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page=wpvivid-backup-and-restore&wpvivid-restore-page-mainwp';
                    }
                }
                else{
                    $slug = $white_label_setting['white_label_slug'];
                    $slug_page = strtolower($white_label_setting['white_label_slug']);
                    if($is_mu)
                    {
                        $location = 'network/admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                    else
                    {
                        $location = 'admin.php?page='.$slug.'-backup-and-restore&'.$slug_page.'-restore-page-mainwp';
                    }
                }
                ?>
                location.href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo esc_html($this->site_id); ?>&location=<?php echo esc_html(base64_encode($location)); ?>&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>";
            });

            jQuery('#mwp_wpvivid_rescan_local_folder_btn').click(function()
            {
                mwp_wpvivid_rescan_local_folder();
            });

            function mwp_wpvivid_rescan_local_folder()
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_rescan_local_folder_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_rescan_local_folder_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                jQuery('#mwp_wpvivid_backup_scanning').addClass('is-active');
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_backup_scanning').removeClass('is-active');
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if(typeof jsonarray.incomplete_backup !== 'undefined' && jsonarray.incomplete_backup.length > 0)
                        {
                            var incomplete_count = jsonarray.incomplete_backup.length;
                            alert('Failed to scan '+incomplete_count+' backup zips, the zips can be corrupted during creation or download process. Please check the zips.');
                        }
                        jQuery( document ).trigger( 'mwp_wpvivid_update_upload_backup');
                    }
                    catch(err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_rescan_local_folder_btn').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_backup_scanning').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('scanning backup list', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-lock', function()
            {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                if(Obj.hasClass('dashicons-lock'))
                {
                    var lock=0;
                }
                else
                {
                    var lock=1;
                }
                var ajax_data= {
                    'action': 'mwp_wpvivid_set_security_lock_ex_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id,
                    'lock': lock
                };
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(lock)
                            {
                                Obj.removeClass('dashicons-unlock');
                                Obj.addClass('dashicons-lock');
                            }
                            else
                            {
                                Obj.removeClass('dashicons-lock');
                                Obj.addClass('dashicons-unlock');
                            }
                            Obj.html(jsonarray.html);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.mwp-backuplist-delete-backup',function()
            {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');

                var backup_storage = jQuery('#mwp_wpvivid_select_backup_storage').val();
                var backup_folder = jQuery('#mwp_wpvivid_select_backup_folder').val();
                var remote_storage = jQuery('#mwp_wpvivid_select_remote_storage').val();

                var current_page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                if(typeof current_page !== 'undefined') {
                    var page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                }
                else{
                    var page = 0;
                }
                var descript = '<?php esc_html_e('Are you sure to remove this backup? This backup will be deleted permanently from your hosting (localhost) and remote storages.', 'wpvivid'); ?>';
                var ret = confirm(descript);
                if(ret === true) {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_backup_ex_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>',
                        'backup_id': backup_id,
                        'backup_storage': backup_storage,
                        'backup_folder': backup_folder,
                        'remote_storage': remote_storage,
                        'page':page
                    };

                    mwp_wpvivid_post_request(ajax_data, function(data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                            }
                            else if(jsonarray.result === 'failed') {
                                alert(jsonarray.error);
                            }
                        }
                        catch(err){
                            alert(err);
                        }

                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#mwp_wpvivid_delete_localhost_array').click(function()
            {
                var delete_backup_array = new Array();
                var count = 0;

                var page =jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                var backup_storage = jQuery('#mwp_wpvivid_select_backup_storage').val();
                var backup_folder = jQuery('#mwp_wpvivid_select_backup_folder').val();
                var remote_storage = jQuery('#mwp_wpvivid_select_remote_storage').val();

                jQuery('#mwp_wpvivid_backup_list .mwp-wpvivid-backup-row input').each(function (i)
                {
                    if(jQuery(this).prop('checked'))
                    {
                        delete_backup_array[count] =jQuery(this).closest('tr').attr('id');
                        count++;
                    }
                });
                if( count === 0 )
                {
                    alert('<?php esc_html_e('Please select at least one item.','wpvivid'); ?>');
                }
                else
                {
                    var descript = '<?php esc_html_e('Are you sure to remove the selected backups? These backups will be deleted permanently.', 'wpvivid'); ?>';

                    var ret = confirm(descript);
                    if (ret === true)
                    {
                        var ajax_data = {
                            'action': 'mwp_wpvivid_delete_backup_array_ex_addon',
                            'site_id': '<?php echo esc_html($this->site_id); ?>',
                            'backup_id': delete_backup_array,
                            'backup_storage': backup_storage,
                            'backup_folder': backup_folder,
                            'remote_storage': remote_storage,
                            'page':page
                        };

                        jQuery('#mwp_wpvivid_delete_localhost_array').css({'pointer-events': 'none', 'opacity': '0.4'});
                        mwp_wpvivid_post_request(ajax_data, function (data)
                        {
                            jQuery('#mwp_wpvivid_delete_localhost_array').css({'pointer-events': 'auto', 'opacity': '1'});
                            try
                            {
                                var jsonarray = jQuery.parseJSON(data);
                                if (jsonarray.result === 'success')
                                {
                                    if(typeof jsonarray.continue !== 'undefined' && jsonarray.continue)
                                    {
                                        mwp_wpvivid_delete_backup_array_ex(delete_backup_array,backup_storage,backup_folder,remote_storage,page);
                                    }
                                    else
                                    {
                                        jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                                    }
                                }
                                else if(jsonarray.result === 'failed')
                                {
                                    alert(jsonarray.error);
                                }
                            }
                            catch(err){
                                alert(err);
                            }
                        }, function (XMLHttpRequest, textStatus, errorThrown) {
                            jQuery('#mwp_wpvivid_delete_localhost_array').css({'pointer-events': 'auto', 'opacity': '1'});
                            var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                            alert(error_message);
                        });
                    }
                }
            });

            function mwp_wpvivid_delete_backup_array_ex(delete_backup_array,backup_storage,backup_folder,remote_storage,page)
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_delete_backup_array_ex_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': delete_backup_array,
                    'backup_storage': backup_storage,
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage,
                    'page':page
                };
                jQuery('#mwp_wpvivid_delete_localhost_array').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_delete_localhost_array').css({'pointer-events': 'auto', 'opacity': '1'});
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            if(typeof jsonarray.continue !== 'undefined' && jsonarray.continue)
                            {
                                mwp_wpvivid_delete_backup_array_ex(delete_backup_array,backup_storage,backup_folder,remote_storage,page);
                            }
                            else
                            {
                                jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                            }
                        }
                        else if(jsonarray.result === 'failed')
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_delete_localhost_array').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('deleting the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_get_scan_backup').click(function()
            {
                var backup_storage = jQuery('#mwp_wpvivid_select_backup_storage').val();
                var remote_storage = jQuery('#mwp_wpvivid_select_remote_storage').val();
                if((backup_storage=='cloud')&&(remote_storage!='all_backup'))
                {
                    mwp_wpvivid_scan_remote_backup();
                }
                else
                {
                    mwp_wpvivid_get_all_backup_folder();
                }

            });

            function mwp_wpvivid_scan_remote_backup()
            {
                jQuery('#mwp_wpvivid_backup_scanning').show();

                jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'none', 'opacity': '0.4'});

                var remote_storage = jQuery('#mwp_wpvivid_select_remote_storage').val();
                var backup_folder = jQuery('#mwp_wpvivid_select_backup_folder').val();

                var ajax_data = {
                    'action': 'mwp_wpvivid_scan_remote_backup_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage
                };
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                            if(jsonarray.finished==false)
                            {
                                mwp_wpvivid_scan_remote_backup_continue();
                            }
                            else
                            {
                                jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#mwp_wpvivid_backup_scanning').hide();
                            }
                        }
                        else
                        {
                            jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#mwp_wpvivid_backup_scanning').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#mwp_wpvivid_backup_scanning').hide();
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_backup_scanning').hide();
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_scan_remote_backup_continue()
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_scan_remote_backup_continue_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                            if(jsonarray.finished==false)
                            {
                                mwp_wpvivid_scan_remote_backup_continue();
                            }
                            else
                            {
                                jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('#mwp_wpvivid_backup_scanning').hide();
                            }
                        }
                        else
                        {
                            jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                            jQuery('#mwp_wpvivid_backup_scanning').hide();
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                        jQuery('#mwp_wpvivid_backup_scanning').hide();
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_get_scan_backup').css({'pointer-events': 'auto', 'opacity': '1'});
                    jQuery('#mwp_wpvivid_backup_scanning').hide();
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_backup_list').on("click",'.first-page',function()
            {
                mwp_wpvivid_get_all_backup_folder('first');
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.prev-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_all_backup_folder(page-1);
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.next-page',function()
            {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_all_backup_folder(page+1);
            });

            jQuery('#mwp_wpvivid_backup_list').on("click",'.last-page',function()
            {
                mwp_wpvivid_get_all_backup_folder('last');
            });

            jQuery('#mwp_wpvivid_backup_list').on("keypress", '.current-page', function()
            {
                if(event.keyCode === 13)
                {
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_all_backup_folder(page);
                }
            });

            function mwp_wpvivid_get_all_backup_folder(page=0)
            {
                if(page==0)
                {
                    var current_page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_backup_list').find('.current-page').val();
                    }
                }

                var backup_storage = jQuery('#mwp_wpvivid_select_backup_storage').val();
                var backup_folder = jQuery('#mwp_wpvivid_select_backup_folder').val();
                var remote_storage = jQuery('#mwp_wpvivid_select_remote_storage').val();

                jQuery('#mwp_wpvivid_backup_scanning').show();

                var ajax_data = {
                    'action': 'mwp_wpvivid_achieve_backup_list_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_storage': backup_storage,
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage,
                    'page':page
                };
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    jQuery('#mwp_wpvivid_backup_scanning').hide();
                    jQuery('#mwp_wpvivid_backup_list').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                        }
                        else
                        {
                            mwp_wpvivid_get_remote_backup_info_retry(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        mwp_wpvivid_get_remote_backup_info_retry(err, backup_storage, backup_folder, remote_storage);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_backup_scanning').hide();
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    mwp_wpvivid_get_remote_backup_info_retry(error_message, backup_storage, backup_folder, remote_storage);
                });
            }

            var mwp_wpvivid_get_remote_backup_info_retry_times = 0;
            function mwp_wpvivid_get_remote_backup_info_retry(error_msg, backup_storage='all_backups', backup_folder='all_backup', remote_storage='all_backup'){
                var need_retry_get_remote_backup_info = false;
                mwp_wpvivid_get_remote_backup_info_retry_times++;
                if(mwp_wpvivid_get_remote_backup_info_retry_times < 3){
                    need_retry_get_remote_backup_info = true;
                }
                if(need_retry_get_remote_backup_info){
                    setTimeout(function(){
                        mwp_wpvivid_init_get_all_backup_folder();
                    }, 3000);
                }
                else{
                    var refresh_btn = '<div>'+error_msg+'</div>' +
                        '<input class="ui green mini button" type="button" value="Retry" onclick="mwp_wpvivid_refresh_get_remote_backup_info(\''+backup_storage+'\', \''+backup_folder+'\', \''+remote_storage+'\');">';
                    jQuery('#mwp_wpvivid_achieve_all_backup_step_1').hide();
                    jQuery('#mwp_wpvivid_achieve_all_backup_error').show();
                    jQuery('#mwp_wpvivid_achieving_all_backup_info').removeClass('is-active');
                    jQuery('#mwp_wpvivid_achieve_all_backup_error').html(refresh_btn);
                }
            }

            function mwp_wpvivid_refresh_get_remote_backup_info(backup_storage='all_backups', backup_folder='all_backup', remote_storage='all_backup'){
                mwp_wpvivid_get_remote_backup_info_retry_times = 0;
                mwp_wpvivid_init_get_all_backup_folder(backup_storage, backup_folder, remote_storage);
            }

            function mwp_wpvivid_init_get_all_backup_folder(backup_storage='all_backups', backup_folder='all_backup', remote_storage='all_backup')
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_achieve_backup_list_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_storage': backup_storage,
                    'backup_folder': backup_folder,
                    'remote_storage': remote_storage,
                    'get_remote_storage': true
                };
                jQuery('#mwp_wpvivid_achieve_all_backup_step_1').show();
                jQuery('#mwp_wpvivid_achieve_all_backup_error').hide();
                jQuery('#mwp_wpvivid_achieving_all_backup_info').addClass('is-active');
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_achieve_all_backup_step_1').hide();
                            jQuery('#mwp_wpvivid_backup_list').html(jsonarray.html);
                            jQuery('#mwp_wpvivid_select_remote_storage').html(jsonarray.remote_list_html);
                        }
                        else {
                            mwp_wpvivid_get_remote_backup_info_retry(jsonarray.error, backup_storage, backup_folder, remote_storage);
                        }
                    }
                    catch (err) {
                        mwp_wpvivid_get_remote_backup_info_retry(err, backup_storage, backup_folder, remote_storage);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('achieving backup', textStatus, errorThrown);
                    mwp_wpvivid_get_remote_backup_info_retry(error_message, backup_storage, backup_folder, remote_storage);
                });
            }

            jQuery(document).ready(function($)
            {
                jQuery(document).on('mwp_wpvivid_update_upload_backup', function(event)
                {
                    jQuery('#mwp_wpvivid_select_backup_storage').val('localhost');
                    jQuery('#mwp_wpvivid_select_backup_folder').val('Upload');
                    mwp_wpvivid_get_all_backup_folder();
                });

                jQuery(document).on('mwp_wpvivid_update_local_backup', function(event)
                {
                    //jQuery('#mwp_wpvivid_select_backup_storage').val('localhost');
                    //jQuery('#mwp_wpvivid_select_backup_folder').val('Upload');
                    mwp_wpvivid_get_all_backup_folder();
                });

                jQuery(document).on('mwp_wpvivid_update_remote_backup', function(event)
                {
                    //jQuery('#mwp_wpvivid_select_backup_storage').val('localhost');
                    //jQuery('#mwp_wpvivid_select_backup_folder').val('Upload');
                    mwp_wpvivid_get_all_backup_folder();
                });

                //jQuery(document).on('mwp_wpvivid_update_all_backup', function(event) {
                    //mwp_wpvivid_get_all_backup_folder();
                //});
                mwp_wpvivid_init_get_all_backup_folder();
            });
        </script>
        <?php
    }

    public function output_download(){
        ?>
        <div style="margin-top: 10px;">
            <div id="mwp_wpvivid_init_download_info">
                <div style="float: left; height: 20px; line-height: 20px; margin-top: 4px;">Initializing the download info</div>
                <div class="spinner" style="float: left;"></div>
                <div style="clear: both;"></div>
            </div>
            <div id="mwp_wpvivid_files_list">
            </div>
        </div>
        <script>
            var mwp_wpvivid_download_files_list = {};
            mwp_wpvivid_download_files_list.backup_id='';
            mwp_wpvivid_download_files_list.wpvivid_download_file_array = Array();
            mwp_wpvivid_download_files_list.wpvivid_download_lock_array = Array();

            mwp_wpvivid_download_files_list.init=function(backup_id)
            {
                mwp_wpvivid_download_files_list.backup_id=backup_id;
                mwp_wpvivid_download_files_list.wpvivid_download_file_array.splice(0, mwp_wpvivid_download_files_list.wpvivid_download_file_array.length);
            };

            mwp_wpvivid_download_files_list.add_download_queue=function(filename)
            {
                var download_file_size = jQuery("[slug='"+filename+"']").find('.mwp-wpvivid-download-status').find('.mwp-wpvivid-download-file-size').html();
                var tmp_html = '<div class="mwp-wpvivid-block-bottom-space">' +
                    '<span class="mwp-wpvivid-block-right-space">Retriving (remote storage to web server)</span><span class="mwp-wpvivid-block-right-space">|</span><span>File Size: </span><span class="mwp-wpvivid-block-right-space">'+download_file_size+'</span><span class="mwp-wpvivid-block-right-space">|</span><span>Downloaded Size: </span><span>0</span>' +
                    '</div>' +
                    '<div style="width:100%;height:10px; background-color:#dcdcdc;">' +
                    '<div style="background-color:#0085ba; float:left;width:0%;height:10px;"></div>' +
                    '</div>';
                jQuery("[slug='"+filename+"']").find('.mwp-wpvivid-download-status').html(tmp_html);
                if(jQuery.inArray(filename, mwp_wpvivid_download_files_list.wpvivid_download_file_array) === -1) {
                    mwp_wpvivid_download_files_list.wpvivid_download_file_array.push(filename);
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_prepare_download_backup_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id':mwp_wpvivid_download_files_list.backup_id,
                    'file_name':filename
                };
                mwp_wpvivid_post_request(ajax_data, function(data)
                {
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                }, 0);

                mwp_wpvivid_download_files_list.check_queue();
            };

            mwp_wpvivid_download_files_list.check_queue=function()
            {
                if(jQuery.inArray(mwp_wpvivid_download_files_list.backup_id, mwp_wpvivid_download_files_list.wpvivid_download_lock_array) !== -1){
                    return;
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_download_progress_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': mwp_wpvivid_download_files_list.backup_id
                };
                mwp_wpvivid_download_files_list.wpvivid_download_lock_array.push(mwp_wpvivid_download_files_list.backup_id);
                mwp_wpvivid_post_request(ajax_data, function(data){
                    mwp_wpvivid_download_files_list.wpvivid_download_lock_array.splice(jQuery.inArray(mwp_wpvivid_download_files_list.backup_id, mwp_wpvivid_download_files_list.wpvivid_download_file_array),1);
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success') {
                        jQuery.each(jsonarray.files,function (index, value) {
                            if(jQuery.inArray(index, mwp_wpvivid_download_files_list.wpvivid_download_file_array) !== -1) {
                                if(value.status === 'timeout' || value.status === 'completed' || value.status === 'error'){
                                    mwp_wpvivid_download_files_list.wpvivid_download_file_array.splice(jQuery.inArray(index, mwp_wpvivid_download_files_list.wpvivid_download_file_array),1);
                                }
                                mwp_wpvivid_download_files_list.update_item(index, value);
                            }
                        });
                        if(mwp_wpvivid_download_files_list.wpvivid_download_file_array.length > 0) {
                            setTimeout(function() {
                                mwp_wpvivid_download_files_list.check_queue();
                            }, 3000);
                        }
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown){
                    mwp_wpvivid_download_files_list.wpvivid_download_lock_array.splice(jQuery.inArray(mwp_wpvivid_download_files_list.backup_id, mwp_wpvivid_download_files_list.wpvivid_download_file_array), 1);
                    setTimeout(function() {
                        mwp_wpvivid_download_files_list.check_queue();
                    }, 3000);
                });
            };

            mwp_wpvivid_download_files_list.update_item=function(index,file)
            {
                jQuery("[slug='"+index+"']").find('.mwp-wpvivid-download-status').html(file.html);
            };

            function mwp_wpvivid_init_download_page(backup_id,list_from){
                jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-show',[ 'download', list_from ]);
                var ajax_data = {
                    'action': 'mwp_wpvivid_init_download_page_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id
                };
                jQuery('#mwp_wpvivid_files_list').html('');
                jQuery('#mwp_wpvivid_init_download_info').show();
                jQuery('#mwp_wpvivid_init_download_info').find('.spinner').addClass('is-active');
                var retry = '<input type="button" class="ui green mini button" value="Retry the initialization" onclick="mwp_wpvivid_init_download_page(\''+backup_id+'\', \''+list_from+'\');" />';
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_init_download_info').hide();
                    jQuery('#mwp_wpvivid_init_download_info').find('.spinner').removeClass('is-active');
                    try{
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            mwp_wpvivid_download_files_list.init(backup_id);
                            var need_check_queue = false;
                            jQuery.each(jsonarray.files, function (index, value)
                            {
                                if(value.status === 'running'){
                                    if(jQuery.inArray(index, mwp_wpvivid_download_files_list.wpvivid_download_file_array) === -1) {
                                        mwp_wpvivid_download_files_list.wpvivid_download_file_array.push(index);
                                        need_check_queue = true;
                                    }
                                }
                            });
                            if(need_check_queue) {
                                mwp_wpvivid_download_files_list.check_queue();
                            }
                            jQuery('#mwp_wpvivid_files_list').html(jsonarray.html);
                        }
                        else{
                            alert(jsonarray.error);
                            jQuery('#mwp_wpvivid_files_list').html(retry);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        jQuery('#mwp_wpvivid_files_list').html(retry);
                    }

                },function(XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#mwp_wpvivid_init_download_info').hide();
                    jQuery('#mwp_wpvivid_init_download_info').find('.spinner').removeClass('is-active');
                    var error_message = mwp_wpvivid_output_ajaxerror('initializing download information', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#mwp_wpvivid_files_list').html(retry);
                });
            }

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-download', function() {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                mwp_wpvivid_init_download_page(backup_id,'all_list');
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-download', function() {
                var Obj=jQuery(this);
                var backup_id=Obj.closest('tr').attr('id');
                mwp_wpvivid_init_download_page(backup_id,'all_list');
            });

            jQuery('#mwp_wpvivid_files_list').on('click', '.mwp-wpvivid-prepare-download', function(){
                var Obj=jQuery(this);
                var file_name=Obj.closest('tr').attr('slug');
                mwp_wpvivid_download_files_list.add_download_queue(file_name);
            });

            jQuery('#mwp_wpvivid_files_list').on('click', '.mwp-wpvivid-ready-download', function(){
                var Obj=jQuery(this);
                var file_name=Obj.closest('tr').attr('slug');
                var loc = 'admin-ajax.php?backup_id='+mwp_wpvivid_download_files_list.backup_id+'&file_name='+file_name+'&action=wpvivid_download_backup_addon_mainwp';
                var url =  mwp_wpvivid_get_donwnloadlink_ex(site_id, loc);
                var win=window.open(url, '_blank');
                setTimeout(function(){
                    win.close();
                }, 5000);
            });

            function mwp_wpvivid_get_donwnloadlink_ex(site_id, location ) {
                return 'admin.php?page=SiteOpen&websiteid=' + site_id + '&location=' + window.btoa(location) + '&_opennonce=<?php echo esc_html(wp_create_nonce( 'mainwp-admin-nonce' )); ?>';
            }

            function wpvivid_get_backup_addon_list(page=0){
                var backup_id = mwp_wpvivid_download_files_list.backup_id;
                if(page==0) {
                    page =jQuery('#mwp_wpvivid_files_list').find('.current-page').val();
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_backup_addon_list',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'backup_id': backup_id,
                    'page':page
                };
                mwp_wpvivid_post_request(ajax_data, function (data){
                    try{
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_files_list').html(jsonarray.html);
                        }
                        else if (jsonarray.result === 'failed') {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = mwp_wpvivid_output_ajaxerror('adding the remote storage', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_files_list').on("click",'.first-page',function() {
                wpvivid_get_backup_addon_list('first');
            });

            jQuery('#mwp_wpvivid_files_list').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_backup_addon_list(page-1);
            });

            jQuery('#mwp_wpvivid_files_list').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                wpvivid_get_backup_addon_list(page+1);
            });

            jQuery('#mwp_wpvivid_files_list').on("click",'.last-page',function() {
                wpvivid_get_backup_addon_list('last');
            });

            jQuery('#mwp_wpvivid_files_list').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    wpvivid_get_backup_addon_list(page);
                }
            });
        </script>
        <?php
    }

    public function output_log(){
        ?>
        <div class="postbox mwp-restore_log" id="mwp_wpvivid_read_log_content"></div>
        <script>
            function mwp_wpvivid_backup_open_log(log, list_from){
                var ajax_data = {
                    'action':'mwp_wpvivid_view_log_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'log': log
                };
                jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-show',[ 'log', list_from ]);
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    jQuery('#mwp_wpvivid_read_log_content').html("");
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === "success")
                        {
                            var log_data = jsonarray.data;
                            while (log_data.indexOf('\n') >= 0)
                            {
                                var iLength = log_data.indexOf('\n');
                                var log = log_data.substring(0, iLength);
                                log_data = log_data.substring(iLength + 1);
                                var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                                jQuery('#mwp_wpvivid_read_log_content').append(insert_log);
                            }
                        }
                        else
                        {
                            jQuery('#mwp_wpvivid_read_log_content').html(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                        var div = "Reading the log failed. Please try again.";
                        jQuery('#mwp_wpvivid_read_log_content').html(div);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('export the previously-exported settings', textStatus, errorThrown);
                    jQuery('#mwp_wpvivid_read_log_content').html(error_message);
                });
            }

            jQuery('#mwp_wpvivid_backup_list').on('click', '.mwp-wpvivid-log', function() {
                var Obj=jQuery(this);
                var log=Obj.attr('name');
                mwp_wpvivid_backup_open_log(log,'localhost_list');
            });

            jQuery('#mwp_wpvivid_remote_backups_list').on('click', '.mwp-wpvivid-log', function() {
                var Obj=jQuery(this);
                var log=Obj.attr('name');
                mwp_wpvivid_backup_open_log(log,'remote_list');
            });
        </script>
        <?php
    }
}