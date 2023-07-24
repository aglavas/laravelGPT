#!/bin/bash

source .venv/bin/activate

COMMAND="python3 $1"
$COMMAND

deactivate
