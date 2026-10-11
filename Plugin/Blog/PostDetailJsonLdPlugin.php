<?php

declare(strict_types=1);

namespace MageOS\BlogSeo\Plugin\Blog;

use MageOS\Blog\ViewModel\Post\Detail;
use MageOS\Seo\Api\StorefrontSettingsInterface;

/**
 * Leaves MageOS_Blog's own BlogPosting out while MageOS_Seo writes the post's.
 *
 * MageOS_Blog's names the store inline as a second Organization, beside MageOS_Seo's. With
 * MageOS_Seo's structured data off, MageOS_Seo writes none, so MageOS_Blog's is kept and the post
 * is never left without one.
 */
class PostDetailJsonLdPlugin
{
    /**
     * @param StorefrontSettingsInterface $settings
     */
    public function __construct(
        private readonly StorefrontSettingsInterface $settings
    ) {
    }

    /**
     * MageOS_Blog's JSON-LD, or nothing while MageOS_Seo writes structured data.
     *
     * @param Detail $subject
     * @param string $result
     * @return string
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function afterGetJsonLd(Detail $subject, string $result): string
    {
        return $this->settings->isStructuredDataEnabled() ? '' : $result;
    }
}
