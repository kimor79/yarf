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

class YarfGraphBindDnsOpcode extends Yarf {

	private $transfer_types = array(
		'AXFR' => '#3020ee',
		'IXFR' => '#00ff00',
		'SOA' => '#800080',
	);

	private $ip_versions = array(
		'IPv4',
		'IPv6',
	);

	public function __construct() {
		parent::__construct();

		$this->optional['transfer_type'] = '_multi_transfer_type';
		$this->optional['ip_version'] = '_multi_ip_version';

		$this->sanitize['transfer_type'] = '_multi_';
		$this->sanitize['ip_version'] = '_multi_ip_version';
	}

	/**
	 * Get the rrd files
	 * @param string $node
	 * @param array $options
	 * @return array array for each transfer_type
	 */
	public function rrdFiles($node = '', $options = array()) {
		$files = array();
		$paths = $this->paths;

		if(array_key_exists('archive', $options)) {
			$archive = $this->findArchive($options['archive']);

			if($archive) {
				$paths = array($archive);
		}

		$ip_version = '*';
		if(array_key_exists('ip_version', $options)) {
			if(count($options['ip_version']) > 1) {
				$ip_version = '{' . implode(',', $options['ip_version']) . '}';
			} else {
				$ip_version = $options['ip_version'][0];
			}
		}

		$transfers = array_keys($this->transfer_types);
		if(array_key_exists('transfer_type', $options)) {
			$transfers = $options['transfer_type'];
		}

		foreach($transfers as $transfer) {
			foreach($paths as $path) {
				$g_path = $path . '/' . $node;
				$g_path .= '/bind-global-zone_maint_stats/dns_opcode-';
				$g_path .= $transfer . '-' . $ip_version . '.rrd';

				$glob = glob($g_path, GLOB_BRACE);
				if(!empty($glob)) {
					$files[$transfer] = $glob;
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
		if(array_key_exists('ip_version', $options)) {
			if(count($options['ip_version']) == 1) {
				$label = '/' . $options['ip_version'][0];
			}
		}

		if(array_key_exists('port', $options)) {
			if(is_array($options['port'])) {
				$label .= ' - ' . implode(',', $options['port']);
			} else {
				$label .= ' - ' . $options['port'];
			}
		}

		$rrd = $this->rrdHeader($nodes, $options, 'DNS Opcode' . $label);
		$rrd[] = '-l';
		$rrd[] = 0;

		$combine = array();
		$count = 0;
		$has_transfer = array();

		foreach($nodes as $node) {
			$transfers = $this->rrdFiles($node, $options);

			if(empty($transfers)) {
				continue;
			}

			$node = str_replace('.', '_', $node);

			foreach($transfers as $transfer => $files) {
				$num = 0;
				$t_combine = array();

				foreach($files as $o_file) {
					$file = str_replace(array('/', '.'), '_', $o_file);

					$rrd[] = 'DEF:' . $file . '=' . $o_file . ':value:AVERAGE';
					$rrd[] = 'DEF:min' . $file . '=' . $o_file . ':value:MIN';
					$rrd[] = 'DEF:max' . $file . '=' . $o_file . ':value:MAX';

					if($num == 0) {
						$t_combine['avg'] = 'CDEF:' . $transfer . $node . '=' . $file;
						$t_combine['min'] = 'CDEF:min' . $transfer . $node . '=min' . $file;
						$t_combine['max'] = 'CDEF:max' . $transfer . $node . '=min' . $file;
					} else {
						$t_combine['avg'] .= ',' . $file . ',ADDNAN';
						$t_combine['min'] .= ',min' . $file . ',ADDNAN';
						$t_combine['max'] .= ',max' . $file . ',ADDNAN';
					}

					$num++;
				}

				$rrd = array_merge($rrd, array_values($t_combine));
			}

			foreach($transfers as $transfer => $junk) {
				if($count == 0) {
					$combine['avg' . $transfer] = 'CDEF:' . $transfer . '=' . $transfer . $node;
					$combine['min' . $transfer] = 'CDEF:min' . $transfer . '=min' . $transfer . $node;
					$combine['max' . $transfer] = 'CDEF:max' . $transfer . '=max' . $transfer . $node;

					$has_transfer[$transfer] = $transfer;
				} else {
					$combine['avg' . $transfer] .= ',' . $transfer . $node . ',ADDNAN';
					$combine['min' . $transfer] .= ',min' . $transfer . $node . ',ADDNAN';
					$combine['max' . $transfer] .= ',max' . $transfer . $node . ',ADDNAN';
				}
			}

			$count++;
		}

		$rrd = array_merge($rrd, array_values($combine));

		$rrd = array_merge($rrd, $this->rrdDate($options));

		foreach($has_transfer as $transfer) {
			$l_transfer = strtoupper($transfer);
			if(strlen($l_transfer) < 4) {
				$l_transfer .= ' ';
			}

			$rrd[] = 'VDEF:last' . $transfer . '=' . $transfer . ',LAST';
			$rrd[] = 'LINE1:' . $transfer . $this->transfer_types[$transfer] . ':' . $l_transfer;
			$rrd[] = 'GPRINT:min' . $transfer . ':MIN:Min\: %5.2lf%s';
			$rrd[] = 'GPRINT:' . $transfer . ':AVERAGE:Avg\: %5.2lf%s';
			$rrd[] = 'GPRINT:max' . $transfer . ':MAX:Max\: %5.2lf%s';
			$rrd[] = 'GPRINT:last' . $transfer . ':Last\: %5.2lf\j';
		}

		return $rrd;
	}

	/**
	 * Sanitize transfer_type
	 * @param string $input
	 * @return string
	 */
	protected function sanitizeInput_transfer_type($input) {
		return strtoupper($input);
	}

	/**
	 * Sanitize ip_version
	 * @param string $input
	 * @return string
	 */
	protected function sanitizeInput_ip_version($input) {
		switch(substr($input, -1)) {
			case '4':
				return 'IPv4';
			case '6':
				return 'IPv6';
		}

		return $input;
	}

	/**
	 * Validate transfer_type
	 * @param string $input
	 */
	protected function validateInput_transfer_type($input) {
		if(array_key_exists(strtoupper($input), $this->transfer_types)) {
			return true;
		}

		return false;
	}

	/**
	 * Validate ip_version
	 * @param string $input
	 */
	protected function validateInput_ip_version($input) {
		if(preg_match('/^(?:IP)?v?[46]$/i', $input) == 1) {
			return true;
		}

		return false;
	}
}

?>
