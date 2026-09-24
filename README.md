# Kawakib — Basis

A Laravel 12 + Inertia/React starter kit: authentication, role-based access
control, an audited CRUD scaffold and an admin console to build products on.

## What is in the box

| Area | What you get |
| --- | --- |
| Auth | Login with throttling, logout, forgot/reset password, optional TOTP two-factor with recovery codes |
| Feature flags | `AUTH_TWO_FACTOR=false` drops two-factor from a project entirely |
| Accounts | Self-service profile, password change, appearance |
| RBAC | Roles, permissions, per-route `permission:` middleware, permission-aware navigation |
| CRUD scaffold | Contract → Service → Controller with paging, search, sorting, bulk delete, soft delete and restore |
| Audit | Every create, update, delete and restore recorded with the actor, on a read-only Activity Log screen |
| Settings | Key/value application settings |

## Getting started

```bash
composer setup
```

That installs dependencies, creates `.env`, generates a key, migrates,
**seeds**, and builds the front end.

Seeding creates the permissions, a `super-admin` role and one admin user.
Set the credentials before seeding:

```dotenv
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@kawakib.test
ADMIN_PASSWORD=
```

Leave `ADMIN_PASSWORD` empty and the seeder generates one and prints it
**once** — copy it from the console output. Re-seeding never rewrites an
existing admin's password.

Then:

```bash
composer dev     # server, queue worker, logs and vite together
```

### Turning two-factor off

Not every project wants it. Set `AUTH_TWO_FACTOR=false` and the two-factor
screens 404, the settings tab disappears and nobody is challenged at login.

Its routes stay registered even when it is off, because Wayfinder builds the
front-end route helpers from the route table — removing them would break the
build for pages that import them. A middleware makes them behave as though
they do not exist.

Users who already enrolled keep their secret: switching it off does not lock
them out, and switching it back on demands their code again.

Sign in at `/auth/login` and the console is at `/backoffice`.

## Checks

Run these before pushing.

```bash
composer test          # pint --test + the full Pest suite
bun run types          # tsc --noEmit
bun run format:check   # prettier
bun run build          # also regenerates the Wayfinder route helpers
```

Wayfinder generates `resources/js/routes` and `resources/js/actions` from the
route table during the build, so **run `bun run build` after adding a route**
or the typecheck will not see it.

## Adding a module

The four setting modules are all the same shape; copy one.

1. Migration and model.
2. `app/Contract/<Area>/ThingContract.php` extending `BaseContract`.
3. `app/Service/<Area>/ThingService.php` extending `BaseService`.
4. Bind the two in `app/Providers/ContractProvider.php`.
5. Controller, form request, routes with `permission:thing.*` middleware.
6. Add `thing` to `RbacSeeder::MODULES` and re-seed.
7. React `index.tsx` (via the shared `IndexPage`) and `form.tsx`.

`BaseService` gives paging, filtering, sorting, soft delete, restore and
audit logging to every module. If you override `create` or `update`, call
`recordActivity()` yourself — there is a test that checks every override
does.

## Notes

- `permission:` middleware is the access boundary. The `usePermission()` hook
  only hides controls that would 403 anyway; it is not a security control.
- Deleting a user is a soft delete, and their email stays reserved until the
  record is restored or purged.
- A user cannot delete their own account, and the last `super-admin` cannot
  be deleted.
