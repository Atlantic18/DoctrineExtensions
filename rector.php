<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::ENABLE_CACHE, true);
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_72);
    $parameters->set(Option::PATHS, [__DIR__ . '/src', __DIR__ . '/tests']);

    $parameters->set(Option::SKIP, [
        'tests/temp/*',
        \Rector\Php70\Rector\Ternary\TernaryToNullCoalescingRector::class,
        \Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector::class,
        \Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector::class,
        \Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class,
        \Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector::class,
        \Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector::class,
    ]);

    $containerConfigurator->import(SetList::PHP_70);
    $containerConfigurator->import(SetList::PHP_71);
    $containerConfigurator->import(SetList::PHP_72);
    $containerConfigurator->import(SetList::CODE_QUALITY);
};
