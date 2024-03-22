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
}
