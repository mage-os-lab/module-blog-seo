<?php

declare(strict_types=1);

namespace MageOS\BlogSeo\Test\Integration\Controller;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;
use MageOS\Blog\Api\Data\PostInterfaceFactory;
use MageOS\Blog\Api\PostRepositoryInterface;
use MageOS\Blog\Model\BlogPostStatus;
use MageOS\Blog\Model\Config as BlogConfig;
use MageOS\Seo\Api\OrganizationRepositoryInterface;

/**
 * Review M11, decision D6: a blog post page describes the post once, as MageOS_Seo's BlogPosting,
 * whose publisher is MageOS_Seo's Organization; no second, inline Organization from MageOS_Blog.
 * With MageOS_Seo's structured data off, MageOS_Blog's own BlogPosting comes back. With no
 * Organization published, the BlogPosting names no publisher rather than one the page doesn't have
 * (decision (a), 2026-10-11).
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class BlogPostStructuredDataTest extends AbstractController
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->_objectManager->get(MutableScopeConfigInterface::class)
            ->setValue(BlogConfig::XML_PATH_ENABLED, '1', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return void
     */
    public function testThePostIsOneBlogPostingPublishedBySeosOrganization(): void
    {
        $this->publishOrganization('Bridge Test Store', 'http://localhost/');

        $nodes = $this->jsonLdNodes($this->postPage('blog-seo-bridge-test'));

        $postings = array_values(array_filter($nodes, static fn (array $node): bool
            => ($node['@type'] ?? null) === 'BlogPosting'));
        $this->assertCount(1, $postings, 'The post is described once.');
        $this->assertSame('Bridge Test', $postings[0]['headline'] ?? null);
        $this->assertSame(
            ['@id'],
            array_keys((array) ($postings[0]['publisher'] ?? [])),
            'The publisher is a reference.'
        );

        $organizations = array_filter($nodes, static fn (array $node): bool
            => \in_array($node['@type'] ?? null, ['Organization', 'OnlineStore', 'LocalBusiness', 'Store'], true));
        $this->assertCount(1, $organizations, 'One Organization: MageOS_Seo\'s.');
        $this->assertSame($postings[0]['publisher']['@id'], reset($organizations)['@id'] ?? null);
    }

    /**
     * @magentoConfigFixture current_store mageos_seo_general/structured_data/enabled 0
     * @return void
     */
    public function testWithSeosStructuredDataOffBlogsOwnBlogPostingComesBack(): void
    {
        $nodes = $this->jsonLdNodes($this->postPage('blog-seo-bridge-off'));

        $postings = array_filter($nodes, static fn (array $node): bool => ($node['@type'] ?? null) === 'BlogPosting');
        $this->assertCount(1, $postings, 'MageOS_Blog\'s BlogPosting is there.');
    }

    /**
     * No Organization is published (none is configured): the BlogPosting stays MageOS_Seo's, and
     * names no author or publisher, rather than an Organization the page doesn't have.
     *
     * @return void
     */
    public function testWithNoOrganizationPublishedThePostingNamesNoPublisher(): void
    {
        $body  = $this->postPage('blog-seo-bridge-no-org');
        $nodes = $this->jsonLdNodes($body);

        $postings = array_values(array_filter($nodes, static fn (array $node): bool
            => ($node['@type'] ?? null) === 'BlogPosting'));
        $this->assertCount(1, $postings, 'The post is described once.');
        $this->assertArrayNotHasKey('publisher', $postings[0]);
        $this->assertArrayNotHasKey('author', $postings[0]);
        $this->assertStringNotContainsString('#organization', $body, 'Nothing points at an Organization.');
    }

    /**
     * Publish an Organization for every store view: the default scope's record, with a name.
     *
     * @param string $name
     * @param string $url
     * @return void
     */
    private function publishOrganization(string $name, string $url): void
    {
        $repository   = $this->_objectManager->get(OrganizationRepositoryInterface::class);
        $organization = $repository->get();
        $organization->setName($name);
        $organization->setUrl($url);
        $repository->save($organization);
    }

    /**
     * Publish a post in the default store view and render its page.
     *
     * @param string $urlKey
     * @return string
     */
    private function postPage(string $urlKey): string
    {
        $objectManager = Bootstrap::getObjectManager();
        $post = $objectManager->get(PostInterfaceFactory::class)->create();
        $post->setTitle('Bridge Test')
            ->setUrlKey($urlKey)
            ->setShortContent('A post the bridge describes.')
            ->setPublishDate('2026-10-01 09:00:00')
            ->setStatus(BlogPostStatus::Published->value)
            ->setStoreIds([1]);
        $objectManager->get(PostRepositoryInterface::class)->save($post);

        $this->dispatch('/blog/' . $urlKey);

        return (string) $this->getResponse()->getBody();
    }

    /**
     * Every JSON-LD node on the page: each script's object, or each item of its array or @graph.
     *
     * @param string $body
     * @return array<int, array<string, mixed>>
     */
    private function jsonLdNodes(string $body): array
    {
        preg_match_all('#<script type="application/ld\+json"[^>]*>\s*(.*?)\s*</script>#s', $body, $matches);

        $nodes = [];
        foreach ($matches[1] as $json) {
            $decoded = json_decode($json, true);
            if (!\is_array($decoded)) {
                continue;
            }
            $items = $decoded['@graph'] ?? (array_is_list($decoded) ? $decoded : [$decoded]);
            foreach ($items as $item) {
                if (\is_array($item)) {
                    $nodes[] = $item;
                }
            }
        }

        return $nodes;
    }
}
