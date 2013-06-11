<?php

class SelectriContaoTableDataFactory extends SelectriTableDataFactory {

	protected static $icons = array(
		'tl_article'				=> 'articles.gif',
		'tl_calendar'				=> 'system/modules/calendar/html/icon.gif',
		'tl_faq_category'			=> 'system/modules/faq/html/icon.gif',
		'tl_form'					=> 'form.gif',
		'tl_layout'					=> 'layout.gif',
		'tl_member'					=> 'member.gif',
		'tl_member_group'			=> 'mgroup.gif',
		'tl_module'					=> 'modules.gif',
		'tl_news_archive'			=> 'news.gif',
		'tl_newsletter_channel'		=> 'system/modules/newsletter/html/icon.gif',
		'tl_newsletter_recipients'	=> 'member.gif',
		'tl_search'					=> 'regular.gif',
		'tl_style'					=> 'iconCSS.gif',
		'tl_style_sheet'			=> 'css.gif',
		'tl_task'					=> 'taskcenter.gif',
		'tl_user'					=> 'user.gif',
		'tl_user_group'				=> 'group.gif',
	);

	public static function setIcon($table, $icon) {
		self::$icons[$table] = $icon;
	}

	public static function getIcon($table) {
		return self::$icons[$table];
	}

	public static function getIcons() {
		return self::$icons;
	}

	protected static $iconCallbacks = array(
		'tl_page' => array(
			array(__CLASS__, 'pageIconCallback'),
			array('type', 'published', 'start', 'stop', 'hide', 'protected')
		),
	);

	public static function setIconCallback($table, $callback, array $columns = null) {
		self::$iconCallbacks[$table] = array($callback, (array) $columns);
	}

	public static function getIconCallback($table) {
		return (array) self::$iconCallbacks[$table];
	}

	public static function getIconCallbacks() {
		return self::$iconCallbacks;
	}

	public function __construct() {
		parent::__construct();

		$cfg = $this->getConfig();

		$cfg->setTreeKeyColumn('id');
		$cfg->setTreeParentKeyColumn('pid');
		$cfg->setTreeRootValue(0);

		$cfg->setItemKeyColumn('id');
		$cfg->setItemTreeKeyColumn('pid');
	}

	public function __clone() {
		parent::__clone();
	}

	public function setParameters($params) {
		parent::setParameters($params);
		$params = (array) $params;
		isset($params['treeTable']) && $this->setTreeTable($params['treeTable']);
		isset($params['itemTable']) && $this->setItemTable($params['itemTable']);
		return $this;
	}

	public function setTreeTable($treeTable) {
		$db = $this->getDatabase();
		if(!$db->tableExists($treeTable)) {
			return $this;
		}

		$cfg = $this->getConfig();
		$cfg->setTreeTable($treeTable);

		if($db->fieldExists('sorting', $treeTable)) {
			$cfg->setTreeOrderByExpr('sorting');
		}

		return $this;
	}

	public function setItemTable($itemTable) {
		$db = $this->getDatabase();
		if(!$db->tableExists($itemTable)) {
			return $this;
		}

		$cfg = $this->getConfig();
		$cfg->setItemTable($itemTable);

		if($db->fieldExists('sorting', $itemTable)) {
			$cfg->setItemOrderByExpr('sorting');
		}

		return $this;
	}

	protected function prepareTreeConfig(SelectriTableDataConfig $cfg) {
		if(!$cfg->getTreeLabelCallback()) {
			$formatter = $this->createLabelFormatter($cfg->getTreeTable(), $cfg->getTreeKeyColumn());
			$cfg->setTreeLabelCallback($formatter->getCallback());
		}
		if(!$cfg->getTreeIconCallback()) {
			list($callback, $columns) = self::getIconCallback($cfg->getTreeTable());
			if($callback) {
				$cfg->setTreeIconCallback($callback);
				$cfg->addTreeColumns($columns);
			} else {
				$cfg->setTreeIconCallback(array(__CLASS__, 'treeIconCallback'));
			}
		}
		parent::prepareTreeConfig($cfg);
	}

	protected function prepareItemConfig(SelectriTableDataConfig $cfg) {
		if(!$cfg->getItemLabelCallback()) {
			$formatter = $this->createLabelFormatter($cfg->getItemTable(), $cfg->getItemKeyColumn());
			$cfg->setItemLabelCallback($formatter->getCallback());
		}
		if(!$cfg->getItemIconCallback()) {
			list($callback, $columns) = self::getIconCallback($cfg->getItemTable());
			if($callback) {
				$cfg->setItemIconCallback($callback);
				$cfg->addItemColumns($columns);
			} else {
				$cfg->setItemIconCallback(array(__CLASS__, 'itemIconCallback'));
			}
		}
		parent::prepareTreeConfig($cfg);
	}

	protected function createLabelFormatter($table, $keyColumn) {
		if($this->getDatabase()->fieldExists('name', $table)) {
			$fields = array('name', $keyColumn);
		} elseif($this->getDatabase()->fieldExists('title', $table)) {
			$fields = array('title', $keyColumn);
		} else {
			$fields = array($keyColumn);
		}

		$format = '';
		foreach($fields as $field) {
			$format .= $field == $keyColumn ? ' (ID %s)' : ', %s';
		}
		$format = ltrim($format, ', ');

		return SelectriLabelFormatter::create($format, $fields);
	}

	public static function treeIconCallback(array $node, SelectriData $data, SelectriTableDataConfig $cfg) {
		return SelectriTableDataFactory::getIconPath($data->getWidget(), self::getIcon($cfg->getTreeTable()));
	}

	public static function itemIconCallback(array $node, SelectriData $data, SelectriTableDataConfig $cfg) {
		return SelectriTableDataFactory::getIconPath($data->getWidget(), self::getIcon($cfg->getItemTable()));
	}

	public static function pageIconCallback(array $node, SelectriData $data, SelectriTableDataConfig $cfg) {
		if(!$node['published'] || ($node['start'] && $node['start'] > time()) || ($node['stop'] && $node['stop'] < time())) {
			$sub += 1;
		}
		if($node['hide'] && !in_array($node['type'], array('redirect', 'forward', 'root', 'error_403', 'error_404'))) {
			$sub += 2;
		}
		if($node['protected'] && !in_array($node['type'], array('root', 'error_403', 'error_404'))) {
			$sub += 4;
		}
		$icon = $sub ? $node['type'] . '_' . $sub . '.gif' : $node['type'].'.gif';
		return SelectriTableDataFactory::getIconPath($data->getWidget(), $icon);
	}

}
