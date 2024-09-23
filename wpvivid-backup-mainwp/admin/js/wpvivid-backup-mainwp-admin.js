var mwp_wpvivid_sync_index=0;
var mwp_wpvivid_need_update=true;
var mwp_running_backup_taskid='';
var mwp_wpvivid_has_remote = true;
var mwp_is_update = false;
var mwp_is_claim = false;

(function ($) {
    'use strict';

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    $(document).ready(function () {
        mwp_wpvivid_interface_flow_control();
        $('input[option=edit-remote]').click(function(){
            mwp_wpvivid_edit_remote_storage();
        });
    });
    
})(jQuery);

function mwp_wpvivid_activate_cron(){
    var next_get_time = 3 * 60 * 1000;
    mwp_wpvivid_cron_task();
    setTimeout("mwp_wpvivid_activate_cron()", next_get_time);
    setTimeout(function(){
        mwp_wpvivid_need_update=true;
    }, 10000);
}

/**
 * Send an Ajax request
 *
 * @param ajax_data         - Data in Ajax request
 * @param callback          - A callback function when the request is succeeded
 * @param error_callback    - A callback function when the request is failed
 * @param time_out          - The timeout for Ajax request
 */
function mwp_wpvivid_post_request(ajax_data, callback, error_callback, time_out){
    if(typeof time_out === 'undefined')    time_out = 30000;
    ajax_data.nonce=ajax_object.ajax_nonce;
    jQuery.ajax({
        type: "post",
        url: ajax_object.ajax_url,
        data: ajax_data,
        success: function (data) {
            callback(data);
        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            error_callback(XMLHttpRequest, textStatus, errorThrown);
        },
        timeout: time_out
    });
}

function mwp_switch_wpvivid_tab(tab_type){
    var tab_backup = jQuery('#mwp_wpvivid_tab_backup');
    var tab_backup_restore = jQuery('#mwp_wpvivid_tab_backup_restore');
    var tab_schedule = jQuery('#mwp_wpvivid_tab_schedule');
    var tab_setting = jQuery('#mwp_wpvivid_tab_setting');
    var tab_capability = jQuery('#mwp_wpvivid_tab_capability');
    var tab_white_label = jQuery('#mwp_wpvivid_tab_white_label');

    var page_backup = jQuery('#mwp_wpvivid_page_backup');
    var page_backup_restore = jQuery('#mwp_wpvivid_page_backup_restore');
    var page_schedule = jQuery('#mwp_wpvivid_page_schedule');
    var page_setting = jQuery('#mwp_wpvivid_page_setting');
    var page_capability = jQuery('#mwp_wpvivid_page_capability');
    var page_white_label = jQuery('#mwp_wpvivid_page_white_label');

    page_backup.hide();
    page_backup_restore.hide();
    page_schedule.hide();
    page_setting.hide();
    page_capability.hide();
    page_white_label.hide();
    if(tab_type === 'backup'){
        tab_backup.addClass('active');
        page_backup.show();
    }
    else{
        tab_backup.removeClass('active');
    }

    if(tab_type === 'backup_restore'){
        tab_backup_restore.addClass('active');
        page_backup_restore.show();
    }
    else{
        tab_backup_restore.removeClass('active');
    }

    if(tab_type === 'schedule'){
        tab_schedule.addClass('active');
        page_schedule.show();
    }
    else{
        tab_schedule.removeClass('active');
    }

    if(tab_type === 'setting'){
        tab_setting.addClass('active');
        page_setting.show();
    }
    else{
        tab_setting.removeClass('active');
    }

    if(tab_type === 'capability'){
        tab_capability.addClass('active');
        page_capability.show();
    }
    else{
        tab_capability.removeClass('active');
    }

    if(tab_type === 'white_label'){
        tab_white_label.addClass('active');
        page_white_label.show();
    }
    else{
        tab_white_label.removeClass('active');
    }
}

