<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Bundle\CartBundle\Infrastructure\Validator\Rule;

use Shopware\Bundle\CartBundle\Domain\Cart\CalculatedCart;
use Shopware\Bundle\CartBundle\Domain\Validator\Data\RuleDataCollection;
use Shopware\Bundle\CartBundle\Domain\Validator\Exception\UnsupportedOperatorException;
use Shopware\Bundle\CartBundle\Domain\Validator\Rule\Rule;
use Shopware\Bundle\StoreFrontBundle\Context\ShopContextInterface;

class OrderAmountRule extends Rule
{
    /**
     * @var float
     */
    protected $amount;

    /**
     * @var string
     */
    protected $operator;

    public function __construct(float $amount, string $operator)
    {
        $this->amount = $amount;
        $this->operator = $operator;
    }

    public function match(
        CalculatedCart $calculatedCart,
        ShopContextInterface $context,
        RuleDataCollection $collection
    ): bool {
        switch ($this->operator) {
            case self::OPERATOR_GTE:

                return $calculatedCart->getPrice()->getTotalPrice() >= $this->amount;

            case self::OPERATOR_LTE:

                return $calculatedCart->getPrice()->getTotalPrice() <= $this->amount;

            default:
                throw new UnsupportedOperatorException($this->operator, __CLASS__);
        }
    }
}
