<?php

declare(strict_types=1);

namespace FlixTech\AvroSerializer\Test\Objects\Schema\Generation;

use Doctrine\Common\Annotations\AnnotationReader;
use FlixTech\AvroSerializer\Objects\Schema;
use FlixTech\AvroSerializer\Objects\Schema\Generation\AttributeReader;
use FlixTech\AvroSerializer\Objects\Schema\Generation\SchemaGenerator;
use FlixTech\AvroSerializer\Test\Objects\Schema\Generation\Fixture\ArraysWithComplexType;
use FlixTech\AvroSerializer\Test\Objects\Schema\Generation\Fixture\EmptyRecord;
use FlixTech\AvroSerializer\Test\Objects\Schema\Generation\Fixture\MapsWithComplexType;
use FlixTech\AvroSerializer\Test\Objects\Schema\Generation\Fixture\PrimitiveTypes;
use FlixTech\AvroSerializer\Test\Objects\Schema\Generation\Fixture\RecordWithComplexTypes;
use FlixTech\AvroSerializer\Test\Objects\Schema\Generation\Fixture\RecordWithRecordType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SchemaGeneratorTest extends TestCase
{
    private SchemaGenerator $generatorDoctrineAnnotations;

    private SchemaGenerator $generatorAttributes;

    protected function setUp(): void
    {
        $this->generatorDoctrineAnnotations = new SchemaGenerator(
            new Schema\Generation\AnnotationReader(
                new AnnotationReader()
            )
        );

        $this->generatorAttributes = new SchemaGenerator(
            new AttributeReader()
        );
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    #[DataProvider('schemaDataProvider')]
    public function it_should_generate_schema_using_doctrine_annotations(string $class, Schema $expectedSchema): void
    {
        $actualSchema = $this->generatorDoctrineAnnotations->generate($class);

        self::assertEquals($expectedSchema, $actualSchema);
    }

    /**
     * @throws \ReflectionException
     */
    #[Test]
    #[DataProvider('schemaDataProvider')]
    public function it_should_generate_schema_using_attributes(string $class, Schema $expectedSchema): void
    {
        $actualSchema = $this->generatorAttributes->generate($class);

        self::assertEquals($expectedSchema, $actualSchema);
    }

    public static function schemaDataProvider(): array
    {
        return [
            'empty record' => [
                'class' => EmptyRecord::class,
                'expectedSchema' => Schema::record()
                    ->name('EmptyRecord')
                    ->namespace('org.acme'),
            ],
            'primitive types' => [
                'class' => PrimitiveTypes::class,
                'expectedSchema' => Schema::record()
                    ->name('PrimitiveTypes')
                    ->namespace('org.acme')
                    ->field(
                        'nullType',
                        Schema::null(),
                        Schema\Record\FieldOption::doc('null type')
                    )
                    ->field(
                        'isItTrue',
                        Schema::boolean(),
                        Schema\Record\FieldOption::default(false)
                    )
                    ->field(
                        'intType',
                        Schema::int()
                    )
                    ->field(
                        'longType',
                        Schema::long(),
                        Schema\Record\FieldOption::orderAsc()
                    )
                    ->field(
                        'floatType',
                        Schema::float(),
                        Schema\Record\FieldOption::aliases('foo', 'bar')
                    )
                    ->field(
                        'doubleType',
                        Schema::double()
                    )
                    ->field(
                        'bytesType',
                        Schema::bytes()
                    )
                    ->field(
                        'stringType',
                        Schema::string()
                    ),
            ],
            'record with complex types' => [
                'class' => RecordWithComplexTypes::class,
                'expectedSchema' => Schema::record()
                    ->name('RecordWithComplexTypes')
                    ->field(
                        'array',
                        Schema::array()
                            ->items(Schema::string())
                            ->default(['foo', 'bar'])
                    )
                    ->field(
                        'map',
                        Schema::map()
                            ->values(Schema::int())
                            ->default(['foo' => 42, 'bar' => 42])
                    )
                    ->field(
                        'enum',
                        Schema::enum()
                            ->name('Suit')
                            ->symbols('SPADES', 'HEARTS', 'DIAMONDS', 'CLUBS'),
                        Schema\Record\FieldOrder::asc()
                    )
                    ->field(
                        'fixed',
                        Schema::fixed()
                            ->name('md5')
                            ->namespace('org.acme')
                            ->aliases('foo', 'bar')
                            ->size(16)
                    )
                    ->field(
                        'union',
                        Schema::union(Schema::string(), Schema::int(), Schema::array()->items(Schema::string()))
                    ),
            ],
            'record with record type' => [
                'class' => RecordWithRecordType::class,
                'expectedSchema' => Schema::record()
                    ->name('RecordWithRecordType')
                    ->field(
                        'simpleField',
                        Schema::record()
                            ->name('SimpleRecord')
                            ->namespace('org.acme')
                            ->doc('This a simple record for testing purposes')
                            ->field(
                                'intType',
                                Schema::int(),
                                Schema\Record\FieldOption::default(42)
                            )
                            ->field(
                                'uuidType',
                                Schema::uuid()
                            )
                            ->field(
                                'timestampMillisType',
                                Schema::timestampMillis()
                            ),
                    )
                    ->field(
                        'unionField',
                        Schema::union(
                            Schema::null(),
                            Schema::named('org.acme.SimpleRecord')
                        )
                    ),
            ],
            'arrays with complex type' => [
                'class' => ArraysWithComplexType::class,
                'expectedSchema' => Schema::record()
                    ->name('ArraysWithComplexType')
                    ->field(
                        'arrayWithUnion',
                        Schema::array()
                            ->items(
                                Schema::union(
                                    Schema::string(),
                                    Schema::array()->items(Schema::string())
                                )
                            )
                    )
                    ->field(
                        'arrayWithMap',
                        Schema::array()
                            ->items(
                                Schema::map()->values(Schema::string())
                            )
                    ),
            ],
            'maps with complex type' => [
                'class' => MapsWithComplexType::class,
                'expectedSchema' => Schema::record()
                    ->name('MapsWithComplexType')
                    ->field(
                        'mapWithUnion',
                        Schema::map()
                            ->values(
                                Schema::union(
                                    Schema::string(),
                                    Schema::array()->items(Schema::string())
                                )
                            )
                    )
                    ->field(
                        'mapWithArray',
                        Schema::map()
                            ->values(
                                Schema::array()->items(Schema::string())
                            )
                    ),
            ],
        ];
    }
}
