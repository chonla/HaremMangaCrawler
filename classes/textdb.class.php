<?php
class TextDB {
	private $file;
	private $dir = 'cache';
	private $ext = '.textdb';
	private $dom;
	private $ai_field = '@id';

	function __construct($name) {
		$this->dir = dirname(dirname(__FILE__)).'/'.$this->dir.'/';
		$this->file = $this->dir . $name . $this->ext;
		if (!file_exists($this->file)) {
			$content = '<?xml version="1.0" encoding="utf-8"?><root/>';
			$this->dom = simplexml_load_string($content);
		} else {
			$this->dom = simplexml_load_file($this->file);
		}
	}

	function __destruct() {
		$this->flush();
	}

	function create($table, $data) {
		$path = '/root/'.$table;
		$nodes = $this->dom->xpath($path);
		if (count($nodes) == 0) {
			$tab = $this->dom->addChild($table);
			$tab->addAttribute('next_ai', 1);
		} else {
			$tab = $nodes[0];
		}
		$row = $tab->addChild('row');
		if (isset($data[0]) && ($data[0] == $this->ai_field)) {
			$attr = $tab->attributes();
			$nextid = (string)$attr['next_ai'];
			$data[$this->ai_field] = $nextid;
			unset($data[0]);
			$tab['next_ai'] = $nextid + 1;
		}
		foreach ($data as $key => $value) {
			if (substr($key, 0, 1) == '@') {
				$row->addAttribute(substr($key, 1), $value);
			} else {
				$row->addChild($key, $value);
			}
		}
	}

	function retrieve($table, $where) {
	}

	function update($table, $data, $where) {
	}

	function delete($table, $where) {
	}

	function flush() {
		$this->dom->asXML($this->file);
	}
}

$txtdb = new TextDB('manga');
$txtdb->create('manga', array('@id','title'=>'test','link'=>'http://www.google.com'));
$txtdb->create('manga', array('@id','title'=>'test2','link'=>'http://www.yahoo.com'));
$txtdb->create('manga', array('@id','title'=>'ทดสอบ','link'=>'http://www.chonla.com'));

?>
