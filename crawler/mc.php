<?php
/*
	Manga Crawler
	Target : HaremManga.com
*/

// Starting URL
$url = 'http://www.haremmanga.com/page/78';

// Create context of request
$opts = array(
	'http'=>array(
		'header'=>array(
			"User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.162 Safari/535.19\r\n"
		)
	)
);
$context = stream_context_create($opts);

$html = @file_get_contents($url, false, $context);

// Grab content
if (preg_match('{<div id="content">(.+)</div><!--/content -->}isU', $html, $m) > 0) {
	$content = $m[1];

	// Grab manga list
	if (preg_match_all('{<a href="(http://www.haremmanga.com/[^"]+\.html)" rel="bookmark" title="Permanent Link to}', $content, $mm, PREG_SET_ORDER) > 0) {
		print_r($mm);
	}
}

?>