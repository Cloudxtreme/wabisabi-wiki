<?php
// Change this if you know better.
$tpl_vars['site_link'] = 'http://' . $_SERVER['SERVER_NAME']
	. dirname($_SERVER['SCRIPT_NAME']);
// Set to any text you like.
$tpl_vars['site_desc'] = 'Home of the WabiSabi wiki engine';

$tpl_vars['head'] .= '<link rel="alternate" type="application/rss+xml"
	href="' . $tpl_vars['site_link'] . '?action=rss">';
$tpl_vars['actions'] .= ' | <a href="?action=rss" class="rss">RSS</a> ';

$wiki_rss_template = <<<NEWSFEED
<rss version="2.0">
 <channel>
  <title>WabiSabi</title>
  <link>\$site_link</link>
  <description>\$site_desc</description>
  
  \$items
 </channel>
</rss>
NEWSFEED;

$wiki_rss_item_template = <<<NEWSITEM
   <item>
    <title>\$page_title</title>
    <pubDate>\$last_mod</pubDate>
    <link>\$site_link?\$page_name</link>
   </item>
NEWSITEM;

function wiki_action_rss(&$tpl_vars) {
	global $wiki_rss_template, $wiki_rss_item_template;
	
	$pagemtimes = page_list('*.txt');
	arsort($pagemtimes);
	$pagemtimes = array_slice($pagemtimes, 0, 15);
	$feed_vars = array(
		'site_link' => $tpl_vars['site_link'],
		'site_desc' => $tpl_vars['site_desc'],
		'items' => '');
	$item_vars = array('site_link' => $tpl_vars['site_link']);
	foreach ($pagemtimes as $fname => $mtime) {
		$item_vars['page_name'] = substr($fname, 0, -4);
		$item_vars['page_title'] =
			strtr($item_vars['page_name'], '_', ' ');
		$item_vars['last_mod'] = date('D, F d Y H:i:s T', $mtime);
		$feed_vars['items'] .=
			tpl_render($wiki_rss_item_template, $item_vars);
	}
	header('Content-type: application/rss+xml');
	print '<?xml version="1.0" encoding="utf-8"?>' . "\n";
	print tpl_render($wiki_rss_template, $feed_vars);
	exit;
}
?>
