<?php
/**
 * @package   Essential_Grid
 * @author    ThemePunch <info@themepunch.com>
 * @link      http://www.themepunch.com/essential/
 * @copyright 2023 ThemePunch
 * @since: 3.0.14
 */

if (!defined('ABSPATH')) exit();

class Essential_Grid_Image_Optimization
{
	/**
	 * @var string 
	 */
	public $retina_ext = '@2x.';

	/**
	 * @var array 
	 */
	public $image_sizes = array();
	/**
	 * @var Essential_Grid_Image_Optimization
	 */
	protected static $instance = null;

	/**
	 * @return Essential_Grid_Image_Optimization
	 */
	public static function get_instance()
	{
		if ( is_null( static::$instance ) ) {
			static::$instance = new Essential_Grid_Image_Optimization();
		}

		return static::$instance;
	}

	protected function __construct()
	{
		$this->_add_filters();
		$this->_add_actions();
	}
	
	protected function _add_filters()
	{
		// filter triggered in Essential_Grid_Admin->update_create_grid
		add_filter('essgrid_before_update_create_grid', array($this, 'before_update_create_grid'));
		// filter triggered in Essential_Grid_Item_Skin->output_item_skin
		add_filter('essgrid_set_media_source', array($this, 'set_media_source_retina'), 10, 3);
		// filter triggered in essential-grid/admin/views/grid-create.php
		add_filter('essgrid_grid_form_create_posts', array($this, 'add_hidden_fields'), 10, 2);
		// filter triggered in Essential_Grid_Admin->on_ajax_action
		add_filter('essgrid_on_ajax_action_data', array($this, 'decode_image_size'), 10, 1);
		
	}
	
	protected function _add_actions()
	{
	}

	/**
	 * @return string
	 */
	public function get_retina_ext()
	{
		return $this->retina_ext;
	}

	/**
	 * @param string $retina_ext
	 */
	public function set_retina_ext($retina_ext)
	{
		$this->retina_ext = $retina_ext;
	}

	/**
	 * @return array
	 */
	public function get_image_sizes()
	{
		return $this->image_sizes;
	}

	/**
	 * @param array $image_sizes
	 */
	public function set_image_sizes($image_sizes)
	{
		$this->image_sizes = $image_sizes;
	}

	/**
	 * prepare for  thumbnails generation
	 * @param int $attachment_id
	 * @param array $image_sizes image data from calculate_image_size
	 * @return bool|WP_Error
	 */
	protected function _prepare_generate($attachment_id, $image_sizes)
	{
		if (empty($attachment_id)) {
			return new WP_Error('invalid_media', __('Empty Attachment ID.', ESG_TEXTDOMAIN));
		}

		if (empty($image_sizes)) {
			return new WP_Error('invalid_media_sizes', __('No sizes provided.', ESG_TEXTDOMAIN));
		}
		$this->set_image_sizes($image_sizes);
		
		return true;
	}
	
	/**
	 * generate thumbnails for an image from media library
	 * 
	 * @param int $attachment_id
	 * @param array $image_sizes image data from calculate_image_size
	 * @return bool|WP_Error
	 */
	public function generate_thumbnails($attachment_id, $image_sizes)
	{
		$result = $this->_prepare_generate($attachment_id, $image_sizes);
		if (is_wp_error($result)) {
			return $result;
		}

		do_action('essgrid_before_generate_thumbnails', $attachment_id, $image_sizes);

		$result = $this->generate_images($attachment_id);
		if (is_wp_error($result)) {
			return $result;
		}
		
		do_action('essgrid_generate_thumbnails', $attachment_id, $image_sizes);

		return true;
	}

	/**
	 * delete attachment
	 * @param int $attachment_id
	 * @param array $image_sizes image data from calculate_image_size
	 * @return bool|WP_Error
	 */
	public function delete_attachment($attachment_id, $image_sizes)
	{
		$result = $this->_prepare_generate($attachment_id, $image_sizes);
		if (is_wp_error($result)) {
			return $result;
		}

		return $this->_delete_attachment($attachment_id);
	}

