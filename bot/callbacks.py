"""Validated, context-preserving callback payloads and inline keyboards."""
from __future__ import annotations

import re

_PREFIX = "spmb:v1:"
_ALLOWED = frozenset({"prev", "next", "reload", "menu", "stats", "list", "stage", "docs"})
_STAGES = frozenset({"1", "2", "3", "4", "5", "6", "7"})
_DOCS = frozenset({"complete", "incomplete"})


def callback_data(action: str, value: str = "") -> str:
    if action not in _ALLOWED:
        raise ValueError("unsupported callback")
    if action == "stage" and value not in _STAGES:
        raise ValueError("unsupported stage")
    if action == "docs" and value not in _DOCS:
        raise ValueError("unsupported document filter")
    if action in {"prev", "next", "reload", "menu", "stats", "list"} and value:
        raise ValueError("unexpected callback value")
    return _PREFIX + action + ((":" + value) if value else "")


def parse_callback_data(value: object) -> tuple[str, str | None] | None:
    if not isinstance(value, str) or len(value) > 32 or not value.startswith(_PREFIX):
        return None
    parts = value.split(":")
    if len(parts) not in {3, 4} or parts[:2] != ["spmb", "v1"]:
        return None
    action = parts[2]
    arg = parts[3] if len(parts) == 4 else None
    try:
        expected = callback_data(action, arg or "")
    except ValueError:
        return None
    return (action, arg) if expected == value else None


def build_inline_keyboard(*, page: int = 1, last_page: int | None = None) -> dict:
    rows = []
    if page > 1 or (last_page and last_page > 1):
        rows.append([
            {"text": "⬅️ Sebelumnya", "callback_data": callback_data("prev")},
            {"text": "🔄 Muat ulang", "callback_data": callback_data("reload")},
            {"text": "Berikutnya ➡️", "callback_data": callback_data("next")},
        ])
    rows.extend([
        [
            {"text": "📋 Daftar pendaftar", "callback_data": callback_data("list")},
            {"text": "📊 Statistik", "callback_data": callback_data("stats")},
        ],
        [
            {"text": "Tahap 1", "callback_data": callback_data("stage", "1")},
            {"text": "Tahap 2", "callback_data": callback_data("stage", "2")},
            {"text": "Tes online", "callback_data": callback_data("stage", "4")},
        ],
        [
            {"text": "✅ Berkas lengkap", "callback_data": callback_data("docs", "complete")},
            {"text": "⚠️ Berkas belum lengkap", "callback_data": callback_data("docs", "incomplete")},
        ],
        [{"text": "🏠 Menu", "callback_data": callback_data("menu")}],
    ])
    return {"inline_keyboard": rows}
