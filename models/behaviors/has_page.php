<?php
class HasPageBehavior extends ModelBehavior {
	var $runtime= array();
	var $languages = false;
	var $_defaultSettings = array(
			'className' => 'MiWrite.Page',
			'foreignKey' => 'foreign_id',
			'conditions' => array(),
	);
	function setup(&$Model, $settings = array()) {
	//	if (!isset($this->runtime[$Model->alias])) {
	//		$this->runtime[$Model->alias] = am(array('display' => ''), $settings);
	//	}
		if ($Model->displayField == 'id') {
			$Model->displayField = 'display';
			$Model->virtualFields['display'] = 'Page.title';
		}

		if(isset($settings['languages'])) {
			$this->languages = $settings['languages'];
			unset($settings['languages']);
		}
		$this->runtime[$Model->alias] = array();
		if (!isset($Model->hasOne['Page']) & !$this->languages) {
			$pageRelationship = Set::merge($this->_defaultSettings, array('conditions' => array('Page.page_number' => 1, 'Page.model'=>$Model->name)), $settings);
			$this->runtime[$Model->alias]['Page'] = $pageRelationship;
			$Model->bindModel(array('hasOne' => array('Page'=>$pageRelationship)), false);
		}
		if ($this->languages) {
			foreach ($this->languages as $locale) {
				$alias = 'Page_'.$locale;
				$pageRelationship = Set::merge($this->_defaultSettings, array('conditions' => array($alias.'.page_number' => 1, $alias.'.model'=>$Model->name, $alias.'.locale'=>$locale)), $settings);
				$Model->bindModel(array('hasOne' => array($alias=>$pageRelationship)), false);
			}
		}
		if (!isset($Model->hasMany['Translations'])) {
			$pageRelationship = Set::merge($this->_defaultSettings, array('conditions' => array('Translation.model' => $Model->name)), $settings);
			$Model->bindModel(array('hasMany' => array('Translation'=>$pageRelationship)), false);
		}		
	}
	function getLocale(&$model) {
		$locale = isset($model->locale) ? $model->locale : Configure::read('Config.locale');
		if (!is_array($locale)) 
			$locale = array($locale, @$this->languages[0]);
		else {
			$locale[] = @$this->languages[0];
		}
		return array_unique($locale);
	}
	function beforeFind(&$model, $query) {
		$aliases = array();
		if ($this->languages) {
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
				$model->Behaviors->attach('Containable');
				$query['recursive'] = 1;
				$query['contain'] = array();
				foreach($aliases as $alias) {
					$query['contain'][$alias] = array('title');
				}
				
			}
		} elseif (in_array($model->findQueryType, array('first', 'all'))) {
			if (!isset($query['recursive']) || (isset($query['recursive']) && $query['recursive'] > -1)) {
				$model->Behaviors->attach('Containable');
				$query['recursive'] = 1;
				if(!isset($query['contain'])) {
					$query['contain'] = array();
				}
				$fields = false;

				if (array_key_exists('Page', $query['contain'])) {
					$fields = $query['contain']['Page'];
					unset($query['contain']['Page']);
				} elseif (in_array('Page', $query['contain'])) {
					$key = array_search('Page', $query['contain']);
					unset($query['contain'][$key]);
				}

				foreach($aliases as $alias) {
					if ($fields) {
						$query['contain'][$alias] = $fields;
					} else {
						array_unshift($query['contain'], $alias);
					}
				}
				$model->contain($query['contain']);
			}
		}
		return $query;
	}
	function afterFind(&$model, $results, $primary) {
		$virtualFields = $this->runtime[$model->alias]['virtualFields'];
		$model->virtualFields = array_merge($model->virtualFields, $virtualFields);
		if (!$this->languages || empty($results)) {//|| empty($this->runtime[$model->alias]['beforeFind'])
			return $results;
		}
		$locale = $this->getLocale(&$model);
		foreach ($results as $i => $result) {
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
	function beforeValidate(&$model, $created) {
		if (isset($model->data['Page'])) {
			$model->data['Page']['model'] = $model->alias;
		} elseif(isset($model->data['Translation'])) {
			foreach ($model->data['Translation'] as $i => $content) {
				$model->data['Translation'][$i]['model'] = $model->alias;
			}
		}
	}
}
?>