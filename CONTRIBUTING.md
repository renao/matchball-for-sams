# Contributing to Matchball - Standings & Tables for SAMS

Thank you for your interest in contributing to Matchball - Standings & Tables for SAMS. This document explains how to get started and what we expect from contributions.

## How to contribute

1. **Open an issue first** for larger changes or new features, so we can discuss the approach before you invest time in code.
2. **Fork the repository** and create a feature branch from `main`.
3. **Make your changes** following the existing code style and conventions.
4. **Run the CI checks locally** before pushing:
   ```bash
   find . -type f -name "*.php" -not -path "./artifacts/*" -print0 | xargs -0 -n1 php -l
   shellcheck scripts/*.sh
   bash scripts/create-release-zip.sh
   ```
5. **Open a pull request** with a clear description of the change and the problem it solves.
6. **Respond to review feedback** promptly and respectfully.

## Development workflow

This project uses a small set of agent-assisted workflow files under `.instructions.md` and the `agents/` directory. If you are participating in a structured refinement cycle, follow the workflow described there:

1. Requirements-Interviewer
2. Spec-Validator
3. Security-Reviewer
4. Dev-Assistant
5. Test-Strategist

For small bug fixes or documentation improvements, a lightweight PR process is sufficient.

## Code standards

- **PHP**: Target PHP 7.4 compatibility. Avoid short array syntax? No — short arrays (`[]`) are fine. Use strict comparisons where reasonable.
- **WordPress**: Follow WordPress coding standards for hooks, capabilities, and internationalization. Use the `matchball` textdomain for all translatable strings.
- **Prefixing**: All global identifiers (classes, constants, functions, hooks, option names, transients) must use the `matchball_` / `MATCHBALL_` prefix to avoid collisions with other plugins.
- **REST routes**: Require the `edit_posts` capability or stricter. Do not expose administrative data to anonymous users.
- **API keys**: Never log, display, or serialize API keys unencrypted. Use the existing encryption helpers in `MATCHBALL_Source_Repository`.

## License

Matchball - Standings & Tables for SAMS is licensed under the **GNU General Public License v2 or later** (GPLv2+), consistent with WordPress.org distribution requirements.

By contributing code, documentation, or other material to this repository, you confirm that:

- You have the right to submit the contribution.
- Your contribution is licensed under GPLv2+.
- You are not submitting material that infringes third-party rights.

## Artificial intelligence and tooling

The use of AI-assisted coding tools (such as GitHub Copilot, ChatGPT, or similar) is permitted. If a meaningful portion of your contribution was generated or heavily assisted by such a tool, we appreciate a note in the pull request description. This is **voluntary good practice**, not a legal requirement.

## Commit messages

Use clear, descriptive commit messages. We follow a loose conventional-commit style:

- `feat:` for new features
- `fix:` for bug fixes
- `refactor:` for code restructuring without behavior changes
- `docs:` for documentation changes
- `ci:` for CI/CD changes
- `test:` for test changes

## Code of conduct

Be respectful and constructive. Harassment, discrimination, or abusive behavior will not be tolerated.

## Questions?

If you are unsure about anything, open a discussion or issue and ask. We are happy to help.

---

*Last updated: 2026-09-18*
