<?php

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Mainwp_WPvivid_Schedule_List extends WP_List_Table
{
    public $page_num;
    public $schedule_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'schedule',
                'screen' => 'schedule',
            )
        );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb']))
        {
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
        $columns['wpvivid_backup_type'] = __( 'Backup Type', 'wpvivid'  );
        $columns['wpvivid_backup_cycles'] = __( 'Backup Cycles', 'wpvivid'  );
        $columns['wpvivid_last_backup'] = __( 'Last Backup', 'wpvivid'  );
        $columns['wpvivid_next_backup'] = __( 'Next Backup', 'wpvivid'  );
        $columns['wpvivid_storage'] = __( 'Storage', 'wpvivid'  );
        $columns['wpvivid_on_off_control'] = __( 'On/off', 'wpvivid'  );
        $columns['wpvivid_actions'] = __( 'Actions', 'wpvivid'  );
        return $columns;
    }

    public function set_schedule_list($schedule_list,$page_num=1)
    {
        $this->schedule_list=$schedule_list;
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

        $total_items =sizeof($this->schedule_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->schedule_list);
    }

    public function  column_cb( $schedule )
    {
        if ($schedule['status'] == 'Active')
        {
            echo '<input type="checkbox" checked/>';
        } else {
            echo '<input type="checkbox"/>';
        }
    }

    public function _column_wpvivid_backup_type( $schedule )
    {
        if (isset($schedule['backup']['backup_files']))
        {
            $backup_type = $schedule['backup']['backup_files'];
            if ($backup_type === 'files+db')
            {
                $backup_type = 'Database + Files (WordPress Files)';
            } else if ($backup_type === 'files')
            {
                $backup_type = 'WordPress Files (Exclude Database)';
            } else if ($backup_type === 'db')
            {
                $backup_type = 'Only Database';
            }
        } else {
            $backup_type = 'Custom';
        }

        echo '<td>'.esc_html($backup_type).'</td>';
    }

    public function _column_wpvivid_backup_cycles( $schedule )
    {
        if (!isset($schedule['week']))
        {
            $schedule['week'] = 'N/A';
        }
        $schedule_type = $schedule['schedule_cycles'];
        echo '<td class="'.esc_attr($schedule['type']).'">'.esc_html($schedule_type).'</td>';
    }

    public function _column_wpvivid_last_backup( $schedule )
    {
        $last_backup_time = $schedule['last_backup_time'];
        echo '<td>'.esc_html($last_backup_time).'</td>';
    }

    public function _column_wpvivid_next_backup( $schedule )
    {
        $next_start = $schedule['next_start_time'];
        echo '<td>'.esc_html($next_start).'</td>';
    }

    public function _column_wpvivid_storage( $schedule )
    {
        if (isset($schedule['backup']['local']))
        {
            if ($schedule['backup']['local'] == '1')
            {
                $backup_to = 'Localhost';
            } else {
                $backup_to = 'Remote';
            }
        } else {
            $backup_to = 'Localhost';
        }
        echo '<td>'.esc_html($backup_to).'</td>';
    }

    public function _column_wpvivid_on_off_control( $schedule )
    {
        if($schedule['status'] === 'Active')
        {
            $style = 'checked';
        }
        else
        {
            $style = '';
        }
        echo '<td>
                    <label class="mwp-wpvivid-switch" title="Enable/Disable the job">
                        <input class="mwp-wpvivid-schedule-on-off-control" type="checkbox" '.esc_attr($style).'>
						<span class="mwp-wpvivid-slider mwp-wpvivid-round"></span>
				    </label>
               </td>';
    }

    public function _column_wpvivid_actions( $schedule )
    {
        echo '<td>
                    <div>
                         <img class="mwp-wpvivid-schedule-edit" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Edit.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Edit the schedule" name="'.esc_attr(wp_json_encode($schedule)).'" />                    
                         <img class="mwp-wpvivid-schedule-delete" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Delete.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Delete the schedule" />                    
                     </div>
                </td>';
    }

    public function _column_wpvivid_status( $schedule )
    {
        echo '<td class="mwp-wpvivid-schedule-status">'.esc_html($schedule['status']).'</td>';
    }

    public function display_rows()
    {
        $this->_display_rows( $this->schedule_list );
    }

    private function _display_rows($schedule_list)
    {
        $page=$this->get_pagenum();

        $page_schedule_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_schedule_list = array_splice( $schedule_list, 0, 10);
            $count++;
        }
        foreach ( $page_schedule_list as $schedule)
        {
            $this->single_row($schedule);
        }
    }

    public function single_row($schedule)
    {
        $class='schedule-item';
        ?>
        <tr class="<?php echo esc_attr($class);?>" slug="<?php echo esc_attr($schedule['id']);?>">
            <?php $this->single_row_columns( $schedule ); ?>
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
                "%s<input class='current-page' id='current-page-selector-schedule' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-schedule" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
        if ( $total_pages >1) {
            ?>
            <div class="tablenav <?php echo esc_attr($which); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav($which);
                $this->pagination($which);
                ?>

                <br class="clear"/>
            </div>
            <?php
        }
    }

    protected function get_table_classes()
    {
        return array( 'widefat plugin-install' );
    }
}

class Mainwp_WPvivid_Schedule_Global_List extends WP_List_Table
{
    public $page_num;
    public $schedule_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'schedule',
                'screen' => 'schedule',
            )
        );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb']))
        {
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
        $columns['wpvivid_backup_type'] = __( 'Backup Type', 'wpvivid'  );
        $columns['wpvivid_backup_cycles'] = __( 'Backup Cycles', 'wpvivid'  );
        $columns['wpvivid_start_time'] = __( 'Start Time', 'wpvivid'  );
        $columns['wpvivid_storage'] = __( 'Storage', 'wpvivid'  );
        $columns['wpvivid_on_off_control'] = __( 'On/off', 'wpvivid'  );
        $columns['wpvivid_actions'] = __( 'Actions', 'wpvivid'  );

        return $columns;
    }

    public function set_schedule_list($schedule_list,$page_num=1)
    {
        $this->schedule_list=$schedule_list;
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

        $total_items =sizeof($this->schedule_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->schedule_list);
    }

    public function  column_cb( $schedule )
    {
        if ($schedule['status'] == 'Active')
        {
            echo '<input type="checkbox" checked/>';
        } else {
            echo '<input type="checkbox"/>';
        }
    }

    public function _column_wpvivid_status( $schedule )
    {
        echo '<td class="mwp-wpvivid-schedule-status">'.esc_html($schedule['status']).'</td>';
    }

    public function _column_wpvivid_backup_cycles( $schedule )
    {
        if (!isset($schedule['week']))
        {
            $schedule['week'] = 'N/A';
        }
        //$schedule_type = $schedule['schedule_cycles'];
        $schedule_type = $schedule['type'];
        switch ($schedule_type){
            case 'wpvivid_hourly':
                $schedule_type = 'Every hour';
                break;
            case 'wpvivid_2hours':
                $schedule_type = 'Every 2 hours';
                break;
            case 'wpvivid_4hours':
                $schedule_type = 'Every 4 hours';
                break;
            case 'wpvivid_8hours':
                $schedule_type = 'Every 8 hours';
                break;
            case 'wpvivid_12hours':
                $schedule_type = 'Every 12 hours';
                break;
            case 'wpvivid_daily':
                $schedule_type = 'Daily';
                break;
            case 'wpvivid_weekly':
                $schedule_type = 'Weekly';
                break;
            case 'wpvivid_fortnightly':
                $schedule_type = 'Fortnightly';
                break;
            case 'wpvivid_monthly':
                $schedule_type = 'Monthly';
                break;
            default:
                $schedule_type = 'not found';
                break;
        }
        if ($schedule_type === 'Weekly') {
            if (isset($schedule['week'])) {
                if ($schedule['week'] === 'sun') {
                    $schedule_type = $schedule_type . '-Sunday';
                } else if ($schedule['week'] === 'mon') {
                    $schedule_type = $schedule_type . '-Monday';
                } else if ($schedule['week'] === 'tue') {
                    $schedule_type = $schedule_type . '-Tuesday';
                } else if ($schedule['week'] === 'wed') {
                    $schedule_type = $schedule_type . '-Wednesday';
                } else if ($schedule['week'] === 'thu') {
                    $schedule_type = $schedule_type . '-Thursday';
                } else if ($schedule['week'] === 'fri') {
                    $schedule_type = $schedule_type . '-Friday';
                } else if ($schedule['week'] === 'sat') {
                    $schedule_type = $schedule_type . '-Saturday';
                }
            }
        }

        echo '<td class="'.esc_attr($schedule['type']).'">'.esc_html($schedule_type).'</td>';
    }

    public function _column_wpvivid_start_time( $schedule ){
        echo '<td>'.esc_html($schedule['current_day']).'</td>';
    }

    public function _column_wpvivid_start_local_utc( $schedule ){
        if(isset($schedule['start_time_local_utc'])){
            $start_time_local_utc = $schedule['start_time_local_utc'];
            if($start_time_local_utc === 'local'){
                $start_time_local_utc = 'Local Time';
            }
            else{
                $start_time_local_utc = 'UTC Time';
            }
        }
        else{
            $start_time_local_utc = 'UTC Time';
        }
        echo '<td>'.esc_html($start_time_local_utc).'</td>';
    }

    public function _column_wpvivid_last_backup( $schedule )
    {
        if (isset($schedule['last_backup_time']))
        {
            $offset=get_option('gmt_offset');
            $localtime = $schedule['last_backup_time'] + $offset * 60 * 60;
            $last_backup_time = date("H:i:s - m/d/Y ", $schedule['last_backup_time']);
        } else {
            $last_backup_time = 'N/A';
        }
        //$last_backup_time = $schedule['last_backup_time'];
        echo '<td>'.esc_html($last_backup_time).'</td>';
    }

    public function _column_wpvivid_backup_type( $schedule )
    {
        if (isset($schedule['backup']['backup_files']))
        {
            $backup_type = $schedule['backup']['backup_files'];
            if ($backup_type === 'files+db')
            {
                $backup_type = 'Database + Files (WordPress Files)';
            } else if ($backup_type === 'files')
            {
                $backup_type = 'WordPress Files (Exclude Database)';
            } else if ($backup_type === 'db')
            {
                $backup_type = 'Only Database';
            }
        } else {
            $backup_type = 'Custom';
        }

        echo '<td>'.esc_html($backup_type).'</td>';
    }

    public function _column_wpvivid_storage( $schedule )
    {
        if (isset($schedule['backup']['local']))
        {
            if ($schedule['backup']['local'] == '1')
            {
                $backup_to = 'Localhost';
            } else {
                $backup_to = 'Remote';
            }
        } else {
            $backup_to = 'Localhost';
        }
        echo '<td>'.esc_html($backup_to).'</td>';
    }

    public function _column_wpvivid_on_off_control( $schedule )
    {
        if($schedule['status'] === 'Active')
        {
            $style = 'checked';
        }
        else
        {
            $style = '';
        }
        echo '<td>
                    <label class="mwp-wpvivid-switch" title="Enable/Disable the job">
                        <input class="mwp-wpvivid-schedule-on-off-control" type="checkbox" '.esc_attr($style).'>
						<span class="mwp-wpvivid-slider mwp-wpvivid-round"></span>
				    </label>
               </td>';
    }

    public function _column_wpvivid_actions( $schedule )
    {
        echo '<td>
                    <div>
                         <img class="mwp-wpvivid-schedule-edit" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Edit.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Edit the schedule" />                    
                         <img class="mwp-wpvivid-schedule-delete" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Delete.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Delete the schedule" />                    
                     </div>
                </td>';
    }

    public function display_rows()
    {
        $this->_display_rows( $this->schedule_list );
    }

    private function _display_rows($schedule_list)
    {
        $page=$this->get_pagenum();

        $page_schedule_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_schedule_list = array_splice( $schedule_list, 0, 10);
            $count++;
        }
        foreach ( $page_schedule_list as $schedule)
        {
            $this->single_row($schedule);
        }
    }

    public function single_row($schedule)
    {
        $class='schedule-item';
        ?>
        <tr class="<?php echo esc_attr($class);?>" slug="<?php echo esc_attr($schedule['id']);?>">
            <?php $this->single_row_columns( $schedule ); ?>
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
                "%s<input class='current-page' id='current-page-selector-schedule' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-schedule" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
        if ( $total_pages >1) {
            ?>
            <div class="tablenav <?php echo esc_attr($which); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav($which);
                $this->pagination($which);
                ?>

                <br class="clear"/>
            </div>
            <?php
        }
    }

    protected function get_table_classes()
    {
        return array( 'widefat plugin-install' );
    }
}

class Mainwp_WPvivid_Schedule_Mould_List extends WP_List_Table
{
    public $page_num;
    public $schedule_mould_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'schedule_mould',
                'screen' => 'schedule_mould',
            )
        );
    }

    public function print_column_headers( $with_id = true )
    {
        list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

        if (!empty($columns['cb']))
        {
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
        $columns['wpvivid_mould_name'] = __( 'Mould Name', 'wpvivid' );
        $columns['wpvivid_sync_mould'] = __( 'Sync Mould', 'wpvivid' );
        $columns['wpvivid_actions'] = __( 'Actions', 'wpvivid' );
        return $columns;
    }

    public function set_schedule_mould_list($schedule_mould_list,$page_num=1)
    {
        $this->schedule_mould_list=$schedule_mould_list;
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

        $total_items =sizeof($this->schedule_mould_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->schedule_mould_list);
    }

    public function _column_wpvivid_mould_name( $schedule_mould )
    {
        echo '<td><div>'.esc_html($schedule_mould['mould_name']).'</div></td>';
    }

    public function _column_wpvivid_sync_mould( $schedule_mould )
    {
        echo '<td><input class="ui green mini button mwp-wpvivid-sync-schedule-mould" type="button" value="Sync" /></td>';
    }

    public function _column_wpvivid_actions( $schedule_mould )
    {
        echo '<td>
                    <div>
                         <img class="mwp-wpvivid-schedule-mould-edit" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Edit.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Edit the schedule" />                    
                         <img class="mwp-wpvivid-schedule-mould-delete" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Delete.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Delete the schedule" />                    
                     </div>
                </td>';
    }

    public function display_rows()
    {
        $this->_display_rows( $this->schedule_mould_list );
    }

    private function _display_rows($schedule_mould_list)
    {
        foreach ($schedule_mould_list as $mould_name => $schedule_mould)
        {
            foreach ($schedule_mould_list[$mould_name] as $schedule_id => $schedule_value)
            {
                $schedule_mould_list[$mould_name][$schedule_id]['mould_name'] = $mould_name;
            }
        }

        $page=$this->get_pagenum();

        $page_schedule_mould_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_schedule_mould_list = array_splice( $schedule_mould_list, 0, 10);
            $count++;
        }
        foreach ( $page_schedule_mould_list as $mould_name => $schedule_mould)
        {
            foreach ($schedule_mould as $schedule_id => $schedule_value)
            {
                $mould_name = $schedule_value['mould_name'];
            }
            $schedule_mould['mould_name'] = $mould_name;
            $this->single_row($schedule_mould);
        }
    }

    public function single_row($schedule_mould)
    {
        ?>
        <tr slug="<?php echo esc_attr($schedule_mould['mould_name']);?>">
            <?php $this->single_row_columns( $schedule_mould ); ?>
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
                "%s<input class='current-page' id='current-page-selector-schedule' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-schedule" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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
        if ( $total_pages >1) {
            ?>
            <div class="tablenav <?php echo esc_attr($which); ?>" style="<?php echo esc_attr($css_type); ?>">
                <?php
                $this->extra_tablenav($which);
                $this->pagination($which);
                ?>

                <br class="clear"/>
            </div>
            <?php
        }
    }

    protected function get_table_classes()
    {
        return array( 'widefat plugin-install' );
    }
}

class Mainwp_WPvivid_Extension_SchedulePage
{
    private $setting;
    private $setting_addon;
    private $global_custom_setting;
    private $time_zone;
    private $select_pro;
    private $site_id;
    public $main_tab;

