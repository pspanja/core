<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\API\Repository\Tests\FieldType;

use eZ\Publish\Core\FieldType\Country\Value as CountryValue;
use eZ\Publish\API\Repository\Values\Content\Field;

/**
 * Integration test for use field type.
 *
 * @group integration
 * @group field-type
 */
class CountryIntegrationTest extends SearchMultivaluedBaseIntegrationTest
{
    /**
     * Get name of tested field type.
     *
     * @return string
     */
    public function getTypeName()
    {
        return 'ezcountry';
    }

    /**
     * Get expected settings schema.
     *
     * @return array
     */
    public function getSettingsSchema()
    {
        return [
            'isMultiple' => [
                'type' => 'boolean',
                'default' => false,
            ],
        ];
    }

    /**
     * Get a valid $fieldSettings value.
     *
     * @return mixed
     */
    public function getValidFieldSettings()
    {
        return [
            'isMultiple' => false,
        ];
    }

    /**
     * Get $fieldSettings value not accepted by the field type.
     *
     * @return mixed
     */
    public function getInvalidFieldSettings()
    {
        return [
            'somethingUnknown' => 0,
        ];
    }

    /**
     * Get expected validator schema.
     *
     * @return array
     */
    public function getValidatorSchema()
    {
        return [];
    }

    /**
     * Get a valid $validatorConfiguration.
     *
     * @return mixed
     */
    public function getValidValidatorConfiguration()
    {
        return [];
    }

    /**
     * Get $validatorConfiguration not accepted by the field type.
     *
     * @return mixed
     */
    public function getInvalidValidatorConfiguration()
    {
        return [
            'unknown' => ['value' => 42],
        ];
    }

    /**
     * Get initial field data for valid object creation.
     *
     * @return mixed
     */
    public function getValidCreationFieldData()
    {
        return new CountryValue(
            [
                'BE' => [
                    'Name' => 'Belgium',
                    'Alpha2' => 'BE',
                    'Alpha3' => 'BEL',
                    'IDC' => 32,
                ],
            ]
        );
    }

