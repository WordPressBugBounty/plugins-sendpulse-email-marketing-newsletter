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
        add_action( 'wsa_form_top_sp_import_setting', array( $this, 'start_import_controls' ) );
        add_action( 'wsa_form_bottom_sp_import_setting', array( $this, 'end_import_controls' ) );
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
					$desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : ''
				);
				break;

			case 'checkbox':
				$checked = checked( $value, 'on', false );
				printf(
					'<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />%4$s',
					esc_attr( $args['section'] ),
					esc_attr( $name ),
					wp_kses_post($checked),
					$desc ? '<p class="description">' . esc_html( $desc ) . '</p>' : ''
				);
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
				if ( $desc ) {
					echo '<p class="description">' . esc_html( $desc ) . '</p>';
				}
				break;
		}
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
            __( 'Import Users', 'sendpulse-email-marketing-newsletter' ),
            __( 'Import Users', 'sendpulse-email-marketing-newsletter' ),
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
                'title' => __( 'Import', 'sendpulse-email-marketing-newsletter' )
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
                    'desc'              => __( 'Get from https://login.sendpulse.com/settings/', 'sendpulse-email-marketing-newsletter' ),
                    'placeholder'       => __( 'Client ID', 'sendpulse-email-marketing-newsletter' ),
                    'type'              => 'text',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                array(
                    'name'              => 'client_secret',
                    'label'             => __( 'Client Secret', 'sendpulse-email-marketing-newsletter' ),
                    'desc'              => __( 'Get from https://login.sendpulse.com/settings/', 'sendpulse-email-marketing-newsletter' ),
                    'placeholder'       => __( 'Client Secret', 'sendpulse-email-marketing-newsletter' ),
                    'type'              => 'text',
                    'default'           => '',
                    'sanitize_callback' => 'sanitize_text_field'
                )

            )
        );

        $settings_fields['sp_api_setting'][] =
            array(
                'name'  => 'is_subscribe_after_register',
                'label' => __( 'Post-subscription option', 'sendpulse-email-marketing-newsletter' ),
                'desc'  => __( 'Add all new WordPress subscribers to the selected mailing list', 'sendpulse-email-marketing-newsletter' ),
                'type'  => 'checkbox'
            );

        // Add customer address book list
        $books = $this->get_lists_address_book();

        if ( ! empty( $books ) ) {
            $options = array_combine( wp_list_pluck( $books, 'id' ), wp_list_pluck( $books, 'name' ) );
            $settings_fields['sp_api_setting'][] = array(
                'name'              => 'default_book',
                'label'             => __( 'Target mailing list', 'sendpulse-email-marketing-newsletter' ),
                'desc'              => __( 'Add a mailing list in your SendPulse account new subscribers will be transferred to', 'sendpulse-email-marketing-newsletter' ),
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
                    'label'   => __( 'Import to Address Book', 'sendpulse-email-marketing-newsletter' ),
                    'desc'    => __( 'Address Book for wordpress users import', 'sendpulse-email-marketing-newsletter' ),
                    'type'    => 'select',
                    'default' => '',
                    'options' => $options
                ),
                array(
                    'name'    => 'import_users_group',
                    'label'   => __( 'Import Users Group', 'sendpulse-email-marketing-newsletter' ),
                    'desc'    => __( 'Users Group that will be imported', 'sendpulse-email-marketing-newsletter' ),
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
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'SendPulse Settings', 'sendpulse-email-marketing-newsletter' ) . '</h1>';
        settings_errors();

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
        echo '<table class="form-table">';
        do_settings_fields( $this->page, 'sp_api_setting' );
        echo '</table>';
        submit_button( __( 'Save API Settings', 'sendpulse-email-marketing-newsletter' ) );
        echo '</form>';
        echo '</div>';
        echo '</div>'; // end .postbox

        // Box 2: Import Settings
        echo '<div class="postbox">';
        echo '<button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Import Settings</span><span class="toggle-indicator" aria-hidden="true"></span></button>';
        echo '<h2 class="hndle"><span>' . esc_html__( 'Import Settings', 'sendpulse-email-marketing-newsletter' ) . '</span></h2>';
        echo '<div class="inside">';
        echo '<form method="post" action="options.php">';
        settings_fields( 'sp_import_setting' );
        echo '<table class="form-table">';
        do_settings_fields( $this->page, 'sp_import_setting' );
        echo '</table>';
        submit_button( __( 'Save Import Settings', 'sendpulse-email-marketing-newsletter' ) );
        echo '</form>';
        echo '</div>';
        echo '</div>'; // end .postbox

        echo '</div>'; // end .postbox-container

        echo '</div>'; // end .metabox-holder
        echo '</div>'; // end #poststuff

        echo '</div>'; // end .wrap
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
            <p><?php esc_html_e( 'Import WordPress users into your SendPulse address book.', 'sendpulse-email-marketing-newsletter' ); ?></p>

            <div id="sendpulse-dynamic-fields">
                <h2><?php esc_html_e( 'Choose Address Book', 'sendpulse-email-marketing-newsletter' ); ?></h2>
                <select id="sp-book" class="sp-book-select">
                    <?php foreach ( $books as $book ) : ?>
                        <option value="<?php echo esc_attr( $book['id'] ); ?>" <?php selected( $saved_book_id, $book['id'] ); ?>>
                            <?php echo esc_html( $book['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <h2><?php esc_html_e( 'Choose User Role', 'sendpulse-email-marketing-newsletter' ); ?></h2>
                <select id="sp-role" class="sp-role-select">
                    <?php foreach ( $roles as $role ) : ?>
                        <option value="<?php echo esc_attr( $role['value'] ); ?>" <?php selected( $saved_role, $role['value'] ); ?>>
                            <?php echo esc_html( $role['label'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sp-import-controls">
                <button id="sp-import" class="button button-primary button-large"
                        data-_ajax_nonce="<?php echo esc_attr( $nonce ); ?>"
                        data-action="sendpulse_import">
                    <?php esc_html_e( 'Start import', 'sendpulse-email-marketing-newsletter' ); ?>
                </button>

                <textarea rows="10" cols="70" id="sp-import-log" class="sp-import-log" readonly
                          title="<?php esc_attr_e( 'Import Log', 'sendpulse-email-marketing-newsletter' ); ?>"></textarea>
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

    public function start_import_controls() { ?>
        <div class="sp-import-controls">
    <?php }

    public function end_import_controls() {
	    echo wp_kses_post(
		    get_submit_button(
			    __( 'Start import', 'sendpulse-email-marketing-newsletter' ),
			    'primary large',
			    'sp-import',
			    true,
			    array(
				    'data-_ajax_nonce' => wp_create_nonce( 'sendpulse_import' ),
				    'data-action'      => 'sendpulse_import'
			    )
		    )
	    );
        ?>

        <textarea rows="5" cols="55" class="sp-import-log" id="sp-import-log"
                  title="<?php esc_attr_e( 'Import Log', 'sendpulse-email-marketing-newsletter' ); ?>"></textarea>
        </div>

    <?php }

}

new Send_Pulse_Newsletter_Settings();
