<?php

namespace App\Support\DataTransfer;

use BackedEnum;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * One transferable field, described once and consumed four ways.
 *
 * A Column is the single source of truth for a field's export heading, its
 * export value, its import validation, its import cast, and the guidance the
 * sample sheet gives the user. Describing a module means listing its Columns;
 * nothing else about import or export has to be written per module.
 *
 * Lookup columns are the reason imports are tenant-safe by construction: the
 * sheet carries a human-readable name, never a foreign key, so a row can only
 * ever point at a record the importing company already owns.
 */
class Column
{
    private ?string $label = null;

    private ?string $help = null;

    private bool $required = false;

    private bool $unique = false;

    private ?string $uniqueColumn = null;

    private ?Closure $uniqueTransform = null;

    /** @var array<int, string> */
    private array $rules = [];

    private mixed $sample = null;

    private bool $exportable = true;

    private bool $importable = true;

    private ?Closure $exportResolver = null;

    private ?Closure $importCaster = null;

    /** @var class-string<Model>|null */
    private ?string $lookupModel = null;

    private ?string $lookupColumn = null;

    private ?string $relation = null;

    /** @var class-string<BackedEnum>|null */
    private ?string $enumClass = null;

    /** @var array<int, BackedEnum>|null */
    private ?array $enumCases = null;

    /** @var array<string|int, string>|null Stored value => label. */
    private ?array $choices = null;

    /** @var array<int, string> */
    private array $aliases = [];

    private ?int $width = null;

    private function __construct(
        public readonly string $key,
        public readonly ColumnType $type,
    ) {}

    // ── Named constructors ─────────────────────────────────────────

    public static function make(string $key, ColumnType $type = ColumnType::String): self
    {
        return new self($key, $type);
    }

    public static function string(string $key): self
    {
        return new self($key, ColumnType::String);
    }

    public static function text(string $key): self
    {
        return new self($key, ColumnType::Text);
    }

    public static function integer(string $key): self
    {
        return new self($key, ColumnType::Integer);
    }

    public static function decimal(string $key): self
    {
        return new self($key, ColumnType::Decimal);
    }

    public static function boolean(string $key): self
    {
        return new self($key, ColumnType::Boolean);
    }

    public static function date(string $key): self
    {
        return new self($key, ColumnType::Date);
    }

    public static function datetime(string $key): self
    {
        return new self($key, ColumnType::DateTime);
    }

    public static function time(string $key): self
    {
        return new self($key, ColumnType::Time);
    }

    public static function email(string $key): self
    {
        return new self($key, ColumnType::Email);
    }

    public static function phone(string $key): self
    {
        return new self($key, ColumnType::Phone);
    }

    public static function cnic(string $key): self
    {
        return new self($key, ColumnType::Cnic);
    }

    /**
     * A column backed by a PHP enum. The sheet carries the enum's label
     * ("Active"), never its stored value (1).
     *
     * @param  class-string<BackedEnum>  $enumClass
     */
    public static function enum(string $key, string $enumClass): self
    {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException("[{$enumClass}] is not an enum.");
        }

        $column = new self($key, ColumnType::Enum);
        $column->enumClass = $enumClass;

