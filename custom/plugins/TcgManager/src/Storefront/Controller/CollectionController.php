<?php declare(strict_types=1);

namespace TcgManager\Storefront\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use TcgManager\Service\CollectionService;
use TcgManager\Service\CardService;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CollectionController extends StorefrontController
{
    private CollectionService $collectionService;
    private CardService $cardService;

    public function __construct(
        CollectionService $collectionService,
        CardService $cardService
    ) {
        $this->collectionService = $collectionService;
        $this->cardService = $cardService;
    }

    /**
     * @Route("/account/tcg/collections", name="frontend.account.tcg.collections", methods={"GET"})
     */
    public function collectionsPage(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        
        $customerId = $context->getCustomer()->getId();
        $collections = $this->collectionService->getCustomerCollections($customerId, $context->getContext());
        
        return $this->renderStorefront('@TcgManager/storefront/page/account/collections.html.twig', [
            'collections' => $collections,
            'page' => [
                'title' => 'Meine Kartensammlungen'
            ]
        ]);
    }

    /**
     * @Route("/account/tcg/collection/{collectionId}", name="frontend.account.tcg.collection.detail", methods={"GET"})
     */
    public function collectionDetail(string $collectionId, Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        
        // TODO: Add security check to ensure customer owns this collection
        
        return $this->renderStorefront('@TcgManager/storefront/page/account/collection-detail.html.twig', [
            'collectionId' => $collectionId,
            'page' => [
                'title' => 'Kartensammlung Details'
            ]
        ]);
    }

    /**
     * @Route("/api/tcg/collections", name="api.tcg.collections.list", methods={"GET"})
     */
    public function getCollections(Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn();
        
        $customerId = $context->getCustomer()->getId();
        $collections = $this->collectionService->getCustomerCollections($customerId, $context->getContext());
        
        $collectionsData = [];
        foreach ($collections as $collection) {
            $collectionsData[] = [
                'id' => $collection->getId(),
                'name' => $collection->getName(),
                'description' => $collection->getDescription(),
                'isPublic' => $collection->getIsPublic(),
                'isDefault' => $collection->getIsDefault(),
                'createdAt' => $collection->getCreatedAt()->format('Y-m-d H:i:s'),
                'cardCount' => $collection->getCollectionCards() ? $collection->getCollectionCards()->count() : 0,
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'data' => $collectionsData
        ]);
    }

    /**
     * @Route("/api/tcg/collections", name="api.tcg.collections.create", methods={"POST"})
     */
    public function createCollection(Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn();
        
        $data = json_decode($request->getContent(), true);
        $customerId = $context->getCustomer()->getId();
        
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;
        $isPublic = $data['isPublic'] ?? false;
        $isDefault = $data['isDefault'] ?? false;
        
        if (empty($name)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Name ist erforderlich'
            ], 400);
        }
        
        try {
            $collectionId = $this->collectionService->createCollection(
                $customerId,
                $name,
                $description,
                $isPublic,
                $isDefault,
                $context->getContext()
            );
            
            return new JsonResponse([
                'success' => true,
                'data' => ['id' => $collectionId],
                'message' => 'Sammlung erfolgreich erstellt'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Erstellen der Sammlung: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/api/tcg/collections/{collectionId}/cards", name="api.tcg.collections.add_card", methods={"POST"})
     */
    public function addCardToCollection(string $collectionId, Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn();
        
        $data = json_decode($request->getContent(), true);
        
        $cardId = $data['cardId'] ?? '';
        $quantity = $data['quantity'] ?? 1;
        $condition = $data['condition'] ?? null;
        $language = $data['language'] ?? 'en';
        $foilType = $data['foilType'] ?? 'normal';
        
        if (empty($cardId)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Karten-ID ist erforderlich'
            ], 400);
        }
        
        try {
            $collectionCardId = $this->collectionService->addCardToCollection(
                $collectionId,
                $cardId,
                $quantity,
                $condition,
                $language,
                $foilType,
                $context->getContext()
            );
            
            return new JsonResponse([
                'success' => true,
                'data' => ['id' => $collectionCardId],
                'message' => 'Karte erfolgreich zur Sammlung hinzugefügt'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Hinzufügen der Karte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/api/tcg/collections/{collectionId}/cards/{cardId}", name="api.tcg.collections.remove_card", methods={"DELETE"})
     */
    public function removeCardFromCollection(string $collectionId, string $cardId, Request $request, SalesChannelContext $context): JsonResponse
    {
        $this->denyAccessUnlessLoggedIn();
        
        $data = json_decode($request->getContent(), true);
        
        $quantity = $data['quantity'] ?? 1;
        $condition = $data['condition'] ?? null;
        $language = $data['language'] ?? 'en';
        $foilType = $data['foilType'] ?? 'normal';
        
        try {
            $success = $this->collectionService->removeCardFromCollection(
                $collectionId,
                $cardId,
                $quantity,
                $condition,
                $language,
                $foilType,
                $context->getContext()
            );
            
            if ($success) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Karte erfolgreich aus der Sammlung entfernt'
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Karte nicht in der Sammlung gefunden'
                ], 404);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Entfernen der Karte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/api/tcg/cards/search", name="api.tcg.cards.search", methods={"GET"})
     */
    public function searchCards(Request $request, SalesChannelContext $context): JsonResponse
    {
        $searchTerm = $request->query->get('q');
        $edition = $request->query->get('edition');
        $rarity = $request->query->get('rarity');
        $cardType = $request->query->get('type');
        $minCost = $request->query->getInt('minCost');
        $maxCost = $request->query->getInt('maxCost');
        $minPrice = $request->query->get('minPrice') ? (float) $request->query->get('minPrice') : null;
        $maxPrice = $request->query->get('maxPrice') ? (float) $request->query->get('maxPrice') : null;
        $inStockOnly = $request->query->getBoolean('inStock', false);
        $limit = $request->query->getInt('limit', 20);
        $offset = $request->query->getInt('offset', 0);
        
        $cards = $this->cardService->searchCards(
            $searchTerm,
            $edition,
            $rarity,
            $cardType,
            $minCost ?: null,
            $maxCost ?: null,
            $minPrice,
            $maxPrice,
            $inStockOnly,
            $limit,
            $offset,
            $context->getContext()
        );
        
        $cardsData = [];
        foreach ($cards as $card) {
            $cardsData[] = [
                'id' => $card->getId(),
                'title' => $card->getTitle(),
                'edition' => $card->getEdition(),
                'thresholdCost' => $card->getThresholdCost(),
                'manaCost' => $card->getManaCost(),
                'rarity' => $card->getRarity(),
                'cardType' => $card->getCardType(),
                'description' => $card->getDescription(),
                'imageUrl' => $card->getImageUrl(),
                'marketPrice' => $card->getMarketPrice(),
                'stockQuantity' => $card->getStockQuantity(),
                'setCode' => $card->getSetCode(),
                'cardNumber' => $card->getCardNumber(),
            ];
        }
        
        return new JsonResponse([
            'success' => true,
            'data' => $cardsData,
            'meta' => [
                'total' => count($cardsData),
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);
    }
}
