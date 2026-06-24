<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Plugin settings class
 *
 * Class Send_Pulse_Newsletter
 */
class Send_Pulse_Newsletter_Settings {
    /**
     * @var string Error message
     */
    private $error = '';

    /**
     * @var null|Send_Pulse_Newsletter_API Instance SP_API class
     */
    private $api = null;

    /**
     * @var string Plugin page slug
     */
    private $page = 'send_pulse_settings';

    /**
     * Send_Pulse_Newsletter constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    /**
     * Init SendPulse API Class
     */
    protected function init_api() {
        try {
            $this->api = new Send_Pulse_Newsletter_API();

            if ( ! $this->api->is_available() ) {
                $this->error = __( 'SendPulse API is temporarily unavailable. Mailing lists could not be loaded.', 'sendpulse-email-marketing-newsletter' );
                return;
            }

            if ( 'on' == $this->api->get_option( 'is_subscribe_after_register' ) && empty( $this->api->default_book ) ) {
                $this->error = __( 'Select a target mailing list and save your settings', 'sendpulse-email-marketing-newsletter' );
            }

        } catch ( \Throwable $exception ) {
            $this->error = $exception->getMessage();
        }
    }

    /**
     * Display settings.
     */
    function admin_init() {
        $this->init_api();

        foreach ( $this->get_settings_sections() as $section ) {
            $section_id = $section['id'];

            register_setting( $section_id, $section_id, array(
                'sanitize_callback' => array( $this, 'sanitize_settings_fields' )
            ) );

            add_settings_section(
                $section_id,
                $section['title'],
                function () use ( $section ) {
                    echo '<p>' . esc_html( $section['title'] ) . '</p>';
                },
                $this->page
            );

            $fields = $this->get_settings_fields();

            if ( isset( $fields[ $section_id ] ) ) {
                foreach ( $fields[ $section_id ] as $field ) {
                    add_settings_field(
                        $field['name'],
                        $field['label'],
                        array( $this, 'render_field' ),
                        $this->page,
                        $section_id,
                        array_merge( $field, [ 'section' => $section_id ] )
                    );
                }
            }
        }

	    $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if (
            $this->error &&
            isset( $_GET['page'] ) &&
            $this->page == $_GET['page'] &&
            $nonce &&
            wp_verify_nonce( $nonce, 'sendpulse_admin_page' )
        ) {
            add_settings_error( 'general', 'settings_updated', $this->error, 'error' );
        }
    }

