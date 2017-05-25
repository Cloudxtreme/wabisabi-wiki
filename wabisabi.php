<?php
// WabiSabi - a fast experimental wiki engine
// 2009-2012 Felix Pleșoianu <felix@plesoianu.ro>
// If you are asking what license this software is released under,
// you are asking the wrong question.

define('PASSWORD', 'wikiwikiweb');
define('HOMEPAGE', 'WabiSabi'); // Must be a WikiWord, see below.
define('GOTOBAR', "*<<home>>\n*WikiLicense");
define('DATE_FORMAT', 'D d M Y, H:i:s'); // Must be valid for date().
define('TIMEZONE', 'UTC'); // See http://www.php.net/manual/en/timezones.php
define('PAGE_DIR', 'pages'); // Should not end with a slash.

define('URL', '[a-z\\+]+://[\\w\\.-]+(:\\d+)?/[\\w$-.+!*\'(),\\?#%&;=~:\\/]*');
// Must not contain dots or slashes, it's a security risk!
define('WIKIWORD', '(([A-Z]+[a-z0-9_-]+){2,})');

$wiki_template = <<<WIKI_TPL
<!DOCTYPE html>
<html>
 <head>
  <title>\$page_title | WabiSabi</title>
  <link rel="stylesheet" type="text/css" href="wabisabi.css">
  <link rel="alternate" type="application/x-wiki" title="Edit this page" href="?page=\$page_name&amp;action=edit">
  <meta http-equiv="Content-type" content="text/html; charset=utf-8">
  \$head
 </head>

 <body>
  <div class="header" role="banner"><h1>\$page_title</h1></div>
  <div class="main">
   <div class="article" role="main">\$page_text</div>
   <div id="gotobar" role="navigation">\$gotobar</div>
  </div>
  <div class="footer">
   <div id="actionbar" role="navigation">
    <div><a href="?action=rc">Recent changes</a> \$actions</div>
   <a href="?page=\$page_name&amp;action=edit" rel="nofollow">Edit this page</a>
    (Password: \$password)<br>Last modified: \$last_mod
   </div>
   <div id="footer" role="banner">\$footer</div>
  </div>
 </body>
</html>
WIKI_TPL;

$edit_template = <<<EDIT_TPL
<form method="post">
 <input type="hidden" name="page_name" value="\$page_name">
 <textarea name="page_text" cols="64" rows="16">\$page_text</textarea>
 <br><label>Password <input name="password"></label>
 <input type="submit" name="save" value="Save">
 <input type="submit" name="preview" value="Preview">
 <a href="?\$page_name">Cancel</a>
</form>
EDIT_TPL;

$saved_template = 'Page saved. <a href="?$page_name">See the new version.</a>';

$wiki_patterns = array(
	'/^\\{\\{\\{(.*?)\\}\\}\\}/mse' => 'wiki_preserve(\'<pre>$1</pre>\');',
	'/\s*==+\s*$/m' => '',
	'/^======(.*)/m' => '<h6>$1</h6>',
	'/^=====(.*)/m' => '<h5>$1</h5>',
	'/^====(.*)/m' => '<h4>$1</h4>',
	'/^===(.*)/m' => '<h3>$1</h3>',
	'/^==(.*)/m' => '<h2>$1</h2>',
	'/^\s*$/m' => '<p>',
	'/^----+/m' => "<hr>\n",
	'/^:(.*)/m' => '<blockquote>$1</blockquote>',
	'/^\\*+(.*)/m' => '<ul><li>$1</li></ul>',
	'/^#+(.*)/m' => '<ol><li>$1</li></ol>',
	'/^;([^:]+):(.*)/m' => '<dl><dt>$1</dt><dd>$2</dd></dl>',
	'!(</ul>\s<ul>)|(</ol>\s<ol>)|(</dl>\s<dl>)!m' => "\n",
	'/^\\{\\|(.*?)\\|\\}/mse'
		=> '"<table><tr>".wiki_render_table("$1")."</tr></table>";',

	'|\\{\\{(' . URL . ')(.*?)\\}\\}|e'
		=> 'wiki_preserve(\'<img src="$1" alt="$3">\');',
	'|\\[(' . URL . ')(.+?)\\]|e'
		=> 'wiki_preserve(\'<a href="$1">$3</a>\');',
	'|(' . URL . ')|e' => 'wiki_preserve(\'<a href="$1">$1</a>\');',
	'/' . WIKIWORD . '/' => '<a href="?$1">$1</a>',

	'/\\{\\{\\{(.*?)\\}\\}\\}/e' => 'wiki_preserve(\'<code>$1</code>\');',
	'/\\*\\*(.*?)\\*\\*/' => '<b>$1</b>',
	'|//(.*?)//|' => '<i>$1</i>',
	'/\\\\\\\\/' => "<br>\n",
	'/\\^\\^(.*?)\\^\\^/' => '<sup>$1</sup>',
	'/,,(.*?),,/' => '<sub>$1</sub>');

