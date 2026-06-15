<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 */

if (!defined('ABSPATH')) exit();

if (!isset($wp_rewrite))
	$wp_rewrite = new WP_Rewrite();

if (!class_exists('PunchPort')) {
	class PunchPort
	{
		/**
		 * @var false|string  a JSON encoded string
		 */
		public $TP_pages;

		/**
		 * @var array
		 */
		public $TP_pages_array = array();

		/**
		 * @var false|string  a JSON encoded string
		 */
		public $TP_posts;

		/**
		 * @var array
		 */
		public $TP_posts_array = array();

		/**
		 * @var array
		 */
		public $TP_categories_array = array();

		/**
		 * @var false|string  a JSON encoded string
		 */
		public $TP_posts_categories;

		/**
		 * @var false|string  a JSON encoded string
		 */
		public $TP_tags;

		/**
		 * @var array
		 */
		public $TP_tags_array = array();

		/**
		 * @var false|string  a JSON encoded string
		 */
		public $TP_import_posts;

		public function set_tp_import_posts($json)
		{
			$this->TP_import_posts = $json;
		}

		/**
		 * @return void
		 */
		public function import_custom_posts()
		{
			$cat = apply_filters('essgrid_PunchPost_category', 'essential_grid_category');
			$type = apply_filters('essgrid_PunchPost_custom_post_type', 'essential_grid');

			$posts_array = json_decode($this->TP_import_posts, true);
			foreach ($posts_array as $post) {
				set_time_limit(30);
				$newPost = new PunchPost;
				
				//Standards
				$newPost->set_title($post["post_title"]);
				$newPost->set_type($type);
				$newPost->set_content($post["post_content"]);
				$newPost->set_post_state("publish");
				
				//Categories
				$newPost->set_tax($cat);
				$newPost->set_tax_terms($post['post_categories']);
				
				//Tags
				if (!empty($post['post_tags'])) $newPost->set_post_tags($post['post_tags']);
				
				//Meta
				$newPost->set_post_meta($post["post_options"]);
				$newPost->create();
			}
		}

		/**
		 * @return void
		 */
		public function export_pages()
		{
			$pages = get_pages();
			foreach ($pages as $page_data) {
				$this->TP_pages_array[$page_data->ID]['post_title'] = $page_data->post_title;
				$this->TP_pages_array[$page_data->ID]['post_author'] = $page_data->post_author;
				$this->TP_pages_array[$page_data->ID]['post_date'] = $page_data->post_date;
				$this->TP_pages_array[$page_data->ID]['post_excerpt'] = $page_data->post_excerpt;
				$this->TP_pages_array[$page_data->ID]['post_status'] = $page_data->post_status;
				$this->TP_pages_array[$page_data->ID]['post_parent'] = $page_data->post_parent;
				$this->TP_pages_array[$page_data->ID]['post_content'] = apply_filters('the_content', $page_data->post_content);
			}
			$this->TP_pages = json_encode($this->TP_pages_array);
		}

		public function export_post_categories()
		{
			$categories = get_categories();
			foreach ($categories as $category) {
				$this->TP_categories_array[$category->term_id]['name'] = $category->name;
				$this->TP_categories_array[$category->term_id]['slug'] = $category->slug;
				$this->TP_categories_array[$category->term_id]['term_group'] = $category->term_group;
				$this->TP_categories_array[$category->term_id]['term_taxonomy_id'] = $category->term_taxonomy_id;
				$this->TP_categories_array[$category->term_id]['taxonomy'] = $category->taxonomy;
				$this->TP_categories_array[$category->term_id]['description'] = $category->description;
				$this->TP_categories_array[$category->term_id]['parent'] = $category->parent;
				$this->TP_categories_array[$category->term_id]['count'] = $category->count;
				$this->TP_categories_array[$category->term_id]['cat_ID'] = $category->cat_ID;
				$this->TP_categories_array[$category->term_id]['category_count'] = $category->category_count;
				$this->TP_categories_array[$category->term_id]['category_description'] = $category->category_description;
				$this->TP_categories_array[$category->term_id]['cat_name'] = $category->cat_name;
				$this->TP_categories_array[$category->term_id]['category_nicename'] = $category->category_nicename;
				$this->TP_categories_array[$category->term_id]['category_parent'] = $category->category_parent;
			}
			$this->TP_posts_categories = json_encode($this->TP_categories_array);
		}

		public function export_tags()
		{
			$tags = get_tags();
			foreach ($tags as $tag) {
				$this->TP_tags_array[$tag->term_id]['name'] = $tag->name;
				$this->TP_tags_array[$tag->term_id]['slug'] = $tag->slug;
				$this->TP_tags_array[$tag->term_id]['term_group'] = $tag->term_group;
				$this->TP_tags_array[$tag->term_id]['term_taxonomy_id'] = $tag->term_taxonomy_id;
				$this->TP_tags_array[$tag->term_id]['taxonomy'] = $tag->taxonomy;
				$this->TP_tags_array[$tag->term_id]['description'] = $tag->description;
				$this->TP_tags_array[$tag->term_id]['parent'] = $tag->parent;
			}
			$this->TP_tags = json_encode($this->TP_tags_array);
		}

		public function export_custom_posts($custom_post_type)
		{
			$args = array(
				'post_type' => $custom_post_type,
				'posts_per_page' => 99999,
				'suppress_filters' => 0
			);
			$list = get_posts($args);
			foreach ($list as $post_data) :
				$this->_fill_posts_array($post_data);
				$this->TP_posts_array[$post_data->ID]['post_options'] = $this->all_get_options($post_data->ID);
			endforeach;
			$this->TP_posts = json_encode($this->TP_posts_array);
		}

		public function all_get_options($id = 0)
		{
			$result = array();
			
			if ($id == 0) {
				global $wp_query;
				$content_array = $wp_query->get_queried_object();
				if (isset($content_array->ID)) {
					$id = $content_array->ID;
				}
			}

			$first_array = get_post_custom_keys($id);

			if (!empty($first_array)) {
				foreach ($first_array as $value) {
					$second_array[$value] = get_post_meta($id, $value);
					foreach ($second_array as $second_key => $second_value) {
						$result[$second_key] = $second_value[0];
					}
				}
			}

			return $result;
		}

		public function export_posts()
		{
			$args = array(
				'posts_per_page' => 99999,
				'suppress_filters' => 0
			);
			$posts = get_posts($args);
			$counter = 1;
			foreach ($posts as $post_data) {
				if ($counter++ > 30) {
					$this->_fill_posts_array($post_data);
					
					//Categories
					$categories = get_the_category($post_data->ID);
					$separator = ',';
					$output = '';
					if ($categories) {
						foreach ($categories as $category) {
							$output .= $category->slug . $separator;
						}
						$this->TP_posts_array[$post_data->ID]['post_categories'] = trim($output, $separator);
					}
					
					//Tags
					$posttags = get_the_tags($post_data->ID);
					$output = '';
					if ($posttags) {
						foreach ($posttags as $tag) {
							$output .= $tag->slug . $separator;
						}
						$this->TP_posts_array[$post_data->ID]['post_tags'] = trim($output, $separator);
					}
					
					//Options
					$this->TP_posts_array[$post_data->ID]['post_options'] = $this->all_get_options($post_data->ID);
				}
			}
			$this->TP_posts = json_encode($this->TP_posts_array);
		}

		public function save_export()
		{

		}

		public function pretty_print($content)
		{
			echo "<pre><code>";
			print_r($content);
			echo "</code></pre>";
			echo "<hr>";
		}

		/**
		 * @param object $post_data
		 * @return void
		 */
		protected function _fill_posts_array($post_data)
		{
			$this->TP_posts_array[$post_data->ID]['post_title'] = $post_data->post_title;
			$this->TP_posts_array[$post_data->ID]['post_author'] = $post_data->post_author;
			$this->TP_posts_array[$post_data->ID]['post_date'] = $post_data->post_date;
			$this->TP_posts_array[$post_data->ID]['post_excerpt'] = $post_data->post_excerpt;
			$this->TP_posts_array[$post_data->ID]['post_status'] = $post_data->post_status;
			$this->TP_posts_array[$post_data->ID]['post_parent'] = $post_data->post_parent;
			$this->TP_posts_array[$post_data->ID]['post_content'] = apply_filters('the_content', $post_data->post_content);
		}

	}
}
