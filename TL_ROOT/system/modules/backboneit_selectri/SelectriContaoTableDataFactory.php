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
			return;
		}

		$cfg = $this->getConfig();
		$cfg->setTreeTable($treeTable);
		
		$labelColumns = $cfg->getTreeLabelColumns();
		$labelFormat = $cfg->getTreeLabelFormat();
		$orderByExpr = $cfg->getTreeOrderByExpr();
		$icon = $cfg->getTreeIcon();
		
		$this->getDefaultSettings(
			$treeTable,
			$cfg->getTreeKeyColumn(),
			$labelColumns,
			$labelFormat,
			$orderByExpr,
			$icon
		);

		$cfg->setTreeLabelColumns($labelColumns);
		$cfg->setTreeLabelFormat($labelFormat);	
		$cfg->setTreeOrderByExpr($orderByExpr);
		$cfg->setTreeIcon($icon);
	}
	
	public function setItemTable($itemTable) {
		if(!$this->db->tableExists($itemTable)) {
			return;
		}
		
		$cfg = $this->getConfig();
		$cfg->setItemTable($itemTable);
		
		$labelColumns = $cfg->getItemLabelColumns();
		$labelFormat = $cfg->getItemLabelFormat();
		$orderByExpr = $cfg->getItemOrderByExpr();
		$icon = $cfg->getItemIcon();
		
		$this->getDefaultSettings(
			$itemTable,
			$cfg->getItemKeyColumn(),
			$labelColumns,
			$labelFormat,
			$orderByExpr,
			$icon
		);
		
		$cfg->setItemLabelColumns($labelColumns);
		$cfg->setItemLabelFormat($labelFormat);
		$cfg->setItemOrderByExpr($orderByExpr);
		$cfg->setItemIcon($icon);
	}
	
	protected function getDefaultSettings($table, $id, &$labelColumns, &$labelFormat, &$orderByExpr, &$icon) {
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
			$icon = self::getIcon($table);
		}
	}
	
	public static function getIcon($table, array &$additionalColumns = null) {
		switch($table) {
			case 'tl_article':				return 'articles.gif'; break;
			case 'tl_calendar':				return 'system/modules/calendar/html/icon.gif'; break;
			case 'tl_faq_category':			return 'system/modules/faq/html/icon.gif'; break;
			case 'tl_form':					return 'form.gif'; break;
			case 'tl_layout':				return 'layout.gif'; break;
			case 'tl_member':				return 'member.gif'; break;
			case 'tl_member_group':			return 'mgroup.gif'; break;
			case 'tl_module':				return 'modules.gif'; break;
			case 'tl_news_archive':			return 'news.gif'; break;
			case 'tl_newsletter_channel':	return 'system/modules/newsletter/html/icon.gif'; break;
			case 'tl_newsletter_recipients':return 'member.gif'; break;
			case 'tl_page':					return 'regular.gif'; break;
// 			case 'tl_page':					return array(__CLASS__, 'getPageIcon'); break; // TODO
			case 'tl_search':				return 'regular.gif'; break;
			case 'tl_style':				return 'iconCSS.gif'; break;
			case 'tl_style_sheet':			return 'css.gif'; break;
			case 'tl_task':					return 'taskcenter.gif'; break;
			case 'tl_user':					return 'user.gif'; break;
			case 'tl_user_group':			return 'group.gif'; break;
			default:
				return isset(self::$icons[$table]) ? self::$icons[$table] : 'iconPLAIN.gif';
				break;
		}
	}
	
	protected static $icons = array();
	
	public static function addIcon($table, $icon) {
		self::$icons[$table] = $icon;
	}
	
	public static function getIcons() {
		return self::$icons;
	}
	
}
