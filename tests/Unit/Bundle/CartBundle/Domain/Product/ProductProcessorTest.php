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

namespace Shopware\Tests\Unit\Bundle\CartBundle\Domain\Product;

use Shopware\Bundle\CartBundle\Domain\Cart\CartContainer;
use Shopware\Bundle\CartBundle\Domain\Cart\ProcessorCart;
use Shopware\Bundle\CartBundle\Domain\Delivery\DeliveryCollection;
use Shopware\Bundle\CartBundle\Domain\Delivery\DeliveryDate;
use Shopware\Bundle\CartBundle\Domain\Delivery\DeliveryInformation;
use Shopware\Bundle\CartBundle\Domain\Delivery\DeliveryInformationCollection;
use Shopware\Bundle\CartBundle\Domain\Error\ErrorCollection;
use Shopware\Bundle\CartBundle\Domain\Error\ProductDeliveryInformationNotFoundError;
use Shopware\Bundle\CartBundle\Domain\Error\ProductPriceNotFoundError;
use Shopware\Bundle\CartBundle\Domain\LineItem\CalculatedLineItemCollection;
use Shopware\Bundle\CartBundle\Domain\LineItem\LineItem;
use Shopware\Bundle\CartBundle\Domain\LineItem\LineItemCollection;
use Shopware\Bundle\CartBundle\Domain\Price\Price;
use Shopware\Bundle\CartBundle\Domain\Price\PriceCalculator;
use Shopware\Bundle\CartBundle\Domain\Price\PriceDefinition;
use Shopware\Bundle\CartBundle\Domain\Price\PriceDefinitionCollection;
use Shopware\Bundle\CartBundle\Domain\Product\CalculatedProduct;
use Shopware\Bundle\CartBundle\Domain\Product\ProductCalculator;
use Shopware\Bundle\CartBundle\Domain\Product\ProductProcessor;
use Shopware\Bundle\CartBundle\Domain\Tax\CalculatedTaxCollection;
use Shopware\Bundle\CartBundle\Domain\Tax\TaxRuleCollection;
use Shopware\Bundle\CartBundle\Infrastructure\Product\ProductDeliveryGateway;
use Shopware\Bundle\CartBundle\Infrastructure\Product\ProductPriceGateway;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContext;
use Shopware\Tests\Unit\Bundle\CartBundle\Common\Generator;

class ProductProcessorTest extends \PHPUnit\Framework\TestCase
{
    const DUMMY_TAX_NAME = 'test-tax';

    public function testConvertingAnEmptyCart()
    {
        $priceGateway = $this->createMock(ProductPriceGateway::class);
        $priceGateway
            ->expects(static::never())
            ->method('get');

        $calculator = $this->createMock(PriceCalculator::class);
        $calculator
            ->expects(static::never())
            ->method('calculate');

        $deliveryGateway = $this->createMock(ProductDeliveryGateway::class);
        $deliveryGateway
            ->expects(static::never())
            ->method('get');

        $productCalculator = new ProductCalculator($priceGateway, $calculator, $deliveryGateway);
        $processor = new ProductProcessor($productCalculator);

        $processorCart = new ProcessorCart(
            new CalculatedLineItemCollection(),
            new DeliveryCollection()
        );

        $processor->process(
            CartContainer::createExisting('test', 'test', []),
            $processorCart,
            Generator::createContext()
        );

        static::assertCount(0, $processorCart->getCalculatedLineItems());
    }

    public function testConvertOneProduct()
    {
        $priceGateway = $this->createMock(ProductPriceGateway::class);
        $priceGateway
            ->expects(static::once())
            ->method('get')
            ->will(
                static::returnValue(new PriceDefinitionCollection([
                    'SW1' => new PriceDefinition(0, new TaxRuleCollection()),
                ]))
            );

        $calculator = $this->createMock(PriceCalculator::class);
        $calculator
            ->expects(static::once())
            ->method('calculate')
            ->will(static::returnValue(
                new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 0)
            ));

        $deliveryGateway = $this->createMock(ProductDeliveryGateway::class);
        $deliveryGateway
            ->expects(static::once())
            ->method('get')
            ->will(
                static::returnValue(new DeliveryInformationCollection([
                    'SW1' => new DefaultDeliveryInformation(),
                ]))
            );

        $productCalculator = new ProductCalculator($priceGateway, $calculator, $deliveryGateway);
        $processor = new ProductProcessor($productCalculator);

        $cart = new ProcessorCart(
            new CalculatedLineItemCollection(),
            new DeliveryCollection()
        );

        $processor->process(
            CartContainer::createExisting('test', 'test', [
                new LineItem('SW1', ProductProcessor::TYPE_PRODUCT, 1),
            ]),
            $cart,
            Generator::createContext()
        );

        static::assertCount(1, $cart->getCalculatedLineItems());

