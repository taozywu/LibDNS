<?php
/**
 * Defines a data type comprising multiple fields
 *
 * PHP version 5.4
 *
 * @category   LibDNS
 * @package    TypeDefinitions
 * @author     Chris Wright <https://github.com/DaveRandom>
 * @copyright  Copyright (c) Chris Wright <https://github.com/DaveRandom>
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 * @version    2.0.0
 */
namespace LibDNS\Records\TypeDefinitions;

/**
 * Defines a data type comprising multiple fields
 *
 * @category   LibDNS
 * @package    TypeDefinitions
 * @author     Chris Wright <https://github.com/DaveRandom>
 */
class TypeDefinition implements \Iterator, \Countable
{
    /**
     * @var FieldDefinitionFactory Creates FieldDefinition objects
     */
    private $fieldDefFactory;

    /**
     * @var int Number of fields in the type
     */
    private $fieldCount;

    /**
     * @var \LibDNS\Records\TypeDefinitions\FieldDefinition The last field defined by the type
     */
    private $lastField;

    /**
     * @var int[] Map of field indexes to type identifiers
     */
    private $fieldDefs = [];

    /**
     * @var int[] Map of field names to indexes
     */
    private $fieldNameMap = [];

    /**
     * @var callable Custom implementation for __toString() handling
     */
    private $toStringFunction;

    /**
     * @var bool Whether the iteration pointer indicates a valid item
     */
    private $pointerValid = true;

    /**
     * Constructor
     *
     * @param FieldDefinitionFactory $fieldDefFactory
     * @param int[]                  $typeDef         Structural definition of the fields
     *
     * @throws \InvalidArgumentException When the type definition is invalid
     */
    public function __construct(FieldDefinitionFactory $fieldDefFactory, array $definition)
    {
        $this->fieldDefFactory = $fieldDefFactory;

        if (isset($definition['__toString'])) {
            if (!is_callable($definition['__toString'])) {
                throw new \InvalidArgumentException('Invalid type definition: __toString() implementation is not callable');
            }

            $this->toStringFunction = $definition['__toString'];
            unset($definition['__toString']);
        }

        $this->fieldCount = count($definition);
        $index = 0;
        foreach ($definition as $name => $type) {
            $this->registerField($index++, $name, $type);
        }
    }

    /**
     * Register a field from the type definition
     *
     * @param int    $index
     * @param string $name
     * @param int    $name
     *
     * @throws \InvalidArgumentException When the field definition is invalid
     */
    private function registerField($index, $name, $type)
    {
        if (!preg_match('/^(?P<name>[\w\-]+)(?P<quantifier>\+|\*)?(?P<minimum>(?<=\+)\d+)?$/', strtolower($name), $matches)) {
            throw new \InvalidArgumentException('Invalid field definition ' . $name . ': Syntax error');
        }

        if (isset($matches['quantifier'])) {
            if ($index !== $this->fieldCount - 1) {
                throw new \InvalidArgumentException('Invalid field definition ' . $name . ': Quantifiers only allowed in last field');
            }

            if (!isset($matches['minimum'])) {
                $matches['minimum'] = $matches['quantifier'] === '+' ? 1 : 0;
            }

            $allowsMultiple = true;
            $minimumValues = (int) $matches['minimum'];
        } else {
            $allowsMultiple = false;
            $minimumValues = 0;
        }

        $this->fieldDefs[$index] = $this->fieldDefFactory->create($index, $matches['name'], $type, $allowsMultiple, $minimumValues);
        if ($index === $this->fieldCount - 1) {
            $this->lastField = $this->fieldDefs[$index];
        }

        $this->fieldNameMap[$matches['name']] = $index;
    }

    /**
     * Get the field definition indicated by the supplied index
     *
     * @param int $index
     *
     * @return \LibDNS\Records\TypeDefinitions\FieldDefinition
     *
     * @throws \OutOfBoundsException When the supplied index does not refer to a valid field
     */
    public function getFieldDefinition($index)
    {
        $index = (int) $index;
        if (isset($this->fieldDefs[$index])) {
            $fieldDef = $this->fieldDefs[$index];
        } else if ($index >= 0 && $this->lastField->allowsMultiple()) {
            $fieldDef = $this->lastField;
        } else {
            throw new \OutOfBoundsException('Index ' . $index . ' does not refer to a valid field');
        }

        return $fieldDef;
    }

    /**
     * Get the field index indicated by the supplied name
     *
     * @param string $index
     *
     * @return int
     *
     * @throws \OutOfBoundsException When the supplied name does not refer to a valid field
     */
    public function getFieldIndexByName($name)
    {
        $fieldName = strtolower($name);
        if (!isset($this->fieldNameMap[$fieldName])) {
            throw new \OutOfBoundsException('Name ' . $name . ' does not refer to a valid field');
        }

        return $this->fieldNameMap[$fieldName];
    }

    /**
     * Get the __toString() implementation
     *
     * @return callable|null
     */
    public function getToStringFunction()
    {
        return $this->toStringFunction;
    }

    /**
     * Set the __toString() implementation
     *
     * @param callable $function
     */
    public function setToStringFunction(callable $function)
    {
        $this->toStringFunction = $function;
    }

    /**
     * Get the field indicated by the iteration pointer (Iterator interface)
     *
     * @return \LibDNS\Records\TypeDefinitions\FieldDefinition
     */
    public function current()
    {
        return current($this->fieldDefs);
    }

    /**
     * Get the key indicated by the iteration pointer
     *
     * @return int
     */
    public function key()
    {
        return key($this->fieldDefs);
    }

    /**
     * Increment the iteration pointer (Iterator interface)
     */
    public function next()
    {
        $this->pointerValid = next($this->fieldDefs) !== false;
    }

    /**
     * Reset the iteration pointer to the beginning (Iterator interface)
     */
    public function rewind()
    {
        reset($this->fieldDefs);
        $this->pointerValid = count($this->fieldDefs) > 0;
    }

    /**
     * Test whether the iteration pointer indicates a valid field (Iterator interface)
     *
     * @return bool
     */
    public function valid()
    {
        return $this->pointerValid;
    }

    /**
     * Get the number of fields (Countable interface)
     *
     * @return int
     */
    public function count()
    {
        return $this->fieldCount;
    }
}