    public function sanitize_settings_fields( $input ) {
        $sanitized = array();

        foreach ( $input as $key => $value ) {
            switch ( $key ) {
                case 'client_id':
                case 'client_secret':
                case 'default_book':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
                case 'is_subscribe_after_register':
                    $sanitized[ $key ] = $value === 'on' ? 'on' : '';
                    break;
                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }

        return $sanitized;
    }

	public function render_field( $args ) {
		$option      = get_option( $args['section'] );
		$name        = $args['name'];
		$value       = isset( $option[ $name ] ) ? $option[ $name ] : '';
		$desc        = isset( $args['desc'] ) ? $args['desc'] : '';
		$placeholder = isset( $args['placeholder'] ) ? $args['placeholder'] : '';

		switch ( $args['type'] ) {
			case 'text':
				printf(
					'<input type="text" name="%1$s[%2$s]" id="%2$s" value="%3$s" class="regular-text" placeholder="%4$s" />%5$s',
					esc_attr( $args['section'] ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $placeholder ),
					$this->render_field_description( $name, $desc )
				);
				break;

			case 'checkbox':
				$this->render_checkbox_field( $args, $name, $value, $desc );
				break;

			case 'select':
				echo '<select name="' . esc_attr( $args['section'] ) . '[' . esc_attr( $name ) . ']" id="' . esc_attr( $name ) . '">';
				foreach ( $args['options'] as $key => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $key ),
						selected( $value, $key, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				echo $this->render_field_description( $name, $desc );
				break;
		}
	}

	protected function render_field_description( $name, $desc ) {
		if ( ! $desc ) {
			return '';
		}

		if ( 'client_id' === $name ) {
			return '';
		}

		if ( 'client_secret' === $name ) {
			return sprintf(
				'<div class="sp-newsletter-api-help"><p class="description"><strong>%1$s</strong></p><p class="description"><a href="%2$s">%3$s</a></p><p class="description"><a href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a></p></div>',
				esc_html__( 'Helpful links:', 'sendpulse-email-marketing-newsletter' ),
				esc_url( $this->get_api_credentials_documentation_url() ),
				esc_html__( 'How to find your client ID and secret key', 'sendpulse-email-marketing-newsletter' ),
				esc_url( $this->get_sendpulse_api_settings_url() ),
				esc_html__( 'SendPulse API settings', 'sendpulse-email-marketing-newsletter' )
			);
		}

		if ( 'is_subscribe_after_register' === $name ) {
			return sprintf(
				'<p class="description sp-newsletter-checkbox-setting__help">%1$s</p><p class="description sp-newsletter-checkbox-setting__help"><a href="%2$s">%3$s</a></p>',
				esc_html__( 'Requires WordPress user registration to be enabled.', 'sendpulse-email-marketing-newsletter' ),
				esc_url( $this->get_documentation_tab_url() ),
				esc_html__( 'Learn more', 'sendpulse-email-marketing-newsletter' )
			);
		}

		return '<p class="description">' . esc_html( $desc ) . '</p>';
	}

	protected function render_checkbox_field( $args, $name, $value, $desc ) {
		$checked = checked( $value, 'on', false );

		if ( 'is_subscribe_after_register' === $name ) {
			printf(
				'<div class="sp-newsletter-checkbox-setting"><label class="sp-newsletter-checkbox-setting__label"><input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="on" %3$s /><span>%4$s</span></label>%5$s</div>',
				esc_attr( $args['section'] ),
				esc_attr( $name ),
				wp_kses_post( $checked ),
				esc_html( $desc ),
				$this->render_field_description( $name, $desc )
			);
			return;
		}

		printf(
			'<input type="checkbox" name="%1$s[%2$s]" id="%2$s" value="on" %3$s />%4$s',
			esc_attr( $args['section'] ),
			esc_attr( $name ),
			wp_kses_post( $checked ),
			$this->render_field_description( $name, $desc )
		);
	}

    /**
     * Add submenu in Settings
     */
    function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=sendpulse_form',
            __( 'Settings', 'sendpulse-email-marketing-newsletter' ),
            __( 'Settings', 'sendpulse-email-marketing-newsletter' ),
            'manage_options',
            'send_pulse_settings',
            array( $this, 'plugin_page' )
        );

        // Import submenu page
        add_submenu_page(
            'edit.php?post_type=sendpulse_form',
            __( 'User contact import', 'sendpulse-email-marketing-newsletter' ),
            __( 'User contact import', 'sendpulse-email-marketing-newsletter' ),
            'manage_options',
            'send_pulse_import',
            array( $this, 'import_page' )
        );
    }

