<?php

namespace MyCLabs\Enum;

/**
 * Polyfill для `myclabs/php-enum`, чтобы PhpSpreadsheet мог работать на PHP,
 * даже если composer-пакет не установлен из-за ограничений платформы.
 *
 * Основано на исходнике myclabs/php-enum.
 */
abstract class Enum implements \JsonSerializable, \Stringable
{
    /**
     * Enum value
     *
     * @var mixed
     */
    protected $value;

    /**
     * Enum key, the constant name
     *
     * @var string
     */
    private $key;

    /**
     * Store existing constants in a static cache per object.
     *
     * @var array<class-string, array<string, mixed>>
     */
    protected static $cache = [];

    /**
     * Cache of instances of the Enum class
     *
     * @var array<class-string, array<string, static>>
     */
    protected static $instances = [];

    /**
     * Creates a new value of some type
     *
     * @psalm-pure
     * @param mixed $value
     * @psalm-param T $value
     * @throws \UnexpectedValueException if incompatible type is given.
     */
    public function __construct($value)
    {
        if ($value instanceof static) {
            /** @psalm-var T */
            $value = $value->getValue();
        }

        // assertValidValueReturningKey всегда вернёт ключ существующей константы
        $this->key = static::assertValidValueReturningKey($value);
        /** @psalm-var T */
        $this->value = $value;
    }

    /**
     * This method exists only for the compatibility reason when deserializing a previously serialized version
     * that didn't had the key property
     */
    public function __wakeup()
    {
        /** @psalm-suppress DocblockTypeContradiction key can be null when deserializing an enum without the key */
        if ($this->key === null) {
            /** @psalm-suppress InaccessibleProperty key is not readonly as marked by psalm */
            /** @psalm-suppress PossiblyFalsePropertyAssignmentValue */
            $this->key = static::search($this->value);
        }
    }

    /**
     * @param mixed $value
     * @return static
     */
    public static function from($value): self
    {
        $key = static::assertValidValueReturningKey($value);
        /** @psalm-pure */
        return self::__callStatic($key, []);
    }

    /**
     * @psalm-pure
     * @return mixed
     * @psalm-return T
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the enum key (i.e. the constant name).
     *
     * @psalm-pure
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @psalm-pure
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Determines if Enum should be considered equal with the variable passed as a parameter.
     *
     * This method is final, for more information read:
     * https://github.com/myclabs/php-enum/issues/4
     *
     * @psalm-pure
     * @psalm-param mixed $variable
     */
    final public function equals($variable = null): bool
    {
        return $variable instanceof self
            && $this->getValue() === $variable->getValue()
            && static::class === \get_class($variable);
    }

    /**
     * Returns the names (keys) of all constants in the Enum class
     *
     * @psalm-pure
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return \array_keys(static::toArray());
    }

    /**
     * Returns instances of the Enum class of all Enum constants
     *
     * @psalm-pure
     * @return array<string, static>
     */
    public static function values(): array
    {
        $values = [];

        /** @psalm-var T $value */
        foreach (static::toArray() as $key => $value) {
            /** @psalm-suppress UnsafeGenericInstantiation */
            $values[$key] = new static($value);
        }

        return $values;
    }

    /**
     * Returns all possible values as an array
     *
     * @psalm-pure
     * @psalm-suppress ImpureStaticProperty
     * @return array<string, mixed>
     */
    public static function toArray(): array
    {
        $class = static::class;

        if (!isset(static::$cache[$class])) {
            /** @psalm-suppress ImpureMethodCall */
            $reflection = new \ReflectionClass($class);
            /** @psalm-suppress ImpureMethodCall */
            static::$cache[$class] = $reflection->getConstants();
        }

        return static::$cache[$class];
    }

    /**
     * Check if is valid enum value
     *
     * @psalm-pure
     * @psalm-param mixed $value
     */
    public static function isValid($value): bool
    {
        return \in_array($value, static::toArray(), true);
    }

    /**
     * Asserts valid enum value
     *
     * @psalm-pure
     * @psalm-assert T $value
     *
     * @param mixed $value
     */
    public static function assertValidValue($value): void
    {
        self::assertValidValueReturningKey($value);
    }

    /**
     * Asserts valid enum value and returns its constant name (key)
     *
     * @psalm-pure
     * @psalm-assert T $value
     *
     * @param mixed $value
     * @return string
     */
    private static function assertValidValueReturningKey($value): string
    {
        if (false === ($key = static::search($value))) {
            throw new \UnexpectedValueException("Value '{$value}' is not part of the enum " . static::class);
        }

        return $key;
    }

    /**
     * Check if is valid enum key
     *
     * @psalm-pure
     * @psalm-param string $key
     */
    public static function isValidKey($key): bool
    {
        $array = static::toArray();

        return isset($array[$key]) || \array_key_exists($key, $array);
    }

    /**
     * Return key for value
     *
     * @psalm-pure
     * @param mixed $value
     * @return string|false
     */
    public static function search($value)
    {
        return \array_search($value, static::toArray(), true);
    }

    /**
     * Returns a value when called statically like so: MyEnum::SOME_VALUE()
     * given SOME_VALUE is a class constant
     *
     * @psalm-pure
     * @throws \BadMethodCallException
     */
    public static function __callStatic($name, $arguments)
    {
        $class = static::class;

        if (!isset(self::$instances[$class][$name])) {
            $array = static::toArray();

            if (!isset($array[$name]) && !\array_key_exists($name, $array)) {
                $message = "No static method or enum constant '{$name}' in class " . static::class;
                throw new \BadMethodCallException($message);
            }

            /** @psalm-suppress UnsafeGenericInstantiation */
            self::$instances[$class][$name] = new static($array[$name]);
        }

        return clone self::$instances[$class][$name];
    }

    /**
     * Specify data which should be serialized to JSON
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->getValue();
    }
}