function mwp_wpvivid_read_log(action, param){
    var tab_id = '';
    var content_id = '';
    var ajax_data = '';
    var show_page = '';
    if(typeof param === 'undefined')    param = '';
    switch(action){
        case 'mwp_wpvivid_view_backup_task_log':
            ajax_data = {
                'action':action,
                'site_id':site_id,
                'id':mwp_running_backup_taskid
            };
            tab_id = 'mwp_wpvivid_tab_backup_log';
            content_id = 'wpvivid_display_log_content';
            show_page = 'backup_page';
            break;
        case 'mwp_wpvivid_read_last_backup_log':
            ajax_data = {
                'action': action,
                'site_id':site_id,
                'log_file_name': param
            };
            tab_id = 'mwp_wpvivid_tab_backup_log';
            content_id = 'wpvivid_display_log_content';
            show_page = 'backup_page';
            break;
        case 'mwp_wpvivid_view_log':
            ajax_data={
                'action':action,
                'site_id':site_id,
                'id':param
            };
            tab_id = 'mwp_wpvivid_tab_backup_log';
            content_id = 'wpvivid_display_log_content';
            show_page = 'backup_page';
            break;
        default:
            break;
    }
    jQuery('#'+tab_id).show();
    jQuery('#'+content_id).html("");
    if(show_page === 'backup_page'){
        mwp_wpvivid_click_switch_page('mwp-wpvivid-backup', tab_id, true);
    }
    mwp_wpvivid_post_request(ajax_data, function(data){
        mwp_wpvivid_show_log(data, content_id);
    }, function(XMLHttpRequest, textStatus, errorThrown) {
        var div = 'Reading the log failed. Please try again.';
        jQuery('#wpvivid_display_log_content').html(div);
    });
}

/**
 * This function will show the log on a text box.
 *
 * @param data - The log message returned by server
 */
function mwp_wpvivid_show_log(data, content_id){
    jQuery('#'+content_id).html("");
    try {
        var jsonarray = jQuery.parseJSON(data);
        if (jsonarray.result === "success") {
            var log_data = jsonarray.data;
            while (log_data.indexOf('\n') >= 0) {
                var iLength = log_data.indexOf('\n');
                var log = log_data.substring(0, iLength);
                log_data = log_data.substring(iLength + 1);
                var insert_log = "<div style=\"clear:both;\">" + log + "</div>";
                jQuery('#'+content_id).append(insert_log);
            }
        }
        else if (jsonarray.result === "failed") {
            jQuery('#'+content_id).html(jsonarray.error);
        }
    }
    catch(err){
        alert(err);
        var div = "Reading the log failed. Please try again.";
        jQuery('#'+content_id).html(div);
    }
}

function mwp_wpvivid_sync_site(website_ids,check_addon,action,return_page,tab_id)
{
    if(website_ids.length>mwp_wpvivid_sync_index)
    {
        var id= website_ids[mwp_wpvivid_sync_index];
        if(action === 'mwp_wpvivid_sync_schedule' && check_addon == '1'){
            var default_setting = jQuery('input:radio[name=mwp_wpvivid_default_schedule]:checked').val();
            var ajax_data = {
                'action': action,
                'id': id,
                'addon': check_addon,
                'default_setting': default_setting
            };
        }
        else {
            var ajax_data = {
                'action': action,
                'id': id,
                'addon': check_addon
            };
        }
        jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('updating...');
        mwp_wpvivid_post_request(ajax_data, function (data) {
            try {
                var jsonarray = jQuery.parseJSON(data);

                if (jsonarray.result === 'success')
                {
                    jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update completed');
                    mwp_wpvivid_sync_index++;
                    mwp_wpvivid_sync_site(website_ids,check_addon,action,return_page,tab_id);
                }
                else {
                    jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update failed');
                    mwp_wpvivid_sync_index++;
                    mwp_wpvivid_sync_site(website_ids,check_addon,action,return_page,tab_id);
                }
            }
            catch (err) {
                mwp_wpvivid_sync_index++;
                mwp_wpvivid_sync_site(website_ids,check_addon,action,return_page,tab_id);
            }
        }, function (XMLHttpRequest, textStatus, errorThrown) {
            jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update failed');
            var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
            mwp_wpvivid_sync_index++;
            mwp_wpvivid_sync_site(website_ids,check_addon,action,return_page,tab_id);
        });
    }
    else
    {
        var res_type = 'Return to Setting Page';
        if(action === 'mwp_wpvivid_sync_schedule'){
            res_type = 'Return to Schedule Page';
        }
        else if(action === 'mwp_wpvivid_sync_remote'){
            res_type = 'Return to Remote Page';
        }
        else if(action === 'mwp_wpvivid_sync_setting'){
            res_type = 'Return to Setting Page';
        }
        var html = '<div style="margin-left: 10px;"><a href="admin.php?page='+return_page+'" class="ui green mini button" type="button">'+res_type+'</a></div>';
        jQuery('#'+tab_id).append(html);
    }
}

