<?php

class Post extends CI_Model
{

	function __construct()
	{
		parent::__construct();
		// Load the database
		$this->load->database();

	}

	/**
	 * Get all categories as an array
	 * Sorted by ascending ID
	 * @return array
	 */
	function get_categories()
	{
		// Select all categories, order by ID
		$this->db->order_by('id', 'ASC');
		$result = $this->db->get('category');
		// Return the raw result array
		return $result->result_array();
	}

	/**
	 * Gets minified information about all posts in a category
	 * Used for the main page
	 * @param $category_id string id of the category to get posts from
	 * @param $f_author string author to filter by
	 * @param $f_category string category to filter by
	 * @param $f_term string term to filter by
	 * @return array
	 */
	function get_posts($category_id, $f_author, $f_category, $f_term)
	{
		// Validate the category id,  it is controlled by the user
		if (empty($category_id))
			return [];
		// set the term filter to lowercase, for case-insensitive search
		if ($f_term)
			$f_term = strtolower($f_term);

		// Select all posts where category is the given category id and order by updated date
		$this->db->where('category', $category_id);
		$this->db->order_by('updated', 'DESC');
		// Execute the query
		$result = $this->db->get('posts');

		$posts = [];

		// Loop through all posts in the result array
		for ($i = 0; $i < $result->num_rows(); $i++) {
			// Get the post at the current index
			$post = $result->row($i);

			// get the username of the author of the post
			$this->db->select('username');
			$this->db->where('id', $post->author);
			$results = $this->db->get('user');
			// if the author is not found, set it to '???'
			if ($results->num_rows() == 0) {
				$author = '???';
			} else {
				$author = $results->row()->username;
			}

			// if the author filter is set
			if ($f_author) {
				// check if the author of the post is the same as the filter
				if ($author != $f_author)
					continue;
			}

			// if the category filter is set
			if ($f_category)
				// check if the category of the post is the same as the filter
				if ($post->category != $f_category)
					continue;

			// if the term filter is set
			if ($f_term) {
				// get the content, title and keywords of the post
				// decode the content from base64
				// set all to lowercase for case-insensitive search
				$content = base64_decode($post->content);
				$content = strtolower($content);
				$title = strtolower($post->title);
				$keywords = strtolower($post->keywords);
				// check if the term is in the title, content or keywords
				if (strpos($title, $f_term) === FALSE && strpos($content, $f_term) === FALSE
					&& strpos($keywords, $f_term) === FALSE) {
					continue;
				}
			}

			// find the number of replies to the post
			// for each post of which the parent is the current post
			// however, only works directly on the first level of replies
			$replies = $this->db->get_where('posts', ['parent' => $post->id])->num_rows();
			// limited information about the post since this is only for the main page
			$posts[] = [
				'id' => $post->id,
				'title' => $post->title,
				'author' => $author,
				'replies' => $replies,
				'updated' => $post->updated,
				'parent' => $post->parent
			];
		}

		// Return array of posts
		return $posts;
	}

	/**
	 * Get a post with all information
	 * @param $user stdClass user object
	 * @param $post_id string id of the post to get
	 * @return array
	 */
	function get_post($user, $post_id)
	{
		// Validate the post id, it is controlled by the user
		if (empty($post_id))
			return NULL;

		// Select the post with the given id
		$this->db->where('id', $post_id);
		$result = $this->db->get('posts');

		// if the post is not found, return NULL
		if ($result->num_rows() == 0)
			return NULL;
		// get the post
		$post = $result->row();

		// get the username of the author of the post
		$this->db->select('username');
		$this->db->where('id', $post->author);
		$results = $this->db->get('user');
		if ($results->num_rows() == 0) {
			$author = '???';
		} else {
			$author = $results->row()->username;
		}

		// get the category of the post, used for checking if the category is locked
		$category = $this->db->get_where('category', ['id' => $post->category])->row();

		$replies = [];

		// get all replies to the post and sort them by creation date
		$this->db->where('parent', $post_id);
		$this->db->order_by('created', 'ASC');
		$results = $this->db->get('posts');

		// recursively get all replies to the post
		for ($i = 0; $i < $results->num_rows(); $i++) {
			$reply = $results->row($i);
			// add to the reply the child post and all its information
			$replies[] = $this->get_post($user, $reply->id);
		}

		// return all information about the post
		return [
			'id' => $post->id,
			'category' => $post->category,
			'title' => $post->title,
			'author' => $post->author,
			'content' => base64_decode($post->content), // decode the content from base64
			'username' => $author,
			'updated' => $post->updated,
			'parent' => $post->parent,
			'replies' => $replies,
			'reply' => $user && ($user->staff || !$category->locked), // if the category is locked, the user cannot reply
			'action' => $user && ($user->staff || $post->author == $user->id), // if the user is staff or the author of the post, they can edit/delete
			'attachments' => $this->get_attachments($post_id) // get all attachments to the post
		];
	}

