<?php

namespace FlixTech\AvroSerializer\Serialize;

use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Datum\AvroIODatumReader;
use Apache\Avro\Datum\AvroIODatumWriter;
use Apache\Avro\IO\AvroIOException;
use Apache\Avro\IO\AvroStringIO;
use Apache\Avro\Schema\AvroSchema;
use FlixTech\AvroSerializer\Objects\Exceptions\Exceptions;
use Widmogrod\Monad\Either\Either;
use Widmogrod\Monad\Either\Left;
use Widmogrod\Monad\Either\Right;

use function Widmogrod\Functional\curryN;

const avroStringIo = '\FlixTech\AvroSerializer\Serialize\avroStringIo';

/**
 * @throws AvroIOException
 */
function avroStringIo(string $contents): AvroStringIO
{
    return new AvroStringIO($contents);
}

const avroBinaryEncoder = '\FlixTech\AvroSerializer\Serialize\avroBinaryEncoder';

function avroBinaryEncoder(AvroStringIO $io): AvroIOBinaryEncoder
{
    return new AvroIOBinaryEncoder($io);
}

const avroBinaryDecoder = '\FlixTech\AvroSerializer\Serialize\avroBinaryDecoder';

function avroBinaryDecoder(AvroStringIO $io): AvroIOBinaryDecoder
{
    return new AvroIOBinaryDecoder($io);
}

const avroDatumWriter = '\FlixTech\AvroSerializer\Serialize\avroDatumWriter';

/**
 * @throws AvroIOException
 */
function avroDatumWriter(): callable
{
    $writer = new AvroIODatumWriter();
    $io = avroStringIo('');

    return curryN(4, writeDatum)($writer)($io);
}

const writeDatum = '\FlixTech\AvroSerializer\Serialize\writeDatum';

function writeDatum(AvroIODatumWriter $writer, AvroStringIO $io, AvroSchema $schema, mixed $record): Either
{
    try {
        $io->truncate();
        $writer->writeData($schema, $record, avroBinaryEncoder($io));

        return Right::of($io->string());
    } catch (\Throwable $exception) {
        return Left::of(
            Exceptions::forEncode($record, $schema, $exception)
        );
    }
}

const avroDatumReader = '\FlixTech\AvroSerializer\Serialize\avroDatumReader';

/**
 * @throws AvroIOException
 */
function avroDatumReader(): callable
{
    $reader = new AvroIODatumReader();
    $io = avroStringIo('');

    return curryN(5, readDatum)($reader)($io);
}

const readDatum = '\FlixTech\AvroSerializer\Serialize\readDatum';

function readDatum(
    AvroIODatumReader $reader,
    AvroStringIO $io,
    AvroSchema $writersSchema,
    AvroSchema $readersSchema,
    mixed $data,
): Either {
    try {
        $io->truncate();
        $io->write($data);
        $io->seek(0);

        return Right::of($reader->readData($writersSchema, $readersSchema, avroBinaryDecoder($io)));
    } catch (\Throwable $exception) {
        return Left::of(
            Exceptions::forDecode($data, $exception)
        );
    }
}
