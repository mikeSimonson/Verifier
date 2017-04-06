<?php

namespace Arachne\Verifier\DI;

use Arachne\ServiceCollections\DI\ServiceCollectionsExtension;
use Arachne\Verifier\Annotations\AnnotationsRuleProvider;
use Arachne\Verifier\ChainRuleProvider;
use Arachne\Verifier\RuleHandlerInterface;
use Arachne\Verifier\RuleProviderInterface;
use Arachne\Verifier\Rules\All;
use Arachne\Verifier\Rules\AllRuleHandler;
use Arachne\Verifier\Rules\Either;
use Arachne\Verifier\Rules\EitherRuleHandler;
use Arachne\Verifier\Verifier;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\ILatteFactory;
use Nette\DI\CompilerExtension;
use Nette\Utils\AssertionException;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class VerifierExtension extends CompilerExtension
{
    const TAG_HANDLER = 'arachne.verifier.ruleHandler';
    const TAG_PROVIDER = 'arachne.verifier.ruleProvider';
    const TAG_VERIFY_PROPERTIES = 'arachne.verifier.verifyProperties';

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        /** @var ServiceCollectionsExtension $serviceCollectionsExtension */
        $serviceCollectionsExtension = $this->getExtension(ServiceCollectionsExtension::class);

        $handlerResolver = $serviceCollectionsExtension->getCollection(
            ServiceCollectionsExtension::TYPE_RESOLVER,
            self::TAG_HANDLER,
            RuleHandlerInterface::class
        );

        $providerIterator = $serviceCollectionsExtension->getCollection(
            ServiceCollectionsExtension::TYPE_ITERATOR,
            self::TAG_PROVIDER,
            RuleProviderInterface::class
        );

        $builder->addDefinition($this->prefix('chainRuleProvider'))
            ->setClass(ChainRuleProvider::class)
            ->setArguments(
                [
                    'providers' => '@'.$providerIterator,
                ]
            );

        $builder->addDefinition($this->prefix('verifier'))
            ->setClass(Verifier::class)
            ->setArguments(
                [
                    'handlerResolver' => '@'.$handlerResolver,
                ]
            );

        $builder->addDefinition($this->prefix('annotationsRuleProvider'))
            ->setClass(RuleProviderInterface::class)
            ->setFactory(AnnotationsRuleProvider::class)
            ->addTag(self::TAG_PROVIDER)
            ->setAutowired(false);

        $builder->addDefinition($this->prefix('allRuleHandler'))
            ->setClass(AllRuleHandler::class)
            ->addTag(
                self::TAG_HANDLER,
                [
                    All::class,
                ]
            );

        $builder->addDefinition($this->prefix('eitherRuleHandler'))
            ->setClass(EitherRuleHandler::class)
            ->addTag(
                self::TAG_HANDLER,
                [
                    Either::class,
                ]
            );
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $latte = $builder->getByType(ILatteFactory::class);
        if ($builder->hasDefinition($latte)) {
            $builder->getDefinition($latte)
                ->addSetup(
                    '?->onCompile[] = function ($engine) { \Arachne\Verifier\Latte\VerifierMacros::install($engine->getCompiler()); }',
                    ['@self']
                );
        }

        foreach ($builder->findByTag(self::TAG_VERIFY_PROPERTIES) as $service => $attributes) {
            $definition = $builder->getDefinition($service);
            if (is_subclass_of($definition->getClass(), Presenter::class)) {
                $definition->addSetup(
                    '$service->onStartup[] = function () use ($service) { ?->verifyProperties($service->getRequest(), $service); }',
                    ['@'.Verifier::class]
                );
            } else {
                $definition->addSetup(
                    '$service->onPresenter[] = function (\Nette\Application\UI\Presenter $presenter) use ($service) { ?->verifyProperties($presenter->getRequest(), $service); }',
                    ['@'.Verifier::class]
                );
            }
        }
    }

    /**
     * @param string $class
     *
     * @return CompilerExtension
     */
    private function getExtension($class)
    {
        $extensions = $this->compiler->getExtensions($class);

        if (!$extensions) {
            throw new AssertionException(
                sprintf('Extension "%s" requires "%s" to be installed.', get_class($this), $class)
            );
        }

        return reset($extensions);
    }
}
