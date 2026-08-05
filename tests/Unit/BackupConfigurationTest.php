<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\File;
use Spatie\Backup\Tasks\Backup\FileSelection;
use Tests\TestCase;

class BackupConfigurationTest extends TestCase
{
    public function test_backup_archives_exclude_deployment_secrets(): void
    {
        $excludedPaths = config('backup.backup.source.files.exclude');

        $this->assertContains(base_path('.env'), $excludedPaths);
        $this->assertContains(base_path('.env.*'), $excludedPaths);
        $this->assertContains(base_path('auth.json'), $excludedPaths);
        $this->assertContains(base_path('bootstrap/cache'), $excludedPaths);
        $this->assertContains(base_path('.git'), $excludedPaths);

        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'rams-backup-'.uniqid('', true);
        File::ensureDirectoryExists($directory);

        try {
            File::put($directory.'/.env', 'secret');
            File::put($directory.'/.env.production', 'secret');
            File::put($directory.'/auth.json', 'secret');
            File::ensureDirectoryExists($directory.'/bootstrap/cache');
            File::put($directory.'/bootstrap/cache/config.php', 'secret');
            File::ensureDirectoryExists($directory.'/.git');
            File::put($directory.'/.git/config', 'secret');
            File::put($directory.'/keep.txt', 'safe');

            $temporaryExclusions = array_map(
                fn (string $path): string => str_replace(base_path(), $directory, $path),
                $excludedPaths,
            );

            $selectedFiles = iterator_to_array(
                FileSelection::create($directory)
                    ->excludeFilesFrom($temporaryExclusions)
                    ->selectedFiles(),
            );

            $this->assertContains($directory.DIRECTORY_SEPARATOR.'keep.txt', $selectedFiles);
            $this->assertNotContains($directory.DIRECTORY_SEPARATOR.'.env', $selectedFiles);
            $this->assertNotContains($directory.DIRECTORY_SEPARATOR.'.env.production', $selectedFiles);
            $this->assertNotContains($directory.DIRECTORY_SEPARATOR.'auth.json', $selectedFiles);
            $this->assertNotContains($directory.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'config.php', $selectedFiles);
            $this->assertNotContains($directory.DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR.'config', $selectedFiles);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
