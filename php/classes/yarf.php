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
	protected $base_paths = array();
	protected $config = array();

	protected $datasources = array(
		array(
			'value' => array(
				'area' => true,
				'color' => '#3020ee',
				'legend' => '',
				'line' => 1,
				'scale' => '',
			),
		),
	);

	public $optional = array(
		'archive' => 'archive',
		'debug' => 'bool',
		'expression' => NULL,
		'node' => '_multi_',
		't_unit' => 'timeunit',
		't_val' => 'digit',
	);

	protected $paths = array();
	public $request = array();
	public $required = array();
	protected $rrd_options = array();

	public $sanitize = array(
		'node' => '_multi_',
	);

	protected $title = 'Generic Graph';
	private $trim_domain = false;

	public function __construct($options = array()) {
		parent::__construct();

		$this->output_formats[] = 'png';

		$this->contentType('png', 'image/png');

		$this->base_paths = explode(PATH_SEPARATOR, get_config('rrd_paths'));
		$this->trim_domain = get_config('trim_domain');

		if(array_key_exists('config', $options)) {
			$this->config = $this->setDetails(
				$this->config, $options['config']);
		}

		if(array_key_exists('datasources', $options)) {
			$this->datasources = $this->setDetails(
				$this->datasources, $options['datasources']);
		}

		if(array_key_exists('paths', $options)) {
			$this->paths = $this->setDetails($this->paths,
				$options['paths']);
		}

		if(array_key_exists('rrd', $options)) {
			$this->rrd_options = $this->setDetails(
				$this->rrd_options, $options['rrd']);
		}

		if(array_key_exists('title', $options)) {
			$this->title = $options['title'];
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
	 * Get a config option
	 * @param string $key
	 * @return mixed
	 */
	public function getConfig($key = '') {
		if(array_key_exists($key, $this->config)) {
			return $this->config[$key];
		}

		return NULL;
	}

	/**
	 * Get the datasources
	 * @return array
	 */
	public function getDS() {
		return $this->datasources;
	}

	/**
	 * Get the title
	 * @param int $count
	 * @return string
	 */
	public function getTitle($count = 0) {
		$title = $this->title;

		if($this->getConfig('combined_average')) {
			if($count > 1) {
				$title .= ' (Avg)';
			}
		}

		return $title;
	}

	/**
	 * Parse 'expression' based on nodegroups config
	 * @param string $expression
	 * @param array
	 */
	public function parseNodes($expression) {
		global $ngclient;

		$nodes = array();

		if(isset($ngclient)) {
			$parsed = $ngclient->getNodesFromExpression($expression);
			if(!empty($parsed)) {
				$nodes = $parsed;
			}

			return $nodes;
		}

		if(get_config('nodes', 'file')) {
			return $nodes;
		}

		if(get_config('nodes', 'list')) {
			return $nodes;
		}

		$nodes = preg_split('/[^\w.-]+/', $expression);

		return $nodes;
	}

	/**
	 * Generate the date (which may be an archive date)
	 * @return array
	 */
	public function rrdDate() {
		$date = date('r');

		if(array_key_exists('archive', $this->request)) {
			$file = $this->findArchive($this->request['archive']) . '/timestamp';
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
	 * Generate DEF and CDEF for a node
	 * @param string $node
	 * @param array $files
	 * @param array $sources
	 * @param string $prefix optional string to prepend to final CDEF
	 * @return array
	 */
	public function rrdDef($node = '', $files = array(), $sources = array(), $prefix = '') {
		$output = array();

		$num = 0;
		$combine = array();

		foreach($files as $o_file) {
			$file = str_replace(array('/', '.'), '_', $o_file);

			foreach($sources as $ds => $junk) {
				$output[] = sprintf("DEF:%s%s%s=%s:%s:AVERAGE",
					$prefix, $ds, $file, $o_file, $ds);
				$output[] = sprintf("DEF:min%s%s%s=%s:%s:MIN",
					$prefix, $ds, $file, $o_file, $ds);
				$output[] = sprintf("DEF:max%s%s%s=%s:%s:MAX",
					$prefix, $ds, $file, $o_file, $ds);

				if($num == 0) {
					$combine['avg' . $ds] = sprintf("CDEF:%s%s%s=%s%s%s",
						$prefix, $ds, $node, $prefix, $ds, $file);
					$combine['min' . $ds] = sprintf("CDEF:min%s%s%s=min%s%s%s",
						$prefix, $ds, $node, $prefix, $ds, $file);
					$combine['max' . $ds] = sprintf("CDEF:max%s%s%s=max%s%s%s",
						$prefix, $ds, $node, $prefix, $ds, $file);
				} else {
					$combine['avg' . $ds] .= sprintf(",%s%s%s,ADDNAN",
						$prefix, $ds, $file);
					$combine['min' . $ds] .= sprintf(",min%s%s%s,ADDNAN",
						$prefix, $ds, $file);
					$combine['max' . $ds] .= sprintf(",max%s%s%s,ADDNAN",
						$prefix, $ds, $file);
				}
			}

			$num++;
		}

		$output = array_merge($output, array_values($combine));
		return $output;
	}

	/**
	 * Verify if rrd files exist for node
	 * @param string $node
	 * @return bool
	 */
	public function rrdExists($node = '') {
		$glob = $this->rrdFiles($node, array('first' => true));

		if(!empty($glob)) {
			return true;
		}

		return false;
	}

	/**
	 * Get rrd files
	 * @param string $node
	 * @param array $options
	 * @return array
	 */
	public function rrdFiles($node, $options) {
		$files = array();
		$search = $this->base_paths;

		if(array_key_exists('archive', $this->request)) {
			$archive = $this->findArchive($this->request['archive']);
			if($archive) {
				$search = array($archive);
			}
		}

		$ext = '';
		if(array_key_exists('ext', $options)) {
			$ext = $options['ext'];
		}

		foreach($search as $path) {
			foreach($this->paths as $glob) {
				$full = sprintf("%s/%s/%s%s.rrd",
					$path, $node, $glob, $ext);

				$list = glob($full, GLOB_NOSORT|GLOB_BRACE);
				if(!empty($list)) {
					if($options['first']) {
						return $list;
					}

					$files = array_merge($list);
				}
			}
		}

		return $files;
	}

	/**
	 * Generate graph statemens (LINE/AREA)
	 * @param string $ds
	 * @param array $data
	 * @return array
	 */
	public function rrdGraph($ds, $data) {
		$color = '#3F3F3F';
		$legend = '';
		$line = 1;
		$output = array();

		if(array_key_exists('color', $data)) {
			$color = $data['color'];
		}

		if(array_key_exists('legend', $data)) {
			$legend = $data['legend'];
		}

		if($data['area']) {
			$stack = '';
			if($data['area'] === 'stack') {
				$stack = sprintf(":%s:STACK", $legend);
			}

			$output[] = sprintf("AREA:%s%s%s", $ds, $color, $stack);
		}

		if(array_key_exists('line', $data)) {
			if(ctype_digit((string) $data['line'])) {
				$output[] = sprintf("LINE%s:%s%s:%s",
					$data['line'], $ds, $color, $legend);
			}
		}

		return $output;
	}

	/**
	 * Generate standard rrd options
	 * @param array $nodes
	 * @param string $plugin
	 * @return array
	 */
	public function rrdHeader($nodes = array(), $plugin = '') {
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

		if(array_key_exists('archive', $this->request)) {
			$label .= ' - ' . $this->request['archive'];

			$file = $this->findArchive($this->request['archive']) . '/timestamp';
			if(is_file($file)) {
				$time = file_get_contents($file);
				$time = trim($time);

				$rrd[] = '--end';
				$rrd[] = $time;
			}
		}
                
		$rrd[] = $label;

		$time = '';

		if(array_key_exists('t_val', $this->request)) {
			$time = $this->request['t_val'];
		}

		if(array_key_exists('t_unit', $this->request)) {
			if(empty($time)) {
				$time = 1;
			}

			$time .= $this->request['t_unit'];
		}

		if(!empty($time)) {
			$rrd[] = '--start';
			$rrd[] = 'end-' . $time;
		}

		return $rrd;
	}

	/**
	 * Custom rrd options
	 * @return array
	 */
	public function rrdOptions() {
		$output = array();

		foreach($this->rrd_options as $key => $value) {
			$output[] = $key;

			if(!is_null($value)) {
				$output[] = $value;
			}
		}

		return $output;
	}

	/**
	 * set $details with overrides
	 * @param array $defaults
	 * @param array $overrides
	 * @return array
	 */
	public function setDetails($defaults = array(), $overrides = array()) {
		$details = $defaults;

		foreach(array_merge($overrides, $defaults) as $key => $junk) {
			if(!array_key_exists($key, $overrides)) {
				continue;
			}

			if(!array_key_exists($key, $defaults)) {
				$details[$key] = $overrides[$key];
				continue;
			}

			if(is_array($defaults[$key])) {
				if(!is_array($overrides[$key])) {
					unset($details[$key]);
					continue;
				}

				$details[$key] = $this->setDetails(
					$details[$key],
					$overrides[$key]);
			} else {
				$details[$key] = $overrides[$key];
			}
		}

		return $details;
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

	/**
	 * Validate time unit
	 * @param string $input
	 * @return bool
	 */
	protected function validateInput_timeunit($input) {
		global $time_units;

		if(is_scalar($input)) {
			if(in_array($input, $time_units)) {
				return true;
			}
		}

		return false;
	}
}

?>
