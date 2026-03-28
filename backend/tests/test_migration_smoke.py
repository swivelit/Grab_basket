from __future__ import annotations

import os
import subprocess
import tempfile
import unittest
from pathlib import Path


class MigrationSmokeTests(unittest.TestCase):
    def test_alembic_upgrade_from_scratch(self):
        backend_dir = Path(__file__).resolve().parents[1]
        with tempfile.TemporaryDirectory() as tmp:
            db_path = Path(tmp) / "migration-smoke.db"
            env = os.environ.copy()
            env["DATABASE_URL"] = f"sqlite:///{db_path}"
            env.setdefault("APP_ENV", "development")
            command = ["alembic", "-c", str(backend_dir / "alembic.ini"), "upgrade", "head"]
            completed = subprocess.run(command, cwd=backend_dir, env=env, capture_output=True, text=True)
            if completed.returncode != 0:
                self.fail(f"alembic upgrade head failed\nstdout:\n{completed.stdout}\nstderr:\n{completed.stderr}")


if __name__ == "__main__":
    unittest.main()
