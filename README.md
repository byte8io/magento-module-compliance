# Byte8 Compliance for Magento 2

`byte8/module-compliance` — an umbrella module for EU consumer-compliance features in Magento 2 / Adobe Commerce.

**First feature: the EU legal-guarantee notice** (*Gesetzliche Gewährleistung / garanzia legale / wettelijke garantie*). The Empowering Consumers Directive (EmpCo, Directive (EU) 2024/825) makes a harmonised statutory-warranty label mandatory in the EU from **27 September 2026**; the label artwork is fixed by Commission Implementing Regulation (EU) 2025/1960 and ships in this module verbatim.

Future EU rules (GPSR, battery regulation, further EmpCo surfaces, …) will be added as additional features under the same module.

## Features

- **Teaser + modal** with the official EU legal-guarantee label:
  - **Product page** — appended to the product-info column (Luma-based themes)
  - **Cart** — top of the cart-summary sidebar, next to proceed-to-checkout
  - **Checkout** — Luma checkout via layout, plus a ready-made Knockout component for [Firecheckout](https://firecheckout.net) (before-place-order region) fed by a checkout config provider
- **Standalone information page** — an idempotent data patch creates a `legal-guarantee` CMS page per EU store view in its own language, so consumers can read the notice without entering checkout
- **Official label artwork** — the Commission SVGs for German, Italian and Dutch plus a language-neutral EU-stars teaser icon; the full-size label is shown in an accessible modal
- **Store-view gating** — the notice renders only on EU store views. Gating is by store-view *code*, not locale, deliberately: a Swiss store sharing `de_DE` with a German store must **not** show the label
- **Admin configuration** — master switch plus independent per-surface toggles (product page / cart / checkout), all per store view, under **Stores → Configuration → Byte8 → Compliance**
- **Translations** — `de_DE`, `it_IT`, `nl_NL` bundled

## Requirements

- Magento Open Source / Adobe Commerce **2.4.x**
- PHP **8.2 – 8.5**

## Installation

### Composer (recommended)

```sh
composer require byte8/module-compliance
bin/magento module:enable Byte8_Compliance
bin/magento setup:upgrade
bin/magento cache:flush
```

Until the package is listed on Packagist, add the GitHub repository first:

```sh
composer config repositories.byte8-compliance vcs https://github.com/byte8io/module-compliance
composer require byte8/module-compliance
```

### app/code

Copy (or `git clone`) the module to `app/code/Byte8/Compliance`, then:

```sh
bin/magento module:enable Byte8_Compliance
bin/magento setup:upgrade
bin/magento cache:flush
```

`setup:upgrade` runs the data patch that creates the per-store `legal-guarantee` CMS pages.

## Configuration

**Stores → Configuration → Byte8 → Compliance → Legal Guarantee**

| Setting | Default | Effect |
|---|---|---|
| Enable Legal Guarantee Notice | Yes | Master switch — hides the notice everywhere when off |
| Show on Product Page | Yes | Teaser below the product info |
| Show in Cart | Yes | Teaser in the cart summary |
| Show in Checkout | Yes | Teaser in checkout (Luma and Firecheckout) |

All settings are store-view scoped. The EU gate always applies on top: non-EU store views never show the notice regardless of these toggles.

## Mapping your store views

The module decides *which store views are EU* — and which label language each one gets — from the store-view **code** map in `ViewModel/LegalGuarantee/Notice.php`:

```php
public const SUPPORTED = [
    'de' => 'de',
    'it' => 'it',
    'nl' => 'nl',
];
```

Adjust the keys to your own store-view codes (e.g. `'default' => 'de'`). To add another EU language, add the map entry **and** ship the official Commission SVG for that language under `view/frontend/web/images/legal-guarantee-<lang>.svg`, the `i18n/<locale>.csv` translations, and extend the CMS-page data patch. Pull requests adding further official label languages are welcome.

## Theme notes

- **Luma-based themes** work out of the box for all three surfaces.
- **Themes that rebuild the product-page block tree** drop externally referenced blocks in `product.info.main`; declare the teaser block in your theme's `catalog_product_view.xml` instead (keep a distinct block name — see the comment in the module's `catalog_product_view.xml`).
- **Firecheckout** is supported via `firecheckout_index_index.xml` and a Knockout component; without Firecheckout the standard checkout layout applies. No hard dependency either way.
- **Hyvä** is not covered yet — the templates are Luma/RequireJS based.

## Legal note

The bundled label graphics are the official artwork established by Commission Implementing Regulation (EU) 2025/1960 (Annex I) and **must not be edited**. The introductory copy on the generated CMS pages is a draft — have it reviewed by your own legal counsel. This module helps you display the notice; it is not legal advice, and compliance responsibility remains with the merchant.

## License

[Open Software License 3.0 (OSL-3.0)](LICENSE.txt). The official EU label graphics are excluded — they are legally fixed artwork reproduced verbatim as the regulation requires.

## Support

Issues and pull requests are welcome on GitHub. Commercial support: [support@byte8.io](mailto:support@byte8.io) · [byte8.io](https://byte8.io)
