<?php

declare(strict_types=1);

namespace MageOS\BlogSeo\Test\Unit\Model;

use MageOS\Blog\Api\Data\PostInterface;
use MageOS\Blog\ViewModel\Post\Detail;
use MageOS\BlogSeo\Model\ArticleDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Review M11, decision D6: a blog post as MageOS_Seo's article data, so MageOS_Seo writes the post's
 * BlogPosting, with its own Organization as publisher, instead of MageOS_Blog writing a second
 * Organization inline.
 *
 * The values are the ones MageOS_Blog resolves for the post's Open Graph tags (its Open Graph
 * override, else its meta value, else the title or short content), so the structured data and the
 * sharing tags say the same.
 */
class ArticleDataProviderTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsAnArticleProviderForPostPages(): void
    {
        $provider = new ArticleDataProvider($this->createStub(Detail::class));

        $this->assertSame(['blog_post_view'], $provider->getHandles());
    }

    /**
     * @return void
     */
    public function testAPostIsMappedFromWhatItsPageShows(): void
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getMetaKeywords')->willReturn('baking, bread ,  sourdough');
        $detail = $this->detail($post, [
            'og:title'               => 'How to bake bread',
            'og:description'         => 'A beginner\'s guide.',
            'og:image'               => 'https://example.com/media/mageos_blog/bread.jpg',
            'article:published_time' => '2026-10-01T09:00:00+00:00',
            'article:modified_time'  => '2026-10-02T10:00:00+00:00',
        ]);

        $this->assertSame(
            [
                'headline'      => 'How to bake bread',
                'url'           => 'https://example.com/blog/how-to-bake-bread',
                'datePublished' => '2026-10-01T09:00:00+00:00',
                'description'   => 'A beginner\'s guide.',
                'dateModified'  => '2026-10-02T10:00:00+00:00',
                'image'         => 'https://example.com/media/mageos_blog/bread.jpg',
                'keywords'      => ['baking', 'bread', 'sourdough'],
            ],
            (new ArticleDataProvider($detail))->getArticle(1)
        );
    }

    /**
     * What the post does not have is left out, not written empty.
     *
     * @return void
     */
    public function testOptionalValuesThePostDoesNotHaveAreLeftOut(): void
    {
        $post = $this->createStub(PostInterface::class);
        $post->method('getMetaKeywords')->willReturn(null);
        $detail = $this->detail($post, ['og:title' => 'Untitled draft']);

        $this->assertSame(
            [
                'headline'      => 'Untitled draft',
                'url'           => 'https://example.com/blog/how-to-bake-bread',
                'datePublished' => '',
            ],
            (new ArticleDataProvider($detail))->getArticle(1)
        );
    }

    /**
     * @return void
     */
    public function testOffAPostPageThereIsNoArticle(): void
    {
        $detail = $this->createStub(Detail::class);
        $detail->method('getPost')->willReturn(null);

        $this->assertNull((new ArticleDataProvider($detail))->getArticle(1));
    }

    /**
     * MageOS_Blog's post view model showing a post, with the Open Graph tags it resolves for it.
     *
     * @param PostInterface $post
     * @param array<string, string> $ogTags
     * @return Detail
     */
    private function detail(PostInterface $post, array $ogTags): Detail
    {
        $detail = $this->createStub(Detail::class);
        $detail->method('getPost')->willReturn($post);
        $detail->method('getCanonicalUrl')->willReturn('https://example.com/blog/how-to-bake-bread');
        $detail->method('getOgTags')->willReturn($ogTags);

        return $detail;
    }
}
