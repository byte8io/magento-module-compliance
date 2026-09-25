<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Setup\Patch\Data;

use Byte8\Compliance\Model\LegalGuarantee\LabelCatalog;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates the standalone EU legal-guarantee information page (EmpCo, mandatory
 * 27 Sep 2026) for each covered store view in its own language, so consumers can
 * view the notice without going through checkout.
 *
 * One CMS page per store view, each at its language's localised URL key
 * (LabelCatalog::getPageIdentifier(), e.g. gesetzliche-gewaehrleistung) and
 * carrying the official Commission SVG plus meta title/description for that
 * language. The same identifier is what Notice::getPageUrl() links to, so page
 * and link stay in lockstep. Coverage is driven by the per-store-view admin
 * setting
 * byte8_compliance/legal_guarantee/label_language (see LabelCatalog) — NOT by
 * store-view code — so it is portable across clients. A store view with no
 * language set is skipped. Idempotent.
 *
 * Ordering: runs after MigrateLegalGuaranteeStoreConfig, so on installs upgrading
 * from the old store-code behaviour the migrated config is already in place.
 *
 * Timing note: this is a one-shot data patch. It creates pages for store views
 * whose language is configured when it runs. If you set label_language for a
 * store view later (e.g. a fresh install configured in admin after setup), create
 * that store view's page manually, or re-trigger this patch (bump the module
 * version / remove its patch_list row) — the storefront modal still shows the
 * full label regardless, this page is the no-JS/SEO fallback.
 *
 * Rename migration: this feature previously lived in Byte8_LegalGuarantee. Where
 * an existing "legal-guarantee" page still references that module's assets
 * ({{view url='Byte8_LegalGuarantee::...'}}), the content is rewritten to the new
 * Byte8_Compliance module. Manual edits to other parts of the page are preserved.
 *
 * NOTE (copy sign-off): the intro sentences (in LabelCatalog) are Byte8 drafts
 * and should be confirmed by the client's Rechtsanwalt. The label graphic itself
 * is the official, legally-fixed artwork and must not be edited (Reg. (EU)
 * 2025/1960, Anhang I).
 */
class AddLegalGuaranteeContent implements DataPatchInterface
{
    private const XML_PATH_LABEL_LANGUAGE = 'byte8_compliance/legal_guarantee/label_language';

    private const OLD_MODULE = 'Byte8_LegalGuarantee::';
    private const NEW_MODULE = 'Byte8_Compliance::';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly PageRepositoryInterface $pageRepository,
        private readonly PageInterfaceFactory $pageFactory,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LabelCatalog $catalog,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        /** @var StoreInterface $store */
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $language = (string) $this->scopeConfig->getValue(
                self::XML_PATH_LABEL_LANGUAGE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $data = $this->catalog->get($language);
            if ($data === null) {
                // Store view not covered (no / unshipped language configured).
                continue;
            }

            $this->createPageForStore($storeId, $language, $data);
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * @param array{name: string, identifier: string, title: string, meta_title: string, meta_description: string, intro: string} $data
     */
    private function createPageForStore(int $storeId, string $language, array $data): void
    {
        $identifier = $data['identifier'];

        $existing = $this->pageCollectionFactory->create()
            ->addFieldToFilter(PageInterface::IDENTIFIER, $identifier)
            ->addStoreFilter($storeId, false);

        if ($existing->getSize() > 0) {
            // Rename migration: rewrite stale Byte8_LegalGuarantee asset refs.
            /** @var PageInterface $page */
            $page = $existing->getFirstItem();
            $content = (string) $page->getContent();
            if (strpos($content, self::OLD_MODULE) !== false) {
                $page->setContent(str_replace(self::OLD_MODULE, self::NEW_MODULE, $content));
                $this->pageRepository->save($page);
            }
            return;
        }

        $svg = $this->catalog->getSvgFilename($language);
        $title = $this->escape($data['title']);
        $intro = $this->escape($data['intro']);
        $content = <<<HTML
<div class="eu-guarantee-page">
    <p class="eu-guarantee-page__intro">{$intro}</p>
    <div class="eu-guarantee-page__label">
        <img src="{{view url='Byte8_Compliance::images/{$svg}'}}" alt="{$title}" width="595" height="842" />
    </div>
</div>
HTML;

        /** @var PageInterface $page */
        $page = $this->pageFactory->create();
        $page->setIdentifier($identifier)
            ->setTitle($data['title'])
            ->setContentHeading($data['title'])
            ->setMetaTitle($data['meta_title'])
            ->setMetaDescription($data['meta_description'])
            ->setPageLayout('1column')
            ->setContent($content)
            ->setIsActive(true)
            ->setStores([$storeId]);

        $this->pageRepository->save($page);

        $this->logger->info(sprintf(
            '[Byte8_Compliance] created legal-guarantee page (%s) for store view id %d.',
            $language,
            $storeId
        ));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function getDependencies(): array
    {
        return [
            MigrateLegalGuaranteeStoreConfig::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
