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
		'label' => 'Generic value',
		'legend' => '',
		'path' => '',
		'vertical_label' => 'Values/sec',
	);

	public function __construct($options = array()) {
		parent::__construct();

		foreach($this->details as $key => $value) {
			if(array_key_exists($key, $options)) {
				$this->details[$key] = $options[$key];
			}
		}
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
			$full_path = $path . '/' . $node;
			$full_path .= '/' . $this->details['path'] . '.rrd';

			if(file_exists($full_path)) {
				$files[] = $full_path;
				continue;
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

				$rrd[] = 'DEF:' . $file . '=' . $o_file . ':value:AVERAGE';
				$rrd[] = 'DEF:min' . $file . '=' . $o_file . ':value:MIN';
				$rrd[] = 'DEF:max' . $file . '=' . $o_file . ':value:MAX';

				if($num == 0) {
					$t_combine['avg'] = 'CDEF:' . $node . '=' . $file;
					$t_combine['min'] = 'CDEF:min' . $node . '=min' . $file;
					$t_combine['max'] = 'CDEF:max' . $node . '=min' . $file;
				} else {
					$t_combine['avg'] .= ',' . $file . ',ADDNAN';
					$t_combine['min'] .= ',min' . $file . ',ADDNAN';
					$t_combine['max'] .= ',max' . $file . ',ADDNAN';
				}

				$num++;
			}

			$rrd = array_merge($rrd, array_values($t_combine));

			if($count == 0) {
				$combine['avg'] = 'CDEF:value=' . $node;
				$combine['min'] = 'CDEF:minvalue=min' . $node;
				$combine['max'] = 'CDEF:maxvalue=max' . $node;
			} else {
				$combine['avg'] .= ',' . $node . ',ADDNAN';
				$combine['min'] .= ',min' . $node . ',ADDNAN';
				$combine['max'] .= ',max' . $node . ',ADDNAN';
			}

			$count++;
		}

		$rrd = array_merge($rrd, array_values($combine));
		$rrd = array_merge($rrd, $this->rrdDate($options));

		$rrd[] = 'VDEF:lastvalue=value,LAST';
		$rrd[] = 'LINE1:value#3020ee:' . $this->details['legend'];
		$rrd[] = 'GPRINT:minvalue:MIN:Min\: %4.0lf%S	\g';
		$rrd[] = 'GPRINT:value:AVERAGE:Avg\: %4.0lf%S	\g';
		$rrd[] = 'GPRINT:maxvalue:MAX:Max\: %4.0lf%S	\g';
		$rrd[] = 'GPRINT:lastvalue:Last\: %4.0lf%S\j';

		return $rrd;
	}
}

?>
