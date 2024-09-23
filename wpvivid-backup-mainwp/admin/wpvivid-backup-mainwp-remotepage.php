<?php

if ( ! class_exists( 'WP_List_Table' ) )
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MainWP_WPvivid_Remote_Storage_Global_List extends WP_List_Table
{
    public $page_num;
    public $storage_list;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'storage',
                'screen' => 'storage'
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
        $columns['wpvivid_storage_type'] = __( 'Storage Provider	', 'wpvivid' );
        $columns['wpvivid_storage_alias'] = __( 'Remote Storage Alias', 'wpvivid' );
        $columns['wpvivid_sync_remote'] = __( 'Sync Remote Storage', 'wpvivid' );
        $columns['wpvivid_storage_actions'] =__( 'Actions', 'wpvivid'  );
        return $columns;
    }

    public function _column_wpvivid_storage_type( $storage )
    {
        $storage_type = $storage['type'];
        $storage_type=apply_filters('wpvivid_storage_provider_tran', $storage_type);
        echo '<td class="plugin-title column-primary"><div>'.esc_html($storage_type).'</div></td>';
    }

    public function _column_wpvivid_storage_alias( $storage )
    {
        echo '<td class="plugin-title column-primary"><label for="tablecell">'.esc_html($storage['name']).'</label></td>';
    }

    public function _column_wpvivid_sync_remote( $storage ){
        echo '<td><input class="ui green mini button mwp-wpvivid-sync-remote" type="button" value="Sync" /></td>';
    }

    public function _column_wpvivid_storage_actions( $storage )
    {
        echo '<td class="tablelistcolumn">
                    <div style="float: left;"><img src="'.esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/Edit.png').'" onclick="mwp_wpvivid_retrieve_remote_storage(\''.esc_js($storage['key']).'\',\''.esc_js($storage['type']).'\',\''.esc_js($storage['name']).'\'
                    );" style="vertical-align:middle; cursor:pointer;" title="Edit the remote storage"/></div>
                    <div><img src="'.esc_url(MAINWP_WPVIVID_EXTENSION_PLUGIN_URL.'/admin/images/Delete.png').'" onclick="mwp_wpvivid_delete_remote_storage_addon(\''.esc_js($storage['key']).'\'
                    );" style="vertical-align:middle; cursor:pointer;" title="Remove the remote storage"/></div>
                </td>';
    }

    public function set_storage_list($storage_list,$page_num=1)
    {
        $this->storage_list=$storage_list;
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

        if(!empty($this->storage_list)){
            $total_items = sizeof($this->storage_list);
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
        return !empty($this->storage_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->storage_list);
    }

    private function _display_rows($storage_list)
    {
        $page=$this->get_pagenum();

        $page_storage_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_storage_list = array_splice( $storage_list, 0, 10);
            $count++;
        }
        $default_remote_storage=array();
        if(isset($page_storage_list['remote_selected'])) {
            foreach ($page_storage_list['remote_selected'] as $value) {
                $default_remote_storage[$value] = $value;
            }
        }
        foreach ( $page_storage_list as $key=>$storage)
        {
            if($key === 'remote_selected')
            {
                continue;
            }
            if (array_key_exists($key,$default_remote_storage))
            {
                $storage['check_status'] = 'checked';
            }
            else
            {
                $storage['check_status']='';
            }
            $storage['key']=$key;
            $this->single_row($storage);
        }
    }

    public function single_row($storage)
    {
        ?>
        <tr id="<?php echo esc_attr($storage['key']); ?>">
            <?php $this->single_row_columns( $storage ); ?>
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
                "%s<input class='current-page' id='current-page-selector-remote' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector-remote" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
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

class MainWP_WPvivid_Website_List extends WP_List_Table{
    public $page_num;
    public $website_list;
    public $remote_id;
    public $batch;

    public function __construct( $args = array() )
    {
        parent::__construct(
            array(
                'plural' => 'website',
                'screen' => 'website'
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
        $columns['cb'] = __( 'cb', 'wpvivid' );
        $columns['mainwp_wpvivid_site_name'] = __( 'Site', 'mainwp-wpvivid-extension' );
        $columns['mainwp_wpvivid_site_url'] = __( 'URL', 'mainwp-wpvivid-extension' );
        $columns['mainwp_wpvivid_custom_path'] = __( 'Custom Path', 'mainwp-wpvivid-extension' );
        //$columns['mainwp_wpvivid_status'] = __( 'Status', 'mainwp-wpvivid-extension' );
        return $columns;
    }

    public function column_cb( $website )
    {
        if($this->batch == '1') {
            $check_status = 'checked';
        }
        else {
            $check_status = '';
        }
        echo '<input type="checkbox" '.esc_attr($check_status).' />';
    }

    public function _column_mainwp_wpvivid_site_name( $website )
    {
        echo '<td style="width: 30%;"><a href="admin.php?page=managesites&dashboard='.esc_attr($website['id']).'">'.esc_html(stripslashes($website['name'])).'</a></td>';
    }

    public function _column_mainwp_wpvivid_site_url( $website )
    {
        echo '<td style="width: 30%;"><a href="'.esc_attr($website['url']).'" target="_blank">'.esc_html($website['url']).'</a></td>';
    }

    public function _column_mainwp_wpvivid_custom_path( $website )
    {
        $domain = rtrim(trailingslashit($website['url']), '/');
        $parse = wp_parse_url($domain);
        $path = '';
        if(isset($parse['path'])) {
            $parse['path'] = str_replace('/', '_', $parse['path']);
            $parse['path'] = str_replace('.', '_', $parse['path']);
            $path = $parse['path'];
        }
        $parse['host'] = str_replace('/', '_', $parse['host']);
        $parse['host'] = str_replace('.', '_', $parse['host']);
        $custom_path = $parse['host'].$path;
        if(isset($website['sync_remote_setting']) && !empty($website['sync_remote_setting'])) {
            foreach ($website['sync_remote_setting'] as $key => $value) {
                if($this->remote_id === $key){
                    if(isset($value['custom_path']) && !empty($value['custom_path'])) {
                        $custom_path = $value['custom_path'];
                    }
                }
            }
        }
        if($this->batch == '1') {
            $btn_css = 'pointer-events: none; opacity: 0.4;';
        }
        else {
            $btn_css = 'pointer-events: auto; opacity: 1;';
        }
        echo '<td>
                    <input class="mwp-wpvivid-font-right-space mwp-wpvivid-remote-custom-path-input" type="text" value="'.esc_attr($custom_path).'" readonly="readonly" />
                    <input class="ui green mini button mwp-wpvivid-custom-path-edit" type="button" value="Edit" style="'.esc_attr($btn_css).'" />
                </td>';
    }

    public function set_website_list($website_list,$batch,$remote_id='',$page_num=1)
    {
        $this->website_list=$website_list;
        $this->batch = $batch;
        $this->remote_id=$remote_id;
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

        $total_items =sizeof($this->website_list);

        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => 50,
            )
        );
    }

    public function has_items()
    {
        return !empty($this->website_list);
    }

    public function display_rows()
    {
        $this->_display_rows($this->website_list);
    }

    private function _display_rows($website_list)
    {
        $page=$this->get_pagenum();

        $page_website_list=array();
        $count=0;
        while ( $count<$page )
        {
            $page_website_list = array_splice( $website_list, 0, 50);
            $count++;
        }
        foreach ( $page_website_list as $key=>$website)
        {
            $website['key']=$key;
            $this->single_row($website);
        }
    }

    public function single_row($website)
    {
        if(!$website['check-status']) {
            return;
        }

        if(1 !== intval($website['pro'])){
            return;
        }

        if($website['individual']) {
            return;
        }
        ?>
        <tr class="mwp-wpvivid-sync-row" website-id="<?php echo esc_attr($website['id']); ?>" website-name="<?php echo esc_attr($website['name']); ?>">
            <?php $this->single_row_columns( $website ); ?>
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
                <th class="row-title" colspan="7"><input class="ui green mini button" type="button" id="mwp_wpvivid_sync_remote_storage" value="Update" /></th>
            </tr>
            </tfoot>

        </table>
        <?php
        $this->display_tablenav( 'bottom' );
    }
}

class Mainwp_WPvivid_Extension_RemotePage
{
    private $setting;
    private $setting_addon;
    private $select_pro;
    private $site_id;
    public $storage_tab;

    public function __construct()
    {
        $this->load_remote_ajax();
        $this->load_remote_action();
    }

    public function set_site_id($site_id)
    {
        $this->site_id=$site_id;
    }

    public function set_remote_info($setting, $setting_addon=array(), $select_pro=0)
    {
        $this->setting=$setting;
        $this->setting_addon=$setting_addon;
        $this->select_pro=$select_pro;
    }

    public function load_remote_ajax()
    {
        add_action('wp_ajax_mwp_wpvivid_sync_remote', array($this, 'sync_remote'));
        add_action('wp_ajax_mwp_wpvivid_add_remote',array($this,'add_remote'));
        add_action('wp_ajax_mwp_wpvivid_delete_remote',array($this,'delete_remote'));
        add_action('wp_ajax_mwp_wpvivid_sync_global_remote_addon', array($this, 'sync_global_remote_addon'));
        add_action('wp_ajax_mwp_wpvivid_set_default_remote_storage',array($this,'set_default_remote_storage'));
        add_action('wp_ajax_mwp_wpvivid_archieve_website_list', array($this, 'archieve_website_list'));
        add_action('wp_ajax_mwp_wpvivid_archieve_website_list_ex', array($this, 'archieve_website_list_ex'));
        add_action('wp_ajax_mwp_wpvivid_get_website_list', array($this, 'get_website_list'));
        add_action('wp_ajax_mwp_wpvivid_archieve_all_website_list', array($this, 'archieve_all_website_list'));
        add_action('wp_ajax_mwp_wpvivid_get_remote_storage_list', array($this, 'get_remote_storage_list'));
        add_action('wp_ajax_mwp_wpvivid_retrieve_global_remote_addon', array($this, 'retrieve_global_remote_addon'));
        add_action('wp_ajax_mwp_wpvivid_update_global_remote_addon', array($this, 'update_global_remote_addon'));
        add_action('wp_ajax_mwp_wpvivid_delete_global_remote_addon', array($this, 'delete_global_remote_addon'));
    }

    public function load_remote_action()
    {
        add_action('mwp_wpvivid_remote_storage_backup_retention', array($this, 'remote_storage_backup_retention'), 10, 2);
    }

    public function remote_storage_backup_retention($type, $action)
    {
        if($action == 'add')
        {
            $option = $type;
        }
        else if($action == 'edit')
        {
            $option = 'edit-'.$type;
        }
        else
        {
            $option = $type;
        }
        $checkbox_classname = $type;
        $tr_classname = 'wpvivid-retention-tr-'.$type;
        ?>
        <tr>
            <td colspan=2>
                <label><input class="<?php echo esc_attr($checkbox_classname); ?>" type="checkbox" option="<?php echo esc_attr($option); ?>" name="use_remote_retention" onclick="wpvivid_check_special_retention(this);">Enable a special rule of backup retention for the storage
            </td>
        </tr>

        <tr class="<?php echo esc_attr($tr_classname); ?>" style="display: none;">
            <td class="plugin-title column-primary">
                <div class="wpvivid-storage-form">
                    <input type="text" class="regular-text wpvivid-remote-backup-retain" autocomplete="off" option="<?php echo esc_attr($option); ?>" name="backup_retain" value="30" />
                </div>
            </td>
            <td class="column-description desc">
                <div class="wpvivid-storage-form-desc">
                    <i>(Manual Backup + General Schedule) File Backups retained.</i>
                </div>
            </td>
        </tr>

        <tr class="<?php echo esc_attr($tr_classname); ?>" style="display: none;">
            <td class="plugin-title column-primary">
                <div class="wpvivid-storage-form">
                    <input type="text" class="regular-text wpvivid-remote-backup-db-retain" autocomplete="off" option="<?php echo esc_attr($option); ?>" name="backup_db_retain" value="30" />
                </div>
            </td>
            <td class="column-description desc">
                <div class="wpvivid-storage-form-desc">
                    <i>(Manual Backup + General Schedule) Database Backups retained.</i>
                </div>
            </td>
        </tr>

        <tr class="<?php echo esc_attr($tr_classname); ?>" style="display: none;">
            <td class="plugin-title column-primary">
                <div class="wpvivid-storage-form">
                    <input type="text" class="regular-text wpvivid-remote-backup-incremental-retain" autocomplete="off" option="<?php echo esc_attr($option); ?>" name="backup_incremental_retain" value="3" />
                </div>
            </td>
            <td class="column-description desc">
                <div class="wpvivid-storage-form-desc">
                    <i>(Incremental Backups) Cycles of incremental backups retained.</i>
                </div>
            </td>
        </tr>

        <tr class="<?php echo esc_attr($tr_classname); ?>" style="display: none;">
            <td class="plugin-title column-primary">
                <div class="wpvivid-storage-form">
                    <input type="text" class="regular-text wpvivid-remote-backup-rollback-retain" autocomplete="off" option="<?php echo esc_attr($option); ?>" name="backup_rollback_retain" value="30" />
                </div>
            </td>
            <td class="column-description desc">
                <div class="wpvivid-storage-form-desc">
                    <i>(Rollback) Rollback Backups retained.</i>
                </div>
            </td>
        </tr>

        <script>
            function wpvivid_check_special_retention(obj)
            {
                var class_name = jQuery(obj).attr('class');
                if(jQuery(obj).prop('checked'))
                {
                    jQuery('.wpvivid-retention-tr-'+class_name).show();
                }
                else
                {
                    jQuery('.wpvivid-retention-tr-'+class_name).hide();
                }
            }
        </script>
        <?php
    }

    public function sync_remote()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if(isset($_POST['id']) && !empty($_POST['id']) && is_string($_POST['id'])) {
                $site_id = sanitize_key($_POST['id']);
                $post_data['mwp_action'] = 'wpvivid_set_remote_mainwp';
                $remote = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote', array());
                $post_data['remote'] = wp_json_encode($remote);
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

    public function add_remote()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if (empty($_POST) || !isset($_POST['remote']) || !is_string($_POST['remote']) || !isset($_POST['type']) || !is_string($_POST['type'])) {
                die();
            }
            $json = sanitize_text_field($_POST['remote']);
            $json = stripslashes($json);
            $remote_options = json_decode($json, true);
            if (is_null($remote_options)) {
                die();
            }

            $remote_options['type'] = sanitize_text_field($_POST['type']);
            try {
                $ret = $mainwp_wpvivid_extension_activator->remote->add_remote($remote_options);
            } catch (Exception $error) {
                $ret['result'] = 'failed';
                $message = 'An exception has occurred. class: ' . get_class($error) . ';msg: ' . $error->getMessage() . ';code: ' . $error->getCode() . ';line: ' . $error->getLine() . ';in_file: ' . $error->getFile() . ';';
                $ret['error'] = $message;
            }


            if ($ret['result'] == 'success') {
                $html = '';
                $html = apply_filters('mwp_wpvivid_add_remote_storage_list', $html);
                $ret['html'] = $html;
            }
            echo wp_json_encode($ret);
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function delete_remote()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if (empty($_POST) || !isset($_POST['remote_id']) || !is_string($_POST['remote_id'])) {
                die();
            }
            $id = sanitize_key($_POST['remote_id']);

            Mainwp_WPvivid_Extension_Option::get_instance()->delete_global_remote($id);
            $ret['result'] = 'success';
            $html = '';
            $html = apply_filters('mwp_wpvivid_add_remote_storage_list', $html);
            $ret['html'] = $html;
            echo wp_json_encode($ret);
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function sync_global_remote_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['site_id']) && !empty($_POST['site_id']) && is_string($_POST['site_id']) &&
                isset($_POST['default_setting']) && !empty($_POST['default_setting']) && is_string($_POST['default_setting']) &&
                isset($_POST['custom_path']) && !empty($_POST['custom_path']) && is_string($_POST['custom_path']) &&
                isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id'])) {
                $site_id = sanitize_key($_POST['site_id']);
                $default_setting = sanitize_text_field($_POST['default_setting']);
                $custom_path = sanitize_text_field($_POST['custom_path']);
                $remote_id = sanitize_key($_POST['remote_id']);
                $post_data['mwp_action'] = 'wpvivid_sync_remote_storage_addon_mainwp';
                $remote = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
                if(isset($remote['upload'][$remote_id])) {
                    $post_data['remote'] = wp_json_encode($remote['upload'][$remote_id]);
                    $post_data['default_setting'] = $default_setting;
                    $post_data['custom_path'] = $custom_path;
                    $remote_option['custom_path'] = $custom_path;

                    $sync_remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'sync_remote_setting', array());
                    if(empty($sync_remote_settings)){
                        $sync_remote_settings = array();
                    }
                    $sync_remote_settings[$remote_id] = $remote_option;
                    Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'sync_remote_setting', $sync_remote_settings);

                    $information = apply_filters('mainwp_fetchurlauthed', $mainwp_wpvivid_extension_activator->childFile, $mainwp_wpvivid_extension_activator->childKey, $site_id, 'wpvivid_backuprestore', $post_data);
                    if (isset($information['error'])) {
                        $ret['result'] = 'failed';
                        $ret['error'] = $information['error'];
                    } else {
                        $ret['result'] = 'success';
                        Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_option($site_id, 'remote', $information['remote']);
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

    public function set_default_remote_storage()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if (!isset($_POST['remote_storage']) || empty($_POST['remote_storage']) || !is_array($_POST['remote_storage'])) {
                $ret['result'] = 'failed';
                $ret['error'] = __('Choose one storage from the list to be the default storage.', 'wpvivid');
                echo wp_json_encode($ret);
                die();
            }
            $remote_storage_array = array_map( 'sanitize_key', $_POST['remote_storage'] );
            $remote_storages = array();
            foreach ($remote_storage_array as $remote_storage_id){
                $remote_storages[] = sanitize_key($remote_storage_id);
            }
            Mainwp_WPvivid_Extension_Option::get_instance()->update_global_remote_default($remote_storages[0]);
            $ret['result'] = 'success';
            $html = '';
            $html = apply_filters('mwp_wpvivid_add_remote_storage_list', $html);
            $ret['html'] = $html;
            echo wp_json_encode($ret);
            die();
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function archieve_website_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['remote']) && !empty($_POST['remote']) && is_string($_POST['remote']) &&
                isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type'])) {
                $json = sanitize_text_field($_POST['remote']);
                $json = stripslashes($json);
                $remote_options = json_decode($json, true);
                if (is_null($remote_options)) {
                    die();
                }
                $remote_options['type'] = sanitize_text_field($_POST['type']);

                $ret = $mainwp_wpvivid_extension_activator->remote->check_remote_options($remote_options);
                if($ret['result']=='success') {
                    $remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
                    if(empty($remote_settings)){
                        $remote_settings = array();
                    }
                    $remote_id = uniqid('wpvivid-remote-');
                    $remote_settings['upload'][$remote_id]=$ret['options'];

                    Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('remote_addon', $remote_settings);
                    //$remote_id = Mainwp_WPvivid_Extension_Option::get_instance()->add_global_remote_addon($remote_options);

                    $remote_storages = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
                    $remote_list = '';
                    if (isset($remote_storages['upload']) && !empty($remote_storages['upload'])) {
                        $remote_list = $remote_storages['upload'];
                    }
                    $table = new MainWP_WPvivid_Remote_Storage_Global_List();
                    $table->set_storage_list($remote_list);
                    $table->prepare_items();
                    ob_start();
                    $table->display();
                    $ret['remote_list'] = ob_get_clean();

                    $websites_with_plugin = $mainwp_wpvivid_extension_activator->get_websites_ex();
                    $new_website_list = array();
                    if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 ) {
                        foreach ($websites_with_plugin as $website) {
                            if(!$website['check-status']){
                                continue;
                            }
                            if ($website['individual']) {
                                continue;
                            }

                            $site_id = $website['id'];
                            $sync_remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'sync_remote_setting', array());
                            if(!empty($sync_remote_settings)){
                                $website['sync_remote_setting']=$sync_remote_settings;
                            }
                            $new_website_list[] = $website;
                        }
                    }
                    if(isset($_POST['batch'])) {
                        $batch = sanitize_text_field($_POST['batch']);
                    }
                    else{
                        $batch = '0';
                    }
                    ob_start();
                    ?>
                    <div class="mwp-wpvivid-block-bottom-space">
                        <div>
                            <label>
                                <input type="radio" name="mwp_wpvivid_default_remote" value="default_only" checked />
                                <span>Set as the only remote storage (This will disable and replace the remote storage you’ve set on child sites).</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="radio" name="mwp_wpvivid_default_remote" value="default_append" />
                                <span>Set as an additional remote storage (This will add the remote storage as another default remote storage on child sites, and will not disable the remote storage you've set).</span>
                            </label>
                        </div>
                        <div>
                            <label>
                                <input type="checkbox" id="mwp_wpvivid_check_all_websites" />
                                <span>Select all websites include other page</span>
                            </label>
                        </div>
                    </div>
                    <div id="mwp_wpvivid_website_list_addon">
                        <?php
                        $table = new MainWP_WPvivid_Website_List();
                        $table->set_website_list($new_website_list, $batch);
                        $table->prepare_items();
                        $table->display();
                        ?>
                    </div>

                    <div class="postbox" id="mwp_wpvivid_sync_task_progress" style="display: none; margin-top: 10px; margin-bottom: 0;">
                        <div class="mwp-action-progress-bar" id="mwp_wpvivid_sync_bar_percent">
                            <div class="mwp-action-progress-bar-percent" style="width:0; height:24px;"></div>
                        </div>
                        <div style="clear: both;"></div>
                        <div style="margin-left:10px; margin-bottom:10px; float: left; width:100%;"><p id="mwp_wpvivid_sync_current_doing"></p></div>
                        <div style="clear: both;"></div>
                    </div>
                    <div class="postbox" id="mwp_wpvivid_sync_summary" style="display: none; margin-top: 10px; margin-bottom: 0; padding: 10px;"></div>

                    <?php
                    $ret['html'] = ob_get_clean();
                    $ret['html'] .= '<div style="margin-top:10px;"><input class="ui green mini button mwp-wpvivid-return-remote" type="button" value="' . esc_attr('Return', 'mainwp-wpvivid-extension') . '" /></div>';
                    $ret['remote_id'] = $remote_id;
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

    public function archieve_website_list_ex()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id'])) {
                $remote_id = sanitize_key($_POST['remote_id']);
                $websites_with_plugin = $mainwp_wpvivid_extension_activator->get_websites_ex();
                $new_website_list = array();
                if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 ) {
                    foreach ($websites_with_plugin as $website) {
                        if(!$website['check-status']){
                            continue;
                        }
                        if ($website['individual']) {
                            continue;
                        }

                        $site_id = $website['id'];
                        $sync_remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'sync_remote_setting', array());
                        if(!empty($sync_remote_settings)){
                            $website['sync_remote_setting']=$sync_remote_settings;
                        }
                        $new_website_list[] = $website;
                    }
                }
                if(isset($_POST['batch'])) {
                    $batch = sanitize_text_field($_POST['batch']);
                }
                else{
                    $batch = '0';
                }
                ob_start();
                ?>
                <div class="mwp-wpvivid-block-bottom-space">
                    <div>
                        <label>
                            <input type="radio" name="mwp_wpvivid_default_remote" value="default_only" checked />
                            <span>Set as the only remote storage (This will disable and replace the remote storage you’ve set on child sites).</span>
                        </label>
                    </div>
                    <div>
                        <label>
                            <input type="radio" name="mwp_wpvivid_default_remote" value="default_append" />
                            <span>Set as an additional remote storage (This will add the remote storage as another default remote storage on child sites, and will not disable the remote storage you've set).</span>
                        </label>
                    </div>
                    <div>
                        <label>
                            <input type="checkbox" id="mwp_wpvivid_check_all_websites" />
                            <span>Select all child sites</span>
                        </label>
                    </div>
                </div>
                <div id="mwp_wpvivid_website_list_addon">
                    <?php
                    $table = new MainWP_WPvivid_Website_List();
                    $table->set_website_list($new_website_list, $batch, $remote_id);
                    $table->prepare_items();
                    $table->display();
                    ?>
                </div>

                <div class="postbox" id="mwp_wpvivid_sync_task_progress" style="display: none; margin-top: 10px; margin-bottom: 0;">
                    <div class="mwp-action-progress-bar" id="mwp_wpvivid_sync_bar_percent">
                        <div class="mwp-action-progress-bar-percent" style="width:0; height:24px;"></div>
                    </div>
                    <div style="clear: both;"></div>
                    <div style="margin-left:10px; margin-bottom:10px; float: left; width:100%;"><p id="mwp_wpvivid_sync_current_doing"></p></div>
                    <div style="clear: both;"></div>
                </div>
                <div class="postbox" id="mwp_wpvivid_sync_summary" style="display: none; margin-top: 10px; margin-bottom: 0; padding: 10px;"></div>

                <?php
                $ret['html'] = ob_get_clean();
                $ret['html'] .= '<div style="margin-top:10px;"><input class="ui green mini button mwp-wpvivid-return-remote" type="button" value="' . esc_attr('Return', 'mainwp-wpvivid-extension') . '" /></div>';
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

    public function get_website_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['page'])){
                $page = sanitize_text_field($_POST['page']);

                if(isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id'])) {
                    $remote_id = sanitize_key($_POST['remote_id']);
                }
                else{
                    $remote_id = '';
                }
                $websites_with_plugin = $mainwp_wpvivid_extension_activator->get_websites_ex();
                $new_website_list = array();
                if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 ) {
                    foreach ($websites_with_plugin as $website) {
                        if(!$website['check-status']){
                            continue;
                        }
                        if ($website['individual']) {
                            continue;
                        }

                        $site_id = $website['id'];
                        $sync_remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'sync_remote_setting', array());
                        if(!empty($sync_remote_settings)){
                            $website['sync_remote_setting']=$sync_remote_settings;
                        }
                        $new_website_list[] = $website;
                    }
                }
                if(isset($_POST['batch'])) {
                    $batch = sanitize_text_field($_POST['batch']);
                }
                else{
                    $batch = '0';
                }
                ob_start();
                $table = new MainWP_WPvivid_Website_List();
                $table->set_website_list($new_website_list, $batch, $remote_id, $page);
                $table->prepare_items();
                $table->display();
                $ret['html'] = ob_get_clean();
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

    public function archieve_all_website_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            $websites_with_plugin = $mainwp_wpvivid_extension_activator->get_websites_ex();
            $new_website_list = array();

            if(isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id'])) {
                $remote_id = sanitize_key($_POST['remote_id']);
            }
            else{
                $remote_id = '';
            }

            if ( is_array( $websites_with_plugin ) && count( $websites_with_plugin ) > 0 ) {
                foreach ($websites_with_plugin as $website) {
                    if(!$website['check-status']){
                        continue;
                    }
                    if ($website['individual']) {
                        continue;
                    }

                    $site_id = $website['id'];
                    $sync_remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_option($site_id, 'sync_remote_setting', array());

                    if($remote_id !== '')
                    {
                        if(isset($sync_remote_settings[$remote_id]) && !empty($sync_remote_settings[$remote_id]))
                        {
                            $website['custom_path']=$sync_remote_settings[$remote_id]['custom_path'];
                        }
                        else
                        {
                            $domain = rtrim(trailingslashit($website['url']), '/');
                            $parse = wp_parse_url($domain);
                            $path = '';
                            if(isset($parse['path'])) {
                                $parse['path'] = str_replace('/', '_', $parse['path']);
                                $parse['path'] = str_replace('.', '_', $parse['path']);
                                $path = $parse['path'];
                            }
                            $parse['host'] = str_replace('/', '_', $parse['host']);
                            $parse['host'] = str_replace('.', '_', $parse['host']);
                            $website['custom_path'] = $parse['host'].$path;
                        }
                    }
                    else
                    {
                        $domain = rtrim(trailingslashit($website['url']), '/');
                        $parse = wp_parse_url($domain);
                        $path = '';
                        if(isset($parse['path'])) {
                            $parse['path'] = str_replace('/', '_', $parse['path']);
                            $parse['path'] = str_replace('.', '_', $parse['path']);
                            $path = $parse['path'];
                        }
                        $parse['host'] = str_replace('/', '_', $parse['host']);
                        $parse['host'] = str_replace('.', '_', $parse['host']);
                        $website['custom_path'] = $parse['host'].$path;
                    }
                    $new_website_list[] = $website;
                }
            }
            $ret['result'] = 'success';
            $ret['websites'] = $new_website_list;
            echo wp_json_encode($ret);
        }
        catch (Exception $error) {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo wp_json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function get_remote_storage_list()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['page'])){
                $page = sanitize_text_field($_POST['page']);

                $remote_storages = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
                $remote_list = '';
                if (isset($remote_storages['upload']) && !empty($remote_storages['upload'])) {
                    $remote_list = $remote_storages['upload'];
                }
                $table = new MainWP_WPvivid_Remote_Storage_Global_List();
                $table->set_storage_list($remote_list, $page);
                $table->prepare_items();
                ob_start();
                $table->display();
                $ret['remote_list'] = ob_get_clean();
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

    public function retrieve_global_remote_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id'])){
                $remote_id = sanitize_key($_POST['remote_id']);
                $remote = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
                $ret['result'] = 'success';
                if(isset($remote['upload'][$remote_id])) {
                    if(isset($remote['upload'][$remote_id]['is_encrypt']) && $remote['upload'][$remote_id]['is_encrypt'] == 1){
                        if($remote['upload'][$remote_id]['type'] === 'ftp' || $remote['upload'][$remote_id]['type'] === 'sftp'){
                            $remote['upload'][$remote_id]['password'] = base64_decode($remote['upload'][$remote_id]['password']);
                        }
                        else if($remote['upload'][$remote_id]['type'] === 'amazons3' || $remote['upload'][$remote_id]['type'] === 's3compat' || $remote['upload'][$remote_id]['type'] === 'wasabi'){
                            $remote['upload'][$remote_id]['secret'] = base64_decode($remote['upload'][$remote_id]['secret']);
                        }
                    }
                    $ret['data'] = $remote['upload'][$remote_id];
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

    public function update_global_remote_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try{
            if(isset($_POST['remote']) && !empty($_POST['remote']) && is_string($_POST['remote']) &&
                isset($_POST['remote_id']) && !empty($_POST['remote_id']) && is_string($_POST['remote_id']) &&
                isset($_POST['type']) && !empty($_POST['type']) && is_string($_POST['type'])) {
                $json = sanitize_text_field($_POST['remote']);
                $json = stripslashes($json);
                $remote_options = json_decode($json, true);
                if (is_null($remote_options)) {
                    die();
                }
                $remote_id = sanitize_key($_POST['remote_id']);
                $remote_options['type'] = sanitize_text_field($_POST['type']);

                if($remote_options['type'] === 'ftp' || $remote_options['type'] === 'sftp'){
                    $remote_options['password'] = base64_encode($remote_options['password']);
                    $remote_options['is_encrypt'] = 1;
                }
                else if($remote_options['type'] === 'amazons3' || $remote_options['type'] === 's3compat' || $remote_options['type'] === 'wasabi'){
                    $remote_options['secret'] = base64_encode($remote_options['secret']);
                    $remote_options['is_encrypt'] = 1;
                }

                $remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
                if(empty($remote_settings)){
                    $remote_settings = array();
                }
                if(isset($remote_settings['upload'][$remote_id])){
                    $remote_settings['upload'][$remote_id] = $remote_options;
                }

                Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('remote_addon', $remote_settings);
                //Mainwp_WPvivid_Extension_Option::get_instance()->update_global_remote_addon($remote_id, $remote_options, $default);

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

    public function delete_global_remote_addon()
    {
        global $mainwp_wpvivid_extension_activator;
        $mainwp_wpvivid_extension_activator->mwp_ajax_check_security();
        try {
            if (empty($_POST) || !isset($_POST['remote_id']) || !is_string($_POST['remote_id'])) {
                die();
            }
            $id = sanitize_key($_POST['remote_id']);

            $remote_settings = Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
            if(empty($remote_settings)){
                $remote_settings = array();
            }
            if(isset($remote_settings['upload'][$id]))
            {
                unset($remote_settings['upload'][$id]);
            }

            Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_update_global_option('remote_addon', $remote_settings);

            //Mainwp_WPvivid_Extension_Option::get_instance()->delete_global_remote_addon($id);

            $remote_storages=Mainwp_WPvivid_Extension_DB_Option::get_instance()->wpvivid_get_global_option('remote_addon', array());
            $remote_list = '';
            if(isset($remote_storages['upload']) && !empty($remote_storages['upload'])){
                $remote_list = $remote_storages['upload'];
            }
            $table=new MainWP_WPvivid_Remote_Storage_Global_List();
            $table->set_storage_list($remote_list);
            $table->prepare_items();
            ob_start();
            $table->display();
            $html = ob_get_clean();
            $ret['result'] = 'success';
            $ret['html'] = $html;
            echo wp_json_encode($ret);
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
            $this->mwp_wpvivid_synchronize_setting($check_addon);
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
                    <div style="background: #fff;">
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
                        $this->output_remote_page_addon($global);
                    }
                    else{
                        $this->output_remote_page($global);
                    }
                    ?>
                    <?php
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

                jQuery('input[option=add-remote]').click(function(){
                    var storage_type = jQuery(".mwp-storage-providers-active").attr("remote_type");
                    mwp_wpvivid_add_remote_storage(storage_type);
                });

                jQuery('#mwp_wpvivid_set_default_remote_storage').click(function(){
                    mwp_wpvivid_set_default_remote_storage();
                });

                function mwp_wpvivid_handle_remote_storage_data(data)
                {
                    var i = 0;
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_remote_storage_list').html('');
                            jQuery('#mwp_wpvivid_remote_storage_list').append(jsonarray.html);
                        }
                        else if(jsonarray.result === 'failed'){
                            alert(jsonarray.error);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                }
                function mwp_wpvivid_set_default_remote_storage()
                {
                    var remote_storage = new Array();
                    remote_storage[0] = jQuery("input[name='remote_storage']:checked").val();
                    var ajax_data = {
                        'action': 'mwp_wpvivid_set_default_remote_storage',
                        'remote_storage': remote_storage
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data)
                    {
                        mwp_wpvivid_handle_remote_storage_data(data);
                    }, function(XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('setting up the default remote storage', textStatus, errorThrown);
                        alert(error_message);
                    });
                }

                function mwp_wpvivid_add_remote_storage(storage_type)
                {
                    var remote_from = mwp_wpvivid_ajax_data_transfer(storage_type);
                    var ajax_data;
                    ajax_data = {
                        'action': 'mwp_wpvivid_add_remote',
                        'remote': remote_from,
                        'type': storage_type
                    };
                    jQuery('input[option=add-remote]').css({'pointer-events': 'none', 'opacity': '0.4'});
                    mwp_wpvivid_post_request(ajax_data, function (data)
                    {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery('input[option=add-remote]').css({'pointer-events': 'auto', 'opacity': '1'});
                                jQuery('input:text[option='+storage_type+']').each(function(){
                                    jQuery(this).val('');
                                });
                                jQuery('input:password[option='+storage_type+']').each(function(){
                                    jQuery(this).val('');
                                });
                                mwp_wpvivid_handle_remote_storage_data(data);
                            }
                            else if (jsonarray.result === 'failed')
                            {
                                alert(jsonarray.error);
                                jQuery('input[option=add-remote]').css({'pointer-events': 'auto', 'opacity': '1'});
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                            jQuery('input[option=add-remote]').css({'pointer-events': 'auto', 'opacity': '1'});
                        }

                    }, function (XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = mwp_wpvivid_output_ajaxerror('adding the remote storage', textStatus, errorThrown);
                        alert(error_message);
                        jQuery('input[option=add-remote]').css({'pointer-events': 'auto', 'opacity': '1'});
                    });
                }

                function mwp_wpvivid_start_sync_remote(addon)
                {
                    window.location.href = window.location.href + "&synchronize=1&addon="+addon;
                }
                function select_remote_storage(evt, storage_page_id)
                {
                    var i, tablecontent, tablinks;
                    tablinks = document.getElementsByClassName("mwp-storage-providers");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace("mwp-storage-providers-active", "");
                    }
                    evt.currentTarget.className += " mwp-storage-providers-active";

                    jQuery(".storage-account-page").hide();
                    jQuery("#"+storage_page_id).show();
                }
                function select_remote_storage_addon(evt, storage_page_id)
                {
                    var i, tablecontent, tablinks;
                    tablinks = document.getElementsByClassName("mwp-storage-providers-addon");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace("mwp-storage-providers-addon-active", "");
                    }
                    evt.currentTarget.className += " mwp-storage-providers-addon-active";

                    jQuery(".storage-account-page-addon").hide();
                    jQuery("#"+storage_page_id).show();
                }
                function switchstorageTabs(evt,contentName,storage_page_id) {
                    // Declare all variables
                    var i, tabcontent, tablinks;

                    // Get all elements with class="table-list-content" and hide them
                    tabcontent = document.getElementsByClassName("storage-tab-content");
                    for (i = 0; i < tabcontent.length; i++) {
                        tabcontent[i].style.display = "none";
                    }

                    // Get all elements with class="table-nav-tab" and remove the class "nav-tab-active"
                    tablinks = document.getElementsByClassName("storage-nav-tab");
                    for (i = 0; i < tablinks.length; i++) {
                        tablinks[i].className = tablinks[i].className.replace(" nav-tab-active", "");
                    }

                    // Show the current tab, and add an "storage-menu-active" class to the button that opened the tab
                    document.getElementById(contentName).style.display = "block";
                    evt.currentTarget.className += " nav-tab-active";

                    var top = jQuery('#'+storage_page_id).offset().top-jQuery('#'+storage_page_id).height();
                    jQuery('html, body').animate({scrollTop:top}, 'slow');
                }
            </script>
            <?php
        }
    }

    public function output_remote_page($global){
        ?>
        <div style="margin-top: 10px;">
            <div style="width:100%; border:1px solid #e5e5e5; float:left; padding:10px;box-sizing: border-box;">
                <div class="mwp-wpvivid-block-bottom-space">We have deleted the global configuration for remote storage from the extension for WPvivid Backup Plugin free version.</div>
                <div class="mwp-wpvivid-block-bottom-space"><strong>Why have we deleted it?</strong></div>
                <div class="mwp-wpvivid-block-bottom-space">Because the free version of WPvivid Backup Plugin does not support custom backup folder, all child sites would use the same backup folder when you add remote storage from main site, which is insecure and not recommended.</div>
                <div class="mwp-wpvivid-block-bottom-space"><strong>How to add remote storage for child sites in the extension for free version ?</strong></div>
                <div class="mwp-wpvivid-block-bottom-space">Please go to the child sites where you are using free version of WPvivid Backup plugin, and add the remote storage manually.</div>
                <div>If you are using WPvivid Back Pro plugin in child sites, please switch to the extension for WPvivid Backup Pro to add the remote storage for your child sites in bulk.</div>
            </div>
        </div>
        <?php
    }

    public function output_cloud_storage_ftp()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_ftp_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_sftp()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_sftp_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_google_drive_addon()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_google_drive_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_onedrive()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_one_drive_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_dropbox_addon()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_dropbox_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_pcloud()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_pcloud_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_amazons3()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_amazons3_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_s3compat()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_s3compat_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_wasabi()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_wasabi_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_b2()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_b2_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_webdav()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_webdav_addon'); ?>
        </div>
        <?php
    }

    public function output_cloud_storage_nextcloud()
    {
        ?>
        <div class="mwp-wpvivid-one-coloum mwp-wpvivid-workflow mwp-wpvivid-clear-float" style="margin: 10px 0;">
            <?php do_action('mwp_wpvivid_add_storage_page_nextcloud_addon'); ?>
        </div>
        <?php
    }

    public function output_remote_page_addon($global){
        ?>
        <div style="margin-top: 10px;">
            <div class="mwp-wpvivid-welcome-bar mwp-wpvivid-clear-float">
                <div class="mwp-wpvivid-welcome-bar-left">
                    <p><span class="dashicons dashicons-admin-site-alt3 mwp-wpvivid-dashicons-large mwp-wpvivid-dashicons-green"></span><span class="mwp-wpvivid-page-title">Cloud Storage</span></p>
                    <p>
                        <div id="mwp_wpvivid_remote_tab_desc" class="about-description">
                            <div>This tab allows you to add remote storage for child sites.</div>
                            <div>Click 'Save and Sync' or 'Sync' to sync the remote storage to child sites.</div>
                        </div>
                    </p>
                </div>
                <div class="mwp-wpvivid-welcome-bar-right"></div>
            </div>



            <div id="mwp_wpvivid_remote_page_step_1">
                <?php
                if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
                    include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
                $this->storage_tab=new Mainwp_WPvivid_Tab_Page_Container();

                $args['is_parent_tab']=0;
                $args['transparency']=1;

                $tabs['mwp_wpvivid_cloud_storage_ftp']['title']='FTP';
                $tabs['mwp_wpvivid_cloud_storage_ftp']['slug']='mwp_wpvivid_cloud_storage_ftp';
                $tabs['mwp_wpvivid_cloud_storage_ftp']['callback']=array($this, 'output_cloud_storage_ftp');
                $tabs['mwp_wpvivid_cloud_storage_ftp']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_sftp']['title']='SFTP';
                $tabs['mwp_wpvivid_cloud_storage_sftp']['slug']='mwp_wpvivid_cloud_storage_sftp';
                $tabs['mwp_wpvivid_cloud_storage_sftp']['callback']=array($this, 'output_cloud_storage_sftp');
                $tabs['mwp_wpvivid_cloud_storage_sftp']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_google_drive']['title'] = 'Google Drive';
                $tabs['mwp_wpvivid_cloud_storage_google_drive']['slug'] = 'mwp_wpvivid_cloud_storage_google_drive';
                $tabs['mwp_wpvivid_cloud_storage_google_drive']['callback'] = array($this, 'output_cloud_storage_google_drive_addon');
                $tabs['mwp_wpvivid_cloud_storage_google_drive']['args'] = $args;

                $tabs['mwp_wpvivid_cloud_storage_onedrive']['title']='Microsoft OneDrive';
                $tabs['mwp_wpvivid_cloud_storage_onedrive']['slug']='mwp_wpvivid_cloud_storage_onedrive';
                $tabs['mwp_wpvivid_cloud_storage_onedrive']['callback']=array($this, 'output_cloud_storage_onedrive');
                $tabs['mwp_wpvivid_cloud_storage_onedrive']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_dropbox']['title']='Dropbox';
                $tabs['mwp_wpvivid_cloud_storage_dropbox']['slug']='mwp_wpvivid_cloud_storage_dropbox';
                $tabs['mwp_wpvivid_cloud_storage_dropbox']['callback']=array($this, 'output_cloud_storage_dropbox_addon');
                $tabs['mwp_wpvivid_cloud_storage_dropbox']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_pcloud']['title']='pCloud';
                $tabs['mwp_wpvivid_cloud_storage_pcloud']['slug']='mwp_wpvivid_cloud_storage_pcloud';
                $tabs['mwp_wpvivid_cloud_storage_pcloud']['callback']=array($this, 'output_cloud_storage_pcloud');
                $tabs['mwp_wpvivid_cloud_storage_pcloud']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_amazons3']['title']='Amazon S3';
                $tabs['mwp_wpvivid_cloud_storage_amazons3']['slug']='mwp_wpvivid_cloud_storage_amazons3';
                $tabs['mwp_wpvivid_cloud_storage_amazons3']['callback']=array($this, 'output_cloud_storage_amazons3');
                $tabs['mwp_wpvivid_cloud_storage_amazons3']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_s3compat']['title']='S3 Compatible Storage';
                $tabs['mwp_wpvivid_cloud_storage_s3compat']['slug']='mwp_wpvivid_cloud_storage_s3compat';
                $tabs['mwp_wpvivid_cloud_storage_s3compat']['callback']=array($this, 'output_cloud_storage_s3compat');
                $tabs['mwp_wpvivid_cloud_storage_s3compat']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_wasabi']['title']='Wasabi';
                $tabs['mwp_wpvivid_cloud_storage_wasabi']['slug']='mwp_wpvivid_cloud_storage_wasabi';
                $tabs['mwp_wpvivid_cloud_storage_wasabi']['callback']=array($this, 'output_cloud_storage_wasabi');
                $tabs['mwp_wpvivid_cloud_storage_wasabi']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_b2']['title']='Backblaze Storage';
                $tabs['mwp_wpvivid_cloud_storage_b2']['slug']='mwp_wpvivid_cloud_storage_b2';
                $tabs['mwp_wpvivid_cloud_storage_b2']['callback']=array($this, 'output_cloud_storage_b2');
                $tabs['mwp_wpvivid_cloud_storage_b2']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_webdav']['title']='Webdav';
                $tabs['mwp_wpvivid_cloud_storage_webdav']['slug']='mwp_wpvivid_cloud_storage_webdav';
                $tabs['mwp_wpvivid_cloud_storage_webdav']['callback']=array($this, 'output_cloud_storage_webdav');
                $tabs['mwp_wpvivid_cloud_storage_webdav']['args']=$args;

                $tabs['mwp_wpvivid_cloud_storage_nextcloud']['title']='NextCloud';
                $tabs['mwp_wpvivid_cloud_storage_nextcloud']['slug']='mwp_wpvivid_cloud_storage_nextcloud';
                $tabs['mwp_wpvivid_cloud_storage_nextcloud']['callback']=array($this, 'output_cloud_storage_nextcloud');
                $tabs['mwp_wpvivid_cloud_storage_nextcloud']['args']=$args;

                foreach ($tabs as $key=>$tab)
                {
                    $this->storage_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                }
                $this->storage_tab->display();
                ?>

            </div>
            <div id="mwp_wpvivid_remote_page_step_2" style="display: none;"></div>

            <div id="mwp_wpvivid_remote_page_step_3">
                <div class="mwp-wpvivid-block-bottom-space">
                    <?php
                    if(!class_exists('Mainwp_WPvivid_Tab_Page_Container'))
                        include_once MAINWP_WPVIVID_EXTENSION_PLUGIN_DIR . '/includes/wpvivid-backup-mainwp-tab-page-container.php';
                    $this->main_tab=new Mainwp_WPvivid_Tab_Page_Container();

                    $args['is_parent_tab']=0;
                    $args['transparency']=1;
                    $this->main_tab->add_tab('Storages','storages',array($this, 'output_storages_list'), $args);
                    $args['can_delete']=1;
                    $args['hide']=1;
                    $this->main_tab->add_tab('Storage Edit','storage_edit',array($this, 'output_storage_edit'), $args);
                    $this->main_tab->display();
                    ?>
                </div>
            </div>
        </div>

        <script>
            var mwp_add_remote_id = '';
            var mwp_wpvivid_editing_storage_id = '';
            var mwp_wpvivid_editing_storage_type = '';
            var mwp_wpvivid_sync_index = 0;
            var mwp_wpvivid_sync_arr = {};
            mwp_wpvivid_sync_arr.success_count = 0;
            mwp_wpvivid_sync_arr.fail_count = 0;
            mwp_wpvivid_sync_arr.fail_array = [];

            jQuery('input[option=add-remote-addon-global]').click(function () {
                //var storage_type = jQuery(".mwp-storage-providers-addon-active").attr("remote_type");
                var storage_type = jQuery(this).attr("remote_type");
                mwp_wpvivid_archieve_website_list(storage_type);
            });

            function mwp_wpvivid_archieve_website_list(storage_type){
                if(jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_check_all_websites').prop('checked')){
                    var batch_status = '1';
                }
                else{
                    var batch_status = '0';
                }
                var remote_from = mwp_wpvivid_ajax_data_transfer(storage_type+'-addon');
                var ajax_data = {
                    'action': 'mwp_wpvivid_archieve_website_list',
                    'remote': remote_from,
                    'type': storage_type,
                    'batch': batch_status
                };
                mwp_wpvivid_post_request(ajax_data, function (data){
                    try{
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_global_remote_list_addon').html(jsonarray.remote_list);
                            jQuery('#mwp_wpvivid_remote_page_step_1').hide();
                            jQuery('#mwp_wpvivid_remote_page_step_2').show();
                            jQuery('#mwp_wpvivid_remote_page_step_2').html(jsonarray.html);
                            jQuery('#mwp_wpvivid_remote_page_step_3').hide();
                            var html = '<div>This tab allows you to set default remote storage and set a custom backup folder in each remote storage for child sites.</div>' +
                                        '<div>Check the child sites and click Update to sync the settings to them.</div>';
                            jQuery('#mwp_wpvivid_remote_tab_desc').html(html);
                            mwp_add_remote_id = jsonarray.remote_id;
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

            jQuery('#mwp_wpvivid_remote_page_step_2').on('click', '.mwp-wpvivid-custom-path-edit', function(){
                jQuery(this).removeClass('mwp-wpvivid-custom-path-edit');
                jQuery(this).addClass('mwp-wpvivid-custom-path-save');
                jQuery(this).attr('value', 'Save');
                jQuery(this).closest('td').find('.mwp-wpvivid-remote-custom-path-input').attr('readonly', false);
                jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_remote_storage').css({'pointer-events': 'none', 'opacity': '0.4'});
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on('click', '.mwp-wpvivid-custom-path-save', function(){
                jQuery(this).removeClass('mwp-wpvivid-custom-path-save');
                jQuery(this).addClass('mwp-wpvivid-custom-path-edit');
                jQuery(this).attr('value', 'Edit');
                jQuery(this).closest('td').find('.mwp-wpvivid-remote-custom-path-input').attr('readonly', true);
                jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_remote_storage').css({'pointer-events': 'auto', 'opacity': '1'});
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on('click', '#mwp_wpvivid_sync_remote_storage', function(){
                var website_ids = [];
                var custom_path = {};
                var website_name = {};
                var default_setting = {};
                mwp_wpvivid_sync_index=0;
                mwp_wpvivid_sync_arr.success_count = 0;
                mwp_wpvivid_sync_arr.fail_count = 0;
                mwp_wpvivid_sync_arr.fail_array = [];
                jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_summary').hide();
                var default_setting = jQuery('#mwp_wpvivid_remote_page_step_2').find('input:radio[name=mwp_wpvivid_default_remote]:checked').val();
                if(jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_check_all_websites').prop('checked')){
                    var ajax_data = {
                        'action': 'mwp_wpvivid_archieve_all_website_list',
                        'remote_id': mwp_add_remote_id
                    };
                    mwp_wpvivid_post_request(ajax_data, function (data) {
                        try
                        {
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success')
                            {
                                jQuery.each(jsonarray.websites, function(key, value){
                                    var tmp_id='';
                                    var tmp_path='';
                                    var tmp_name='';

                                    var id = value.id;
                                    website_ids.push(id);
                                    jQuery('#mwp_wpvivid_remote_page_step_2 .mwp-wpvivid-sync-row input:checkbox').each(function (){
                                        if(jQuery(this).prop('checked')) {
                                            tmp_id = jQuery(this).closest('tr').attr('website-id');
                                            if(id === tmp_id)
                                            {
                                                tmp_path = jQuery(this).closest('tr').find('.mwp-wpvivid-remote-custom-path-input').val();
                                                tmp_name = jQuery(this).closest('tr').attr('website-name');
                                                return false;
                                            }
                                        }
                                    });

                                    if(tmp_path !== '')
                                    {
                                        custom_path[id] = tmp_path;
                                    }
                                    else
                                    {
                                        custom_path[id] = value.custom_path;
                                    }
                                    if(tmp_name !== '')
                                    {
                                        website_name[id] = tmp_name;
                                    }
                                    else
                                    {
                                        website_name[id] = value.name;
                                    }
                                });
                                if(website_ids.length>0)
                                {
                                    var descript = 'Are you sure you want to sync the settings to the selected child sites?';
                                    var ret = confirm(descript);
                                    if (ret === true) {
                                        jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_task_progress').show();
                                        jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_current_doing').html('Start sync.');
                                        jQuery('#mwp_wpvivid_sync_remote_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                                        var check_addon = '1';
                                        jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-wpvivid-return-remote').css({'pointer-events': 'none', 'opacity': '0.4'});
                                        mwp_wpvivid_sync_site_remote(website_ids,default_setting,custom_path,check_addon,'mwp_wpvivid_sync_global_remote_addon',website_name);
                                    }
                                }
                                else{
                                    alert('Please select at least one child site to sync the settings.');
                                }
                            }
                            else
                            {
                                alert(jsonarray.error);
                                return;
                            }
                        }
                        catch (err)
                        {
                            alert(err);
                            return;
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        alert(error_message);
                        return;
                    });
                }
                else{
                    jQuery('#mwp_wpvivid_remote_page_step_2 .mwp-wpvivid-sync-row input:checkbox').each(function (){
                        if(jQuery(this).prop('checked')) {
                            var id = jQuery(this).closest('tr').attr('website-id');
                            website_ids.push(id);
                            var path = jQuery(this).closest('tr').find('.mwp-wpvivid-remote-custom-path-input').val();
                            custom_path[id] = path;
                            var name = jQuery(this).closest('tr').attr('website-name');
                            website_name[id] = name;
                        }
                    });
                    if(website_ids.length>0)
                    {
                        var descript = 'Are you sure you want to sync the settings to the selected child sites?';
                        var ret = confirm(descript);
                        if (ret === true) {
                            jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_task_progress').show();
                            jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_current_doing').html('Start sync.');
                            jQuery('#mwp_wpvivid_sync_remote_addon').css({'pointer-events': 'none', 'opacity': '0.4'});
                            var check_addon = '1';
                            jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-wpvivid-return-remote').css({'pointer-events': 'none', 'opacity': '0.4'});
                            mwp_wpvivid_sync_site_remote(website_ids, default_setting, custom_path, check_addon, 'mwp_wpvivid_sync_global_remote_addon', website_name);
                        }
                    }
                    else{
                        alert('Please select at least one child site to sync the settings.');
                    }
                }
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on("click",'.first-page',function() {
                mwp_wpvivid_get_website_list('first');
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_website_list(page-1);
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_website_list(page+1);
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on("click",'.last-page',function() {
                mwp_wpvivid_get_website_list('last');
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_website_list(page);
                }
            });

            function mwp_wpvivid_get_website_list(page=0) {
                if(page === 0){
                    var current_page = jQuery('#mwp_wpvivid_website_list_addon').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_website_list_addon').find('.current-page').val();
                    }
                }
                if(jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_check_all_websites').prop('checked')){
                    var batch_status = '1';
                }
                else{
                    var batch_status = '0';
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_website_list',
                    'page':page,
                    'batch': batch_status,
                    'remote_id': mwp_add_remote_id
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_website_list_addon').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_website_list_addon').html(jsonarray.html);
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
                        mwp_wpvivid_get_website_list();
                    }, 3000);
                });
            }

            jQuery('#mwp_wpvivid_remote_page_step_2').on('click', '#mwp_wpvivid_check_all_websites', function(){
                if(jQuery(this).prop('checked')) {
                    jQuery('#mwp_wpvivid_website_list_addon').find('input:checkbox').prop('checked', true);
                    jQuery('#mwp_wpvivid_website_list_addon').find('.mwp-wpvivid-custom-path-edit').css({'pointer-events': 'none', 'opacity': '0.4'});
                }
                else{
                    jQuery('#mwp_wpvivid_website_list_addon').find('input:checkbox').prop('checked', false);
                    jQuery('#mwp_wpvivid_website_list_addon').find('.mwp-wpvivid-custom-path-edit').css({'pointer-events': 'auto', 'opacity': '1'});
                }
            });

            jQuery('#mwp_wpvivid_remote_page_step_2').on('click', '#mwp_wpvivid_website_list_addon input:checkbox', function(){
                if(!jQuery(this).prop('checked')){
                    jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_check_all_websites').prop('checked', false);
                    jQuery('#mwp_wpvivid_website_list_addon').find('.mwp-wpvivid-custom-path-edit').css({'pointer-events': 'auto', 'opacity': '1'});
                }
            });

            function mwp_wpvivid_sync_site_remote(website_ids,default_setting,custom_path,check_addon,action,website_name) {
                if(website_ids.length>mwp_wpvivid_sync_index) {
                    var id= website_ids[mwp_wpvivid_sync_index];
                    var path = custom_path[id];
                    var ajax_data = {
                        'action': action,
                        'site_id': id,
                        'default_setting': default_setting,
                        'custom_path': path,
                        'addon': check_addon,
                        'remote_id': mwp_add_remote_id
                    };
                    //jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('updating...');
                    jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_current_doing').html('Syncing the settings to '+website_name[id]);
                    mwp_wpvivid_post_request(ajax_data, function(data){
                        try {
                            var jsonarray = jQuery.parseJSON(data);

                            if (jsonarray.result === 'success')
                            {
                                mwp_wpvivid_sync_arr.success_count++;
                                var percent = (mwp_wpvivid_sync_arr.success_count + mwp_wpvivid_sync_arr.fail_count) / website_ids.length * 100;
                                jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-action-progress-bar-percent').css('width', percent+'%');
                                //jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update completed');
                                mwp_wpvivid_sync_index++;
                                mwp_wpvivid_sync_site_remote(website_ids,default_setting,custom_path,check_addon,action,website_name);
                            }
                            else {
                                mwp_wpvivid_sync_arr.fail_count++;
                                var percent = (mwp_wpvivid_sync_arr.success_count + mwp_wpvivid_sync_arr.fail_count) / website_ids.length * 100;
                                jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-action-progress-bar-percent').css('width', percent+'%');
                                mwp_wpvivid_sync_arr.fail_array.push(website_name[id]);
                                //jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update failed');
                                mwp_wpvivid_sync_index++;
                                mwp_wpvivid_sync_site_remote(website_ids,default_setting,custom_path,check_addon,action,website_name);
                            }
                        }
                        catch (err) {
                            mwp_wpvivid_sync_arr.fail_count++;
                            var percent = (mwp_wpvivid_sync_arr.success_count + mwp_wpvivid_sync_arr.fail_count) / website_ids.length * 100;
                            jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-action-progress-bar-percent').css('width', percent+'%');
                            mwp_wpvivid_sync_arr.fail_array.push(website_name[id]);
                            mwp_wpvivid_sync_index++;
                            mwp_wpvivid_sync_site_remote(website_ids,default_setting,custom_path,check_addon,action,website_name);
                        }
                    }, function (XMLHttpRequest, textStatus, errorThrown) {
                        mwp_wpvivid_sync_arr.fail_count++;
                        var percent = (mwp_wpvivid_sync_arr.success_count + mwp_wpvivid_sync_arr.fail_count) / website_ids.length * 100;
                        jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-action-progress-bar-percent').css('width', percent+'%');
                        mwp_wpvivid_sync_arr.fail_array.push(website_name[id]);
                        //jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update failed');
                        var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                        mwp_wpvivid_sync_index++;
                        mwp_wpvivid_sync_site_remote(website_ids,default_setting,custom_path,check_addon,action,website_name);
                    });
                }
                else{
                    jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_task_progress').hide();
                    jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_summary').show();
                    var sync_result = '';
                    sync_result += '<div class="mwp-wpvivid-block-bottom-space">Sync completed!</div>';
                    sync_result += '<div class="mwp-wpvivid-block-bottom-space">Total synced sites: '+website_ids.length+'</div>';
                    sync_result += '<div>Succeeded sites: '+mwp_wpvivid_sync_arr.success_count+'</div>';
                    if(mwp_wpvivid_sync_arr.fail_count > 0){
                        var fail_website = '';
                        for(var i = 0; i < mwp_wpvivid_sync_arr.fail_array.length; i++){
                            fail_website += mwp_wpvivid_sync_arr.fail_array[i] + ', ';
                        }
                        if (fail_website.length > 0) {
                            fail_website = fail_website.substr(0, fail_website.length - 2);
                        }
                        sync_result += '<div class="mwp-wpvivid-block-bottom-space" style="margin-top: 10px;">Failed sites: '+mwp_wpvivid_sync_arr.fail_count+'</div>';
                        sync_result += '<div>Failed sites name: '+fail_website+'</div>';
                    }
                    jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_sync_summary').html(sync_result);
                    jQuery('#mwp_wpvivid_remote_page_step_2').find('.mwp-wpvivid-return-remote').css({'pointer-events': 'auto', 'opacity': '1'});
                }
            }

            jQuery('#mwp_wpvivid_remote_page_step_2').on('click', '.mwp-wpvivid-return-remote', function(){
                jQuery('#mwp_wpvivid_remote_page_step_1').show();
                jQuery('#mwp_wpvivid_remote_page_step_2').hide();
                jQuery('#mwp_wpvivid_remote_page_step_3').show();
                var html = '<div>This tab allows you to add remote storage for child sites.</div>' +
                    '<div>Click \'Save and Sync\' or \'Sync\' to sync the remote storage to child sites.</div>';
                jQuery('#mwp_wpvivid_remote_tab_desc').html(html);
            });

            function mwp_wpvivid_retrieve_remote_storage(id, type, name){
                jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-show',[ 'storage_edit', 'storages' ]);
                mwp_wpvivid_editing_storage_id = id;
                mwp_wpvivid_editing_storage_type = type;
                jQuery('.mwp-wpvivid-remote-storage-edit').hide();
                jQuery('#mwp_wpvivid_storage_account_'+mwp_wpvivid_editing_storage_type+'_edit').fadeIn();
                jQuery('#wpvivid_page_storage_edit').find('#remote_storage_edit_'+mwp_wpvivid_editing_storage_type).hide();
                jQuery('#mwp_wpvivid_archieve_remote_info').show();
                jQuery('#mwp_wpvivid_archieve_remote_info').find('.spinner').addClass('is-active');
                jQuery('#mwp_wpvivid_archieve_remote_retry').hide();
                var retry = '<input type="button" class="ui green mini button" value="Retry the information retrieval" onclick="mwp_wpvivid_retrieve_remote_storage(\''+id+'\', \''+type+'\', \''+name+'\');" />';
                var ajax_data = {
                    'action': 'mwp_wpvivid_retrieve_global_remote_addon',
                    'remote_id': id
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    jQuery('#mwp_wpvivid_archieve_remote_info').hide();
                    jQuery('#mwp_wpvivid_archieve_remote_info').find('.spinner').removeClass('is-active');
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success'){
                            jQuery('input:text[option=edit-'+jsonarray.data.type+'-addon]').each(function(){
                                var key = jQuery(this).prop('name');
                                jQuery(this).val(jsonarray.data[key]);
                            });
                            jQuery('input:password[option=edit-'+jsonarray.data.type+'-addon]').each(function(){
                                var key = jQuery(this).prop('name');
                                jQuery(this).val(jsonarray.data[key]);
                            });
                            jQuery('input:checkbox[option=edit-'+jsonarray.data.type+'-addon]').each(function() {
                                var key = jQuery(this).prop('name');
                                var value;
                                if(jsonarray.data[key] == '0'){
                                    value = false;
                                }
                                else{
                                    value = true;
                                }
                                jQuery(this).prop('checked', value);

                                if(key === 'use_remote_retention')
                                {
                                    if(value)
                                    {
                                        jQuery('.wpvivid-retention-tr-'+jsonarray.data.type+'-addon').show();
                                    }
                                    else
                                    {
                                        jQuery('.wpvivid-retention-tr-'+jsonarray.data.type+'-addon').hide();
                                    }
                                }
                            });
                            if(jsonarray.data.type === 'wasabi'){
                                if(jsonarray.data.endpoint === 's3.wasabisys.com'){
                                    jQuery('#mwp_wpvivid_wasabi_endpoint_select_edit').val('us_east1');
                                }
                                if(jsonarray.data.endpoint === 's3.us-east-2.wasabisys.com'){
                                    jQuery('#mwp_wpvivid_wasabi_endpoint_select_edit').val('us_east2');
                                }
                                else if(jsonarray.data.endpoint === 's3.us-west-1.wasabisys.com'){
                                    jQuery('#mwp_wpvivid_wasabi_endpoint_select_edit').val('us_west1');
                                }
                                else if(jsonarray.data.endpoint === 's3.eu-central-1.wasabisys.com'){
                                    jQuery('#mwp_wpvivid_wasabi_endpoint_select_edit').val('us_central1');
                                }
                                else{
                                    jQuery('#mwp_wpvivid_wasabi_endpoint_select_edit').val('custom');
                                }
                            }
                        }
                        else if (jsonarray.result === 'failed'){
                            jQuery('#mwp_wpvivid_archieve_remote_retry').show();
                            jQuery('#mwp_wpvivid_archieve_remote_retry').html(retry);
                            alert(jsonarray.error);
                        }
                    }
                    catch(err) {
                        jQuery('#mwp_wpvivid_archieve_remote_retry').show();
                        jQuery('#mwp_wpvivid_archieve_remote_retry').html(retry);
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#mwp_wpvivid_archieve_remote_info').hide();
                    jQuery('#mwp_wpvivid_archieve_remote_info').find('.spinner').removeClass('is-active');
                    jQuery('#mwp_wpvivid_archieve_remote_retry').show();
                    jQuery('#mwp_wpvivid_archieve_remote_retry').html(retry);
                    var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_delete_remote_storage_addon(id){
                var descript = 'Deleting a remote storage will make it unavailable until it is added again. Are you sure to continue?';
                var ret = confirm(descript);
                if(ret === true){
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_global_remote_addon',
                        'remote_id': id
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data)
                    {
                        try{
                            var jsonarray = jQuery.parseJSON(data);
                            if (jsonarray.result === 'success'){
                                jQuery('#mwp_wpvivid_global_remote_list_addon').html(jsonarray.html);
                            }
                            else if (jsonarray.result === 'failed'){
                                alert(jsonarray.error);
                            }
                        }
                        catch(err) {
                            alert(err);
                        }
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the remote storage', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }

            jQuery('.mwp-wpvivid-remote-backup-retain').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery(this).val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery(this).val('');
                }
            });

            jQuery('.mwp-wpvivid-remote-backup-db-retain').on("keyup", function(){
                var regExp = /^[1-9][0-9]{0,2}$/g;
                var input_value = jQuery(this).val();
                if(!regExp.test(input_value)){
                    alert('Only enter numbers from 1-999');
                    jQuery(this).val('');
                }
            });

            jQuery('input[option=edit-remote-addon-global]').click(function(){
                mwp_wpvivid_edit_remote_storage();
            });

            function mwp_wpvivid_edit_remote_storage() {
                var data_tran = 'edit-'+mwp_wpvivid_editing_storage_type+'-addon';
                var remote_data = mwp_wpvivid_ajax_data_transfer(data_tran);
                var ajax_data = {
                    'action': 'mwp_wpvivid_update_global_remote_addon',
                    'remote': remote_data,
                    'remote_id': mwp_wpvivid_editing_storage_id,
                    'type': mwp_wpvivid_editing_storage_type
                };
                mwp_wpvivid_post_request(ajax_data, function(data){
                    try {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery( document ).trigger( '<?php echo esc_js($this->main_tab->container_id); ?>-delete',[ 'storage_edit', 'storages' ]);
                        }
                        else if (jsonarray.result === 'failed') {
                            alert(jsonarray.error);
                        }
                    }
                    catch(err){
                        alert(err);
                    }
                },function(XMLHttpRequest, textStatus, errorThrown) {
                    var error_message = mwp_wpvivid_output_ajaxerror('editing the remote storage', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            function mwp_wpvivid_archieve_website_list_ex(remote_id){
                if(jQuery('#mwp_wpvivid_remote_page_step_2').find('#mwp_wpvivid_check_all_websites').prop('checked')){
                    var batch_status = '1';
                }
                else{
                    var batch_status = '0';
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_archieve_website_list_ex',
                    'remote_id': remote_id,
                    'batch': batch_status
                };
                mwp_wpvivid_post_request(ajax_data, function (data){
                    try{
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success') {
                            jQuery('#mwp_wpvivid_remote_page_step_1').hide();
                            jQuery('#mwp_wpvivid_remote_page_step_2').show();
                            jQuery('#mwp_wpvivid_remote_page_step_2').html(jsonarray.html);
                            var html = '<div>This tab allows you to set default remote storage and set a custom backup folder in each remote storage for child sites.</div>' +
                                '<div>Check the child sites and click Update to sync the settings to them.</div>';
                            jQuery('#mwp_wpvivid_remote_tab_desc').html(html);
                            jQuery('#mwp_wpvivid_remote_page_step_3').hide();
                        }
                        else if (jsonarray.result === 'failed') {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = mwp_wpvivid_output_ajaxerror('adding the remote storage', textStatus, errorThrown);
                    alert(error_message);
                });
            }

            jQuery('#mwp_wpvivid_global_remote_list_addon').on('click', '.mwp-wpvivid-sync-remote', function(){
                var remote_id = jQuery(this).closest('tr').attr('id');
                mwp_add_remote_id = remote_id;
                mwp_wpvivid_archieve_website_list_ex(remote_id);
            });

            jQuery('#mwp_wpvivid_global_remote_list_addon').on("click",'.first-page',function() {
                mwp_wpvivid_get_remote_storage_list('first');
            });

            jQuery('#mwp_wpvivid_global_remote_list_addon').on("click",'.prev-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_remote_storage_list(page-1);
            });

            jQuery('#mwp_wpvivid_global_remote_list_addon').on("click",'.next-page',function() {
                var page=parseInt(jQuery(this).attr('value'));
                mwp_wpvivid_get_remote_storage_list(page+1);
            });

            jQuery('#mwp_wpvivid_global_remote_list_addon').on("click",'.last-page',function() {
                mwp_wpvivid_get_remote_storage_list('last');
            });

            jQuery('#mwp_wpvivid_global_remote_list_addon').on("keypress", '.current-page', function(){
                if(event.keyCode === 13){
                    var page = jQuery(this).val();
                    mwp_wpvivid_get_remote_storage_list(page);
                }
            });

            function mwp_wpvivid_get_remote_storage_list(page=0) {
                if(page === 0){
                    var current_page = jQuery('#mwp_wpvivid_global_remote_list_addon').find('.current-page').val();
                    if(typeof current_page !== 'undefined') {
                        page = jQuery('#mwp_wpvivid_global_remote_list_addon').find('.current-page').val();
                    }
                }
                var ajax_data = {
                    'action': 'mwp_wpvivid_get_remote_storage_list',
                    'page':page
                };
                mwp_wpvivid_post_request(ajax_data, function (data) {
                    jQuery('#mwp_wpvivid_global_remote_list_addon').html('');
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#mwp_wpvivid_global_remote_list_addon').html(jsonarray.remote_list);
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
                        mwp_wpvivid_get_remote_storage_list();
                    }, 3000);
                });
            }
        </script>
        <?php
    }

    public function output_storages_list(){
        $remote_storages = $this->setting_addon;
        $remote_list = '';
        if(isset($remote_storages['upload']) && !empty($remote_storages['upload'])){
            $remote_list = $remote_storages['upload'];
        }
        ?>
        <div class="mwp-wpvivid-block-bottom-space" style="margin-top:10px;">
            <p><strong><?php esc_html_e('Please choose one storage to synchronize the settings to child sites', 'wpvivid');?></strong></p>
        </div>
        <div id="mwp_wpvivid_global_remote_list_addon">
            <?php
            $table=new MainWP_WPvivid_Remote_Storage_Global_List();
            $table->set_storage_list($remote_list);
            $table->prepare_items();
            $table->display();
            ?>
        </div>
        <?php
    }

    public function output_storage_edit(){
        ?>
        <div id="mwp_wpvivid_archieve_remote_info" style="margin-top: 10px;">
            <div style="float: left; height: 20px; line-height: 20px; margin-top: 4px;">Retrieving the information of remote storge</div>
            <div class="spinner" style="float: left;"></div>
            <div style="clear: both;"></div>
        </div>
        <div id="mwp_wpvivid_archieve_remote_retry" style="margin-top: 10px; display: none;"></div>
        <div><?php do_action('mwp_wpvivid_edit_storage_page_addon'); ?></div>
        <?php
    }

    function mwp_wpvivid_add_page_storage_list(){
        ?>
        <div class="storage-tab-content" id="page-storage-list">
            <div class="mwp-wpvivid-block-bottom-space"><p><strong><?php esc_html_e('Please choose one storage to save your backups (remote storage)', 'mainwp-wpvivid-extension'); ?></strong></p></div>
            <div class="schedule-tab-block"></div>
            <div>
                <table class="widefat">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th><?php esc_html_e( 'Storage Provider', 'mainwp-wpvivid-extension' ); ?></th>
                        <th class="row-title"><?php esc_html_e( 'Remote Storage Alias', 'mainwp-wpvivid-extension' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'mainwp-wpvivid-extension' ); ?></th>
                    </tr>
                    </thead>
                    <tbody class="mwp-wpvivid-remote-storage-list" id="mwp_wpvivid_remote_storage_list">
                    <?php
                    $html = '';
                    $html = apply_filters('mwp_wpvivid_add_remote_storage_list', $html);
                    echo esc_attr($html);
                    ?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th colspan="5" class="row-title"><input class="ui green mini button" id="mwp_wpvivid_set_default_remote_storage" type="button" name="choose-remote-storage" value="<?php esc_attr_e('Save Changes'); ?>" /></th>
                    </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <script>
            function mwp_wpvivid_delete_remote_storage(storage_id)
            {
                var descript = 'Deleting a remote storage will make it unavailable until it is added again. Are you sure to continue?';
                var ret = confirm(descript);
                if(ret === true){
                    var ajax_data = {
                        'action': 'mwp_wpvivid_delete_remote',
                        'remote_id': storage_id
                    };
                    mwp_wpvivid_post_request(ajax_data, function(data)
                    {
                        mwp_wpvivid_handle_remote_storage_data(data);
                    },function(XMLHttpRequest, textStatus, errorThrown)
                    {
                        var error_message = mwp_wpvivid_output_ajaxerror('deleting the remote storage', textStatus, errorThrown);
                        alert(error_message);
                    });
                }
            }
        </script>
        <?php
    }

    public function mwp_wpvivid_synchronize_setting($check_addon)
    {

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
            <tr class="mwp-wpvivid-sync-row">
                <th class="check-column" website-id="<?php echo esc_attr($website_id); ?>">
                    <input type="checkbox"  name="checked[]">
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