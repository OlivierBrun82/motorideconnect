<?php

namespace App\Command;

use App\Entity\Brand;
use App\Repository\BrandRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import-brands',
    description: 'Importe les marques de moto depuis data/brands.csv',
)]
class ImportBrandsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private BrandRepository $brandRepository,
        // Chemin racine du projet, injecté automatiquement
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $csvPath = $this->projectDir . '/data/brands.csv';

        // 1. Vérifie que le fichier existe
        if (!file_exists($csvPath)) {
            $io->error("Fichier introuvable : $csvPath");

            return Command::FAILURE;
        }

        // 2. Charge les noms déjà en base pour éviter les doublons (1 seule requête)
        $existing = [];
        foreach ($this->brandRepository->findAll() as $brand) {
            $existing[$brand->getName()] = true;
        }

        // 3. Ouvre le CSV et parcourt chaque ligne
        $handle = fopen($csvPath, 'r');
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            // Chaque ligne = un nom de marque dans la 1re colonne
            $name = trim($row[0] ?? '');

            // Ignore les lignes vides
            if ($name === '') {
                continue;
            }

            // Ignore les doublons (déjà en base ou déjà vus dans ce fichier)
            if (isset($existing[$name])) {
                $skipped++;
                continue;
            }

            $brand = new Brand();
            $brand->setName($name);
            $this->em->persist($brand);

            $existing[$name] = true;
            $created++;
        }

        fclose($handle);

        // 4. Un seul flush à la fin (performant pour beaucoup de lignes)
        $this->em->flush();

        $io->success("$created marque(s) importée(s), $skipped ignorée(s).");

        return Command::SUCCESS;
    }
}
