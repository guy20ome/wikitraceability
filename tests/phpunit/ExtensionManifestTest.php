<?php

namespace WikiTraceability\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class ExtensionManifestTest extends TestCase
{
    private $manifestPath;
    private $manifest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manifestPath = __DIR__ . '/../../extension.json';
        $this->manifest = json_decode( file_get_contents( $this->manifestPath ), true );
    }

    public function testManifestFileExists()
    {
        $this->assertFileExists( $this->manifestPath );
    }

    public function testManifestIsValidJson()
    {
        $this->assertIsArray( $this->manifest );
    }

    public function testManifestHasRequiredFields()
    {
        $requiredFields = [ 'name', 'version', 'author', 'url', 'type', 'license-name', 'manifest_version' ];

        foreach ( $requiredFields as $field ) {
            $this->assertArrayHasKey( $field, $this->manifest, "Missing required field: $field" );
        }
    }

    public function testExtensionName()
    {
        $this->assertEquals( 'WikiTraceability', $this->manifest['name'] );
    }

    public function testExtensionVersion()
    {
        $this->assertArrayHasKey( 'version', $this->manifest );
        $this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $this->manifest['version'] );
    }

    public function testAutoloadClasses()
    {
        $this->assertArrayHasKey( 'AutoloadClasses', $this->manifest );
        $classes = $this->manifest['AutoloadClasses'];

        $expectedClasses = [
            'WikiTraceability\\Traceability',
            'WikiTraceability\\ApiOpenFoodFacts',
            'WikiTraceability\\SpecialProduct',
            'WikiTraceability\\SpecialTransformation',
            'WikiTraceability\\SpecialTraceabilityTree'
        ];

        foreach ( $expectedClasses as $class ) {
            $this->assertArrayHasKey( $class, $classes, "Missing autoload class: $class" );
        }
    }

    public function testSpecialPages()
    {
        $this->assertArrayHasKey( 'SpecialPages', $this->manifest );
        $specialPages = $this->manifest['SpecialPages'];

        $expectedPages = [
            'TraceabilityProduct',
            'TraceabilityTransformation',
            'TraceabilityTree'
        ];

        foreach ( $expectedPages as $page ) {
            $this->assertArrayHasKey( $page, $specialPages, "Missing special page: $page" );
        }
    }

    public function testSpecialPagesClasses()
    {
        $specialPages = $this->manifest['SpecialPages'];

        $this->assertEquals( 'WikiTraceability\\SpecialProduct', $specialPages['TraceabilityProduct'] );
        $this->assertEquals( 'WikiTraceability\\SpecialTransformation', $specialPages['TraceabilityTransformation'] );
        $this->assertEquals( 'WikiTraceability\\SpecialTraceabilityTree', $specialPages['TraceabilityTree'] );
    }

    public function testOpenFoodFactsApiModule()
    {
        $this->assertSame(
            'WikiTraceability\\ApiOpenFoodFacts',
            $this->manifest['APIModules']['wikitraceabilityopenfoodfacts']
        );
    }

    public function testResourceModules()
    {
        $this->assertArrayHasKey( 'ResourceModules', $this->manifest );
        $modules = $this->manifest['ResourceModules'];

        $this->assertArrayHasKey( 'ext.wikitraceability', $modules );
        $this->assertArrayHasKey( 'scripts', $modules['ext.wikitraceability'] );
        $this->assertArrayHasKey( 'styles', $modules['ext.wikitraceability'] );
    }

    public function testResourceModuleScripts()
    {
        $scripts = $this->manifest['ResourceModules']['ext.wikitraceability']['scripts'];
        $this->assertContains( 'resources/src/Traceability.js', $scripts );
    }

    public function testResourceModuleStyles()
    {
        $styles = $this->manifest['ResourceModules']['ext.wikitraceability']['styles'];
        $this->assertContains( 'resources/styles/Traceability.css', $styles );
    }

    public function testHooks()
    {
        $this->assertArrayHasKey( 'Hooks', $this->manifest );
        $hooks = $this->manifest['Hooks'];

        $this->assertSame(
            'WikiTraceability\\Traceability::onMessagesPreLoad',
            $hooks['MessagesPreLoad']
        );
        $this->assertArrayHasKey( 'OutputPageBeforeHTML', $hooks );
        $this->assertEquals( 
            'WikiTraceability\\Traceability::onOutputPageBeforeHTML',
            $hooks['OutputPageBeforeHTML']
        );
    }

    public function testExtensionFunctions()
    {
        $this->assertArrayHasKey( 'ExtensionFunctions', $this->manifest );
        $functions = $this->manifest['ExtensionFunctions'];

        $this->assertContains( 'WikiTraceability\\Traceability::init', $functions );
    }

    public function testMessagesDirs()
    {
        $this->assertArrayHasKey( 'MessagesDirs', $this->manifest );
        $this->assertArrayHasKey( 'WikiTraceability', $this->manifest['MessagesDirs'] );
        $this->assertEquals( 'i18n', $this->manifest['MessagesDirs']['WikiTraceability'] );
    }

    public function testMessagesFiles()
    {
        $this->assertArrayHasKey( 'MessagesFiles', $this->manifest );
        $this->assertArrayHasKey( 'WikiTraceabilityAlias', $this->manifest['MessagesFiles'] );
        $this->assertEquals( 'i18n/WikiTraceability.alias.php', $this->manifest['MessagesFiles']['WikiTraceabilityAlias'] );
    }
}
