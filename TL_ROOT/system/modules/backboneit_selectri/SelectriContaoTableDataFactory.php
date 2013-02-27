<?php

class SelectriContaoTableDataFactory extends SelectriTableDataFactory {
	
	protected $db;
	
	public function __construct() {
		parent::__construct();
		
		$this->db = Database::getInstance();
		
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
		if(!$this->db->tableExists($treeTable)) {
			return $this;
		}

		$cfg = $this->getConfig();
		$cfg->setTreeTable($treeTable);
		
		$labelColumns = $cfg->getTreeLabelColumns();
		$labelFormat = $cfg->getTreeLabelFormat();
		$orderByExpr = $cfg->getTreeOrderByExpr();
		$icon = $cfg->getTreeIcon();
		$additionalColumns = $cfg->getTreeAdditionalColumns();
		
		$this->getDefaultSettings(
			$treeTable,
			$cfg->getTreeKeyColumn(),
			$labelColumns,
			$labelFormat,
			$orderByExpr,
			$icon,
			$additionalColumns
		);

		$cfg->setTreeLabelColumns($labelColumns);
		$cfg->setTreeLabelFormat($labelFormat);	
		$cfg->setTreeOrderByExpr($orderByExpr);
		$cfg->setTreeIcon($icon);
		$cfg->setTreeAdditionalColumns($additionalColumns);

		return $this;
	}
	
	public function setItemTable($itemTable) {
		if(!$this->db->tableExists($itemTable)) {
			return $this;
		}
		
		$cfg = $this->getConfig();
		$cfg->setItemTable($itemTable);
		
		$labelColumns = $cfg->getItemLabelColumns();
		$labelFormat = $cfg->getItemLabelFormat();
		$orderByExpr = $cfg->getItemOrderByExpr();
		$icon = $cfg->getItemIcon();
		$additionalColumns = $cfg->getItemAdditionalColumns();
		
		$this->getDefaultSettings(
			$itemTable,
			$cfg->getItemKeyColumn(),
			$labelColumns,
			$labelFormat,
			$orderByExpr,
			$icon,
			$additionalColumns
		);
		
		$cfg->setItemLabelColumns($labelColumns);
		$cfg->setItemLabelFormat($labelFormat);
		$cfg->setItemOrderByExpr($orderByExpr);
		$cfg->setItemIcon($icon);
		$cfg->setItemAdditionalColumns($additionalColumns);
		
		return $this;
	}
	
	protected function getDefaultSettings($table, $id, &$labelColumns, &$labelFormat, &$orderByExpr, &$icon, &$additionalColumns) {
		if(!$labelColumns) {
			if($this->db->fieldExists('name', $table)) {
				$labelColumns = array('name', $id);
			} elseif($this->db->fieldExists('title', $table)) {
				$labelColumns = array('title', $id);
			} else {
				$labelColumns = array($id);
			}
		}
		
		if(!$labelFormat) {
			$labelFormat = '';
			foreach($labelColumns as $column) {
				$labelFormat .= $column == $id ? ' (ID %s)' : ', %s';
			}
			$labelFormat = ltrim($labelFormat, ', ');
		}
		
		if(!$orderByExpr) {
			if($this->db->fieldExists('sorting', $table)) {
				$orderByExpr = 'sorting';
			} else {
				$orderByExpr = $labelColumns[0];
			}
		}
		
		if(!$icon) {
			$icon = self::getIcon($table, $iconColumns);
			$iconColumns && $additionalColumns = array_merge((array) $additionalColumns, $iconColumns);
		}
	}
	
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
		'tl_page'					=> array(__CLASS__, 'resolvePageIcon'),
		'tl_search'					=> 'regular.gif',
		'tl_style'					=> 'iconCSS.gif',
		'tl_style_sheet'			=> 'css.gif',
		'tl_task'					=> 'taskcenter.gif',
		'tl_user'					=> 'user.gif',
		'tl_user_group'				=> 'group.gif',
	);
	
	protected static $iconColumns = array(
		'tl_page'	=> array('type', 'published', 'start', 'stop', 'hide', 'protected'),
	);
	
	public static function setIcon($table, $icon, array $iconColumns = null) {
		self::$icons[$table] = $icon;
		$iconColumns && self::$iconColumns[$table] = $iconColumns;
	}
	
	public static function getIcon($table, array &$iconColumns = null) {
		isset(self::$iconColumns[$table]) && $iconColumns = self::$iconColumns[$table];
		return isset(self::$icons[$table]) ? self::$icons[$table] : 'iconPLAIN.gif';
	}
	
	public static function getIcons(array &$iconColumns = null) {
		$iconColumns = self::$iconColumns;
		return self::$icons;
	}
	
	public static function resolvePageIcon($node, $data, $cfg) {
		$row = $node['additional'];
		if(!$row['published'] || ($row['start'] && $row['start'] > time()) || ($row['stop'] && $row['stop'] < time())) {
			$sub += 1;
		}
		
		if($row['hide'] && !in_array($row['type'], array('redirect', 'forward', 'root', 'error_403', 'error_404'))) {
			$sub += 2;
		}
		
		if($row['protected'] && !in_array($row['type'], array('root', 'error_403', 'error_404'))) {
			$sub += 4;
		}
		
		return $sub ? $row['type'] . '_' . $sub . '.gif' : $row['type'].'.gif';
	}
	
}
