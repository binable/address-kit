<?php

declare(strict_types=1);

namespace Binable\AddressKit\Language\En;

/**
 * Numbered street names in figures and spelled out ("2nd" => "Second"), the
 * way city gazetteers write them.
 *
 * Used by {@see \Binable\AddressKit\Permutation\Strategy\En\OrdinalPermutation}.
 */
final class Ordinals
{
    /**
     * @var array<string, string>
     */
    public const SPELLED_OUT = [
        '1st' => 'First', '2nd' => 'Second', '3rd' => 'Third', '4th' => 'Fourth', '5th' => 'Fifth',
        '6th' => 'Sixth', '7th' => 'Seventh', '8th' => 'Eighth', '9th' => 'Ninth', '10th' => 'Tenth',
        '11th' => 'Eleventh', '12th' => 'Twelfth', '13th' => 'Thirteenth', '14th' => 'Fourteenth', '15th' => 'Fifteenth',
        '16th' => 'Sixteenth', '17th' => 'Seventeenth', '18th' => 'Eighteenth', '19th' => 'Nineteenth', '20th' => 'Twentieth',
    ];

    private function __construct()
    {
    }
}