	/**
	 * delete all retina images for attachment
	 * @param int $attachment_id
	 * @return bool|WP_Error
	 */
	protected function _delete_attachment($attachment_id)
	{
		$metadata = wp_get_attachment_metadata($attachment_id);
		$result = $this->validate_metadata($metadata);
		if (is_wp_error($result)) {
			return $result;
		}
		
		$basepath = $this->get_basepath($metadata['file']);
		$sizes = $this->get_image_sizes();
		foreach ($sizes as $attr) {
			$name = $attr['width'] . 'x' . $attr['height'];
			if (isset($metadata['sizes'][$name]['file'])) {
				$this->_delete_file($this->get_file($metadata['sizes'][$name]['file'], $basepath));
			}
			
			$name .= $this->get_retina_ext();
			if (isset($metadata['sizes'][$name]['file'])) {
				$this->_delete_file($this->get_file($metadata['sizes'][$name]['file'], $basepath));
			}
		}
		
		return true;
	}

	/**
	 * @param string $file
	 */
	private function _delete_file($file)
	{
		if (file_exists($file)) {
			unlink($file);
		}
	}
	
	/**
	 * @param string $file
	 * @return string
	 */
	public function get_basepath($file)
	{
		$dir = wp_upload_dir();
		$pathinfo = pathinfo($file);
		return path_join($dir['basedir'], $pathinfo['dirname']);
	}

	/**
	 * get filename and check if file exist
	 * @param string $file
	 * @param string $basepath
	 * @return string|bool
	 */
	public function get_file($file, $basepath)
	{
		$pathinfo = pathinfo($file);
		$filepath = path_join($basepath, $pathinfo['filename'] . '.' . $pathinfo['extension']);
		return file_exists($filepath) ? $filepath : false;
	}

	/**
	 * @param array $m Attachment metadata, should be in following format 
	 * {
	 *     @type int    $width      The width of the attachment.
	 *     @type int    $height     The height of the attachment.
	 *     @type string $file       The file path relative to `wp-content/uploads`.
	 *     @type array  $sizes      Keys are size slugs, each value is an array containing
	 *                              'file', 'width', 'height', and 'mime-type'.
	 *     @type array  $image_meta Image metadata.
	 * }
	 * @return bool|WP_Error 
	 */
	public function validate_metadata($m)
	{
		$result = !empty($m)
			&& isset($m['file'], $m['sizes'], $m['width'], $m['height'])
			&& is_array($m['sizes']);
		
		if (!$result) {
			return new WP_Error('invalid_media_metadata', __('Invalid Media Metadata. Please check attachment ID', ESG_TEXTDOMAIN));
		}
		
		return true;
	}

