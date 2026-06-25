<?php

declare(strict_types=1);

namespace Softspring\CmsMateExtension\Service;

use RuntimeException;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

use function class_exists;
use function filter_var;
use function is_file;
use function is_subclass_of;

use const FILTER_VALIDATE_BOOL;

final class AppKernelProvider
{
    private ?KernelInterface $kernel = null;

    public function __construct(
        private readonly string $rootDir,
    ) {}

    public function getContainer(): ContainerInterface
    {
        return $this->getKernel()->getContainer();
    }

    public function getKernel(): KernelInterface
    {
        if ($this->kernel instanceof KernelInterface) {
            return $this->kernel;
        }

        $this->loadEnvironment();

        $environment = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
        $debug = filter_var($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? ('prod' !== $environment), FILTER_VALIDATE_BOOL);

        $kernelClass = 'App\\Kernel';
        if (!class_exists($kernelClass) || !is_subclass_of($kernelClass, KernelInterface::class)) {
            throw new RuntimeException('The host application App\\Kernel class is not available.');
        }

        $kernel = new $kernelClass($environment, $debug);
        $kernel->boot();

        return $this->kernel = $kernel;
    }

    private function loadEnvironment(): void
    {
        $envFile = $this->rootDir . '/.env';
        if (!is_file($envFile) || !class_exists(Dotenv::class)) {
            return;
        }

        new Dotenv()->bootEnv($envFile);
    }
}
