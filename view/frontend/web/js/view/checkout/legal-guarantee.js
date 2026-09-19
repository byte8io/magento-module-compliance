/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Checkout (Firecheckout) box for the EU legal-guarantee notice, rendered in the
 * order-summary "before place order" region. All gating + localisation is
 * resolved server-side in LegalGuaranteeConfigProvider and read from
 * window.checkoutConfig here; the box opens the shared #eu-guarantee-modal.
 */
define([
    'uiComponent',
    'Byte8_Compliance/js/legal-guarantee/modal-opener'
], function (Component, modalOpener) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Byte8_Compliance/checkout/legal-guarantee',
            show: false,
            iconUrl: '',
            pageUrl: '',
            titleText: '',
            textLine: '',
            moreLabel: ''
        },

        /** @inheritdoc */
        initialize: function () {
            this._super();

            var config = (window.checkoutConfig || {}).byte8LegalGuarantee || {};

            this.show = !!config.show;
            this.iconUrl = config.iconUrl || '';
            this.pageUrl = config.pageUrl || '';
            this.titleText = config.title || '';
            this.textLine = config.text || '';
            this.moreLabel = config.moreLabel || '';

            return this;
        },

        /**
         * @return {Boolean} false — keep the anchor href as a no-JS fallback only
         */
        openModal: function () {
            modalOpener.open(this.titleText);

            return false;
        }
    });
});
