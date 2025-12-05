# Development Assets

This directory contains images used for generating random fake data during development.

## Structure

- `random_faces/` - Face images for user profile pictures
- `random_images/` - General images for project and task attachments

## Usage

The `RandomDataSeeder` automatically loads images from these directories when generating fake data:

```bash
docker compose exec php bin/console app:dev:seed-random-data --purge
```

## Requirements

- Images should be in common formats: jpg, jpeg, png, gif, webp
- Images can be in subdirectories (recursive scan)
- Minimum recommended: ~100 face images and ~200 general images
- Images are copied to `var/uploads/images/` with UUID filenames

## Image Distribution

- **User profiles**: 80% chance to get a random face image
- **Projects**: 60% chance to get 1-4 random images
- **Tasks**: 30% chance to get 1-3 random images

