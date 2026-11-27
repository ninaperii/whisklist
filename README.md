# WhiskList

WhiskList is a community recipe-sharing web application built with Laravel. Visitors can discover recipes and filter them by categories or ingredients. Registered users can publish recipes, discuss them, upload food photos, and save recipes to a personal WhiskList. Administrators maintain the platform's categories and ingredients and moderate user-created content.

> Status: project foundation and MVP definition. The application features described below are planned and will be implemented incrementally.

## Project goals

WhiskList is intended to demonstrate the backend concepts covered by the course through one coherent application:

- Laravel models and relationships
- RESTful routes and resource controllers
- Blade views and reusable layouts/components
- Authentication and role-based authorization
- Complete CRUD workflows
- Form validation and useful error feedback
- Image uploads and file storage
- Database factories and realistic seeded data
- Search, filtering, and non-CRUD business actions
- Automated feature and unit tests
- Efficient database access without N+1 queries

The interface should be usable and consistent, but backend quality takes priority over elaborate frontend design.

## User roles

### Visitor

A visitor can:

- View the welcome page.
- Browse published recipes.
- View a recipe's ingredients, instructions, images, and comments.
- Search and filter recipes by title, category, and ingredients.
- Browse categories and user profiles that their owners have made public.

A visitor cannot create, save, comment on, or modify content.

### Registered user

A registered user can:

- Use all public functionality.
- Manage their profile and choose whether it is public or private.
- Create and manage their own recipes, including ingredients, categories, steps, and images.
- Comment on recipes and optionally attach an image.
- Manage their own comments.
- Save other recipes to a personal list called **My WhiskList**.
- Use a dashboard to access their published recipes, drafts, and saved favorites.

### Administrator

An administrator can:

- Manage categories and the canonical ingredient list.
- Moderate recipes and comments when necessary.
- Access an administration dashboard in addition to normal user functionality.

User and administrator permissions will be enforced on the server through middleware and Laravel policies, not only by hiding interface controls.

## MVP scope

The first complete version will include:

1. Registration, login, and logout.
2. A public welcome page and public recipe discovery.
3. Recipe CRUD with ownership and authorization.
4. Multiple categories per recipe.
5. Structured recipe ingredients with quantity, unit, notes, and a main-ingredient flag.
6. Ordered recipe instruction steps.
7. A required recipe thumbnail plus optional recipe and step images.
8. Comments with optional image attachments.
9. A personal saved-recipe list called **My WhiskList**.
10. Administrator CRUD for categories and ingredients.
11. Administrator moderation of recipes and comments.
12. Search and filtering by title, categories, and selected ingredients.
13. Validation, policies, factories, seeders, and automated tests.

## Out of scope for the initial MVP

The following ideas may be added after the core application is complete:

- Recipe ratings
- Following other users
- User-to-user notifications
- Nutritional calculations
- Automatic shopping lists
- Social-media sharing
- Recipe version history
- Multiple languages
- Advanced image transformations

Keeping these features out of the initial MVP prevents the project from becoming too broad before its required backend functionality is reliable.

## Planned data model

### User

Represents an account and determines whether the account is a regular user or administrator.

Planned fields:

- `id`
- `name`
- `email`
- `password`
- `role`
- `bio` (optional)
- `avatar_path` (optional)
- `is_profile_public`
- timestamps

Relationships:

- Has many authored recipes through `recipes.user_id`.
- Has many comments.
- Belongs to many saved recipes through a separate favorites pivot table.

Authored recipes and saved recipes are independent collections. A user's recipe list contains recipes they created and published; their WhiskList contains recipes they chose to save, regardless of who authored them.

### Recipe

Represents a recipe submitted by a registered user.

Planned fields:

- `id`
- `user_id`
- `title`
- `slug`
- `summary`
- `preparation_minutes`
- `cooking_minutes`
- `servings`
- `difficulty`
- `status`
- `published_at`
- timestamps

Relationships:

- Belongs to one author (`User`).
- Belongs to many categories.
- Belongs to many ingredients.
- Has many instruction steps.
- Has many comments.
- Has many images.
- Is saved by many users.

### Category

Represents a reusable way to group recipes, such as `Dessert`, `Vegetarian`, or `Italian`.

Planned fields:

- `id`
- `name`
- `slug`
- `description` (optional)
- timestamps

Relationships:

- Belongs to many recipes through `category_recipe`.

### Ingredient

Represents one canonical ingredient that can be reused and searched consistently.

Planned fields:

- `id`
- `name`
- `slug`
- timestamps

Relationships:

- Belongs to many recipes through `ingredient_recipe`.

The `ingredient_recipe` pivot will also store:

- `quantity`
- `unit`
- `notes` (optional)
- `is_main`

Ingredients will be managed by administrators so filtering is not damaged by duplicates such as `tomato`, `tomatoes`, and `fresh tomato`.

### RecipeStep

Represents one ordered instruction in a recipe.

Planned fields:

- `id`
- `recipe_id`
- `position`
- `instruction`
- timestamps

Relationships:

- Belongs to one recipe.
- Has optional images.

### Comment

Represents a registered user's response to a recipe.

Planned fields:

- `id`
- `user_id`
- `recipe_id`
- `body`
- timestamps

Relationships:

- Belongs to one user.
- Belongs to one recipe.
- Has optional images.

### Image

Represents uploaded media through a polymorphic relationship.

Planned fields:

- `id`
- `imageable_type`
- `imageable_id`
- `path`
- `alt_text` (optional)
- `position`
- `is_thumbnail`
- timestamps

An image can belong to a recipe, recipe step, or comment. This allows the same storage and validation logic to support recipe thumbnails, final-dish photos, step photos, and commenter photos.

