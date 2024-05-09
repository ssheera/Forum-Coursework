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
		// Load both post and user models
		// User model is required for validation of user tokens
		$this->load->model('Post');
		$this->load->model('User');
	}

	/**
	 * GET method for the index page
	 * Loads the posts view
	 * @return void
	 */
	public function index_get()
	{
		$this->load->view('posts/posts');
	}

	/**
	 * GET method for the create page
	 * Loads the create view
	 * @return void
	 */
	public function create_get()
	{
		$this->load->view('posts/create');
	}

	/**
	 * POST method for creating a post
	 * Takes in the category, keywords, title, content and parent post id
	 * Creates a post with the given parameters and returns the result
	 * @return void
	 */
	public function create_post()
	{
		// Get the token from the request header
		$token = $this->input->get_request_header('X-Token');
		// Get the user from the token
		$user = $this->User->get_user($token);
		// If the user is not found, return unauthorized
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}

		$category = $this->input->post('category');
		$keywords = $this->input->post('keywords');
		$title = $this->input->post('title');
		$content = $this->input->post('content');
		$parent = $this->input->post('parent');
		// Create the post
		$result = $this->Post->create_post($user, $category, $keywords, $title, $content, $parent);
		// Return the result
		echo json_encode($result);
	}

	/**
	 * GET method for the edit page
	 * Loads the edit view
	 * @return void
	 */
	public function edit_get() {
		$this->load->view('posts/edit');
	}

	public function edit_post() {
		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}

		$post_id = $this->uri->segment(3);
		$keywords = $this->input->post('keywords');
		$title = $this->input->post('title');
		$content = $this->input->post('content');
		$result = $this->Post->edit_post($user, $keywords, $title, $content, $post_id);

		echo json_encode($result);
	}

	/**
	 * GET method for the search page
	 * Loads the search view
	 * @return void
	 */
	public function search_get()
	{
		$this->load->view('posts/search');
	}

	/**
	 * GET method for the categories
	 * Returns the categories
	 * @return void
	 */
	public function categories_get()
	{
		// Get the token from the request header
		$token = $this->input->get_request_header('X-Token');
		// Get all the categories
		$categories = $this->Post->get_categories($token);
		// If user is requesting with a token, that means that we only show the unlocked categories
		// This is usually the case for creating posts
		if (!empty($token)) {
			// Get the user from the token
			$user = $this->User->get_user($token);
			// If the user is found
			if ($user) {
				// If the user is not a staff member, filter the categories
				if (!$user->staff) {
					$filtered = [];
					foreach ($categories as $category) {
						// If the category is not locked, add it to the filtered array
						if ($category['locked'] == 0) {
							$filtered[] = $category;
						}
					}
					// Set the categories to the filtered array
					$categories = $filtered;
				}
			} else {
				// If the user is not found, set the categories to NULL, meaning invalid token
				$categories = NULL;
			}
		}
		// If the categories are set, assuming valid or no token
		if ($categories) {
			echo json_encode($categories);
		} else {
			// If the categories are NULL, return unauthorized
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
		}
	}

	/**
	 * GET method for the posts
	 * Returns the posts for the given category
	 * @return void
	 */
	public function posts_get()
	{
		// Get the category from the URL
		$category = $this->uri->segment(3);

		// Get the filters from the request headers
		$f_author = $this->input->get_request_header('X-Filter-Author');
		$f_category = $this->input->get_request_header('X-Filter-Category');
		$f_term = $this->input->get_request_header('X-Filter-Term');
		// Get the posts for the given category
		$posts = $this->Post->get_posts($category, $f_author, $f_category, $f_term);
		echo json_encode($posts);
	}

	/**
	 * GET method for the view page
	 * Loads the view view
	 * @return void
	 */
	public function view_get()
	{
		$this->load->view('posts/view');
	}

	/**
	 * GET method for the post
	 * Returns the post for the given id
	 * @return void
	 */
	public function post_get() {

		// Get the token from the request header
		$token = $this->input->get_request_header('X-Token');
		// Get the user from the token
		$user = $this->User->get_user($token);

		// Get the post id from the URL
		$id = $this->uri->segment(3);
		// Get the post for the given id
		$post = $this->Post->get_post($user, $id);
		// If the post is found, return the post
		if ($post) {
			echo json_encode($post);
		} else {
			// If the post is not found, return post not found
			$this->response([
				'status' => FALSE,
				'message' => 'Post not found'
			], 404);
		}
	}

	/**
	 * DELETE method for deleting a post
	 * Deletes the post for the given id
	 * @return void
	 */
	public function post_delete() {

		// Get the token from the request header and get the user from the token
		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);
		// If the user is not found, return unauthorized
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}
		// Get the post id from the URL and return the result of deleting the post
		$id = $this->uri->segment(3);
		echo json_encode($this->Post->delete_post($user, $id));
	}

	/**
	 * POST method for creating an attachment
	 * Creates an attachment for the given post id
	 * Takes in the file given by $_FILES
	 * @return void
	 */
	public function attach_post()
	{
		// Get the token from the request header and get the user from the token
		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);
		// If the user is not found, return unauthorized
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}

		// Get the post id from the URL and get the name, size and data of the attachment
		$post_id = $this->uri->segment(3);

		// Create the attachment and return the result
		// Send the $_FILES array to the create_attachment function
		$response = $this->Post->create_attachment($user, $post_id, $_FILES['files']);
		// Return the result
		echo json_encode($response);
	}

	/**
	 * DELETE method for deleting an attachment
	 * Deletes the attachment for the given id
	 * @return void
	 */
	public function attach_delete() {

		// Get the token from the request header and get the user from the token
		$token = $this->input->get_request_header('X-Token');
		$user = $this->User->get_user($token);
		// If the user is not found, return unauthorized
		if (!$user) {
			$this->response([
				'status' => FALSE,
				'message' => 'Unauthorized'
			], 401);
			return;
		}

		// Get the attachment id from the URL and return the result of deleting the attachment
		$attachment_id = $this->uri->segment(3);
		$response = $this->Post->delete_attachment($user, $attachment_id);
		echo json_encode($response);
	}

}
