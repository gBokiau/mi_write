<?php
class HasPageBehavior extends ModelBehavior {
	var $runtime= array();
		function setup(&$Model, $settings) {
			if (!isset($this->runtime[$Model->alias])) {
				$this->runtime[$Model->alias] = array(
					'display' => ''
				);
			}
		}
	function beforeFind(&$model, $query) {
		$model->Behaviors->attach('Containable');
		if ($model->findQueryType == 'list') { // && !isset($query['fields'])
			//$query['fields'] = array($this->alias.'.id', 'Page.title');
			if($model->isVirtualField($query['fields'][1])) {
				$query['recursive'] = 1;
				$query['contain'] = array('Page'=>array('title'));
			}
		} elseif ($model->findQueryType == 'first') {
			if (!isset($query['recursive']) || (isset($query['recursive']) && $query['recursive'] > -1)) {
				$query['recursive'] = 1;
				if(!isset($query['contain'])) {
					$query['contain'] = array();
				}
				if(!array_key_exists('Page', $query['contain']))
					$query['contain'][] = 'Page';
			}
		}
		return $query;
	}
	
	function afterSave(&$model, $created) {
		if ($model->id && isset($model->data['Page'])) {
			$this->runtime[$model->alias]['display'] = $model->data['Page']['title'];
		}
		
	}
	
	function display(&$model) {
		return $this->runtime[$model->alias]['display'];
	}
}
?>