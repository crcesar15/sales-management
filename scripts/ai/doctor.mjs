import { existsSync, lstatSync, readFileSync } from "node:fs";
import { spawnSync } from "node:child_process";

const failures = [];

function requirePath(path) {
  if (!existsSync(path)) {
    failures.push(`Missing ${path}. Run npm run ai:setup.`);
  }
}

function requireCommand(command, args) {
  const result = spawnSync(command, args, { stdio: "ignore" });

  if (result.status !== 0) {
    failures.push(`Unable to run ${command} ${args.join(" ")}.`);
  }
}

requirePath("opencode.json");
requirePath("AGENTS.md");
requirePath(".opencode/rules");
requirePath(".opencode/agent");
requirePath(".opencode/skills/commit/SKILL.md");
requirePath(".opencode/skills/impeccable/SKILL.md");
requirePath("node_modules/.bin/skills");
requirePath("node_modules/.bin/openspec");
requirePath("openspec/config.yaml");
requirePath(".opencode/commands/opsx-explore.md");
requirePath(".opencode/skills/openspec-explore/SKILL.md");
requirePath("vendor/autoload.php");

if (existsSync("opencode.json")) {
  const config = JSON.parse(readFileSync("opencode.json", "utf8"));

  if (!config.mcp?.["laravel-boost"]) {
    failures.push("opencode.json does not configure the laravel-boost MCP server.");
  }
}

for (const path of [".agents/skills", ".ai/skills"]) {
  if (existsSync(path) && lstatSync(path).isSymbolicLink()) {
    failures.push(`${path} must be a directory, not a symlink.`);
  }
}

requireCommand("npx", ["--no-install", "skills", "list"]);
requireCommand("php", ["artisan", "boost:list-skills"]);
requireCommand("npx", ["--no-install", "openspec", "list", "--json"]);

if (failures.length > 0) {
  console.error("AI setup is incomplete:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log("AI setup is ready.");
