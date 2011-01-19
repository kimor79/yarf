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

class YarfGeneric extends Yarf {

	protected $details = array(
		'data' => array(
			'value' => array(
				'area' => true,
				'color' => '#3020ee',
				'legend' => '',
				'line' => 1,
			),
		),
		'label' => 'Generic Graph',
		'paths' => array(''),
		'vertical_label' => '',
	);

	public function __construct($options = array()) {
		parent::__construct();

		$this->details = $this->setDetails($this->details, $options);
	}

	/**
	 * Get the rrd files
	 * @param string $node
	 * @param array $options
	 * @return array array of files
	 */
	public function rrdFiles($node = '', $options = array()) {
		$files = array();
		$paths = $this->paths;

		if(array_key_exists('archive', $options)) {
			$archive = $this->findArchive($options['archive']);

			if($archive) {
				$paths = array($archive);
			}
		}

		foreach($paths as $path) {
			foreach($this->details['paths'] as $s_path) {
				$full_path = sprintf("%s/%s/%s.rrd",
					$path, $node, $s_path);

				if(file_exists($full_path)) {
					$files[] = $full_path;
					continue;
				}
			}
		}

		return $files;
	}

	/**
	 * Build the rrd options array
	 * @param array $nodes
	 * @param array $options
	 * @return array
	 */
	public function rrdOptions($nodes = array(), $options = array()) {
		$rrd = $this->rrdHeader($nodes, $options, $this->details['label']);
		$rrd[] = '-l';
		$rrd[] = 0;

		if(!empty($this->details['vertical_label'])) {
			$rrd[] = '-v';
			$rrd[] = $this->details['vertical_label'];
		}

		$combine = array();
		$count = 0;

		foreach($nodes as $node) {
			$files = $this->rrdFiles($node, $options);

			if(empty($files)) {
				continue;
			}

			$node = str_replace('.', '_', $node);

			$num = 0;
			$t_combine = array();

			foreach($files as $o_file) {
				$file = str_replace(array('/', '.'), '_', $o_file);

				foreach($this->details['data'] as $ds => $data) {
					$rrd[] = sprintf("DEF:%s%s=%s:%s:AVERAGE",
						$ds, $file, $o_file, $ds);
					$rrd[] = sprintf("DEF:min%s%s=%s:%s:MIN",
						$ds, $file, $o_file, $ds);
					$rrd[] = sprintf("DEF:max%s%s=%s:%s:MAX",
						$ds, $file, $o_file, $ds);

					if($num == 0) {
						$t_combine['avg' . $ds] = sprintf("CDEF:%s%s=%s%s",
							$ds, $node, $ds, $file);
						$t_combine['min' . $ds] = sprintf("CDEF:min%s%s=min%s%s",
							$ds, $node, $ds, $file);
						$t_combine['max' . $ds] = sprintf("CDEF:max%s%s=max%s%s",
							$ds, $node, $ds, $file);
					} else {
						$t_combine['avg' . $ds] .= sprintf(",%s%s,ADDNAN",
							$ds, $file);
						$t_combine['min' . $ds] .= sprintf(",min%s%s,ADDNAN",
							$ds, $file);
						$t_combine['max' . $ds] .= sprintf(",max%s%s,ADDNAN",
							$ds, $file);
					}
				}

				$num++;
			}

			$rrd = array_merge($rrd, array_values($t_combine));

			foreach($this->details['data'] as $ds => $data) {
				if($count == 0) {
					$combine['avg' . $ds] = sprintf("CDEF:%s=%s%s",
						$ds, $ds, $node);
					$combine['min' . $ds] = sprintf("CDEF:min%s=min%s%s",
						$ds, $ds, $node);
					$combine['max' . $ds] = sprintf("CDEF:max%s=max%s%s",
						$ds, $ds, $node);
				} else {
					$combine['avg' . $ds] .= sprintf(",%s%s,ADDNAN",
						$ds, $node);
					$combine['min' . $ds] .= sprintf(",min%s%s,ADDNAN",
						$ds, $node);
					$combine['max' . $ds] .= sprintf(",max%s%s,ADDNAN",
						$ds, $node);
				}
			}

			$count++;
		}

		$rrd = array_merge($rrd, array_values($combine));
		$rrd = array_merge($rrd, $this->rrdDate($options));

		foreach($this->details['data'] as $ds => $data) {
			$rrd[] = sprintf("VDEF:last%s=%s,LAST",
				$ds, $ds);

			if($data['area']) {
				$rrd[] = sprintf("AREA:%s%s",
					$ds, $data['color']);
			}

			if(array_key_exists('line', $data)) {
				if(ctype_digit((string) $data['line'])) {
					$rrd[] = sprintf("LINE%s:%s%s:%s",
						$data['line'], $ds, $data['color'], $data['legend']);
				}
			} else {
				$rrd[] = sprintf("LINE1:%s%s:%s",
					$ds, $data['color'], $data['legend']);
			}

			$rrd[] = sprintf("GPRINT:min%s:MIN:Min\\: %%4.0lf%%S	\\g",
				$ds);
			$rrd[] = sprintf("GPRINT:%s:AVERAGE:Avg\\: %%4.0lf%%S	\\g",
				$ds);
			$rrd[] = sprintf("GPRINT:max%s:MAX:Max\\: %%4.0lf%%S	\\g",
				$ds);
			$rrd[] = sprintf("GPRINT:last%s:Last\\: %%4.0lf%%S\\j",
				$ds);
		}

		return $rrd;
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

				$details[$key] = $this->setDetails($details[$key], $overrides[$key]);
			} else {
				$details[$key] = $overrides[$key];
			}
		}

		return $details;
	}
}
