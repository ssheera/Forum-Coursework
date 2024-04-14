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

		$args = $this->uri->uri_to_assoc(2);

		$category = isset($args['category']) ? $args['category'] : NULL;
		$author = isset($args['author']) ? $args['author'] : NULL;

		$this->load->view('posts/posts', ['category' => $category, 'author' => $author]);
	}

	public function create_get()
	{
		$args = $this->uri->uri_to_assoc(3);

		$parent = isset($args['parent']) ? $args['parent'] : NULL;
		$category = isset($args['category']) ? $args['category'] : NULL;

		$this->load->view('posts/create', ['reply' => $parent || $category, 'parent' => $parent, 'category' => $category]);
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
		$parent = $this->input->post('parent');

		$result = $this->Post->create_post($user, $category, $tags, $keywords, $title, $content, $parent);
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

	public function category_get()
	{
		$category = $this->uri->segment(3);

		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);

		$f_author = $this->input->get_request_header('X-Filter-Author');
		$f_category = $this->input->get_request_header('X-Filter-Category');

		if ($f_author && $f_author == 'self') {
			if ($user) {
				$f_author = $user->id;
			}
		}

		if (!isset($category)) {
			echo json_encode([]);
			return;
		}

		$posts = $this->Post->get_posts($category, $f_author, $f_category);
		echo json_encode($posts);
	}

	public function view_get()
	{
		$this->load->view('posts/view', ['post_id' => $this->uri->segment(3)]);
	}

	public function post_get() {

		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);

		$id = $this->uri->segment(3);
		$post = $this->Post->get_post($user, $id);
		if ($post) {
			echo json_encode($post);
		} else {
			$this->response([
				'status' => FALSE,
				'message' => 'Post not found'
			], 404);
		}
	}

	public function post_delete() {

		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}

		$id = $this->uri->segment(3);
		echo json_encode($this->Post->delete_post($user, $id));
	}

	public function attach_post()
	{
		$post_id = $this->input->post('post');
		$name = $this->input->post('name');
		$size = $this->input->post('size');
		$data = $this->input->post('data');

		$this->Post->create_attachment($post_id, $name, $size, $data);
	}

}
