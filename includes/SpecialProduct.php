<?php

namespace WikiTraceability;

use SpecialPage;
use MediaWiki\MediaWikiServices;

class SpecialProduct extends SpecialPage {
    public function __construct() {
        parent::__construct( 'TraceabilityProduct' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $out = $this->getOutput();

        // Handle form submission
        if ($this->getRequest()->wasPosted() && $this->getRequest()->getVal('submit')) {
            $this->saveProduct();
            $out->addHTML('<p style="color: green;">Product saved successfully!</p>');
        }

        $out->addWikiTextAsInterface( '== Product entry ==\nThis page will allow users to create and edit traceability products.' );
        $this->displayForm();
    }

    private function displayForm() {
        $out = $this->getOutput();
        $form = '
        <form method="post" class="mw-htmlform">
            <label for="wpProductName">Product name</label><br />
            <input id="wpProductName" name="wpProductName" type="text" class="mw-htmlform-field" required /><br />
            <label for="wpProductCategory">Category</label><br />
            <input id="wpProductCategory" name="wpProductCategory" type="text" class="mw-htmlform-field" required /><br />
            <label for="wpProductWeight">Weight</label><br />
            <input id="wpProductWeight" name="wpProductWeight" type="number" step="0.01" class="mw-htmlform-field" required /><br />
            <label for="wpProductUnit">Unit</label><br />
            <select id="wpProductUnit" name="wpProductUnit" class="mw-htmlform-field" required>
                <option value="kg">kg</option>
                <option value="g">g</option>
                <option value="lb">lb</option>
                <option value="oz">oz</option>
            </select><br />
            <label for="wpProductDescription">Description</label><br />
            <textarea id="wpProductDescription" name="wpProductDescription" class="mw-htmlform-field"></textarea><br />
            <label for="wpOffId">Open Food Facts ID (optional)</label><br />
            <input id="wpOffId" name="wpOffId" type="text" class="mw-htmlform-field" /><br />
            <input type="submit" name="submit" value="Save Product" class="mw-htmlform-submit" />
        </form>
        ';
        $out->addHTML($form);
    }

    private function saveProduct() {
        $request = $this->getRequest();
        $name = $request->getVal('wpProductName');
        $category = $request->getVal('wpProductCategory');
        $weight = $request->getVal('wpProductWeight');
        $unit = $request->getVal('wpProductUnit');
        $description = $request->getVal('wpProductDescription');
        $offId = $request->getVal('wpOffId');

        $title = Title::newFromText($name, NS_MAIN);
        if (!$title) {
            throw new MWException("Invalid product name.");
        }

        $content = "{{Product
|name=$name
|category=$category
|weight=$weight
|unit=$unit
|description=$description
|openFoodFactsID=$offId
}}";

        $page = WikiPage::factory($title);
        $page->doEditContent(new WikitextContent($content), 'Created/updated via Special:TraceabilityProduct');
    }
}
