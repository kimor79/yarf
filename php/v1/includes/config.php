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

require_once('yarf/v1/classes/yarf.php');

$yarf = new Yarf();
$req = array_merge($_GET, $_POST);

if(!isset($default_format)) {
	$default_format = 'png';
}

$yarf->setParameters($req, array('outputFormat' => $default_format));

$config = @parse_ini_file('/usr/local/etc/yarf/config.ini', true);

if(empty($config)) {
	$yarf->sendHeaders();
	$yarf->showOutput('500', 'Error with config file');
	exit(0);
}

function get_config($key = '', $sub = '') {
	global $config;

	$defaults = array(
		'archive' => array(
			'paths' => '/yarf/archive',
		),

		'collectd' => array(
			'paths' => '/var/db/collectd',
		),

		'yui' => array(
			'base_uri' => 'http://yui.yahooapis.com/2.8.2r1/build',
		),
	);

	if(array_key_exists($key, $config)) {
		if(!empty($sub)) {
			if(array_key_exists($sub, $config[$key])) {
				return $config[$key][$sub];
			}
		}

		return $config[$key];
	}

	if(array_key_exists($key, $defaults)) {
		if(!empty($sub)) {
			if(array_key_exists($sub, $defaults[$key])) {
				return $defaults[$key][$sub];
			}
		}

		return $defaults[$key];
	}

	return NULL;
}

?>
