<?php
class HasPageBehavior extends ModelBehavior {
	var $runtime= array();
	var $languages = false;
	var $_defaultSettings = array(
			'className' => 'MiWrite.Page',
			'foreignKey' => 'foreign_id',
			'conditions' => array(),
	);
	function setup(&$model, $settings = array()) {
		if ($model->displayField == 'id') {
			$model->displayField = 'display';
			$model->virtualFields['display'] = 'Page.title';
		}

		$this->runtime[$model->alias] = array();
		if(isset($settings['languages'])) {
			$this->runtime[$model->alias]['languages'] = $settings['languages'];
			unset($settings['languages']);
		} else {
			$this->runtime[$model->alias]['languages'] = false;
		}

		if (!isset($model->hasOne['Page']) & !$this->runtime[$model->alias]['languages']) {
			$pageRelationship = Set::merge($this->_defaultSettings, array('conditions' => array('Page.page_number' => 1, 'Page.model'=>$model->name)), $settings);
			$this->runtime[$model->alias]['Page'] = $pageRelationship;
			$model->bindModel(array('hasOne' => array('Page'=>$pageRelationship)), false);
		}
		if (isset($this->runtime[$model->alias]['languages']) && $languages = $this->runtime[$model->alias]['languages']) {
			foreach ($languages as $locale) {
				$alias = 'Page_'.$locale;
				$pageRelationship = Set::merge($this->_defaultSettings, array('conditions' => array($alias.'.page_number' => 1, $alias.'.model'=>$model->name, $alias.'.locale'=>$locale)), $settings);
				$model->bindModel(array('hasOne' => array($alias=>$pageRelationship)), false);
			}
		}
		if (!isset($model->hasMany['Translations'])) {
			$pageRelationship = Set::merge($this->_defaultSettings, array('conditions' => array('Translation.page_number' => 1, 'Translation.model' => $model->name)), $settings);
			$model->bindModel(array('hasMany' => array('Translation'=>$pageRelationship)), false);
		}		
	}
	function getLocale(&$model) {
		$locale = isset($model->locale) ? $model->locale : Configure::read('Config.locale');
		if (!is_array($locale)) 
			$locale = array($locale, @$this->runtime[$model->alias]['languages'][0]);
		else {
			$locale[] = @$this->runtime[$model->alias]['languages'][0];
		}
		return array_unique($locale);
	}
	function beforeFind(&$model, $query, $isChild = false) {
		if($isChild) {
			$_query = array('contain'=>$query);
			$query = $_query;
		}
		$aliases = array();
		
		//pr(array($model->findQueryType, $query));
		
		if ($this->runtime[$model->alias]['languages']) {
			$locale = $this->getLocale(&$model);
			foreach ($locale as $_locale) {
				$aliases[] = 'Page_'.$_locale;
			}
		} else {
			$aliases[] = 'Page';
		}

		$virtualFields = array();

		foreach($model->virtualFields as $field => $value) {
			if ($value == 'Page.title' && !in_array('Page', $aliases)) {
				$virtualFields[$field] = $value;
				list($_model, $_field) = pluginSplit($value);
				unset($model->virtualFields[$field]);
				foreach($locale as $_locale) {
					$model->virtualFields[$field.'_'.$_locale] = 'Page_'.$_locale.'.'.$_field;
				}
			}
		}
		$fields = array();
		if(isset($query['fields']) && ($model->findQueryType != 'count')) {
			if(!is_array($query['fields'])) {
				$query['fields'] = array($query['fields']);
			}
			foreach ($query['fields'] as $i => $field) {
				list($_model, $_field) = pluginSplit($field);
				if (in_array($_field, array_keys($virtualFields)) && ($_model == $model->alias)) {
					$fields[$_field] = array();
					unset($query['fields'][$i]);
					unset($model->virtualFields[$_field]);
					$fields = array();
					foreach($aliases as $alias) {
						foreach($locale as $_locale) {
							$fields[$_field][] = $query['fields'][] = $_field.'_'.$_locale;
						}
					}
				}
			}
		}

		$this->runtime[$model->alias]['virtualFields'] = $virtualFields;
		$this->runtime[$model->alias]['fields'] = array_unique($fields);

		if ($model->findQueryType == 'list') { 
			if(count($virtualFields)) {
				$query = $this->_addChild($model, $query, $aliases, array('title'), $isChild);
			}
		} elseif (in_array($model->findQueryType, array('first', 'all'))) {
			if (!isset($query['recursive']) || (isset($query['recursive']) && $query['recursive'] > -1)) {
				$fields = false;

				if (isset($query['contain']['Page'])) {
					$fields = $query['contain']['Page'];
					unset($query['contain']['Page']);
				} elseif (isset($query['contain']) && in_array('Page', $query['contain'])) {
					$key = array_search('Page', $query['contain']);
					unset($query['contain'][$key]);
				}

				foreach($aliases as $alias) {
					if ($fields) {
						$query = $this->_addChild($model, $query, $aliases, $fields, $isChild);
					} else {
						$query = $this->_addChild($model, $query, $aliases, array(), $isChild);
					}
				}
				//$model->contain($query['contain']);
			}
		}
		if(isset($query['contain'])) {
			foreach ($query['contain'] as $key => $value) {
				if (is_numeric($key)) {
					$key = $value;
					$value = array();
				}
				if (in_array($key, array_keys($this->runtime))) {
					$model->$key->findQueryType = (count($value) > 1) ? 'all' : 'list';
					$query['contain'][$key] = $this->beforeFind(&$model->$key, $value, true);
				}
			}
		}
		if($isChild) {
			$query = $query['contain'];
		}
	//	pr($query);
		return $query;
	}
	function afterFind(&$model, $results, $primary) {
		if (!in_array($model->alias, array_keys($this->runtime))) {
			return $results;
		}
		$virtualFields = $this->runtime[$model->alias]['virtualFields'];
		$languages = $this->runtime[$model->alias]['languages'];
		$model->virtualFields = array_merge($model->virtualFields, $virtualFields);
		if (!$languages || empty($results)) {//|| empty($this->runtime[$model->alias]['beforeFind'])
			return $results;
		}
			
		foreach($results as $i=>$result) {
			if (is_array($result)) {
				foreach($result as $key=>$value) {
					if (is_array($value) && isset($model->$key) && is_object($model->$key)) {
						$results[$i][$key] = $this->afterFind(&$model->$key, $value, false);
					}
				}
			}
		}
		$locale = $this->getLocale(&$model);
		foreach ($results as $i => $result) {
			if(!is_array($result)) {
				continue;
			}
			$found = false;
			foreach ($locale as $_locale) {
				$alias = 'Page_'.$_locale;
				if(!isset($result[$alias])) {
					continue;
				}

				$first = current($result[$alias]);
				if ($first && !$found) {
					$found = true;
					$results[$i]['Page'] = $result[$alias];
				}
				unset($results[$i][$alias]);
			}
		}
		$keep = false;

		foreach($virtualFields as $key => $virtualField) {
			list($alias, $field) = pluginSplit($virtualField);
			foreach($results as $i=>$result) {
				if(!is_array($result)) {
					continue;
				}
				$keep = isset($result[$alias][$field]) ? $result[$alias][$field] : null;
				if ($keep) {
					$results[$i][$model->alias][$key] = $keep;
					foreach($locale as $_locale) {
						unset($results[$i][$model->alias][$key.'_'.$_locale]);
					}
				}
			}
			
		}
		$fields = array_unique($this->runtime[$model->alias]['fields']);

		foreach($results as $i => $result) {
			if(!is_array($result)) {
				continue;
			}
			$found = false;
			foreach ($fields as $_field => $_translations) {
				foreach ($_translations as $_found) {
					if (isset($result[$model->alias][$_found]) && $result[$model->alias][$_found] && !$found) {
						$results[$i][$model->alias][$_field] = $result[$model->alias][$_found];
						$found = true;
					}
					unset($results[$i][$model->alias][$_found]);
				}
			}
		}
		return $results;
	}
	
	function _addChild($model, $query, $aliases, $fields = array('title'), $isChild = false) {
		$out = array();
		foreach($aliases as $alias) {
			$out[$alias] = $fields;
		}
		if(!$isChild) {
			$model->Behaviors->attach('Containable');
			if(!isset($query['recursive']) || $query['recursive'] < 1) {
				$query['recursive'] = 1;
			}
		}
		if (!isset($query['contain'])) {
			$query['contain'] = array();
		}
		$query['contain'] = array_merge($query['contain'], $out);
			//$model->contain($query['contain']);
		return $query;
	}
}
?>