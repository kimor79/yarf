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

require_once('yarf/includes/init.php');

$yui = rtrim(get_config('yui', 'base_uri'), '/');

?>

<html>
 <head>
  <title>YARF - Yet Another RRD Frontend</title>

<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/reset-fonts-grids/reset-fonts-grids.css">
<link rel="stylesheet" type="text/css" href="<?php echo $yui; ?>/assets/skins/sam/skin.css">
<script type="text/javascript" src="<?php echo $yui; ?>/utilities/utilities.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/container/container-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/event-mouseenter/event-mouseenter-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/selector/selector-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/event-delegate/event-delegate-min.js"></script> 
<script type="text/javascript" src="<?php echo $yui; ?>/resize/resize-min.js"></script>
<script type="text/javascript" src="<?php echo $yui; ?>/layout/layout-min.js"></script>
<script type="text/javascript" src="<?php echo $yui; ?>/menu/menu-min.js"></script>

<link rel="stylesheet" type="text/css" href="/css/default.css">

 </head>

<body class="yui-skin-sam">
 <div id="layouttop"><?php include('yarf/includes/graph_form.php'); ?></div>
 <div id="layoutleft"><?php include('yarf/includes/node_form.php'); ?></div>
 <div id="layoutcenter"><?php include('yarf/includes/graph_view.php'); ?></div>
 <div id="loading"></div>
</body>

<script type="text/javascript">
var Dom = YAHOO.util.Dom;
var Event = YAHOO.util.Event;

Event.onDOMReady(function() {
	var layout = new YAHOO.widget.Layout({
		units: [
			{
				body: 'layoutcenter',
				gutter: '0px 5px 5px 5px',
				position: 'center',
				scroll: true
			},
			{
				body: 'layoutleft',
				collapse: false,
				gutter: '0px 0px 5px 5px',
				position: 'left',
				resize: true,
				width: 250
			},
			{
				body: 'layouttop',
				collapse: false,
				gutter: '5px 5px 5px 5px',
				height: 150,
				position: 'top',
				resize: true
			}
		]
	});

	layout.render();

	var menu = new YAHOO.widget.MenuBar('quicklinks');
	menu.render();
	menu.show();

	loading = new YAHOO.widget.Panel('loading', {
		close: false,
		draggable: false,
		fixedcenter: true,
		modal: true,
		visible: false,
		width: "240px",
		zindex:4
	});

	loading.setHeader('Loading, please wait...');
	loading.setBody('<img src="<?php echo get_config('yui', 'loading_img'); ?>"/>');
	loading.render(document.body);

	Event.on('top_hide', 'click', function(ev) {
		layout.getUnitByPosition('top').toggle();
	});

	Event.on('left_hide', 'click', function(ev) {
		layout.getUnitByPosition('left').toggle();
	});

<?php
if(!empty($row_images)) {
	foreach($row_images as $key => $img) {
		printf("	document.images['graph%s'].src = '%s';\n",
			$key, $img);
	}
}

if(!empty($row_included)) {
	foreach($row_included as $row => $junk) {
		printf("
	var list%s = document.getElementById('nodelist%s');
	var panel%s = new YAHOO.widget.Panel('panel%s', {
		close: true,
		context: ['nodelistsum%s', 'tl', 'tl', ['beforeShow']],
		draggable: true,
		visible: false,
		zindex: 4
	});

	panel%s.render(document.body);
	panel%s.setBody(list%s);
	list%s.style.display = 'inline';
	Event.addListener('nodelistsum%s', 'click', panel%s.show, panel%s, true);
",
		$row, $row, $row, $row, $row, $row,
		$row, $row, $row, $row, $row, $row);
	}
}
?>
});

function parseExpression() {
	var param = '';

<?php
if(empty($available_nodes)) {
?>
	var expr = document.getElementById('expression').value;
	if(expr != '') {
		param = 'expression=' + encodeURIComponent(expr);
	}
<?php
} else {
?>
	var expr = document.getElementById('expression');

	var nodes = new Array();
	var n = 0;
	for(var i = 0; i < expr.options.length; i++) {
		if(expr.options[i].selected == true) {
			nodes[n] = expr.options[i].value;
			n++;
		}
	}

	if(n > 0) {
		param = 'expression=' + encodeURIComponent(nodes.join(','));
	}
<?php
}
?>

	return param;
}

function showQuickLink() {
	var select = document.getElementById('quicklink');
	var url = select.options[select.selectedIndex];

	if(url.value != '') {
		window.location = url.value;
		return;
	}

	window.location = window.location.pathname;
};

function submitGraph() {
	loading.show();
	var params = new Array();

	params[0] = parseExpression();

	for(var graph = 1; graph < 5; graph++) {
		var oForm = document.getElementById('graph' + graph);
		var elem = oForm.elements;

		if(oForm.elements['data'].value != '') {
			var param = new Array();

			var p = 0;
			for(var i = 0; i < elem.length; i++) {
				if(elem[i].value != '') {
					param[p] = elem[i].name + '=' + elem[i].value;
					p++;
				}
			}

			params[graph] = 'graph[]=' + encodeURIComponent(param.join('&'));
		}
	}

	window.location = '?' + params.join('&');
};
</script>
</html>
