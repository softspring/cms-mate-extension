<?php

declare(strict_types=1);

namespace Softspring\CmsMateExtension\Capability;

use Mcp\Capability\Attribute\McpTool;
use Softspring\CmsMateExtension\Service\CmsContentReader;
use Symfony\AI\Mate\Encoding\ResponseEncoder;

final class CmsContentTool
{
    public function __construct(
        private readonly CmsContentReader $reader,
    ) {
    }

    /**
     * @param bool $includeRawConfig Include full CMS content type configuration.
     */
    #[McpTool(name: 'cms-content-types', title: 'CMS Content Types', description: 'List configured Softspring CMS content types and their main fields.')]
    public function contentTypes(bool $includeRawConfig = false): string
    {
        return ResponseEncoder::encode($this->reader->contentTypes($includeRawConfig));
    }

    /**
     * @param string|null $type          Optional CMS content type id. Plural aliases such as pages are resolved when possible.
     * @param string      $query         Text matched against content name, routes, sites and version payloads.
     * @param string|null $site          Optional CMS site id.
     * @param string|null $locale        Optional locale filter.
     * @param bool        $publishedOnly Only return contents with a published version.
     * @param int         $limit         Maximum number of contents to return. Capped at 100.
     * @param bool        $includeData   Include version data payloads in the response.
     */
    #[McpTool(name: 'cms-content-search', title: 'CMS Content Search', description: 'Search Softspring CMS contents by type, text, site and locale.')]
    public function search(?string $type = null, string $query = '', ?string $site = null, ?string $locale = null, bool $publishedOnly = false, int $limit = 20, bool $includeData = false): string
    {
        return ResponseEncoder::encode($this->reader->search($type, $query, $site, $locale, $publishedOnly, $limit, $includeData));
    }

    /**
     * @param string      $type        CMS content type id. Plural aliases such as pages are resolved when possible.
     * @param string      $id          CMS content id.
     * @param string|null $locale      Optional locale filter for routes.
     * @param bool        $includeData Include version data payloads in the response.
     */
    #[McpTool(name: 'cms-content-get', title: 'CMS Content Get', description: 'Get one Softspring CMS content item by type and id.')]
    public function get(string $type, string $id, ?string $locale = null, bool $includeData = false): string
    {
        return ResponseEncoder::encode([
            'content' => $this->reader->get($type, $id, $locale, $includeData),
        ]);
    }
}
