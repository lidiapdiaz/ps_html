<?php
/*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2017 PrestaShop SA
*  @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/


function upgrade_module_2_0_0($module)
{
    // Añadimos las nuevas configuraciones con valor vacío para evitar errores
    Configuration::updateValue('CUSTOMMODALFOOTER_CART_RULES', '');
    Configuration::updateValue('CUSTOMMODALFOOTER_PRODUCTS', '');
    Configuration::updateValue('CUSTOMMODALFOOTER_BRANDS', '');
    Configuration::updateValue('CUSTOMMODALFOOTER_CATEGORIES', '');

    // Actualizamos la versión del módulo
    return true;
}
