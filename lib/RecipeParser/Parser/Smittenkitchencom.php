<?php

class RecipeParser_Parser_Smittenkitchencom {

    static public function parse($html, $url) {
        $recipe = RecipeParser_Parser_Microformat::parse($html, $url);

        // Turn off libxml errors to prevent mismatched tag warnings.
        libxml_use_internal_errors(true);
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($doc);

        $recipe->source = "smitten kitchen";
        $recipe->url = $url;

        foreach($xpath->query('//meta[@property="og:title"]/@content') as $titleAttribute) {
            $recipe->title = $titleAttribute->value;
        }
        foreach($xpath->query('//meta[@property="og:image"]/@content') as $imageAttribute) {
            $recipe->photo_url = $imageAttribute->value;
        }
        foreach($xpath->query('//meta[@property="og:site_name"]/@content') as $siteNameAttribute) {
            $recipe->source = $siteNameAttribute->value;
        }
        foreach($xpath->query('//meta[@property="og:description"]/@content') as $descriptionAttribute) {
            $recipe->description = $descriptionAttribute->value;
        }
        foreach($xpath->query('//meta[@property="og:url"]/@content') as $urlAttribute) {
            $recipe->url = $urlAttribute->value;
        }

        foreach( $xpath->query('//li[@itemprop="recipeYield"]') as $yield) {
            $recipe->yield = $yield->textContent;
        }
        foreach($xpath->query('//li[@itemprop="totalTime"]') as $time) {
            $recipe->time['total'] = $time->textContent;
        }

        foreach($xpath->query('//div[@class="jetpack-recipe-ingredients"]/ul/node()') as $node) {
            if ( $node instanceof \DOMText ) {
                continue;
            }

            if ( $node->tagName == 'h5' ) {
                $recipe->addIngredientsSection(RecipeParser_Text::formatSectionName($node->textContent));
            } else if ( $node->attributes && $node->attributes->getNamedItem('itemprop')->nodeValue == 'recipeIngredient' ) {
                $recipe->appendIngredient(RecipeParser_Text::formatAsOneLine($node->textContent));
            }
        }

        $recipe->resetInstructions();
        foreach($xpath->query('//div[@class="jetpack-recipe-directions"]/node()') as $node) {
            $recipe->appendInstruction(RecipeParser_Text::formatAsOneLine($node->nodeValue));
        }

        foreach($xpath->query('//footer[@class="entry-footer"]/span[@class="cat-links"]/a[@rel="category tag"]') as $footerLink) {
            $recipe->categories[] = $footerLink->textContent;
        }

        return $recipe;
    }

}

?>
