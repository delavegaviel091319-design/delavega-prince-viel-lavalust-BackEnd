<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Welcome extends Controller {
	public function index() {
		$this->call->view('welcome_page');
	}

	/** Simple public health-check used by Render and for quick testing. */
	public function health() {
		header('Content-Type: application/json');
		echo json_encode(['status' => 'ok', 'service' => 'Product Management API']);
	}
}
?>
