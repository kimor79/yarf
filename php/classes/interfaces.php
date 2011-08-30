<?php

/**

Copyright (c) 2011, Kimo Rosenbaum and contributors
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

require_once('yarf/classes/yarf.php');

class YarfInterfaces extends Yarf {

	protected $ifname_replace = array(
		'/',
		'.',
	);
	protected $interfaces = array();

	public function __construct($options = array()) {
		parent::__construct($options);

		$this->optional['interface'] = '_multi_';
		$this->sanitize['interface'] = '_multi_interface';
	}

	/**
	 * Get title
	 * @param int $count
	 * @return string
	 */
	public function getTitle($count = 0) {
		if(array_key_exists('interface', $this->request)) {
			if(count($this->request['interface']) == 1) {
				$title = $this->interfaces[$this->request['interface'][0]] . ' - ';
			}
		}

		$title .= $this->title;

		return $title;
	}

	/**
	 * Get rrd files
	 * @param string $node
	 * @param array $options
	 * @return array
	 */
	public function rrdFiles($node, $options) {
		$options['ext'] = '*';

		if(array_key_exists('interface', $this->request)) {
			$interfaces = array();
			while(list($junk, $iface) =
					each($this->request['interface'])) {
				$interfaces[] = str_replace(',', '\,', $iface);
			}
			reset($this->request['interface']);

			$options['ext'] = sprintf("{%s}",
				implode(',', $interfaces));
		}

		return parent::rrdFiles($node, $options);
	}

	/**
	 * Sanitize interface
	 * @param string $input
	 * @return string
	 */
	public function sanitizeInput_interface($input) {
		$output = str_replace($this->ifname_replace, '_', $input);

		$this->interfaces[$output] = $input;

		return $output;
	}
}

?>
