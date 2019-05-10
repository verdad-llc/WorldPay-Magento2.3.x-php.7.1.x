/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, additionalValidators) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Meetanshi_Cardsave/payment/cardsave',
                active: false
            },

            placeOrderHandler: null,
            validateHandler: null,

            getCode: function () {
                return 'cardsave';
            },

            isActive: function () {
                return true;
            },

            initObservable: function () {
                this._super()
                    .observe('active');

                return this;
            },

            context: function () {
                return this;
            },

            getCardsaveLogoUrl: function () {
                return window.checkoutConfig.cardsave_imageurl;
            },

            getCardsaveInstructions: function () {
                return window.checkoutConfig.cardsave_instructions;
            },

            isShowLegend: function () {
                return true;
            },

            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },

            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },

            placeOrder: function () {
                if (this.validateHandler() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    this._super();
                }
            }

        });
    }
);
