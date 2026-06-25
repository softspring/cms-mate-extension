<?php

declare(strict_types=1);

namespace Softspring\CmsMateExtension\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Softspring\CmsBundle\Model\ContentInterface;
use Softspring\CmsBundle\Model\ContentVersionInterface;
use Softspring\CmsBundle\Model\RouteInterface;
use Softspring\CmsBundle\Model\RoutePathInterface;
use Softspring\CmsBundle\Model\SiteInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function max;
use function mb_stripos;
use function str_ends_with;
use function substr;
use function trim;

use const DATE_ATOM;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class CmsContentReader
{
    public function __construct(
        private readonly AppKernelProvider $kernelProvider,
    ) {}

    /**
     * @return array{count: int, contentTypes: list<array<string, mixed>>}
     */
    public function contentTypes(bool $includeRawConfig = false): array
    {
        $types = [];

        foreach ($this->getContentConfig() as $type => $config) {
            $row = [
                'id' => $type,
                'entityClass' => $config['entity_class'] ?? null,
                'defaultLayout' => $config['default_layout'] ?? null,
                'allowedLayouts' => $config['allowed_layouts'] ?? [],
                'saveCompiled' => $config['save_compiled'] ?? null,
                'extraFields' => array_keys($config['extra_fields'] ?? []),
                'indexingFields' => array_keys($config['indexing'] ?? []),
                'seoFields' => array_keys($config['version_seo'] ?? []),
            ];

            if ($includeRawConfig) {
                $row['config'] = $config;
            }

            $types[] = $row;
        }

        return [
            'count' => count($types),
            'contentTypes' => $types,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $type, string $id, ?string $locale = null, bool $includeData = false): ?array
    {
        $type = $this->resolveContentType($type);
        if (null === $type) {
            return null;
        }

        $class = $this->getContentClass($type);
        if (null === $class) {
            return null;
        }

        $content = $this->entityManager()->getRepository($class)->find($id);
        if (!$content instanceof ContentInterface) {
            return null;
        }

        return $this->detail($content, $type, $locale, $includeData);
    }

    /**
     * @return array{type: string|null, filters: array<string, mixed>, count: int, content: list<array<string, mixed>>}
     */
    public function search(?string $type = null, string $query = '', ?string $site = null, ?string $locale = null, bool $publishedOnly = false, int $limit = 20, bool $includeData = false): array
    {
        $limit = max(1, min($limit, 100));
        $query = trim($query);
        $rows = [];
        $resolvedType = null !== $type && '' !== trim($type) ? $this->resolveContentType($type) : null;
        $types = null !== $resolvedType ? [$resolvedType] : array_keys($this->getContentConfig());

        foreach ($types as $contentType) {
            $class = $this->getContentClass($contentType);
            if (null === $class) {
                continue;
            }

            foreach ($this->findContents($class, $site, $publishedOnly, max($limit * 5, 50)) as $content) {
                if ($locale && !in_array($locale, $content->getLocales() ?? [], true)) {
                    continue;
                }

                if ('' !== $query && !$this->matchesQuery($content, $query)) {
                    continue;
                }

                $rows[] = $includeData
                    ? $this->detail($content, $contentType, $locale, true)
                    : $this->summary($content, $contentType, $locale);

                if (count($rows) >= $limit) {
                    break 2;
                }
            }
        }

        return [
            'type' => $resolvedType,
            'filters' => [
                'query' => $query,
                'site' => $site,
                'locale' => $locale,
                'publishedOnly' => $publishedOnly,
                'includeData' => $includeData,
            ],
            'count' => count($rows),
            'content' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(ContentInterface $content, string $contentType, ?string $locale = null): array
    {
        $summary = [
            'id' => $content->getId(),
            'type' => $contentType,
            'name' => $content->getName(),
            'defaultLocale' => $content->getDefaultLocale(),
            'locales' => $content->getLocales(),
            'sites' => array_map(static fn(SiteInterface $site): ?string => $site->getId(), $content->getSites()->toArray()),
            'routes' => $this->routes($content, $locale),
        ];
        $publishedVersion = $content->getPublishedVersion();
        $lastVersion = $content->getLastVersion();

        $summary['locales'] = array_values($summary['locales'] ?? []);
        $summary['publishedVersion'] = $publishedVersion instanceof ContentVersionInterface ? $this->version($publishedVersion) : null;
        $summary['lastVersion'] = $lastVersion instanceof ContentVersionInterface ? $this->version($lastVersion) : null;

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(ContentInterface $content, string $contentType, ?string $locale, bool $includeData): array
    {
        $publishedVersion = $content->getPublishedVersion();
        if ($publishedVersion instanceof ContentVersionInterface) {
            $detail = $this->summary($content, $contentType, $locale);
            $detail['publishedVersion'] = $this->version($publishedVersion, true, $includeData);
        } else {
            $detail = $this->summary($content, $contentType, $locale);
        }

        $detail['extraData'] = $content->getExtraData();
        $detail['indexing'] = $content->getIndexing();
        $detail['locales'] = array_values($detail['locales'] ?? []);
        $lastVersion = $content->getLastVersion();
        $detail['lastVersion'] = $lastVersion instanceof ContentVersionInterface ? $this->version($lastVersion, true, $includeData) : null;

        return $detail;
    }

    /**
     * @return array<string, mixed>
     */
    private function version(ContentVersionInterface $version, bool $includeSeo = false, bool $includeData = false): array
    {
        $data = [
            'id' => $version->getId(),
            'versionNumber' => $version->getVersionNumber(),
            'layout' => $version->getLayout(),
            'origin' => $version->getOrigin(),
            'originDescription' => $version->getOriginDescription(),
            'note' => $version->getNote(),
            'keep' => $version->isKeep(),
            'published' => $version->isPublished(),
            'lastVersion' => $version->isLastVersion(),
            'createdAt' => $version->getCreatedAt()?->format(DATE_ATOM),
        ];

        if ($includeSeo) {
            $data['seo'] = $version->getSeo();
        }

        if ($includeData) {
            $data['data'] = $version->getData();
            $data['meta'] = $version->getMeta();
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function routes(ContentInterface $content, ?string $locale = null): array
    {
        return array_map(
            fn(RouteInterface $route): array => [
                'id' => $route->getId(),
                'type' => $route->getType(),
                'paths' => $this->routePaths($route, $locale),
            ],
            array_values(array_filter(
                $content->getRoutes()->toArray(),
                static fn(mixed $route): bool => $route instanceof RouteInterface,
            )),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function routePaths(RouteInterface $route, ?string $locale = null): array
    {
        $paths = [];

        foreach ($route->getPaths() as $path) {
            if (!$path instanceof RoutePathInterface) {
                continue;
            }

            if ($locale && $path->getLocale() !== $locale) {
                continue;
            }

            $paths[] = [
                'id' => $path->getId(),
                'locale' => $path->getLocale(),
                'path' => $path->getPath(),
                'compiledPath' => $path->getCompiledPath(),
                'cacheTtl' => $path->getCacheTtl(),
                'sites' => array_map(static fn(SiteInterface $site): ?string => $site->getId(), $path->getSites()->toArray()),
            ];
        }

        return $paths;
    }

    /**
     * @return list<ContentInterface>
     */
    private function findContents(string $class, ?string $site, bool $publishedOnly, int $maxResults): array
    {
        $em = $this->entityManager();
        $repository = $em->getRepository($class);
        $qb = $repository->createQueryBuilder('content')
            ->setMaxResults($maxResults);

        $this->orderContents($qb, $em->getClassMetadata($class));

        if ($publishedOnly) {
            $qb->andWhere('content.publishedVersion IS NOT NULL');
        }

        if (null !== $site && '' !== trim($site)) {
            $qb
                ->innerJoin('content.sites', 'site')
                ->andWhere('site.id = :site')
                ->setParameter('site', $site);
        }

        return array_values(array_filter(
            $qb->getQuery()->getResult(),
            static fn(mixed $content): bool => $content instanceof ContentInterface,
        ));
    }

    private function orderContents(QueryBuilder $qb, ClassMetadata $metadata): void
    {
        if ($metadata->hasField('name')) {
            $qb->addOrderBy('content.name', 'ASC');
        }

        if ($metadata->hasField('id')) {
            $qb->addOrderBy('content.id', 'ASC');
        }
    }

    private function matchesQuery(ContentInterface $content, string $query): bool
    {
        if (false !== mb_stripos((string) $content->getName(), $query)) {
            return true;
        }

        foreach ([$content->getExtraData(), $content->getIndexing()] as $payload) {
            if ($this->payloadMatchesQuery($payload, $query)) {
                return true;
            }
        }

        foreach ([$content->getPublishedVersion(), $content->getLastVersion()] as $version) {
            if (!$version instanceof ContentVersionInterface) {
                continue;
            }

            foreach ([$version->getSeo(), $version->getData(), $version->getMeta()] as $payload) {
                if ($this->payloadMatchesQuery($payload, $query)) {
                    return true;
                }
            }
        }

        foreach ($content->getRoutes() as $route) {
            if (!$route instanceof RouteInterface) {
                continue;
            }

            if (false !== mb_stripos((string) $route->getId(), $query)) {
                return true;
            }

            foreach ($route->getPaths() as $path) {
                if ($path instanceof RoutePathInterface && false !== mb_stripos((string) $path->getPath(), $query)) {
                    return true;
                }
            }
        }

        foreach ($content->getSites() as $site) {
            if ($site instanceof SiteInterface && false !== mb_stripos((string) $site->getId(), $query)) {
                return true;
            }
        }

        return false;
    }

    private function payloadMatchesQuery(mixed $payload, string $query): bool
    {
        if (is_string($payload)) {
            return false !== mb_stripos($payload, $query);
        }

        if (is_array($payload)) {
            return false !== mb_stripos((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $query);
        }

        return false;
    }

    private function resolveContentType(string $type): ?string
    {
        $type = trim($type);
        if ('' === $type) {
            return null;
        }

        $contents = $this->getContentConfig();
        if (isset($contents[$type])) {
            return $type;
        }

        if (str_ends_with($type, 's')) {
            $singular = substr($type, 0, -1);
            if (isset($contents[$singular])) {
                return $singular;
            }
        }

        return null;
    }

    private function getContentClass(string $type): ?string
    {
        $config = $this->getContentConfig();
        $class = $config[$type]['entity_class'] ?? null;

        return is_string($class) && class_exists($class) ? $class : null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getContentConfig(): array
    {
        $contents = $this->appContainer()->getParameter('sfs_cms.contents');

        return is_array($contents) ? $contents : [];
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = $this->appContainer()->get('doctrine.orm.default_entity_manager');
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('The doctrine.orm.default_entity_manager service is not available.');
        }

        return $entityManager;
    }

    private function appContainer(): ContainerInterface
    {
        return $this->kernelProvider->getContainer();
    }
}
