/**
 * Created by Pham Quang Hau on 02/08/2016.
 */
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'cardsave',
                component: 'Meetanshi_Cardsave/js/view/payment/method-renderer/cardsave-direct'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
