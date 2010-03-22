<?php
/**
 * Entry model
 *
 * The lynchpin for editing content. The entry is intended only to hold meta data
 * contents are stored in the pages model
 *
 * PHP version 5
 *
 * Copyright (c) 2010, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.ad7six.com
 * @package       mi_write
 * @subpackage    mi_write.models
 * @since         v 1.0 (23-Mar-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Entry class
 *
 * @uses          MiWriteAppModel
 * @package       mi_write
 * @subpackage    mi_write.models
 */
class Entry extends MiWriteAppModel {

/**
 * name property
 *
 * @var string 'Entry'
 * @access public
 */
	public $name = 'Entry';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array(
		'Mi.RestrictSite',
		'MiEnums.Enum' => array(
			'fields' => array('comment_policy', 'status')
		)
	);

/**
 * displayField property
 *
 * @var string 'display'
 * @access public
 */
	public $displayField = 'display';

/**
 * recursive property
 *
 * @var int 0
 * @access public
 */
	public $recursive = 0;

/**
 * order property
 *
 * @var string 'Entry.web_id DESC'
 * @access public
 */
	public $order = 'Entry.web_id DESC';

/**
 * virtualFields property
 *
 * @var array
 * @access public
 */
	public $virtualFields = array(
		'display' => 'Page.title'
	);

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array(
		'site_id' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'user_id' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'comment_count' => array(
			'numeric' => array(
				'rule' => array('numeric'),
			),
		),
		'page_count' => array(
			'numeric' => array(
				'rule' => array('numeric'),
			),
		),
	);

	public $belongsTo = array(
		'Site' => array(
			'className' => 'Site',
			'foreignKey' => 'site_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
		'Category' => array(
			'className' => 'MiTags.Tag',
			'conditions' => array(
				'Category.type' => 'category',
			),
		),
		'Series' => array(
			'className' => 'MiTags.Tag',
			'conditions' => array(
				'Category.type' => 'series',
			),
		)
	);

	public $hasOne = array(
		'Page' => array(
			'className' => 'MiWrite.Page',
			'foreignKey' => 'foreign_id',
			'conditions' => array(
				'Page.model' => 'Entry',
				'Page.page_number' => 1,
			),
		)
	);

	public $hasMany = array(
		'Comment' => array(
			'className' => 'MiWrite.Comment',
		)
	);

/**
 * Get the list of categories available categories
 *
 * @param array $conditions array()
 * @param array $params array()
 * @return void
 * @access public
 */
	public function categories($conditions = array(), $params = array()) {
		$conditions = array_merge(array(
			'Tag.type' => 'category',
			'Tag.status' => 1
		), $conditions);

		$params = array_merge(array(
			'conditions' => $conditions,
			'order' => 'Tag.lft'
		), $params;

		return MiCache::('MiTags.Tag', 'find', 'list', $params);
	}

/**
 * Look for entries which match the passed search term
 *
 * @param mixed $term
 * @param array $params array()
 * @return void
 * @access public
 */
	function search($term, $params = array()) {
		$conditions = array();
		$page = 1;
		$limit = 20;

		if ($term) {
			$conditions['OR'] = array(
				$this->alias . '.id LIKE' => $term . '%',
				'Page.title LIKE' => $term . '%',
			);
		}

		if (!empty($params['page'])) {
			$page = $params['page'];
			unset($params['page']);
		}

		if (!empty($params['limit'])) {
			$limit = $params['limit'];
			unset($params['limit']);
		}

		$conditions = array_merge($conditions, $params);
		$return = $this->find('list', compact('conditions', 'page', 'limit'));
		if ($term && !$return) {
			$conditions['OR'][$this->alias . '.id LIKE'] = '%' . $term . '%';
			$conditions['OR']['Page.title LIKE'] = '%' . $term . '%';
			$return = $this->find('list', compact('conditions', 'page', 'limit'));
		}
		return $return;
	}

/**
 * findList method
 *
 * Override recursive to ensure the page title can be found
 *
 * @param mixed $state
 * @param mixed $query
 * @param array $results array()
 * @return void
 * @access protected
 */
	function _findList($state, $query, $results = array()) {
		if ($state == 'before' && !isset($query['fields'])) {
			$query['fields'] = array('Entry.id', 'Page.title');
			$query['recursive'] = 0;
		}
		return parent::_findList($state, $query, $results);
	}
}