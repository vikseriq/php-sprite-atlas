<?php

/**
 * Class SpriteAtlasGenerator
 *
 * Generate sprite atlas with css rule from images array
 *
 * @author w <vikseriq@gmail.com>
 * @license MIT
 */
class SpriteAtlasGenerator {

	var $config;
	var $sizes;

	/**
	 * SpriteAtlasGenerator constructor.
	 *
	 * @param array $config Configuration options:
	 *      'out_css'       string  Full path to result css rules file
	 *      'out_image'     string  Full path to result atlas image
	 *      'out_format'    string  Atlas image format: jpg/png
	 *      'css_class'     string  Class name for sprites.
	 *                              By default sprite presented as `css-class``css-class`-`sprite-key`.
	 *                              For `css-class` = '.language', 'sprite-key' = 'php'
	 *                              rule will be `.language.language-php { ... }`
	 *      'css_path'      string  Relative path to sprite image in css rule
	 *      'cell_padding'  int     Atlas cells gap, starting from top left corner
	 *      'cell_width'    int     Atlas cell width
	 *      'cell_height'   int     Atlas cell height
	 *      'background'    array   Atlas background in RGB format: [0xFF, 0xFF, 0xFF] for white
	 */
	public function __construct($config){
		$this->config = $config;
	}

	/**
	 * Generate atlas from images
	 *
	 * @param array $paths List of images with css rule suffixes as keys
	 * @return bool
	 * @throws Error
	 */
	public function generate($paths){
		ksort($paths);
		$this->sizes = $this->get_map_size(count($paths));

		$atlas = $this->create_atlas();
		if (!$atlas)
			throw new Error('Can not create atlas');

		$css_places = [];

		$i = 0;
		foreach ($paths as $name => $path){
			$x = $this->config['cell_padding']
				+ ($this->config['cell_width'] + $this->config['cell_padding'])
				* ($i % $this->sizes['cols']);
			$y = $this->config['cell_padding']
				+ ($this->config['cell_height'] + $this->config['cell_padding'])
				* floor($i / $this->sizes['cols']);

			if ($this->add_to_atlas($atlas, $path, $x, $y)){
				$css_places[$name] = ['x' => $x, 'y' => $y];
			}

			$i++;
		}

		if (!$this->save_atlas($atlas))
			throw new Error('Can not save atlas');

		$this->save_css($css_places);

		return true;
	}

	/**
	 * Calculate atlas size
	 *
	 * @param $n
	 * @return array
	 */
	private function get_map_size($n){
		$w = ceil(sqrt($n));
		return [
			'rows' => $w, 'cols' => $w,
			'width' => $w * ($this->config['cell_width'] + $this->config['cell_padding']),
			'height' => $w * ($this->config['cell_height'] + $this->config['cell_padding'])
		];
	}

	/**
	 * Generate empty atlas with filled background
	 *
	 * @return resource
	 */
	private function create_atlas(){
		$atlas = imagecreatetruecolor($this->sizes['width'], $this->sizes['height']);
		$bg_color = imagecolorallocate($atlas,
			$this->config['background'][0],
			$this->config['background'][1],
			$this->config['background'][2]
		);
		imagefill($atlas, 0, 0, $bg_color);
		return $atlas;
	}

	/**
	 * Write out atlas as image file
	 *
	 * @param $atlas
	 * @return bool
	 */
	private function save_atlas($atlas){
		switch ($this->config['out-format'] == 'jpg'){
			case 'png':
				return imagepng($atlas, $this->config['out_image'], 5, PNG_ALL_FILTERS);
				break;
			case 'jpg':
			default:
				imageinterlace($atlas, 1);
				return imagejpeg($atlas, $this->config['out_image'], 85);
		}
	}

	/**
	 * Generate and save css rules
	 *
	 * @param $css_places
	 */
	private function save_css($css_places){
		$css = sprintf("%s { background: url('%s') no-repeat %dpx %dpx; }".PHP_EOL,
			$this->config['css_class'],
			$this->config['css_path'],
			$this->sizes['width'],
			$this->sizes['height']
		);

		foreach ($css_places as $place => $position){
			// sanitize name
			$place = preg_replace("/[^a-z0-9_]/", '_', strtolower($place));

			// generate css rule
			$css .= sprintf('%s%s-%s { background-position: -%dpx -%dpx; }'.PHP_EOL,
				$this->config['css_class'],
				$this->config['css_class'],
				$place,
				$position['x'],
				$position['y']
			);
		}

		file_put_contents($this->config['out_css'], $css);
	}

	/**
	 * Load and image from $image_path to $atlas on specified cell coordinates
	 *
	 * @param $atlas
	 * @param $image_path
	 * @param $atlas_x
	 * @param $atlas_y
	 * @return bool true on success
	 */
	private function add_to_atlas($atlas, $image_path, $atlas_x, $atlas_y){
		// check file
		if (!file_exists($image_path))
			return false;

		// load
		$original = null;
		$image_size = getimagesize($image_path);
		switch ($image_size[2]){
			case 1:
				$original = imagecreatefromgif($image_path);
				break;
			case 2:
				$original = imagecreatefromjpeg($image_path);
				break;
			case 3:
				$original = imagecreatefrompng($image_path);
				break;
			default:
				break;
		}
		if (!$original)
			return false;

		// calculate dimensions
		$ox = imagesx($original);
		$oy = imagesy($original);
		$rx = $this->config['cell_width'];
		$ry = $this->config['cell_height'];
		$dst_w = $rx;
		$dst_h = $ry;

		// resize
		if ($rx / $ox < $ry / $oy){
			$dst_h = (int)($rx * $oy / $ox);
		} else {
			$dst_w = (int)($ry * $ox / $oy);
		}

		$dst_x = $atlas_x + ($rx - $dst_w) / 2;
		$dst_y = $atlas_y + ($ry - $dst_h) / 2;

		// place image to altas
		$ok = imagecopyresampled($atlas, $original, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $ox, $oy);

		// clean up
		imagedestroy($original);

		return $ok;
	}

}