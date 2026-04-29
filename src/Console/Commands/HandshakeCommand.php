<?php

declare(strict_types=1);

namespace GeekCo\CommerceJson\Console\Commands;

use GeekCo\CommerceJson\Console\Concerns\InteractsWithExchange;
use Illuminate\Console\Command;

/**
 * Команда: Проверка соединения с CommerceJSON API
 */
class HandshakeCommand extends Command
{
    use InteractsWithExchange;

    protected $signature = 'commercejson:handshake {--show-all : Показать всю информацию}';

    protected $description = 'Проверка соединения с CommerceJSON API (handshake)';

    public function handle(): int
    {
        $this->info('Checking CommerceJSON API connection...');

        return $this->withErrorHandling(function () {
            $response = $this->connector()->handshake();
            $data = json_decode($response->getBody()->getContents(), true);

            $this->newLine();
            $this->table(
                ['Parameter', 'Value'],
                [
                    ['Status', '<fg=green>✓ Connected</>'],
                    ['API Version', $data['version'] ?? 'N/A'],
                    ['Server Time', $data['server_time'] ?? 'N/A'],
                    ['Base URL', config('commercejson.base_url')],
                ]
            );

            if ($this->option('show-all')) {
                $this->showCapabilities($data['capabilities'] ?? []);
                $this->showSupportedVersions($data['supported_versions'] ?? []);
            } else {
                $this->showCapabilitiesSummary($data['capabilities'] ?? []);
            }

            $this->newLine();
            $this->info('Handshake completed successfully!');

            return 0;
        });
    }

    /**
     * Показать возможности сервера (полная информация)
     *
     * @param  array<string, bool>  $capabilities
     */
    protected function showCapabilities(array $capabilities): void
    {
        $this->newLine();
        $this->info('Server Capabilities:');

        $rows = [];
        foreach ($capabilities as $key => $value) {
            $status = $value ? '<fg=green>✓ Enabled</>' : '<fg=red>✗ Disabled</>';
            $rows[] = [ucwords(str_replace('_', ' ', $key)), $status];
        }

        $this->table(['Capability', 'Status'], $rows);
    }

    /**
     * Показать возможности сервера (кратко)
     *
     * @param  array<string, bool>  $capabilities
     */
    protected function showCapabilitiesSummary(array $capabilities): void
    {
        $enabled = array_filter($capabilities);
        $disabled = array_filter($capabilities, fn ($v) => ! $v);

        $this->newLine();
        $this->line(sprintf(
            'Capabilities: <fg=green>%d enabled</>, <fg=red>%d disabled</>',
            count($enabled),
            count($disabled)
        ));

        if (! empty($disabled)) {
            $this->warn('Disabled: '.implode(', ', array_keys($disabled)));
        }
    }

    /**
     * Показать поддерживаемые версии
     *
     * @param  array<int, string>  $versions
     */
    protected function showSupportedVersions(array $versions): void
    {
        $this->newLine();
        $this->info('Supported API Versions:');

        foreach ($versions as $version) {
            $current = $version === (config('commercejson.base_version') ?? '1.0.8')
                ? '<fg=green>●</> '
                : '  ';
            $this->line("{$current}{$version}");
        }
    }
}
