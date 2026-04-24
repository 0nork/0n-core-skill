# 0n Core Skill

Universal skill file for the 0n ecosystem. One token, 96 services, 1,554 tools.

## Install

```bash
cp -r . ~/.claude/skills/0n-core
```

Then type `/0n` in Claude Code.

## What's Inside

- **SKILL.md** — Brain of the skill
- **onboarding/** — Universal onboarding flow + HSM scripts
- **assets/prebuilt/** — Chrome extension, dashboard, Slack app, WordPress plugin, Claude Code config
- **references/** — Ecosystem map, API docs, tool catalog

## Platforms

| Platform | Location | Auth | Notes |
|----------|----------|------|-------|
| Claude Code | `/extensions/claude-code/` | Session config | Full CLI integration |
| Chrome Extension | `/extensions/chrome/` | Side panel | LinkedIn overlay + VPIS |
| Slack | `/extensions/slack/` | Slash commands | `/0n connect 0n_token` |
| WordPress | `/extensions/wordpress/` | Settings page | wp_options storage |
| ChatGPT | `/extensions/chatgpt/` | GPT Actions | Custom GPT + OpenAPI schema |
| Gemini | `/extensions/gemini/` | Function calling | Gem + function declarations |

## Quick Start by Platform

### ChatGPT Custom GPT
See `extensions/chatgpt/README.md` — import `openapi.yaml` as GPT Actions, paste `system-prompt.md` as instructions.

### Google Gemini Gem
See `extensions/gemini/README.md` — paste `system-prompt.md` as system instructions, load `function-declarations.json` for function calling.

### Claude Code
```bash
cp extensions/claude-code/session-config.json ~/.0n/session.json
```

Built by [RocketOpp LLC](https://rocketopp.com) — [0ncore.com](https://0ncore.com)
