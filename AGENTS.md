# AGENTS.md

Guidance for coding agents working on this Laravel project.

## Context

- Read `composer.json` and `composer.lock` for PHP requirements and package versions. Use documentation matching the installed versions.
- Check `package.json` and its lockfile before working on frontend tooling, if present.
- Read related code, callers, configuration, and tests before changing behavior. Follow sibling files for naming and structure.

## Conventions

- Preserve the existing directory structure and authentication approach. Reuse framework features and installed packages before introducing dependencies or architecture layers.
- Keep changes focused on the request. Report unrelated issues separately rather than fixing them incidentally.
- Use descriptive names and appropriate PHP parameter and return types, consistent with surrounding code and framework contracts.
- Use Artisan generators where appropriate; check command options with `php artisan help <command>` and use `--no-interaction` for automation.
- Keep controllers focused on HTTP orchestration. Follow existing validation, authorization, and business-logic patterns; do not require a new service or repository for every operation.
- Prefer Eloquent relationships and the query builder where suitable. Check for N+1 queries when loading related records.
- Read environment variables in configuration files; use `config()` in application code.
- Preserve API response contracts and existing error handling unless the requested change requires otherwise.

## Safety

- Validate untrusted input and authorize access to protected resources. Authentication alone is not authorization.
- Keep secrets and personal data out of committed files, logs, and responses.
- Confirm the target environment and database before migrations or destructive commands. Ask before operations that can discard data.
- Isolate tests from production data and real third-party services. Use appropriate fakes or doubles; Laravel HTTP fakes do not intercept every SDK.
- Review generated files and dependency changes. Use package managers to regenerate lockfiles rather than editing package entries manually.

## Verification

- Use the project's existing test runner and formatting configuration; do not switch between PHPUnit and Pest merely to match a preference.
- Add or update focused tests for changed behavior and important failure cases. Documentation-only changes do not require application tests.
- Run the affected tests first, then broader checks as appropriate. Use an isolated test environment before running database tests.
- Typical commands, when supported by the installed tooling:

```bash
php artisan test --filter=TestName
php artisan test
vendor/bin/pint --test
composer validate --strict
composer check-platform-reqs
composer audit
```

- Scope formatting checks to changed files where practical; avoid unrelated formatting rewrites.
- Report what changed, checks performed, failures, and checks not run. Separate pre-existing failures from regressions introduced by the change.

## Project-specific guidance

Add team conventions here as they become necessary. Keep one source of truth; use nested instructions for bounded areas and skills for repeatable task-specific workflows. Verify discovery in the chosen coding client before relying on them. Do not assume Boost, MCP tools, or optional skills are installed.

## References

Adapted in scope from Laravel Boost's [foundation](https://github.com/laravel/boost/blob/main/.ai/foundation.blade.php) and [Laravel conventions](https://github.com/laravel/boost/blob/main/.ai/laravel/core.blade.php), with [Koel](https://github.com/koel/koel/blob/master/AGENTS.md) as an example of separating general guidance from application-specific rules. These are references, not additional instructions to load automatically.
