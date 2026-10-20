<?php

declare(strict_types=1);

namespace Tests\PHPStanRules;

use App\Exceptions\ApiException;
use App\Providers\ModuleServiceProvider;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Spatie\LaravelData\Data;

/**
 * Структура модуля (ARCHITECTURE.md): имя модуля в единственном числе, только известные папки,
 * у класса в папке слоя — суффикс и база этого слоя.
 *
 * @implements Rule<InClassNode>
 */
final class ModuleStructureRule implements Rule
{
    private const array SUFFIXES = [
        'Actions'           => 'Action',
        'Tasks'             => 'Task',
        'Repositories'      => 'Repository',
        'DTO'               => 'DTO',
        'Events'            => 'Event',
        'Exceptions'        => 'Exception',
        'Http\\Controllers' => 'Controller',
        'Http\\Requests'    => 'Request',
        'Http\\Resources'   => 'Resource',
        'Providers'         => 'ServiceProvider',
        'Services'          => 'Service',
        'Console'           => 'Command',
    ];

    private const array PARENTS = [
        'Repositories'    => BaseRepository::class,
        'DTO'             => Data::class,
        'Models'          => Model::class,
        'Exceptions'      => ApiException::class,
        'Http\\Requests'  => FormRequest::class,
        'Http\\Resources' => JsonResource::class,
        'Providers'       => ModuleServiceProvider::class,
    ];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $location = ModuleClass::parse($class->getName());

        // Миграции — анонимные классы; тесты и Database живут по правилам Laravel
        if ($location === null || $class->isAnonymous() || \in_array($location->layer, ['Database', 'Tests'], true)) {
            return [];
        }

        $short = $class->getNativeReflection()->getShortName();
        $layer = $location->layer;
        $errors = [];

        if (str_ends_with($location->module, 's')) {
            $errors[] = $this->error("Модуль {$location->module}: имя модуля — в единственном числе.", 'module.singular');
        }

        if (!\in_array($layer, ModuleClass::LAYERS, true)) {
            return [...$errors, $this->error("{$short}: папки {$layer} нет в структуре модуля (ARCHITECTURE.md).", 'module.unknownFolder')];
        }

        $suffix = self::SUFFIXES[$layer] ?? null;

        if ($suffix !== null && !str_ends_with($short, $suffix)) {
            $errors[] = $this->error("{$short} в папке {$layer}: имя должно кончаться на {$suffix}.", 'module.suffix');
        }

        $parent = self::PARENTS[$layer] ?? null;

        if ($parent !== null && !$class->isSubclassOf($parent)) {
            $errors[] = $this->error("{$short} в папке {$layer} должен наследовать {$parent}.", 'module.parent');
        }

        if ($layer === 'Enums' && !$class->isEnum()) {
            $errors[] = $this->error("{$short}: в папке Enums — только enum.", 'module.enum');
        }

        // Контракт — интерфейс, а данные на входе и выходе — DTO рядом с ним
        if ($layer === 'Contracts' && !$class->isInterface() && !$class->isSubclassOf(Data::class)) {
            $errors[] = $this->error("{$short}: в Contracts — только интерфейсы и их DTO (extends Data).", 'module.contract');
        }

        return $errors;
    }

    private function error(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier('school.' . $identifier)->build();
    }
}
