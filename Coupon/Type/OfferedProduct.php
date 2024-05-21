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

namespace CouponPack\Coupon\Type;

use CouponPack\CouponPack;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Event\Cart\CartEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Coupon\Type\AbstractRemove;
use Thelia\Model\CartItem;
use Thelia\Model\ProductQuery;

class OfferedProduct extends AbstractRemove
{
    public const OFFERED_PRODUCT_ID  = 'offered_product_id';
    public const OFFERED_CATEGORY_ID = 'offered_category_id';

    /** @var string ServiceId  */
    protected $serviceId = CouponPack::OFFERED_PRODUCT_SERVICE_ID;

    protected int $offeredProductId;
    protected int $offeredCategoryId;

    protected function getSessionVarName(): string
    {
        return "coupon.offered_product.cart_items." . $this->getCode();
    }

    public function getName(): string
    {
        return $this->facade
            ->getTranslator()
            ->trans('Offer a product', array(), CouponPack::DOMAIN_NAME);
    }

    public function getToolTip(): string
    {
        return '';
    }

    public function getCartItemDiscount(CartItem $cartItem): int
    {
        return 0;
    }

    public function setFieldsValue($effects): void
    {
        $this->offeredProductId = $effects[self::OFFERED_PRODUCT_ID];
        $this->offeredCategoryId = $effects[self::OFFERED_CATEGORY_ID];
    }

    public function drawBackOfficeInputs(): string
    {
        return $this->drawBaseBackOfficeInputs("coupon/type-fragments/offered-product.html", [
            'offered_category_field_name' => $this->makeCouponFieldName(self::OFFERED_CATEGORY_ID),
            'offered_category_value'      => $this->offeredCategoryId,

            'offered_product_field_name'  => $this->makeCouponFieldName(self::OFFERED_PRODUCT_ID),
            'offered_product_value'       => $this->offeredProductId
        ]);
    }

    /**
     * @throws PropelException
     */
    public function exec(): float|int
    {
        $discount = 0;

        $isInCartOfferedProduct = false;

        $cartItems = $this->facade->getCart()->getCartItems();

        /** @var CartItem $cartItem */
        foreach ($cartItems as $cartItem) {
            if ($cartItem->getProduct()->getId() === $this->offeredProductId) {
                $isInCartOfferedProduct = true; // at this point, the offeredProduct is already in the $cart

                if (! $cartItem->getPromo() || $this->isAvailableOnSpecialOffers()) {
                    $discount += $cartItem->getRealTaxedPrice($this->facade->getDeliveryCountry());
                    break;
                }
            }
        }

        // Create the product if it's not in the cart yet
        if (!$isInCartOfferedProduct && null !== $freeProduct = ProductQuery::create()->findPk($this->offeredProductId)) {

            $cartEvent = new CartEvent($this->facade->getCart());

            $cartEvent->setNewness(true);
            $cartEvent->setAppend(false);
            $cartEvent->setQuantity(1);
            $cartEvent->setProductSaleElementsId($freeProduct->getDefaultSaleElements()->getId());
            $cartEvent->setProduct($this->offeredProductId);

            $this->facade->getDispatcher()->dispatch($cartEvent, TheliaEvents::CART_ADDITEM);

            $freeProductCartItem = $cartEvent->getCartItem();

            $discount += $freeProductCartItem->getRealTaxedPrice($this->facade->getDeliveryCountry());
        }

        return $discount;
    }

    protected function checkCouponFieldValue($fieldName, $fieldValue): string
    {
        $fieldValue = $this->checkBaseCouponFieldValue($fieldName, $fieldValue);

        if ($fieldName === self::OFFERED_PRODUCT_ID) {
            if ((float)$fieldValue < 0) {
                throw new \InvalidArgumentException(
                    Translator::getInstance()->trans(
                        'Please select the offered product',
                        array(),
                        CouponPack::DOMAIN_NAME
                    )
                );
            }
        } elseif ($fieldName === self::OFFERED_CATEGORY_ID) {
            if (empty($fieldValue)) {
                throw new \InvalidArgumentException(
                    Translator::getInstance()->trans(
                        'Please select the category of the offered product',
                        array(),
                        CouponPack::DOMAIN_NAME
                    )
                );
            }
        }

        return $fieldValue;
    }

    protected function getFieldList(): array
    {
        return  $this->getBaseFieldList([self::OFFERED_CATEGORY_ID, self::OFFERED_PRODUCT_ID]);
    }
}