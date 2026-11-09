<?php

// Bootstrap file for WikiTraceability unit tests

if ( !defined( 'MEDIAWIKI' ) ) {
    // Standalone mode - set up minimal environment for unit tests
    define( 'MEDIAWIKI', true );
    define( 'NS_MAIN', 0 );
    define( 'DB_REPLICA', 0 );
}

// Ensure composer autoloader is available
if ( is_readable( __DIR__ . '/../../../vendor/autoload.php' ) ) {
    require_once __DIR__ . '/../../../vendor/autoload.php';
} elseif ( is_readable( __DIR__ . '/../../vendor/autoload.php' ) ) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} else {
    // Fallback: register our own autoloader
    spl_autoload_register( function ( $class ) {
        $prefix = 'WikiTraceability\\';
        $baseDir = __DIR__ . '/../../includes/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relativeClass = substr( $class, $len );
        $file = $baseDir . str_replace( '\\', '/', $relativeClass ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    } );
}

// Mock classes for testing without MediaWiki dependencies
if ( !class_exists( 'SpecialPage' ) ) {
    class SpecialPage {
        protected $mName;
        public function __construct( $name ) {
            $this->mName = $name;
        }
        protected function setHeaders() {}
        protected function getOutput() {
            return new class {
                public function addWikiTextAsInterface( $text ) {}
                public function addHTML( $html ) {}
                public function getTitle() { return null; }
            };
        }
        protected function getRequest() {
            return new class {
                public function getText( $key ) { return null; }
            };
        }
        protected function getUser() {
            return new class {
                public function isRegistered() { return true; }
            };
        }
        public function getPageTitle() {
            return new class {
                public function getLocalURL() { return '#'; }
                public function getPrefixedText() { return 'Special:' . $this->mName; }
            };
        }
    }
}

if ( !class_exists( 'WikiPage' ) ) {
    class WikiPage {
        public function __construct( $title ) {}
        public function doEditContent( $content, $summary ) {
            return new class {
                public function isGood() { return true; }
                public function getMessage() { return new class { public function text() { return ''; } }; }
            };
        }
    }
}

if ( !class_exists( 'WikitextContent' ) ) {
    class WikitextContent {
        public function __construct( $text ) {}
    }
}

if ( !class_exists( 'Title' ) ) {
    class Title {
        public static function newFromText( $text, $namespace = 0 ) {
            return new class( $text ) {
                private $text;
                public function __construct( $text ) { $this->text = $text; }
                public function getText() { return $this->text; }
                public function getLocalURL() { return '/wiki/' . urlencode( $this->text ); }
                public function getNamespace() { return 0; }
            };
        }
    }
}

if ( !class_exists( '\SMW\Store' ) ) {
    namespace SMW {
        class Store {
            public static function getInstance() { return null; }
        }
    }
}

if ( !class_exists( '\SMWDIWikiPage' ) ) {
    namespace SMW {
        class SMWDIWikiPage {
            public static function newFromTitle( $title ) { return null; }
        }
    }
}

if ( !class_exists( '\SMWDIProperty' ) ) {
    namespace SMW {
        class SMWDIProperty {
            public static function newFromUserLabel( $label ) { return null; }
        }
    }
}

// Define wfGetDB if not defined
if ( !function_exists( 'wfGetDB' ) ) {
    function wfGetDB( $dbType ) {
        return null;
    }
}
