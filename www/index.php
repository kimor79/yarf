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

$yui = rtrim(get_config('yui', 'base_uri'), '/');

?>

<html>
 <head>
  <title>YARF - Yet Another RRD Frontend</title>

<style type="text/css">
body {
	margin: 0px;
	padding: 0;
}

#expression {
	height: 70%;
	margin: 1%;
	padding: 0;
	width: 98%;
}

label {
	font-weight: bold;
}

.img {
	border: none;
}
</style>
<link rel="stylesheet" type="text/css"href="<?php echo $yui; ?>/reset-fonts-grids/reset-fonts-grids.css">
<!-- Skin CSS files resize.css must load before layout.css -->
<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/assets/skins/sam/resize.css">
<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/assets/skins/sam/layout.css">
<!-- Utility Dependencies -->
<script type="text/javascript" src="<?php echo $yui; ?>/yahoo-dom-event/yahoo-dom-event.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/dragdrop/dragdrop-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/element/element-min.js"></script> 
<!-- Optional Animation Support-->
<?php /*
<script type="text/javascript" src="<?php echo $yui; ?>/animation/animation-min.js"></script> 
*/ ?>
<!-- Optional Resize Support -->
<script type="text/javascript" src="<?php echo $yui; ?>/resize/resize-min.js"></script>
<!-- Source file for the Layout Manager -->
<script type="text/javascript" src="<?php echo $yui; ?>/layout/layout-min.js"></script>

 <head>

<body class="yui-skin-sam">
<?php
if(get_config('use_nodegroups')) {
?>
 <div id="layouttop"><?php include('yarf/v1/includes/node_form.php'); ?></div>
 <div id="layoutleft"><?php include('yarf/v1/includes/graph_form.php'); ?></div>
<?php
} else {
?>
 <div id="layouttop"><?php include('yarf/v1/includes/graph_form.php'); ?></div>
 <div id="layoutleft"><?php include('yarf/v1/includes/node_form.php'); ?></div>
<?php
}
?>
 <div id="layoutcenter"><?php include('yarf/v1/includes/graph_view.php'); ?></div>
 <div id="layoutright"><?php include('yarf/v1/includes/node_list.php'); ?></div>
</body>

<script type="text/javascript">
var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

Event.onDOMReady(function() {
	var layout = new YAHOO.widget.Layout({
		units: [
			{
				body: 'layoutcenter',
				gutter: '5px',
				position: 'center'
			},
			{
				body: 'layoutleft',
				collapse: false,
				gutter: '5px',
				position: 'left',
				resize: true,
				width: 250
			},
			{
				body: 'layoutright',
				collapse: false,
				gutter: '5px',
				position: 'right',
				resize: true,
				width: 200
			},
			{
				body: 'layouttop',
				collapse: false,
				gutter: '5px',
				height: 150,
				position: 'top',
				resize: true
			}
		]
	});

	layout.render();
});
</script>
</html>
