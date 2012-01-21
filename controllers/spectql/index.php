<?php
$base_url = Config::$HOSTNAME . Config::$SUBDIR;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>The DataTank: SPECTQL end-point</title>
    <base href="http://datatank.demo.ibbt.be/">
    <link rel="stylesheet" href="http://twitter.github.com/bootstrap/assets/css/bootstrap-1.1.1.min.css">  
    <link rel="stylesheet" href="installer/static/main.css"> 
    <script src="http://datatank.demo.ibbt.be/The-Semantifier/lib/jquery-1.7.1.min.js"></script>
  </head>
  <body>
    <div id="masterhead" class="container">
      <div class="row">
	<div class="columns"><img src="http://datatank.demo.ibbt.be/installer/static/logo.png"/>
	</div>
      </div>
    </div>
    <div id="main">
      <div class="container">
	<textarea name="query" style="width: 78%; height: 100px;" id="query">/TDTInfo/Resources{*}:html</textarea>
        <select id="resources" style="width: 20%; height: 110px;" size="5">
        </select>
        <br/>
	<input type="button" id="run" value="Run the Query"/>
        <br/>
        <div id="uri"></div>
	<hr/>
	<pre id="result">
	</pre>
      </div>
    </div>
    <footer>
      <div class="footer" align="center">
	&copy; 2011 <a href="http://npo.irail.be" target="_blank">iRail npo</a> - Powered by <a href="http://thedatatank.com" target="_blank">The DataTank</a>
      </div>
    </footer>

    <script>
      $('#run').click(function () {
        $.ajax({
           url: "<?php echo $base_url ?>spectql" + $('#query').val(),
           success: function(data) {
              $('#result').text(data);
           }
        });
      });
      
      //Load options
      $.ajax({
        url: "<?php echo $base_url; ?>TDTInfo/Resources.json",
        success: function(data) {
           data = data["Resources"];
           $.each(data, function(package, resources){
             $.each(resources, function(resourcename, resource){
                var resourcename = package + "/" + resourcename;
                $('#resources').append("<option value=\"" + resourcename + "\">" + resourcename +  "</option>");
             });
           });
        }
      });

    $("#resources").dblclick(function(){
      $("#query").val($("#query").val() + $("#resources").val());
    });

    $("#query").keyup(function(){
      $("#uri").html("In programming code, use this URL to access your query:<br/><strong><?php echo $base_url; ?>spectql" + encodeURI($("#query").val())+ "</strong>" );
    });
    </script>
  </body>
</html>
