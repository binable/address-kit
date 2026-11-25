<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation\Strategy\It;

use Binable\AddressKit\Model\Address;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * The given name in front of a surname, dropped and shortened to its initial:
 * "Via Guglielmo Marconi" / "Via G. Marconi" / "Via Marconi".
 *
 * This is the single most common mismatch in Italian registers - the comune
 * files the street under the short form while the resident types the full one,
 * or the other way round.
 *
 * Only a known given name is touched. Without the list "Via Monte Bianco"
 * would turn into "Via Bianco" and "Via Porta Nuova" into "Via Nuova": in
 * Italian the first word of a name is a place just as often as a person.
 */
#[AsTaggedItem(priority: 30)]
final class GivenNamePermutation extends AbstractItalianPermutation
{
    /**
     * The given names Italian street names are actually built from - the
     * patriots, artists and saints of the Risorgimento canon plus the common
     * first names. Lowercase.
     *
     * @var list<string>
     */
    private const GIVEN_NAMES = [
        'achille', 'adolfo', 'alberto', 'aldo', 'alessandro', 'alessandra', 'alfredo', 'amedeo', 'amerigo',
        'anna', 'annibale', 'antonio', 'antonietta', 'armando', 'arnaldo', 'arturo', 'attilio', 'aurelio',
        'bartolomeo', 'benedetto', 'benedetta', 'bernardo', 'bruno', 'camillo', 'carlo', 'carla', 'caterina',
        'cesare', 'chiara', 'cristoforo', 'clara', 'claudio', 'clemente', 'corrado', 'costantino', 'daniele',
        'dante', 'davide', 'diego', 'domenico', 'edmondo', 'edoardo', 'elena', 'eleonora', 'elia', 'emanuele',
        'emilio', 'emilia', 'enrico', 'enzo', 'ernesto', 'ettore', 'eugenio', 'evangelista', 'fabio', 'fabrizio',
        'fausto', 'federico', 'felice', 'ferdinando', 'filippo', 'flavio', 'francesco', 'francesca', 'fulvio',
        'gabriele', 'gaetano', 'galileo', 'gaspare', 'gennaro', 'gerardo', 'giacinto', 'giacomo', 'gian',
        'giancarlo', 'gianni', 'gino', 'gioacchino', 'giordano', 'giorgio', 'giosuè', 'giosue', 'giovanni',
        'giovanna', 'girolamo', 'giulio', 'giulia', 'giuliano', 'giuseppe', 'giuseppina', 'goffredo', 'graziano',
        'gregorio', 'guglielmo', 'guido', 'ignazio', 'ippolito', 'italo', 'lorenzo', 'luca', 'luciano', 'lucia',
        'ludovico', 'luigi', 'luigia', 'marcello', 'marco', 'margherita', 'maria', 'mario', 'massimo', 'matteo',
        'maurizio', 'mauro', 'michele', 'michelangelo', 'mose', 'mosè', 'napoleone', 'nazario', 'niccolo',
        'niccolò', 'nicola', 'nicolo', 'nicolò', 'oreste', 'ottavio', 'paolo', 'paola', 'pasquale', 'pellegrino',
        'pietro', 'pio', 'primo', 'quintino', 'raffaele', 'raffaello', 'renato', 'riccardo', 'rita', 'roberto',
        'romolo', 'rosa', 'ruggero', 'salvatore', 'sandro', 'sebastiano', 'sergio', 'silvio', 'silvia',
        'simone', 'stefano', 'teresa', 'tommaso', 'ugo', 'umberto', 'valerio', 'vincenzo', 'vitaliano',
        'vittorio', 'vittoria',
    ];

    /**
     * Single letters with a dot that are a saint and not an initial:
     * "Via S. Marco" keeps its name, "Via G. Marconi" loses its given name.
     *
     * @var list<string>
     */
    private const SAINT_MARKERS = ['s.', 'ss.'];

    public function permute(Address $address): iterable
    {
        foreach ($this->getGivenNameVariations($this->splitType($address->street), self::GIVEN_NAMES, self::SAINT_MARKERS) as $street) {
            if ($street !== $address->street) {
                yield $address->withStreet($street);
            }
        }
    }
}
