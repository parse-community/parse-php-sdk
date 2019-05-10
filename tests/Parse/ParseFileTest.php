<?php

namespace Parse\Test;

use Parse\ParseFile;
use Parse\ParseObject;
use Parse\ParseQuery;

use PHPUnit\Framework\TestCase;

class ParseFileTest extends TestCase
{
    public static function setUpBeforeClass() : void
    {
        Helper::setUp();
    }

    public function tearDown() : void
    {
        Helper::tearDown();
        Helper::clearClass('TestFileObject');
    }

    public function testParseFileFactories()
    {
        $file = ParseFile::_createFromServer('hi.txt', 'http://');
        $file2 = ParseFile::createFromData('hello', 'hi.txt');
        $file3 = ParseFile::createFromFile(
            APPLICATION_PATH.'/tests/Parse/ParseFileTest.php',
            'file.php'
        );
        $this->assertEquals('http://', $file->getURL());
        $this->assertEquals('hi.txt', $file->getName());
        $this->assertEquals('hello', $file2->getData());
        $this->assertEquals('hi.txt', $file2->getName());
        $this->assertTrue(
            strpos(
                $file3->getData(),
                'i am looking for myself'
            ) !== false
        );
    }

    /**
     * @group file-upload-test
     */
    public function testParseFileUpload()
    {
        $file = ParseFile::createFromData('Fosco', 'test.txt');
        $file->save();
        $this->assertTrue(
            strpos($file->getURL(), 'http') !== false
        );
        $this->assertNotEquals('test.txt', $file->getName());
    }

    public function testParseFileDownload()
    {
        $file = ParseFile::_createFromServer('index.html', 'http://example.com');
        $data = $file->getData();
        $this->assertTrue(
            strpos($data, 'Example Domain') !== false
        );
    }

    /**
     * @group file-download-test
     */
    public function testParseFileDownloadUnsaved()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Cannot retrieve data for unsaved ParseFile.'
        );
        $file = ParseFile::createFromData(null, 'file.txt');
        $file->getData();
    }

    /**
     * @group file-download-test
     */
    public function testParsefileDeleteUnsaved()
    {
        $this->expectException(
            '\Parse\ParseException',
            'Cannot delete file that has not been saved.'
        );
        $file = ParseFile::createFromData('a test file', 'file.txt');
        $file->delete();
    }

    /**
     * @group file-download-test
     */
    public function testParseFileDownloadBadURL()
    {
        global $USE_CLIENT_STREAM;

        if (!isset($USE_CLIENT_STREAM)) {
            // curl exception expectation
            $this->expectException('\Parse\ParseException', '', 6);
        } else {
            // stream exception expectation
            $this->expectException('\Parse\ParseException', '', 2);
        }

        $file = ParseFile::_createFromServer('file.txt', 'http://404.example.com');
        $file->getData();
    }

    /**
     * @group test-parsefile-round-trip
     */
    public function testParseFileRoundTrip()
    {
        $contents = 'What would Bryan do?';
        $file = ParseFile::createFromData($contents, 'test.txt');
        $this->assertEquals($contents, $file->getData());
        $file->save();

        $fileAgain = ParseFile::_createFromServer($file->getName(), $file->getURL());
        $this->assertEquals($contents, $fileAgain->getData());
        $fileAgain->save();
        $this->assertEquals($file->getURL(), $fileAgain->getURL());
    }

    public function testParseFileTypes()
    {
        $contents = 'a fractal of rad design';
        $file = ParseFile::createFromData($contents, 'noextension');
        $file2 = ParseFile::createFromData($contents, 'photo.png', 'text/plain');
        $file3 = ParseFile::createFromData($contents, 'photo.png');
        $file4 = ParseFile::createFromData($contents, 'photo.PNG');
        $file->save();
        $file2->save();
        $file3->save();
        $file4->save();

        // check initial mime types after creating from data
        $this->assertEquals('unknown/unknown', $file->getMimeType());
        $this->assertEquals('text/plain', $file2->getMimeType());
        $this->assertEquals('image/png', $file3->getMimeType());
        $this->assertEquals('image/png', $file4->getMimeType());

        $fileAgain = ParseFile::_createFromServer($file->getName(), $file->getURL());
        $file2Again = ParseFile::_createFromServer($file2->getName(), $file2->getURL());
        $file3Again = ParseFile::_createFromServer($file3->getName(), $file3->getURL());
        $file4Again = ParseFile::_createFromServer($file4->getName(), $file4->getURL());

        $this->assertEquals($contents, $fileAgain->getData());
        $this->assertEquals($contents, $file2Again->getData());
        $this->assertEquals($contents, $file3Again->getData());
        $this->assertEquals($contents, $file4Again->getData());

        // check mime types after calling getData
        $mt = $fileAgain->getMimeType();
        // both of the following are acceptable for a response from a submitted mime type of unknown/unknown
        $this->assertTrue(
            $mt === 'application/octet-stream' ||  // parse-server < 2.7.0
            $mt === 'unknown/unknown'                       // parse-server >= 2.7.0, unknown mime type response change
        );
        $this->assertEquals('image/png', $file2Again->getMimeType());
        $this->assertEquals('image/png', $file3Again->getMimeType());
        $this->assertEquals('image/png', $file4Again->getMimeType());
    }

    public function testFileOnObject()
    {
        $contents = 'irrelephant';
        $file = ParseFile::createFromData($contents, 'php.txt');
        $file->save();

        $obj = ParseObject::create('TestFileObject');
        $obj->set('file', $file);
        $obj->save();

        $query = new ParseQuery('TestFileObject');
        $objAgain = $query->get($obj->getObjectId());
        $fileAgain = $objAgain->get('file');
        $contentsAgain = $fileAgain->getData();
        $this->assertEquals($contents, $contentsAgain);
    }

    public function testUnsavedFileOnObjectSave()
    {
        $contents = 'remember';
        $file = ParseFile::createFromData($contents, 'bones.txt');
        $obj = ParseObject::create('TestFileObject');
        $obj->set('file', $file);
        $obj->save();

        $query = new ParseQuery('TestFileObject');
        $objAgain = $query->get($obj->getObjectId());
        $fileAgain = $objAgain->get('file');
        $contentsAgain = $fileAgain->getData();
        $this->assertEquals($contents, $contentsAgain);
    }

    public function testFileDelete()
    {
        $data = 'c-c-c-combo breaker';
        $name = 'php.txt';
        $file = ParseFile::createFromData($data, $name);
        $file->save();
        $url = $file->getURL();
        $fileAgain = ParseFile::_createFromServer($name, $url);
        $contents = $fileAgain->getData();
        $this->assertEquals($data, $contents);
        $file->delete();
        $fileAgain = ParseFile::_createFromServer($name, $url);
        $this->expectException('Parse\ParseException', 'Download failed');
        $fileAgain->getData();
    }
}