        static::assertEquals(
            new LineItem('SW1', ProductProcessor::TYPE_PRODUCT, 1),
            $cart->getCalculatedLineItems()->get('SW1')->getLineItem()
        );
    }

    public function testConvertMultipleProducts()
    {
        $priceGateway = $this->createMock(ProductPriceGateway::class);
        $priceGateway
            ->expects(static::any())
            ->method('get')
            ->will(
                static::returnValue(new PriceDefinitionCollection([
                    'SW1' => new PriceDefinition(0, new TaxRuleCollection()),
                    'SW2' => new PriceDefinition(0, new TaxRuleCollection()),
                    'SW3' => new PriceDefinition(0, new TaxRuleCollection()),
                ]))
            );

        $calculator = $this->createMock(PriceCalculator::class);
        $calculator
            ->expects(static::any())
            ->method('calculate')
            ->will(static::returnValue(
                new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 0)
            ));

        $deliveryGateway = $this->createMock(ProductDeliveryGateway::class);
        $deliveryGateway
            ->expects(static::once())
            ->method('get')
            ->will(
                static::returnValue(new DeliveryInformationCollection([
                    'SW1' => new DefaultDeliveryInformation(),
                    'SW2' => new DefaultDeliveryInformation(),
                    'SW3' => new DefaultDeliveryInformation(),
                ]))
            );

        $productCalculator = new ProductCalculator($priceGateway, $calculator, $deliveryGateway);
        $processor = new ProductProcessor($productCalculator);

        $cart = new ProcessorCart(
            new CalculatedLineItemCollection(),
            new DeliveryCollection()
        );

        $processor->process(
            CartContainer::createExisting('test', 'test', [
                new LineItem('SW1', ProductProcessor::TYPE_PRODUCT, 1),
                new LineItem('SW2', ProductProcessor::TYPE_PRODUCT, 1),
                new LineItem('SW3', ProductProcessor::TYPE_PRODUCT, 1),
            ]),
            $cart,
            Generator::createContext()
        );

        static::assertEquals(
            new CalculatedLineItemCollection([
                new CalculatedProduct(
                    'SW1',
                    1,
                    new LineItem('SW1', ProductProcessor::TYPE_PRODUCT, 1),
                    new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 0),
                    new DefaultDeliveryInformation()
                ),
                new CalculatedProduct(
                    'SW2',
                    1,
                    new LineItem('SW2', ProductProcessor::TYPE_PRODUCT, 1),
                    new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 0),
                    new DefaultDeliveryInformation()
                ),
                new CalculatedProduct(
                    'SW3',
                    1,
                    new LineItem('SW3', ProductProcessor::TYPE_PRODUCT, 1),
                    new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 0),
                    new DefaultDeliveryInformation()
                ),
            ]),
            $cart->getCalculatedLineItems()
        );
    }

    public function testConvertProductWhenPricesAreMissing()
    {
        $priceGateway = $this->createMock(ProductPriceGateway::class);
        $priceGateway
            ->expects(static::once())
            ->method('get')
            ->will(
                static::returnValue(new PriceDefinitionCollection([
                    'SW1' => new PriceDefinition(0, new TaxRuleCollection()),
                ]))
            );

        $calculator = $this->createMock(PriceCalculator::class);
        $calculator
            ->expects(static::exactly(1))
            ->method('calculate')
            ->will(static::returnValue(
                new Price(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), 0)
            ));

        $deliveryGateway = $this->createMock(ProductDeliveryGateway::class);
        $deliveryGateway
            ->expects(static::once())
            ->method('get')
            ->will(
                static::returnValue(new DeliveryInformationCollection([
                    'SW1' => new DefaultDeliveryInformation(),
                ]))
            );

        $productCalculator = new ProductCalculator($priceGateway, $calculator, $deliveryGateway);
        $processor = new ProductProcessor($productCalculator);

        $processorCart = new ProcessorCart(
            new CalculatedLineItemCollection(),
            new DeliveryCollection()
        );

        $processor->process(
            CartContainer::createExisting('test', 'test', [
                new LineItem('SW1', ProductProcessor::TYPE_PRODUCT, 1),
                new LineItem('SW2', ProductProcessor::TYPE_PRODUCT, 1),
                new LineItem('SW3', ProductProcessor::TYPE_PRODUCT, 1),
            ]),
            $processorCart,
            Generator::createContext()
        );

        $this->assertEquals(
            $processorCart->getErrors(),
            new ErrorCollection([
                new ProductPriceNotFoundError('SW2'),
                new ProductPriceNotFoundError('SW3'),
            ])
        );
    }

    public function testWithMissingDeliveryInformation()
    {
        $priceGateway = $this->createMock(ProductPriceGateway::class);
        $priceGateway->method('get')
            ->will($this->returnValue(
                new PriceDefinitionCollection([
                    'SW1' => new PriceDefinition(0, new TaxRuleCollection()),
                ])
            ));

        $deliveryGateway = $this->createMock(ProductDeliveryGateway::class);
        $deliveryGateway->expects($this->once())
            ->method('get')->will($this->returnValue(new DeliveryInformationCollection([])));

        $priceCalculator = $this->createMock(PriceCalculator::class);
        $priceCalculator->method('calculate')->will($this->returnValue(
            new Price(1, 1, new CalculatedTaxCollection(), new TaxRuleCollection())
        ));

        $productCalculator = new ProductCalculator(
            $priceGateway,
            $priceCalculator,
            $deliveryGateway
        );

        $lineItemCollection = new LineItemCollection([
            new LineItem('SW1', ProductProcessor::TYPE_PRODUCT, 1),
        ]);

        $context = $this->createMock(ShopContext::class);

        $productCollection = $productCalculator->calculate($lineItemCollection, $context);

        $this->assertCount(1, $productCollection->getErrors());
        $this->assertEquals(
            [new ProductDeliveryInformationNotFoundError('SW1')],
            $productCollection->getErrors()
        );
    }
}

class DefaultDeliveryInformation extends DeliveryInformation
{
    public function __construct()
    {
        parent::__construct(
            0,
            0,
            0,
            0,
            0,
            new DeliveryDate(
                new \DateTime(),
                new \DateTime()
            ),
            new DeliveryDate(
                new \DateTime(),
                new \DateTime()
            )
        );
    }
}