    /**
     * Get name generated by the given field type (via fieldType->getName()).
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'Belgium';
    }

    /**
     * Asserts that the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was stored and loaded correctly.
     *
     * @param Field $field
     */
    public function assertFieldDataLoadedCorrect(Field $field)
    {
        $this->assertInstanceOf(
            'eZ\\Publish\\Core\\FieldType\\Country\\Value',
            $field->value
        );

        $expectedData = [
            'countries' => [
                'BE' => [
                    'Name' => 'Belgium',
                    'Alpha2' => 'BE',
                    'Alpha3' => 'BEL',
                    'IDC' => 32,
                ],
            ],
        ];

        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    /**
     * Get field data which will result in errors during creation.
     *
     * This is a PHPUnit data provider.
     *
     * The returned records must contain of an error producing data value and
     * the expected exception class (from the API or SPI, not implementation
     * specific!) as the second element. For example:
     *
     * <code>
     * array(
     *      array(
     *          new DoomedValue( true ),
     *          'eZ\\Publish\\API\\Repository\\Exceptions\\ContentValidationException'
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array[]
     */
    public function provideInvalidCreationFieldData()
    {
        return [
            [
                'Sindelfingen',
                'eZ\\Publish\\API\\Repository\\Exceptions\\InvalidArgumentException',
            ],
            [
                ['NON_VALID_ALPHA2_CODE'],
                'eZ\\Publish\\API\\Repository\\Exceptions\\InvalidArgumentException',
            ],
            [
                ['BE', 'FR'],
                'eZ\\Publish\\Core\\Base\\Exceptions\\ContentFieldValidationException',
            ],
            [
                new CountryValue(
                    [
                        'NON_VALID_ALPHA2_CODE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                    ]
                ),
                'eZ\\Publish\\Core\\Base\\Exceptions\\ContentFieldValidationException',
            ],
        ];
    }

    /**
     * Get update field externals data.
     *
     * @return array
     */
    public function getValidUpdateFieldData()
    {
        return new CountryValue(
            [
                'FR' => [
                    'Name' => 'France',
                    'Alpha2' => 'FR',
                    'Alpha3' => 'FRA',
                    'IDC' => 33,
                ],
            ]
        );
    }

    /**
     * Get externals updated field data values.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function assertUpdatedFieldDataLoadedCorrect(Field $field)
    {
        $this->assertInstanceOf(
            'eZ\\Publish\\Core\\FieldType\\Country\\Value',
            $field->value
        );

        $expectedData = [
            'countries' => [
                'FR' => [
                    'Name' => 'France',
                    'Alpha2' => 'FR',
                    'Alpha3' => 'FRA',
                    'IDC' => 33,
                ],
            ],
        ];
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    /**
     * Get field data which will result in errors during update.
     *
     * This is a PHPUnit data provider.
     *
     * The returned records must contain of an error producing data value and
     * the expected exception class (from the API or SPI, not implementation
     * specific!) as the second element. For example:
     *
     * <code>
     * array(
     *      array(
     *          new DoomedValue( true ),
     *          'eZ\\Publish\\API\\Repository\\Exceptions\\ContentValidationException'
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array[]
     */
    public function provideInvalidUpdateFieldData()
    {
        return $this->provideInvalidCreationFieldData();
    }

    /**
     * Asserts the the field data was loaded correctly.
     *
     * Asserts that the data provided by {@link getValidCreationFieldData()}
     * was copied and loaded correctly.
     *
     * @param Field $field
     */
    public function assertCopiedFieldDataLoadedCorrectly(Field $field)
    {
        $this->assertInstanceOf(
            'eZ\\Publish\\Core\\FieldType\\Country\\Value',
            $field->value
        );

        $expectedData = [
            'countries' => [
                'BE' => [
                    'Name' => 'Belgium',
                    'Alpha2' => 'BE',
                    'Alpha3' => 'BEL',
                    'IDC' => 32,
                ],
            ],
        ];
        $this->assertPropertiesCorrect(
            $expectedData,
            $field->value
        );
    }

    /**
     * Get data to test to hash method.
     *
     * This is a PHPUnit data provider
     *
     * The returned records must have the the original value assigned to the
     * first index and the expected hash result to the second. For example:
     *
     * <code>
     * array(
     *      array(
     *          new MyValue( true ),
     *          array( 'myValue' => true ),
     *      ),
     *      // ...
     * );
     * </code>
     *
     * @return array
     */
    public function provideToHashData()
    {
        return [
            [
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
                ['BE', 'FR'],
            ],
        ];
    }

    /**
     * Get expectations for the fromHash call on our field value.
     *
     * This is a PHPUnit data provider
     *
     * @return array
     */
    public function provideFromHashData()
    {
        return [
            [
                ['BE', 'FR'],
                new CountryValue(
                    [
                        'BE' => [
                            'Name' => 'Belgium',
                            'Alpha2' => 'BE',
                            'Alpha3' => 'BEL',
                            'IDC' => 32,
                        ],
                        'FR' => [
                            'Name' => 'France',
                            'Alpha2' => 'FR',
                            'Alpha3' => 'FRA',
                            'IDC' => 33,
                        ],
                    ]
                ),
            ],
        ];
    }

    public function providerForTestIsEmptyValue()
    {
        return [
            [new CountryValue()],
            [new CountryValue([])],
        ];
    }

    public function providerForTestIsNotEmptyValue()
    {
        return [
            [
                $this->getValidCreationFieldData(),
            ],
        ];
    }

    protected function getValidSearchValueOne()
    {
        return ['Andorra'];
    }

    protected function getValidSearchValueTwo()
    {
        return ['Trinidad and Tobago'];
    }

    protected function getSearchTargetValueOne()
    {
        return 'Andorra';
    }

    protected function getSearchTargetValueTwo()
    {
        return 'Trinidad and Tobago';
    }

    protected function getAdditionallyIndexedFieldData()
    {
        return [
            [
                'idc',
                '376',
                '1868',
            ],
            [
                'alpha2',
                'AD',
                'TT',
            ],
            [
                'alpha3',
                'AND',
                'TTO',
            ],
            [
                'name',
                'Andorra',
                'Trinidad and Tobago',
            ],
            [
                'sort_value',
                'andorra',
                'trinidad and tobago',
            ],
        ];
    }

    protected function getValidMultivaluedSearchValuesOne()
    {
        return ['Andorra', 'Bolivia'];
    }

    protected function getValidMultivaluedSearchValuesTwo()
    {
        return ['Syrian Arab Republic', 'Trinidad and Tobago'];
    }

    protected function getAdditionallyIndexedMultivaluedFieldData()
    {
        return [
            [
                'idc',
                [376, 591],
                [963, 1868],
            ],
            [
                'alpha2',
                ['AD', 'BO'],
                ['SY', 'TT'],
            ],
            [
                'alpha3',
                ['AND', 'BOL'],
                ['SYR', 'TTO'],
            ],
            [
                'name',
                ['Andorra', 'Bolivia'],
                ['Syrian Arab Republic', 'Trinidad and Tobago'],
            ],
        ];
    }

    protected function getFullTextIndexedFieldData()
    {
        return [
            ['Andorra', 'Tobago'],
        ];
    }

    protected function createTestContentType()
    {
        $contentType = $this->createContentType(
            [
                'isMultiple' => true,
            ],
            $this->getValidValidatorConfiguration()
        );

        return $contentType;
    }
}
