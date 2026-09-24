<?php
/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Byte8\Compliance\Plugin\Checkout\Block;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\App\RequestInterface;

/**
 * Injects the desktop EU legal-guarantee box into the native checkout sidebar's
 * order-summary region, so on desktop the reassurance sits directly below the
 * order summary (aside column) instead of only at the top of the page.
 *
 * Native checkout only: the guard on the full action name keeps this off the
 * Firecheckout page (firecheckout_index_index), which injects its own Knockout
 * box via layout XML and organises its sidebar differently — running both would
 * duplicate the box. The box is hidden on mobile/tablet by CSS (the native
 * sidebar is a slide panel there); the server-side mobile box at the top of the
 * checkout content covers those breakpoints. Both open the single shared
 * #eu-guarantee-modal.
 *
 * Gating (EU store view + master switch + show_in_checkout) is resolved
 * server-side in LegalGuaranteeConfigProvider and exposed as the component's
 * `show` flag, so an off state simply renders nothing — no need to gate here.
 */
class LayoutProcessorPlugin
{
    private const COMPONENT = 'Byte8_Compliance/js/view/checkout/legal-guarantee';

    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @param LayoutProcessor $subject
     * @param array $jsLayout
     * @return array
     */
    public function afterProcess(LayoutProcessor $subject, array $jsLayout): array
    {
        if ($this->request->getFullActionName() !== 'checkout_index_index') {
            // Firecheckout (and any other checkout) place the box via their own
            // layout — see firecheckout_index_index.xml.
            return $jsLayout;
        }

        if (isset($jsLayout['components']['checkout']['children']['sidebar']['children'])) {
            $jsLayout['components']['checkout']['children']['sidebar']['children']['byte8-legal-guarantee'] = [
                'component' => self::COMPONENT,
                'displayArea' => 'summary',
                'sortOrder' => 100,
            ];
        }

        return $jsLayout;
    }
}