    /**
     * @return array Section
     */
    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'sp_api_setting',
                'title' => __( 'API Settings', 'sendpulse-email-marketing-newsletter' )
            ),
            array(
                'id'    => 'sp_import_setting',
                'title' => __( 'Import Defaults', 'sendpulse-email-marketing-newsletter' )
            )
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            'sp_api_setting' => array(
                array(
                    'name'              => 'client_id',
                    'label'             => __( 'Client ID', 'sendpulse-email-marketing-newsletter' ),
                    'desc'              => '',
                    'placeholder'       => __( 'Client ID', 'sendpulse-email-marketing-newsletter' ),
                    'type'              => 'text',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'              => 'client_secret',
                    'label'             => __( 'Client secret key', 'sendpulse-email-marketing-newsletter' ),
                    'desc'              => 'api_credentials_help',
                    'placeholder'       => __( 'Client secret key', 'sendpulse-email-marketing-newsletter' ),
                    'type'              => 'text',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field'
                )

            )
        );

        $settings_fields['sp_api_setting'][] =
			array(
				'name'  => 'is_subscribe_after_register',
				'label' => __( 'Automated subscription', 'sendpulse-email-marketing-newsletter' ),
				'desc'  => __( 'This automatically adds the contacts of newly registered WordPress users to your selected SendPulse mailing list.', 'sendpulse-email-marketing-newsletter' ),
				'type'  => 'checkbox'
			);

        // Add customer address book list
        $books = $this->get_lists_address_book();

        if ( ! empty( $books ) ) {
            $options = array_combine( wp_list_pluck( $books, 'id' ), wp_list_pluck( $books, 'name' ) );
			$settings_fields['sp_api_setting'][] = array(
				'name'              => 'default_book',
				'label'             => __( 'Target mailing list', 'sendpulse-email-marketing-newsletter' ),
				'desc'              => __( 'Used to automatically store the contacts of new WordPress users.', 'sendpulse-email-marketing-newsletter' ),
				'type'              => 'select',
				'default'           => '',
				'options'           => $options,
				'sanitize_callback' => 'sanitize_text_field'
            );
        }


        if ( ! empty( $books ) ) {

            $editable_roles = array_reverse( get_editable_roles() );

            $role_options = array();

            foreach ( $editable_roles as $role => $details ) {
                $role_options[ $role ] = translate_user_role( $details['name'] );
            }


            $settings_fields['sp_import_setting'] = array(
                array(
                    'name'    => 'import_to_book',
                    'label'   => __( 'Default mailing list for import', 'sendpulse-email-marketing-newsletter' ),
                    'desc'    => '',
                    'type'    => 'select',
                    'default' => '',
                    'options' => $options
                ),
                array(
                    'name'    => 'import_users_group',
                    'label'   => __( 'User role', 'sendpulse-email-marketing-newsletter' ),
                    'desc'    => '',
                    'type'    => 'select',
                    'default' => '',
                    'options' => $role_options
                )

            );
        }

        return $settings_fields;
    }

    /**
     * Display setting page
     */
    public function plugin_page() {
        $current_tab = $this->get_current_tab();

        echo '<div class="wrap">';
        if ( 'documentation' === $current_tab ) {
            echo '<h1>' . esc_html__( 'SendPulse Documentation', 'sendpulse-email-marketing-newsletter' ) . '</h1>';
        } else {
            echo '<h1>' . esc_html__( 'SendPulse Settings', 'sendpulse-email-marketing-newsletter' ) . '</h1>';
        }
        $this->render_tabs( $current_tab );
        settings_errors();

        if ( 'documentation' === $current_tab ) {
            $this->render_documentation_tab();
            echo '</div>'; // end .wrap
            return;
        }

        echo '<div id="poststuff">';
        echo '<div class="metabox-holder columns-2">';

        // Left Column
        echo '<div class="postbox-container">';

        // Box 1: API Settings
        echo '<div class="postbox">';
        echo '<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: API Settings</span><span class="toggle-indicator" aria-hidden="true"></span></button>';
        echo '<h2 class="hndle"><span>' . esc_html__( 'API Settings', 'sendpulse-email-marketing-newsletter' ) . '</span></h2>';
        echo '<div class="inside">';
        echo '<form method="post" action="options.php">';
        settings_fields( 'sp_api_setting' );
        echo '<table class="form-table sp-newsletter-api-settings-table">';
        do_settings_fields( $this->page, 'sp_api_setting' );
        echo '</table>';
        submit_button( __( 'Save API Settings', 'sendpulse-email-marketing-newsletter' ) );
        echo '</form>';
        echo '</div>';
        echo '</div>'; // end .postbox

        // Box 2: Import Defaults
        echo '<div id="import-defaults" class="postbox">';
        echo '<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Import Defaults</span><span class="toggle-indicator" aria-hidden="true"></span></button>';
        echo '<h2 class="hndle"><span>' . esc_html__( 'Import Defaults', 'sendpulse-email-marketing-newsletter' ) . '</span></h2>';
        echo '<div class="inside">';
        echo '<div class="notice notice-info inline sp-newsletter-import-defaults-help"><p>' . esc_html__( 'These values are used by default on the user import page.', 'sendpulse-email-marketing-newsletter' ) . '</p><p>' . esc_html__( 'Changing values during an import does not modify these defaults.', 'sendpulse-email-marketing-newsletter' ) . '</p></div>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'sp_import_setting' );
        echo '<table class="form-table sp-newsletter-import-defaults-table">';
        do_settings_fields( $this->page, 'sp_import_setting' );
        echo '</table>';
        submit_button( __( 'Save Import Defaults', 'sendpulse-email-marketing-newsletter' ) );
        echo '</form>';
        echo '</div>';
        echo '</div>'; // end .postbox

        echo '</div>'; // end .postbox-container

        echo '</div>'; // end .metabox-holder
        echo '</div>'; // end #poststuff

        echo '</div>'; // end .wrap
    }

    protected function get_current_tab() {
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
        $allowed_tabs = array( 'settings', 'documentation' );

        if ( ! in_array( $tab, $allowed_tabs, true ) ) {
            return 'settings';
        }

        return $tab;
    }

    protected function get_settings_page_url() {
        return admin_url( 'edit.php?post_type=sendpulse_form&page=' . $this->page );
    }

	protected function get_documentation_tab_url() {
		return add_query_arg(
			'tab',
			'documentation',
			$this->get_settings_page_url()
		);
	}

	protected function get_sendpulse_api_settings_url() {
		return 'https://login.sendpulse.com/settings/api';
	}

	protected function get_api_credentials_documentation_url() {
		return $this->get_documentation_tab_url() . '#api-credentials';
	}

	protected function get_forms_page_url() {
		return admin_url( 'edit.php?post_type=sendpulse_form' );
	}

	protected function get_import_defaults_url() {
		return $this->get_settings_page_url() . '#import-defaults';
	}

	protected function get_import_sidebar_links() {
		return array(
			array(
				'label'       => __( 'SendPulse forms', 'sendpulse-email-marketing-newsletter' ),
				'url'         => $this->get_forms_page_url(),
				'icon'        => 'dashicons-email-alt',
				'description' => __( 'Create and manage website subscription forms.', 'sendpulse-email-marketing-newsletter' ),
			),
			array(
				'label'       => __( 'Settings', 'sendpulse-email-marketing-newsletter' ),
				'url'         => $this->get_settings_page_url(),
				'icon'        => 'dashicons-admin-generic',
				'description' => __( 'Access your API credentials and automated subscription settings.', 'sendpulse-email-marketing-newsletter' ),
			),
			array(
				'label'       => __( 'Import Defaults', 'sendpulse-email-marketing-newsletter' ),
				'url'         => $this->get_import_defaults_url(),
				'icon'        => 'dashicons-controls-repeat',
				'description' => __( 'Manage your mailing lists and user roles for default imports.', 'sendpulse-email-marketing-newsletter' ),
			),
			array(
				'label'       => __( 'Documentation', 'sendpulse-email-marketing-newsletter' ),
				'url'         => $this->get_documentation_tab_url(),
				'icon'        => 'dashicons-media-document',
				'description' => __( 'Learn how imports and automated subscription work.', 'sendpulse-email-marketing-newsletter' ),
			),
		);
	}

    protected function render_tabs( $current_tab ) {
        $settings_classes = 'nav-tab';
        $documentation_classes = 'nav-tab';

        if ( 'settings' === $current_tab ) {
            $settings_classes .= ' nav-tab-active';
        } else {
            $documentation_classes .= ' nav-tab-active';
        }

        echo '<nav class="nav-tab-wrapper">';
        printf(
            '<a href="%1$s" class="%2$s">%3$s</a>',
            esc_url( $this->get_settings_page_url() ),
            esc_attr( $settings_classes ),
            esc_html__( 'Settings', 'sendpulse-email-marketing-newsletter' )
        );
        printf(
            '<a href="%1$s" class="%2$s">%3$s</a>',
            esc_url( $this->get_documentation_tab_url() ),
            esc_attr( $documentation_classes ),
            esc_html__( 'Documentation', 'sendpulse-email-marketing-newsletter' )
        );
        echo '</nav>';
    }

    protected function render_documentation_tab() {

        echo '<div class="sp-newsletter-docs-layout">';
        echo '<div class="postbox sp-newsletter-docs-main">';
        echo '<div class="inside sp-newsletter-docs-content">';

        echo '<div id="api-credentials" class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Where to find Client ID and Client Secret', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'Open your SendPulse account settings and go to API.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p>' . esc_html__( 'For this plugin, use credentials from the Client credentials tab.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Copy Client ID into the Client ID field.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Copy Secret into the Client Secret field.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Do not use values from the API keys tab for this plugin.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        printf(
            '<p><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a></p>',
            esc_url( $this->get_sendpulse_api_settings_url() ),
            esc_html__( 'SendPulse API settings', 'sendpulse-email-marketing-newsletter' )
        );
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
	    echo '<div class="notice inline notice-info"><p>' . esc_html__( 'Learn how automated subscription of newly registered WordPress users works.', 'sendpulse-email-marketing-newsletter' ) . '</p></div>';
        echo '<h2>' . esc_html__( 'How Import Defaults work', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'Import Defaults define the mailing list and user role that are preselected on the User contact import page.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p>' . esc_html__( 'They are saved settings used as a starting point for future imports.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Default mailing list for import: preselects the SendPulse mailing list.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'User role: preselects which WordPress users will be imported.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'You can override both values on the User contact import page before starting an import.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Import Defaults vs User contact import', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p><strong>' . esc_html__( 'Import Defaults', 'sendpulse-email-marketing-newsletter' ) . '</strong></p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Saved default settings', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Used for future imports', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        echo '<p><strong>' . esc_html__( 'User contact import', 'sendpulse-email-marketing-newsletter' ) . '</strong></p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Used for the current import only', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Does not modify Import Defaults', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        echo '<p>' . esc_html__( 'Updating values on this page doesn’t affect the settings.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p>' . esc_html__( 'To permanently change the default mailing list or user role used for future imports, update Import Defaults in Settings.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'How automated subscription works', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'When enabled, this feature automatically adds each newly registered WordPress user to the selected SendPulse mailing list.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Required WordPress settings', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'Enabling this feature does not enable user registration automatically.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p>' . esc_html__( 'WordPress user registration must be configured separately in Settings > General.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Membership: Anyone can register', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'New User Default Role: choose the role for new users', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Registration page', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'When user registration is enabled, WordPress provides the standard registration page at:', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p><code>/wp-login.php?action=register</code></p>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Target mailing list', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'The Target mailing list setting defines which SendPulse mailing list will receive newly registered WordPress users.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p>' . esc_html__( 'If no mailing list is selected, automated subscription cannot work correctly.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Troubleshooting', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'If automated subscription does not work, first check that:', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'WordPress user registration is enabled', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Automated subscription is enabled', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Target mailing list is selected', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="sp-newsletter-docs-section">';
        echo '<h2>' . esc_html__( 'Why is the SendPulse subscription form not appearing on my website?', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<p>' . esc_html__( 'If the subscription form does not appear on your website, the issue is often caused by frontend optimization plugins.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<p>' . esc_html__( 'Many performance plugins (such as WP Rocket, Autoptimize, LiteSpeed Cache, Fast Velocity Minify, etc.) modify how JavaScript is loaded by enabling features like:', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'JavaScript minification', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'JavaScript combination (bundling)', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Deferred or delayed script execution', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'JavaScript optimization and aggregation', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';
        echo '<p>' . esc_html__( 'These optimizations can change the loading order or execution timing of third-party scripts and may prevent SendPulse subscription forms from loading correctly.', 'sendpulse-email-marketing-newsletter' ) . '</p>';

        echo '<h3>' . esc_html__( 'How to fix', 'sendpulse-email-marketing-newsletter' ) . '</h3>';
        echo '<ul class="sp-newsletter-docs-checklist">';
        echo '<li>' . esc_html__( 'Temporarily disable JavaScript optimization options and check whether the subscription form starts working.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'If it does, re-enable options one by one to identify the conflicting setting.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Clear all caches after changing optimization settings.', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';

        echo '<h3>' . esc_html__( 'For WP Rocket users', 'sendpulse-email-marketing-newsletter' ) . '</h3>';
        echo '<ul>';
        echo '<li>' . esc_html__( 'Disable the "Minify JavaScript files" option', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Disable the "Combine JavaScript files" option', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Disable the "Delay JavaScript Execution" option (if enabled)', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '<li>' . esc_html__( 'Clear the cache after making changes', 'sendpulse-email-marketing-newsletter' ) . '</li>';
        echo '</ul>';

        echo '<div class="notice inline notice-info sp-newsletter-docs-info-block">';
        echo '<h4>' . esc_html__( 'Recommended exclusions', 'sendpulse-email-marketing-newsletter' ) . '</h4>';
        echo '<p>' . esc_html__( 'In some cases, excluding SendPulse resources from optimization may help.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '<pre class="sp-newsletter-docs-code-block"><code>static-login.sendpulse.com' . "\n" . 'web.webformscr.com' . "\n" . '/apps/fc3/build/loader.js</code></pre>';
        echo '<p>' . esc_html__( 'These resources are required for loading and rendering SendPulse subscription forms.', 'sendpulse-email-marketing-newsletter' ) . '</p>';
        echo '</div>';

        echo '<div class="notice inline notice-info sp-newsletter-docs-info-block"><p><strong>' . esc_html__( 'Note', 'sendpulse-email-marketing-newsletter' ) . '</strong></p><p>' . esc_html__( 'JavaScript combination is often unnecessary on modern HTTP/2 and HTTP/3 websites and may cause compatibility issues with third-party services such as embedded forms, chat widgets, analytics tools, and marketing integrations.', 'sendpulse-email-marketing-newsletter' ) . '</p></div>';
        echo '</div>';

        printf(
            '<p><a href="%1$s" class="button button-secondary">%2$s</a></p>',
            esc_url( $this->get_settings_page_url() ),
            esc_html__( 'Back to Settings', 'sendpulse-email-marketing-newsletter' )
        );

        echo '</div>';
        echo '</div>';

        echo '<div class="postbox sp-newsletter-docs-sidebar">';
        echo '<div class="inside">';
        echo '<h2>' . esc_html__( 'Helpful links', 'sendpulse-email-marketing-newsletter' ) . '</h2>';
        echo '<ul class="sp-newsletter-docs-links">';
        foreach ( $this->get_documentation_links() as $link ) {
            printf(
                '<li class="sp-newsletter-docs-link-item"><span class="dashicons dashicons-external sp-newsletter-docs-link-icon" aria-hidden="true"></span><div class="sp-newsletter-docs-link-content"><a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a><span class="screen-reader-text">%4$s</span><p class="sp-newsletter-docs-link-description">%3$s</p></div></li>',
                esc_url( $link['url'] ),
                esc_html( $link['label'] ),
                esc_html( $link['description'] ),
                esc_html__( 'opens in a new tab', 'sendpulse-email-marketing-newsletter' )
            );
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    protected function get_documentation_links() {
        return array(
            array(
				'label' => __( 'The SendPulse Email Marketing Newsletter plugin', 'sendpulse-email-marketing-newsletter' ),
                'description' => __( 'Learn how to install and configure SendPulse\'s plugin on WordPress.', 'sendpulse-email-marketing-newsletter' ),
                'url'   => 'https://sendpulse.com/knowledge-base/app-directory/wordpress',
            ),
            array(
                'label' => __( 'Simple subscription form', 'sendpulse-email-marketing-newsletter' ),
                'description' => __( 'Create your own website subscription form using SendPulse\'s builder.', 'sendpulse-email-marketing-newsletter' ),
                'url'   => 'https://sendpulse.com/knowledge-base/email-service/mailing-list-recipients/simple-form',
            ),
            array(
                'label' => __( 'Multichannel subscription form', 'sendpulse-email-marketing-newsletter' ),
				'description' => __( 'Capture contacts through email, SMS, and social media.', 'sendpulse-email-marketing-newsletter' ),
                'url'   => 'https://sendpulse.com/knowledge-base/email-service/mailing-list-recipients/create-subscription-form',
            ),
            array(
                'label' => __( 'More about subscription forms', 'sendpulse-email-marketing-newsletter' ),
                'description' => __( 'Explore other subscription form features and best practices.', 'sendpulse-email-marketing-newsletter' ),
                'url'   => 'https://sendpulse.com/features/email/subscription-forms',
            ),
        );
    }

    public function import_page() {
        $books = array();
        $roles = array();

        $import_settings = get_option( 'sp_import_setting', array() );
        $saved_book_id = isset( $import_settings['import_to_book'] ) ? $import_settings['import_to_book'] : '';
        $saved_role = isset( $import_settings['import_users_group'] ) ? $import_settings['import_users_group'] : '';


        try {
            $api = new Send_Pulse_Newsletter_API();
            $books = $api->get_books(); // Returns list of address books (id + name)
        } catch ( Exception $e ) {
            $books[] = array(
                'id'   => 0,
                'name' => __( 'Error fetching books: ', 'sendpulse-email-marketing-newsletter' ) . $e->getMessage()
            );
        }

        // Fetch WP roles
        global $wp_roles;
        foreach ( $wp_roles->roles as $role_key => $role_data ) {
            $roles[] = array(
                'value' => $role_key,
                'label' => $role_data['name'],
            );
        }

        $nonce = wp_create_nonce( 'sendpulse_import' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'SendPulse User Import', 'sendpulse-email-marketing-newsletter' ); ?></h1>

            <div class="sp-newsletter-import-layout">
                <div class="sp-newsletter-import-main">
                    <div class="postbox sp-newsletter-import-card">
                        <div class="inside">
                            <h2><?php esc_html_e( 'Import Defaults', 'sendpulse-email-marketing-newsletter' ); ?></h2>
                            <div class="notice notice-info inline">
                                <p><?php esc_html_e( 'The mailing list and user role below were preselected from your saved Import Defaults.', 'sendpulse-email-marketing-newsletter' ); ?></p>
                            <p><?php esc_html_e( 'Selected values apply only to this import run.', 'sendpulse-email-marketing-newsletter' ); ?></p>
                                <p><?php esc_html_e( 'Updating values on this page doesn’t affect the settings.', 'sendpulse-email-marketing-newsletter' ); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="postbox sp-newsletter-import-card">
                        <div class="inside">
                            <h2><?php esc_html_e( 'User contact import', 'sendpulse-email-marketing-newsletter' ); ?></h2>
                            <div id="sendpulse-dynamic-fields" class="sp-newsletter-import-form">
                                <div class="sp-newsletter-import-form-row">
                                    <div class="sp-newsletter-import-form-label">
                                        <label for="sp-book"><?php esc_html_e( 'Mailing list', 'sendpulse-email-marketing-newsletter' ); ?></label>
                                    </div>
                                    <div class="sp-newsletter-import-form-control">
                                        <select id="sp-book" class="sp-book-select">
                                            <?php foreach ( $books as $book ) : ?>
                                                <option value="<?php echo esc_attr( $book['id'] ); ?>" <?php selected( $saved_book_id, $book['id'] ); ?>>
                                                    <?php echo esc_html( $book['name'] ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="sp-newsletter-import-form-row">
                                    <div class="sp-newsletter-import-form-label">
                                        <label for="sp-role"><?php esc_html_e( 'User role', 'sendpulse-email-marketing-newsletter' ); ?></label>
                                    </div>
                                    <div class="sp-newsletter-import-form-control">
                                        <select id="sp-role" class="sp-role-select">
                                            <?php foreach ( $roles as $role ) : ?>
                                                <option value="<?php echo esc_attr( $role['value'] ); ?>" <?php selected( $saved_role, $role['value'] ); ?>>
                                                    <?php echo esc_html( $role['label'] ); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="sp-import-controls sp-newsletter-import-form-actions">
                                <button id="sp-import" class="button button-primary button-large"
                                        data-_ajax_nonce="<?php echo esc_attr( $nonce ); ?>"
                                        data-action="sendpulse_import">
									<?php esc_html_e( 'Run import', 'sendpulse-email-marketing-newsletter' ); ?>
                                </button>

                                <textarea rows="10" cols="70" id="sp-import-log" class="sp-import-log" readonly
                                          title="<?php esc_attr_e( 'Import Log', 'sendpulse-email-marketing-newsletter' ); ?>"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="postbox sp-newsletter-import-sidebar">
                    <div class="inside">
                        <h2><?php esc_html_e( 'Helpful links', 'sendpulse-email-marketing-newsletter' ); ?></h2>
                        <ul class="sp-newsletter-import-links">
                            <?php foreach ( $this->get_import_sidebar_links() as $link ) : ?>
                                <li class="sp-newsletter-import-link-item">
                                    <span class="dashicons <?php echo esc_attr( $link['icon'] ); ?> sp-newsletter-import-link-icon" aria-hidden="true"></span>
                                    <div class="sp-newsletter-import-link-content">
                                        <a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['label'] ); ?></a>
                                        <p class="sp-newsletter-import-link-description"><?php echo esc_html( $link['description'] ); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>


        </div>
        <?php
    }

    /**
     * Get the value of a settings field
     *
     * @param string $option settings field name
     * @param string $section the section name this field belongs to
     * @param string $default default text if it's not found
     *
     * @return mixed
     */

    public static function get_option( $option, $section, $default = '' ) {
        $options = get_option( $section );

        if ( isset( $options[ $option ] ) ) {
            return $options[ $option ];
        }

        return $default;
    }


    /**
     * Get customer address books list
     *
     * @return array Address books list
     */
    protected function get_lists_address_book() {
        $books = array();

        if ( $this->api ) {
            $api = $this->api;
            $response = $api->listAddressBooks();

            if ( is_wp_error( $response ) ) {
                $this->error = __( 'SendPulse API is temporarily unavailable. Mailing lists could not be loaded.', 'sendpulse-email-marketing-newsletter' );
            } elseif ( ! $api->is_available() ) {
                $this->error = __( 'SendPulse API is temporarily unavailable. Mailing lists could not be loaded.', 'sendpulse-email-marketing-newsletter' );
            } elseif ( is_array( $response ) && ! empty( $response ) ) {
                $books = $response;
            } elseif ( is_array( $response ) ) {
                if ( $api->get_last_error() ) {
                    $this->error = __( 'SendPulse API is temporarily unavailable. Mailing lists could not be loaded.', 'sendpulse-email-marketing-newsletter' );
                } else {
                    $this->error = __( 'You have no books to show', 'sendpulse-email-marketing-newsletter' );
                }
            } elseif ( is_object( $response ) && empty( get_object_vars( $response ) ) ) {
                $this->error = __( 'You have no books to show', 'sendpulse-email-marketing-newsletter' );
            } else {
                $this->error = __( 'Error API. Please try again later', 'sendpulse-email-marketing-newsletter' );
            }
        }

        return $books;
    }

}

new Send_Pulse_Newsletter_Settings();