    public function __construct()
    {
        add_action('wp_ajax_mwp_wpvivid_sync_schedule', array($this, 'sync_schedule'));
        add_action('wp_ajax_mwp_wpvivid_set_schedule', array($this, 'set_schedule'));
        add_action('wp_ajax_mwp_wpvivid_set_global_schedule', array($this, 'set_global_schedule'));
        add_action('wp_ajax_mwp_wpvivid_get_schedules_addon', array($this, 'get_schedules_addon'));
        add_action('wp_ajax_mwp_wpvivid_create_schedule_addon', array($this, 'create_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_update_schedule_addon', array($this, 'update_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_schedule_addon', array($this, 'delete_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_edit_schedule_addon', array($this, 'edit_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_save_schedule_status_addon', array($this, 'save_schedule_status_addon'));
        add_action('wp_ajax_mwp_wpvivid_global_create_schedule_addon', array($this, 'global_create_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_edit_global_schedule_addon', array($this, 'edit_global_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_global_update_schedule_addon', array($this, 'global_update_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_global_delete_schedule_addon', array($this, 'global_delete_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_global_save_schedule_status_addon', array($this, 'global_save_schedule_status_addon'));
        add_action('wp_ajax_mwp_wpvivid_edit_global_schedule_mould_addon', array($this, 'edit_global_schedule_mould_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_global_schedule_mould_addon', array($this, 'delete_global_schedule_mould_addon'));
        add_action('wp_ajax_mwp_wpvivid_get_schedule_mould_list', array($this, 'get_schedule_mould_list'));
        add_action('wp_ajax_mwp_wpvivid_update_global_schedule_backup_exclude_extension_addon', array($this, 'update_global_schedule_backup_exclude_extension_addon'));
        add_action('wp_ajax_mwp_wpvivid_edit_global_schedule_mould_name_addon', array($this, 'edit_global_schedule_mould_name_addon'));
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_schedule_info($setting, $setting_addon=array(), $global_custom_setting=array(), $time_zone=0, $select_pro=0)
    {
        $this->setting=$setting;
        $this->setting_addon=$setting_addon;
        $this->global_custom_setting=$global_custom_setting;
        $this->select_pro=$select_pro;
        $this->time_zone=$time_zone;
    }

    public function sync_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['id']);
                $check_addon = '0';
                if(isset($_POST['addon']) && !empty($_POST['addon']) && is_string($_POST['addon'])) {
                    $check_addon = sanitize_text_field($_POST['addon']);
                }
                if($check_addon == '1'){
                    $schedule_mould_name = '';
                    if(isset($_POST['schedule_mould_name']) && !empty($_POST['schedule_mould_name'])){
                        $schedule_mould_name = sanitize_text_field($_POST['schedule_mould_name']);
                    }
                    $post_data['mwp_action'] = 'wpvivid_sync_schedule_addon_mainwp';
                    $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                    $schedules = $schedule_mould[$schedule_mould_name];
                    if(isset($_POST['default_setting'])){
                        $default_setting = sanitize_text_field($_POST['default_setting']);
                    }
                    else{
                        $default_setting = 'default_only';
                    }
                    $post_data['schedule'] = $schedules;
                    $post_data['default_setting'] = $default_setting;
                }
                else {
                    $post_data['mwp_action'] = 'wpvivid_set_schedule_mainwp';
                    $schedule = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule', array());
                    Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'schedule', $schedule);
                    if ($schedule['enable'] == 1) {
                        $schedule_data['enable'] = $schedule['enable'];
                        $schedule_data['recurrence'] = $schedule['type'];
                        $schedule_data['event'] = $schedule['event'];
                        $schedule_data['backup_type'] = $schedule['backup']['backup_files'];
                        if ($schedule['backup']['remote'] == 1) {
                            $schedule_data['save_local_remote'] = 'remote';
                        } else {
                            $schedule_data['save_local_remote'] = 'local';
                        }
                        $schedule_data['lock'] = 0;
                    } else {
                        $schedule_data['enable'] = $schedule['enable'];
                    }
                    $post_data['schedule'] = wp_json_encode($schedule_data);
                }

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

    public function set_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])) {
                $schedule = array();
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $schedule = json_decode($json, true);
                $options = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'schedule', array());
                if ($schedule['mwp_enable'] == 1) {
                    $options['enable'] = $schedule['mwp_enable'];

                    $options['type'] = $schedule['mwp_recurrence'];
                    if (!defined('WPVIVID_MAIN_SCHEDULE_EVENT'))
                        define('WPVIVID_MAIN_SCHEDULE_EVENT', 'wpvivid_main_schedule_event');
                    $options['event'] = WPVIVID_MAIN_SCHEDULE_EVENT;
                    $options['start_time'] = 0;

                    $options['backup']['backup_files'] = $schedule['mwp_backup_type'];
                    if ($schedule['mwp_save_local_remote'] == 'remote') {
                        $options['backup']['local'] = 0;
                        $options['backup']['remote'] = 1;
                    } else {
                        $options['backup']['local'] = 1;
                        $options['backup']['remote'] = 0;
                    }
                    $options['backup']['ismerge'] = 1;
                    $options['backup']['lock'] = $schedule['mwp_lock'];
                } else {
                    $options['enable'] = $schedule['mwp_enable'];
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'schedule', $options);

                $new_schedule = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'schedule', array());

                if ($new_schedule['enable'] == 1) {
                    $schedule_data['enable'] = $new_schedule['enable'];
                    $schedule_data['recurrence'] = $new_schedule['type'];
                    $schedule_data['event'] = $new_schedule['event'];
                    $schedule_data['backup_type'] = $new_schedule['backup']['backup_files'];
                    if ($new_schedule['backup']['remote'] == 1) {
                        $schedule_data['save_local_remote'] = 'remote';
                    } else {
                        $schedule_data['save_local_remote'] = 'local';
                    }
                    $schedule_data['lock'] = 0;
                } else {
                    $schedule_data['enable'] = $new_schedule['enable'];
                }
                $post_data['mwp_action'] = 'wpvivid_set_schedule_mainwp';
                $post_data['schedule'] = wp_json_encode($schedule_data);
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

    public function set_global_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            $schedule = array();
            if (isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])) {
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $schedule = json_decode($json, true);
                $options = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule', array());
                if(empty($options)){
                    $options = array();
                }
                if ($schedule['mwp_enable'] == 1) {
                    $options['enable'] = $schedule['mwp_enable'];

                    $options['type'] = $schedule['mwp_recurrence'];
                    if (!defined('WPVIVID_MAIN_SCHEDULE_EVENT'))
                        define('WPVIVID_MAIN_SCHEDULE_EVENT', 'wpvivid_main_schedule_event');
                    $options['event'] = WPVIVID_MAIN_SCHEDULE_EVENT;
                    $options['start_time'] = 0;

                    $options['backup']['backup_files'] = $schedule['mwp_backup_type'];
                    if ($schedule['mwp_save_local_remote'] == 'remote') {
                        $options['backup']['local'] = 0;
                        $options['backup']['remote'] = 1;
                    } else {
                        $options['backup']['local'] = 1;
                        $options['backup']['remote'] = 0;
                    }
                    $options['backup']['ismerge'] = 1;
                    $options['backup']['lock'] = $schedule['mwp_lock'];
                } else {
                    $options['enable'] = $schedule['mwp_enable'];
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule', $options);

                $ret['result'] = 'success';
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

    public function get_schedules_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_schedules_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Schedule_List();
                    $table->set_schedule_list($information['schedule_info']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                }
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function create_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $post_data['mwp_action'] = 'wpvivid_create_schedule_addon_mainwp';

                $json = json_decode($json, true);
                if(isset($json['custom_dirs'])){
                    $mainwp_wpvivid_extension_activator->mwp_wpvivid_update_backup_custom_setting($site_id, $json);
                }
                $json = wp_json_encode($json);

                $post_data['schedule'] = $json;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $information['error']);
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Schedule_List();
                    $table->set_schedule_list($information['schedule_info']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $success_msg = 'You have successfully added a schedule.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                    if(isset($information['enable_incremental_schedules'])){
                        if(empty($information['enable_incremental_schedules'])) $information['enable_incremental_schedules'] = 0;
                        $mainwp_wpvivid_extension_activator->set_incremental_enable($site_id, $information['enable_incremental_schedules']);
                    }
                    if(isset($information['incremental_schedules'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_schedules($site_id, $information['incremental_schedules']);
                    }
                    if(isset($information['incremental_backup_data'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_backup_data($site_id, $information['incremental_backup_data']);
                    }
                    if(isset($information['incremental_output_msg'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_output_msg($site_id, $information['incremental_output_msg']);
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

    public function update_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $post_data['mwp_action'] = 'wpvivid_update_schedule_addon_mainwp';

                $json = json_decode($json, true);
                if(isset($json['custom_dirs'])){
                    $mainwp_wpvivid_extension_activator->mwp_wpvivid_update_backup_custom_setting($site_id, $json);
                }
                $json = wp_json_encode($json);


                $post_data['schedule'] = $json;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);

                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $information['error']);
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Schedule_List();
                    $table->set_schedule_list($information['schedule_info']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $success_msg = 'You have successfully updated the schedule.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
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

    public function delete_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) && is_string($_POST['schedule_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_delete_schedule_addon_mainwp';
                $post_data['schedule_id'] = sanitize_key($_POST['schedule_id']);
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $information['error']);
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Schedule_List();
                    $table->set_schedule_list($information['schedule_info']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $success_msg = 'The schedule has been deleted successfully.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                }
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function edit_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) && is_string($_POST['schedule_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $schedule_id = sanitize_key($_POST['schedule_id']);
                $post_data['mwp_action'] = 'wpvivid_get_database_tables_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['database_tables'] = Mainwp_WPvivid_Extension_Subpage::output_edit_schedule_database_table($information['base_tables'], $information['other_tables'], false, $site_id, $schedule_id);
                }
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function save_schedule_status_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule_data']) && !empty($_POST['schedule_data']) && is_string($_POST['schedule_data'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_save_schedule_status_addon_mainwp';
                $post_data['schedule_data'] = stripslashes(sanitize_text_field($_POST['schedule_data']));
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $information['error']);
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Schedule_List();
                    $table->set_schedule_list($information['schedule_info']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $success_msg = 'You have successfully saved the changes.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                    if(isset($information['enable_incremental_schedules'])){
                        if(empty($information['enable_incremental_schedules'])) $information['enable_incremental_schedules'] = 0;
                        $mainwp_wpvivid_extension_activator->set_incremental_enable($site_id, $information['enable_incremental_schedules']);
                    }
                    if(isset($information['incremental_schedules'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_schedules($site_id, $information['incremental_schedules']);
                    }
                    if(isset($information['incremental_backup_data'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_backup_data($site_id, $information['incremental_backup_data']);
                    }
                    if(isset($information['incremental_output_msg'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_output_msg($site_id, $information['incremental_output_msg']);
                    }
                }
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function global_create_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])) {
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $schedule = json_decode($json, true);
                if(isset($_POST['schedule_mould_name']) && !empty($_POST['schedule_mould_name'])){
                    $schedule_mould_name = sanitize_text_field($_POST['schedule_mould_name']);
                    if (isset($schedule['custom_dirs'])) {
                        $mainwp_wpvivid_extension_activator->mwp_wpvivid_update_global_backup_custom_setting($schedule['custom_dirs']);
                    }

                    if(isset($_POST['first_create'])){
                        if(sanitize_text_field($_POST['first_create']) == '1'){
                            $need_check_exist = true;
                        }
                        else{
                            $need_check_exist = false;
                        }
                    }
                    else{
                        $need_check_exist = true;
                    }

                    $schedule_mould_name_array = array();
                    $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                    if(empty($schedule_mould)){
                        $schedule_mould = array();
                    }
                    else{
                        foreach ($schedule_mould as $schedule_name => $value){
                            $schedule_mould_name_array[] = $schedule_name;
                        }
                    }
                    if(!in_array($schedule_mould_name, $schedule_mould_name_array) || !$need_check_exist){
                        if(!$need_check_exist){
                            $schedules = $schedule_mould[$schedule_mould_name];
                        }
                        else{
                            $schedules = array();
                        }
                        $schedule_data = array();
                        $schedule_data['id'] = uniqid('wpvivid_schedule_event');
                        $schedule_data['status'] = $schedule['status'];
                        $schedule_data['type'] = $schedule['recurrence'];
                        $schedule_data['week'] = isset($schedule['week']) ? $schedule['week'] : 'sun';
                        $schedule_data['day'] = isset($schedule['day']) ? $schedule['day'] : '01';
                        $schedule['current_day_hour'] = isset($schedule['current_day_hour']) ? $schedule['current_day_hour'] : '00';
                        $schedule['current_day_minute'] = isset($schedule['current_day_minute']) ? $schedule['current_day_minute'] : '00';
                        $schedule_data['current_day'] = $schedule['current_day_hour'] . ':' . $schedule['current_day_minute'];
                        $schedule_data['start_time_local_utc'] = isset($schedule['start_time_zone']) ? $schedule['start_time_zone'] : 'utc';
                        if (isset($schedule['mwp_schedule_add_backup_type']) && !empty($schedule['mwp_schedule_add_backup_type'])) {
                            $schedule_data['backup']['backup_files'] = $schedule['mwp_schedule_add_backup_type'];
                            if ($schedule['mwp_schedule_add_backup_type'] === 'custom') {
                                $schedule_data['backup']['custom_dirs'] = $schedule['custom_dirs'];
                            }
                        }

                        $schedule_data['backup']['exclude_files'] = $schedule['exclude_files'];
                        $schedule_data['backup']['exclude_file_type'] = $schedule['exclude_file_type'];

                        $schedule_data['backup']['local'] = 1;
                        $schedule_data['backup']['remote'] = 0;
                        if ($schedule['save_local_remote'] == 'remote') {
                            $schedule_data['backup']['local'] = 0;
                            $schedule_data['backup']['remote'] = 1;
                        }
                        $schedule_data['backup']['lock'] = 0;
                        $schedule_data['backup']['backup_prefix'] = $schedule['backup_prefix'];
                        $schedules[$schedule_data['id']] = $schedule_data;

                        $schedule_mould[$schedule_mould_name] = $schedules;
                        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);

                        $table = new Mainwp_WPvivid_Schedule_Global_List();
                        $table->set_schedule_list($schedules);
                        $table->prepare_items();
                        ob_start();
                        $table->display();
                        $html = ob_get_clean();

                        $success_msg = 'You have successfully added a schedule.';
                        $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                        $ret['html'] = $html;
                        $ret['result'] = 'success';
                    }
                    else {
                        $ret['result'] = 'failed';
                        $error_msg = 'The schedule mould name already existed.';
                        $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
                    }
                }
                else{
                    $ret['result'] = 'failed';
                    $error_msg = 'A schedule mould name is required.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
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

    public function edit_global_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) && is_string($_POST['schedule_id']) &&
                isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])) {
                $schedule_id = sanitize_key($_POST['schedule_id']);
                $schedule_mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                $schedules = $schedule_mould[$schedule_mould_name];

                $ret['result'] = 'success';
                $ret['schedule_info'] = $schedules[$schedule_id];
                if(!isset($schedules[$schedule_id]['start_time_local_utc'])){
                    $ret['schedule_info']['start_time_local_utc'] = 'utc';
                }

                if(isset($ret['schedule_info']['current_day']))
                {
                    $dt = DateTime::createFromFormat("H:i", $ret['schedule_info']['current_day']);
                    $offset=get_option('gmt_offset');
                    $hours=$dt->format('H');
                    $minutes=$dt->format('i');

                    $hour=(float)$hours+$offset;

                    $whole = floor($hour);
                    $fraction = $hour - $whole;
                    $minute=(float)(60*($fraction))+(int)$minutes;

                    $hour=(int)$hour;
                    $minute=(int)$minute;

                    if($minute>=60)
                    {
                        $hour=(int)$hour+1;
                        $minute=(int)$minute-60;
                    }

                    if($hour>=24)
                    {
                        $hour=$hour-24;
                    }
                    else if($hour<0)
                    {
                        $hour=24-abs ($hour);
                    }

                    if($hour<10)
                    {
                        $hour='0'.(int)$hour;
                    }
                    else
                    {
                        $hour=(string)$hour;
                    }

                    if($minute<10)
                    {
                        $minute='0'.(int)$minute;
                    }
                    else
                    {
                        $minute=(string)$minute;
                    }

                    $ret['schedule_info']['hours']=$hour;
                    $ret['schedule_info']['minute']=$minute;
                }
                else
                {
                    $ret['schedule_info']['hours']='00';
                    $ret['schedule_info']['minute']='00';
                }

                echo wp_json_encode($ret);
                die();
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function global_update_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule']) &&
                isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])) {
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $schedule_data = json_decode($json, true);

                $schedule_mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                $schedules = $schedule_mould[$schedule_mould_name];

                $schedule_tmp = array();
                $schedule_tmp['id'] = $schedule_data['schedule_id'];
                $schedule_tmp['status'] = $schedule_data['status'];
                $schedule_tmp['type'] = $schedule_data['recurrence'];
                $schedule_tmp['week'] = $schedule_data['week'];
                $schedule_tmp['day'] = $schedule_data['day'];
                $schedule_tmp['current_day'] = $schedule_data['current_day_hour'].':'.$schedule_data['current_day_minute'];
                $schedule_tmp['start_time_local_utc'] = isset($schedule_data['start_time_zone']) ? $schedule_data['start_time_zone'] : 'utc';

                if(isset($schedule_data['mwp_schedule_update_backup_type']) && !empty($schedule_data['mwp_schedule_update_backup_type'])){
                    $schedule_tmp['backup']['backup_files'] = $schedule_data['mwp_schedule_update_backup_type'];
                    if($schedule_data['mwp_schedule_update_backup_type'] === 'custom'){
                        $schedule_tmp['backup']['custom_dirs'] = $schedule_data['custom_dirs'];
                    }
                }

                $schedule_tmp['backup']['exclude_files'] = $schedule_data['exclude_files'];
                $schedule_tmp['backup']['exclude_file_type'] = $schedule_data['exclude_file_type'];

                $schedule_tmp['backup']['local'] = $schedule_data['save_local_remote']==='local' ? 1 : 0;
                $schedule_tmp['backup']['remote'] = $schedule_data['save_local_remote']==='local' ? 0 : 1;
                $schedule_tmp['backup']['lock'] = intval($schedule_data['lock']);
                $schedule_tmp['backup']['backup_prefix'] = $schedule_data['backup_prefix'];

                $schedules[$schedule_data['schedule_id']] = $schedule_tmp;

                $schedule_mould[$schedule_mould_name] = $schedules;
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);

                $table=new Mainwp_WPvivid_Schedule_Global_List();
                $table->set_schedule_list($schedules);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();

                $success_msg = 'You have successfully updated the schedule. Please click on Save Changes and Sync button to synchronize the settings to child sites.';
                $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                $ret['html'] = $html;
                $ret['result'] = 'success';
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

    public function global_delete_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule_id']) && !empty($_POST['schedule_id']) && is_string($_POST['schedule_id']) &&
                isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])) {
                $schedule_id = sanitize_key($_POST['schedule_id']);

                $schedule_mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                $schedules = $schedule_mould[$schedule_mould_name];

                if(isset($schedules[$schedule_id])) {
                    unset($schedules[$schedule_id]);
                }

                $schedule_mould[$schedule_mould_name] = $schedules;
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);

                $table=new Mainwp_WPvivid_Schedule_Global_List();
                $table->set_schedule_list($schedules);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();

                $success_msg = 'The schedule has been deleted successfully. Please click on Save Changes and Sync button to synchronize the settings to child sites.';
                $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                $ret['html'] = $html;
                $ret['result'] = 'success';
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function global_save_schedule_status_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule_data']) && !empty($_POST['schedule_data']) && is_string($_POST['schedule_data']) &&
                isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])) {
                $json = stripslashes(sanitize_text_field($_POST['schedule_data']));
                $schedule_data = json_decode($json, true);

                $schedule_mould_name = sanitize_text_field($_POST['mould_name']);

                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());

                $schedules = $schedule_mould[$schedule_mould_name];

                foreach ($schedule_data as $schedule_id => $schedule_status){
                    $schedules[$schedule_id]['status'] = $schedule_status;
                }

