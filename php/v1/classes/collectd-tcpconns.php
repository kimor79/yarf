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

		$this->optional['direction'] = 'direction';
		$this->optional['port'] = '_multi_digit';
		$this->optional['state'] = 'state';
	}

	/**
	 * Verify if rrd files exist for given node
	 * @param string $node
	 * @param array $options
	 * @return string The path or false
	 */
	public function rrdExists($node = '', $options = array()) {
		$archive = '';
		$paths = $this->paths;

		if(array_key_exists('archive', $options)) {
			$archive = $options['archive'];
			$paths = $this->archives;
		}

		foreach($paths as $path) {
			$exists = $path . '/' . $archive . '/' . $node;

			if(file_exists($exists . '/' . $this->test_file)) {
				return $exists;
			}
		}

		return false;
	}

	/**
	 * Build the rrd options array
	 * @param array $nodes
	 * @param array $options
	 * @return array
	 */
	public function rrdOptions($nodes = array(), $options = array()) {
		$port = '*';
		if(array_key_exists('port', $options)) {
			if(is_array($options['port'])) {
				$port = '{' . implode(',', $options['port']) . '}';
			} else {
				$t_port = explode(',', $options['port']);
				if(count($t_port) > 1) {
					$port = '{' . implode(',', $t_port) . '}';
				} else {
					$port = $options['port'];
				}
			}
		}

		$label = '';
		$state = '*';
		if(array_key_exists('state', $options)) {
			if(is_array($options['state'])) {
				$state = '{' . strtoupper(implode(',', $options['state'])) . '}';
			} else {
				$t_state = explode(',', $options['state']);
				if(count($t_state) > 1) {
					$state = '{' . strtoupper(implode(',', $t_state)) . '}';
				} else {
					$label = '/' . strtoupper($options['state']);
					$state = strtoupper($options['state']);
				}
			}
		}

		$vectors = array_keys($this->directions);
		if(array_key_exists('direction', $options)) {
			if(is_array($options['direction'])) {
				$vectors = $options['direction'];
			} else {
				$vectors = explode(',', $options['direction']);
			}
		}

		$rrd = $this->rrdHeader($nodes, $options, 'tcpconns' . $label);
		$rrd[] = '-l';
		$rrd[] = 0;

		$combine = array();
		$count = 0;
		$has_vector = array();

		foreach($nodes as $node) {
			$dir = $this->rrdExists($node, $options);

			if(!$dir) {
				continue;
			}

			$node = str_replace('.', '_', $node);

			$t_has_vector = array();

			foreach($vectors as $vector) {
				$num = 0;
				$t_combine = array();

				$glob_dir = $dir . '/tcpconns-' . $port . '-';
				$glob_dir .= $vector . '/tcp_connections-';
				$glob_dir .= $state . '.rrd';

				foreach(glob($glob_dir) as $file) {
					$rrd[] = 'DEF:' . $vector . $node . $num . '=' . $file . ':value:AVERAGE';
					$rrd[] = 'DEF:min' . $vector . $node . $num . '=' . $file . ':value:MIN';
					$rrd[] = 'DEF:max' . $vector . $node . $num . '=' . $file . ':value:MAX';

					if($num == 0) {
						$t_combine['avg'] = 'CDEF:' . $vector . $node . '=' . $vector . $node . $num;
						$t_combine['min'] = 'CDEF:min' . $vector . $node . '=' . $vector . $node . $num;
						$t_combine['max'] = 'CDEF:max' . $vector . $node . '=' . $vector . $node . $num;
					} else {
						$t_combine['avg'] .= ',' . $vector . $node . $num . ',ADDNAN';
						$t_combine['min'] .= ',min' . $vector . $node . $num . ',ADDNAN';
						$t_combine['max'] .= ',max' . $vector . $node . $num . ',ADDNAN';
					}

					$num++;
				}

				if($num > 0) {
					$t_has_vector[$vector] = $vector;
				}

				$rrd = array_merge($rrd, array_values($t_combine));
			}

			foreach($t_has_vector as $vector) {
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

		foreach($has_vector as $vector) {
			$rrd[] = 'VDEF:last' . $vector . '=' . $vector . ',LAST';
			$rrd[] = 'LINE1:' . $vector . $this->directions[$vector] . ':' . $vector;
			$rrd[] = 'GPRINT:min' . $vector . ':MIN:Min\: %7.2lf%s';
			$rrd[] = 'GPRINT:' . $vector . ':AVERAGE:Avg\: %7.2lf%s';
			$rrd[] = 'GPRINT:max' . $vector . ':MAX:Max\: %7.2lf%s';
			$rrd[] = 'GPRINT:last' . $vector . ':Last\: %7.2lf%s';
		}

		return $rrd;
	}

	/**
	 * Validate direction
	 * @param mixed $input
	 */
	protected function validateInput_direction($input) {
		$tests = array();

		if(is_array($input)) {
			$tests = $input;
		} else {
			$tests = explode(',', $input);
		}

		$count = 0;
		$total = count($tests);

		foreach($tests as $test) {
			if(array_key_exists(strtolower($test), $this->directions)) {
				$count++;
			}
		}

		if($total > 0) {
			if($count === $total) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate state
	 * @param mixed $input
	 */
	protected function validateInput_state($input) {
		$tests = array();

		if(is_array($input)) {
			$tests = $input;
		} else {
			$tests = explode(',', $input);
		}

		$count = 0;
		$total = count($tests);

		foreach($tests as $test) {
			if(in_array(strtoupper($test), $this->states)) {
				$count++;
			}
		}

		if($total > 0) {
			if($count === $total) {
				return true;
			}
		}

		return false;
	}
}

?>
