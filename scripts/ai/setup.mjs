import { existsSync } from "node:fs";
import { spawnSync } from "node:child_process";

function run(command, args) {
  const result = spawnSync(command, args, { stdio: "inherit" });

  if (result.error) {
    throw new Error(`Unable to run ${command}: ${result.error.message}`);
  }

  if (result.status !== 0) {
    process.exit(result.status ?? 1);
  }
}

if (!existsSync("node_modules/.bin/skills")) {
  throw new Error("Run npm ci before npm run ai:setup.");
}

if (!existsSync("vendor/autoload.php")) {
  throw new Error("Run composer install before npm run ai:setup.");
}

run("npx", ["--no-install", "skills", "install", "--agent", "opencode", "--frozen-lockfile"]);
run("npx", ["--no-install", "impeccable", "install", "--providers=opencode", "--project", "--no-hooks", "--yes", "--force"]);
run("php", ["artisan", "boost:install", "--skills", "--no-interaction"]);
run("npx", ["--no-install", "openspec", "init", "--tools", "opencode", "--profile", "core", "--no-animation"]);