function mwp_wpvivid_sync_schedule_mould(website_ids, schedule_mould_name, check_addon,action,return_page,tab_id)
{
    if(website_ids.length>mwp_wpvivid_sync_index)
    {
        var id= website_ids[mwp_wpvivid_sync_index];
        if(action === 'mwp_wpvivid_sync_schedule' && check_addon == '1'){
            var default_setting = jQuery('input:radio[name=mwp_wpvivid_default_schedule]:checked').val();
            var ajax_data = {
                'action': action,
                'id': id,
                'addon': check_addon,
                'default_setting': default_setting,
                'schedule_mould_name': schedule_mould_name
            };
        }
        else if(action === 'mwp_wpvivid_sync_incremental_schedule' && check_addon == '1'){
            var ajax_data = {
                'action': action,
                'id': id,
                'addon': check_addon,
                'schedule_mould_name': schedule_mould_name
            };
        }
        else {
            var ajax_data = {
                'action': action,
                'id': id,
                'addon': check_addon
            };
        }
        jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('updating...');
        mwp_wpvivid_post_request(ajax_data, function (data) {
            try {
                var jsonarray = jQuery.parseJSON(data);

                if (jsonarray.result === 'success')
                {
                    jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update completed');
                    mwp_wpvivid_sync_index++;
                    mwp_wpvivid_sync_schedule_mould(website_ids,schedule_mould_name,check_addon,action,return_page,tab_id);
                }
                else {
                    jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update failed');
                    mwp_wpvivid_sync_index++;
                    mwp_wpvivid_sync_schedule_mould(website_ids,schedule_mould_name,check_addon,action,return_page,tab_id);
                }
            }
            catch (err) {
                mwp_wpvivid_sync_index++;
                mwp_wpvivid_sync_schedule_mould(website_ids,schedule_mould_name,check_addon,action,return_page,tab_id);
            }
        }, function (XMLHttpRequest, textStatus, errorThrown) {
            jQuery('.mwp-wpvivid-progress[website-id='+id+']').children().html('update failed');
            var error_message = mwp_wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
            mwp_wpvivid_sync_index++;
            mwp_wpvivid_sync_schedule_mould(website_ids,schedule_mould_name,check_addon,action,return_page,tab_id);
        });
    }
    else
    {
        var res_type = 'Return to Setting Page';
        if(action === 'mwp_wpvivid_sync_schedule'){
            res_type = 'Return to Schedule Page';
        }
        else if(action === 'mwp_wpvivid_sync_remote'){
            res_type = 'Return to Remote Page';
        }
        else if(action === 'mwp_wpvivid_sync_setting'){
            res_type = 'Return to Setting Page';
        }
        var html = '<div style="margin-left: 10px;"><a href="admin.php?page='+return_page+'" class="ui green mini button" type="button">'+res_type+'</a></div>';
        jQuery('#'+tab_id).append(html);
    }
}

/**
 * This function will control interface flow.
 */
function mwp_wpvivid_interface_flow_control(){
    jQuery('#quickstart_storage_setting').css({'pointer-events': 'none', 'opacity': '0.4'});
    jQuery('#wpvivid_backup_remote').click(function(){
        if(jQuery('#wpvivid_backup_remote').prop('checked') === true){
            jQuery('#quickstart_storage_setting').css({'pointer-events': 'auto', 'opacity': '1'});
        }
        else{
            jQuery('#quickstart_storage_setting').css({'pointer-events': 'none', 'opacity': '0.4'});
        }
    });

    jQuery('input[name="remote_storage"]').on("click", function(){
        var check_status = true;
        if(jQuery(this).prop('checked') === true){
            check_status = true;
        }
        else {
            check_status = false;
        }
        jQuery('input[name="remote_storage"]').prop('checked', false);
        if(check_status === true){
            jQuery(this).prop('checked', true);
        }
        else {
            jQuery(this).prop('checked', false);
        }
    });
}

