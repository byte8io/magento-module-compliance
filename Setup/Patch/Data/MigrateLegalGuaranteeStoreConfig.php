<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Setup\Patch\Data;

use Byte8\Compliance\Model\LegalGuarantee\LabelCatalog;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Auto-configures the per-store-view label language
 * (byte8_compliance/legal_guarantee/label_language) so EU store views get the
 * statutory-warranty notice without any manual admin step after install.
 *
 * For each store view that has no language configured yet, it derives the
 * language from the store's LOCALE (general/locale/code: `de_DE` -> `de`) and
 * writes it — but only when BOTH hold:
 *   - the store's default country (general/country/default) is in the EU, and
 *   - we actually ship that official label (LabelCatalog::has()).
 *
 * The EU-country gate is deliberate. A non-EU store view can share an EU locale
 * — a Swiss (CH) store on `de_DE` is the canonical case — and the EU
 * statutory-warranty label must NOT appear there. That is exactly why keying on
 * locale ALONE is wrong; it is also why this no longer keys on the arbitrary
 * store-view CODE (the earlier, fragile approach, which never matched a store
 * coded `default` even though it was a perfectly ordinary German EU store).
 *
 * Idempotent and non-destructive: it never overwrites a language an admin has
 * already set. Runs before AddLegalGuaranteeContent (which declares this patch
 * as a dependency) so the standalone-page patch sees the configured language in
 * the same setup:upgrade and creates the page in one pass.
 */
class MigrateLegalGuaranteeStoreConfig implements DataPatchInterface
{
    private const XML_PATH_LABEL_LANGUAGE = 'byte8_compliance/legal_guarantee/label_language';

    private const XML_PATH_LOCALE = 'general/locale/code';

    private const XML_PATH_COUNTRY = 'general/country/default';

    /**
     * EU-27 member states (ISO 3166-1 alpha-2). Used only as the EU gate on top
     * of the locale, to keep the label off non-EU store views that share an EU
     * language (e.g. Switzerland/CH, Liechtenstein/LI, Norway/NO on German).
     */
    private const EU_COUNTRIES = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR',
        'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK',
        'SI', 'ES', 'SE',
    ];

    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly WriterInterface $configWriter,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LabelCatalog $catalog
    ) {
    }

    public function apply(): self
    {
        /** @var StoreInterface $store */
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            // Never override a language an admin has already chosen.
            $current = (string) $this->scopeConfig->getValue(
                self::XML_PATH_LABEL_LANGUAGE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($current !== '') {
                continue;
            }

            // EU gate: only auto-configure store views whose default country is
            // in the EU, so a non-EU store view sharing an EU locale (Swiss
            // de_DE) is left untouched.
            $country = strtoupper((string) $this->scopeConfig->getValue(
                self::XML_PATH_COUNTRY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            ));
            if (!in_array($country, self::EU_COUNTRIES, true)) {
                continue;
            }

            // Language from the store view's locale (de_DE -> de); only when we
            // ship that official label.
            $locale = (string) $this->scopeConfig->getValue(
                self::XML_PATH_LOCALE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $language = strtolower(substr($locale, 0, 2));
            if (!$this->catalog->has($language)) {
                continue;
            }

            $this->configWriter->save(
                self::XML_PATH_LABEL_LANGUAGE,
                $language,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
