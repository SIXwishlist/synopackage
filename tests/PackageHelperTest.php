<?php

namespace DSMPackageSearch\Tests;

use \DSMPackageSearch\Config;
use \DSMPackageSearch\Package\PackageHelper;
use \PHPUnit\Framework\TestCase;
use \DSMPackageSearch\Package\SearchResult;
use \DSMPackageSearch\Package\Package;

// load Monolog library
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;

class PackageHelperTest extends TestCase
{
    private $goodConfig ='/config-files/goodConfig.yaml';

    private $packageHelper;
    public function setUp()
    {
        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');
        $this->packageHelper = new PackageHelper($config, $log);
        if (!file_exists(__DIR__."/cache"))
        {
            mkdir(__DIR__."/cache", 0777, true);
        }
    }

    public function testGetVersionDetailsProperValue1()
    {
        $major = null;
        $minor = null;
        $build = null;
        $result = $this->packageHelper->GetVersionDetails("6.1.3-15252", $major, $minor, $build);
        $this->assertTrue($result);
        $this->assertEquals(6, $major, "major");
        $this->assertEquals(1, $minor, "minor");
        $this->assertEquals(15252, $build, "build");
    }

    public function testGetVersionDetailsProperValue2()
    {
        $major = null;
        $minor = null;
        $build = null;
        $result = $this->packageHelper->GetVersionDetails("4.0-1300", $major, $minor, $build);
        $this->assertTrue($result);
        $this->assertEquals(4, $major, "major");
        $this->assertEquals(0, $minor, "minor");
        $this->assertEquals(1300, $build, "build");
    }

    public function testGetVersionDetailsBadValue()
    {
        $major = null;
        $minor = null;
        $build = null;
        $result = $this->packageHelper->GetVersionDetails("4.0x-1300", $major, $minor, $build);
        $this->assertFalse($result);
        $this->assertNull($major);
        $this->assertNull($minor);
        $this->assertNull($build);
    }

    public function testCompareResults1()
    {
        $searchResult1 = new SearchResult();
        $searchResult1->urlIndex = 1;
        $searchResult1->packagesFoundCount = 5;

        $searchResult2 = new SearchResult();
        $searchResult2->urlIndex = 2;
        $searchResult2->packagesFoundCount = 1;
        $result = PackageHelper::CompareSearchResult($searchResult1, $searchResult2);
        $this->assertEquals(-1, $result);
    }

    public function testCompareResults2()
    {
        $searchResult1 = new SearchResult();
        $searchResult1->urlIndex = 1;
        $searchResult1->packagesFoundCount = 0;

        $searchResult2 = new SearchResult();
        $searchResult2->urlIndex = 2;
        $searchResult2->packagesFoundCount = 1;
        $result = PackageHelper::CompareSearchResult($searchResult1, $searchResult2);
        $this->assertEquals(1, $result);
    }

    public function testCompareResults3()
    {
        $searchResult1 = new SearchResult();
        $searchResult1->urlIndex = 1;
        $searchResult1->packagesFoundCount = 1;

        $searchResult2 = new SearchResult();
        $searchResult2->urlIndex = 2;
        $searchResult2->packagesFoundCount = 0;
        $result = PackageHelper::CompareSearchResult($searchResult1, $searchResult2);
        $this->assertEquals(-1, $result);
    }

    public function testCompareResults4()
    {
        $searchResult1 = new SearchResult();
        $searchResult1->urlIndex = 1;
        $searchResult1->packagesFoundCount = 0;

        $searchResult2 = new SearchResult();
        $searchResult2->urlIndex = 2;
        $searchResult2->packagesFoundCount = 0;
        $result = PackageHelper::CompareSearchResult($searchResult1, $searchResult2);
        $this->assertEquals(-1, $result);
    }

    public function testCompareResultsEqual()
    {
        $searchResult1 = new SearchResult();
        $searchResult1->urlIndex = 1;
        $searchResult1->packagesFoundCount = 0;
        $result = PackageHelper::CompareSearchResult($searchResult1, $searchResult1);
        $this->assertEquals(0, $result);
    }

