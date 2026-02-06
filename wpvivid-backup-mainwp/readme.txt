=== WPvivid Backup for MainWP ===
Contributors: wpvivid
Tags: WPvivid backup, MainWP extension, backup, auto backup, cloud backup 
Requires at least: 4.5
Tested up to: 6.9
Requires PHP: 5.3
Stable tag: 0.9.40
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

Set up and control WPvivid Backup Free and Pro for all child sites directly from your MainWP Dashboard.

== Description ==
WPvivid Backup for MainWP enables you to create and download backups of a specific child site, set backup schedules, set [WPvivid Backup Plugin](https://wordpress.org/plugins/wpvivid-backuprestore/) settings for all of your child sites directly from your MainWP dashboard.

== WPvivid Backup Pro Supported ==
The extension now also supports [WPvivid Backup Pro](https://wpvivid.com/wpvivid-backup-for-mainwp):

* Install, claim and update WPvivid Backup Pro for child site in bulk.
* Set up remote storage for child sites in bulk.
* Install [WPvivid Image Optimization Plugin](https://wordpress.org/plugins/wpvivid-imgoptim/) in child sites in bulk.

== Features ==
* Create backups for a specific child site
* Download backups of a specific child site to local
* Set backup schedules for child sites
* Set WPvivid Backup Free and Pro settings for child sites
* Set up remote storage for child sites with WPvivid Backup Pro
* Install, claim and update WPvivid Backup Pro for child sites
* Set up incremental backups for child sites
* Set up white label for child sites
* Select and hide WPvivid modules on child sites

== Minimum Requirements ==
* MainWP Dashboard 4.0.3 or later
* MainWP Child Requirement: 4.0.2 or later

== Installation and Setup ==
See the [get started guide](https://wpvivid.com/wpvivid-backup-for-mainwp).
= Note: =
1. Install WPVivid Backup for MainWP on MainWP site ONLY.
2. Install WPVivid Backup Plugin (not MainWP) on each child site.

== Screenshots ==
1. Create backups for a specific child site
2. Set backup schedules for all child sites
3. Set WPvivid Backup Plugin settings for all child sites

== Changelog ==
= 0.9.40 =
- Added backup settings including Auto Delete Logs, Large Database/Uploads modes, and Backup Performance presets.
- Optimized display of backup results in website list on the Dashboard.
- Successfully tested with WordPress 6.9.
= 0.9.39 =
- Added compatibility with MainWP 5.4.
- Optimized the plugin code.
- Successfully tested with WordPress 6.7.2.
= 0.9.38 =
- Added an option to globally configure backup location for rollback backups.
- Added an option to export/import settings of the extension.
- Fixed: Redirection to a child site would fail when white label slug of the site contained capital letters.
- Fixed: Activating WPvivid license would fail in some cases.
= 0.9.37 =
- Added an option to install staging addon for child sites.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.36 =
- Added an option of globally configuring settings of Rollback function.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.5.4.
= 0.9.35 =
- Reviewed and optimized the plugin code.
- Added an option to globally hide Rollback and Staging pages.
- Successfully tested with WordPress 6.4.3.
= 0.9.34 =
- Fixed an XSS vulnerability in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.4.3.
= 0.9.33 =
- Fixed: Could not access settings in some environments.
- Fixed some vulnerabilities in the plugin code.
- Fixed some bugs on the UI and improved the UI.
- Fixed some bugs in the plugin code.
- Successfully tested with WordPress 6.4.3.
= 0.9.32 =
- Added WebDav and NextCloud cloud options.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.4.1.
= 0.9.31 =
- Fixed: License activation would fail when phpinfo is disabled on web hosting server.
- Fixed some PHP warnings.
- Fixed: The last backup time on MainWP dashboard was not synced in some cases.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.30 =
- Added support for MainWP plugin's 'Required Plugins Check' function.
- Added an option of hiding Roles & Capabilities tab on child sites.
- Fixed: WordPress was outputting warnings based on the plugin's use of the script localization functionality.
- Fixed some PHP warnings that would appear on the plugin UI in some environments.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.29 =
- Added a region field for s3 compatible storage.
- Fixed: Installing addons failed in some cases.
- Fixed some UI bugs.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.28 =
- Added a global setting for configuring backup email report for child sites.
- Updated the last backup time on MainWP child sites list to local time.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.27 =
- Fixed some UI bugs.
- Optimized the plugin code.
= 0.9.26 =
- Added support for MainWP client reports.
- Integrated all settings of WPvivid plugins to the extension.
- Integrated all remote storage providers of WPvivid plugins to the extension.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.25 =
- Added WP Nonce verification to the Go to WP Admin request for additional security.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 6.0.
= 0.9.24 =
- Fixed: The option 'Keep storing backups in localhost after uploading them to cloud storage' was enabled by default.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.23 =
- Added a global setting for keeping backups in localhost after uploading them to cloud storage.
- Successfully tested with WordPress 5.9.3.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.22 =
- Fixed: Site overview not showing last backup.
- Fixed: Could not update incremental backup schedules.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.21 =
- Successfully tested with WordPress 5.8.
- Fixed: Could not re-add child sites that were migrated from other child sites to MainWP.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.20 =
- Fixed: Installing & Claiming WPvivid Backup Pro to child sites failed.
- Fixed a UI display bug.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.19 =
- Added an option to install WPvivid Image Optimization Plugin in child sites.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.18 =
- Fixed: Could not update WPvivid Backup Pro when multiple child sites were selected.
= 0.9.17 =
- Fixed: Failed to update WPvivid Backup Pro for child sites.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.16 =
- Fixed: WPvivid Pro could not be claimed.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.15 =
- Remote storage credentials synced to child sites are encrypted now.
- Fixed some bugs in the plugin code.
- Successfully tested with WordPress 5.7.
= 0.9.14 =
- Added an option to set backup retention for cloud storage in the settings.
- Added a column on WPvivid Backup dashboard to show whether backup schedule and cloud storage are configured for each child site.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
- Successfully tested with WordPress 5.6.1.
= 0.9.13 =
- Added support for WPvivid Backup Pro 2.0.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.12 =
- Fixed: WPvivid Pro login status did not remain.
- Fixed some bugs in the plugin code.
- Optimized the plugin code.
= 0.9.11 =
- Added support for MainWP 4.1.
- Added an option to set up incremental backups for child sites.
- Added an option to select and hide WPvivid modules on child sites.
- Added an option to set up white label for child sites.
- Fixed: Last backup date was not synced when backups were taken from child sites.
- Fixed some bugs in the plugin code and optimize the plugin code.
- Successfully tested with WordPress 5.6.
= 0.9.10 =
- Added the options to run scheduled backups in UTC or local time.
- Fixed: 'Backup Now' failed in some cases.
- Fixed: Claim statuses of some sites were not displayed properly.
- Fixed some bugs in the plugin code.
= 0.9.9 =
- Added an option to select all child sites on remote storage tab.
- Added options to choose if you want to set a new schedule as the only or an additional active schedule on the child sites.
- Fixed: 'Backup Now' on MainWP dashboard often failed in some cases.
- Fixed: Claims kept losing on some child sites.
= 0.9.8 =
- Fixed: Updating WPvivid Backup Pro activation error on the child sites where the pro version was already activated or installed.
- Fixed: Pagination was not working in some cases when selecting child sites to sync settings.
= 0.9.7 =
- Added a tab where you can log in to your WPvivid Backup Pro account to install, claim and update WPvivid Backup Pro for child site in bulk.
- Fixed: Pagination was not working in some cases when selecting child sites to sync settings.
- Fixed some bugs in the plugin code.
= 0.9.6 =
- Added support for WPvivid Backup Pro.
- Optimized the plugin code.
= 0.9.5 =
- Fixed a fatal error occurred during backup process in some cases.
- Successfully tested with WordPress 5.3.
= 0.9.4 =
- Fixed an error that appeared when clicking Remote Storage for a child site from MainWP Dashboard.
- Fixed some bugs in the plugin code.
= 0.9.3 =
- Fixed: Could not activate WPvivid Backup for MainWP on the site where WPvivid Backup Plugin had been installed and activated.
= 0.9.2 =
- Added support for remote storage connection.
= 0.9.1 =
- Initial release of the plugin. Now you see it.