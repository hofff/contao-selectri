<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Util;

use Contao\Backend;
use Contao\Controller;
use Hofff\Contao\Selectri\Model\Node;

use function strlen;
use function strncmp;
use function strpos;

class Icons
{
    public const DEFAULT_ICON = 'iconPLAIN.gif';

    /** @var array<string,string> */
    protected static $tableIcons = [
        'tl_article'                => 'articles.svg',
        'tl_calendar'               => 'system/modules/calendar/html/icon.svg',
        'tl_faq_category'           => 'system/modules/faq/html/icon.svg',
        'tl_form'                   => 'form.svg',
        'tl_layout'                 => 'layout.svg',
        'tl_member'                 => 'member.svg',
        'tl_member_group'           => 'mgroup.svg',
        'tl_module'                 => 'modules.svg',
        'tl_news_archive'           => 'news.svg',
        'tl_newsletter_channel'     => 'system/modules/newsletter/html/icon.svg',
        'tl_newsletter_recipients'  => 'member.svg',
        'tl_search'                 => 'regular.svg',
        'tl_style'                  => 'iconCSS.svg',
        'tl_style_sheet'            => 'css.svg',
        'tl_task'                   => 'taskcenter.svg',
        'tl_user'                   => 'user.svg',
        'tl_user_group'             => 'group.svg',
    ];

    /** @var array<string,callable> */
    protected static $tableIconCallbacks = [
        'tl_page' => [
            [self::class, 'getPageIcon'],
            ['type', 'published', 'start', 'stop', 'hide', 'protected'],
        ],
    ];

    public static function setTableIcon(string $table, string $icon): void
    {
        self::$tableIcons[$table] = $icon;
    }

    public static function getTableIcon(string $table): ?string
    {
        return self::$tableIcons[$table] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function getTableIcons(): array
    {
        return self::$tableIcons;
    }

    /**
     * @param array<string> $columns
     */
    public static function setTableIconCallback(string $table, callable $callback, ?array $columns = null): void
    {
        self::$tableIconCallbacks[$table] = [$callback, (array) $columns];
    }

    public static function getTableIconCallback(string $table): ?callable
    {
        return self::$tableIconCallbacks[$table] ?? null;
    }

    /**
     * @return array<string, callable>
     */
    public static function getTableIconCallbacks(): array
    {
        return self::$tableIconCallbacks;
    }

    public static function getIconPath(?string $icon = null): string
    {
        strlen($icon) || $icon = self::DEFAULT_ICON;

        if (strpos($icon, '/') !== false) {
            return $icon;
        }

        if (strncmp($icon, 'icon', 4) === 0) {
            return TL_ASSETS_URL . 'assets/contao/images/' . $icon;
        }

        return TL_FILES_URL . 'system/themes/' . Backend::getTheme() . '/images/' . $icon;
    }

    public static function getPageIcon(Node $node): string
    {
        return self::getIconPath(Controller::getPageStatusIcon((object) $node->getData()));
    }
}
