<?php

/*
 * Sample config containing additions/overwrites
*/

$data_types = array(
	'syslog-ng' => array(
		'class' => 'YarfGeneric',
		'class_options' => array( /* new $class($class_options) */
			'data' => array(
				'value' => array( /* rrd ds name */
					'color' => '#ef45tg',
				),
			),
			'title' => 'syslog-ng',
			'paths' => array(
				'syslog-ng/derive-processed', /* .../$path.rrd */
			),
			'vertical_label' => 'messages/sec',
		),
		'file' => 'generic', /* yarf/classes/$file.php */
	),
);

?>
