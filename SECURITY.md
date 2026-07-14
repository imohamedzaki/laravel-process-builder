# Security Policy

Laravel Process Builder is a developer tool that can read application architecture
(routes, controllers) and, when generation is explicitly enabled, write PHP files
to the host application. Because of that capability, security issues here can
have an outsized impact. See [docs/security.md](docs/security.md) for the full
threat model and the guarantees the package is designed to provide.

## Supported versions

While the package is pre-1.0, only the latest release on the default branch
receives security fixes.

| Version        | Supported |
| -------------- | --------- |
| `main` / latest | Yes       |
| Older tags      | No        |

## Reporting a vulnerability

**Do not open a public GitHub issue for security vulnerabilities.**

Instead, report privately using one of the following:

- [GitHub Security Advisories](../../security/advisories/new) for this repository (preferred), or
- Email the maintainer directly (see the `authors` field in [composer.json](composer.json)).

Please include:

- A description of the vulnerability and its impact (e.g. unauthorized file write, path traversal, authorization bypass, environment-gate bypass).
- Steps to reproduce, including relevant `config/process-builder.php` values.
- The affected version/commit.

You should expect an initial response within a few days. We'll work with you to
understand and validate the issue, agree on a disclosure timeline, and credit
you in the release notes unless you prefer to stay anonymous.

## In scope

- Bypassing the environment allowlist or `manage-process-builder` authorization gate.
- Writing to files outside the package's managed-file boundaries (path traversal, symlink tricks, checksum bypass).
- Generating code that leads to arbitrary PHP execution outside the intended compiler output.
- Preview/confirmation token forgery or replay.

## Out of scope

- Issues that require the dashboard to already be exposed in production against the documented security guidance in the README.
- Denial of service against a locally running developer tool.
