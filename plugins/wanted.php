<?php
// Compile a list of links to non-existent pages from the wiki content.
function wiki_action_wanted(&$tpl_vars) {
	$pagemtimes = page_list('*.txt');
	//asort($pagemtimes);
	//$pagemtimes = array_slice($pagemtimes, 0, 50);
	$wanted = array();
	foreach ($pagemtimes as $fname => $mtime) {
		$page_text = @file_get_contents(PAGE_DIR . '/' . $fname);
		preg_match_all('/' . WIKIWORD . '/', $page_text, $matches);
		foreach ($matches[0] as $page_name) {
			$wfname = "$page_name.txt";
			if (!file_exists(PAGE_DIR . '/' . $wfname))
				$wanted[$wfname] = $mtime;
		}
	}
	asort($wanted);
	$tpl_vars['page_title'] = 'Wanted pages';
	$tpl_vars['page_text'] = page_list_format($wanted);
}
$tpl_vars['actions'] .= ' | <a href="?action=wanted">Wanted pages</a> ';
?>
