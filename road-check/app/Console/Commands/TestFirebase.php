<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Firebase\Firestore;

class TestFirebase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Firebase Firestore connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Firebase connection...');

        try {
            $firestore = app(Firestore::class);

            if (!$firestore) {
                $this->error('Firestore is NULL - Configuration issue');
                $this->warn('Check:');
                $this->warn('1. Firebase credentials file exists');
                $this->warn('2. Firestore is enabled in Firebase project');
                $this->warn('3. Service account has proper permissions');
                return 1;
            }

            $this->info('✓ Firestore initialized successfully');

            $database = $firestore->database();
            $this->info('✓ Database connection established');

            // Test simple collection access
            $collections = $database->collections();
            $this->info('✓ Collections accessible');

            // Test write operation
            $testData = [
                'test' => 'connection_ok',
                'timestamp' => now()->toIso8601String(),
                'source' => 'laravel_test'
            ];

            $collection = $database->collection('test_connection');
            $doc = $collection->add($testData);

            $this->info('✓ Test write successful - Document ID: ' . $doc->id());

            $this->info('🎉 Firebase Firestore is working correctly!');

        } catch (\Exception $e) {
            $this->error('❌ Firebase connection failed: ' . $e->getMessage());

            if (str_contains($e->getMessage(), 'credentials')) {
                $this->warn('Issue: Credentials file not found or invalid');
            } elseif (str_contains($e->getMessage(), 'permission')) {
                $this->warn('Issue: Service account permissions');
            } elseif (str_contains($e->getMessage(), 'project')) {
                $this->warn('Issue: Firebase project configuration');
            }

            return 1;
        }

        return 0;
    }
}
