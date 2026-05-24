# Database Self-Review

Use for migrations, schema docs, repositories, and soft-delete behavior.

Source policies:

- `docs/development/coding-standards.md`
- NENE2: `docs/development/database-migrations.md`

## Checklist

- [ ] Migrations are reversible or rollback strategy is documented.
- [ ] Schema docs or migration comments explain entity/field table relationships.
- [ ] Soft delete columns (`is_deleted`, `deleted_at`) are used consistently when required.
- [ ] Repository queries default to excluding soft-deleted rows unless admin API documented otherwise.
- [ ] Indexes exist for common filter columns (entity_type_id, foreign keys).
- [ ] No secrets or environment-specific values in migration files.
- [ ] SQLite tests cover important repository contracts.
- [ ] PR notes mention this checklist when database-facing.
