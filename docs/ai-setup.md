# OpenCode Setup

This repository supports OpenCode only. Its application-specific rules, agents, and MCP configuration are committed; downloaded skills and binaries are not.

## New Device Setup

```bash
composer install
npm ci
npm run ai:setup
npm run ai:doctor
```

Restart OpenCode after the setup completes so it reloads project configuration, agents, and skills.

`npm run ai:setup` restores the project skills pinned in `skills-lock.json`, installs the pinned Impeccable package, and generates Laravel Boost skills for the installed PHP packages. It does not update dependencies, change global OpenCode settings, or require provider credentials.

Sign in to providers separately on each device through OpenCode. Desktop installations may have a different `PATH` than a terminal session; launch `npm run ai:doctor` from the same environment if Laravel Boost is unavailable.

## Ownership

| Asset | Owner | Tracked |
| --- | --- | --- |
| `AGENTS.md`, `.opencode/rules/`, `.opencode/agent/` | This project | Yes |
| `.opencode/skills/commit/` | This project | Yes |
| `opencode.json` | This project | Yes |
| Laravel Boost | Composer | `composer.json` and `composer.lock` |
| Third-party skills | Skills CLI | `skills-lock.json` only |
| Impeccable | npm | `package.json` and `package-lock.json` only |
| `.agents/skills/`, generated `.opencode/skills/`, `.ai/skills/` | Local installation output | No |

## Maintaining Skills

Do not edit an installed third-party skill in place. Update a dependency deliberately, review the change, and commit the changed lockfile. `npm run ai:setup` always restores the committed lock state.

Some `skillPath` values in `skills-lock.json` describe the upstream repository layout and may mention another agent directory. They do not create or enable that agent locally.

Run `npx --no-install skills check` to inspect third-party skill updates. Run `npx --no-install impeccable check` to inspect Impeccable updates. Neither command updates the project automatically.

Project-specific guidance belongs in `AGENTS.md`, `.opencode/rules/`, `.opencode/agent/`, or a tracked project skill under `.opencode/skills/`.
