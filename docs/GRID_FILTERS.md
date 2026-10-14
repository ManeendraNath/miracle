# Reusable Grid Filters

This document explains how to use the reusable GridView filtering components available in the application.

Currently supported:

- Searchable Select2 dropdown filters
- Status filters
- Date-range filters
- UNIX timestamp date-range filtering
- SQL DATE/DATETIME range filtering
- Backend and frontend reuse

---

## Structure

Reusable filtering functionality lives under `common` so it can be used by both backend and frontend applications.

```text
common/
├── helpers/
│   └── GridFilterHelper.php
│
└── models/
    └── traits/
        └── DateRangeSearchTrait.php
```

Search models remain application-specific:

```text
backend/
└── models/
    └── searches/
        └── UserSearch.php
```

A future frontend search model can use the same common components:

```text
frontend/
└── models/
    └── searches/
        └── SomeSearch.php
```

---

# 1. Dependencies

The filters use:

```text
kartik-v/yii2-widget-select2
kartik-v/yii2-date-range
```

Install with:

```bash
composer2 require kartik-v/yii2-widget-select2
composer2 require kartik-v/yii2-date-range
```

Verify:

```bash
composer2 show kartik-v/yii2-widget-select2
composer2 show kartik-v/yii2-date-range
```

> Important: Composer uses CLI PHP. Do not change the global Apache PHP version merely to install these packages, especially on development machines hosting multiple PHP projects.

---

# 2. GridFilterHelper

Location:

```text
common/helpers/GridFilterHelper.php
```

This helper contains reusable GridView UI filters.

Example methods:

```php
GridFilterHelper::select2()
GridFilterHelper::dateRange()
```

---

## Searchable Select2 Filter

Example:

```php
'filter' => GridFilterHelper::select2(
    $searchModel,
    'status',
    User::getStatuses(),
    'All Statuses'
),
```

This is useful for:

- Status
- Role
- Type
- Category
- Payment status
- Hosting status
- Any fixed list of values

Example:

```php
[
    'attribute' => 'status',

    'filter' => GridFilterHelper::select2(
        $searchModel,
        'status',
        User::getStatuses(),
        'All Statuses'
    ),
],
```

Keep the actual status definitions in the relevant model instead of duplicating them in views:

```php
User::getStatuses()
```

---

# 3. Date Range Filter

Example:

```php
'filter' => GridFilterHelper::dateRange(
    $searchModel,
    'created_at_range',
    'Created date'
),
```

The filter value has the following format:

```text
YYYY-MM-DD - YYYY-MM-DD
```

Example:

```text
2026-10-01 - 2026-10-14
```

The GridView filter only handles the UI.

The SearchModel is responsible for converting the selected range into a database query.

---

# 4. DateRangeSearchTrait

Location:

```text
common/models/traits/DateRangeSearchTrait.php
```

Use:

```php
use common\models\traits\DateRangeSearchTrait;
```

Then inside a search model:

```php
class UserSearch extends User
{
    use DateRangeSearchTrait;
}
```

The trait supports two types of database columns.

### UNIX timestamp columns

Use:

```php
applyTimestampDateRange()
```

For fields such as:

```text
created_at INT
updated_at INT
```

### SQL DATE/DATETIME columns

Use:

```php
applyDateRange()
```

For fields such as:

```text
created_at DATETIME
invoice_date DATE
```

Do not use `applyTimestampDateRange()` on SQL DATETIME columns.

---

# 5. Adding Created At Filtering

## Step 1: Add a SearchModel property

```php
public ?string $created_at_range = null;
```

Do not use `created_at` itself to hold the range because `created_at` represents the actual database timestamp.

---

## Step 2: Add validation rule

```php
[
    ['created_at_range'],
    'safe',
],
```

Or with multiple range fields:

```php
[
    [
        'created_at_range',
        'updated_at_range',
    ],
    'safe',
],
```

---

## Step 3: Enable the trait

```php
use common\models\traits\DateRangeSearchTrait;

class UserSearch extends User
{
    use DateRangeSearchTrait;
```

---

## Step 4: Apply the query filter

For a UNIX timestamp:

```php
$this->applyTimestampDateRange(
    $query,
    'user.created_at',
    $this->created_at_range
);
```

Using the qualified column name is preferred:

```text
user.created_at
```

instead of:

```text
created_at
```

This avoids ambiguity if joins are added later.

---

## Step 5: Add the GridView filter

Import:

```php
use common\helpers\GridFilterHelper;
```

Then:

```php
[
    'attribute' => 'created_at',

    'filter' => GridFilterHelper::dateRange(
        $searchModel,
        'created_at_range',
        'Created date'
    ),

    'value' => static function (User $model): string {
        return Yii::$app->formatter->asDatetime(
            $model->created_at,
            'php:d M Y, h:i A'
        );
    },
],
```

---

# 6. Adding Updated At Filtering

SearchModel:

```php
public ?string $updated_at_range = null;
```

Rule:

```php
[
    ['updated_at_range'],
    'safe',
],
```

Query:

```php
$this->applyTimestampDateRange(
    $query,
    'user.updated_at',
    $this->updated_at_range
);
```

GridView:

```php
[
    'attribute' => 'updated_at',

    'filter' => GridFilterHelper::dateRange(
        $searchModel,
        'updated_at_range',
        'Updated date'
    ),

    'value' => static function (User $model): string {
        return Yii::$app->formatter->asDatetime(
            $model->updated_at,
            'php:d M Y, h:i A'
        );
    },
],
```

---

# 7. Complete SearchModel Pattern

A typical SearchModel looks like:

