# MageOS_BlogSeo

Connects [MageOS_Blog](https://github.com/mage-os-lab/module-blog) to
[MageOS_Seo](https://github.com/mage-os-lab/module-seo), so a blog post page is described once, by
MageOS_Seo, in the same structured data as the rest of the store.

---

## What it does

With both modules installed and no bridge, a post page carries two descriptions of who publishes
it: MageOS_Blog's BlogPosting names the store inline as an Organization, and MageOS_Seo writes its
own Organization for every page. This module makes it one:

- **MageOS_Seo writes the post's BlogPosting.** `Model\ArticleDataProvider` gives it the post as
  article data. The BlogPosting's author and publisher reference MageOS_Seo's Organization by
  `@id`, and it carries Speakable when MageOS_Seo's Speakable setting is on.
- **Configure the Organization** (Marketing → SEO → Organization) for the BlogPosting to
  name its author and publisher. Until it has a name, MageOS_Seo publishes no Organization, and the
  BlogPosting names neither rather than reference one the page doesn't have.
- **MageOS_Blog's BlogPosting is left out meanwhile** (`Plugin\Blog\PostDetailJsonLdPlugin`).
- **Turn MageOS_Seo's structured data off** (Stores → Configuration → MageOS SEO → SEO →
  Structured Data (JSON-LD) → Enable JSON-LD Output) and MageOS_Blog's BlogPosting comes back, so
  a post is never without one.

The article data is what MageOS_Blog resolves for the post's Open Graph tags, so the structured
data and the sharing tags agree:

| BlogPosting | From the post |
| --- | --- |
| `headline` | Its Open Graph title, else its meta title, else its title |
| `url`, `@id` | Its canonical URL (`{url}#article`) |
| `datePublished` | Its publish date |
| `dateModified` | Its last update, when it has one |
| `description` | Its Open Graph description, else its meta description, else its short content |
| `image` | Its Open Graph image, else its featured image |
| `keywords` | Its meta keywords, split at commas |

The Open Graph and Twitter tags stay MageOS_Blog's: MageOS_Seo adds only `og:site_name` and
`og:locale` on a post page, which MageOS_Blog does not write.

---

## Requirements

- PHP 8.3 – 8.5
- Magento Open Source / Mage-OS **2.4.7 or newer** (`magento/framework ^103.0.7`)
- **MageOS_Blog** (`mage-os/module-blog` `^1.3`) and **MageOS_Seo** (`mage-os/module-seo` `^2.0`).
  Neither requires the other; install this module to connect them.

---

## Installation

```bash
composer require mage-os/module-blog-seo
bin/magento module:enable MageOS_BlogSeo
bin/magento setup:upgrade
bin/magento cache:flush
```

There are no settings.

---

## How it hooks in

| Where | What |
| --- | --- |
| `etc/di.xml` | `Model\ArticleDataProvider` in `MageOS\Seo\Model\StructuredData\Provider\ArticleSchemaProvider`'s `dataProviders`, for the `blog_post_view` handle |
| `etc/frontend/di.xml` | `Plugin\Blog\PostDetailJsonLdPlugin`, an after plugin on `MageOS\Blog\ViewModel\Post\Detail::getJsonLd()` |

To describe posts differently, replace `Model\ArticleDataProvider` with a preference.

---

## License

[OSL-3.0](LICENSE)
