<?php

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Mainwp_WPvivid_Incremental_Schedule_Backup_list extends WP_List_Table
{
    public $page_num;
    public $schedule_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'incremental_schedule',
                'screen' => 'incremental_schedule',
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
        $columns['wpvivid_backup_type'] = __( 'Backup Content', 'wpvivid' );
        $columns['wpvivid_backup_cycles'] = __( 'Cycles', 'wpvivid'  );
        $columns['wpvivid_last_backup'] = __( 'Latest Backup', 'wpvivid'  );
        $columns['wpvivid_next_backup'] = __( 'Next Backup', 'wpvivid'  );
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

    public function _column_wpvivid_backup_type($schedule)
    {
        if($schedule['backup_type'] === 'Database Backup'){
            $display_type = 'Database (Full Backup)';
            echo '<td class="row-title">
                        <span>'.esc_html($display_type).'</span>
                   </td>';
        }
        else{
            if($schedule['backup_type'] === 'Full Backup'){
                $display_type = 'Files (Full Backup)';
            }
            else{
                $display_type = 'Files (Incremental Backup)';
            }
            echo '<td class="row-title"><label for="tablecell">'.esc_html($display_type).'</label></td>';
        }
    }

    public function _column_wpvivid_backup_cycles($schedule)
    {
        echo '<td>'.esc_html($schedule['backup_cycles']).'</td>';
    }

    public function _column_wpvivid_last_backup($schedule)
    {
        echo '<td>'.esc_html($schedule['backup_last_time']).'</td>';
    }

    public function _column_wpvivid_next_backup($schedule)
    {
        echo '<td>'.esc_html($schedule['backup_next_time']).'</td>';
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
        if ($schedule['backup_type'] == 'Incremental Backup')
        {
            $class='alternate';
        } else {
            $class='';
        }
        ?>
        <tr class="<?php echo esc_attr($class);?>">
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

            <tbody
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

    protected function get_table_classes()
    {
        return array( 'widefat plugin-install' );
    }
}

class Mainwp_WPvivid_Incremental_Schedule_Mould_List extends WP_List_Table
{
    public $page_num;
    public $incremental_schedule_mould_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'incremental_schedule_mould',
                'screen' => 'incremental_schedule_mould',
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

    public function set_schedule_mould_list($incremental_schedule_mould_list,$page_num=1)
    {
        $this->incremental_schedule_mould_list=$incremental_schedule_mould_list;
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

        if(!empty($this->incremental_schedule_mould_list)) {
            $total_items = sizeof($this->incremental_schedule_mould_list);
        }
        else{
            $total_items = 0;
        }

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 10,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->incremental_schedule_mould_list);
    }

    public function _column_wpvivid_mould_name( $incremental_schedule_mould )
    {
        echo '<td><div>'.esc_html($incremental_schedule_mould['mould_name']).'</div></td>';
    }

    public function _column_wpvivid_sync_mould( $incremental_schedule_mould )
    {
        echo '<td><input class="ui green mini button mwp-wpvivid-sync-incremental-schedule-mould" type="button" value="Sync" /></td>';
    }

    public function _column_wpvivid_actions( $incremental_schedule_mould )
    {
        echo '<td>
                    <div>
                         <img class="mwp-wpvivid-incremental-schedule-mould-edit" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Edit.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Edit the schedule" />                    
                         <img class="mwp-wpvivid-incremental-schedule-mould-delete" src="' . esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL . '/admin/images/Delete.png') . '"
                              style="vertical-align:middle; cursor:pointer;" title="Delete the schedule" />                    
                     </div>
                </td>';
    }

    public function display_rows()
    {
        $this->_display_rows( $this->incremental_schedule_mould_list );
    }

    private function _display_rows($incremental_schedule_mould)
    {
        foreach ($incremental_schedule_mould as $mould_name => $schedule_mould)
        {
            foreach ($incremental_schedule_mould[$mould_name] as $key => $schedule)
            {
                foreach ($incremental_schedule_mould[$mould_name][$key] as $schedule_id => $schedule_value)
                {
                    $incremental_schedule_mould[$mould_name][$key][$schedule_id]['mould_name'] = $mould_name;
                }
            }
        }

        $page=$this->get_pagenum();

        $page_schedule_mould_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_schedule_mould_list = array_splice( $incremental_schedule_mould, 0, 10);
            $count++;
        }
        foreach ( $page_schedule_mould_list as $mould_name => $schedule_mould)
        {
            foreach ($schedule_mould as $key => $schedule)
            {
                foreach ($schedule as $schedule_id => $schedule_value)
                {
                    $mould_name = $schedule_value['mould_name'];
                }
            }
            $schedule_mould['mould_name'] = $mould_name;
            $this->single_row($schedule_mould);
        }
    }

    public function single_row($incremental_schedule_mould)
    {
        ?>
        <tr slug="<?php echo esc_attr($incremental_schedule_mould['mould_name']);?>">
            <?php $this->single_row_columns( $incremental_schedule_mould ); ?>
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

class Mainwp_WPvivid_Extension_Incremental_Backup
{
    private $site_id;
    private $incremental_backup_data;

    public function __construct()
    {
        $this->load_incremental_schedule_filter();
        $this->load_incremental_schedule_ajax();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_incremental_backup_data($incremental_backup_data)
    {
        $this->incremental_backup_data=$incremental_backup_data;
    }

    public function load_incremental_schedule_filter()
    {
        add_filter('mwp_wpvivid_schedule_tabs', array($this, 'add_schedule_tabs'));
    }

    public function add_schedule_tabs($tabs)
    {
        $args['is_parent_tab']=0;
        $args['transparency']=1;
        $tabs['incremental_backup_schedules']['title']='Incremental Backup Schedule';
        $tabs['incremental_backup_schedules']['slug']='incremental_backup_schedule';
        $tabs['incremental_backup_schedules']['callback']=array($this, 'output_incremental_page');
        $tabs['incremental_backup_schedules']['args']=$args;
        return $tabs;
    }

    public function load_incremental_schedule_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_sync_incremental_schedule', array($this, 'sync_incremental_schedule'));
        add_action('wp_ajax_mwp_wpvivid_get_incremental_schedules_addon', array($this, 'get_incremental_schedules_addon'));
        add_action('wp_ajax_mwp_wpvivid_refresh_incremental_tables', array($this, 'refresh_incremental_tables'));
        add_action('wp_ajax_mwp_wpvivid_edit_incremental_schedule_addon', array($this, 'edit_incremental_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_enable_incremental_backup', array($this, 'enable_incremental_backup'));
        add_action('wp_ajax_mwp_wpvivid_save_incremental_backup_schedule', array($this, 'save_incremental_backup_schedule'));
        add_action('wp_ajax_mwp_wpvivid_set_incremental_backup_schedule', array($this, 'set_incremental_backup_schedule'));
        add_action('wp_ajax_mwp_wpvivid_update_incremental_backup_exclude_extension_addon', array($this, 'update_incremental_backup_exclude_extension_addon'));
        add_action('wp_ajax_mwp_wpvivid_incremental_connect_additional_database_addon', array($this, 'incremental_connect_additional_database_addon'));
        add_action('wp_ajax_mwp_wpvivid_incremental_add_additional_database_addon', array($this, 'incremental_add_additional_database_addon'));
        add_action('wp_ajax_mwp_wpvivid_incremental_remove_additional_database_addon', array($this, 'incremental_remove_additional_database_addon'));
        add_action('wp_ajax_mwp_wpvivid_save_global_incremental_backup_schedule_addon', array($this, 'save_global_incremental_backup_schedule_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_global_incremental_backup_schedule', array($this, 'set_global_incremental_backup_schedule'));
        add_action('wp_ajax_mwp_wpvivid_edit_global_incremental_schedule_mould_addon', array($this, 'edit_global_incremental_schedule_mould_addon'));
        add_action('wp_ajax_mwp_wpvivid_update_global_incremental_backup_schedule', array($this, 'update_global_incremental_backup_schedule'));
        add_action('wp_ajax_mwp_wpvivid_delete_global_incremental_schedule_mould_addon', array($this, 'delete_global_incremental_schedule_mould_addon'));
        add_action('wp_ajax_mwp_wpvivid_get_incremental_schedule_mould_list', array($this, 'mwp_wpvivid_get_incremental_schedule_mould_list'));
    }

    public function sync_incremental_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
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
                    $post_data['mwp_action'] = 'wpvivid_sync_incremental_schedule_addon_mainwp';
                    $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                    $schedules = $schedule_mould[$schedule_mould_name];
                    $post_data['schedule'] = $schedules;

                    foreach ($schedules['incremental_schedules'] as $incremental_schedule_id => $incremental_schedule_data)
                    {
                        if(isset($incremental_schedule_data['incremental_files_start_backup']) && $incremental_schedule_data['incremental_files_start_backup'] == '1')
                        {
                            $post_data['start_immediate'] = '1';
                        }
                        else
                        {
                            $post_data['start_immediate'] = '0';
                        }
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
            }
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_incremental_schedules_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_get_incremental_backup_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Incremental_Schedule_Backup_list();
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

    public function refresh_incremental_tables()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_refresh_incremental_table_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['database_tables'] = Mainwp_WPvivid_Extension_Subpage::output_database_table($information['database_tables']['base_tables'], $information['database_tables']['other_tables']);
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

    public function edit_incremental_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id'])){
                $site_id = sanitize_key($_POST['site_id']);

                $incremental_backup_setting = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'incremental_backup_setting', array());

                if(empty($incremental_backup_setting)){
                    $schedule_data = array();
                }
                else{
                    if(isset($incremental_backup_setting['incremental_schedules']) && !empty($incremental_backup_setting['incremental_schedules']))
                    {
                        $incremental_schedules = $incremental_backup_setting['incremental_schedules'];
                        $schedule_data = array_shift($incremental_schedules);
                    }
                    else
                    {
                        $schedule_data = array();
                    }
                }

                $ret['recurrence'] = isset($schedule_data['incremental_recurrence']) ? $schedule_data['incremental_recurrence'] : 'wpvivid_weekly';
                $ret['incremental_files_recurrence'] = isset($schedule_data['incremental_files_recurrence']) ? $schedule_data['incremental_files_recurrence'] : 'wpvivid_hourly';
                $ret['incremental_db_recurrence'] = isset($schedule_data['incremental_db_recurrence']) ? $schedule_data['incremental_db_recurrence'] : 'wpvivid_weekly';

                $ret['incremental_files_recurrence_week'] = isset($schedule_data['incremental_recurrence_week']) ? $schedule_data['incremental_recurrence_week'] : 'mon';
                $ret['incremental_files_recurrence_day'] = isset($schedule_data['incremental_recurrence_day']) ? $schedule_data['incremental_recurrence_day'] : '1';
                $ret['incremental_db_recurrence_week'] = isset($schedule_data['incremental_db_recurrence_week']) ? $schedule_data['incremental_db_recurrence_week'] : 'mon';
                $ret['incremental_db_recurrence_day'] = isset($schedule_data['incremental_db_recurrence_day']) ? $schedule_data['incremental_db_recurrence_day'] : '1';

                $ret['files_current_day_hour'] = isset($schedule_data['files_current_day_hour']) ? $schedule_data['files_current_day_hour'] : '01';
                $ret['files_current_day_minute'] = isset($schedule_data['files_current_day_minute']) ? $schedule_data['files_current_day_minute'] : '00';
                $ret['db_current_day_hour'] = isset($schedule_data['db_current_day_hour']) ? $schedule_data['db_current_day_hour'] : '00';
                $ret['db_current_day_minute'] = isset($schedule_data['db_current_day_minute']) ? $schedule_data['db_current_day_minute'] : '00';

                if(isset($schedule_data['backup']['remote']) && $schedule_data['backup']['remote'])
                {
                    $ret['backup_to']='remote';
                }
                else
                {
                    $ret['backup_to']='local';
                }

                if(isset($schedule_data['backup']['remote_id']))
                {
                    $remote_id = $schedule_data['backup']['remote_id'];
                    $ret['remote_options'][$remote_id] = array();
                }
                else if(isset($schedule_data['backup']['remote_options']))
                {
                    $ret['remote_options'] = $schedule_data['backup']['remote_options'];
                }

                if(isset($schedule_data['backup']['backup_prefix']))
                {
                    $ret['backup_prefix'] = $schedule_data['backup']['backup_prefix'];
                }
                else
                {
                    $ret['backup_prefix'] = '';
                    /*$general_setting=WPvivid_Setting::get_setting(true, "");
                    if(!isset($general_setting['options']['wpvivid_common_setting']['backup_prefix']))
                    {
                        $home_url_prefix=get_home_url();
                        $parse = wp_parse_url($home_url_prefix);
                        $path = '';
                        if(isset($parse['path']))
                        {
                            $parse['path'] = str_replace('/', '_', $parse['path']);
                            $parse['path'] = str_replace('.', '_', $parse['path']);
                            $path = $parse['path'];
                        }
                        $parse['host'] = str_replace('/', '_', $parse['host']);
                        $ret['backup_prefix'] = $parse['host'].$path;
                    }
                    else
                    {
                        $ret['backup_prefix'] = $general_setting['options']['wpvivid_common_setting']['backup_prefix'];
                    }*/
                }

                $ret['incremental_files_start_backup']=isset($schedule_data['incremental_files_start_backup']) ? $schedule_data['incremental_files_start_backup'] : '0';
                $ret['backup_file_type']=isset($schedule_data['backup_files']['backup_files']) ? $schedule_data['backup_files']['backup_files'] : 'files';
                $ret['backup_db_type']=isset($schedule_data['backup_db']['backup_files']) ? $schedule_data['backup_db']['backup_files'] : 'db';

                if($ret['backup_file_type'] === 'custom')
                {
                    $custom_dir=$schedule_data['backup_files']['custom_dirs'];
                    if(isset($custom_dir['core_check']))
                    {
                        $ret['core_check']=$custom_dir['core_check'];
                    }
                    else
                    {
                        $ret['core_check']=0;
                    }

                    if(isset($custom_dir['content_check']))
                    {
                        $ret['content_check']=$custom_dir['content_check'];
                    }
                    else
                    {
                        $ret['content_check']=0;
                    }

                    if(isset($custom_dir['themes_check']))
                    {
                        $ret['themes_check']=$custom_dir['themes_check'];
                    }
                    else
                    {
                        $ret['themes_check']=0;
                    }

                    if(isset($custom_dir['plugins_check']))
                    {
                        $ret['plugins_check']=$custom_dir['plugins_check'];
                    }
                    else
                    {
                        $ret['plugins_check']=0;
                    }

                    if(isset($custom_dir['uploads_check']))
                    {
                        $ret['uploads_check']=$custom_dir['uploads_check'];
                    }
                    else
                    {
                        $ret['uploads_check']=0;
                    }

                    if(isset($custom_dir['other_check']))
                    {
                        $ret['other_check']=$custom_dir['other_check'];
                    }
                    else
                    {
                        $ret['other_check']=0;
                    }

                    if(isset($custom_dir['other_list']))
                    {
                        $ret['other_list']=$custom_dir['other_list'];
                    }
                    else
                    {
                        $ret['other_list']=array();
                    }

                }
                if($ret['backup_db_type'] === 'custom')
                {
                    $ret['database_check']=$schedule_data['backup_db']['custom_dirs']['database_check'];
                }

                if(isset($schedule_data['backup_files']['exclude_files']))
                {
                    $ret['exclude_files']=$schedule_data['backup_files']['exclude_files'];
                }

                if(isset($schedule_data['backup_files']['exclude_file_type']))
                {
                    $ret['exclude_file_type']=$schedule_data['backup_files']['exclude_file_type'];
                }

                /*$post_data['mwp_action'] = 'wpvivid_refresh_incremental_table_addon_mainwp';
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['database_tables'] = Mainwp_WPvivid_Extension_Subpage::output_database_table($information['database_tables']['base_tables'], $information['database_tables']['other_tables']);
                }*/
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

    public function enable_incremental_backup()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) && isset($_POST['enable'])){
                $site_id = sanitize_key($_POST['site_id']);
                $post_data['mwp_action'] = 'wpvivid_enable_incremental_backup_mainwp';
                $post_data['enable'] = sanitize_text_field($_POST['enable']);
                $post_data['start_immediate'] = sanitize_text_field($_POST['start_immediate']);

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
                    $mainwp_wpvivid_extension_activator->set_incremental_enable($site_id, $information['enable_incremental_schedules']);
                    $mainwp_wpvivid_extension_activator->set_incremental_schedules($site_id, $information['incremental_schedules']);
                    $mainwp_wpvivid_extension_activator->set_incremental_backup_data($site_id, $information['incremental_backup_data']);
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

    public function save_incremental_backup_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && isset($_POST['schedule'])&&!empty($_POST['schedule']))
            {
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $post_data['mwp_action'] = 'wpvivid_save_incremental_backup_schedule_mainwp';
                $post_data['schedule'] = $json;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $table=new Mainwp_WPvivid_Incremental_Schedule_Backup_list();
                    $table->set_schedule_list($information['schedule_info']);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $ret['data'] = $information['data'];
                    $ret['notice'] = $information['notice'];
                    $mainwp_wpvivid_extension_activator->set_incremental_enable($site_id, $information['enable_incremental_schedules']);
                    $mainwp_wpvivid_extension_activator->set_incremental_schedules($site_id, $information['incremental_schedules']);
                    $mainwp_wpvivid_extension_activator->set_incremental_backup_data($site_id, $information['incremental_backup_data']);
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

    public function set_incremental_backup_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule']) &&
                isset($_POST['start'])){
                $site_id = sanitize_key($_POST['site_id']);
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                if(isset($_POST['incremental_remote_retain']) && !empty($_POST['incremental_remote_retain'])){
                    $incremental_remote_retain = intval(sanitize_text_field($_POST['incremental_remote_retain']));
                    $post_data['incremental_remote_retain'] = $incremental_remote_retain;
                    $mainwp_wpvivid_extension_activator->set_incremental_remote_retain_count($site_id, $incremental_remote_retain);
                }
                $post_data['mwp_action'] = 'wpvivid_set_incremental_backup_schedule_mainwp';
                $post_data['schedule'] = $json;
                $post_data['start'] = sanitize_text_field($_POST['start']);
                if(isset($post_data['start'])&&$post_data['start']){
                    $mainwp_wpvivid_extension_activator->set_incremental_enable($site_id, true);
                }
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
                    $ret['data'] = $information['data'];
                    $ret['notice'] = $information['notice'];
                    $schedule = json_decode($json, true);
                    if(isset($schedule['custom']['files'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_file_settings($site_id, $schedule['custom']['files']);
                    }
                    if(isset($schedule['custom']['db'])){
                        $mainwp_wpvivid_extension_activator->set_incremental_db_setting($site_id, $schedule['custom']['db']);
                    }
                    $mainwp_wpvivid_extension_activator->set_incremental_schedules($site_id, $information['incremental_schedules']);
                    $mainwp_wpvivid_extension_activator->set_incremental_backup_data($site_id, $information['incremental_backup_data']);
                    $mainwp_wpvivid_extension_activator->set_incremental_output_msg($site_id, $information['incremental_output_msg']);
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

    public function update_incremental_backup_exclude_extension_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type']) &&
                isset($_POST['exclude_content']) && !empty($_POST['exclude_content']) && is_string($_POST['exclude_content'])){
                $site_id = sanitize_key($_POST['site_id']);
                $type = sanitize_text_field($_POST['type']);
                $exclude_content = sanitize_text_field($_POST['exclude_content']);
                //$this->mwp_wpvivid_update_backup_exclude_extension_rule($site_id, $type, $exclude_content);
                $post_data['mwp_action'] = 'wpvivid_update_incremental_backup_exclude_extension_addon_mainwp';
                $post_data['type'] = $type;
                $post_data['exclude_content'] = $exclude_content;
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

    public function incremental_connect_additional_database_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])){
                $site_id = sanitize_key($_POST['site_id']);
                $data = sanitize_text_field($_POST['database_info']);
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $post_data['mwp_action'] = 'wpvivid_incremental_connect_additional_database_addon_mainwp';
                $post_data['db_user'] = sanitize_text_field($json['db_user']);
                $post_data['db_pass'] = sanitize_text_field($json['db_pass']);
                $post_data['db_host'] = sanitize_text_field($json['db_host']);
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_table($information['database_array']);
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

    public function incremental_add_additional_database_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['database_info']) && !empty($_POST['database_info']) && is_string($_POST['database_info'])){
                $site_id = sanitize_key($_POST['site_id']);
                $data = sanitize_text_field($_POST['database_info']);
                $data = stripslashes($data);
                $json = json_decode($data, true);
                $post_data['mwp_action'] = 'wpvivid_incremental_add_additional_database_addon_mainwp';
                $post_data['db_user'] = $json['db_user'];
                $post_data['db_pass'] = $json['db_pass'];
                $post_data['db_host'] = $json['db_host'];
                $post_data['additional_database_list'] = $json['additional_database_list'];
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_list($information['data']);
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

    public function incremental_remove_additional_database_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['database_name']) && !empty($_POST['database_name']) && is_string($_POST['database_name'])){
                $site_id = sanitize_key($_POST['site_id']);
                $database_name = sanitize_text_field($_POST['database_name']);
                $post_data['mwp_action'] = 'wpvivid_incremental_remove_additional_database_addon_mainwp';
                $post_data['database_name'] = $database_name;
                $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                if (isset($information['error'])) {
                    $ret['result'] = 'failed';
                    $ret['error'] = $information['error'];
                } else {
                    $ret['result'] = 'success';
                    $ret['html'] = Mainwp_WPvivid_Extension_Subpage::output_additional_database_list($information['data']);
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

    public function save_global_incremental_backup_schedule_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])){
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $schedule = json_decode($json, true);

                if(isset($_POST['incremental_schedule_mould_name'])  && !empty($_POST['incremental_schedule_mould_name']) && is_string($_POST['incremental_schedule_mould_name']))
                {
                    $incremental_schedule_mould_name = sanitize_text_field($_POST['incremental_schedule_mould_name']);

                    $incremental_schedule_mould_name_array = array();
                    $incremental_schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                    if(empty($incremental_schedule_mould)){
                        $incremental_schedule_mould = array();
                    }
                    else{
                        foreach ($incremental_schedule_mould as $incremental_schedule_name => $value){
                            $incremental_schedule_mould_name_array[] = $incremental_schedule_name;
                        }
                    }

                    if(!in_array($incremental_schedule_mould_name, $incremental_schedule_mould_name_array)){
                        $mainwp_wpvivid_extension_activator->set_global_incremental_schedules($incremental_schedule_mould_name, $schedule);
                        $incremental_schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                        if(empty($incremental_schedule_mould)){
                            $incremental_schedule_mould = array();
                        }
                        $table = new Mainwp_WPvivid_Incremental_Schedule_Mould_List();
                        $table->set_schedule_mould_list($incremental_schedule_mould);
                        $table->prepare_items();
                        ob_start();
                        $table->display();
                        $html = ob_get_clean();
                        $ret['html'] = $html;
                        $success_msg = 'You have successfully added a schedule.';
                        $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                        $ret['result'] = 'success';
                    }
                    else{
                        $ret['result'] = 'failed';
                        $error_msg = 'The schedule mould name already existed.';
                        $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
                    }
                }
                else
                {
                    $ret['result'] = 'failed';
                    $error_msg = 'A schedule mould name is required.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
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

    public function set_global_incremental_backup_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule']) &&
                isset($_POST['incremental_schedule_mould_name'])  && !empty($_POST['incremental_schedule_mould_name']) && is_string($_POST['incremental_schedule_mould_name'])){
                $json = stripslashes(sanitize_text_field($_POST['schedule']));
                $schedule = json_decode($json, true);
                $incremental_schedule_mould_name = sanitize_text_field($_POST['incremental_schedule_mould_name']);

                $incremental_schedule_mould_name_array = array();
                $incremental_schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                if(empty($incremental_schedule_mould)){
                    $incremental_schedule_mould = array();
                }
                else{
                    foreach ($incremental_schedule_mould as $incremental_schedule_name => $value){
                        $incremental_schedule_mould_name_array[] = $incremental_schedule_name;
                    }
                }

                if(!in_array($incremental_schedule_mould_name, $incremental_schedule_mould_name_array)){
                    if(isset($_POST['incremental_remote_retain']) && !empty($_POST['incremental_remote_retain'])){
                        $incremental_remote_retain = intval(sanitize_text_field($_POST['incremental_remote_retain']));
                        $mainwp_wpvivid_extension_activator->set_global_incremental_remote_retain_count($incremental_schedule_mould_name, $incremental_remote_retain);
                    }
                    if(isset($schedule['custom']['files'])) {
                        $mainwp_wpvivid_extension_activator->set_global_incremental_file_settings($incremental_schedule_mould_name, $schedule['custom']['files']);
                    }
                    if(isset($schedule['custom']['db'])){
                        $mainwp_wpvivid_extension_activator->set_global_incremental_db_settings($incremental_schedule_mould_name, $schedule['custom']['db']);
                    }
                    $mainwp_wpvivid_extension_activator->set_global_incremental_schedules($incremental_schedule_mould_name, $schedule);
                    $incremental_schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                    if(empty($incremental_schedule_mould)){
                        $incremental_schedule_mould = array();
                    }
                    $table = new Mainwp_WPvivid_Incremental_Schedule_Mould_List();
                    $table->set_schedule_mould_list($incremental_schedule_mould);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $success_msg = 'You have successfully added a schedule.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                    $ret['result'] = 'success';
                }
                else{
                    $ret['result'] = 'failed';
                    $error_msg = 'The schedule mould name already existed.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
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

    public function edit_global_incremental_schedule_mould_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])){
                $mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                $incremental_schedule = $schedule_mould[$mould_name];
                $incremental_schedule_id = '';
                foreach ($incremental_schedule['incremental_schedules'] as $key => $value){
                    $incremental_schedule_id = $key;
                }

                $ret['incremental_schedule'] = $incremental_schedule['incremental_schedules'][$incremental_schedule_id];
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

    public function update_global_incremental_backup_schedule()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['schedule']) && !empty($_POST['schedule']) && is_string($_POST['schedule'])){

                if(isset($_POST['incremental_schedule_mould_name'])  && !empty($_POST['incremental_schedule_mould_name']) && is_string($_POST['incremental_schedule_mould_name']))
                {
                    $incremental_schedule_mould_name = sanitize_text_field($_POST['incremental_schedule_mould_name']);
                    $json = stripslashes(sanitize_text_field($_POST['schedule']));
                    $schedule = json_decode($json, true);

                    if(isset($_POST['incremental_schedule_mould_old_name']))
                    {
                        $incremental_schedule_mould_old_name = sanitize_text_field($_POST['incremental_schedule_mould_old_name']);

                        if($incremental_schedule_mould_old_name === $incremental_schedule_mould_name)
                        {
                            $mainwp_wpvivid_extension_activator->set_global_incremental_schedules($incremental_schedule_mould_name, $schedule);
                        }
                        else
                        {
                            $incremental_schedule_mould_name_array = array();
                            $incremental_schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());

                            if(empty($incremental_schedule_mould))
                            {
                                $incremental_schedule_mould = array();
                            }
                            else {
                                foreach ($incremental_schedule_mould as $incremental_schedule_name => $value)
                                {
                                    $incremental_schedule_mould_name_array[] = $incremental_schedule_name;
                                }
                            }

                            if(!in_array($incremental_schedule_mould_name, $incremental_schedule_mould_name_array))
                            {
                                if(isset($incremental_schedule_mould[$incremental_schedule_mould_old_name])){
                                    unset($incremental_schedule_mould[$incremental_schedule_mould_old_name]);
                                }
                                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('incremental_backup_setting', $incremental_schedule_mould);
                                $mainwp_wpvivid_extension_activator->set_global_incremental_schedules($incremental_schedule_mould_name, $schedule);
                            }
                            else {
                                $ret['result'] = 'failed';
                                $error_msg = 'The schedule mould name already existed.';
                                $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
                                echo wp_json_encode($ret);
                                die();
                            }
                        }
                    }
                    else
                    {
                        $mainwp_wpvivid_extension_activator->set_global_incremental_schedules($incremental_schedule_mould_name, $schedule);
                    }

                    $incremental_schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                    if(empty($incremental_schedule_mould)){
                        $incremental_schedule_mould = array();
                    }
                    $table = new Mainwp_WPvivid_Incremental_Schedule_Mould_List();
                    $table->set_schedule_mould_list($incremental_schedule_mould);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $html = ob_get_clean();
                    $ret['html'] = $html;
                    $success_msg = 'You have successfully update the schedule.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', true, $success_msg);
                    $ret['result'] = 'success';
                }
                else
                {
                    $ret['result'] = 'failed';
                    $error_msg = 'A schedule mould name is required.';
                    $ret['notice'] = apply_filters('mwp_wpvivid_set_schedule_notice', false, $error_msg);
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

    public function delete_global_incremental_schedule_mould_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['mould_name']) && !empty($_POST['mould_name']) && is_string($_POST['mould_name'])){
                $mould_name = sanitize_text_field($_POST['mould_name']);
                $schedule_mould = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                if(isset($schedule_mould[$mould_name])){
                    unset($schedule_mould[$mould_name]);
                }
                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('incremental_backup_setting', $schedule_mould);

                $table = new Mainwp_WPvivid_Incremental_Schedule_Mould_List();
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
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function mwp_wpvivid_get_incremental_schedule_mould_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['page'])){
                $page = sanitize_text_field($_POST['page']);

                $schedule_mould_list = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                $table = new Mainwp_WPvivid_Incremental_Schedule_Mould_List();
                $table->set_schedule_mould_list($schedule_mould_list, $page);
                $table->prepare_items();
                ob_start();
                $table->display();
                $html = ob_get_clean();
                $ret['incremental_schedule_mould_list'] = $html;
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

    public function output_incremental_page($global)
    {
        if($global) {
            ?>
            <div style="margin-top: 10px;">
                <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_incremental_backup_part_1">
                    <div class="mwp-wpvivid-block-bottom-space" id="mwp_wpvivid_incremental_schedule_mould_list_addon">
                        <?php
                        $incremental_schedule_mould_list = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('incremental_backup_setting', array());
                        if(empty($incremental_schedule_mould_list)){
                            $incremental_schedule_mould_list = array();
                        }
                        $table = new Mainwp_WPvivid_Incremental_Schedule_Mould_List();
                        $table->set_schedule_mould_list($incremental_schedule_mould_list);
                        $table->prepare_items();
                        $table->display();
                        ?>
                    </div>
                    <div>
                        <input class="ui green mini button" type="button" value="<?php esc_attr_e('Create New Incremental Schedule Mould'); ?>" onclick="mwp_wpvivid_create_new_incremental_schedule_mould();" />
                    </div>
                </div>
                <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow" id="mwp_wpvivid_incremental_backup_deploy" style="display: none;">
                    <?php
                    $this->output_edit_schedule_ex($global);
                    ?>
                </div>
            </div>
            <script>
                var mwp_wpvivid_update_global_incremental_backup_schedule = false;
                var mwp_edit_global_incremental_schedule_name = '';

                function mwp_wpvivid_create_new_incremental_schedule_mould(){
                    mwp_wpvivid_update_global_incremental_backup_schedule = false;
                    mwp_edit_global_incremental_schedule_name = '';
                    jQuery('#mwp_wpvivid_incremental_backup_part_1').hide();
                    jQuery('#mwp_wpvivid_incremental_backup_deploy').show();
                }

                function mwp_wpvivid_edit_incremental_schedule_mould(mould_name){
                    mwp_wpvivid_update_global_incremental_backup_schedule = true;
                    mwp_edit_global_incremental_schedule_name = mould_name;
                    jQuery('#mwp_wpvivid_incremental_schedule_mould_name').val(mould_name);
                    //jQuery('#mwp_wpvivid_incremental_schedule_mould_name').attr('disabled', 'disabled');
                    var ajax_data = {
                        'action': 'mwp_wpvivid_edit_global_incremental_schedule_mould_addon',
                        'mould_name': mould_name
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                var recurrence=jsonarray.incremental_schedule.incremental_recurrence;
                                var incremental_files_recurrence=jsonarray.incremental_schedule.incremental_files_recurrence;
                                var incremental_db_recurrence=jsonarray.incremental_schedule.incremental_db_recurrence;

                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').hide();
                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').hide();
                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').hide();
                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').hide();

                                jQuery('[option=mwp_incremental_backup][name=recurrence]').val(recurrence);
                                jQuery('[option=mwp_incremental_backup][name=incremental_files_recurrence]').val(incremental_files_recurrence);
                                jQuery('[option=mwp_incremental_backup][name=incremental_db_recurrence]').val(incremental_db_recurrence);
                                if(recurrence === 'wpvivid_weekly' || recurrence === 'wpvivid_fortnightly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').show();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').hide();
                                }
                                else if(recurrence === 'wpvivid_monthly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').hide();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').show();
                                }
                                if(incremental_db_recurrence === 'wpvivid_weekly' || incremental_db_recurrence === 'wpvivid_fortnightly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').show();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').hide();
                                }
                                else if(incremental_db_recurrence === 'wpvivid_monthly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').hide();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').show();
                                }

                                jQuery('#mwp_wpvivid_incrementa_schedule_recurrence').val(jsonarray.incremental_schedule.incremental_recurrence);
                                jQuery('[option=mwp_incremental_backup][name=recurrence_week]').val(jsonarray.incremental_schedule.incremental_recurrence_week);
                                jQuery('[option=mwp_incremental_backup][name=recurrence_day]').val(jsonarray.incremental_schedule.incremental_recurrence_day);
                                var arr_file = new Array();
                                arr_file = jsonarray.incremental_schedule.files_current_day.split(':');
                                jQuery('select[option=mwp_incremental_backup][name=files_current_day_hour]').each(function() {
                                    jQuery(this).val(arr_file[0]);
                                });
                                jQuery('select[option=mwp_incremental_backup][name=files_current_day_minute]').each(function(){
                                    jQuery(this).val(arr_file[1]);
                                });
                                //jQuery('[option=mwp_incremental_backup][name=file_start_time_zone]').val(jsonarray.incremental_schedule.file_start_time_zone);
                                jQuery('[option=mwp_incremental_backup][name=incremental_files_recurrence]').val(jsonarray.incremental_schedule.incremental_files_recurrence);

                                if(jsonarray.incremental_schedule.incremental_backup_status == '1'){
                                    jQuery('[option=mwp_incremental_backup][name=incremental_backup_status]').prop('checked', true);
                                }
                                else{
                                    jQuery('[option=mwp_incremental_backup][name=incremental_backup_status]').prop('checked', false);
                                }

                                if(jsonarray.incremental_schedule.incremental_files_start_backup == '1'){
                                    jQuery('[option=mwp_incremental_backup][name=incremental_files_start_backup]').prop('checked', true);
                                }
                                else{
                                    jQuery('[option=mwp_incremental_backup][name=incremental_files_start_backup]').prop('checked', false);
                                }
                                //
                                var core_check = true;
                                var themes_check = true;
                                var plugins_check = true;
                                var uploads_check = true;
                                var content_check = true;

                                if(jsonarray.incremental_schedule.backup_files.backup_files === 'custom')
                                {
                                    jQuery('[option=mwp_incremental_backup_file][name=backup_file][value=custom]').prop('checked', true);
                                    jQuery('#mwp_wpvivid_incremental_backup_file').show();
                                    if(jsonarray.incremental_schedule.backup_files.custom_dirs.core_check != '1')
                                    {
                                        core_check = false;
                                    }
                                    if(jsonarray.incremental_schedule.backup_files.custom_dirs.content_check != '1')
                                    {
                                        content_check = false;
                                    }
                                    if(jsonarray.incremental_schedule.backup_files.custom_dirs.themes_check != '1')
                                    {
                                        themes_check = false;
                                    }
                                    if(jsonarray.incremental_schedule.backup_files.custom_dirs.plugins_check != '1')
                                    {
                                        plugins_check = false;
                                    }
                                    if(jsonarray.incremental_schedule.backup_files.custom_dirs.uploads_check != '1')
                                    {
                                        uploads_check = false;
                                    }
                                    jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-core-check').prop('checked', core_check);
                                    jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-themes-check').prop('checked', themes_check);
                                    jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-plugins-check').prop('checked', plugins_check);
                                    jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-uploads-check').prop('checked', uploads_check);
                                    jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-content-check').prop('checked', content_check);
                                }
                                else
                                {
                                    jQuery('[option=mwp_incremental_backup_file][name=backup_file][value=files]').prop('checked', true);
                                }

                                //
                                jQuery('#mwp_wpvivid_incrementa_schedule_db_recurrence').val(jsonarray.incremental_schedule.incremental_db_recurrence);
                                jQuery('[option=mwp_incremental_backup][name=incremental_db_recurrence_week]').val(jsonarray.incremental_schedule.incremental_db_recurrence_week);
                                jQuery('[option=mwp_incremental_backup][name=incremental_db_recurrence_day]').val(jsonarray.incremental_schedule.incremental_db_recurrence_day);
                                var arr_db = new Array();
                                arr_db = jsonarray.incremental_schedule.db_current_day.split(':');
                                jQuery('select[option=mwp_incremental_backup][name=db_current_day_hour]').each(function() {
                                    jQuery(this).val(arr_db[0]);
                                });
                                jQuery('select[option=mwp_incremental_backup][name=db_current_day_minute]').each(function(){
                                    jQuery(this).val(arr_db[1]);
                                });
                                //jQuery('[option=mwp_incremental_backup][name=db_start_time_zone]').val(jsonarray.incremental_schedule.db_start_time_zone);
                                //

                                var database_check = true;
                                if(jsonarray.incremental_schedule.backup_db.backup_files === 'custom')
                                {
                                    jQuery('[option=mwp_incremental_backup_db][name=backup_db][value=custom]').prop('checked', true);
                                    jQuery('#mwp_wpvivid_incremental_backup_db').show();
                                    if(jsonarray.incremental_schedule.backup_db.custom_dirs.database_check != '1')
                                    {
                                        database_check = false;
                                    }
                                    jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-database-check').prop('checked', database_check);
                                }
                                else
                                {
                                    jQuery('[option=mwp_incremental_backup_db][name=backup_db][value=db]').prop('checked', true);
                                }

                                //
                                var backup_to = jsonarray.incremental_schedule.backup.local === 1 ? 'local' : 'remote';
                                jQuery('input:radio[option=mwp_incremental_backup][name=save_local_remote][value='+backup_to+']').prop('checked', true);
                                jQuery('#mwp_wpvivid_incremental_backup_schedule_save').text('Update');

                                jQuery('#mwp_wpvivid_incremental_backup_advanced_option').find('.mwp-wpvivid-exclude-path').val(jsonarray.incremental_schedule.exclude_files);
                                jQuery('#mwp_wpvivid_incremental_backup_advanced_option').find('.mwp-wpvivid-custom-exclude-extension').val(jsonarray.incremental_schedule.exclude_file_type);

                                if(typeof jsonarray.incremental_schedule.backup.backup_prefix !== 'undefined')
                                {
                                    jQuery('input:text[option=mwp_incremental_backup][name=backup_prefix]').val(jsonarray.incremental_schedule.backup.backup_prefix);
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

                function mwp_wpvivid_delete_incremental_schedule_mould(mould_name){
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_global_incremental_schedule_mould_addon',
                        'mould_name': mould_name
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success') {
                                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').html(jsonarray.html);
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

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on('click', '.mwp-wpvivid-sync-incremental-schedule-mould', function(){
                    var Obj=jQuery(this);
                    var mould_name=Obj.closest('tr').attr('slug');
                    window.location.href = window.location.href + "&synchronize=1&addon=1&is_incremental=1&mould_name=" + mould_name;
                });

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on('click', '.mwp-wpvivid-incremental-schedule-mould-edit', function(){
                    jQuery('#mwp_wpvivid_incremental_backup_part_1').hide();
                    jQuery('#mwp_wpvivid_incremental_backup_deploy').show();
                    var Obj=jQuery(this);
                    var mould_name=Obj.closest('tr').attr('slug');
                    mwp_wpvivid_edit_incremental_schedule_mould(mould_name);
                });

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on('click', '.mwp-wpvivid-incremental-schedule-mould-delete', function(){
                    var descript = 'Are you sure to remove this schedule mould?';
                    var ret = confirm(descript);
                    if(ret === true) {
                        var Obj = jQuery(this);
                        var mould_name = Obj.closest('tr').attr('slug');
                        mwp_wpvivid_delete_incremental_schedule_mould(mould_name);
                    }
                });

                function mwp_wpvivid_click_save_incremental_schedule()
                {
                    //global
                    var schedule_data = mwp_wpvivid_ajax_data_transfer('mwp_incremental_backup');
                    schedule_data = JSON.parse(schedule_data);
                    var exclude_dirs = mwp_wpvivid_get_global_exclude_json('mwp_wpvivid_incremental_backup_advanced_option');
                    var custom_option = {
                        'exclude_files': exclude_dirs
                    };
                    jQuery.extend(schedule_data, custom_option);

                    var exclude_file_type = mwp_wpvivid_get_exclude_file_type('mwp_wpvivid_incremental_backup_advanced_option');
                    var exclude_file_type_option = {
                        'exclude_file_type': exclude_file_type
                    };
                    jQuery.extend(schedule_data, exclude_file_type_option);

                    var backup_db = {};
                    jQuery('input:radio[option=mwp_incremental_backup_db][name=backup_db]').each(function ()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            backup_db['backup_files']=value;
                            if(value === 'custom')
                            {
                                backup_db['custom_dirs'] = mwp_wpvivid_create_incremental_json_ex('mwp_wpvivid_incremental_backup_deploy', 'database');
                            }
                        }
                    });
                    schedule_data['backup_db']=backup_db;
                    var backup_files = {};
                    jQuery('input:radio[option=mwp_incremental_backup_file][name=backup_file]').each(function (){
                        if(jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            backup_files['backup_files']=value;
                            if(value === 'custom')
                            {
                                backup_files['custom_dirs'] = mwp_wpvivid_create_incremental_json_ex('mwp_wpvivid_incremental_backup_deploy', 'files');
                            }
                        }
                    });
                    schedule_data['backup_files']=backup_files;

                    jQuery('input:radio[option=mwp_incremental_backup][name=save_local_remote]').each(function ()
                    {
                        if (jQuery(this).prop('checked'))
                        {
                            if (this.value === 'remote')
                            {
                                var remote_id_select = jQuery('#mwp_wpvivid_incremental_backup_remote_selector').val();
                                var local_remote_option = {
                                    'remote_id_select': remote_id_select
                                };
                                jQuery.extend(schedule_data, local_remote_option);
                            }
                        }
                    });

                    var db_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','db_current_day_hour','db_current_day_minute');
                    var files_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','files_current_day_hour','files_current_day_minute');
                    var current_day = {
                        'db_current_day': db_current_day,
                        'files_current_day': files_current_day,
                    };

                    jQuery.extend(schedule_data, current_day);
                    schedule_data = JSON.stringify(schedule_data);
                    console.log(schedule_data);

                    var incremental_schedule_mould_name = jQuery('#mwp_wpvivid_incremental_schedule_mould_name').val();
                    if(incremental_schedule_mould_name == ''){
                        alert('A schedule mould name is required.');
                        return;
                    }

                    if(!mwp_wpvivid_update_global_incremental_backup_schedule)
                    {
                        var action = 'mwp_wpvivid_save_global_incremental_backup_schedule_addon';
                        var ajax_data = {
                            'action': action,
                            'schedule': schedule_data,
                            'incremental_schedule_mould_name': incremental_schedule_mould_name
                        };
                    }
                    else
                    {
                        var action = 'mwp_wpvivid_update_global_incremental_backup_schedule';
                        var ajax_data = {
                            'action': action,
                            'schedule': schedule_data,
                            'incremental_schedule_mould_name': incremental_schedule_mould_name,
                            'incremental_schedule_mould_old_name': mwp_edit_global_incremental_schedule_name
                        };
                    }

                    mwp_wpvivid_post_request(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#mwp_wpvivid_incremental_backup_part_1').show();
                                jQuery('#mwp_wpvivid_incremental_backup_deploy').hide();
                                jQuery('#mwp_wpvivid_global_incremental_backup_schedule_notice').html(jsonarray.notice);
                                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').html(jsonarray.html);
                            }
                            else {
                                if(jsonarray.error !== undefined){
                                    jQuery('#mwp_wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.error);
                                }
                                else{
                                    jQuery('#mwp_wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.notice);
                                }
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = mwp_wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on("click",'.first-page',function() {
                    mwp_wpvivid_get_incremental_schedule_mould_list('first');
                });

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on("click",'.prev-page',function() {
                    var page=parseInt(jQuery(this).attr('value'));
                    mwp_wpvivid_get_incremental_schedule_mould_list(page-1);
                });

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on("click",'.next-page',function() {
                    var page=parseInt(jQuery(this).attr('value'));
                    mwp_wpvivid_get_incremental_schedule_mould_list(page+1);
                });

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on("click",'.last-page',function() {
                    mwp_wpvivid_get_incremental_schedule_mould_list('last');
                });

                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').on("keypress", '.current-page', function(){
                    if(event.keyCode === 13){
                        var page = jQuery(this).val();
                        mwp_wpvivid_get_incremental_schedule_mould_list(page);
                    }
                });

                function mwp_wpvivid_get_incremental_schedule_mould_list(page=0) {
                    if(page === 0){
                        var current_page = jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').find('.current-page').val();
                        if(typeof current_page !== 'undefined') {
                            page = jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').find('.current-page').val();
                        }
                    }
                    var ajax_data = {
                        'action': 'mwp_wpvivid_get_incremental_schedule_mould_list',
                        'page':page
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').html('');
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#mwp_wpvivid_incremental_schedule_mould_list_addon').html(jsonarray.incremental_schedule_mould_list);
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
                            mwp_wpvivid_get_incremental_schedule_mould_list();
                        }, 3000);
                    });
                }
            </script>
            <?php
        }
        else{
            $enable_incremental_schedules = isset($this->incremental_backup_data['enable_incremental_schedules']) && !empty($this->incremental_backup_data['enable_incremental_schedules']) ? $this->incremental_backup_data['enable_incremental_schedules'] : '0';
            $incremental_schedules = isset($this->incremental_backup_data['incremental_schedules']) ? $this->incremental_backup_data['incremental_schedules'] : array();

            if(!empty($incremental_schedules))
            {
                $schedule=array_shift($incremental_schedules);
                $incremental_files_start_backup = $schedule['incremental_files_start_backup'];
            }
            else
            {
                $incremental_files_start_backup = '0';
            }

            if($enable_incremental_schedules)
            {
                $incremental_enable_status = 'checked';
                $auto_start_backup_display = 'display: none;';
            }
            else{
                $incremental_enable_status = '';
                $auto_start_backup_display = '';
            }
            if($incremental_files_start_backup){
                $incremental_files_start_backup_check = 'checked';
            }
            else{
                $incremental_files_start_backup_check = '';
            }
            ?>
            <div class="mwp-wpvivid-one-coloum" style="padding-top:1em;padding-left:0em;">
                <div class="mwp-wpvivid-two-col">
                    <label class="mwp-wpvivid-switch">
                        <input type="checkbox" id="mwp_wpvivid_incremental_backup_switch" <?php echo esc_attr($incremental_enable_status); ?>>
                        <span class="mwp-wpvivid-slider mwp-wpvivid-round"></span>
                    </label>
                    <label>
                        <span>Enable Incremental Backup Schedule</span>
                    </label>
                </div>
                <div class="mwp-wpvivid-two-col wpvivid-ignore" style="<?php echo esc_attr($auto_start_backup_display); ?>">
                    <span style="float:right;">
                        <label>
                            <input type="checkbox" option="mwp_incremental_backup" name="incremental_files_start_backup" <?php echo esc_attr($incremental_files_start_backup_check); ?> />
                            <span>Perform a full backup immediately when enabling incremental backup</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-left">
                                    <!-- The content you need -->
                                    <p>With the option checked, the plugin will perform a full backup of website(files + db) immediately when you enable incremental backups.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </label>
                    </span>
                </div>
            </div>

            <div id="mwp_wpvivid_incremental_schedule_backup_list" style="width:100%; white-space: nowrap;"></div>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-clear-float" id="mwp_wpvivid_edit_incremental_backup" style="padding-bottom:1em;padding-left:0;">
                <input class="ui green mini button" type="button" value="Edit">
            </div>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow" id="mwp_wpvivid_incremental_backup_deploy" style="display: none;">
                <?php
                $this->output_edit_schedule_ex($global);
                ?>
            </div>
            <?php

            ?>
            <script>
                var is_global = '<?php echo esc_js($global); ?>';
                if(!is_global){
                    mwp_wpvivid_get_incremental_schedules_addon();
                }

                function mwp_wpvivid_get_incremental_schedules_addon()
                {
                    var ajax_data={
                        'action': 'mwp_wpvivid_get_incremental_schedules_addon',
                        'site_id':'<?php echo esc_html($this->site_id); ?>'
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data)
                    {
                        try {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#mwp_wpvivid_incremental_schedule_backup_list').html(jsonarray.html);
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
                            mwp_wpvivid_get_incremental_schedules_addon();
                        }, 3000);
                    });
                }

                function mwp_wpvivid_display_incremental_schedule_setting()
                {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_edit_incremental_schedule_addon',
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data){
                        try{
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                var recurrence=jsonarray.recurrence;
                                var incremental_files_recurrence=jsonarray.incremental_files_recurrence;
                                var incremental_db_recurrence=jsonarray.incremental_db_recurrence;
                                var incremental_files_recurrence_week=jsonarray.incremental_files_recurrence_week;
                                var incremental_files_recurrence_day=jsonarray.incremental_files_recurrence_day;
                                var incremental_db_recurrence_week=jsonarray.incremental_db_recurrence_week;
                                var incremental_db_recurrence_day=jsonarray.incremental_db_recurrence_day;
                                var db_current_day_hour=jsonarray.db_current_day_hour;
                                var db_current_day_minute=jsonarray.db_current_day_minute;
                                var files_current_day_hour=jsonarray.files_current_day_hour;
                                var files_current_day_minute=jsonarray.files_current_day_minute;
                                var backup_to=jsonarray.backup_to;

                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').hide();
                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').hide();
                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').hide();
                                jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').hide();

                                jQuery('[option=mwp_incremental_backup][name=recurrence]').val(recurrence);
                                jQuery('[option=mwp_incremental_backup][name=incremental_files_recurrence]').val(incremental_files_recurrence);
                                jQuery('[option=mwp_incremental_backup][name=incremental_db_recurrence]').val(incremental_db_recurrence);
                                if(recurrence === 'wpvivid_weekly' || recurrence === 'wpvivid_fortnightly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').show();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').hide();
                                }
                                else if(recurrence === 'wpvivid_monthly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').hide();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').show();
                                }
                                if(incremental_db_recurrence === 'wpvivid_weekly' || incremental_db_recurrence === 'wpvivid_fortnightly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').show();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').hide();
                                }
                                else if(incremental_db_recurrence === 'wpvivid_monthly')
                                {
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').hide();
                                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').show();
                                }

                                jQuery('[option=mwp_incremental_backup][name=recurrence_week]').val(incremental_files_recurrence_week);
                                jQuery('[option=mwp_incremental_backup][name=recurrence_day]').val(incremental_files_recurrence_day);
                                jQuery('[option=mwp_incremental_backup][name=incremental_db_recurrence_week]').val(incremental_db_recurrence_week);
                                jQuery('[option=mwp_incremental_backup][name=incremental_db_recurrence_day]').val(incremental_db_recurrence_day);

                                jQuery('[option=mwp_incremental_backup][name=files_current_day_hour]').val(files_current_day_hour);
                                jQuery('[option=mwp_incremental_backup][name=files_current_day_minute]').val(files_current_day_minute);
                                jQuery('[option=mwp_incremental_backup][name=db_current_day_hour]').val(db_current_day_hour);
                                jQuery('[option=mwp_incremental_backup][name=db_current_day_minute]').val(db_current_day_minute);

                                var db_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','db_current_day_hour','db_current_day_minute');
                                var files_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','files_current_day_hour','files_current_day_minute');
                                jQuery('#mwp_wpvivid_incremental_files_utc_time').html(files_current_day);
                                jQuery('#mwp_wpvivid_incremental_db_utc_time').html(db_current_day);

                                jQuery('[option=mwp_incremental_backup][name=save_local_remote]').each(function()
                                {
                                    if(jQuery(this).val()===backup_to)
                                    {
                                        jQuery(this).prop('checked',true);
                                        if(backup_to === 'remote')
                                        {
                                            jQuery('#mwp_wpvivid_incremental_backup_remote_selector_part').show();
                                            if(typeof jsonarray.remote_options !== 'undefined'){
                                                jQuery.each(jsonarray.remote_options, function(remote_id, remote_option){
                                                    jQuery('#mwp_wpvivid_incremental_backup_remote_selector').val(remote_id);
                                                });
                                            }
                                            else
                                            {
                                                jQuery('#mwp_wpvivid_incremental_backup_remote_selector').val('all');
                                            }
                                        }
                                    }
                                    else
                                    {
                                        jQuery(this).prop('checked',false);
                                    }
                                });

                                jQuery('[option=mwp_incremental_backup_db][name=backup_db]').each(function()
                                {
                                    if(jQuery(this).val() === jsonarray.backup_db_type)
                                    {
                                        jQuery(this).prop('checked',true);
                                        if(jsonarray.backup_db_type === 'custom')
                                        {
                                            jQuery('#mwp_wpvivid_incremental_backup_db').show();
                                            var database_check = true;
                                            if(jsonarray.database_check != 1)
                                            {
                                                database_check = false;
                                            }
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-database-check').prop('checked', database_check);
                                        }
                                    }
                                    else
                                    {
                                        jQuery(this).prop('checked',false);
                                    }
                                });

                                jQuery('[option=mwp_incremental_backup_file][name=backup_file]').each(function()
                                {
                                    if(jQuery(this).val() === jsonarray.backup_file_type)
                                    {
                                        jQuery(this).prop('checked',true);
                                        if(jsonarray.backup_file_type === 'custom')
                                        {
                                            jQuery('#mwp_wpvivid_incremental_backup_file').show();
                                            var core_check = true;
                                            var content_check = true;
                                            var themes_check = true;
                                            var plugin_check = true;
                                            var uploads_check = true;
                                            var other_check = true;
                                            if(jsonarray.core_check != 1)
                                            {
                                                core_check = false;
                                            }
                                            if(jsonarray.content_check != 1)
                                            {
                                                content_check = false;
                                            }
                                            if(jsonarray.themes_check != 1)
                                            {
                                                themes_check = false;
                                            }
                                            if(jsonarray.plugins_check != 1)
                                            {
                                                plugin_check = false;
                                            }
                                            if(jsonarray.uploads_check != 1)
                                            {
                                                uploads_check = false;
                                            }
                                            if(jsonarray.other_check != 1)
                                            {
                                                other_check = false;
                                            }
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-core-check').prop('checked', core_check);
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-content-check').prop('checked', content_check);
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-themes-check').prop('checked', themes_check);
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-plugins-check').prop('checked', plugin_check);
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-uploads-check').prop('checked', uploads_check);
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-additional-folder-check').prop('checked', other_check);

                                            var include_other = '';
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-include-additional-folder-list').html('');
                                            jQuery.each(jsonarray.other_list, function(index ,value){
                                                var type = 'folder';
                                                var class_span = 'dashicons dashicons-category wpvivid-dashicons-orange wpvivid-icon-16px-nopointer';
                                                include_other += "<div class='wpvivid-text-line' type='"+type+"'>" +
                                                    "<span class='dashicons dashicons-trash wpvivid-icon-16px mwp-wpvivid-remove-custom-exlcude-tree'></span>" +
                                                    "<span class='"+class_span+"'></span>" +
                                                    "<span class='wpvivid-text-line'>" + value + "</span>" +
                                                    "</div>";
                                            });
                                            jQuery('#mwp_wpvivid_incremental_backup_deploy').find('.mwp-wpvivid-custom-include-additional-folder-list').append(include_other);
                                        }
                                    }
                                    else
                                    {
                                        jQuery(this).prop('checked',false);
                                    }
                                });

                                if(typeof jsonarray.exclude_files !== 'undefined')
                                {
                                    var exclude_list = '';
                                    jQuery('#mwp_wpvivid_incremental_backup_advanced_option').find('.mwp-wpvivid-custom-exclude-list').html('');
                                    jQuery.each(jsonarray.exclude_files, function(index, value)
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
                                    jQuery('#mwp_wpvivid_incremental_backup_advanced_option').find('.mwp-wpvivid-custom-exclude-list').append(exclude_list);
                                }

                                if(typeof jsonarray.exclude_file_type !== 'undefined')
                                {
                                    jQuery('#mwp_wpvivid_incremental_backup_advanced_option').find('.mwp-wpvivid-custom-exclude-extension').val(jsonarray.exclude_file_type);
                                }

                                if(typeof jsonarray.backup_prefix !== 'undefined')
                                {
                                    jQuery('input:text[option=mwp_incremental_backup][name=backup_prefix]').val(jsonarray.backup_prefix);
                                }
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
                        var error_message = mwp_wpvivid_output_ajaxerror('editing incremental schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                jQuery('#mwp_wpvivid_edit_incremental_backup').click(function()
                {
                    if(jQuery('#mwp_wpvivid_incremental_backup_deploy').is(':hidden'))
                    {
                        jQuery('#mwp_wpvivid_incremental_backup_deploy').show();
                        jQuery( document ).trigger( 'mwp_wpvivid_refresh_incremental_custom_backup_tables' );
                        mwp_wpvivid_display_incremental_schedule_setting();
                    }
                });


                var mwp_wpvivid_refresh_incremental_table_retry_times = 0;

                function mwp_wpvivid_refresh_incremental_backup_table()
                {
                    var ajax_data = {
                        'action': 'mwp_wpvivid_refresh_incremental_tables',
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data){
                        try {
                            var json = jQuery.parseJSON(data);
                            if (json.result === 'success') {
                                mwp_wpvivid_refresh_incremental_table_retry_times = 0;
                                jQuery('#mwp_wpvivid_incremental_backup_db').find('.mwp-wpvivid-custom-database-info').html(json.database_tables);
                            }
                            else{
                                mwp_wpvivid_refresh_incremental_table_retry();
                            }
                        }
                        catch(err) {
                            mwp_wpvivid_refresh_incremental_table_retry();
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        mwp_wpvivid_refresh_incremental_table_retry();
                    });
                }

                function mwp_wpvivid_refresh_incremental_table_retry()
                {
                    var need_retry_incremental_table = false;
                    mwp_wpvivid_refresh_incremental_table_retry_times++;
                    if(mwp_wpvivid_refresh_incremental_table_retry_times < 10){
                        need_retry_incremental_table = true;
                    }
                    if(need_retry_incremental_table){
                        setTimeout(function(){
                            mwp_wpvivid_refresh_incremental_backup_table();
                        }, 3000);
                    }
                    else{
                        var refresh_btn = '<input class="ui green mini button" type="button" value="Refresh" onclick="mwp_wpvivid_refresh_incremental_backup();">';
                        jQuery('#mwp_wpvivid_incremental_backup_db').find('.mwp-wpvivid-custom-database-info').html(refresh_btn);
                    }
                }

                function mwp_wpvivid_refresh_incremental_backup()
                {
                    mwp_wpvivid_refresh_incremental_table_retry_times = 0;
                    var custom_database_loading = '<div class="spinner is-active" style="margin: 0 5px 10px 0; float: left;"></div>' +
                        '<div style="float: left;">Archieving ...</div>' +
                        '<div style="clear: both;"></div>';
                    jQuery('#mwp_wpvivid_incremental_backup_db').find('.mwp-wpvivid-custom-database-info').html(custom_database_loading);
                    mwp_wpvivid_refresh_incremental_backup_table();
                }

                function mwp_wpvivid_click_save_incremental_schedule()
                {
                    //general
                    var schedule_data = mwp_wpvivid_ajax_data_transfer('mwp_incremental_backup');
                    schedule_data = JSON.parse(schedule_data);
                    var exclude_dirs = mwp_wpvivid_get_exclude_json('mwp_wpvivid_incremental_backup_advanced_option');
                    var custom_option = {
                        'exclude_files': exclude_dirs
                    };
                    jQuery.extend(schedule_data, custom_option);

                    var exclude_file_type = mwp_wpvivid_get_exclude_file_type('mwp_wpvivid_incremental_backup_advanced_option');
                    var exclude_file_type_option = {
                        'exclude_file_type': exclude_file_type
                    };
                    jQuery.extend(schedule_data, exclude_file_type_option);

                    var backup_db = {};
                    jQuery('input:radio[option=mwp_incremental_backup_db][name=backup_db]').each(function ()
                    {
                        if(jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            backup_db['backup_files']=value;
                            if(value === 'custom')
                            {
                                backup_db['custom_dirs'] = mwp_wpvivid_create_incremental_json_ex('mwp_wpvivid_incremental_backup_deploy', 'database');
                            }
                        }
                    });
                    schedule_data['backup_db']=backup_db;
                    var backup_files = {};
                    jQuery('input:radio[option=mwp_incremental_backup_file][name=backup_file]').each(function (){
                        if(jQuery(this).prop('checked'))
                        {
                            var value = jQuery(this).prop('value');
                            backup_files['backup_files']=value;
                            if(value === 'custom')
                            {
                                backup_files['custom_dirs'] = mwp_wpvivid_create_incremental_json_ex('mwp_wpvivid_incremental_backup_deploy', 'files');
                            }
                        }
                    });
                    schedule_data['backup_files']=backup_files;

                    jQuery('input:radio[option=mwp_incremental_backup][name=save_local_remote]').each(function ()
                    {
                        if (jQuery(this).prop('checked'))
                        {
                            if (this.value === 'remote')
                            {
                                var remote_id_select = jQuery('#mwp_wpvivid_incremental_backup_remote_selector').val();
                                var local_remote_option = {
                                    'remote_id_select': remote_id_select
                                };
                                jQuery.extend(schedule_data, local_remote_option);
                            }
                        }
                    });

                    var db_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','db_current_day_hour','db_current_day_minute');
                    var files_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','files_current_day_hour','files_current_day_minute');
                    var current_day = {
                        'db_current_day': db_current_day,
                        'files_current_day': files_current_day,
                    };

                    jQuery.extend(schedule_data, current_day);
                    schedule_data = JSON.stringify(schedule_data);
                    console.log(schedule_data);
                    var ajax_data = {
                        'action': 'mwp_wpvivid_save_incremental_backup_schedule',
                        'schedule': schedule_data,
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('#mwp_wpvivid_incremental_schedule_backup_list').html(jsonarray.incremental_backup_list);
                                jQuery('#mwp_wpvivid_incremental_backup_deploy').hide();
                            }
                            else {
                                if(typeof jsonarray.error !== undefined){
                                    jQuery('#mwp_wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.error);
                                }
                                else{
                                    jQuery('#mwp_wpvivid_incremental_backup_schedule_create_notice').html(jsonarray.notice);
                                }
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = mwp_wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                jQuery('input:radio[option=mwp_incremental_backup][name=save_local_remote]').click(function(){
                    var value = jQuery(this).prop('value');
                    if(value === 'remote'){
                        if(!mwp_wpvivid_has_remote){
                            alert('There is no default remote storage configured. Please set it up first.');
                            jQuery('input:radio[option=mwp_incremental_backup][name=save_local_remote][value=local]').prop('checked', true);
                        }
                        else{
                            jQuery('#mwp_wpvivid_incremental_backup_remote_selector_part').show();
                        }
                    }
                    else
                    {
                        jQuery('#mwp_wpvivid_incremental_backup_remote_selector_part').hide();
                    }
                });

                jQuery('#mwp_wpvivid_incremental_backup_switch').click(function(){
                    if(jQuery('#mwp_wpvivid_incremental_backup_switch').prop('checked')){
                        var enable = 1;
                        var descript = 'Enabling incremental backup schedule will disable full backup schedules, if any, are you sure to continue?';
                    }
                    else{
                        var enable = 0;
                        var descript = 'Disabling incremental backup will cause the scheduled incremental backup task to not run. Are you sure to continue?';
                    }

                    var ret = confirm(descript);
                    if (ret !== true) {
                        if(enable === 1){
                            jQuery('#mwp_wpvivid_incremental_backup_switch').prop('checked', false);
                        }
                        else{
                            jQuery('#mwp_wpvivid_incremental_backup_switch').prop('checked', true);
                        }
                        return;
                    }

                    if(jQuery('input:checkbox[option=mwp_incremental_backup][name=incremental_files_start_backup]').prop('checked')){
                        var start_immediate = '1';
                    }
                    else{
                        var start_immediate = '0';
                    }
                    jQuery('input:checkbox[option=mwp_incremental_backup][name=incremental_files_start_backup]').css({'pointer-events': 'none', 'opacity': '0.4'});
                    var ajax_data = {
                        'action': 'mwp_wpvivid_enable_incremental_backup',
                        'enable': enable,
                        'start_immediate': start_immediate,
                        'site_id': '<?php echo esc_html($this->site_id); ?>'
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                location.reload();
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = wpvivid_output_ajaxerror('changing schedule', textStatus, errorThrown);
                        alert(error_message);
                    });
                });

                jQuery(document).ready(function ()
                {
                    var incremental_backup_refresh = false;

                    jQuery(document).on('mwp_wpvivid_refresh_incremental_custom_backup_tables', function(event){
                        event.stopPropagation();
                        if(!incremental_backup_refresh){
                            incremental_backup_refresh = true;
                            mwp_wpvivid_refresh_incremental_backup_table();
                        }
                    });
                });
            </script>
            <?php
        }
    }

    public function output_edit_schedule_ex($global)
    {
        if($global)
        {
            ?>
            <div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;">
                <span>Name the schedule template:</span>
                <input id="mwp_wpvivid_incremental_schedule_mould_name" />
            </div>

            <div class="mwp-wpvivid-one-coloum" style="padding-top:1em;padding-left:0em;">
                <div class="mwp-wpvivid-two-col">
                    <label class="mwp-wpvivid-switch">
                        <input type="checkbox" option="mwp_incremental_backup" name="incremental_backup_status" />
                        <span class="mwp-wpvivid-slider mwp-wpvivid-round"></span>
                    </label>
                    <label>
                        <span>Enable Incremental Backup Schedule</span>
                    </label>
                </div>
                <div class="mwp-wpvivid-two-col wpvivid-ignore" style="">
                    <span style="float:right;">
                        <label>
                            <input type="checkbox" option="mwp_incremental_backup" name="incremental_files_start_backup" />
                            <span>Perform a full backup immediately when enabling incremental backup</span>
                            <span class="dashicons dashicons-editor-help mwp-wpvivid-dashicons-editor-help mwp-wpvivid-tooltip-ex">
                                <div class="mwp-wpvivid-left">
                                    <!-- The content you need -->
                                    <p>With the option checked, the plugin will perform a full backup of website(files + db) immediately when you enable incremental backups.</p>
                                    <i></i> <!-- do not delete this line -->
                                </div>
                            </span>
                        </label>
                    </span>
                </div>
            </div>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-clear-float" style="padding-bottom:1em;padding-left:0;">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span><strong>Incremental Backup Strategy:</strong></span>
                <p></p>
                <div style="padding-left:2em;border-sizing:border-box;">
                    <p><span><strong>Files: </strong></span><span>Weekly Full Backup + Hourly (or every 'xx' hours) Incremental Backup</span>
                    <p><span><strong>Database: </strong></span><span>Database cannot be incrementally backed up, you have to set a backup schedule for database separately.</span>
                </div>
            </div>

            <table class="widefat" style="margin-bottom:1em;">
                <thead>
                <tr>
                    <th class="row-title"></th>
                    <th>Backup Cycles</th>
                    <th>Start Time</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td class="row-title"><label for="tablecell">Files (Full Backup)</label></td>
                    <td>
                        <select id="mwp_wpvivid_incrementa_schedule_recurrence" option="mwp_incremental_backup" name="recurrence" onchange="mwp_change_incremental_backup_recurrence();">
                            <option value="wpvivid_6hours">Every 6 hours</option>
                            <option value="wpvivid_12hours">Every 12 hours</option>
                            <option value="wpvivid_daily">Daily</option>
                            <option value="wpvivid_3days">Every 3 days</option>
                            <option value="wpvivid_weekly" selected="selected">Weekly</option>
                            <option value="wpvivid_fortnightly">Fortnightly</option>
                            <option value="wpvivid_monthly">Every 30 days</option>
                        </select>
                    </td>
                    <td>
                        <span id="mwp_wpvivid_incrementa_schedule_backup_start_week">
                            <select option="mwp_incremental_backup" name="recurrence_week">
                                <option value="sun">Sunday</option>
                                <option value="mon" selected="selected">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </span>
                        <span id="mwp_wpvivid_incrementa_schedule_backup_start_day" style="display: none;">
                                Day<select option="mwp_incremental_backup" name="recurrence_day">
                                    <?php
                                    for($i=1;$i<31;$i++)
                                    {
                                        echo '<option value="'.esc_attr($i).'">'.esc_html($i).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                        <span>
                                Hour<select option="mwp_incremental_backup" name="files_current_day_hour" onchange="mwp_wpvivid_check_incremental_time('files');">
                                    <?php
                                    for($hour=0; $hour<24; $hour++){
                                        $format_hour = sprintf("%02d", $hour);
                                        echo '<option value="'.esc_attr($format_hour).'">'.esc_html($format_hour).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                        <span>:</span>
                        <span>
                                Minutes<select option="mwp_incremental_backup" name="files_current_day_minute" onchange="mwp_wpvivid_check_incremental_time('files');">
                                    <?php
                                    for($minute=0; $minute<60; $minute++){
                                        $format_minute = sprintf("%02d", $minute);
                                        echo '<option value="'.esc_attr($format_minute).'">'.esc_html($format_minute).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                    </td>
                </tr>

                <tr>
                    <td class="row-title"><label for="tablecell">Files (Incremental Backup)</label></td>
                    <td>
                        <select option="mwp_incremental_backup" name="incremental_files_recurrence">
                            <option value="wpvivid_hourly">Every hour</option>
                            <option value="wpvivid_2hours">Every 2 hours</option>
                            <option value="wpvivid_4hours">Every 4 hours</option>
                            <option value="wpvivid_8hours">Every 8 hours</option>
                            <option value="wpvivid_12hours">Every 12 hours</option>
                            <option value="wpvivid_daily" >Daily</option>
                        </select>
                    </td>
                    <td></td>
                </tr>

                <tr>
                    <td class="row-title"><label for="tablecell">Database Backup Cycle</label></td>
                    <td>
                        <select id="mwp_wpvivid_incrementa_schedule_db_recurrence" option="mwp_incremental_backup" name="incremental_db_recurrence" onchange="mwp_change_incremental_backup_db_recurrence();">
                            <option value="wpvivid_hourly">Every hour</option>
                            <option value="wpvivid_2hours">Every 2 hours</option>
                            <option value="wpvivid_4hours">Every 4 hours</option>
                            <option value="wpvivid_8hours">Every 8 hours</option>
                            <option value="wpvivid_12hours">Every 12 hours</option>
                            <option value="wpvivid_daily">Daily</option>
                            <option value="wpvivid_weekly" selected="selected">Weekly</option>
                            <option value="wpvivid_fortnightly">Fortnightly</option>
                            <option value="wpvivid_monthly">Every 30 days</option>
                        </select>
                    </td>
                    <td>
                        <span id="mwp_wpvivid_incrementa_schedule_backup_db_start_week">
                            <select option="mwp_incremental_backup" name="incremental_db_recurrence_week">
                                <option value="sun">Sunday</option>
                                <option value="mon" selected="selected">Monday</option>
                                <option value="tue">Tuesday</option>
                                <option value="wed">Wednesday</option>
                                <option value="thu">Thursday</option>
                                <option value="fri">Friday</option>
                                <option value="sat">Saturday</option>
                            </select>
                        </span>
                        <span id="mwp_wpvivid_incrementa_schedule_backup_db_start_day" style="display: none;">
                                Day<select option="mwp_incremental_backup" name="incremental_db_recurrence_day">
                                    <?php
                                    for($i=1;$i<31;$i++)
                                    {
                                        echo '<option value="'.esc_attr($i).'">'.esc_html($i).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                        <span>
                                Hour<select option="mwp_incremental_backup" name="db_current_day_hour" onchange="mwp_wpvivid_check_incremental_time('db');">
                                    <?php
                                    for($hour=0; $hour<24; $hour++)
                                    {
                                        $format_hour = sprintf("%02d", $hour);
                                        echo '<option value="'.esc_attr($format_hour).'">'.esc_html($format_hour).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                        <span>:</span>
                        <span>
                                Minutes<select option="mwp_incremental_backup" name="db_current_day_minute" onchange="mwp_wpvivid_check_incremental_time('db');">
                                    <?php
                                    for($minute=0; $minute<60; $minute++)
                                    {
                                        $format_minute = sprintf("%02d", $minute);
                                        echo '<option value="'.esc_attr($format_minute).'">'.esc_html($format_minute).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                    </td>
                </tr>
                </tbody>
            </table>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin-top:1em;">
                <div style="margin-bottom:1em;">
                    <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                    <div style="padding-left:2em;">
                        <label class="">
                            <input type="radio" option="mwp_incremental_backup" name="save_local_remote" value="local" checked="" />Save it to localhost
                        </label>
                        <span style="padding:0 1em;"></span>
                        <label class="">
                            <input type="radio" option="mwp_incremental_backup" name="save_local_remote" value="remote" />Send it to cloud storage
                        </label>
                        <span style="padding:0 0.2em;"></span>
                        <label style="display: none;">
                            <input type="checkbox" option="mwp_incremental_backup" name="lock" value="0" />
                        </label>
                    </div>
                </div>

                <div>
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Database Content</strong></span></p>
                    <div style="padding:0.5em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <fieldset>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_db" name="backup_db" value="db" checked>
                                <span>Database</span>
                            </label>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_db" name="backup_db" value="custom">
                                <span>Custom content</span>
                            </label>
                        </fieldset>
                    </div>
                </div>
                <div id="mwp_wpvivid_incremental_backup_db" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                        $custom_backup_manager->set_parent_id('mwp_wpvivid_incremental_backup_deploy','incremental_backup','0','1');
                        $custom_backup_manager->output_custom_backup_db_table();
                        ?>
                    </div>
                </div>

                <div>
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup File Content</strong></span></p>
                    <div style="padding:0.5em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <fieldset>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_file" name="backup_file" value="files" checked>
                                <span>Wordpress Files</span>
                            </label>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_file" name="backup_file" value="custom">
                                <span>Custom content</span>
                            </label>
                        </fieldset>
                    </div>
                </div>
                <div id="mwp_wpvivid_incremental_backup_file" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager->output_custom_backup_file_table();
                        ?>
                    </div>
                </div>

                <!--Advanced Option (Exclude)-->
                <div id="mwp_wpvivid_incremental_backup_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('mwp_wpvivid_incremental_backup_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <p></p>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_incremental_backup" name="backup_prefix" id="wpvivid_set_incremental_schedule_prefix" value="" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="">
                    </p>
                </div>
            </div>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-clear-float" style="padding-bottom:0;padding-left:0;">
                <div id="mwp_wpvivid_incremental_backup_schedule_create_notice"></div>
                <input class="ui green mini button" type="submit" value="Save Changes" onclick="mwp_wpvivid_click_save_incremental_schedule();">
            </div>
            <?php
        }
        else
        {
            $prefix = '';
            $prefix = apply_filters('mwp_wpvivid_get_backup_prefix', $prefix);
            ?>
            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-clear-float" style="padding-bottom:1em;padding-left:0;">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span><strong>Incremental Backup Strategy:</strong></span>
                <p></p>
                <div style="padding-left:2em;border-sizing:border-box;">
                    <p><span><strong>Files: </strong></span><span>Weekly Full Backup + Hourly (or every 'xx' hours) Incremental Backup</span>
                    <p><span><strong>Database: </strong></span><span>Database cannot be incrementally backed up, you have to set a backup schedule for database separately.</span>
                </div>
            </div>

            <table class="widefat" style="margin-bottom:1em;">
                <thead>
                    <tr>
                        <th class="row-title"></th>
                        <th>Backup Cycles</th>
                        <th>Start Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="row-title"><label for="tablecell">Files (Full Backup)</label></td>
                        <td>
                            <select id="mwp_wpvivid_incrementa_schedule_recurrence" option="mwp_incremental_backup" name="recurrence" onchange="mwp_change_incremental_backup_recurrence();">
                                <option value="wpvivid_6hours">Every 6 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily">Daily</option>
                                <option value="wpvivid_3days">Every 3 days</option>
                                <option value="wpvivid_weekly" selected="selected">Weekly</option>
                                <option value="wpvivid_fortnightly">Fortnightly</option>
                                <option value="wpvivid_monthly">Every 30 days</option>
                            </select>
                        </td>
                        <td>
                            <span id="mwp_wpvivid_incrementa_schedule_backup_start_week">
                                <select option="mwp_incremental_backup" name="recurrence_week">
                                    <option value="sun">Sunday</option>
                                    <option value="mon" selected="selected">Monday</option>
                                    <option value="tue">Tuesday</option>
                                    <option value="wed">Wednesday</option>
                                    <option value="thu">Thursday</option>
                                    <option value="fri">Friday</option>
                                    <option value="sat">Saturday</option>
                                </select>
                            </span>
                            <span id="mwp_wpvivid_incrementa_schedule_backup_start_day">
                                Day<select option="mwp_incremental_backup" name="recurrence_day">
                                    <?php
                                    for($i=1;$i<31;$i++)
                                    {
                                        echo '<option value="'.esc_attr($i).'">'.esc_html($i).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                            <span>
                                Hour<select option="mwp_incremental_backup" name="files_current_day_hour" onchange="mwp_wpvivid_check_incremental_time('files');">
                                    <?php
                                    for($hour=0; $hour<24; $hour++){
                                        $format_hour = sprintf("%02d", $hour);
                                        echo '<option value="'.esc_attr($format_hour).'">'.esc_html($format_hour).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                            <span>:</span>
                            <span>
                                Minutes<select option="mwp_incremental_backup" name="files_current_day_minute" onchange="mwp_wpvivid_check_incremental_time('files');">
                                    <?php
                                    for($minute=0; $minute<60; $minute++){
                                        $format_minute = sprintf("%02d", $minute);
                                        echo '<option value="'.esc_attr($format_minute).'">'.esc_html($format_minute).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td class="row-title"><label for="tablecell">Files (Incremental Backup)</label></td>
                        <td>
                            <select option="mwp_incremental_backup" name="incremental_files_recurrence">
                                <option value="wpvivid_hourly">Every hour</option>
                                <option value="wpvivid_2hours">Every 2 hours</option>
                                <option value="wpvivid_4hours">Every 4 hours</option>
                                <option value="wpvivid_8hours">Every 8 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily" >Daily</option>
                            </select>
                        </td>
                        <td></td>
                    </tr>

                    <tr>
                        <td class="row-title"><label for="tablecell">Database Backup Cycle</label></td>
                        <td>
                            <select id="mwp_wpvivid_incrementa_schedule_db_recurrence" option="mwp_incremental_backup" name="incremental_db_recurrence" onchange="mwp_change_incremental_backup_db_recurrence();">
                                <option value="wpvivid_hourly">Every hour</option>
                                <option value="wpvivid_2hours">Every 2 hours</option>
                                <option value="wpvivid_4hours">Every 4 hours</option>
                                <option value="wpvivid_8hours">Every 8 hours</option>
                                <option value="wpvivid_12hours">Every 12 hours</option>
                                <option value="wpvivid_daily">Daily</option>
                                <option value="wpvivid_weekly" selected="selected">Weekly</option>
                                <option value="wpvivid_fortnightly">Fortnightly</option>
                                <option value="wpvivid_monthly">Every 30 days</option>
                            </select>
                        </td>
                        <td>
                            <span id="mwp_wpvivid_incrementa_schedule_backup_db_start_week">
                                <select option="mwp_incremental_backup" name="incremental_db_recurrence_week">
                                    <option value="sun">Sunday</option>
                                    <option value="mon" selected="selected">Monday</option>
                                    <option value="tue">Tuesday</option>
                                    <option value="wed">Wednesday</option>
                                    <option value="thu">Thursday</option>
                                    <option value="fri">Friday</option>
                                    <option value="sat">Saturday</option>
                                </select>
                            </span>
                            <span id="mwp_wpvivid_incrementa_schedule_backup_db_start_day">
                                Day<select option="mwp_incremental_backup" name="incremental_db_recurrence_day">
                                    <?php
                                    for($i=1;$i<31;$i++)
                                    {
                                        echo '<option value="'.esc_attr($i).'">'.esc_html($i).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                            <span>
                                Hour<select option="mwp_incremental_backup" name="db_current_day_hour" onchange="mwp_wpvivid_check_incremental_time('db');">
                                    <?php
                                    for($hour=0; $hour<24; $hour++)
                                    {
                                        $format_hour = sprintf("%02d", $hour);
                                        echo '<option value="'.esc_attr($format_hour).'">'.esc_html($format_hour).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                            <span>:</span>
                            <span>
                                Minutes<select option="mwp_incremental_backup" name="db_current_day_minute" onchange="mwp_wpvivid_check_incremental_time('db');">
                                    <?php
                                    for($minute=0; $minute<60; $minute++)
                                    {
                                        $format_minute = sprintf("%02d", $minute);
                                        echo '<option value="'.esc_attr($format_minute).'">'.esc_html($format_minute).'</option>';
                                    }
                                    ?>
                                </select>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin-top:1em;">
                <div style="margin-bottom:1em;">
                    <p><span class="dashicons dashicons-backup wpvivid-dashicons-blue"></span><span><strong>Backup Location</strong></span></p>
                    <div style="padding-left:2em;">
                        <label class="">
                            <input type="radio" option="mwp_incremental_backup" name="save_local_remote" value="local" checked="" />Save it to localhost
                        </label>
                        <span style="padding:0 1em;"></span>
                        <label class="">
                            <input type="radio" option="mwp_incremental_backup" name="save_local_remote" value="remote" />Send it to cloud storage
                        </label>
                        <span style="padding:0 0.2em;"></span>
                        <span id="mwp_wpvivid_incremental_backup_remote_selector_part" style="display: none;">
                            <select id="mwp_wpvivid_incremental_backup_remote_selector">
                                <?php
                                $remoteslist=array();
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
                        <label style="display: none;">
                            <input type="checkbox" option="mwp_incremental_backup" name="lock" value="0" />
                        </label>
                    </div>
                </div>

                <div>
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup Database Content</strong></span></p>
                    <div style="padding:0.5em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <fieldset>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_db" name="backup_db" value="db" checked>
                                <span>Database</span>
                            </label>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_db" name="backup_db" value="custom">
                                <span>Custom content</span>
                            </label>
                        </fieldset>
                    </div>
                </div>
                <div id="mwp_wpvivid_incremental_backup_db" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager = new Mainwp_WPvivid_Custom_Backup_Manager();
                        $custom_backup_manager->set_site_id($this->site_id);
                        $custom_backup_manager->set_parent_id('mwp_wpvivid_incremental_backup_deploy','incremental_backup','0','0');
                        $custom_backup_manager->output_custom_backup_db_table();
                        ?>
                    </div>
                </div>

                <div>
                    <p><span class="dashicons dashicons-screenoptions wpvivid-dashicons-blue"></span><span><strong>Backup File Content</strong></span></p>
                    <div style="padding:0.5em;margin-bottom:1em;background:#eaf1fe;border-radius:8px;">
                        <fieldset>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_file" name="backup_file" value="files" checked>
                                <span>Wordpress Files</span>
                            </label>
                            <label style="padding-right:2em;">
                                <input type="radio" option="mwp_incremental_backup_file" name="backup_file" value="custom">
                                <span>Custom content</span>
                            </label>
                        </fieldset>
                    </div>
                </div>
                <div id="mwp_wpvivid_incremental_backup_file" style="display: none;">
                    <div style="border-left: 4px solid #eaf1fe; border-right: 4px solid #eaf1fe;box-sizing: border-box; padding-left:0.5em;">
                        <?php
                        $custom_backup_manager->output_custom_backup_file_table();
                        ?>
                    </div>
                </div>

                <!--Advanced Option (Exclude)-->
                <div id="mwp_wpvivid_incremental_backup_advanced_option">
                    <?php
                    $custom_backup_manager->wpvivid_set_advanced_id('mwp_wpvivid_incremental_backup_advanced_option');
                    $custom_backup_manager->output_advanced_option_table();
                    $custom_backup_manager->load_js();
                    ?>
                </div>

                <p></p>

                <div>
                    <p>
                        <span class="dashicons dashicons-welcome-write-blog wpvivid-dashicons-green" style="margin-top:0.2em;"></span>
                        <span><strong>Comment the backup</strong>(optional): </span><input type="text" option="mwp_incremental_backup" name="backup_prefix" id="wpvivid_set_incremental_schedule_prefix" value="<?php echo esc_attr($prefix); ?>" onkeyup="value=value.replace(/[^a-zA-Z0-9._]/g,'')" onpaste="value=value.replace(/[^\a-\z\A-\Z0-9]/g,'')" placeholder="<?php echo esc_attr($prefix); ?>">
                    </p>
                </div>
            </div>

            <div class="mwp-wpvivid-one-coloum mwp-wpvivid-clear-float" style="padding-bottom:0;padding-left:0;">
                <div id="mwp_wpvivid_incremental_backup_schedule_create_notice"></div>
                <input class="ui green mini button" type="submit" value="Save Changes" onclick="mwp_wpvivid_click_save_incremental_schedule();">
            </div>
            <?php
        }

        ?>
        <script>
            function mwp_get_wpvivid_sync_time(option_name,current_day_hour,current_day_minute)
            {
                var hour='00';
                var minute='00';
                jQuery('select[option='+option_name+'][name='+current_day_hour+']').each(function()
                {
                    hour=jQuery(this).val();
                });
                jQuery('select[option='+option_name+'][name='+current_day_minute+']').each(function(){
                    minute=jQuery(this).val();
                });
                return hour+":"+minute;
            }

            function mwp_wpvivid_check_incremental_time(type)
            {
                var db_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','db_current_day_hour','db_current_day_minute');
                var files_current_day=mwp_get_wpvivid_sync_time('mwp_incremental_backup','files_current_day_hour','files_current_day_minute');
                if(db_current_day === files_current_day){
                    alert('You have set the same start time for the files incremental backup schedule and the database backup schedule. When there is a conflict of starting times for schedule tasks, only one task will be executed properly. Please make sure that the times are different.')
                }
                jQuery('#mwp_wpvivid_incremental_db_utc_time').html(db_current_day);
                jQuery('#mwp_wpvivid_incremental_files_utc_time').html(files_current_day);
            }

            function mwp_change_incremental_backup_recurrence()
            {
                jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').hide();
                jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').hide();
                var select_value = jQuery('#mwp_wpvivid_incrementa_schedule_recurrence').val();
                if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly')
                {
                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_week').show();
                }
                else if(select_value === 'wpvivid_monthly')
                {
                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_start_day').show();
                }
            }

            function mwp_change_incremental_backup_db_recurrence()
            {
                jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').hide();
                jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').hide();
                var select_value = jQuery('#mwp_wpvivid_incrementa_schedule_db_recurrence').val();
                if(select_value === 'wpvivid_weekly' || select_value === 'wpvivid_fortnightly') {
                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_week').show();
                }
                else if(select_value === 'wpvivid_monthly'){
                    jQuery('#mwp_wpvivid_incrementa_schedule_backup_db_start_day').show();
                }
            }

            jQuery('input:radio[option=mwp_incremental_backup_db][name=backup_db]').click(function()
            {
                var value = jQuery(this).val();
                if(value === 'db'){
                    jQuery( '#mwp_wpvivid_incremental_backup_db' ).hide();
                }
                else{
                    jQuery( '#mwp_wpvivid_incremental_backup_db' ).show();
                }
            });

            jQuery('input:radio[option=mwp_incremental_backup_file][name=backup_file]').click(function()
            {
                var value = jQuery(this).val();
                if(value === 'files'){
                    jQuery( '#mwp_wpvivid_incremental_backup_file' ).hide();
                }
                else{
                    jQuery( '#mwp_wpvivid_incremental_backup_file' ).show();
                }
            });
        </script>
        <?php
    }
}