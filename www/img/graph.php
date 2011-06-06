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

$default_format = 'png';

require_once('yarf/includes/init.php');

if(!array_key_exists('data', $request)) {
	$yarf->sendHeaders();
	$yarf->showOutput('400', 'Missing data');
	exit(0);
}

if(!array_key_exists($request['data'], $data_types)) {
	$yarf->sendHeaders();
	$yarf->showOutput('400', 'No such data type');
	exit(0);
}

$data_type = $data_types[$request['data']];
unset($request['data']);

if(array_key_exists('class', $data_type)) {
	require_once('yarf/classes/' . $data_type['class']['file'] . '.php');
	$class = $data_type['class']['name'];
} else {
	$class = 'Yarf';
}

if(array_key_exists('class_options', $data_type)) {
	$api = new $class($data_type['class_options']);
} else {
	$api = $class;
}

$api->setParameters($request, array('outputFormat' => $default_format));
$input = $api->setInput($request);

$errors = $api->validateInput($input, $api->required, $api->optional);

if(!empty($errors)) {
	$api->sendHeaders();
	$api->showOutput('400', implode("\n", $errors));
	exit(0);
}

$errors = array();

if(!array_key_exists('node', $input) &&
		!array_key_exists('expression', $input)) {
	$errors[] = 'Missing node(s) or expression';
}

if(array_key_exists('node', $input)) {
	if(empty($input['node'])) {
		$errors[] = 'Empty node';
	}
}

if(array_key_exists('expression', $input)) {
	if(empty($input['expression'])) {
		$errors[] = 'Empty expression';
	}
}

if(!empty($errors)) {
	$api->sendHeaders();
	$api->showOutput('400', implode("\n", $errors));
	exit(0);
}

$input = $api->sanitizeInput($input, $api->sanitize);

$entries = array();

if(array_key_exists('expression', $input)) {
	$entries = $api->parseNodes($input['expression']);
}

if(array_key_exists('node', $input)) {
	$entries = array_merge($entries, $input['node']);
}

if(empty($entries)) {
	$api->sendHeaders();
	$api->showOutput('400', 'No nodes to graph');
	exit(0);
}

unset($input['expression']);
unset($input['node']);

$api->request = $input;

// See the comments at
// http://php.net/manual/en/function.array-unique.php
// as to why this is faster than array_unique()
$entries = array_merge(array_flip(array_flip($entries)));

$nodes = array();
while(list($junk, $node) = each($entries)) {
	if($api->rrdExists($node)) {
		$nodes[] = $node;
	}
}
reset($nodes);

if(empty($nodes)) {
	$message = 'No nodes for this data set';
	if(count($entries) == 1) {
		$message = 'No graph for ' . $entries[0];
	}

	$api->sendHeaders();
	$api->showOutput('400', $message);
	exit(0);
}

$rrd = $api->rrdHeader($nodes, $api->getTitle(count($nodes)));
$rrd = array_merge($rrd, $api->rrdOptions());

$combine = array();
$count = 0;

