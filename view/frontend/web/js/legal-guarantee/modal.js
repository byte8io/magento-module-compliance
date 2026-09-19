/**
 * Copyright © Byte8 Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 *
 * Opens the EU legal-guarantee notice in a modal when the "Learn more" trigger
 * is clicked. The trigger is a real link to the standalone page, so if this
 * script never runs the link still works (graceful degradation).
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($, modal, $t) {
    'use strict';

    return function (config, element) {
        var $trigger = $(element),
            $content = $('#eu-guarantee-modal-content'),
            initialized = false;

        if (!$content.length) {
            return;
        }

        $trigger.on('click', function (e) {
            e.preventDefault();

            if (!initialized) {
                modal({
                    type: 'popup',
                    modalClass: 'eu-guarantee-modal',
                    responsive: true,
                    innerScroll: true,
                    title: config.title || $t('Statutory warranty'),
                    buttons: []
                }, $content);
                initialized = true;
            }

            $content.modal('openModal');
        });
    };
});
