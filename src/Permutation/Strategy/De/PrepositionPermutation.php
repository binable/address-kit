<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\De;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The leading preposition of a German street name, which one register carries
 * and the next drops: "Am Freiheitsplatz" / "Freiheitsplatz", "Am Porscheplatz"
 * / "Porscheplatz", "An der Mühle" / "Mühle".
 *
 * Unlike a generic term, the preposition is not part of the name - it is the
 * dative phrase the name is embedded in - so a register is free to store the
 * square as "Am Freiheitsplatz", sort it as "Freiheitsplatz, Am" or simply
 * leave the phrase out, and all three spellings are in use side by side.
 *
 * The two directions are deliberately not symmetric:
 *
 *   - Dropping is safe. A street that starts with a preposition cannot mean
 *     anything else, so every one of them is dropped, whichever preposition it
 *     is.
 *   - Prepending is a guess, and every guess is one more lookup. It therefore
 *     only fires where the name itself says which preposition belongs in front
 *     of it: a field name, a square, a hill - a {@see self::TERMS} entry - and
 *     never for the generic terms that are never used with one ("Hauptstraße",
 *     "Kirchweg", "Lindenallee").
 *
 * That keeps the permutation at one extra spelling per address instead of the
 * dozen a blind "Am|An der|Auf dem|Im|…" would add.
 */
#[AsTaggedItem(priority: 30)]
final class PrepositionPermutation extends AbstractGermanPermutation
{
    /**
     * Every dative phrase a German street name is known to start with, in the
     * spellings the registers use - the contracted ones ("Am", "Beim",
     * "Vorm") as well as the written out ones ("An dem" is not among them,
     * nobody spells a street that way).
     *
     * @var list<string>
     */
    private const PREPOSITIONS = [
        'am', 'an den', 'an der', 'auf dem', 'auf den', 'auf der', 'aufm', 'bei den', 'bei der', 'beim',
        'hinter den', 'hinter der', 'hinterm', 'im', 'in den', 'in der', 'neben der', 'unter den', 'unter der',
        'unterm', 'vor dem', 'vor der', 'vorm', 'zu den', 'zum', 'zur',
        // The Low German forms the registers of the north carry unchanged
        // ("Achtern Busch", "Op de Wisch", "Bi de Kark").
        'achter de', 'achterm', 'achtern', 'anner', 'bi de', 'bi den', 'in de', 'op de', 'op den', 'vor de',
        'vör de',
    ];

    /**
     * Last element of the name => the preposition that belongs in front of it.
     * The gender of the compound is the gender of its last element, so the
     * term decides between "Am" (der/das) and "An der" (die).
     *
     * Only terms that name a place rather than a road are listed: a square, a
     * field, a water, a building. The road terms are exactly the ones that are
     * never used with a preposition, which is why "…straße", "…weg",
     * "…gasse", "…allee" and "…ring" are missing here although they are the
     * most common endings of all.
     *
     * @var array<string, string>
     */
    private const TERMS = [
        // der / das
        'anger' => 'Am',
        'bach' => 'Am',
        'bad' => 'Am',
        'berg' => 'Am',
        'brunnen' => 'Am',
        'busch' => 'Am',
        'damm' => 'Am',
        'deich' => 'Am',
        'feld' => 'Am',
        'graben' => 'Am',
        'hafen' => 'Am',
        'hain' => 'Am',
        'hang' => 'Am',
        'haus' => 'Am',
        'hof' => 'Am',
        'kamp' => 'Am',
        'kanal' => 'Am',
        'kreuz' => 'Am',
        'markt' => 'Am',
        'moor' => 'Am',
        'park' => 'Am',
        'platz' => 'Am',
        'rain' => 'Am',
        'see' => 'Am',
        'steg' => 'Am',
        'stein' => 'Am',
        'teich' => 'Am',
        'tor' => 'Am',
        'turm' => 'Am',
        'ufer' => 'Am',
        'wald' => 'Am',
        'wall' => 'Am',
        'weiher' => 'Am',
        // die
        'bleiche' => 'An der',
        'brücke' => 'An der',
        'burg' => 'An der',
        'chaussee' => 'An der',
        'halde' => 'An der',
        'heide' => 'An der',
        'höhe' => 'An der',
        'kirche' => 'An der',
        'koppel' => 'An der',
        'leite' => 'An der',
        'mauer' => 'An der',
        'mühle' => 'An der',
        'quelle' => 'An der',
        'schule' => 'An der',
        'trift' => 'An der',
        'wiese' => 'An der',
    ];