function mwp_wpvivid_add_notice(notice_action, notice_type, notice_msg){
    var notice_id="";
    var tmp_notice_msg = "";
    if(notice_type === "Warning"){
        tmp_notice_msg = "Warning: " + notice_msg;
    }
    else if(notice_type === "Error"){
        tmp_notice_msg = "Error: " + notice_msg;
    }
    else if(notice_type === "Success"){
        tmp_notice_msg = "Success: " + notice_msg;
    }
    else if(notice_type === "Info"){
        tmp_notice_msg = notice_msg;
    }
    switch(notice_action){
        case "Backup":
            notice_id="mwp_wpvivid_backup_notice";
            break;
    }
    var bfind = false;
    $div = jQuery('#'+notice_id).children('div').children('p');
    $div.each(function (index, value) {
        if(notice_action === "Backup" && notice_type === "Success"){
            bfind = false;
            return false;
        }
        if (value.innerHTML === tmp_notice_msg) {
            bfind = true;
            return false;
        }
    });
    if (bfind === false) {
        jQuery('#'+notice_id).show();
        var div = '';
        if(notice_type === "Warning"){
            div = "<div class='notice notice-warning is-dismissible inline' style='margin: 0; padding-top: 10px;'><p>Warning: " + notice_msg + "</p>" +
                "<button type='button' class='notice-dismiss' onclick='mwp_click_dismiss_notice(this);'>" +
                "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                "</button>" +
                "</div>";
        }
        else if(notice_type === "Error"){
            div = "<div class='notice notice-error inline' style='margin: 0; padding: 10px;'><p>Error: " + notice_msg + "</p></div>";
        }
        else if(notice_type === "Success"){
            mwp_wpvivid_clear_notice('mwp_wpvivid_backup_notice');
            jQuery('#mwp_wpvivid_backup_notice').show();
            var success_msg = "backup task have been completed.";
            div = "<div class='notice notice-success is-dismissible inline' style='margin: 0; padding-top: 10px; margin-left: 0px !important;'><p>" + success_msg + "</p>" +
                "<button type='button' class='notice-dismiss' onclick='mwp_click_dismiss_notice(this);'>" +
                "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                "</button>" +
                "</div>";
        }
        else if(notice_type === "Info"){
            div = "<div class='notice notice-info is-dismissible inline' style='margin: 0; padding-top: 10px; margin-left: 0px !important;'><p>" + notice_msg + "</p>" +
                "<button type='button' class='notice-dismiss' onclick='mwp_click_dismiss_notice(this);'>" +
                "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                "</button>" +
                "</div>";
        }
        jQuery('#'+notice_id).append(div);
    }
}

function mwp_click_dismiss_notice(obj){
    wpvivid_completed_backup = 1;
    jQuery(obj).parent().remove();
}

function mwp_wpvivid_get_download_task(backup_id){
    var ajax_data = {
        'action': 'mwp_wpvivid_get_download_task',
        'site_id':site_id,
        'backup_id':backup_id
    };
    mwp_wpvivid_post_request(ajax_data, function(data){
        try {
            var jsonarray = jQuery.parseJSON(data);
            if(jsonarray.length !== 0) {
                if (jsonarray.result === 'success') {
                    jQuery('#wpvivid_file_part_' + backup_id).html("");
                    var file_not_found = false;
                    jQuery.each(jsonarray.files, function (index, value) {
                        if (value.status === 'need_download') {
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            setTimeout(function () {
                                mwp_wpvivid_get_download_task(backup_id);
                            }, 3000);
                        }
                        else if (value.status === 'running') {
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_lock_download();
                            setTimeout(function () {
                                mwp_wpvivid_get_download_task(backup_id);
                            }, 3000);
                        }
                        else if (value.status === 'completed') {
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_unlock_download();
                        }
                        else if (value.status === 'error') {
                            alert(value.error);
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_unlock_download();
                        }
                        else if (value.status === 'timeout') {
                            alert('Download timeout, please retry.');
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_unlock_download();
                        }
                        else if (value.status === 'file_not_found') {
                            alert("Download failed, file not found. The file might has been moved, renamed or deleted. Please verify the file exists and try again.");
                            mwp_wpvivid_unlock_download();
                            return false;
                        }
                    });
                    if (file_not_found === false) {
                        jQuery('#wpvivid_file_part_' + backup_id).append(jsonarray.files.place_html);
                    }
                }
            }
        }
        catch(err){
            alert(err);
        }
    },function(XMLHttpRequest, textStatus, errorThrown){
        var error_message = mwp_wpvivid_output_ajaxerror('initializing download information', textStatus, errorThrown);
        alert(error_message);
    });
}

