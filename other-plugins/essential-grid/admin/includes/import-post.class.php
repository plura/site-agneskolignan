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

if (!class_exists('PunchPost')) {
	class PunchPost
	{

		/**
		 * @var string
		 */
		public $TP_title;

		/**
		 * @var string
		 */
		public $TP_type;

		/**
		 * @var string
		 */
		public $TP_content;

		/**
		 * @var array
		 */
		public $TP_category;

		/**
		 * @var array
		 */
		public $TP_taxonomy;

		/**
		 * @var string
		 */
		public $TP_terms;

		/**
		 * @var string
		 */
		public $TP_template;

		/**
		 * @var string
		 */
		public $TP_slug;

		/**
		 * @var string
		 */
		public $TP_date;

		/**
		 * @var array
		 */
		public $TP_post_tags;

		/**
		 * @var array
		 */
		public $TP_meta;

		/**
		 * @var int
		 */
		public $TP_auth_id;

		/**
		 * @var string 
		 */
		public $TP_status = "publish";

		/**
		 * @var WP_Post|array|null
		 */
		public $TP_current_post;

		/**
		 * @var int|WP_Error The post ID on success. The value 0 or WP_Error on failure
		 */
		public $TP_current_post_id;

		/**
		 * @var string|false The permalink URL
		 */
		public $TP_current_post_permalink;

		/**
		 * @var array 
		 */
		protected $errors = array();
		
		public function get_page_by_title($title, $object = 'OBJECT', $type = 'page')
		{
			$posts = get_posts(
				array(
					'post_type'              => $type,
					'title'                  => $title,
					'post_status'            => 'all',
					'numberposts'            => 1,
					'update_post_term_cache' => false,
					'update_post_meta_cache' => false,
					'orderby'                => 'post_date ID',
					'order'                  => 'ASC',
				)
			);

			$page_got_by_title = null;

			if ( ! empty( $posts ) ) {
				$page_got_by_title = $posts[0];
			}

			return $page_got_by_title;
		}

		// Creation functions
		public function create()
		{
			$cat = apply_filters('essgrid_PunchPost_category', 'essential_grid_category');

			$error_obj = "";
			if (isset($this->TP_title)) {
				$post = $this->get_page_by_title($this->TP_title, 'OBJECT', $this->TP_type);

				$post_data = array(
					'post_title' => wp_strip_all_tags($this->TP_title),
					'post_name' => $this->TP_slug,
					'post_content' => $this->TP_content,
					'post_status' => $this->TP_status,
					'post_type' => $this->TP_type,
					'post_author' => $this->TP_auth_id,
					'post_category' => $this->TP_category,
					'page_template' => $this->TP_template,
					'post_date' => $this->TP_date
				);

				if (!isset($post)) {

					$this->TP_current_post_id = wp_insert_post($post_data, $error_obj);
					$this->TP_current_post = get_post((integer)$this->TP_current_post_id, 'OBJECT');
					$this->TP_current_post_permalink = get_permalink((integer)$this->TP_current_post_id);

					$terms = array();
					$terms_array = explode(',', $this->TP_terms);
					if (!empty($terms_array)) {
						foreach ($terms_array as $singleterm) {
							$term = get_term_by('slug', $singleterm, $cat);
							if (empty($term) || !isset($term->term_id)) continue;
							$terms[] = $term->term_id;
						}
					}
					wp_set_post_terms($this->TP_current_post_id, $terms, $cat);

					if (!empty($this->TP_post_tags)) {
						wp_set_post_terms($this->TP_current_post_id, $this->TP_post_tags);
					}

					foreach ($this->TP_meta as $meta_key => $meta_value) {
						if ($meta_key == 'eg-clients-icon' && !empty($meta_value)) {
							$attach_id = $this->create_image('client.png');
							$meta_value = $attach_id;
						}
						if ($meta_key == 'eg-clients-icon-dark' && !empty($meta_value)) {
							$attach_id = $this->create_image('client_dark.png');
							$meta_value = $attach_id;
						}
						update_post_meta($this->TP_current_post_id, $meta_key, $meta_value);
					}

					global $imagenr;
					if ($imagenr == 10) $imagenr = 1;
					$attach_id = $this->create_image('demoimage' . $imagenr++ . '.jpg');
					set_post_thumbnail($this->TP_current_post_id, $attach_id);


					return $this->TP_current_post_id;
				} else {
					$this->errors[] = 'That page already exists. Try updating instead. Control passed to the update() function.';
					return FALSE;
				}
			} else {
				$this->errors[] = 'Title has not been set.';
				return FALSE;
			}
		}

		public function create_image($file)
		{
			$image_url = ESG_PLUGIN_PATH . 'admin/assets/images/' . $file;
			$upload_dir = wp_upload_dir();
			$image_data = file_get_contents($image_url);
			$filename = basename($image_url);
			if (wp_mkdir_p($upload_dir['path']))
				$file = $upload_dir['path'] . '/' . $filename;
			else
				$file = $upload_dir['basedir'] . '/' . $filename;
			file_put_contents($file, $image_data);

			$wp_filetype = wp_check_filetype($filename);
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => sanitize_file_name($filename),
				'post_content' => '',
				'post_status' => 'inherit'
			);
			$attach_id = wp_insert_attachment($attachment, $file, $this->TP_current_post_id);
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $file);
			wp_update_attachment_metadata($attach_id, $attach_data);
			return $attach_id;
		}

		/**
		 * @param string $name
		 * @return string
		 */
		public function set_title($name)
		{
			$this->TP_title = $name;
			return $this->TP_title;
		}

		/**
		 * @param string $type
		 * @return string
		 */
		public function set_type($type)
		{
			$this->TP_type = $type;
			return $this->TP_type;
		}

		/**
		 * @param string $content
		 * @return string
		 */
		public function set_content($content)
		{
			$this->TP_content = $content;
			return $this->TP_content;
		}

		/**
		 * @param int $auth_id
		 * @return int
		 */
		public function set_author_id($auth_id)
		{
			$this->TP_auth_id = $auth_id;
			return $this->TP_auth_id;
		}


		// SET POST'S STATE	
		public function set_post_state($content)
		{
			$this->TP_status = $content;
			return $this->TP_status;
		}

		/**
		 * @param array $option_array
		 * @return array
		 */
		public function set_post_meta($option_array)
		{
			$this->TP_meta = $option_array;
			return $this->TP_meta;
		}

		/**
		 * @param string $date
		 * @return string
		 */
		public function set_date($date)
		{
			$this->TP_date = $date;
			return $this->TP_date;
		}

		/**
		 * @param string $slug
		 * @return false|string
		 */
		public function set_post_slug($slug)
		{
			$args = array('name' => $slug);
			$posts_query = get_posts($args);
			if (!get_posts($args) && !get_page_by_path($this->TP_slug)) {
				$this->TP_slug = $slug;
				return $this->TP_slug;
			} else {
				$this->errors[] = 'Slug already in use.';
				return FALSE;
			}
		}

		// SET PAGE TEMPLATE
		public function set_page_template($content)
		{
			if ($this->TP_type == "page") {
				$this->TP_template = $content;
				return $this->TP_template;
			} else {
				$this->errors[] = 'You can only use templates for pages.';
				return FALSE;
			}
		}

		/**
		 * @param array $tax
		 * @return array
		 */	
		public function set_tax($tax)
		{
			$this->TP_taxonomy = $tax;
			return $this->TP_taxonomy;
		}

		/**
		 * @param string $terms
		 * @return string
		 */
		public function set_tax_terms($terms)
		{
			$this->TP_terms = $terms;
			return $this->TP_terms;
		}

		/**
		 * @param array $tags
		 * @return array
		 */
		public function set_post_tags($tags)
		{
			$this->TP_post_tags = $tags;
			return $this->TP_post_tags;
		}

		public function import_taxonomies($terms)
		{
			$cat = apply_filters('essgrid_PunchPost_category', 'essential_grid_category');

			$terms = json_decode($terms, true);
			//print_r($terms);die;
			foreach ($terms as $term) {
				if (!term_exists($term['name'], $cat)) {
					wp_insert_term($term['name'], $cat, array('description' => $term['category_description'], 'slug' => $term['slug']));
				}
			}
		}

		// ADD CATEGORY IDs TO THE CATEGORIES ARRAY
		public function add_category($IDs)
		{
			if (is_array($IDs)) {
				foreach ($IDs as $id) {
					if (is_int($id)) {
						$this->TP_category[] = $id;
					} else {
						$this->errors[] = '<b>' . $id . '</b> is not a valid integer input.';
						return FALSE;
					}
				}
			} else {
				$this->errors[] = 'Input specified is not a valid array.';
				return FALSE;
			}
		}

		public function prettyprint($content)
		{
			echo "<pre>";
			print_r($content);
			echo "</pre>";
		}

	}
}
