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
 *
 *   // Check if selector return something
 *   $exists = $txtdb->exists('manga', '[title="test"]');
 *
 *   // Switch to other db
 *   $txtdb->select_db('manga2');
 */
class TextDB {
	private $file;
	private $dir = 'cache';
	private $ext = '.textdb';
	private $dom;
	private $ai_field = '@id';
	private $changed = false;
	private $nodb = true;

	function __construct($name = '') {
		$this->dir = dirname(dirname(__FILE__)).'/'.$this->dir.'/';
		if ($name != '') {
			$this->select_db($name);
		}
	}

	function __destruct() {
		if ($this->changed) {
			$this->flush();
		}
	}

	function select_db($name) {
		$this->nodb = false;

		if ($this->changed) {
			$this->flush();
		}

		$this->file = $this->dir . $name . $this->ext;
		if (!file_exists($this->file)) {
			$content = '<?xml version="1.0" encoding="utf-8"?><root/>';
			$this->dom = simplexml_load_string($content);
		} else {
			$this->dom = simplexml_load_file($this->file);
		}
	}

	function create($table, $data) {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

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
				$newnode = $row->addChild($key);
				$node= dom_import_simplexml($newnode);
				$no = $node->ownerDocument; 
				$node->appendChild($no->createCDATASection($value));
			}
		}
		$this->changed = true;
	}

	function retrieve($table, $where = '', $limit = 0) {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

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
			$row = array();
			foreach($node->attributes() as $attr => $attr_val) {
				$row['@'.$attr] = $attr_val;
			}
			foreach($node->children() as $child) {
				$row[$child->getName()] = $child[0];
			}
			$rows[] = $row;
		}
		return $rows;
	}

	function update($table, $data, $where = '') {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			foreach($node->children() as $child) {
				$cname = $child->getName();
				if (array_key_exists($cname, $data)) {
					$child[0] = $data[$cname];
				}
				$this->changed = true;
			}
		}
	}

	function delete($table, $where = '') {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			$domnode = dom_import_simplexml($node);
			$domnode->parentNode->removeChild($domnode);
			$this->changed = true;
		}
	}

	function drop($table) {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$path = '/root/'.$table;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			$domnode = dom_import_simplexml($node);
			$domnode->parentNode->removeChild($domnode);
			$this->changed = true;
		}
	}

	function truncate($table) {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$path = '/root/'.$table;
		$nodes = $this->dom->xpath($path);
		foreach($nodes as $node) {
			$domnode = dom_import_simplexml($node);
			$domnode->parentNode->removeChild($domnode);
		}
		$tab = $this->dom->addChild($table);
		$tab->addAttribute('next_ai', 1);
		$this->changed = true;
	}

	function count($table, $where = '') {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		return count($nodes);
	}

	function exists($table, $where = '') {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$path = '/root/'.$table.'/row'.$where;
		$nodes = $this->dom->xpath($path);
		return !empty($nodes);
	}

	function flush() {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		$this->dom->asXML($this->file);
		$this->changed = false;
	}

	function to_string() {
		if ($this->nodb) {
			die('Database has not been selected.');
		}

		return $this->dom->asXML();
	}
}
?>
