<?php

namespace MemberPressMultiSiteRoles;


class Main {
	const MEMBER_PRESS_POST_TYPE = 'memberpressproduct';
	const MEMBER_PRESS_ROLES_META_KEY = '_mepruserroles_roles';
	const MENU_SLUG = 'memberpress-multisite-roles';


	public function __construct() {
		add_action('init', [$this, 'initialize']);
	}

	public function initialize() {
		add_action('mepr-signup', [$this, 'signup']);
		add_action('mepr-transaction-expired', [$this, 'checkValidMemberRoles']);

		if (is_admin()) {
			add_action('admin_menu', [$this, 'addOptionsMenu']);
		}
	}

	public function signup(\MeprTransaction $transaction) {
		// TODO: set the correct role for the multisites for this membership
		if (!$transaction->is_active()) {
			return;
		}

		$product = $transaction->product();
		// TODO: Match product with role mapping
		$memberShipPost = $product->get_attrs();
		$roles = get_post_meta($memberShipPost['ID'], self::MEMBER_PRESS_ROLES_META_KEY, true);

	}

	public function expired(\MeprTransaction $transaction, string $subscriptionStatus) {
		// TODO: set the correct role for the multisites for this membership
	}

	public function addOptionsMenu() {
		add_options_page(
			'MemberPress MultiSite Roles Options',
			'MemberPress Roles',
			'manage_options',
			self::MENU_SLUG,
			[Options::class, 'show']
		);
	}

	public static function getMemberPressProductRoles(): array {
		$memberPressPosts = get_posts([
			'post_type' => self::MEMBER_PRESS_POST_TYPE,
			'posts_per_page' => -1,
		]);

		$productsAndRoles = [];
		foreach ($memberPressPosts as $memberPressPost) {
			$productsAndRoles[$memberPressPost->ID] = get_post_meta($memberPressPost->ID, self::MEMBER_PRESS_ROLES_META_KEY, true);
		}

		return $productsAndRoles;
	}

	public static function getRolesForMultiSite(int $multiSiteId): array {
		$currentSiteId = get_current_blog_id();

		if ($currentSiteId === $multiSiteId) {
			return get_editable_roles();
		}

		switch_to_blog($multiSiteId);
		$roles = get_editable_roles();
		switch_to_blog($currentSiteId);

		return $roles;
	}
}