<?php

namespace Parse\Test;

use Parse\ParseFile;
use Parse\ParseObject;
use Parse\ParseQuery;

class ParseFileTest extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Helper::setUp();
    }

    public function tearDown()
    {
        Helper::tearDown();
        Helper::clearClass('TestFileObject');
    }

    public function testParseFileFactories()
    {
        $file = ParseFile::_createFromServer('hi.txt', 'http://');
        $file2 = ParseFile::createFromData('hello', 'hi.txt');
        $file3 = ParseFile::createFromFile(
            'ParseFileTest.php',
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
        $file->save();
        $file2->save();
        $file3->save();

        $fileAgain = ParseFile::_createFromServer($file->getName(), $file->getURL());
        $file2Again = ParseFile::_createFromServer($file2->getName(), $file2->getURL());
        $file3Again = ParseFile::_createFromServer($file3->getName(), $file3->getURL());

        $this->assertEquals($contents, $fileAgain->getData());
        $this->assertEquals($contents, $file2Again->getData());
        $this->assertEquals($contents, $file3Again->getData());

        $this->assertEquals('unknown/unknown', $fileAgain->getMimeType());
        $this->assertEquals('text/plain', $file2Again->getMimeType());
        $this->assertEquals('image/png', $file3Again->getMimeType());
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
        $this->setExpectedException('Parse\ParseException', 'Download failed');
        $contents = $fileAgain->getData();
    }
}
