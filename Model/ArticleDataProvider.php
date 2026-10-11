<?php

declare(strict_types=1);

namespace MageOS\BlogSeo\Model;

use MageOS\Blog\ViewModel\Post\Detail;
use MageOS\Seo\Api\ArticleDataProviderInterface;

/**
 * A MageOS_Blog post as MageOS_Seo's article data, from which MageOS_Seo writes the post's
 * BlogPosting, with its own Organization as author and publisher.
 *
 * The values are the ones MageOS_Blog resolves for the post's Open Graph tags: its Open Graph
 * override, else its meta value, else the title or short content; its Open Graph image, else its
 * featured image. So the structured data and the sharing tags say the same, as MageOS_Blog's own
 * BlogPosting did. Plugin\Blog\PostDetailJsonLdPlugin leaves that one out meanwhile.
 */
class ArticleDataProvider implements ArticleDataProviderInterface
{
    /**
     * The optional article fields, each from the Open Graph tag holding it.
     */
    private const OPTIONAL = [
        'description'  => 'og:description',
        'dateModified' => 'article:modified_time',
        'image'        => 'og:image',
    ];

    /**
     * @param Detail $detail MageOS_Blog's post view model, which reads the post the page shows.
     */
    public function __construct(
        private readonly Detail $detail
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getHandles(): array
    {
        return ['blog_post_view'];
    }

    /**
     * @inheritdoc
     *
     * The required fields always, datePublished empty when the post has no publish date (as
     * MageOS_Seo writes it); the optional ones only when the post has them.
     */
    public function getArticle(int $storeId): ?array
    {
        $post = $this->detail->getPost();
        if ($post === null) {
            return null;
        }

        $tags    = $this->detail->getOgTags();
        $article = [
            'headline'      => $tags['og:title'] ?? '',
            'url'           => $this->detail->getCanonicalUrl(),
            'datePublished' => $tags['article:published_time'] ?? '',
        ];

        foreach (self::OPTIONAL as $field => $tag) {
            if (($tags[$tag] ?? '') !== '') {
                $article[$field] = $tags[$tag];
            }
        }

        $keywords = array_values(array_filter(
            array_map('trim', explode(',', (string) $post->getMetaKeywords())),
            static fn (string $keyword): bool => $keyword !== ''
        ));
        if ($keywords !== []) {
            $article['keywords'] = $keywords;
        }

        return $article;
    }
}
