<?php
/*************************************************************************************/
/*      This file is part of the CouponPack package.                                 */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace CouponPack\EventListeners;

use CouponPack\CouponPack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Cart\CartEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\CartItemQuery;
use Thelia\Model\CouponQuery;

class RemoveCartItemListener implements EventSubscriberInterface
{
    /** @var  Request */
    protected Request $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            TheliaEvents::CART_DELETEITEM => array('removeCouponFromSession', 300)
        );
    }

    public function removeCouponFromSession(CartEvent $cartEvent): void
    {
        $cartItem = CartItemQuery::create()->findOneById($cartEvent->getCartItemId());

        if (null !== $cartItem) {
            $consumedCoupons = $this->request->getSession()->getConsumedCoupons();

            if (!isset($consumedCoupons) || !$consumedCoupons) {
                $consumedCoupons = array();
            }

            foreach ($consumedCoupons as $key => $value) {
                if (CouponPack::isCouponTypeOfferedProduct($value)) {
                    $coupon = CouponQuery::create()->findOneByCode($value);
                    $effects = $coupon?->unserializeEffects($coupon?->getSerializedEffects());

                    if ($effects['offered_product_id'] === $cartItem->getProductId()) {
                        unset($consumedCoupons[$key]);
                    }
                }
            }

            $this->request->getSession()->setConsumedCoupons($consumedCoupons);
        }
    }
}