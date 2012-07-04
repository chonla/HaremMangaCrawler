<?php
/************
 * TextDB, a simple file-based database.
 * Version: 0.1
 * Author: Chonla
 * Update: 4 July 2012
 *
 * Usage:
 *   // Create a new database
 *   $txtdb = new TextDB('mangadb');
 *
 *   // Create a new record into table
 *   $txtdb->create('manga', array('@id','title'=>'test','link'=>'http://www.google.com'));
 *   Note: '@id' without value is predefined to be primary key with auto-increment attribute.
 *
 *   // Retreive an existing record
 *   $row = $txtdb->retrieve('manga', '[title="test"]');
 *
 *   // Update an existing record
 *   $txtdb->update('manga', array('title'=>'aaaa'), '[title="test"]');
 *
 *   // Delete an existing record
 *   $txtdb->delete('manga', '[title="bbb"]');
 *
 *   // Drop table
 *   $txtdb->drop('manga');
 *
 *   // Truncate table
 *   $txtdb->truncate('manga');
 *
 *   // Count rows
 *   $txtdb->count('manga', '[title="test"]');
 *
 *   // Dump content as XML string
 *   $xml = $txtdb->to_string();
 */
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

	function retrieve($table, $where = '', $limit = 0) {
		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		if (count($nodes) == 0) {
			return FALSE;
		}
		if ($limit <= 0) {
			$limit = count($nodes);
		}
		$rows = array();
		foreach($nodes as $node) {
			$rows[] = $node;
		}
		return $rows;
	}

	function update($table, $data, $where = '') {
		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			foreach($node->children() as $child) {
				$cname = $child->getName();
				if (array_key_exists($cname, $data)) {
					$child[0] = $data[$cname];
				}
			}
		}
	}

	function delete($table, $where = '') {
		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			$domnode = dom_import_simplexml($node);
			$domnode->parentNode->removeChild($domnode);
		}
	}

	function drop($table) {
		$path = '/root/'.$table;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			$domnode = dom_import_simplexml($node);
			$domnode->parentNode->removeChild($domnode);
		}
	}

	function truncate($table) {
		$path = '/root/'.$table;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			$domnode = dom_import_simplexml($node);
			$domnode->parentNode->removeChild($domnode);
		}
		$tab = $this->dom->addChild($table);
		$tab->addAttribute('next_ai', 1);
	}

	function count($table, $where = '') {
		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		return count($nodes);
	}

	function flush() {
		$this->dom->asXML($this->file);
	}

	function to_string() {
		return $this->dom->asXML();
	}
}
?>
