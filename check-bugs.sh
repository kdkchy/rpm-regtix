#!/bin/bash

# Script untuk check bug di lokal sebelum deploy
# Usage: ./check-bugs.sh

echo "=== Laravel Bug Checker ==="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ERRORS=0
WARNINGS=0

# 1. Check for $this in static context
echo "1. Checking for \$this usage in static methods..."
STATIC_THIS=$(grep -r "static function" app/Filament --include="*.php" -A 10 | grep -E "\$this->" | grep -v "//" | head -5)
if [ -z "$STATIC_THIS" ]; then
    echo -e "${GREEN}✓ No \$this in static context found${NC}"
else
    echo -e "${RED}✗ Found \$this in static context:${NC}"
    echo "$STATIC_THIS"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 2. Check for missing methods
echo "2. Checking for printXml method in Report.php..."
if grep -q "public function printXml" app/Filament/Pages/Report.php; then
    echo -e "${GREEN}✓ printXml method exists${NC}"
else
    echo -e "${RED}✗ printXml method NOT found${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 3. Check for reportGeneratedAt property
echo "3. Checking for reportGeneratedAt property..."
if grep -q "public string \$reportGeneratedAt" app/Filament/Pages/Report.php; then
    echo -e "${GREEN}✓ reportGeneratedAt property exists${NC}"
else
    echo -e "${RED}✗ reportGeneratedAt property NOT found${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 4. Check route for printXml
echo "4. Checking route for printXml..."
if grep -q "report.print-xml" routes/web.php; then
    echo -e "${GREEN}✓ Route report.print-xml exists${NC}"
else
    echo -e "${RED}✗ Route report.print-xml NOT found${NC}"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 5. Check static method calls in RegistrationResource
echo "5. Checking static method calls in RegistrationResource..."
if grep -q "self::getEmailStatus" app/Filament/Resources/RegistrationResource.php; then
    echo -e "${GREEN}✓ Static methods use self:: correctly${NC}"
else
    echo -e "${YELLOW}⚠ Check static method calls${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# 6. Check PHP syntax
echo "6. Checking PHP syntax..."
SYNTAX_ERRORS=$(find app -name "*.php" -exec php -l {} \; 2>&1 | grep -i "error" | head -5)
if [ -z "$SYNTAX_ERRORS" ]; then
    echo -e "${GREEN}✓ No PHP syntax errors${NC}"
else
    echo -e "${RED}✗ PHP syntax errors found:${NC}"
    echo "$SYNTAX_ERRORS"
    ERRORS=$((ERRORS + 1))
fi
echo ""

# 7. Check for missing imports
echo "7. Checking for common missing imports..."
MISSING_IMPORTS=$(grep -r "Carbon::" app/Filament --include="*.php" | grep -v "use Carbon" | head -3)
if [ -z "$MISSING_IMPORTS" ]; then
    echo -e "${GREEN}✓ No obvious missing imports${NC}"
else
    echo -e "${YELLOW}⚠ Possible missing imports:${NC}"
    echo "$MISSING_IMPORTS"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# 8. Check storage and cache directories exist
echo "8. Checking storage and cache directories..."
if [ -d "storage/logs" ] && [ -d "bootstrap/cache" ]; then
    echo -e "${GREEN}✓ Storage and cache directories exist${NC}"
else
    echo -e "${YELLOW}⚠ Storage or cache directories missing${NC}"
    WARNINGS=$((WARNINGS + 1))
fi
echo ""

# Summary
echo "=== Summary ==="
if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! Ready to deploy.${NC}"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}⚠ $WARNINGS warning(s) found, but no critical errors${NC}"
    exit 0
else
    echo -e "${RED}✗ $ERRORS error(s) and $WARNINGS warning(s) found${NC}"
    echo -e "${RED}Please fix errors before deploying!${NC}"
    exit 1
fi





