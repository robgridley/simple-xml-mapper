<?php

namespace SimpleXmlMapper;

use SimpleXMLElement;
use InvalidArgumentException;
use UnexpectedValueException;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;

class XmlMapper
{
    /**
     * The property type extractor instance.
     *
     * @var PropertyTypeExtractorInterface
     */
    protected $extractor;

    /**
     * The default type instance.
     *
     * @var Type
     */
    protected $defaultType;

    /**
     * The custom types.
     *
     * @var array
     */
    protected $types = [];

    /**
     * Create a new mapper instance.
     *
     * @param PropertyTypeExtractorInterface $extractor
     * @param Type|null $defaultType
     */
    public function __construct(PropertyTypeExtractorInterface $extractor, Type $defaultType = null)
    {
        $this->extractor = $extractor;
        $this->defaultType = $defaultType ?: new Type('string');
    }

    /**
     * Add a custom type.
     *
     * @param string $type
     * @param callable $callback
     */
    public function addType($type, callable $callback)
    {
        $this->types[$type] = $callback;
    }

    /**
     * Map XML to the specified entity.
     *
     * @param SimpleXMLElement $xml
     * @param string $class
     * @return mixed
     */
    public function map(SimpleXMLElement $xml, $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class [$class] does not exist");
        }

        $properties = $this->getProperties($class);
        $entity = new $class;

        foreach ($xml as $node) {
            $name = $node->getName();

            if (in_array($name, $properties)) {
                $type = $this->getType($class, $name) ?: $this->defaultType;
                $entity->{$name} = $this->getValue($node, $type);
            }
        }

        return $entity;
    }

    /**
     * Get all public properties for the specified class.
     *
     * @param string $class
     * @return array
     */
    protected function getProperties($class)
    {
        return array_keys(get_class_vars($class));
    }

    /**
     * Determine the type for the specified property using reflection.
     *
     * @param string $class
     * @param string $name
     * @return Type
     */
    protected function getType($class, $name)
    {
        return $this->extractor->getTypes($class, $name)[0];
    }

    /**
     * Get the value for the specified node.
     *
     * @param SimpleXMLElement $xml
     * @param Type $type
     * @return array|bool|mixed|SimpleXMLElement
     */
    protected function getValue(SimpleXMLElement $xml, Type $type)
    {
        if ($type->isCollection()) {
            $collectionType = $type->getCollectionValueType() ?: $this->defaultType;
            return $this->asCollection($xml, $collectionType);
        }

        $builtinType = $type->getBuiltinType();

        if ($builtinType == 'object') {
            return $this->asObject($xml, $type);
        }

        if ($builtinType == 'bool') {
            return $this->asBool($xml);
        }

        settype($xml, $builtinType);

        return $xml;
    }

    /**
     * Cast node to collection.
     *
     * @param SimpleXMLElement $xml
     * @param Type $type
     * @return array
     */
    protected function asCollection(SimpleXMLElement $xml, Type $type)
    {
        $collection = [];

        foreach ($xml as $node) {
            $collection[] = $this->getValue($node, $type);
        }

        return $collection;
    }

    /**
     * Cast node to object.
     *
     * @param SimpleXMLElement $xml
     * @param Type $type
     * @return mixed
     */
    protected function asObject(SimpleXMLElement $xml, Type $type)
    {
        $class = $type->getClassName();

        if ($this->isCustomType($class)) {
            return $this->callCustomInstantiator($xml, $class);
        }

        return $this->map($xml, $class);
    }

    /**
     * Cast node to boolean.
     *
     * @param SimpleXMLElement $xml
     * @return bool
     */
    protected function asBool(SimpleXMLElement $xml)
    {
        $value = (string)$xml;

        switch ($value) {
            case '0':
            case 'false':
                return false;
            case '1':
            case 'true':
                return true;
            default:
                throw new UnexpectedValueException("Could not convert [$value] to boolean");
        }
    }

    /**
     * Determine if the specified type has a custom instantiator.
     *
     * @param string $type
     * @return bool
     */
    protected function isCustomType($type)
    {
        return array_key_exists($type, $this->types);
    }

    /**
     * Call the custom instantiator for the specified type.
     *
     * @param SimpleXMLElement $node
     * @param string $type
     * @return mixed
     */
    protected function callCustomInstantiator(SimpleXMLElement $node, $type)
    {
        $callback = $this->types[$type];

        return $callback($node);
    }
}
