#!/bin/bash

PORT=8888
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

echo "Starting WRLA Documentation server at http://localhost:$PORT"
echo "Press Ctrl+C to stop."
echo ""

# Open browser after starting the server
(sleep 2 && xdg-open "http://localhost:$PORT" 2>/dev/null || open "http://localhost:$PORT" 2>/dev/null) &

php -S "localhost:$PORT" -t "$SCRIPT_DIR"