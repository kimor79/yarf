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

$data_types = array();

$builtin_data_types = array(
	'Disk IO Bytes (All)' => array(
		'class' => 'YarfGeneric',
		'class_options' => array(
			'data' => array(
				'read' => array(
					'color' => '#3020EE',
					'format' => '%7.2lf%s',
					'legend' => 'Reads',
				),
				'write' => array(
					'color' => '#00FF00',
					'format' => '%7.2lf%s',
					'legend' => 'Writes',
				),
				'value' => NULL,
			),
			'paths' => array(
				'disk-*/disk_octets',
			),
			'rrd' => array(
				'-v' => 'Bytes/sec',
			),
			'title' => 'Disk IO (All)',
		),
		'file' => 'generic',
	),

	'load' => array(
		'class' => 'YarfGeneric',
		'class_options' => array(
			'combined' => array(
				'average' => true,
			),
			'data' => array(
				'shortterm' => array(
					'color' => '#ff0000',
					'legend' => '1minute',
				),
				'midterm' => array(
					'color' => '#00ff00',
					'legend' => '5minute',
					'line' => 2,
				),
				'longterm' => array(
					'area' => true,
					'color' => '#3020ee',
					'legend' => '15minute',
					'line' => 0,
				),
				'value' => NULL,
			),
			'paths' => array(
				'load/load',
				'snmp/load',
			),
			'title' => 'Load',
		),
		'file' => 'generic',
	),

	'tcpconns' => array(
		'class' => 'YarfTcpConns',
		'file' => 'tcpconns',
	),

	'temperature' => array(
		'class' => 'YarfGeneric',
		'class_options' => array(
			'combined' => array(
				'average' => true,
			),
			'data' => array(
				'value' => array(
					'scale' => '9,*,5,/,32,+'
				),
			),
			'paths' => array(
				'snmp/temperature',
			),
			'rrd' => array(
				'-u' => 100,
				'-v' => 'Fahrenheit',
			),
			'title' => 'Temperature',
		),
		'file' => 'generic',
	),

	'uptime' => array(
		'class' => 'YarfGeneric',
		'class_options' => array(
			'combined' => array(
				'average' => true,
			),
			'data' => array(
				'value' => array(
					'scale' => '86400,/',
				),
			),
			'paths' => array(
				'uptime/uptime',
				'snmp/uptime',
			),
			'rrd' => array(
				'-v' => 'Days',
			),
			'title' => 'Uptime',
			'vertical_label' => 'Days',
		),
		'file' => 'generic',
	),
);

if(is_file('/usr/local/etc/yarf/data_types.php')) {
	@include_once('/usr/local/etc/yarf/data_types.php');
}

$data_types = array_merge($builtin_data_types, $data_types);

?>
