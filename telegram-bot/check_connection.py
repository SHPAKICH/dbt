#!/usr/bin/env python3
"""Проверка доступа бота к Telegram API через прокси (запускать на сервере)."""

import asyncio
import os
import sys
from pathlib import Path

from dotenv import load_dotenv
from telegram import Bot
from telegram.request import HTTPXRequest

from bot import normalize_proxy_for_httpx


async def main() -> int:
    load_dotenv(Path(__file__).resolve().parent / ".env")
    token = (os.getenv("TELEGRAM_BOT_TOKEN") or "").strip()
    proxy_raw = (os.getenv("TELEGRAM_PROXY") or "").strip() or None
    proxy = normalize_proxy_for_httpx(proxy_raw)

    if not token:
        print("FAIL: TELEGRAM_BOT_TOKEN не задан в .env")
        return 1

    print(f"Proxy: {proxy_raw or '(нет)'}" + (f" → httpx: {proxy}" if proxy_raw != proxy else ""))
    request = HTTPXRequest(
        proxy=proxy,
        connect_timeout=15,
        read_timeout=15,
        write_timeout=15,
        pool_timeout=10,
    )
    bot = Bot(token=token, request=request)

    try:
        me = await bot.get_me()
        print(f"OK getMe: @{me.username} (id={me.id})")
    except Exception as exc:
        print(f"FAIL getMe: {exc}")
        print("Проверьте: systemctl status xray, ss -tlnp | grep 1080, TELEGRAM_PROXY в .env")
        return 1

    try:
        info = await bot.get_webhook_info()
        if info.url:
            print(f"WARN: установлен webhook: {info.url}")
            print("Бот в режиме polling не получит сообщения, пока webhook активен.")
            print("Сброс: python -c \"...\" или перезапустите bot.py (он сбрасывает webhook).")
        else:
            print("OK webhook: не установлен (polling возможен)")
    except Exception as exc:
        print(f"WARN getWebhookInfo: {exc}")

    return 0


if __name__ == "__main__":
    raise SystemExit(asyncio.run(main()))
