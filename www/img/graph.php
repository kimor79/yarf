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
$api = new $class();

$api->setParameters($req, array('outputFormat' => $default_format));
$input = $api->setInput($req);

$errors = $api->validateInput($input, $api->required, $api->optional);

if(!empty($errors)) {
	$api->sendHeaders();
	$api->showOutput('400', implode("\n", $errors));
	exit(0);
}

$errors = array();

if(!array_key_exists('node', $input) && !array_key_exists('expression', $input)) {
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
	// $nodegroups->getNodesFromExpression()
	$nodes = array_merge($nodes, explode(',', $input['expression']));
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

$nodes = array_unique($nodes);

$exists = array();
foreach($nodes as $node) {
	if($api->rrdExists($node, $input)) {
		$exists[] = $node;
	}
}

if(empty($exists)) {
	$api->sendHeaders();
	$api->showOutput('400', 'No nodes for this data set');
	exit(0);
}

$options = $api->rrdOptions($exists, $input);

$out_file = '/tmp/yarf-' . $_SERVER['UNIQUE_ID'] . mt_rand() . mt_rand();
$return = rrd_graph($out_file, $options, count($options));

if(!is_array($return)) {
	$error = rrd_error();
	if(empty($error)) {
		$error = 'Unable to graph';
	}

	$api->sendHeaders();
	$api->showOutput('500', $error, $options);
	@unlink($out_file);
	exit(0);
}

if(array_key_exists('debug', $input)) {
	if($api->trueFalse($input['debug'], false)) {
		$api->sendHeaders();
		$api->showOutput('200', 'OK', $options);
		exit(0);
	}
}

$api->sendHeaders();
echo file_get_contents($out_file);
@unlink($out_file);

?>
