<?php

/**
 * @group filter
 */
class DDM_Filter_Word_UnderscoreToCamelCaseTest extends DDM_Test_TestCase
{
    protected static $filter;

    /**
     * Creates the filter for use in tests
     */
    public static function setUpBeforeClass() {
        self::$filter = new DDM_Filter_Word_UnderscoreToCamelCase();
    }

    /**
     * @test
     */
    public function address() {
        $input = 'address';
        $expected_output = 'Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function getAddress() {
        $input = 'get_address';
        $expected_output = 'GetAddress';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function getAddress2() {
        $input = 'get_address_2';
        $expected_output = 'GetAddress2';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function twoGetAddress() {
        $input = '2_get_address';
        $expected_output = '2GetAddress';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function get2Address() {
        $input = 'get_2_address';
        $expected_output = 'Get2Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }
}