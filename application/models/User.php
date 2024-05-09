<?php

class User extends CI_Model {

	function __construct()
	{
		parent::__construct();
		// Load the database
		$this->load->database();
	}

	/**
	 * @param $username string the username
	 * @param $email string the email address
	 * @param $password string the password
	 * @return array of status and message
	 */
	function register($username, $email, $password)
	{
		// Validate missing fields
		if (!isset($username) || !isset($email) || !isset($password))
			return ['status' => FALSE, 'message' => 'Missing required fields'];
		// Validate empty fields
		if (empty($username) || empty($email) || empty($password))
			return ['status' => FALSE, 'message' => 'Required fields cannot be empty'];
		// Validate username length
		if (strlen($username) < 3)
			return ['status' => FALSE, 'message' => 'Username must be at least 3 characters'];
		// Validate password length
		if (strlen($password) < 5)
			return ['status' => FALSE, 'message' => 'Password must be at least 6 characters'];
		// Validate username format
		$usernameRegex = "/^[a-zA-Z0-9]+$/";
		if (!preg_match($usernameRegex, $username))
			return ['status' => FALSE, 'message' => 'Username must be alphanumeric'];

		// Validate email format
		$emailRegex = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/";
		if (!preg_match($emailRegex, $email))
			return ['status' => FALSE, 'message' => 'Invalid email address'];

		// Build the query
		// where username = $username
		$this->db->where('username', $username);
		// from user table
		$result = $this->db->get('user');
		// Check if the username already exists
		if ($result->num_rows() > 0)
			return ['status' => FALSE, 'message' => 'Username already exists'];

		// Same as above, checking if email already exists
		$this->db->where('email', $email);
		$result = $this->db->get('user');
		if ($result->num_rows() > 0)
			return ['status' => FALSE, 'message' => 'Email already exists'];

		// While loop is running, generate random token for the user, if the token already exists, run the loop again
		// This is to ensure that the token is unique for each user
		while (TRUE) {
			$token = md5(uniqid());
			// Build query, where token = $token from user table
			$this->db->where('token', $token);
			$result = $this->db->get('user');
			// Check if any results were found, none found => break the loop
			if ($result->num_rows() == 0)
				break;
		}

		// Generate the data array to be inserted into the database
		// The data array contains the username, email, password, created date and the token
		// The password is hashed using the password_hash function, the default encryption being BCrypt, provided by PHP
		$data = [
			'username' => $username,
			'email' => $email,
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'created' => date('Y-m-d H:i:s'),
			'token' => $token
		];

		// Insert the data array into the user table and return the success message
		$this->db->insert('user', $data);
		return ['status' => TRUE, 'message' => 'User registered successfully'];
	}

	/**
	 * @param $username string the username
	 * @param $password string the password
	 * @return array of status and message
	 */
	function login($username, $password) {
		// Validate missing fields
		if (empty($username) || empty($password))
			return ['status' => FALSE, 'message' => 'Missing required fields'];
		// Build the query, where username = $username from user table
		$this->db->where('username', $username);
		$result = $this->db->get('user');
		// If no results were found, return invalid username or password
		if ($result->num_rows() == 0)
			return ['status' => FALSE, 'message' => 'Invalid username or password'];
		// Get the user that was found
		$user = $result->row();
		// Verify the password using the password_verify function
		if (!password_verify($password, $user->password))
			return ['status' => FALSE, 'message' => 'Invalid username or password'];
		// Return the stored token for the user
		return ['status' => TRUE, 'token' => $user->token];
	}

	/**
	 * @param $token
	 * Get the user information from the token
	 * if the token is invalid, return NULL
	 * @return stdClass|null
	 */
	function get_user($token)
	{
		// Validate token
		if (empty($token)) return NULL;
		// Build the query, where token = $token from user table
		$this->db->where('token', $token);
		// Get all users with the token
		$result = $this->db->get('user');
		// If none found, token is invalid, return NULL
		if ($result->num_rows() == 0) return NULL;
		// Get the user
		$user = $result->row();
		// Build query in staff table to check if the user is a staff member
		$this->db->where('userId', $user->id);
		$result = $this->db->get('staff');
		// Set the staff property to TRUE if the user is a staff member
		$user->staff = $result->num_rows() > 0;
		// Remove the email and password from the user object, it is not required
		unset($user->password);
		unset($user->email);
		return $user;
	}

}
