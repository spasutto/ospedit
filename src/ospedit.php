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
  $fc = file_get_contents($file);
  $fenc = mb_detect_encoding($fc);
  if ($fenc && strtolower($fenc) != 'utf-8') {
    $fc2 = iconv(/*'windows-1250'*/$fenc, 'utf-8', $fc);
    if ($fc2) $fc = $fc2;
  }
  $handle = fopen("php://memory", "rw");
  fwrite($handle, $fc);
  fseek($handle, 0);
  $fsize = filesize($file);
  $content = "";
  if ($fsize > 0)
    $content = fread($handle,$fsize);
  // remove BOM https://fr.wikipedia.org/wiki/Indicateur_d%27ordre_des_octets
  if (substr($content, 0, 3) == chr(0xef).chr(0xbb).chr(0xbf))
    $content = substr($content, 3);
  else if (substr($content, 0, 2) == chr(0xfe).chr(0xff))
    $content = substr($content, 2);
}

$operation='';
if(isset($_POST['operation']))
  $operation = htmlspecialchars($_POST['operation']);
else if(isset($_GET['operation']))
  $operation = htmlspecialchars($_GET['operation']);

if ($operation=="load")
{
//sleep(3);
  if (!file_exists($file) || !is_file($file))
    echo json_encode(["status"=>"ko", "message"=> "\"".$file."\""." isn't a file"]);
  else
  {
    $response = json_encode(["status"=>"ok", "message"=> "", "content"=> $content]);
    if (json_last_error() != JSON_ERROR_NONE)
    {
      $response = json_encode(["status"=>"ok", "message"=> "", "content"=> utf8_encode($content)]);
      if (json_last_error() != JSON_ERROR_NONE)
        $response = json_encode(["status"=>"ko", "message"=> "unable to encode \"".$file."\" : ".json_last_error_msg()]);
    }
    echo $response;
  }
  exit(0);
}
else if ($operation=="save")
{
  $lastchar = substr(trim($file), -1);
  $dirfile = dirname($file);
  if (!file_exists($dirfile))
    mkdir($dirfile, 0755, true);
  //if (strlen($content)<=0 && strlen(trim(basename($file))) == 0)
  if ($lastchar == "/" || $lastchar == "\\")
  {
      mkdir($file, 0755, true);
      echo json_encode(["status"=>"ok", "message"=> "directory ".$file." created"]);
      exit(0);
  }
  if(!isset($_POST['content']))
    $content = "";
  else
    $content = $_POST['content'];
  $fenc = mb_detect_encoding($content);
  $content = iconv($fenc, 'utf-8', $content);
  $backup = isset($_POST['backup']) && ($_POST['backup'] == '1' || strtolower($_POST['backup']) == 'true');
  if (@file_put_contents($file, $content) !== FALSE)
    echo json_encode(["status"=>"ok", "message"=> "file ".($backup?"backuped to ".$file:"saved")]);
  else
  {
    $message = "unable to save file \"".$file."\"";
    $error = @error_get_last()['message'];
    if ($error && strlen($error) > 0)
      $message .= " : ".$error;
    echo json_encode(["status"=>"ko", "message"=> $message]);
  }
  exit(0);
}
else if ($operation=="checkfile")
{
  if (is_file($file))
    echo json_encode(["status"=>"exists"]);
  else
    echo json_encode(["status"=>"nexists"]);
  exit(0);
}
else if ($operation=="delete")
{
  if (file_exists($file))
  {
    if ((is_dir($file) && @rmdir($file)) || (is_file($file) && @unlink($file)))
      echo json_encode(["status"=>"ok", "message"=> "\"".$file."\""." removed."]);
    else
    {
      $message = "error while removing \"".$file."\"";
      $error = @error_get_last()['message'];
      if ($error && strlen($error) > 0)
        $message .= " : ".$error;
      echo json_encode(["status"=>"ko", "message"=> $message]);
    }
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
    if (@rename($oldfile, $file))
      echo json_encode(["status"=>"ok", "message"=>"\"".$oldfile."\""." renamed to \"".$file."\"", "folder"=>is_dir($file)?"1":"0"]);
    else
    {
      $message = "error while renaming \"".$oldfile."\""." to \"".$file."\"";
      $error = @error_get_last()['message'];
      if ($error && strlen($error) > 0)
        $message .= " : ".$error;
      echo json_encode(["status"=>"ko", "message"=> $message]);
    }
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
if (strlen(/*trim*/($searchterm))>0)
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
  //if (!@is_dir($rep))
  if (strlen(trim($rep)) <= 0)
    $rep = ".";
  $scans = array();
  if (@is_dir($rep))
      $scans = @scandir($rep);
  $filestotal = array();
  if ($scans)
  foreach($scans as $scan)
  {
    if ($scan == "." || ($scan == ".." && $islash) || !fnmatch($searchterm."*", $scan, FNM_CASEFOLD))
      continue;
    $filescan = $scan;
    //if (trim($searchterm) != "." && trim($searchterm) == "..")
      $filescan = $rep."/".$scan;
    if (is_dir($filescan))
      $filestotal[] = array("type" => "folder" , "value"=> $filescan."/" , "time" => filemtime($filescan));
    else
      $filestotal[] = array("type"=> "file" , "value"=> $filescan , "time" => filemtime($filescan));
  }
  $arrayLength = count($filestotal);
  for ($j = 0; $j < $arrayLength; $j++)
  {
    if ($filestotal[$j]["type"] != "folder") continue;
    if ($i>0) echo ",";
    echo '{"type":"folder","value":"'.$filestotal[$j]["value"].'","time":'.$filestotal[$j]["time"].'}';
    $i++;
  }
  for ($j = 0; $j < $arrayLength; $j++)
  {
    if ($filestotal[$j]["type"] != "file") continue;
    if ($i>0) echo ",";
    echo '{"type":"file","value":"'.$filestotal[$j]["value"].'","time":'.$filestotal[$j]["time"].'}';
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
      <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>OSPEdit v0.31</title>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.0/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="//code.jquery.com/jquery-1.12.3.js" type="text/javascript"></script>
    <script src="//code.jquery.com/ui/1.12.0/jquery-ui.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.23.4/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.23.4/ext-modelist.js" type="text/javascript" charset="utf-8"></script>
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
      div_message.click(function(){$(this).html('');});
      disableedit = disableedit || /*is_touch_device() || */typeof ace != "object";
      if (!disableedit) {
        initmodechooser();
        $('#content').hide();
      }
      else
        $('#editor').hide();
      initeditor();
      initcatcomplete();
      $( "#file" ).catcomplete({
        source: function (request, response) {
          show_loading = false;
          $.post(url_script, {password: $("#password").val(), term: request.term}, (val)=> {
            //val.forEach(v => v.value = v.value + ' ('+(new Date(v.time*1000)).toLocaleString()+')');
            response(val);
          }, "json");
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
          else {
              setTimeout(function(){
                  atc = $(this);
                  atc.data("ui-autocomplete", atc.data("customCatcomplete"));
                atc.autocomplete('search',  ui.item.value);
              }.bind(this), 0);
          }
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
      $( document ).ajaxError(function( event, jqxhr, settings, thrownError ) {
        show_message( "Error while retrieving server content!!! Server down?", true );
      });
      initspinner();
    }
    function initeditor(mode)
    {
      if (!disableedit)
      {
        window.editor = ace.edit("editor");
        window.editor.setTheme("ace/theme/twilight");
        if (!mode) {
          mode = modelist.getModeForPath($("#file").val()).mode;
          document.getElementById('modechooser').value = mode;
        }
        console.log("editor set mode " + mode);
        window.editor.session.setMode(mode);
        window.editor.session.setOptions({
            tabSize: 2,
            useSoftTabs: true
        });
        window.editor.on("input", function() {
          has_changes = !window.editor.session.getUndoManager().isClean();
          toggle_btns();
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
            toggle_btns();
          },0);
        });
      }
      has_changes = false;
      toggle_btns();
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
            li.children('div')[0].style.display='inline-block';
            li.append($('<span style="float:right" title="date de dernière modification"/>').append((new Date(item.time*1000)).toLocaleString()));
          });
        }
      });
    }
    function initspinner()
    {
      if (typeof Spinner !== "object" && typeof Spinner !== "function")
        window.Spinner = function(){this.spin=function (){div_loading.show();};this.stop=function (){div_loading.hide();};};
      var opts = {
       color: '#7FB900'//F2
      }
      spinner = new Spinner(opts);
    }
    function initmodechooser() {
      window.modelist = window.modelist || ace.require("ace/ext/modelist");
      let modechooser = document.getElementById('modechooser');
      let modechooserlbl = document.getElementById('modechooserlbl');
      modelist.modes.forEach(mode => {
        var opt = document.createElement('option');
        opt.value = mode.mode;
        opt.innerHTML = mode.caption;
        modechooser.appendChild(opt);
      });
    }
    function setmode(value) {
      let mode = modelist.modes.find(m => m.mode == value);
      initeditor(mode.mode);
    }
    function loading(bloading)
    {
      if (bloading)
        spinner.spin($('#spinner')[0]);
      else
        spinner.stop();
    }
    function toggle_btns(bdisable)
    {
      var is_file_selected = $.trim($("#file").val()).length > 0;
      $("#loadbtn")[0].disabled = !is_file_selected || bdisable;
      $("#savebtn")[0].disabled = !is_file_selected || bdisable;
      $("#bkpbtn")[0].disabled = !is_file_selected || bdisable;
      $("#delbtn")[0].disabled = !is_file_selected || bdisable;
      $("#renbtn")[0].disabled = !is_file_selected || bdisable;
      if (bdisable || (disableedit || !is_file_selected)) {
        modechooserlbl.classList.add('invisible');
        modechooser.classList.add('invisible');
      } else {
        modechooserlbl.classList.remove('invisible');
        modechooser.classList.remove('invisible');
      }
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
      if (typeof toastr !== "undefined")
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
            if (!disableedit) {
              editor.getSession().setValue(data.content);
              editor.focus();
            } else {
              $('#content').val(data.content).focus();
            }
            initeditor();
          }
        }
      );
      return false;
    }
    function dosave(backup)
    {
      var filecontent = $('#content').val();
      let filename = $("#file").val();
      if (backup) {
        let datesuffix = '.' + Math.floor(Date.now() / 1000);
        let exti = filename.lastIndexOf('.');
        if (exti > -1 && exti > filename.lastIndexOf('/')) {
          filename = filename.substring(0, exti) + datesuffix + filename.substring(exti);
        } else {
          filename += datesuffix;
        }
      }
      if (!disableedit)
        filecontent = editor.getSession().getValue();
      show_loading = true;
      var savepost = function()
      {
        $.post( url_script,
          { password: $("#password").val(), operation: "save", file: filename, content: filecontent, backup: backup },
          function( data )
          {
            data = trygetdata(data, true);
            if (data.status == "ok" && !backup)
            {
              current_file = filename;
              has_changes = false;
              if (!disableedit)
                editor.session.getUndoManager().markClean();
            }
          }
        );
      };
      if (filename == current_file)
        savepost();
      else
      {
        $.post( url_script,
          { password: $("#password").val(), operation: "checkfile", file: filename },
          function( data )
          {
            data = trygetdata(data, true);
            if (data.status != "exists" || confirm("Warning, \"" + filename + "\" already exists, overwrite?"))
              savepost();
          }
        );
      }
      return true;
    }
    function dobackup()
    {
      return dosave(true);
    }
    function dodelete()
    {
      var filename = $("#file").val();
      if ($.trim(filename).length <= 0 || !confirm('are you sure you want to delete "'+filename+'"?'))
        return false;
      show_loading = true;
      $.post( url_script,
        { password: $("#password").val(), operation: "delete", file: $("#file").val() },
        function( data ) { data = trygetdata(data, true); /*if (data.status=="ok") has_changes = true;*/ }
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
          if (data.status == "ok" && data.folder!="1")
          {
            if (confirm("Go to new file"+(has_changes?" (changes will not be saved)":"")+"?"))
            {
              $("#file").val(newfile);
              doload();//$("#formfile").submit();
            }
          }
        }
      );
      return false;
    }
  </script>
    <style type="text/css">
    input {
      display: inline;
    }
    a#filelink {
      margin: 0px 20px;
    }
    #file {
      width: 50%;
    }
    body {
      overflow: hidden;
      font-family: tahoma;
    }
    html, body {
      height: 100%;
    }
    .content {
      margin: 0;
      position: absolute;
      bottom: 0px;
      left: 0px;
      right: 0px;
    }
    @media (min-width:840px) {
      .content {
      top: 40px;
      }
    }
    @media (max-width:840px) {
      .content {
      top: 55px;
      }
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
    /* IE 6 doesn't support max-height   * we use height instead, but this forces the menu to always be this tall   */
    * html .ui-autocomplete {
      height: 600px;
    }
    .ui-menu-item-folder {
      font-weight: bold;
      color : rgb(255,153,0);
    }
    .ui-menu-item-folder .ui-state-active {
      font-weight: bold !important;
    }
    .invisible {
      display: none;
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
      <label id="modechooserlbl" for="modechooser" class="invisible">language : </label><select name="modechooser" id="modechooser" onchange="setmode(this.value)" class="invisible"></select>
      <span id="loading" style="display:none;">Loading...</span>
      <span id="message"></span>
      <textarea class="content" name="content" id="content" cols="190" rows="35"><?php echo htmlspecialchars($content);?></textarea>
    </form>
    <div id="editor" class="content"><?php echo htmlspecialchars($content);?></div>
    <div id="spinner"></div>
  </body>
</html>
