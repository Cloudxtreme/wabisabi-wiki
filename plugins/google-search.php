<?php
$tpl_vars['footer'] .= <<<SEARCH
<div id="cse" style="width: 100%;">Loading</div>
<script src="http://www.google.com/jsapi" type="text/javascript"></script>
<script type="text/javascript">
  google.load('search', '1');
  google.setOnLoadCallback(function() {
    var customSearchControl = new google.search.CustomSearchControl('001887539766011560593:qyujjso27_i');
    customSearchControl.setResultSetSize(google.search.Search.SMALL_RESULTSET);
    var options = new google.search.DrawOptions();
    options.setAutoComplete(true);
    customSearchControl.draw('cse', options);
  }, true);
</script>
SEARCH;
?>
