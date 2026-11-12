#!/usr/bin/env python3
"""Generate the repo-root `.env` from `.env.example` with random dev secrets.

DEV MODE ONLY: `make init` always produces a development environment
(APP_ENV=dev, APP_DEBUG=1, Mailpit mailer, local n8n). Production values are
injected by docker-compose.prod.yml at runtime — the host .env is never used
directly in prod (the baked .env.dist + Compose's `environment:` block are).

Design: credentials are stored as PARTS (MYSQL_USER, MYSQL_PASSWORD,
MYSQL_DATABASE, REDIS_PASSWORD). The DSNs (DATABASE_URL, REDIS_URL,
MESSENGER_TRANSPORT_DSN) are DERIVED from those parts so they can never drift
out of sync with the credentials.

Usage: envgen.py <path-to-env-file>   (writes in place)
"""
import sys
import secrets

# Dev-mode defaults (overridden regardless of what .env.example says).
DEV_DEFAULTS = {
    "APP_ENV": "dev",
    "APP_DEBUG": "1",
    "XDEBUG_MODE": "off",
    "MAILER_DSN": "smtp://mailpit:1025",
    # Local n8n on the host (dev compose adds host.docker.internal:host-gateway).
    "N8N_BASE_URL": "http://host.docker.internal:5678",
    # Anonymization: no hardcoded contact email — set CONTACT_TO_EMAIL in .env.
    "CONTACT_TO_EMAIL": "",
}


def main(path: str) -> int:
    lines = open(path, encoding="utf-8").read().splitlines()

    def get(key: str) -> str:
        for line in lines:
            if line.startswith(key + "="):
                return line.split("=", 1)[1]
        return ""

    mysql_user = get("MYSQL_USER") or "portfolio"
    mysql_db = get("MYSQL_DATABASE") or "portfolio"
    mysql_password = secrets.token_hex(16)
    mysql_root_password = secrets.token_hex(16)
    redis_password = secrets.token_hex(16)
    app_secret = secrets.token_hex(32)

    out = []
    for line in lines:
        if line.startswith("MYSQL_PASSWORD="):
            line = "MYSQL_PASSWORD=" + mysql_password
        elif line.startswith("MYSQL_ROOT_PASSWORD="):
            line = "MYSQL_ROOT_PASSWORD=" + mysql_root_password
        elif line.startswith("REDIS_PASSWORD="):
            line = "REDIS_PASSWORD=" + redis_password
        elif line.startswith("APP_SECRET="):
            line = "APP_SECRET=" + app_secret
        elif line.startswith("DATABASE_URL="):
            line = (
                f"DATABASE_URL=mysql://{mysql_user}:{mysql_password}"
                f"@mysql:3306/{mysql_db}?serverVersion=8.4&charset=utf8mb4"
            )
        elif line.startswith("REDIS_URL="):
            line = "REDIS_URL=redis://:" + redis_password + "@redis:6379"
        elif line.startswith("MESSENGER_TRANSPORT_DSN="):
            line = "MESSENGER_TRANSPORT_DSN=redis://:" + redis_password + "@redis:6379/messages"
        else:
            # Force dev-mode defaults for the well-known keys.
            for key, value in DEV_DEFAULTS.items():
                if line.startswith(key + "="):
                    line = key + "=" + value
                    break
        out.append(line)

    open(path, "w", encoding="utf-8").write("\n".join(out) + "\n")
    print(f"[envgen] wrote {path} (DEV mode: random secrets + derived DSNs)")
    return 0


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(f"usage: {sys.argv[0]} <path-to-env-file>", file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1]))
