# Codex Plugin Marketplace Repair Design

## Context

Codex is running in a Linux environment with a global configuration that contains marketplace paths created on Windows. The `openai-bundled` and `openai-primary-runtime` paths are unavailable here, and the cached `darven-plugins` marketplace is incomplete. Enabled plugin records whose installations are absent cause repeated `failed to load plugin: plugin is not installed` warnings while Codex assembles MCP runtime configuration.

The explicitly configured MCP servers (`node_repl` and `chrome-devtools`) are registered normally. The warnings originate in plugin loading rather than those MCP registrations.

## Outcome

Restore the useful plugin set without retaining invalid cross-platform configuration:

- preserve the existing MCP server configuration;
- retain and verify Superpowers;
- install Octo from the configured `nyldn-plugins` marketplace;
- refresh the Git-backed `darven-plugins` marketplace and reinstall Jira and Clockify;
- remove stale Windows-local marketplace and plugin records that cannot resolve in Linux;
- retain the official OpenAI Developer Docs MCP added during diagnosis;
- eliminate plugin-loader warnings caused by missing installations.

## Approach

Use a hybrid repair through supported `codex plugin` and `codex mcp` commands. First create a timestamped backup of the global Codex configuration. Refresh or re-add Git marketplaces from their configured repository sources, then install the selected plugins. Remove only irrecoverable Windows-local marketplace records and their stale enabled-plugin records; OpenAI's built-in app connectors remain provided by the current Codex surface and are not replaced with guessed filesystem paths.

This is preferred to removing every missing plugin because Jira and Clockify have recoverable Git sources. It is also preferred to attempting a literal reinstall of bundled OpenAI plugins because their configured source directories do not exist in this environment and no supported replacement local path is available.

## Execution and Safety

All mutations target `~/.codex` and therefore require explicit elevated filesystem permission. The existing configuration is backed up before the first mutation. Commands are run one marketplace or plugin at a time so failures remain attributable and do not leave an ambiguous partial batch.

No project source files are changed as part of the repair. Existing uncommitted plugin-project work remains untouched.

## Verification

After repair:

1. `codex plugin marketplace list` resolves every remaining marketplace.
2. `codex plugin list` reports Superpowers, Octo, Jira, and Clockify without snapshot errors.
3. `codex mcp list` retains `node_repl`, `chrome-devtools`, and `openaiDeveloperDocs`.
4. `codex doctor --summary` is checked for configuration, plugin, and MCP failures.
5. A fresh Codex process is started and its new log records are inspected for `failed to load plugin` warnings.

If a recoverable Git marketplace or plugin cannot be installed, stop at that component, preserve the backup, and report the exact command failure instead of deleting additional functionality.
