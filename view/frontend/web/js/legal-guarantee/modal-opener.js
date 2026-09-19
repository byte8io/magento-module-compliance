/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Shared opener for the EU legal-guarantee modal on the checkout page. The full
 * label lives in a single server-rendered #eu-guarantee-modal (modal.phtml); the
 * Knockout teaser box opens it through this helper, so the Magento modal widget
 * is initialised only once (guarded).
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    var initialized = false;

    return {
        /**
         * Open the shared modal, initialising it on first use.
         *
         * @param {String} [title]
         */
        open: function (title) {
            var $content = $('#eu-guarantee-modal');

            if (!$content.length) {
                return;
            }

            if (!initialized) {
                modal({
                    type: 'popup',
                    modalClass: 'eu-guarantee-modal',
                    responsive: true,
                    innerScroll: true,
                    title: title || $t('Statutory warranty'),
                    buttons: []
                }, $content);
                initialized = true;
            }

            $content.modal('openModal');
        }
    };
});
