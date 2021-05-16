<?php

namespace Gedmo\Uploadable\Mapping;

/**
 * These are tests for the Mapping Validator of the Uploadable behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    protected $meta;

    public function setUp(): void
    {
        $this->meta = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setConstructorArgs(['', null])
            ->getMock();

        Validator::$enableMimeTypesConfigException = false;
    }

    public function tearDown(): void
    {
        Validator::$enableMimeTypesConfigException = true;
    }

    public function testValidateFieldIfFieldIsNotOfAValidTypeThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(['type' => 'someType']));

        Validator::validateField(
            $this->meta,
            'someField',
            Validator::UPLOADABLE_FILE_MIME_TYPE,
            Validator::$validFileMimeTypeTypes
        );
    }

    public function testValidatePathIfPathIsNotAStringOrIsAnEmptyStringThrowException()
    {
        $this->expectException('Gedmo\Exception\UploadableInvalidPathException');
        Validator::validatePath('');
    }

    public function testValidatePathCreatesNewDirectoryWhenItNotExists()
    {
        $dir = sys_get_temp_dir().'/new/directory-12312432423';
        Validator::validatePath($dir);
        $this->assertTrue(is_dir($dir));
        rmdir($dir);
        rmdir(dirname($dir));
    }

    public function testValidateConfigurationIfNeitherFilePathFieldNorFileNameFieldIsNotDefinedThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $config = ['filePathField' => false, 'fileNameField' => false];

        Validator::validateConfiguration($this->meta, $config);
    }

    public function testValidateConfigurationIfPathMethodIsNotAValidMethodThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        $config = ['filePathField' => 'someField', 'pathMethod' => 'invalidMethod'];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfCallbackMethodIsNotAValidMethodThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        $config = ['filePathField' => 'someField', 'pathMethod' => '', 'callback' => 'invalidMethod'];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsNotValidThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(['type' => 'someType']));

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'invalidClass',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsValidButDoesntImplementNeededInterfaceThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(['type' => 'someType']));

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'DateTime',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsValidThenDontThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(['type' => 'string']));

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'SHA1',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfFilenameGeneratorValueIsAValidClassThenDontThrowException()
    {
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));
        $this->meta->expects($this->any())
            ->method('getFieldMapping')
            ->will($this->returnValue(['type' => 'string']));

        $config = [
            'fileMimeTypeField' => '',
            'fileSizeField' => '',
            'fileNameField' => '',
            'filePathField' => 'someField',
            'pathMethod' => '',
            'callback' => '',
            'filenameGenerator' => 'Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorSha1',
            'maxSize' => 0,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfMaxSizeIsLessThanZeroThenThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        $config = [
            'fileMimeTypeField' => 'someField',
            'filePathField' => 'someField',
            'fileSizeField' => '',
            'pathMethod' => '',
            'callback' => '',
            'maxSize' => -123,
            'allowedTypes' => '',
            'disallowedTypes' => '',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }

    public function testValidateConfigurationIfAllowedTypesAndDisallowedTypesAreSetThenThrowException()
    {
        $this->expectException('Gedmo\Exception\InvalidMappingException');
        $this->meta->expects($this->once())
            ->method('getReflectionClass')
            ->will($this->returnValue(new \ReflectionClass(new FakeEntity())));

        Validator::$enableMimeTypesConfigException = true;

        $config = [
            'fileMimeTypeField' => 'someField',
            'filePathField' => 'someField',
            'fileSizeField' => '',
            'pathMethod' => '',
            'callback' => '',
            'maxSize' => 0,
            'allowedTypes' => 'text/plain',
            'disallowedTypes' => 'text/css',
        ];

        Validator::validateConfiguration(
            $this->meta,
            $config
        );
    }
}

class FakeEntity
{
}
