<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Model\Checkout;

use Byte8\Compliance\ViewModel\LegalGuarantee\Notice;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Exposes the EU legal-guarantee teaser data to the checkout JS layer
 * (window.checkoutConfig) for the Firecheckout Knockout box rendered in the
 * order-summary "before place order" region.
 *
 * All gating is resolved server-side (EU store-view + master switch + the
 * show_in_checkout toggle) and handed to the component as a single `show` flag,
 * so the Knockout template stays dumb. Localised strings are translated here too
 * (via __()) so the box shows the right language without relying on
 * js-translation.json collection.
 */
class LegalGuaranteeConfigProvider implements ConfigProviderInterface
{
    private const XML_PATH_SHOW_CHECKOUT = 'byte8_compliance/legal_guarantee/show_in_checkout';

    public function __construct(
        private readonly Notice $notice,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function getConfig(): array
    {
        $show = $this->notice->isEnabled()
            && $this->scopeConfig->isSetFlag(self::XML_PATH_SHOW_CHECKOUT, ScopeInterface::SCOPE_STORE);

        if (!$show) {
            return ['byte8LegalGuarantee' => ['show' => false]];
        }

        return [
            'byte8LegalGuarantee' => [
                'show' => true,
                'iconUrl' => $this->notice->getIconUrl(),
                'pageUrl' => $this->notice->getPageUrl(),
                'title' => (string) __('Statutory warranty'),
                'text' => (string) __('At least two years statutory warranty.'),
                'moreLabel' => (string) __('Learn more'),
            ],
        ];
    }
}
