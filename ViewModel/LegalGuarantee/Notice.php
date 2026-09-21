<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\ViewModel\LegalGuarantee;

use Byte8\Compliance\Model\LegalGuarantee\LabelCatalog;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Resolves the EU legal-guarantee notice (Gesetzliche Gewährleistung, EmpCo —
 * mandatory 27 Sep 2026) per store view.
 *
 * Two independent gates, both driven by admin config so the module is portable
 * across clients regardless of their store-view codes:
 *  - Label language: byte8_compliance/legal_guarantee/label_language (per store
 *    view). Its value both gates rendering (empty => the store view is not
 *    covered, e.g. non-EU Switzerland) and picks the official label language.
 *    This replaces the previous hard-coded store-view-CODE map, which silently
 *    rendered nothing on any store whose code was not de/it/nl.
 *  - Admin master switch: byte8_compliance/legal_guarantee/enabled. Per-surface
 *    toggles (pdp/cart/checkout) are applied in layout via ifconfig; this class
 *    enforces the master switch and the language gate.
 *
 * The label graphic is the official Commission SVG shipped verbatim; its
 * elements are non-editable (Durchführungsverordnung (EU) 2025/1960, Anhang I).
 */
class Notice implements ArgumentInterface
{
    public const PAGE_IDENTIFIER = 'legal-guarantee';

    private const XML_PATH_ENABLED = 'byte8_compliance/legal_guarantee/enabled';

    private const XML_PATH_LABEL_LANGUAGE = 'byte8_compliance/legal_guarantee/label_language';

    /**
     * Language-neutral teaser icon (EU-flag stars) — shared across every store
     * view. Only the full-size modal label is language-specific.
     */
    private const ICON_ASSET = 'Byte8_Compliance::images/eu-guarantee-icon.svg';

    /**
     * Label language used only as a last-resort asset fallback (the notice never
     * renders without a configured language, so this is defensive).
     */
    private const FALLBACK_LANGUAGE = 'de';

    public function __construct(
        private readonly AssetRepository $assetRepo,
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LabelCatalog $catalog
    ) {
    }

    /**
     * Configured label language for the current store view, or null if the notice
     * does not apply here (no language selected / unshipped language).
     */
    public function getLanguageKey(): ?string
    {
        $key = (string) $this->scopeConfig->getValue(
            self::XML_PATH_LABEL_LANGUAGE,
            ScopeInterface::SCOPE_STORE
        );

        return $this->catalog->has($key) ? $key : null;
    }

    /**
     * True when the notice may render at all on this store view: the admin master
     * switch is on AND a shipped label language is configured. Per-surface
     * visibility is handled by the layout ifconfig toggles.
     */
    public function isEnabled(): bool
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return false;
        }

        return $this->getLanguageKey() !== null;
    }

    /**
     * URL of the small language-neutral teaser icon (EU-flag stars).
     */
    public function getIconUrl(): string
    {
        return $this->assetRepo->getUrl(self::ICON_ASSET);
    }

    /**
     * URL of the official colour SVG for the current store view's configured language.
     */
    public function getLabelUrl(): string
    {
        $key = $this->getLanguageKey() ?? self::FALLBACK_LANGUAGE;

        return $this->assetRepo->getUrl(
            'Byte8_Compliance::images/' . $this->catalog->getSvgFilename($key)
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
