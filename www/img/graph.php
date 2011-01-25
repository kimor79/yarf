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

if(!array_key_exists('data', $req)) {
	$yarf->sendHeaders();
	$yarf->showOutput('400', 'Missing data');
	exit(0);
}

if(!array_key_exists($req['data'], $data_types)) {
	$yarf->sendHeaders();
	$yarf->showOutput('400', 'No such data type');
	exit(0);
}

$data_type = $data_types[$req['data']];

require_once('yarf/classes/' . $data_type['file'] . '.php');

unset($req['data']);

$class = $data_type['class'];

if(array_key_exists('class_options', $data_type)) {
	$api = new $class($data_type['class_options']);
} else {
	$api = new $class();
}

$api->setParameters($req, array('outputFormat' => $default_format));
$input = $api->setInput($req);

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

$nodes = array();

if(array_key_exists('expression', $input)) {
	$nodes = $api->parseNodes($input['expression']);
}

if(array_key_exists('node', $input)) {
	$nodes = array_merge($nodes, $input['node']);
}

if(empty($nodes)) {
	$api->sendHeaders();
	$api->showOutput('400', 'No nodes to graph');
	exit(0);
}

unset($input['expression']);
unset($input['node']);

// See the comments at
// http://php.net/manual/en/function.array-unique.php
// as to why this is faster than array_unique()
$nodes = array_merge(array_flip(array_flip($nodes)));

$exists = array();
while(list($junk, $node) = each($nodes)) {
	if($api->rrdExists($node, $input)) {
		$exists[] = $node;
	}
}
reset($nodes);

if(empty($exists)) {
	$message = 'No nodes for this data set';
	if(count($nodes) == 1) {
		$message = 'No graph for ' . $nodes[0];
	}

	$api->sendHeaders();
	$api->showOutput('400', $message);
	exit(0);
}

$rrd = $api->rrdHeader($nodes, $input, $api->getTitle());
$rrd = array_merge($rrd, $api->rrdOptions());

$combine = array();
$count = 0;

while(list($junk, $node) = each($nodes)) {
	$files = $api->rrdFiles($node, $input);
	if(empty($files)) {
		continue;
	}

	$node = str_replace('.', '_', $node);

	$defs = $api->rrdDef($node, $files, $api->getDS());
	if(empty($defs)) {
		continue;
	}

	$rrd = array_merge($rrd, $defs);

	foreach($api->getDS() as $ds => $junk) {
		if($count == 0) {
			$combine['avg' . $ds] = sprintf("CDEF:%s=%s%s",
				$ds, $ds, $node);
			$combine['max' . $ds] = sprintf("CDEF:max%s=%s%s",
				$ds, $ds, $node);
			$combine['min' . $ds] = sprintf("CDEF:min%s=%s%s",
				$ds, $ds, $node);
		} else {
			$combine['avg' . $ds] .= sprintf(",%s%s,ADDNAN",
				$ds, $node);
			$combine['max' . $ds] .= sprintf(",max%s%s,ADDNAN",
				$ds, $node);
			$combine['min' . $ds] .= sprintf(",min%s%s,ADDNAN",
				$ds, $node);
		}
	}

	$count++;
}
reset($nodes);

$rrd = array_merge($rrd, array_values($combine));
$rrd = array_merge($rrd, $api->rrdDate($input));

foreach($api->getDS() as $ds => $data) {
	$format = '%4.0lf%s';

	if(array_key_exists('format', $data)) {
		$format = $data['format'];
	}

	if($count > 1) {
		if($api->combinedAverage()) {
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

	$rrd[] = sprintf("GPRINT:min%s:MIN:Min\\: %s	\\g", $ds, $format);
	$rrd[] = sprintf("GPRINT:%s:AVERAGE:Avg\\: %s	\\g", $ds, $format);
	$rrd[] = sprintf("GPRINT:max%s:MAX:Max\\: %s	\\g", $ds, $format);
	$rrd[] = sprintf("GPRINT:last%s:Last\\: %s\\j", $ds, $format);
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
