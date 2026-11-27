# WhiskList

WhiskList is a recipe-sharing app. Visitors can browse published recipes, search by title, and filter by category, ingredients, dietary tags, difficulty, or total time. Signed-in users can publish recipes, save favorites to a private WhiskList, comment, and apply their own dietary and ingredient preferences.

## Features

- Draft and publish recipes with measured ingredients, ordered instructions, and a thumbnail. Authors can edit, unpublish, or delete their own recipes.
- Browse nested categories, include or exclude ingredients, and match any or all selected ingredients.
- Save recipes, manage your own comments, and customize your profile and photo.
- Get personalized allergen warnings based on the known allergen links entered by administrators. Missing warnings do not establish that a recipe is safe for a particular allergy.
- Manage categories, ingredients, dietary tags, and allergen links from the admin area.

## Architecture

WhiskList uses Laravel 13 with MySQL, Blade, Tailwind CSS, and Vite. Routes and controllers handle the web workflows. Form requests and validators check input, while middleware and policies enforce sign-in, admin access, and ownership.

Eloquent models connect recipes to authors, instructions, comments, categories, ingredients, and dietary tags. The ingredient relationship carries quantity, unit, notes, and order. Separate database tables track saved recipes and personal preferences.

Recipe thumbnails and profile photos live under `storage/app/private`. MySQL records the file paths, and Laravel routes serve the images. Draft recipe thumbnails are restricted to their authors. A private profile hides the biography from other visitors, while the name, photo, and published recipes remain visible.

## Local setup

You’ll need PHP 8.4 or newer within the 8.x series, Composer 2, MySQL, and Node.js 22.12 or newer with npm. Enable PHP’s `pdo_mysql` extension. The test suite also uses `pdo_sqlite` and GD with PNG support.

1. Clone the repository and open the project directory.

   ```sh
   git clone https://github.com/ninaperii/whisklist.git
   cd whisklist
   composer install
   npm ci
   ```

2. On a new checkout, copy `.env.example` to `.env` (`Copy-Item .env.example .env` in PowerShell, or `cp .env.example .env` on macOS/Linux). Create an empty MySQL database, then set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Set `APP_URL` to the URL you’ll use locally.
3. For optional admin access, set `WHISKLIST_ADMIN_EMAIL` and `WHISKLIST_ADMIN_PASSWORD` in your local `.env` before seeding. The seeder creates the administrator only if the email is unused. It leaves existing accounts untouched.
4. Generate the app key on the new checkout, migrate the database, build the frontend, and start Laravel.

   ```sh
   php artisan key:generate
   php artisan migrate --seed
   npm run build
   php artisan serve
   ```

5. Open the URL printed by Laravel. On an existing installation, use `php artisan migrate` for pending migrations and keep your current `APP_KEY`.

A fresh database starts without sample recipes. To publish a recipe, first add a category in the admin area. Authors can add missing ingredients directly from the recipe form.

## Tests

```sh
php vendor/bin/pest
```

The tests use an in-memory SQLite database and cover recipe publishing, ownership, filtering, saved recipes, comments, and photo uploads.

## Future updates

- Editable recipe ratings and sorting by highest rated.
- Ingredient groups and subgroups for browsing and filtering.
- Additional recipe and step photos, plus photo attachments on comments.
- Admin tools for moderating recipes and comments.
- A dark theme and a backup and restore workflow for database records and uploaded photos.
