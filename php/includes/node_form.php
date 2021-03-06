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

$nf_label = 'Node(s)';

if(get_config('nodes', 'use_nodegroups')) {
	$nf_label = 'Expression';
}

?>

<div id="nf_main">
<form id="node_submit" onSubmit="return false;">
<label for="expression"><?php echo $nf_label; ?>:</label><br>
<?php
if(!empty($available_nodes)) {
	$nf_get_nodes = array();
	if(array_key_exists('expression', $_GET)) {
		$nf_get_nodes = explode(',', $_GET['expression']);
	}

	echo '<select id="expression" name="expression" multiple="multiple">';
	echo "\n";

	foreach($available_nodes as $node) {
		if(substr($node, 0, 1) == '#') {
			continue;
		}

		echo ' <option value="' . $node . '"';

		if(in_array($node, $nf_get_nodes)) {
			echo ' selected';
		}

		echo '>' . $node . '</option>' . "\n";
	}

	echo '</select>' . "\n";
} else {
?>
<textarea id="expression" name="expression"><?php echo $_GET['expression']; ?></textarea>
<?php
}
?>
</form>
</div>

<div id="nf_hide">
<span id="left_hide" class="layout_hide">hide</span>
</div>
