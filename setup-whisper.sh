#!/bin/bash

# remember to: chmod +x setup.sh

python3 -m venv .venv

source .venv/bin/activate

pip install -U openai-whisper

deactivate
