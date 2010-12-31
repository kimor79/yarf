<?php

/**

Copyright (c) 2010, Kimo Rosenbaum and contributors
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the owner nor the names of its contributors
      may be used to endorse or promote products derived from this
      software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

**/

require_once('api_producer/v1/classes/details.php');

class Yarf extends ApiProducerDetails {

	private $archive_path = '';

	public $optional = array(
		'archive' => 'archive',
		'debug' => 'bool',
		'expression' => NULL,
		'node' => '_multi_',
	);

	public $required = array();

	public $sanitize = array(
		'node' => '_multi_',
	);

	private $trim_domain = false;

	public function __construct() {
		parent::__construct();

		$this->output_formats[] = 'png';

		$this->contentType('png', 'image/png');

		if(function_exists('get_config')) {
			$this->trim_domain = get_config('trim_domain');
		}
	}

	/**
	 * Find archive path
	 * @param string $archive
	 * @return string
	 */
	protected function findArchive($archive) {
		if($this->archive_path) {
			return $this->archive_path;
		}

		$paths = explode(PATH_SEPARATOR, get_config('archive', 'paths'));

		foreach($paths as $path) {
			if(is_dir($path . '/' . $archive)) {
				$this->archive_path = $path . '/' . $archive;
				return $path . '/' . $archive;
			}
		}

		return '';
	}

	/**
	 * Generate the date (which may be an archive date)
	 * @param array $options
	 * @return array
	 */
	protected function rrdDate($options = array()) {
		$date = date('r');

		if(array_key_exists('archive', $options)) {
			$file = $this->findArchive($options['archive']) . '/timestamp';
			if(is_file($file)) {
				$time = file_get_contents($file);
				$time = trim($time);
				$date = date('r', $time);
			}
		}

		$rrd = array(
			'COMMENT:' . str_replace(':', '\:', $date) . '\c',
			'COMMENT:\s',
		);

		return $rrd;
	}

	/**
	 * Generate standard rrd options
	 * @param array $nodes
	 * @param array $options
	 * @param string $plugin
	 * @return array
	 */
	protected function rrdHeader($nodes = array(), $options = array(), $plugin = '') {
		$rrd = array('-t');

		$label = 'Combined';

		if(count($nodes) == 1) {
			if($this->trim_domain) {
				$length = 0 - strlen($this->trim_domain);
				$label = substr($nodes[0], 0, $length);
			} else {
				$label = $nodes[0];
			}
		}

		$label .= ': ' . $plugin;

		if(array_key_exists('archive', $options)) {
			$label .= ' - ' . $options['archive'];

			$file = $this->findArchive($options['archive']) . '/timestamp';
			if(is_file($file)) {
				$time = file_get_contents($file);
				$time = trim($time);

				$rrd[] = '--end';
				$rrd[] = $time;
			}
		}
                
		$rrd[] = $label;

		return $rrd;
	}

	/**
	 * Display output as png
	 * @param array $data
	 */
	protected function showOutput_png($data) {
		$im = imagecreate(300, 100);

		$bg = imagecolorallocate($im, 255, 255, 255);
		$textcolor = imagecolorallocate($im, 0, 0, 0);

		$text = $data['status'] . ' - ' . $data['message'];

		imagestring($im, 3, 0, 0, $text, $textcolor);
		imagepng($im);
		imagedestroy($im);
	}

	/**
	 * Validate archive exists
	 * @param string $input
	 * @return bool
	 */
	protected function validateInput_archive($input) {
		if(is_scalar($input)) {
			if($this->findArchive($input)) {
				return true;
			}
		}

		return false;
	}
}

?>
