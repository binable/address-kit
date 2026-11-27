<?php

declare(strict_types=1);

namespace Binable\AddressKit\Permutation;

use Binable\AddressKit\Language\StreetLanguageMap;
use Binable\AddressKit\Model\Address;
use Binable\AddressKit\Permutation\Strategy;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Combines all strategies: every strategy is applied to every variant produced so far,
 * so variants of different strategies are combined. The original address is always first.
 *
 * Language specific strategies ({@see LocalizedPermutationStrategyInterface}) only run for the
 * languages the {@see StreetLanguageMap} lists for the country of the address.
 */
final readonly class AddressPermutator
{
    private StreetLanguageMap $languageMap;

    /**
     * @param iterable<PermutationStrategyInterface> $strategies ordered, the first one runs first
     */
    public function __construct(
        private iterable $strategies,
        private int $maxPermutations = 50,
        ?StreetLanguageMap $languageMap = null,
    ) {
        $this->languageMap = $languageMap ?? new StreetLanguageMap();
    }

    /**
     * @param list<string>|null $languages ISO 639-1 codes of the language specific strategies to run;
     *                                     null derives them from the country of the address
     *
     * @return list<Address>
     */
    public function permute(Address $address, ?array $languages = null): array
    {
        $languages ??= $this->languageMap->getLanguages($address->country);
        $variants = [self::key($address) => $address];

        foreach ($this->strategies as $strategy) {
            if ($strategy instanceof LocalizedPermutationStrategyInterface && !\in_array($strategy->getLanguage(), $languages, true)) {
                continue;
            }

            foreach ($variants as $variant) {
                foreach ($strategy->permute($variant) as $permutation) {
                    $variants[self::key($permutation)] ??= $permutation;

                    if (\count($variants) >= $this->maxPermutations) {
                        return array_values($variants);
                    }
                }
            }
        }

        return array_values($variants);
    }

    /**
     * Instance with all built-in strategies, for usage without the Symfony container.
     */
    public static function createDefault(int $maxPermutations = 50, ?StreetLanguageMap $languageMap = null): self
    {
        return new self(self::sortByPriority(self::builtInStrategies()), $maxPermutations, $languageMap);
    }

    /**
     * @return list<PermutationStrategyInterface>
     */
    private static function builtInStrategies(): array
    {
        return [
            // language independent
            new Strategy\HouseNumberInStreetPermutation(),
            new Strategy\CasePermutation(),
            new Strategy\HouseNumberPermutation(),
            // de
            new Strategy\De\UmlautPermutation(),
            new Strategy\De\HyphenPermutation(),
            new Strategy\De\PrepositionPermutation(),
            new Strategy\De\GrundwortPermutation(),
            new Strategy\De\StrassePermutation(),
            // nl
            new Strategy\Nl\StreetPermutation(),
            // en
            new Strategy\En\OrdinalPermutation(),
            new Strategy\En\DirectionalPermutation(),
            new Strategy\En\StreetSuffixPermutation(),
            // fr
            new Strategy\Fr\AccentPermutation(),
            new Strategy\Fr\HyphenPermutation(),
            new Strategy\Fr\TitlePermutation(),
            new Strategy\Fr\StreetTypePermutation(),
            // it
            new Strategy\It\AccentPermutation(),
            new Strategy\It\GivenNamePermutation(),
            new Strategy\It\TitlePermutation(),
            new Strategy\It\StreetTypePermutation(),
            // pl
            new Strategy\Pl\DiacriticPermutation(),
            new Strategy\Pl\GivenNamePermutation(),
            new Strategy\Pl\TitlePermutation(),
            new Strategy\Pl\StreetTypePermutation(),
            // pt
            new Strategy\Pt\StreetTypePermutation(),
            // ro
            new Strategy\Ro\StreetTypePermutation(),
            // cs
            new Strategy\Cs\StreetTypePermutation(),
            // lt
            new Strategy\Lt\StreetTypePermutation(),
            // da
            new Strategy\Da\LetterPermutation(),
            new Strategy\Da\AccentPermutation(),
            new Strategy\Da\PrefixPermutation(),
            new Strategy\Da\LinkingSPermutation(),
            new Strategy\Da\BygadePermutation(),
            new Strategy\Da\StreetTermPermutation(),
            // sv
            new Strategy\Sv\DiacriticPermutation(),
        ];
    }

    /**
     * Same order as the container's tagged iterator: highest {@see AsTaggedItem} priority first,
     * declaration order on equal priority.
     *
     * @param list<PermutationStrategyInterface> $strategies
     *
     * @return list<PermutationStrategyInterface>
     */
    private static function sortByPriority(array $strategies): array
    {
        $priority = static function (PermutationStrategyInterface $strategy): int {
            $attributes = (new \ReflectionClass($strategy))->getAttributes(AsTaggedItem::class);

            return [] === $attributes ? 0 : ($attributes[0]->newInstance()->priority ?? 0);
        };

        $indexed = [];
        foreach ($strategies as $index => $strategy) {
            $indexed[] = [$priority($strategy), $index, $strategy];
        }

        usort($indexed, static fn (array $a, array $b): int => [$b[0], $a[1]] <=> [$a[0], $b[1]]);

        return array_column($indexed, 2);
    }

    private static function key(Address $address): string
    {
        return implode("\0", [$address->street, $address->houseNumber, $address->zipCode, $address->city, $address->country]);
    }
}
