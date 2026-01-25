<?php

declare(strict_types=1);

use Illuminate\Validation\Rule;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/examples',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withTypeCoverageLevel(37)
    ->withDeadCodeLevel(50)
    ->withCodeQualityLevel(71)
    ->withImportNames(
        importShortClasses: false,
        removeUnusedImports: true,
    )
    ->withRules([
        DeclareStrictTypesRector::class,
    ])
    ->withParallel(
        timeoutSeconds: 120,
        maxNumberOfProcess: 16,
        jobSize: 16,
    )
    ->withCache(__DIR__ . '/.rector-cache');
