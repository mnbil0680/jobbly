#!/bin/bash
# **================================================**
# ** File: START_PROJECT.sh                         **
# ** Responsibility: Start XAMPP and open project   **
# ** Usage: chmod +x START_PROJECT.sh && ./START_PROJECT.sh **
# **================================================**

echo ""
echo "╔════════════════════════════════════════════════╗"
echo "║   Jobbly Project - Quick Start                 ║"
echo "╚════════════════════════════════════════════════╝"
echo ""

# Detect XAMPP installation path
XAMPP_PATH=""
if [ -d "/opt/xampp" ]; then
    XAMPP_PATH="/opt/xampp"
elif [ -d "/usr/local/xampp" ]; then
    XAMPP_PATH="/usr/local/xampp"
elif [ -d "$HOME/xampp" ]; then
    XAMPP_PATH="$HOME/xampp"
elif [ -d "/Applications/XAMPP" ]; then
    XAMPP_PATH="/Applications/XAMPP"
else
    echo "✗ Error: XAMPP not found"
    echo "  Please install XAMPP first from: https://www.apachefriends.org"
    exit 1
fi

# Check if XAMPP httpd exists
if [ ! -f "$XAMPP_PATH/bin/httpd" ]; then
    echo "✗ Error: XAMPP httpd not found at $XAMPP_PATH"
    echo "  Please install XAMPP first from: https://www.apachefriends.org"
    exit 1
fi

echo "Starting XAMPP services..."
echo ""

# Start Apache
echo "[1/2] Starting Apache..."
if sudo "$XAMPP_PATH/bin/apachectl" start > /dev/null 2>&1; then
    echo "✓ Apache started successfully"
else
    # Try without sudo
    if "$XAMPP_PATH/bin/apachectl" start > /dev/null 2>&1; then
        echo "✓ Apache started successfully"
    else
        echo "✗ Failed to start Apache"
        echo "  Make sure port 80 is not in use"
        exit 1
    fi
fi

# Start MySQL
echo "[2/2] Starting MySQL..."
if sudo "$XAMPP_PATH/bin/mysql.server" start > /dev/null 2>&1; then
    echo "✓ MySQL started successfully"
else
    # Try without sudo
    if "$XAMPP_PATH/bin/mysql.server" start > /dev/null 2>&1; then
        echo "✓ MySQL started successfully"
    else
        echo "⚠ MySQL may already be running"
    fi
fi

# Wait a bit for services to start
sleep 2

echo ""
echo "╔════════════════════════════════════════════════╗"
echo "║   Services Started Successfully!               ║"
echo "╚════════════════════════════════════════════════╝"
echo ""
echo "You can now access:"
echo "  Web:  http://localhost/jobbly/app"
echo "  API:  http://localhost/jobbly/src/fetch_sources.php"
echo ""
echo "Or run from terminal:"
echo "  cd $(dirname "$XAMPP_PATH")/htdocs/jobbly"
echo "  php src/fetch_sources_cli.php"
echo ""
echo "To stop services later, use:"
echo "  sudo $XAMPP_PATH/bin/apachectl stop  (Apache)"
echo "  sudo $XAMPP_PATH/bin/mysql.server stop  (MySQL)"
echo ""

# Ask if user wants to open browser (only on systems with xdg-open or open command)
if command -v xdg-open > /dev/null; then
    # Linux
    read -p "Would you like to open http://localhost/jobbly/app in your browser? (y/n) " response
    if [ "$response" = "y" ] || [ "$response" = "Y" ]; then
        xdg-open "http://localhost/jobbly/app" > /dev/null 2>&1 &
    fi
elif command -v open > /dev/null; then
    # macOS
    read -p "Would you like to open http://localhost/jobbly/app in your browser? (y/n) " response
    if [ "$response" = "y" ] || [ "$response" = "Y" ]; then
        open "http://localhost/jobbly/app"
    fi
fi
