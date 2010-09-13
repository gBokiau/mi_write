<?php
class HasPageBehavior extends ModelBehavior {
	var $runtime= array();
	var $_defaultSettings = array(
			'className' => 'MiWrite.Page',
			'foreignKey' => 'foreign_id',
			'conditions' => array(
				'Page.page_number' => 1,
				'Page.locale' => 'eng'
			),
	);
	function setup(&$Model, $settings = array()) {
	//	if (!isset($this->runtime[$Model->alias])) {
	//		$this->runtime[$Model->alias] = am(array('display' => ''), $settings);
	//	}
		if($Model->displayField == 'id') {
			$Model->displayField = 'display';
			$Model->virtualFields['display'] = 'Page.title';
		}

		if (!isset($Model->hasOne['Page'])) {
			$pageRelationship = Set::merge($this->_defaultSettings, array('conditions'=>array('Page.model'=>$Model->name)), $settings);
			$this->runtime[$Model->alias] = $pageRelationship;
			$Model->bindModel(array('hasOne' => array('Page'=>$pageRelationship)), false);

		}
	}
	function beforeFind(&$model, $query) {
		if ($model->Behaviors->enabled('Translate')) {
			$pageRelationship = $this->runtime[$model->alias];
			$locale = isset($model->locale) ? $model->locale : Configure::read('Config.locale');
			$pageRelationship['conditions']['Page.locale'] = $locale;
			$model->bindModel(array('hasOne' => array('Page'=>$pageRelationship)), false);
				
		}
		if ($model->findQueryType == 'list') { 
			if($model->isVirtualField($query['fields'][1])) {
				$model->Behaviors->attach('Containable');
				$query['recursive'] = 1;
				$query['contain'] = array('Page'=>array('title'));
			}
		} elseif ($model->findQueryType == 'first') {
			if (!isset($query['recursive']) || (isset($query['recursive']) && $query['recursive'] > -1)) {
				$model->Behaviors->attach('Containable');
				$query['recursive'] = 1;
				if(!isset($query['contain'])) {
					$query['contain'] = array();
				}
				if(!array_key_exists('Page', $query['contain']))
					array_unshift($query['contain'], 'Page');
				$model->contain($query['contain']);
			}
		}
		return $query;
	}
	
/*	function afterSave(&$model, $created) {
		if ($model->id && isset($model->data['Page'])) {
			$this->runtime[$model->alias]['display'] = $model->data['Page']['title'];
		}
		
	}*/
	
	//function display(&$model) {
//		return $this->runtime[$model->alias]['display'];
//	}
}
?>