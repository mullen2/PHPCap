<?php

namespace IU\PHPCap;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\RedCapProject;

/**
 * PHPUnit integrations tests for importing and deleting metadata.
 * There tests include not only the metadata returned by the
 * getMetadata() method, but also other metadata including
 * project info, arms and events.
 */
class ImportTest extends TestCase
{
    private static $config;
    private static $emptyProject;
    private static $longitudinalDataProject;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file(__DIR__.'/../config.ini');
        self::$emptyProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['empty.project.api.token']
        );
        self::$longitudinalDataProject = new RedCapProject(
            self::$config['api.url'],
            self::$config['longitudinal.data.api.token']
        );
    }

    public function testImport()
    {
        $projectInfo = [
            'project_irb_number' => 'IRB-123',
        ];
        
        $count = self::$emptyProject->importProjectInfo($projectInfo, $format = 'php');
        
        $this->assertEquals(1, $count, 'Project info value updates check.');
        
        $result = self::$emptyProject->exportProjectInfo();
        
        $this->assertEquals(
            $projectInfo['project_irb_number'],
            $result['project_irb_number'],
            'IRB number check.'
        );
    }

    
    public function testFileCsvFileImports()
    {
        $projectInfo = FileUtil::fileToString(__DIR__.'/../data/longitudinal-project-info.csv');
        $metadata    = FileUtil::fileToString(__DIR__.'/../data/longitudinal-metadata.csv');
                
        $count = self::$emptyProject->importProjectInfo($projectInfo, $format = 'csv');
        $count = self::$emptyProject->importMetadata($metadata, $format = 'csv');
        
        $expectedMetadata = self::$longitudinalDataProject->exportMetadata();
        $actualMetadata   = self::$emptyProject->exportMetadata();
        
        $this->assertEquals($expectedMetadata, $actualMetadata, 'Metadata comparison.');
         
        # Call with no override specified to make sure
        # it doesn't cause an error
        $arms = self::$longitudinalDataProject->exportArms();
        self::$emptyProject->importArms($arms, 'php');
        
        $arms = self::$longitudinalDataProject->exportArms();
        self::$emptyProject->importArms($arms, 'php', true);

        # Import arms with non-boolean override
        $exceptionCaught = false;
        try {
            self::$emptyProject->importArms($arms, 'php', 123);
        } catch (PhpCapException $exception) {
            $exceptionCaught = true;
        }
        $this->assertTrue($exceptionCaught, 'Non-boolean override exception caught.');
        
        $events = self::$longitudinalDataProject->exportEvents();
        self::$emptyProject->importEvents($events, 'php', true);
        
        $mappings = self::$longitudinalDataProject->exportInstrumentEventMappings();
        self::$emptyProject->importInstrumentEventMappings($mappings);
        
        $newArms = self::$emptyProject->exportArms();
        $this->assertEquals($arms, $newArms, 'Arms check.');
        
        $newEvents = self::$emptyProject->exportEvents();
        $this->assertEquals($events, $newEvents, 'Events check.');
        
        $newMappings = self::$emptyProject->exportInstrumentEventMappings();
        $this->assertEquals($mappings, $newMappings, 'Mappings check.');
        
        # Try deleting arm 2
        $expectedArms = [['arm_num' => '1', 'name' => 'Drug A']];
        $count = self::$emptyProject->deleteArms([2]);
        $newArms = self::$emptyProject->exportArms();
        $this->assertEquals(1, $count, 'Arm deletion count check.');
        $this->assertEquals($expectedArms, $newArms, 'Arm deletion check.');
    }
    
    public function testDeleteArmsWithNullArmsSpecified()
    {
        $caughtException = false;
        try {
            $count = self::$emptyProject->deleteArms(null);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
    }
    
    public function testDeleteArmsWithEmptyArmsArraySpecified()
    {
        $caughtException = false;
        try {
            $count = self::$emptyProject->deleteArms([]);
        } catch (PhpCapException $exception) {
            $caughtException = true;
            $code = $exception->getCode();
            $this->assertEquals(PhpCapException::INVALID_ARGUMENT, $code, 'Exception code check.');
        }
        $this->assertTrue($caughtException, 'Caught exception.');
    }
}