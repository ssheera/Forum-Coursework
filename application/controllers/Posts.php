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

	public function create_post()
	{
		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}

		$category = $this->input->post('category');
		$tags = $this->input->post('tags');
		$keywords = $this->input->post('keywords');
		$title = $this->input->post('title');
		$content = $this->input->post('content');
		$attachments = $this->input->post('attachments');
		$parent = $this->input->post('parent');

		$result = $this->Post->create_post($user, $category, $tags, $keywords, $title, $content, $attachments, $parent);
		echo json_encode($result);
	}

	public function categories_get()
	{
		$token = $this->input->get_request_header('X-Token');
		$categories = $this->Post->get_categories($token);
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
			} else {
				$categories = NULL;
			}
		}
		if ($categories) {
			echo json_encode($categories);
		} else {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
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
