<?php

declare(strict_types=1);

namespace FlixTech\AvroSerializer\Objects\SchemaResolvers;

use Apache\Avro\Schema\AvroSchema;
use Apache\Avro\Schema\AvroSchemaParseException;
use FlixTech\AvroSerializer\Objects\SchemaResolverInterface;

class CallableResolver implements SchemaResolverInterface
{
    /**
     * @var callable
     */
    private $valueSchemaResolverCallable;

    /**
     * @var callable|null
     */
    private $keySchemaResolverCallable;

    public function __construct(callable $valueSchemaResolverCallable, ?callable $keySchemaResolverCallable = null)
    {
        $this->valueSchemaResolverCallable = $valueSchemaResolverCallable;
        $this->keySchemaResolverCallable = $keySchemaResolverCallable;
    }

    /**
     * @throws AvroSchemaParseException
     */
    public function valueSchemaFor(mixed $record): AvroSchema
    {
        $schema = $this->resolveSchema(\call_user_func($this->valueSchemaResolverCallable, $record));

        return $schema ?? throw new \RuntimeException('Cannot resolve value schema for the given record');
    }

    /**
     * @throws AvroSchemaParseException
     */
    public function keySchemaFor(mixed $record): ?AvroSchema
    {
        if (!$this->keySchemaResolverCallable) {
            return null;
        }

        return $this->resolveSchema(\call_user_func($this->keySchemaResolverCallable, $record));
    }

    private function resolveSchema(mixed $record): ?AvroSchema
    {
        return match (true) {
            $record instanceof AvroSchema => $record,
            \is_array($record) => AvroSchema::realParse($record),
            \is_string($record) => AvroSchema::parse($record),
            default => null,
        };
    }
}
