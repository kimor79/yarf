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
		'combined' => array(
			'average' => false,
		),
		'data' => array(
			'value' => array(
				'area' => true,
				'color' => '#3020ee',
				'legend' => '',
				'line' => 1,
				'scale' => '',
			),
		),
		'rrd' => array(
			'-l' => 0,
		),
		'title' => 'Generic Graph',
		'paths' => array(''),
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
		$title = $this->details['title'];
		if(count($nodes) > 1) {
			if(array_key_exists('combined', $this->details)) {
				if(array_key_exists('average', $this->details['combined'])) {
					if($this->details['combined']['average']) {
						$title .= ' (Avg)';
					}
				}
			}
		}

		$rrd = $this->rrdHeader($nodes, $options, $title);

		if(array_key_exists('rrd', $this->details)) {
			foreach($this->details['rrd'] as $key => $value) {
				if(!is_null($value)) {
					$rrd[] = $key;

					if($value !== '') {
						$rrd[] = $value;
					}
				}
			}
		}

		$combine = array();
		$count = 0;

		foreach($nodes as $node) {
			$files = $this->rrdFiles($node, $options);

			if(empty($files)) {
				continue;
			}

			$node = str_replace('.', '_', $node);

			$t_rrd = $this->rrdDef($node, $files, array_keys($this->details['data']));

			if(empty($t_rrd)) {
				continue;
			}

			$rrd = array_merge($rrd, $t_rrd);

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

		foreach($this->details['data'] as $o_ds => $data) {
			$ds = $o_ds;

			if($count > 1) {
				if(array_key_exists('combined', $this->details)) {
					if(array_key_exists('average', $this->details['combined'])) {
						if($this->details['combined']['average']) {
							$rrd[] = sprintf("CDEF:%scombined=%s,%s,/",
								$ds, $ds, count($nodes));
							$rrd[] = sprintf("CDEF:min%scombined=min%s,%s,/",
								$ds, $ds, count($nodes));
							$rrd[] = sprintf("CDEF:max%scombined=max%s,%s,/",
								$ds, $ds, count($nodes));

							$ds .= 'combined';
						}
					}
				}
			}

			if($data['scale']) {
				$rrd[] = sprintf("CDEF:%sscaled=%s,%s",
					$ds, $ds, $data['scale']);
				$rrd[] = sprintf("CDEF:min%sscaled=min%s,%s",
					$ds, $ds, $data['scale']);
				$rrd[] = sprintf("CDEF:max%sscaled=max%s,%s",
					$ds, $ds, $data['scale']);

				$ds .= 'scaled';
			}

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
