<?php declare(strict_types=1);

namespace TcgManager\DataFixtures;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use TcgManager\Service\CardService;

class CardFixtures
{
    private CardService $cardService;

    public function __construct(CardService $cardService)
    {
        $this->cardService = $cardService;
    }

    /**
     * Load sample trading card data
     */
    public function loadSampleCards(Context $context = null): array
    {
        $context = $context ?? Context::createDefaultContext();
        $cardIds = [];

        $sampleCards = $this->getSampleCardData();

        foreach ($sampleCards as $cardData) {
            $cardId = $this->cardService->createCard($cardData, $context);
            $cardIds[] = $cardId;
        }

        return $cardIds;
    }

    /**
     * Get sample card data for different TCG types
     */
    private function getSampleCardData(): array
    {
        return [
            // Magic: The Gathering style cards
            [
                'title' => 'Lightning Bolt',
                'edition' => 'Alpha',
                'thresholdCost' => 1,
                'manaCost' => 'R',
                'rarity' => 'Common',
                'cardType' => 'Instant',
                'description' => 'Lightning Bolt deals 3 damage to any target.',
                'setCode' => 'LEA',
                'cardNumber' => '161',
                'marketPrice' => 150.00,
                'stockQuantity' => 5,
                'metadata' => [
                    'artist' => 'Christopher Rush',
                    'power' => null,
                    'toughness' => null,
                    'cmc' => 1
                ]
            ],
            [
                'title' => 'Black Lotus',
                'edition' => 'Alpha',
                'thresholdCost' => 0,
                'manaCost' => '0',
                'rarity' => 'Rare',
                'cardType' => 'Artifact',
                'description' => 'Tap, Sacrifice Black Lotus: Add three mana of any one color.',
                'setCode' => 'LEA',
                'cardNumber' => '232',
                'marketPrice' => 25000.00,
                'stockQuantity' => 1,
                'metadata' => [
                    'artist' => 'Christopher Rush',
                    'power' => null,
                    'toughness' => null,
                    'cmc' => 0
                ]
            ],
            [
                'title' => 'Shivan Dragon',
                'edition' => 'Alpha',
                'thresholdCost' => 6,
                'manaCost' => '4RR',
                'rarity' => 'Rare',
                'cardType' => 'Creature — Dragon',
                'description' => 'Flying. The undisputed master of the mountains of Shiv.',
                'setCode' => 'LEA',
                'cardNumber' => '175',
                'marketPrice' => 80.00,
                'stockQuantity' => 3,
                'metadata' => [
                    'artist' => 'Melissa A. Benson',
                    'power' => 5,
                    'toughness' => 5,
                    'cmc' => 6
                ]
            ],
            [
                'title' => 'Counterspell',
                'edition' => 'Alpha',
                'thresholdCost' => 2,
                'manaCost' => 'UU',
                'rarity' => 'Common',
                'cardType' => 'Instant',
                'description' => 'Counter target spell.',
                'setCode' => 'LEA',
                'cardNumber' => '055',
                'marketPrice' => 15.00,
                'stockQuantity' => 12,
                'metadata' => [
                    'artist' => 'Mark Poole',
                    'power' => null,
                    'toughness' => null,
                    'cmc' => 2
                ]
            ],
            [
                'title' => 'Serra Angel',
                'edition' => 'Alpha',
                'thresholdCost' => 5,
                'manaCost' => '3WW',
                'rarity' => 'Uncommon',
                'cardType' => 'Creature — Angel',
                'description' => 'Flying, vigilance. Born with wings of light and a sword of faith.',
                'setCode' => 'LEA',
                'cardNumber' => '030',
                'marketPrice' => 45.00,
                'stockQuantity' => 8,
                'metadata' => [
                    'artist' => 'Douglas Shuler',
                    'power' => 4,
                    'toughness' => 4,
                    'cmc' => 5
                ]
            ],
            // Modern cards
            [
                'title' => 'Teferi, Time Raveler',
                'edition' => 'War of the Spark',
                'thresholdCost' => 3,
                'manaCost' => '1WU',
                'rarity' => 'Rare',
                'cardType' => 'Legendary Planeswalker — Teferi',
                'description' => 'Each opponent can cast spells only any time they could cast a sorcery.',
                'setCode' => 'WAR',
                'cardNumber' => '221',
                'marketPrice' => 12.00,
                'stockQuantity' => 20,
                'metadata' => [
                    'artist' => 'Chris Rallis',
                    'loyalty' => 4,
                    'cmc' => 3
                ]
            ],
            [
                'title' => 'Ragavan, Nimble Pilferer',
                'edition' => 'Modern Horizons 2',
                'thresholdCost' => 1,
                'manaCost' => 'R',
                'rarity' => 'Mythic Rare',
                'cardType' => 'Legendary Creature — Monkey Pirate',
                'description' => 'Whenever Ragavan deals combat damage to a player, create a Treasure token.',
                'setCode' => 'MH2',
                'cardNumber' => '138',
                'marketPrice' => 65.00,
                'stockQuantity' => 6,
                'metadata' => [
                    'artist' => 'Simon Dominic',
                    'power' => 2,
                    'toughness' => 1,
                    'cmc' => 1
                ]
            ],
            [
                'title' => 'Oko, Thief of Crowns',
                'edition' => 'Throne of Eldraine',
                'thresholdCost' => 3,
                'manaCost' => '1GU',
                'rarity' => 'Mythic Rare',
                'cardType' => 'Legendary Planeswalker — Oko',
                'description' => 'Target artifact or creature loses all abilities and becomes a green Elk creature.',
                'setCode' => 'ELD',
                'cardNumber' => '197',
                'marketPrice' => 25.00,
                'stockQuantity' => 4,
                'metadata' => [
                    'artist' => 'Yongjae Choi',
                    'loyalty' => 4,
                    'cmc' => 3
                ]
            ],
            // Budget/Common cards
            [
                'title' => 'Shock',
                'edition' => 'Core Set 2021',
                'thresholdCost' => 1,
                'manaCost' => 'R',
                'rarity' => 'Common',
                'cardType' => 'Instant',
                'description' => 'Shock deals 2 damage to any target.',
                'setCode' => 'M21',
                'cardNumber' => '159',
                'marketPrice' => 0.25,
                'stockQuantity' => 100,
                'metadata' => [
                    'artist' => 'Zoltan Boros',
                    'cmc' => 1
                ]
            ],
            [
                'title' => 'Llanowar Elves',
                'edition' => 'Core Set 2021',
                'thresholdCost' => 1,
                'manaCost' => 'G',
                'rarity' => 'Common',
                'cardType' => 'Creature — Elf Druid',
                'description' => 'Tap: Add G.',
                'setCode' => 'M21',
                'cardNumber' => '197',
                'marketPrice' => 0.50,
                'stockQuantity' => 80,
                'metadata' => [
                    'artist' => 'Kev Walker',
                    'power' => 1,
                    'toughness' => 1,
                    'cmc' => 1
                ]
            ],
            [
                'title' => 'Opt',
                'edition' => 'Core Set 2021',
                'thresholdCost' => 1,
                'manaCost' => 'U',
                'rarity' => 'Common',
                'cardType' => 'Instant',
                'description' => 'Scry 1. Draw a card.',
                'setCode' => 'M21',
                'cardNumber' => '59',
                'marketPrice' => 0.30,
                'stockQuantity' => 120,
                'metadata' => [
                    'artist' => 'Tyler Jacobson',
                    'cmc' => 1
                ]
            ],
            [
                'title' => 'Duress',
                'edition' => 'Core Set 2021',
                'thresholdCost' => 1,
                'manaCost' => 'B',
                'rarity' => 'Common',
                'cardType' => 'Sorcery',
                'description' => 'Target opponent reveals their hand. You choose a noncreature, nonland card from it.',
                'setCode' => 'M21',
                'cardNumber' => '96',
                'marketPrice' => 0.40,
                'stockQuantity' => 90,
                'metadata' => [
                    'artist' => 'Steven Belledin',
                    'cmc' => 1
                ]
            ]
        ];
    }
}