### Saved recipes

A many-to-many pivot will connect users to recipes they have saved. This relationship powers the **Add to my WhiskList**, **Remove from my WhiskList**, and **My WhiskList** functionality.

## Relationship overview

```text
User       1 ---- N Recipe      (authored recipes)
User       1 ---- N Comment
Recipe     1 ---- N RecipeStep
Recipe     1 ---- N Comment
Recipe     N ---- N Category
Recipe     N ---- N Ingredient  (quantity, unit, notes, is_main)
User       N ---- N Recipe      (saved favorites / My WhiskList)
Recipe     1 ---- N Image       (polymorphic)
RecipeStep 1 ---- N Image       (polymorphic)
Comment    1 ---- N Image       (polymorphic)
```

## Planned pages

### Public pages

- `/` — welcome page with relevant recipe content.
- `/recipes` — recipe search, filtering, sorting, and pagination.
- `/recipes/{recipe}` — recipe details, ingredients, instructions, images, and comments.
- `/categories` — category index.
- `/categories/{category}` — published recipes within a category.
- `/users/{user}` — profile and published recipes when the owner has made the profile public.

### Authentication pages

- `/register`
- `/login`
- Logout action

### Authenticated pages

- `/dashboard`
- `/profile/edit`
- `/my-recipes`
- `/whisklist`
- `/recipes/create`
- `/recipes/{recipe}/edit`

### Administrator pages

- `/admin`
- `/admin/categories`
- `/admin/ingredients`
- `/admin/recipes`
- `/admin/comments`

The final paths may change slightly while preserving RESTful route conventions and named routes.

## Planned backend structure

The application will use Laravel's conventional structure rather than introducing unnecessary architectural layers.

Expected controllers include:

- `HomeController`
- `RecipeController`
- `CommentController`
- `CategoryController`
- `ProfileController`
- `WhiskListController`
- Authentication controllers
- Administrator controllers for categories, ingredients, recipes, and comments
- Focused single-action controllers for non-CRUD operations when appropriate

Expected supporting code includes:

- Eloquent models and relationship methods
- Database migrations
- Model factories and seeders
- Form Request validation classes
- Authorization policies
- Authentication and administrator middleware
- Blade layouts and reusable components
- Mail or notification classes where useful
- Feature and unit tests

## Business rules and authorization

- Only authenticated users may create recipes, comment, or save recipes.
- A regular user may edit or delete only their own recipes.
- A regular user may edit or delete only their own comments.
- Users can always access their own profile and dashboard.
- Other visitors can access a user's profile only when `is_profile_public` is enabled.
- Profile privacy does not hide published recipes from the public recipe pages; it hides the profile page and its grouped author information.
- Only administrators may manage categories and canonical ingredients.
- Administrators may moderate all recipes and comments.
- Only published recipes are publicly visible.
- Draft recipes are visible only to their author and administrators.
- Every recipe must have at least one category, ingredient, instruction step, and thumbnail image.
- The same ingredient cannot appear more than once in one recipe.
- Uploaded files must pass type, size, and image validation.
- Authorization will be enforced in controllers/policies even when a corresponding button is not displayed.

## Recipe moderation

The initial plan uses a lightweight publication workflow:

- A user can save a recipe as a draft.
- The recipe owner can publish their recipe when it is ready.
- An administrator can unpublish or delete content that violates platform rules.

Mandatory approval of every submitted recipe is not part of the initial MVP. It can be introduced later if the core implementation is completed early.

## Search and filtering

The recipe index will initially support:

- Text search by recipe title.
- Filtering by one or more categories.
- Filtering for recipes containing all selected ingredients.
- Pagination.

Possible later filters include difficulty, maximum preparation time, maximum total time, newest recipes, and most-saved recipes.

Queries will eager-load only the relationships required by each page to avoid N+1 database problems.

## Seeded demonstration data

Every model has a factory. The optional `DemoRecipeSeeder` creates fictional recipes and related demonstration data.

To add demo recipes, ingredients, steps, comments, images, and saved lists to a migrated database, run:

```bash
php artisan db:seed --class=DemoRecipeSeeder
```

## Validation and testing strategy

All user-controlled input will be validated, including nested recipe ingredients and steps. Invalid forms will preserve entered values where practical and display useful field-level error messages.

Feature tests will cover the important application behavior:

- Public and protected route access
- Registration, login, and logout
- Recipe CRUD and ownership restrictions
- Comment CRUD and ownership restrictions
- Administrator-only management
- Ingredient and category relationships
- Recipe publishing and moderation
- Saving and removing WhiskList recipes
- Search and ingredient filtering
- File upload validation

## Development milestones

1. Establish authentication, roles, layout, and navigation.
2. Implement the core models, migrations, factories, and seeders.
3. Implement public recipe browsing and recipe detail pages.
4. Implement authorized recipe creation and management.
5. Add structured ingredients, categories, and ordered steps.
6. Add image uploads and storage.
7. Add comments and My WhiskList.
8. Build the administrator area and moderation controls.
9. Add search, filtering, caching, eager loading, and notifications.
10. Complete tests, documentation, demonstration data, and presentation preparation.

Each milestone should be delivered through small, focused commits.

## Technology

- PHP 8.5
- Laravel 13
- MySQL
- Blade templates
- Tailwind CSS 4
- Vite
- Pest
- Composer and npm

## Local development

Detailed installation instructions will be finalized as the project develops. The current local workflow is:

```bash
composer install
npm install
php artisan migrate:fresh --seed
composer run dev
```

The local `.env` file contains machine-specific settings and database credentials and must never be committed.
