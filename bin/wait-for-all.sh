#!/usr/bin/env bash

KAFKA_BROKER_IPV4=${KAFKA_BROKER_IPV4:-localhost}
SCHEMA_REGISTRY_IPV4=${SCHEMA_REGISTRY_IPV4:-localhost}

bin/wait-for-it.sh "${KAFKA_BROKER_IPV4}:9092" -t 60 -- echo "kafka broker is up"
bin/wait-for-it.sh "${SCHEMA_REGISTRY_IPV4}:8081" -t 60 -- echo "schema registry is up"