    public function testGetSources()
    {
        $sources = $this->packageHelper->GetSources();
        $this->assertNotNull($sources);
        $this->assertEquals(3, count($sources));
    }

    public function testGetUnsupportedSources()
    {
        $sources = $this->packageHelper->GetUnsupportedSources();
        $this->assertNotNull($sources);
        $this->assertEquals(1, count($sources));
    }

    public function testValidateArchProperValue()
    {
        $result = $this->packageHelper->ValidateArch("armada375");
        $this->assertTrue($result);
    }

    public function testValidateArchBadValue()
    {
        $result = $this->packageHelper->ValidateArch("none");
        $this->assertFalse($result);
    }

    public function testValidateModelProperValue()
    {
        $result = $this->packageHelper->ValidateModel("DS215j");
        $this->assertTrue($result);
    }

    public function testValidateModelBadValue()
    {
        $result = $this->packageHelper->ValidateModel("none");
        $this->assertFalse($result);
    }

    public function testVerifyAndGetSourceGoodValue()
    {
        $source = null;
        $result = $this->packageHelper->VerifyAndGetSource("synocommunity", $source);
        $this->assertTrue($result);
        $this->assertNotNull($source);
        $this->assertEquals("http://packages.synocommunity.com", $source->url);
    }

    public function testParseResponseNullValue()
    {
        $response = null;
        $result = TestTools::InvokeMethod($this->packageHelper, 
            "parseResponse", 
            array($response, "http://packages.synocommunity.com"));
        $this->assertNotNull($result);
        $this->assertEquals(0, count($result));
    }

    public function testParseResponseGoodValue()
    {
        $response = null;
        $fullPath = __DIR__."/sample-files/goodResponse.json";
        $fullPathPngSamle = __DIR__."/sample-files/sample.png";

        $this->assertTrue(file_exists($fullPathPngSamle));
        $fp = fopen($fullPathPngSamle, "r");
        $pngFile = fread($fp, filesize($fullPathPngSamle));
        fclose($fp);

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');

        $mock = $this->getMockBuilder("DSMPackageSearch\\DownloadManager")
            ->setConstructorArgs(array($config, $log))
            ->getMock();
        
        $mock->expects($this->any())
            ->method("DownloadContent")
            ->will($this->returnValue($pngFile));

        TestTools::SetPrivateProperty($this->packageHelper, "downloadManager", $mock);

        $this->assertTrue(file_exists($fullPath));
        $fh = fopen($fullPath, 'r');
        $response = fread($fh, filesize($fullPath));
        fclose($fh);
    
        $result = TestTools::InvokeMethod($this->packageHelper, 
            "parseResponse", 
            array($response, "http://packages.synocommunity.com"));
        $this->assertNotNull($result);
        $this->assertEquals(98, count($result));

    }

    public function testParseResponseInvalidCharactersValue()
    {
        $response = null;
        $fullPath = __DIR__."/sample-files/invalidCharactersResponse.json";
        $fullPathPngSamle = __DIR__."/sample-files/sample.png";

        $this->assertTrue(file_exists($fullPathPngSamle));
        $fp = fopen($fullPathPngSamle, "r");
        $pngFile = fread($fp, filesize($fullPathPngSamle));
        fclose($fp);

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');

        $mock = $this->getMockBuilder("DSMPackageSearch\\DownloadManager")
            ->setConstructorArgs(array($config, $log))
            ->getMock();
        
        $mock->expects($this->any())
            ->method("DownloadContent")
            ->will($this->returnValue($pngFile));

        TestTools::SetPrivateProperty($this->packageHelper, "downloadManager", $mock);

        $this->assertTrue(file_exists($fullPath));
        $fh = fopen($fullPath, 'r');
        $response = fread($fh, filesize($fullPath));
        fclose($fh);
    
        $result = TestTools::InvokeMethod($this->packageHelper, 
            "parseResponse", 
            array($response, "http://packages.synocommunity.com"));
        $this->assertNotNull($result);
        $this->assertEquals(1, count($result));
               
    }

