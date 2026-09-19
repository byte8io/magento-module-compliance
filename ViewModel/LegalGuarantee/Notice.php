<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\ViewModel\LegalGuarantee;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolves the EU legal-guarantee notice (Gesetzliche Gewährleistung, EmpCo —
 * mandatory 27 Sep 2026) per store view.
 *
 * Two independent gates:
 *  - EU coverage: by store-view CODE (not locale) — the Swiss (ch) store shares
 *    the de_DE locale with Germany but is NOT in the EU, so a locale gate would
 *    wrongly show the notice there. The store code both gates rendering and picks
 *    the official label language.
 *  - Admin master switch: byte8_compliance/legal_guarantee/enabled. Per-surface
 *    toggles (pdp/cart/checkout) are applied in layout via ifconfig; this class
 *    enforces the master switch and the EU gate. (The footer reminder now lives
 *    in the theme footer CMS block, not this module.)
 *
 * The label graphic is the official Commission SVG shipped verbatim; its
 * elements are non-editable (Durchführungsverordnung (EU) 2025/1960, Anhang I).
 */
class Notice implements ArgumentInterface
{
    /**
     * EU store-view CODE => language key of the official label + copy. Must match
     * the store views the data patch creates the CMS page for
     * (Setup\Patch\Data\AddLegalGuaranteeContent). Add a store view here (and ship
     * its official SVG + i18n + CMS page) to extend coverage.
     */
    public const SUPPORTED = [
        'de' => 'de',
        'it' => 'it',
        'nl' => 'nl',
    ];

    public const PAGE_IDENTIFIER = 'legal-guarantee';

    private const XML_PATH_ENABLED = 'byte8_compliance/legal_guarantee/enabled';

    /**
     * Language-neutral teaser icon (EU-flag stars) — shared across every store
     * view. Only the full-size modal label is language-specific.
     */
    private const ICON_ASSET = 'Byte8_Compliance::images/eu-guarantee-icon.svg';

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly AssetRepository $assetRepo,
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Language key for the current store view, or null if the notice does not
     * apply here (non-EU / unsupported store view).
     */
    public function getLanguageKey(): ?string
    {
        try {
            $code = (string) $this->storeManager->getStore()->getCode();
        } catch (NoSuchEntityException $e) {
            return null;
        }

        return self::SUPPORTED[$code] ?? null;
    }

    /**
     * True when the notice may render at all on this store view: it is an EU
     * store AND the admin master switch is on. Per-surface visibility is handled
     * by the layout ifconfig toggles.
     */
    public function isEnabled(): bool
    {
        if ($this->getLanguageKey() === null) {
            return false;
        }

        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * URL of the small language-neutral teaser icon (EU-flag stars).
     */
    public function getIconUrl(): string
    {
        return $this->assetRepo->getUrl(self::ICON_ASSET);
    }

    /**
     * URL of the official colour SVG for the current store view's language.
     */
    public function getLabelUrl(): string
    {
        $key = $this->getLanguageKey() ?? 'de';

        return $this->assetRepo->getUrl(
            'Byte8_Compliance::images/legal-guarantee-' . $key . '.svg'
        );
    }

    /**
     * URL of the standalone information page (resolves per store view).
     */
    public function getPageUrl(): string
    {
        return $this->urlBuilder->getUrl(self::PAGE_IDENTIFIER);
    }
}
