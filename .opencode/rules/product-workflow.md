# Existing-Product Workflow

## Sources of Truth

- This is an existing product. Do not treat discovery as a greenfield redesign.
- `AGENTS.md` and `.opencode/rules/*.md` define implementation conventions. Extension instructions and design presets must not replace them.
- Confirmed business specifications live in `openspec/specs/`. Observed code behavior is evidence, not automatically an approved requirement.
- OpenSpec changes contain requirements, design, and detailed tasks for substantial improvements.
- Kanban holds the overall backlog and small tasks. A large task has one card referencing its OpenSpec change; do not duplicate all implementation tasks as cards.
- Worktree Decisions records consequential choices, rejected alternatives, and reasons. Reference relevant specifications rather than duplicating them.
- Do not assume these extensions synchronize their statuses.

## Choosing a Workflow

- Small, clear, low-risk changes can proceed from a Kanban card or direct user request without an OpenSpec change.
- Use OpenSpec for business-sensitive, cross-module, ambiguous, or multi-session work, even when the eventual code change is small.
- Group related visual adjustments under a bounded outcome, not a permanent miscellaneous bucket.
- Review existing specifications and relevant worktree decisions before proposing a change.
- A card entering Review or an agent becoming idle does not prove implementation correctness. Mark work Done only after verification and user acceptance.
- Kanban currently deletes a task when its linked session is deleted. Archive sessions instead if the task must be retained.
- Do not archive OpenSpec changes or convert decisions into repository documentation without authorization.

## Module-by-Module Discovery

Spec Miner is the selected future discovery skill; do not assume it is installed.

1. Inventory modules, integrations, roles, and cross-module dependencies from the repository.
2. Review one bounded module or end-to-end workflow at a time: routes, services, validation, permissions, database constraints, UI, and relevant tests.
3. Present findings in business terms with code locations. Ask at most three focused questions per round, using concrete scenarios.
4. Do not ask the user for repository facts that can be inspected. Ask about intent, exceptions, conflicting behavior, and priorities.
5. Distinguish confirmed requirements, observed behavior, behavior verified by an executed check, open questions, and known gaps.
6. Track coverage and deferred areas explicitly. Existing tests alone do not establish verified behavior.
7. Obtain the user's confirmation before publishing a module's business baseline. Do not invent business rules or document a suspected bug as approved behavior.
8. Record approved gaps separately from the baseline. Distinguish behavior bugs, missing functionality, and missing test coverage.
9. Discovery does not authorize implementation, database resets, or data changes.

## Skill Selection

- Honor explicitly requested skills and the installed skills' mandatory activation rules. Mention missing skills instead of silently pretending to use them.
- OpenSpec skills organize the workflow; they do not replace the implementation skills below.
- Laravel PHP: `laravel-best-practices`.
- Vue / TypeScript components: `vue-best-practices`; add `inertia-vue-development` for Inertia pages, forms, and navigation.
- Tailwind styling: `tailwindcss-development`.
- UI design and review: `impeccable` and `ui-ux-pro-max` when their activation rules apply.
- Dashboard work: `creating-dashboards` when applicable.
- Pest tests: `pest-testing`.
- Spatie media features: `medialibrary-development`.
- Commits: `commit`, only when a commit is requested.
- When Spec Miner is installed, use it for evidence gathering together with this discovery interview loop. Adapt its default output to OpenSpec rather than creating a competing specifications tree.
- Task descriptions should name explicitly required skills and acceptance criteria. Selecting one skill does not waive other mandatory triggers.
- Skill Bundles must not disable required skills or edit downloaded skill metadata behind `ai:setup`. Review changes before applying a bundle.

## Browser and Design Work

- Design Studio is the preferred browser provider. Server Browser may remain installed but disabled as a fallback.
- Keep Design Mode off until a design task is requested and the generated harness has been reviewed for conflicts.
- Prefer existing PrimeVue props, theme tokens, configuration, and shared styles before introducing custom components or wrappers.
- Confirm whether a visual change is local, shared, or application-wide before making broad configuration changes.
- Do not scaffold a replacement application or apply a new design preset to this existing product without approval.
- Use synthetic/local development data for browser verification. Do not submit real sales or change stock merely to test browser integration.
