<?php

namespace App\Support;

use Collator;

/**
 * How names are put in official alphabetical order: French collation, accents and case ignored.
 */
final class FrenchCollation
{
    public static function collator(): Collator
    {
        $collator = new Collator('fr_FR');
        $collator->setStrength(Collator::PRIMARY);

        return $collator;
    }
}
