<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Model\Config\Source;

use Byte8\Compliance\Model\LegalGuarantee\LabelCatalog;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Admin source for byte8_compliance/legal_guarantee/label_language: the official
 * EU legal-guarantee label to show on the current store view.
 *
 * Options are built from the shipped label catalog plus a leading empty option
 * ("Not applicable") that hides the notice — so a non-EU store view (e.g.
 * Switzerland) is configured explicitly rather than inferred from its locale.
 */
class LabelLanguage implements OptionSourceInterface
{
    public function __construct(
        private readonly LabelCatalog $catalog
    ) {
    }

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase|string}>
     */
    public function toOptionArray(): array
    {
        $options = [
            ['value' => '', 'label' => __('— Not applicable (hide on this store view) —')],
        ];

        foreach ($this->catalog->getKeys() as $key) {
            $options[] = [
                'value' => $key,
                'label' => __($this->catalog->get($key)['name']),
            ];
        }

        return $options;
    }
}