        return $column;
    }

    /**
     * A closed set of values that is not backed by a PHP enum — a database
     * enum column such as gender, stored as 'male' but written as "Male".
     *
     * @param  array<string|int, string>  $choices  Stored value => label.
     */
    public static function choice(string $key, array $choices): self
    {
        $column = new self($key, ColumnType::Enum);
        $column->choices = $choices;

        return $column;
    }

    /**
     * A foreign key carried through the sheet as a human-readable name.
     *
     * $lookupColumn must be unique within a company — it is used both to
     * render the export and to resolve the import, so an export fed straight
     * back into an import round-trips exactly.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function lookup(string $key, string $modelClass, string $lookupColumn): self
    {
        $column = new self($key, ColumnType::Lookup);
        $column->lookupModel = $modelClass;
        $column->lookupColumn = $lookupColumn;

        return $column;
    }

    /**
     * A derived value that exists only in the export — a relationship count,
     * a formatted total. Never importable.
     */
    public static function computed(string $key, Closure $resolver): self
    {
        $column = new self($key, ColumnType::Computed);
        $column->exportResolver = $resolver;
        $column->importable = false;

        return $column;
    }

    /**
     * A value read from a related record — the employee's name behind a
     * teacher, for instance.
     *
     * Almost every module wants one of these next to a lookup: the sheet
     * carries the code because a code is unique, and this adds the name
     * because a name is readable. Declaring it here rather than as a closure
     * per module also registers the relation for eager loading, so the export
     * cannot trip preventLazyLoading().
     *
     * $relation may be a dotted path, e.g. "teacher.employee".
     */
    public static function related(string $key, string $relation, string $attribute): self
    {
        $column = new self($key, ColumnType::Computed);
        $column->importable = false;
        $column->relation = $relation;

        $column->exportResolver = static function (Model $record) use ($relation, $attribute): mixed {
            $related = $record;

            foreach (explode('.', $relation) as $segment) {
                if (! $related instanceof Model) {
                    return null;
                }

                $related = $related->getRelationValue($segment);
            }

            return $related instanceof Model ? $related->getAttribute($attribute) : null;
        };

        return $column;
    }

    // ── Fluent configuration ───────────────────────────────────────

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function help(string $help): self
    {
        $this->help = $help;

        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function unique(bool $unique = true): self
    {
        $this->unique = $unique;

        return $this;
    }

    /**
     * Check this column's uniqueness against a different stored column.
     *
     * Needed wherever the value in the sheet is not what the database holds.
     * CNIC is stored encrypted alongside a SHA-256 column for lookups, so its
     * uniqueness is decided by that hash, not by the ciphertext.
     */
    public function uniqueVia(string $column, ?Closure $transform = null): self
    {
        $this->unique = true;
        $this->uniqueColumn = $column;
        $this->uniqueTransform = $transform;

        return $this;
    }

    /** @param  array<int, string>  $rules */
    public function rules(array $rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    public function sample(mixed $sample): self
    {
        $this->sample = $sample;

        return $this;
    }

    /** Present in exports and sample sheets, ignored on import. */
    public function exportOnly(): self
    {
        $this->importable = false;

        return $this;
    }

    /** Accepted on import, never written to an export. */
    public function importOnly(): self
    {
        $this->exportable = false;

        return $this;
    }

    /** Override how the value is rendered into the export cell. */
    public function exportUsing(Closure $resolver): self
    {
        $this->exportResolver = $resolver;

        return $this;
    }

    /** Override how the raw spreadsheet cell becomes a model attribute. */
    public function importUsing(Closure $caster): self
    {
        $this->importCaster = $caster;

        return $this;
    }

    /**
     * Narrow an enum column to a subset of its cases.
     *
     * Some modules accept fewer values than their enum defines — a branch is
     * Active or Inactive but never Suspended. Importing must not be more
     * permissive than the module's own form.
     *
     * @param  array<int, BackedEnum>  $cases
     */
    public function only(array $cases): self
    {
        $this->enumCases = $cases;

        return $this;
    }

    /**
     * Name the Eloquent relation backing a lookup column.
     *
     * Only needed where the relation is not the camel-cased key minus its
     * "_id" suffix — for example quran_status_id => quranStatusRelation.
     */
    public function relation(string $relation): self
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * Extra headings that map onto this column, so files produced by an
     * earlier version of the template still import.
     *
     * @param  array<int, string>  $aliases
     */
    public function aliases(array $aliases): self
    {
        $this->aliases = $aliases;

        return $this;
    }

    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    // ── Accessors ──────────────────────────────────────────────────

    public function getLabel(): string
    {
        return $this->label ?? Str::headline($this->key);
    }

    public function getHelp(): ?string
    {
        return $this->help;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    /** The stored column uniqueness is decided by. */
    public function uniqueColumn(): string
    {
        return $this->uniqueColumn ?? $this->key;
    }

    /** The sheet value translated into what the unique column actually holds. */
    public function uniqueValue(mixed $value): mixed
    {
        return $this->uniqueTransform !== null
            ? ($this->uniqueTransform)($value)
            : $value;
    }

    public function isExportable(): bool
    {
        return $this->exportable;
    }

    public function isImportable(): bool
    {
        return $this->importable && $this->type !== ColumnType::Computed;
    }

    public function isLookup(): bool
    {
        return $this->type === ColumnType::Lookup;
    }

    public function isEnum(): bool
    {
        return $this->type === ColumnType::Enum;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getImportCaster(): ?Closure
    {
        return $this->importCaster;
    }

    /** @return class-string<Model>|null */
    public function getLookupModel(): ?string
    {
        return $this->lookupModel;
    }

    public function getLookupColumn(): ?string
    {
        return $this->lookupColumn;
    }

    /** @return class-string<BackedEnum>|null */
    public function getEnumClass(): ?string
    {
        return $this->enumClass;
    }

    /**
     * The relation to eager-load so exporting this column never lazy-loads.
     *
     * Model::preventLazyLoading() is enabled outside production, so an export
     * that forgets this throws rather than silently issuing N+1 queries.
     */
    public function getRelation(): ?string
    {
        if ($this->relation !== null) {
            return $this->relation;
        }

        if (! $this->isLookup()) {
            return null;
        }

        return Str::camel(Str::beforeLast($this->key, '_id'));
    }

    /**
     * Every heading that should map onto this column when reading a file.
     *
     * @return array<int, string>
     */
    public function headingCandidates(): array
    {
        return array_values(array_unique(array_merge(
            [$this->getLabel(), $this->key],
            $this->aliases,
        )));
    }

    // ── Export ─────────────────────────────────────────────────────

    /**
     * Render this column's value for one exported record.
     */
    public function exportValue(Model $record): mixed
    {
        if ($this->exportResolver !== null) {
            return ($this->exportResolver)($record);
        }

        if ($this->isLookup()) {
            $relation = $this->getRelation();
            $related = $relation !== null ? $record->getRelationValue($relation) : null;

            return $related instanceof Model
                ? $related->getAttribute($this->lookupColumn)
                : null;
        }

        return $this->formatScalar($record->getAttribute($this->key));
    }

    private function formatScalar(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof BackedEnum) {
            return self::enumLabel($value);
        }

        // A choice column stores a raw scalar ('male'), so it is mapped back
        // to its label here rather than by an enum cast on the model.
        if ($this->choices !== null) {
            return $this->choices[$value] ?? $value;
        }

        if ($value instanceof DateTimeInterface) {
            return match ($this->type) {
                ColumnType::DateTime => $value->format('Y-m-d H:i'),
                ColumnType::Time => $value->format('H:i'),
                default => $value->format('Y-m-d'),
            };
        }

        if ($this->type === ColumnType::Boolean) {
            return $value ? __('data_transfer.yes') : __('data_transfer.no');
        }

        if ($this->type === ColumnType::Time && is_string($value)) {
            return Str::substr($value, 0, 5);
        }

        return $value;
    }

    // ── Sample / validation metadata ───────────────────────────────

    /**
     * The example value written into the sample sheet's demonstration row.
     */
    public function sampleValue(): mixed
    {
        if ($this->sample !== null) {
            return $this->sample;
        }

        return match ($this->type) {
            ColumnType::Integer => 1,
            ColumnType::Decimal => 0.00,
            ColumnType::Boolean => __('data_transfer.yes'),
            ColumnType::Date => now()->format('Y-m-d'),
            ColumnType::DateTime => now()->format('Y-m-d H:i'),
            ColumnType::Time => '09:00',
            ColumnType::Email => 'name@example.com',
            ColumnType::Phone => '0300-1234567',
            ColumnType::Cnic => '35201-1234567-1',
            ColumnType::Enum => $this->allowedValues()[0] ?? '',
            default => '',
        };
    }

    /**
     * The closed set of values this column accepts, where one exists.
     *
     * Enum and boolean columns answer from their own definition. Lookup
     * columns answer from live tenant data and are resolved by the sample
     * generator, which has the query context this object deliberately lacks.
     *
     * @return array<int, string>
     */
    public function allowedValues(): array
    {
        return array_values($this->valueMap());
    }

    /**
     * Stored value => human label, for every column with a closed value set.
     *
     * This is the single place that knows how a value is written in a sheet
     * versus how it is stored, so the export writer, the sample sheet and the
     * import caster cannot disagree about it.
     *
     * @return array<string|int, string>
     */
    public function valueMap(): array
    {
        if ($this->type === ColumnType::Boolean) {
            return [1 => __('data_transfer.yes'), 0 => __('data_transfer.no')];
        }

        if ($this->choices !== null) {
            return $this->choices;
        }

        $map = [];

        foreach ($this->enumCases() as $case) {
            $map[$case->value] = self::enumLabel($case);
        }

        return $map;
    }

    /**
     * The enum cases this column accepts, honouring any only() restriction.
     *
     * @return array<int, BackedEnum>
     */
    public function enumCases(): array
    {
        if ($this->enumClass === null) {
            return [];
        }

        return $this->enumCases ?? $this->enumClass::cases();
    }

    /**
     * Baseline validation rules, merged with whatever the definition added.
     *
     * Uniqueness and lookup existence are not expressed here: both need the
     * importing company's id, which only the row validator holds.
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable'];

        // A lookup has already been resolved to an integer id by the time
        // these rules run, so the enum's own value set no longer applies.
        if ($this->type === ColumnType::Enum) {
            return array_values(array_unique(array_merge($rules, $this->rules)));
        }

        return array_values(array_unique(array_merge(
            $rules,
            $this->type->baseRules(),
            $this->rules,
        )));
    }

    /**
     * Read an enum case's human label, falling back to its case name for
     * enums that do not define one.
     */
    public static function enumLabel(BackedEnum $case): string
    {
        return method_exists($case, 'label') ? $case->label() : $case->name;
    }
}