	/**
	 * Get information about a category
	 * @param $category_id string id of the category to get
	 * @return stdClass
	 */
	function get_category($category_id) {
		// not used by controller, no need to validate
		// get the category with the given id
		$this->db->where('id', $category_id);
		$result = $this->db->get('category');
		// if the category is not found, return NULL
		if ($result->num_rows() == 0)
			return NULL;
		// return the whole category object
		return $result->row();
	}

	/**
	 * Create a new post
	 * @param $user stdClass the user object
	 * @param $category string the id of the category
	 * @param $keywords string the keywords of the post
	 * @param $title string the title of the post
	 * @param $content string the content of the post
	 * @param $parent string the id of the parent post
	 * @return array
	 */
	function create_post($user, $category, $keywords, $title, $content, $parent)
	{
		// Validate the fields, they are controlled by the user
		// category, title and content are required fields
		if (empty($title) || empty($content))
			return ['status' => FALSE, 'message' => 'Missing required fields'];

		// if parent post is set
		if ($parent) {
			// get the parent post
			$parent_post = $this->get_post($user, $parent);
			// if the parent post is not found, return an error
			if ($parent_post == NULL)
				return ['status' => FALSE, 'message' => 'Invalid parent'];
			// set category to the category of the parent post
			$cat = $this->get_category($parent_post['category']);
		} else {
			// if parent post is not set, get the category from the given category id
			if (empty($category))
				return ['status' => FALSE, 'message' => 'Missing required fields'];
			$cat = $this->get_category($category);
		}

		// if the category is not found, return an error
		if ($cat == NULL)
			return ['status' => FALSE, 'message' => 'Invalid category'];

		// if the category is locked and the user is not staff, return an error
		if ($cat->locked && !$user->staff)
			return ['status' => FALSE, 'message' => 'Category is locked'];

		// encode the content to base64, trim to remove whitespace
		$content = base64_encode(trim($content));

		// get the current date and time, used for created and updated fields
		$now = date('Y-m-d H:i:s');

		// data to insert into the database
		$data = [
			'author' => $user->id,
			'category' => $cat->id,
			'keywords' => $keywords,
			'title' => trim($title), // trim title to remove whitespace
			'content' => $content,
			'parent' => $parent,
			'created' => $now,
			'updated' => $now,
		];

		// insert the post into the database
		$this->db->insert('posts', $data);
		// get the id of the new post for reference
		$id = $this->db->insert_id();
		// return success
		return ['status' => TRUE, 'id' => $id];
	}

	/**
	 * Edit a post
	 * @param $user
	 * @param $keywords
	 * @param $title
	 * @param $content
	 * @param $post_id
	 * @return array
	 */
	function edit_post($user, $keywords, $title, $content, $post_id) {
		// Validate the fields, they are controlled by the user
		if (empty($post_id) || empty($title) || empty($content))
			return ['status' => FALSE, 'message' => 'Missing required fields'];

		// encode the content to base64, trim to remove whitespace
		$content = base64_encode(trim($content));

		// Get date of update
		$now = date('Y-m-d H:i:s');
		// Get the post with the given id
		$post = $this->get_post($user, $post_id);

		// check if they are updating a post that exists
		if ($post == NULL)
			return ['status' => FALSE, 'message' => 'Post not found'];
		// check if user has access to edit the post
		if (!$user->staff && $post['author'] != $user->id)
			return ['status' => FALSE, 'message' => 'Unauthorized'];

		// new data to update the post with
		$data = [
			'keywords' => $keywords,
			'title' => trim($title),
			'content' => $content,
			'updated' => $now,
		];

		// update the post in the database
		$this->db->update('posts', $data, ['id' => $post_id]);
		// return success
		return ['status' => TRUE];
	}

	/**
	 * Delete a post
	 * @param $user
	 * @param $post_id string the id of the post to delete
	 * @return array
	 */
	function delete_post($user, $post_id) {
		// Validate the post id, it is controlled by the user
		if (empty($post_id))
			return ['status' => FALSE, 'message' => 'Missing required fields'];

		// Get the post with the given id
		$post = $this->get_post($user, $post_id);

		// check if the post exists
		if ($post == NULL)
			return ['status' => FALSE, 'message' => 'Post not found'];

		// check if user has access to delete the post
		if ($post['author'] != $user->id && !$user->staff)
			return ['status' => FALSE, 'message' => 'Unauthorized'];

		// Find all replies to the post, update their parent to the parent of the post, so they are not lost
		$this->db->where('parent', $post_id);
		// get all replies to the post
		$replies = $this->db->get('posts');
		for ($i = 0; $i < $replies->num_rows(); $i++) {
			$reply = $replies->row($i);
			// update the parent of the reply to the parent of the post
			$this->db->where('id', $reply->id);
			$this->db->update('posts', ['parent' => $post['parent']]);
		}

		// now, delete the attachments of the post
		// find all attachments with the given post id
		$this->db->where('post', $post_id);
		$attachments = $this->db->get('attachments');

		for ($i = 0; $i < $attachments->num_rows(); $i++) {
			$attachment = $attachments->row($i);
			// delete the file on the server if it exists
			if (file_exists($attachment->system_path))
				unlink($attachment->system_path);
		}

		// delete the post from the database
		$this->db->where('id', $post_id);
		$this->db->delete('posts');

		// return success
		return ['status' => TRUE];
	}

