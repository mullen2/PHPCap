<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;
use IU\PHPCap\PhpCapException;

/**
 * PHPUnit tests for the RedCapProject class.
 */
class MetadataTest extends TestCase
{
    private static $config;
    private static $basicDemographyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$basicDemographyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['basic.demography.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }

    public function testExportMetadata()
    {
        $result = self::$basicDemographyProject->exportMetadata();
         
        $this->assertArrayHasKey('field_name', $result[0], 'Metadata has field_name field test.');
        $this->assertEquals($result[0]['field_name'], 'record_id', 'Metadata has study_id field test.');
    
        $callInfo = self::$basicDemographyProject->getCallInfo();
     
        $this->assertEquals($callInfo['url'], self::$config['api.url'], 'Metadata url test.');
        $this->assertArrayHasKey('content_type', $callInfo, 'Metadata has content type test.');
        $this->assertArrayHasKey('http_code', $callInfo, 'Metadata has HTTP code test.');
    }
    
    public function testExportMetadataWithForms()
    {
        $result = self::$longitudinalDataProject->exportMetadata($format = 'php', $forms = ['lab_data']);
        
        $this->assertEquals(5, count($result), 'Number of fields check.');
        
        $expectedFields = ['prealbumin', 'creatinine', 'npcr', 'cholesterol', 'transferrin'];
        
        $actualFields = array_column($result, 'field_name');
        
        $this->assertEquals($expectedFields, $actualFields, 'Field names check.');
    }
    
    public function testExportMetadataWithFields()
    {
        $fields = ['study_id', 'age', 'bmi'];
        
        $result = self::$longitudinalDataProject->exportMetadata($format = 'php', $forms = [], $fields);
    
        $this->assertEquals(count($fields), count($result), 'Number of fields check.');
     
        $actualFields = array_column($result, 'field_name');
    
        $this->assertEquals($fields, $actualFields, 'Field names check.');
    }
}