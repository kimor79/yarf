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

class YarfTcpConns extends Yarf {

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

	public function __construct($options = array()) {
		parent::__construct($options);

		$this->optional['port'] = '_multi_digit';
		$this->optional['state'] = '_multi_state';

		$this->sanitize['port'] = '_multi_';
		$this->sanitize['state'] = '_multi_state';
	}

	/**
	 * Get the title
	 * @param int $count
	 * @return string
	 */
	public function getTitle($count = 0) {
		$title = 'tcpconns';
		if(array_key_exists('state', $this->request)) {
			if(count($this->request['state']) == 1) {
				$title .= '/' . $this->request['state'][0];
			}
		}

		if(array_key_exists('port', $this->request)) {
			$title .= sprintf(" - %s",
				implode(',', $this->request['port']));
		}

		return $title;
	}

	/**
	 * Get the rrd files
	 * @param string $node
	 * @param array $options
	 * @return array
	 */
	public function rrdFiles($node = '', $options = array()) {
		$port = '*';
		if(array_key_exists('port', $this->request)) {
			if(count($this->request['port']) > 1) {
				$port = sprintf("{%s}",
					implode(',', $this->request['port']));
			} else {
				$port = $this->request['port'][0];
			}
		}

		$state = '*';
		if(array_key_exists('state', $this->request)) {
			if(count($this->request['state']) > 1) {
				$state = sprintf("{%s}",
					implode(',', $this->request['state']));
			} else {
				$state = $this->request['state'][0];
			}
		}

		$this->paths = array(
			sprintf("tcpconns-%s-%s/tcp_connections-%s",
				$port, $options['ext'], $state),
		);

		$options['ext'] = '';

		return parent::rrdFiles($node, $options);
	}

	/**
	 * Sanitize state
	 * @param string $input
	 * @return string
	 */
	protected function sanitizeInput_state($input) {
		return strtoupper($input);
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
