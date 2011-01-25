<?php

/*
 * Sample config containing additions/overwrites
*/

$data_types = array(
	'syslog-ng' => array(
		'class' => 'YarfGeneric',
		'class_options' => array( /* new $class($class_options) */
			'datasources' => array(
				'value' => array( /* rrd ds name */
					'color' => '#ef45tg',
				),
			),
			'rrd' => array( /* additional rrd options */
				'-v' => 'messages/sec',
			),
			'paths' => array(
				'syslog-ng/derive-processed', /* .../$path.rrd */
			),
			'title' => 'syslog-ng',
		),
		'file' => 'generic', /* yarf/classes/$file.php */
	),
);

?>
