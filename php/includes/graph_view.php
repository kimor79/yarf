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
if(empty($req)) {
	echo 'Please select up to 4 graphs from the form above';
	return;
}

$row_combined = array();
$row_included = array();
$row_nodes = array();
$int = 0;

foreach($req['graph'] as $query) {
	parse_str($query, $graph);

	if(!array_key_exists($graph['data'], $data_types)) {
		continue;
	}

	$row_combined[$int] = $query;

	$nodes = $yarf->parseNodes($req['expression']);

	// file exists for $data_type
	$row_included[$int] = array(
		'included' => 1,
		'excluded' => 5,
	);

	foreach($nodes as $node) {
		$row_nodes[$node][$int] = $query;
	}


	$int++;
}

?>

<table>
 <tr>
<?php
foreach($row_combined as $row) {
	echo '  <td><img src="img/graph.php?expression=' . urlencode($req['expression']) . '&' . $row . '"></td>' . "\n";
}
?>
 </tr>
 <tr>
<?php
foreach($row_included as $row) {
	echo '  <td>' . $row['included'] . ' included<br>';
	echo $row['excluded'] . ' excluded</td>' . "\n";
}
?>
 </tr>
<?php
foreach($row_nodes as $node => $n_query) {
	echo ' <tr>' . "\n";
	foreach($n_query as $query) {
		echo '  <td><img src="img/graph.php?node=' . urlencode($node) . '&' . $query . '"></td>' . "\n";
	}
	echo ' </tr>';
}
?>
</table>
