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

namespace CouponPack;

use Thelia\Model\CouponQuery;
use Thelia\Module\BaseModule;

class CouponPack extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'couponpack';
    const OFFERED_PRODUCT_SERVICE_ID = 'coupon.type.offered_product';

    public static function isCouponTypeOfferedProduct($couponCode)
    {
        $coupon = CouponQuery::create()->findOneByCode($couponCode);

        if ($coupon !== null && $coupon->getType() == self::OFFERED_PRODUCT_SERVICE_ID) {
            return true;
        }

        return false;
    }
}
