/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * data-mage-init component for a server-rendered "Learn more" link that opens
 * the single shared #eu-guarantee-modal (rendered once by modal.phtml) through
 * the shared opener singleton. Used where a server-side teaser must share the
 * one modal with a Knockout box on the same page — the checkout mobile box,
 * whose desktop counterpart (js/view/checkout/legal-guarantee) opens the very
 * same modal, so both go through one modal-widget init guard and there is no
 * duplicate-id collision.
 *
 * The link's href still points at the standalone page, so it works if this
 * script never runs (graceful degradation).
 */
define([
    'jquery',
    'Byte8_Compliance/js/legal-guarantee/modal-opener'
], function ($, modalOpener) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function (e) {
            e.preventDefault();
            modalOpener.open(config.title);
        });
    };
});
