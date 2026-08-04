# Codex Plugin Marketplace Repair Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restore the intended Codex plugin set and remove plugin-loader warnings caused by invalid cross-platform marketplace state.

**Architecture:** Treat `~/.codex/config.toml` and `~/.codex/plugins/cache` as one configuration unit managed through supported `codex plugin` commands. Back up configuration first, remove irrecoverable Windows-local records, reconstruct the recoverable Git marketplace, install the requested plugins, and verify MCP registration plus fresh-process logs.

**Tech Stack:** Codex CLI 0.146.0, TOML global configuration, Git-backed Codex plugin marketplaces, stdio/HTTP MCP registrations.

## Global Constraints

- Preserve `node_repl`, `chrome-devtools`, and `openaiDeveloperDocs` MCP registrations.
- Preserve all existing project working-tree changes.
- Install or verify Superpowers, Octo, Jira, and Clockify only.
- Do not invent Linux paths for unavailable Windows-local OpenAI marketplace bundles.
- Stop on an unrecoverable marketplace/plugin installation error; do not delete additional functionality.
- Every write under `~/.codex` requires approved elevated permission.

---

### Task 1: Capture the failing state and create a rollback point

**Files:**
- Read: `/home/darven/.codex/config.toml`
- Create: `/home/darven/.codex/config.toml.pre-plugin-repair-20260804`

**Interfaces:**
- Consumes: Current Codex global configuration and plugin cache.
- Produces: A byte-for-byte configuration backup and baseline command output.

- [ ] **Step 1: Verify the current failure**

Run:

```bash
codex plugin marketplace list
codex plugin list
```

Expected: both commands fail while resolving `openai-bundled`, `openai-primary-runtime`, and `darven-plugins` marketplace roots.

- [ ] **Step 2: Record the configured MCP registrations**

Run:

```bash
codex mcp list
```

Expected: `node_repl`, `chrome-devtools`, and `openaiDeveloperDocs` are enabled.

- [ ] **Step 3: Create the rollback copy**

Run:

```bash
cp --no-clobber /home/darven/.codex/config.toml /home/darven/.codex/config.toml.pre-plugin-repair-20260804
cmp /home/darven/.codex/config.toml /home/darven/.codex/config.toml.pre-plugin-repair-20260804
```

Expected: `cp` and `cmp` exit 0, proving the backup is identical.

### Task 2: Remove irrecoverable marketplace state

**Files:**
- Modify through Codex CLI: `/home/darven/.codex/config.toml`
- Modify through Codex CLI: `/home/darven/.codex/plugins/cache/`

**Interfaces:**
- Consumes: The rollback copy from Task 1.
- Produces: A global configuration containing only marketplace sources resolvable in Linux.

- [ ] **Step 1: Remove stale bundled plugin enablements**

Run each command independently:

```bash
codex plugin remove documents@openai-primary-runtime
codex plugin remove spreadsheets@openai-primary-runtime
codex plugin remove presentations@openai-primary-runtime
codex plugin remove pdf@openai-primary-runtime
codex plugin remove template-creator@openai-primary-runtime
codex plugin remove sites@openai-bundled
codex plugin remove visualize@openai-bundled
codex plugin remove browser@openai-bundled
```

Expected: each selector is removed from plugin configuration. If the CLI reports that a missing snapshot prevents removal, continue to Step 2; marketplace removal is then expected to clear its associated stale selectors.

- [ ] **Step 2: Remove invalid Windows-local marketplace sources**

Run:

```bash
codex plugin marketplace remove openai-bundled
codex plugin marketplace remove openai-primary-runtime
```

Expected: both commands exit 0 and remove the unavailable Windows-local sources.

- [ ] **Step 3: Remove the incomplete Darven snapshot before rebuilding it**

Run:

```bash
codex plugin marketplace remove darven-plugins
```

Expected: command exits 0 without changing the requested Jira/Clockify outcome, which is restored in Task 3.

- [ ] **Step 4: Verify remaining marketplace health**

Run:

```bash
codex plugin marketplace list
```

Expected: the remaining `nyldn-plugins` and `superpowers-dev` marketplaces resolve without snapshot errors.

### Task 3: Restore Git-backed marketplaces and requested plugins

**Files:**
- Modify through Codex CLI: `/home/darven/.codex/config.toml`
- Create through Codex CLI: `/home/darven/.codex/.tmp/marketplaces/darven-plugins/`
- Create through Codex CLI: `/home/darven/.codex/plugins/cache/`

**Interfaces:**
- Consumes: Healthy marketplace configuration from Task 2 and configured Git access.
- Produces: Installed Superpowers, Octo, Jira, and Clockify plugin cache entries.

- [ ] **Step 1: Re-add the Darven marketplace from its canonical configured repository**

Run:

```bash
codex plugin marketplace add https://github.com/fkdarven/publishers-ops.git --json
```

Expected: JSON reports a successful marketplace add named `darven-plugins`.

- [ ] **Step 2: Install Jira and Clockify**

Run each command independently:

```bash
codex plugin add jira@darven-plugins --json
codex plugin add clockify@darven-plugins --json
```

Expected: each command reports a successful installation and creates its plugin cache entry.

- [ ] **Step 3: Install Octo**

Run:

```bash
codex plugin add octo@nyldn-plugins --json
```

Expected: JSON reports a successful Octo installation.

- [ ] **Step 4: Verify or install Superpowers**

Run:

```bash
codex plugin add superpowers@superpowers-dev --json
```

Expected: JSON reports that Superpowers is installed or already installed; its cache remains available under `plugins/cache/superpowers-dev/superpowers/`.

### Task 4: Verify the repaired runtime configuration

**Files:**
- Read: `/home/darven/.codex/config.toml`
- Read: `/home/darven/.codex/plugins/cache/`
- Read: `/home/darven/.codex/logs_2.sqlite` and current WAL file

**Interfaces:**
- Consumes: Installed plugin and marketplace state from Task 3.
- Produces: Evidence that marketplace, plugin, MCP, and fresh-process startup checks are healthy.

- [ ] **Step 1: Verify marketplaces and plugins**

Run:

```bash
codex plugin marketplace list
codex plugin list
```

Expected: both exit 0; the output includes `superpowers`, `octo`, `jira`, and `clockify`, with no invalid snapshot errors.

- [ ] **Step 2: Verify MCP preservation**

Run:

```bash
codex mcp list
```

Expected: `node_repl`, `chrome-devtools`, and `openaiDeveloperDocs` remain enabled.

- [ ] **Step 3: Run the built-in health check**

Run:

```bash
codex doctor --summary --no-color
```

Expected: no plugin marketplace or MCP configuration failures. Environment-only PATH alias warnings are recorded separately and are not plugin/MCP failures.

- [ ] **Step 4: Start a fresh Codex process and inspect new plugin-loader output**

Run:

```bash
codex plugin list
strings /home/darven/.codex/logs_2.sqlite /home/darven/.codex/logs_2.sqlite-wal
```

Filter the fresh output for `failed to load plugin: plugin is not installed` and compare process/session identifiers with the pre-repair baseline.

Expected: no new post-repair plugin-loader warnings name the repaired or removed selectors.

- [ ] **Step 5: Confirm project work stayed untouched**

Run:

```bash
git status --short
git diff --stat
```

Expected: no project source change was introduced by the configuration repair; only the already committed design/plan documentation history differs from the pre-repair repository state.
