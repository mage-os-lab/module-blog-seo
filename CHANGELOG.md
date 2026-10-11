# Changelog

All notable changes to this module are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow
[Semantic Versioning](https://semver.org/). Releases are cut from git tags —
the tag is the source of truth for the version (composer.json carries no
hardcoded version field).

## [Unreleased]

### Added

- **A blog post page is described once, by MageOS_Seo.** With MageOS_Blog and MageOS_Seo both
  installed, each wrote its own structured data for a post page: MageOS_Blog a BlogPosting whose
  publisher was the store as a second, inline Organization, beside MageOS_Seo's Organization.
  - `Model\ArticleDataProvider` gives MageOS_Seo the post as article data
    (`MageOS\Seo\Api\ArticleDataProviderInterface`), registered in MageOS_Seo's
    `ArticleSchemaProvider` pool. MageOS_Seo writes the BlogPosting, with its Organization as
    author and publisher by reference once one is configured, and Speakable when that is on.
  - The values are the ones MageOS_Blog resolves for the post's Open Graph tags, so the structured
    data and the sharing tags say the same: title, description, image, publish and update times,
    the canonical URL, and the post's meta keywords.
  - `Plugin\Blog\PostDetailJsonLdPlugin` leaves MageOS_Blog's BlogPosting out while MageOS_Seo's
    structured data is on. With it off, MageOS_Blog's comes back, so a post is never without one.
  - Neither module depends on the other; this one requires both.