/**
 * This function will initialize the download information.
 *
 * @param backup_id - The unique ID of the backup
 */
function mwp_wpvivid_initialize_download(backup_id){
    mwp_wpvivid_reset_backup_list();
    jQuery('#wpvivid_download_loading_'+backup_id).addClass('is-active');
    tmp_current_click_backupid = backup_id;
    var ajax_data = {
        'action':'mwp_wpvivid_init_download_page',
        'site_id':site_id,
        'backup_id':backup_id
    };
    mwp_wpvivid_post_request(ajax_data, function(data){
        try {
            var jsonarray = jQuery.parseJSON(data);
            jQuery('#wpvivid_download_loading_'+backup_id).removeClass('is-active');
            if (jsonarray.result === 'success') {
                jQuery('#wpvivid_file_part_' + backup_id).html("");
                var i = 0;
                var file_not_found = false;
                    var file_name = '';
                    jQuery.each(jsonarray.files, function (index, value) {
                        i++;
                        file_name = index;
                        if (value.status === 'need_download') {
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                        }
                        else if (value.status === 'running') {
                            mwp_wpvivid_lock_download();
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_get_download_task(backup_id);
                        }
                        else if (value.status === 'completed') {
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_unlock_download();
                        }
                        else if (value.status === 'error') {
                            alert(value.error);
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                        }
                        else if (value.status === 'timeout') {
                            jQuery('#wpvivid_file_part_' + backup_id).append(value.html);
                            mwp_wpvivid_unlock_download();
                        }
                        else if (value.status === 'file_not_found') {
                            file_not_found=true;
                            mwp_wpvivid_reset_backup_list();
                            alert("Download failed, file not found. The file might has been moved, renamed or deleted. Please verify the file exists and try again.");
                            mwp_wpvivid_unlock_download();
                            return false;
                        }
                    });
                    if (file_not_found === false) {
                        jQuery('#wpvivid_file_part_' + backup_id).append(jsonarray.files.place_html);
                    }
            }
        }
        catch(err){
            alert(err);
            jQuery('#wpvivid_download_loading_'+backup_id).removeClass('is-active');
        }
    },function(XMLHttpRequest, textStatus, errorThrown){
        jQuery('#wpvivid_download_loading_'+backup_id).removeClass('is-active');
        var error_message = mwp_wpvivid_output_ajaxerror('initializing download information', textStatus, errorThrown);
        alert(error_message);
    });
}

function mwp_wpvivid_lock_download(){
    jQuery('#mwp_wpvivid_backuplist tr').each(function(i){
        jQuery(this).children('td').each(function (j) {
            if (j == 2) {
                jQuery(this).css({'pointer-events': 'none', 'opacity': '0.4'});
            }
        });
    });
}

function mwp_wpvivid_unlock_download(){
    jQuery('#mwp_wpvivid_backuplist tr').each(function(i){
        jQuery(this).children('td').each(function (j) {
            if (j == 2) {
                jQuery(this).css({'pointer-events': 'auto', 'opacity': '1'});
            }
        });
    });
}

function mwp_wpvivid_clear_notice(notice_id){
    var t = document.getElementById(notice_id);
    var oDiv = t.getElementsByTagName("div");
    var count = oDiv.length;
    for (count; count > 0; count--) {
        var i = count - 1;
        oDiv[i].parentNode.removeChild(oDiv[i]);
    }
    jQuery('#'+notice_id).hide();
}

