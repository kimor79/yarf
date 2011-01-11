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

$default_format = 'print_r';

require_once('yarf/v1/includes/config.php');
require_once('yarf/v1/includes/available_graphs.php');

$desired_graphs = array(
	0 => false, // So the key numbers align with graph labels
);
if(array_key_exists('graph', $req)) {
	foreach($req['graph'] as $graph_string) {
		parse_str($graph_string, $desired_graphs[]);
	}
}

?>

<table width="99%">
 <tr>
<?php
for($graph_num = 1; $graph_num < 5; $graph_num++) {
?>
<!-- Begin column <?php echo $graph_num; ?> -->
  <td>

<form id="graph<?php echo $graph_num; ?>" onSubmit="return false;">
<table>
 <tr>
  <th colspan="2">Graph <?php echo $graph_num; ?></th>
 </tr>
 <tr>
  <td><label for="archive">Archive: </label></td>
  <td><input type="text" id="archive" name="archive" value="<?php
if(array_key_exists($graph_num, $desired_graphs)) {
	if(array_key_exists('archive', $desired_graphs[$graph_num])) {
		echo $desired_graphs[$graph_num]['archive'];
	}
}
?>" size="7"></td>
 </tr>
 <tr>
  <td><label>Time: </label></td>
  <td>
<select name="t_val">
<?php
	for($time_num = 1; $time_num < 13; $time_num++) {
		echo ' <option ' . "\n";
		if(array_key_exists($graph_num, $desired_graphs)) {
			if(array_key_exists('t_val', $desired_graphs[$graph_num])) {
				if($desired_graphs[$graph_num]['t_val'] == $time_num) {
					echo 'selected ';
				}
			}
		}
		printf("value=\"%s\">%s</option>\n", $time_num, $time_num);
	}
?>
</select>
<select name="t_unit">
<?php
	foreach($time_units as $time_unit) {
		echo ' <option ' . "\n";
		if(array_key_exists($graph_num, $desired_graphs)) {
			if(array_key_exists('t_unit', $desired_graphs[$graph_num])) {
				if($desired_graphs[$graph_num]['t_unit'] == $time_unit) {
					echo 'selected ';
				}
			}
		}
		printf("value=\"%s\">%s</option>\n", $time_unit, $time_unit);
	}
?>
</select>
  </td>
 </tr>
 <tr>
  <td><label for="data">Data: <label></td>
  <td>
<select name="data">
 <option value=""></option>
<?php
	foreach($available_graphs as $type => $data) {
		echo ' <option ' . "\n";
		if(array_key_exists($graph_num, $desired_graphs)) {
			if(array_key_exists('data', $desired_graphs[$graph_num])) {
				if($desired_graphs[$graph_num]['data'] == $type) {
					echo 'selected ';
				}
			}
		}
		printf("value=\"%s\">%s</option>\n", $type, $type);
	}
?>
</select>
  </td>
 </tr>
</table>
</form>

<!-- End column <?php echo $graph_num; ?> -->
  </td>
<?php
}
?>
 </tr>
 <tr>
  <td colspan="4" align=center>
<form id="submit_graph" onSubmit="submitGraph(); return false;">
 <input type="submit" name="graph" value="Graph">
</form>
  </td>
 </tr>
</table>

<span id="top_toggle">toggle</span>
