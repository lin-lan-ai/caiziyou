#!/usr/bin/env python3
"""
Phase 1b - Database Unification: One-time user migration script.

Migrates users from the old caiziyou_db.users into caiziyou_community_db.users.
Idempotent: safe to run multiple times. Skips users whose username or email
already exists in the target database.

Usage:
    python3 scripts/migrate_users.py
"""

import os
import sys
import mysql.connector
from dotenv import load_dotenv

# Load .env so we can read DB credentials
load_dotenv(os.path.join(os.path.dirname(__file__), '..', '.env'))


# ---------------------------------------------------------------------------
# DB connection helpers
# ---------------------------------------------------------------------------

def _conn(config_key_prefix, fallback_user, fallback_pass, fallback_db):
    """Create a mysql.connector connection using env vars or fallback values."""
    return mysql.connector.connect(
        host=os.environ.get(f'{config_key_prefix}_HOST', 'localhost'),
        user=os.environ.get(f'{config_key_prefix}_USER', fallback_user),
        password=os.environ.get(f'{config_key_prefix}_PASS', fallback_pass),
        database=os.environ.get(f'{config_key_prefix}_NAME', fallback_db),
        charset='utf8mb4',
    )


def get_old_connection():
    """Connect to the legacy caiziyou_db."""
    return _conn('DB', 'caiziyou_user', 'CaiziYou@2026', 'caiziyou_db')


def get_new_connection():
    """Connect to the unified caiziyou_community_db."""
    return _conn('COMMUNITY_DB', 'caiziyou_community', 'Community@2026', 'caiziyou_community_db')


# ---------------------------------------------------------------------------
# Unique ID generation (mirrors PHP generateUniqueId())
# ---------------------------------------------------------------------------

def get_next_unique_id(cursor, prefix='CZ'):
    """Calculate the next CZ-prefixed unique_id."""
    cursor.execute(
        "SELECT MAX(CAST(SUBSTRING(unique_id, %s) AS UNSIGNED)) AS max_num "
        "FROM users WHERE unique_id LIKE %s",
        (len(prefix) + 1, prefix + '%')
    )
    row = cursor.fetchone()
    next_num = (row['max_num'] or 0) + 1
    return prefix + str(next_num).zfill(4)


# ---------------------------------------------------------------------------
# Role mapping
# ---------------------------------------------------------------------------

def map_role(old_role):
    """
    Map old DB role to community DB role.
    'admin' stays 'admin'; everything else becomes 'user'
    (the community DB role enum only accepts 'user' or 'admin').
    """
    if old_role == 'admin':
        return 'admin'
    return 'user'


# ---------------------------------------------------------------------------
# Main migration logic
# ---------------------------------------------------------------------------

def main():
    print("=" * 60)
    print("Phase 1b - User Migration")
    print("=" * 60)

    # 1. Connect to both databases
    try:
        old_conn = get_old_connection()
        print("[OK] Connected to caiziyou_db (source)")
    except mysql.connector.Error as e:
        print(f"[FAIL] Cannot connect to caiziyou_db: {e}", file=sys.stderr)
        sys.exit(1)

    try:
        new_conn = get_new_connection()
        print("[OK] Connected to caiziyou_community_db (target)")
    except mysql.connector.Error as e:
        old_conn.close()
        print(f"[FAIL] Cannot connect to caiziyou_community_db: {e}", file=sys.stderr)
        sys.exit(1)

    old_cursor = old_conn.cursor(dictionary=True)
    new_cursor = new_conn.cursor(dictionary=True)

    # 2. Read all users from old database
    old_cursor.execute("SELECT * FROM users")
    old_users = old_cursor.fetchall()
    print(f"\nFound {len(old_users)} user(s) in caiziyou_db.users.")

    migrated = 0
    skipped = 0
    errors = []

    for old_u in old_users:
        username = old_u['username']
        email = old_u['email']
        print(f"\n  Processing: {username} <{email}> ...", end=" ")

        # 3. Check for existing user by username or email
        new_cursor.execute(
            "SELECT id FROM users WHERE username = %s OR email = %s",
            (username, email)
        )
        existing = new_cursor.fetchone()

        if existing:
            print(f"SKIPPED (already exists as id={existing['id']})")
            skipped += 1
            continue

        # 4. Generate unique_id
        try:
            unique_id = get_next_unique_id(new_cursor, 'CZ')
        except Exception as e:
            print(f"ERROR generating unique_id: {e}")
            errors.append((username, str(e)))
            continue

        # 5. Determine nickname
        full_name = old_u.get('full_name') or ''
        nickname = full_name if full_name.strip() else username

        # 6. Map role
        role = map_role(old_u.get('role', 'user'))

        # 7. Build insert
        columns = [
            'username', 'email', 'password_hash',
            'nickname', 'avatar_url',
            'unique_id', 'role', 'status',
            'registration_status', 'full_name',
            'last_login', 'created_at',
        ]
        values = [
            username,
            email,
            old_u['password_hash'],
            nickname,
            old_u.get('avatar_url') or '/assets/images/default-avatar.png',
            unique_id,
            role,
            'active',       # migrated users are active by default
            'approved',     # migrated users skip re-approval
            full_name,
            old_u.get('last_login'),
            old_u.get('created_at') or None,
        ]

        # 8. Try to preserve original id if it is not taken
        old_id = old_u['id']
        new_cursor.execute("SELECT id FROM users WHERE id = %s", (old_id,))
        id_taken = new_cursor.fetchone()
        if not id_taken:
            # We can preserve the original id
            columns.insert(0, 'id')
            values.insert(0, old_id)

        placeholders = ', '.join(['%s'] * len(columns))
        col_names = ', '.join(columns)
        sql = f"INSERT INTO users ({col_names}) VALUES ({placeholders})"

        try:
            new_cursor.execute(sql, values)
            new_conn.commit()
            print(f"MIGRATED (id={new_cursor.lastrowid}, unique_id={unique_id})")
            migrated += 1
        except mysql.connector.Error as e:
            new_conn.rollback()
            print(f"ERROR: {e}")
            errors.append((username, str(e)))

    # 9. Summary
    print("\n" + "=" * 60)
    print("Migration Summary")
    print("=" * 60)
    print(f"  Total users in source DB:     {len(old_users)}")
    print(f"  Migrated:                     {migrated}")
    print(f"  Skipped (already exists):     {skipped}")
    if errors:
        print(f"  Errors:                       {len(errors)}")
        for user, err in errors:
            print(f"    - {user}: {err}")
    print("=" * 60)

    old_cursor.close()
    new_cursor.close()
    old_conn.close()
    new_conn.close()

    if errors:
        sys.exit(1)


if __name__ == '__main__':
    main()
