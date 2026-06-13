import json
import logging
import os
import sys
import urllib.error
import urllib.request
from pathlib import Path
from typing import Optional

from dotenv import load_dotenv
from telegram import Update
from telegram.request import HTTPXRequest
from telegram.ext import (
    Application,
    CommandHandler,
    ContextTypes,
    MessageHandler,
    filters,
)


logger = logging.getLogger(__name__)

BOT_DIR = Path(__file__).resolve().parent


def load_bot_env() -> None:
    """Загружает .env из каталога бота (systemd может стартовать с другим cwd)."""
    load_dotenv(BOT_DIR / ".env")


def get_env(name: str) -> str:
    value = os.getenv(name)
    if not value:
        raise RuntimeError(f"Environment variable {name} is not set")
    return value


def normalize_proxy_for_httpx(proxy: Optional[str]) -> Optional[str]:
    """httpx понимает socks5://, но не socks5h:// (для PHP/curl оставляем socks5h в .env)."""
    if not proxy:
        return None
    proxy = proxy.strip()
    if proxy.lower().startswith("socks5h://"):
        return "socks5://" + proxy[len("socks5h://") :]
    return proxy or None


def build_http_request() -> HTTPXRequest:
    """Таймауты и опциональный прокси — если сервер не доходит до api.telegram.org."""
    proxy = normalize_proxy_for_httpx((os.getenv("TELEGRAM_PROXY") or "").strip() or None)
    connect_timeout = float(os.getenv("TELEGRAM_CONNECT_TIMEOUT", "30"))
    read_timeout = float(os.getenv("TELEGRAM_READ_TIMEOUT", "30"))
    write_timeout = float(os.getenv("TELEGRAM_WRITE_TIMEOUT", "30"))
    pool_timeout = float(os.getenv("TELEGRAM_POOL_TIMEOUT", "5"))
    return HTTPXRequest(
        connection_pool_size=8,
        proxy=proxy,
        connect_timeout=connect_timeout,
        read_timeout=read_timeout,
        write_timeout=write_timeout,
        pool_timeout=pool_timeout,
    )


