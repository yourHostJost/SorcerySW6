<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TcgManager\Service\CollectionService;

#[AsCommand(
    name: 'tcg:create:test-data',
    description: 'Create test customer and collections with Sorcery cards'
)]
class CreateTestDataCommand extends Command
{
    private EntityRepository $customerRepository;
    private EntityRepository $cardRepository;
    private CollectionService $collectionService;

    public function __construct(
        EntityRepository $customerRepository,
        EntityRepository $cardRepository,
        CollectionService $collectionService
    ) {
        parent::__construct();
        $this->customerRepository = $customerRepository;
        $this->cardRepository = $cardRepository;
        $this->collectionService = $collectionService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('🧪 Creating Test Data for TCG Manager');

        // Step 1: Find or create test customer
        $io->section('Step 1: Test Customer');
        $customer = $this->findOrCreateTestCustomer($context);
        $io->success("Test customer ready: {$customer['email']}");

        // Step 2: Get some Sorcery cards
        $io->section('Step 2: Getting Sorcery Cards');
        $sorceryCards = $this->getSorceryCards($context);
        $io->info("Found {$sorceryCards->count()} Sorcery cards");

        // Step 3: Create test collections
        $io->section('Step 3: Creating Test Collections');
        $this->createTestCollections($customer['id'], $sorceryCards, $context, $io);

        $io->success('🎉 Test data created successfully!');
        $io->note('You can now test the frontend with real data:');
        $io->listing([
            'Login with: test@tcg-manager.local / password123',
            'Visit: http://localhost:8000/account/tcg/collections',
            'View collections with real Sorcery cards'
        ]);

        return Command::SUCCESS;
    }

    private function findOrCreateTestCustomer(Context $context): array
    {
        $email = 'test@tcg-manager.local';
        
        // Try to find existing customer
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        
        $result = $this->customerRepository->search($criteria, $context);
        
        if ($result->count() > 0) {
            return [
                'id' => $result->first()->getId(),
                'email' => $email,
                'existing' => true
            ];
        }

        // Create new test customer
        $customerId = Uuid::randomHex();
        $customerData = [
            'id' => $customerId,
            'email' => $email,
            'firstName' => 'TCG',
            'lastName' => 'Tester',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'customerNumber' => 'TCG-TEST-001',
            'salutationId' => $this->getDefaultSalutationId($context),
            'defaultPaymentMethodId' => $this->getDefaultPaymentMethodId($context),
            'groupId' => $this->getDefaultCustomerGroupId($context),
            'salesChannelId' => $this->getDefaultSalesChannelId($context),
            'defaultBillingAddressId' => Uuid::randomHex(),
            'defaultShippingAddressId' => Uuid::randomHex(),
            'addresses' => [
                [
                    'id' => Uuid::randomHex(),
                    'firstName' => 'TCG',
                    'lastName' => 'Tester',
                    'street' => 'Test Street 123',
                    'zipcode' => '12345',
                    'city' => 'Test City',
                    'countryId' => $this->getDefaultCountryId($context),
                ]
            ]
        ];

        $this->customerRepository->create([$customerData], $context);

        return [
            'id' => $customerId,
            'email' => $email,
            'existing' => false
        ];
    }

    private function getSorceryCards(Context $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('apiSource', 'sorcery'));
        $criteria->addSorting(new FieldSorting('title', FieldSorting::ASCENDING));
        $criteria->setLimit(50); // Get first 50 cards for testing

        return $this->cardRepository->search($criteria, $context)->getEntities();
    }

    private function createTestCollections(string $customerId, $cards, Context $context, SymfonyStyle $io): void
    {
        // Collection 1: Alpha Cards
        $alphaCards = $cards->filter(fn($card) => $card->getEdition() === 'Alpha');
        if ($alphaCards->count() > 0) {
            $collection1 = $this->collectionService->createCollection(
                $customerId,
                'Alpha Collection',
                'My collection of Alpha edition cards',
                false,
                false,
                $context
            );
            $io->info("Created Alpha Collection with {$alphaCards->count()} cards");
        }

        // Collection 2: Arthurian Legends
        $arthurianCards = $cards->filter(fn($card) => $card->getEdition() === 'Arthurian Legends');
        if ($arthurianCards->count() > 0) {
            $collection2 = $this->collectionService->createCollection(
                $customerId,
                'Arthurian Legends',
                'Knights of the Round Table and legendary creatures',
                true,
                false,
                $context
            );
            $io->info("Created Arthurian Legends Collection with {$arthurianCards->count()} cards");
        }

        // Collection 3: High-Cost Cards
        $expensiveCards = $cards->filter(fn($card) => $card->getCost() && $card->getCost() >= 7);
        if ($expensiveCards->count() > 0) {
            $collection3 = $this->collectionService->createCollection(
                $customerId,
                'High-Cost Powerhouses',
                'Expensive but powerful cards for late game',
                true,
                false,
                $context
            );
            $io->info("Created High-Cost Collection with {$expensiveCards->count()} cards");
        }
    }

    // Helper methods to get default IDs (simplified)
    private function getDefaultSalutationId(Context $context): string
    {
        return Uuid::randomHex(); // In real implementation, fetch from database
    }

    private function getDefaultPaymentMethodId(Context $context): string
    {
        return Uuid::randomHex(); // In real implementation, fetch from database
    }

    private function getDefaultCustomerGroupId(Context $context): string
    {
        return Uuid::randomHex(); // In real implementation, fetch from database
    }

    private function getDefaultSalesChannelId(Context $context): string
    {
        return Uuid::randomHex(); // In real implementation, fetch from database
    }

    private function getDefaultCountryId(Context $context): string
    {
        return Uuid::randomHex(); // In real implementation, fetch from database
    }
}
