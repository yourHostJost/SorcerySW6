@echo off
REM Git Commit Script for TcgManager Plugin
REM Run this from the SorcerySW6 root directory

echo ==========================================
echo Git Commit - TcgManager Plugin
echo ==========================================
echo.

echo 1. Checking current directory...
cd /d "C:\Users\Jo\Documents\augment-projects\SorcerySW6"
echo Current directory: %CD%
echo.

echo 2. Checking git status...
git status
echo.

echo 3. Adding all new plugin files...
git add custom/plugins/TcgManager/
git add .gitignore
echo Files added to staging area.
echo.

echo 4. Creating commit...
git commit -m "feat: Add TcgManager Plugin - Trading Card Game Manager

✨ Features implemented:
- Complete plugin structure with 5 database entities
- Card collection organizer with detailed card properties  
- Deck management with collection comparison
- Shop integration foundation (API endpoints ready)
- Frontend integration in customer account area
- Automatic default collection for new customers

🗄️ Database entities:
- tcg_card: Card master data (title, edition, costs, rarity, etc.)
- tcg_collection: Customer collections
- tcg_collection_card: Cards in collections (condition, quantity, foil)
- tcg_deck: Deck definitions (format, archetype, colors)
- tcg_deck_card: Cards in decks (main/sideboard)

🎮 Demo data:
- 12 realistic sample cards (Magic: The Gathering style)
- Price range from €0.25 to €25,000 (Black Lotus!)
- Various editions and rarities
- Setup script for easy installation

📚 Documentation:
- Comprehensive README with feature overview
- Detailed installation guide  
- API endpoint documentation

🚀 Ready for:
- Plugin installation and activation
- Customer registration with auto-collection
- Card search and collection management
- Deck creation and comparison
- Missing cards identification

Next phase: Complete shop integration with one-click cart functionality"

echo.
echo 5. Pushing to remote repository...
git push origin staging
echo.

echo ==========================================
echo Git Commit completed successfully!
echo ==========================================
echo.
echo Next steps:
echo 1. Install plugin: bin/console plugin:install --activate TcgManager
echo 2. Load demo data: php custom/plugins/TcgManager/setup-demo-data.php
echo 3. Test in customer account area
echo.
pause
