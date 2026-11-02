# Running the tests

Tests use their own database (`<DB name>_test`, or `TEST_DB_DSN` in `.env`) so they never touch dev data.

## One-time setup (fresh clone)

The `*-local.php` test configs are gitignored and come from `environments/dev`.
Copy only the missing ones (do not run `php init`, it overwrites `main-local.php`):

```sh
for f in backend/config/codeception-local.php backend/config/test-local.php \
         common/config/codeception-local.php common/config/test-local.php \
         frontend/config/codeception-local.php frontend/config/test-local.php \
         console/config/test-local.php yii_test; do
  [ -e "$f" ] || cp environments/dev/$f $f
done
```

Create and migrate the test database (inside the backend container):

```sh
docker exec miracle-yii-stack-mysql-1 sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS miracle_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"'
docker exec -w /app miracle-backend-1 php yii_test migrate --interactive=0
```

Re-run `php yii_test migrate` whenever new migrations are added.

## Run

```sh
docker exec -w /app miracle-backend-1 php vendor/bin/codecept build   # after changing suite modules
docker exec -w /app miracle-backend-1 php vendor/bin/codecept run
```

A single app or suite: `cd backend && ../vendor/bin/codecept run Unit`.

Notes
- The test config uses an in-memory cache, so login-throttle counters don't leak between runs.
- `yii_test` seeds the test superadmin with a fixed password (`ADMIN_INITIAL_PASSWORD` is not needed).
- Billing tests set GST to 9/9/18% themselves, independent of the live `params.php` rates.

## Schema drift check (dev / production)

`console/schema/expected.json` is the structure the migrations produce (columns, indexes and
foreign keys, compared by columns rather than names). Check any database against it, read-only:

```sh
php yii schema/check        # exit 1 + list of differences when the DB has drifted
```

It also lists applied migrations whose file no longer exists, which usually explains the drift.

After adding a migration, refresh the snapshot from the clean test DB (the unit test
`SchemaSnapshotTest` fails until you do):

```sh
php yii_test migrate --interactive=0 && php yii_test schema/snapshot
```
