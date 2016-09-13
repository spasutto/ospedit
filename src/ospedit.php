<?php
/*
MIT License

Copyright (c) 2016 Sylvain PASUTTO

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 0");

$url_script = "http".(!empty($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['SERVER_NAME'].":".$_SERVER['SERVER_PORT'].$_SERVER['REQUEST_URI'];

$password='';
if(isset($_POST['password']))
	$password = htmlspecialchars($_POST['password']);
else if(isset($_GET['password']))
	$password = htmlspecialchars($_GET['password']);

if ($password != "###_YOUR_PASSWORD_HERE_###")
{
?>
<center>
	<form method="POST">
	Please enter your password : <BR>
		<input type="password" name="password" autofocus>
		<input type="submit" value="connect">
	</form>
</center>
<?php
	exit(0);
}

$file='';
if(isset($_POST['file']))
	$file = htmlspecialchars($_POST['file']);
else if(isset($_GET['file']))
	$file = htmlspecialchars($_GET['file']);

$content='';
if (strlen(trim($file)) > 0 && @file_exists($file) && !@is_dir($file))
{
	$opts = array('http' => array('header' => 'Accept-Charset: UTF-8, *;q=0'));
	$context = stream_context_create($opts);
	$content = file_get_contents($file, false, $context);
}

$operation='';
if(isset($_POST['operation']))
	$operation = htmlspecialchars($_POST['operation']);
else if(isset($_GET['operation']))
	$operation = htmlspecialchars($_GET['operation']);


if ($operation=="load")
{
//sleep(3);
	echo json_encode(["status"=>"ok", "message"=> "", "content"=> $content]);
	exit(0);
}
else if ($operation=="save")
{
	if (true/*file_exists($file)*/)
	{
		if(isset($_POST['content']) && strlen(trim($_POST['content']))>0)
		{
			$content = $_POST['content'];
			if (file_put_contents($file, htmlspecialchars_decode($content)))
				echo json_encode(["status"=>"ok", "message"=> "file saved."]);
		}
	}
	else
		echo json_encode(["status"=>"ko", "message"=> "\"".$file."\""." doesn't exists"]);
	exit(0);
}
else if ($operation=="backup")
{
	$today =getdate();
	if (is_file($file))
	{
		copy($file, $file.".".$today['0']);
		echo json_encode(["status"=>"ok", "message"=> "\"".$file."\""." backuped in ".$file.".".$today['0']]);
	}
	else
		echo json_encode(["status"=>"ko", "message"=> "\"".$file."\""." doesn't exists"]);
	exit(0);
}
else if ($operation=="delete")
{
	if (is_file($file))
	{
		if (unlink($file))
			echo json_encode(["status"=>"ok", "message"=> "\"".$file."\""." removed."]);
		else
			echo json_encode(["status"=>"ko", "message"=> "error while removing ".$file]);
	}
	else
		echo json_encode(["status"=>"ko", "message"=> "\"".$file."\""." doesn't exists"]);
	exit(0);
}
else if ($operation=="rename")
{
	$oldfile='';
	if(isset($_POST['oldfile']))
		$oldfile = htmlspecialchars($_POST['oldfile']);
	else if(isset($_GET['file']))
		$oldfile = htmlspecialchars($_GET['oldfile']);
	if (!file_exists($oldfile))
		echo json_encode(["status"=>"ko", "message"=> "\"".$oldfile."\""." doesn't exists"]);
	else if (file_exists($file))
		echo json_encode(["status"=>"ko", "message"=> "\"".$file."\""." already exists"]);
	else
	{
		rename($oldfile, $file);
		echo json_encode(["status"=>"ok", "message"=> "\"".$oldfile."\""." renamed to ".$file]);
	}
	exit(0);
}
else if (strlen(trim($operation))>0)
{
	echo json_encode(["status"=>"ko", "message"=> "error: unknown operation"]);
	exit(0);
}

$searchterm='';
if(isset($_POST['term']))
	$searchterm = htmlspecialchars($_POST['term']);
else if(isset($_GET['term']))
	$searchterm = htmlspecialchars($_GET['term']);
