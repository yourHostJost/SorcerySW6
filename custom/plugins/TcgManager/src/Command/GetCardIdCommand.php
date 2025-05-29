<?php declare(strict_types=1);

namespace TcgManager\Command;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'tcg:get-card-id',
    description: 'Get card ID by name'
)]
class GetCardIdCommand extends Command
{
    private EntityRepository $cardRepository;

    public function __construct(EntityRepository $cardRepository)
    {
        parent::__construct();
        $this->cardRepository = $cardRepository;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Card name to search for');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createDefaultContext();
        $cardName = $input->getArgument('name');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('title', $cardName));
        $criteria->setLimit(1);

        $result = $this->cardRepository->search($criteria, $context);
        $card = $result->first();

        if ($card) {
            $io->success("Card found!");
            $io->table(
                ['Property', 'Value'],
                [
                    ['ID', $card->getId()],
                    ['Title', $card->getTitle()],
                    ['Product ID', $card->getShopwareProductId() ?? 'None'],
                    ['Edition', $card->getEdition()],
                    ['Rarity', $card->getRarity()]
                ]
            );
            
            $io->text("Detail URL: http://localhost/tcg/shop/card/{$card->getId()}");
        } else {
            $io->error("Card '{$cardName}' not found");
        }

        return Command::SUCCESS;
    }
}
