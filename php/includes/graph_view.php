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

?>

<?php
if(empty($request)) {
	echo 'Please select up to 4 graphs from the form above';
	return;
}

$row_combined = array();
$row_images = array();
$row_included = array();
$row_nodes = array();
$int = 0;

foreach($request['graph'] as $query) {
	parse_str($query, $graph);

	if(!array_key_exists($graph['data'], $data_types)) {
		continue;
	}

	$row_combined[$int] = $query;

	if(!array_key_exists($int, $row_included)) {
		$row_included[$int] = array(
			'included' => array(),
			'excluded' => array(),
		);
	}

	$nodes = $yarf->parseNodes($request['expression']);

	$type = $data_types[$graph['data']];
	if(array_key_exists('class', $type)) {
		require_once('yarf/classes/' . $type['class']['file'] . '.php');
		$class = $type['class']['name'];
	} else {
		$class = 'Yarf';
	}

	if(array_key_exists('class_options', $type)) {
		$api = new $class($type['class_options']);
	} else {
		$api = $class;
	}

	while(list($junk, $node) = each($nodes)) {
		if($api->rrdExists($node)) {
			$row_included[$int]['included'][] = $node;
		} else {
			$row_included[$int]['excluded'][] = $node;
		}

		$row_nodes[$node][$int] = $query;
	}
	reset($nodes);

	$int++;
}

?>

<table id="gv_main">
 <tr class="gv_combined">
<?php
foreach($row_combined as $key => $row) {
	$row_images['c' . $key] = 'img/graph.php?expression=' . urlencode($request['expression']) . '&' . $row;
	echo '  <td><img id="graphc' . $key . '" src="' . get_config('yui', 'loading_img') . '"></td>' . "\n";
}
?>
 </tr>
 <tr class="gv_nodes">
<?php
foreach($row_included as $key => $row) {
	echo "  <td>";
	printf("<span onClick=\"showNodeList('nodelist%s')\">%s included<br>%s excluded</span>",
		$key, count($row['included']), count($row['excluded']));
	echo "</td>\n";
}
?>
 </tr>
<?php
$int = 0;
foreach($row_nodes as $node => $n_query) {
	echo ' <tr class="gv_single">' . "\n";
	foreach($n_query as $query) {
		$row_images['n' . $int] = 'img/graph.php?node=' . urlencode($node) . '&' . $query;
		echo '  <td><img id="graphn' . $int . '" src="' . get_config('yui', 'loading_img') . '"></td>' . "\n";
		$int++;
	}
	echo ' </tr>';
}
?>
</table>

<?php
foreach($row_included as $key => $row) {
	printf("<div id=\"nodelist%s\" class=\"gv_nodelist\">\n", $key);

	if(!empty($row['included'])) {
		echo "<label>Included</label><br>\n";
		echo implode("<br>\n", $row['included']);
		echo "<br>\n";
	}

	if(!empty($row['excluded'])) {
		echo "<label>Excluded</label><br>\n";
		echo implode("<br>\n", $row['excluded']);
		echo "<br>\n";
	}

	echo "</div>\n";
}
?>
