<?php

class Post extends CI_Model
{

	function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	function get_categories()
	{
		$this->db->order_by('id', 'ASC');
		$result = $this->db->get('category');
		return $result->result_array();
	}

	function get_posts($category_id, $f_author, $f_category)
	{
		$this->db->where('category', $category_id);

		if ($f_author)
			$this->db->where('author', $f_author);
		if ($f_category)
			$this->db->where('category', $f_category);

		$this->db->order_by('updated', 'DESC');
		$result = $this->db->get('posts');

		$posts = [];

		for ($i = 0; $i < $result->num_rows(); $i++) {
			$post = $result->row($i);

			$this->db->select('username');
			$this->db->where('id', $post->author);
			$results = $this->db->get('user');
			if ($results->num_rows() == 0) {
				$author = 'unknown';
			} else {
				$author = $results->row()->username;
			}

			$replies = $this->db->get_where('posts', ['parent' => $post->id])->num_rows();
			$posts[] = [
				'id' => $post->id,
				'title' => $post->title,
				'author' => $author,
				'replies' => $replies,
				'updated' => $post->updated,
				'parent' => $post->parent
			];
		}

		return $posts;
	}

	function get_post($user, $post_id)
	{

		$this->db->where('id', $post_id);
		$result = $this->db->get('posts');

		if ($result->num_rows() == 0) {
			return NULL;
		}

		$post = $result->row();

		$this->db->select('username');
		$this->db->where('id', $post->author);
		$results = $this->db->get('user');
		if ($results->num_rows() == 0) {
			$author = 'unknown';
		} else {
			$author = $results->row()->username;
		}

		$category = $this->db->get_where('category', ['id' => $post->category])->row();

		$replies = [];

		$this->db->where('parent', $post_id);
		$this->db->order_by('created', 'ASC');
		$results = $this->db->get('posts');

		for ($i = 0; $i < $results->num_rows(); $i++) {
			$reply = $results->row($i);
			$replies[] = $this->get_post($user, $reply->id);
		}

		return [
			'id' => $post->id,
			'category' => $post->category,
			'title' => $post->title,
			'author' => $post->author,
			'content' => base64_decode($post->content),
			'username' => $author,
			'updated' => $post->updated,
			'parent' => $post->parent,
			'replies' => $replies,
			'reply' => !$category->locked,
			'action' => $user && ($user->staff || $post->author == $user->id),
			'attachments' => $this->get_attachments($post_id)
		];
	}

	function create_post($user, $category, $tags, $keywords, $title, $content, $parent)
	{

		if (empty($category) || empty($title) || empty($content)) {
			return ['status' => FALSE, 'message' => 'Missing required fields'];
		}

		$content = base64_encode($content);

		$uid = $user->id;
		$now = date('Y-m-d H:i:s');

		$categories = $this->get_categories();

		try {
			for ($i = 0; $i < count($categories); $i++) {
				if ($categories[$i]['id'] == $category) {
					$cat = $categories[$i];
					break;
				}
			}
		} catch (Exception $e) {
			return ['status' => FALSE, 'message' => 'Invalid category'];
		}

		if (!$user->staff) {
			if ($cat['locked']) {
				return ['status' => FALSE, 'message' => 'Category is locked'];
			}
		}

		$data = [
			'author' => $uid,
			'category' => $category,
			'keywords' => $keywords,
			'title' => $title,
			'content' => $content,
			'parent' => $parent,
			'created' => $now,
			'updated' => $now,
			'edited' => $now,
		];

		$this->db->insert('posts', $data);
		$id = $this->db->insert_id();

		return ['status' => TRUE, 'id' => $id];
	}

	function delete_post($user, $post_id) {
		$this->db->where('id', $post_id);
		$result = $this->db->get('posts');

		if ($result->num_rows() == 0) {
			return ['status' => FALSE, 'message' => 'Post not found'];
		}

		$post = $result->row();

		if ($post->author != $user->id && !$user->staff) {
			return ['status' => FALSE, 'message' => 'Unauthorized'];
		}

		$this->db->where('parent', $post_id);
		$replies = $this->db->get('posts');
		for ($i = 0; $i < $replies->num_rows(); $i++) {
			$reply = $replies->row($i);
			$this->db->where('id', $reply->id);
			$this->db->update('posts', ['parent' => $post->parent]);
		}

		$this->db->where('post', $post_id);
		$attachments = $this->db->get('attachments');

		for ($i = 0; $i < $attachments->num_rows(); $i++) {
			$attachment = $attachments->row($i);
			if (file_exists($attachment->path))
				unlink($attachment->path);
		}

		$this->db->where('id', $post_id);
		$this->db->delete('posts');

		return ['status' => TRUE];
	}

	function get_attachments($post_id) {
		$this->db->where('post', $post_id);
		$result = $this->db->get('attachments');
		$attachments = [];
		for ($i = 0; $i < $result->num_rows(); $i++) {
			$attachment = $result->row($i);
			$attachments[] = [
				'name' => $attachment->name,
				'size' => $attachment->size,
				'path' => $attachment->path
			];
		}
		return $attachments;
	}

	function create_attachment($post_id, $name, $size, $data) {

		$path = 'public/' . md5(uniqid(rand(), true));

		while (file_exists($path)) {
			$path = 'public/' . md5(uniqid(rand(), true));
		}

		$data = base64_decode($data);
		$data = hex2bin($data);
		$this->db->insert('attachments', [
			'post' => $post_id,
			'name' => $name,
			'size' => $size,
			'path' => $path
		]);

		file_put_contents($path, $data);
	}
}