    public function testParseResponseArrayOfPackages()
    {
        $response = null;
        $fullPath = __DIR__."/sample-files/arrayOfPackagesResponse.json";
        $fullPathPngSamle = __DIR__."/sample-files/sample.png";

        $this->assertTrue(file_exists($fullPathPngSamle));
        $fp = fopen($fullPathPngSamle, "r");
        $pngFile = fread($fp, filesize($fullPathPngSamle));
        fclose($fp);

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');

        $mock = $this->getMockBuilder("DSMPackageSearch\\DownloadManager")
            ->setConstructorArgs(array($config, $log))
            ->getMock();
        
        $mock->expects($this->any())
            ->method("DownloadContent")
            ->will($this->returnValue($pngFile));

        TestTools::SetPrivateProperty($this->packageHelper, "downloadManager", $mock);

        $this->assertTrue(file_exists($fullPath));
        $fh = fopen($fullPath, 'r');
        $response = fread($fh, filesize($fullPath));
        fclose($fh);
    
        $result = TestTools::InvokeMethod($this->packageHelper, 
            "parseResponse", 
            array($response, "http://packages.synocommunity.com"));
        $this->assertNotNull($result);
        $this->assertEquals(1, count($result));
               
    }

    public function testParseResponseEncodedIcon()
    {
        $response = null;
        $fullPath = __DIR__."/sample-files/encodedIconResponse.json";
        $fullPathPngSamle = __DIR__."/sample-files/sample.png";

        $this->assertTrue(file_exists($fullPathPngSamle));
        $fp = fopen($fullPathPngSamle, "r");
        $pngFile = fread($fp, filesize($fullPathPngSamle));
        fclose($fp);

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');

        $mock = $this->getMockBuilder("DSMPackageSearch\\DownloadManager")
            ->setConstructorArgs(array($config, $log))
            ->getMock();
        
        $mock->expects($this->any())
            ->method("DownloadContent")
            ->will($this->returnValue($pngFile));

        TestTools::SetPrivateProperty($this->packageHelper, "downloadManager", $mock);

        $this->assertTrue(file_exists($fullPath));
        $fh = fopen($fullPath, 'r');
        $response = fread($fh, filesize($fullPath));
        fclose($fh);
    
        $result = TestTools::InvokeMethod($this->packageHelper, 
            "parseResponse", 
            array($response, "http://packages.synocommunity.com"));
        $this->assertNotNull($result);
        $this->assertEquals(3, count($result));
               
    }

    public function testRequestPackageListProperValue()
    {
        $response = null;
        $fullPath = __DIR__."/sample-files/goodResponse.json";
        $fullPathPngSamle = __DIR__."/sample-files/sample.png";

        $this->assertTrue(file_exists($fullPathPngSamle));
        $fp = fopen($fullPathPngSamle, "r");
        $pngFile = fread($fp, filesize($fullPathPngSamle));
        fclose($fp);
        
        $this->assertTrue(file_exists($fullPath));
        $fh = fopen($fullPath, 'r');
        $response = fread($fh, filesize($fullPath));
        fclose($fh);

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');

        $mock = $this->getMockBuilder("DSMPackageSearch\\DownloadManager")
            ->setConstructorArgs(array($config, $log))
            ->getMock();
        
        $mock->expects($this->any())
            ->method("DownloadContent")
            ->will($this->returnValue($pngFile));
        $mock->expects($this->any())
            ->method("PostRequest")
            ->will($this->returnValue($response));

        TestTools::SetPrivateProperty($this->packageHelper, "downloadManager", $mock);

        $errorMessage = null;
        $result = $this->packageHelper->RequestPackageList("http://packages.synocommunity.com"
            , "armada375"
            , "DS15j"
            , 6
            , 1
            , 15252
            , false
            , "Mozilla"
            , $errorMessage);
        
        $errorMessageBeta = null;

        $resultBeta = $this->packageHelper->RequestPackageList("http://packages.synocommunity.com"
            , "armada375"
            , "DS15j"
            , 6
            , 1
            , 15252
            , true
            , "Mozilla"
            , $errorMessageBeta);

    
        // $result = TestTools::InvokeMethod($this->packageHelper, 
        //     "parseResponse", 
        //     array($response, "http://packages.synocommunity.com"));
        $this->assertNotNull($result);
        $this->assertNull($errorMessage);
        $this->assertEquals(98, count($result));

        $this->assertNotNull($resultBeta);
        $this->assertNull($errorMessage);
        $this->assertEquals(98, count($resultBeta));
    }

