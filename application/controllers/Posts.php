<?php

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . '/libraries/RestController.php';
require APPPATH . '/libraries/Format.php';

use chriskacerguis\RestServer\RestController;

class Posts extends RestController
{

	function __construct()
	{
		parent::__construct();
		$this->load->model('Post');
		$this->load->model('User');
	}

	public function index_get()
	{
		$this->load->view('posts/posts');
	}

	public function create_get()
	{
		$this->load->view('posts/create');
	}

	public function categories_get()
	{
		$categories = $this->Post->get_categories();
		$token = $this->input->get_request_header('X-Token');
		if (!empty($token)) {
			$user = $this->User->get_user($token);
			if ($user) {
				if (!$user->staff) {
					$filtered = [];
					foreach ($categories as $category) {
						if ($category['locked'] == 0) {
							$filtered[] = $category;
						}
					}
					$categories = $filtered;
				}
				echo json_encode($categories);
			} else {
				$this->response(['error' => 'Unauthorized'], 401);
			}
		} else {
			echo json_encode($categories);
		}
	}

	public function fetch_get()
	{
		$category = $this->uri->segment(3);
		if (!isset($category)) {
			echo json_encode([]);
			return;
		}
		$posts = $this->Post->get_posts($category);
		echo json_encode($posts);
	}



}
