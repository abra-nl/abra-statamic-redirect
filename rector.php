<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    // Define paths to process
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])

    // Skip these paths
    ->withSkip([
        __DIR__.'/vendor',
        __DIR__.'/node_modules',
        __DIR__.'/coverage-html',
    ])

    // Apply rule sets
    ->withSets([
        // PHP version sets
        LevelSetList::UP_TO_PHP_81,

        // General code quality sets
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ])

    // Enable parallel processing
    ->withParallel()

    // Import short classes
    ->withImportNames();