async def start(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    user = update.effective_user
    text = (
        "Привет! Я бот обратной связи DBT Hub.\n\n"
        "Отправь сюда сообщение, если нашёл ошибку в системе или хочешь предложить улучшение.\n\n"
        "Команды:\n"
        "/bug — сообщить об ошибке\n"
        "/idea — предложить улучшение\n"
        "/link КОД — привязать Telegram для сброса пароля (код в настройках DBT Hub)\n"
        "/cancel — отменить ввод заявки"
    )
    await update.message.reply_text(text)
    logger.info("User %s (%s) called /start", user.id if user else "?", user.username if user else "?")


async def bug(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    context.user_data["feedback_mode"] = "bug"
    await update.message.reply_text(
        "Окей, опиши, пожалуйста, ошибку одним-двумя сообщениями. "
        "Можно приложить скриншот. После отправки я передам это разработчику."
    )


async def idea(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    context.user_data["feedback_mode"] = "idea"
    await update.message.reply_text(
        "Отлично! Напиши свою идею или предложение по улучшению. "
        "После отправки я передам её разработчику."
    )


def get_api_base_url() -> str:
    """Базовый URL API. HTTP→HTTPS редирект на nginx превращает POST в GET и даёт 405."""
    api_base = (os.getenv("API_BASE_URL") or "").strip().rstrip("/")
    if not api_base:
        return ""
    if api_base.lower().startswith("http://"):
        logger.warning(
            "API_BASE_URL=%s — используйте https://, иначе редирект ломает POST-запросы",
            api_base,
        )
        api_base = "https://" + api_base[7:]
    return api_base


def confirm_telegram_link(code: str, chat_id: int) -> tuple[bool, str]:
    api_base = get_api_base_url()
    secret = (os.getenv("TELEGRAM_WEBHOOK_SECRET") or "").strip()
    if not api_base or not secret:
        return False, "Привязка не настроена на сервере (API_BASE_URL / TELEGRAM_WEBHOOK_SECRET)."

    url = f"{api_base}/api/v1/telegram/confirm-link"
    payload = json.dumps({"code": code, "chatId": chat_id, "secret": secret}).encode("utf-8")
    req = urllib.request.Request(
        url,
        data=payload,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
            data = json.loads(resp.read().decode("utf-8"))
    except urllib.error.HTTPError as e:
        try:
            data = json.loads(e.read().decode("utf-8"))
        except Exception:
            if e.code == 405:
                return False, (
                    "Ошибка связи с сервером (неверный HTTP-метод). "
                    "Проверьте, что API_BASE_URL начинается с https://"
                )
            return False, "Сервер отклонил запрос. Проверьте код и попробуйте снова."
        message = data.get("message") or "Не удалось привязать аккаунт."
        if e.code == 405 and "Method Not Allowed" in message:
            message = (
                "Ошибка связи с сервером. Укажите API_BASE_URL=https://... в .env бота "
                "(http:// ломает POST при редиректе на HTTPS)."
            )
        return False, message
    except Exception:
        logger.exception("confirm-link request failed")
        return False, "Не удалось связаться с сервером DBT Hub."

    if data.get("success"):
        return True, data.get("message") or "Telegram привязан к аккаунту DBT Hub."
    return False, data.get("message") or "Не удалось привязать аккаунт."


async def link_account(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    message = update.effective_message
    if not message:
        return

    args = context.args or []
    if not args:
        await message.reply_text(
            "Отправьте команду с кодом из настроек DBT Hub:\n"
            "/link ABCDEF\n\n"
            "Код можно получить в разделе «Настройки» → «Привязка Telegram»."
        )
        return

    code = args[0].strip().upper()
    chat = update.effective_chat
    if not chat:
        await message.reply_text("Не удалось определить чат.")
        return

    ok, text = confirm_telegram_link(code, chat.id)
    await message.reply_text(text)


async def cancel(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    if "feedback_mode" in context.user_data:
        context.user_data.pop("feedback_mode", None)
        await update.message.reply_text("Отменил текущую заявку. Можно начать заново.")
    else:
        await update.message.reply_text("Сейчас нет активной заявки.")


def build_feedback_header(update: Update, feedback_type: Optional[str]) -> str:
    user = update.effective_user
    chat = update.effective_chat

    if feedback_type == "bug":
        prefix = "🐞 *Новая ошибка*"
    elif feedback_type == "idea":
        prefix = "💡 *Новая идея*"
    else:
        prefix = "📨 *Новое сообщение*"

    username = f"@{user.username}" if user and user.username else "(без username)"
    full_name = user.full_name if user else "Неизвестно"
    chat_title = chat.title if chat and chat.title else ""

    header_lines = [
        prefix,
        "",
        f"От: *{full_name}* {username}",
        f"Telegram ID: `{user.id if user else 'unknown'}`",
    ]
    if chat_title:
        header_lines.append(f"Чат: {chat_title}")

    return "\n".join(header_lines)


async def handle_message(update: Update, context: ContextTypes.DEFAULT_TYPE) -> None:
    message = update.effective_message
    if not message:
        return

    feedback_type = context.user_data.pop("feedback_mode", None)
    header = build_feedback_header(update, feedback_type)

    admin_chat_id_raw = (os.getenv("TELEGRAM_ADMIN_CHAT_ID") or "").strip()
    if admin_chat_id_raw and admin_chat_id_raw != "123456789":
        try:
            admin_chat_id = int(admin_chat_id_raw)
            if message.text or message.caption:
                text = message.text or message.caption or ""
                body = f"{header}\n\n*Текст:*\n{text}"
                await context.bot.send_message(
                    chat_id=admin_chat_id,
                    text=body,
                    parse_mode="Markdown",
                )
            else:
                await context.bot.send_message(
                    chat_id=admin_chat_id,
                    text=header,
                    parse_mode="Markdown",
                )

            if any(
                [
                    message.photo,
                    message.document,
                    message.video,
                    message.voice,
                    message.audio,
                    message.sticker,
                ]
            ):
                await message.copy(chat_id=admin_chat_id)
        except Exception:
            logger.exception("Failed to forward feedback to admin chat %s", admin_chat_id_raw)
    else:
        logger.warning("TELEGRAM_ADMIN_CHAT_ID не настроен — обратная связь только логируется")

    if feedback_type == "bug":
        reply = "Спасибо! Ошибка зафиксирована и отправлена разработчику."
    elif feedback_type == "idea":
        reply = "Спасибо за идею! Я передал её разработчику."
    else:
        reply = "Спасибо! Сообщение отправлено разработчику."

    await message.reply_text(reply)


async def error_handler(update: object, context: ContextTypes.DEFAULT_TYPE) -> None:
    logger.error("Exception while handling update %s", update, exc_info=context.error)


async def post_init(application: Application) -> None:
    proxy = (os.getenv("TELEGRAM_PROXY") or "").strip()
    logger.info("Telegram proxy: %s", proxy or "(direct)")
    info = await application.bot.get_webhook_info()
    if info.url:
        logger.warning("Active webhook %s — removing for polling mode", info.url)
        await application.bot.delete_webhook(drop_pending_updates=True)
    me = await application.bot.get_me()
    logger.info("Bot ready: @%s", me.username)


def main() -> None:
    load_bot_env()

    token = get_env("TELEGRAM_BOT_TOKEN")

    logging.basicConfig(
        format="%(asctime)s - %(name)s - %(levelname)s - %(message)s",
        level=logging.INFO,
    )

    application = (
        Application.builder()
        .token(token)
        .request(build_http_request())
        .post_init(post_init)
        .build()
    )

    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("bug", bug))
    application.add_handler(CommandHandler("idea", idea))
    application.add_handler(CommandHandler("cancel", cancel))
    application.add_handler(CommandHandler("link", link_account))

    application.add_handler(
        MessageHandler(
            (
                (filters.TEXT & ~filters.COMMAND)
                | filters.PHOTO
                | filters.Document.ALL
                | filters.VIDEO
                | filters.VOICE
                | filters.AUDIO
                | filters.Sticker.ALL
            ),
            handle_message,
        )
    )

    application.add_error_handler(error_handler)

    logger.info("Starting feedback bot...")
    application.run_polling(
        allowed_updates=Update.ALL_TYPES,
        drop_pending_updates=True,
        timeout=20,
        bootstrap_retries=-1,
    )


if __name__ == "__main__":
    try:
        main()
    except Exception:
        logging.basicConfig(level=logging.INFO)
        logging.exception("Bot failed to start")
        sys.exit(1)

