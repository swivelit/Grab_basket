from __future__ import annotations

import threading
import time
from collections import defaultdict


class InProcessMetrics:
    def __init__(self) -> None:
        self._lock = threading.Lock()
        self._counters = defaultdict(float)
        self._timers = defaultdict(float)

    def incr(self, key: str, value: float = 1.0) -> None:
        with self._lock:
            self._counters[key] += float(value)

    def observe(self, key: str, value: float) -> None:
        with self._lock:
            self._timers[f"{key}.sum"] += float(value)
            self._timers[f"{key}.count"] += 1.0

    def snapshot(self) -> dict:
        with self._lock:
            return {
                "counters": dict(self._counters),
                "timers": dict(self._timers),
                "generated_at": int(time.time()),
            }


metrics = InProcessMetrics()
