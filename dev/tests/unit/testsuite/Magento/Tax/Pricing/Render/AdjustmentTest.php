<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Tax\Pricing\Render;

class AdjustmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Context mock
     *
     * @var \Magento\Framework\View\Element\Template\Context
     */
    protected $contextMock;

    /**
     * Price currency model mock
     *
     * @var \Magento\Directory\Model\PriceCurrency
     */
    protected $priceCurrencyMock;

    /**
     * Price helper mock
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $taxHelperMock;

    /**
     * @var \Magento\Tax\Pricing\Render\Adjustment
     */
    protected $model;

    /**
     * Init mocks and model
     */
    public function setUp()
    {
        $this->contextMock = $this->getMock(
            'Magento\Framework\View\Element\Template\Context',
            ['getEventManager', 'getStoreConfig', 'getScopeConfig'],
            [],
            '',
            false
        );
        $this->priceCurrencyMock = $this->getMock('Magento\Directory\Model\PriceCurrency', [], [], '', false);
        $this->taxHelperMock = $this->getMock(
            'Magento\Tax\Helper\Data',
            [],
            [],
            '',
            false
        );

        $eventManagerMock = $this->getMockBuilder('Magento\Framework\Event\ManagerInterface')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $storeConfigMock = $this->getMockBuilder('Magento\Store\Model\Store\Config')
            ->disableOriginalConstructor()
            ->getMock();
        $scopeConfigMock = $this->getMockForAbstractClass('Magento\Framework\App\Config\ScopeConfigInterface');

        $this->contextMock->expects($this->any())
            ->method('getEventManager')
            ->will($this->returnValue($eventManagerMock));
        $this->contextMock->expects($this->any())
            ->method('getStoreConfig')
            ->will($this->returnValue($storeConfigMock));
        $this->contextMock->expects($this->any())
            ->method('getScopeConfig')
            ->will($this->returnValue($scopeConfigMock));

        $this->model = new Adjustment(
            $this->contextMock,
            $this->priceCurrencyMock,
            $this->taxHelperMock
        );
    }

    /**
     * Test for method getAdjustmentCode
     */
    public function testGetAdjustmentCode()
    {
        $this->assertEquals(\Magento\Tax\Pricing\Adjustment::CODE, $this->model->getAdjustmentCode());
    }

    /**
     * Test for method displayBothPrices
     */
    public function testDisplayBothPrices()
    {
        $shouldDisplayBothPrices = true;
        $this->taxHelperMock->expects($this->once())
            ->method('displayBothPrices')
            ->will($this->returnValue($shouldDisplayBothPrices));
        $this->assertEquals($shouldDisplayBothPrices, $this->model->displayBothPrices());
    }

    /**
     * Test for method getDisplayAmountExclTax
     */
    public function testGetDisplayAmountExclTax()
    {
        $expectedPriceValue = 1.23;
        $expectedPrice = '$4.56';

        /** @var \Magento\Framework\Pricing\Render\Amount $amountRender */
        $amountRender = $this->getMockBuilder('Magento\Framework\Pricing\Render\Amount')
            ->disableOriginalConstructor()
            ->setMethods(['getAmount'])
            ->getMock();

        /** @var \Magento\Framework\Pricing\Amount\Base $baseAmount */
        $baseAmount = $this->getMockBuilder('Magento\Framework\Pricing\Amount\Base')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $baseAmount->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($expectedPriceValue));

        $amountRender->expects($this->any())
            ->method('getAmount')
            ->will($this->returnValue($baseAmount));

        $this->priceCurrencyMock->expects($this->any())
            ->method('convertAndFormat')
            ->will($this->returnValue($expectedPrice));

        $this->model->render($amountRender);
        $result = $this->model->getDisplayAmountExclTax();

        $this->assertEquals($expectedPrice, $result);
    }

    /**
     * Test for method getDisplayAmount
     *
     * @param bool $includeContainer
     * @dataProvider getDisplayAmountDataProvider
     */
    public function testGetDisplayAmount($includeContainer)
    {
        $expectedPriceValue = 1.23;
        $expectedPrice = '$4.56';

        /** @var \Magento\Framework\Pricing\Render\Amount $amountRender */
        $amountRender = $this->getMockBuilder('Magento\Framework\Pricing\Render\Amount')
            ->disableOriginalConstructor()
            ->setMethods(['getAmount'])
            ->getMock();
        /** @var \Magento\Framework\Pricing\Amount\Base $baseAmount */
        $baseAmount = $this->getMockBuilder('Magento\Framework\Pricing\Amount\Base')
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $baseAmount->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue($expectedPriceValue));

        $amountRender->expects($this->any())
            ->method('getAmount')
            ->will($this->returnValue($baseAmount));

        $this->priceCurrencyMock->expects($this->any())
            ->method('convertAndFormat')
            ->with($this->anything(), $this->equalTo($includeContainer))
            ->will($this->returnValue($expectedPrice));

        $this->model->render($amountRender);
        $result = $this->model->getDisplayAmount($includeContainer);

        $this->assertEquals($expectedPrice, $result);
    }

    /**
     * Data provider for testGetDisplayAmount
     *
     * @return array
     */
    public function getDisplayAmountDataProvider()
    {
        return [[true], [false]];
    }

    /**
     * Test for method buildIdWithPrefix
     *
     * @param string $prefix
     * @param null|false|int $saleableId
     * @param null|false|string $suffix
     * @param string $expectedResult
     * @dataProvider buildIdWithPrefixDataProvider
     */
    public function testBuildIdWithPrefix($prefix, $saleableId, $suffix, $expectedResult)
    {
        /** @var \Magento\Framework\Pricing\Render\Amount $amountRender */
        $amountRender = $this->getMockBuilder('Magento\Framework\Pricing\Render\Amount')
            ->disableOriginalConstructor()
            ->setMethods(['getSaleableItem'])
            ->getMock();

        /** @var \Magento\Catalog\Model\Product $saleable */
        $saleable = $this->getMockBuilder('Magento\Catalog\Model\Product')
            ->disableOriginalConstructor()
            ->setMethods(['getId', '__wakeup'])
            ->getMock();

        $amountRender->expects($this->any())
            ->method('getSaleableItem')
            ->will($this->returnValue($saleable));
        $saleable->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($saleableId));

        $this->model->setIdSuffix($suffix);
        $this->model->render($amountRender);
        $result = $this->model->buildIdWithPrefix($prefix);

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * data provider for testBuildIdWithPrefix
     *
     * @return array
     */
    public function buildIdWithPrefixDataProvider()
    {
        return [
            ['some_prefix_', null, '_suffix', 'some_prefix__suffix'],
            ['some_prefix_', false, '_suffix', 'some_prefix__suffix'],
            ['some_prefix_', 123, '_suffix', 'some_prefix_123_suffix'],
            ['some_prefix_', 123, null, 'some_prefix_123'],
            ['some_prefix_', 123, false, 'some_prefix_123'],
        ];
    }

    /**
     * test for method displayPriceIncludingTax
     */
    public function testDisplayPriceIncludingTax()
    {
        $expectedResult = true;

        $this->taxHelperMock->expects($this->once())
            ->method('displayPriceIncludingTax')
            ->will($this->returnValue($expectedResult));

        $result = $this->model->displayPriceIncludingTax();

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * test for method displayPriceExcludingTax
     */
    public function testDisplayPriceExcludingTax()
    {
        $expectedResult = true;

        $this->taxHelperMock->expects($this->once())
            ->method('displayPriceExcludingTax')
            ->will($this->returnValue($expectedResult));

        $result = $this->model->displayPriceExcludingTax();

        $this->assertEquals($expectedResult, $result);
    }
}
