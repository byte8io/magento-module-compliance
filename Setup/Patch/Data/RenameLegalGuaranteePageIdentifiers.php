<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Setup\Patch\Data;

use Byte8\Compliance\Model\LegalGuarantee\LabelCatalog;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Renames the standalone legal-guarantee page from the old shared identifier
 * ("legal-guarantee") to the per-language localised URL key
 * (LabelCatalog::getPageIdentifier(), e.g. gesetzliche-gewaehrleistung) on
 * installs created before per-language identifiers existed.
 *
 * Notice::getPageUrl() now links to the localised key, so without this an
 * already-created "legal-guarantee" page would 404 from the teaser/footer link.
 * The old English slug was poor SEO on an EU-language store anyway, so the
 * rename is a net improvement; the pages are new (EmpCo, Sep 2026), so no 301 is
 * warranted.
 *
 * Idempotent and safe: per store view, it renames only when the store view has a
 * configured language, no page already uses the new identifier, and an
 * old-identifier page exists for it. Fresh installs (no old page) are untouched —
 * AddLegalGuaranteeContent creates the page directly at the localised key.
 */
class RenameLegalGuaranteePageIdentifiers implements DataPatchInterface
{
    private const OLD_IDENTIFIER = 'legal-guarantee';

    private const XML_PATH_LABEL_LANGUAGE = 'byte8_compliance/legal_guarantee/label_language';

    public function __construct(
        private readonly PageRepositoryInterface $pageRepository,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LabelCatalog $catalog,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        /** @var StoreInterface $store */
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            $language = (string) $this->scopeConfig->getValue(
                self::XML_PATH_LABEL_LANGUAGE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $newIdentifier = $this->catalog->getPageIdentifier($language);
            if ($newIdentifier === null || $newIdentifier === self::OLD_IDENTIFIER) {
                continue;
            }

            // Already migrated for this store view — nothing to do.
            $withNew = $this->pageCollectionFactory->create()
                ->addFieldToFilter(PageInterface::IDENTIFIER, $newIdentifier)
                ->addStoreFilter($storeId, false);
            if ($withNew->getSize() > 0) {
                continue;
            }

            // Old-identifier page assigned to this store view — rename it.
            $withOld = $this->pageCollectionFactory->create()
                ->addFieldToFilter(PageInterface::IDENTIFIER, self::OLD_IDENTIFIER)
                ->addStoreFilter($storeId, false);
            if ($withOld->getSize() === 0) {
                continue;
            }

            /** @var PageInterface $page */
            $page = $withOld->getFirstItem();
            $page->setIdentifier($newIdentifier);
            $this->pageRepository->save($page);

            $this->logger->info(sprintf(
                '[Byte8_Compliance] renamed legal-guarantee page to "%s" for store view id %d.',
                $newIdentifier,
                $storeId
            ));
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
