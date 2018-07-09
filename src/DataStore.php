<?php
namespace App;
use Gt\Core\Path;
use Exception;
use DirectoryIterator;

class DataStore {

	public $type = 'default';
	
	public $blockFields = [];
	protected $privateFields = [];

	const NOT_EQUALS = '!=';
	const EQUALS = '=';
	const GREATER_THAN = '>';
	const GREATER_THAN_EQUALS = '>=';
	const LESS_THAN = '<';
	const LESS_THAN_EQUALS = '<=';
	const LIKE = 'like';
	const IN = 'in';

	public function __construct ($identifier=false, $readOnly=false) {
		$this->identifier = strtolower($identifier);
		$this->path = Path::get(Path::DATA)."/{$this->type}/";
		$this->currentDirectory =  $this->path.$this->identifier.'/'; 
		$this->readOnly = $readOnly;  
	}

	public function setType($type) {
		$this->type = $type;
		$this->path = Path::get(Path::DATA)."/{$this->type}/";
		$this->currentDirectory =  $this->path.$this->identifier.'/'; 
	}

	public function create ($payload) {  
		if (file_exists($this->currentDirectory)) {
			throw new Exception("Entity Already Exists");
		}      

		mkdir($this->currentDirectory, 0777, true);    	
		foreach ($payload as $field => $value) {
			$this->setValue($field,$value);
		}
	}
	
	public function update ($payload) {
		foreach ($payload as $field => $value) {
			$this->setValue($field,$value);
		}
	}
	
	public function delete () {
		$this->removeDirectory($this->currentDirectory);
	} 

	public function search ($filters=false) { 
		$items = [];

		$dir = new DirectoryIterator($this->path);

		$filter_fields = [];
		if (is_array($filters)) {
			foreach ($filters as $i => $keys) {
				if (!isset($keys[0])||!isset($keys[1])||!isset($keys[2])) continue;
				$filter_field = $keys[0];
				$filter_operator = $keys[1];
				$filter_value = $keys[2];

				$filter_fields[$filter_field] = [
					'operator' => $filter_operator,
					'value' => $filter_value
				];
			}
		}

		foreach ($dir as $fileinfo) {
			if (!$fileinfo->isDir() || $fileinfo->isDot()) continue;
			$identifier = $fileinfo->getFilename();
			$items[$identifier]['id'] = $identifier;
			$this->currentDirectory = $this->path.$identifier.'/';

			$datastore = new DataStore($identifier);
			$datastore->setType($this->type);

			$failures = 0;
			foreach ($filter_fields as $field => $opt) {
				$value = strtolower($datastore->getValue($field));
				$failures += $this->comparitor($value, $opt) ? 0 : 1;
			}

			// Unset on Failure
			if ($failures > 0) {
				unset($items[$identifier]);
				continue;
			}

			// Get Values for Each Identifier
			$valuesDirectory = new DirectoryIterator($this->currentDirectory);
			foreach ($valuesDirectory as $valueinfo) {
				if ($valueinfo->isDot()) continue;
				$field = explode('.',$valueinfo->getFilename())[0];
				if (in_array($field, $this->privateFields)) continue;
				$items[$identifier][$field] = $this->getValue($field);
			}
		 
		}

		return $items;  
	}

	private function comparitor($value, $opt) {
		switch ($opt['operator']) {
			case '!=':
				return $value != $opt['value'];
				break;
			case '>':
				return $value > $opt['value'];
				break;
			case '>=':
				return $value >= $opt['value'];
				break;
			case '<':
				return $value < $opt['value'];
				break;
			case '<=':
				return $value <= $opt['value'];
				break;
			case 'like':
				return strstr($value,$opt['value']);
				break;
			case 'in':
				return in_array($value, explode(',',$opt['value']));
				break;
			case '=':
			default:
				return $value == $opt['value'];
		}
	}

	private function protectField($field) {
		if (strstr($field,'./')) {
			throw new Exception("Invalid Field");
		}
	}

	public function getValue ($field) {
		$this->protectField($field);
		$path = "{$this->currentDirectory}/{$field}.dat";
		return file_exists($path) ? file_get_contents($path) : false;
	}

	public function setValue ($field,$value=false) {
		$this->protectField($field);
		if (in_array($field,$this->blockFields) || $this->readOnly) return;
		$path = "{$this->currentDirectory}/{$field}.dat";
		return file_put_contents($path, $value);
	}

	private function removeDirectory($path) {
		if ($this->readOnly) return; 
		$files = glob($path . '/*');
		foreach ($files as $file) {
			is_dir($file) ? $this->removeDirectory($file) : unlink($file);
		}
		rmdir($path);
	}

}