<?php
/*
MIT License

Copyright (c) 2018 Sylvain PASUTTO

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

$dirupload='data/upload/';
if(isset($_POST['dirupload']))
	$dirupload = htmlspecialchars($_POST['dirupload']);
else if(isset($_GET['dirupload']))
	$dirupload = htmlspecialchars($_GET['dirupload']);
	

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

?>

<html>
<head>
  <meta charset="utf-8">
		<link rel="stylesheet" href="//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css">
		<script src="//code.jquery.com/jquery-1.12.3.min.js" type="text/javascript"></script>
		<script src="//code.jquery.com/ui/1.12.0/jquery-ui.js"></script>
		<script type="text/javascript">
		var url_script = "<?php echo $url_script;?>";
		function Init()
		{
			initcatcomplete();
			$( "#dirupload" ).catcomplete({
				source: function (request, response) {
					$.post(url_script, {password: $("#password").val(), term: request.term}, response, "json");
				},
				delay: 0,//minLength: 2,
				select: function( event, ui ) {
					$("#dirupload").val(ui.item.value);
				}
			});
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
	</script>
		<style type="text/css">
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

    <form enctype="multipart/form-data" action="<?php echo $url_script;?>" method="post">
        <input id="password" type="hidden" name="password" value="<?php echo $password;?>" />
        <input type="hidden" name="MAX_FILE_SIZE" value="1000000000" />
        <input id="dirupload" type="text" name="dirupload" value="<?php echo $dirupload;?>" />
        Upload file <input type="file" name="myufile" />
        <input type="submit" />
    </form>
  </body>
</html>

<?php
if (isset($_FILES["myufile"]))
{
    if (!is_uploaded_file($_FILES["myufile"]["tmp_name"]))
    {
        echo "Can't upload the file (too big?)";
        return ;
    }
?>
<?php
$nomDestination = $_FILES["myufile"]["name"];

    if (file_exists($dirupload.$nomDestination))
        echo "<span style='color:#ff0000;font-weight: 900;'>The destination file <b>\"".$nomDestination."\"</b> already exists in destination directory <b>\"".$dirupload."\"</b>!!!</span>";
    else if (rename($_FILES["myufile"]["tmp_name"],
                   $dirupload.$nomDestination)) {
        echo "The temp file <b>\"".$_FILES["myufile"]["tmp_name"].
                "\"</b> has been moved to <b>\"".$dirupload.$nomDestination."\"</b>";
    } else {
        echo "<span style='color:#ff0000;font-weight: 900;'>Error while moving the uploaded file".
                " please check the directory ".$dirupload."</span>";
    }          

?>
</body>
</html>
<?php
}
?>
