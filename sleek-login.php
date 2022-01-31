<?php
namespace Sleek\Login;

########################################
# Checks whether we're on the login page
# https://wordpress.stackexchange.com/questions/28095/how-can-i-tell-if-im-on-a-login-page
function is_login_page () {
	return $GLOBALS['pagenow'] == 'wp-login.php';
}

#######################
# Give subscribers less
add_action('init', function () {
	# Prevent subscribers from viewing admin
	# https://wordpress.stackexchange.com/questions/23007/how-do-i-remove-dashboard-access-from-specific-user-roles
	if (is_admin() and current_user_can('subscriber') and !defined('DOING_AJAX')) {
		wp_redirect(home_url());
	}

	# Hide admin bar from subscribers
	if (current_user_can('subscriber')) {
		add_filter('show_admin_bar', '__return_false');
	}
});

###############################################
# Redirect subscribers to home page after login
# https://codex.wordpress.org/Plugin_API/Filter_Reference/login_redirect
add_filter('login_redirect', function ($to, $request, $user) {
	if (isset($user->roles) and is_array($user->roles) and in_array('subscriber', $user->roles)) {
		return home_url();
	}

	return $to;
}, 10, 3);

##################################
# Require login on the entire site
add_action('after_setup_theme', function () {
	if (get_theme_support('sleek/login/require_login')) {
		add_action('init', function () {
			if (!defined('WP_CLI') and !is_admin() and !is_login_page() and !is_user_logged_in()) {
				auth_redirect();
			}
		});
	}
});

####################################
# Include theme CSS/JS on login page
add_action('after_setup_theme', function () {
	# NOTE: Don't do this on the recover password page because it has very special CSS/JS
	if (get_theme_support('sleek/login/styling') and !(isset($_GET['action']) and $_GET['action'] === 'rp')) {
		# Link logo to home page
		add_filter('login_headerurl', function () {
			return home_url();
		});

		# Change "Powered by WordPress" to site name
		add_filter('login_headertext', function () {
			return get_bloginfo('name');
		});

		# Remove default login style
		# https://wordpress.stackexchange.com/questions/113501/avoid-to-load-default-wp-styles-in-login-screen
		add_action('login_init', function() {
			wp_deregister_style('login');
		});

		# Add our styles
		add_action('login_enqueue_scripts', function () {
			if (file_exists(get_stylesheet_directory() . '/dist/app.css')) {
				wp_enqueue_style('sleek', get_stylesheet_directory_uri() . '/dist/app.css', [], filemtime(get_stylesheet_directory() . '/dist/app.css'));
			}
		});

		# Style the login page
		add_action('login_head', function () {
			?>
			<style>
				body.login {
					display: flex;
					align-items: center;
					justify-content: center;
					flex-direction: column;
					min-height: 100vh;
				}

				body.login #login {
					background: var(--sleek-login-bg, white);

					width: 100%;
					max-width: 100%;
					min-height: 100vh;

					margin: 0;
					padding: var(--sleek-login-padding, var(--spacing-large));
				}

				@media (min-width: 44rem) {
					body.login #login {
						width: var(--sleek-login-width, 40rem);
						min-height: 0;
						margin: 2rem 0;
					}
				}

				/* Give this div the same margin as other fields in the form (which are paragraphs) */
				body.login #login #login_error,
				body.login #login .user-pass-wrap {
					margin-bottom: var(--paragraph-margin, var(--spacing-medium, 1rem));
				}

				body.login #login .wp-pwd {
					position: relative;
				}

				/* Make room for icon */
				body.login #login .wp-pwd input {
					padding-right: calc(var(--form-field-padding-horizontal, 1.25rem) * 2 + var(--sleek-login-show-password-size, 1.5rem))
				}

				/* Show/hide password icon */
				body.login #login .wp-pwd button.wp-hide-pw {
					all: unset;

					position: absolute;
					right: var(--form-field-padding-horizontal, 1.25rem);
					top: 50%;
					transform: translateY(-50%);
					cursor: pointer;
				}

				/* Remove potential before/after styling */
				body.login #login .wp-pwd button.wp-hide-pw::before,
				body.login #login .wp-pwd button.wp-hide-pw::after {
					all: unset;
				}

				/* Icon */
				body.login #login .wp-pwd button.wp-hide-pw > span {
					color: var(--sleek-login-show-password-color, var(--link-color, blue));
					font-size: var(--sleek-login-show-password-size, 1.5rem);
				}

				body.login #login .wp-pwd button.wp-hide-pw > span::before {
					content: var(--sleek-login-show-password-icon, "👀");
				}

				body.login #login .wp-pwd button.wp-hide-pw > span.dashicons-hidden::before {
					content: var(--sleek-login-hide-password-icon, "🕶");
				}
			</style>
			<?php
		});
	}
});
