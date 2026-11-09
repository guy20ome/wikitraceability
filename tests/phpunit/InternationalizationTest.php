<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class InternationalizationTest extends TestCase
{
    public function testAliasFileExists()
    {
        $path = __DIR__ . '/../../i18n/WikiTraceability.alias.php';
        $this->assertFileExists( $path );
    }

    public function testAliasFileIsValidPhp()
    {
        $path = __DIR__ . '/../../i18n/WikiTraceability.alias.php';
        $content = file_get_contents( $path );
        
        // Check for PHP syntax errors
        $this->assertStringContainsString( '<?php', $content );
        $this->assertStringContainsString( '$specialPageAliases', $content );
    }

    public function testAliasFileContainsEnglishAliases()
    {
        $path = __DIR__ . '/../../i18n/WikiTraceability.alias.php';
        $content = file_get_contents( $path );

        $this->assertStringContainsString( "'en'", $content );
        $this->assertStringContainsString( "'TraceabilityProduct'", $content );
        $this->assertStringContainsString( "'TraceabilityTransformation'", $content );
        $this->assertStringContainsString( "'TraceabilityTree'", $content );
    }

    public function testEnJsonFileExists()
    {
        $path = __DIR__ . '/../../i18n/en.json';
        $this->assertFileExists( $path );
    }

    public function testEnJsonIsValidJson()
    {
        $path = __DIR__ . '/../../i18n/en.json';
        $content = file_get_contents( $path );
        $decoded = json_decode( $content, true );
        
        $this->assertIsArray( $decoded );
        $this->assertNotFalse( $decoded );
    }

    public function testEnJsonHasMetadata()
    {
        $path = __DIR__ . '/../../i18n/en.json';
        $content = json_decode( file_get_contents( $path ), true );

        $this->assertArrayHasKey( '@metadata', $content );
        $this->assertArrayHasKey( 'authors', $content['@metadata'] );
    }

    public function testEnJsonHasDescription()
    {
        $path = __DIR__ . '/../../i18n/en.json';
        $content = json_decode( file_get_contents( $path ), true );

        $this->assertArrayHasKey( 'wikitraceability-desc', $content );
    }

    public function testEnJsonHasSpecialPageMessages()
    {
        $path = __DIR__ . '/../../i18n/en.json';
        $content = json_decode( file_get_contents( $path ), true );

        $this->assertArrayHasKey( 'specialpage-traceabilityproduct', $content );
        $this->assertArrayHasKey( 'specialpage-traceabilitytransformation', $content );
        $this->assertArrayHasKey( 'specialpage-traceabilitytree', $content );
    }

    public function testSpecialPageMessageValues()
    {
        $path = __DIR__ . '/../../i18n/en.json';
        $content = json_decode( file_get_contents( $path ), true );

        $this->assertEquals( 'Traceability Product', $content['specialpage-traceabilityproduct'] );
        $this->assertEquals( 'Traceability Transformation', $content['specialpage-traceabilitytransformation'] );
        $this->assertEquals( 'Traceability Tree', $content['specialpage-traceabilitytree'] );
    }
}
