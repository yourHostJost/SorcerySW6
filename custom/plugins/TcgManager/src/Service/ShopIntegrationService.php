<?php declare(strict_types=1);

namespace TcgManager\Service;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ShopIntegrationService
{
    private EntityRepository $productRepository;
    private AbstractSalesChannelContextFactory $salesChannelContextFactory;
    private CartService $cartService;
    private CardService $cardService;

    public function __construct(
        EntityRepository $productRepository,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        CartService $cartService,
        CardService $cardService
    ) {
        $this->productRepository = $productRepository;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->cartService = $cartService;
        $this->cardService = $cardService;
    }

    /**
     * Add cards to cart based on missing cards from deck comparison
     */
    public function addMissingCardsToCart(
        array $missingCards,
        SalesChannelContext $salesChannelContext
    ): array {
        $addedItems = [];
        $errors = [];

        foreach ($missingCards as $missingCard) {
            try {
                $card = $missingCard['card'];
                $quantity = $missingCard['missing'];

                // Find corresponding product for this card
                $product = $this->findProductForCard($card->getId(), $salesChannelContext->getContext());

                if (!$product) {
                    $errors[] = "Kein Produkt für Karte '{$card->getTitle()}' gefunden";
                    continue;
                }

                // Check stock availability
                if ($product['stock'] < $quantity) {
                    $errors[] = "Nicht genügend Lagerbestand für '{$card->getTitle()}' (benötigt: {$quantity}, verfügbar: {$product['stock']})";
                    continue;
                }

                // Add to cart
                $lineItem = [
                    'id' => $product['id'],
                    'type' => 'product',
                    'referencedId' => $product['id'],
                    'quantity' => $quantity,
                ];

                // This is a simplified version - in a real implementation,
                // you would use the proper CartService methods
                $addedItems[] = [
                    'cardId' => $card->getId(),
                    'cardTitle' => $card->getTitle(),
                    'productId' => $product['id'],
                    'quantity' => $quantity,
                    'price' => $product['price'],
                ];

            } catch (\Exception $e) {
                $errors[] = "Fehler beim Hinzufügen von '{$card->getTitle()}': " . $e->getMessage();
            }
        }

        return [
            'addedItems' => $addedItems,
            'errors' => $errors,
            'totalItems' => count($addedItems),
            'totalErrors' => count($errors),
        ];
    }

    /**
     * Find product that corresponds to a card
     */
    private function findProductForCard(string $cardId, Context $context): ?array
    {
        // This is a placeholder implementation
        // In a real scenario, you would have a mapping between cards and products
        // For now, we'll return a mock product structure

        return [
            'id' => 'mock-product-id-' . $cardId,
            'stock' => 10,
            'price' => 1.99,
        ];
    }

    /**
     * Get available products for cards
     */
    public function getAvailableProductsForCards(array $cardIds, Context $context): array
    {
        $products = [];

        foreach ($cardIds as $cardId) {
            $product = $this->findProductForCard($cardId, $context);
            if ($product) {
                $products[$cardId] = $product;
            }
        }

        return $products;
    }

    /**
     * Calculate total price for missing cards
     */
    public function calculateMissingCardsPrice(array $missingCards, Context $context): array
    {
        $totalPrice = 0.0;
        $availableItems = 0;
        $unavailableItems = 0;

        foreach ($missingCards as $missingCard) {
            $card = $missingCard['card'];
            $quantity = $missingCard['missing'];

            $product = $this->findProductForCard($card->getId(), $context);

            if ($product && $product['stock'] >= $quantity) {
                $totalPrice += $product['price'] * $quantity;
                $availableItems++;
            } else {
                $unavailableItems++;
            }
        }

        return [
            'totalPrice' => $totalPrice,
            'availableItems' => $availableItems,
            'unavailableItems' => $unavailableItems,
            'canAddAllToCart' => $unavailableItems === 0,
        ];
    }
}