```php
<?php

namespace backend\models\searches;

use common\models\traits\DateRangeSearchTrait;
use common\models\User;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class UserSearch extends User
{
    use DateRangeSearchTrait;

    public ?string $created_at_range = null;
    public ?string $updated_at_range = null;

    public function rules(): array
    {
        return [
            [['id', 'status'], 'integer'],

            [
                [
                    'username',
                    'email',
                    'created_at_range',
                    'updated_at_range',
                ],
                'safe',
            ],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(
        array $params,
        ?string $formName = null
    ): ActiveDataProvider {
        $query = User::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params, $formName);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query
            ->andFilterWhere([
                'like',
                'username',
                $this->username,
            ])
            ->andFilterWhere([
                'like',
                'email',
                $this->email,
            ]);

        $this->applyTimestampDateRange(
            $query,
            'user.created_at',
            $this->created_at_range
        );

        $this->applyTimestampDateRange(
            $query,
            'user.updated_at',
            $this->updated_at_range
        );

        return $dataProvider;
    }
}
```

---

# 8. Reusing in Another SearchModel

Example:

```php
class HostingSearch extends Hosting
{
    use DateRangeSearchTrait;

    public ?string $created_at_range = null;
}
```

Rule:

```php
[['created_at_range'], 'safe'],
```

Query:

```php
$this->applyTimestampDateRange(
    $query,
    'hosting.created_at',
    $this->created_at_range
);
```

GridView:

```php
'filter' => GridFilterHelper::dateRange(
    $searchModel,
    'created_at_range',
    'Created date'
),
```

No new date-picker implementation is required.

---

# 9. Frontend/User Dashboard Usage

Because both reusable classes live in `common`, frontend dashboards can use exactly the same functionality.

For example:

```php
namespace frontend\models\searches;

use common\models\traits\DateRangeSearchTrait;

class InvoiceSearch extends Invoice
{
    use DateRangeSearchTrait;
}
```

And the frontend view can use:

```php
use common\helpers\GridFilterHelper;
```

There should be no dependency such as:

```text
frontend -> backend
```

Instead:

```text
                 common
              /          \
             /            \
        backend          frontend
```

Both applications consume the shared functionality from `common`.

---

# 10. Which Date Method Should I Use?

### Integer UNIX timestamp

Database example:

```text
created_at = 1791957300
```

Use:

```php
$this->applyTimestampDateRange(...);
```

Common examples:

```text
created_at
updated_at
```

in standard Yii2 timestamp-based tables.

### SQL DATETIME

Database example:

```text
2026-10-14 09:30:00
```

Use:

```php
$this->applyDateRange(...);
```

### SQL DATE

Database example:

```text
2026-10-14
```

Use the date/datetime range implementation appropriate to that model rather than converting it as a UNIX timestamp.

---

# 11. New Filter Checklist

When adding a reusable date filter to a new GridView:

1. Add `DateRangeSearchTrait`.
2. Add the `*_range` property.
3. Mark the property `safe`.
4. Load and validate the SearchModel normally.
5. Apply the appropriate date-range method.
6. Add `GridFilterHelper::dateRange()` to GridView.
7. Use qualified database column names where appropriate.
8. Test clearing the filter.
9. Test a single-day range.
10. Test a multi-day range.

Example:

```php
public ?string $created_at_range = null;
```

```php
[['created_at_range'], 'safe'],
```

```php
$this->applyTimestampDateRange(
    $query,
    'table_name.created_at',
    $this->created_at_range
);
```

```php
'filter' => GridFilterHelper::dateRange(
    $searchModel,
    'created_at_range',
    'Created date'
),
```

---

# 12. Design Rules

## Keep UI logic in the helper

Reusable widget configuration belongs in:

```text
common/helpers/GridFilterHelper.php
```

Examples:

```php
GridFilterHelper::select2()
GridFilterHelper::dateRange()
```

## Keep query logic in the trait

Reusable date query behaviour belongs in:

```text
common/models/traits/DateRangeSearchTrait.php
```

## Keep searchable attributes explicit

Each SearchModel should explicitly declare:

```php
public ?string $created_at_range = null;
```

rather than having a base SearchModel automatically create arbitrary search attributes.

This keeps each SearchModel clear about what filters it supports.

---

# 13. Important Notes for This Development Environment

There are multiple PHP projects on the development machine.

Do not change Apache's global PHP module simply because Composer requires a PHP extension.

Composer dependency operations use CLI PHP.

Check CLI PHP with:

```bash
php -v
```

Check an extension with:

```bash
php -m | grep -i curl
```

Use the project's working Composer 2 command:

```bash
composer2
```

When adding dependencies, commit:

```text
composer.json
composer.lock
```

Do not commit `vendor/` when deployment installs packages from `composer.lock`.

---

# Quick Reference

### Searchable status

```php
GridFilterHelper::select2(
    $searchModel,
    'status',
    User::getStatuses(),
    'All Statuses'
)
```

### Date picker

```php
GridFilterHelper::dateRange(
    $searchModel,
    'created_at_range',
    'Created date'
)
```

### UNIX timestamp query

```php
$this->applyTimestampDateRange(
    $query,
    'user.created_at',
    $this->created_at_range
);
```

### SQL date/datetime query

```php
$this->applyDateRange(
    $query,
    'table.date_column',
    $this->date_range
);
```

---

## Maintenance Principle

Keep the responsibilities separated:

```text
GridFilterHelper
    -> reusable filter UI

DateRangeSearchTrait
    -> reusable date query logic

SearchModel
    -> declares supported filters

GridView
    -> uses the shared filters
```

This allows the same filtering functionality to be reused across backend administration pages and future frontend/user dashboard pages without duplicating the implementation.