while(list($junk, $node) = each($nodes)) {
	$r_node = $node;
	$node = preg_replace('/[a-z0-9_]/i', '_', $node);

	$percent = array();
	$t_count = 0;
	$total = array();

	foreach($api->getDS() as $key => $data) {
		$file_data = array();

		if(!is_int($key)) {
			$file_data['ext'] = $key;
		}

		$files = $api->rrdFiles($r_node, $file_data);
		if(empty($files)) {
			continue;
		}

		$defs = $api->rrdDef($node, $files, $data, $key);
		if(empty($defs)) {
			continue;
		}

		$rrd = array_merge($rrd, $defs);

		foreach($data as $ds => $junk) {
			$o_ds = $ds;
			$ds = $key . $ds;

			$percent['avg' . $ds] = sprintf(
				"CDEF:%s%s=percent%s%s,total%s%s,/,100,*",
				$ds, $node, $ds, $node, $o_ds, $node);
			$percent['max' . $ds] = sprintf(
				"CDEF:max%s%s=maxpercent%s%s,total%s%s,/,100,*",
				$ds, $node, $ds, $node, $o_ds, $node);
			$percent['min' . $ds] = sprintf(
				"CDEF:min%s%s=minpercent%s%s,total%s%s,/,100,*",
				$ds, $node, $ds, $node, $o_ds, $node);

			if($t_count == 0) {
				$total['avg' . $o_ds] = sprintf(
					"CDEF:total%s%s=percent%s%s",
					$o_ds, $node, $ds, $node);
				$total['max' . $o_ds] = sprintf(
					"CDEF:totalmax%s%s=percent%s%s",
					$o_ds, $node, $ds, $node);
				$total['min' . $o_ds] = sprintf(
					"CDEF:totalmin%s%s=percent%s%s",
					$o_ds, $node, $ds, $node);
			} else {
				$total['avg' . $o_ds] .= sprintf(
					",percent%s%s,ADDNAN", $ds, $node);
				$total['max' . $o_ds] .= sprintf(
					",maxpercent%s%s,ADDNAN", $ds, $node);
				$total['min' . $o_ds] .= sprintf(
					",minpercent%s%s,ADDNAN", $ds, $node);
			}

			if($count == 0) {
				$combine['avg' . $ds] =
					sprintf("CDEF:%s=%s%s",
					$ds, $ds, $node);
				$combine['max' . $ds] =
					sprintf("CDEF:max%s=max%s%s",
					$ds, $ds, $node);
				$combine['min' . $ds] =
					sprintf("CDEF:min%s=min%s%s",
					$ds, $ds, $node);
			} else {
				$combine['avg' . $ds] .=
					sprintf(",%s%s,ADDNAN",
					$ds, $node);
				$combine['max' . $ds] .=
					sprintf(",max%s%s,ADDNAN",
					$ds, $node);
				$combine['min' . $ds] .=
					sprintf(",min%s%s,ADDNAN",
					$ds, $node);
			}
		}

		$t_count++;
	}

	if($api->getConfig('percent')) {
		$rrd = array_merge($rrd, array_values($total));
		$rrd = array_merge($rrd, array_values($percent));
	}

	$count++;
}
reset($nodes);

$rrd = array_merge($rrd, array_values($combine));
$rrd = array_merge($rrd, $api->rrdDate());

foreach($api->getDS() as $key => $values) {
	foreach($values as $ds => $data) {
		if(!is_array($data)) {
			continue;
		}

		$ds = $key . $ds;
		$format = '%4.0lf%s';

		if(array_key_exists('format', $data)) {
			$format = $data['format'];
		}

		if($count > 1) {
			if($api->getConfig('combined_average')) {
				$rrd[] = sprintf("CDEF:%scombined=%s,%s,/",
					$ds, $ds, $count);
				$rrd[] = sprintf("CDEF:max%scombined=%s,%s,/",
					$ds, $ds, $count);
				$rrd[] = sprintf("CDEF:min%scombined=%s,%s,/",
					$ds, $ds, $count);

				$ds .= 'combined';
			}
		}

		if($data['scale']) {
			$rrd[] = sprintf("CDEF:%sscaled=%s,%s",
				$ds, $ds, $data['scale']);
			$rrd[] = sprintf("CDEF:max%sscaled=max%s,%s",
				$ds, $ds, $data['scale']);
			$rrd[] = sprintf("CDEF:min%sscaled=min%s,%s",
				$ds, $ds, $data['scale']);

			$ds .= 'scaled';
		}

		$rrd[] = sprintf("VDEF:last%s=%s,LAST", $ds, $ds);

		$rrd = array_merge($rrd, $api->rrdGraph($ds, $data));

		$rrd[] = sprintf("GPRINT:min%s:MIN:Min\\: %s	\\g",
			$ds, $format);
		$rrd[] = sprintf("GPRINT:%s:AVERAGE:Avg\\: %s	\\g",
			$ds, $format);
		$rrd[] = sprintf("GPRINT:max%s:MAX:Max\\: %s	\\g",
			$ds, $format);
		$rrd[] = sprintf("GPRINT:last%s:Last\\: %s\\j",
			$ds, $format);
	}
}

$out_file = '/tmp/yarf-' . $_SERVER['UNIQUE_ID'] . mt_rand() . mt_rand();
$return = rrd_graph($out_file, $rrd, count($rrd));

if(!is_array($return)) {
	$error = rrd_error();
	if(empty($error)) {
		$error = 'Unable to graph';
	}

	$api->sendHeaders();
	$api->showOutput('500', $error, $rrd);
	@unlink($out_file);
	exit(0);
}

if(array_key_exists('debug', $input)) {
	if($api->trueFalse($input['debug'], false)) {
		$api->sendHeaders();
		$api->showOutput('200', 'OK', $rrd);
		exit(0);
	}
}

$api->sendHeaders();
echo file_get_contents($out_file);
@unlink($out_file);

?>
