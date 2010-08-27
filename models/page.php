<?php
/**
 * Page Model
 *
 * The page model handles the content of a single page. Seperating Page from entry
 * Allows this model to be associated with others (such as a description for a calendar
 * event).
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
 * Page class
 *
 * @uses          MiWriteAppModel
 * @package       mi_write
 * @subpackage    mi_write.models
 */
class Page extends MiWriteAppModel {

/**
 * name property
 *
 * @var string 'Page'
 * @access public
 */
	public $name = 'Page';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array(
		/*'Media.MediaFields' => array(
			'fields' => array(
				'icon',
				'media' => array(
					'file' => 'media',
					'mime_type' => 'media_type',
				)
			),
		),
		'Mi.AutoFormat' => array(
			'fields' => array(
				'body',
				'intro'
			)
		),
		'Mi.List' => array(
			'sequence' => 'page_number',
			'scope' => array(
				'model',
				'foreign_id'
			)
		),*/
		//'Mi.RestrictSite',
		'MiWrite.Slugged' => array(
			'label' => 'title'
		),
		/*'MiEnums.Enum' => array(
			'fields' => array(
				'auto_intro',
				'layout'
			)
		),
		'MiTags.Tagged' => array(
			'tagModel' => 'MiTags.Tag',
			'linkModel' => 'TagLink',
			'newTags' => true,
			'format' => false,
		),*/
	);

/**
 * order property
 *
 * @var string 'Page.created DESC'
 * @access public
 */
	public $order = 'Page.created DESC';

/**
 * validate property
 *
 * @var array
 * @access public
 */
	public $validate = array(
		/*'site_id' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),*/
		'user_id' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'layout' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
		'page_number' => array(
			'numeric' => array(
				'rule' => array('numeric'),
			),
		),
		'title' => array(
			'notempty' => array(
				'rule' => array('notempty'),
			),
		),
	);

/**
 * stopWords - place holder for the stop words stored in vendors/stop_words
 * used to filter serach terms
 *
 * @var array
 * @access public
 */
	public $stopWords = array();

/**
 * belongsTo property
 *
 * @var array
 * @access public
 */
	public $belongsTo = array(
		/**'Site' => array(
			'className' => 'Site',
		),*/
		'User' => array(
			'className' => 'User',
		),
		'Update' => array(
			'className' => 'Update',
			'foreignKey' => 'foreign_id',
			'conditions' => array('Page.model' => 'Update'),
		),
		'Event' => array(
			'className' => 'Event',
			'foreignKey' => 'foreign_id',
			'conditions' => array('Page.model' => 'Event'),
		)
	);

/**
 * Set the intro and meta data if missing. Use the text helper (oh the horror)
 * for getting an extract of the body
 *
 * @return boolean True if validate operation should continue, false to abort
 * @param $options array Options passed from model::save(), see $options of model::save().
 * @access public
 * @link          http://book.cakephp.org/view/682/beforeValidate
 */
	public function beforeValidate($options = array()) {
		if (!empty($this->data[$this->alias]['body']) && (!$this->id || !empty($this->data[$this->alias]['auto_intro']))) {
			App::import('Core', 'Helper');
			App::import('Helper', 'Text');
			$this->data[$this->alias]['intro'] = TextHelper::truncate(
				preg_replace('@<img.*>@U', '', $this->data[$this->alias]['body']),
				400,
				array('exact' => false, 'html' => true)
			);
			if (empty($this->data[$this->alias]['meta_title'])) {
				$this->data[$this->alias]['meta_title'] = $this->data[$this->alias]['title'];
			}
			if (empty($this->data[$this->alias]['meta_description'])) {
				$this->data[$this->alias]['meta_description'] = trim(strip_tags($this->data[$this->alias]['intro']));
			}
		}
		/*
		if (!empty($this->data[$this->alias]['serialized']) && is_array($this->data[$this->alias]['serialized'])) {
			$this->data[$this->alias]['serialized'] = serialize($this->data[$this->alias]['serialized']);
		}
		*/
		return true;
	}

/**
 * pageList method
 *
 * @param mixed $model null
 * @param mixed $fk null
 * @return void
 * @access public
 */
	public function pageList($model = null, $fk = null) {
		if (!$model) {
			if ($this->id && !empty($this->data[$this->alias]['model'])) {
				$model = $this->data[$this->alias]['model'];
			} elseif ($this->id) {
				$model = $this->field('model');
			}
		}
		if (!$fk) {
			if ($this->id && !empty($this->data[$this->alias]['foreign_id'])) {
				$fk = $this->data[$this->alias]['foreign_id'];
			} elseif ($this->id) {
				$fk = $this->field('foreign_id');
			}
		}
		$conditions['model'] = $model;
		$conditions['foreign_id'] = $fk;
		return $this->find('list', compact('conditions'));
	}

/**
 * removeStopWords from a search term. if $splitOnStopWord is true, the following occurs:
 * 	input "apples bananas pears and red cars"
 * 	output array('apples bananas pears', 'red cars')
 *
 * If the passed string doesn't contain the seperator, or after stripping out stop words there's
 * nothing left - the original input is returned (in the desired format)
 *
 * Therefore passing "contain" will return immediately array('contain')
 * Passing "contain this text" will return array('text')
 * 	both contain and this are stop words
 * Passing "contain this" will return array('contain this')
 *
 * @param string $string ''
 * @param string $seperator '
 * @param bool $splitOnStopWord true
 * @param bool $returnArray true
 * @return void
 * @access public
 */
	public function removeStopWords($string = '', $seperator = ' ', $splitOnStopWord = true, $returnArray = true) {
		if (!strpos($string, $seperator)) {
			if ($returnArray) {
				return array($string);
			}
			return $string;
		}
		$originalTerms = $terms = array_filter(array_map('trim', explode($seperator, $string)));
		$lang = MiCache::setting('Site.lang');
		if (!array_key_exists($lang, $this->stopWords)) {
			ob_start();
			App::import('Vendor', 'stop_words/' . $lang, array('file' => "stop_words/$lang.txt"));
			$stopWords = preg_replace('@/\*.*\*/@', '', ob_get_clean());
			$this->stopWords[$lang] = array_map('trim', explode(',', str_replace(array("\n", "\r"), '', $stopWords)));
		}
		if ($splitOnStopWord) {
			$terms = $chunk = array();
			foreach($originalTerms as $term) {
				if (in_array($term, $this->stopWords[$lang])) {
					if ($chunk) {
						$terms[] = $chunk;
						$chunk = array();
					}
					continue;
				}
				$chunk[] = $term;
			}
			if ($chunk) {
				$terms[] = $chunk;
			}
			foreach($terms as &$phrase) {
				$phrase = implode(' ', $phrase);
			}
		} else {
			$terms = array_diff($terms, $this->stopWords[$lang]);
		}
		if (!$terms) {
			$terms = array(implode(' ', $originalTerms));
		}
		if ($returnArray) {
			return $terms;
		}
		return implode($sepeartor, $terms);
	}

/**
 * Resave/recreate the tag_links based on the contents of the Page.tags field
 *
 * @param mixed $id null
 * @return void
 * @access public
 */
	public function resetTags($id = null) {
		$conditions = array(
			'page_number' => 1
		);
		if ($id) {
			$conditions['foreign_id'] = $id;
		}
		$pages = $this->find('all', array(
			'fields' => array('id', 'foreign_id', 'model', 'tags'),
			'conditions' => $conditions
		));
		foreach($pages as $page) {
			$this->id = $page['Page']['id'];
			$this->save($page);
		}
		/** todo
		* $this->ClassRegistry::init('MiTags.Tag')->updateCounters();
		*/
	}

/**
 * Set the page count on polymorphic associated models (Entry, Event)
 *
 * @return void
 * @access public
 */
	public function updateCounterCache() {
		if (!empty($this->data[$this->alias]['model']) && !empty($this->data[$this->alias]['foreign_id'])) {
			$Model = ClassRegistry::init($this->data[$this->alias]['model']);
			if ($Model->hasField('page_count')) {
				$count = $this->find('count', array(
					'conditions' => array(
						'model' => $this->data[$this->alias]['model'],
						'foreign_id' => $this->data[$this->alias]['foreign_id'],
					)
				));
				$Model->id = $this->data[$this->alias]['foreign_id'];
				$Model->saveField('page_count', $count);
			}
		}
		parent::updateCounterCache();
	}
}