<?php

declare(strict_types=1);

namespace Softspring\CmsMateExtension\Service;

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class AppKernelProvider
{
    private ?KernelInterface $kernel = null;

    public function __construct(
        private readonly string $rootDir,
    ) {
    }

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
        $debug = filter_var($_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? ('prod' !== $environment), \FILTER_VALIDATE_BOOL);

        $kernel = new Kernel($environment, $debug);
        $kernel->boot();

        return $this->kernel = $kernel;
    }

    private function loadEnvironment(): void
    {
        $envFile = $this->rootDir.'/.env';
        if (!is_file($envFile) || !class_exists(Dotenv::class)) {
            return;
        }

        (new Dotenv())->bootEnv($envFile);
    }
}
