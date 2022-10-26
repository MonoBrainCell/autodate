<?php
if( ! defined('WP_UNINSTALL_PLUGIN') ) {
	exit;
}

$table_name_postfix="autodate_data";

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

$table_name=$wpdb->get_blog_prefix().$table_name_postfix;

$sql="DROP TABLE IF EXISTS `{$table_name}`";
$wpdb->query($sql);