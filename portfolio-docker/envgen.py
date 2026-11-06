#!/usr/bin/env python3
"""Generate the repo-root `.env` from `.env.example` with random secrets.

Design: credentials are stored as PARTS (MYSQL_USER, MYSQL_PASSWORD,
MYSQL_DATABASE, REDIS_PASSWORD). The DSNs (DATABASE_URL, REDIS_URL,
MESSENGER_TRANSPORT_DSN) are DERIVED from those parts so they can never
drift out of sync with the credentials.

In dev, the host `.env` carries the real DSNs. In prod, Compose re-derives the
same DSNs from the same parts and injects them into the container (the baked
.env.dist has them empty, so Compose's values always win).

Usage: envgen.py <path-to-env-file>   (writes in place)
"""
import sys
import secrets


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
        out.append(line)

    open(path, "w", encoding="utf-8").write("\n".join(out) + "\n")
    print(f"[envgen] wrote {path} (random secrets + derived DSNs)")
    return 0


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(f"usage: {sys.argv[0]} <path-to-env-file>", file=sys.stderr)
        sys.exit(2)
    sys.exit(main(sys.argv[1]))
