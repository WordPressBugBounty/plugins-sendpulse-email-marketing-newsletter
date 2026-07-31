<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Class Send_Pulse_Newsletter_Forms
 */
class Send_Pulse_Newsletter_Forms {
	private $post_type = 'sendpulse_form';
	public function __construct() {
		add_action( 'init', array( $this, 'register_forms_post' ) );
		add_action( 'add_meta_boxes_sendpulse_form', array( $this, 'meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta' ) );
		add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'get_columns' ) );
		add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
		add_filter( 'post_updated_messages', array( $this, 'change_form_updated_messages' ) );
		add_filter( 'post_date_column_status', array( $this, 'change_date_column_status' ), 10, 2 );
	}

	public function register_forms_post() {
		$labels = array(
			'name'               => _x( 'SendPulse forms', 'Post type general name', 'sendpulse-email-marketing-newsletter' ),
			'singular_name'      => _x( 'SendPulse Form', 'Post type singular name', 'sendpulse-email-marketing-newsletter' ),
			'menu_name'          => _x( 'SendPulse', 'Admin Menu text', 'sendpulse-email-marketing-newsletter' ),
			'name_admin_bar'     => _x( 'SendPulse Form', 'Add New on Toolbar', 'sendpulse-email-marketing-newsletter' ),
			'add_new'            => _x( 'Add form', 'Add New SP form', 'sendpulse-email-marketing-newsletter' ),
			'add_new_item'       => __( 'New form', 'sendpulse-email-marketing-newsletter' ),
			'new_item'           => __( 'New SendPulse Form', 'sendpulse-email-marketing-newsletter' ),
			'edit_item'          => __( 'Edit SendPulse Form', 'sendpulse-email-marketing-newsletter' ),
			'view_item'          => __( 'View SendPulse Form', 'sendpulse-email-marketing-newsletter' ),
			'all_items'          => __( 'SendPulse forms', 'sendpulse-email-marketing-newsletter' ),
			'search_items'       => __( 'Search', 'sendpulse-email-marketing-newsletter' ),
			'parent_item_colon'  => __( 'Parent SendPulse Forms:', 'sendpulse-email-marketing-newsletter' ),
			'not_found'          => __( 'No SendPulse Forms found.', 'sendpulse-email-marketing-newsletter' ),
			'not_found_in_trash' => __( 'No SendPulse Forms found in Trash.', 'sendpulse-email-marketing-newsletter' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => '',
			'query_var'          => false,
			'has_archive'        => false,
			'supports'           => array( 'title' ),
		);

		register_post_type( 'sendpulse_form', $args );
	}

	public function meta_box() {
		add_meta_box(
			'sendpulse_form_code',           // Unique ID
			__( 'Subscription form code', 'sendpulse-email-marketing-newsletter' ),  // Box title
			array( $this, 'code_metabox_output' ),  // Content callback, must be of type callable
			'sendpulse_form'                   // Post type
		);

		add_meta_box( 'sendpulse_form_shortcode', __( 'Shortcode', 'sendpulse-email-marketing-newsletter' ), array(
			$this,
			'shortcode_metabox_output'
		), 'sendpulse_form', 'side', 'core', null );

		$this->remove_built_in_metaboxes();
	}

	public function code_metabox_output( $post ) {
		$code = get_post_meta( $post->ID, '_sp_form_code', true );
		wp_nonce_field( 'sp_form_code_save', 'sp_form_code_nonce' );
		?>
        <textarea rows="20" cols="40" name="sp_form_code" id="sp_form_code"
                  placeholder="<?php esc_attr_e( 'Paste the code of your SendPulse-powered form', 'sendpulse-email-marketing-newsletter' ); ?>"><?php echo esc_textarea( $code ); ?></textarea>
        <p>
			<?php echo esc_html__( 'Get code from SendPulse builder', 'sendpulse-email-marketing-newsletter' ); ?>
        </p>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( $this->post_type !== get_post_type( $post_id ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$nonce = isset( $_POST['sp_form_code_nonce'] ) ? wp_unslash( $_POST['sp_form_code_nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'sp_form_code_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['sp_form_code'] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$raw_code = wp_unslash( $_POST['sp_form_code'] );
			$sanitized_code = sendpulse_email_marketing_newsletter_normalize_form_embed_code( $raw_code );

			if ( null === $sanitized_code ) {
				return;
			}

			if ( '' === $sanitized_code ) {
				delete_post_meta( $post_id, '_sp_form_code' );
				return;
			}

			update_post_meta( $post_id, '_sp_form_code', $sanitized_code );
		}
	}

	public function shortcode_metabox_output( $post ) {
		$this->shortcode_text( $post->ID );?>
        <p><?php echo esc_html__( 'Embed this shortcode in your page, post, or widget', 'sendpulse-email-marketing-newsletter' ); ?></p>
		<?php $this->post_submit_meta_box( $post );
	}

	public function remove_built_in_metaboxes() {
		remove_meta_box( 'submitdiv', 'sendpulse_form', 'side' );
	}

	public function post_submit_meta_box( $post ) { ?>
        <div class="submitbox" id="submitpost">
            <div style="display:none;">
				<?php submit_button( __( 'Save', 'sendpulse-email-marketing-newsletter' ), '', 'save' ); ?>
            </div>
            <div id="major-publishing-actions">
                <div id="delete-action">
					<?php
					if ( current_user_can( 'delete_post', $post->ID ) ) {
						if ( ! EMPTY_TRASH_DAYS ) {
							$delete_text = __( 'Delete Permanently', 'sendpulse-email-marketing-newsletter' );
						} else {
							$delete_text = __( 'Move to Trash', 'sendpulse-email-marketing-newsletter' );
						}
						?>
                        <a class="submitdelete deletion"
                           href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>">
							<?php echo esc_html( $delete_text ); ?>
                        </a>
						<?php
					}
					?>
                </div>
                <div id="publishing-action">
                    <span class="spinner"></span>
                    <input name="original_publish" type="hidden" id="original_publish"
                           value="<?php esc_attr_e( 'Save', 'sendpulse-email-marketing-newsletter' ); ?>" />
					<?php submit_button( __( 'Save', 'sendpulse-email-marketing-newsletter' ), 'primary large', 'publish', false ); ?>
                </div>
                <div class="clear"></div>
            </div>
        </div>
		<?php
	}

	public function get_columns( $columns ) {
		$first_array = array_splice( $columns, 0, 2 );
		$columns     = array_merge( $first_array, array( 'sp_shortcode' => __( 'Shortcode', 'sendpulse-email-marketing-newsletter' ) ), $columns );
		return $columns;
	}

	public function render_column( $column_name, $post_id ) {
		if ( 'sp_shortcode' == $column_name ) {
			$this->shortcode_text( $post_id );
		}

	}

	protected function shortcode_text( $post_id ) {
		$shortcode = sprintf( '[sendpulse-form id="%s"]', esc_attr( $post_id ) );
		$desc      = __( 'Embed this shortcode in your page, post, or widget', 'sendpulse-email-marketing-newsletter' ); ?>

        <input type="text" value="<?php echo esc_attr( $shortcode ); ?>" title="<?php echo esc_attr( $desc ); ?>"
               readonly="readonly">
		<?php
	}

	public function change_form_updated_messages( $messages ) {
		global $post_type;

		if ( 'sendpulse_form' == $post_type ) {
			$messages['post'][1] =
			$messages['post'][4] =
			$messages['post'][6] = __( 'Saved', 'sendpulse-email-marketing-newsletter' );
		}

		return $messages;
	}

	/**
	 * @param $status string
	 * @param $post \WP_Post
	 *
	 * @return string
	 *
	 */
	public function change_date_column_status( $status, $post ) {
		if ( 'sendpulse_form' == $post->post_type ) {
			$status = __( 'Saved', 'sendpulse-email-marketing-newsletter' );
		}

		return $status;
	}

}

new Send_Pulse_Newsletter_Forms();