	/**
	 * get attachment id by url
	 * @param string $url
	 * @return false|int|WP_Post
	 */
	public function get_attachment_id($url)
	{
		if (!is_string($url)) return false;
		
		$dir = wp_upload_dir();
		// baseurl never has a trailing slash
		if (false === strpos($url, $dir['baseurl'] . '/')) {
			// URL points to a place outside of upload directory
			return false;
		}

		$file = basename($url);
		$query = array(
			'post_type' => 'attachment',
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key' => '_wp_attached_file',
					'value' => $file,
					'compare' => 'LIKE',
				),
			)
		);

		// query attachments
		$ids = get_posts($query);
		if (!empty($ids)) {
			foreach ($ids as $id) {
				// first entry of returned array is the URL
				$src = wp_get_attachment_image_src($id, 'full');
				if ($url === array_shift($src))
					return $id;
			}
		}
		$query['meta_query'][0]['key'] = '_wp_attachment_metadata';

		// query attachments again
		$ids = get_posts($query);
		if (empty($ids)) return false;

		foreach ($ids as $id) {
			$meta = wp_get_attachment_metadata($id);
			foreach ($meta['sizes'] as $size => $values) {
				$src = wp_get_attachment_image_src($id, $size);
				if ($values['file'] === $file && $url === array_shift($src))
					return $id;
			}
		}

		return false;
	}

	/**
	 * @param int $attachment_id 
	 * @return array|WP_Error
	 */
	public function generate_images($attachment_id) {
		
		$sizes = $this->get_image_sizes();
		$result = $this->_prepare_generate($attachment_id, $sizes);
		if (is_wp_error($result)) {
			return $result;
		}

		$metadata = wp_get_attachment_metadata($attachment_id);
		$result = $this->validate_metadata($metadata);
		if (is_wp_error($result)) {
			return $result;
		}

		// Load pluggable functions.
		require_once ABSPATH . WPINC . '/pluggable.php';
		require_once ABSPATH . WPINC . '/pluggable-deprecated.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$new_sizes = array();
		foreach ($sizes as $attr ) {
			$size_name = $this->get_size_name($attr);
			$size_name_retina = $this->get_size_name($attr, true);
			$new_sizes[$size_name] = $attr;
			$new_sizes[$size_name_retina] = $attr;
			$new_sizes[$size_name_retina]['width'] *= 2;
			$new_sizes[$size_name_retina]['height'] *= 2;
		}
		
		//do not generate already existing sizes
		foreach ($metadata['sizes'] as $name => $attr) {
			unset($new_sizes[$name]);
		}

		$dir = wp_upload_dir();
		$file = path_join($dir['basedir'], $metadata['file']);
		
		// WordPress will detect if it is a “big” image by checking if its height or
		// its width is above a 'big_image' threshold ( default: 2560 )
		// In this case, the original image name is stored under 'original_image' key
		if (isset($metadata['original_image'])) {
			$basepath = $this->get_basepath($metadata['file']);
			$file = path_join($basepath, $metadata['original_image']);
		}

		return _wp_make_subsizes($new_sizes, $file, $metadata, $attachment_id);
	}

	/**
	 * return metadata size name based on image size array
	 * 
	 * @param array $size
	 * @param bool $is_retina
	 * @return string
	 */
	public function get_size_name($size, $is_retina = false)
	{
		return 'esg-' . $size['width'] . 'x' . $size['height'] . '-' . $size['crop'] 
			. ($is_retina ? $this->get_retina_ext() : '');
	}
	
	/**
	 * calculate optimal image sizes
	 * - desktop large ( default: 1400 )
	 * - tablet landscape ( default: 960 )
	 * - mobile landscape ( default: 640 )
	 *
	 * default width could be overrided in advanced columns mode
	 *
	 * @return array
	 */
	public function calculate_image_size($grid_params)
	{
		$base = new Essential_Grid_Base();

		$layout = $base->getVar($grid_params, 'layout', 'even');
		$ratio_x = $base->getVar($grid_params, 'x-ratio', 4, 'i');
		$ratio_y = $base->getVar($grid_params, 'y-ratio', 3, 'i');
		$ratio_auto = $base->getVar($grid_params, 'auto-ratio', 'true');

		$columns = $base->getVar($grid_params, 'columns');
		$columns = $base->set_basic_colums($columns);

		$columns_width = $base->set_basic_colums_width();

		$columns_advanced = $base->getVar($grid_params, 'columns-advanced', 'off');
		if ($columns_advanced == 'on') {
			$columns_width = $base->getVar($grid_params, 'columns-width');
			$columns_width = $base->set_basic_colums_width($columns_width);

			$columns_advanced_rows = $base->get_advanced_colums($grid_params, $columns);
		}

		$device_columns = $base->get_device_columns();
		$device_sizes = array();

		switch ($layout) {
			case 'cobbles':
				//item size calculated same as even, but item can hold space of up to 3x3 items
				//non-standard item size will use full image
				//cobbles layout do not use advanced columns

			case 'even':
				//items has the same size
				//Grid Item Width = Total Grid Width / Number of Columns
				//Grid Item Height = Grid Item Width * (Items Ratio Y / Items Ratio X)

				$min_items_in_columns = $columns;
				if ('on' === $columns_advanced && 'cobbles' !== $layout) {
					//advanced mode
					//find min column items for each key
					foreach ($columns as $key => $val) {
						foreach ($columns_advanced_rows as $row) {
							if (!empty($row[$key]) && $min_items_in_columns[$key] > $row[$key]) $min_items_in_columns[$key] = $row[$key];
						}
					}
				}

				foreach ($device_columns as $data) {
					$max_width = 0;
					foreach ($data['columns'] as $key) {
						$width = ceil($columns_width[$key] / $min_items_in_columns[$key]);
						if ($width > $max_width) $max_width = $width;
					}

					$device_sizes[$data['device']] = array(
						'width' => (int) $max_width,
						'height' => (int) ceil($max_width * $ratio_y / $ratio_x),
						'crop' => 1,
					);
				}

				break;

			case 'masonry':
				//items has same width, height can vary, have the option to use the image’s original size ratio
				//Grid Item Width = Total Grid Width / Number of Columns
				//Grid Item Height = Grid Item Width * (Image Original Height / Image Original Width) - auto ratio enabled
				//Grid Item Height = Grid Item Width * (Items Ratio Y / Items Ratio X) - no auto ratio
				//masonry layout do not use advanced columns

				foreach ($device_columns as $data) {
					$max_width = 0;
					foreach ($data['columns'] as $key) {
						$width = ceil($columns_width[$key] / $columns[$key]);
						if ($width > $max_width) $max_width = $width;
					}

					$height = 'true' === $ratio_auto ? 0 : ceil($max_width * $ratio_y / $ratio_x);
					$crop = 'true' === $ratio_auto ? 0 : 1;
					$device_sizes[$data['device']] = array(
						'width' => (int) $max_width,
						'height' => (int) $height,
						'crop' => $crop,
					);
				}

				break;

			default:
				//throw error about not supported layout?
				return $device_sizes;
		}
		
		return $device_sizes;
	}

	/**
	 * handler for filter triggered in Essential_Grid_Admin::update_create_grid
	 * check if smart image size enabled
	 * calculate image sizes and add it to grid params
	 * 
	 * @param array $grid
	 * @return array
	 */
	public function before_update_create_grid($grid)
	{
		$image_source_smart = Essential_Grid_Base::getVar($grid, array('postparams', 'image-source-smart'), 'off');
		if ('on' === $image_source_smart) {
			//calculate optimal image sizes
			$grid['postparams']['image-source-smart-size'] = $this->calculate_image_size($grid['params']);
		}
		
		return $grid;
	}
	
	/**
	 * handler for filter triggered in Essential_Grid_Item_Skin->output_item_skin
	 * check if there is retina image and add it to img attributes
	 * $order: 
	 * 
	 * @param string $echo_media img html code
	 * @param string $order media source name ( featured-image, content-image, alternate-image )
	 * @param array $media_sources all available media sources for the item
	 * @return string
	 */
	public function set_media_source_retina($echo_media, $order, $media_sources)
	{
		if (!empty($media_sources[$order . '-' . $this->get_retina_ext()])) {
			$echo_media = str_replace('>', ' data-retina="'.$media_sources[$order . '-' . $this->get_retina_ext()].'" >', $echo_media);
		}
		
		return $echo_media;
	}

	/**
	 * @param int $attachment_id
	 * @param string $image_type
	 * @param array $image_size
	 * @return array  x1 - normal image, x2 - retina image
	 */
	public function get_media_source_src($attachment_id, $image_type, $image_size = array())
	{
		$result = array(
			'x1' => false,
			'x2' => false,
		);
		if (is_array($image_size)) {
			$device = Essential_Grid_Base::detect_device();
			if (!empty($image_size[$device])) {
				$size_name = $this->get_size_name($image_size[$device]);
				$size_name_retina = $this->get_size_name($image_size[$device], true);
				$result['x1'] = wp_get_attachment_image_src($attachment_id, $size_name);
				$result['x2'] = wp_get_attachment_image_src($attachment_id, $size_name_retina);
			}
		}

		if (empty($result['x1'])) {
			$result['x1'] = wp_get_attachment_image_src($attachment_id, $image_type);
		}

		return $result;
	}

	/**
	 * add image size as hidden field to grid create form
	 * otherwise it won't be passed to render preview html function
	 * 
	 * @param string $str
	 * @param mixed $grid
	 * @return string
	 */
	public function add_hidden_fields($str, $grid)
	{
		$image_source_smart_size = Essential_Grid_Base::getVar($grid, array('postparams', 'image-source-smart-size'), false);
		return $str . '<input type="hidden" name="image-source-smart-size" value="'. esc_attr(json_encode($image_source_smart_size)) .'" />';
	}

	/**
	 * filter triggered in Essential_Grid_Admin->on_ajax_action
	 * decode image size from json string
	 * 
	 * @param array $data
	 * @return array
	 */
	public function decode_image_size($data)
	{
		if (!empty($data['postparams']['image-source-smart-size'])) {
			$data['postparams']['image-source-smart-size'] = json_decode($data['postparams']['image-source-smart-size'], true);
		}
		
		return $data;
	}



}

Essential_Grid_Image_Optimization::get_instance();