if (strlen(trim($searchterm))>0)
{
//sleep(1);
	$rep = '';
	$islash = strrpos($searchterm, '/');
	$ibslash = strrpos($searchterm, '\\');
	$islash = $islash ?: $ibslash;
	if ($islash !== FALSE)
	{
		$islash = max($islash, $ibslash);
		if ($islash >= 0)
		{
			$rep = trim(substr($searchterm, 0, $islash));
			$searchterm = trim(substr($searchterm, $islash+1));
		}
	}
	//echo '[{"id":"Somateria mollissima","label":"Common Eider","value":"Common Eider"},{"id":"Circus pygargus","label":"Montagu`s Harrier","value":"Montagu`s Harrier"}]';
	echo '[';
	$i=0;
	if (!@is_dir($rep))
		$rep = ".";
	$scans = @scandir($rep);
	$filestotal = array();
	foreach($scans as $scan)
	{
		if (!fnmatch($searchterm."*", $scan, FNM_CASEFOLD))
			continue;
		$filescan = $scan;
		//if (trim($searchterm) != "." && trim($searchterm) == "..")
			$filescan = $rep."/".$scan;
		if (is_dir($filescan))
			$filestotal[] = array("type" => "folder" , "value"=> $filescan."/");
		else
			$filestotal[] = array("type"=> "file" , "value"=> $filescan);
	}
	$arrayLength = count($filestotal);
	for ($j = 0; $j < $arrayLength; $j++)
	{
		if ($filestotal[$j]["type"] != "folder") continue;
		if ($i>0) echo ",";
		echo '{"type":"folder","value":"'.$filestotal[$j]["value"].'"}';
		$i++;
	}
	for ($j = 0; $j < $arrayLength; $j++)
	{
		if ($filestotal[$j]["type"] != "file") continue;
		if ($i>0) echo ",";
		echo '{"type":"file","value":"'.$filestotal[$j]["value"].'"}';
		$i++;
	}
	echo ']';

	exit(0);
}

$disableedit='0';
if(isset($_POST['disableedit']))
	$disableedit = htmlspecialchars($_POST['disableedit']);
else if(isset($_GET['disableedit']))
	$disableedit = htmlspecialchars($_GET['disableedit']);
