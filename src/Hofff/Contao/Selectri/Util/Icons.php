<?php

namespace Hofff\Contao\Selectri\Util;

use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Model\Data;

class Icons {

	/**
	 * @var string
	 */
	const DEFAULT_ICON = 'iconPLAIN.gif';

	/**
	 * @var array
	 */
	protected static $tableIcons = array(
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

	/**
	 * @var array
	 */
	protected static $tableIconCallbacks = array(
		'tl_page' => array(
			array(__CLASS__, 'getPageIcon'),
			array('type', 'published', 'start', 'stop', 'hide', 'protected')
		),
	);

	/**
	 * @param string $table
	 * @param string $icon
	 * @return void
	 */
	public static function setTableIcon($table, $icon) {
		self::$tableIcons[$table] = $icon;
	}

	/**
	 * @param string $table
	 * @return string|null
	 */
	public static function getTableIcon($table) {
		return self::$tableIcons[$table];
	}

	/**
	 * @return array<string, string>
	 */
	public static function getTableIcons() {
		return self::$tableIcons;
	}

	/**
	 * @param string $table
	 * @param callable $callback
	 * @param array<string> $columns
	 * @return void
	 */
	public static function setTableIconCallback($table, $callback, array $columns = null) {
		self::$tableIconCallbacks[$table] = array($callback, (array) $columns);
	}

	/**
	 * @param string $table
	 * @return callable
	 */
	public static function getTableIconCallback($table) {
		return (array) self::$tableIconCallbacks[$table];
	}

	/**
	 * @return array<string, callable>
	 */
	public static function getTableIconCallbacks() {
		return self::$tableIconCallbacks;
	}

	/**
	 * @param string|null $icon
	 * @return string
	 */
	public static function getIconPath($icon = null) {
		strlen($icon) || $icon = self::DEFAULT_ICON;

		if(strpos($icon, '/') !== false) {
			return $icon;
		}

		if(strncmp($icon, 'icon', 4) === 0) {
			return TL_ASSETS_URL . 'assets/contao/images/' . $icon;
		}

		return TL_FILES_URL . 'system/themes/' . \Backend::getTheme() . '/images/' . $icon;
	}

	/**
	 * @param Node $node
	 * @param Data $data
	 * @return string
	 */
	public static function getPageIcon(Node $node, Data $data) {
		return self::getIconPath(\Controller::getPageStatusIcon((object) $node->getData()));
	}

}
