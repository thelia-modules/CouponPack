SET FOREIGN_KEY_CHECKS = 0;

UPDATE `coupon` SET `type` = "CouponPack\\Coupon\\Type\\OfferedProduct"
WHERE `type` = 'coupon.type.offered_product';

SET FOREIGN_KEY_CHECKS = 1;