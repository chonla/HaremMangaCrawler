<?php
/**
 * Manga Crawler - HaremManga.com
 */

// Require
require dirname(dirname(__FILE__)).'/classes/textdb.class.php';

// Initialize
$tdb = new TextDB('haremmangadb');
$tbn = 'manga';

$do_crawl_pages = false;

// Create context of request
$opts = array(
	'http'=>array(
		'header'=>array(
			"User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.162 Safari/535.19\r\n"
		)
	)
);
$context = stream_context_create($opts);

if ($do_crawl_pages) {
	echo "Crawling pages...\n";

	// Starting URL
	$url = 'http://www.haremmanga.com';
	$html = @file_get_contents($url, false, $context);
	if (preg_match('{<a href=\'http://www.haremmanga.com/page/(\d+)\' class=\'last\'>}', $html, $pm) > 0) {
		$pagecount = $pm[1];
	}

	$added = 0;
	for ($p = 1; ($p <= $pagecount); $p++) {
		echo " page #".$p."...";
		// Loop through pages
		$url = 'http://www.haremmanga.com/page/'.$p;

		$html = @file_get_contents($url, false, $context);

		// Grab content
		if (preg_match('{<div id="content">(.+)</div><!--/content -->}isU', $html, $m) > 0) {
			$content = $m[1];

			// Grab manga list
			if (preg_match_all('{<a href="(http://www.haremmanga.com/[^"]+\.html)" rel="bookmark" title="Permanent Link to}', $content, $mm) > 0) {
				for ($i = 0, $n = count($mm[1]); ($i < $n); $i++) {
					$url = $mm[1][$i];
					$hash = md5($url);
					if (!$tdb->exists($tbn, '[@hash="'.$hash.'"]')) {
						$tdb->create($tbn, array('@hash'=>$hash, 'url'=>$url));
						$added++;
					}
				}
			}
		}
		echo "DONE\n";
	}

	echo "Crawling pages...DONE\n";
}

$tdb->flush(); // Force flush

$mdb = new TextDB('mangas'); // Mangas DB
$cdb = new TextDB; // Per Chapters DB

echo "Crawling chapters...\n";
$rows = $tdb->retrieve('manga');
foreach ($rows as $row) {
	$hash = $row['@hash'];
	$url = $row['url'];

	$html = @file_get_contents($url, false, $context);
	echo " chapter url ".$url."...";

	$manga_key = "";
	$manga_title = "";
	$manga_chapter_title = "";

	if (preg_match('{<div id="crumbs"><a href="http://www.haremmanga.com">Home</a> &raquo; <a href="http://www.haremmanga.com/manga/([^"]+)" title="View all posts in ([^"]+)">[^<]+</a> &raquo; <span class="current">(.+)</span></div>}', $html, $cm) > 0) {
		list($dump, $manga_key, $manga_title, $manga_chapter_title) = $cm;
		if (preg_match('{<span class="time">([^<]+)</span>}', $html, $ct) > 0) {
			// Grab time of chapter posting
			$chapter_time = $ct[1];
			list($M,$d,$Y) = explode(' - ', $chapter_time);
			$t = sprintf('%s - %s - %s', $d, $M, $Y);
			$chapter_time = date("D, j M Y", strtotime($t));
			$chapter_time_tick = $chapter_time . ' 00:00:00 +0000';
			if (preg_match('{<div class="entry">(.+)</div>}sU', $html, $mx) > 0) {
				$body = trim($mx[1]);

				if (!$mdb->exists('mangas', '[@id="'.$manga_key.'"]')) {
					// Add manga info
					$mdb->create('mangas', array('@id'=>$manga_key, 'title'=>$manga_title));
					$mdb->flush();
				}

				$cdb->select_db($manga_key);
				if (!$cdb->exists('chapters', '[@hash="'.$hash.'"]')) {
					// Add chapter entry
					$cdb->create('chapters', array('@hash'=>$hash, 'url'=>$url, 'title'=>$manga_chapter_title, 'body'=>$body, 'timestamp'=>$chapter_time_tick));
				} else {
					// Update chapter
					$cdb->update('chapters', array('@hash'=>$hash, 'url'=>$url, 'title'=>$manga_chapter_title, 'body'=>$body, 'timestamp'=>$chapter_time_tick), '[@hash="'.$hash.'"]');
				}
			}
		}
	}
	echo "DONE\n";
}
echo "Crawling chapters...DONE\n";

?>