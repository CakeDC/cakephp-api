<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\Config\RectorConfig;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveNullTagValueNodeRector;
use Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector;
use Rector\DeadCode\Rector\For_\RemoveDeadLoopRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveDuplicatedReturnSelfDocblockRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveMixedDocblockOverruledByNativeTypeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveReturnTagIncompatibleWithNativeTypeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Rector\TypeDeclaration\Rector\FuncCall\AddArrayFunctionClosureParamTypeRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayMapRector;
use Rector\TypeDeclaration\Rector\FunctionLike\AddClosureParamTypeForArrayReduceRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        ReadOnlyPropertyRector::class,
        DisallowedEmptyRuleFixerRector::class,
        ClassPropertyAssignToConstructorPromotionRector::class,
        AddClosureParamTypeForArrayMapRector::class,
        AddClosureParamTypeForArrayReduceRector::class,
        AddArrayFunctionClosureParamTypeRector::class,
        RemoveDeadLoopRector::class,
        RemoveDeadIfForeachForRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveUselessParamTagRector::class,
        RemoveUselessVarTagRector::class,
        RemoveUselessUnionReturnDocblockRector::class,
        RemoveDuplicatedReturnSelfDocblockRector::class,
        RemoveMixedDocblockOverruledByNativeTypeRector::class,
        RemoveReturnTagIncompatibleWithNativeTypeRector::class,
        // conflicts with phpcs: cs requires a @return tag, this rule removes @return null
        RemoveNullTagValueNodeRector::class,
        ReturnTypeFromStrictFluentReturnRector::class,
        ReturnNeverTypeRector::class,
        SafeDeclareStrictTypesRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
        IfIssetToCoalescingRector::class,
        // conflicts with phpcbf on Windows: blank-line churn (144 files in dry-run)
        NewlineAfterStatementRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withPhpSets(php81: true);
