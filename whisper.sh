#!/bin/bash

COMMAND="whisper --output_format=json --model=small.en --output_dir=$2 $1"

source .venv/bin/activate

$COMMAND

deactivate
