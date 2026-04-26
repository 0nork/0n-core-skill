# 0n for WordPress

Turn any WordPress site into a connected 0n service. One token, full control from Claude, Chrome, Slack, your phone — anywhere 0n runs.

## What It Does

This plugin does two things:

1. **Installs an MCP server endpoint** on your WordPress site at `/wp-json/0n/v1/mcp`, exposing the WP REST API as MCP tools.
2. **Registers your site** with your 0n account, so any 0n surface (Claude, Chrome, Slack, mobile) can call it by name.

Once connected, you can say things like:

- *"Publish a blog post titled 'New Service Launch'"* — from Claude
- *"List all my draft posts"* — from Slack
- *"Update the site tagline to 'Now serving Pittsburgh'"* — from your phone
- *"Install Yoast SEO and activate it"* — from anywhere

## Installation

1. Download `0n-mcp.php` (or the full plugin folder).
2. Upload to `/wp-content/plugins/` and activate, OR install via the WP plugin uploader.
3. Go to **Settings → 0n MCP**.
4. Paste your `0n_` token from [0ncore.com/settings](https://0ncore.com/settings).
5. Click **Connect**.

That's it. Your site is now a connected 0n service.

## MCP Tools Exposed

| Tool | Purpose |
|------|---------|
| `wp_create_post` | Create a post or page |
| `wp_update_post` | Update an existing post |
| `wp_delete_post` | Delete or trash a post |
| `wp_list_posts` | List posts with filters |
| `wp_get_post` | Get a single post by ID |
| `wp_create_user` | Create a user |
| `wp_list_users` | List users |
| `wp_update_option` | Update a WP option |
| `wp_get_option` | Get a WP option |
| `wp_install_plugin` | Install + activate a plugin from the WP repo |
| `wp_site_info` | Site title, URL, version, theme, plugins list |
| `wp_upload_media` | Upload media from a URL |

All tools are token-authenticated. No request without a valid bearer token gets through.

## REST Endpoints

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `POST` | `/wp-json/0n/v1/mcp` | Execute an MCP tool. Body: `{"tool": "wp_create_post", "input": {...}}` |
| `GET` | `/wp-json/0n/v1/mcp/tools` | List available tools and schemas |
| `GET` | `/wp-json/0n/v1/mcp/health` | Site ID, version, tool count (no auth) |

### Example Request

```bash
curl -X POST https://yoursite.com/wp-json/0n/v1/mcp \
  -H "Authorization: Bearer 0n_your_token_here" \
  -H "Content-Type: application/json" \
  -d '{"tool":"wp_create_post","input":{"title":"Hello","content":"World","status":"publish"}}'
```

## How Registration Works

When you click **Connect**:

1. Plugin verifies your token against `https://www.0ncore.com/api/auth/verify-token`.
2. Plugin posts your site URL, MCP endpoint, and tool list to `https://www.0ncore.com/api/services/register`.
3. Your 0n account now knows this WordPress site exists and what it can do.
4. Any AI surface using your account can now route WP commands to this site.

When you click **Disconnect**:

- Plugin calls `/api/services/unregister` and clears local options.
- Your token, profile, and registration are removed.

## Security

- Token is stored in WordPress options (consider using a security plugin to encrypt the database).
- Every MCP request requires a `Bearer` token in the `Authorization` header that exactly matches the connected token (constant-time compare).
- The `/health` endpoint is unauthenticated and intentionally minimal — used for service discovery.
- Plugin install/activate goes through standard WP capability checks at the file system level.

## Requirements

- WordPress 5.6+
- PHP 7.4+
- Outbound HTTPS to `www.0ncore.com`

## Author

RocketOpp LLC — built as part of the [0n Core](https://0ncore.com) ecosystem.
