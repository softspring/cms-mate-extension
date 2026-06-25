<?php

declare(strict_types=1);

namespace Softspring\CmsMateExtension\Tests;

use PHPUnit\Framework\TestCase;
use Softspring\CmsMateExtension\Capability\CmsContentTool;
use Softspring\CmsMateExtension\Service\AppKernelProvider;
use Softspring\CmsMateExtension\Service\CmsContentReader;

class PackageStructureTest extends TestCase
{
    public function testPackageProvidesExpectedMateFiles(): void
    {
        self::assertFileExists(dirname(__DIR__) . '/INSTRUCTIONS.md');
        self::assertFileExists(dirname(__DIR__) . '/config/config.php');
    }

    public function testMainServicesAreAutoloadable(): void
    {
        self::assertTrue(class_exists(CmsContentTool::class));
        self::assertTrue(class_exists(CmsContentReader::class));
        self::assertTrue(class_exists(AppKernelProvider::class));
    }
}
