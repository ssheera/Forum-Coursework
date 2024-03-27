<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/RestController.php';
require APPPATH . '/libraries/Format.php';

use chriskacerguis\RestServer\RestController;

class Auth extends RestController {

    function __construct()
    {
        parent::__construct();
		$this->load->model('User');
    }

	public function login_get()
	{
		$this->load->view('auth/login');
	}

	public function login_post()
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');

		$response = $this->User->login($username, $password);
		echo json_encode($response);
	}

    public function register_get()
    {
        $this->load->view('auth/register');
    }

	public function register_post()
	{
		// Get post data, username, email, and password
		$username = $this->input->post('username');
		$email = $this->input->post('email');
		$password = $this->input->post('password');

		$response = $this->User->register($username, $email, $password);
		echo json_encode($response);
	}

	public function logout_get()
	{
		$this->load->view('auth/logout');
	}

}
