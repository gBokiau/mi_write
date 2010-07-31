<?php
class HasPageBehavior extends ModelBehavior {
	function beforeFind(&$model, $query) {
		$model->Behaviors->attach('Containable');
		if ($model->findQueryType == 'list') { // && !isset($query['fields'])
			//$query['fields'] = array($this->alias.'.id', 'Page.title');
			if($model->isVirtualField($query['fields'][1])) {
				$query['recursive'] = 1;
				$query['contain'] = array('Page'=>array('title'));
			}
		} elseif ($model->findQueryType == 'first') {
			if(!isset($query['contain'])) {
				$query['contain'] = array();
			}
			if(!array_key_exists('Page', $query['contain']))
				$query['contain'] = $query['contain']+array('Page');
		}
		return $query;
	}
}
?>