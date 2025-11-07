# Random Data Seeding

The `app:dev:seed-random-data` console command populates a local database with opinionated demo data that follows the requirements from `RandomDataGenRules.md`.

## Usage

```bash
docker compose exec php bin/console app:dev:seed-random-data --purge
```

The `--purge` flag truncates all relevant tables before inserting the new sample data. Omit the flag if you only want to populate an empty database—running the command without `--purge` aborts when records are already present.

## Generated Dataset

- Roles: `admin`, `teamlead`, `staff` with permission presets oriented on the project plan
- Users: 2 admins, 10 teamleads, 100 staff members with hashed password `Password123!`
- Projects: 100 unique projects owned by admins or teamleads
- Tasks: For each project between 0 and 50 tasks (both extremes happen at least once)

Tasks receive random priorities, statuses, optional due dates, and assignees (mostly staff). Project and task descriptions use curated text snippets, so the generator works without additional dependencies.

## Extending

For richer fake data you can bring in [`fakerphp/faker`](https://fakerphp.github.io/) and adjust `App\Dev\DataGenerator\RandomDataSeeder`. After installing the package via Composer, inject a `Faker\Generator` into the seeder and swap out the hardcoded pools for dynamic values.
