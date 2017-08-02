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
		// Fire on same events as MemberPress User Roles, just later
		add_action('mepr-txn-store', [$this, 'updateRoles'], 99);
		add_action('mepr-transaction-expired', [$this, 'updateRoles'], 99);

		if (is_admin()) {
			add_action('admin_menu', [$this, 'addOptionsMenu']);
		}
	}

	public function updateRoles(\MeprTransaction $transaction) {
		$user = get_user_by('id', $transaction->user_id);
		if ($user === false) {
			return;
		}

		if (!function_exists('get_sites')) {
			return;
		}

		$rolesMapping = get_option(Options::ROLES_MAPPING_KEY);
		$product = $transaction->product();
		$isActive = $transaction->is_active();

		if (!isset($rolesMapping[$product->ID])) {
			return;
		}

		$productMapping = $rolesMapping[$product->ID];

		/** @var \WP_Site $blog */
		foreach (\get_sites() as $blog) {
			if ($blog->id === 1) {
				continue;
			}

			if ($isActive) {
				foreach ($user->roles as $role) {
					if (!isset($productMapping[$role][$blog->id])) {
						continue;
					}

					$this->addRoleToBlog($user, $blog->id, $productMapping[$role][$blog->id]);
				}
				continue;
			}

			foreach ($productMapping as $role => $blogIds) {
				if (!isset($blogIds[$blog->id])) {
					continue;
				}
				$this->removeRoleFromBlog($user, $blog->id, $blogIds[$blog->id]);
			}
		}
	}

	protected function addRoleToBlog(\WP_User $user, int $blogId, string $role) {
		$key = vsprintf('wp_%d_capabilities', [$blogId]);
		$roles = get_user_meta($user->ID, $key, true);
		if (in_array($role, $roles)) {
			return;
		}
		$roles[] = $role;
		$roles[$role] = true;
		update_user_meta($user->ID, $key, $roles);
	}

	protected function removeRoleFromBlog(\WP_User $user, int $blogId, string $role) {
		$key = vsprintf('wp_%d_capabilities', [$blogId]);
		$roles = get_user_meta($user->ID, $key, true);

		if ($roles === '') {
			return;
		}

		$index = array_search($role, $roles);
		if ($index === false) {
			return;
		}
		unset($roles[$index]);
		unset($roles[$role]);
		if (count($roles) === 0) {
			delete_user_meta($user->ID, $key);
		} else {
			update_user_meta($user->ID, $key, $roles);
		}
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