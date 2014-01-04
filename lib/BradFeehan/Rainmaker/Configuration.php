<?php

namespace BradFeehan\Rainmaker;

use BradFeehan\Rainmaker\Exception\InvalidArgumentException;
use ReflectionClass;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * Handles the configuration for Rainmaker
 *
 * Powered by Symfony's Config component.
 */
class Configuration implements ConfigurationInterface
{

    /**
     * The configuration data backing this instance
     *
     * @var array
     */
    private $data;

    /**
     * Cache for the logger as set up in this configuration
     *
     * @var Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * The processor that is used to parse configuration input
     *
     * @var Symfony\Component\Config\Definition\Processor
     */
    private $processor;


    /**
     * Initializes a new Configuration instance with a Processor
     *
     * @param Symfony\Component\Config\Definition\Processor $processor
     */
    public function __construct(Processor $processor = null)
    {
        $this->data = array();
        $this->processor = $processor ?: new Processor();
    }

    /**
     * Processes one or more config arrays into this instance
     *
     * This takes any number of configuration data arrays as arguments.
     * It merges them all together using the Processor and saves the
     * resulting data into this Configuration instance. This instance
     * will then be able to be used to query for configuration values.
     *
     * @param array $data A configuration data array to process
     *
     * @return BradFeehan\Rainmaker\Configuration $this
     * @chainable
     */
    public function process($data)
    {
        $this->data = $this->processor->processConfiguration(
            $this,
            func_get_args()
        );

        return $this;
    }

    /**
     * Retrieves a configuration item by key, or the whole config array
     *
     * Optionally, the key can contain a forward-slash character ("/"),
     * which allows accessing sub-elements of arrays. For example, to
     * access the "bar" element of the array contained in the "foo"
     * key, use "foo/bar" for $key.
     *
     * If $key is null, the entire configuration array is returned.
     *
     * @param string $key The key to retrieve
     *
     * @return array
     */
    public function get($key = null)
    {
        $result = $this->data;

        // Short-circuit if key is null, return full data array
        if ($key === null) {
            return $result;
        }

        foreach (explode('/', (string) $key) as $part) {
            if (!isset($result[$part])) {
                throw new InvalidArgumentException(
                    "Unknown configuration key '$key'"
                );
            }

            $result = $result[$part];
        }

        return $result;
    }

    /**
     * Retrieves the logger as set up in this configuration
     *
     * @return Psr\Log\LoggerInterface
     */
    public function logger()
    {
        if (!$this->logger) {
            $this->logger = $this->createLogger();
        }

        return $this->logger;
    }

    /**
     * Creates the logger as set up in this configuration
     *
     * The returned value of this method is cached in the logger()
     * method.
     *
     * @return Psr\Log\LoggerInterface
     */
    protected function createLogger()
    {
        // Instantiate configurer
        $configClassName = $this->get('logger/configuration/class');
        $configClass = new ReflectionClass($configClassName);
        $configurer = $configClass->newInstance();

        // Configure logger using the configurer
        return $configurer->createLogger($this->get('logger/class'));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();
        $builder->root('rainmaker')
            ->children()
                ->integerNode('interval')
                    ->cannotBeEmpty()
                    ->defaultValue(60)
                ->end()
                ->arrayNode('mailboxes')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->enumNode('protocol')
                                ->isRequired()
                                ->values(array(
                                    'imap',
                                    'pop',
                                ))
                            ->end()
                            ->scalarNode('user')->end()
                            ->scalarNode('password')->end()
                            ->scalarNode('host')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('port')->end()
                            ->scalarNode('ssl')->end()
                            ->scalarNode('folder')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('logger')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class')
                            ->cannotBeEmpty()
                            ->defaultValue('Monolog\\Logger')
                            ->validate()
                                // if not a valid PSR-3 logger...
                                ->ifTrue(function ($value) {
                                    return !is_a(
                                        $value,
                                        'Psr\\Log\\LoggerInterface',
                                        true
                                    );
                                })
                                ->thenInvalid(
                                    "'%s' isn't a PSR-3 logger. " .
                                    'It must implement the interface ' .
                                    'Psr\\Log\\LoggerInterface'
                                )
                            ->end()
                        ->end()
                        ->arrayNode('configuration')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')
                                    ->cannotBeEmpty()
                                    ->defaultValue(
                                        'BradFeehan\\Rainmaker\\' .
                                        'Logging\\MonologConfigurer'
                                    )
                                    ->validate()
                                        ->ifTrue(function ($value) {
                                            return !is_a(
                                                $value,
                                                'BradFeehan\\Rainmaker\\' .
                                                'Logging\\ConfigurerInterface',
                                                true
                                            );
                                        })
                                        ->thenInvalid(
                                            "'%s' isn't a configurer class. " .
                                            'It must implement the interface ' .
                                            'BradFeehan\\Rainmaker\\' .
                                            'Logging\\ConfigurerInterface'
                                        )
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $builder;
    }
}
