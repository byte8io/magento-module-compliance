<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Setup\Patch\Data;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Creates the standalone EU legal-guarantee information page (EmpCo, mandatory
 * 27 Sep 2026) for each EU store view in its own language, so consumers can view
 * the notice without going through checkout.
 *
 * One CMS page per store view (identifier "legal-guarantee"), each carrying the
 * official Commission SVG for that language (shipped verbatim in the module).
 * Store views are resolved by code at runtime, so this is portable across
 * environments and skips gracefully where a store view is absent. Idempotent.
 *
 * Rename migration: this feature previously lived in Byte8_LegalGuarantee. Where
 * an existing "legal-guarantee" page still references that module's assets
 * ({{view url='Byte8_LegalGuarantee::...'}}), the content is rewritten to the new
 * Byte8_Compliance module. Manual edits to other parts of the page are preserved.
 *
 * NOTE (copy sign-off): the intro sentences below are Byte8 drafts and should be
 * confirmed by the client's Rechtsanwalt. The label graphic itself is the
 * official, legally-fixed artwork and must not be edited (Reg. (EU) 2025/1960,
 * Anhang I).
 */
class AddLegalGuaranteeContent implements DataPatchInterface
{
    private const IDENTIFIER = 'legal-guarantee';

    private const OLD_MODULE = 'Byte8_LegalGuarantee::';
    private const NEW_MODULE = 'Byte8_Compliance::';

    /** EU store-view code => localized page content (matches ViewModel\LegalGuarantee\Notice::SUPPORTED). */
    private const PAGES = [
        'de' => [
            'lang' => 'de',
            'title' => 'Gesetzliche Gewährleistung',
            'intro' => 'Als Verkäufer informieren wir Sie mit dem harmonisierten EU-Gewährleistungslabel über Ihre gesetzlichen Rechte. Für Verbrauchsgüter gilt eine gesetzliche Gewährleistung von mindestens zwei Jahren.',
        ],
        'it' => [
            'lang' => 'it',
            'title' => 'Garanzia legale di conformità',
            'intro' => 'In qualità di venditore, vi informiamo dei vostri diritti tramite l\'etichetta UE armonizzata sulla garanzia legale. I beni di consumo beneficiano di una garanzia legale di conformità di almeno due anni.',
        ],
        'nl' => [
            'lang' => 'nl',
            'title' => 'Wettelijke garantie',
            'intro' => 'Als verkoper informeren wij u met het geharmoniseerde EU-garantielabel over uw wettelijke rechten. Voor consumptiegoederen geldt een wettelijke garantie van ten minste twee jaar.',
        ],
    ];

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly PageRepositoryInterface $pageRepository,
        private readonly PageInterfaceFactory $pageFactory,
        private readonly PageCollectionFactory $pageCollectionFactory,
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        foreach (self::PAGES as $storeCode => $data) {
            $this->createPageForStore($storeCode, $data);
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    private function createPageForStore(string $storeCode, array $data): void
    {
        try {
            $storeId = (int) $this->storeRepository->get($storeCode)->getId();
        } catch (NoSuchEntityException $e) {
            $this->logger->info(sprintf(
                '[Byte8_Compliance] store view "%s" not found; skipping legal-guarantee page.',
                $storeCode
            ));
            return;
        }

        $existing = $this->pageCollectionFactory->create()
            ->addFieldToFilter(PageInterface::IDENTIFIER, self::IDENTIFIER)
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

        $svg = 'legal-guarantee-' . $data['lang'] . '.svg';
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
        $page->setIdentifier(self::IDENTIFIER)
            ->setTitle($data['title'])
            ->setContentHeading($data['title'])
            ->setPageLayout('1column')
            ->setContent($content)
            ->setIsActive(true)
            ->setStores([$storeId]);

        $this->pageRepository->save($page);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
