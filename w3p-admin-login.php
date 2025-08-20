<?php
/**
 * Plugin Name: W3P Admin Login
 * Plugin URI: https://getbutterfly.com/wordpress-plugins/w3p-admin-login/
 * Description: Change /wp-admin/ login to whatever you want (e.g. example.com/my-login). Go under Settings and then click on "Permalinks" and change your URL under "W3P Admin Login".
 * Version: 1.1.0
 * Requires PHP: 7.2
 * Requires CP: 2.0
 * Author: Ciprian Popescu
 * Author URI: https://getbutterfly.com/
 * GitHub Plugin URI: wolffe/w3p-admin-login
 * Primary Branch: master
 * License: GNU General Public License v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Copyright 2024-2025  Ciprian Popescu (email: getbutterfly@gmail.com)
 * Copyright 2023  Saad lqbal (email: saad@objects.ws)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * Acknowledgements to Ella van Durpe (https://wordpress.org/plugins/rename-wp-login/), some of whose code was used
 * in the development of this plug-in. This plugin (https://wordpress.org/plugins/rename-wp-login/) don't have any copyright policy.
 */

defined( 'ABSPATH' ) or die();

require 'includes/updater.php';

if ( ! class_exists( 'W3P_Admin_Login' ) ) {
    class W3P_Admin_Login {
        private $wp_login_php;

        private function basename() {
            return plugin_basename( __FILE__ );
        }

        private function path() {
            return trailingslashit( __DIR__ );
        }

        private function use_trailing_slashes() {
            return '/' === substr( get_option( 'permalink_structure' ), -1, 1 );
        }

        private function user_trailingslashit( $text ) {
            return $this->use_trailing_slashes() ? trailingslashit( $text ) : untrailingslashit( $text );
        }

        private function wp_template_loader() {
            global $pagenow;

            $pagenow = 'index.php';

            if ( ! defined( 'WP_USE_THEMES' ) ) {
                define( 'WP_USE_THEMES', true );
            }

            wp();

            if ( $_SERVER['REQUEST_URI'] === $this->user_trailingslashit( str_repeat( '-/', 10 ) ) ) {
                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/wp-login-php/' );
            }

            require_once ABSPATH . WPINC . '/template-loader.php';

            die;
        }

        private function new_login_slug() {
            if (
                ( $slug = get_option( 'rwl_page' ) ) || (
                    is_multisite() &&
                    is_plugin_active_for_network( $this->basename() ) &&
                    ( $slug = get_site_option( 'rwl_page', 'login' ) )
                ) ||
                ( $slug = 'login' )
            ) {
                return $slug;
            }
        }

        public function new_login_url( $scheme = null ) {
            if ( get_option( 'permalink_structure' ) ) {
                return $this->user_trailingslashit( home_url( '/', $scheme ) . $this->new_login_slug() );
            } else {
                return home_url( '/', $scheme ) . '?' . $this->new_login_slug();
            }
        }

        public function __construct() {
            global $wp_version;

            register_activation_hook( $this->basename(), [ $this, 'activate' ] );
            register_uninstall_hook( $this->basename(), [ 'W3P_Admin_Login', 'uninstall' ] );

            add_action( 'admin_init', [ $this, 'admin_init' ] );
            add_action( 'admin_notices', [ $this, 'admin_notices' ] );
            add_action( 'network_admin_notices', [ $this, 'admin_notices' ] );

            if ( is_multisite() && ! function_exists( 'is_plugin_active_for_network' ) ) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }

            add_filter( 'plugin_action_links_' . $this->basename(), [ $this, 'plugin_action_links' ] );

            if ( is_multisite() && is_plugin_active_for_network( $this->basename() ) ) {
                add_filter( 'network_admin_plugin_action_links_' . $this->basename(), [ $this, 'plugin_action_links' ] );

                add_action( 'wpmu_options', [ $this, 'wpmu_options' ] );
                add_action( 'update_wpmu_options', [ $this, 'update_wpmu_options' ] );
            }

            add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 1 );
            add_action( 'wp_loaded', [ $this, 'wp_loaded' ] );

            add_filter( 'site_url', [ $this, 'site_url' ], 10, 4 );
            add_filter( 'network_site_url', [ $this, 'network_site_url' ], 10, 3 );
            add_filter( 'wp_redirect', [ $this, 'wp_redirect' ], 10, 2 );

            add_filter( 'site_option_welcome_email', [ $this, 'welcome_email' ] );

            remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
        }

        public function activate() {
            add_option( 'rwl_redirect', '1' );
            delete_option( 'rwl_admin' );
        }

        public static function uninstall() {
            global $wpdb;

            if ( is_multisite() ) {
                $blogs = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );

                if ( $blogs ) {
                    foreach ( $blogs as $blog ) {
                        switch_to_blog( $blog );
                        delete_option( 'rwl_page' );
                    }

                    restore_current_blog();
                }

                delete_site_option( 'rwl_page' );
            } else {
                delete_option( 'rwl_page' );
            }
        }

        public function wpmu_options() {
            echo '<h3>' . __( 'W3P Admin Login', 'w3p-admin-login' ) . '</h3>
            <p>' . __( 'This option allows you to set a networkwide default, which can be overridden by individual sites. Simply go to to the site’s permalink settings to change the url.', 'w3p-admin-login' ) . '</p>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">' . __( 'Networkwide default', 'w3p-admin-login' ) . '</th>
                    <td><input id="rwl-page-input" type="text" name="rwl_page" value="' . get_site_option( 'rwl_page', 'login' ) . '"></td>
                </tr>
            </table>';
        }

        public function update_wpmu_options() {
            if (
                ( $rwl_page = sanitize_title_with_dashes( $_POST['rwl_page'] ) ) &&
                strpos( $rwl_page, 'wp-login' ) === false &&
                ! in_array( $rwl_page, $this->forbidden_slugs() )
            ) {
                update_site_option( 'rwl_page', $rwl_page );
            }
        }

        public function admin_init() {
            global $pagenow;

            add_settings_section(
                'w3p-admin-login-section',
                __( 'W3P Admin Login', 'w3p-admin-login' ),
                [ $this, 'rwl_section_desc' ],
                'permalink'
            );

            add_settings_field(
                'rwl-page',
                '<label for="rwl-page">' . __( 'Login URL', 'w3p-admin-login' ) . '</label>',
                [ $this, 'rwl_page_input' ],
                'permalink',
                'w3p-admin-login-section'
            );

            // Add redirect field
            add_settings_field(
                'rwl_redirect_field',
                __( 'Redirect URL', 'w3p-admin-login' ),
                [ $this, 'rwl_redirect_func' ],
                'permalink',
                'w3p-admin-login-section'
            );

            register_setting( 'permalink', 'rwl_page_input' );
            register_setting( 'permalink', 'rwl_redirect_field' );

            if ( current_user_can( 'manage_options' ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'update-permalink' ) ) {
                if ( isset( $_POST['permalink_structure'] ) && isset( $_POST['rwl_redirect_field'] ) ) {
                    $short_domain = sanitize_title_with_dashes( wp_unslash( $_POST['rwl_redirect_field'] ) );
                    update_option( 'rwl_redirect_field', $short_domain );
                }

                if ( isset( $_POST['permalink_structure'] ) && isset( $_POST['rwl_page'] ) ) {
                    if (
                        ( $rwl_page = sanitize_title_with_dashes( $_POST['rwl_page'] ) ) &&
                        strpos( $rwl_page, 'wp-login' ) === false &&
                        ! in_array( $rwl_page, $this->forbidden_slugs() )
                    ) {
                        if ( is_multisite() && $rwl_page === get_site_option( 'rwl_page', 'login' ) ) {
                            delete_option( 'rwl_page' );
                        } else {
                            update_option( 'rwl_page', $rwl_page );
                        }
                    }
                }

                if ( get_option( 'rwl_redirect' ) ) {
                    delete_option( 'rwl_redirect' );

                    if ( is_multisite() && is_super_admin() && is_plugin_active_for_network( $this->basename() ) ) {
                        $redirect = network_admin_url( 'settings.php#rwl-page-input' );
                    } else {
                        $redirect = admin_url( 'options-permalink.php#rwl-page-input' );
                    }

                    wp_safe_redirect( $redirect );

                    die;
                }
            }
        }

        public function rwl_section_desc() {
            if ( is_multisite() && is_super_admin() && is_plugin_active_for_network( $this->basename() ) ) {
                echo '<p>' . sprintf( __( 'To set a networkwide default, go to %s.', 'w3p-admin-login' ), '<a href="' . network_admin_url( 'settings.php#rwl-page-input' ) . '">' . __( 'Network Settings', 'w3p-admin-login' ) . '</a>' ) . '</p>';
            }
        }

        public function rwl_redirect_func() {
            $value = get_option( 'rwl_redirect_field' );
            echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '</code> <input type="text" value="' . esc_attr( $value ) . '" name="rwl_redirect_field" id="rwl_redirect_field" class="regular-text" /> <code>/</code>';
            echo '<p class="description"><strong>' . __( 'If you leave the above field empty the plugin will add a redirect to the website homepage.', 'w3p-admin-login' ) . '</strong></p>';
        }

        public function rwl_page_input() {
            if ( get_option( 'permalink_structure' ) ) {
                echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '</code> <input id="rwl-page-input" type="text" name="rwl_page" value="' . $this->new_login_slug() . '">' . ( $this->use_trailing_slashes() ? ' <code>/</code>' : '' );
            } else {
                echo '<code>' . esc_url( trailingslashit( home_url() ) ) . '?</code> <input id="rwl-page-input" type="text" name="rwl_page" value="' . $this->new_login_slug() . '">';
            }
        }

        public function admin_notices() {
            global $pagenow;

            if ( ! is_network_admin() && $pagenow === 'options-permalink.php' && isset( $_GET['settings-updated'] ) ) {
                echo '<div class="updated"><p>' . sprintf( __( 'Your login page is now here: %s. Bookmark this page!', 'w3p-admin-login' ), '<strong><a href="' . $this->new_login_url() . '">' . $this->new_login_url() . '</a></strong>' ) . '</p></div>';
            }
        }

        public function plugin_action_links( $links ) {
            if ( is_network_admin() && is_plugin_active_for_network( $this->basename() ) ) {
                array_unshift( $links, '<a href="' . network_admin_url( 'settings.php#rwl-page-input' ) . '">' . __( 'Settings', 'w3p-admin-login' ) . '</a>' );
            } elseif ( ! is_network_admin() ) {
                array_unshift( $links, '<a href="' . admin_url( 'options-permalink.php#rwl-page-input' ) . '">' . __( 'Settings', 'w3p-admin-login' ) . '</a>' );
            }

            return $links;
        }

        public function plugins_loaded() {
            global $pagenow;

            if ( ! is_multisite()
                && ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-signup' ) !== false
                    || strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-activate' ) !== false ) ) {

                wp_die( __( 'This feature is not enabled.', 'w3p-admin-login' ) );

            }

            $request = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

            if ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-login.php' ) !== false
                || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-login', 'relative' ) ) )
                && ! is_admin() ) {

                $this->wp_login_php = true;

                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );

                $pagenow = 'index.php';

            } elseif ( ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $this->new_login_slug(), 'relative' ) )
                    || ( ! get_option( 'permalink_structure' )
                            && isset( $_GET[ $this->new_login_slug() ] )
                            && empty( $_GET[ $this->new_login_slug() ] ) ) ) {

                $pagenow = 'wp-login.php';

            } elseif ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-register.php' ) !== false
                        || ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === site_url( 'wp-register', 'relative' ) ) )
                    && ! is_admin() ) {

                $this->wp_login_php = true;

                $_SERVER['REQUEST_URI'] = $this->user_trailingslashit( '/' . str_repeat( '-/', 10 ) );

                $pagenow = 'index.php';
            }
        }

        public function wp_loaded() {
            global $pagenow;

            if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) ) {

                if ( get_option( 'rwl_redirect_field' ) == 'false' ) {
                    wp_safe_redirect( '/' );
                } else {
                    wp_safe_redirect( '/' . get_option( 'rwl_redirect_field' ) );
                }

                die();
            }

            $request = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

            if (
                $pagenow === 'wp-login.php' &&
                $request['path'] !== $this->user_trailingslashit( $request['path'] ) &&
                get_option( 'permalink_structure' )
            ) {
                wp_safe_redirect( $this->user_trailingslashit( $this->new_login_url() ) . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '' ) );
                die;
            } elseif ( $this->wp_login_php ) {
                if (
                    ( $referer = wp_get_referer() ) &&
                    strpos( $referer, 'wp-activate.php' ) !== false &&
                    ( $referer = parse_url( $referer ) ) &&
                    ! empty( $referer['query'] )
                ) {
                    parse_str( $referer['query'], $referer );

                    if (
                        ! empty( $referer['key'] ) &&
                        ( $result = wpmu_activate_signup( $referer['key'] ) ) &&
                        is_wp_error( $result ) && (
                            $result->get_error_code() === 'already_active' ||
                            $result->get_error_code() === 'blog_taken'
                    ) ) {
                        wp_safe_redirect( $this->new_login_url() . ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . wp_unslash( $_SERVER['QUERY_STRING'] ) : '' ) );
                        die;
                    }
                }

                $this->wp_template_loader();
            } elseif ( $pagenow === 'wp-login.php' ) {
                global $error, $interim_login, $action, $user_login;

                @require_once ABSPATH . 'wp-login.php';

                die;
            }
        }

        public function site_url( $url, $path, $scheme, $blog_id ) {
            return $this->filter_wp_login_php( $url, $scheme );
        }

        public function network_site_url( $url, $path, $scheme ) {
            return $this->filter_wp_login_php( $url, $scheme );
        }

        public function wp_redirect( $location, $status ) {
            return $this->filter_wp_login_php( $location );
        }

        public function filter_wp_login_php( $url, $scheme = null ) {
            $current_url = isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';
            if ( is_int( strpos( $url, 'wp-login.php' ) ) || is_int( strpos( $url, 'wp-login' ) ) ) {
                if ( is_ssl() ) {
                    $scheme = 'https';
                }
                $args = explode( '?', $url );
                if ( isset( $args[1] ) ) {
                    wp_parse_str( $args[1], $args );
                    $url = add_query_arg( $args, $this->new_login_url( $scheme ) );
                } else {
                    $url = $this->new_login_url( $scheme );
                }
            }

            if ( ! is_int( strpos( $current_url, 'wp-admin' ) ) ) {
                return $url;
            }

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                return $url;
            }

            if ( ! function_exists( 'is_user_logged_in' ) ) {
                return $url;
            }

            if ( ! is_user_logged_in() ) {
                $redirect_url = get_option( 'rwl_redirect_field' );
                if ( is_null( $redirect_url ) ) {
                    $redirect_url = '';
                }
                return '/' . $redirect_url;
            }

            return $url;
        }

        public function welcome_email( $value ) {
            return $value = str_replace( 'wp-login.php', trailingslashit( get_site_option( 'rwl_page', 'login' ) ), $value );
        }

        public function forbidden_slugs() {
            $wp = new WP();
            return array_merge( $wp->public_query_vars, $wp->private_query_vars );
        }
    }

    new W3P_Admin_Login();
}