$wiki_table_patterns = array(
	'/^\s*\\|-/m' => '</tr><tr>',
	'/^\s*\\|\\+(.*)/m' => '<th>$1</th>',
	'/^\s*\\|(.*)/m' => '<td>$1</td>');

$preserved_strings = array();

function wiki_preserve($text) {
	global $preserved_strings;
	$gensym = '_' . count($preserved_strings);
	$preserved_strings[$gensym] = $text;
	return "\$$gensym";
}

function wiki_render($text, $patterns = array()) {
	global $preserved_strings;
	$preserved_strings = array();
	$tmp = preg_replace_callback(
		'/<<(\w+)(.*?)>>/', 'wiki_plugin', $text);
	$tmp = preg_replace(
		array_keys($patterns),
		array_values($patterns),
		htmlspecialchars($tmp, ENT_QUOTES, "UTF-8"));
	return tpl_render($tmp, $preserved_strings);
}

function wiki_render_table($text) {
	global $wiki_table_patterns;
	return preg_replace(
		array_keys($wiki_table_patterns),
		array_values($wiki_table_patterns),
		$text);
}

function wiki_plugin($matches) {
	$fn = 'wiki_plugin_' . $matches[1];
	return function_exists($fn) ? $fn($matches[2]) : $matches[0];
}

function wiki_plugin_include($text) {
	$text = trim($text);
	if (!preg_match('/^' . WIKIWORD . '$/', $text))
		return '';
	else if ($text == page_name())
		return ''; // avoid recursive reading
	else
		return preg_replace_callback(
			'/<<(\w+)(.*?)>>/', 'wiki_plugin',
			page_retrieve($text));
}

function wiki_plugin_title($text) {
	$GLOBALS['tpl_vars']['page_title'] = trim($text); return '';
}

function wiki_plugin_home($text) {
	return wiki_preserve('<a href="?' . HOMEPAGE . '" rel="home">Home</a>');
}

function tpl_render($text, $tpl_vars) {
	return preg_replace('/\\$(\\w+)/e',
		'isset($tpl_vars["$1"]) ? $tpl_vars["$1"] : "";', $text);
}

function tpl_render_editbox($page_text) {
	global $edit_template, $page_name;
	return tpl_render($edit_template, array(
		'page_name' => $page_name,
		'page_text' => htmlspecialchars(
			$page_text, ENT_QUOTES, "UTF-8")));
}

function page_name() {
	$ww = '/^' . WIKIWORD . '$/';
	if (isset($_GET['page'])) {
		if (preg_match($ww, $_GET['page'])) {
			return $_GET['page'];
		} else {
			return HOMEPAGE;
		}
	} else if (preg_match($ww, $_SERVER['QUERY_STRING'])) {
		return $_SERVER['QUERY_STRING'];
	} else {
		return HOMEPAGE;
	}
}

function page_retrieve($page_name) {
	return @file_get_contents(PAGE_DIR . "/$page_name.txt");
}

