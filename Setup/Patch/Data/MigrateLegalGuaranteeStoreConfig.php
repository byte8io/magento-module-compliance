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
 * Backward-compatibility migration for the move from a hard-coded store-view-CODE
 * language map (ViewModel\LegalGuarantee\Notice::SUPPORTED, removed) to the
 * per-store-view admin setting byte8_compliance/legal_guarantee/label_language.
 *
 * The old code showed the label wherever a store view's CODE was a shipped
 * language (de/it/nl). To preserve that behaviour with zero manual steps on
 * existing installs, this one-off patch writes label_language for every store
 * view whose code matches a shipped language and does not already have the
 * setting configured. Installs without such store-view codes (the common case
 * for new clients) are unaffected — they configure the language in admin.
 *
 * Runs before AddLegalGuaranteeContent so the standalone-page patch sees the
 * migrated config in the same setup:upgrade.
 */
class MigrateLegalGuaranteeStoreConfig implements DataPatchInterface
{
    private const XML_PATH_LABEL_LANGUAGE = 'byte8_compliance/legal_guarantee/label_language';

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
            $code = (string) $store->getCode();
            if (!$this->catalog->has($code)) {
                continue;
            }

            $storeId = (int) $store->getId();
            $current = (string) $this->scopeConfig->getValue(
                self::XML_PATH_LABEL_LANGUAGE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if ($current !== '') {
                // Already configured by an admin — do not overwrite.
                continue;
            }

            $this->configWriter->save(
                self::XML_PATH_LABEL_LANGUAGE,
                $code,
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
