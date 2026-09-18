# Security Policy for Easy SAMS

We take security issues seriously. If you believe you have found a vulnerability in the Easy SAMS WordPress plugin or its repository, please follow the process below.

## Supported versions

Only the latest release line published from this repository is supported with security updates.

| Version   | Supported          |
| --------- | ------------------ |
| 3.x       | :white_check_mark: |
| 1.x / 2.x | :x:                |

The predecessor plugin **SAMS Integration** is maintained separately and is scheduled for retirement around mid-2027. Security reports for that plugin should not be filed against this repository.

## Reporting a vulnerability

**Preferred method:** Use GitHub's **Private vulnerability reporting** feature:

1. Go to <https://github.com/renao/easy-sams/security/advisories/new>
2. Submit a private security advisory with a clear description of the issue, steps to reproduce, and affected version(s).

If you cannot use GitHub's reporting feature, you may also open a confidential issue or contact the maintainer through the contact options visible on the GitHub profile. Please do **not** disclose security issues in public issues, discussions, or pull requests before they are resolved.

## What to include

A helpful report should contain:

- Affected file(s) and function(s), if known
- Steps to reproduce the issue
- Proof-of-concept or minimal example, if possible
- Impact assessment (what an attacker could do)
- Suggested fix, if you have one

## Response process

Easy SAMS is an uncommercial hobby project. We handle security reports on a **best-effort basis** and cannot promise fixed response times.

Typical handling:

- We try to acknowledge receipt within a few days.
- We investigate as soon as time allows.
- If a fix is feasible and appropriate, we will coordinate disclosure and publish a security advisory.
- We credit reporters who wish to be named, unless they prefer to remain anonymous.

If a report is out of scope or cannot be addressed in the near future, we will say so clearly.

## Scope

In scope for this policy:

- The plugin source code in this repository
- The GitHub Actions workflows
- The release scripts under `scripts/`

Out of scope:

- Vulnerabilities in WordPress core, themes, or other plugins
- Vulnerabilities in the SAMS API or infrastructure operated by third parties
- Social engineering attacks against users or maintainers

## Secure configuration recommendations

- Keep API keys out of version control. Use environment variables or `wp-config.php` constants instead.
- Use HTTPS-only SAMS API endpoints.
- Restrict WordPress admin accounts and follow the principle of least privilege.
- Keep WordPress core, PHP, and all plugins up to date.

---

*Last updated: 2026-09-18*
