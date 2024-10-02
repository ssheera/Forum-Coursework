<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/RestController.php';
require APPPATH . '/libraries/Format.php';

use chriskacerguis\RestServer\RestController;

class Auth extends RestController {

    function __construct()
    {
        parent::__construct();
		// Load the model
		$this->load->model('User');
    }

	/**
	 * GET method for login page
	 * Just to show the login page
	 * @return void
	 */
	public function login_get()
	{
		$this->load->view('auth/login');
	}

	/**
	 * POST method for login
	 * For users to try and login
	 * username and password are received from the form
	 * and validation is done in the model
	 * @return void
	 */
	public function login_post()
	{
		// Get the username and password from the form
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		// Call the login function in the model
		$response = $this->User->login($username, $password);
		echo json_encode($response);
	}

	/**
	 * GET method for self
	 * For users to get their user information,
	 * primarily used for getting their username
	 * @return void
	 */
	public function self_get() {
		// Get the token from the header
		$token = $this->input->get_request_header('X-Token', TRUE);
		// Call the get_user function in the model, passing the token
		$response = $this->User->get_user($token);
		// If the response is not empty, i.e. the token is valid
		if ($response)
			echo json_encode($response);
		else
			// If the token is invalid
			echo json_encode(['status' => FALSE, 'message' => 'Invalid token']);
	}

	/**
	 * GET method for register page
	 * Just to show the register page
	 * @return void
	 */
    public function register_get()
    {
        $this->load->view('auth/register');
    }

	/**
	 * POST method for register
	 * For users to try and register
	 * username, email and password are received from the form
	 * and validation is done in the model
	 * @return void
	 */
	public function register_post()
	{
		$username = $this->input->post('username');
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		// Call the register function in the model
		$response = $this->User->register($username, $email, $password);
		echo json_encode($response);
	}

	/**
	 * GET method for logout page
	 * Just to show the logout page
	 * @return void
	 */
	public function logout_get()
	{
		$this->load->view('auth/logout');
	}

}