function page_save() {
	if (!preg_match('/^' . WIKIWORD . '$/', $_POST['page_name']))
		return false;
	if (strlen($_POST['page_text']) < 6) return false;
	if ($_POST['password'] != PASSWORD) return false;
	if ($_POST['page_text'] == $GLOBALS['raw_page_text']) return false;
	$basename = PAGE_DIR . '/' . $_POST['page_name'];
	@copy($basename . '.1', $basename . '.2');
	@copy($basename . '.txt', $basename . '.1');
	return @file_put_contents($basename . '.txt', $_POST['page_text']);
}

function page_list($pattern) {
	$oldcwd = getcwd();
	chdir(PAGE_DIR);
	$pages = @glob($pattern);
	if (!$pages) $pages = array();
	$mtimes = array_map('filemtime', $pages);
	chdir($oldcwd);
	return array_combine($pages, $mtimes);
}

function page_list_format($page_list) {
	$output = "<ol>\n";
	foreach ($page_list as $fname => $mtime) {
		$tstr = date(DATE_FORMAT, $mtime);
		$pname = substr($fname, 0, -4);
		$output .= "<li><a href=\"?$pname\">$pname</a> ($tstr)</li>\n";
	}
	$output .= "</ol>\n";
	return $output;
}

function revert_magic_quotes() {
	if (get_magic_quotes_gpc())
		$_POST['page_text'] = stripslashes($_POST['page_text']);
}

function wiki_action_edit(&$tpl_vars) {
	global $saved_template, $wiki_patterns, $raw_page_text;
	if (isset($_POST['save'])) {
		revert_magic_quotes();
		if (page_save()) {
			$tpl_vars['page_text'] =
				tpl_render($saved_template, $tpl_vars);
		} else {
			$tpl_vars['page_text'] = 'Save failed<br>'
				. tpl_render_editbox($_POST['page_text']);
		}
	} else if (isset($_POST['preview'])) {
		revert_magic_quotes();
		$tpl_vars['page_text'] = 'Preview:<br>'
			. wiki_render($_POST['page_text'], $wiki_patterns)
			. tpl_render_editbox($_POST['page_text']);
	} else {
		$tpl_vars['page_text'] = tpl_render_editbox($raw_page_text);
	}
}

function wiki_action_rc(&$tpl_vars) {
	$pagemtimes = page_list('*.txt');
	arsort($pagemtimes);
	$pagemtimes = array_slice($pagemtimes, 0, 50);
	$tpl_vars['page_title'] = 'Recent changes';
	$tpl_vars['page_text'] = page_list_format($pagemtimes);
}

function wiki_action_view(&$tpl_vars) {
	global $raw_page_text, $wiki_patterns;
	$tpl_vars['page_text'] = wiki_render($raw_page_text, $wiki_patterns);
}

date_default_timezone_set(TIMEZONE);
$page_name = page_name();
$raw_page_text = page_retrieve($page_name);
if (empty($raw_page_text)) 
	$raw_page_text = "Page $page_name does not exist yet.";
$gotobar = page_retrieve('GoToBar');
if (empty($gotobar)) $gotobar = GOTOBAR;
$tpl_vars = array(
	'page_name' => $page_name,
	'page_title' => strtr($page_name, '_', ' '),
	'last_mod' => date(DATE_FORMAT,
		@filemtime(PAGE_DIR . "/$page_name.txt")),
	'password' => PASSWORD,
	'home_page' => HOMEPAGE,
	'gotobar' => wiki_render($gotobar, $wiki_patterns),
	'actions' => '',
	'footer' => '',
	'head' => '');

foreach (glob('plugins/*.php') as $plugin) @include($plugin);
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$action_handler = 'wiki_action_' . $action;
if (!function_exists($action_handler)) $action_handler = 'wiki_action_view';
$action_handler($tpl_vars);

header('Content-type: text/html; charset=utf-8');
print tpl_render($wiki_template, $tpl_vars);
?>
