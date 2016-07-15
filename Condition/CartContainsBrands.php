<?php
/**
 * Created by PhpStorm.
 * User: tompradat
 * Date: 12/07/2016
 * Time: 11:41
 */

namespace CouponPack\Condition;

use CouponPack\CouponPack;
use Thelia\Condition\Implementation\ConditionAbstract;
use Thelia\Condition\Operators;
use Thelia\Coupon\FacadeInterface;
use Thelia\Exception\InvalidConditionValueException;
use Thelia\Model\Brand;
use Thelia\Model\BrandQuery;
use Thelia\Model\CartItem;

class CartContainsBrands extends ConditionAbstract
{
    const BRAND_LIST = 'brands';

    public function __construct(FacadeInterface $facade)
    {
        $this->availableOperators = [
            self::BRAND_LIST => [
                Operators::IN,
                Operators::OUT
            ]
        ];

        parent::__construct($facade);
    }

    public function getServiceId()
    {
        return 'thelia.condition.cart_contains_brands';
    }

    public function setValidatorsFromForm(array $operators, array $values)
    {
        $this->checkComparisonOperatorValue($operators, self::BRAND_LIST);

        // Use default values if data is not defined.
        if (! isset($operators[self::BRAND_LIST]) || ! isset($values[self::BRAND_LIST])) {
            $operators[self::BRAND_LIST] = Operators::IN;
            $values[self::BRAND_LIST] = [];
        }

        // Be sure that the value is an array, make one if required
        if (! is_array($values[self::BRAND_LIST])) {
            $values[self::BRAND_LIST] = array($values[self::BRAND_LIST]);
        }

        // Check that at least one brand is selected
        if (empty($values[self::BRAND_LIST])) {
            throw new InvalidConditionValueException(
                get_class(),
                self::BRAND_LIST
            );
        }

        $this->operators = [ self::BRAND_LIST => $operators[self::BRAND_LIST] ];
        $this->values    = [ self::BRAND_LIST => $values[self::BRAND_LIST] ];

        return $this;
    }

    public function isMatching()
    {
        $cartItems = $this->facade->getCart()->getCartItems();

        if ($this->operators[self::BRAND_LIST] == Operators::IN) {
            foreach ($cartItems as $cartItem) {
                if ($this->conditionValidator->variableOpComparison(
                    $cartItem->getProduct()->getBrand()->getId(),
                    $this->operators[self::BRAND_LIST],
                    $this->values[self::BRAND_LIST]
                )) {
                    return true;
                }
            }

            return false;
        }

        if ($this->operators[self::BRAND_LIST] == Operators::OUT) {
            /** @var CartItem $cartItem */
            foreach ($cartItems as $cartItem) {
                if (!$this->conditionValidator->variableOpComparison(
                    $cartItem->getProduct()->getBrand()->getId(),
                    $this->operators[self::BRAND_LIST],
                    $this->values[self::BRAND_LIST]
                )) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public function getName()
    {
        return $this->translator->trans(
            'Cart contains brands condition',
            [],
            CouponPack::DOMAIN_NAME
        );
    }

    public function getToolTip()
    {
        $toolTip = $this->translator->trans(
            'The coupon applies if the cart contains at least one product of the selected brands',
            [],
            CouponPack::DOMAIN_NAME
        );

        return $toolTip;
    }

    protected function generateInputs()
    {
        return array(
            self::BRAND_LIST => array(
                'availableOperators' => $this->availableOperators[self::BRAND_LIST],
                'value' => '',
                'selectedOperator' => Operators::IN
            )
        );
    }

    public function getSummary()
    {
        $i18nOperator = Operators::getI18n(
            $this->translator,
            $this->operators[self::BRAND_LIST]
        );

        $brandStrList = '';

        $brandIds = $this->values[self::BRAND_LIST];

        if (null !== $brandList = BrandQuery::create()->findPks($brandIds)) {
            /** @var Brand $brand */
            foreach ($brandList as $brand) {
                $brandStrList .= $brand->setLocale($this->getCurrentLocale())->getTitle() . ', ';
            }

            $brandStrList = rtrim($brandStrList, ', ');
        }

        $toolTip = $this->translator->trans(
            'At least one of cart products brand is %op% <strong>%brand_list%</strong>',
            [
                '%brand_list%' => $brandStrList,
                '%op%' => $i18nOperator
            ],
            CouponPack::DOMAIN_NAME
        );

        return $toolTip;
    }

    public function drawBackOfficeInputs()
    {
        return $this->facade->getParser()->render(
            'coupon/condition-fragments/cart-contains-brands-condition.html',
            [
                'operatorSelectHtml' => $this->drawBackOfficeInputOperators(self::BRAND_LIST),
                'brand_field_name' => self::BRAND_LIST,
                'values' => isset($this->values[self::BRAND_LIST]) ? $this->values[self::BRAND_LIST] : array()
            ]
        );
    }
}