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

namespace Shopware\Tests\Functional\Bundle\AttributeBundle;

use Doctrine\DBAL\Connection;
use Shopware\Bundle\AttributeBundle\Service\DataLoader;
use Shopware\Bundle\AttributeBundle\Service\DataPersister;

class DataLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataLoader
     */
    private $attributeLoader;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DataPersister
     */
    private $attributePersister;

    protected function setUp()
    {
        $this->connection = Shopware()->Container()->get('dbal_connection');
        $this->connection->beginTransaction();

        $this->attributePersister = Shopware()->Container()->get('shopware_attribute.data_persister');
        $this->attributeLoader = Shopware()->Container()->get('shopware_attribute.data_loader');

        parent::setUp();
    }

    public function tearDown()
    {
        $this->connection->rollBack();

        parent::tearDown();
    }

    public function testLoadReturnArrayWhenEmpty()
    {
        $result = $this->attributeLoader->load('s_user_addresses_attributes', 555);

        $this->assertInternalType('array', $result);
    }

    public function testLoadReturnArrayIfNotEmpty()
    {
        $this->attributePersister->persist(['text1' => 'foo'], 's_user_addresses_attributes', 2);
        $result = $this->attributeLoader->load('s_user_addresses_attributes', 2);

        $this->assertInternalType('array', $result);
        $this->assertNotEmpty($result);
    }

    /**
     * @dataProvider getForeignKeyData
     *
     * @param $input
     */
    public function testLoadForeignKeyValidation($input)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No foreign key provided');

        $this->attributeLoader->load('s_user_addresses_attributes', $input);
    }

    public function getForeignKeyData()
    {
        return [
            [false],
            [null],
            [0],
            [''],
        ];
    }

    public function testLoadWithUnknownTable()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Table table_does_not_exists is no attribute table');

        $this->attributeLoader->load('table_does_not_exists', 1);
    }

    public function testLoadTranslationsReturnArrayWhenEmpty()
    {
        $result = $this->attributeLoader->loadTranslations('s_user_addresses_attributes', 555);

        $this->assertInternalType('array', $result);
    }

    public function testLoadTranslationsReturnArrayIfNotEmpty()
    {
        $this->attributePersister->persist(['text1' => 'foo'], 's_user_addresses_attributes', 2);
        $result = $this->attributeLoader->loadTranslations('s_user_addresses_attributes', 2);

        $this->assertInternalType('array', $result);
    }

    /**
     * @dataProvider getForeignKeyData
     *
     * @param $input
     */
    public function testLoadTranslationsForeignKeyValidation($input)
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No foreign key provided');

        $this->attributeLoader->load('s_user_addresses_attributes', $input);
    }
}
