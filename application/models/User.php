<?php

class User extends CI_Model {

	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	function register($username, $email, $password)
	{

		if (!isset($username) || !isset($email) || !isset($password)) {
			return json_encode(['status' => FALSE, 'message' => 'Missing required fields']);
		}

		if (empty($username) || empty($email) || empty($password)) {
			return json_encode(['status' => FALSE, 'message' => 'Required fields cannot be empty']);
		}

		if (strlen($username) < 3) {
			return json_encode(['status' => FALSE, 'message' => 'Username must be at least 3 characters']);
		}

		if (strlen($password) < 5) {
			return json_encode(['status' => FALSE, 'message' => 'Password must be at least 6 characters']);
		}

		$emailRegex = "/[a-z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+\/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/";

		if (!preg_match($emailRegex, $email)) {
			return json_encode(['status' => FALSE, 'message' => 'Invalid email address']);
		}

		$this->db->where('username', $username);
		$result = $this->db->get('user');
		if ($result->num_rows() > 0)
			return json_encode(['status' => FALSE, 'message' => 'Username already exists']);

		$this->db->where('email', $email);
		$result = $this->db->get('user');
		if ($result->num_rows() > 0)
			return json_encode(['status' => FALSE, 'message' => 'Email already exists']);

		while (TRUE) {
			$token = md5(uniqid());
			$this->db->where('token', $token);
			$result = $this->db->get('user');
			if ($result->num_rows() == 0)
				break;
		}

		$data = [
			'username' => $username,
			'email' => $email,
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'created' => date('Y-m-d H:i:s'),
			'token' => $token
		];

		$this->db->insert('user', $data);
		return json_encode(['status' => TRUE, 'message' => 'User registered successfully']);
	}

	function login($username, $password) {

		if (!isset($username) || !isset($password)) {
			return json_encode(['status' => FALSE, 'message' => 'Missing required fields']);
		}

		if (empty($username) || empty($password)) {
			return json_encode(['status' => FALSE, 'message' => 'Required fields cannot be empty']);
		}

		$this->db->where('username', $username);
		$result = $this->db->get('user');

		if ($result->num_rows() == 0) {
			return json_encode(['status' => FALSE, 'message' => 'Invalid username or password']);
		}

		$user = $result->row();
		if (!password_verify($password, $user->password)) {
			return json_encode(['status' => FALSE, 'message' => 'Invalid username or password']);
		}

		return json_encode(['status' => TRUE, 'token' => $user->token]);

	}

	function get_user($token)
	{
		if (empty($token)) return FALSE;
		$this->db->where('token', $token);
		$result = $this->db->get('user');
		if ($result->num_rows() == 0) return FALSE;
		$user = $result->row();
		$this->db->where('userId', $user->id);
		$result = $this->db->get('staff');
		$user->staff = $result->num_rows() > 0;
		return $user;
	}
}
