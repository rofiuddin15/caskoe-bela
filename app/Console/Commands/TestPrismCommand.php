<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use EchoLabs\Prism\Prism;

class TestPrismCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test-prism';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Laravel Prism integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Laravel Prism...');
        $this->newLine();

        try {
            $provider = config('prism.default_provider', 'openai');
            $model = config('prism.providers.openai.model', 'gpt-4o-mini');

            $this->info("Provider: {$provider}");
            $this->info("Model: {$model}");
            $this->newLine();

            $this->info('Sending test prompt...');

            $response = Prism::text()
                ->using($provider, $model)
                ->withPrompt('Halo! Sebutkan 3 menu kopi yang populer di Indonesia dalam format JSON dengan struktur: {"menus": [{"name": "...", "description": "..."}]}')
                ->withMaxTokens(300)
                ->generate();

            $this->info('Response received!');
            $this->newLine();
            $this->line($response->text);
            $this->newLine();

            // Try parsing JSON
            $result = json_decode($response->text, true);
            if ($result) {
                $this->info('✅ JSON parsing successful!');
            } else {
                $this->warn('⚠️  JSON parsing failed, but response received');
            }

            $this->newLine();
            $this->info('🎉 Laravel Prism is working correctly!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}
