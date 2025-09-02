<?php
use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\SessionStorage;
use Sendpulse\RestApi\Storage\FileStorage;

class Send_Pulse_Newsletter_API
{

    /**
     * @var ApiClient
     */
    protected $api;

    /**
     * @var string|int Id default address book for subscribe.
     */
    public $default_book;

    /**
     * @var Send_Pulse_Newsletter_Requirement
     */
    protected $requirement;

//    public function __construct($apiClient = null)
//    {
//        $user_id = $this->get_option('client_id');
//        $secret = $this->get_option('client_secret');
//
//        $this->requirement = new Send_Pulse_Newsletter_Requirement();
//
//		if (!$apiClient) {
//			$storage       = null;
//			$notice_action = '';
//
//			if ( $this->requirement->is_folder_writable( SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR ) !== true ) {
//				$storage       = new SessionStorage();
//				$notice_action = 'sp_emp_admin_activated_session_storage_notice';
//			} else {
//				$storage       = new FileStorage( SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR );
//				$notice_action = 'sp_emp_admin_activated_file_storage_notice';
//			}
//
//			$this->api = new ApiClient( $user_id, $secret, $storage );
//
//			add_action( 'admin_notices', array( $this, $notice_action ) );
//		}
//
//	    $this->api = $apiClient;
//        $this->default_book = $this->get_option('default_book');
//    }
	public function __construct($apiClient = null)
	{
		$user_id = $this->get_option('client_id');
		$secret  = $this->get_option('client_secret');

		$this->requirement = new Send_Pulse_Newsletter_Requirement();

		if (!$apiClient) {
			$storage       = null;
			$notice_action = '';

			if ($this->requirement->is_folder_writable(SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR) !== true) {
				$storage       = new SessionStorage();
				$notice_action = 'sp_emp_admin_activated_session_storage_notice';
			} else {
				$storage       = new FileStorage(SP_EMAIL_MARKETING_PLUGIN_STORAGE_DIR);
				$notice_action = 'sp_emp_admin_activated_file_storage_notice';
			}

			$this->api = new ApiClient($user_id, $secret, $storage);
			add_action('admin_notices', [$this, $notice_action]);
		} else {
			$this->api = $apiClient;
		}

		$this->default_book = $this->get_option('default_book');
	}


    public function get_option($name)
    {
        return Send_Pulse_Newsletter_Settings::get_option($name, 'sp_api_setting');
    }

    public function get_api()
    {
        return $this->api;
    }

    public function listAddressBooks( $limit = 100, $offset = 0 ) {
        try {
            $response = $this->api->get('addressbooks', [
                'limit'  => $limit,
                'offset' => $offset,
            ]);

            return $response;
        } catch (Exception $e) {
            return new WP_Error('sendpulse_error', $e->getMessage());
        }
    }

    public function get_books() {
        $books = $this->api->listAddressBooks();

        return array_map(function ($book) {
            return [
                'id' => $book['id'] ?? 'UNKNOWN',
                'name' => $book['name'] ?? 'UNKNOWN',
            ];
        }, $books);
    }

    public function add_contact_to_list($email, $book_id, $variables = [])
    {
        try {
            $response = $this->api->post("addressbooks/{$book_id}/emails", [
                'emails' => [
                    [
                        'email' => $email,
                        'variables' => $variables
                    ]
                ],
                'update_existing' => true
            ]);

            if ( !$response ) {
                return new WP_Error('sendpulse_error', json_encode($response));
            }

            return $response;

        } catch (Exception $e) {
            return new WP_Error('sendpulse_error', $e->getMessage());
        }
    }

    public function sp_emp_admin_activated_session_storage_notice()
    {
        if (get_option('sp_emp_session_storage_notice_dismissed')) return;
        echo wp_kses_post(sprintf(
            '<div class="notice notice-warning is-dismissible"><p><strong>%s</strong></p><p>%s</p><p>%s<br>%s</p><p>%s</p><p>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
            esc_html__('The "SendPulse Email Marketing Newsletter" plugin is activated in safe mode with the SessionStorage feature enabled.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('To use file storage, change directory rights to 775: /storage.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('Ensure owner or group is www-data.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('If using Docker, check file permissions or consult your sysadmin.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('Use only SessionStorage on wordpress.com sites.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('You can close this notification by clicking dismiss.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('Dismiss this notification and never show it again.', 'sendpulse-email-marketing-newsletter')
        ));
    }

    public function sp_emp_admin_activated_file_storage_notice()
    {
        if (get_option('sp_emp_file_storage_notice_dismissed')) return;
        echo wp_kses_post(sprintf(
            '<div class="notice notice-success is-dismissible"><p><strong>%s</strong></p><p>%s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%s</span></button></div>',
            esc_html__('The "SendPulse Email Marketing Newsletter" plugin is activated in normal mode using FileStorage.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('You can close this notification by clicking dismiss.', 'sendpulse-email-marketing-newsletter'),
            esc_html__('Dismiss this notification and never show it again.', 'sendpulse-email-marketing-newsletter')
        ));
    }
}