/**
 * Start downloading backup
 *
 * @param part_num  - The part number for the download object
 * @param backup_id - The unique ID for the backup
 * @param file_name - File name
 */
function mwp_wpvivid_prepare_download(part_num, backup_id, file_name){
    var ajax_data = {
        'action': 'mwp_wpvivid_prepare_download_backup',
        'site_id':site_id,
        'backup_id':backup_id,
        'file_name':file_name
    };
    mwp_wpvivid_lock_download();
    m_downloading_id = backup_id;
    tmp_current_click_backupid = backup_id;
    m_downloading_file_name = file_name;
    mwp_wpvivid_get_download_task(backup_id);
    mwp_wpvivid_post_request(ajax_data, function(data)
    {
    }, function(XMLHttpRequest, textStatus, errorThrown)
    {
    }, 0);
}

function mwp_wpvivid_utf8_to_b64(str) {
    return window.btoa(str);
}

/*function mwp_wpvivid_get_donwnloadlink(site_id, location) {
    location = location + '&_mwpNoneName=_wpnonce&_mwpNoneValue=wpvivid_download';
    return 'admin.php?page=Extensions-Wpvivid-Backup-Mainwp&action=mwpWPvividOpenSite&websiteid=' + site_id + '&open_location=' + mwp_wpvivid_utf8_to_b64(location);
}*/

/**
 * Download backups to user's computer.
 *
 * @param backup_id     - The unique ID for the backup
 * @param file_name     - File name
 */
/*function mwp_wpvivid_download(backup_id, file_name){
    var loc = 'admin-ajax.php?backup_id='+backup_id+'&file_name='+file_name+'&action=wpvivid_download_backup_mainwp';
    var url =  mwp_wpvivid_get_donwnloadlink(site_id, loc);
    window.open(url, '_blank');
}*/

function mwp_wpvivid_click_switch_page(tab, type, scroll){
    jQuery('.'+tab+'-tab-content:not(.' + type + ')').hide();
    jQuery('.'+tab+'-tab-content.' + type).show();
    jQuery('.'+tab+'-nav-tab:not(#' + type + ')').removeClass('nav-tab-active');
    jQuery('.'+tab+'-nav-tab#' + type).addClass('nav-tab-active');
    if(scroll == true){
        var top = jQuery('#'+type).offset().top-jQuery('#'+type).height();
        jQuery('html, body').animate({scrollTop:top}, 'slow');
    }
}

function mwp_wpvivid_close_tab(event, hide_tab, type, show_tab){
    event.stopPropagation();
    jQuery('#'+hide_tab).hide();
    mwp_wpvivid_click_switch_page(type, show_tab, true);
}

/**
 * Output ajax error in a standard format.
 *
 * @param action        - The specific operation
 * @param textStatus    - The textual status message returned by the server
 * @param errorThrown   - The error message thrown by server
 *
 * @returns {string}
 */
function mwp_wpvivid_output_ajaxerror(action, textStatus, errorThrown){
    action = 'trying to establish communication with your server';
    var error_msg = "wpvivid_request: "+ textStatus + "(" + errorThrown + "): an error occurred when " + action + ". " +
        "This error may be request not reaching or server not responding. Please try again later.";
    return error_msg;
}

function mwp_wpvivid_ajax_data_transfer(data_type){
    var json = {};
    jQuery('input:checkbox[option='+data_type+']').each(function() {
        var value = '0';
        var key = jQuery(this).prop('name');
        if(jQuery(this).prop('checked')) {
            value = '1';
        }
        else {
            value = '0';
        }
        json[key]=value;
    });
    jQuery('input:radio[option='+data_type+']').each(function() {
        if(jQuery(this).prop('checked'))
        {
            var key = jQuery(this).prop('name');
            var value = jQuery(this).prop('value');
            json[key]=value;
        }
    });
    jQuery('input:text[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    jQuery('textarea[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    jQuery('input:password[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    jQuery('select[option='+data_type+']').each(function(){
        var obj = {};
        var key = jQuery(this).prop('name');
        var value = jQuery(this).val();
        json[key]=value;
    });
    return JSON.stringify(json);
}