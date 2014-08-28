<?php

/**
 * @group filter
 */
class DDM_Filter_Word_CamelCaseToUnderscoreTest extends DDM_Test_TestCase
{
    protected static $filter;

    /**
     * Creates the filter for use in tests
     */
    public static function setUpBeforeClass() {
        self::$filter = new DDM_Filter_Word_CamelCaseToUnderscore();
    }

    /**
     * @test
     */
    public function address() {
        $input = 'Address';
        $expected_output = 'Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function getAddress() {
        $input = 'getAddress';
        $expected_output = 'get_Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function getAddressCapital() {
        $input = 'GetAddress';
        $expected_output = 'Get_Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function getAddress2() {
        $input = 'GetAddress2';
        $expected_output = 'Get_Address_2';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function twoGetAddress() {
        $input = '2GetAddress';
        $expected_output = '2_Get_Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }

    /**
     * @test
     */
    public function get2Address() {
        $input = 'Get2Address';
        $expected_output = 'Get_2_Address';
        $output = self::$filter->filter($input);
        $this->assertEquals($expected_output, $output);
    }
}