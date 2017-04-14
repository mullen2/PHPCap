<?php

use PHPUnit\Framework\TestCase;
use IU\PHPCap\RedCapApiConnection;

class RedCapApiConnectionTest extends TestCase {
    private static $config;
    private static $apiConnection;
    
    public static function setUpBeforeClass()
    {
        self::$config = parse_ini_file('config.ini');
        self::$apiConnection = new RedCapApiConnection(self::$config['api.url']);
    }
    
    public function testProjectInfo()
    {
        $data = array(
                'token' => self::$config['basic.demography.api.token'],
                'content' => 'project',
                'format' => 'json',
                'returnFormat' => 'json'
        );
        
        $callData = http_build_query($data, '', '&');
        
        $result = self::$apiConnection->call($callData);
        $result = json_decode($result, true);
        
        //print_r($result);
        
        $this->assertEquals($result['project_language'], 'English', 'Project info "project_language" test.');
        $this->assertEquals($result['purpose_other'], 'PHPCap testing', 'Project info "purpose_other" test.');

    }

}