    public function testFilterResultsNullValue()
    {
        $result = $this->packageHelper->FilterResults(null, "test");
        $this->assertNull($result);
    }

    public function testFilterResultsEmptyArray()
    {
        $list = array();
        $result = $this->packageHelper->FilterResults($list, "test");
        $this->assertNull($result);
    }

    public function testFilterResultsKeyword()
    {
        $list = array();
        $package = new Package();
        $package->name = "NotIncluded";
        $package->description = "NotIncluded";
        $list[0] = $package;

        $package = new Package();
        $package->name = "ThisIsTest";
        $package->description = "ThisIsDescription";
        $list[1] = $package;

        $package = new Package();
        $package->name = "ThisIsName";
        $package->description = "ThisIsTestDescription";
        $list[2] = $package;

        $result = $this->packageHelper->FilterResults($list, "test");
        $this->assertNotNull($result);
        $this->assertEquals(2, count($result));
        $this->assertEquals("ThisIsTest", $result[0]->name);
        $this->assertEquals("ThisIsDescription", $result[0]->description);

        $this->assertEquals("ThisIsName", $result[1]->name);
        $this->assertEquals("ThisIsTestDescription", $result[1]->description);

    }

    public function testGetPackagesProperValue()
    {

        $response = null;
        $fullPath = __DIR__."/sample-files/goodResponse.json";
        $fullPathPngSamle = __DIR__."/sample-files/sample.png";

        $this->assertTrue(file_exists($fullPathPngSamle));
        $fp = fopen($fullPathPngSamle, "r");
        $pngFile = fread($fp, filesize($fullPathPngSamle));
        fclose($fp);
        
        $this->assertTrue(file_exists($fullPath));
        $fh = fopen($fullPath, 'r');
        $response = fread($fh, filesize($fullPath));
        fclose($fh);

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');
        
        $mock = $this->getMockBuilder("DSMPackageSearch\\DownloadManager")
            ->setConstructorArgs(array($config, $log))
            ->getMock();

        $mock->expects($this->any())
            ->method("DownloadContent")
            ->will($this->returnValue($pngFile));
        $mock->expects($this->any())
            ->method("PostRequest")
            ->will($this->returnValue($response));

        TestTools::SetPrivateProperty($this->packageHelper, "downloadManager", $mock);

        $errorMessage = null;
        $result = $this->packageHelper->GetPackages("http://packages.synocommunity.com", "armada375", "DS215j", 6, 1, 15152, 1, null, $errorMessage);
        $this->assertNotNull($result);
        $this->assertNull($errorMessage);
        $this->assertEquals(98, count($result));
    } 

    public function testGetPackagesErrorHandling()
    {

        $config = new Config(__DIR__, $this->goodConfig);
        $log = new Logger('main_logger');
        
        $mock = $this->getMockBuilder("DSMPackageSearch\\Package\PackageHelper")
            ->setConstructorArgs(array($config, $log))
            ->getMock();

        $mock->expects($this->any())
            ->method("RequestPackageList")
            ->will($this->throwException(new \Exception("error")));
        

        $errorMessage = null;
        $result = $mock->GetPackages("http://packages.synocommunity.com", "armada375", "DS215j", 6, 1, 15152, 1, null, $errorMessage);
        $this->assertEquals(0, count($result));
    } 

}