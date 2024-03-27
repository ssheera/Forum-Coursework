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

	function get_posts($category_id)
	{
		$this->db->where('category', $category_id);
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

	function create_post($user, $category, $tags, $keywords, $title, $content, $attachments, $parent)
	{
		if (empty($category) || empty($title) || empty($content)) {
			return ['status' => FALSE, 'message' => 'Missing required fields'];
		}

		$uid = $user->id;
		$now = date('Y-m-d H:i:s');

		$categories = $this->get_categories();

		try {
			$cat = $categories[$category - 1];
		} catch (Exception $e) {
			return ['status' => FALSE, 'message' => 'Invalid category'];
		}

		if (!$user->staff) {
			if ($cat->locked) {
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
			'edited' => $now
		];

		$this->db->insert('posts', $data);
		$id = $this->db->insert_id();

		for ($i = 0; $i < count($attachments); $i++) {
			$attachment = $attachments[$i];
			$attachment['post'] = $id;

			$fileName = md5(uniqid(rand(), true));
			$directory = 'public/' . md5(uniqid(rand(), true));
			$attachment['path'] = $directory . '/' . $fileName;
			$hex = $attachment['data'];
			$data = hex2bin($hex);
			unset($attachment['data']);
			$this->db->insert('attachments', $attachment);

			if (!file_exists($directory)) mkdir($directory, 0777, TRUE);
			file_put_contents($attachment['path'], $data);
		}

		return ['status' => TRUE, 'id' => $id];
	}
}
