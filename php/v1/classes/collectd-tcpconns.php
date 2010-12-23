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

class CollectdGraph extends Collectd {

	private $directions = array(
		'local' => '#3020ee',
		'remote' => '#00ff00',
	);

	private $states = array(
		'CLOSE_WAIT',
		'CLOSING',
		'ESTABLISHED',
		'FIN_WAIT1',
		'FIN_WAIT2',
		'LAST_ACK',
		'LISTEN',
		'SYN_RECV',
		'SYN_SENT',
		'TIME_WAIT',
	);

	private $test_file = 'tcpconns-22-local/tcp_connections-LISTEN.rrd';

	public function __construct() {
		parent::__construct();

		$this->optional['direction'] = '_multi_direction';
		$this->optional['port'] = '_multi_digit';
		$this->optional['state'] = '_multi_state';
	}

	/**
	 * Verify if rrd files exist for node
	 * @param string $node
	 * @param array $options
	 * @return bool
	 */
	public function rrdExists($node = '', $options = array()) {
		$glob = $this->rrdFiles($node, $options);

		if(!empty($glob)) {
			return true;
		}

		return false;
	}

	/**
	 * Get the rrd files
	 * @param string $node
	 * @param array $options
	 * @return array array for each direction
	 */
	public function rrdFiles($node = '', $options = array()) {
		$archive = '';
		$files = array();
		$paths = $this->paths;

		if(array_key_exists('archive', $options)) {
			$archive = $options['archive'];
			$paths = $this->archives;
		}

		$port = '*';
		if(array_key_exists('port', $options)) {
			if(is_array($options['port'])) {
				$port = '{' . implode(',', $options['port']) . '}';
			} else {
				$t_port = explode($this->multi_separator, $options['port']);
				if(count($t_port) > 1) {
					$port = '{' . implode(',', $t_port) . '}';
				} else {
					$port = $options['port'];
				}
			}
		}

		$state = '*';
		if(array_key_exists('state', $options)) {
			if(is_array($options['state'])) {
				$state = '{' . strtoupper(implode(',', $options['state'])) . '}';
			} else {
				$t_state = explode($this->multi_separator, $options['state']);
				if(count($t_state) > 1) {
					$state = '{' . strtoupper(implode(',', $t_state)) . '}';
				} else {
					$state = strtoupper($options['state']);
				}
			}
		}

		$vectors = array_keys($this->directions);
		if(array_key_exists('direction', $options)) {
			if(is_array($options['direction'])) {
				$vectors = $options['direction'];
			} else {
				$vectors = explode($this->multi_separator, $options['direction']);
			}
		}

		foreach($vectors as $vector) {
			foreach($paths as $path) {
				$g_path = $path . '/' . $archive . '/' . $node;
				$g_path .= '/tcpconns-' . $port . '-' . $vector;
				$g_path .= '/tcp_connections-' . $state . '.rrd';

				$glob = glob($g_path, GLOB_BRACE);
				if(!empty($glob)) {
					$files[$vector] = $glob;
					continue 2;
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
		$label = '';
		if(array_key_exists('state', $options)) {
			if(!is_array($options['state'])) {
				$t_state = explode($this->multi_separator, $options['state']);
				if(count($t_state) == 1) {
					$label = '/' . strtoupper($options['state']);
				}
			}
		}

		if(array_key_exists('port', $options)) {
			if(is_array($options['port'])) {
				$label .= ' - ' . implode(',', $options['port']);
			} else {
				$label .= ' - ' . $options['port'];
			}
		}

		$rrd = $this->rrdHeader($nodes, $options, 'tcpconns' . $label);
		$rrd[] = '-l';
		$rrd[] = 0;
		$rrd[] = '-v';
		$rrd[] = 'Connections';

		$combine = array();
		$count = 0;
		$has_vector = array();

		foreach($nodes as $node) {
			$vectors = $this->rrdFiles($node, $options);

			if(empty($vectors)) {
				continue;
			}

			$node = str_replace('.', '_', $node);

			foreach($vectors as $vector => $files) {
				$num = 0;
				$t_combine = array();

				foreach($files as $o_file) {
					$file = str_replace(array('/', '.'), '_', $o_file);

					$rrd[] = 'DEF:' . $file . '=' . $o_file . ':value:AVERAGE';
					$rrd[] = 'DEF:min' . $file . '=' . $o_file . ':value:MIN';
					$rrd[] = 'DEF:max' . $file . '=' . $o_file . ':value:MAX';

					if($num == 0) {
						$t_combine['avg'] = 'CDEF:' . $vector . $node . '=' . $file;
						$t_combine['min'] = 'CDEF:min' . $vector . $node . '=min' . $file;
						$t_combine['max'] = 'CDEF:max' . $vector . $node . '=min' . $file;
					} else {
						$t_combine['avg'] .= ',' . $file . ',ADDNAN';
						$t_combine['min'] .= ',min' . $file . ',ADDNAN';
						$t_combine['max'] .= ',max' . $file . ',ADDNAN';
					}

					$num++;
				}

				$rrd = array_merge($rrd, array_values($t_combine));
			}

			foreach($vectors as $vector => $junk) {
				if($count == 0) {
					$combine['avg' . $vector] = 'CDEF:' . $vector . '=' . $vector . $node;
					$combine['min' . $vector] = 'CDEF:min' . $vector . '=min' . $vector . $node;
					$combine['max' . $vector] = 'CDEF:max' . $vector . '=max' . $vector . $node;

					$has_vector[$vector] = $vector;
				} else {
					$combine['avg' . $vector] .= ',' . $vector . $node . ',ADDNAN';
					$combine['min' . $vector] .= ',min' . $vector . $node . ',ADDNAN';
					$combine['max' . $vector] .= ',max' . $vector . $node . ',ADDNAN';
				}
			}

			$count++;
		}

		$rrd = array_merge($rrd, array_values($combine));

		$rrd = array_merge($rrd, $this->rrdDate($options));

		foreach($has_vector as $vector) {
			$rrd[] = 'VDEF:last' . $vector . '=' . $vector . ',LAST';
			$rrd[] = 'LINE1:' . $vector . $this->directions[$vector] . ':' . $vector;
			$rrd[] = 'GPRINT:min' . $vector . ':MIN:Min\: %5.2lf%s';
			$rrd[] = 'GPRINT:' . $vector . ':AVERAGE:Avg\: %5.2lf%s';
			$rrd[] = 'GPRINT:max' . $vector . ':MAX:Max\: %5.2lf%s';
			$rrd[] = 'GPRINT:last' . $vector . ':Last\: %5.2lf\j';
		}

		return $rrd;
	}

	/**
	 * Validate direction
	 * @param string $input
	 */
	protected function validateInput_direction($input) {
		if(array_key_exists(strtolower($input), $this->directions)) {
			return true;
		}

		return false;
	}

	/**
	 * Validate state
	 * @param string $input
	 */
	protected function validateInput_state($input) {
		if(in_array(strtoupper($input), $this->states)) {
			return true;
		}

		return false;
	}
}

?>
