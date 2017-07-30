<?php
/*
 * Plugin Name: MemberPress Multisite Roles
 * Plugin URI: https://github.com/berlotti/MemberpressMultisiteRoles
 * Description: Set what roles MemberPress users get on your multi sites
 * Version: 0.1
 * Author: Bastiaan Grutters
 * Author URI: http://www.bastiaangrutters.nl
 */

// Not directly accessible
if (!defined('WPINC')) {
	exit;
}

spl_autoload_register('memberPressMultiSiteRolesAutoload');

function memberPressMultiSiteRolesAutoload(string $className) {
	if (strpos($className, 'MemberPressMultiSiteRoles') === false) {
		return;
	}

	$parts = explode('\\', $className);
	require_once(trailingslashit(dirname(__FILE__)) . 'classes/' . implode('/', $parts) . '.php');
}

new MemberPressMultiSiteRoles\Main();