                $schedule_mould[$schedule_mould_name] = $schedules;

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);

                $table=new Mainwp_WPvivid_Schedule_Global_List();
                $table->set_schedule_list($schedules);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();

                $success_msg = 'You have successfully saved the changes.';
                $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                $ret['html'] = $html;
                $ret['result'] = 'success';
                echo wp_json_encode($ret);
            }
            die();
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function edit_global_schedule_mould_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])){
                $mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                $schedules = $schedule_mould[$mould_name];
                $table = new Mainwp_WPvivid_Schedule_Global_List();
                $table->set_schedule_list($schedules);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();
                $ret['html'] = $html;
                $ret['result'] = 'success';
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function delete_global_schedule_mould_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])){
                $mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                if(isset($schedule_mould[$mould_name])){
                    unset($schedule_mould[$mould_name]);
                }
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);

                $table = new Mainwp_WPvivid_Schedule_Mould_List();
                $table->set_schedule_mould_list($schedule_mould);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();
                $ret['html'] = $html;
                $ret['result'] = 'success';
                echo wp_json_encode($ret);
            }
        }
        catch (Exception $error){
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_schedule_mould_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['page'])){
                $page = sanitize_text_field($_POST['page']);

                $schedule_mould_list = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                $table = new Mainwp_WPvivid_Schedule_Mould_List();
                $table->set_schedule_mould_list($schedule_mould_list, $page);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();
                $ret['schedule_mould_list'] = $html;
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

    public function update_global_schedule_backup_exclude_extension_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']) &&
                isset($_POST['exclude_content']) && !empty($_POST['exclude_content']) && is_string($_POST['exclude_content'])){
                $type = sanitize_text_field($_POST['type']);
                $exclude_content = sanitize_text_field($_POST['exclude_content']);
                $mainwp_wpvivid_extension_activator->mwp_wpvivid_update_global_backup_exclude_extension_rule($type, $exclude_content);
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

    public function edit_global_schedule_mould_name_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule_mould_name']) && !empty($_POST['schedule_mould_name']) &&
                isset($_POST['schedule_mould_old_name']) && !empty($_POST['schedule_mould_old_name'])){
                $schedule_mould_name = sanitize_text_field($_POST['schedule_mould_name']);
                $schedule_mould_old_name = sanitize_text_field($_POST['schedule_mould_old_name']);

                if($schedule_mould_name === $schedule_mould_old_name)
                {
                    $ret['result'] = 'success';
                }
                else
                {
                    $schedule_mould_name_array = array();
                    $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                    if(empty($schedule_mould))
                    {
                        $schedule_mould = array();
                    }
                    else {
                        foreach ($schedule_mould as $schedule_name => $value)
                        {
                            $schedule_mould_name_array[] = $schedule_name;
                        }
                    }
                    if(!in_array($schedule_mould_name, $schedule_mould_name_array))
                    {
                        if(isset($schedule_mould[$schedule_mould_old_name]))
                        {
                            $schedule_mould[$schedule_mould_name] = $schedule_mould[$schedule_mould_old_name];
                            unset($schedule_mould[$schedule_mould_old_name]);
                        }
                        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('schedule_mould_addon', $schedule_mould);
                        $ret['result'] = 'success';
                    }
                    else {
                        $ret['result'] = 'failed';
                        $error_msg = 'The schedule mould name already existed.';
                        $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
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

    public function render($check_pro, $global=false)
    {
        if(isset($_GET['synchronize']) && isset($_GET['addon']))
        {
            $check_addon = sanitize_text_field($_GET['addon']);
            if(isset($_GET['mould_name'])){
                $mould_name = sanitize_text_field($_GET['mould_name']);
            }
            else{
                $mould_name = '';
            }
            if(isset($_GET['is_incremental']) && $_GET['is_incremental'] == 1){
                $is_incremental = 1;
            }
            else{
                $is_incremental = 0;
            }
            $this->mwp_wpvivid_synchronize_setting($check_addon, $mould_name, $is_incremental);
        }
        else
        {
            ?>
            <div style="padding: 10px;">
                <?php
                if($global){
                    if($this->select_pro){
                        $select_pro_check = 'checked';
                    }
                    else{
                        $select_pro_check = '';
                    }
                    ?>
                    <div class="mwp-wpvivid-block-bottom-space" style="background: #fff;">
                        <div class="postbox" style="padding: 10px; margin-bottom: 0;">
                            <div style="float: left; margin-top: 7px; margin-right: 25px;"><?php esc_html_e('Switch to WPvivid Backup Pro'); ?></div>
                            <div class="ui toggle checkbox mwp-wpvivid-pro-swtich" style="float: left; margin-top:4px; margin-right: 10px;">
                                <input type="checkbox" <?php echo esc_attr($select_pro_check); ?> />
                                <label for=""></label>
                            </div>
                            <div style="float: left;"><input class="ui green mini button" type="button" value="Save" onclick="mwp_wpvivid_switch_pro_setting();" /></div>
                            <div style="clear: both;"></div>
                        </div>
                    </div>
                    <div style="clear: both;"></div>
                    <?php
                    if($this->select_pro){
                        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_first_init_schedule_to_module();
                        $this->mwp_wpvivid_schedule_page_addon($global);
                    }
                    else{
                        $this->mwp_wpvivid_schedule_page($global);
                    }
                    ?>
                    <?php
                }
                else {
                    if ($check_pro) {
                        $this->mwp_wpvivid_schedule_page_addon($global);
                    } else {
                        $this->mwp_wpvivid_schedule_page($global);
                    }
                }
                ?>
            </div>
            <script>
                function mwp_wpvivid_switch_pro_setting(){
                    if(jQuery('.mwp-wpvivid-pro-swtich').find('input:checkbox').prop('checked')){
                        var pro_setting = 1;
                    }
                    else{
                        var pro_setting = 0;
                    }

                    var ajax_data = {
                        'action': 'mwp_wpvivid_switch_pro_setting',
                        'pro_setting': pro_setting
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                location.reload();
                            }
                            else {
                                alert(jsonarray.error);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                function mwp_wpvivid_swtich_global_schedule_tab(evt, contentName){
                    var i, tabcontent, tablinks;
                    tabcontent = document.getElementsByClassName("mwp-global-schedule-tab-content");
                    for (i = 0; i < tabcontent.length; i++) {
                        tabcontent[i].style.display = "none";
                    }
                    tablinks = document.getElementsByClassName("mwp-global-schedule-nav-tab");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
                    }
                    document.getElementById(contentName).style.display = "block";
                    evt.currentTarget.className += " nav-tab-active";
                }
            </script>
            <?php
        }
    }

    public function mwp_wpvivid_schedule_page_addon($global){
        global $mainwp_wpvivid_extension_activator;
        if(!$global){
            $mainwp_wpvivid_extension_activator->incremental_schedule->set_site_id($this->site_id);
            $incremental_backup_data=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($this->site_id, 'incremental_backup_setting', array());
            $mainwp_wpvivid_extension_activator->incremental_schedule->set_incremental_backup_data($incremental_backup_data);
        }
        else{
            $incremental_backup_data=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
            $mainwp_wpvivid_extension_activator->incremental_schedule->set_incremental_backup_data($incremental_backup_data);
        }
        $schedules = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_addon', array());

        add_filter('mwp_wpvivid_schedule_local_remote_addon', array($this, 'mwp_wpvivid_schedule_local_remote_addon'), 10, 2);

        ?>
        <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
            <div class="mwp-wpvivid-welcome-bar-left">
                <p><span class="dashicons dashicons-calendar-alt mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-green"></span><span class="mwp-wpvivid-page-title">Backup Schedule</span></p>
                <span class="about-description">The page allows you to create backup/unused images clean/image optimiztion schedules</span>
            </div>
            <div class="mwp-wpvivid-welcome-bar-right"></div>
            <div class="mwp-wpvivid-nav-bar mwp-wpvivid-clear-float">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span> In order to ensure that the scheduled tasks can be performed as expected, it is best to complete a manual backup first to ensure that your server has sufficient resources.</span>
            </div>
        </div>

        <?php

        if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
            include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
        $this->main_tab=new Mainwp_WPvivid_Tab_Page_Container();

        $args['is_parent_tab']=0;
        $args['transparency']=1;

        $tabs['schedules']['title'] = 'Schedules';
        $tabs['schedules']['slug'] = 'schedules';
        $tabs['schedules']['callback'] = array($this, 'output_schedules_page');
        $tabs['schedules']['args'] = $args;

        $args['can_delete']=1;
        $args['hide']=1;
        $args['global']=$global;
        $tabs['schedules_edit']['title'] = 'Schedule Edit';
        $tabs['schedules_edit']['slug'] = 'schedules_edit';
        $tabs['schedules_edit']['callback'] = array($this, 'output_schedules_edit_page');
        $tabs['schedules_edit']['args'] = $args;
        $tabs=apply_filters('mwp_wpvivid_schedule_tabs',$tabs);
        foreach ($tabs as $key=>$tab)
        {
            $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
        }
        $this->main_tab->display();
        ?>
        <script>
            var is_global = '<?php echo esc_js($global); ?>';
            if(!is_global){
                mwp_wpvivid_get_schedules_addon();
            }
            function mwp_wpvivid_get_schedules_addon(){
                var ajax_data={
                    'action': 'mwp_wpvivid_get_schedules_addon',
                    'site_id':'<?php echo esc_html($this->site_id); ?>'
                };
                mwp_wpvivid_post_request(ajax_data, function (data)
                {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_schedule_list_addon').html(jsonarray.html);
                        }
                        else
                        {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    setTimeout(function ()
                    {
                        mwp_wpvivid_get_schedules_addon();
                    }, 3000);
                });
            }

            var mwp_wpvivid_edit_schedule_id = '';


            function mwp_wpvivid_display_edit_schedule_database_table(schedule_id)
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_edit_schedule_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'schedule_id': schedule_id
                };
                jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                            jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-database-info').html(jsonarray.database_tables);
                        }
                        else {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_display_schedule_setting(backupinfo){
                var database_check = true;
                var additional_database = true;
                var core_check = true;
                var content_check = true;
                var themes_check = true;
                var plugins_check = true;
                var uploads_check = true;
                var other_check = true;
                if(backupinfo.custom_dirs.database_check != 1){
                    database_check = false;
                }
                if(backupinfo.custom_dirs.additional_database_check != 1){
                    additional_database = false;
                }
                if(backupinfo.custom_dirs.core_check != 1){
                    core_check = false;
                }
                if(backupinfo.custom_dirs.content_check != 1){
                    content_check = false;
                }
                if(backupinfo.custom_dirs.themes_check != 1){
                    themes_check = false;
                }
                if(backupinfo.custom_dirs.plugins_check != 1){
                    plugins_check = false;
                }
                if(backupinfo.custom_dirs.uploads_check != 1){
                    uploads_check = false;
                }
                if(backupinfo.custom_dirs.other_check != 1){
                    other_check = false;
                }

                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-database-check').prop('checked', database_check);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-additional-database-check').prop('checked', additional_database);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-core-check').prop('checked', core_check);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-content-check').prop('checked', content_check);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-themes-check').prop('checked', themes_check);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-plugins-check').prop('checked', plugins_check);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-uploads-check').prop('checked', uploads_check);
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-additional-folder-check').prop('checked', other_check);

                var include_other = '';
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-include-additional-folder-list').html('');
                jQuery.each(backupinfo.custom_dirs.other_list, function(index ,value){
                    var type = 'folder';
                    var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    include_other += "<div class='wpvivid-text-line' type='"+type+"'>" +
                        "<span class='dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree'></span>" +
                        "<span class='"+class_span+"'></span>" +
                        "<span class='wpvivid-text-line'>" + value + "</span>" +
                        "</div>";
                });
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-include-additional-folder-list').append(include_other);
            }

            function mwp_wpvivid_edit_schedule_ex(schedule_id, data){
                console.log(data);
                var jsonarray = jQuery.parseJSON(data);

                mwp_wpvivid_edit_schedule_id = jsonarray.id;
                jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-show',[ 'schedules_edit', 'schedules' ]);

                var cycles = jsonarray.type;
                jQuery("#mwp_wpvivid_schedule_update_cycles_select").val(cycles);
                jQuery('#mwp_wpvivid_schedule_update_week').hide();
                jQuery('#mwp_wpvivid_schedule_update_day').hide();
                if(cycles === 'wpvivid_weekly' || cycles === 'wpvivid_fortnightly')
                {
                    jQuery('#mwp_wpvivid_schedule_update_week').show();
                    jQuery('#mwp_wpvivid_schedule_update_week_select').val(jsonarray.week);
                }
                else if(cycles === 'wpvivid_monthly'){
                    jQuery('#mwp_wpvivid_schedule_update_day').show();
                    jQuery('#mwp_wpvivid_schedule_update_day_select').val(jsonarray.day);
                }

                jQuery('select[option=mwp_schedule_update][name=current_day_hour]').each(function() {
                    jQuery(this).val(jsonarray.hours);
                });
                jQuery('select[option=mwp_schedule_update][name=current_day_minute]').each(function(){
                    jQuery(this).val(jsonarray.minute);
                });

                jQuery('#mwp_wpvivid_schedule_update_utc_time').html(jsonarray.current_day);

                jQuery('#mwp_wpvivid_schedule_update_start_local_time').html(jsonarray.hours+':'+jsonarray.minute);
                jQuery('#mwp_wpvivid_schedule_update_start_utc_time').html(jsonarray.current_day);
                jQuery('#mwp_wpvivid_schedule_update_start_cycles').html(jsonarray.schedule_cycles);

                if(typeof jsonarray.backup.backup_files !== 'undefined') {
                    if (jsonarray.backup.backup_files === 'files+db') {
                        jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=\'files+db\']').prop('checked', true);
                    }
                    else if(jsonarray.backup.backup_files === 'custom'){
                        jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=custom]').prop('checked', true);
                        jQuery('#wpvivid_custom_update_schedule_backup').show();
                        mwp_wpvivid_display_schedule_setting(jsonarray.backup);
                        mwp_wpvivid_display_edit_schedule_database_table(schedule_id);
                    }
                    else {
                        jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=' + jsonarray.backup.backup_files + ']').prop('checked', true);
                    }
                }
                else{
                    jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=custom]').prop('checked', true);
                    jQuery('#wpvivid_custom_update_schedule_backup').show();
                    mwp_wpvivid_display_schedule_setting(jsonarray.backup);
                    mwp_wpvivid_display_edit_schedule_database_table(schedule_id);
                }

                //var backup_to = jsonarray.backup.local === 1 ? 'local' : 'remote';
                //jQuery('input:radio[option=mwp_schedule_update][name=mwp_schedule_update_save_local_remote][value='+backup_to+']').prop('checked', true);
                if(jsonarray.backup.local == 1){
                    jQuery('input[option=mwp_update_schedule_backup][name=update_schedule_save_local_remote][value=local]').prop('checked', true);
                    jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector_part').hide();
                }
                else{
                    jQuery('input[option=mwp_update_schedule_backup][name=update_schedule_save_local_remote][value=remote]').prop('checked', true);
                    jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector_part').show();
                    if(typeof jsonarray.backup.remote_options !== 'undefined'){
                        jQuery.each(jsonarray.backup.remote_options, function(remote_id, remote_option){
                            jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector').val(remote_id);
                        });
                    }
                    else
                    {
                        jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector').val('all');
                    }
                }

                if(typeof jsonarray.backup.exclude_files !== 'undefined')
                {
                    var exclude_list = '';
                    jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.mwp-wpvivid-custom-exclude-list').html('');
                    jQuery.each(jsonarray.backup.exclude_files, function(index, value)
                    {
                        if(value.type === 'folder')
                        {
                            var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                        }
                        else
                        {
                            var class_span = 'dashicons dashicons-media-default wpvivid-dashicons-grey wpvivid-icon-16px-nopointer';
                        }
                        exclude_list += "<div class='wpvivid-text-line' type='"+value.type+"'>" +
                            "<span class='dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree'></span>" +
                            "<span class='"+class_span+"'></span>" +
                            "<span class='wpvivid-text-line'>" + value.path + "</span>" +
                            "</div>";
                    });
                    jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.mwp-wpvivid-custom-exclude-list').append(exclude_list);
                }

                jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.mwp-wpvivid-custom-exclude-extension').val('');
                if(typeof jsonarray.backup.exclude_file_type !== 'undefined')
                {
                    jQuery('#wpvivid_custom_update_schedule_advanced_option').find('.mwp-wpvivid-custom-exclude-extension').val(jsonarray.backup.exclude_file_type);
                }

                if(typeof jsonarray.backup.backup_prefix !== 'undefined')
                {
                    jQuery('input:text[option=mwp_update_schedule_backup][name=backup_prefix]').val(jsonarray.backup.backup_prefix);
                }
            }

            function mwp_wpvivid_delete_schedule(schedule_id){
                var ajax_data = {
                    'action': 'mwp_wpvivid_delete_schedule_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'schedule_id': schedule_id
                };
                jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                            jQuery('#mwp_wpvivid_schedule_list_addon').html(jsonarray.html);
                        }
                        else {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_schedule_list_addon').on('click', '.mwp-wpvivid-schedule-on-off-control', function(){
                var Obj=jQuery(this);
                var json = {};
                var schedule_id = '';
                var schedule_status = '';

                schedule_id = Obj.closest('tr').attr('slug');
                if(jQuery(this).prop('checked'))
                {
                    schedule_status = 'Active';
                }
                else
                {
                    schedule_status = 'InActive';
                }
                json[schedule_id] = schedule_status;
                schedule_status = JSON.stringify(json);

                var ajax_data= {
                    'action': 'mwp_wpvivid_save_schedule_status_addon',
                    'site_id': '<?php echo esc_html($this->site_id); ?>',
                    'schedule_data': schedule_status,
                };
                jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                mwp_wpvivid_post_request(ajax_data, function(data)
                {
                    location.href=window.location.href;
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#mwp_wpvivid_schedule_list_addon').on('click', '.mwp-wpvivid-schedule-edit', function(){
                var Obj = jQuery(this);
                var id = Obj.closest('tr').attr('slug');
                var name = jQuery(this).attr('name');
                mwp_wpvivid_edit_schedule_ex(id, name);
            });

            jQuery('#mwp_wpvivid_schedule_list_addon').on('click', '.mwp-wpvivid-schedule-delete', function(){
                var descript = 'Are you sure to remove this schedule?';
                var ret = confirm(descript);
                if(ret === true) {
                    var Obj = jQuery(this);
                    var id = Obj.closest('tr').attr('slug');
                    mwp_wpvivid_delete_schedule(id);
                }
            });

            jQuery('#mwp_wpvivid_schedule_list_addon').on('change', '.schedule-item > .check-column > input', function(){
                if( jQuery(this).is(':checked') )
                {
                    var Obj=jQuery(this).closest('tr');
                    Obj.addClass('mwp-wpvivid-schedule-active');
                    Obj.find('.mwp-wpvivid-schedule-status').html('Active');
                }
                else
                {
                    var Obj=jQuery(this).closest('tr');
                    Obj.removeClass('mwp-wpvivid-schedule-active');
                    Obj.find('.mwp-wpvivid-schedule-status').html('InActive');
                }
            });

            jQuery('#mwp_wpvivid_schedule_list_addon').on('change' ,'thead .check-column input',function() {
                if( jQuery(this).is(':checked') )
                {
                    jQuery('#mwp_wpvivid_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.addClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('Active');
                    });
                }
                else
                {
                    jQuery('#mwp_wpvivid_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.removeClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('InActive');
                    });
                }
            });

            jQuery('#mwp_wpvivid_schedule_list_addon').on('change' ,'tfoot .check-column input',function() {
                if( jQuery(this).is(':checked') )
                {
                    jQuery('#mwp_wpvivid_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.addClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('Active');
                    });
                }
                else
                {
                    jQuery('#mwp_wpvivid_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.removeClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('InActive');
                    });
                }
            });

            var mwp_wpvivid_global_edit_schedule_id = '';
            var mwp_wpvivid_global_edit_schedule_mould_name = '';

            function mwp_wpvivid_display_global_schedule_setting(backupinfo)
            {
                var database_check = true;
                var core_check = true;
                var content_check = true;
                var themes_check = true;
                var plugins_check = true;
                var uploads_check = true;

                if(backupinfo.backup_select.db != 1){
                    database_check = false;
                }
                if(backupinfo.backup_select.core != 1){
                    core_check = false;
                }
                if(backupinfo.backup_select.content != 1){
                    content_check = false;
                }
                if(backupinfo.backup_select.themes != 1){
                    themes_check = false;
                }
                if(backupinfo.backup_select.plugin != 1){
                    plugins_check = false;
                }
                if(backupinfo.backup_select.uploads != 1){
                    uploads_check = false;
                }

                jQuery('#wpvivid_global_custom_update_schedule_backup').find('.mwp-wpvivid-custom-database-check').prop('checked', database_check);
                jQuery('#wpvivid_global_custom_update_schedule_backup').find('.mwp-wpvivid-custom-core-check').prop('checked', core_check);
                jQuery('#wpvivid_global_custom_update_schedule_backup').find('.mwp-wpvivid-custom-content-check').prop('checked', content_check);
                jQuery('#wpvivid_global_custom_update_schedule_backup').find('.mwp-wpvivid-custom-themes-check').prop('checked', themes_check);
                jQuery('#wpvivid_global_custom_update_schedule_backup').find('.mwp-wpvivid-custom-plugins-check').prop('checked', plugins_check);
                jQuery('#wpvivid_global_custom_update_schedule_backup').find('.mwp-wpvivid-custom-uploads-check').prop('checked', uploads_check);

                /*var include_other = '';
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-include-additional-folder-list').html('');
                jQuery.each(backupinfo.custom_dirs.other_list, function(index ,value){
                    var type = 'folder';
                    var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                    include_other += "<div class='wpvivid-text-line' type='"+type+"'>" +
                        "<span class='dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree'></span>" +
                        "<span class='"+class_span+"'></span>" +
                        "<span class='wpvivid-text-line'>" + value + "</span>" +
                        "</div>";
                });
                jQuery('#wpvivid_custom_update_schedule_backup').find('.mwp-wpvivid-custom-include-additional-folder-list').append(include_other);*/
            }

            function mwp_wpvivid_global_edit_schedule(schedule_id){
                var mould_name = jQuery('#mwp_wpvivid_schedule_mould_name').val();
                mwp_wpvivid_global_edit_schedule_id = schedule_id;
                mwp_wpvivid_global_edit_schedule_mould_name = mould_name;
                var ajax_data = {
                    'action': 'mwp_wpvivid_edit_global_schedule_addon',
                    'schedule_id': schedule_id,
                    'mould_name': mould_name
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_tab_schedule_edit').show();
                            jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-show',[ 'schedules_edit', 'schedules' ]);

                            var arr = new Array();
                            arr = jsonarray.schedule_info.current_day.split(':');

                            jQuery('select[option=mwp_schedule_update][name=current_day_hour]').each(function()
                            {
                                jQuery(this).val(arr[0]);
                            });
                            jQuery('select[option=mwp_schedule_update][name=current_day_minute]').each(function(){
                                jQuery(this).val(arr[1]);
                            });

                            if(jsonarray.schedule_info.start_time_local_utc === 'local') {
                                jQuery('#mwp_wpvivid_schedule_update_start_timezone').val('local');
                            }
                            else{
                                jQuery('#mwp_wpvivid_schedule_update_start_timezone').val('utc');
                            }

                            if(jsonarray.schedule_info.type === 'wpvivid_daily')
                            {
                                jQuery('#mwp_wpvivid_schedule_update_cycles_select').val('wpvivid_daily');
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_weekly')
                            {
                                jQuery('#mwp_wpvivid_schedule_update_week').show();
                                jQuery('#mwp_wpvivid_schedule_update_cycles_select').val('wpvivid_weekly');
                                jQuery('#mwp_wpvivid_schedule_update_week_select').val(jsonarray.schedule_info.week);
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_fortnightly')
                            {
                                jQuery('#mwp_wpvivid_schedule_update_week').show();
                                jQuery('#mwp_wpvivid_schedule_update_cycles_select').val('wpvivid_fortnightly');
                                jQuery('#mwp_wpvivid_schedule_update_week_select').val(jsonarray.schedule_info.week);
                            }
                            else if(jsonarray.schedule_info.type === 'wpvivid_monthly')
                            {
                                jQuery('#mwp_wpvivid_schedule_update_day').show();
                                jQuery('#mwp_wpvivid_schedule_update_cycles_select').val('wpvivid_monthly');
                                jQuery('#mwp_wpvivid_schedule_update_day_select').val(jsonarray.schedule_info.day);
                            }
                            else{
                                jQuery('#mwp_wpvivid_schedule_update_cycles_select').val(jsonarray.schedule_info.type);
                            }

                            jQuery('#mwp_wpvivid_schedule_update_week').hide();
                            jQuery('#mwp_wpvivid_schedule_update_day').hide();
                            var select_value = jQuery('#mwp_wpvivid_schedule_update_cycles_select').val();
                            if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly')
                            {
                                jQuery('#mwp_wpvivid_schedule_update_week').show();
                            }
                            else if(select_value === 'wpvivid_monthly'){
                                jQuery('#mwp_wpvivid_schedule_update_day').show();
                            }

                            jQuery('#mwp_wpvivid_schedule_update_start_local_time').html(jsonarray.schedule_info.current_day);
                            jQuery('#mwp_wpvivid_schedule_update_start_utc_time').html(jsonarray.schedule_info.current_day);
                            var backup_cycles = jQuery("#mwp_wpvivid_schedule_update_cycles_select option:selected").text();
                            jQuery('#mwp_wpvivid_schedule_update_start_cycles').html(backup_cycles);

                            if(typeof jsonarray.schedule_info.backup.backup_files !== 'undefined') {
                                if (jsonarray.schedule_info.backup.backup_files == 'files+db') {
                                    jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=\'files+db\']').prop('checked', true);
                                }
                                else {
                                    jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=' + jsonarray.schedule_info.backup.backup_files + ']').prop('checked', true);
                                }
                                jQuery('#wpvivid_global_custom_update_schedule_backup').hide();
                                //jQuery('#mwp_wpvivid_schedule_update_custom_module_part').hide();
                                //mwp_wpvivid_popup_schedule_tour_addon('hide', 'schedule_update');
                            }
                            else{
                                jQuery('input[option=mwp_schedule_update][name=mwp_schedule_update_backup_type][value=custom]').prop('checked', true);

                                jQuery('#wpvivid_global_custom_update_schedule_backup').show();
                                mwp_wpvivid_display_global_schedule_setting(jsonarray.schedule_info.backup);
                                //mwp_wpvivid_display_edit_schedule_database_table(schedule_id)
                                //jQuery('#mwp_wpvivid_schedule_update_custom_module_part').show();
                                //mwp_wpvivid_popup_schedule_tour_addon('show', 'schedule_update');
                                //mwp_wpvivid_display_schedule_setting(jsonarray.schedule_info.backup);
                            }

                            var backup_to = jsonarray.schedule_info.backup.local === 1 ? 'local' : 'remote';
                            jQuery('input:radio[option=mwp_update_schedule_backup][name=update_schedule_save_local_remote][value='+backup_to+']').prop('checked', true);
                            jQuery('#mwp_wpvivid_schedule_update_utc_time').html(jsonarray.schedule_info.current_day);

                            jQuery('#wpvivid_global_custom_update_schedule_advanced_option').find('.mwp-wpvivid-exclude-path').val(jsonarray.schedule_info.backup.exclude_files);
                            jQuery('#wpvivid_global_custom_update_schedule_advanced_option').find('.mwp-wpvivid-custom-exclude-extension').val(jsonarray.schedule_info.backup.exclude_file_type);

                            if(typeof jsonarray.schedule_info.backup.backup_prefix !== 'undefined')
                            {
                                jQuery('input:text[option=mwp_update_schedule_backup][name=backup_prefix]').val(jsonarray.schedule_info.backup.backup_prefix);
                            }
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('editing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_global_delete_schedule(schedule_id){
                var mould_name = jQuery('#mwp_wpvivid_schedule_mould_name').val();
                var ajax_data = {
                    'action': 'mwp_wpvivid_global_delete_schedule_addon',
                    'schedule_id': schedule_id,
                    'mould_name': mould_name
                };
                jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                            jQuery('#mwp_wpvivid_global_schedule_list_addon').html(jsonarray.html);
                        }
                        else {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_global_schedule_list_addon').on('click', '.mwp-wpvivid-schedule-on-off-control', function(){
                var mould_name = jQuery('#mwp_wpvivid_schedule_mould_name').val();

                var Obj=jQuery(this);
                var json = {};
                var schedule_id = '';
                var schedule_status = '';

                schedule_id = Obj.closest('tr').attr('slug');
                if(jQuery(this).prop('checked'))
                {
                    schedule_status = 'Active';
                }
                else
                {
                    schedule_status = 'InActive';
                }
                json[schedule_id] = schedule_status;
                schedule_status = JSON.stringify(json);

                var ajax_data= {
                    'action': 'mwp_wpvivid_global_save_schedule_status_addon',
                    'schedule_data': schedule_status,
                    'mould_name': mould_name
                };
                jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                            jQuery('#mwp_wpvivid_global_schedule_list_addon').html(jsonarray.html);
                        }
                        else {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            });

            jQuery('#mwp_wpvivid_global_schedule_list_addon').on('click', '.mwp-wpvivid-schedule-edit', function(){
                var Obj=jQuery(this);
                var id=Obj.closest('tr').attr('slug');
                mwp_wpvivid_global_edit_schedule(id);
            });

            jQuery('#mwp_wpvivid_global_schedule_list_addon').on('click', '.mwp-wpvivid-schedule-delete', function(){
                var descript = 'Are you sure to remove this schedule?';
                var ret = confirm(descript);
                if(ret === true) {
                    var Obj = jQuery(this);
                    var id = Obj.closest('tr').attr('slug');
                    mwp_wpvivid_global_delete_schedule(id);
                }
            });

            jQuery('#mwp_wpvivid_global_schedule_list_addon').on('change', '.schedule-item > .check-column > input', function(){
                if( jQuery(this).is(':checked') )
                {
                    var Obj=jQuery(this).closest('tr');
                    Obj.addClass('mwp-wpvivid-schedule-active');
                    Obj.find('.mwp-wpvivid-schedule-status').html('Active');
                }
                else
                {
                    var Obj=jQuery(this).closest('tr');
                    Obj.removeClass('mwp-wpvivid-schedule-active');
                    Obj.find('.mwp-wpvivid-schedule-status').html('InActive');
                }
            });

            jQuery('#mwp_wpvivid_global_schedule_list_addon').on('change', 'thead .check-column input', function(){
                if( jQuery(this).is(':checked') )
                {
                    jQuery('#mwp_wpvivid_global_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.addClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('Active');
                    });
                }
                else
                {
                    jQuery('#mwp_wpvivid_global_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.removeClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('InActive');
                    });
                }
            });

            jQuery('#mwp_wpvivid_global_schedule_list_addon').on('change' ,'tfoot .check-column input',function() {
                if( jQuery(this).is(':checked') )
                {
                    jQuery('#mwp_wpvivid_global_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.addClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('Active');
                    });
                }
                else
                {
                    jQuery('#mwp_wpvivid_global_schedule_list_addon').find('.schedule-item > .check-column > input').each(function()
                    {
                        var Obj=jQuery(this).closest('tr');
                        Obj.removeClass('mwp-wpvivid-schedule-active');
                        Obj.find('.mwp-wpvivid-schedule-status').html('InActive');
                    });
                }
            });

            jQuery('#mwp_wpvivid_global_schedule_save_addon').click(function(){
                mwp_wpvivid_global_schedule_save_addon();
            });

            jQuery('#mwp_wpvivid_schedule_save_addon').click(function(){
                mwp_wpvivid_schedule_save_addon();
            });

            function mwp_wpvivid_global_schedule_save_addon() {
                var json={};
                var schedule_id = '';
                var schedule_status = '';
                var need_update = false;

                jQuery('#mwp_wpvivid_global_schedule_list_addon tbody').find('tr').each(function(){
                    if(!jQuery(this).hasClass('no-items')) {
                        need_update = true;
                        schedule_id = jQuery(this).attr('slug');
                        if (jQuery(this).children().children().prop('checked')) {
                            schedule_status = 'Active';
                        }
                        else {
                            schedule_status = 'InActive';
                        }
                        json[schedule_id] = schedule_status;
                    }
                });
                schedule_status = JSON.stringify(json);

                var ajax_data= {
                    'action': 'mwp_wpvivid_global_save_schedule_status_addon',
                    'schedule_data': schedule_status
                };
                jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                mwp_wpvivid_post_request(ajax_data, function(data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            window.location.href = window.location.href + "&synchronize=1&addon=1";
                        }
                        else {
                            jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_schedule_save_addon() {
                var json={};
                var schedule_id = '';
                var schedule_status = '';
                var need_update = false;

                jQuery('#mwp_wpvivid_schedule_list_addon tbody').find('tr').each(function(){
                    if(!jQuery(this).hasClass('no-items')) {
                        need_update = true;
                        schedule_id = jQuery(this).attr('slug');
                        if (jQuery(this).children().children().prop('checked')) {
                            schedule_status = 'Active';
                        }
                        else {
                            schedule_status = 'InActive';
                        }
                        json[schedule_id] = schedule_status;
                    }
                });
                schedule_status = JSON.stringify(json);

                if(need_update === true){
                    var ajax_data= {
                        'action': 'mwp_wpvivid_save_schedule_status_addon',
                        'schedule_data': schedule_status,
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                    mwp_wpvivid_post_request(ajax_data, function(data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                                jQuery('#mwp_wpvivid_schedule_list_addon').html(jsonarray.html);
                            }
                            else {
                                jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('setting up a lock for the backup', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            function mwp_wpvivid_start_sync_schedule(){
                mwp_wpvivid_global_schedule_save_addon();
            }
        </script>
        <?php
    }

    public function output_schedules_page($global){
        ?>
        <div style="margin-top: 10px;">
            <?php
            if($global){
                ?>
                <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_schedule_mould_part_1">
                    <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_schedule_mould_list_addon">
                        <?php
                        $schedule_mould_list = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('schedule_mould_addon', array());
                        if(empty($schedule_mould_list)){
                            $schedule_mould_list = array();
                        }
                        $table = new Mainwp_WPvivid_Schedule_Mould_List();
                        $table->set_schedule_mould_list($schedule_mould_list);
                        $table->prepare_items();
                        $table->display();
                        ?>
                    </div>
                    <div>
                        <input class="ui green mini button" type="button" value="<?php esc_attr_e('Create New Schedule Mould'); ?>" onclick="mwp_wpvivid_create_new_schedule_mould();" />
                    </div>
                </div>
                <div id="mwp_wpvivid_schedule_mould_part_2" style="display: none;">
                    <div class="mwp-wpvivid-block-bottom-space">
                        <span>Name the schedule template:</span>
                        <input id="mwp_wpvivid_schedule_mould_name" />
                        <input class="ui green mini button" id="mwp_wpvivid_schedule_mould_name_edit" type="button" value="Edit" style="display: none;" />
                        <input class="ui green mini button" id="mwp_wpvivid_schedule_mould_name_save" type="button" value="Save" style="display: none;" />
                    </div>


                    <div class="mwp-wpvivid-one-coloum" style="padding: 0em;">
                        <div id="mwp_wpvivid_schedule_create_notice"></div>
                        <div id="mwp_wpvivid_schedule_save_notice"></div>
                    </div>

                    <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_global_schedule_list_addon">
                        <?php
                        $schedules = $this->setting_addon;
                        $schedules_list = array();
                        $table=new Mainwp_WPvivid_Schedule_Global_List();
                        $table->set_schedule_list($schedules_list);
                        $table->prepare_items();
                        $table->display();
                        ?>
                    </div>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <input class="ui green mini button" onclick="mwp_wpvivid_back_schedule_mould();" type="button" value="<?php esc_attr_e('Back to Mould List'); ?>" />
                    </div>

                    <?php
                    $type='mwp_schedule_add';
                    $utc_time=date( 'H:i:s - m/d/Y ', time() );
                    $offset = get_option('gmt_offset');
                    $local_time=date( 'H:i:s - m/d/Y ', current_time( 'timestamp', 0 ) );
                    $mwp_wpvivid_cycles = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles' : 'mwp_wpvivid_schedule_update_cycles';
                    $mwp_wpvivid_cycles_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles_select' : 'mwp_wpvivid_schedule_update_cycles_select';
                    $mwp_wpvivid_week = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week' : 'mwp_wpvivid_schedule_update_week';
                    $mwp_wpvivid_week_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week_select' : 'mwp_wpvivid_schedule_update_week_select';
                    $mwp_wpvivid_day = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day' : 'mwp_wpvivid_schedule_update_day';
                    $mwp_wpvivid_day_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day_select' : 'mwp_wpvivid_schedule_update_day_select';
                    $mwp_wpvivid_hour_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_hour_select' : 'mwp_wpvivid_schedule_update_hour_select';
                    $mwp_wpvivid_minute_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_minute_select' : 'mwp_wpvivid_schedule_update_minute_select';
                    $mwp_wpvivid_utc_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_utc_time' : 'mwp_wpvivid_schedule_update_utc_time';
                    $mwp_wpvivid_start_local_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_local_time' : 'mwp_wpvivid_schedule_update_start_local_time';
                    $mwp_wpvivid_start_utc_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_utc_time' : 'mwp_wpvivid_schedule_update_start_utc_time';
                    $mwp_wpvivid_start_cycles = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_cycles' : 'mwp_wpvivid_schedule_update_start_cycles';
                    $mwp_wpvivid_start_timezone = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_timezone' : 'mwp_wpvivid_schedule_update_start_timezone';
                    $location = 'options-general.php';
                    $mwp_wpvivid_timezone = $global === true ? admin_url().'options-general.php' : 'admin.php?page=SiteOpen&newWindow=yes&websiteid='.$this->site_id.'&location='.base64_encode($location).'&_opennonce='.wp_create_nonce( 'mainwp-admin-nonce' );
                    ?>

                    <div style="width:100%; border:1px solid #e5e5e5; float:left; box-sizing: border-box;margin-bottom:10px;">
                            <div class="mwp-wpvivid-block-bottom-space" style="margin: 1px 1px 10px 1px; background-color: #f7f7f7; box-sizing: border-box; padding: 10px;">Set backup cycle and start time:</div>
                            <div class="mwp-wpvivid-block-bottom-space" style="margin-left: 10px; margin-right: 10px;">
                                <div style="padding: 4px 10px 0 0; float: left;">The backup will run</div>
                                <div id="<?php echo esc_attr($mwp_wpvivid_cycles); ?>" style="padding: 0 10px 0 0; float: left;">
                                    <select id="<?php echo esc_attr($mwp_wpvivid_cycles_select); ?>" option="<?php echo esc_attr($type); ?>" name="recurrence" onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                        <option value="wpvivid_hourly">Every hour</option>
                                        <option value="wpvivid_2hours">Every 2 hours</option>
                                        <option value="wpvivid_4hours">Every 4 hours</option>
                                        <option value="wpvivid_8hours">Every 8 hours</option>
                                        <option value="wpvivid_12hours">Every 12 hours</option>
                                        <option value="wpvivid_daily" selected>Daily</option>
                                        <option value="wpvivid_weekly">Weekly</option>
                                        <option value="wpvivid_fortnightly">Fortnightly</option>
                                        <option value="wpvivid_monthly">30 Days</option>
                                    </select>
                                </div>
                                <div style="padding: 4px 10px 0 0; float: left;">at</div>
                                <div id="<?php echo esc_attr($mwp_wpvivid_week); ?>" style="padding: 0 10px 0 0; float: left; display: none;">
                                    <select id="<?php echo esc_attr($mwp_wpvivid_week_select); ?>" option="<?php echo esc_attr($type); ?>" name="week">
                                        <option value="sun" selected>Sunday</option>
                                        <option value="mon">Monday</option>
                                        <option value="tue">Tuesday</option>
                                        <option value="wed">Wednesday</option>
                                        <option value="thu">Thursday</option>
                                        <option value="fri">Friday</option>
                                        <option value="sat">Saturday</option>
                                    </select>
                                </div>
                                <div id="<?php echo esc_attr($mwp_wpvivid_day); ?>" style="padding: 0 10px 0 0; float: left; display: none;">
                                    <select id="<?php echo esc_attr($mwp_wpvivid_day_select); ?>" option="<?php echo esc_attr($type); ?>" name="day">
                                        <?php
                                        for ($i = 1; $i < 31; $i++) {
                                            echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div style="padding: 0 10px 0 0; float: left;">
                                    <select id="<?php echo esc_attr($mwp_wpvivid_hour_select); ?>" option="<?php echo esc_attr($type); ?>" name="current_day_hour" style="margin-bottom: 4px;" onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                        <?php
                                        for ($hour = 0; $hour < 24; $hour++) {
                                            $format_hour = sprintf("%02d", $hour);
                                            echo '<option value="' . esc_attr($format_hour) . '">' . esc_html($format_hour) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <span>:</span>
                                    <select id="<?php echo esc_attr($mwp_wpvivid_minute_select); ?>" option="<?php echo esc_attr($type); ?>" name="current_day_minute" style="margin-bottom: 4px;" onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                        <?php
                                        for ($minute = 0; $minute < 60; $minute++) {
                                            $format_minute = sprintf("%02d", $minute);
                                            echo '<option value="' . esc_attr($format_minute) . '">' . esc_html($format_minute) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div style="clear: both;"></div>
                            </div>
                        </div>

                    <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin-top:0.5em; margin-bottom: 0.5em;">
                        <div>
                            <p><span class="dashicons dashicons-backup mwp-wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                            <div style="padding-left:2em;">
                                <label class="">
                                    <input type="radio" option="mwp_schedule_backup" name="schedule_save_local_remote" value="local" checked="checked" />Backup to localhost
                                </label>
                                <span style="padding: 0 1em;"></span>

                                <label class="">
                                    <input type="radio" option="mwp_schedule_backup" name="schedule_save_local_remote" value="remote" />Backup to remote storage
                                </label>
                                <span style="padding: 0 0.2em;"></span>

                                <?php
                                if(!$global)
                                {
                                    ?>
                                    <span id="mwp_wpvivid_create_schedule_backup_remote_selector_part" style="display: none;">
                                        <select id="mwp_wpvivid_create_schedule_backup_remote_selector">
                                            <?php
                                            $remoteslist=array();//WPvivid_Setting::get_all_remote_options();
                                            foreach ($remoteslist as $key=>$remote_option)
                                            {
                                                if($key=='remote_selected')
                                                {
                                                    continue;
                                                }
                                                if(!isset($remote_option['id']))
                                                {
                                                    $remote_option['id'] = $key;
                                                }
                                                ?>
                                                <option value="<?php echo esc_attr($remote_option['id']); ?>" selected="selected"><?php echo esc_html($remote_option['name']); ?></option>
                                                <?php
                                            }
                                            ?>
                                            <option value="all">All activated remote storage</option>
                                        </select>
                                    </span>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                        <p></p>

                        <div>
                            <p><span class="dashicons dashicons-screenoptions mwp-wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                            <div style="padding:0.5em;margin-bottom:0.5em;background:#eaf1fe;border-radius:8px;">
                                <?php
                                ?>
                                <fieldset>
                                    <?php
                                    $this->mwp_wpvivid_schedule_backup_type_addon($type, $global);
                                    ?>
                                </fieldset>
                                <?php
                                ?>
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                        <p></p>

                        <div id="wpvivid_global_custom_schedule_backup" style="display: none;">
                            <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                                <?php
                                $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                                $custom_backup_manager->set_parent_id('wpvivid_global_custom_schedule_backup','schedule_backup','0','1');
                                $custom_backup_manager->output_custom_backup_db_table();
                                $custom_backup_manager->output_custom_backup_file_table();
                                ?>
                            </div>
                        </div>
                        <p></p>

                        <!--Advanced Option (Exclude)-->
                        <div id="wpvivid_global_custom_schedule_advanced_option">
                            <?php
                            $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_global_custom_schedule_advanced_option');
                            $custom_backup_manager->output_advanced_option_table();
                            $custom_backup_manager->load_js();
                            ?>
                        </div>
                        <p></p>

                        <div>
                            <p>
                                <span class="dashicons dashicons-welcome-write-blog mwp-wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                                <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_schedule_backup" name="backup_prefix" id="wpvivid_set_schedule_prefix" value="" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="">
                            </p>
                        </div>

                    </div>

                    <div style="clear: both;"></div>

                    <div class="mwp-wpvivid-block-bottom-space">
                        <div id="mwp_wpvivid_schedule_create_notice"></div>
                        <?php
                        if($type === 'mwp_schedule_add'){
                            ?>
                            <input class="ui green mini button" type="button" id="mwp_wpvivid_create_schedule_btn" value="Create new schedule" onclick="mwp_wpvivid_create_schedule_addon('<?php echo esc_js($type); ?>', '<?php echo esc_js($global); ?>');" />
                            <?php
                        }
                        else{
                            ?>
                            <input class="ui green mini button" type="button" value="Update Schedule" onclick="mwp_wpvivid_edit_schedule_addon('<?php echo esc_js($type); ?>', '<?php echo esc_js($global); ?>');" />
                            <?php
                        }
                        ?>
                    </div>

                    <script>
                        var first_create = '1';

                        var time_offset=<?php echo esc_js($offset); ?>;
                        jQuery('input:radio[option=<?php echo esc_attr($type); ?>][name=mwp_schedule_add_backup_type]').click(function()
                        {
                            if(this.value === 'custom')
                            {
                                jQuery('#wpvivid_custom_schedule_backup').show();
                                jQuery('#wpvivid_global_custom_schedule_backup').show();
                                //jQuery( document ).trigger( 'wpvivid_refresh_schedule_backup_tables', 'schedule_backup' );
                            }
                            else
                            {
                                jQuery('#wpvivid_custom_schedule_backup').hide();
                                jQuery('#wpvivid_global_custom_schedule_backup').hide();
                            }
                        });
                    </script>
                </div>
                <?php
            }
            else{
                $type='mwp_schedule_add';
                $utc_time=date( 'H:i:s - m/d/Y ', time() );
                $offset = $this->time_zone;
                $local_time = time() + $offset * 60 * 60;
                $local_time = date("H:i:s - m/d/Y ", $local_time);
                $mwp_wpvivid_cycles = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles' : 'mwp_wpvivid_schedule_update_cycles';
                $mwp_wpvivid_cycles_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles_select' : 'mwp_wpvivid_schedule_update_cycles_select';
                $mwp_wpvivid_week = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week' : 'mwp_wpvivid_schedule_update_week';
                $mwp_wpvivid_week_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week_select' : 'mwp_wpvivid_schedule_update_week_select';
                $mwp_wpvivid_day = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day' : 'mwp_wpvivid_schedule_update_day';
                $mwp_wpvivid_day_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day_select' : 'mwp_wpvivid_schedule_update_day_select';
                $mwp_wpvivid_hour_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_hour_select' : 'mwp_wpvivid_schedule_update_hour_select';
                $mwp_wpvivid_minute_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_minute_select' : 'mwp_wpvivid_schedule_update_minute_select';
                $mwp_wpvivid_utc_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_utc_time' : 'mwp_wpvivid_schedule_update_utc_time';
                $mwp_wpvivid_start_local_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_local_time' : 'mwp_wpvivid_schedule_update_start_local_time';
                $mwp_wpvivid_start_utc_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_utc_time' : 'mwp_wpvivid_schedule_update_start_utc_time';
                $mwp_wpvivid_start_cycles = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_cycles' : 'mwp_wpvivid_schedule_update_start_cycles';
                $mwp_wpvivid_start_timezone = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_timezone' : 'mwp_wpvivid_schedule_update_start_timezone';
                $location = 'options-general.php';
                $mwp_wpvivid_timezone = $global === true ? admin_url().'options-general.php' : 'admin.php?page=SiteOpen&newWindow=yes&websiteid='.$this->site_id.'&location='.base64_encode($location).'&_opennonce='.wp_create_nonce( 'mainwp-admin-nonce' );
                $prefix = '';
                $prefix = apply_filters('mwp_wpvivid_get_backup_prefix', $prefix);
                ?>

                <div id="mwp_wpvivid_schedule_update_notice"></div>
                <div style="width: 100%; border: 1px solid #e5e5e5; float: left; box-sizing: border-box; margin-bottom: 10px; padding: 10px;">
                    <div class="mwp-wpvivid-block-bottom-space"><strong>Tips: </strong>Selected schedules will be executed sequentially. When there is a conflict of starting times for scheduled tasks, only one will be executed properly.</div>
                    <div id="mwp_wpvivid_schedule_list_addon"></div>
                    <?php
                    if($global===false){
                        ?>
                        <div style="margin-top: 10px; float: left;">
                            <?php if($global===false)
                            {
                                $save_change_id= 'mwp_wpvivid_schedule_save_addon';
                                ?>
                                <!--<input class="ui green mini button" id="<?php echo esc_attr($save_change_id); ?>" type="button" value="Save Changes" />-->
                                <?php
                            }
                            else
                            {
                                $save_change_id= 'mwp_wpvivid_global_schedule_save_addon';
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                    <div style="clear: both;"></div>
                </div>

                <div class="mwp-wpvivid-block-bottom-space">
                    <input class="ui green mini button" type="button" value="Create a job" onclick="mwp_wpvivid_create_schedule_job();">
                </div>

                <div id="mwp_wpvivid_schedule_backup_deploy" style="display: none;">
                    <div class="mwp-wpvivid-block-bottom-space" >
                        <table class="wp-list-table widefat plugin">
                            <thead>
                            <tr>
                                <th></th>
                                <th class="manage-column column-name column-primary"><strong>Local Time </strong><a
                                            href="<?php echo esc_url($mwp_wpvivid_timezone); ?>">(Timezone Setting)</a></th>
                                <th class="manage-column column-name column-primary"><strong>Universal Time (UTC)</strong></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <th><strong>Current Time</strong></th>
                                <td>
                                    <div>
                                        <div style="float: left; margin-right: 10px;"><?php echo esc_html($local_time); ?></div>
                                        <small>
                                            <div class="mwp-wpvivid-tooltip"
                                                 style="float: left; margin-top:3px; line-height: 100%;">?
                                                <div class="mwp-wpvivid-tooltiptext">Current time in the city or the UTC
                                                    timezone offset you have chosen in WordPress Timezone Settings.
                                                </div>
                                            </div>
                                        </small>
                                        <div style="clear: both;"></div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div style="float: left; margin-right: 10px;"><?php echo esc_html($utc_time); ?></div>
                                        <small>
                                            <div class="mwp-wpvivid-tooltip"
                                                 style="float: left; margin-top:3px; line-height: 100%;">?
                                                <div class="mwp-wpvivid-tooltiptext">Current local time in UTC.</div>
                                            </div>
                                        </small>
                                        <div style="clear: both;"></div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th><strong>Schedule Start Time</strong></th>
                                <td>
                        <span>
                            <div id="<?php echo esc_attr($mwp_wpvivid_cycles); ?>"
                                 style="padding: 0 10px 0 0; float: left;">
                                <select id="<?php echo esc_attr($mwp_wpvivid_cycles_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="recurrence"
                                        onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                    <option value="wpvivid_hourly">Every hour</option>
                                    <option value="wpvivid_2hours">Every 2 hours</option>
                                    <option value="wpvivid_4hours">Every 4 hours</option>
                                    <option value="wpvivid_8hours">Every 8 hours</option>
                                    <option value="wpvivid_12hours">Every 12 hours</option>
                                    <option value="wpvivid_daily" selected>Daily</option>
                                    <option value="wpvivid_weekly">Weekly</option>
                                    <option value="wpvivid_fortnightly">Fortnightly</option>
                                    <option value="wpvivid_monthly">30 Days</option>
                                </select>
                            </div>
                        </span>
                                    <span>
                            <div id="<?php echo esc_attr($mwp_wpvivid_week); ?>"
                                 style="padding: 0 10px 0 0; float: left; display: none;">
                                <select id="<?php echo esc_attr($mwp_wpvivid_week_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="week">
                                    <option value="sun" selected>Sunday</option>
                                    <option value="mon">Monday</option>
                                    <option value="tue">Tuesday</option>
                                    <option value="wed">Wednesday</option>
                                    <option value="thu">Thursday</option>
                                    <option value="fri">Friday</option>
                                    <option value="sat">Saturday</option>
                                </select>
                            </div>
                        </span>
                                    <span>
                            <div id="<?php echo esc_attr($mwp_wpvivid_day); ?>"
                                 style="padding: 0 10px 0 0; float: left; display: none;">
                                <div class="mwp-wpvivid-schedule-font-fix mwp-wpvivid-font-right-space"
                                     style="float: left;">Start at:</div>
                                <select id="<?php echo esc_attr($mwp_wpvivid_day_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="day">
                                    <?php
                                    for ($i = 1; $i < 31; $i++) {
                                        echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </span>
                                    <span>
                            <div style="padding: 0 10px 0 0;">
                                <select id="<?php echo esc_attr($mwp_wpvivid_hour_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="current_day_hour"
                                        style="margin-bottom: 4px;"
                                        onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                    <?php
                                    for ($hour = 0; $hour < 24; $hour++) {
                                        $format_hour = sprintf("%02d", $hour);
                                        echo '<option value="' . esc_attr($format_hour) . '">' . esc_html($format_hour) . '</option>';
                                    }
                                    ?>
                                </select>
                                <span>:</span>
                                <select id="<?php echo esc_attr($mwp_wpvivid_minute_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="current_day_minute"
                                        style="margin-bottom: 4px;"
                                        onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                    <?php
                                    for ($minute = 0; $minute < 60; $minute++) {
                                        $format_minute = sprintf("%02d", $minute);
                                        echo '<option value="' . esc_attr($format_minute) . '">' . esc_html($format_minute) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </span>
                                </td>
                                <td style="vertical-align: middle;">
                                    <div>
                                        <div id="<?php echo esc_attr($mwp_wpvivid_utc_time); ?>"
                                             style="float: left; margin-right: 10px;">00:00
                                        </div>
                                        <small>
                                            <div class="mwp-wpvivid-tooltip"
                                                 style="float: left; margin-top:3px; line-height: 100%;">?
                                                <div class="mwp-wpvivid-tooltiptext">The schedule start time in UTC.</div>
                                            </div>
                                        </small>
                                        <div style="clear: both;"></div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="3">
                                    <i>
                                        <span>The schedule will be performed at [(local time)</span>
                                        <span id="<?php echo esc_attr($mwp_wpvivid_start_local_time); ?>" style="margin-right: 0;">00:00</span>
                                        <span>] [UTC</span>
                                        <span id="<?php echo esc_attr($mwp_wpvivid_start_utc_time); ?>" style="margin-right: 0;">00:00</span>
                                        <span>] [Schedule Cycles:</span>
                                        <span id="<?php echo esc_attr($mwp_wpvivid_start_cycles); ?>" style="margin-right: 0;">Daily</span>]
                                    </i>
                                </th>
                            <tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin-top:0.5em; margin-bottom: 0.5em;">
                        <div>
                            <p><span class="dashicons dashicons-backup mwp-wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                            <div style="padding-left:2em;">
                                <label class="">
                                    <input type="radio" option="mwp_schedule_backup" name="schedule_save_local_remote" value="local" checked="checked" />Backup to localhost
                                </label>
                                <span style="padding: 0 1em;"></span>

                                <label class="">
                                    <input type="radio" option="mwp_schedule_backup" name="schedule_save_local_remote" value="remote" />Backup to remote storage
                                </label>
                                <span style="padding: 0 0.2em;"></span>

                                <span id="mwp_wpvivid_create_schedule_backup_remote_selector_part" style="display: none;">
                                <select id="mwp_wpvivid_create_schedule_backup_remote_selector">
                                    <?php
                                    $remoteslist=array();//WPvivid_Setting::get_all_remote_options();
                                    foreach ($remoteslist as $key=>$remote_option)
                                    {
                                        if($key=='remote_selected')
                                        {
                                            continue;
                                        }
                                        if(!isset($remote_option['id']))
                                        {
                                            $remote_option['id'] = $key;
                                        }
                                        ?>
                                        <option value="<?php echo esc_attr($remote_option['id']); ?>" selected="selected"><?php echo esc_html($remote_option['name']); ?></option>
                                        <?php
                                    }
                                    ?>
                                    <option value="all">All activated remote storage</option>
                                </select>
                            </span>

                            </div>
                        </div>

                        <div style="clear: both;"></div>
                        <p></p>

                        <div>
                            <p><span class="dashicons dashicons-screenoptions mwp-wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                            <div style="padding:1em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                                <?php
                                $fieldset_style = '';
                                ?>
                                <fieldset style="<?php echo esc_attr($fieldset_style); ?>">
                                    <?php
                                    $this->mwp_wpvivid_schedule_backup_type_addon($type, $global);
                                    ?>
                                </fieldset>
                                <?php
                                ?>
                            </div>
                        </div>

                        <div id="wpvivid_custom_schedule_backup" style="display: none;">
                            <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                                <?php
                                $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                                $custom_backup_manager->set_site_id($this->site_id);
                                $custom_backup_manager->set_parent_id('wpvivid_custom_schedule_backup','schedule_backup','0','0');
                                $custom_backup_manager->output_custom_backup_db_table();
                                $custom_backup_manager->output_custom_backup_file_table();
                                ?>
                            </div>
                        </div>

                        <!--Advanced Option (Exclude)-->
                        <div id="wpvivid_custom_schedule_advanced_option">
                            <?php
                            $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_schedule_advanced_option');
                            $custom_backup_manager->output_advanced_option_table();
                            $custom_backup_manager->load_js();
                            ?>
                        </div>

                        <div>
                            <p>
                                <span class="dashicons dashicons-welcome-write-blog mwp-wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                                <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_schedule_backup" name="backup_prefix" id="wpvivid_set_schedule_prefix" value="<?php echo esc_attr($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php echo esc_attr($prefix); ?>">
                            </p>
                        </div>

                    </div>

                    <div style="clear: both;"></div>

                    <div class="mwp-wpvivid-block-bottom-space">
                        <div id="mwp_wpvivid_schedule_create_notice"></div>
                        <?php
                        if($type === 'mwp_schedule_add'){
                            ?>
                            <input class="ui green mini button" type="button" id="mwp_wpvivid_create_schedule_btn" value="Create new schedule" onclick="mwp_wpvivid_create_schedule_addon('<?php echo esc_js($type); ?>', '<?php echo esc_js($global); ?>');" />
                            <?php
                        }
                        else{
                            ?>
                            <input class="ui green mini button" type="button" value="Update Schedule" onclick="mwp_wpvivid_edit_schedule_addon('<?php echo esc_js($type); ?>', '<?php echo esc_js($global); ?>');" />
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <script>
                    var first_create = '1';
                    var time_offset=<?php echo esc_js($offset); ?>;

                    jQuery('input:radio[option=<?php echo esc_attr($type); ?>][name=mwp_schedule_add_backup_type]').click(function()
                    {
                        if(this.value === 'custom')
                        {
                            jQuery('#wpvivid_custom_schedule_backup').show();
                            jQuery('#wpvivid_global_custom_schedule_backup').show();
                            //jQuery( document ).trigger( 'wpvivid_refresh_schedule_backup_tables', 'schedule_backup' );
                        }
                        else
                        {
                            jQuery('#wpvivid_custom_schedule_backup').hide();
                            jQuery('#wpvivid_global_custom_schedule_backup').hide();
                        }
                    });

                    function mwp_wpvivid_create_schedule_job()
                    {
                        jQuery('#mwp_wpvivid_schedule_backup_deploy').show();
                    }
                </script>
                <?php
            }
            ?>
        </div>
        <script>
            var mwp_edit_global_schedule_mould_name = '';

            function mwp_wpvivid_create_new_schedule_mould()
            {
                jQuery('#mwp_wpvivid_schedule_mould_part_1').hide();
                jQuery('#mwp_wpvivid_schedule_mould_part_2').show();
            }

            function mwp_wpvivid_back_schedule_mould()
            {
                window.location.href = window.location.href;
            }

            jQuery('#mwp_wpvivid_schedule_mould_name_edit').click(function(){
                jQuery('#mwp_wpvivid_schedule_mould_name').attr('disabled', false);
                jQuery('#mwp_wpvivid_create_schedule_btn').attr('disabled', true);
                jQuery('#mwp_wpvivid_schedule_mould_name_edit').hide();
                jQuery('#mwp_wpvivid_schedule_mould_name_save').show();
            });

            jQuery('#mwp_wpvivid_schedule_mould_name_save').click(function(){
                jQuery('#mwp_wpvivid_schedule_create_notice').html('');

                var schedule_mould_name = jQuery('#mwp_wpvivid_schedule_mould_name').val();
                if(schedule_mould_name == ''){
                    alert('A schedule mould name is required.');
                    return;
                }

                if(mwp_edit_global_schedule_mould_name === schedule_mould_name)
                {
                    jQuery('#mwp_wpvivid_schedule_mould_name').attr('disabled', true);
                    jQuery('#mwp_wpvivid_create_schedule_btn').attr('disabled', false);
                    jQuery('#mwp_wpvivid_schedule_mould_name_edit').show();
                    jQuery('#mwp_wpvivid_schedule_mould_name_save').hide();
                }
                else
                {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_edit_global_schedule_mould_name_addon',
                        'schedule_mould_name': schedule_mould_name,
                        'schedule_mould_old_name': mwp_edit_global_schedule_mould_name
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_schedule_mould_name').attr('disabled', true);
                                jQuery('#mwp_wpvivid_create_schedule_btn').attr('disabled', false);
                                jQuery('#mwp_wpvivid_schedule_mould_name_edit').show();
                                jQuery('#mwp_wpvivid_schedule_mould_name_save').hide();
                            }
                            else {
                                jQuery('#mwp_wpvivid_schedule_create_notice').html(jsonarray.notice);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('editing schedule mould name', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on('click', '.mwp-wpvivid-sync-schedule-mould', function(){
                var Obj=jQuery(this);
                var mould_name=Obj.closest('tr').attr('slug');
                window.location.href = window.location.href + "&synchronize=1&addon=1&mould_name=" + mould_name;
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on('click', '.mwp-wpvivid-schedule-mould-edit', function(){
                jQuery('#mwp_wpvivid_schedule_mould_part_1').hide();
                jQuery('#mwp_wpvivid_schedule_mould_part_2').show();
                var Obj=jQuery(this);
                var mould_name=Obj.closest('tr').attr('slug');
                mwp_wpvivid_edit_schedule_mould(mould_name);
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on('click', '.mwp-wpvivid-schedule-mould-delete', function(){
                var descript = 'Are you sure to remove this schedule mould?';
                var ret = confirm(descript);
                if(ret === true) {
                    var Obj = jQuery(this);
                    var mould_name = Obj.closest('tr').attr('slug');
                    mwp_wpvivid_delete_schedule_mould(mould_name);
                }
            });

            function mwp_wpvivid_edit_schedule_mould(mould_name)
            {
                mwp_edit_global_schedule_mould_name = mould_name;
                jQuery('#mwp_wpvivid_schedule_mould_name').val(mould_name);
                jQuery('#mwp_wpvivid_schedule_mould_name').attr('disabled', 'disabled');
                jQuery('#mwp_wpvivid_schedule_mould_name_edit').show();
                first_create = '0';
                var ajax_data = {
                    'action': 'mwp_wpvivid_edit_global_schedule_mould_addon',
                    'mould_name': mould_name
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_global_schedule_list_addon').html(jsonarray.html);
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('editing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_delete_schedule_mould(mould_name)
            {
                var ajax_data = {
                    'action': 'mwp_wpvivid_delete_global_schedule_mould_addon',
                    'mould_name': mould_name
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_schedule_mould_list_addon').html(jsonarray.html);
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('editing schedule', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on("click",'.first-page',function() {
                mwp_wpvivid_get_schedule_mould_list('first');
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_schedule_mould_list(page-1);
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_schedule_mould_list(page+1);
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on("click",'.last-page',function() {
                mwp_wpvivid_get_schedule_mould_list('last');
            });

            jQuery('#mwp_wpvivid_schedule_mould_list_addon').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_schedule_mould_list(page);
                }
            });

            function mwp_wpvivid_get_schedule_mould_list(page=0) {
                if(page === 0){
                    var current_page = jQuery('#mwp_wpvivid_schedule_mould_list_addon').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_schedule_mould_list_addon').find('.current-page').val();
                    }
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_schedule_mould_list',
                    'page':page
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_schedule_mould_list_addon').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_schedule_mould_list_addon').html(jsonarray.schedule_mould_list);
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
                        mwp_wpvivid_get_schedule_mould_list();
                    }, 3000);
                });
            }
        </script>
        <?php
    }

    public function output_schedules_edit_page($global){
        ?>
        <div style="margin-top: 10px;">
            <?php
            $type='mwp_schedule_update';
            ?>
            <?php
            $utc_time=date( 'H:i:s - m/d/Y ', time() );
            if($global) {
            $offset = get_option('gmt_offset');
            $local_time=date( 'H:i:s - m/d/Y ', current_time( 'timestamp', 0 ) );
            }
            else{
            $offset = $this->time_zone;
            $local_time = time() + $offset * 60 * 60;
            $local_time = date("H:i:s - m/d/Y ", $local_time);
            }
            $mwp_wpvivid_cycles = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles' : 'mwp_wpvivid_schedule_update_cycles';
            $mwp_wpvivid_cycles_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles_select' : 'mwp_wpvivid_schedule_update_cycles_select';
            $mwp_wpvivid_week = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week' : 'mwp_wpvivid_schedule_update_week';
            $mwp_wpvivid_week_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week_select' : 'mwp_wpvivid_schedule_update_week_select';
            $mwp_wpvivid_day = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day' : 'mwp_wpvivid_schedule_update_day';
            $mwp_wpvivid_day_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day_select' : 'mwp_wpvivid_schedule_update_day_select';
            $mwp_wpvivid_hour_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_hour_select' : 'mwp_wpvivid_schedule_update_hour_select';
            $mwp_wpvivid_minute_select = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_minute_select' : 'mwp_wpvivid_schedule_update_minute_select';
            $mwp_wpvivid_utc_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_utc_time' : 'mwp_wpvivid_schedule_update_utc_time';
            $mwp_wpvivid_start_local_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_local_time' : 'mwp_wpvivid_schedule_update_start_local_time';
            $mwp_wpvivid_start_utc_time = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_utc_time' : 'mwp_wpvivid_schedule_update_start_utc_time';
            $mwp_wpvivid_start_cycles = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_cycles' : 'mwp_wpvivid_schedule_update_start_cycles';
            $mwp_wpvivid_start_timezone = $type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_timezone' : 'mwp_wpvivid_schedule_update_start_timezone';
            $location = 'options-general.php';
            $mwp_wpvivid_timezone = $global === true ? admin_url().'options-general.php' : 'admin.php?page=SiteOpen&newWindow=yes&websiteid='.$this->site_id.'&location='.base64_encode($location).'&_opennonce='.wp_create_nonce( 'mainwp-admin-nonce' );
            ?>
            <?php
            if(!$global) {
                ?>
                <div class="mwp-wpvivid-block-bottom-space">
                    <table class="wp-list-table widefat plugin">
                        <thead>
                        <tr>
                            <th></th>
                            <th class="manage-column column-name column-primary"><strong>Local Time </strong><a
                                        href="<?php echo esc_url($mwp_wpvivid_timezone); ?>">(Timezone Setting)</a></th>
                            <th class="manage-column column-name column-primary"><strong>Universal Time (UTC)</strong></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <th><strong>Current Time</strong></th>
                            <td>
                                <div>
                                    <div style="float: left; margin-right: 10px;"><?php echo esc_html($local_time); ?></div>
                                    <small>
                                        <div class="mwp-wpvivid-tooltip"
                                             style="float: left; margin-top:3px; line-height: 100%;">?
                                            <div class="mwp-wpvivid-tooltiptext">Current time in the city or the UTC
                                                timezone offset you have chosen in WordPress Timezone Settings.
                                            </div>
                                        </div>
                                    </small>
                                    <div style="clear: both;"></div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div style="float: left; margin-right: 10px;"><?php echo esc_html($utc_time); ?></div>
                                    <small>
                                        <div class="mwp-wpvivid-tooltip"
                                             style="float: left; margin-top:3px; line-height: 100%;">?
                                            <div class="mwp-wpvivid-tooltiptext">Current local time in UTC.</div>
                                        </div>
                                    </small>
                                    <div style="clear: both;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th><strong>Schedule Start Time</strong></th>
                            <td>
                        <span>
                            <div id="<?php echo esc_attr($mwp_wpvivid_cycles); ?>"
                                 style="padding: 0 10px 0 0; float: left;">
                                <select id="<?php echo esc_attr($mwp_wpvivid_cycles_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="recurrence"
                                        onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                    <option value="wpvivid_hourly">Every hour</option>
                                    <option value="wpvivid_2hours">Every 2 hours</option>
                                    <option value="wpvivid_4hours">Every 4 hours</option>
                                    <option value="wpvivid_8hours">Every 8 hours</option>
                                    <option value="wpvivid_12hours">Every 12 hours</option>
                                    <option value="wpvivid_daily" selected>Daily</option>
                                    <option value="wpvivid_weekly">Weekly</option>
                                    <option value="wpvivid_fortnightly">Fortnightly</option>
                                    <option value="wpvivid_monthly">30 Days</option>
                                </select>
                            </div>
                        </span>
                                <span>
                            <div id="<?php echo esc_attr($mwp_wpvivid_week); ?>"
                                 style="padding: 0 10px 0 0; float: left; display: none;">
                                <select id="<?php echo esc_attr($mwp_wpvivid_week_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="week">
                                    <option value="sun" selected>Sunday</option>
                                    <option value="mon">Monday</option>
                                    <option value="tue">Tuesday</option>
                                    <option value="wed">Wednesday</option>
                                    <option value="thu">Thursday</option>
                                    <option value="fri">Friday</option>
                                    <option value="sat">Saturday</option>
                                </select>
                            </div>
                        </span>
                                <span>
                            <div id="<?php echo esc_attr($mwp_wpvivid_day); ?>"
                                 style="padding: 0 10px 0 0; float: left; display: none;">
                                <div class="mwp-wpvivid-schedule-font-fix mwp-wpvivid-font-right-space"
                                     style="float: left;">Start at:</div>
                                <select id="<?php echo esc_attr($mwp_wpvivid_day_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="day">
                                    <?php
                                    for ($i = 1; $i < 31; $i++) {
                                        echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </span>
                                <span>
                            <div style="padding: 0 10px 0 0;">
                                <select id="<?php echo esc_attr($mwp_wpvivid_hour_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="current_day_hour"
                                        style="margin-bottom: 4px;"
                                        onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                    <?php
                                    for ($hour = 0; $hour < 24; $hour++) {
                                        $format_hour = sprintf("%02d", $hour);
                                        echo '<option value="' . esc_attr($format_hour) . '">' . esc_html($format_hour) . '</option>';
                                    }
                                    ?>
                                </select>
                                <span>:</span>
                                <select id="<?php echo esc_attr($mwp_wpvivid_minute_select); ?>"
                                        option="<?php echo esc_attr($type); ?>" name="current_day_minute"
                                        style="margin-bottom: 4px;"
                                        onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                    <?php
                                    for ($minute = 0; $minute < 60; $minute++) {
                                        $format_minute = sprintf("%02d", $minute);
                                        echo '<option value="' . esc_attr($format_minute) . '">' . esc_html($format_minute) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </span>
                            </td>
                            <td style="vertical-align: middle;">
                                <div>
                                    <div id="<?php echo esc_attr($mwp_wpvivid_utc_time); ?>"
                                         style="float: left; margin-right: 10px;">00:00
                                    </div>
                                    <small>
                                        <div class="mwp-wpvivid-tooltip"
                                             style="float: left; margin-top:3px; line-height: 100%;">?
                                            <div class="mwp-wpvivid-tooltiptext">The schedule start time in UTC.</div>
                                        </div>
                                    </small>
                                    <div style="clear: both;"></div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th colspan="3">
                                <i>
                                    <span>The schedule will be performed at [(local time)</span>
                                    <span id="<?php echo esc_attr($mwp_wpvivid_start_local_time); ?>" style="margin-right: 0;">00:00</span>
                                    <span>] [UTC</span>
                                    <span id="<?php echo esc_attr($mwp_wpvivid_start_utc_time); ?>" style="margin-right: 0;">00:00</span>
                                    <span>] [Schedule Cycles:</span>
                                    <span id="<?php echo esc_attr($mwp_wpvivid_start_cycles); ?>" style="margin-right: 0;">Daily</span>]
                                </i>
                            </th>
                        <tr>
                        </tfoot>
                    </table>
                </div>
                <?php
            }
            else{
                ?>
                <div style="width:100%; border:1px solid #e5e5e5; float:left; box-sizing: border-box;margin-bottom:10px;">
                    <div class="mwp-wpvivid-block-bottom-space" style="margin: 1px 1px 10px 1px; background-color: #f7f7f7; box-sizing: border-box; padding: 10px;">Set backup cycle and start time:</div>
                    <div class="mwp-wpvivid-block-bottom-space" style="margin-left: 10px; margin-right: 10px;">
                        <div style="padding: 4px 10px 0 0; float: left;">The backup will run</div>
                        <div id="<?php echo esc_attr($mwp_wpvivid_cycles); ?>" style="padding: 0 10px 0 0; float: left;">
                            <select id="<?php echo esc_attr($mwp_wpvivid_cycles_select); ?>" option="<?php echo esc_attr($type); ?>" name="recurrence" onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                <option value="wpvivid_hourly">Every hour</option>
                                <option value="wpvivid_2hours">Every 2 hours</option>
                                <option value="wpvivid_4hours">Every 4 hours</option>
                                <option value="wpvivid_8hours">Every 8 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily" selected>Daily</option>
                                <option value="wpvivid_weekly">Weekly</option>
                                <option value="wpvivid_fortnightly">Fortnightly</option>
                                <option value="wpvivid_monthly">30 Days</option>
                            </select>
                        </div>
                        <div style="padding: 4px 10px 0 0; float: left;">at</div>
                        <div id="<?php echo esc_attr($mwp_wpvivid_week); ?>" style="padding: 0 10px 0 0; float: left; display: none;">
                            <select id="<?php echo esc_attr($mwp_wpvivid_week_select); ?>" option="<?php echo esc_attr($type); ?>" name="week">
                                <option value="sun" selected>Sunday</option>
                                <option value="mon">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </div>
                        <div id="<?php echo esc_attr($mwp_wpvivid_day); ?>" style="padding: 0 10px 0 0; float: left; display: none;">
                            <select id="<?php echo esc_attr($mwp_wpvivid_day_select); ?>" option="<?php echo esc_attr($type); ?>" name="day">
                                <?php
                                for ($i = 1; $i < 31; $i++) {
                                    echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div style="padding: 0 10px 0 0; float: left;">
                            <select id="<?php echo esc_attr($mwp_wpvivid_hour_select); ?>" option="<?php echo esc_attr($type); ?>" name="current_day_hour" style="margin-bottom: 4px;" onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                <?php
                                for ($hour = 0; $hour < 24; $hour++) {
                                    $format_hour = sprintf("%02d", $hour);
                                    echo '<option value="' . esc_attr($format_hour) . '">' . esc_html($format_hour) . '</option>';
                                }
                                ?>
                            </select>
                            <span>:</span>
                            <select id="<?php echo esc_attr($mwp_wpvivid_minute_select); ?>" option="<?php echo esc_attr($type); ?>" name="current_day_minute" style="margin-bottom: 4px;" onchange="mwp_wpvivid_set_schedule('<?php echo esc_attr($type); ?>');">
                                <?php
                                for ($minute = 0; $minute < 60; $minute++) {
                                    $format_minute = sprintf("%02d", $minute);
                                    echo '<option value="' . esc_attr($format_minute) . '">' . esc_html($format_minute) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div style="clear: both;"></div>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin-top:0.5em;">
                <div>
                    <p><span class="dashicons dashicons-backup mwp-wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                    <div style="padding-left:2em;">
                        <?php
                        if($global)
                        {
                            ?>
                            <label class="">
                                <input type="radio" option="mwp_update_schedule_backup" name="update_schedule_save_local_remote" value="local" checked="checked" />Backup to localhost
                            </label>
                            <span style="padding: 0 1em;"></span>

                            <label class="">
                                <input type="radio" option="mwp_update_schedule_backup" name="update_schedule_save_local_remote" value="remote" />Backup to remote storage
                            </label>
                            <span style="padding: 0 0.2em;"></span>
                            <?php
                        }
                        else
                        {
                            ?>
                            <label class="">
                                <input type="radio" option="mwp_update_schedule_backup" name="update_schedule_save_local_remote" value="local" checked="checked" />Backup to localhost
                            </label>
                            <span style="padding: 0 1em;"></span>

                            <label class="">
                                <input type="radio" option="mwp_update_schedule_backup" name="update_schedule_save_local_remote" value="remote" />Backup to remote storage
                            </label>
                            <span style="padding: 0 0.2em;"></span>
                            <?php
                        }
                        ?>
                        <?php
                        if(!$global)
                        {
                            ?>
                            <span id="mwp_wpvivid_update_schedule_backup_remote_selector_part" style="display: none;">
                            <select id="mwp_wpvivid_update_schedule_backup_remote_selector">
                                <?php
                                $remoteslist=array();//WPvivid_Setting::get_all_remote_options();
                                foreach ($remoteslist as $key=>$remote_option)
                                {
                                    if($key=='remote_selected')
                                    {
                                        continue;
                                    }
                                    if(!isset($remote_option['id']))
                                    {
                                        $remote_option['id'] = $key;
                                    }
                                    ?>
                                    <option value="<?php echo esc_attr($remote_option['id']); ?>" selected="selected"><?php echo esc_html($remote_option['name']); ?></option>
                                    <?php
                                }
                                ?>
                                <option value="all">All activated remote storage</option>
                                </select>
                            </span>
                            <?php
                        }
                        ?>
                    </div>
                </div>

                <div style="clear: both;"></div>
                <p></p>

                <div>
                    <p><span class="dashicons dashicons-screenoptions mwp-wpvivid-dashicons-blue"></span><span><strong>Backup Content</strong></span></p>
                    <div style="padding:0.5em;margin-bottom:0.5em;background:#eaf1fe;border-radius:8px;">
                        <?php
                        ?>
                        <fieldset>
                            <?php
                            $this->mwp_wpvivid_schedule_backup_type_addon($type, $global);
                            ?>
                        </fieldset>
                        <?php
                        ?>
                    </div>
                </div>

                <p></p>

                <?php
                if(!$global)
                {
                    $prefix = '';
                    $prefix = apply_filters('mwp_wpvivid_get_backup_prefix', $prefix);
                    ?>
                    <div id="wpvivid_custom_update_schedule_backup" style="display: none;">
                        <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                            <?php
                            $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                            $custom_backup_manager->set_site_id($this->site_id);
                            $custom_backup_manager->set_parent_id('wpvivid_custom_update_schedule_backup','schedule_backup','0','0');
                            $custom_backup_manager->output_custom_backup_db_table();
                            $custom_backup_manager->output_custom_backup_file_table();
                            ?>
                        </div>
                    </div>

                    <!--Advanced Option (Exclude)-->
                    <div id="wpvivid_custom_update_schedule_advanced_option">
                        <?php
                        $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_custom_update_schedule_advanced_option');
                        $custom_backup_manager->output_advanced_option_table();
                        $custom_backup_manager->load_js();
                        ?>
                    </div>

                    <p></p>

                    <div>
                        <p>
                            <span class="dashicons dashicons-welcome-write-blog mwp-wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                            <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_update_schedule_backup" name="backup_prefix" id="wpvivid_set_schedule_prefix" value="<?php echo esc_attr($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php echo esc_attr($prefix); ?>">
                        </p>
                    </div>
                    <?php
                }
                else
                {
                    ?>
                    <div id="wpvivid_global_custom_update_schedule_backup" style="display: none;">
                        <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                            <?php
                            $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                            $custom_backup_manager->set_parent_id('wpvivid_global_custom_update_schedule_backup','schedule_backup','0','1');
                            $custom_backup_manager->output_custom_backup_db_table();
                            $custom_backup_manager->output_custom_backup_file_table();
                            ?>
                        </div>
                    </div>

                    <!--Advanced Option (Exclude)-->
                    <div id="wpvivid_global_custom_update_schedule_advanced_option">
                        <?php
                        $custom_backup_manager->wpvivid_set_advanced_id('wpvivid_global_custom_update_schedule_advanced_option');
                        $custom_backup_manager->output_advanced_option_table();
                        $custom_backup_manager->load_js();
                        ?>
                    </div>

                    <p></p>

                    <div>
                        <p>
                            <span class="dashicons dashicons-welcome-write-blog mwp-wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                            <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_update_schedule_backup" name="backup_prefix" id="wpvivid_set_schedule_prefix" value="" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="">
                        </p>
                    </div>
                    <?php
                }
                ?>
            </div>

            <div style="clear: both;"></div>
            <p></p>

            <div class="mwp-wpvivid-block-bottom-space">
                <div id="mwp_wpvivid_schedule_create_notice"></div>
                <?php
                if($type === 'mwp_schedule_add'){
                    ?>
                    <input class="ui green mini button" type="button" id="mwp_wpvivid_create_schedule_btn" value="Create new schedule" onclick="mwp_wpvivid_create_schedule_addon('<?php echo esc_js($type); ?>', '<?php echo esc_js($global); ?>');" />
                    <?php
                }
                else{
                    ?>
                    <input class="ui green mini button" type="button" value="Update Schedule" onclick="mwp_wpvivid_edit_schedule_addon('<?php echo esc_js($type); ?>', '<?php echo esc_js($global); ?>');" />
                    <?php
                }
                ?>
            </div>

            <script>
                var first_create = '1';

                function mwp_wpvivid_create_schedule_addon(type, global){
                    var mwp_wpvivid_utc_time = type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_utc_time' : 'mwp_wpvivid_schedule_update_utc_time';
                    var schedule_data = '';
                    schedule_data = mwp_wpvivid_ajax_data_transfer('mwp_schedule_add');
                    schedule_data = JSON.parse(schedule_data);
                    if(global){
                        var exclude_dirs = mwp_wpvivid_get_global_exclude_json('wpvivid_global_custom_schedule_advanced_option');
                    }
                    else {
                        var exclude_dirs = mwp_wpvivid_get_exclude_json('wpvivid_custom_schedule_advanced_option');
                    }

                    var custom_option = {
                        'exclude_files': exclude_dirs
                    };
                    jQuery.extend(schedule_data, custom_option);

                    if(global){
                        var exclude_file_type = mwp_wpvivid_get_exclude_file_type('wpvivid_global_custom_schedule_advanced_option');
                    }
                    else {
                        var exclude_file_type = mwp_wpvivid_get_exclude_file_type('wpvivid_custom_schedule_advanced_option');
                    }

                    var exclude_file_type_option = {
                        'exclude_file_type': exclude_file_type
                    };
                    jQuery.extend(schedule_data, exclude_file_type_option);
                    schedule_data = JSON.stringify(schedule_data);


                    jQuery('input:radio[option=mwp_schedule_add][name=mwp_schedule_add_backup_type]').each(function ()
                    {
                        if (jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            if (value === 'custom')
                            {
                                schedule_data = JSON.parse(schedule_data);
                                if(global){
                                    var custom_dirs = mwp_wpvivid_get_custom_setting_json_ex('wpvivid_global_custom_schedule_backup');
                                }
                                else {
                                    var custom_dirs = mwp_wpvivid_get_custom_setting_json_ex('wpvivid_custom_schedule_backup');
                                }
                                var custom_option = {
                                    'custom_dirs': custom_dirs
                                };
                                jQuery.extend(schedule_data, custom_option);
                                schedule_data = JSON.stringify(schedule_data);
                            }
                        }
                    });

                    jQuery('input:radio[option=mwp_schedule_backup][name=schedule_save_local_remote]').each(function ()
                    {
                        if (jQuery(this).prop('checked'))
                        {
                            schedule_data = JSON.parse(schedule_data);
                            if (this.value === 'remote')
                            {
                                if(global)
                                {
                                    var local_remote_option = {
                                        'save_local_remote': this.value
                                    };
                                }
                                else
                                {
                                    var remote_id_select = jQuery('#mwp_wpvivid_create_schedule_backup_remote_selector').val();
                                    var local_remote_option = {
                                        'save_local_remote': this.value,
                                        'remote_id_select': remote_id_select
                                    };
                                }
                            }
                            else
                            {
                                var local_remote_option = {
                                    'save_local_remote': this.value
                                };
                            }
                            jQuery.extend(schedule_data, local_remote_option);
                            schedule_data = JSON.stringify(schedule_data);
                        }
                    });

                    schedule_data = JSON.parse(schedule_data);
                    var backup_prefix = jQuery('input:text[option=mwp_schedule_backup][name=backup_prefix]').val();
                    var backup_prefix_option = {
                        'backup_prefix': backup_prefix
                    };
                    jQuery.extend(schedule_data, backup_prefix_option);
                    schedule_data = JSON.stringify(schedule_data);

                    if(global){
                        schedule_data = JSON.parse(schedule_data);
                        schedule_data['save_local_remote'] = schedule_data['save_local_remote'];
                        schedule_data['schedule_backup_backup_type'] = schedule_data['mwp_schedule_add_backup_type'];
                        schedule_data['status'] = 'Active';
                        schedule_data = JSON.stringify(schedule_data);
                        var schedule_mould_name = jQuery('#mwp_wpvivid_schedule_mould_name').val();
                        if(schedule_mould_name == ''){
                            alert('A schedule mould name is required.');
                            return;
                        }
                        var ajax_data = {
                            'action': 'mwp_wpvivid_global_create_schedule_addon',
                            'schedule': schedule_data,
                            'schedule_mould_name': schedule_mould_name,
                            'first_create': first_create
                        };
                    }
                    else {
                        //var utc_time = jQuery('#'+mwp_wpvivid_utc_time).html();
                        //var arr = new Array();
                        //arr = utc_time.split(':');
                        schedule_data = JSON.parse(schedule_data);
                        schedule_data['save_local_remote'] = schedule_data['save_local_remote'];
                        schedule_data['schedule_backup_backup_type'] = schedule_data['mwp_schedule_add_backup_type'];
                        //schedule_data['current_day_hour'] = arr[0];
                        //schedule_data['current_day_minute'] = arr[1];
                        schedule_data['status'] = 'Active';
                        schedule_data = JSON.stringify(schedule_data);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_create_schedule_addon',
                            'schedule': schedule_data,
                            'site_id': '<?php echo esc_html($this->site_id); ?>'
                        };
                    }
                    jQuery('#mwp_wpvivid_schedule_create_notice').html('');
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(global) {
                                    first_create = '0';
                                    jQuery('#mwp_wpvivid_schedule_create_notice').html(jsonarray.notice);
                                    jQuery('#mwp_wpvivid_global_schedule_list_addon').html(jsonarray.html);
                                }
                                else{
                                    jQuery('#mwp_wpvivid_schedule_create_notice').html(jsonarray.notice);
                                    jQuery('#mwp_wpvivid_schedule_list_addon').html(jsonarray.html);
                                    jQuery('#mwp_wpvivid_schedule_backup_deploy').hide();
                                }
                            }
                            else {
                                jQuery('#mwp_wpvivid_schedule_create_notice').html(jsonarray.notice);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                function mwp_wpvivid_edit_schedule_addon(type, global){
                    var mwp_wpvivid_utc_time = type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_utc_time' : 'mwp_wpvivid_schedule_update_utc_time';
                    var schedule_data = '';
                    schedule_data = mwp_wpvivid_ajax_data_transfer('mwp_schedule_update');
                    schedule_data = JSON.parse(schedule_data);
                    if(global){
                        var exclude_dirs = mwp_wpvivid_get_global_exclude_json('wpvivid_global_custom_update_schedule_advanced_option');
                    }
                    else {
                        var exclude_dirs = mwp_wpvivid_get_exclude_json('wpvivid_custom_update_schedule_advanced_option');
                    }

                    var custom_option = {
                        'exclude_files': exclude_dirs
                    };
                    jQuery.extend(schedule_data, custom_option);

                    if(global){
                        var exclude_file_type = mwp_wpvivid_get_exclude_file_type('wpvivid_global_custom_update_schedule_advanced_option');
                    }
                    else {
                        var exclude_file_type = mwp_wpvivid_get_exclude_file_type('wpvivid_custom_update_schedule_advanced_option');
                    }

                    var exclude_file_type_option = {
                        'exclude_file_type': exclude_file_type
                    };
                    jQuery.extend(schedule_data, exclude_file_type_option);
                    schedule_data = JSON.stringify(schedule_data);

                    jQuery('input:radio[option=mwp_schedule_update][name=mwp_schedule_update_backup_type]').each(function ()
                    {
                        if (jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            if (value === 'custom')
                            {
                                schedule_data = JSON.parse(schedule_data);
                                if(global){
                                    var custom_dirs = mwp_wpvivid_get_custom_setting_json_ex('wpvivid_global_custom_update_schedule_backup');
                                }
                                else {
                                    var custom_dirs = mwp_wpvivid_get_custom_setting_json_ex('wpvivid_custom_update_schedule_backup');
                                }
                                var custom_option = {
                                    'custom_dirs': custom_dirs
                                };
                                jQuery.extend(schedule_data, custom_option);
                                schedule_data = JSON.stringify(schedule_data);
                            }
                        }
                    });

                    jQuery('input:radio[option=mwp_update_schedule_backup][name=update_schedule_save_local_remote]').each(function ()
                    {
                        if (jQuery(this).prop('checked'))
                        {
                            schedule_data = JSON.parse(schedule_data);
                            if (this.value === 'remote')
                            {
                                var remote_id_select = jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector').val();
                                var local_remote_option = {
                                    'save_local_remote': this.value,
                                    'remote_id_select': remote_id_select
                                };
                            }
                            else
                            {
                                var local_remote_option = {
                                    'save_local_remote': this.value
                                };
                            }
                            jQuery.extend(schedule_data, local_remote_option);
                            schedule_data = JSON.stringify(schedule_data);
                        }
                    });

                    schedule_data = JSON.parse(schedule_data);
                    var backup_prefix = jQuery('input:text[option=mwp_update_schedule_backup][name=backup_prefix]').val();
                    var backup_prefix_option = {
                        'backup_prefix': backup_prefix
                    };
                    jQuery.extend(schedule_data, backup_prefix_option);
                    schedule_data = JSON.stringify(schedule_data);

                    if(global){
                        var schedule_mould_name = mwp_wpvivid_global_edit_schedule_mould_name;
                        schedule_data = JSON.parse(schedule_data);
                        schedule_data['update_schedule_backup_save_local_remote'] = schedule_data['mwp_schedule_update_save_local_remote'];
                        schedule_data['update_schedule_backup_backup_type'] = schedule_data['mwp_schedule_update_backup_type'];
                        schedule_data['status'] = 'Active';
                        schedule_data['schedule_id'] = mwp_wpvivid_global_edit_schedule_id;
                        schedule_data = JSON.stringify(schedule_data);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_global_update_schedule_addon',
                            'schedule': schedule_data,
                            'mould_name': schedule_mould_name
                        };
                    }
                    else {
                        //var utc_time = jQuery('#'+mwp_wpvivid_utc_time).html();
                        //var arr = new Array();
                        //arr = utc_time.split(':');
                        schedule_data = JSON.parse(schedule_data);
                        schedule_data['update_schedule_backup_save_local_remote'] = schedule_data['mwp_schedule_update_save_local_remote'];
                        schedule_data['update_schedule_backup_backup_type'] = schedule_data['mwp_schedule_update_backup_type'];
                        //schedule_data['current_day_hour'] = arr[0];
                        //schedule_data['current_day_minute'] = arr[1];
                        schedule_data['status'] = 'Active';
                        schedule_data['schedule_id'] = mwp_wpvivid_edit_schedule_id;
                        schedule_data = JSON.stringify(schedule_data);
                        var ajax_data = {
                            'action': 'mwp_wpvivid_update_schedule_addon',
                            'schedule': schedule_data,
                            'site_id': '<?php echo esc_html($this->site_id); ?>'
                        };
                    }

                    jQuery('#mwp_wpvivid_schedule_update_notice').html('');
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                if(global) {
                                    jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                                    jQuery('#mwp_wpvivid_global_schedule_list_addon').html(jsonarray.html);
                                    jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-delete',[ 'schedules_edit', 'schedules' ]);
                                }
                                else{
                                    jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                                    jQuery('#mwp_wpvivid_schedule_list_addon').html(jsonarray.html);
                                    jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-delete',[ 'schedules_edit', 'schedules' ]);
                                }
                            }
                            else {
                                jQuery('#mwp_wpvivid_schedule_update_notice').html(jsonarray.notice);
                            }
                        }
                        catch (err) {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                var time_offset=<?php echo esc_js($offset); ?>;
                function mwp_wpvivid_set_schedule(type){
                    var mwp_wpvivid_week_id = type === 'mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_week' : 'mwp_wpvivid_schedule_update_week';
                    var mwp_wpvivid_day_id = type === 'mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_day' : 'mwp_wpvivid_schedule_update_day';
                    var mwp_wpvivid_cycles_select = type === 'mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_cycles_select' : 'mwp_wpvivid_schedule_update_cycles_select';
                    var mwp_wpvivid_utc_time = type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_utc_time' : 'mwp_wpvivid_schedule_update_utc_time';
                    var mwp_wpvivid_start_local_time = type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_local_time' : 'mwp_wpvivid_schedule_update_start_local_time';
                    var mwp_wpvivid_start_utc_time = type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_utc_time' : 'mwp_wpvivid_schedule_update_start_utc_time';
                    var mwp_wpvivid_start_cycles = type==='mwp_schedule_add' ? 'mwp_wpvivid_schedule_add_start_cycles' : 'mwp_wpvivid_schedule_update_start_cycles';

                    jQuery('#'+mwp_wpvivid_week_id).hide();
                    jQuery('#'+mwp_wpvivid_day_id).hide();
                    var cycles_value = jQuery('#'+mwp_wpvivid_cycles_select).val();
                    if(cycles_value === 'wpvivid_weekly' || cycles_value === 'wpvivid_fortnightly') {
                        jQuery('#'+mwp_wpvivid_week_id).show();
                    }
                    else if(cycles_value === 'wpvivid_monthly'){
                        jQuery('#'+mwp_wpvivid_day_id).show();
                    }
                    var cycles_display = jQuery('#'+mwp_wpvivid_cycles_select+' option:checked').text();
                    jQuery('#'+mwp_wpvivid_start_cycles).html(cycles_display);

                    var hour='00';
                    var minute='00';
                    jQuery('select[option='+type+'][name=current_day_hour]').each(function() {
                        hour=jQuery(this).val();
                    });
                    jQuery('select[option='+type+'][name=current_day_minute]').each(function(){
                        minute=jQuery(this).val();
                    });
                    var time=hour+":"+minute;
                    jQuery('#'+mwp_wpvivid_start_local_time).html(time);
                    hour=Number(hour)-Number(time_offset);
                    var Hours=Math.floor(hour);
                    var Minutes=Math.floor(60*(hour-Hours));
                    Minutes=Number(minute)+Minutes;
                    if(Minutes>=60) {
                        Hours=Hours+1;
                        Minutes=Minutes-60;
                    }
                    if(Hours>=24) {
                        Hours=Hours-24;
                    }
                    else if(Hours<0) {
                        Hours=24-Math.abs(Hours);
                    }
                    if(Hours<10) {
                        Hours='0'+Hours;
                    }
                    if(Minutes<10) {
                        Minutes='0'+Minutes;
                    }
                    time=Hours+":"+Minutes;
                    jQuery('#'+mwp_wpvivid_utc_time).html(time);
                    jQuery('#'+mwp_wpvivid_start_utc_time).html(time);
                }

                jQuery('input:radio[option=<?php echo esc_attr($type); ?>][name=mwp_schedule_update_backup_type]').click(function()
                {
                    if(this.value === 'custom')
                    {
                        jQuery('#wpvivid_custom_update_schedule_backup').show();
                        jQuery('#wpvivid_global_custom_update_schedule_backup').show();
                        //jQuery( document ).trigger( 'wpvivid_refresh_schedule_backup_tables', 'schedule_backup' );
                    }
                    else
                    {
                        jQuery('#wpvivid_custom_update_schedule_backup').hide();
                        jQuery('#wpvivid_global_custom_update_schedule_backup').hide();
                    }
                });

                jQuery('input:radio[option=mwp_schedule_backup][name=schedule_save_local_remote]').click(function(){
                    var value = jQuery(this).prop('value');
                    if(value === 'remote'){
                        if(!mwp_wpvivid_has_remote){
                            alert('There is no default remote storage configured. Please set it up first.');
                            jQuery('input:radio[option=mwp_schedule_backup][name=schedule_save_local_remote][value=local]').prop('checked', true);
                        }
                        else{
                            jQuery('#mwp_wpvivid_create_schedule_backup_remote_selector_part').show();
                        }
                    }
                    else
                    {
                        jQuery('#mwp_wpvivid_create_schedule_backup_remote_selector_part').hide();
                    }
                });

                jQuery('input:radio[option=mwp_update_schedule_backup][name=update_schedule_save_local_remote]').click(function(){
                    var value = jQuery(this).prop('value');
                    if(value === 'remote'){
                        if(!mwp_wpvivid_has_remote){
                            alert('There is no default remote storage configured. Please set it up first.');
                            jQuery('input:radio[option=mwp_update_schedule_backup][name=update_schedule_save_local_remote][value=local]').prop('checked', true);
                        }
                        else{
                            jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector_part').show();
                        }
                    }
                    else
                    {
                        jQuery('#mwp_wpvivid_update_schedule_backup_remote_selector_part').hide();
                    }
                });

                jQuery(document).ready(function ()
                {
                    mwp_wpvivid_set_schedule('mwp_schedule_add');
                });
            </script>
        </div>
        <?php
    }

    public function mwp_wpvivid_schedule_page($global){
        ?>
        <table class="widefat">
            <tbody>
            <?php
            add_action('mwp_wpvivid_schedule_do_js',array( $this, 'mwp_wpvivid_schedule_do_js' ),10);

            $this->mwp_wpvivid_schedule_settings();
            ?>
            <tfoot>
            <tr>
                <?php if($global===false)
                {
                    $save_change_id= 'mwp_wpvivid_schedule_save';
                }
                else
                {
                    $save_change_id= 'mwp_wpvivid_global_schedule_save';
                }
                ?>
                <th class="row-title"><input class="ui green mini button" id="<?php echo esc_attr($save_change_id); ?>" type="button" value="Save Changes" /></th>
                <th></th>
            </tr>
            </tfoot>
            </tbody>
        </table>
        <script>
            function mwp_wpvivid_global_schedule_save()
            {
                var setting_data = mwp_wpvivid_ajax_data_transfer('mwp-schedule');
                var ajax_data = {
                    'action': 'mwp_wpvivid_set_global_schedule',
                    'schedule': setting_data,
                };
                jQuery('#mwp_wpvivid_global_schedule_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#mwp_wpvivid_global_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            window.location.href = window.location.href + "&synchronize=1&addon=0";
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#mwp_wpvivid_global_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_global_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_schedule_save()
            {
                var setting_data = mwp_wpvivid_ajax_data_transfer('mwp-schedule');
                var ajax_data = {
                    'action': 'mwp_wpvivid_set_schedule',
                    'schedule': setting_data,
                    'site_id': '<?php echo esc_html($this->site_id); ?>'
                };
                jQuery('#mwp_wpvivid_schedule_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    try {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#mwp_wpvivid_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success') {
                            location.reload();
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err) {
                        alert(err);
                        jQuery('#mwp_wpvivid_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_schedule_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_global_schedule_save').click(function(){
                mwp_wpvivid_global_schedule_save();
            });
            jQuery('#mwp_wpvivid_schedule_save').click(function(){
                mwp_wpvivid_schedule_save();
            });
        </script>
        <?php
    }

    public function mwp_wpvivid_schedule_backup_type_addon($type, $global){
        if(!$global){
            ?>
            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="files+db" checked />
                <span>Wordpress Files + Database</span>
            </label>

            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="db" />
                <span>Database</span>
            </label>

            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="files" />
                <span>Wordpress Files</span>
            </label>

            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="custom" />
                <span>Custom content</span>
            </label>
            <?php
        }
        else{
            ?>
            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="files+db" checked />
                <span>Wordpress Files + Database</span>
            </label>

            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="db" />
                <span>Database</span>
            </label>

            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="files" />
                <span>Wordpress Files</span>
            </label>

            <label style="padding-right:2em;">
                <input type="radio" option="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>_backup_type" value="custom" />
                <span>Custom content</span>
            </label>
            <?php
        }
    }

    public function mwp_wpvivid_schedule_local_remote_addon($html, $type){
        $html .= '
        <div class="mwp-wpvivid-block-bottom-space">
            <label>
                <input type="radio" option="'.$type.'" name="'.$type.'_save_local_remote" value="local" checked />
                <span>Save backups on localhost (web server)</span>
            </label>
        </div>
        <div>
            <label>
                <input type="radio" option="'.$type.'" name="'.$type.'_save_local_remote" value="remote" />
                <span>Send backups to remote storage (Backups will be deleted from localhost after they are completely uploaded to remote storage)</span>
            </label>
        </div>
        <input type="checkbox" option="'.$type.'" name="lock" value="0" style="display: none;" />';
        return $html;
    }

    public function mwp_wpvivid_schedule_settings()
    {
        ?>
        <tr>
            <td class="row-title tablelistcolumn"><label for="tablecell">Schedule Settings</label></td>
            <td class="tablelistcolumn">
                <div>
                    <div class="postbox mwp-wpvivid-schedule-block" style="margin-bottom: 10px;">
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label for="mwp_wpvivid_schedule_enable">
                                <input option="mwp-schedule" name="mwp_enable" type="checkbox" id="mwp_wpvivid_schedule_enable" />
                                <span>Enable backup schedule</span>
                            </label><br>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <?php
                            $this->mwp_wpvivid_schedule_notice();
                            ?>
                        </div>
                    </div>

                    <div class="postbox mwp-wpvivid-schedule-block" style="margin-bottom: 10px;">
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label>
                                <input type="radio" option="mwp-schedule" name="mwp_recurrence" value="wpvivid_12hours" />
                                <span>12Hours</span>
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label>
                                <input type="radio" option="mwp-schedule" name="mwp_recurrence" value="wpvivid_daily" />
                                <span>Daily</span>
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label>
                                <input type="radio" option="mwp-schedule" name="mwp_recurrence" value="wpvivid_weekly" />
                                <span>Weekly</span>
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label>
                                <input type="radio" option="mwp-schedule" name="mwp_recurrence" value="wpvivid_fortnightly" />
                                <span>Fortnightly</span>
                            </label>
                        </div>
                        <div class="mwp-wpvivid-block-bottom-space">
                            <label>
                                <input type="radio" option="mwp-schedule" name="mwp_recurrence" value="wpvivid_monthly" />
                                <span>Monthly</span>
                            </label>
                        </div>
                    </div>

                    <div class="postbox mwp-wpvivid-schedule-block" id="mwp_wpvivid_schedule_backup_type" style="margin-bottom: 10px;">
                        <?php
                        $this->mwp_wpvivid_schedule_backup_type();
                        ?>
                    </div>

                    <div class="postbox mwp-wpvivid-schedule-block" id="mwp_wpvivid_schedule_remote_storage" style="margin-bottom: 10px;">
                        <?php
                        $this->mwp_wpvivid_schedule_local_remote();
                        ?>
                    </div>
                </div>
            </td>
        </tr>
        <script>
            <?php
            do_action('mwp_wpvivid_schedule_do_js');
            ?>
        </script>
        <?php
    }

    public function mwp_wpvivid_schedule_backup_type()
    {
        ?>
        <div class="mwp-wpvivid-block-bottom-space">
            <label>
                <input type="radio" option="mwp-schedule" name="mwp_backup_type" value="files+db"/>
                <span>Database + Files (Entire website)</span>
            </label>
        </div>

        <div class="mwp-wpvivid-block-bottom-space">
            <label>
                <input type="radio" option="mwp-schedule" name="mwp_backup_type" value="files"/>
                <span>All Files (Exclude Database)</span>
            </label>
        </div>

        <div class="mwp-wpvivid-block-bottom-space">
            <label>
                <input type="radio" option="mwp-schedule" name="mwp_backup_type" value="db"/>
                <span>Only Database</span>
            </label>
        </div>
        <?php
    }

    public function mwp_wpvivid_schedule_notice()
    {
        ?>
        <div class="mwp-wpvivid-block-bottom-space">1) Scheduled job will start at web server time: </div>
        <div class="mwp-wpvivid-block-bottom-space">2) Being subjected to mechanisms of PHP, a scheduled backup task for your site will be triggered only when the site receives at least a visit at any page.</div>
        <?php
    }

    public function mwp_wpvivid_schedule_local_remote()
    {
        $html = '';
        $schedule=$this->setting;
        $backup_local = 'checked';
        $backup_remote = '';
        if(isset($schedule['enable'])) {
            if ($schedule['enable'] == true) {
                if ($schedule['backup']['remote'] === 1) {
                    $backup_local = '';
                    $backup_remote = 'checked';
                } else {
                    $backup_local = 'checked';
                    $backup_remote = '';
                }
            }
        }
        ?>
        <div class="mwp-wpvivid-block-bottom-space">
            <label>
                <input type="radio" option="mwp-schedule" name="mwp_save_local_remote" value="local" <?php echo esc_attr($backup_local); ?> />
                <span>Save backups on localhost of child-site (web server)</span>
            </label>
        </div>
        <div class="mwp-wpvivid-block-bottom-space">
            <label>
                <input type="radio" option="mwp-schedule" name="mwp_save_local_remote" value="remote" <?php echo esc_attr($backup_remote); ?> />
                <span>Send backups to remote storage (choose this option, the local backup will be deleted after uploading to remote storage completely)</span>
            </label>
        </div>
        <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_schedule_upload_storage" style="cursor:pointer;" title="Highlighted icon illuminates that you have choosed a remote storage to store backups"></div>
        <label style="display: none;">
            <input type="checkbox" option="mwp-schedule" name="mwp_lock" value="0" />
        </label>
        <?php
    }

    public function mwp_wpvivid_schedule_do_js()
    {
        $schedule=$this->setting;
        if(isset($schedule['enable'])) {
            if ($schedule['enable'] == true) {
                ?>
                jQuery("#mwp_wpvivid_schedule_enable").prop('checked', true);
                <?php
                if ($schedule['backup']['remote'] === 1) {
                    $schedule_remote = 'remote';
                } else {
                    $schedule_remote = 'local';
                }
            } else {
                $schedule['type'] = 'wpvivid_daily';
                $schedule['backup']['backup_files'] = 'files+db';
                $schedule_remote = 'local';
            }
        }
        else{
            $schedule = array();
            $schedule['type'] = 'wpvivid_daily';
            $schedule['backup']['backup_files'] = 'files+db';
            $schedule_remote = 'local';
        }
        ?>
        jQuery("input:radio[value='<?php echo esc_attr($schedule['type']); ?>']").prop('checked', true);
        jQuery("input:radio[value='<?php echo esc_attr($schedule['backup']['backup_files']); ?>']").prop('checked', true);
        jQuery("input:radio[name='mwp_save_local_remote'][value='remote']").click(function(){
            if(!mwp_wpvivid_has_remote){
                alert('There is no default remote storage configured. Please set it up first.');
                jQuery('input:radio[name=mwp_save_local_remote][value=local]').prop('checked', true);
            }
        });
        <?php
    }

    public function mwp_wpvivid_synchronize_setting($check_addon, $mould_name = '', $is_incremental = 0)
    {
        global $mainwp_wpvivid_extension_activator;
        if(intval($check_addon) === 1) {
            if (intval($is_incremental) === 1) {
                $submit_id = 'mwp_wpvivid_sync_incremental_schedule';
            } else {
                $submit_id = 'mwp_wpvivid_sync_schedule';
            }
        }
        else{
            $submit_id = 'mwp_wpvivid_sync_schedule';
        }
        $mainwp_wpvivid_extension_activator->render_sync_websites_page($submit_id, $check_addon, $mould_name);
        ?>
        <script>
            var sync_btn_id = '<?php echo esc_js($submit_id); ?>';
            jQuery('#'+sync_btn_id).click(function(){
                mwp_wpvivid_sync_schedule();
            });
            function mwp_wpvivid_sync_schedule()
            {
                var website_ids= [];
                mwp_wpvivid_sync_index=0;
                jQuery('.mwp-wpvivid-sync-row').each(function()
                {
                    jQuery(this).children('td:first').each(function(){
                        if (jQuery(this).children().children().prop('checked')) {
                            var id = jQuery(this).attr('website-id');
                            website_ids.push(id);
                        }
                    });
                });

                if(website_ids.length>0)
                {
                    jQuery('#mwp_wpvivid_sync_schedule').css({'pointer-events': 'none', 'opacity': '0.4'});
                    var check_addon = '<?php echo esc_js($check_addon); ?>';
                    if(check_addon){
                        var schedule_mould_name = jQuery('.mwp_wpvivid_schedule_mould_name').html();
                        mwp_wpvivid_sync_schedule_mould(website_ids, schedule_mould_name, check_addon, sync_btn_id, 'Extensions-Wpvivid-Backup-Mainwp&tab=schedules', 'mwp_wpvivid_scheduled_tab');
                    }
                    else {
                        mwp_wpvivid_sync_site(website_ids, check_addon, sync_btn_id, 'Extensions-Wpvivid-Backup-Mainwp&tab=schedules', 'mwp_wpvivid_scheduled_tab');
                    }
                }
            }
        </script>
        <?php
    }

    public function get_websites_row($websites)
    {
        foreach ( $websites as $website )
        {
            $website_id = $website['id'];
            if(!$website['active'])
            {
                continue;
            }

            ?>
            <tr class="mwp-wpvivid-sync-row"">
                <th class="check-column" website-id="<?php echo esc_attr($website_id); ?>">
                    <input type="checkbox"  name="checked[]" >
                </th>
                <td>
                    <a href="admin.php?page=managesites&dashboard=<?php echo esc_url($website_id); ?>"><?php echo esc_html(stripslashes($website['name'])); ?></a><br/>
                </td>
                <td>
                    <a href="<?php echo esc_url($website['url']); ?>" target="_blank"><?php echo esc_html($website['url']); ?></a><br/>
                </td>
                <td class="mwp-wpvivid-progress" website-id="<?php echo esc_attr($website_id); ?>">
                    <span>Ready to update</span>
                </td>
            </tr>
            <?php
        }
    }
}