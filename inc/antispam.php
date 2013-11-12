<?php
class AntiCommentSpam {
	/**
	 * Number of seconds delay expected before a comment is submitted. Any earlier indicates it may be spam!
	 *
	 * @var int
	 */
	public $required_delay = 4;

	/**
	 * Will be set to the NONCE_KEY by default. Used to protect the countermeasure data from tampering by spambots.
	 *
	 * @var string
	 */
	public $base_key = '';

	/**
	 * Plugin directory URL.
	 *
	 * @var string
	 */
	protected $url = '';


	public function __construct() {
		$this->init();
		$this->add_marker_element();
		$this->setup_js();
		$this->catch_responses();
		$this->check_comments();
	}


	protected function init() {
		$this->base_key = apply_filters('anticommentspam_base_key', NONCE_KEY);
		$parent_file = realpath(__DIR__ . '/../anti-comment-spam.php');
		$this->url = plugin_dir_url($parent_file);
	}


	protected function add_marker_element() {
		add_action('comment_form', array($this, 'generate_marker'));
	}


	public function generate_marker() {
		$init_val = absint($this->required_delay) . '|' . $this->time_marker(hash('crc32', $this->required_delay));
		echo '<input type="hidden" name="acsmarker" value="' . $init_val . '" />"';
	}


	protected function setup_js() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_js'));
	}


	public function enqueue_js() {
		wp_enqueue_script('anticommentspam', $this->url . '/inc/commentprotection.js', array('jquery'));
		wp_localize_script('anticommentspam', 'acs_references', array('ajax_url' => admin_url('admin-ajax.php')) );
	}


	protected function catch_responses() {
		add_action('wp_ajax_nopriv_acs_update', array($this, 'update_requests'));
	}


	public function update_requests() {
		// If we weren't sent time and hash vars then send a zero response
		if (!isset($_POST['time']) || !isset($_POST['hash'])) exit('0');

		// If the response is invalid return a zero response
		$received = $_POST['time'] . '|' . $_POST['hash'];
		$expected = $this->time_marker(hash('crc32', $this->required_delay), $_POST['time']);
		if ($received !== $expected) exit('0');

		// If response if premature return a zero response
		$now = time();
		$earliest = absint($_POST['time']) + $this->required_delay;
		if ($now < $earliest) exit('0');

		// Seems ok ... respond with new hash
		$response = hash('md5', $_POST['hash'] . $this->base_key);
		exit($response);
	}


	protected function time_marker($last_hash = '', $time = 0) {
		$time = (0 === $time) ? time() : $time;
		$hash = hash('md5', $this->base_key . $time . $last_hash);
		return "$time|$hash";
	}


	protected function check_comments() {
		add_filter('pre_comment_approved', array($this, 'marker_check'), 50, 2);
	}


	public function marker_check($status, $comment) {
		if (is_user_logged_in()) return $status; // Don't interfere with comments from authenticated users
		if (!isset($_POST['acsmarker'])) return 'spam'; // No acsmarker? Spam!

		// We should have at 4 components, if not treat as spam
		$checkdata = explode('|', $_POST['acsmarker']);
		if (4 !== count($checkdata)) return 'spam';

		// Time checks out?
		if ($checkdata[0] != $this->required_delay) return 'spam';
		$received = $checkdata[1] . '|' . $checkdata[2];
		$expected = $this->time_marker(hash('crc32', $this->required_delay), $checkdata[1]);
		if ($received != $expected) return 'spam';

		// Final check
		if ($checkdata[3] == hash('md5', $checkdata[2] . $this->base_key)) return $status;

		return 'spam'; // Mark anything else as spam
	}
}