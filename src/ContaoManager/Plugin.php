<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Config\ConfigInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Hofff\Contao\Selectri\HofffContaoSelectriBundle;

final class Plugin implements BundlePluginInterface
{
    /**
     * @return list<ConfigInterface>
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(HofffContaoSelectriBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['hofff_selectri']),
        ];
    }
}