$disableedit = $disableedit=='1'?TRUE:FALSE;

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<title>OSPEdit v0.2</title>
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css">
		<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
		<script src="//code.jquery.com/jquery-1.12.3.min.js" type="text/javascript"></script>
		<script src="//code.jquery.com/ui/1.12.0/jquery-ui.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.2.5/ace.js" type="text/javascript" charset="utf-8"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/spin.js/2.3.2/spin.js" type="text/javascript" charset="utf-8"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript">
		var editor = null;
		var disableedit = <?php echo $disableedit==TRUE?"true":"false";?>;
		var url_script = "<?php echo $url_script;?>";
		var types_ext = {
			"javascript" : ["js"],
			"html" : ["htm","html"],
			"css" : ["css"],
			"php" : ["php", "php3", "php4"]
		};
		var interval_loading = null;
		var div_loading;
		var div_message;
		var show_loading = true;
		var has_changes = false;
		var current_file = "";
		var spinner = null;
		function Init()
		{
	 		div_loading = $("#loading");
			div_message = $("#message");
			disableedit = disableedit || is_touch_device() || typeof ace != "object";
			if (!disableedit)
				$('#content').hide();
			else
				$('#editor').hide();
			initeditor();
			initcatcomplete();
			$( "#file" ).catcomplete({
				source: function (request, response) {
					show_loading = false;
					$.post(url_script, {password: $("#password").val(), term: request.term}, response, "json");
				},
				delay: 0,//minLength: 2,
				select: function( event, ui ) {
					//log( ui.item ? "Selected: " + ui.item.value + " aka " + ui.item.id : "Nothing selected, input was " + this.value );
					$("#file").val(ui.item.value);
					if (ui.item.type != "folder")
					{
						setTimeout(function(){
							doload();
						},0);
					}
					//else
					//	setTimeout(function(){ $("#file").autocomplete('search', ui.item.value); }, 1000);
				}
			});
			$( "#file" ).on("keyup paste cut", function(){toggle_btns();});
			$(document).keydown(function(e) {
				if ((e.which == '115' || e.which == '83' ) && (e.ctrlKey || e.metaKey))
				{
					e.preventDefault();
					dosave();
					return false;
				}
				return true;
			});
			$(document).ajaxStart(function() {if (show_loading){toggle_btns(true);loading(true);}});
			$(document).ajaxStop(function() {toggle_btns(false);loading(false);});
			initspinner();
		}
		function initeditor()
		{
			if (!disableedit)
			{
				window.editor = ace.edit("editor");
				window.editor.setTheme("ace/theme/twilight");
				var filename = $("#file").val();
				var filext=filename.substring(filename.lastIndexOf(".")+1, filename.length);
				filext=filext.toLowerCase();
				window.editor.session.setMode("ace/mode/" + getModeFromExt(filext));
				window.editor.on("input", function() {
					has_changes = !window.editor.session.getUndoManager().isClean();
					/*redraw_btns*/toggle_btns();
				});
			}
			else
			{
				var timer, editor = $("#content");
				editor.on("keydown paste cut", function(){
					clearTimeout(timer);
					var origvalue = this.value, self = this;
					timer = setTimeout(function(){
						has_changes = ( origvalue !== self.value );
						/*redraw_btns*/toggle_btns();
					},0);
				});
			}
			has_changes = false;
			/*redraw_btns*/toggle_btns();
		}
		function initcatcomplete()
		{
			$.widget( "custom.catcomplete", $.ui.autocomplete,
			{
				_create: function() {
					this._super();
					this.widget().menu( "option", "items", "> :not(.ui-autocomplete-type)" );
				},
				_renderMenu: function( ul, items ) {
					var that = this;
					$.each( items, function( index, item ) {
						var li;
						li = that._renderItemData( ul, item );
						if ( item.type == "folder")
							li.addClass("ui-menu-item-folder");
					});
				}
			});
		}
		function initspinner()
		{
      if (typeof Spinner !== "object" && typeof Spinner !== "function")
        window.Spinner = function(){this.spin=function (){div_loading.show();};this.stop=function (){div_loading.hide();};};
			var opts = {
				lines: 13 // The number of lines to draw
			, length: 28 // The length of each line
			, width: 14 // The line thickness
			, radius: 42 // The radius of the inner circle
			, scale: 1 // Scales overall size of the spinner
			, corners: 1 // Corner roundness (0..1)
			, color: '#7FB9F2' // #rgb or #rrggbb or array of colors
			, opacity: 0.25 // Opacity of the lines
			, rotate: 0 // The rotation offset
			, direction: 1 // 1: clockwise, -1: counterclockwise
			, speed: 1 // Rounds per second
			, trail: 60 // Afterglow percentage
			, fps: 20 // Frames per second when using setTimeout() as a fallback for CSS
			, zIndex: 2e9 // The z-index (defaults to 2000000000)
			, className: 'spinner' // The CSS class to assign to the spinner
			, top: '50%' // Top position relative to parent
			, left: '50%' // Left position relative to parent
			, shadow: false // Whether to render a shadow
			, hwaccel: false // Whether to use hardware acceleration
			, position: 'absolute' // Element positioning
			}
			spinner = new Spinner(opts);
		}
		function loading(bloading)
		{
			if (bloading)
				spinner.spin($('#spinner')[0]);
			else
				spinner.stop();
		}
		function redraw_btns()
		{
			$("#savebtn")[0].disabled = !has_changes;
		}
		function toggle_btns(bdisable)
		{
			var is_file_selected = $.trim($("#file").val()).length > 0;
			$("#loadbtn")[0].disabled = !is_file_selected || bdisable;
			$("#savebtn")[0].disabled = !has_changes || bdisable;
			$("#bkpbtn")[0].disabled = !is_file_selected || bdisable;
			$("#delbtn")[0].disabled = !is_file_selected || bdisable;
			$("#renbtn")[0].disabled = !is_file_selected || bdisable;
		}
		function is_touch_device()
		{
			try
			{
				document.createEvent("TouchEvent");
				return true;
			} catch (e) { return false; }
		}
		function show_message(message, bError)
		{
			if (toastr)
			{
				var ltoastr = bError ? toastr.error : toastr.info;
				ltoastr(message);
			}
			else
			{
				div_message.html( message );
				div_message.css('color', bError?'red':'black');
			}
		}
		function trygetdata(data, bdisplay)
		{
			bdisplay = typeof bdisplay === "boolean" ? bdisplay : false;
			try
			{
				data = $.parseJSON(data);
			}
			catch(e)
			{
				data = typeof data === "string" ? data : "";
				data = {status : 'ko', message :'Error while retrieving server content:\r\n' + data.substring(0, 150)};
			}
			if (bdisplay && typeof data.message === "string" && $.trim(data.message).length > 0)
				show_message(data.message, data.status == "ko");
			return data;
		}
		function doload()
		{
			var filename = $("#file").val();
			if ($.trim(filename).length <= 0)
				return false;
			if (has_changes && !confirm("Warning, you have pending changes, are you sure?"))
			{
				$("#file").val(current_file);
				return false;
			}
			show_loading = true;
			$.post( url_script,
				{ password: $("#password").val(), operation: "load", file: $("#file").val() },
				function( data )
				{
					data = trygetdata(data, true);
					if (data.status == "ok")
					{
						current_file = $("#file").val();//alert('loaded');
						if (!disableedit)
							editor.getSession().setValue(data.content);
						else
							$('#content').val(data.content);
						initeditor();
					}
				}
			);
			return false;
		}
		function dosave()
		{
			var filecontent = $('#content').val();
			if (!disableedit)
				filecontent = editor.getSession().getValue();
			if (!disableedit)
			{
				editor.session.getUndoManager().markClean();
				$("#savebtn")[0].disabled = editor.session.getUndoManager().isClean()
			}
			show_loading = true;
			$.post( url_script,
				{ password: $("#password").val(), operation: "save", file: $("#file").val(), content: filecontent },
				function( data ) { data = trygetdata(data, true);if (data.status == "ok") has_changes = false; }
			);
			return true;
		}
		function dobackup()
		{
			var filename = $("#file").val();
			if ($.trim(filename).length <= 0)
				return false;
			show_loading = true;
			$.post( url_script,
				{ password: $("#password").val(), operation: "backup", file: $("#file").val() },
				function( data ) { trygetdata(data, true); }
			);
			return false;
		}
		function dodelete()
		{
			var filename = $("#file").val();
			if ($.trim(filename).length <= 0 || !confirm('are you sure you want to delete "'+filename+'"?'))
				return false;
			show_loading = true;
			$.post( url_script,
				{ password: $("#password").val(), operation: "delete", file: $("#file").val() },
				function( data ) { trygetdata(data, true); }
			);
			return false;
		}
		function dorename()
		{
			var filename = $("#file").val();
			if ($.trim(filename).length <= 0)
				return false;
			var newfile = prompt("Enter new name for " + filename, filename);
			if (newfile == null || $.trim(newfile) == $.trim(filename))
					return ;
			show_loading = true;
			$.post( url_script,
				{ password: $("#password").val(), operation: "rename", file: newfile, oldfile: filename },
				function( data )
				{
					data = trygetdata(data, true);
					if (data.status == "ok")
					{
						if (confirm("Go to new file"+(has_changes?" (changes will not be saved)":"")+"?"))
						{
							$("#file").val(newfile);
							$("#formfile").submit();
						}
					}
				}
			);
			return false;
		}
		function getModeFromExt(ext)
		{
			for (prop in types_ext)
				for (i=0; i<types_ext[prop].length; i++)
					if (types_ext[prop][i]== ext)
						return prop;
			return "php";
		}
	</script>
		<style type="text/css">
		input {
			display: inline;
		}
		a#filelink {
			margin: 0px 20px;
		}
		input#button {
			margin: 0px 20px;
		}
		textarea {
			/*display: none;*/
		}
		body {
			overflow: hidden;
			font-family: tahoma;
		}
		html, body {
			height: 100%;
		}
		#content {
			width: 100%;
			height: 100%;
		}
		#editor {
			margin: 0;
			position: absolute;
			top: 40px;
			bottom: 0;
			left: 0;
			right: 0;
		}
		#message {
			font-family: monospace;
			margin-left: 10px;
		}
		#loading {
			display:none;
		}
		.ui-autocomplete {
			max-height: 600px;
			overflow-y: auto;
		/* prevent horizontal scrollbar */
			overflow-x: hidden;
		}
		/* IE 6 doesn't support max-height	 * we use height instead, but this forces the menu to always be this tall	 */
		* html .ui-autocomplete {
			height: 600px;
		}
		.ui-menu-item-folder {
			font-weight: bold;
			color : rgb(255,153,0);
		}
		</style>
	</head>
	<body onload="Init();">
		<form id="formfile" name="formfile" method="post" action="<?php echo $url_script;?>" onsubmit="return false;">
			<input type="hidden" name="password" id="password" value="<?php echo $password;?>"/>
			<input type="text" name="file" id="file" value="<?php echo $file;?>" autofocus/>
			<a id="filelink" href="<?php echo $file;?>" target="_blank"><?php echo $file;?></a>
			<button name="loadbtn" id="loadbtn" onclick="doload()" disabled>load</button>
			<button name="savebtn" id="savebtn" onclick="dosave()" disabled>save</button>
			<button name="bkpbtn" id="bkpbtn" onclick="dobackup()" disabled>backup</button>
			<button name="delbtn" id="delbtn" onclick="dodelete()" disabled>delete</button>
			<button name="renbtn" id="renbtn" onclick="dorename()" disabled>rename</button>
			<span id="loading" style="display:none;">Loading...</span>
			<span id="message"></span>
			<textarea name="content" id="content" cols="190" rows="35"><?php echo htmlspecialchars($content);?></textarea>
		</form>
		<div id="editor"><?php echo htmlspecialchars($content);?></div>
		<div id="spinner"></div>
	</body>
</html>
