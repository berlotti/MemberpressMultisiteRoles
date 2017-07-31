<?php

namespace MemberPressMultiSiteRoles;


class Options {
	const ROLES_MAPPING_KEY = 'memberpress_multisite_roles_mapping';

	const NOTICE_TYPE_ERROR = 'notice-error';
	const NOTICE_TYPE_WARNING = 'notice-warning';
	const NOTICE_TYPE_SUCCESS = 'notice-success';
	const NOTICE_TYPE_INFO = 'notice-info';


	public static function show() {
		self::handleOptionsSave();

		$rolesMapping = get_option(self::ROLES_MAPPING_KEY);

		print('<div class="wrap">');
		print('<h1>MemberPress MultiSite Roles</h1>');

		if (!function_exists('get_sites')) {
			print(self::getNoticeHtml('No multi sites detected, options disabled.', self::NOTICE_TYPE_ERROR));
			print('</div>');
			return;
		}

		vprintf('<form method="post" action="%s">', [menu_page_url(Main::MENU_SLUG, false)]);

		/** @var \WP_Site[] $multiSites */
		$multiSites = \get_sites();

		foreach (Main::getMemberPressProductRoles() as $memberPressProductId => $roles) {
			$memberPressProduct = get_post($memberPressProductId);
			vprintf('<h3>%s</h3>', [$memberPressProduct->post_title]);

			foreach ($roles as $role) {
				vprintf('<h4>Configure role `%s` from the main site</h4>', [$role]);

				foreach ($multiSites as $multiSite) {
					if ($multiSite->id === 1) {
						continue;
					}

					$options = [];
					foreach (Main::getRolesForMultiSite($multiSite->id) as $subSiteRole => $roleData) {
						$options[] = vsprintf(
							'<option value="%1$s"%2$s>%1$s</option>',
							[
								$subSiteRole,
								(isset($rolesMapping[$memberPressProductId][$role][$multiSite->id]) && $rolesMapping[$memberPressProductId][$role][$multiSite->id] === $subSiteRole) ?
									' selected' :
									''
							]
						);
					}

					vprintf(
						'<label>Select role from `%s` <select name="%s[%d][%s][%d]"><option value="">No role</option>%s</select></label><br/>',
						[
							$multiSite->blogname,
							self::ROLES_MAPPING_KEY,
							$memberPressProductId,
							$role,
							$multiSite->id,
							implode('', $options),
						]
					);
				}
			}
		}

		submit_button();
		print('</form>');
		print('</div>');
	}

	protected static function handleOptionsSave() {
		if (!isset($_POST, $_POST[self::ROLES_MAPPING_KEY])) {
			return;
		}

		if (!is_array($_POST[self::ROLES_MAPPING_KEY])) {
			print(self::getNoticeHtml('No valid role mapping!', self::NOTICE_TYPE_ERROR));
			return;
		}

		$mapping = $_POST[self::ROLES_MAPPING_KEY];

		foreach ($mapping as $memberPressProductId => $roles) {
			if (!is_array($roles)) {
				print(self::getNoticeHtml(vsprintf('Invalid roles for product `%s`, entry removed.', [$memberPressProductId]), self::NOTICE_TYPE_WARNING));
				unset($mapping[$memberPressProductId]);
				continue;
			}

			foreach ($roles as $role => $multiSites) {
				if (!is_array($multiSites)) {
					print(self::getNoticeHtml(vsprintf('Invalid multi sites for product `%s` with role `%s`, entry removed.', [$memberPressProductId, $role]), self::NOTICE_TYPE_WARNING));
					unset($mapping[$memberPressProductId][$role]);
					continue;
				}

				foreach ($multiSites as $multiSiteId => $multiSiteRole) {
					if ($multiSiteRole === '' || !is_string($multiSiteRole)) {
						unset($mapping[$memberPressProductId][$role][$multiSiteId]);
						continue;
					}
				}
			}
		}
		update_option(self::ROLES_MAPPING_KEY, $mapping);
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