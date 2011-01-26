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

	protected $datasources = array(
		'value' => array(
			'area' => true,
			'color' => '#3020ee',
			'legend' => '',
			'line' => 1,
			'scale' => '',
		),
	);

	public function __construct($options = array()) {
		parent::__construct();

		$this->rrd_options['-l'] = 0;

		if(array_key_exists('combined_average', $options)) {
			$this->combined_average = $options['combined_average'];
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
	 * Get the datasources
	 * @return array
	 */
	public function getDS() {
		return $this->datasources;
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
