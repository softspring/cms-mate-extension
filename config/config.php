<?php

declare(strict_types=1);

use Softspring\CmsMateExtension\Capability\CmsContentTool;
use Softspring\CmsMateExtension\Service\AppKernelProvider;
use Softspring\CmsMateExtension\Service\CmsContentReader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $configurator): void {
    $configurator->services()
        ->set(AppKernelProvider::class)
            ->args([
                '%mate.root_dir%',
            ])

        ->set(CmsContentReader::class)
            ->args([
                service(AppKernelProvider::class),
            ])

        ->set(CmsContentTool::class)
            ->args([
                service(CmsContentReader::class),
            ])
    ;
};