    /**
     * Adjective stems that stand in front of a street name. A German adjective
     * behind a definite article is declined weak, and the weak dative is "-en"
     * for every gender, so "Alter Markt" becomes "Am Alten Markt" and "Alte
     * Mühle" becomes "An der Alten Mühle".
     *
     * Only real adjectives are listed. The "-er" of a place ("Potsdamer Platz",
     * "Fürstenwalder Damm") is not an ending but part of the word and never
     * changes, which is exactly what a list of stems keeps apart from a rule
     * over the ending alone.
     *
     * @var list<string>
     */
    private const ADJECTIVES = [
        'alt', 'altstädtisch', 'breit', 'dürr', 'groß', 'gross', 'grün', 'heilig', 'hinter', 'hoch', 'klein',
        'krumm', 'kurz', 'lang', 'mittler', 'neu', 'neustädtisch', 'nieder', 'ober', 'rot', 'schmal', 'schwarz',
        'tief', 'unter', 'vorder', 'weiß', 'weiss', 'weit',
    ];

    /**
     * Road terms that are a word of their own only because a preposition
     * stands in front of them. "Am Weg", "In der Gasse" and "Am Ring" are
     * street names, "Weg", "Gasse" and "Ring" are not.
     *
     * @var list<string>
     */
    private const BARE_TERMS = [
        'allee', 'bogen', 'gasse', 'kehre', 'pfad', 'ring', 'steig', 'stieg', 'straße', 'strasse', 'str.',
        'twiete', 'weg', 'winkel', 'zeile',
    ];

    public function permute(Address $address): iterable
    {
        if (null === $address->street) {
            return;
        }

        $street = trim($address->street);

        $name = $this->dropPreposition($street);
        if (null !== $name) {
            yield $address->withStreet($name);

            return;
        }

        $preposition = $this->getPreposition($street);
        if (null !== $preposition) {
            yield $address->withStreet($preposition.' '.$this->declineLeadingAdjective($street));
        }
    }

    /**
     * The name without its leading preposition, or null when the street does
     * not start with one.
     *
     * The remainder has to be a name of its own: "Am Weg" without the "Am" is
     * not a street.
     */
    private function dropPreposition(string $street): ?string
    {
        if (1 !== preg_match('/^(?:'.self::alternation(self::PREPOSITIONS).') +(\S.*)$/iu', $street, $matches)) {
            return null;
        }

        $name = trim($matches[1]);
        if (mb_strlen($name) < 3 || \in_array(mb_strtolower($name), self::BARE_TERMS, true)) {
            return null;
        }

        return $name;
    }

    /**
     * The preposition that belongs in front of this name, or null when the name
     * does not end in a term that asks for one.
     */
    private function getPreposition(string $street): ?string
    {
        if (1 !== preg_match('/('.self::alternation(array_keys(self::TERMS)).')$/iu', $street, $matches)) {
            return null;
        }

        return self::TERMS[mb_strtolower($matches[1])] ?? null;
    }

    /**
     * The name with its leading adjective in the weak dative the preposition
     * asks for: "Alter Markt" -> "Alten Markt", "Große Bleiche" -> "Großen
     * Bleiche". A name that does not start with an adjective, or one whose
     * adjective is already declined, is returned unchanged.
     */
    private function declineLeadingAdjective(string $street): string
    {
        if (1 !== preg_match('/^(\p{L}+)(\s)/u', $street, $matches)) {
            return $street;
        }

        $word = $matches[1];
        if (1 !== preg_match('/^(.+?)(?:e|er|es)$/u', mb_strtolower($word), $stem) || !\in_array($stem[1], self::ADJECTIVES, true)) {
            return $street;
        }

        $declined = $stem[1].'en';
        if (mb_strtoupper(mb_substr($word, 0, 1)) === mb_substr($word, 0, 1)) {
            $declined = mb_strtoupper(mb_substr($declined, 0, 1)).mb_substr($declined, 1);
        }

        return $declined.mb_substr($street, mb_strlen($word));
    }
}
