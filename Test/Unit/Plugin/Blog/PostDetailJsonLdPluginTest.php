<?php

declare(strict_types=1);

namespace MageOS\BlogSeo\Test\Unit\Plugin\Blog;

use MageOS\Blog\ViewModel\Post\Detail;
use MageOS\BlogSeo\Plugin\Blog\PostDetailJsonLdPlugin;
use MageOS\Seo\Api\StorefrontSettingsInterface;
use PHPUnit\Framework\TestCase;

/**
 * Review M11, decision D6: while MageOS_Seo writes structured data, it writes the post's BlogPosting,
 * and MageOS_Blog's own is left out; one BlogPosting, with MageOS_Seo's Organization as publisher.
 * With MageOS_Seo's structured data off, MageOS_Blog's comes back, so the post is never left without
 * one.
 */
class PostDetailJsonLdPluginTest extends TestCase
{
    private const BLOG_JSON_LD = '{"@context":"https://schema.org","@type":"BlogPosting","headline":"Bread"}';

    /**
     * @return void
     */
    public function testBlogsJsonLdIsLeftOutWhileSeoWritesStructuredData(): void
    {
        $this->assertSame(
            '',
            $this->plugin(true)->afterGetJsonLd($this->createStub(Detail::class), self::BLOG_JSON_LD)
        );
    }

    /**
     * @return void
     */
    public function testBlogsJsonLdStaysWhileSeosStructuredDataIsOff(): void
    {
        $this->assertSame(
            self::BLOG_JSON_LD,
            $this->plugin(false)->afterGetJsonLd($this->createStub(Detail::class), self::BLOG_JSON_LD)
        );
    }

    /**
     * The plugin over MageOS_Seo's structured-data switch.
     *
     * @param bool $seoStructuredData
     * @return PostDetailJsonLdPlugin
     */
    private function plugin(bool $seoStructuredData): PostDetailJsonLdPlugin
    {
        $settings = $this->createStub(StorefrontSettingsInterface::class);
        $settings->method('isStructuredDataEnabled')->willReturn($seoStructuredData);

        return new PostDetailJsonLdPlugin($settings);
    }
}
