<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tcg:test-media-urls',
    description: 'Test media URLs for TCG products'
)]
class TestMediaUrlsCommand extends Command
{
    private EntityRepository $cardRepository;
    private EntityRepository $productRepository;

    public function __construct(
        EntityRepository $cardRepository,
        EntityRepository $productRepository
    ) {
        parent::__construct();
        $this->cardRepository = $cardRepository;
        $this->productRepository = $productRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();

        $io->title('TCG Media URLs Test');

        // Get cards with products
        $criteria = new Criteria();
        $criteria->setLimit(3);
        $criteria->addFilter(new NotFilter(
            NotFilter::CONNECTION_AND,
            [new EqualsFilter('shopwareProductId', null)]
        ));

        $cards = $this->cardRepository->search($criteria, $context);

        foreach ($cards as $card) {
            $io->section("Card: {$card->getTitle()}");
            $io->text("Product ID: {$card->getShopwareProductId()}");

            if ($card->getShopwareProductId()) {
                $productCriteria = new Criteria([$card->getShopwareProductId()]);
                $productCriteria->addAssociation('media.media');
                $productResult = $this->productRepository->search($productCriteria, $context);
                $product = $productResult->first();

                if ($product && $product->getMedia()) {
                    $io->text("Found {$product->getMedia()->count()} media items:");
                    
                    foreach ($product->getMedia() as $media) {
                        $mediaEntity = $media->getMedia();
                        if ($mediaEntity) {
                            $finishCode = $media->getCustomFields()['tcg_finish_code'] ?? 'unknown';
                            $finishName = $media->getCustomFields()['tcg_finish_name'] ?? 'Unknown';
                            
                            $io->text("  - {$finishCode} ({$finishName}): {$mediaEntity->getUrl()}");
                        }
                    }
                } else {
                    $io->warning("No media found for product");
                }
            }
            
            $io->newLine();
        }

        return Command::SUCCESS;
    }
}
