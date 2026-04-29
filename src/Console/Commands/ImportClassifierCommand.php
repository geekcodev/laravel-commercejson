<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use GeekCo\CommerceJson\Events\ClassifierImported;
use GeekCo\CommerceJson\Services\ClassifierService;
use Illuminate\Console\Command;

/**
 * Команда: Импорт классификатора
 */
class ImportClassifierCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:import-classifier
                            {--queue : Использовать очередь}
                            {--no-sync : Не синхронизировать с БД, только получить данные}';

    protected $description = 'Импортировать классификатор (категории, свойства, типы цен)';

    public function handle(ClassifierService $classifierService): int
    {
        $this->info('Starting classifier import...');

        return $this->withErrorHandling(function () use ($classifierService) {
            // Проверка соединения
            if (! $this->checkConnection()) {
                return 1;
            }

            // Получение классификатора
            $this->newLine();
            $this->info('Fetching classifier from API...');

            $classifier = $classifierService->getClassifier();

            $categoriesCount = count($classifier->categories ?? []);
            $propertiesCount = count($classifier->properties ?? []);
            $priceTypesCount = count($classifier->priceTypes ?? []);

            $this->line("Received: {$categoriesCount} categories, {$propertiesCount} properties, {$priceTypesCount} price types");

            if ($this->option('no-sync')) {
                $this->warn('Skipping database sync (--no-sync flag)');

                return 0;
            }

            // Синхронизация с БД
            $this->newLine();
            $this->info('Synchronizing with database...');

            $stats = [
                'categories' => 0,
                'properties' => 0,
                'priceTypes' => 0,
            ];

            // Категории
            if (! empty($classifier->categories)) {
                $this->info('Syncing categories...');
                $stats['categories'] = $classifierService->syncCategories($classifier->categories);
                $this->line("  → {$stats['categories']} categories synced");
            }

            // Свойства
            if (! empty($classifier->properties)) {
                $this->info('Syncing properties...');
                $stats['properties'] = $classifierService->syncProperties($classifier->properties);
                $this->line("  → {$stats['properties']} properties synced");
            }

            // Типы цен
            if (! empty($classifier->priceTypes)) {
                $this->info('Syncing price types...');
                $stats['priceTypes'] = $classifierService->syncPriceTypes($classifier->priceTypes);
                $this->line("  → {$stats['priceTypes']} price types synced");
            }

            // Итоговая таблица
            $this->newLine();
            $this->table(
                ['Entity', 'Count'],
                [
                    ['Categories', $stats['categories']],
                    ['Properties', $stats['properties']],
                    ['Price Types', $stats['priceTypes']],
                    ['Total', array_sum($stats)],
                ]
            );

            // Dispatch event
            event(new ClassifierImported(
                $stats['categories'],
                $stats['properties'],
                $stats['priceTypes']
            ));

            $this->newLine();
            $this->info('<fg=green>✓ Classifier import completed successfully!</>');

            return 0;
        });
    }
}
