<?php

declare(strict_types=1);

namespace Arachne\Verifier;

use Reflector;
use Traversable;

/**
 * @author Jáchym Toušek <enumag@gmail.com>
 */
class ChainRuleProvider implements RuleProviderInterface
{
    /**
     * @var Traversable
     */
    private $providers;

    public function __construct(Traversable $providers)
    {
        $this->providers = $providers;
    }

    /**
     * {@inheritdoc}
     */
    public function getRules(Reflector $reflection): array
    {
        $rules = [];
        foreach ($this->providers as $provider) {
            $rules = array_merge($rules, $provider->getRules($reflection) ?: []);
        }

        return $rules;
    }
}