	/**
	 * Get all attachments of a post
	 * @param $post_id string the id of the post to get attachments from
	 * @return array
	 */
	function get_attachments($post_id) {
		// Find all attachments with the given post id
		$this->db->where('post', $post_id);
		$result = $this->db->get('attachments');
		// Get the assets_url variable from the config
		$dir = get_instance()->config->slash_item('assets_url') . 'attachments/';
		$attachments = [];
		// Loop through all attachments and add them to the array
		for ($i = 0; $i < $result->num_rows(); $i++) {
			$attachment = $result->row($i);
			// Add the attachment to the array
			// add the path to the attachment, this is the url to the file
			// path in database is merely the name of the file
			$attachments[] = [
				'id' => $attachment->id,
				'name' => $attachment->name,
				'size' => $attachment->size,
				'path' => $dir . $attachment->path
			];
		}
		return $attachments;
	}

	/**
	 * Create an attachment
	 * @param $user stdClass the user object
	 * @param $post_id string the id of the post to attach the file to
	 * @param $files array the files to attach
	 * @return array
	 */
	function create_attachment($user, $post_id, $files) {
		// Validate the fields, they are controlled by the user
		if (empty($post_id) || empty($files))
			return ['status' => FALSE, 'message' => 'Missing required fields'];

		// Get the post that the attachment will be attached to
		$post = $this->get_post($user, $post_id);

		// If the post is not found, return an error, what post is user trying to attach to?
		if ($post == NULL)
			return ['status' => FALSE, 'message' => 'Post not found'];

		$this->db->where('post', $post_id);
		$results = $this->db->get('attachments');
		// If post has more than 5 attachments, return an error
		if ($results->num_rows() >= 5)
			return ['status' => FALSE, 'message' => 'Too many attachments'];

		// Check if new length of attachments is more than 5
		if ($results->num_rows() + count($files['name']) > 5)
			return ['status' => FALSE, 'message' => 'Too many attachments'];

		// Check if user has access to attach the file
		if (!$user->staff && $post['author'] != $user->id)
			return ['status' => FALSE, 'message' => 'Unauthorized'];

		// Get the config from codeigniter
		$config = get_instance()->config;
		// Get the assets_path variable from the config
		// assets_path is system path for the assets directory, compared to assets_url which is the url
		// convert the path to a slash-ended path and add 'attachments/' to the end
		// /attachments/ is the directory where all attachments are stored
		$real_dir = $config->slash_item('assets_path') . 'attachments/';

		// uploaded count
		$uploaded = 0;
		// Loop every file in the files array
		foreach ($files['name'] as $key => $name) {
			// Get the temporary path of the file
			// Get the name of the file and the size
			$tmp = $files['tmp_name'][$key];
			$name = $files['name'][$key];
			$size = $files['size'][$key];

			// Check file size, shouldn't be more than 8MB
			if ($size > 8 * 1024 * 1024)
				continue;

			// Generate a random path for the file
			$path = md5(uniqid(rand(), true));
			// If the path already exists, generate a new one
			while (file_exists($real_dir . $path))
				$path = md5(uniqid(rand(), true));

			// Insert the attachment into the database
			$this->db->insert('attachments', [
				'post' => $post_id,
				'name' => trim($name),
				'size' => $size,
				'path' => $path,
				'system_path' => $real_dir . $path,
			]);

			move_uploaded_file($tmp, $real_dir . $path);
			$uploaded++;
		}

		// return success since the attachments were created
		return ['status' => TRUE, 'uploaded' => $uploaded];
	}

	/**
	 * Delete an attachment
	 * @param $user stdClass the user object
	 * @param $attachment_id string the id of the attachment to delete
	 * @return array
	 */
	function delete_attachment($user, $attachment_id) {
		// Validate the attachment id, it is controlled by the user
		if (empty($attachment_id))
			return ['status' => FALSE, 'message' => 'Missing required fields'];

		// Find the attachment with the given id
		$this->db->where('id', $attachment_id);
		$result = $this->db->get('attachments');

		// If the attachment is not found, return an error
		if ($result->num_rows() == 0)
			return ['status' => FALSE, 'message' => 'Attachment not found'];

		// Get the attachment
		$attachment = $result->row();

		// Find the post that the attachment is attached to
		$post = $this->get_post($user, $attachment->post);
		// If the post is not found, return an error
		if ($post == NULL)
			return ['status' => FALSE, 'message' => 'Post not found'];

		// Check if user has access to delete the attachment
		if (!$user->staff && $post['author'] != $user->id)
			return ['status' => FALSE, 'message' => 'Unauthorized'];

		// Now, delete the file on the server it exists
		if (file_exists($attachment->system_path))
			unlink($attachment->system_path);

		// Delete the attachment from the database
		$this->db->where('id', $attachment->id);
		$this->db->delete('attachments');

		// Return success
		return ['status' => TRUE];
	}

}
