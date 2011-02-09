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
	'CPU Usage' => array(
		'class_options' => array(
			'config' => array(
				'combined_average' => true,
				'multi_file' => true,
				'percent' => true,
			),
			'datasources' => array(
				0 => NULL,
				'user' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#FFD000',
						'format' => '%3.0lf',
						'legend' => 'User     ',
						'line' => NULL,
					),
				),
				'nice' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#888888',
						'format' => '%3.0lf',
						'legend' => 'Nice     ',
						'line' => NULL,
					),
				),
				'system' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#0000FF',
						'format' => '%3.0lf',
						'legend' => 'System   ',
						'line' => NULL,
					),
				),
				'interrupt' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#FF0000',
						'format' => '%3.0lf',
						'legend' => 'Interrupt',
						'line' => NULL,
					),
				),
				'idle' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#00FF00',
						'format' => '%3.0lf',
						'legend' => 'Idle     ',
						'line' => NULL,
					),
				),
			),
			'paths' => array(
				'cpu-*/cpu-',
			),
			'rrd' => array(
				'-l' => 0,
				'-r' => NULL,
				'-u' => 100,
				'-v' => 'Percent',
			),
			'title' => 'CPU',
		),
	),

	'Disk IO Bytes (All)' => array(
		'class_options' => array(
			'datasources' => array(
				array(
					'read' => array(
						'color' => '#3020EE',
						'format' => '%7.2lf%s',
						'legend' => 'Reads ',
					),
					'write' => array(
						'color' => '#00FF00',
						'format' => '%7.2lf%s',
						'legend' => 'Writes',
					),
					'value' => NULL,
				),
			),
			'paths' => array(
				'disk-*/disk_octets',
			),
			'rrd' => array(
				'-v' => 'Bytes/sec',
			),
			'title' => 'Disk IO (All)',
		),
	),

	'load' => array(
		'class_options' => array(
			'config' => array(
				'combined_average' => true,
			),
			'datasources' => array(
				array(
					'shortterm' => array(
						'color' => '#ff0000',
						'legend' => '1minute ',
					),
					'midterm' => array(
						'color' => '#00ff00',
						'legend' => '5minute ',
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
			),
			'paths' => array(
				'load/load',
				'snmp/load',
			),
			'title' => 'Load',
		),
	),

	'Memory Usage' => array(
		'class_options' => array(
			'config' => array(
				'combined_average' => true,
				'multi_file' => true,
				'percent' => true,
			),
			'datasources' => array(
				0 => NULL,
				'active' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#FFD000',
						'format' => '%3.0lf',
						'legend' => 'Active  ',
						'line' => NULL,
					),
				),
				'inactive' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#888888',
						'format' => '%3.0lf',
						'legend' => 'Inactive',
						'line' => NULL,
					),
				),
				'wired' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#FF0000',
						'format' => '%3.0lf',
						'legend' => 'Wired   ',
						'line' => NULL,
					),
				),
				'cache' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#9370DB',
						'format' => '%3.0lf',
						'legend' => 'Cache   ',
						'line' => NULL,
					),
				),
				'free' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#00FF00',
						'format' => '%3.0lf',
						'legend' => 'Free    ',
						'line' => NULL,
					),
				),
			),
			'paths' => array(
				'memory/memory-',
			),
			'rrd' => array(
				'-l' => 0,
				'-r' => NULL,
				'-u' => 100,
				'-v' => '% Utilization',
			),
			'title' => 'Memory',
		),
	),

	'Swap' => array(
		'class_options' => array(
			'config' => array(
				'combined_average' => true,
				'multi_file' => true,
				'percent' => true,
			),
			'datasources' => array(
				0 => NULL,
				'used' => array(
					'value' => array(
						'area' => 'stack',
						'color' => '#FF0000',
						'format' => '%3.0lf',
						'legend' => 'Used',
						'line' => NULL,
					),
				),
				'free' => array(
					'value' => NULL,
				),
			),
			'paths' => array(
				'swap/swap-',
			),
			'rrd' => array(
				'-l' => 0,
				'-r' => NULL,
				'-u' => 100,
				'-v' => '% Utilization',
			),
			'title' => 'Swap',
		),
	),

	'tcpconns' => array(
		'class' => array(
			'file' => 'tcpconns',
			'name' => 'YarfTcpConns',
		),
		'class_options' => array(
			'config' => array(
				'multi_file' => true,
			),
			'datasources' => array(
				0 => NULL,
				'local' => array(
					'value' => array(
						'color' => '#3020EE',
						'format' => '%4.0lf%s',
						'legend' => 'Local ',
					),
				),
				'remote' => array(
					'value' => array(
						'color' => '#00FF00',
						'format' => '%4.0lf%s',
						'legend' => 'Remote',
					),
				),
			),
			'rrd' => array(
				'-v' => 'Connections',
			),
		),
	),

	'temperature' => array(
		'class_options' => array(
			'config' => array(
				'combined_average' => true,
			),
			'datasources' => array(
				array(
					'value' => array(
						'scale' => '9,*,5,/,32,+'
					),
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
	),

	'Traffic - Bits' => array(
		'class_options' => array(
			'datasources' => array(
				array(
					'rx' => array(
						'color' => '#3020EE',
						'format' => '%5.2lf%s',
						'legend' => 'RX',
						'scale' => '8,*',
					),
					'tx' => array(
						'color' => '#00FF00',
						'format' => '%5.2lf%s',
						'legend' => 'TX',
						'scale' => '8,*',
					),
					'value' => NULL,
				),
			),
			'paths' => array(
				'interface/if_octets-*',
				'snmp/if_octets_u-*',
			),
			'rrd' => array(
				'-v' => 'Bits/sec',
			),
			'title' => 'Traffic',
		),
	),

	'uptime' => array(
		'class_options' => array(
			'config' => array(
				'combined_average' => true,
			),
			'datasources' => array(
				array(
					'value' => array(
						'scale' => '86400,/',
					),
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
		),
	),
);

if(is_file('/usr/local/etc/yarf/data_types.php')) {
	@include_once('/usr/local/etc/yarf/data_types.php');
}

$data_types = array_merge($builtin_data_types, $data_types);

?>
