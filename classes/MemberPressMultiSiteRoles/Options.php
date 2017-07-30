<?php

namespace MemberPressMultiSiteRoles;


class Options {
	const ROLES_MAPPING_KEY = 'memberpress_multisite_roles_mapping';

	const NOTICE_TYPE_ERROR = 'notice-error';
	const NOTICE_TYPE_WARNING = 'notice-warning';
	const NOTICE_TYPE_SUCCESS = 'notice-success';
	const NOTICE_TYPE_INFO = 'notice-info';


	public static function show() {
		$rolesMapping = get_option(self::ROLES_MAPPING_KEY);

		print('<div class="wrap">');
		print('<h1>MemberPress MultiSite Roles</h1>');

		if (!function_exists('get_sites')) {
			print(self::getNoticeHtml('No multi sites detected, options disabled.', self::NOTICE_TYPE_ERROR));
			print('</div>');
			return;
		}

		print('<form method="post" action="options.php">');

		$multiSites = \get_sites();

		foreach (Main::getMemberPressProductRoles() as $memberPressProductId => $roles) {
			$memberPressProduct = get_post($memberPressProductId);
			vprintf('<h3>%s</h3>', [$memberPressProduct->post_title]);

			foreach ($roles as $role) {
				var_dump($role);
				foreach ($multiSites as $multiSite) {
					var_dump($multiSite);
				}
			}
		}

		submit_button();
		print('</form>');
		print('</div>');
	}

	public static function getNoticeHtml(string $message, string $noticeType = self::NOTICE_TYPE_INFO, bool $isDismissable = false): string {
		if (!in_array($noticeType, [self::NOTICE_TYPE_ERROR, self::NOTICE_TYPE_WARNING, self::NOTICE_TYPE_INFO, self::NOTICE_TYPE_SUCCESS], true)) {
			throw new \Exception(vsprintf('Invalid notice type `%s`.', [$noticeType]));
		}

		return vsprintf(
			'<div class="notice %s%s"><p>%s</p></div>',
			[$noticeType, $isDismissable ? ' is-dismissable' : '', $message]
		);
	}
}