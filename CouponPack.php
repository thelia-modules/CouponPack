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

use CouponPack\Coupon\Type\OfferedProduct;
use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Thelia\Install\Database;
use Thelia\Model\CouponQuery;
use Thelia\Module\BaseModule;

class CouponPack extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'couponpack';
    public const OFFERED_PRODUCT_SERVICE_ID = OfferedProduct::class;

    public static function isCouponTypeOfferedProduct($couponCode): bool
    {
        $coupon = CouponQuery::create()->findOneByCode($couponCode);

        return $coupon !== null && $coupon->getType() === self::OFFERED_PRODUCT_SERVICE_ID;
    }

    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        $finder = (new Finder())
            ->files()
            ->name('#.*?\.sql#')
            ->sortByName()
            ->in(__DIR__ . DS . 'Config' . DS . 'update')
        ;

        $database = new Database($con);

        /** @var SplFileInfo $updateSQLFile */
        foreach ($finder as $updateSQLFile) {
            if (version_compare($currentVersion, str_replace('.sql', '', $updateSQLFile->getFilename()), '<')) {
                $database->insertSql(
                    null,
                    [
                        $updateSQLFile->getPathname()
                    ]
                );
            }
        }
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire()
            ->autoconfigure();
    }
}
