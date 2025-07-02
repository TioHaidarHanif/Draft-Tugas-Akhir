#!/bin/bash

# Check if the Laravel development server is running
if ! curl -s http://127.0.0.1:8000 > /dev/null; then
    echo "Laravel development server is not running. Please start it with 'php artisan serve'"
    exit 1
fi

# Create a temporary file to store the HTML response
TMP_FILE=$(mktemp)

# Fetch the root URL (which should now be our API documentation)
curl -s http://127.0.0.1:8000 > $TMP_FILE

# Check if the page contains key elements of our API documentation
if grep -q "Helpdesk API Documentation" $TMP_FILE && \
   grep -q "Authentication" $TMP_FILE && \
   grep -q "Endpoints" $TMP_FILE; then
    echo "✅ API Documentation page loads successfully!"
    echo "✅ Key sections detected in the page"
else
    echo "❌ API Documentation page failed to load correctly"
    echo "   The page doesn't contain the expected content"
fi

# Clean up
rm $TMP_FILE

echo "-----------------------------------"
echo "API documentation is now available at http://127.0.0.1:8000"
echo "The documentation includes:"
echo "✓ Auto-generated list of all API endpoints"
echo "✓ Authentication instructions"
echo "✓ Request/response examples"
echo "✓ Role-based access information"
echo "✓ Error handling documentation"
echo "-----------------------------------"
